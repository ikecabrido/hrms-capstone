<?php
/**
 * Shared course navigation sidebar footer — JS only.
 */
?>

<script>
/* Module accordion */
function studyToggleModule(header) {
    var items = header.nextElementSibling;
    var chevron = header.querySelector('.study-sidebar-chevron');
    if (!items) return;
    if (items.classList.contains('expanded')) {
        items.classList.remove('expanded');
        if (chevron) chevron.classList.remove('open');
    } else {
        items.classList.add('expanded');
        if (chevron) chevron.classList.add('open');
    }
}

/* Open / close sidebar */
function studyToggleSidebar() {
    var sidebar = document.getElementById('studySidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (!sidebar) return;
    if (sidebar.classList.contains('open')) {
        studyCloseSidebar();
    } else {
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('visible');
        document.body.style.overflow = 'hidden';
    }
}

function studyCloseSidebar() {
    var sidebar = document.getElementById('studySidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('visible');
    document.body.style.overflow = '';
}

/* Close button inside sidebar */
document.addEventListener('click', function(e) {
    if (e.target.closest('#sidebarCloseBtn')) studyCloseSidebar();
});

/* Close on overlay click */
document.addEventListener('click', function(e) {
    if (e.target.id === 'sidebarOverlay') studyCloseSidebar();
});

/* Escape to close */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') studyCloseSidebar();
});

/* ── Sync course panel position with nav sidebar state ──────── */
(function() {
    var navSidebar = document.querySelector('.sidebar');
    if (!navSidebar) return;

    function getNavWidth() {
        return navSidebar.classList.contains('hidden') ? 0 : 252;
    }

    function syncPositions() {
        var w = getNavWidth();
        var panel = document.getElementById('studySidebar');
        var btn = document.getElementById('studyToggleBtn');
        var overlay = document.getElementById('sidebarOverlay');
        if (panel) panel.style.left = w + 'px';
        if (btn) btn.style.left = w + 'px';
        if (overlay) overlay.style.left = w + 'px';
    }

    /* Watch for class changes on the nav sidebar */
    var observer = new MutationObserver(syncPositions);
    observer.observe(navSidebar, { attributes: true, attributeFilter: ['class'] });

    /* Also hook into the hamburger click as a safety net */
    var hamburger = document.querySelector('.hamburger');
    if (hamburger) hamburger.addEventListener('click', function() {
        setTimeout(syncPositions, 50);
    });

    /* Initial sync */
    syncPositions();
})();
</script>
