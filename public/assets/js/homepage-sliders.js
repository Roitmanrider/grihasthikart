document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-homepage-slider]').forEach((track) => {
        const card = track.closest('.gk-home-slider-card, .gk-subcategory-body');
        const next = card?.querySelector('[data-slide-next]');
        const prev = card?.querySelector('[data-slide-prev]');
        const amount = () => Math.max(180, Math.floor(track.clientWidth * 0.8));

        next?.addEventListener('click', () => {
            track.scrollBy({ left: amount(), behavior: 'smooth' });
        });

        prev?.addEventListener('click', () => {
            track.scrollBy({ left: -amount(), behavior: 'smooth' });
        });
    });

    const allCategoriesTrack = document.querySelector('.gk-home-category-strip [data-homepage-slider]');

    if (allCategoriesTrack) {
        window.setInterval(() => {
            const maxScroll = allCategoriesTrack.scrollWidth - allCategoriesTrack.clientWidth;

            if (allCategoriesTrack.scrollLeft >= maxScroll - 4) {
                allCategoriesTrack.scrollTo({ left: 0, behavior: 'smooth' });
                return;
            }

            allCategoriesTrack.scrollBy({ left: 130, behavior: 'smooth' });
        }, 3000);
    }

    const hero = document.querySelector('.gk-home-hero');
    const heroCards = hero ? Array.from(hero.querySelectorAll('.gk-hero-card')) : [];

    if (heroCards.length > 1) {
        const dots = Array.from(hero.querySelectorAll('.gk-slider-dots span'));
        let activeIndex = 0;
        const showHero = (nextIndex) => {
            activeIndex = (nextIndex + heroCards.length) % heroCards.length;
            heroCards.forEach((card, index) => card.classList.toggle('d-none', index !== activeIndex));
            dots.forEach((dot, index) => dot.classList.toggle('active', index === activeIndex));
        };

        hero.querySelector('.gk-hero-arrow-right')?.addEventListener('click', () => showHero(activeIndex + 1));
        hero.querySelector('.gk-hero-arrow-left')?.addEventListener('click', () => showHero(activeIndex - 1));
    }
});
