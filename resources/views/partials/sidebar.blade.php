<aside class="bg-white border-end p-3" style="width: 260px;">

    <h2 class="h5 mb-4">GrihasthiKart</h2>

    <nav class="nav flex-column gap-1">

        <a class="nav-link text-dark" href="{{ url('/admin') }}">Dashboard</a>

        <div class="text-uppercase text-muted small fw-semibold mt-3 mb-1">Catalog</div>

        <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.categories.index') }}">
            Categories
        </a>

        <a class="nav-link {{ request()->routeIs('admin.brands.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.brands.index') }}">
            Brands
        </a>

        <a class="nav-link {{ request()->routeIs('admin.attributes.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.attributes.index') }}">
            Attributes
        </a>

        <a class="nav-link {{ request()->routeIs('admin.attribute-values.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.attribute-values.index') }}">
            Attribute Values
        </a>

        <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.products.index') }}">
            Products
        </a>

        <div class="text-uppercase text-muted small fw-semibold mt-3 mb-1">Operations</div>

        <a class="nav-link {{ request()->routeIs('admin.inventories.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.inventories.index') }}">
            Inventory
        </a>

        <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.orders.index') }}">
            Orders
        </a>

        <a class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.payments.index') }}">
            Payments
        </a>

        <a class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.coupons.index') }}">
            Coupons
        </a>

        <a class="nav-link {{ request()->routeIs('admin.cashback.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.cashback.index') }}">
            Cashback
        </a>

        <a class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.customers.index') }}">
            Customers
        </a>

        <div class="text-uppercase text-muted small fw-semibold mt-3 mb-1">Settings</div>

        <a class="nav-link {{ request()->routeIs('admin.settings.checkout.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.settings.checkout.edit') }}">
            Checkout Settings
        </a>

        <a class="nav-link {{ request()->routeIs('admin.settings.payments.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.settings.payments.edit') }}">
            Payment Settings
        </a>

        <a class="nav-link {{ request()->routeIs('admin.delivery-slots.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.delivery-slots.index') }}">
            Delivery Slots
        </a>

    </nav>

</aside>
