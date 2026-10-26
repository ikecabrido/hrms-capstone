<?php
/**
 * Announcement publisher for admins and instructors alike.
 *
 * It lived under pages/admin/ajax/ only because announcements were first an
 * admin surface, but the composer in includes/announcement-composer.php is shown
 * to instructors too and this endpoint has always accepted both roles (gated
 * below by LearningRole). The shared folder is where a multi-role endpoint
 * belongs, so the path no longer implies a permission the code does not require.
 *
 * The row is written by the Announcement class rather than by SQL here, so the
 * one writer that knows how to store a row on either ld_announcement shape is
 * also the one the composer exercises.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/CSRF.php';
require_once dirname(__FILE__, 4) . '/classes/LearningRole.php';
require_once dirname(__FILE__, 4) . '/classes/announcement.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

CSRF::requireValid();

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
        echo json_encode(['success' => false, 'message' => 'Only admins and trainers can publish announcements.']);
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
        $expiration = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $expirationInput);
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

    // The write itself lives in the Announcement class: it is the one place that
    // knows how to store a row whether or not the table allocates ids and whether
    // or not its audience column can hold several audiences.
    $result = (new Announcement($pdo))->create([
        'title' => $title,
        'message' => $message,
        'audience' => $audience,
        'posted_by' => $actorId,
        'expires_at' => $expiresAt,
    ], $actorId);

    if (empty($result['success'])) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => (string) ($result['message'] ?? 'Could not create the announcement.'),
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => (string) ($result['message'] ?? 'Announcement created successfully.'),
        'id' => (int) ($result['id'] ?? 0),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create announcement.',
        'error' => $e->getMessage(),
    ]);
}
