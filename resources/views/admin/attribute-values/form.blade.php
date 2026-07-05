@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Attribute Value Information</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Attribute <span class="text-danger">*</span></label>
                        <select name="attribute_id" class="form-select @error('attribute_id') is-invalid @enderror" required>
                            <option value="">Select attribute</option>
                            @foreach ($attributes as $attribute)
                                <option value="{{ $attribute->id }}" @selected((string) old('attribute_id', $attributeValue->attribute_id ?? request('attribute_id')) === (string) $attribute->id)>
                                    {{ $attribute->name }} ({{ str($attribute->type)->headline() }})
                                </option>
                            @endforeach
                        </select>
                        @error('attribute_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" value="{{ old('display_order', $attributeValue->display_order ?? 0) }}" class="form-control @error('display_order') is-invalid @enderror" min="0">
                        @error('display_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Value <span class="text-danger">*</span></label>
                        <input type="text" name="value" value="{{ old('value', $attributeValue->value ?? '') }}" class="form-control @error('value') is-invalid @enderror" required>
                        @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $attributeValue->slug ?? '') }}" class="form-control @error('slug') is-invalid @enderror" placeholder="Auto generated when empty">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Visibility</h2>
            </div>
            <div class="card-body">
                <input type="hidden" name="status" value="0">

                <div class="form-check form-switch">
                    <input type="checkbox" name="status" value="1" class="form-check-input" id="status" @checked(old('status', $attributeValue->status ?? true))>
                    <label class="form-check-label" for="status">Active</label>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button class="btn btn-success">Save Attribute Value</button>
            <a href="{{ route('admin.attribute-values.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>
