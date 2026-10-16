<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 3) . '/classes/enrollment.php';
require_once dirname(__DIR__, 3) . '/classes/progress.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$evaluationId = (int) ($_GET['evaluation_id'] ?? 0);
$courseId = (int) ($_GET['course_id'] ?? 0);
$errorMessage = trim((string) ($_GET['error'] ?? ''));
$justFinished = !empty($_GET['done']);

$evaluation = null;
$enrollment = null;
$questionCount = 0;
$attemptCount = 0;
$bestAttempt = null;
$recentAttempt = null;
$gateOk = false;
$totalItems = 0;
$completedItems = 0;
$moduleId = 0;
$currentPageType = 'evaluation';
$currentPageId = $evaluationId;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT e.id, e.title, e.course_id, e.duration_seconds, e.passing_score,
               e.max_attempts AS attempt_limit, e.show_answers_after_submit,
               c.title AS course_title
        FROM ld_evaluation e
        JOIN ld_course c ON c.id = e.course_id
        WHERE e.id = :id AND e.status = 'active' AND c.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $evaluationId]);
    $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($evaluation) {
        $courseId = (int) $evaluation['course_id'];

        $enrollmentObj = new Enrollment($pdo);
        $enrollment = $enrollmentObj->getByLearnerAndCourse($learnerId, $courseId);

        // Real number of active questions in the pool.
        $qStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_quiz_question WHERE item_type = 'evaluation' AND reference_id = :eid AND status = 'active'");
        $qStmt->execute([':eid' => $evaluationId]);
        $questionCount = (int) $qStmt->fetchColumn();

        // Finished attempts (submitted or expired).
        $attemptsStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_quiz_session WHERE learner_id = :lid AND item_type = 'evaluation' AND reference_id = :eid AND status IN ('submitted','expired')");
        $attemptsStmt->execute([':lid' => $learnerId, ':eid' => $evaluationId]);
        $attemptCount = (int) $attemptsStmt->fetchColumn();

        $bestStmt = $pdo->prepare("SELECT id, score, passed, submitted_at FROM ld_quiz_session WHERE learner_id = :lid AND item_type = 'evaluation' AND reference_id = :eid AND status IN ('submitted','expired') ORDER BY score DESC LIMIT 1");
        $bestStmt->execute([':lid' => $learnerId, ':eid' => $evaluationId]);
        $bestAttempt = $bestStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $recentStmt = $pdo->prepare("SELECT id, score, passed, submitted_at FROM ld_quiz_session WHERE learner_id = :lid AND item_type = 'evaluation' AND reference_id = :eid AND status IN ('submitted','expired') ORDER BY id DESC LIMIT 1");
        $recentStmt->execute([':lid' => $learnerId, ':eid' => $evaluationId]);
        $recentAttempt = $recentStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Completion gate — the evaluation is the course's final assessment.
        if ($enrollment) {
            $progress = new Progress($pdo);
            $gateOk = $progress->hasCompletedAllCourseContent((int) $enrollment['id'], $courseId);

            $lessonStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_lesson l JOIN ld_module m ON m.id = l.module_id WHERE m.course_id = :cid AND l.status = 'active' AND m.status = 'active'");
            $lessonStmt->execute([':cid' => $courseId]);
            $quizStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_quiz q JOIN ld_module m ON m.id = q.module_id WHERE m.course_id = :cid AND q.status = 'active' AND m.status = 'active'");
            $quizStmt->execute([':cid' => $courseId]);
            $totalItems = (int) $lessonStmt->fetchColumn() + (int) $quizStmt->fetchColumn();

            $doneStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_progress WHERE enrollment_id = :eid AND status = 'completed' AND item_type IN ('lesson','quiz')");
            $doneStmt->execute([':eid' => $enrollment['id']]);
            $completedItems = (int) $doneStmt->fetchColumn();
        }
    }
} catch (Throwable $e) {
    DbError::capture($e, 'learner/study-subpage/evaluation');
    $evaluation = null;
}

if (!$evaluation) {
    ?>
    <div class="module-content">
        <div class="study-doc">
            <div class="study-panel" style="text-align:center;">
                <i class="fas fa-triangle-exclamation" style="display:block;margin-bottom:0.75rem;color:var(--muted);font-size:2rem;"></i>
                <h2 style="margin:0 0 0.5rem;">Evaluation Not Found</h2>
                <p style="margin:0 0 1.25rem;color:var(--muted);">The evaluation you are looking for does not exist.</p>
                <a class="study-btn" href="?page=learner/study"><i class="fas fa-arrow-left"></i> Back to Study</a>
            </div>
        </div>
    </div>
    <?php
    return;
}

$hasQuestions = $questionCount > 0;
$canAttempt = true;
if ($evaluation['attempt_limit'] && $attemptCount >= (int) $evaluation['attempt_limit']) {
    $canAttempt = false;
}
$canStart = $hasQuestions && $canAttempt && $gateOk;

$passingScore = $evaluation['passing_score'] !== null ? (float) $evaluation['passing_score'] : null;
$passingLabel = $passingScore === null
    ? 'N/A'
    : rtrim(rtrim(number_format($passingScore, 2, '.', ''), '0'), '.') . '%';

$fmtScore = static function ($score): string {
    return $score === null ? 'N/A' : rtrim(rtrim(number_format((float) $score, 1, '.', ''), '0'), '.') . '%';
};

$showBest = $bestAttempt !== null;
$showRecent = $recentAttempt !== null && (!$showBest || (int) $recentAttempt['id'] !== (int) $bestAttempt['id']);
$remainingItems = max(0, $totalItems - $completedItems);
?>

<div class="module-content">
<?php require_once __DIR__ . '/includes/course-sidebar.php'; ?>

    <div class="study-breadcrumb">
        <a href="?page=learner/study"><i class="fas fa-book-open"></i> My Study</a>
        <i class="fas fa-chevron-right sep"></i>
        <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>"><?= htmlspecialchars($evaluation['course_title']) ?></a>
        <i class="fas fa-chevron-right sep"></i>
        <span class="current">Evaluation</span>
    </div>

    <div class="study-doc">
        <?php if ($justFinished): ?>
            <div class="study-alert study-alert-success" role="status">
                <i class="fas fa-circle-check"></i>
                <span>Your evaluation has been submitted. See your result below.</span>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="study-alert study-alert-error" role="alert">
                <i class="fas fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($errorMessage) ?></span>
            </div>
        <?php endif; ?>

        <!-- Evaluation header -->
        <div class="study-header-card">
            <div class="study-pills">
                <span class="study-pill study-pill-primary"><i class="fas fa-clipboard-check"></i> Evaluation</span>
                <span class="study-pill study-pill-outline"><?= htmlspecialchars($evaluation['course_title']) ?></span>
            </div>
            <h1 class="study-header-title"><?= htmlspecialchars($evaluation['title']) ?></h1>
            <p class="study-header-desc">This is the final assessment for the course. Complete all lessons and quizzes to unlock it.</p>
        </div>

        <!-- Evaluation facts -->
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
                    <span class="home-stat-value"><?= $evaluation['duration_seconds'] ? round($evaluation['duration_seconds'] / 60) . ' min' : 'None' ?></span>
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
                    <span class="home-stat-value"><?= $evaluation['attempt_limit'] ? $attemptCount . ' / ' . (int) $evaluation['attempt_limit'] : (string) $attemptCount ?></span>
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
                                <span class="home-badge <?= $bestAttempt['passed'] ? 'is-success' : 'is-danger' ?>"><?= $fmtScore($bestAttempt['score']) ?></span>
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
                                <span class="home-badge <?= $recentAttempt['passed'] ? 'is-success' : 'is-danger' ?>"><?= $fmtScore($recentAttempt['score']) ?></span>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Start / status -->
        <div class="study-panel" style="text-align:center;">
            <?php if (!$enrollment): ?>
                <div class="study-alert study-alert-info" style="justify-content:center;">
                    <i class="fas fa-circle-info"></i>
                    <span>You are not enrolled in this course.</span>
                </div>
            <?php elseif (!$hasQuestions): ?>
                <div class="study-alert study-alert-info" style="justify-content:center;">
                    <i class="fas fa-circle-info"></i>
                    <span>This evaluation doesn't have any questions yet. Please check back later.</span>
                </div>
            <?php elseif (!$gateOk): ?>
                <div class="study-alert study-alert-info" style="justify-content:center;">
                    <i class="fas fa-lock"></i>
                    <span>Complete all course content to unlock this evaluation<?= $totalItems > 0 ? ' — ' . $completedItems . ' of ' . $totalItems . ' items done' : '' ?>.</span>
                </div>
                <a class="study-btn" href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>" style="margin-top:1rem;">
                    <i class="fas fa-arrow-left"></i> Back to Course
                </a>
            <?php elseif (!$canAttempt): ?>
                <div class="study-alert study-alert-info" style="justify-content:center;">
                    <i class="fas fa-ban"></i>
                    <span>You have reached the maximum number of attempts (<?= (int) $evaluation['attempt_limit'] ?>).</span>
                </div>
            <?php else: ?>
                <a class="study-btn" href="?page=learner/study-subpage/evaluation-taker&evaluation_id=<?= $evaluationId ?>&course_id=<?= $courseId ?>">
                    <i class="fas fa-play"></i> <?= $attemptCount > 0 ? 'Retake Evaluation' : 'Start Evaluation' ?>
                </a>
                <p style="margin:0.9rem 0 0;color:var(--muted);font-size:0.82rem;">
                    <?php if ($evaluation['attempt_limit']): ?>
                        <?= $attemptCount ?> of <?= (int) $evaluation['attempt_limit'] ?> attempts used
                    <?php else: ?>
                        Unlimited attempts
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/course-sidebar-footer.php'; ?>
</div>
