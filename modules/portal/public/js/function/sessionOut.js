const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutes

    let inactivityTimer;

    function resetInactivityTimer() {
        clearTimeout(inactivityTimer);

        inactivityTimer = setTimeout(function () {
            window.location.href =
                'index.php?url=auth-logout&timeout=1';
        }, SESSION_TIMEOUT);
    }

    // User activity
    document.addEventListener('mousemove', resetInactivityTimer);
    document.addEventListener('mousedown', resetInactivityTimer);
    document.addEventListener('keydown', resetInactivityTimer);
    document.addEventListener('scroll', resetInactivityTimer);
    document.addEventListener('touchstart', resetInactivityTimer);

    // Start timer
    resetInactivityTimer();