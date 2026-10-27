<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';

try {
    $instructorId = (int) ($_SESSION['employee_id'] ?? 0);
    if ($instructorId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $requestId = (int) ($input['id'] ?? 0);
    $action = trim($input['action'] ?? '');
    $note = trim($input['note'] ?? '');

    if ($requestId <= 0 || !in_array($action, ['accept', 'reject', 'completed'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    // Get current request
    $stmt = $pdo->prepare("SELECT * FROM ld_training_requests WHERE id = :id");
    $stmt->execute([':id' => $requestId]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found.']);
        exit;
    }

    // Map action to status
    $newStatus = match($action) {
        'accept' => 'accepted',
        'reject' => 'rejected',
        'completed' => 'completed',
        default => $action,
    };

    // Update the request
    $stmt = $pdo->prepare("
        UPDATE ld_training_requests
        SET status = :status, instructor_id = :iid, instructor_note = :note, updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':status' => $newStatus,
        ':iid' => $instructorId,
        ':note' => $note ?: null,
        ':id' => $requestId,
    ]);

    // Notify the learner
    try {
        $instrName = 'An instructor';
        $empStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM em_employees WHERE employee_id = :eid");
        $empStmt->execute([':eid' => $instructorId]);
        $empRow = $empStmt->fetch(PDO::FETCH_ASSOC);
        if ($empRow) $instrName = $empRow['name'];

        $statusMessages = [
            'accepted' => $instrName . ' accepted your training request for "' . $req['skill_name'] . '". A new course will be created.',
            'rejected' => $instrName . ' declined your training request for "' . $req['skill_name'] . '".' . ($note ? ' Reason: ' . $note : ''),
            'completed' => $instrName . ' completed the training for "' . $req['skill_name'] . '". You can now enroll.',
        ];

        $notifStmt = $pdo->prepare("
            INSERT INTO ld_notification (user_id, type, title, message, is_read, created_at)
            VALUES (:uid, 'training_request', :title, :msg, 0, NOW())
        ");
        $notifStmt->execute([
            ':uid' => (int) $req['learner_id'],
            ':title' => 'Training Request ' . ucfirst($newStatus),
            ':msg' => $statusMessages[$newStatus] ?? 'Your training request status has been updated.',
        ]);
    } catch (Throwable $e) { /* notification may not exist */ }

    echo json_encode([
        'success' => true,
        'new_status' => $newStatus,
        'note' => $note ?: null,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update request.', 'error' => $e->getMessage()]);
}
