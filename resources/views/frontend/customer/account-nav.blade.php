@php
    $links = [
        ['label' => 'Overview', 'route' => 'customer.dashboard', 'active' => request()->routeIs('customer.dashboard')],
        ['label' => 'Profile', 'route' => 'customer.profile.edit', 'active' => request()->routeIs('customer.profile.*')],
        ['label' => 'Addresses', 'route' => 'customer.addresses.index', 'active' => request()->routeIs('customer.addresses.*')],
        ['label' => 'Orders', 'route' => 'customer.orders.index', 'active' => request()->routeIs('customer.orders.*')],
        ['label' => 'Returns', 'route' => 'customer.returns.index', 'active' => request()->routeIs('customer.returns.*')],
        ['label' => 'Customer Credit', 'route' => 'customer.credit.index', 'active' => request()->routeIs('customer.credit.*')],
        ['label' => 'Cashback', 'route' => 'customer.cashback.index', 'active' => request()->routeIs('customer.cashback.*')],
        ['label' => 'Coupons', 'route' => 'customer.coupons.index', 'active' => request()->routeIs('customer.coupons.*')],
        ['label' => 'Wishlist', 'route' => 'wishlist.index', 'active' => request()->routeIs('wishlist.*')],
        ['label' => 'Notifications', 'route' => 'customer.notifications.index', 'active' => request()->routeIs('customer.notifications.*')],
        ['label' => 'Security', 'route' => 'customer.security.index', 'active' => request()->routeIs('customer.security.*')],
        ['label' => 'Support', 'route' => 'pages.support', 'active' => request()->routeIs('pages.support')],
    ];
@endphp

<nav class="mb-4" aria-label="Account navigation">
    <div class="d-flex flex-wrap gap-2">
        @foreach ($links as $link)
            <a href="{{ route($link['route']) }}" class="btn btn-sm {{ $link['active'] ? 'btn-success' : 'btn-outline-secondary' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
</nav>
