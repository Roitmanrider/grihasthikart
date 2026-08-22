@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label" for="title">Title</label>
            <input id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $banner->title) }}">
            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="stock_location_id">Scope</label>
            <select id="stock_location_id" name="stock_location_id" class="form-select">
                <option value="">Global/default</option>
                @foreach ($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) old('stock_location_id', $banner->stock_location_id) === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="subtitle">Subtitle</label>
            <input id="subtitle" name="subtitle" class="form-control @error('subtitle') is-invalid @enderror" value="{{ old('subtitle', $banner->subtitle) }}">
            @error('subtitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="cta_text">CTA Text</label>
            <input id="cta_text" name="cta_text" class="form-control @error('cta_text') is-invalid @enderror" value="{{ old('cta_text', $banner->cta_text) }}">
            @error('cta_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-8">
            <label class="form-label" for="cta_url">CTA URL</label>
            <input id="cta_url" name="cta_url" class="form-control @error('cta_url') is-invalid @enderror" value="{{ old('cta_url', $banner->cta_url) }}" placeholder="/products">
            @error('cta_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-8">
            <label class="form-label" for="alt_text">Alt Text</label>
            <input id="alt_text" name="alt_text" class="form-control @error('alt_text') is-invalid @enderror" value="{{ old('alt_text', $banner->alt_text) }}">
            @error('alt_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="sort_order">Sort Order</label>
            <input id="sort_order" name="sort_order" type="number" min="0" max="999" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" required>
            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="starts_at">Start At</label>
            <input id="starts_at" name="starts_at" type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror" value="{{ old('starts_at', $banner->starts_at?->format('Y-m-d\\TH:i')) }}">
            @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="ends_at">End At</label>
            <input id="ends_at" name="ends_at" type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror" value="{{ old('ends_at', $banner->ends_at?->format('Y-m-d\\TH:i')) }}">
            @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="desktop_image">Desktop Image</label>
            @if ($banner->desktop_image_path)
                <div class="small text-muted mb-1">{{ $banner->desktop_image_path }}</div>
            @endif
            <input id="desktop_image" name="desktop_image" type="file" class="form-control @error('desktop_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp" @required(! $banner->exists)>
            @error('desktop_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="mobile_image">Mobile Image</label>
            @if ($banner->mobile_image_path)
                <div class="small text-muted mb-1">{{ $banner->mobile_image_path }}</div>
                <div class="form-check mb-2">
                    <input type="hidden" name="remove_mobile_image" value="0">
                    <input class="form-check-input" type="checkbox" name="remove_mobile_image" value="1" id="remove_mobile_image">
                    <label class="form-check-label" for="remove_mobile_image">Remove mobile image</label>
                </div>
            @endif
            <input id="mobile_image" name="mobile_image" type="file" class="form-control @error('mobile_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
            @error('mobile_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <div class="form-check">
                <input type="hidden" name="enabled" value="0">
                <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled" @checked(old('enabled', $banner->enabled ?? true))>
                <label class="form-check-label" for="enabled">Enabled</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check">
                <input type="hidden" name="open_in_new_tab" value="0">
                <input class="form-check-input" type="checkbox" name="open_in_new_tab" value="1" id="open_in_new_tab" @checked(old('open_in_new_tab', $banner->open_in_new_tab ?? false))>
                <label class="form-check-label" for="open_in_new_tab">Open CTA in new tab</label>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-success">Save Banner</button>
    </div>
</form>
