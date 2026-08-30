<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

if (!isset($_SESSION['employee_id'])) {
	http_response_code(401);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'redirect' => 'login.php']);
	exit();
}

require_once 'classes/Page.php';

$pageController = new Page();

header('Content-Type: text/html; charset=utf-8');
header('X-Rendered-Page: ' . $pageController->getPage());

$pageController->render();
exit;