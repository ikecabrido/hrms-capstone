<?php
/**
 * AJAX — mark notifications read for the signed-in employee.
 *
 * The inbox page is shared by every learning role (classes/notificationinbox.php) and so is
 * this: "mark read" is the same session-scoped UPDATE whoever asks. It replaces the four
 * per-role copies (learner/ and instructor/ each had a mark-notification-read.php and a
 * mark-all-notification-read.php) that the three pages used to call across each other's
 * trees — the admin page was posting to the instructor endpoints.
 *
 * POST id=<notification id>  — mark one row
 * POST all=1                 — mark every unread row for this employee
 *
 * The update is scoped to the session employee id, so a request cannot touch somebody
 * else's inbox whatever id it sends.
 */
require_once dirname(__DIR__, 5) . '/auth/session.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST required.']);
    exit;
}

$employeeId = (int) ($_SESSION['employee_id'] ?? 0);
if ($employeeId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not signed in.']);
    exit;
}

try {
    require_once dirname(__DIR__, 5) . '/database/db.php';
    require_once dirname(__DIR__, 5) . '/includes/Notification.php';

    $notifications = new Notification((new Database())->getConnection());

    if (!empty($_POST['all'])) {
        echo json_encode(['success' => true, 'updated' => $notifications->markAllAsRead($employeeId)]);
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'A notification id (or all=1) is required.']);
        exit;
    }

    echo json_encode(['success' => true, 'updated' => $notifications->markAsRead($employeeId, $id)]);
} catch (Throwable $e) {
    error_log('mark-notification.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
