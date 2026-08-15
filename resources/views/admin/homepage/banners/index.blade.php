@extends('layouts.admin')

@section('title', 'Homepage Banners')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Homepage Banners</h1>
            <div class="text-muted">Manage desktop and mobile homepage banner slides.</div>
        </div>
        <a href="{{ route('admin.homepage.banners.create') }}" class="btn btn-success">Add Banner</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Banner</th>
                        <th>Status</th>
                        <th>Schedule</th>
                        <th>Order</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($banners as $banner)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $banner->title ?: 'Untitled banner' }}</div>
                                <div class="small text-muted">{{ $banner->desktop_image_path }}</div>
                            </td>
                            <td><span class="badge {{ $banner->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $banner->enabled ? 'Enabled' : 'Disabled' }}</span></td>
                            <td class="small text-muted">
                                {{ $banner->starts_at?->format('d M Y H:i') ?: 'No start' }} - {{ $banner->ends_at?->format('d M Y H:i') ?: 'No end' }}
                            </td>
                            <td>{{ $banner->sort_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.homepage.banners.edit', $banner) }}" class="btn btn-sm btn-outline-success">Edit</a>
                                <form method="POST" action="{{ route('admin.homepage.banners.destroy', $banner) }}" class="d-inline" onsubmit="return confirm('Delete this banner?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No banners created. The storefront uses its default hero.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($banners->hasPages())
            <div class="card-footer bg-white">{{ $banners->links() }}</div>
        @endif
    </div>
@endsection
