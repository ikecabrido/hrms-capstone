<?php
include_once dirname(__DIR__, 3) . '/classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

header('Content-Type: application/json');

try {
    $employeeClass = new Employee();
    $learnerId = (int)($employeeClass->getEmployeeId() ?? 0);
    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

    if ($courseId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    $course = $pdo->prepare("SELECT id, title, description, category, status FROM ld_course WHERE id = :cid AND status = 'active'");
    $course->execute([':cid' => $courseId]);
    $courseData = $course->fetch(PDO::FETCH_ASSOC);

    if (!$courseData) {
        echo json_encode(['success' => false, 'error' => 'Course not found']);
        exit;
    }

    $modulesStmt = $pdo->prepare("SELECT id, title, description, status FROM ld_module WHERE course_id = :cid AND status = 'active' ORDER BY created_at ASC");
    $modulesStmt->execute([':cid' => $courseId]);
    $modules = $modulesStmt->fetchAll(PDO::FETCH_ASSOC);

    $structure = ['modules' => []];

    foreach ($modules as $mod) {
        $lessonsStmt = $pdo->prepare("SELECT id, title, content_type, status FROM ld_lesson WHERE module_id = :mid AND status = 'active' ORDER BY created_at ASC");
        $lessonsStmt->execute([':mid' => $mod['id']]);
        $lessons = $lessonsStmt->fetchAll(PDO::FETCH_ASSOC);

        $lessonsWithQuizzes = [];
        foreach ($lessons as $lesson) {
            $quizzesStmt = $pdo->prepare("SELECT id, title, status FROM ld_quiz WHERE module_id = :mid AND status = 'active' ORDER BY created_at ASC");
            $quizzesStmt->execute([':mid' => $mod['id']]);
            $quizzes = $quizzesStmt->fetchAll(PDO::FETCH_ASSOC);
            $lesson['quizzes'] = $quizzes;
            $lessonsWithQuizzes[] = $lesson;
        }

        $mod['lessons'] = $lessonsWithQuizzes;
        $structure['modules'][] = $mod;
    }

    $evalStmt = $pdo->prepare("SELECT id, title, status FROM ld_evaluation WHERE course_id = :cid AND status = 'active' ORDER BY created_at ASC");
    $evalStmt->execute([':cid' => $courseId]);
    $structure['evaluations'] = $evalStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'structure' => $structure]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
