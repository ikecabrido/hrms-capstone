<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $adminId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
    $title = trim((string) ($_POST['title'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $audience = trim((string) ($_POST['audience'] ?? 'all'));
    $expiresAt = trim((string) ($_POST['expires_at'] ?? ''));

    if ($adminId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    if ($title === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Announcement title is required.']);
        exit;
    }

    if ($message === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Announcement message is required.']);
        exit;
    }

    if (!in_array($audience, ['all', 'instructor', 'learner', 'admin'], true)) {
        $audience = 'all';
    }

    $sql = 'INSERT INTO ld_announcement (title, message, audience, posted_by, expires_at) VALUES (:title, :message, :audience, :posted_by, :expires_at)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':message' => $message,
        ':audience' => $audience,
        ':posted_by' => $adminId,
        ':expires_at' => $expiresAt !== '' ? $expiresAt : null,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Announcement created successfully.',
        'id' => (int) $pdo->lastInsertId(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create announcement.',
        'error' => $e->getMessage(),
    ]);
}
