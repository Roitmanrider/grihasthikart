@php
    $image = $category->image;
@endphp

<article class="card h-100 border-0 shadow-sm">

    <a href="{{ route('categories.show', $category->slug) }}" class="text-decoration-none">
        @if ($image)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($image) }}"
                 class="card-img-top object-fit-cover"
                 style="height: 150px;"
                 alt="{{ $category->name }}">
        @else
            <div class="ratio ratio-4x3 bg-light d-flex align-items-center justify-content-center text-success">
                <i class="fa-solid fa-layer-group fa-2x"></i>
            </div>
        @endif
    </a>

    <div class="card-body">
        <h3 class="h6 mb-1">
            <a href="{{ route('categories.show', $category->slug) }}" class="text-dark text-decoration-none">
                {{ $category->name }}
            </a>
        </h3>

        @if ($category->children_count ?? null)
            <div class="small text-muted">{{ $category->children_count }} subcategories</div>
        @endif
    </div>

</article>
