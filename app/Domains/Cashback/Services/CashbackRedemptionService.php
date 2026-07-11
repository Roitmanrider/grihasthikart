<?php

namespace App\Domains\Cashback\Services;

use App\Domains\Coupon\Services\CouponService;
use App\Domains\Notification\Services\NotificationService;
use App\Models\CashbackRedemptionRequest;
use App\Models\Coupon;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CashbackRedemptionService
{
    public function __construct(
        private readonly CashbackService $cashbackService,
        private readonly CouponService $couponService,
        private readonly NotificationService $notificationService
    ) {}

    public function request(Customer $customer, float $amount, ?string $note = null): CashbackRedemptionRequest
    {
        $rule = $this->cashbackService->defaultRule();
        $this->validateAmount($customer, $amount, (float) $rule->redemption_multiple);

        $redemption = CashbackRedemptionRequest::query()->create([
            'customer_id' => $customer->id,
            'requested_amount' => $amount,
            'status' => 'pending',
            'customer_note' => $note,
            'requested_at' => now(),
        ]);

        $this->notificationService->notifyAdminCashbackRedemptionRequested($redemption);

        return $redemption;
    }

    public function approve(CashbackRedemptionRequest $request, float $amount, ?string $note = null): CashbackRedemptionRequest
    {
        return DB::transaction(function () use ($request, $amount, $note) {
            $request = CashbackRedemptionRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($request->status !== 'pending') {
                throw new InvalidArgumentException('Only pending requests can be approved.');
            }

            $rule = $this->cashbackService->defaultRule();
            $this->validateAmount($request->customer, $amount, (float) $rule->redemption_multiple);

            $request->update([
                'approved_amount' => $amount,
                'status' => 'approved',
                'admin_note' => $note,
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);

            $this->notificationService->notifyCustomerCashbackUpdated(
                $request->fresh('customer'),
                'Cashback redemption approved',
                'Your cashback redemption request for Rs. '.number_format($amount, 2).' was approved.'
            );

            return $request;
        });
    }

    public function reject(CashbackRedemptionRequest $request, string $note): CashbackRedemptionRequest
    {
        $request->update([
            'status' => 'rejected',
            'admin_note' => $note,
            'rejected_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        $this->notificationService->notifyCustomerCashbackUpdated(
            $request->fresh('customer'),
            'Cashback redemption rejected',
            'Your cashback redemption request was rejected. Reason: '.$note
        );

        return $request;
    }

    public function generateCoupon(CashbackRedemptionRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $request = CashbackRedemptionRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($request->coupon_id) {
                throw new InvalidArgumentException('Coupon has already been generated.');
            }

            if ($request->status !== 'approved') {
                throw new InvalidArgumentException('Only approved requests can generate coupons.');
            }

            $amount = (float) ($request->approved_amount ?: $request->requested_amount);
            $this->validateAmount($request->customer, $amount, (float) $this->cashbackService->defaultRule()->redemption_multiple);

            $coupon = $this->couponService->create([
                'code' => $this->generateCouponCode(),
                'name' => 'Cashback Redemption',
                'description' => 'Cashback coupon generated for redemption request #'.$request->id,
                'discount_type' => 'fixed',
                'discount_value' => $amount,
                'max_discount_amount' => null,
                'minimum_order_amount' => 0,
                'usage_limit_total' => 1,
                'usage_limit_per_customer' => 1,
                'usage_limit_per_session' => null,
                'customer_id' => $request->customer_id,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
                'status' => true,
                'is_cashback_coupon' => true,
                'source' => 'cashback',
            ]);

            $request->update([
                'status' => 'coupon_generated',
                'coupon_id' => $coupon->id,
                'coupon_generated_at' => now(),
            ]);

            $this->cashbackService->writeLedger(
                $request->customer,
                'redeemed',
                $amount,
                couponId: $coupon->id,
                redemptionRequestId: $request->id,
                description: 'Cashback redeemed into coupon '.$coupon->code
            );

            $this->notificationService->notifyCustomerCashbackUpdated(
                $request->fresh('customer'),
                'Cashback coupon generated',
                'Your cashback coupon '.$coupon->code.' is ready to use.'
            );

            return $coupon;
        });
    }

    private function validateAmount(Customer $customer, float $amount, float $multiple): void
    {
        if ($amount < $multiple) {
            throw new InvalidArgumentException('Cashback redemption must be at least Rs. '.number_format($multiple, 2).'.');
        }

        if (fmod($amount, $multiple) !== 0.0) {
            throw new InvalidArgumentException('Cashback redemption must be in multiples of Rs. '.number_format($multiple, 2).'.');
        }

        if ($amount > $this->cashbackService->availableBalance($customer)) {
            throw new InvalidArgumentException('Requested amount exceeds available cashback balance.');
        }
    }

    private function generateCouponCode(): string
    {
        do {
            $code = 'GKCB-'.now()->format('Ym').'-'.Str::upper(Str::random(5));
        } while (Coupon::query()->where('code', $code)->exists());

        return $code;
    }
}
