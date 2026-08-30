<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 3) . '/classes/progress.php';
require_once dirname(__DIR__, 3) . '/classes/enrollment.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$moduleId = (int) ($_GET['module_id'] ?? 0);
$courseId = (int) ($_GET['course_id'] ?? 0);
$module = null;
$lessons = [];
$quizzes = [];
$enrollment = null;
$currentPageType = 'module';
$currentPageId = $moduleId;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT m.id, m.title, m.description, m.order_index,
               c.id AS course_id, c.title AS course_title
        FROM ld_module m
        JOIN ld_course c ON c.id = m.course_id
        WHERE m.id = :id AND m.status = 'active' AND c.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $moduleId]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($module) {
        $courseId = (int) $module['course_id'];
        $enrollmentObj = new Enrollment($pdo);
        $enrollment = $enrollmentObj->getByLearnerAndCourse($learnerId, $courseId);

        $lessonStmt = $pdo->prepare("
            SELECT l.id, l.title, l.content_type, l.order_index,
                (SELECT status FROM ld_progress WHERE enrollment_id = :eid AND item_type = 'lesson' AND reference_id = l.id LIMIT 1) AS progress_status
            FROM ld_lesson l
            WHERE l.module_id = :mid AND l.status = 'active'
            ORDER BY l.order_index ASC, l.id ASC
        ");
        $lessonStmt->execute([':mid' => $moduleId, ':eid' => $enrollment['id'] ?? 0]);
        $lessons = $lessonStmt->fetchAll(PDO::FETCH_ASSOC);

        $quizStmt = $pdo->prepare("
            SELECT q.id, q.title, ROUND(q.duration_seconds / 60) AS time_limit_minutes, q.passing_score,
                (SELECT COUNT(*) FROM ld_quiz_question qq
                 WHERE qq.item_type = 'quiz' AND qq.reference_id = q.id AND qq.status = 'active') AS question_count,
                (SELECT CASE WHEN qa.passed = 1 THEN 'passed' ELSE 'failed' END
                 FROM ld_quiz_attempt qa
                 WHERE qa.learner_id = :lid AND qa.quiz_id = q.id
                 ORDER BY qa.id DESC LIMIT 1) AS attempt_status
            FROM ld_quiz q
            WHERE q.module_id = :mid AND q.status = 'active'
            ORDER BY q.id ASC
        ");
        $quizStmt->execute([':mid' => $moduleId, ':lid' => $learnerId]);
        $quizzes = $quizStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $module = null;
}

if (!$module) {
    echo '<div class="module-content"><div class="mode-card"><h2>Module Not Found</h2><p>The module you are looking for does not exist.</p>';
    echo '<a href="?page=learner/study" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; font-weight:600;">Back to Study</a></div></div>';
    return;
}
?>

<style>
.study-pill{display:inline-flex;align-items:center;padding:0.3rem 0.75rem;border-radius:999px;font-size:0.72rem;font-weight:700;letter-spacing:0.02em}
.study-pill-primary{background:linear-gradient(135deg,rgba(32,0,130,0.88),rgba(81,70,183,0.75));color:#fff}
.study-pill-outline{background:rgba(32,0,130,0.06);color:var(--primary)}
.study-mod-item{display:flex;align-items:center;gap:1rem;padding:1rem 1.25rem;background:var(--surface,#fff);border:1px solid rgba(32,0,130,0.08);border-radius:12px;text-decoration:none;color:inherit;transition:all 0.2s}
.study-mod-item:hover{border-color:rgba(32,0,130,0.2);box-shadow:0 2px 8px rgba(32,0,130,0.06);transform:translateY(-1px)}
.study-mod-item.done{border-left:4px solid #10b981;background:rgba(16,185,129,0.02)}
.study-mod-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.85rem;font-weight:700}
.study-mod-icon.done{background:rgba(16,185,129,0.1);color:#10b981}
.study-mod-icon.pending{background:rgba(32,0,130,0.06);color:var(--primary)}
.study-mod-info{flex:1;min-width:0}
.study-mod-name{font-weight:600;color:var(--text);margin-bottom:0.15rem;font-size:0.92rem}
.study-mod-detail{font-size:0.78rem;color:var(--muted,#888)}
.study-mod-arrow{color:var(--muted,#ccc);font-size:0.8rem}
</style>

<div class="module-content">
<?php require_once __DIR__ . '/includes/course-sidebar.php'; ?>

    <!-- Breadcrumb -->
    <div class="study-breadcrumb">
        <a href="?page=learner/study"><i class="fas fa-book-open" style="margin-right:0.25rem;"></i> My Study</a>
        <i class="fas fa-chevron-right sep"></i>
        <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>"><?= htmlspecialchars($module['course_title']) ?></a>
        <i class="fas fa-chevron-right sep"></i>
        <span class="current">Module <?= $module['order_index'] + 1 ?></span>
    </div>

    <!-- Module Header -->
    <div class="mode-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:0.45rem; margin-bottom:0.75rem; flex-wrap:wrap;">
            <span class="study-pill study-pill-primary">Module <?= $module['order_index'] + 1 ?></span>
            <span class="study-pill study-pill-outline"><?= count($lessons) ?> lesson<?= count($lessons) !== 1 ? 's' : '' ?></span>
            <span class="study-pill study-pill-outline"><?= count($quizzes) ?> quiz<?= count($quizzes) !== 1 ? 'zes' : '' ?></span>
        </div>
        <h1 style="margin:0 0 0.5rem 0; font-size:1.55rem; color:var(--text); font-weight:800; letter-spacing:-0.02em;"><?= htmlspecialchars($module['title']) ?></h1>
        <?php if ($module['description']): ?>
            <p style="color:var(--muted, #555); line-height:1.7; margin:0;"><?= nl2br(htmlspecialchars($module['description'])) ?></p>
        <?php endif; ?>
    </div>

    <!-- Lessons -->
    <div class="mode-card" style="margin-bottom:1.5rem;">
        <h2 style="margin-bottom:1rem; font-size:1.1rem;"><i class="fas fa-book-open" style="color:var(--primary); margin-right:0.4rem;"></i>Lessons</h2>
        <?php if (empty($lessons)): ?>
            <div style="text-align:center; padding:2rem; color:var(--muted, #999);">No lessons in this module yet.</div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                <?php foreach ($lessons as $idx => $lesson):
                    $isComplete = $lesson['progress_status'] === 'completed';
                    $typeIcon = match($lesson['content_type']) { 'video' => 'fa-video', 'text' => 'fa-file-alt', 'file' => 'fa-paperclip', default => 'fa-book-open' };
                ?>
                    <a href="?page=learner/study-subpage/lesson&lesson_id=<?= $lesson['id'] ?>&course_id=<?= $courseId ?>" class="study-mod-item <?= $isComplete ? 'done' : '' ?>">
                        <div class="study-mod-icon <?= $isComplete ? 'done' : 'pending' ?>">
                            <?= $isComplete ? '<i class="fas fa-check"></i>' : ($idx + 1) ?>
                        </div>
                        <div class="study-mod-info">
                            <div class="study-mod-name"><?= htmlspecialchars($lesson['title']) ?></div>
                            <div class="study-mod-detail"><i class="fas <?= $typeIcon ?>" style="margin-right:3px;"></i><?= ucfirst($lesson['content_type'] ?? 'lesson') ?></div>
                        </div>
                        <i class="fas fa-chevron-right study-mod-arrow"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quizzes -->
    <div class="mode-card">
        <h2 style="margin-bottom:1rem; font-size:1.1rem;"><i class="fas fa-question-circle" style="color:var(--primary); margin-right:0.4rem;"></i>Quizzes</h2>
        <?php if (empty($quizzes)): ?>
            <div style="text-align:center; padding:2rem; color:var(--muted, #999);">No quizzes in this module yet.</div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                <?php foreach ($quizzes as $quiz): ?>
                    <a href="?page=learner/study-subpage/quiz&quiz_id=<?= $quiz['id'] ?>&course_id=<?= $courseId ?>" class="study-mod-item">
                        <div class="study-mod-icon pending"><i class="fas fa-question"></i></div>
                        <div class="study-mod-info">
                            <div class="study-mod-name"><?= htmlspecialchars($quiz['title']) ?></div>
                            <div class="study-mod-detail">
                                <?= $quiz['question_count'] ?> question<?= $quiz['question_count'] !== 1 ? 's' : '' ?>
                                <?php if ($quiz['time_limit_minutes']): ?> &middot; <?= $quiz['time_limit_minutes'] ?> min<?php endif; ?>
                                <?php if ($quiz['attempt_status']): ?> &middot; <?= ucfirst($quiz['attempt_status']) ?><?php endif; ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right study-mod-arrow"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/includes/course-sidebar-footer.php'; ?>
</div>
