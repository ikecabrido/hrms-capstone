<?php
include_once dirname(__DIR__, 3) . '/classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

header('Content-Type: application/json');

try {
    $employeeClass = new Employee();
    $learnerId = (int)($employeeClass->getEmployeeId() ?? 0);
    $quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

    if ($quizId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid quiz ID']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    // Get quiz info
    $quizStmt = $pdo->prepare("SELECT id, title, passing_score, question_count, max_attempts, duration_seconds FROM ld_quiz WHERE id = :qid AND status = 'active'");
    $quizStmt->execute([':qid' => $quizId]);
    $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        echo json_encode(['success' => false, 'error' => 'Quiz not found']);
        exit;
    }

    // Get all attempts
    $attemptsStmt = $pdo->prepare("SELECT score, status, created_at FROM ld_quiz_attempt WHERE learner_id = :lid AND quiz_id = :qid ORDER BY created_at DESC");
    $attemptsStmt->execute([':lid' => $learnerId, ':qid' => $quizId]);
    $attempts = $attemptsStmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    $attemptNum = count($attempts);

    foreach ($attempts as $attempt) {
        $score = (int)($attempt['score'] ?? 0);
        $passed = $score >= (int)($quiz['passing_score'] ?? 70);

        $items[] = [
            'title' => 'Attempt #' . $attemptNum,
            'score' => $score,
            'status' => $passed ? 'Passed' : 'Failed',
            'type' => 'quiz_attempt',
            'passing_score' => (int)($quiz['passing_score'] ?? 70),
            'completed_at' => $attempt['created_at'] ?? null
        ];
        $attemptNum--;
    }

    // Add summary
    $bestScore = !empty($items) ? max(array_column($items, 'score')) : 0;
    $passed = $bestScore >= (int)($quiz['passing_score'] ?? 70);
    $remainingAttempts = max(0, (int)($quiz['max_attempts'] ?? 3) - count($attempts));

    echo json_encode([
        'success' => true,
        'items' => $items,
        'summary' => [
            'quiz_title' => $quiz['title'],
            'best_score' => $bestScore,
            'passed' => $passed,
            'total_attempts' => count($attempts),
            'remaining_attempts' => $remainingAttempts,
            'passing_score' => (int)($quiz['passing_score'] ?? 70),
            'question_count' => (int)($quiz['question_count'] ?? 0),
            'duration_seconds' => (int)($quiz['duration_seconds'] ?? 0)
        ]
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
