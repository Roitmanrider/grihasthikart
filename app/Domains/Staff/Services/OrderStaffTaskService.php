<?php

namespace App\Domains\Staff\Services;

use App\Models\Order;
use App\Models\OrderStaffAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderStaffTaskService
{
    public function __construct(
        private readonly StaffPermissionService $permissions,
        private readonly StaffNotificationService $notifications
    ) {}

    public function assign(Order $order, string $taskType, User $assignee, User $actor): OrderStaffAssignment
    {
        $requiredPermission = match ($taskType) {
            OrderStaffAssignment::TASK_PICKING => 'picking.start',
            OrderStaffAssignment::TASK_PACKING => 'packing.start',
            OrderStaffAssignment::TASK_DELIVERY => 'delivery.start',
            default => throw new InvalidArgumentException('Unsupported task type.'),
        };

        if (! $this->permissions->has($assignee, $requiredPermission) || ! $this->permissions->canAccessStore($assignee, $order->stock_location_id)) {
            throw new InvalidArgumentException('Selected employee is not eligible for this task.');
        }

        return DB::transaction(function () use ($order, $taskType, $assignee, $actor) {
            $assignment = OrderStaffAssignment::query()
                ->where('order_id', $order->id)
                ->where('task_type', $taskType)
                ->lockForUpdate()
                ->first();

            $data = [
                'order_id' => $order->id,
                'stock_location_id' => $order->stock_location_id,
                'task_type' => $taskType,
                'assigned_user_id' => $assignee->id,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'status' => 'ASSIGNED',
            ];

            if ($assignment) {
                $data['reassigned_from_user_id'] = $assignment->assigned_user_id;
                $data['reassigned_by'] = $actor->id;
                $data['reassigned_at'] = now();
                $assignment->update($data);
            } else {
                $assignment = OrderStaffAssignment::query()->create($data);
            }

            $this->notifications->notify(
                $assignee,
                strtolower($taskType),
                'task_assigned',
                str($taskType)->headline().' assigned',
                'Order '.$order->order_number.' is assigned to you.',
                $assignment,
                $order->stock_location_id
            );

            return $assignment->fresh();
        });
    }

    public function start(OrderStaffAssignment $assignment, User $actor): OrderStaffAssignment
    {
        $permission = strtolower($assignment->task_type).'.start';

        if (! $this->permissions->has($actor, $permission) || ! $this->permissions->canAccessStore($actor, $assignment->stock_location_id)) {
            throw new InvalidArgumentException('You are not authorized to start this task.');
        }

        return DB::transaction(function () use ($assignment, $actor) {
            $locked = OrderStaffAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            if ($locked->status !== 'ASSIGNED') {
                throw new InvalidArgumentException('Task is not available to start.');
            }

            if ($locked->assigned_user_id && (int) $locked->assigned_user_id !== (int) $actor->id) {
                throw new InvalidArgumentException('Task is assigned to another employee.');
            }

            $locked->update([
                'assigned_user_id' => $locked->assigned_user_id ?: $actor->id,
                'started_by' => $actor->id,
                'started_at' => now(),
                'status' => 'IN_PROGRESS',
            ]);

            return $locked->fresh();
        });
    }

    public function complete(OrderStaffAssignment $assignment, User $actor): OrderStaffAssignment
    {
        $permission = strtolower($assignment->task_type).'.complete';

        if (! $this->permissions->has($actor, $permission) || ! $this->permissions->canAccessStore($actor, $assignment->stock_location_id)) {
            throw new InvalidArgumentException('You are not authorized to complete this task.');
        }

        return DB::transaction(function () use ($assignment, $actor) {
            $locked = OrderStaffAssignment::query()->with('order')->lockForUpdate()->findOrFail($assignment->id);

            if (! in_array($locked->status, ['ASSIGNED', 'IN_PROGRESS'], true)) {
                throw new InvalidArgumentException('Task is not completable.');
            }

            if ($locked->assigned_user_id && (int) $locked->assigned_user_id !== (int) $actor->id) {
                throw new InvalidArgumentException('Task is assigned to another employee.');
            }

            $locked->update([
                'assigned_user_id' => $locked->assigned_user_id ?: $actor->id,
                'completed_by' => $actor->id,
                'completed_at' => now(),
                'status' => 'COMPLETED',
            ]);

            $newStatus = match ($locked->task_type) {
                OrderStaffAssignment::TASK_PICKING => 'preparing',
                OrderStaffAssignment::TASK_PACKING => 'packed',
                default => null,
            };

            if ($newStatus) {
                $locked->order->update(['order_status' => $newStatus]);
            }

            return $locked->fresh();
        });
    }
}
