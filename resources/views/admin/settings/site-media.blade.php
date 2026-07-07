@extends('layouts.admin')

@section('title', 'Site Media')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Site Media</h1>
            <div class="text-muted">Manage splash and loading visuals.</div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.settings.site-media.update') }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
        @csrf
        @method('PATCH')
        <div class="card-body row g-4">
            @foreach (['splash_image_path' => 'Splash Image', 'loading_image_path' => 'Loading Image'] as $key => $label)
                @php
                    $input = $key === 'splash_image_path' ? 'splash_image' : 'loading_image';
                    $remove = $key === 'splash_image_path' ? 'remove_splash_image' : 'remove_loading_image';
                    $path = $settings[$key] ?? null;
                @endphp
                <div class="col-md-6">
                    <label class="form-label">{{ $label }}</label>
                    @if ($path)
                        <div class="border rounded p-2 mb-2">
                            <img src="{{ app(\App\Services\MediaResolver::class)->url($path) }}" alt="{{ $label }}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">
                            <div class="small text-muted mt-2">{{ $path }}</div>
                        </div>
                        <div class="form-check mb-2">
                            <input type="hidden" name="{{ $remove }}" value="0">
                            <input class="form-check-input" type="checkbox" name="{{ $remove }}" value="1" id="{{ $remove }}">
                            <label class="form-check-label" for="{{ $remove }}">Remove current {{ strtolower($label) }}</label>
                        </div>
                    @endif
                    <input type="file" name="{{ $input }}" class="form-control @error($input) is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                    @error($input) <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endforeach
        </div>
        <div class="card-footer bg-white text-end">
            <button class="btn btn-success">Save Site Media</button>
        </div>
    </form>
@endsection
