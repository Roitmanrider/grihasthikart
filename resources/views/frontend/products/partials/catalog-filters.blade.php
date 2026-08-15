@php
    $filters = $filters ?? [];
    $filterOptions = $filterOptions ?? ['brands' => collect(), 'weights' => collect(), 'discounts' => [10, 20, 30]];
    $selectedBrands = collect($filters['brand_ids'] ?? [])->map(fn ($id) => (string) $id)->all();
    $selectedWeights = collect($filters['weights'] ?? [])->map(fn ($weight) => (string) $weight)->all();
    $baseRoute = $baseRoute ?? url()->current();
@endphp

<form method="GET" action="{{ $baseRoute }}" class="card border-0 shadow-sm mb-4" data-no-loader="true">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="q">Search</label>
                <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="150" class="form-control" placeholder="Search catalog">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="sort">Sort</label>
                <select id="sort" name="sort" class="form-select">
                    @foreach (['relevance' => 'Relevance', 'latest' => 'Newest', 'name' => 'Name A-Z', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'discount_desc' => 'Discount: High to Low'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['sort'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="min_price">Min Price</label>
                <input id="min_price" name="min_price" type="number" min="0" step="1" value="{{ $filters['min_price'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="max_price">Max Price</label>
                <input id="max_price" name="max_price" type="number" min="0" step="1" value="{{ $filters['max_price'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="discount_min">Discount</label>
                <select id="discount_min" name="discount_min" class="form-select">
                    <option value="">Any discount</option>
                    @foreach ($filterOptions['discounts'] ?? [10, 20, 30] as $discount)
                        <option value="{{ $discount }}" @selected((string) ($filters['discount_min'] ?? '') === (string) $discount)>{{ $discount }}%+</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row g-3 mt-1">
            @if (($filterOptions['brands'] ?? collect())->isNotEmpty())
                <div class="col-md-6">
                    <div class="form-label">Brand</div>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach ($filterOptions['brands'] as $brand)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="brand[]" value="{{ $brand->id }}" id="brand_{{ $brand->id }}" @checked(in_array((string) $brand->id, $selectedBrands, true))>
                                <label class="form-check-label" for="brand_{{ $brand->id }}">{{ $brand->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (($filterOptions['weights'] ?? collect())->isNotEmpty())
                <div class="col-md-6">
                    <div class="form-label">Weight / Pack</div>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach ($filterOptions['weights'] as $weight)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="weight[]" value="{{ $weight }}" id="weight_{{ md5($weight) }}" @checked(in_array((string) $weight, $selectedWeights, true))>
                                <label class="form-check-label" for="weight_{{ md5($weight) }}">{{ $weight }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <button class="btn btn-success" type="submit">Apply Filters</button>
            <a href="{{ $baseRoute }}{{ ($filters['q'] ?? '') !== '' ? '?q='.urlencode($filters['q']) : '' }}" class="btn btn-outline-secondary">Clear Filters</a>
        </div>
    </div>
</form>
