<script>
(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!form || !form.classList.contains('cd-exit-clearance-form')) {
            return;
        }

        var dateInput = form.querySelector('input[name="exit_date"]');

        if (!dateInput || !dateInput.value) {
            return;
        }

        var date = dateInput.value;

        /*
         * Keep exit_date available globally so the preview-generation
         * code can reuse it after the drawer is refreshed.
         */
        window.exitClearanceDate = date;

        /*
         * Persist the selected date for this browser session.
         */
        try {
            sessionStorage.setItem('exit_clearance_date', date);
        } catch (e) {}

        /*
         * Make sure the current URL contains exit_date.
         */
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('exit_date', date);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    });

    /*
     * Restore the date if the drawer/page is refreshed.
     */
    document.addEventListener('DOMContentLoaded', function () {
        var input = document.querySelector(
            '.cd-exit-clearance-form input[name="exit_date"]'
        );

        if (!input) {
            return;
        }

        var date = '';

        try {
            date = sessionStorage.getItem('exit_clearance_date') || '';
        } catch (e) {}

        if (!date) {
            try {
                var url = new URL(window.location.href);
                date = url.searchParams.get('exit_date') || '';
            } catch (e) {}
        }

        if (date) {
            input.value = date;
            window.exitClearanceDate = date;
        }
    });
})();
</script>
