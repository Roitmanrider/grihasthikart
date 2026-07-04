@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Brand Information</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $brand->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" value="{{ old('display_order', $brand->display_order ?? 0) }}" class="form-control @error('display_order') is-invalid @enderror" min="0">
                        @error('display_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $brand->slug ?? '') }}" class="form-control @error('slug') is-invalid @enderror" placeholder="Auto generated when empty">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Website URL</label>
                        <input type="url" name="website_url" value="{{ old('website_url', $brand->website_url ?? '') }}" class="form-control @error('website_url') is-invalid @enderror" placeholder="https://example.com">
                        @error('website_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $brand->description ?? '') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">SEO</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $brand->meta_title ?? '') }}" class="form-control @error('meta_title') is-invalid @enderror">
                        @error('meta_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" rows="3" class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $brand->meta_description ?? '') }}</textarea>
                        @error('meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Meta Keywords</label>
                        <textarea name="meta_keywords" rows="2" class="form-control @error('meta_keywords') is-invalid @enderror">{{ old('meta_keywords', $brand->meta_keywords ?? '') }}</textarea>
                        @error('meta_keywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                <input type="hidden" name="is_featured" value="0">

                <div class="form-check form-switch mb-3">
                    <input type="checkbox" name="status" value="1" class="form-check-input" id="status" @checked(old('status', $brand->status ?? true))>
                    <label class="form-check-label" for="status">Active</label>
                </div>

                <div class="form-check form-switch">
                    <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="is_featured" @checked(old('is_featured', $brand->is_featured ?? false))>
                    <label class="form-check-label" for="is_featured">Featured brand</label>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Media</h2>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Logo</label>
                    <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                    @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if (! empty($brand?->logo))
                        <div class="small text-muted mt-2">{{ $brand->logo }}</div>
                    @endif
                </div>

                <div>
                    <label class="form-label">Banner</label>
                    <input type="file" name="banner" class="form-control @error('banner') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                    @error('banner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if (! empty($brand?->banner))
                        <div class="small text-muted mt-2">{{ $brand->banner }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button class="btn btn-success">Save Brand</button>
            <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>
