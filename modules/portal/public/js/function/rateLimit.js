document.addEventListener('DOMContentLoaded', function () {

    const timer = document.getElementById('lockoutTimer');
    const form = document.getElementById('loginForm');

    if (!timer) {
        return;
    }

    const lockedUntil = window.loginLockedUntil || 0;

    function updateCountdown() {

        const remaining = Math.max(
            0,
            Math.ceil(
                (lockedUntil - Date.now()) / 1000
            )
        );

        timer.textContent = remaining;

        if (form) {

            const fields = form.querySelectorAll(
                'input, button'
            );

            fields.forEach(function (field) {
                field.disabled = remaining > 0;
            });
        }

        if (remaining <= 0) {

            window.location.reload();

            return;
        }

        setTimeout(updateCountdown, 250);
    }

    updateCountdown();

});