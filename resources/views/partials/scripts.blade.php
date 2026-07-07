<script src="{{ asset('assets/js/main.js') }}"></script>
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
</script>
