@extends('layouts.admin')

@section('title', 'Customer Marketing Banners')

@section('admin-content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Customer Marketing Banners</h1>
        <div class="text-muted">Up to five applicable account-page banners are shown to customers.</div>
    </div>
    <a href="{{ route('admin.marketing-banners.create') }}" class="btn btn-success">New Banner</a>
</div>
@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead class="table-light"><tr><th>Banner</th><th>Stores</th><th>Status</th><th>Priority</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($banners as $banner)
        <tr><td><div class="fw-semibold">{{ $banner->title ?: 'Untitled banner' }}</div><div class="small text-muted">{{ $banner->image_path }}</div></td><td>{{ $banner->stores_count ?: 'Global' }}</td><td><span class="badge {{ $banner->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $banner->enabled ? 'Active' : 'Inactive' }}</span></td><td>{{ $banner->priority }}</td><td class="text-end"><a href="{{ route('admin.marketing-banners.edit', $banner) }}" class="btn btn-sm btn-outline-success">Edit</a><form method="POST" action="{{ route('admin.marketing-banners.destroy', $banner) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-secondary">Deactivate</button></form></td></tr>
    @empty
        <tr><td colspan="5" class="text-center text-muted py-4">No customer marketing banners configured.</td></tr>
    @endforelse
    </tbody>
</table></div>@if($banners->hasPages())<div class="card-footer bg-white">{{ $banners->links() }}</div>@endif</div>
@endsection
