<?php $adminModerationPage = (string) ($_GET['page'] ?? ''); ?>
<div class="admin-data-navigation admin-data-navigation--collapsed">
    <aside class="admin-data-sidebar" aria-label="Moderation and data tools">
        <button type="button" class="admin-data-sidebar-collapse" aria-label="Hide moderation tools" title="Hide moderation tools">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>
        <h2>Moderation tools</h2>
        <a class="admin-data-sidebar-link<?= $adminModerationPage === 'admin/moderation' ? ' active' : '' ?>" href="?page=admin/moderation">
            <i class="fas fa-shield-alt" aria-hidden="true"></i> Report review
        </a>
        <a class="admin-data-sidebar-link<?= $adminModerationPage === 'admin/database-export' ? ' active' : '' ?>" href="?page=admin/database-export">
            <i class="fas fa-database" aria-hidden="true"></i> Export LD Database
        </a>
    </aside>
    <button type="button" class="admin-data-sidebar-expand" aria-label="Show moderation tools" title="Show moderation tools">
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
    </button>
</div>

<style>
    .admin-data-layout {
        display: block;
    }
    .admin-data-navigation {
        position: fixed;
        top: calc(var(--header-height, 60px) + 1rem);
        left: var(--admin-data-sidebar-left, var(--sidebar-width, 252px));
        z-index: 1001;
        width: 220px;
        transition: left 0.3s ease;
    }
    .admin-data-navigation.admin-data-navigation--collapsed { width: 34px; }
    .admin-data-sidebar {
        box-sizing: border-box;
        display: grid;
        gap: 0.4rem;
        padding: 0.75rem;
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 8px;
        background: var(--surface, #fff);
    }
    .admin-data-sidebar-collapse,
    .admin-data-sidebar-expand {
        width: 32px;
        height: 32px;
        padding: 0;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 6px;
        background: var(--surface, #fff);
        color: var(--primary);
        cursor: pointer;
    }
    .admin-data-sidebar-collapse { display: inline-flex; justify-self: end; }
    .admin-data-sidebar-expand {
        display: none;
        position: absolute;
        top: 0;
        left: 0;
    }
    .admin-data-sidebar h2 {
        margin: 0 0 0.35rem;
        padding: 0.3rem 0.45rem;
        color: var(--muted, #6b7280);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .admin-data-sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.7rem 0.75rem;
        border-radius: 6px;
        color: var(--text, #222);
        font-size: 0.84rem;
        font-weight: 600;
        text-decoration: none;
    }
    .admin-data-sidebar-link.active {
        background: var(--primary, #200082);
        color: #fff;
    }
    .admin-data-layout > .module-content,
    .admin-data-layout > main.module-content { min-width: 0; }
    .admin-data-navigation.admin-data-navigation--collapsed .admin-data-sidebar {
        width: 34px;
        padding: 0.1rem;
        overflow: hidden;
    }
    .admin-data-navigation.admin-data-navigation--collapsed .admin-data-sidebar h2,
    .admin-data-navigation.admin-data-navigation--collapsed .admin-data-sidebar-link,
    .admin-data-navigation.admin-data-navigation--collapsed .admin-data-sidebar-collapse { display: none; }
    .admin-data-navigation.admin-data-navigation--collapsed .admin-data-sidebar-expand {
        display: inline-flex;
        position: relative;
    }
    @media (max-width: 768px) {
        .admin-data-navigation { top: calc(var(--header-height, 60px) + 0.5rem); }
    }
</style>

<script>
(function() {
    function initializeAdminDataNavigation() {
        var navigation = document.querySelector('.admin-data-navigation');
        if (!navigation) return;
        var collapseButton = navigation.querySelector('.admin-data-sidebar-collapse');
        var expandButton = navigation.querySelector('.admin-data-sidebar-expand');
        var appSidebar = document.querySelector('.sidebar');

        function syncPosition() {
            var sidebarWidth = 0;
            if (appSidebar && !appSidebar.classList.contains('hidden')) {
                sidebarWidth = appSidebar.getBoundingClientRect().width;
            }
            document.documentElement.style.setProperty('--admin-data-sidebar-left', sidebarWidth + 'px');
        }

        function setCollapsed(collapsed) {
            navigation.classList.toggle('admin-data-navigation--collapsed', collapsed);
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
        document.addEventListener('DOMContentLoaded', initializeAdminDataNavigation, { once: true });
    } else {
        initializeAdminDataNavigation();
    }
})();
</script>