document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-catalog-search]').forEach((form) => {
        const input = form.querySelector('[data-catalog-search-input]');
        const panel = form.querySelector('[data-catalog-suggestions]');
        const endpoint = form.dataset.autocompleteUrl;
        let timer = null;
        let requestId = 0;

        if (!input || !panel || !endpoint) {
            return;
        }

        const hide = () => {
            panel.classList.add('d-none');
            panel.innerHTML = '';
        };

        input.addEventListener('input', () => {
            window.clearTimeout(timer);
            const query = input.value.trim().replace(/\s+/g, ' ');

            if (query.length < 2) {
                hide();
                return;
            }

            timer = window.setTimeout(async () => {
                const currentRequest = ++requestId;
                const url = endpoint + '?q=' + encodeURIComponent(query);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });

                if (currentRequest !== requestId || !response.ok) {
                    return;
                }

                const payload = await response.json();
                const suggestions = payload.data || [];

                if (!suggestions.length) {
                    hide();
                    return;
                }

                panel.innerHTML = suggestions.map((suggestion) => {
                    const label = String(suggestion.label || '').replace(/[&<>"']/g, (character) => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;',
                    })[character]);
                    const meta = String(suggestion.meta || '').replace(/[&<>"']/g, (character) => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;',
                    })[character]);

                    return `<a href="${suggestion.url}" class="gk-search-suggestion"><strong>${label}</strong><span>${meta}</span></a>`;
                }).join('');
                panel.classList.remove('d-none');
            }, 300);
        });

        document.addEventListener('click', (event) => {
            if (!form.contains(event.target)) {
                hide();
            }
        });
    });
});
