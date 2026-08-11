<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
$isLocked = $lockedUntil > time();

?>

<?php if ($isLocked): ?>

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
                <span id="lockoutTimer">60</span>
                seconds.
            </div>

        </div>

    </div>

<?php endif; ?>