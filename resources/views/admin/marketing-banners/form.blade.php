@extends('layouts.admin')

@php $selectedStores = old('store_ids', $banner->stores?->pluck('id')->map(fn ($id) => (string) $id)->all() ?? []); @endphp

@section('title', $banner->exists ? 'Edit Marketing Banner' : 'New Marketing Banner')

@section('admin-content')
<div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0">{{ $banner->exists ? 'Edit Marketing Banner' : 'New Marketing Banner' }}</h1><a href="{{ route('admin.marketing-banners.index') }}" class="btn btn-outline-secondary">Back</a></div>
@if ($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
<form method="POST" enctype="multipart/form-data" action="{{ $banner->exists ? route('admin.marketing-banners.update', $banner) : route('admin.marketing-banners.store') }}" class="card border-0 shadow-sm">
    @csrf @if($banner->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ old('title', $banner->title) }}"></div>
        <div class="col-md-6"><label class="form-label">Subtitle</label><input name="subtitle" class="form-control" value="{{ old('subtitle', $banner->subtitle) }}"></div>
        <div class="col-md-6"><label class="form-label">Desktop Image</label><input type="file" name="image" class="form-control" @required(! $banner->exists)>@if($banner->image_path)<div class="form-text">{{ $banner->image_path }}</div>@endif</div>
        <div class="col-md-6"><label class="form-label">Mobile Image</label><input type="file" name="mobile_image" class="form-control">@if($banner->mobile_image_path)<label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_mobile_image" value="1"> Remove current mobile image</label>@endif</div>
        <div class="col-md-3"><label class="form-label">CTA Text</label><input name="cta_text" class="form-control" value="{{ old('cta_text', $banner->cta_text) }}"></div>
        <div class="col-md-3"><label class="form-label">CTA URL</label><input name="cta_url" class="form-control" value="{{ old('cta_url', $banner->cta_url) }}"></div>
        <div class="col-md-3"><label class="form-label">Display Order</label><input type="number" name="display_order" min="0" max="255" class="form-control" value="{{ old('display_order', $banner->display_order ?? 0) }}" required></div>
        <div class="col-md-3"><label class="form-label">Priority</label><input type="number" name="priority" min="0" max="9999" class="form-control" value="{{ old('priority', $banner->priority ?? 0) }}" required></div>
        <div class="col-md-6"><label class="form-label">Store Targeting</label><select name="store_ids[]" class="form-select" multiple size="6">@foreach($stores as $store)<option value="{{ $store->id }}" @selected(in_array((string)$store->id, $selectedStores, true))>{{ $store->name }}</option>@endforeach</select><div class="form-text">Leave empty for global/default.</div></div>
        <div class="col-md-3"><label class="form-label">Start</label><input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', $banner->starts_at?->format('Y-m-d\\TH:i')) }}"></div>
        <div class="col-md-3"><label class="form-label">End</label><input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', $banner->ends_at?->format('Y-m-d\\TH:i')) }}"></div>
        <div class="col-12"><label class="form-check"><input type="hidden" name="enabled" value="0"><input class="form-check-input" type="checkbox" name="enabled" value="1" @checked(old('enabled', $banner->enabled ?? true))> Active</label></div>
    </div>
    <div class="card-footer bg-white text-end"><button class="btn btn-success">Save Banner</button></div>
</form>
@endsection
