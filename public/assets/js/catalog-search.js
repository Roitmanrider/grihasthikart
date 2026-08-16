document.addEventListener('DOMContentLoaded', () => {
    const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);

    const escapeAttribute = (value) => escapeHtml(value).replace(/`/g, '&#096;');
    const typeLabels = {
        product: 'Products',
        category: 'Categories/Subcategories',
        brand: 'Brands',
    };

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

                const groups = suggestions.reduce((carry, suggestion) => {
                    const type = suggestion.type || 'product';
                    carry[type] = carry[type] || [];
                    carry[type].push(suggestion);
                    return carry;
                }, {});

                panel.innerHTML = Object.keys(groups).map((type) => {
                    const rows = groups[type].map((suggestion) => {
                        const label = escapeHtml(suggestion.label);
                        const meta = escapeHtml(suggestion.meta || typeLabels[type] || 'Suggestion');
                        const url = escapeAttribute(suggestion.url || '#');

                        return `<a href="${url}" class="gk-search-suggestion">
                            <span class="gk-search-suggestion-main">${label}</span>
                            <span class="gk-search-suggestion-meta">${meta}</span>
                        </a>`;
                    }).join('');

                    return `<div class="gk-search-suggestion-group">
                        <div class="gk-search-suggestion-heading">${escapeHtml(typeLabels[type] || 'Suggestions')}</div>
                        ${rows}
                    </div>`;
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
