@php
    $image = $category->image;
    $mediaResolver = app(\App\Services\MediaResolver::class);
    $categoryKey = \Illuminate\Support\Str::lower($category->slug.' '.$category->name);
    $fallbackIcon = match (true) {
        str_contains($categoryKey, 'fruit') || str_contains($categoryKey, 'vegetable') => 'fa-solid fa-leaf',
        str_contains($categoryKey, 'grain') || str_contains($categoryKey, 'flour') || str_contains($categoryKey, 'rice') || str_contains($categoryKey, 'atta') => 'fa-solid fa-wheat-awn',
        str_contains($categoryKey, 'face') || str_contains($categoryKey, 'body') || str_contains($categoryKey, 'hair') || str_contains($categoryKey, 'personal') => 'fa-solid fa-pump-soap',
        str_contains($categoryKey, 'dairy') || str_contains($categoryKey, 'milk') => 'fa-solid fa-bottle-water',
        str_contains($categoryKey, 'snack') || str_contains($categoryKey, 'biscuit') || str_contains($categoryKey, 'cookie') => 'fa-solid fa-cookie-bite',
        str_contains($categoryKey, 'oil') || str_contains($categoryKey, 'ghee') => 'fa-solid fa-droplet',
        str_contains($categoryKey, 'baby') => 'fa-solid fa-baby',
        str_contains($categoryKey, 'home') || str_contains($categoryKey, 'clean') => 'fa-solid fa-house-chimney',
        str_contains($categoryKey, 'pet') => 'fa-solid fa-paw',
        default => 'fa-solid fa-basket-shopping',
    };
@endphp

<a href="{{ route('categories.show', $category->slug) }}" class="gk-home-category-tile">
    @if ($image)
        <img src="{{ $mediaResolver->url($image) }}" alt="{{ $category->name }}">
    @else
        <span class="gk-home-category-fallback">
            <i class="{{ $category->icon ?: $fallbackIcon }}"></i>
        </span>
    @endif
    <strong>{{ $category->name }}</strong>
</a>
