@php
    $selectedCategories = old('category_ids', isset($product) ? $product->categories->pluck('id')->all() : []);
    $primaryCategoryId = old('primary_category_id', isset($product) ? $product->categories->firstWhere('pivot.is_primary', true)?->id : null);
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $product->slug ?? '') }}" class="form-control @error('slug') is-invalid @enderror">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Brand</label>
                        <select name="brand_id" class="form-select @error('brand_id') is-invalid @enderror">
                            <option value="">No brand</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id ?? '') === (string) $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" value="{{ old('display_order', $product->display_order ?? 0) }}" class="form-control @error('display_order') is-invalid @enderror" min="0">
                        @error('display_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="1" @selected((string) old('status', isset($product) ? (int) $product->status : 1) === '1')>Active</option>
                            <option value="0" @selected((string) old('status', isset($product) ? (int) $product->status : 1) === '0')>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Short Description</label>
                        <textarea name="short_description" class="form-control" rows="2">{{ old('short_description', $product->short_description ?? '') }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="5">{{ old('description', $product->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Categories</div>
            <div class="card-body">
                @error('product') <div class="alert alert-danger">{{ $message }}</div> @enderror
                @error('category_ids') <div class="alert alert-danger">{{ $message }}</div> @enderror
                @error('primary_category_id') <div class="alert alert-danger">{{ $message }}</div> @enderror

                <div class="row g-2">
                    @foreach ($categories as $category)
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="category_ids[]" value="{{ $category->id }}" id="category_{{ $category->id }}" @checked(in_array($category->id, array_map('intval', $selectedCategories), true))>
                                    <label class="form-check-label fw-semibold" for="category_{{ $category->id }}">{{ $category->name }}</label>
                                </div>
                                <div class="form-check ms-4 mt-1">
                                    <input class="form-check-input" type="radio" name="primary_category_id" value="{{ $category->id }}" id="primary_category_{{ $category->id }}" @checked((string) $primaryCategoryId === (string) $category->id)>
                                    <label class="form-check-label small text-muted" for="primary_category_{{ $category->id }}">Primary</label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">SEO</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $product->meta_title ?? '') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="3">{{ old('meta_description', $product->meta_description ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Meta Keywords</label>
                        <textarea name="meta_keywords" class="form-control" rows="2">{{ old('meta_keywords', $product->meta_keywords ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Catalog Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Barcode</label>
                        <input type="text" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label">HSN Code</label>
                        <input type="text" name="hsn_code" value="{{ old('hsn_code', $product->hsn_code ?? '') }}" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label">GST Rate</label>
                        <input type="number" step="0.01" name="gst_rate" value="{{ old('gst_rate', $product->gst_rate ?? '') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Manufacturer</label>
                        <input type="text" name="manufacturer" value="{{ old('manufacturer', $product->manufacturer ?? '') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Country of Origin</label>
                        <input type="text" name="country_of_origin" value="{{ old('country_of_origin', $product->country_of_origin ?? 'India') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Shelf Life</label>
                        <input type="text" name="shelf_life" value="{{ old('shelf_life', $product->shelf_life ?? '') }}" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Minimum Order</label>
                        <input type="number" name="minimum_order_quantity" value="{{ old('minimum_order_quantity', $product->minimum_order_quantity ?? 1) }}" class="form-control" min="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Maximum Order</label>
                        <input type="number" name="maximum_order_quantity" value="{{ old('maximum_order_quantity', $product->maximum_order_quantity ?? '') }}" class="form-control" min="1">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Merchandising</div>
            <div class="card-body">
                @foreach ([
                    'returnable' => 'Returnable',
                    'cod_available' => 'COD Available',
                    'is_featured' => 'Featured',
                    'is_trending' => 'Trending',
                    'is_popular' => 'Popular',
                    'is_new_arrival' => 'New Arrival',
                ] as $field => $label)
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="{{ $field }}" @checked((bool) old($field, $product->{$field} ?? in_array($field, ['returnable', 'cod_available'], true)))>
                        <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-success">{{ $buttonText }}</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>
