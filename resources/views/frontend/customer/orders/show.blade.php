@extends('layouts.frontend')

@section('title','Order '.$order->order_number)

@section('content')
@inject('orderStatusService', 'App\Domains\Order\Services\OrderStatusService')
@inject('returnService', 'App\Domains\ReturnRequest\Services\ReturnRequestService')

<section class="py-5">
    <div class="container">
        <x-customer-page-header :title="$order->order_number" :subtitle="$order->placed_at?->format('d M Y')">
            <x-slot:actions>
                @if ($customerInvoiceEnabled)
                    <a href="{{ route('customer.orders.invoice', $order->order_number) }}" class="btn btn-outline-success gk-compact-action" target="_blank">View/Print Invoice</a>
                @endif
                @if ($canCancel)
                    <button class="btn btn-outline-danger gk-compact-action" type="button" data-bs-toggle="modal" data-bs-target="#customerCancelOrderModal">Cancel Order</button>
                @endif
                @if ($returnService->isEligible($order))
                    <a href="{{ route('customer.returns.create', $order) }}" class="btn btn-outline-success gk-compact-action">Request Return</a>
                @endif
                @if ($order->payment_method === 'razorpay' && $order->payment_status !== 'paid' && $order->order_status === 'pending')
                    <button class="btn btn-success gk-compact-action"
                            type="button"
                            id="retryRazorpayPayment"
                            data-retry-url="{{ route('checkout.razorpay.retry', $order->order_number) }}"
                            data-verify-url="{{ route('checkout.razorpay.verify') }}"
                            data-failure-url="{{ route('checkout.razorpay.failure') }}"
                            data-csrf-token="{{ csrf_token() }}">
                        Retry Payment
                    </button>
                @endif
                <a href="{{ route('customer.orders.index') }}" class="btn btn-outline-secondary gk-compact-action">Back</a>
            </x-slot:actions>
        </x-customer-page-header>

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

        @if (! empty($deliveryOtpCode))
            <div class="alert alert-warning border">
                <div class="fw-semibold">Delivery OTP for Order {{ $order->order_number }}: {{ $deliveryOtpCode }}</div>
                <div>Share this OTP only after you receive your order.</div>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Order Timeline</div>
            <div class="card-body">
                <div class="gk-order-timeline">
                    @foreach($statusTimeline['steps'] as $step)
                        <div class="gk-order-timeline-step {{ $step['state'] === 'upcoming' ? 'inactive' : $step['state'] }}">
                            <div class="gk-order-timeline-name">{{ $step['label'] }}</div>
                            <div class="small text-muted mt-1">
                                {{ $step['completed_at'] ?: 'Not reached yet' }}
                            </div>
                        </div>
                    @endforeach
                    @if($statusTimeline['final_state'])
                        <div class="gk-order-timeline-step current">
                            <div class="gk-order-timeline-name">{{ $statusTimeline['final_state'] }}</div>
                            <div class="small text-muted mt-1">Final status</div>
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
                <div class="row g-2 mb-3">
                    <div class="col-md-4"><div class="border rounded p-2 h-100"><div class="small text-muted">Order Status</div><x-customer-status-badge :status="$order->order_status" :label="$orderStatusService->label($order->order_status)" /></div></div>
                    <div class="col-md-4"><div class="border rounded p-2 h-100"><div class="small text-muted">Payment Method</div><div class="fw-semibold">{{ strtoupper($order->payment_method) }}</div></div></div>
                    <div class="col-md-4"><div class="border rounded p-2 h-100"><div class="small text-muted">Payment Status</div><x-customer-status-badge :status="$order->payment_status" /></div></div>
                    @if ($latestReturn = $order->returnRequests->sortByDesc('created_at')->first())
                        <div class="col-md-4"><div class="border rounded p-2 h-100"><div class="small text-muted">Return Status</div><x-customer-status-badge :status="$latestReturn->status" /></div></div>
                    @endif
                </div>

                @foreach($order->items as $item)
                    @php
                        $quantity = (float) $item->quantity;
                        $unitMrp = (float) $item->mrp;
                        $unitPrice = (float) $item->unit_price;
                        $discountPercent = $unitMrp > 0 && $unitMrp > $unitPrice
                            ? round((($unitMrp - $unitPrice) / $unitMrp) * 100)
                            : 0;
                        $includedGst = 'Included GST: '.number_format((float) ($item->gst_rate_snapshot ?? 0), 2).'% / Rs. '.number_format((float) $item->tax_amount, 2);
                    @endphp
                    <div class="border-bottom pb-3 mb-3">
                        <div class="fw-semibold">{{ $item->product_name_snapshot }}</div>
                        <div class="small text-muted">{{ $item->variant_name_snapshot }} x {{ rtrim(rtrim(number_format((float)$item->quantity,3),'0'),'.') }}</div>
                        <div class="gk-order-item-price-grid small mt-2">
                            <div>Unit MRP: Rs. {{ number_format($unitMrp, 2) }}</div>
                            <div>MRP Total: Rs. {{ number_format((float) ($item->line_mrp_total ?: ($unitMrp * $quantity)), 2) }}</div>
                            <div>GK Unit Price: Rs. {{ number_format($unitPrice, 2) }}</div>
                            <div>GK Merchandise: Rs. {{ number_format((float) $item->line_total, 2) }}</div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                            @if ($discountPercent > 0)
                                <span class="gk-discount-pill">{{ $discountPercent }}% OFF</span>
                            @endif
                            <button class="gk-gst-info"
                                    type="button"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="{{ $includedGst }}"
                                    title="{{ $includedGst }}"
                                    aria-label="{{ $includedGst }}">
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            </button>
                            <span class="small text-muted">{{ $includedGst }}</span>
                        </div>
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
                        <span>Merchandise Coupon Discount</span>
                        <span>- Rs. {{ number_format((float)$order->discount_total,2) }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between">
                    <span>Tax / GST</span>
                    <span>Rs. {{ number_format((float)$order->tax_total,2) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Original Delivery Charge</span>
                    <span>{{ (float)($order->original_delivery_charge ?: $order->delivery_charge) > 0 ? 'Rs. '.number_format((float)($order->original_delivery_charge ?: $order->delivery_charge),2) : 'Free' }}</span>
                </div>
                @if((float)$order->delivery_discount_total > 0)
                    <div class="d-flex justify-content-between text-success">
                        <span>Delivery Discount</span>
                        <span>- Rs. {{ number_format((float)$order->delivery_discount_total,2) }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between">
                    <span>Final Delivery Charge</span>
                    <span>{{ (float)$order->delivery_charge > 0 ? 'Rs. '.number_format((float)$order->delivery_charge,2) : 'Free' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Amount Before Customer Credit</span>
                    <span>Rs. {{ number_format((float)($order->amount_before_customer_credit ?: ((float)$order->grand_total + (float)$order->customer_credit_used)),2) }}</span>
                </div>
                <div class="d-flex justify-content-between {{ (float)$order->customer_credit_used > 0 ? 'text-success' : '' }}">
                    <span>Customer Credit Used</span>
                    <span>{{ (float)$order->customer_credit_used > 0 ? '- ' : '' }}Rs. {{ number_format((float)$order->customer_credit_used,2) }}</span>
                </div>
                @if((float)$order->customer_credit_used > 0)
                    @if((float)$order->grand_total <= 0)
                        <div class="alert alert-success py-2 mt-2 mb-0">Paid using Customer Credit</div>
                    @endif
                @endif

                <div class="d-flex justify-content-between h5 mt-3">
                    <span>Final Amount Paid / Due</span>
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

@if ($order->payment_method === 'razorpay' && $order->payment_status !== 'paid' && $order->order_status === 'pending')
@push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('retryRazorpayPayment');
            if (! button || typeof Razorpay === 'undefined') {
                return;
            }

            button.addEventListener('click', async function () {
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = 'Opening payment...';

                try {
                    const retryResponse = await fetch(button.dataset.retryUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': button.dataset.csrfToken,
                            'Accept': 'application/json',
                        },
                    });
                    const orderData = await retryResponse.json();

                    if (! retryResponse.ok) {
                        throw new Error(orderData.message || 'Unable to retry online payment.');
                    }

                    const razorpay = new Razorpay({
                        key: orderData.key,
                        amount: orderData.amount,
                        currency: orderData.currency,
                        name: orderData.name,
                        description: orderData.description,
                        order_id: orderData.order_id,
                        prefill: orderData.prefill,
                        handler: async function (response) {
                            const verifyResponse = await fetch(button.dataset.verifyUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': button.dataset.csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    order_number: orderData.order_number,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_signature: response.razorpay_signature,
                                }),
                            });
                            const verifyData = await verifyResponse.json();

                            if (! verifyResponse.ok) {
                                throw new Error(verifyData.message || 'Payment verification failed.');
                            }

                            window.location.href = verifyData.redirect_url;
                        },
                        modal: {
                            ondismiss: function () {
                                fetch(button.dataset.failureUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': button.dataset.csrfToken,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        order_number: orderData.order_number,
                                        razorpay_order_id: orderData.order_id,
                                        reason: 'Customer closed Razorpay checkout retry.',
                                    }),
                                });
                            },
                        },
                    });

                    razorpay.open();
                } catch (error) {
                    alert(error.message || 'Unable to retry online payment.');
                } finally {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        });
    </script>
@endpush
@endif
