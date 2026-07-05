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

    </nav>

</aside>
