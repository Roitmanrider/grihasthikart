<?php

namespace App\Http\Controllers\Staff;

use App\Domains\Staff\Services\DeliveryWorkflowService;
use App\Domains\Staff\Services\OrderStaffTaskService;
use App\Domains\Staff\Services\StaffNotificationService;
use App\Domains\Staff\Services\StaffPermissionService;
use App\Http\Controllers\Controller;
use App\Models\DeliveryAttempt;
use App\Models\Order;
use App\Models\OrderStaffAssignment;
use App\Models\StaffApprovalRequest;
use App\Models\StaffNotification;
use App\Models\User;
use Illuminate\Http\Request;
use InvalidArgumentException;

class StaffPortalController extends Controller
{
    public function __construct(
        private readonly StaffPermissionService $permissions,
        private readonly StaffNotificationService $notifications
    ) {}

    public function dashboard(Request $request)
    {
        $this->authorizeStaff($request);
        $user = $request->user();
        $counts = $this->notifications->unreadCounts($user);
        $tasks = OrderStaffAssignment::query()
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', ['ASSIGNED', 'IN_PROGRESS'])
            ->count();
        $approvals = StaffApprovalRequest::query()
            ->where('status', 'PENDING')
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('stock_location_id', $user->assigned_store_id))
            ->where('requested_by', '!=', $user->id)
            ->count();

        return view('staff.dashboard', compact('counts', 'tasks', 'approvals'));
    }

    public function notifications(Request $request)
    {
        $this->authorizeStaff($request);
        $notifications = StaffNotification::query()
            ->where('recipient_user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('staff.notifications.index', compact('notifications'));
    }

    public function readNotification(StaffNotification $notification, Request $request)
    {
        $this->authorizeStaff($request);
        abort_unless((int) $notification->recipient_user_id === (int) $request->user()->id, 404);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function readWorkstream(Request $request)
    {
        $this->authorizeStaff($request);
        $data = $request->validate(['workstream' => ['nullable', 'string', 'max:40']]);
        $this->notifications->markAllRead($request->user(), $data['workstream'] ?? null);

        return back()->with('success', 'Notifications marked as read.');
    }

    public function picking(Request $request)
    {
        return $this->taskQueue($request, OrderStaffAssignment::TASK_PICKING, 'picking.view', 'staff.tasks.picking');
    }

    public function packing(Request $request)
    {
        return $this->taskQueue($request, OrderStaffAssignment::TASK_PACKING, 'packing.view', 'staff.tasks.packing');
    }

    public function deliveries(Request $request)
    {
        $this->authorizeStaff($request, 'delivery.view');
        $user = $request->user();
        $attempts = DeliveryAttempt::query()
            ->with(['order', 'assignment'])
            ->where('delivery_agent_id', $user->id)
            ->whereIn('status', ['ASSIGNED', 'OUT_FOR_DELIVERY', 'OVERRIDE_PENDING', 'RETURN_TO_STORE_PENDING'])
            ->latest()
            ->paginate(20);

        return view('staff.delivery.index', compact('attempts'));
    }

    public function assign(Request $request, OrderStaffTaskService $tasks)
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'task_type' => ['required', 'in:PICKING,PACKING,DELIVERY'],
            'assigned_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);
        $this->authorizeStaff($request, match ($data['task_type']) {
            OrderStaffAssignment::TASK_PICKING => 'picking.assign',
            OrderStaffAssignment::TASK_PACKING => 'packing.assign',
            OrderStaffAssignment::TASK_DELIVERY => 'delivery.assign',
        });
        $order = Order::query()->findOrFail($data['order_id']);
        $this->authorizeStore($request, $order->stock_location_id);

        try {
            $tasks->assign($order, $data['task_type'], User::query()->findOrFail($data['assigned_user_id']), $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()]);
        }

        return back()->with('success', 'Task assigned.');
    }

    public function startTask(OrderStaffAssignment $assignment, Request $request, OrderStaffTaskService $tasks)
    {
        $this->authorizeStore($request, $assignment->stock_location_id);

        try {
            $tasks->start($assignment, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()]);
        }

        return back()->with('success', 'Task started.');
    }

    public function completeTask(OrderStaffAssignment $assignment, Request $request, OrderStaffTaskService $tasks)
    {
        $this->authorizeStore($request, $assignment->stock_location_id);

        try {
            $tasks->complete($assignment, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()]);
        }

        return back()->with('success', 'Task completed.');
    }

    public function startDelivery(OrderStaffAssignment $assignment, Request $request, DeliveryWorkflowService $delivery)
    {
        $this->authorizeStore($request, $assignment->stock_location_id);

        try {
            $delivery->startDelivery($assignment, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['delivery' => $exception->getMessage()]);
        }

        return redirect()->route('staff.deliveries.index')->with('success', 'Delivery started and OTP generated for the customer.');
    }

    public function verifyDelivery(DeliveryAttempt $attempt, Request $request, DeliveryWorkflowService $delivery)
    {
        $this->authorizeStore($request, $attempt->stock_location_id);
        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'accuracy_meters' => ['nullable', 'numeric'],
        ]);

        try {
            $delivery->verifyOtp($attempt, $request->user(), $data['otp'], $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['otp' => $exception->getMessage()]);
        }

        return back()->with('success', 'Order marked delivered.');
    }

    public function deliveryException(DeliveryAttempt $attempt, Request $request, DeliveryWorkflowService $delivery)
    {
        $this->authorizeStore($request, $attempt->stock_location_id);
        $data = $request->validate([
            'event_type' => ['required', 'in:DELIVERY_FAILED_BY_AGENT,CUSTOMER_UNAVAILABLE,RESCHEDULE_REQUESTED'],
            'reason_code' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'accuracy_meters' => ['nullable', 'numeric'],
        ]);

        try {
            $delivery->recordFailure($attempt, $request->user(), $data['event_type'], $data['reason_code'], $data['notes'] ?? null, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['delivery' => $exception->getMessage()]);
        }

        return back()->with('success', 'Delivery event recorded.');
    }

    public function requestApproval(DeliveryAttempt $attempt, Request $request, DeliveryWorkflowService $delivery)
    {
        $this->authorizeStore($request, $attempt->stock_location_id);
        $data = $request->validate([
            'approval_type' => ['required', 'in:RETURN_TO_STORE,DELIVERY_OTP_OVERRIDE'],
            'reason_code' => ['required', 'string', 'max:80'],
            'notes' => ['required', 'string'],
        ]);

        try {
            $delivery->requestApproval($attempt, $request->user(), $data['approval_type'], $data['reason_code'], $data['notes']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['approval' => $exception->getMessage()]);
        }

        return back()->with('success', 'Approval request submitted.');
    }

    public function approvals(Request $request)
    {
        $this->authorizeStaff($request, 'approvals.view');
        $user = $request->user();
        $approvals = StaffApprovalRequest::query()
            ->with(['requester', 'subject'])
            ->where('status', 'PENDING')
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('stock_location_id', $user->assigned_store_id))
            ->where('requested_by', '!=', $user->id)
            ->latest()
            ->paginate(20);

        return view('staff.approvals.index', compact('approvals'));
    }

    public function decideApproval(StaffApprovalRequest $approval, Request $request, DeliveryWorkflowService $delivery)
    {
        $this->authorizeStore($request, $approval->stock_location_id);
        $data = $request->validate(['decision' => ['required', 'in:approve,reject']]);

        try {
            $delivery->decideApproval($approval, $request->user(), $data['decision'] === 'approve');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['approval' => $exception->getMessage()]);
        }

        return back()->with('success', 'Approval decided.');
    }

    private function taskQueue(Request $request, string $taskType, string $permission, string $view)
    {
        $this->authorizeStaff($request, $permission);
        $user = $request->user();
        $assignments = OrderStaffAssignment::query()
            ->with(['order', 'assignee'])
            ->where('task_type', $taskType)
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('stock_location_id', $user->assigned_store_id))
            ->whereIn('status', ['PENDING', 'ASSIGNED', 'IN_PROGRESS'])
            ->latest()
            ->paginate(20);

        return view($view, compact('assignments'));
    }

    private function authorizeStaff(Request $request, ?string $permission = null): void
    {
        $user = $request->user();
        abort_unless($user && $user->staff_active && $this->permissions->isOperationalStaff($user), 403);

        if ($permission) {
            abort_unless($this->permissions->has($user, $permission), 403);
        }
    }

    private function authorizeStore(Request $request, ?int $storeId): void
    {
        $this->authorizeStaff($request);
        abort_unless($this->permissions->canAccessStore($request->user(), $storeId), 403);
    }
}
