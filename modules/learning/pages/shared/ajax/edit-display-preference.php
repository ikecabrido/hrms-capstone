<?php
/**
 * Shared display-preference writer for every Learning role.
 *
 * Any signed-in employee may change their own preferences; a save only ever
 * touches the row keyed by their own employee id. Both the profile card and the
 * in-page grid/list toggles post here, always with one field at a time so one
 * control cannot overwrite the other's value.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 4) . '/classes/CSRF.php';
require_once dirname(__FILE__, 4) . '/classes/displaypreference.php';
require_once dirname(__FILE__, 6) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

CSRF::requireValid();

try {
    $employeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($employeeId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $pdo = (new Database())->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $allowed = array_intersect_key($input, ['page_size' => true, 'view_mode' => true]);
    if (!$allowed) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'page_size or view_mode is required']);
        exit;
    }

    $result = DisplayPreference::set($pdo, $employeeId, $allowed);

    if (empty($result['success'])) {
        http_response_code(422);
    }

    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
