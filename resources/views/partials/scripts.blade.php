<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="{{ asset('assets/js/catalog-search.js') }}"></script>
<script>
    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || form.dataset.noLoader === 'true') {
            return;
        }

        const overlay = document.querySelector('[data-loading-overlay]');

        if (overlay) {
            overlay.hidden = false;
        }
    });

    window.addEventListener('pageshow', function () {
        const overlay = document.querySelector('[data-loading-overlay]');

        if (overlay) {
            overlay.hidden = true;
        }

        document.querySelectorAll('button[disabled][data-loading-disabled="true"]').forEach((button) => {
            button.disabled = false;
            delete button.dataset.loadingDisabled;
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (window.bootstrap && typeof window.bootstrap.Tooltip === 'function') {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
                window.bootstrap.Tooltip.getOrCreateInstance(element);
            });
        }
    });
</script>
