@if ($inventory)
    <div class="col-md-6">
        <label class="form-label">Product Variant</label>
        <input type="text" class="form-control" value="{{ $inventory->productVariant?->product?->name }} / {{ $inventory->productVariant?->variant_name }} / {{ $inventory->productVariant?->sku }}" disabled>
    </div>
    <div class="col-md-6">
        <label class="form-label">Stock Location</label>
        <input type="text" class="form-control" value="{{ $inventory->stockLocation?->name }}" disabled>
    </div>
@else
    <div class="col-md-6">
        <label class="form-label">Product Variant</label>
        <select name="product_variant_id" class="form-select" required>
            <option value="">Select variant</option>
            @foreach ($options['variants'] as $variant)
                <option value="{{ $variant->id }}" @selected((string) old('product_variant_id') === (string) $variant->id)>
                    {{ $variant->product?->name }} / {{ $variant->variant_name }} / {{ $variant->sku }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Stock Location</label>
        <select name="stock_location_id" class="form-select" required>
            <option value="">Select location</option>
            @foreach ($options['locations'] as $location)
                <option value="{{ $location->id }}" @selected((string) old('stock_location_id') === (string) $location->id)>
                    {{ $location->name }} / {{ $location->code }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Quantity On Hand</label>
        <input type="number" step="0.001" min="0" name="quantity_on_hand" value="{{ old('quantity_on_hand', 0) }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Reserved Quantity</label>
        <input type="number" step="0.001" min="0" name="reserved_quantity" value="{{ old('reserved_quantity', 0) }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Damaged Quantity</label>
        <input type="number" step="0.001" min="0" name="damaged_quantity" value="{{ old('damaged_quantity', 0) }}" class="form-control">
    </div>
@endif

<div class="col-md-4">
    <label class="form-label">Low Stock Threshold</label>
    <input type="number" step="0.001" min="0" name="low_stock_threshold" value="{{ old('low_stock_threshold', $inventory?->low_stock_threshold) }}" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">Reorder Level</label>
    <input type="number" step="0.001" min="0" name="reorder_level" value="{{ old('reorder_level', $inventory?->reorder_level) }}" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">Target Stock Level</label>
    <input type="number" step="0.001" min="0" name="target_stock_level" value="{{ old('target_stock_level', $inventory?->target_stock_level) }}" class="form-control">
</div>
<div class="col-md-4">
    <div class="form-check mt-4">
        <input type="hidden" name="status" value="0">
        <input class="form-check-input" type="checkbox" name="status" value="1" id="status" @checked(old('status', $inventory?->status ?? true))>
        <label class="form-check-label" for="status">Active</label>
    </div>
</div>
