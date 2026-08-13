<?php
/**
 * DEPRECATED: Use app/cron/detect_attendance_status.php instead.
 *
 * This file is kept for backward compatibility and now only reports deprecation.
 */

header('Content-Type: application/json');

echo json_encode([
    'success' => false,
    'deprecated' => true,
    'message' => 'detect_absences.php is deprecated. Use app/cron/detect_attendance_status.php instead.'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

exit(0);
?>

