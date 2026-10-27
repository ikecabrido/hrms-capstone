<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit();
}

require_once dirname(__DIR__, 2) . '/database/db.php';
require_once __DIR__ . '/classes/UserSession.php';

// Register/refresh this session and make sure it has not been signed out from
// another device (profile → Security → Sign out other sessions).
try {
    $ldSessionPdo = (new Database())->getConnection();
    $ldSessionActive = (new UserSession($ldSessionPdo))->touch(
        (int) $_SESSION['employee_id'],
        isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    );
    if (!$ldSessionActive) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
        exit();
    }
} catch (Throwable $e) {
    // Session tracking is best-effort; never block the app on it.
}

require_once 'classes/Page.php';
require_once 'classes/CSRF.php';

$pageController = new Page();
$currentPage = $pageController->getPage();

// Ensure CSRF token exists
$csrfToken = CSRF::getToken();

// Display preferences: the employee's saved list length and default card view.
// page_size falls back to the module default from Admin > Settings > General;
// both are handed to the page scripts below (pagination reads
// LD_DEFAULT_PAGE_SIZE, the grid/list toggles read LD_DISPLAY_PREFS).
$ldDisplayPrefs = ['page_size' => 12, 'view_mode' => 'grid'];
try {
    require_once 'classes/displaypreference.php';
    $ldDisplayPrefs = DisplayPreference::effective(
        $ldSessionPdo ?? (new Database())->getConnection(),
        (int) $_SESSION['employee_id']
    );
} catch (Throwable $e) {
    // keep the built-in defaults
}
$ldDefaultPageSize = (int) $ldDisplayPrefs['page_size'];

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
<script>window.LD_DEFAULT_PAGE_SIZE = <?= (int) $ldDefaultPageSize ?>; window.LD_DISPLAY_PREFS = <?= json_encode($ldDisplayPrefs) ?>; window.CSRF_TOKEN = <?= json_encode($csrfToken) ?>;</script>

<main class="main-content">
    <div class="container" data-page="<?= htmlspecialchars($currentPage) ?>">
        <?php $pageController->render(); ?>
    </div>
</main>

<script>
(function() {
    var catalogScript = document.createElement('script');
    catalogScript.src = 'js/catalog-browse.js?v=<?= (int) filemtime(__DIR__ . '/js/catalog-browse.js') ?>';
    document.head.appendChild(catalogScript);
})();
</script>
<?php include 'includes/footer.php'; ?>

<script>
(function() {
    var csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';

    function getFetchOptions(extraHeaders = {}) {
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (csrfToken) headers['X-CSRF-Token'] = csrfToken;
        Object.assign(headers, extraHeaders);
        return { credentials: 'same-origin', headers: headers };
    }

    // ─── Sidebar Navigation Fallback ──────────────────────────────────────────
    // Ensures sidebar links navigate via AJAX even if the ES module fails to load.
    document.body.addEventListener('click', function(e) {
        var a = e.target.closest('a[data-page]');
        if (!a) return;
        a.dataset.navHandled = '1';
        e.preventDefault();
        var page = a.getAttribute('data-page');
        var container = document.querySelector('.container');
        if (!container) { window.location.href = a.href; return; }
        fetch('page-loader.php?page=' + encodeURIComponent(page), getFetchOptions())
            .then(function(r) {
                if (r.status === 401) return r.json().then(function(d) { window.location.href = d.redirect; });
                if (!r.ok) throw new Error('Network error');
                var rendered = r.headers.get('X-Rendered-Page') || page;
                return r.text().then(function(html) { return { html: html, rendered: rendered }; });
            })
            .then(function(result) {
                if (!result) return;
                while (container.firstChild) container.removeChild(container.firstChild);
                container.innerHTML = result.html;
                container.setAttribute('data-page', result.rendered);
                // Execute any inline scripts in the new content
                container.querySelectorAll('script').forEach(function(old) {
                    var s = document.createElement('script');
                    Array.from(old.attributes).forEach(function(attr) { s.setAttribute(attr.name, attr.value); });
                    s.textContent = old.textContent;
                    old.parentNode.replaceChild(s, old);
                });
                // Update active sidebar link (scoped to menu links so the profile block keeps its class)
                document.querySelectorAll('.menu-link, .active-menu-link').forEach(function(el) {
                    el.className = 'menu-link';
                });
                var active = document.querySelector('.sidebar a.menu-link[data-page="' + result.rendered + '"], .sidebar a.active-menu-link[data-page="' + result.rendered + '"]');
                if (active) active.className = 'active-menu-link';
                history.pushState({ page: result.rendered }, '', '?page=' + encodeURIComponent(result.rendered));
                window.dispatchEvent(new CustomEvent('page:loaded', { detail: { page: result.rendered } }));
            })
            .catch(function(err) {
                console.error('Sidebar nav failed, falling back to full reload', err);
                window.location.href = a.href;
            });
    });

    // ─── Back/Forward Browser Navigation ──────────────────────────────────────
    window.addEventListener('popstate', function(e) {
        var page = (e.state && e.state.page) || new URL(location).searchParams.get('page') || 'dashboard-overview';
        var container = document.querySelector('.container');
        if (!container) return;
        fetch('page-loader.php?page=' + encodeURIComponent(page), getFetchOptions())
            .then(function(r) { return r.text().then(function(html) { return html; }); })
            .then(function(html) {
                while (container.firstChild) container.removeChild(container.firstChild);
                container.innerHTML = html;
                container.setAttribute('data-page', page);
                // Re-execute inline scripts (same as sidebar nav)
                container.querySelectorAll('script').forEach(function(old) {
                    var s = document.createElement('script');
                    Array.from(old.attributes).forEach(function(attr) { s.setAttribute(attr.name, attr.value); });
                    s.textContent = old.textContent;
                    old.parentNode.replaceChild(s, old);
                });
                document.querySelectorAll('.menu-link, .active-menu-link').forEach(function(el) {
                    el.className = 'menu-link';
                });
                var active = document.querySelector('.sidebar a.menu-link[data-page="' + page + '"], .sidebar a.active-menu-link[data-page="' + page + '"]');
                if (active) active.className = 'active-menu-link';
                window.dispatchEvent(new CustomEvent('page:loaded', { detail: { page: page } }));
            })
            .catch(function() {});
    });

    // Apply saved theme on load
    var saved = localStorage.getItem('theme');
    if (saved === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }

    // Notification badge
    fetch('pages/shared/ajax/get-pending-notification.php', getFetchOptions())
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var count = data.total || 0;
            if (count > 0) {
                var links = document.querySelectorAll('.sidebar a[href*="notification"]');
                links.forEach(function(link) {
                    if (!link.querySelector('.nav-badge')) {
                        var badge = document.createElement('span');
                        badge.className = 'nav-badge';
                        badge.textContent = count > 99 ? '99+' : count;
                        link.style.position = 'relative';
                        link.appendChild(badge);
                    }
                });
            }
        })
        .catch(function() {});
})();
</script>