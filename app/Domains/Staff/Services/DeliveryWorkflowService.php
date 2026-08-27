<?php

namespace App\Domains\Staff\Services;

use App\Models\CustomerAddress;
use App\Models\DeliveryAttempt;
use App\Models\DeliveryEvent;
use App\Models\DeliveryOtp;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderStaffAssignment;
use App\Models\StaffApprovalRequest;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class DeliveryWorkflowService
{
    private const MAX_FAILED_OTP_ATTEMPTS = 5;

    public function __construct(
        private readonly StaffPermissionService $permissions,
        private readonly StaffNotificationService $notifications
    ) {}

    public function startDelivery(OrderStaffAssignment $assignment, User $actor): DeliveryAttempt
    {
        if ($assignment->task_type !== OrderStaffAssignment::TASK_DELIVERY || ! $this->permissions->has($actor, 'delivery.start')) {
            throw new InvalidArgumentException('You are not authorized to start delivery.');
        }

        return DB::transaction(function () use ($assignment, $actor) {
            $locked = OrderStaffAssignment::query()->with('order.customer')->lockForUpdate()->findOrFail($assignment->id);

            if ((int) $locked->assigned_user_id !== (int) $actor->id) {
                throw new InvalidArgumentException('Delivery is assigned to another employee.');
            }

            $attemptNumber = ((int) DeliveryAttempt::query()->where('order_id', $locked->order_id)->max('attempt_number')) + 1;
            $attempt = DeliveryAttempt::query()->create([
                'order_id' => $locked->order_id,
                'order_staff_assignment_id' => $locked->id,
                'stock_location_id' => $locked->stock_location_id,
                'delivery_agent_id' => $actor->id,
                'attempt_number' => $attemptNumber,
                'status' => 'OUT_FOR_DELIVERY',
                'started_at' => now(),
            ]);

            $locked->update([
                'started_by' => $actor->id,
                'started_at' => $locked->started_at ?: now(),
                'status' => 'IN_PROGRESS',
            ]);
            $locked->order->update(['order_status' => 'out_for_delivery']);

            $this->generateOtp($attempt);
            $this->recordEvent($locked->order, $attempt, $actor, 'OUT_FOR_DELIVERY');

            return $attempt;
        });
    }

    public function generateOtp(DeliveryAttempt $attempt): string
    {
        $otp = (string) random_int(100000, 999999);

        DeliveryOtp::query()
            ->where('delivery_attempt_id', $attempt->id)
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->update(['invalidated_at' => now()]);

        $credential = DeliveryOtp::query()->create([
            'order_id' => $attempt->order_id,
            'delivery_attempt_id' => $attempt->id,
            'otp_hash' => Hash::make($otp),
            'otp_ciphertext' => Crypt::encryptString($otp),
            'generated_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $order = $attempt->order()->with('customer')->first();

        if ($order?->customer_id) {
            Notification::query()->create([
                'audience' => Notification::AUDIENCE_CUSTOMER,
                'customer_id' => $order->customer_id,
                'type' => 'delivery_otp',
                'title' => 'Delivery OTP for Order '.$order->order_number,
                'message' => 'Share this OTP only after you receive your order.',
                'action_url' => route('customer.orders.show', $order->order_number),
                'data' => ['delivery_otp_id' => $credential->id],
            ]);
        }

        return $otp;
    }

    public function verifyOtp(DeliveryAttempt $attempt, User $actor, string $otp, array $location = []): DeliveryEvent
    {
        if (! $this->permissions->has($actor, 'delivery.mark_delivered') || (int) $attempt->delivery_agent_id !== (int) $actor->id) {
            throw new InvalidArgumentException('You are not authorized to complete this delivery.');
        }

        $result = DB::transaction(function () use ($attempt, $actor, $otp, $location) {
            $credential = DeliveryOtp::query()
                ->where('delivery_attempt_id', $attempt->id)
                ->whereNull('used_at')
                ->whereNull('invalidated_at')
                ->lockForUpdate()
                ->firstOrFail();

            if ($credential->expires_at->isPast()) {
                $credential->update(['invalidated_at' => now()]);

                return 'Delivery OTP has expired.';
            }

            if ($credential->failed_attempt_count >= self::MAX_FAILED_OTP_ATTEMPTS) {
                return 'Too many invalid delivery OTP attempts. Request delivery override or reschedule.';
            }

            if (! Hash::check($otp, $credential->otp_hash)) {
                $credential->increment('failed_attempt_count');
                $credential->refresh();

                if ($credential->failed_attempt_count >= self::MAX_FAILED_OTP_ATTEMPTS) {
                    $credential->update(['invalidated_at' => now()]);

                    return 'Too many invalid delivery OTP attempts. Request delivery override or reschedule.';
                }

                return 'Invalid delivery OTP.';
            }

            $credential->update(['used_at' => now(), 'invalidated_at' => now()]);
            $attempt->update(['status' => 'DELIVERED', 'completed_at' => now()]);
            $attempt->order->update(['order_status' => 'delivered', 'delivered_at' => now()]);
            $attempt->assignment?->update(['status' => 'COMPLETED', 'completed_by' => $actor->id, 'completed_at' => now()]);

            return $this->recordEvent($attempt->order, $attempt, $actor, 'DELIVERED', $location + ['otp_verified' => true]);
        });

        if ($result instanceof DeliveryEvent) {
            return $result;
        }

        throw new InvalidArgumentException($result);
    }

    public function recordFailure(DeliveryAttempt $attempt, User $actor, string $eventType, string $reason, ?string $notes = null, array $location = []): DeliveryEvent
    {
        if (! $this->permissions->has($actor, 'delivery.mark_failed') && ! $this->permissions->has($actor, 'delivery.mark_customer_unavailable')) {
            throw new InvalidArgumentException('You are not authorized to record delivery exception.');
        }

        $attempt->update(['status' => $eventType]);
        $this->invalidateOtps($attempt);

        return $this->recordEvent($attempt->order, $attempt, $actor, $eventType, $location + [
            'reason_code' => $reason,
            'notes' => $notes,
        ]);
    }

    public function requestApproval(DeliveryAttempt $attempt, User $actor, string $type, string $reason, ?string $notes = null, array $evidence = []): StaffApprovalRequest
    {
        $permission = $type === 'RETURN_TO_STORE' ? 'delivery.request_return_to_store' : 'delivery.request_override';

        if (! $this->permissions->has($actor, $permission)) {
            throw new InvalidArgumentException('You are not authorized to request this approval.');
        }

        $approval = StaffApprovalRequest::query()->create([
            'stock_location_id' => $attempt->stock_location_id,
            'approval_type' => $type,
            'subject_type' => $attempt::class,
            'subject_id' => $attempt->id,
            'requested_by' => $actor->id,
            'requested_at' => now(),
            'status' => 'PENDING',
            'reason_code' => $reason,
            'notes' => $notes,
            'evidence' => $evidence ?: null,
        ]);

        $attempt->update(['status' => $type === 'RETURN_TO_STORE' ? 'RETURN_TO_STORE_PENDING' : 'OVERRIDE_PENDING']);
        $this->invalidateOtps($attempt);

        return $approval;
    }

    public function decideApproval(StaffApprovalRequest $approval, User $checker, bool $approved): StaffApprovalRequest
    {
        $required = $approval->approval_type === 'RETURN_TO_STORE'
            ? 'approvals.return_to_store'
            : 'approvals.delivery_override';

        if (! $this->permissions->has($checker, $required) || ! $this->permissions->canAccessStore($checker, $approval->stock_location_id)) {
            throw new InvalidArgumentException('You are not authorized to decide this approval.');
        }

        if ((int) $approval->requested_by === (int) $checker->id) {
            throw new InvalidArgumentException('Maker cannot approve their own request.');
        }

        return DB::transaction(function () use ($approval, $checker, $approved) {
            $locked = StaffApprovalRequest::query()->lockForUpdate()->findOrFail($approval->id);

            if ($locked->status !== 'PENDING') {
                throw new InvalidArgumentException('Approval request is already decided.');
            }

            $locked->update([
                'status' => $approved ? 'APPROVED' : 'REJECTED',
                'checked_by' => $checker->id,
                'checked_at' => now(),
            ]);

            $attempt = $locked->subject;
            if ($attempt instanceof DeliveryAttempt) {
                $attempt->update(['status' => $this->approvalAttemptStatus($locked->approval_type, $approved)]);

                if ($locked->approval_type === 'DELIVERY_OTP_OVERRIDE' && $approved) {
                    $attempt->order->update(['order_status' => 'delivered', 'delivered_at' => now()]);
                    $this->recordEvent($attempt->order, $attempt, $checker, 'DELIVERED_OTP_OVERRIDE_APPROVED', [
                        'otp_override_approved' => true,
                        'override_approved_by' => $checker->id,
                    ]);
                }
            }

            return $locked->fresh();
        });
    }

    public function recordEvent(Order $order, ?DeliveryAttempt $attempt, ?User $actor, string $eventType, array $data = []): DeliveryEvent
    {
        $location = $this->locationEvidence($order, $data);

        return DeliveryEvent::query()->create([
            'order_id' => $order->id,
            'delivery_attempt_id' => $attempt?->id,
            'stock_location_id' => $order->stock_location_id,
            'actor_user_id' => $actor?->id,
            'event_type' => $eventType,
            'occurred_at' => now(),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'accuracy_meters' => $data['accuracy_meters'] ?? null,
            'distance_from_customer_meters' => $location['distance'],
            'geofence_result' => $location['geofence_result'],
            'reason_code' => $data['reason_code'] ?? null,
            'notes' => $data['notes'] ?? null,
            'otp_verified' => (bool) ($data['otp_verified'] ?? false),
            'otp_override_approved' => (bool) ($data['otp_override_approved'] ?? false),
            'override_approved_by' => $data['override_approved_by'] ?? null,
            'manager_review_required' => $location['geofence_result'] === 'OUTSIDE',
            'review_status' => $location['geofence_result'] === 'OUTSIDE' ? 'PENDING' : null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function invalidateOtps(DeliveryAttempt $attempt): void
    {
        DeliveryOtp::query()
            ->where('delivery_attempt_id', $attempt->id)
            ->whereNull('invalidated_at')
            ->update(['invalidated_at' => now()]);
    }

    private function locationEvidence(Order $order, array $data): array
    {
        if (! isset($data['latitude'], $data['longitude'])) {
            return ['distance' => null, 'geofence_result' => 'UNAVAILABLE'];
        }

        $address = $order->customer_id
            ? CustomerAddress::query()
                ->where('customer_id', $order->customer_id)
                ->where('is_default', true)
                ->where('is_approved', true)
                ->first()
            : null;

        if (! $address?->latitude || ! $address?->longitude) {
            return ['distance' => null, 'geofence_result' => 'NO_CUSTOMER_PIN'];
        }

        $distance = $this->distanceMeters((float) $address->latitude, (float) $address->longitude, (float) $data['latitude'], (float) $data['longitude']);
        $radius = (float) ($address->geofence_radius_meters ?: 150);

        return [
            'distance' => round($distance, 2),
            'geofence_result' => $distance <= $radius ? 'INSIDE' : 'OUTSIDE',
        ];
    }

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function approvalAttemptStatus(string $type, bool $approved): string
    {
        if ($type === 'RETURN_TO_STORE') {
            return $approved ? 'RETURNED_TO_STORE_CONFIRMED' : 'RETURNED_TO_STORE_REJECTED';
        }

        return $approved ? 'DELIVERED_OTP_OVERRIDE_APPROVED' : 'OTP_OVERRIDE_REJECTED';
    }
}
