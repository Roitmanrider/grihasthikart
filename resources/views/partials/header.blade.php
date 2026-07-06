@php
    $cartService = app(\App\Domains\Cart\Services\CartService::class);
    $cartSummary = $cartService->getCartSummary($cartService->sessionIdentifier(request()->session()));
    $currentCustomer = app(\App\Domains\Customer\Services\CustomerAuthService::class)->currentCustomer(request()->session());
    $wishlistCount = app(\App\Domains\Wishlist\Services\WishlistService::class)->countForCustomer($currentCustomer);
    $cartCount = rtrim(rtrim(number_format($cartSummary['item_count'], 3), '0'), '.');
@endphp

<header class="gk-header sticky-top">
    <div class="container">
        <div class="gk-header-main">
            <a class="gk-logo" href="{{ route('home') }}" aria-label="GrihasthiKart home">
                <img src="{{ asset('assets/images/logos/logo.png') }}" alt="GrihasthiKart">
            </a>

            <form class="gk-search gk-search-desktop"
                  action="{{ route('products.index') }}"
                  method="GET"
                  role="search">
                <input type="search"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search for products, categories, brands..."
                       aria-label="Search products, categories, brands">
                <button type="submit" aria-label="Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>

            <div class="gk-header-actions">
                @if ($currentCustomer)
                    <a href="{{ route('customer.dashboard') }}" class="gk-account d-none d-lg-flex">
                        <i class="fa-regular fa-user"></i>
                        <span>Hi, {{ \Illuminate\Support\Str::limit($currentCustomer->name, 10) }}</span>
                    </a>
                @else
                    <a href="{{ route('customer.login') }}" class="gk-account d-none d-lg-flex">
                        <i class="fa-regular fa-user"></i>
                        <span>Login</span>
                    </a>
                @endif

                <a href="{{ route('wishlist.index') }}" class="gk-icon-link d-none d-lg-flex" aria-label="Wishlist">
                    <i class="fa-regular fa-heart"></i>
                    @if ($wishlistCount > 0)
                        <span class="gk-cart-badge">{{ $wishlistCount }}</span>
                    @endif
                </a>

                <a href="{{ route('cart.show') }}" class="gk-icon-link" aria-label="Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    @if ($cartSummary['item_count'] > 0)
                        <span class="gk-cart-badge">{{ $cartCount }}</span>
                    @endif
                </a>

                <a href="https://wa.me/" class="gk-icon-link gk-whatsapp" aria-label="WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i>
                </a>

                <a href="tel:" class="gk-icon-link gk-call" aria-label="Call">
                    <i class="fa-solid fa-phone"></i>
                </a>
            </div>
        </div>

        <form class="gk-search gk-search-mobile"
              action="{{ route('products.index') }}"
              method="GET"
              role="search">
            <input type="search"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Search for products, categories, brands..."
                   aria-label="Search products, categories, brands">
            <button type="submit" aria-label="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>
    </div>
</header>
