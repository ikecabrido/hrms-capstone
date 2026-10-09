<?php
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

require_once dirname(__DIR__, 5) . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid data.']);
    exit;
}

$employeeId = (int) ($input['employee_id'] ?? 0);
$successorId = !empty($input['successor_id']) ? (int) $input['successor_id'] : null;
$startDate = trim((string) ($input['start_date'] ?? ''));
$endDate = trim((string) ($input['end_date'] ?? ''));
$status = (string) ($input['status'] ?? 'active');
$planId = (int) ($input['id'] ?? 0);
$createdBy = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : null;

if ($employeeId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Employee is required.']);
    exit;
}

$startDate = $startDate !== '' ? $startDate : null;
$endDate = $endDate !== '' ? $endDate : null;

if (!in_array($status, ['active', 'completed', 'cancelled'], true)) {
    $status = 'active';
}

try {
    $pdo = (new Database())->getConnection();

    if ($successorId !== null) {
        $successorCheck = $pdo->prepare("SELECT 1 FROM em_employees WHERE employee_id = :employee_id AND employment_status = 'Active' LIMIT 1");
        $successorCheck->execute([':employee_id' => $successorId]);
        if (!$successorCheck->fetchColumn()) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Choose an active employee as the successor.']);
            exit;
        }
    }

    $pdo->beginTransaction();

    if ($planId > 0) {
        $planStmt = $pdo->prepare("UPDATE exit_knowledge_transfer_plans
                                   SET employee_id = :employee_id,
                                       successor_id = :successor_id,
                                       start_date = :start_date,
                                       end_date = :end_date,
                                       status = :status
                                   WHERE id = :id");
        $planStmt->execute([
            ':employee_id' => $employeeId,
            ':successor_id' => $successorId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':id' => $planId,
        ]);

        $existsStmt = $pdo->prepare('SELECT 1 FROM exit_knowledge_transfer_plans WHERE id = :id');
        $existsStmt->execute([':id' => $planId]);
        if (!$existsStmt->fetchColumn()) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Knowledge transfer plan not found.']);
            exit;
        }
    } else {
        $planStmt = $pdo->prepare("INSERT INTO exit_knowledge_transfer_plans
            (employee_id, successor_id, start_date, end_date, status, created_by)
            VALUES (:employee_id, :successor_id, :start_date, :end_date, :status, :created_by)");
        $planStmt->execute([
            ':employee_id' => $employeeId,
            ':successor_id' => $successorId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':created_by' => $createdBy,
        ]);
        $planId = (int) $pdo->lastInsertId();
    }

    $courseStmt = $pdo->prepare("SELECT e.course_id, c.title, e.status AS enrollment_status
                                 FROM ld_enrollment e
                                 JOIN ld_course c ON c.id = e.course_id
                                 WHERE e.learner_id = :employee_id
                                   AND e.status IN ('completed', 'in_progress')
                                 ORDER BY e.enrolled_at ASC");
    $courseStmt->execute([':employee_id' => $employeeId]);
    $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

    $employeeStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', IFNULL(CONCAT(middle_name, ' '), ''), last_name)
                                   FROM em_employees WHERE employee_id = :employee_id LIMIT 1");
    $employeeStmt->execute([':employee_id' => $employeeId]);
    $employeeName = (string) ($employeeStmt->fetchColumn() ?: 'Employee #' . $employeeId);

    $pathStmt = $pdo->prepare('SELECT id FROM ld_learning_path WHERE kt_plan_id = :plan_id ORDER BY id ASC LIMIT 1');
    $pathStmt->execute([':plan_id' => $planId]);
    $pathId = (int) ($pathStmt->fetchColumn() ?: 0);

    if ($pathId === 0) {
        $pathStmt = $pdo->prepare("INSERT INTO ld_learning_path
            (instructor_id, title, description, assigned_to, status, type, is_public, kt_plan_id)
            VALUES (:instructor_id, :title, :description, :successor_id, 'active', 'knowledge_transfer', 0, :plan_id)");
        $pathStmt->execute([
            ':instructor_id' => $createdBy ?? $employeeId,
            ':title' => 'Knowledge Transfer — ' . $employeeName,
            ':description' => 'Knowledge transfer path for ' . $employeeName . '. Contains ' . count($courses) . ' completed or in-progress courses.',
            ':successor_id' => $successorId,
            ':plan_id' => $planId,
        ]);
        $pathId = (int) $pdo->lastInsertId();

        if ($courses) {
            $pathItemStmt = $pdo->prepare("INSERT INTO ld_learning_path_item
                (learning_path_id, item_type, reference_id, order_index)
                VALUES (:path_id, 'course', :course_id, :order_index)");
            foreach ($courses as $index => $course) {
                $pathItemStmt->execute([
                    ':path_id' => $pathId,
                    ':course_id' => (int) $course['course_id'],
                    ':order_index' => $index + 1,
                ]);
            }
        }
    } else {
        $pathStmt = $pdo->prepare("UPDATE ld_learning_path
                                   SET assigned_to = :successor_id,
                                       type = 'knowledge_transfer',
                                       is_public = 0,
                                       updated_at = NOW()
                                   WHERE id = :path_id");
        $pathStmt->execute([
            ':successor_id' => $successorId,
            ':path_id' => $pathId,
        ]);
    }

    if ($successorId !== null) {
        $inviteStmt = $pdo->prepare("INSERT INTO ld_learning_path_invitation
            (learning_path_id, employee_id, status, invited_by)
            VALUES (:path_id, :employee_id, 'invited', :invited_by)
            ON DUPLICATE KEY UPDATE
                status = IF(status IN ('declined', 'withdrawn'), 'invited', status),
                invited_by = VALUES(invited_by)");
        $inviteStmt->execute([
            ':path_id' => $pathId,
            ':employee_id' => $successorId,
            ':invited_by' => $createdBy,
        ]);
    }

    if ($planId > 0) {
        $existingItemsStmt = $pdo->prepare('SELECT COUNT(*) FROM exit_knowledge_transfer_items WHERE plan_id = :plan_id');
        $existingItemsStmt->execute([':plan_id' => $planId]);
        if ((int) $existingItemsStmt->fetchColumn() === 0) {
            $transferItemStmt = $pdo->prepare("INSERT INTO exit_knowledge_transfer_items
                (plan_id, item_type, title, description, priority, status)
                VALUES (:plan_id, 'document', :title, :description, :priority, :status)");
            foreach ($courses as $course) {
                $isCompleted = $course['enrollment_status'] === 'completed';
                $transferItemStmt->execute([
                    ':plan_id' => $planId,
                    ':title' => $course['title'],
                    ':description' => 'Knowledge transfer for course: ' . $course['title'],
                    ':priority' => $isCompleted ? 'high' : 'medium',
                    ':status' => $isCompleted ? 'completed' : 'pending',
                ]);
            }
        }
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'id' => $planId,
        'learning_path_id' => $pathId,
        'message' => $successorId === null
            ? 'Plan and private learning path saved. Assign a successor later to send an invitation.'
            : 'Plan and private learning path saved. The successor has been invited.',
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('save-kt-plan.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to save the knowledge transfer plan.']);
}