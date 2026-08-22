<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\Order\Services\OrderService;
use App\Domains\Order\Services\OrderStatusService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdminOrderController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderService $orderService,
        private readonly OrderStatusService $orderStatusService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'order_status', 'payment_status', 'payment_method', 'date_from', 'date_to', 'stock_location_id']);
        if ($request->user()?->assigned_store_id && ! $request->user()?->isSuperAdmin()) {
            $filters['stock_location_id'] = $request->user()->assigned_store_id;
        }

        $orders = $this->orderService->paginate(
            $filters,
            (int) $request->input('per_page', 20)
        );

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $this->authorizeStoreAccess($order);
        $order = $this->orderRepository->findWithDetails($order->id);
        $statusActions = $this->orderStatusService->actionsFor($order);
        $statusTimeline = $this->orderStatusService->timelineFor($order);

        return view('admin.orders.show', compact('order', 'statusActions', 'statusTimeline'));
    }

    public function updateStatus(Order $order, UpdateOrderStatusRequest $request)
    {
        $this->authorizeStoreAccess($order);
        $data = $request->validated();

        try {
            $this->orderService->updateOrderStatus($order, $data['order_status'], $data['admin_notes'] ?? null);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['order' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order status updated successfully.');
    }

    private function authorizeStoreAccess(Order $order): void
    {
        $user = request()->user();

        if ($user?->assigned_store_id && ! $user->isSuperAdmin()) {
            abort_unless((int) $user->assigned_store_id === (int) $order->stock_location_id, 403);
        }
    }
}
