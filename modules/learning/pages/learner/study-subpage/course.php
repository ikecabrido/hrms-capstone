<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 3) . '/classes/enrollment.php';
require_once dirname(__DIR__, 3) . '/classes/progress.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$courseId = (int) ($_GET['course_id'] ?? 0);
$course = null;
$modules = [];
$enrollment = null;
$progressPercent = 0;
$skills = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.description, c.category, c.status, c.thumbnail_path,
               c.start_date, c.enrollment_deadline, c.created_at,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
               c.instructor_id
        FROM ld_course c
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($course) {
        $enrollmentObj = new Enrollment($pdo);
        $enrollment = $enrollmentObj->getByLearnerAndCourse($learnerId, $courseId);

        if ($enrollment) {
            $progressObj = new Progress($pdo);
            $progressPercent = $progressObj->getPercentComplete((int) $enrollment['id'], $courseId);

            $modStmt = $pdo->prepare("
                SELECT m.id, m.title, m.description, m.order_index,
                    (SELECT COUNT(*) FROM ld_lesson l WHERE l.module_id = m.id AND l.status = 'active') AS lesson_count,
                    (SELECT COUNT(*) FROM ld_quiz q WHERE q.module_id = m.id AND q.status = 'active') AS quiz_count
                FROM ld_module m
                WHERE m.course_id = :cid AND m.status = 'active'
                ORDER BY m.order_index ASC, m.id ASC
            ");
            $modStmt->execute([':cid' => $courseId]);
            $modules = $modStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($modules as &$mod) {
                $pStmt = $pdo->prepare("SELECT status FROM ld_progress WHERE enrollment_id = :eid AND item_type = 'module' AND reference_id = :rid LIMIT 1");
                $pStmt->execute([':eid' => $enrollment['id'], ':rid' => $mod['id']]);
                $mod['completed'] = ($pStmt->fetchColumn() === 'completed');

                // Fetch lessons with progress
                $lStmt = $pdo->prepare("
                    SELECT l.id, l.title, l.order_index,
                        (SELECT status FROM ld_progress WHERE enrollment_id = :eid AND item_type = 'lesson' AND reference_id = l.id LIMIT 1) AS progress_status
                    FROM ld_lesson l WHERE l.module_id = :mid AND l.status = 'active'
                    ORDER BY l.order_index ASC, l.id ASC
                ");
                $lStmt->execute([':mid' => $mod['id'], ':eid' => $enrollment['id']]);
                $mod['lessons'] = $lStmt->fetchAll(PDO::FETCH_ASSOC);

                // Fetch quizzes with attempt info
                $qStmt = $pdo->prepare("
                    SELECT q.id, q.title,
                        (SELECT qa.score FROM ld_quiz_attempt qa WHERE qa.learner_id = :lid AND qa.quiz_id = q.id ORDER BY qa.id DESC LIMIT 1) AS last_score,
                        (SELECT qa.passed FROM ld_quiz_attempt qa WHERE qa.learner_id = :lid AND qa.quiz_id = q.id ORDER BY qa.id DESC LIMIT 1) AS last_passed,
                        (SELECT COUNT(*) FROM ld_quiz_attempt qa WHERE qa.learner_id = :lid AND qa.quiz_id = q.id) AS attempt_count
                    FROM ld_quiz q WHERE q.module_id = :mid AND q.status = 'active' ORDER BY q.id ASC
                ");
                $qStmt->execute([':mid' => $mod['id'], ':lid' => $learnerId]);
                $mod['quizzes'] = $qStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($mod);
        }

        $skillsStmt = $pdo->prepare("SELECT s.name FROM ld_course_skill cs JOIN ld_skill s ON s.id = cs.skill_id WHERE cs.course_id = :cid");
        $skillsStmt->execute([':cid' => $courseId]);
        $skills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Throwable $e) {
    $course = null;
}

if (!$course) {
    echo '<div class="module-content"><div class="mode-card"><h2>Course Not Found</h2><p>The course you are looking for does not exist.</p>';
    echo '<a href="?page=learner/study" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; font-weight:600;">Back to Study</a></div></div>';
    return;
}

$isEnrolled = !empty($enrollment);
$isCompleted = $isEnrolled && $enrollment['status'] === 'completed';
$enrollmentStatus = $isEnrolled ? $enrollment['status'] : 'not_enrolled';
$completedCount = 0;
foreach ($modules as $m) { if ($m['completed']) $completedCount++; }

// Sidebar variables
$moduleId = 0;
$currentPageType = 'course';
$currentPageId = $courseId;
?>

<style>
/* ── Study Course Page ───────────────────────────────────────────── */
.course-header-card {
    position: relative;
    z-index: 2;
    background: var(--surface, #fff);
    border: 1px solid rgba(32,0,130,0.12);
    border-radius: 18px;
    padding: 2rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    margin-bottom: 1.5rem;
    overflow: hidden;
}
.course-header-card .study-pills {
    display: flex;
    gap: 0.45rem;
    margin-bottom: 0.85rem;
    flex-wrap: wrap;
}
.study-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.study-pill-primary {
    background: linear-gradient(135deg, rgba(32,0,130,0.88), rgba(81,70,183,0.75));
    color: #fff;
}
.study-pill-outline {
    background: rgba(32,0,130,0.06);
    color: var(--primary);
}
.study-pill-success {
    background: rgba(16,185,129,0.1);
    color: #10b981;
}
.study-pill-muted {
    background: var(--bg-subtle, rgba(240,240,240,1));
    color: var(--muted, #666);
}

.course-title {
    margin: 0 0 0.6rem;
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--text);
    letter-spacing: -0.02em;
    line-height: 1.3;
}
.course-desc {
    color: var(--muted, #555);
    line-height: 1.7;
    margin: 0 0 1.5rem;
}

.course-meta {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.course-meta-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.course-meta-item i {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(32,0,130,0.06);
    color: var(--primary);
    font-size: 0.85rem;
    flex-shrink: 0;
}
.course-meta-label {
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--muted, #999);
    font-weight: 600;
    margin-bottom: 0.1rem;
}
.course-meta-value {
    font-weight: 600;
    color: var(--text);
    font-size: 0.92rem;
}

.course-skills {
    margin-bottom: 1.5rem;
}
.course-skills-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.course-skills-list {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
}
.course-skill-tag {
    padding: 0.3rem 0.75rem;
    background: rgba(32,0,130,0.06);
    border: 1px solid rgba(32,0,130,0.1);
    border-radius: 20px;
    font-size: 0.78rem;
    color: var(--primary);
    font-weight: 500;
}

.course-progress { margin-bottom: 0; }
.course-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}
.course-progress-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text);
}
.course-progress-value {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--primary);
}
.course-progress-track {
    height: 8px;
    background: rgba(32,0,130,0.08);
    border-radius: 99px;
    overflow: hidden;
}
.course-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), rgba(81,70,183,0.8));
    border-radius: 99px;
    transition: width 0.6s ease;
}

/* Module list */
.study-modules-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 1.25rem;
}
.study-modules-header h2 { margin: 0; }
.study-modules-count {
    font-size: 0.82rem;
    color: var(--muted, #666);
}

.study-module-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.1rem 1.25rem;
    background: var(--surface, #fff);
    border: 1px solid rgba(32,0,130,0.08);
    border-radius: 14px;
    transition: all 0.2s ease;
}
.study-module-item:hover {
    border-color: rgba(32,0,130,0.2);
    box-shadow: 0 2px 8px rgba(32,0,130,0.06);
}
.study-module-item.completed {
    border-left: 4px solid #10b981;
    background: rgba(16,185,129,0.03);
}
.study-module-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.9rem;
    font-weight: 700;
}
.study-module-icon.done {
    background: rgba(16,185,129,0.1);
    color: #10b981;
}
.study-module-icon.locked {
    background: rgba(32,0,130,0.06);
    color: var(--primary);
}
.study-module-info { flex: 1; min-width: 0; }
.study-module-name {
    font-weight: 600;
    color: var(--text);
    margin-bottom: 0.2rem;
    font-size: 0.95rem;
}
.study-module-detail {
    font-size: 0.8rem;
    color: var(--muted, #888);
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
.study-module-action {
    padding: 0.5rem 1.1rem;
    background: var(--primary);
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: all 0.2s;
}
.study-module-action:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(32,0,130,0.25);
}
.study-module-action.done-btn {
    background: rgba(16,185,129,0.1);
    color: #10b981;
}

/* Empty state */
.study-empty {
    text-align: center;
    padding: 3rem;
    color: var(--muted, #999);
}
.study-empty i { font-size: 2rem; margin-bottom: 0.75rem; display: block; opacity: 0.5; }

/* Completed banner */
.study-completed-banner {
    text-align: center;
    padding: 2rem;
    margin-top: 1.5rem;
    border: 2px solid rgba(16,185,129,0.3);
    background: rgba(16,185,129,0.04);
    border-radius: 18px;
}
.study-completed-banner i { font-size: 2.5rem; color: #10b981; margin-bottom: 0.75rem; display: block; }
.study-completed-banner h2 { margin: 0 0 0.5rem; color: #10b981; }
.study-completed-banner p { margin: 0 0 1rem; color: var(--muted, #666); }

/* Expandable module dropdown */
.module-expand-btn{
    display:flex;align-items:center;gap:0.6rem;width:100%;padding:1.1rem 1.25rem;
    background:var(--surface,#fff);border:1px solid rgba(32,0,130,0.08);border-radius:14px;
    cursor:pointer;transition:all 0.2s ease;text-align:left;
}
.module-expand-btn:hover{border-color:rgba(32,0,130,0.2);box-shadow:0 2px 8px rgba(32,0,130,0.06)}
.module-expand-btn.completed{border-left:4px solid #10b981;background:rgba(16,185,129,0.03)}
.module-expand-chevron{font-size:0.65rem;color:var(--muted,#aaa);transition:transform 0.25s ease;flex-shrink:0}
.module-expand-chevron.open{transform:rotate(90deg)}
.module-expand-children{max-height:0;overflow:hidden;transition:max-height 0.3s ease;padding:0 0 0 3.25rem}
.module-expand-children.open{max-height:600px;padding:0.25rem 0 0.5rem 3.25rem}
.module-child-item{display:flex;align-items:center;gap:0.6rem;padding:0.5rem 0.75rem;font-size:0.82rem;color:var(--muted,#666);border-radius:8px;transition:background 0.15s}
.module-child-item:hover{background:rgba(32,0,130,0.03)}
.module-child-icon{width:20px;text-align:center;flex-shrink:0}
.module-child-icon.done{color:#10b981}
.module-child-icon.quiz-pass{color:#10b981}
.module-child-icon.quiz-fail{color:#dc3545}
.module-child-title{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.module-child-badge{font-size:0.7rem;font-weight:600;padding:0.15rem 0.5rem;border-radius:99px;white-space:nowrap}
.module-child-badge.pass{background:rgba(16,185,129,0.1);color:#10b981}
.module-child-badge.fail{background:rgba(220,53,69,0.1);color:#dc3545}
.module-child-badge.score{background:rgba(32,0,130,0.06);color:var(--primary)}
.module-child-badge.done{background:rgba(16,185,129,0.1);color:#10b981}
.module-child-badge.pending{background:rgba(32,0,130,0.06);color:var(--muted,#888)}

/* Not enrolled */
.study-not-enrolled {
    text-align: center;
    padding: 2rem;
}
.study-not-enrolled h2 { margin: 0 0 0.5rem; }
.study-not-enrolled p { color: var(--muted, #666); margin: 0 0 1.25rem; }

/* Modules list card */
.course-content-card {
    position: relative;
    z-index: 2;
    background: var(--surface, #fff);
    border: 1px solid rgba(32,0,130,0.12);
    border-radius: 18px;
    padding: 2rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
}
</style>

<div class="module-content">
<?php require_once __DIR__ . '/includes/course-sidebar.php'; ?>

    <!-- Breadcrumb -->
    <div class="study-breadcrumb">
        <a href="?page=learner/study"><i class="fas fa-book-open" style="margin-right:0.25rem;"></i> My Study</a>
        <i class="fas fa-chevron-right sep"></i>
        <span class="current"><?= htmlspecialchars($course['title']) ?></span>
    </div>

    <!-- Course Header Card — everything is INSIDE this card -->
    <div class="course-header-card">
        <div class="study-pills">
            <span class="study-pill study-pill-primary"><i class="fas fa-graduation-cap" style="margin-right:0.25rem;"></i> Course</span>
            <?php if (!empty($course['category'])): ?>
                <span class="study-pill study-pill-outline"><?= htmlspecialchars($course['category']) ?></span>
            <?php endif; ?>
            <?php if ($isCompleted): ?>
                <span class="study-pill study-pill-success"><i class="fas fa-check-circle" style="margin-right:0.2rem;"></i> Completed</span>
            <?php elseif ($isEnrolled): ?>
                <span class="study-pill study-pill-outline"><i class="fas fa-play-circle" style="margin-right:0.2rem;"></i> Enrolled</span>
            <?php else: ?>
                <span class="study-pill study-pill-muted">Not Enrolled</span>
            <?php endif; ?>
        </div>

        <h1 class="course-title"><?= htmlspecialchars($course['title']) ?></h1>
        <?php if ($course['description']): ?>
            <p class="course-desc"><?= nl2br(htmlspecialchars($course['description'])) ?></p>
        <?php endif; ?>

        <div class="course-meta">
            <?php if (!empty($course['instructor_name'])): ?>
                <div class="course-meta-item">
                    <i class="fas fa-user-tie"></i>
                    <div>
                        <div class="course-meta-label">Instructor</div>
                        <div class="course-meta-value"><?= htmlspecialchars($course['instructor_name']) ?></div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="course-meta-item">
                <i class="fas fa-cubes"></i>
                <div>
                    <div class="course-meta-label">Modules</div>
                    <div class="course-meta-value"><?= count($modules) ?></div>
                </div>
            </div>
            <?php if ($isEnrolled && count($modules) > 0): ?>
                <div class="course-meta-item">
                    <i class="fas fa-tasks"></i>
                    <div>
                        <div class="course-meta-label">Completed</div>
                        <div class="course-meta-value"><?= $completedCount ?> / <?= count($modules) ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($skills)): ?>
            <div class="course-skills">
                <div class="course-skills-label">Skills Covered</div>
                <div class="course-skills-list">
                    <?php foreach ($skills as $skill): ?>
                        <span class="course-skill-tag"><?= htmlspecialchars($skill) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isEnrolled): ?>
            <div class="course-progress">
                <div class="course-progress-header">
                    <span class="course-progress-label">Progress</span>
                    <span class="course-progress-value"><?= round($progressPercent) ?>%</span>
                </div>
                <div class="course-progress-track">
                    <div class="course-progress-fill" style="width:<?= $progressPercent ?>%;"></div>
                </div>
            </div>
        <?php endif; ?>
    </div><!-- /.course-header-card -->

    <!-- Course Content / Modules -->
    <?php if ($isEnrolled): ?>
        <div class="course-content-card">
            <div class="study-modules-header">
                <h2>Course Content</h2>
                <span class="study-modules-count"><?= count($modules) ?> module<?= count($modules) !== 1 ? 's' : '' ?> &middot; Self-paced</span>
            </div>

            <?php if (empty($modules)): ?>
                <div class="study-empty">
                    <i class="fas fa-folder-open"></i>
                    <p>No modules have been added to this course yet.</p>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                    <?php foreach ($modules as $idx => $mod): ?>
                        <div>
                            <button type="button" class="module-expand-btn <?= $mod['completed'] ? 'completed' : '' ?>" onclick="this.nextElementSibling.classList.toggle('open');this.querySelector('.module-expand-chevron').classList.toggle('open')">
                                <div class="study-module-icon <?= $mod['completed'] ? 'done' : 'locked' ?>" style="width:36px;height:36px;border-radius:10px;flex-shrink:0;">
                                    <?php if ($mod['completed']): ?>
                                        <i class="fas fa-check"></i>
                                    <?php else: ?>
                                        <?= $idx + 1 ?>
                                    <?php endif; ?>
                                </div>
                                <a href="?page=learner/study-subpage/module&module_id=<?= $mod['id'] ?>&course_id=<?= $courseId ?>" style="flex:1;min-width:0;text-decoration:none;color:inherit;" onclick="event.stopPropagation()">
                                    <div style="font-weight:600;color:var(--text);font-size:0.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($mod['title']) ?></div>
                                    <div style="font-size:0.78rem;color:var(--muted,#888);margin-top:0.15rem;">
                                        <?= $mod['lesson_count'] ?> lesson<?= $mod['lesson_count'] !== 1 ? 's' : '' ?>
                                        &middot;
                                        <?= $mod['quiz_count'] ?> quiz<?= $mod['quiz_count'] !== 1 ? 'zes' : '' ?>
                                    </div>
                                </a>
                                <i class="fas fa-chevron-right module-expand-chevron"></i>
                            </button>
                            <div class="module-expand-children">
                                <?php if (!empty($mod['lessons'])): ?>
                                    <?php foreach ($mod['lessons'] as $lesson): ?>
                                        <?php $lDone = ($lesson['progress_status'] === 'completed'); ?>
                                        <a href="?page=learner/study-subpage/lesson&lesson_id=<?= $lesson['id'] ?>&course_id=<?= $courseId ?>" class="module-child-item">
                                            <span class="module-child-icon <?= $lDone ? 'done' : '' ?>">
                                                <i class="fas <?= $lDone ? 'fa-check-circle' : 'fa-file-alt' ?>"></i>
                                            </span>
                                            <span class="module-child-title"><?= htmlspecialchars($lesson['title']) ?></span>
                                            <span class="module-child-badge <?= $lDone ? 'done' : 'pending' ?>"><?= $lDone ? 'Done' : 'Pending' ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (!empty($mod['quizzes'])): ?>
                                    <?php foreach ($mod['quizzes'] as $quiz): ?>
                                        <?php
                                            $qDone = ($quiz['last_passed'] == 1);
                                            $qFailed = ($quiz['attempt_count'] > 0 && !$qDone);
                                        ?>
                                        <a href="?page=learner/study-subpage/quiz&quiz_id=<?= $quiz['id'] ?>&course_id=<?= $courseId ?>" class="module-child-item">
                                            <span class="module-child-icon <?= $qDone ? 'quiz-pass' : ($qFailed ? 'quiz-fail' : '') ?>">
                                                <i class="fas <?= $qDone ? 'fa-check-circle' : ($qFailed ? 'fa-times-circle' : 'fa-question-circle') ?>"></i>
                                            </span>
                                            <span class="module-child-title"><?= htmlspecialchars($quiz['title']) ?></span>
                                            <?php if ($qDone): ?>
                                                <span class="module-child-badge pass"><?= round($quiz['last_score'], 1) ?>%</span>
                                            <?php elseif ($qFailed): ?>
                                                <span class="module-child-badge fail"><?= round($quiz['last_score'], 1) ?>%</span>
                                            <?php else: ?>
                                                <span class="module-child-badge pending">Not taken</span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (empty($mod['lessons']) && empty($mod['quizzes'])): ?>
                                    <div style="padding:0.5rem 0.75rem;font-size:0.8rem;color:var(--muted,#aaa);font-style:italic;">No content yet</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div><!-- /.course-content-card -->
    <?php else: ?>
        <div class="course-content-card study-not-enrolled">
            <i class="fas fa-lock" style="font-size:2.5rem; color:var(--primary); opacity:0.4; margin-bottom:1rem; display:block;"></i>
            <h2>Not Enrolled</h2>
            <p>Enroll in this course to access its content and track your progress.</p>
            <a href="?page=learner/catalog-subpage/course&course_id=<?= $courseId ?>" class="study-module-action" style="display:inline-flex;">
                <i class="fas fa-graduation-cap"></i> View in Catalog
            </a>
        </div>
    <?php endif; ?>

    <?php if ($isCompleted): ?>
        <div class="course-content-card study-completed-banner">
            <i class="fas fa-award"></i>
            <h2>Course Completed!</h2>
            <p>Congratulations on completing this course.</p>
            <a href="?page=learner/result-subpage/certificate" class="study-module-action" style="display:inline-flex; background:#10b981;">
                <i class="fas fa-certificate"></i> View Certificate
            </a>
        </div>
    <?php endif; ?>

<?php require_once __DIR__ . '/includes/course-sidebar-footer.php'; ?>
</div><!-- /.module-content -->
