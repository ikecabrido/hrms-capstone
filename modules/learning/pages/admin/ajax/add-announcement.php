<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/LearningRole.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $actorId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
    $title = trim((string) ($_POST['title'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $expiresAt = trim((string) ($_POST['expires_at'] ?? ''));

    if ($actorId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $learningRole = LearningRole::forEmployee($pdo, $actorId);
    if (!in_array($learningRole, ['admin', 'instructor'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only admins and instructors can publish announcements.']);
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

    $allowedAudiences = $learningRole === 'admin'
        ? ['all', 'instructor', 'learner', 'admin']
        : ['all', 'learner'];
    $audienceInput = $_POST['audiences'] ?? (isset($_POST['audience']) ? [$_POST['audience']] : ['all']);
    if (!is_array($audienceInput)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Select one or more valid audiences.']);
        exit;
    }
    $audienceInput = array_values(array_unique(array_map('strval', $audienceInput)));
    if (!$audienceInput || array_diff($audienceInput, $allowedAudiences)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Select one or more audiences you are allowed to notify.']);
        exit;
    }
    if (in_array('all', $audienceInput, true) && count($audienceInput) > 1) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Everyone cannot be combined with other audiences.']);
        exit;
    }
    $audienceOrder = ['all', 'instructor', 'learner', 'admin'];
    $audience = implode(',', array_values(array_intersect($audienceOrder, $audienceInput)));

    $expirationInput = trim((string) ($_POST['expires_at'] ?? ''));
    $expiresAt = null;
    if ($expirationInput !== '') {
        $expiration = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $expirationInput);
        $expirationErrors = DateTimeImmutable::getLastErrors();
        if (!$expiration || ($expirationErrors && ($expirationErrors['warning_count'] > 0 || $expirationErrors['error_count'] > 0))) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Choose a valid expiration date and time.']);
            exit;
        }
        if ($expiration <= new DateTimeImmutable()) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Expiration must be in the future.']);
            exit;
        }
        $expiresAt = $expiration->format('Y-m-d H:i:s');
    }

    $sql = 'INSERT INTO ld_announcement (title, message, audience, posted_by, expires_at) VALUES (:title, :message, :audience, :posted_by, :expires_at)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':message' => $message,
        ':audience' => $audience,
        ':posted_by' => $actorId,
        ':expires_at' => $expiresAt,
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
