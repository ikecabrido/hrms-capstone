<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 3) . '/classes/enrollment.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$quizId = (int) ($_GET['quiz_id'] ?? 0);
$courseId = (int) ($_GET['course_id'] ?? 0);
$quiz = null;
$bestAttempt = null;
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

        $attemptsStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_quiz_attempt WHERE learner_id = :lid AND quiz_id = :qid");
        $attemptsStmt->execute([':lid' => $learnerId, ':qid' => $quizId]);
        $attemptCount = (int) $attemptsStmt->fetchColumn();

        $bestStmt = $pdo->prepare("SELECT id, score, passed, attempted_at AS submitted_at FROM ld_quiz_attempt WHERE learner_id = :lid AND quiz_id = :qid ORDER BY score DESC LIMIT 1");
        $bestStmt->execute([':lid' => $learnerId, ':qid' => $quizId]);
        $bestAttempt = $bestStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $recentStmt = $pdo->prepare("SELECT id, score, passed, attempted_at AS submitted_at FROM ld_quiz_attempt WHERE learner_id = :lid AND quiz_id = :qid ORDER BY id DESC LIMIT 1");
        $recentStmt->execute([':lid' => $learnerId, ':qid' => $quizId]);
        $recentAttempt = $recentStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $e) {
    $quiz = null;
}

if (!$quiz) {
    echo '<div class="module-content"><div class="mode-card"><h2>Quiz Not Found</h2><p>The quiz you are looking for does not exist.</p>';
    echo '<a href="?page=learner/study" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; font-weight:600;">Back to Study</a></div></div>';
    return;
}

$canAttempt = true;
if ($quiz['attempt_limit'] && $attemptCount >= $quiz['attempt_limit']) $canAttempt = false;
?>

<style>
.study-pill{display:inline-flex;align-items:center;padding:0.3rem 0.75rem;border-radius:999px;font-size:0.72rem;font-weight:700;letter-spacing:0.02em}
.study-pill-primary{background:linear-gradient(135deg,rgba(32,0,130,0.88),rgba(81,70,183,0.75));color:#fff}
.study-pill-outline{background:rgba(32,0,130,0.06);color:var(--primary)}
.quiz-stat-card{padding:1rem;background:rgba(32,0,130,0.05);border-radius:10px;text-align:center}
.quiz-stat-label{font-size:0.72rem;color:var(--muted,#888);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.25rem}
.quiz-stat-value{font-size:1.5rem;font-weight:700;color:var(--primary)}
</style>

<div class="module-content">
<?php require_once __DIR__ . '/includes/course-sidebar.php'; ?>

    <div class="study-breadcrumb">
        <a href="?page=learner/study"><i class="fas fa-book-open" style="margin-right:0.25rem;"></i> My Study</a>
        <i class="fas fa-chevron-right sep"></i>
        <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>"><?= htmlspecialchars($quiz['course_title']) ?></a>
        <i class="fas fa-chevron-right sep"></i>
        <a href="?page=learner/study-subpage/module&module_id=<?= $moduleId ?>&course_id=<?= $courseId ?>"><?= htmlspecialchars($quiz['module_title']) ?></a>
        <i class="fas fa-chevron-right sep"></i>
        <span class="current">Quiz</span>
    </div>

    <div class="mode-card" style="max-width:700px; margin:0 auto;">
        <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem;">
            <div style="width:56px;height:56px;border-radius:14px;background:rgba(32,0,130,0.1);color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-question-circle" style="font-size:1.5rem;"></i>
            </div>
            <div>
                <h1 style="margin:0;font-size:1.5rem;color:var(--text);font-weight:800;"><?= htmlspecialchars($quiz['title']) ?></h1>
                <?php if ($quiz['description']): ?>
                    <p style="color:var(--muted,#555);line-height:1.5;margin:0.25rem 0 0;font-size:0.9rem;"><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;margin-bottom:1.5rem;">
            <tbody>
                <tr style="border-bottom:1px solid rgba(32,0,130,0.08);">
                    <td style="padding:0.75rem 1rem;color:var(--muted,#888);font-weight:600;width:50%;">Questions</td>
                    <td style="padding:0.75rem 1rem;font-weight:700;color:var(--primary);text-align:right;"><?= $quiz['question_count'] ?></td>
                </tr>
                <tr style="border-bottom:1px solid rgba(32,0,130,0.08);">
                    <td style="padding:0.75rem 1rem;color:var(--muted,#888);font-weight:600;">Time Limit</td>
                    <td style="padding:0.75rem 1rem;font-weight:700;color:var(--primary);text-align:right;"><?= $quiz['time_limit_minutes'] ? $quiz['time_limit_minutes'] . ' min' : 'None' ?></td>
                </tr>
                <tr style="border-bottom:1px solid rgba(32,0,130,0.08);">
                    <td style="padding:0.75rem 1rem;color:var(--muted,#888);font-weight:600;">Passing Score</td>
                    <td style="padding:0.75rem 1rem;font-weight:700;color:var(--primary);text-align:right;"><?= $quiz['passing_score'] ? $quiz['passing_score'] . '%' : 'N/A' ?></td>
                </tr>
                <tr style="border-bottom:1px solid rgba(32,0,130,0.08);">
                    <td style="padding:0.75rem 1rem;color:var(--muted,#888);font-weight:600;">Attempts Used</td>
                    <td style="padding:0.75rem 1rem;font-weight:700;color:var(--primary);text-align:right;"><?= $quiz['attempt_limit'] ? $attemptCount . ' / ' . $quiz['attempt_limit'] : $attemptCount . ' taken' ?></td>
                </tr>
                <?php if ($bestAttempt): ?>
                    <tr style="border-bottom:1px solid rgba(32,0,130,0.08);">
                        <td style="padding:0.75rem 1rem;color:var(--muted,#888);font-weight:600;">Highest Score</td>
                        <td style="padding:0.75rem 1rem;text-align:right;">
                            <span style="font-weight:700;color:<?= $bestAttempt['passed'] ? '#10b981' : '#dc3545' ?>;">"><?= $bestAttempt['score'] !== null ? round($bestAttempt['score'], 1) . '%' : 'N/A' ?></span>
                            <a href="?page=learner/study-subpage/quiz-review&session_id=<?= $bestAttempt['id'] ?>&course_id=<?= $courseId ?>" style="font-size:0.78rem;color:var(--primary);font-weight:600;margin-left:0.5rem;">Review</a>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if ($recentAttempt && (!$bestAttempt || $recentAttempt['id'] !== $bestAttempt['id'])): ?>
                    <tr>
                        <td style="padding:0.75rem 1rem;color:var(--muted,#888);font-weight:600;">Recent Score</td>
                        <td style="padding:0.75rem 1rem;text-align:right;">
                            <span style="font-weight:700;color:<?= $recentAttempt['passed'] ? '#10b981' : '#dc3545' ?>;">"><?= $recentAttempt['score'] !== null ? round($recentAttempt['score'], 1) . '%' : 'N/A' ?></span>
                            <a href="?page=learner/study-subpage/quiz-review&session_id=<?= $recentAttempt['id'] ?>&course_id=<?= $courseId ?>" style="font-size:0.78rem;color:var(--primary);font-weight:600;margin-left:0.5rem;">Review</a>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if ($bestAttempt && !$recentAttempt): ?>
                    <tr>
                        <td style="padding:0.75rem 1rem;color:var(--muted,#888);font-weight:600;">Recent Score</td>
                        <td style="padding:0.75rem 1rem;font-weight:700;color:var(--primary);text-align:right;"><?= round($bestAttempt['score'], 1) . '%' ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="text-align:center;">
            <?php if ($canAttempt): ?>
                <a href="?page=learner/study-subpage/quiz-taker&quiz_id=<?= $quizId ?>&course_id=<?= $courseId ?>" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.85rem 2rem;background:var(--primary);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:1.05rem;transition:all 0.2s;">
                    <i class="fas fa-play"></i> <?= $attemptCount > 0 ? 'Retake Quiz' : 'Start Quiz' ?>
                </a>
            <?php else: ?>
                <div style="padding:1rem;background:var(--bg-subtle,#f8f9fa);border-radius:10px;color:var(--muted,#666);">
                    <i class="fas fa-ban" style="margin-right:0.4rem;"></i> Maximum attempts reached (<?= $quiz['attempt_limit'] ?>)
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/course-sidebar-footer.php'; ?>
</div>
