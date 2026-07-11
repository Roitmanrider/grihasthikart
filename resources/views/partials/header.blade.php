@php
    $cartService = app(\App\Domains\Cart\Services\CartService::class);
    $cartSummary = $cartService->getCartSummary($cartService->sessionIdentifier(request()->session()));
    $currentCustomer = app(\App\Domains\Customer\Services\CustomerAuthService::class)->currentCustomer(request()->session());
    $settingService = app(\App\Domains\Setting\Services\BusinessSettingService::class);
    $wishlistCount = app(\App\Domains\Wishlist\Services\WishlistService::class)->countForCustomer($currentCustomer);
    $notificationCount = $currentCustomer ? app(\App\Domains\Notification\Services\NotificationService::class)->customerUnreadCount($currentCustomer) : 0;
    $cartCount = rtrim(rtrim(number_format($cartSummary['item_count'], 3), '0'), '.');
    $whatsappUrl = $settingService->whatsappUrl();
    $phoneUrl = $settingService->phoneUrl();
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
                       placeholder="Search for products, categories, subcategories..."
                       aria-label="Search products, categories, brands">
                <button type="submit" aria-label="Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>

            <div class="gk-header-actions">
                @if ($currentCustomer)
                    <a href="{{ route('customer.dashboard') }}" class="gk-account">
                        <i class="fa-regular fa-user"></i>
                        <span>Hi, {{ \Illuminate\Support\Str::limit($currentCustomer->name, 10) }}</span>
                    </a>
                @else
                    <a href="{{ route('customer.login') }}" class="gk-account">
                        <i class="fa-regular fa-user"></i>
                        <span>Login</span>
                    </a>
                @endif

                @if ($currentCustomer)
                    <a href="{{ route('customer.notifications.index') }}" class="gk-icon-link {{ $notificationCount > 0 ? 'is-active' : '' }}" aria-label="Notifications">
                        <i class="{{ $notificationCount > 0 ? 'fa-solid' : 'fa-regular' }} fa-bell"></i>
                        @if ($notificationCount > 0)
                            <span class="gk-cart-badge">{{ $notificationCount }}</span>
                        @endif
                    </a>
                @endif

                <a href="{{ route('wishlist.index') }}" class="gk-icon-link gk-wishlist-link {{ $wishlistCount > 0 ? 'is-active' : '' }}" aria-label="Wishlist">
                    <i class="{{ $wishlistCount > 0 ? 'fa-solid' : 'fa-regular' }} fa-heart"></i>
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

                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" class="gk-icon-link gk-whatsapp" aria-label="WhatsApp" target="_blank" rel="noopener">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                @endif

                @if ($phoneUrl)
                    <a href="{{ $phoneUrl }}" class="gk-icon-link gk-call" aria-label="Call">
                        <i class="fa-solid fa-phone"></i>
                    </a>
                @endif
            </div>
        </div>

        <form class="gk-search gk-search-mobile"
              action="{{ route('products.index') }}"
              method="GET"
              role="search">
            <input type="search"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Search for products, categories, subcategories..."
                   aria-label="Search products, categories, brands">
            <button type="submit" aria-label="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>
    </div>
</header>
