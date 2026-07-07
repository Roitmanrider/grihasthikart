@php
    $currentCustomer = app(\App\Domains\Customer\Services\CustomerAuthService::class)->currentCustomer(request()->session());
    $wishlistCount = app(\App\Domains\Wishlist\Services\WishlistService::class)->countForCustomer($currentCustomer);
@endphp

<nav class="gk-mobile-nav" aria-label="Mobile navigation">
    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
        <i class="fa-solid fa-house"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'active' : '' }}">
        <i class="fa-solid fa-table-cells-large"></i>
        <span>Categories</span>
    </a>
    <a href="{{ route('wishlist.index') }}" class="{{ request()->routeIs('wishlist.*') || $wishlistCount > 0 ? 'active' : '' }}">
        <i class="{{ $wishlistCount > 0 ? 'fa-solid' : 'fa-regular' }} fa-heart"></i>
        @if ($wishlistCount > 0)
            <span class="gk-mobile-badge">{{ $wishlistCount }}</span>
        @endif
        <span>Wishlist</span>
    </a>
    <a href="{{ route('customer.orders.index') }}" class="{{ request()->routeIs('customer.orders.*') ? 'active' : '' }}">
        <i class="fa-solid fa-bag-shopping"></i>
        <span>Orders</span>
    </a>
    <a href="{{ route('customer.dashboard') }}" class="{{ request()->routeIs('customer.*') && ! request()->routeIs('customer.orders.*') ? 'active' : '' }}">
        <i class="fa-regular fa-user"></i>
        <span>Account</span>
    </a>
</nav>
