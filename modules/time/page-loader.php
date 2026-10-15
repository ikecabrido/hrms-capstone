<?php
require_once __DIR__ . '/includes/ta_paths.php';
require_once __DIR__ . '/../../auth/guard.php';
require_once 'classes/Page.php';

$pageController = new Page();

header('Content-Type: text/html; charset=utf-8');
header('X-Rendered-Page: ' . $pageController->getPage());
?>
<script>
    window.__TA_ROOT = window.__TA_ROOT || <?= json_encode(ta_base_path(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.__TA_ROOT = String(window.__TA_ROOT || '').replace(/\/+$/, '');
    window.taJoinUrl = function(base, path) {
        const root = String(base || '').replace(/\/+$/, '');
        const suffix = String(path || '').replace(/^\/+/, '');
        if (!root && !suffix) return '';
        if (!root) return '/' + suffix;
        return suffix ? root + '/' + suffix : root;
    };
    window.__TA_API_ROOT = window.__TA_API_ROOT || window.taJoinUrl(window.__TA_ROOT, 'app/api');
</script>
<?php
$pageController->render();
exit;