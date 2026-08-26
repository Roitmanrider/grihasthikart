@php($config = $config ?? [])
@php($dailyOfferConfig = $config['configuration'] ?? [])

<section class="gk-section gk-daily-offers">
    <div class="container">
        <div class="gk-daily-panel">
            <div class="gk-daily-heading">
                <h2><i class="fa-solid fa-stopwatch"></i> {{ $config['title'] ?? 'Daily Offers' }}</h2>
                @if (! empty($config['subtitle']))
                    <span class="gk-deal-timer">{{ $config['subtitle'] }}</span>
                @endif
                @if ($config['view_all_enabled'] ?? true)
                    <a href="{{ $config['view_all_url'] ?? route('daily-offers.index') }}">{{ $config['view_all_text'] ?? 'View All' }}</a>
                @endif
            </div>

            @if ($dailyOffers->isNotEmpty())
                <div class="gk-offer-carousel"
                     data-auto-slide="{{ ($dailyOfferConfig['auto_slide'] ?? true) ? '1' : '0' }}"
                     data-slide-interval="{{ max(3, min(15, (int) ($dailyOfferConfig['slide_interval'] ?? 5))) }}">
                    <button class="gk-offer-arrow gk-offer-arrow-prev" type="button" aria-label="Previous Daily Offer">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <div class="gk-offer-track" tabindex="0">
                        @foreach ($dailyOffers as $dailyOffer)
                            @include('components.offer-card', ['dailyOffer' => $dailyOffer])
                        @endforeach
                    </div>
                    <button class="gk-offer-arrow gk-offer-arrow-next" type="button" aria-label="Next Daily Offer">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
                <div class="gk-offer-condition">Offer price applies while allocated Daily Offer stock is available.</div>
            @else
                <div class="alert alert-light border mb-0">Daily offers coming soon.</div>
            @endif
        </div>
    </div>
</section>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('.gk-offer-carousel').forEach((carousel) => {
                    const track = carousel.querySelector('.gk-offer-track');
                    const previous = carousel.querySelector('.gk-offer-arrow-prev');
                    const next = carousel.querySelector('.gk-offer-arrow-next');
                    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    let timer = null;

                    if (!track || !previous || !next) {
                        return;
                    }

                    const cardStep = () => {
                        const card = track.querySelector('.gk-offer-card');

                        return card ? card.getBoundingClientRect().width + 16 : track.clientWidth;
                    };

                    const slide = (direction) => {
                        track.scrollBy({left: direction * cardStep(), behavior: prefersReducedMotion ? 'auto' : 'smooth'});
                    };

                    previous.addEventListener('click', () => slide(-1));
                    next.addEventListener('click', () => slide(1));

                    const stop = () => {
                        if (timer) {
                            clearInterval(timer);
                            timer = null;
                        }
                    };

                    const start = () => {
                        stop();

                        if (prefersReducedMotion || carousel.dataset.autoSlide !== '1') {
                            return;
                        }

                        timer = setInterval(() => {
                            const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
                            track.scrollTo({left: atEnd ? 0 : track.scrollLeft + cardStep(), behavior: 'smooth'});
                        }, Number(carousel.dataset.slideInterval || 5) * 1000);
                    };

                    carousel.addEventListener('mouseenter', stop);
                    carousel.addEventListener('mouseleave', start);
                    carousel.addEventListener('focusin', stop);
                    carousel.addEventListener('focusout', start);
                    carousel.addEventListener('pointerdown', stop);
                    carousel.addEventListener('pointerup', start);
                    start();
                });
            });
        </script>
    @endpush
@endonce
