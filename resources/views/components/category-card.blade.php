@php
    $image = $category->image;
@endphp

<article class="gk-category-card">

    <a href="{{ route('categories.show', $category->slug) }}" class="text-decoration-none">
        @if ($image)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($image) }}"
                 alt="{{ $category->name }}">
        @else
            <div class="gk-category-fallback">
                <i class="fa-solid fa-basket-shopping"></i>
            </div>
        @endif

        <h3>{{ $category->name }}</h3>

        @if ($category->children_count ?? null)
            <span>{{ $category->children_count }} subcategories</span>
        @endif
    </a>

</article>
