@extends('layouts.admin')

@section('title', 'Return '.$returnRequest->return_number)

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $returnRequest->return_number }}</h1>
        <div class="text-muted">Order {{ $returnRequest->order?->order_number }} / {{ $returnRequest->customer?->name }}</div>
    </div>
    <a href="{{ route('admin.returns.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Return Items</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Refund</th>
                            <th>Reason</th>
                            <th>Condition</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($returnRequest->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->orderItem?->product_name_snapshot }}</div>
                                    <div class="small text-muted">{{ $item->orderItem?->variant_name_snapshot }} / {{ $item->orderItem?->sku_snapshot }}</div>
                                </td>
                                <td>{{ number_format((float) $item->quantity, 3) }}</td>
                                <td>Rs. {{ number_format((float) $item->unit_price, 2) }}</td>
                                <td>Rs. {{ number_format((float) $item->refund_amount, 2) }}</td>
                                <td>{{ $item->reason ?: 'N/A' }}</td>
                                <td>{{ $item->condition ?: 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Summary</div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Status</span><span class="badge text-bg-light border">{{ str($returnRequest->status)->headline() }}</span></div>
                <div class="d-flex justify-content-between mt-2"><span>Refund Amount</span><strong>Rs. {{ number_format((float) $returnRequest->refund_amount, 2) }}</strong></div>
                <div class="d-flex justify-content-between mt-2"><span>Restocked</span><span>{{ $returnRequest->restock_items ? 'Yes' : 'No' }}</span></div>
                @if ($returnRequest->customer_notes)
                    <hr>
                    <div class="fw-semibold">Customer Notes</div>
                    <div class="text-muted small">{{ $returnRequest->customer_notes }}</div>
                @endif
                @if ($returnRequest->admin_notes)
                    <hr>
                    <div class="fw-semibold">Admin Notes</div>
                    <div class="text-muted small">{{ $returnRequest->admin_notes }}</div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Actions</div>
            <div class="card-body">
                @if ($returnRequest->status === 'requested')
                    <button class="btn btn-success w-100 mb-3" type="button" data-bs-toggle="modal" data-bs-target="#approveReturnModal">Approve</button>

                    <form method="POST" action="{{ route('admin.returns.reject', $returnRequest) }}">
                        @csrf
                        @method('PATCH')
                        <label class="form-label" for="reject_notes">Reject reason</label>
                        <textarea id="reject_notes" name="admin_notes" class="form-control mb-2" rows="3" required></textarea>
                        <button class="btn btn-outline-danger w-100">Reject</button>
                    </form>
                @elseif ($returnRequest->status === 'approved')
                    <form method="POST" action="{{ route('admin.returns.mark-refunded', $returnRequest) }}" class="mb-3">
                        @csrf
                        @method('PATCH')
                        <textarea name="admin_notes" class="form-control mb-2" rows="2" placeholder="Refund note"></textarea>
                        <button class="btn btn-success w-100">Mark Refunded</button>
                    </form>
                    <form method="POST" action="{{ route('admin.returns.close', $returnRequest) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-outline-secondary w-100">Close</button>
                    </form>
                @elseif ($returnRequest->status === 'refunded')
                    <form method="POST" action="{{ route('admin.returns.close', $returnRequest) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-outline-secondary w-100">Close</button>
                    </form>
                @else
                    <div class="text-muted">No actions available.</div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($returnRequest->status === 'requested')
    <div class="modal fade" id="approveReturnModal" tabindex="-1" aria-labelledby="approveReturnModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.returns.approve', $returnRequest) }}" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h2 class="modal-title h5" id="approveReturnModalLabel">Approve Return</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="approve_notes">Admin notes</label>
                    <textarea id="approve_notes" name="admin_notes" class="form-control mb-3" rows="3"></textarea>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="restock_items" value="1" id="restock_items">
                        <label class="form-check-label" for="restock_items">Restock returned items</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                    <button class="btn btn-success">Approve Return</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
