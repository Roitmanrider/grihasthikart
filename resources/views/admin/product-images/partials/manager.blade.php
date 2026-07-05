@php
    $isVariantScope = isset($productVariant);
    $images = $isVariantScope ? $productVariant->images : $product->images;
    $storeRoute = $isVariantScope
        ? route('admin.products.variants.images.store', [$product, $productVariant])
        : route('admin.products.images.store', $product);
@endphp

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">
        {{ $isVariantScope ? 'Variant Images' : 'Product Images' }}
    </div>
    <div class="card-body">
        @error('image') <div class="alert alert-danger">{{ $message }}</div> @enderror
        @error('images') <div class="alert alert-danger">{{ $message }}</div> @enderror
        @error('images.*') <div class="alert alert-danger">{{ $message }}</div> @enderror

        <form method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data" class="row g-3 mb-4">
            @csrf

            <div class="col-lg-4">
                <label class="form-label">Images</label>
                <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple required>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Alt Text</label>
                <input type="text" name="alt_text" class="form-control">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control">
            </div>
            <div class="col-lg-2">
                <label class="form-label">Order</label>
                <input type="number" name="display_order" value="0" class="form-control" min="0">
            </div>
            <div class="col-lg-3">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_primary" value="0">
                    <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="{{ $isVariantScope ? 'variant_image_primary' : 'product_image_primary' }}">
                    <label class="form-check-label" for="{{ $isVariantScope ? 'variant_image_primary' : 'product_image_primary' }}">Primary</label>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-check form-switch">
                    <input type="hidden" name="status" value="0">
                    <input class="form-check-input" type="checkbox" name="status" value="1" id="{{ $isVariantScope ? 'variant_image_status' : 'product_image_status' }}" checked>
                    <label class="form-check-label" for="{{ $isVariantScope ? 'variant_image_status' : 'product_image_status' }}">Active</label>
                </div>
            </div>
            <div class="col-lg-auto">
                <button class="btn btn-success">Upload</button>
            </div>
        </form>

        <div class="row g-3">
            @forelse ($images as $image)
                @php
                    $editRoute = $isVariantScope
                        ? route('admin.products.variants.images.edit', [$product, $productVariant, $image])
                        : route('admin.products.images.edit', [$product, $image]);
                    $primaryRoute = $isVariantScope
                        ? route('admin.products.variants.images.primary', [$product, $productVariant, $image])
                        : route('admin.products.images.primary', [$product, $image]);
                    $deleteRoute = $isVariantScope
                        ? route('admin.products.variants.images.destroy', [$product, $productVariant, $image])
                        : route('admin.products.images.destroy', [$product, $image]);
                    $restoreRoute = $isVariantScope
                        ? route('admin.products.variants.images.restore', [$product, $productVariant, $image->id])
                        : route('admin.products.images.restore', [$product, $image->id]);
                @endphp
                <div class="col-md-4 col-xl-3">
                    <div class="card h-100">
                        <img src="{{ Storage::url($image->path) }}" class="card-img-top" alt="{{ $image->alt_text }}" style="height: 160px; object-fit: cover;">
                        <div class="card-body">
                            <div class="fw-semibold text-truncate">{{ $image->title ?? 'Untitled' }}</div>
                            <div class="small text-muted text-truncate">{{ $image->alt_text ?? 'No alt text' }}</div>
                            <div class="mt-2">
                                @if ($image->is_primary)
                                    <span class="badge text-bg-success">Primary</span>
                                @endif
                                <span class="badge {{ $image->status ? 'text-bg-light' : 'text-bg-secondary' }}">{{ $image->status ? 'Active' : 'Inactive' }}</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex flex-wrap gap-1">
                                <a href="{{ $editRoute }}" class="btn btn-sm btn-outline-secondary">Edit</a>

                                @if (! $image->is_primary)
                                    <form method="POST" action="{{ $primaryRoute }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">Primary</button>
                                    </form>
                                @endif

                                @if ($image->trashed())
                                    <form method="POST" action="{{ $restoreRoute }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">Restore</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ $deleteRoute }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-muted">No images uploaded.</div>
            @endforelse
        </div>
    </div>
</div>
