document.addEventListener("DOMContentLoaded", function () {

    console.log("GrihasthiKart Loaded");

});
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-account-nav-row]').forEach((row) => {
        const active = row.querySelector('[data-account-nav-active]');
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let timer = null;

        const hasOverflow = () => row.scrollWidth > row.clientWidth + 8;
        const stop = () => {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        };

        if (active && hasOverflow()) {
            active.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', inline: 'center', block: 'nearest' });
        }

        if (!prefersReducedMotion && hasOverflow()) {
            timer = window.setInterval(() => {
                if (!hasOverflow()) {
                    stop();
                    return;
                }

                const firstLink = row.querySelector('.gk-account-nav-link');
                const step = firstLink ? Math.max(90, firstLink.getBoundingClientRect().width + 10) : 120;
                const maxScroll = row.scrollWidth - row.clientWidth;
                const next = row.scrollLeft + step >= maxScroll ? 0 : row.scrollLeft + step;
                row.scrollTo({ left: next, behavior: 'smooth' });
            }, 1400);
        }

        ['pointerdown', 'wheel', 'touchstart', 'keydown', 'focusin'].forEach((eventName) => {
            row.addEventListener(eventName, stop, { once: true, passive: true });
        });
    });
});
