<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
$remaining = max(0, $lockedUntil - time());

?>

<?php if ($remaining > 0): ?>

    <div class="login-lockout">

        <div class="lockout-icon">
            <i class="fas fa-lock"></i>
        </div>

        <div class="lockout-content">

            <strong>Login Temporarily Locked</strong>

            <p>
                Too many failed login attempts.
            </p>

            <div class="lockout-timer">
                Please try again in
                <span id="lockoutTimer"><?= $remaining ?></span>
                seconds.
            </div>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const timer = document.getElementById('lockoutTimer');

            if (!timer) {
                return;
            }

            let remaining = <?= $remaining ?>;

            function updateCountdown() {

                timer.textContent = remaining;

                if (remaining <= 0) {
                    window.location.reload();
                    return;
                }

                remaining--;

                setTimeout(updateCountdown, 1000);
            }

            updateCountdown();

        });
    </script>

<?php endif; ?>