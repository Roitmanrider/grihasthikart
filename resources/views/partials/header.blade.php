<header class="bg-white border-bottom sticky-top">

    <nav class="navbar navbar-expand-lg">

        <div class="container">

            <a class="navbar-brand fw-bold text-success" href="{{ route('home') }}">

                GrihasthiKart

            </a>

            <button class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#customerCatalogNav"
                    aria-controls="customerCatalogNav"
                    aria-expanded="false"
                    aria-label="Toggle navigation">

                <span class="navbar-toggler-icon"></span>

            </button>

            <div class="collapse navbar-collapse gap-3" id="customerCatalogNav">

                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('products.index') }}">Products</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('categories.index') }}">Categories</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('brands.index') }}">Brands</a>
                    </li>

                </ul>

                <form class="d-flex flex-grow-1 flex-lg-grow-0"
                      action="{{ route('products.index') }}"
                      method="GET"
                      role="search">

                    <input class="form-control me-2"
                           type="search"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search products, SKU, barcode"
                           aria-label="Search products">

                    <button class="btn btn-success" type="submit">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>

                </form>

                @php
                    $cartService = app(\App\Domains\Cart\Services\CartService::class);
                    $cartSummary = $cartService->getCartSummary($cartService->sessionIdentifier(request()->session()));
                @endphp

                <a href="{{ route('cart.show') }}" class="btn btn-outline-success position-relative">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="ms-1">Cart</span>
                    @if ($cartSummary['item_count'] > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger">
                            {{ rtrim(rtrim(number_format($cartSummary['item_count'], 3), '0'), '.') }}
                        </span>
                    @endif
                </a>

                @php
                    $currentCustomer = app(\App\Domains\Customer\Services\CustomerAuthService::class)->currentCustomer(request()->session());
                @endphp

                @if ($currentCustomer)
                    <a href="{{ route('customer.dashboard') }}" class="btn btn-success">{{ $currentCustomer->name }}</a>
                @else
                    <a href="{{ route('customer.login') }}" class="btn btn-outline-secondary">Login</a>
                @endif

            </div>

        </div>

    </nav>

</header>
