<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 3) . '/classes/enrollment.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$quizId = (int) ($_GET['quiz_id'] ?? 0);
$courseId = (int) ($_GET['course_id'] ?? 0);
$errorMessage = trim((string) ($_GET['error'] ?? ''));
$quiz = null;
$bestAttempt = null;
$recentAttempt = null;
$attemptCount = 0;
$enrollment = null;
$moduleId = 0;
$currentPageType = 'quiz';
$currentPageId = $quizId;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
         SELECT q.id, q.title, NULL AS description,
             ROUND(q.duration_seconds / 60) AS time_limit_minutes,
             q.passing_score, q.max_attempts AS attempt_limit,
               m.id AS module_id, m.title AS module_title, m.order_index AS module_order,
               c.id AS course_id, c.title AS course_title,
             (SELECT COUNT(*) FROM ld_quiz_question qq
              WHERE qq.item_type = 'quiz' AND qq.reference_id = q.id AND qq.status = 'active') AS question_count
        FROM ld_quiz q
        JOIN ld_module m ON m.id = q.module_id
        JOIN ld_course c ON c.id = m.course_id
        WHERE q.id = :id AND q.status = 'active' AND m.status = 'active' AND c.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $quizId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($quiz) {
        $courseId = (int) $quiz['course_id'];
        $moduleId = (int) $quiz['module_id'];

        $enrollmentObj = new Enrollment($pdo);
        $enrollment = $enrollmentObj->getByLearnerAndCourse($learnerId, $courseId);

        $attemptsStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM ld_quiz_session
            WHERE learner_id = :lid AND item_type = 'quiz' AND reference_id = :qid
              AND status IN ('submitted', 'expired')
        ");
        $attemptsStmt->execute([':lid' => $learnerId, ':qid' => $quizId]);
        $attemptCount = (int) $attemptsStmt->fetchColumn();

        $bestStmt = $pdo->prepare("
            SELECT id, score, passed, submitted_at
            FROM ld_quiz_session
            WHERE learner_id = :lid AND item_type = 'quiz' AND reference_id = :qid
              AND status IN ('submitted', 'expired')
            ORDER BY score DESC, submitted_at DESC, id DESC
            LIMIT 1
        ");
        $bestStmt->execute([':lid' => $learnerId, ':qid' => $quizId]);
        $bestAttempt = $bestStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $recentStmt = $pdo->prepare("
            SELECT id, score, passed, submitted_at
            FROM ld_quiz_session
            WHERE learner_id = :lid AND item_type = 'quiz' AND reference_id = :qid
              AND status IN ('submitted', 'expired')
            ORDER BY submitted_at DESC, id DESC
            LIMIT 1
        ");
        $recentStmt->execute([':lid' => $learnerId, ':qid' => $quizId]);
        $recentAttempt = $recentStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $e) {
    DbError::capture($e, 'learner/study-subpage/quiz');
    $quiz = null;
}

if (!$quiz) {
    ?>
    <div class="module-content">
        <div class="study-doc">
            <div class="study-panel" style="text-align:center;">
                <i class="fas fa-triangle-exclamation" style="display:block;margin-bottom:0.75rem;color:var(--muted);font-size:2rem;"></i>
                <h2 style="margin:0 0 0.5rem;">Quiz Not Found</h2>
                <p style="margin:0 0 1.25rem;color:var(--muted);">The quiz you are looking for does not exist.</p>
                <a class="study-btn" href="?page=learner/study"><i class="fas fa-arrow-left"></i> Back to Study</a>
            </div>
        </div>
    </div>
    <?php
    return;
}

$questionCount = (int) $quiz['question_count'];
$hasQuestions = $questionCount > 0;
$canAttempt = true;
if ($quiz['attempt_limit'] && $attemptCount >= (int) $quiz['attempt_limit']) {
    $canAttempt = false;
}

$passingScore = $quiz['passing_score'] !== null ? (float) $quiz['passing_score'] : null;
$passingLabel = $passingScore === null
    ? 'N/A'
    : rtrim(rtrim(number_format($passingScore, 2, '.', ''), '0'), '.') . '%';

$fmtScore = static function ($score): string {
    return $score === null ? 'N/A' : rtrim(rtrim(number_format((float) $score, 1, '.', ''), '0'), '.') . '%';
};

$showBest = $bestAttempt !== null;
$showRecent = $recentAttempt !== null && (!$showBest || (int) $recentAttempt['id'] !== (int) $bestAttempt['id']);
?>

<div class="module-content">
<?php require_once __DIR__ . '/includes/course-sidebar.php'; ?>

    <div class="study-breadcrumb">
        <a href="?page=learner/study"><i class="fas fa-book-open"></i> My Study</a>
        <i class="fas fa-chevron-right sep"></i>
        <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>"><?= htmlspecialchars($quiz['course_title']) ?></a>
        <i class="fas fa-chevron-right sep"></i>
        <a href="?page=learner/study-subpage/module&module_id=<?= $moduleId ?>&course_id=<?= $courseId ?>"><?= htmlspecialchars($quiz['module_title']) ?></a>
        <i class="fas fa-chevron-right sep"></i>
        <span class="current">Quiz</span>
    </div>

    <div class="study-doc">
        <?php if ($errorMessage !== ''): ?>
            <div class="study-alert study-alert-error" role="alert">
                <i class="fas fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($errorMessage) ?></span>
            </div>
        <?php endif; ?>

        <!-- Quiz header -->
        <div class="study-header-card">
            <div class="study-pills">
                <span class="study-pill study-pill-primary"><i class="fas fa-question-circle"></i> Quiz</span>
                <span class="study-pill study-pill-outline"><?= htmlspecialchars($quiz['module_title']) ?></span>
            </div>
            <h1 class="study-header-title"><?= htmlspecialchars($quiz['title']) ?></h1>
            <?php if (!empty($quiz['description'])): ?>
                <p class="study-header-desc"><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>
            <?php endif; ?>
        </div>

        <!-- Quiz facts -->
        <div class="home-stats">
            <div class="home-stat">
                <span class="home-stat-icon"><i class="fas fa-list-ol"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Questions</span>
                    <span class="home-stat-value"><?= $questionCount ?></span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon"><i class="fas fa-clock"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Time Limit</span>
                    <span class="home-stat-value"><?= $quiz['time_limit_minutes'] ? (int) $quiz['time_limit_minutes'] . ' min' : 'None' ?></span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon"><i class="fas fa-bullseye"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Passing Score</span>
                    <span class="home-stat-value"><?= htmlspecialchars($passingLabel) ?></span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon"><i class="fas fa-rotate"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Attempts</span>
                    <span class="home-stat-value"><?= $quiz['attempt_limit'] ? $attemptCount . ' / ' . (int) $quiz['attempt_limit'] : (string) $attemptCount ?></span>
                </span>
            </div>
        </div>

        <?php if ($showBest || $showRecent): ?>
            <div class="study-panel">
                <h2 class="study-panel-title"><i class="fas fa-chart-simple"></i> Your Results</h2>
                <div class="home-list">
                    <?php if ($showBest): ?>
                        <div class="home-row">
                            <span class="home-row-icon"><i class="fas fa-medal"></i></span>
                            <span class="home-row-body">
                                <span class="home-row-title">Best score</span>
                                <span class="home-row-meta"><?= htmlspecialchars(date('M j, Y', strtotime((string) $bestAttempt['submitted_at']))) ?></span>
                            </span>
                            <span class="home-row-trail">
                                <span class="home-badge <?= $bestAttempt['passed'] || $passingScore === null ? 'is-success' : 'is-danger' ?>"><?= $fmtScore($bestAttempt['score']) ?></span>
                                <a class="home-card-link" href="?page=learner/study-subpage/quiz-review&session_id=<?= (int) $bestAttempt['id'] ?>&course_id=<?= $courseId ?>">Review <i class="fas fa-arrow-right"></i></a>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if ($showRecent): ?>
                        <div class="home-row">
                            <span class="home-row-icon"><i class="fas fa-clock-rotate-left"></i></span>
                            <span class="home-row-body">
                                <span class="home-row-title">Most recent attempt</span>
                                <span class="home-row-meta"><?= htmlspecialchars(date('M j, Y', strtotime((string) $recentAttempt['submitted_at']))) ?></span>
                            </span>
                            <span class="home-row-trail">
                                <span class="home-badge <?= $recentAttempt['passed'] || $passingScore === null ? 'is-success' : 'is-danger' ?>"><?= $fmtScore($recentAttempt['score']) ?></span>
                                <a class="home-card-link" href="?page=learner/study-subpage/quiz-review&session_id=<?= (int) $recentAttempt['id'] ?>&course_id=<?= $courseId ?>">Review <i class="fas fa-arrow-right"></i></a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Start / status -->
        <div class="study-panel" style="text-align:center;">
            <?php if (!$hasQuestions): ?>
                <div class="study-alert study-alert-info" style="justify-content:center;">
                    <i class="fas fa-circle-info"></i>
                    <span>This quiz doesn't have any questions yet. Please check back later.</span>
                </div>
            <?php elseif (!$canAttempt): ?>
                <div class="study-alert study-alert-info" style="justify-content:center;">
                    <i class="fas fa-ban"></i>
                    <span>You have reached the maximum number of attempts (<?= (int) $quiz['attempt_limit'] ?>).</span>
                </div>
            <?php else: ?>
                <a class="study-btn" href="?page=learner/study-subpage/quiz-taker&quiz_id=<?= $quizId ?>&course_id=<?= $courseId ?>">
                    <i class="fas fa-play"></i> <?= $attemptCount > 0 ? 'Retake Quiz' : 'Start Quiz' ?>
                </a>
                <p style="margin:0.9rem 0 0;color:var(--muted);font-size:0.82rem;">
                    <?php if ($quiz['attempt_limit']): ?>
                        <?= $attemptCount ?> of <?= (int) $quiz['attempt_limit'] ?> attempts used
                    <?php else: ?>
                        Unlimited attempts
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/course-sidebar-footer.php'; ?>
</div>
