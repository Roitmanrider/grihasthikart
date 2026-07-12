@extends('layouts.admin')

@section('title', 'Stock Adjustment Details')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Stock Adjustment #{{ $stockAdjustment->id }}</h1>
        <div class="text-muted">{{ $stockAdjustment->adjustment_date?->format('d M Y') }}</div>
    </div>
    <a href="{{ route('admin.stock-adjustments.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Adjustment</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Variant</dt>
                    <dd class="col-sm-8">{{ $stockAdjustment->productVariant?->product?->name }} / {{ $stockAdjustment->productVariant?->variant_name }} / {{ $stockAdjustment->productVariant?->sku }}</dd>
                    <dt class="col-sm-4">Location</dt>
                    <dd class="col-sm-8">{{ $stockAdjustment->inventory?->stockLocation?->name ?? 'No movement' }}</dd>
                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8">{{ str($stockAdjustment->adjustment_type)->headline() }}</dd>
                    <dt class="col-sm-4">Reason</dt>
                    <dd class="col-sm-8">{{ str($stockAdjustment->reason)->replace('_', ' ')->headline() }}</dd>
                    <dt class="col-sm-4">Before / After</dt>
                    <dd class="col-sm-8">{{ number_format((float) $stockAdjustment->before_quantity, 3) }} -> {{ number_format((float) $stockAdjustment->after_quantity, 3) }}</dd>
                    <dt class="col-sm-4">Quantity</dt>
                    <dd class="col-sm-8">{{ number_format((float) $stockAdjustment->quantity, 3) }}</dd>
                    <dt class="col-sm-4">Reference</dt>
                    <dd class="col-sm-8">{{ $stockAdjustment->reference_number ?: 'None' }}</dd>
                    <dt class="col-sm-4">Notes</dt>
                    <dd class="col-sm-8">{{ $stockAdjustment->notes ?: 'None' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Movement</div>
            <div class="card-body">
                @forelse ($stockAdjustment->movements as $movement)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="fw-semibold">{{ str($movement->movement_type)->replace('_', ' ')->headline() }}</div>
                        <div class="small text-muted">Qty {{ number_format((float) $movement->quantity, 3) }} / Balance {{ number_format((float) $movement->balance_after, 3) }}</div>
                    </div>
                @empty
                    <div class="text-muted">No stock movement was needed.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
