<section class="gk-section gk-trust-strip-section">
    <div class="container">
        <div class="gk-offers-strip gk-trust-strip">
            @foreach ($items as $item)
                <div>
                    <i class="{{ $item['icon'] }}"></i>
                    <strong>{{ $item['title'] }}</strong>
                    <span>{{ $item['subtitle'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>
