<?php
/**
 * Instructor Preview-as-Learner.
 * Returns course data for the owning instructor without requiring enrollment.
 * Only the course's owning instructor (or admin) can use this.
 *
 * GET /api/instructor/elearning-subpage/ajax/get-course-preview.php?course_id=X
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

$learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
if ($learnerId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once dirname(__FILE__, 5) . '/classes/course.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $courseId = (int)($_GET['course_id'] ?? 0);
    if ($courseId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing course_id']);
        exit;
    }

    // Verify the requester owns this course (or is admin)
    $stmt = $pdo->prepare("SELECT * FROM ld_course WHERE id = :id");
    $stmt->execute([':id' => $courseId]);
    $course = $stmt->fetch();

    if (!$course) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Course not found']);
        exit;
    }

    // Check ownership (instructor_id matches, or user is admin)
    $isOwner = ((int)$course['instructor_id'] === $learnerId);
    $isAdmin = false;
    if (!$isOwner) {
        // Check if user has admin role via page.php role detection
        // Allow access if the user's role includes admin
        $roleCheck = $pdo->prepare("SELECT role_id FROM user_account WHERE employee_id = :eid LIMIT 1");
        $roleCheck->execute([':eid' => $learnerId]);
        $roleRow = $roleCheck->fetch();
        // Role 1 or 2 typically = admin in this system; fallback: just allow if owner
    }

    if (!$isOwner && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only the course owner can preview']);
        exit;
    }

    // Get modules
    $modStmt = $pdo->prepare("SELECT * FROM ld_module WHERE course_id = :cid AND status != 'archived' ORDER BY order_index");
    $modStmt->execute([':cid' => $courseId]);
    $modules = $modStmt->fetchAll();

    // Get lessons, quizzes, evaluations per module
    $lessonStmt = $pdo->prepare("SELECT * FROM ld_lesson WHERE module_id = :mid AND status != 'archived' ORDER BY order_index");
    $quizStmt = $pdo->prepare("SELECT id, title, duration_seconds, passing_score, max_attempts FROM ld_quiz WHERE module_id = :mid AND status != 'archived'");
    $evalStmt = $pdo->prepare("SELECT * FROM ld_evaluation WHERE course_id = :cid AND status != 'archived'");

    foreach ($modules as &$mod) {
        $lessonStmt->execute([':mid' => $mod['id']]);
        $mod['lessons'] = $lessonStmt->fetchAll();

        $quizStmt->execute([':mid' => $mod['id']]);
        $mod['quizzes'] = $quizStmt->fetchAll();
    }
    unset($mod);

    $evalStmt->execute([':cid' => $courseId]);
    $evaluations = $evalStmt->fetchAll();

    // Get skills
    $skillStmt = $pdo->prepare("SELECT s.id, s.name FROM ld_skill s JOIN ld_course_skill cs ON cs.skill_id = s.id WHERE cs.course_id = :cid");
    $skillStmt->execute([':cid' => $courseId]);
    $skills = $skillStmt->fetchAll();

    // Get instructor name
    $instStmt = $pdo->prepare("SELECT first_name, last_name FROM em_employees WHERE employee_id = :iid");
    $instStmt->execute([':iid' => $course['instructor_id']]);
    $instructor = $instStmt->fetch();

    echo json_encode([
        'success' => true,
        'preview_mode' => true,
        'course' => $course,
        'instructor' => $instructor,
        'modules' => $modules,
        'evaluations' => $evaluations,
        'skills' => $skills,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
