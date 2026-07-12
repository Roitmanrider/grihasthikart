<aside class="bg-white border-end p-3" style="width: 260px;">

    <h2 class="h5 mb-4">GrihasthiKart</h2>

    <nav class="nav flex-column gap-1">

        <a class="nav-link text-dark" href="{{ route('admin.dashboard') }}">Dashboard</a>

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

        <a class="nav-link {{ request()->routeIs('admin.product-imports.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.product-imports.index') }}">
            Product Import
        </a>

        <a class="nav-link {{ request()->routeIs('admin.daily-offers.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.daily-offers.index') }}">
            Daily Offers
        </a>

        <div class="text-uppercase text-muted small fw-semibold mt-3 mb-1">Operations</div>

        <a class="nav-link {{ request()->routeIs('admin.inventories.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.inventories.index') }}">
            Inventory
        </a>

        <a class="nav-link {{ request()->routeIs('admin.stock-adjustments.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.stock-adjustments.index') }}">
            Stock Adjustments
        </a>

        <a class="nav-link {{ request()->routeIs('admin.stock-verifications.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.stock-verifications.index') }}">
            Stock Verification
        </a>

        <a class="nav-link {{ request()->routeIs('admin.suppliers.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.suppliers.index') }}">
            Suppliers
        </a>

        <a class="nav-link {{ request()->routeIs('admin.purchases.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.purchases.index') }}">
            Purchases
        </a>

        <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.orders.index') }}">
            Orders
        </a>

        <a class="nav-link {{ request()->routeIs('admin.returns.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.returns.index') }}">
            Returns
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

        <div class="text-uppercase text-muted small fw-semibold mt-3 mb-1">Reports</div>

        <a class="nav-link {{ request()->routeIs('admin.reports.index') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.reports.index') }}">
            Reports Dashboard
        </a>

        <a class="nav-link {{ request()->routeIs('admin.reports.gst-summary') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.reports.gst-summary') }}">
            GST Summary
        </a>

        <a class="nav-link {{ request()->routeIs('admin.reports.gst-by-rate') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.reports.gst-by-rate') }}">
            GST by Rate
        </a>

        <a class="nav-link {{ request()->routeIs('admin.reports.gst-monthly') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.reports.gst-monthly') }}">
            Monthly GST
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

        <a class="nav-link {{ request()->routeIs('admin.settings.business.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.settings.business.edit') }}">
            Business Contact
        </a>

        <a class="nav-link {{ request()->routeIs('admin.settings.payments.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.settings.payments.edit') }}">
            Payment Settings
        </a>

        <a class="nav-link {{ request()->routeIs('admin.settings.site-media.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.settings.site-media.edit') }}">
            Site Media
        </a>

        <a class="nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.contact-messages.index') }}">
            Contact Messages
        </a>

        <a class="nav-link {{ request()->routeIs('admin.delivery-slots.*') ? 'active fw-semibold text-success' : 'text-dark' }}"
           href="{{ route('admin.delivery-slots.index') }}">
            Delivery Slots
        </a>

    </nav>

</aside>
