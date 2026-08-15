@php($config = $config ?? [])

<section class="gk-section gk-daily-offers">
    <div class="container">
        <div class="gk-daily-panel">
            <div class="gk-daily-heading">
                <h2><i class="fa-solid fa-stopwatch"></i> {{ $config['title'] ?? 'Daily Offers' }}</h2>
                <span class="gk-deal-timer">{{ $config['subtitle'] ?? 'Fresh deals updated in '.config('app.timezone') }}</span>
                @if ($config['view_all_enabled'] ?? true)
                    <a href="{{ $config['view_all_url'] ?? route('daily-offers.index') }}">{{ $config['view_all_text'] ?? 'View All' }}</a>
                @endif
            </div>

            @if ($dailyOffers->isNotEmpty())
                <div class="gk-offer-track">
                    @foreach ($dailyOffers as $dailyOffer)
                        @include('components.offer-card', ['dailyOffer' => $dailyOffer])
                    @endforeach
                </div>
            @else
                <div class="alert alert-light border mb-0">Daily offers coming soon.</div>
            @endif
        </div>
    </div>
</section>
