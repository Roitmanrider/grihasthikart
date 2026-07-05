@php
    $selectedValues = array_values(old('attribute_values', isset($productVariant) ? $productVariant->attributeValues->pluck('id')->all() : []));
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Variant Details</div>
            <div class="card-body">
                @error('variant') <div class="alert alert-danger">{{ $message }}</div> @enderror

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Variant Name</label>
                        <input type="text" name="variant_name" value="{{ old('variant_name', $productVariant->variant_name ?? '') }}" class="form-control @error('variant_name') is-invalid @enderror" required>
                        @error('variant_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $productVariant->sku ?? '') }}" class="form-control @error('sku') is-invalid @enderror" required>
                        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Barcode</label>
                        <input type="text" name="barcode" value="{{ old('barcode', $productVariant->barcode ?? '') }}" class="form-control @error('barcode') is-invalid @enderror">
                        @error('barcode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">MRP</label>
                        <input type="number" step="0.01" name="mrp" value="{{ old('mrp', $productVariant->mrp ?? '') }}" class="form-control @error('mrp') is-invalid @enderror" required>
                        @error('mrp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Selling Price</label>
                        <input type="number" step="0.01" name="selling_price" value="{{ old('selling_price', $productVariant->selling_price ?? '') }}" class="form-control @error('selling_price') is-invalid @enderror" required>
                        @error('selling_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase Price</label>
                        <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $productVariant->purchase_price ?? '') }}" class="form-control @error('purchase_price') is-invalid @enderror">
                        @error('purchase_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Weight</label>
                        <input type="number" step="0.001" name="weight" value="{{ old('weight', $productVariant->weight ?? '') }}" class="form-control @error('weight') is-invalid @enderror">
                        @error('weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" value="{{ old('unit', $productVariant->unit ?? '') }}" class="form-control @error('unit') is-invalid @enderror" placeholder="g, kg, ml, l, pack">
                        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" value="{{ old('display_order', $productVariant->display_order ?? 0) }}" class="form-control @error('display_order') is-invalid @enderror" min="0">
                        @error('display_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Attributes</div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse ($attributes as $attribute)
                        <div class="col-md-6">
                            <label class="form-label">{{ $attribute->name }}</label>
                            <select name="attribute_values[{{ $attribute->id }}]" class="form-select">
                                <option value="">None</option>
                                @foreach ($attribute->values as $value)
                                    <option value="{{ $value->id }}" @selected(in_array($value->id, array_map('intval', $selectedValues), true))>{{ $value->value }}</option>
                                @endforeach
                            </select>
                        </div>
                    @empty
                        <div class="col-12 text-muted">No variant attributes available.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Product</div>
            <div class="card-body">
                <div class="fw-semibold">{{ $product->name }}</div>
                <div class="text-muted small">{{ $product->slug }}</div>
                <div class="mt-3">{{ $product->brand?->name ?? 'No brand' }}</div>
                <div class="mt-2">
                    @foreach ($product->categories as $category)
                        <span class="badge text-bg-light border">{{ $category->name }}{{ $category->pivot->is_primary ? ' *' : '' }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Status</div>
            <div class="card-body">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="status" value="0">
                    <input class="form-check-input" type="checkbox" name="status" value="1" id="status" @checked((bool) old('status', $productVariant->status ?? true))>
                    <label class="form-check-label" for="status">Active</label>
                </div>
                <div class="form-check form-switch">
                    <input type="hidden" name="is_default" value="0">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" @checked((bool) old('is_default', $productVariant->is_default ?? false))>
                    <label class="form-check-label" for="is_default">Default Variant</label>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-success">{{ $buttonText }}</button>
            <a href="{{ route('admin.products.variants.index', $product) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>
