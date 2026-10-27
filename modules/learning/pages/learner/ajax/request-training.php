<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';

try {
    $learnerId = (int) ($_SESSION['employee_id'] ?? 0);
    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $skillId = (int) ($input['skill_id'] ?? 0);
    $message = trim($input['message'] ?? '');

    if ($skillId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid skill ID.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    // Get skill name
    $stmt = $pdo->prepare("SELECT id, name FROM ld_skill WHERE id = :sid AND status = 'active'");
    $stmt->execute([':sid' => $skillId]);
    $skill = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$skill) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Skill not found.']);
        exit;
    }

    // Check if learner already has an active request for this skill
    $stmt = $pdo->prepare("SELECT id FROM ld_training_requests WHERE learner_id = :lid AND skill_id = :sid AND status IN ('pending','accepted')");
    $stmt->execute([':lid' => $learnerId, ':sid' => $skillId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already have an active request for this skill.']);
        exit;
    }

    // Check if there are active courses teaching this skill (in which case no request needed)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM ld_course_skill cs
        JOIN ld_course c ON c.id = cs.course_id
        WHERE cs.skill_id = :sid AND c.status = 'active'
    ");
    $stmt->execute([':sid' => $skillId]);
    $courseCount = (int) $stmt->fetchColumn();

    if ($courseCount > 0) {
        echo json_encode(['success' => false, 'message' => 'There are already courses available for this skill. Please enroll directly.']);
        exit;
    }

    // Insert training request
    $stmt = $pdo->prepare("
        INSERT INTO ld_training_requests (learner_id, skill_id, skill_name, message, status, created_at)
        VALUES (:lid, :sid, :sname, :msg, 'pending', NOW())
    ");
    $stmt->execute([
        ':lid' => $learnerId,
        ':sid' => $skillId,
        ':sname' => $skill['name'],
        ':msg' => $message ?: null,
    ]);

    $requestId = (int) $pdo->lastInsertId();

    // Create notification for all instructors
    $learnerName = 'A learner';
    try {
        $empStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM em_employees WHERE employee_id = :eid");
        $empStmt->execute([':eid' => $learnerId]);
        $empRow = $empStmt->fetch(PDO::FETCH_ASSOC);
        if ($empRow) $learnerName = $empRow['name'];
    } catch (Throwable $e) { /* ignore */ }

    try {
        // Get all instructor IDs
        $instrStmt = $pdo->query("SELECT DISTINCT instructor_id FROM ld_course WHERE instructor_id IS NOT NULL AND status = 'active'");
        $instructors = $instrStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($instructors)) {
            $notifStmt = $pdo->prepare("
                INSERT INTO ld_notification (user_id, type, title, message, is_read, created_at)
                VALUES (:uid, 'training_request', 'New Training Request', :msg, 0, NOW())
            ");
            foreach ($instructors as $instrId) {
                $notifStmt->execute([
                    ':uid' => (int) $instrId,
                    ':msg' => $learnerName . ' requested training for "' . $skill['name'] . '".',
                ]);
            }
        }
    } catch (Throwable $e) { /* notification table may not exist, ignore */ }

    echo json_encode([
        'success' => true,
        'message' => 'Training request submitted for "' . htmlspecialchars($skill['name']) . '".',
        'request_id' => $requestId,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit request.', 'error' => $e->getMessage()]);
}
