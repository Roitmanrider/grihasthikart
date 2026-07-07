<section class="gk-section gk-daily-offers">
    <div class="container">
        <div class="gk-daily-panel">
            <div class="gk-daily-heading">
                <h2><i class="fa-solid fa-stopwatch"></i> Daily Offers</h2>
                <span class="gk-deal-timer">Deal Expires In <strong>29m 49s</strong></span>
                <a href="{{ route('products.index') }}">View All</a>
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
