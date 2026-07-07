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
});
