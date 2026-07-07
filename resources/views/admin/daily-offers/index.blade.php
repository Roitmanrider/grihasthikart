@extends('layouts.admin')

@section('title', 'Daily Offers')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Daily Offers</h1>
            <div class="text-muted">Control the homepage Daily Offers section.</div>
            <div class="small text-muted mt-1">
                Current app time: <span class="fw-semibold">{{ now(config('app.timezone'))->format('d M Y, h:i A T') }}</span>
            </div>
        </div>
        <a href="{{ route('admin.daily-offers.create') }}" class="btn btn-success">Add Daily Offer</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.daily-offers.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search product, SKU, badge">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="1" @selected(request('status') === '1')>Active</option>
                        <option value="0" @selected(request('status') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="current" class="form-select">
                        <option value="">All Dates</option>
                        <option value="1" @selected(request('current') === '1')>Current Only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="trashed" class="form-select">
                        <option value="">Without Deleted</option>
                        <option value="with" @selected(request('trashed') === 'with')>With Deleted</option>
                        <option value="only" @selected(request('trashed') === 'only')>Deleted Only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-success w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product / Variant</th>
                        <th>Offer Price</th>
                        <th>Badge</th>
                        <th>Schedule</th>
                        <th>Lifecycle</th>
                        <th>Status</th>
                        <th>Order</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dailyOffers as $offer)
                        @php
                            $variant = $offer->productVariant;
                            $product = $variant?->product;
                        @endphp
                        <tr @class(['table-warning' => $offer->trashed()])>
                            <td>
                                <div class="fw-semibold">{{ $product?->name ?? 'Missing Product' }}</div>
                                <div class="small text-muted">
                                    {{ $variant?->variant_name }} / {{ $variant?->sku }}
                                </div>
                                @if ($offer->title)
                                    <div class="small text-success">{{ $offer->title }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">Rs. {{ number_format((float) $offer->offer_price, 2) }}</div>
                                <div class="small text-muted">MRP Rs. {{ number_format((float) ($variant?->mrp ?? 0), 2) }}</div>
                            </td>
                            <td>{{ $offer->badge_text ?: $offer->discountBadge() ?: '-' }}</td>
                            <td>
                                <div class="small">Start: {{ $offer->starts_at?->format('d M Y, h:i A') ?: 'Anytime' }}</div>
                                <div class="small">End: {{ $offer->ends_at?->format('d M Y, h:i A') ?: 'Open' }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $offer->lifecycleBadgeClass() }}">
                                    {{ $offer->lifecycleState() }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $offer->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $offer->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $offer->display_order }}</td>
                            <td class="text-end">
                                @if (! $offer->trashed())
                                    <a href="{{ route('admin.daily-offers.edit', $offer) }}" class="btn btn-sm btn-outline-success">Edit</a>
                                    <form method="POST" action="{{ route('admin.daily-offers.destroy', $offer) }}" class="d-inline" onsubmit="return confirm('Delete this daily offer?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.daily-offers.restore', $offer->id) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">Restore</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No daily offers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $dailyOffers->links() }}</div>
    </div>
@endsection
