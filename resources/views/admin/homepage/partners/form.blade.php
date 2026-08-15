@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <div class="card-body row g-3">
        <div class="col-md-8">
            <label class="form-label" for="name">Name</label>
            <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $partner->name) }}" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="sort_order">Sort Order</label>
            <input id="sort_order" name="sort_order" type="number" min="0" max="999" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $partner->sort_order ?? 0) }}" required>
            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-8">
            <label class="form-label" for="external_url">External URL</label>
            <input id="external_url" name="external_url" class="form-control @error('external_url') is-invalid @enderror" value="{{ old('external_url', $partner->external_url) }}" placeholder="https://partner.example">
            @error('external_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="promo_text">Promo Text</label>
            <input id="promo_text" name="promo_text" class="form-control @error('promo_text') is-invalid @enderror" value="{{ old('promo_text', $partner->promo_text) }}">
            @error('promo_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-8">
            <label class="form-label" for="image">Logo/Image</label>
            @if ($partner->image_path)
                <div class="small text-muted mb-1">{{ $partner->image_path }}</div>
                <div class="form-check mb-2">
                    <input type="hidden" name="remove_image" value="0">
                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                    <label class="form-check-label" for="remove_image">Remove image</label>
                </div>
            @endif
            <input id="image" name="image" type="file" class="form-control @error('image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
                <input type="hidden" name="enabled" value="0">
                <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled" @checked(old('enabled', $partner->enabled ?? true))>
                <label class="form-check-label" for="enabled">Enabled</label>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-success">Save Partner</button>
    </div>
</form>
