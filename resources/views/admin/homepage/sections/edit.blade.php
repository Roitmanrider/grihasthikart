@extends('layouts.admin')

@php
    $current = $section ? array_merge($defaults, $section->toArray()) : $defaults;
    $selectedCategoryIds = $section?->selectedCategories?->pluck('id')->map(fn ($id) => (string) $id)->all() ?? [];
    $selectedProductIds = $section?->selectedProducts?->pluck('id')->map(fn ($id) => (string) $id)->all() ?? [];
@endphp

@section('title', 'Edit Homepage Section')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Homepage Section</h1>
            <div class="text-muted">{{ $defaults['section_key'] }}</div>
        </div>
        <a href="{{ route('admin.homepage.sections.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.homepage.sections.update', $defaults['section_key']) }}" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body row g-3">
            <div class="col-md-8">
                <label class="form-label" for="title">Title</label>
                <input id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $current['title']) }}" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="stock_location_id">Scope</label>
                <select id="stock_location_id" name="stock_location_id" class="form-select">
                    <option value="">Global/default</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) old('stock_location_id', $current['stock_location_id'] ?? '') === (string) $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="sort_order">Order</label>
                <input id="sort_order" name="sort_order" type="number" min="0" max="999" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $current['sort_order']) }}" required>
                @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="subtitle">Subtitle</label>
                <input id="subtitle" name="subtitle" class="form-control @error('subtitle') is-invalid @enderror" value="{{ old('subtitle', $current['subtitle'] ?? '') }}">
                @error('subtitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="icon">Section Icon</label>
                <input id="icon" type="file" name="icon" class="form-control">
                @if (! empty($current['icon_path']))
                    <label class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_icon" value="1">
                        Remove current icon
                    </label>
                    <div class="form-text">{{ $current['icon_path'] }}</div>
                @endif
            </div>
            <div class="col-md-3">
                <label class="form-label" for="desktop_item_limit">Item Limit</label>
                <input id="desktop_item_limit" name="desktop_item_limit" type="number" min="1" max="24" class="form-control @error('desktop_item_limit') is-invalid @enderror" value="{{ old('desktop_item_limit', $current['desktop_item_limit']) }}" required>
                @error('desktop_item_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="mobile_item_limit">Mobile Limit</label>
                <input id="mobile_item_limit" name="mobile_item_limit" type="number" min="1" max="24" class="form-control @error('mobile_item_limit') is-invalid @enderror" value="{{ old('mobile_item_limit', $current['mobile_item_limit'] ?? '') }}">
                @error('mobile_item_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="source_mode">Source</label>
                <select id="source_mode" name="source_mode" class="form-select">
                    @foreach (['automatic' => 'Automatic', 'manual' => 'Manual'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('source_mode', $current['source_mode'] ?? 'automatic') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check">
                    <input type="hidden" name="enabled" value="0">
                    <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled" @checked(old('enabled', $current['enabled']))>
                    <label class="form-check-label" for="enabled">Enabled</label>
                </div>
            </div>

            @if (in_array($defaults['section_type'], ['category_section', 'category_section_group'], true))
                <div class="col-md-6">
                    <label class="form-label" for="root_category_id">Root Category</label>
                    <select id="root_category_id" name="root_category_id" class="form-select">
                        <option value="">Automatic root categories</option>
                        @foreach ($categories->whereNull('parent_id') as $category)
                            <option value="{{ $category->id }}" @selected((string) old('root_category_id', $current['root_category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="category_ids">Selected Subcategories</label>
                    <select id="category_ids" name="category_ids[]" class="form-select" multiple size="8">
                        @foreach ($categories->whereNotNull('parent_id') as $category)
                            <option value="{{ $category->id }}" @selected(in_array((string) $category->id, old('category_ids', $selectedCategoryIds), true))>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Leave empty for automatic active subcategories.</div>
                </div>
            @endif

            @if ($defaults['section_type'] === 'products')
                <div class="col-12">
                    <label class="form-label" for="product_ids">Manual Products</label>
                    <select id="product_ids" name="product_ids[]" class="form-select" multiple size="10">
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(in_array((string) $product->id, old('product_ids', $selectedProductIds), true))>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input type="hidden" name="view_all_enabled" value="0">
                    <input class="form-check-input" type="checkbox" name="view_all_enabled" value="1" id="view_all_enabled" @checked(old('view_all_enabled', $current['view_all_enabled']))>
                    <label class="form-check-label" for="view_all_enabled">Show View All</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="view_all_text">View All Text</label>
                <input id="view_all_text" name="view_all_text" class="form-control @error('view_all_text') is-invalid @enderror" value="{{ old('view_all_text', $current['view_all_text'] ?? '') }}">
                @error('view_all_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="view_all_url">View All URL</label>
                <input id="view_all_url" name="view_all_url" class="form-control @error('view_all_url') is-invalid @enderror" value="{{ old('view_all_url', $current['view_all_url'] ?? '') }}" placeholder="/categories">
                @error('view_all_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="card-footer bg-white text-end">
            <button class="btn btn-success">Save Section</button>
        </div>
    </form>
@endsection
