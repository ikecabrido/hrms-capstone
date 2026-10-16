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

require_once __DIR__ . '/classes/UserSession.php';

// Refresh this session and reject it if it was signed out on another device.
try {
	$ldSessionPdo = (new Database())->getConnection();
	$ldSessionActive = (new UserSession($ldSessionPdo))->touch(
		(int) $_SESSION['employee_id'],
		isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
		$_SERVER['REMOTE_ADDR'] ?? null,
		$_SERVER['HTTP_USER_AGENT'] ?? null
	);
	if (!$ldSessionActive) {
		$_SESSION = [];
		session_destroy();
		http_response_code(401);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['success' => false, 'redirect' => 'login.php']);
		exit();
	}
} catch (Throwable $e) {
	// Session tracking is best-effort; never block the app on it.
}

require_once 'classes/Page.php';

$pageController = new Page();

header('Content-Type: text/html; charset=utf-8');
header('X-Rendered-Page: ' . $pageController->getPage());

$pageController->render();
exit;