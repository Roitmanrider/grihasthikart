@extends('layouts.frontend')

@section('title','Order '.$order->order_number)

@section('content')
@inject('orderStatusService', 'App\Domains\Order\Services\OrderStatusService')
@inject('returnService', 'App\Domains\ReturnRequest\Services\ReturnRequestService')

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
                @if ($returnService->isEligible($order))
                    <a href="{{ route('customer.returns.create', $order) }}" class="btn btn-outline-success">Request Return</a>
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

        @if ($order->order_status === 'delivered')
            <div class="alert alert-light border">
                @if ($returnService->isEligible($order))
                    Return available until {{ $returnService->returnAvailableUntil($order)?->format('d M Y, h:i A') }}
                @else
                    Return window closed
                @endif
            </div>
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
                                @if ($step['completed_at'])
                                    <div class="small text-muted mt-1">{{ $step['completed_at'] }}</div>
                                @endif
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
                    @if ($latestReturn = $order->returnRequests->sortByDesc('created_at')->first())
                        <span class="badge text-bg-info">{{ str($latestReturn->status)->headline() }}</span>
                    @endif
                </div>

                @foreach($order->items as $item)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="fw-semibold">{{ $item->product_name_snapshot }}</div>
                        <div class="small text-muted">{{ $item->variant_name_snapshot }} x {{ rtrim(rtrim(number_format((float)$item->quantity,3),'0'),'.') }}</div>
                        <div class="small">Unit price: Rs. {{ number_format((float)$item->unit_price,2) }} / Merchandise: Rs. {{ number_format((float)$item->line_total,2) }}</div>
                        <div class="small text-muted">GST {{ number_format((float)($item->gst_rate_snapshot ?? 0),2) }}% / Tax: Rs. {{ number_format((float)$item->tax_amount,2) }}</div>
                    </div>
                @endforeach

                <hr>

                <div class="d-flex justify-content-between">
                    <span>Merchandise Amount</span>
                    <span>Rs. {{ number_format((float)$order->subtotal,2) }}</span>
                </div>
                @if((float)$order->total_mrp > 0)
                    <div class="d-flex justify-content-between">
                        <span>MRP Total</span>
                        <span>Rs. {{ number_format((float)$order->total_mrp,2) }}</span>
                    </div>
                @endif
                @if((float)$order->total_savings > 0)
                    <div class="d-flex justify-content-between text-success">
                        <span>Savings</span>
                        <span>Rs. {{ number_format((float)$order->total_savings,2) }}</span>
                    </div>
                @endif
                @if($order->discount_total > 0)
                    <div class="d-flex justify-content-between text-success">
                        <span>Coupon Discount</span>
                        <span>- Rs. {{ number_format((float)$order->discount_total,2) }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between">
                    <span>Tax / GST</span>
                    <span>Rs. {{ number_format((float)$order->tax_total,2) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Delivery Charge</span>
                    <span>{{ (float)$order->delivery_charge > 0 ? 'Rs. '.number_format((float)$order->delivery_charge,2) : 'Free' }}</span>
                </div>

                <div class="d-flex justify-content-between h5 mt-3">
                    <span>Final Amount</span>
                    <strong>Rs. {{ number_format((float)$order->grand_total,2) }}</strong>
                </div>
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
