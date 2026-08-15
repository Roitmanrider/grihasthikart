@php($config = $config ?? [])

<section class="gk-section gk-view-more-categories">
    <div class="container text-center">
        <a href="{{ $config['view_all_url'] ?? route('categories.index') }}" class="btn btn-outline-success">
            {{ $config['view_all_text'] ?? $config['title'] ?? 'View More Categories' }}
        </a>
    </div>
</section>
