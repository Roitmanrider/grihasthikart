@php
    $mediaResolver = app(\App\Services\MediaResolver::class);
    $config = $config ?? [];
@endphp

<section class="gk-section gk-partners">
    <div class="container">
        <div class="gk-section-heading gk-heading-plain">
            <h2>{{ $config['title'] ?? 'Our Associated Partners' }}</h2>
            @if ($config['view_all_enabled'] ?? true)
                <a href="{{ $config['view_all_url'] ?? route('brands.index') }}">{{ $config['view_all_text'] ?? 'View All' }}</a>
            @endif
        </div>
        <div class="gk-partner-grid">
            @foreach ($partners as $partner)
                @php
                    $name = $partner->name ?? $partner['name'];
                    $url = $partner->external_url ?? route('brands.index');
                    $image = $partner->image_path ?? null;
                    $description = $partner['description'] ?? '';
                    $promo = $partner->promo_text ?? $partner['promo_text'] ?? '';
                    $class = $partner['class'] ?? '';
                @endphp
                <a href="{{ $url ?: route('brands.index') }}" class="gk-partner-card {{ $class }}" @if(! empty($partner->external_url)) target="_blank" rel="noopener noreferrer" @endif>
                    @if ($image)
                        <img src="{{ $mediaResolver->url($image) }}" alt="{{ $name }}" class="img-fluid mb-2" style="max-height: 64px; object-fit: contain;">
                    @endif
                    <strong>{{ $name }}</strong>
                    @if ($description)
                        <span>{{ $description }}</span>
                    @endif
                    @if ($promo)
                        <em>{{ $promo }}</em>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</section>
