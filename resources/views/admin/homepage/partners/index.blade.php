@extends('layouts.admin')

@section('title', 'Associated Partners')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Associated Partners</h1>
            <div class="text-muted">Manage partner cards shown on the storefront homepage.</div>
        </div>
        <a href="{{ route('admin.homepage.partners.create') }}" class="btn btn-success">Add Partner</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Partner</th>
                        <th>Status</th>
                        <th>URL</th>
                        <th>Order</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($partners as $partner)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $partner->name }}</div>
                                <div class="small text-muted">{{ $partner->promo_text ?: 'No promo text' }}</div>
                            </td>
                            <td><span class="badge {{ $partner->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $partner->enabled ? 'Enabled' : 'Disabled' }}</span></td>
                            <td class="small text-muted">{{ $partner->external_url ?: 'Brands page fallback' }}</td>
                            <td>{{ $partner->sort_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.homepage.partners.edit', $partner) }}" class="btn btn-sm btn-outline-success">Edit</a>
                                <form method="POST" action="{{ route('admin.homepage.partners.destroy', $partner) }}" class="d-inline" onsubmit="return confirm('Delete this partner?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No partners created. The storefront uses default partner cards.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($partners->hasPages())
            <div class="card-footer bg-white">{{ $partners->links() }}</div>
        @endif
    </div>
@endsection
