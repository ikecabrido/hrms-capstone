<?php require_once __DIR__ . '/ta_paths.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/../css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Human Resources Management System</title>
    <?php if (!empty($page_head_extra)) { echo $page_head_extra; } ?>
</head>
<body>
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
    <header>
        <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
        </div>
        <div class="realtime" id="realtimeClock" aria-live="polite">--:-- </div>
    </header>
