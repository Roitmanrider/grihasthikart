@extends('layouts.frontend')

@section('title','Order '.$order->order_number)

@section('content')
@inject('orderStatusService', 'App\Domains\Order\Services\OrderStatusService')

<section class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">{{ $order->order_number }}</h1>
                <div class="text-muted">{{ $order->placed_at?->format('d M Y') }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($customerInvoiceEnabled)
                    <a href="{{ route('customer.orders.invoice', $order->order_number) }}" class="btn btn-outline-success" target="_blank">View/Print Invoice</a>
                @endif
                @if ($canCancel)
                    <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#customerCancelOrderModal">Cancel Order</button>
                @endif
                <a href="{{ route('customer.orders.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Order Timeline</div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($statusTimeline['steps'] as $step)
                        @php
                            $badgeClass = match ($step['state']) {
                                'completed' => 'text-bg-success',
                                'current' => 'text-bg-warning',
                                default => 'text-bg-light border',
                            };
                        @endphp
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="border rounded p-2 h-100 text-center">
                                <span class="badge {{ $badgeClass }} w-100 text-wrap py-2">{{ $step['label'] }}</span>
                            </div>
                        </div>
                    @endforeach
                    @if($statusTimeline['final_state'])
                        <div class="col-12 col-md-4 col-lg-2">
                            <div class="border rounded p-2 h-100 text-center">
                                <span class="badge text-bg-danger w-100 text-wrap py-2">{{ $statusTimeline['final_state'] }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($orderStatusService->isCancellation($order->order_status) && $order->admin_notes)
            <div class="alert alert-light border">
                <div class="fw-semibold">Cancellation reason</div>
                <div class="text-muted">{{ $order->admin_notes }}</div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge text-bg-light border">{{ $orderStatusService->label($order->order_status) }}</span>
                    <span class="badge text-bg-light border">{{ strtoupper($order->payment_method) }} / {{ str($order->payment_status)->headline() }}</span>
                </div>

                @foreach($order->items as $item)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="fw-semibold">{{ $item->product_name_snapshot }}</div>
                        <div class="small text-muted">{{ $item->variant_name_snapshot }} x {{ rtrim(rtrim(number_format((float)$item->quantity,3),'0'),'.') }} - Rs. {{ number_format((float)$item->line_total,2) }}</div>
                    </div>
                @endforeach

                <hr>

                @if($order->discount_total > 0)
                    <div class="d-flex justify-content-between text-success">
                        <span>Coupon Discount</span>
                        <span>- Rs. {{ number_format((float)$order->discount_total,2) }}</span>
                    </div>
                @endif

                <div class="h5 mt-2">Grand Total: Rs. {{ number_format((float)$order->grand_total,2) }}</div>
            </div>
        </div>
    </div>
</section>

@if ($canCancel)
    <div class="modal fade" id="customerCancelOrderModal" tabindex="-1" aria-labelledby="customerCancelOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('customer.orders.cancel', $order->order_number) }}" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h2 class="modal-title h5" id="customerCancelOrderModalLabel">Cancel Order</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="customerCancelReason">Cancellation reason</label>
                    <textarea id="customerCancelReason" name="reason" class="form-control" rows="4" required>{{ old('reason') }}</textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                    <button class="btn btn-danger">Cancel Order</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
