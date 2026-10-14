<?php
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/auth/logout.php');
$modulesPosition = strpos($scriptPath, '/modules/');
$appBasePath = $modulesPosition === false ? rtrim(dirname(dirname($scriptPath)), '/') : substr($scriptPath, 0, $modulesPosition);

// Clear the saved page cookie before destroying session
setcookie('lastEngagementPage', '', time() - 3600, $appBasePath . '/modules/engagement/');
setcookie('openModal', '', time() - 3600, $appBasePath . '/modules/engagement/');

session_start();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging Out...</title>
</head>
<body>
    <script>
        // Clear saved tab states so a fresh login starts on the default tab
        try {
            const tabKeys = [
                'communication-active-tab',
                'engagement:communication:active-tab',
                'survey-active-tab',
                'engagement:survey:active-tab',
                'social-active-tab',
                'socialPageActiveTab',
                'engagement:social:active-tab',
                'recognition-active-tab',
                'engagement:recognition:active-tab',
                'grievance-active-tab',
                'engagement:grievance:active-tab',
                'dashboard-active-tab',
                '__hrms_session_id__'
            ];

            tabKeys.forEach(function (key) {
                try {
                    sessionStorage.removeItem(key);
                    localStorage.removeItem(key);
                } catch (e) {
                    // Ignore storage errors.
                }
            });

            const sessionId = document.cookie.split('; ').find(function (cookie) {
                return cookie.indexOf('PHPSESSID=') === 0;
            });

            if (sessionId) {
                const sessionValue = sessionId.split('=').slice(1).join('=');
                const scopedKeys = [
                    'communication-active-tab:' + sessionValue,
                    'engagement:communication:active-tab:' + sessionValue,
                    'survey-active-tab:' + sessionValue,
                    'engagement:survey:active-tab:' + sessionValue,
                    'social-active-tab:' + sessionValue,
                    'socialPageActiveTab:' + sessionValue,
                    'engagement:social:active-tab:' + sessionValue,
                    'recognition-active-tab:' + sessionValue,
                    'engagement:recognition:active-tab:' + sessionValue,
                    'grievance-active-tab:' + sessionValue,
                    'engagement:grievance:active-tab:' + sessionValue
                ];

                scopedKeys.forEach(function (key) {
                    try {
                        sessionStorage.removeItem(key);
                        localStorage.removeItem(key);
                    } catch (e) {
                        // Ignore storage errors.
                    }
                });
            }
        } catch (e) {
            // Ignore errors
        }

        // Redirect to login page
        window.location.href = <?= json_encode(($appBasePath ?: '') . '/index.php') ?>;
    </script>
</body>
</html>
