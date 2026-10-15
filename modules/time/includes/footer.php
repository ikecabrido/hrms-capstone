<?php require_once __DIR__ . '/ta_paths.php'; ?>
<div>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Human Resources Management System. All rights reserved.</p>
    </footer>
</div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script type="module" src="js/script.js"></script>
    <?php if (!empty($page_footer_extra)) { echo $page_footer_extra; } ?>
</body>
</html>