@extends('layouts.app')

@section('content')

<div class="d-flex flex-column flex-lg-row min-vh-100 bg-light admin-shell">

    @include('partials.sidebar')

    <div class="flex-grow-1 min-w-0 admin-content" style="flex-basis: 0; overflow-x: hidden;">

        @include('partials.topbar')

        <main class="container-fluid py-4">

            <div class="d-flex flex-wrap justify-content-end align-items-center gap-3 mb-3">
                @if (auth()->user()?->isSuperAdmin())
                    @php
                        $adminStoreContext = app(\App\Domains\Store\Services\AdminStoreContextService::class);
                        $adminStoreOptions = $adminStoreContext->storesForSelector(auth()->user());
                        $selectedAdminStoreId = $adminStoreContext->selectedStoreId(request());
                    @endphp
                    <form method="POST" action="{{ route('admin.store-context.update') }}" class="d-flex align-items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <label class="small text-muted" for="admin_store_context">Store</label>
                        <select id="admin_store_context" name="stock_location_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Stores</option>
                            @foreach ($adminStoreOptions as $storeOption)
                                <option value="{{ $storeOption->id }}" @selected((int) $selectedAdminStoreId === (int) $storeOption->id)>{{ $storeOption->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <div class="small text-muted">
                    Current app time:
                    <span class="fw-semibold text-dark">
                        {{ now(config('app.timezone'))->format('d M Y, h:i A T') }}
                    </span>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Logout</button>
                </form>
            </div>

            @include('partials.flash')

            @yield('admin-content')

        </main>

    </div>

</div>

@endsection
