<?php
/**
 * Shared display-preference reader for every Learning role.
 *
 * The store is employee-scoped, so an admin, an instructor and a learner each
 * read their own list length and default card view here. It used to sit under
 * pages/learner/ajax/, which made a module-wide preference look learner-only.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/displaypreference.php';

$employeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

if ($employeeId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();

    // Returns what the module will actually do for this employee: their saved
    // row when they have one, the module default from Admin > Settings otherwise.
    echo json_encode([
        'success' => true,
        'preferences' => DisplayPreference::effective($pdo, $employeeId),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
