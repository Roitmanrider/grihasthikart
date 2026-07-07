@php
    $image = $category->image;
@endphp

<a href="{{ route('categories.show', $category->slug) }}" class="gk-home-category-tile">
    @if ($image)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($image) }}" alt="{{ $category->name }}">
    @else
        <span class="gk-home-category-fallback">
            <i class="{{ $category->icon ?: 'fa-solid fa-basket-shopping' }}"></i>
        </span>
    @endif
    <strong>{{ $category->name }}</strong>
</a>
