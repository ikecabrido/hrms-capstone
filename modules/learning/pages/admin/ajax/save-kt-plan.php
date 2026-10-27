<?php
header('Content-Type: application/json');
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $pdo = (new Database())->getConnection();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid data.']);
        exit;
    }

    $employeeId = isset($input['employee_id']) ? (int) $input['employee_id'] : 0;
    $successorId = !empty($input['successor_id']) ? (int) $input['successor_id'] : null;
    $startDate = $input['start_date'] ?? '';
    $endDate = $input['end_date'] ?? '';
    $status = $input['status'] ?? 'active';
    $id = !empty($input['id']) ? (int) $input['id'] : 0;

    if ($employeeId <= 0 || empty($startDate) || empty($endDate)) {
        echo json_encode(['success' => false, 'error' => 'Employee, start date, and end date are required.']);
        exit;
    }

    $validStatuses = ['active', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        $status = 'active';
    }

    if (session_status() === PHP_SESSION_NONE) session_start();
    $createdBy = $_SESSION['employee_id'] ?? null;

    $publishToCatalog = !empty($input['publish_to_catalog']);

    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE exit_knowledge_transfer_plans 
            SET employee_id = :employee_id,
                successor_id = :successor_id,
                start_date = :start_date,
                end_date = :end_date,
                status = :status
            WHERE id = :id
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':successor_id' => $successorId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':id' => $id
        ]);
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Plan updated.']);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO exit_knowledge_transfer_plans 
                (employee_id, successor_id, start_date, end_date, status, created_by)
            VALUES 
                (:employee_id, :successor_id, :start_date, :end_date, :status, :created_by)
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':successor_id' => $successorId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':created_by' => $createdBy
        ]);
        $newId = $pdo->lastInsertId();

        // Always generate a KT learning path
        if ($newId) {
            try {
                // Fetch resignee's completed/in-progress courses
                $courseStmt = $pdo->prepare("
                    SELECT e.course_id, c.title
                    FROM ld_enrollment e
                    JOIN ld_course c ON c.id = e.course_id
                    WHERE e.learner_id = :eid AND e.status IN ('completed', 'in_progress')
                    ORDER BY e.enrolled_at ASC
                ");
                $courseStmt->execute([':eid' => $employeeId]);
                $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($courses)) {
                    // Fetch employee names for path title
                    $empStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', IFNULL(CONCAT(middle_name, ' '), ''), last_name) AS full_name FROM em_employees WHERE employee_id = :eid");
                    $empStmt->execute([':eid' => $employeeId]);
                    $empName = ($empStmt->fetch(PDO::FETCH_ASSOC)['full_name'] ?? 'Unknown');

                    // Create learning path
                    $pathTitle = 'Knowledge Transfer — ' . $empName;
                    $pathDesc = 'Knowledge transfer path for ' . $empName . '. Contains ' . count($courses) . ' courses from their completed/in-progress enrollments.';
                    $pathStmt = $pdo->prepare("
                        INSERT INTO ld_learning_path (instructor_id, title, description, assigned_to, status, type, is_public, kt_plan_id)
                        VALUES (:iid, :title, :desc, :assigned, 'active', 'knowledge_transfer', :pub, :ktpid)
                    ");
                    $pathStmt->execute([
                        ':iid' => $createdBy ?? $employeeId,
                        ':title' => $pathTitle,
                        ':desc' => $pathDesc,
                        ':assigned' => $successorId,
                        ':pub' => $publishToCatalog ? 1 : 0,
                        ':ktpid' => $newId,
                    ]);
                    $pathId = $pdo->lastInsertId();

                    // Add each course as a path item
                    $itemStmt = $pdo->prepare("
                        INSERT INTO ld_learning_path_item (learning_path_id, item_type, reference_id, order_index)
                        VALUES (:lpid, 'course', :cid, :ord)
                    ");
                    $order = 1;
                    foreach ($courses as $course) {
                        $itemStmt->execute([':lpid' => $pathId, ':cid' => $course['course_id'], ':ord' => $order]);
                        $order++;
                    }

                    // Auto-enroll successor in each course (skip if already enrolled)
                    if ($successorId) {
                        $checkStmt = $pdo->prepare("SELECT id FROM ld_enrollment WHERE learner_id = :lid AND course_id = :cid LIMIT 1");
                        $enrollStmt = $pdo->prepare("
                            INSERT INTO ld_enrollment (learner_id, course_id, status, enrolled_at)
                            VALUES (:lid, :cid, 'enrolled', NOW())
                        ");
                        foreach ($courses as $course) {
                            $checkStmt->execute([':lid' => $successorId, ':cid' => $course['course_id']]);
                            if (!$checkStmt->fetch()) {
                                $enrollStmt->execute([':lid' => $successorId, ':cid' => $course['course_id']]);
                            }
                        }
                    }
                }

                // Create transfer items from resignee's courses
                $allCoursesStmt = $pdo->prepare("
                    SELECT e.course_id, c.title, e.status AS enroll_status
                    FROM ld_enrollment e
                    JOIN ld_course c ON c.id = e.course_id
                    WHERE e.learner_id = :eid
                    ORDER BY e.enrolled_at ASC
                ");
                $allCoursesStmt->execute([':eid' => $employeeId]);
                $allCourses = $allCoursesStmt->fetchAll(PDO::FETCH_ASSOC);

                $itemStmt = $pdo->prepare("
                    INSERT INTO exit_knowledge_transfer_items
                        (plan_id, item_type, title, description, priority, status)
                    VALUES (:pid, :type, :title, :desc, :priority, :status)
                ");
                foreach ($allCourses as $course) {
                    $isCompleted = ($course['enroll_status'] === 'completed');
                    $itemStmt->execute([
                        ':pid' => $newId,
                        ':type' => 'document',
                        ':title' => $course['title'],
                        ':desc' => 'Knowledge transfer for course: ' . $course['title'],
                        ':priority' => $isCompleted ? 'high' : 'medium',
                        ':status' => $isCompleted ? 'completed' : 'pending',
                    ]);
                }
            } catch (Throwable $pathErr) {
                error_log('save-kt-plan: KT path generation failed: ' . $pathErr->getMessage());
            }
        }

        echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Plan created.' . ($publishToCatalog ? ' Learning path published to catalog.' : '') . ' ' . count($allCourses ?? []) . ' transfer items added.']);
    }
} catch (Throwable $e) {
    error_log('save-kt-plan.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
