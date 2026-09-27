const form     = document.getElementById('loginForm');
const errorMsg = document.getElementById('errorMsg');
const loginBtn = document.getElementById('loginBtn');
const pass = document.getElementById('password');

let countdownInterval = null;

function showError(message, isLocked = false) {
    errorMsg.innerHTML = message;
    errorMsg.className = 'error-message show' + (isLocked ? ' locked' : '');
}

function hideError() {
    errorMsg.className   = 'error-message';
    errorMsg.textContent = '';
}

function setLoading(loading) {
    loginBtn.disabled    = loading;
    loginBtn.textContent = loading ? 'Logging in...' : 'Login';
}

function formatTime(secs) {
    const m = Math.floor(secs / 60).toString().padStart(2, '0');
    const s = (secs % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
}

function startLockoutTimer(seconds) {
    if (countdownInterval) clearInterval(countdownInterval);

    // Guard against NaN / invalid values
    let remaining = parseInt(seconds);
    if (isNaN(remaining) || remaining <= 0) {
        showError('🔒 Too many failed attempts. Please wait before trying again.', true);
        loginBtn.disabled = true;
        return;
    }

    loginBtn.disabled = true;

    function updateDisplay() {
        showError(
            `🔒 Too many failed attempts. Please try again in <strong>${formatTime(remaining)}</strong>`,
            true
        );
    }


    updateDisplay();

    countdownInterval = setInterval(() => {
        remaining--;

        if (remaining <= 0) {
            clearInterval(countdownInterval);
            countdownInterval = null;
            loginBtn.disabled = false;
            hideError();
        } else {
            updateDisplay();
        }
    }, 1000);
}

form.addEventListener('submit', async function (e) {
    e.preventDefault();

    // Block submission if already locked
    if (countdownInterval) return;

    hideError();
    setLoading(true);

    try {
        const response = await fetch('auth/login.php', {   // no leading slash
        method: 'POST',
        body: new FormData(form)
    });

        // Guard against non-JSON responses (e.g. PHP errors)
        const text = await response.text();

        console.log('HTTP Status:', response.status);
        console.log('Login response:', text);

        let data;

        try {
            data = JSON.parse(text);
        } catch (error) {
            console.error('JSON parse error:', error);
            console.error('Raw server response:', text);

            showError('Server error. Please try again.');
            return;
        }

        if (data.success) {
            window.location.href = data.redirect;
        } else if (data.locked) {
            startLockoutTimer(parseInt(data.remaining_seconds));
        } else {
            const attemptsLeft = data.remaining_attempts;
            const suffix = attemptsLeft !== undefined
                ? ` (${attemptsLeft} attempt${attemptsLeft !== 1 ? 's' : ''} left)`
                : '';
            showError(`${data.message}${suffix}`, false);
        }

    } catch (err) {
        showError('Something went wrong. Please try again.');
    } finally {
        setLoading(false);
    }
});