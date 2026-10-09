<?php
$currentAdminPage = (string) ($_GET['page'] ?? '');
?>
<div class="user-admin-navigation user-admin-layout--collapsed">
    <aside class="user-admin-sidebar" aria-label="User administration navigation">
        <button type="button" class="user-admin-sidebar-collapse" aria-label="Hide user navigation" title="Hide user navigation">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>
        <a class="user-admin-nav-link<?= $currentAdminPage === 'admin/user' ? ' active' : '' ?>" href="?page=admin/user">Users</a>
        <a class="user-admin-nav-link<?= $currentAdminPage === 'admin/knowledge-transfer' ? ' active' : '' ?>" href="?page=admin/knowledge-transfer">Knowledge Transfer</a>
    </aside>
    <button type="button" class="user-admin-sidebar-expand" aria-label="Show user navigation" title="Show user navigation">
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
    </button>
</div>

<style>
    .user-admin-sidebar {
        position: fixed;
        top: calc(var(--header-height, 60px) + 48px);
        left: var(--user-admin-sidebar-left, 252px);
        z-index: 1100;
        box-sizing: border-box;
        width: 220px;
        padding: 0.5rem;
        border: 1px solid rgba(32,0,130,0.08);
        border-radius: 14px;
        background: #fff !important;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        box-shadow: 0 12px 32px rgba(18, 8, 54, 0.2);
        opacity: 1;
        transform: translateX(0);
        visibility: visible;
        transition: opacity 0.16s ease, transform 0.18s ease, visibility 0s;
    }
    .user-admin-nav-link {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        padding: 0.75rem 0.9rem;
        border: 1px solid rgba(32,0,130,0.12);
        border-radius: 10px;
        color: var(--text);
        font-size: 0.82rem;
        font-weight: 700;
        text-decoration: none;
    }
    .user-admin-nav-link.active {
        background: var(--primary);
        color: #fff;
    }
    .user-admin-sidebar-collapse,
    .user-admin-sidebar-expand {
        width: 32px;
        height: 32px;
        padding: 0;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(32,0,130,0.14);
        border-radius: 8px;
        background: #fff;
        color: var(--primary);
        cursor: pointer;
    }
    .user-admin-sidebar-collapse { transition: background-color 0.16s ease, color 0.16s ease; }
    .user-admin-sidebar-collapse:hover,
    .user-admin-sidebar-expand:hover {
        background: rgba(32,0,130,0.06);
    }
    .user-admin-sidebar-collapse { display: inline-flex; align-self: flex-end; }
    .user-admin-sidebar-expand {
        position: fixed;
        top: calc(var(--header-height, 60px) + 48px);
        left: var(--user-admin-sidebar-left, 252px);
        z-index: 1101;
        display: inline-flex;
        opacity: 0;
        transform: translateX(-6px);
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.16s ease, transform 0.18s ease, visibility 0s linear 0.18s;
    }
    .user-admin-navigation.user-admin-layout--collapsed .user-admin-sidebar {
        opacity: 0;
        transform: translateX(-10px);
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.16s ease, transform 0.18s ease, visibility 0s linear 0.18s;
    }
    .user-admin-navigation.user-admin-layout--collapsed .user-admin-sidebar-expand {
        opacity: 1;
        transform: translateX(0);
        visibility: visible;
        pointer-events: auto;
        transition: opacity 0.16s ease, transform 0.18s ease, visibility 0s;
    }
    @media (max-width: 768px) {
        .user-admin-sidebar { top: calc(var(--header-height, 60px) + 48px); }
    }
</style>

<script>
(function() {
    function initializeUserNavigation() {
        var navigation = document.querySelector('.user-admin-navigation');
        if (!navigation) return;

        var collapseButton = navigation.querySelector('.user-admin-sidebar-collapse');
        var expandButton = navigation.querySelector('.user-admin-sidebar-expand');
        var appSidebar = document.querySelector('.sidebar');

        function syncPosition() {
            var sidebarWidth = 0;
            if (window.innerWidth > 768 && appSidebar && !appSidebar.classList.contains('hidden')) {
                sidebarWidth = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width'), 10) || 252;
            }
            document.documentElement.style.setProperty('--user-admin-sidebar-left', sidebarWidth + 'px');
        }

        function setCollapsed(collapsed) {
            navigation.classList.toggle('user-admin-layout--collapsed', collapsed);
            if (collapseButton) collapseButton.setAttribute('aria-expanded', String(!collapsed));
            if (expandButton) expandButton.setAttribute('aria-expanded', String(collapsed));
        }

        if (collapseButton) collapseButton.addEventListener('click', function() { setCollapsed(true); });
        if (expandButton) expandButton.addEventListener('click', function() { setCollapsed(false); });

        setCollapsed(true);
        syncPosition();
        window.addEventListener('resize', syncPosition);
        if (appSidebar) {
            new MutationObserver(syncPosition).observe(appSidebar, { attributes: true, attributeFilter: ['class'] });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeUserNavigation, { once: true });
    } else {
        initializeUserNavigation();
    }
})();
</script>