<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/guard.php';

// Module-level authorization: only System Administrator (1) or
// Employee Management Staff (3) may load this module's page fragments.
$ALLOWED_ROLE_IDS = [1, 3];
if (!isset($_SESSION['role_id']) || !in_array((int) $_SESSION['role_id'], $ALLOWED_ROLE_IDS, true)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You are not authorized to access this module.']);
    exit();
}

require_once 'classes/Page.php';

$pageController = new Page();

header('Content-Type: text/html; charset=utf-8');
header('X-Rendered-Page: ' . $pageController->getPage());

$pageController->render();
exit;
