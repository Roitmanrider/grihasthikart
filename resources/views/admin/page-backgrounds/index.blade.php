@extends('layouts.admin')

@section('title', 'Page Backgrounds')

@section('admin-content')
<div class="mb-4"><h1 class="h3 mb-1">Page Backgrounds</h1><div class="text-muted">Global/default and store-specific lightweight page appearance rules.</div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.page-backgrounds.store') }}" class="card border-0 shadow-sm mb-4">
    @csrf
    <div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">Scope</label><select name="stock_location_id" class="form-select"><option value="">Global/default</option>@foreach($stores as $store)<option value="{{ $store->id }}">{{ $store->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Page</label><select name="page_key" class="form-select">@foreach($pages as $page)<option value="{{ $page }}">{{ str($page)->replace('_',' ')->headline() }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Background</label><input type="file" name="background" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Opacity</label><input type="number" step="0.05" min="0" max="1" name="opacity" class="form-control" value="1"></div>
        <div class="col-md-3"><label class="form-label">Repeat</label><select name="repeat_mode" class="form-select"><option>no-repeat</option><option>repeat</option><option>repeat-x</option><option>repeat-y</option></select></div>
        <div class="col-md-3"><label class="form-label">Position</label><input name="position" class="form-control" value="center center"></div>
        <div class="col-md-3"><label class="form-label">Size</label><select name="size_mode" class="form-select"><option>cover</option><option>contain</option><option>auto</option></select></div>
        <div class="col-md-3 d-flex align-items-end"><label class="form-check"><input type="hidden" name="is_enabled" value="0"><input class="form-check-input" type="checkbox" name="is_enabled" value="1" checked> Enabled</label></div>
    </div>
    <div class="card-footer bg-white text-end"><button class="btn btn-success">Save Background</button></div>
</form>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Page</th><th>Scope</th><th>Media</th><th>Status</th></tr></thead><tbody>
@forelse($backgrounds as $background)
    <tr><td>{{ str($background->page_key)->replace('_',' ')->headline() }}</td><td>{{ $background->stockLocation?->name ?: 'Global/default' }}</td><td class="small text-muted">{{ $background->background_path }}</td><td><span class="badge {{ $background->is_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $background->is_enabled ? 'Enabled' : 'Disabled' }}</span></td></tr>
@empty
    <tr><td colspan="4" class="text-center text-muted py-4">No page backgrounds configured.</td></tr>
@endforelse
</tbody></table></div>@if($backgrounds->hasPages())<div class="card-footer bg-white">{{ $backgrounds->links() }}</div>@endif</div>
@endsection
