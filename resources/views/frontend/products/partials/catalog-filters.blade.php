@php
    $filters = $filters ?? [];
    $filterOptions = $filterOptions ?? ['brands' => collect(), 'weights' => collect(), 'discounts' => [10, 20, 30]];
    $selectedBrands = collect($filters['brand_ids'] ?? [])->map(fn ($id) => (string) $id)->all();
    $selectedWeights = collect($filters['weights'] ?? [])->map(fn ($weight) => (string) $weight)->all();
    $baseRoute = $baseRoute ?? url()->current();
    $sortOptions = ['relevance' => 'Relevance', 'latest' => 'Newest', 'name' => 'Name A-Z', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'discount_desc' => 'Discount: High to Low'];
    $hasFilters = ($filters['q'] ?? '') !== ''
        || $selectedBrands !== []
        || $selectedWeights !== []
        || ($filters['min_price'] ?? '') !== ''
        || ($filters['max_price'] ?? '') !== ''
        || ($filters['discount_min'] ?? '') !== '';
@endphp

<div class="gk-catalog-filter-shell mb-4">
    <div class="gk-catalog-filter-bar">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button class="btn btn-outline-success btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#catalogFilterPanel" aria-controls="catalogFilterPanel">
                <i class="fa-solid fa-filter me-1" aria-hidden="true"></i> Filter
            </button>
            <form method="GET" action="{{ $baseRoute }}" class="d-flex align-items-center gap-2" data-no-loader="true">
                @foreach (request()->except(['sort', 'page']) as $key => $value)
                    @if (is_array($value))
                        @foreach ($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label class="small text-muted" for="catalogSort">Sort</label>
                <select id="catalogSort" name="sort" class="form-select form-select-sm gk-sort-select" onchange="this.form.submit()">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['sort'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($hasFilters)
            <div class="gk-filter-chip-row">
                @if (($filters['q'] ?? '') !== '')
                    <span class="gk-filter-chip">Search: {{ $filters['q'] }}</span>
                @endif
                @foreach (($filterOptions['brands'] ?? collect())->whereIn('id', array_map('intval', $selectedBrands)) as $brand)
                    <span class="gk-filter-chip">{{ $brand->name }}</span>
                @endforeach
                @foreach ($selectedWeights as $weight)
                    <span class="gk-filter-chip">{{ $weight }}</span>
                @endforeach
                @if (($filters['discount_min'] ?? '') !== '')
                    <span class="gk-filter-chip">{{ $filters['discount_min'] }}%+ off</span>
                @endif
                @if (($filters['min_price'] ?? '') !== '' || ($filters['max_price'] ?? '') !== '')
                    <span class="gk-filter-chip">Rs. {{ $filters['min_price'] ?? '0' }} - {{ $filters['max_price'] ?? 'Any' }}</span>
                @endif
                <a class="gk-filter-chip gk-filter-chip-clear" href="{{ $baseRoute }}{{ ($filters['q'] ?? '') !== '' ? '?q='.urlencode($filters['q']) : '' }}">Clear</a>
            </div>
        @endif
    </div>

    <form method="GET" action="{{ $baseRoute }}" class="gk-catalog-filter-form d-none d-lg-block" data-no-loader="true">
        @include('frontend.products.partials.catalog-filter-fields', compact('filters', 'filterOptions', 'selectedBrands', 'selectedWeights', 'sortOptions', 'baseRoute'))
    </form>
</div>

<div class="offcanvas offcanvas-bottom gk-catalog-filter-offcanvas" tabindex="-1" id="catalogFilterPanel" aria-labelledby="catalogFilterPanelLabel">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5" id="catalogFilterPanelLabel">Filter Products</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form method="GET" action="{{ $baseRoute }}" data-no-loader="true">
            @include('frontend.products.partials.catalog-filter-fields', compact('filters', 'filterOptions', 'selectedBrands', 'selectedWeights', 'sortOptions', 'baseRoute'))
        </form>
    </div>
</div>
