@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Attribute Information</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $attribute->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" value="{{ old('display_order', $attribute->display_order ?? 0) }}" class="form-control @error('display_order') is-invalid @enderror" min="0">
                        @error('display_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $attribute->slug ?? '') }}" class="form-control @error('slug') is-invalid @enderror" placeholder="Auto generated when empty">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(old('type', $attribute->type ?? 'text') === $type)>{{ str($type)->headline() }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Behavior</h2>
            </div>
            <div class="card-body">
                <input type="hidden" name="status" value="0">
                <input type="hidden" name="is_filterable" value="0">
                <input type="hidden" name="is_variant_defining" value="0">

                <div class="form-check form-switch mb-3">
                    <input type="checkbox" name="status" value="1" class="form-check-input" id="status" @checked(old('status', $attribute->status ?? true))>
                    <label class="form-check-label" for="status">Active</label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="checkbox" name="is_filterable" value="1" class="form-check-input" id="is_filterable" @checked(old('is_filterable', $attribute->is_filterable ?? true))>
                    <label class="form-check-label" for="is_filterable">Available as catalog filter</label>
                </div>

                <div class="form-check form-switch">
                    <input type="checkbox" name="is_variant_defining" value="1" class="form-check-input" id="is_variant_defining" @checked(old('is_variant_defining', $attribute->is_variant_defining ?? false))>
                    <label class="form-check-label" for="is_variant_defining">Defines product variants</label>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button class="btn btn-success">Save Attribute</button>
            <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>
