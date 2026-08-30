<?php
include_once dirname(__DIR__, 3) . '/classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

header('Content-Type: application/json');

try {
    $employeeClass = new Employee();
    $learnerId = (int)($employeeClass->getEmployeeId() ?? 0);
    $moduleId = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;

    if ($moduleId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid module ID']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    // Get enrollment for this module's course
    $modStmt = $pdo->prepare("SELECT id, course_id FROM ld_module WHERE id = :mid AND status = 'active'");
    $modStmt->execute([':mid' => $moduleId]);
    $module = $modStmt->fetch(PDO::FETCH_ASSOC);

    if (!$module) {
        echo json_encode(['success' => false, 'error' => 'Module not found']);
        exit;
    }

    $enrollStmt = $pdo->prepare("SELECT id FROM ld_enrollment WHERE learner_id = :lid AND course_id = :cid");
    $enrollStmt->execute([':lid' => $learnerId, ':cid' => $module['course_id']]);
    $enrollment = $enrollStmt->fetch(PDO::FETCH_ASSOC);
    $enrollmentId = $enrollment ? (int)$enrollment['id'] : 0;

    $items = [];

    // Get lessons
    $lessonsStmt = $pdo->prepare("SELECT id, title, content_type FROM ld_lesson WHERE module_id = :mid AND status = 'active' ORDER BY created_at ASC");
    $lessonsStmt->execute([':mid' => $moduleId]);
    $lessons = $lessonsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lessons as $lesson) {
        $status = 'Not started';
        $completedAt = null;

        if ($enrollmentId > 0) {
            $progStmt = $pdo->prepare("SELECT status, completed_at FROM ld_progress WHERE enrollment_id = :eid AND item_type = 'lesson' AND reference_id = :rid LIMIT 1");
            $progStmt->execute([':eid' => $enrollmentId, ':rid' => $lesson['id']]);
            $prog = $progStmt->fetch(PDO::FETCH_ASSOC);
            if ($prog) {
                $status = $prog['status'] === 'completed' ? 'Completed' : 'In progress';
                $completedAt = $prog['completed_at'];
            }
        }

        $items[] = [
            'title' => $lesson['title'],
            'score' => $status === 'Completed' ? 100 : 0,
            'status' => $status,
            'type' => 'lesson',
            'content_type' => $lesson['content_type'] ?? 'text',
            'completed_at' => $completedAt
        ];
    }

    // Get quizzes
    $quizzesStmt = $pdo->prepare("SELECT id, title, passing_score, question_count FROM ld_quiz WHERE module_id = :mid AND status = 'active' ORDER BY created_at ASC");
    $quizzesStmt->execute([':mid' => $moduleId]);
    $quizzes = $quizzesStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($quizzes as $quiz) {
        $status = 'Not started';
        $score = 0;
        $completedAt = null;

        if ($enrollmentId > 0) {
            $progStmt = $pdo->prepare("SELECT status, completed_at FROM ld_progress WHERE enrollment_id = :eid AND item_type = 'quiz' AND reference_id = :rid LIMIT 1");
            $progStmt->execute([':eid' => $enrollmentId, ':rid' => $quiz['id']]);
            $prog = $progStmt->fetch(PDO::FETCH_ASSOC);

            if ($prog && $prog['status'] === 'completed') {
                $status = 'Passed';
                $score = 100;
                $completedAt = $prog['completed_at'];
            } elseif ($prog) {
                $status = 'In progress';
            }
        }

        $items[] = [
            'title' => $quiz['title'],
            'reference_id' => (int)$quiz['id'],
            'score' => $score,
            'status' => $status,
            'type' => 'quiz',
            'passing_score' => (int)($quiz['passing_score'] ?? 70),
            'question_count' => (int)($quiz['question_count'] ?? 0),
            'completed_at' => $completedAt
        ];
    }

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
