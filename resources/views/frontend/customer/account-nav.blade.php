@php
    $accountCustomer = $customer ?? app(\App\Domains\Customer\Services\CustomerAuthService::class)->currentCustomer(request()->session());
    $links = [
        ['label' => 'Shop', 'route' => 'home', 'active' => request()->routeIs('home')],
        ['label' => 'Overview', 'route' => 'customer.dashboard', 'active' => request()->routeIs('customer.dashboard')],
        ['label' => 'Profile', 'route' => 'customer.profile.edit', 'active' => request()->routeIs('customer.profile.*')],
        ['label' => 'Addresses', 'route' => 'customer.addresses.index', 'active' => request()->routeIs('customer.addresses.*')],
        ['label' => 'Orders', 'route' => 'customer.orders.index', 'active' => request()->routeIs('customer.orders.*')],
        ['label' => 'Returns', 'route' => 'customer.returns.index', 'active' => request()->routeIs('customer.returns.*')],
        ['label' => 'Customer Credit', 'route' => 'customer.credit.index', 'active' => request()->routeIs('customer.credit.*')],
        ['label' => 'Coupons', 'route' => 'customer.coupons.index', 'active' => request()->routeIs('customer.coupons.*')],
        ['label' => 'Wishlist', 'route' => 'wishlist.index', 'active' => request()->routeIs('wishlist.*')],
        ['label' => 'Notifications', 'route' => 'customer.notifications.index', 'active' => request()->routeIs('customer.notifications.*')],
        ['label' => 'Security', 'route' => 'customer.security.index', 'active' => request()->routeIs('customer.security.*')],
        ['label' => 'Support', 'route' => 'pages.support', 'active' => request()->routeIs('pages.support')],
    ];

    if ($accountCustomer?->cashback_enabled) {
        array_splice($links, 7, 0, [[
            'label' => 'Cashback',
            'route' => 'customer.cashback.index',
            'active' => request()->routeIs('customer.cashback.*'),
        ]]);
    }
@endphp

<x-account-notice-strip />

<nav class="gk-account-nav" aria-label="Account navigation" data-account-nav>
    <div class="gk-account-nav-row" tabindex="0" data-account-nav-row>
        @foreach ($links as $link)
            <a href="{{ route($link['route']) }}"
               class="gk-account-nav-link {{ $link['active'] ? 'active' : '' }}"
               @if($link['active']) aria-current="page" data-account-nav-active @endif>
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
</nav>
