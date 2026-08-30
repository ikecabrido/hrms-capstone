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

    // Get enrollment
    $stmt = $pdo->prepare("SELECT id, status, completed_at FROM ld_enrollment WHERE learner_id = :lid AND course_id = :cid");
    $stmt->execute([':lid' => $learnerId, ':cid' => $courseId]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enrollment) {
        echo json_encode(['success' => true, 'items' => [], 'message' => 'Not enrolled in this course']);
        exit;
    }

    $enrollmentId = (int)$enrollment['id'];
    $items = [];
    $totalQuizScore = 0;
    $quizCount = 0;

    // Get all modules in this course
    $modulesStmt = $pdo->prepare("SELECT id, title FROM ld_module WHERE course_id = :cid AND status = 'active' ORDER BY created_at ASC");
    $modulesStmt->execute([':cid' => $courseId]);
    $modules = $modulesStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($modules as $mod) {
        // Get lessons in this module
        $lessonsStmt = $pdo->prepare("SELECT id, title, content_type FROM ld_lesson WHERE module_id = :mid AND status = 'active' ORDER BY created_at ASC");
        $lessonsStmt->execute([':mid' => $mod['id']]);
        $lessons = $lessonsStmt->fetchAll(PDO::FETCH_ASSOC);
        $totalLessons = count($lessons);

        // Check completion for each lesson
        $completedLessons = 0;
        $lessonDetails = [];
        foreach ($lessons as $lesson) {
            $progStmt = $pdo->prepare("SELECT status, completed_at FROM ld_progress WHERE enrollment_id = :eid AND item_type = 'lesson' AND reference_id = :rid LIMIT 1");
            $progStmt->execute([':eid' => $enrollmentId, ':rid' => $lesson['id']]);
            $prog = $progStmt->fetch(PDO::FETCH_ASSOC);
            $isCompleted = $prog && $prog['status'] === 'completed';
            if ($isCompleted) $completedLessons++;
            $lessonDetails[] = [
                'title' => $lesson['title'],
                'content_type' => $lesson['content_type'] ?? 'text',
                'completed' => $isCompleted,
                'completed_at' => $prog['completed_at'] ?? null
            ];
        }

        $lessonProgress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        // Check quizzes in this module — use ld_quiz_session for real scores
        $quizzesStmt = $pdo->prepare("SELECT id, title, passing_score FROM ld_quiz WHERE module_id = :mid AND status = 'active' ORDER BY created_at ASC");
        $quizzesStmt->execute([':mid' => $mod['id']]);
        $quizzes = $quizzesStmt->fetchAll(PDO::FETCH_ASSOC);

        $quizResults = [];
        foreach ($quizzes as $quiz) {
            // Get best score from quiz sessions
            $sessionStmt = $pdo->prepare("SELECT score, passed, submitted_at, status FROM ld_quiz_session WHERE learner_id = :lid AND item_type = 'quiz' AND reference_id = :qid AND status = 'submitted' ORDER BY score DESC LIMIT 1");
            $sessionStmt->execute([':lid' => $learnerId, ':qid' => $quiz['id']]);
            $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

            // Also count total attempts
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_quiz_session WHERE learner_id = :lid AND item_type = 'quiz' AND reference_id = :qid AND status = 'submitted'");
            $countStmt->execute([':lid' => $learnerId, ':qid' => $quiz['id']]);
            $attemptCount = (int)$countStmt->fetchColumn();

            if ($session) {
                $bestScore = (int)round((float)$session['score']);
                $quizResults[] = [
                    'title' => $quiz['title'],
                    'reference_id' => (int)$quiz['id'],
                    'score' => $bestScore,
                    'passed' => (bool)$session['passed'],
                    'passing_score' => (int)($quiz['passing_score'] ?? 70),
                    'status' => $session['passed'] ? 'Passed' : 'Failed',
                    'attempts' => $attemptCount,
                    'completed_at' => $session['submitted_at'] ?? null
                ];
                if ($session['passed']) {
                    $totalQuizScore += $bestScore;
                    $quizCount++;
                }
            } else {
                $quizResults[] = [
                    'title' => $quiz['title'],
                    'reference_id' => (int)$quiz['id'],
                    'score' => 0,
                    'passed' => false,
                    'passing_score' => (int)($quiz['passing_score'] ?? 70),
                    'status' => 'Not started',
                    'attempts' => 0,
                    'completed_at' => null
                ];
            }
        }

        // Module status
        $allQuizzesDone = !empty($quizResults) && !in_array('Not started', array_column($quizResults, 'status'));
        $anyQuizPassed = !empty($quizResults) && in_array('Passed', array_column($quizResults, 'status'));
        $hasProgress = $lessonProgress > 0 || $anyQuizPassed;
        $moduleStatus = ($lessonProgress === 100 && $allQuizzesDone) ? 'Completed' : ($hasProgress ? 'In progress' : 'Not started');

        $items[] = [
            'title' => $mod['title'],
            'reference_id' => (int)$mod['id'],
            'score' => $lessonProgress,
            'status' => $moduleStatus,
            'lessons_completed' => $completedLessons,
            'lessons_total' => $totalLessons,
            'lesson_details' => $lessonDetails,
            'quizzes' => $quizResults
        ];
    }

    // Check evaluations
    $evalStmt = $pdo->prepare("SELECT e.id, e.title, e.passing_score FROM ld_evaluation e WHERE e.course_id = :cid AND e.status = 'active'");
    $evalStmt->execute([':cid' => $courseId]);
    $evaluations = $evalStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($evaluations as $eval) {
        $progStmt = $pdo->prepare("SELECT id, status, completed_at FROM ld_progress WHERE enrollment_id = :eid AND item_type = 'evaluation' AND reference_id = :rid LIMIT 1");
        $progStmt->execute([':eid' => $enrollmentId, ':rid' => $eval['id']]);
        $prog = $progStmt->fetch(PDO::FETCH_ASSOC);

        $items[] = [
            'title' => $eval['title'],
            'score' => ($prog && $prog['status'] === 'completed') ? 100 : 0,
            'status' => ($prog && $prog['status'] === 'completed') ? 'Completed' : 'Not started',
            'type' => 'evaluation',
            'completed_at' => $prog['completed_at'] ?? null
        ];
    }

    // Certificate info
    $certStmt = $pdo->prepare("SELECT id, verification_code, issued_at, valid_until, status FROM ld_certificate WHERE learner_id = :lid AND course_id = :cid AND status = 'active' LIMIT 1");
    $certStmt->execute([':lid' => $learnerId, ':cid' => $courseId]);
    $certificate = $certStmt->fetch(PDO::FETCH_ASSOC);

    // Overall course summary
    $completedModules = count(array_filter($items, function($i) { return $i['status'] === 'Completed'; }));
    $avgQuizScore = $quizCount > 0 ? round($totalQuizScore / $quizCount) : 0;

    echo json_encode([
        'success' => true,
        'items' => $items,
        'summary' => [
            'modules_completed' => $completedModules,
            'modules_total' => count($modules),
            'quizzes_passed' => $quizCount,
            'quizzes_total' => count(array_filter($items, function($i) { return !isset($i['type']) && !empty($i['quizzes']); })),
            'avg_quiz_score' => $avgQuizScore,
            'enrollment_status' => $enrollment['status'],
            'enrolled_at' => $enrollment['enrolled_at'] ?? null,
            'completed_at' => $enrollment['completed_at'] ?? null
        ],
        'certificate' => $certificate ? [
            'has_certificate' => true,
            'verification_code' => $certificate['verification_code'],
            'issued_at' => $certificate['issued_at'],
            'valid_until' => $certificate['valid_until']
        ] : [
            'has_certificate' => false
        ]
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
