<div class="col-12">
    <input type="hidden" name="stock_location_id" value="{{ old('stock_location_id', $dailyOffer->stock_location_id ?? $selectedStore?->id) }}">
    <div class="alert alert-light border mb-0">
        Store: <span class="fw-semibold">{{ $selectedStore?->name ?? 'Select a store from the top bar before saving.' }}</span>
    </div>
</div>

<div class="col-12">
    <label class="form-label">Product Variant</label>
    <select name="product_variant_id" class="form-select" required>
        <option value="">Select product variant</option>
        @foreach ($variants as $variant)
            @php
                $storeId = (int) old('stock_location_id', $dailyOffer->stock_location_id ?? $selectedStore?->id);
                $physicalAvailable = (float) $variant->inventories->where('stock_location_id', $storeId)->sum('available_quantity');
                $activeAllocated = $variant->dailyOffers
                    ->filter(fn ($offer) => (int) $offer->stock_location_id === $storeId && $offer->is_active && (! $offer->ends_at || $offer->ends_at->isFuture()) && (! $dailyOffer->id || $offer->id !== $dailyOffer->id))
                    ->sum(fn ($offer) => max(0, (float) $offer->allocated_quantity - $offer->soldQuantity()));
                $unallocatedStock = max(0, $physicalAvailable - $activeAllocated);
            @endphp
            <option value="{{ $variant->id }}"
                    data-product-max="{{ $variant->product?->maximum_order_quantity ?: '' }}"
                    @selected((int) old('product_variant_id', $dailyOffer->product_variant_id ?? 0) === $variant->id)>
                {{ $variant->product?->name }} - {{ $variant->variant_name }} - {{ $variant->sku }} - Rs. {{ number_format((float) $variant->selling_price, 2) }} - Stock {{ number_format($physicalAvailable, 0) }} - Active Allocation {{ number_format($activeAllocated, 0) }} - Unallocated {{ number_format($unallocatedStock, 0) }}
            </option>
        @endforeach
    </select>
    <div class="form-text">Only active variants from active products are available. Allocation cannot exceed unallocated stock.</div>
</div>

<div class="col-md-6">
    <label class="form-label">Title</label>
    <input name="title" value="{{ old('title', $dailyOffer->title ?? '') }}" class="form-control" maxlength="255">
</div>

<div class="col-md-3">
    <label class="form-label">Offer Price</label>
    <input type="number" step="0.01" min="0" name="offer_price" value="{{ old('offer_price', $dailyOffer->offer_price ?? '') }}" class="form-control" required>
</div>

<div class="col-md-3">
    <label class="form-label">Allocated Offer Qty</label>
    <input type="number" step="1" min="1" name="allocated_quantity" value="{{ old('allocated_quantity', $dailyOffer->allocated_quantity ?? '') }}" class="form-control" required>
</div>

<div class="col-md-3">
    <label class="form-label">Badge Text</label>
    <input name="badge_text" value="{{ old('badge_text', $dailyOffer->badge_text ?? '') }}" class="form-control" maxlength="255">
</div>

<div class="col-md-4">
    <label class="form-label">Starts At</label>
    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', isset($dailyOffer) && $dailyOffer?->starts_at ? $dailyOffer->starts_at->format('Y-m-d\TH:i') : '') }}" class="form-control">
</div>

<div class="col-md-4">
    <label class="form-label">Ends At</label>
    <input type="datetime-local" name="ends_at" value="{{ old('ends_at', isset($dailyOffer) && $dailyOffer?->ends_at ? $dailyOffer->ends_at->format('Y-m-d\TH:i') : '') }}" class="form-control">
</div>

<div class="col-md-2">
    <label class="form-label">Max Qty / Order</label>
    <input type="number" min="1" name="max_quantity_per_order" value="{{ old('max_quantity_per_order', $dailyOffer->max_quantity_per_order ?? 1) }}" class="form-control" required>
    <div class="form-text" id="dailyOfferProductMaxHint"></div>
    @error('max_quantity_per_order') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="col-md-2">
    <label class="form-label">Display Order</label>
    <input type="number" min="1" name="display_order" value="{{ old('display_order', $dailyOffer->display_order ?? 1) }}" class="form-control" required>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selector = document.querySelector('select[name="product_variant_id"]');
            const hint = document.getElementById('dailyOfferProductMaxHint');

            if (!selector || !hint) {
                return;
            }

            const updateHint = () => {
                const max = selector.selectedOptions[0]?.dataset.productMax || '';
                hint.textContent = max ? 'Maximum allowed for this variant: ' + max : '';
            };

            selector.addEventListener('change', updateHint);
            updateHint();
        });
    </script>
@endpush

<div class="col-12">
    <div class="form-check">
        <input type="hidden" name="is_active" value="0">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $dailyOffer->is_active ?? true))>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>

<div class="col-12 d-flex justify-content-end gap-2">
    <a href="{{ route('admin.daily-offers.index') }}" class="btn btn-outline-secondary">Cancel</a>
    <button class="btn btn-success">Save Daily Offer</button>
</div>
