@extends('layouts.frontend')
@section('title','Customer Credit')
@section('content')
<section class="py-5">
    <div class="container">
        @include('frontend.customer.account-nav')
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Customer Credit</h1>
                <div class="text-muted">Separate from Cashback Points.</div>
            </div>
            <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-secondary">Back to Account</a>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="text-muted small">Available Balance</div>
                <div class="h3 mb-0">Rs. {{ number_format((float) $creditBalance, 2) }}</div>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Ledger</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Reference</th><th>Amount</th><th>Balance After</th></tr></thead>
                    <tbody>
                        @forelse ($creditTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at?->format('d M Y, h:i A') }}</td>
                                <td>{{ str($transaction->type)->headline() }}<div class="small text-muted">{{ $transaction->description ?: $transaction->source }}</div></td>
                                <td>
                                    @if ($transaction->order)
                                        <a href="{{ route('customer.orders.show', $transaction->order->order_number) }}">{{ $transaction->order->order_number }}</a>
                                    @elseif ($transaction->returnRequest)
                                        <a href="{{ route('customer.returns.show', $transaction->returnRequest) }}">{{ $transaction->returnRequest->return_number }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>Rs. {{ number_format((float) $transaction->amount, 2) }}</td>
                                <td>Rs. {{ number_format((float) $transaction->balance_after, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No Customer Credit transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $creditTransactions->links() }}</div>
        </div>
    </div>
</section>
@endsection
