<?php
$currentAdminPage = (string) ($_GET['page'] ?? '');
?>
<div class="user-admin-navigation user-admin-layout--collapsed">
    <aside class="user-admin-sidebar" aria-label="User administration navigation">
        <button type="button" class="user-admin-sidebar-collapse" aria-label="Hide user navigation" title="Hide user navigation">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>
        <a class="user-admin-nav-link<?= $currentAdminPage === 'admin/user' ? ' active' : '' ?>" href="?page=admin/user">
            <i class="fas fa-users" aria-hidden="true"></i>
            <span>Users</span>
        </a>
        <a class="user-admin-nav-link<?= $currentAdminPage === 'admin/knowledge-transfer' ? ' active' : '' ?>" href="?page=admin/knowledge-transfer">
            <i class="fas fa-share-nodes" aria-hidden="true"></i>
            <span>Knowledge Transfer</span>
        </a>
    </aside>
    <button type="button" class="user-admin-sidebar-expand" aria-label="Show user navigation" title="Show user navigation">
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
    </button>
</div>

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
