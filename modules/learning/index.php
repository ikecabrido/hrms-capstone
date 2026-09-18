<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'classes/Page.php';
require_once 'classes/CSRF.php';

$pageController = new Page();
$currentPage = $pageController->getPage();

// Ensure CSRF token exists
$csrfToken = CSRF::getToken();

// Default items-per-page, configurable from Admin > Settings > General.
$ldDefaultPageSize = 12;
try {
    require_once 'classes/Setting.php';
    $ldSettingClass = new Setting();
    $ldDefaultPageSize = (int) ($ldSettingClass->get('default_page_size') ?: 12);
    if (!in_array($ldDefaultPageSize, [12, 24, 36], true)) {
        $ldDefaultPageSize = 12;
    }
} catch (Throwable $e) {
    $ldDefaultPageSize = 12;
}

include 'includes/sidebar.php';
include 'includes/header.php';
?>

<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
<script>window.LD_DEFAULT_PAGE_SIZE = <?= (int) $ldDefaultPageSize ?>; window.CSRF_TOKEN = <?= json_encode($csrfToken) ?>;</script>

<main class="main-content">
    <div class="container" data-page="<?= htmlspecialchars($currentPage) ?>">
        <?php $pageController->render(); ?>
    </div>
</main>

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
                // Update active sidebar link
                document.querySelectorAll('.menu-link, .active-menu-link').forEach(function(el) {
                    el.className = 'menu-link';
                });
                var active = document.querySelector('a[data-page="' + result.rendered + '"]');
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
                var active = document.querySelector('a[data-page="' + page + '"]');
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
    fetch('pages/learner/ajax/get-pending-notification.php', getFetchOptions())
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