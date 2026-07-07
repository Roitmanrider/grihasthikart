<section class="gk-section gk-partners">
    <div class="container">
        <div class="gk-section-heading gk-heading-plain">
            <h2>Our Associated Partners</h2>
            <a href="{{ route('brands.index') }}">View All</a>
        </div>
        <div class="gk-partner-grid">
            @foreach ($partners as $partner)
                <a href="{{ route('brands.index') }}" class="gk-partner-card {{ $partner['class'] }}">
                    <strong>{{ $partner['name'] }}</strong>
                    <span>{{ $partner['description'] }}</span>
                    <em>{{ $partner['discount'] }}</em>
                </a>
            @endforeach
        </div>
    </div>
</section>
