<?php $adminModerationPage = (string) ($_GET['page'] ?? ''); ?>
<div class="admin-data-navigation admin-data-navigation--collapsed">
    <aside class="admin-data-sidebar" aria-label="Moderation and data tools">
        <button type="button" class="admin-data-sidebar-collapse" aria-label="Hide moderation tools" title="Hide moderation tools">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>
        <h2>Moderation tools</h2>
        <a class="admin-data-sidebar-link<?= $adminModerationPage === 'admin/moderation' ? ' active' : '' ?>" href="?page=admin/moderation"<?= $adminModerationPage === 'admin/moderation' ? ' aria-current="page"' : '' ?>>
            <i class="fas fa-shield-halved" aria-hidden="true"></i>
            <span>Report review</span>
        </a>
        <a class="admin-data-sidebar-link<?= $adminModerationPage === 'admin/database-export' ? ' active' : '' ?>" href="?page=admin/database-export"<?= $adminModerationPage === 'admin/database-export' ? ' aria-current="page"' : '' ?>>
            <i class="fas fa-database" aria-hidden="true"></i>
            <span>Export LD Database</span>
        </a>
    </aside>
    <button type="button" class="admin-data-sidebar-expand" aria-label="Show moderation tools" title="Show moderation tools">
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
    </button>
</div>

<?php /* Styling lives in css/pages/moderation.css (loaded by includes/header.php). */ ?>
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
            if (window.innerWidth > 768 && appSidebar && !appSidebar.classList.contains('hidden')) {
                sidebarWidth = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width'), 10) || 252;
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
