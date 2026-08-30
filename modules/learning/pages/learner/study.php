<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int)($employeeClass->getEmployeeId() ?? 0);

$enrolledCourses = [];
$lastCourse = null;
$lastModules = [];
$lastEnrollment = null;
$lastProgress = 0;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT e.id AS enrollment_id, e.status AS enrollment_status, e.enrolled_at, e.last_accessed_at,
               c.id, c.title, c.description, c.category, c.status, c.thumbnail_path, c.start_date, c.enrollment_deadline,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
               (SELECT COUNT(*) FROM ld_module m WHERE m.course_id = c.id AND m.status = 'active') AS module_count,
               (SELECT COUNT(*) FROM ld_lesson l JOIN ld_module m2 ON m2.id = l.module_id WHERE m2.course_id = c.id AND l.status = 'active') AS lesson_count,
               (SELECT COUNT(*) FROM ld_enrollment e2 WHERE e2.course_id = c.id) AS enrollment_count
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE e.learner_id = :learner_id
        ORDER BY e.last_accessed_at DESC, e.enrolled_at DESC
    ");
    $stmt->execute([':learner_id' => $learnerId]);
    $enrolledCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($enrolledCourses as &$course) {
        $enrollStmt = $pdo->prepare("SELECT id FROM ld_enrollment WHERE learner_id = :lid AND course_id = :cid");
        $enrollStmt->execute([':lid' => $learnerId, ':cid' => $course['id']]);
        $enrollment = $enrollStmt->fetch(PDO::FETCH_ASSOC);
        $enrollmentId = $enrollment ? (int)$enrollment['id'] : 0;

        $totalItems = (int)$course['module_count'];
        $completedItems = 0;
        if ($enrollmentId > 0) {
            $progStmt = $pdo->prepare("SELECT COUNT(DISTINCT item_type, reference_id) FROM ld_progress WHERE enrollment_id = :eid AND status = 'completed'");
            $progStmt->execute([':eid' => $enrollmentId]);
            $completedItems = (int)$progStmt->fetchColumn();
        }
        $course['progress_pct'] = $totalItems > 0 ? min(100, round(($completedItems / $totalItems) * 100)) : 0;
        $course['completed_count'] = $completedItems;

        $quizScoreStmt = $pdo->prepare("
            SELECT qs.score, qs.passed, qs.reference_id AS quiz_id
            FROM ld_quiz_session qs
            JOIN ld_quiz q ON q.id = qs.reference_id
            JOIN ld_module m ON m.id = q.module_id
            WHERE qs.learner_id = :lid AND qs.item_type = 'quiz' AND qs.status = 'submitted' AND m.course_id = :cid
            ORDER BY qs.score DESC
        ");
        $quizScoreStmt->execute([':lid' => $learnerId, ':cid' => $course['id']]);
        $allQuizSessions = $quizScoreStmt->fetchAll(PDO::FETCH_ASSOC);

        $bestPerQuiz = [];
        foreach ($allQuizSessions as $qs) {
            $qid = (int)$qs['quiz_id'];
            if (!isset($bestPerQuiz[$qid]) || (float)$qs['score'] > (float)$bestPerQuiz[$qid]['score']) {
                $bestPerQuiz[$qid] = $qs;
            }
        }
        $passedQuizzes = array_filter($bestPerQuiz, function($q) { return (bool)$q['passed']; });
        $avgScore = !empty($bestPerQuiz) ? round(array_sum(array_column($bestPerQuiz, 'score')) / count($bestPerQuiz)) : 0;
        $course['avg_quiz_score'] = $avgScore;
        $course['quizzes_passed'] = count($passedQuizzes);
        $course['quizzes_total'] = count($bestPerQuiz);

        $certStmt = $pdo->prepare("SELECT id, verification_code, issued_at, valid_until FROM ld_certificate WHERE learner_id = :lid AND course_id = :cid AND status = 'active' LIMIT 1");
        $certStmt->execute([':lid' => $learnerId, ':cid' => $course['id']]);
        $course['certificate'] = $certStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    unset($course);

    // Last accessed course sidebar
    if (!empty($enrolledCourses) && $enrolledCourses[0]['enrollment_status'] !== 'completed') {
        $lc = $enrolledCourses[0];
        $lastCourse = $lc;

        $lcEnrollStmt = $pdo->prepare("SELECT id FROM ld_enrollment WHERE learner_id = :lid AND course_id = :cid LIMIT 1");
        $lcEnrollStmt->execute([':lid' => $learnerId, ':cid' => $lc['id']]);
        $lastEnrollment = $lcEnrollStmt->fetch(PDO::FETCH_ASSOC);

        if ($lastEnrollment) {
            require_once dirname(__DIR__, 2) . '/classes/progress.php';
            $progressObj = new Progress($pdo);
            $lastProgress = $progressObj->getPercentComplete((int) $lastEnrollment['id'], $lc['id']);

            $lmStmt = $pdo->prepare("SELECT m.id, m.title, m.order_index FROM ld_module m WHERE m.course_id = :cid AND m.status = 'active' ORDER BY m.order_index ASC, m.id ASC");
            $lmStmt->execute([':cid' => $lc['id']]);
            $lastModules = $lmStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($lastModules as &$lm) {
                $llStmt = $pdo->prepare("SELECT l.id, l.title, l.content_type, (SELECT status FROM ld_progress WHERE enrollment_id = :eid AND item_type = 'lesson' AND reference_id = l.id LIMIT 1) AS progress_status FROM ld_lesson l WHERE l.module_id = :mid AND l.status = 'active' ORDER BY l.order_index ASC, l.id ASC");
                $llStmt->execute([':mid' => $lm['id'], ':eid' => $lastEnrollment['id']]);
                $lm['lessons'] = $llStmt->fetchAll(PDO::FETCH_ASSOC);

                $lqStmt = $pdo->prepare("SELECT q.id, q.title, (SELECT CASE WHEN qa.passed = 1 THEN 'passed' ELSE 'failed' END FROM ld_quiz_attempt qa WHERE qa.learner_id = :lid AND qa.quiz_id = q.id ORDER BY qa.id DESC LIMIT 1) AS attempt_status FROM ld_quiz q WHERE q.module_id = :mid AND q.status = 'active' ORDER BY q.id ASC");
                $lqStmt->execute([':mid' => $lm['id'], ':lid' => $learnerId]);
                $lm['quizzes'] = $lqStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($lm);
        }
    }

    // Study streak
    $streakStmt = $pdo->prepare("SELECT DATE(p.completed_at) AS day FROM ld_progress p JOIN ld_enrollment e ON e.id = p.enrollment_id WHERE e.learner_id = :lid AND p.status = 'completed' AND p.completed_at IS NOT NULL GROUP BY DATE(p.completed_at) ORDER BY day DESC");
    $streakStmt->execute([':lid' => $learnerId]);
    $activeDays = $streakStmt->fetchAll(PDO::FETCH_COLUMN);
    $studyStreak = 0;
    if (!empty($activeDays)) {
        $today = new DateTime('today');
        $checkDate = new DateTime($activeDays[0]);
        if ($today->diff($checkDate)->days <= 1) {
            $studyStreak = 1;
            for ($i = 1; $i < count($activeDays); $i++) {
                $prev = new DateTime($activeDays[$i - 1]);
                $curr = new DateTime($activeDays[$i]);
                if ($prev->diff($curr)->days === 1) { $studyStreak++; } else { break; }
            }
        }
    }

    $timeStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_progress p JOIN ld_enrollment e ON e.id = p.enrollment_id WHERE e.learner_id = :lid AND p.status = 'completed'");
    $timeStmt->execute([':lid' => $learnerId]);
    $completedItems = (int)$timeStmt->fetchColumn();
    $totalMinutes = $completedItems * 15;
    $studyHours = floor($totalMinutes / 60);
    $studyMins = $totalMinutes % 60;
    $studyTimeDisplay = $studyHours > 0 ? $studyHours . 'h ' . $studyMins . 'm' : $studyMins . 'm';

} catch (Throwable $e) {
    $enrolledCourses = [];
    $studyStreak = 0;
    $studyTimeDisplay = '0m';
}

$catGradients = [
    'IT' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'Programming' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    'Data Science' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    'Business' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    'Marketing' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    'Design' => 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)',
    'General' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
    'default' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
];
function getStudyGradient($category, $gradients) {
    foreach ($gradients as $key => $grad) { if (strcasecmp($key, $category) === 0) return $grad; }
    return $gradients['default'];
}
?>

<style>
/* Layout */
.study-page-main{min-height:calc(100vh - 60px)}

/* Sidebar header */
.sp-sidebar-header{padding:1.25rem 2.5rem 1.25rem 1.25rem;border-bottom:1px solid rgba(32,0,130,0.06)}
.sp-sidebar-title{font-size:0.72rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--muted,#888);font-weight:700;margin-bottom:0.75rem}
.sp-course-card{display:block;text-decoration:none;padding:0.75rem;background:rgba(32,0,130,0.04);border-radius:10px;border:1px solid rgba(32,0,130,0.08);transition:all 0.2s}
.sp-course-card:hover{border-color:rgba(32,0,130,0.2);background:rgba(32,0,130,0.06)}
.sp-course-name{font-size:0.9rem;font-weight:700;color:var(--text);margin-bottom:0.3rem;line-height:1.3}
.sp-course-meta{font-size:0.72rem;color:var(--muted,#888)}
.sp-progress{margin-top:0.5rem}
.sp-progress-label{font-size:0.68rem;color:var(--muted,#888);font-weight:500;margin-bottom:0.25rem}
.sp-progress-track{height:4px;background:rgba(32,0,130,0.08);border-radius:99px;overflow:hidden}
.sp-progress-fill{height:100%;background:linear-gradient(90deg,var(--primary),rgba(81,70,183,0.8));border-radius:99px}

/* Sidebar nav */
.sp-nav{flex:1;overflow-y:auto;padding:0.5rem 0}
.sp-mod{border-bottom:1px solid rgba(32,0,130,0.04)}
.sp-mod-head{display:flex;align-items:center;gap:0.6rem;padding:0.65rem 1.25rem;cursor:pointer;font-size:0.8rem;font-weight:600;color:var(--text);transition:background 0.15s;user-select:none}
.sp-mod-head:hover{background:rgba(32,0,130,0.03)}
.sp-mod-num{width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;flex-shrink:0;background:rgba(32,0,130,0.06);color:var(--primary)}
.sp-mod-title{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sp-chevron{font-size:0.55rem;color:var(--muted,#aaa);transition:transform 0.2s}
.sp-chevron.open{transform:rotate(90deg)}
.sp-items{display:none;padding:0 0 0.25rem}
.sp-items.expanded{display:block}
.sp-item{display:flex;align-items:center;gap:0.4rem;padding:0.4rem 1.25rem 0.4rem 2.5rem;font-size:0.75rem;color:var(--muted,#666);text-decoration:none;transition:all 0.15s;border-right:3px solid transparent}
.sp-item:hover{background:rgba(32,0,130,0.03);color:var(--text);border-left:3px solid var(--primary);border-right:none}
.sp-item.done{color:var(--muted,#888)}
.sp-item-icon{width:18px;text-align:center;font-size:0.65rem;flex-shrink:0}
.sp-item-icon.ok{color:#10b981}
.sp-empty{text-align:center;padding:2rem 1rem;color:var(--muted,#999);font-size:0.85rem}
.sp-empty i{font-size:1.5rem;margin-bottom:0.5rem;display:block;opacity:0.4}

/* Toggle button */
.sp-toggle-btn{position:fixed;top:var(--header-height,60px);left:var(--sidebar-width,252px);display:flex;align-items:center;justify-content:center;width:28px;height:28px;padding:0;z-index:50;background:var(--surface,#fff);border:1px solid rgba(32,0,130,0.1);border-radius:0 8px 8px 0;font-size:0.65rem;color:var(--primary);cursor:pointer;transition:left 0.3s ease,background 0.2s,box-shadow 0.2s;box-shadow:2px 2px 8px rgba(32,0,130,0.08)}
.sp-toggle-btn:hover{background:rgba(32,0,130,0.08);box-shadow:2px 2px 12px rgba(32,0,130,0.15)}
.sp-toggle-btn i{font-size:0.65rem;transition:transform 0.25s}

/* Overlay sidebar (fixed drawer, slides from right) */
.sp-overlay{position:fixed;top:var(--header-height,60px);left:0;right:0;bottom:0;background:rgba(0,0,0,0.35);z-index:998;opacity:0;pointer-events:none;transition:opacity 0.3s ease,left 0.3s ease}
.sp-close-btn{position:absolute;top:1rem;right:0.75rem;width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:transparent;border:none;color:var(--muted,#999);cursor:pointer;font-size:0.85rem;transition:all 0.15s}
.sp-close-btn:hover{background:rgba(32,0,130,0.06);color:var(--text)}
.sp-overlay.visible{opacity:1;pointer-events:auto}
.study-page-sidebar{position:fixed;top:var(--header-height,60px);bottom:0;left:var(--sidebar-width,252px);width:320px;z-index:999;background:var(--surface,#fff);border-right:1px solid rgba(32,0,130,0.1);box-shadow:4px 0 24px rgba(0,0,0,0.12);display:flex;flex-direction:column;overflow:hidden;border-radius:0;border:none;padding:0;transform:translateX(-100%);visibility:hidden;pointer-events:none;transition:transform 0.3s ease,visibility 0.3s ease,left 0.3s ease}
.study-page-sidebar.open{transform:translateX(0);visibility:visible;pointer-events:auto}


/* Existing study page styles */
.study-page{padding:0}
.study-header{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap}
.study-header h1{font-size:1.4rem;font-weight:800;color:var(--text);margin:0}
.study-header p{font-size:0.88rem;color:var(--muted,#888);margin:0.25rem 0 0}
.study-search{position:relative;flex:0 0 280px}
.study-search input{width:100%;padding:0.65rem 1rem 0.65rem 2.5rem;border:1.5px solid rgba(32,0,130,0.12);border-radius:10px;font-size:0.88rem;background:var(--surface,#fff);color:var(--text);transition:border-color 0.2s}
.study-search input:focus{outline:none;border-color:var(--primary)}
.study-search i{position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);color:var(--muted,#aaa);font-size:0.85rem}
.study-header-stats{display:flex;gap:0.75rem}
.study-stat-box{display:flex;align-items:center;gap:0.6rem;padding:0.6rem 1rem;background:var(--surface,#fff);border:1px solid rgba(32,0,130,0.08);border-radius:12px;box-shadow:0 2px 8px rgba(32,0,130,0.06)}
.study-stat-value{font-size:1.1rem;font-weight:800;color:var(--text,#333);line-height:1.2}
.study-stat-label{font-size:0.68rem;color:var(--muted,#999);font-weight:600;text-transform:uppercase;letter-spacing:0.05em}
.study-header-actions{display:flex;gap:0.75rem;align-items:center}
.study-sort{display:flex;align-items:center;gap:0.4rem}
.study-tabs{display:flex;gap:6px;background:#e8e4f0;border-radius:12px;padding:4px;margin-bottom:1.5rem;overflow-x:auto}
.study-tabs::-webkit-scrollbar{display:none}
.study-tab{flex:1 1 0;min-width:0;padding:0.55rem 0.75rem;border:none;border-radius:8px;background:rgba(255,255,255,0.7);color:var(--text);font-size:0.8rem;font-weight:600;cursor:pointer;text-align:center;transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:0.35rem;white-space:nowrap}
.study-tab:hover{background:rgba(255,255,255,0.95)}
.study-tab.active{background:var(--primary);color:#fff;box-shadow:0 2px 8px rgba(32,0,130,0.3)}
.study-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:18px;padding:0 5px;border-radius:999px;background:rgba(32,0,130,0.1);font-size:0.68rem;font-weight:700}
.study-tab.active .study-tab-count{background:rgba(255,255,255,0.25);color:#fff}
.study-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem}
.study-card{background:var(--surface,#fff);border-radius:14px;overflow:hidden;border:1px solid rgba(32,0,130,0.08);transition:all 0.25s ease;cursor:pointer}
.study-card:hover{transform:translateY(-4px);box-shadow:0 12px 35px rgba(32,0,130,0.12)}
.study-card-thumb{height:140px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}
.study-card-thumb-icon{font-size:2.5rem;color:rgba(255,255,255,0.9)}
.study-card-badge{position:absolute;top:0.75rem;left:0.75rem;padding:0.3rem 0.7rem;border-radius:6px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em}
.study-card-status{position:absolute;top:0.75rem;right:0.75rem;padding:0.3rem 0.7rem;border-radius:6px;font-size:0.7rem;font-weight:700}
.study-card-body{padding:1rem 1.15rem}
.study-card-title{font-size:0.95rem;font-weight:700;color:var(--text);margin:0 0 0.3rem;line-height:1.3}
.study-card-instructor{font-size:0.78rem;color:var(--muted,#888);margin:0 0 0.5rem;display:flex;align-items:center;gap:0.35rem}
.study-card-desc{font-size:0.82rem;color:var(--muted,#666);line-height:1.5;margin:0 0 0.75rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.study-card-stats{display:flex;gap:0.75rem;margin-bottom:0.75rem}
.study-card-stat{font-size:0.72rem;color:var(--muted,#888);display:flex;align-items:center;gap:0.25rem}
.study-card-stat i{font-size:0.7rem;color:var(--primary)}
.study-card-progress{margin-top:0.5rem}
.study-card-progress-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:0.3rem}
.study-card-progress-label{font-size:0.72rem;color:var(--muted,#888)}
.study-card-progress-pct{font-size:0.72rem;font-weight:700;color:var(--primary)}
.study-card-progress-bar{height:5px;background:rgba(32,0,130,0.08);border-radius:999px;overflow:hidden}
.study-card-progress-fill{height:100%;border-radius:999px;transition:width 0.5s ease}
.study-card-footer{display:flex;align-items:center;justify-content:space-between;padding:0.65rem 1.15rem;border-top:1px solid rgba(32,0,130,0.06)}
.study-card-time{font-size:0.72rem;color:var(--muted,#aaa)}
.study-card-action{font-size:0.78rem;font-weight:700;color:var(--primary);display:flex;align-items:center;gap:0.3rem}
.study-card-quiz-score{margin-top:0.5rem;padding:0.5rem 0.7rem;background:rgba(32,0,130,0.04);border-radius:8px;display:flex;align-items:center;justify-content:space-between}
.study-card-quiz-label{font-size:0.72rem;color:var(--muted,#888)}
.study-card-quiz-value{font-size:0.78rem;font-weight:700}
.study-card-quiz-value.passed{color:#10b981}
.study-card-quiz-value.failed{color:#ef4444}
.study-card-cert{display:flex;align-items:center;justify-content:space-between;margin-top:0.5rem;padding:0.5rem 0.7rem;background:linear-gradient(135deg,rgba(251,191,36,0.12) 0%,rgba(245,158,11,0.12) 100%);border:1px solid rgba(245,158,11,0.25);border-radius:8px}
.study-card-cert-label{display:inline-flex;align-items:center;gap:0.3rem;font-size:0.72rem;font-weight:700;color:#b45309}
.study-card-cert-label i{color:#f59e0b}
.study-card-cert-btn{padding:0.3rem 0.65rem;border:none;border-radius:6px;background:#f59e0b;color:#fff;font-size:0.68rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:0.25rem;transition:background 0.2s}
.study-card-cert-btn:hover{background:#d97706}
.study-empty{text-align:center;padding:3rem 1rem;color:var(--muted,#999)}
.study-empty i{font-size:2.5rem;color:var(--muted,#ddd);margin-bottom:1rem;display:block}
.study-empty h3{color:var(--text);margin:0 0 0.5rem}
.study-empty p{font-size:0.9rem;margin:0}
@media(max-width:768px){.study-header{flex-direction:column;align-items:stretch}.study-header-actions{flex-direction:column}.study-header-stats{flex-wrap:wrap}.study-search{flex:0 0 auto}.study-grid{grid-template-columns:1fr}}
</style>

<!-- Overlay -->
<div class="sp-overlay" id="sidebarOverlay"></div>

<!-- Sidebar drawer -->
<aside class="study-page-sidebar" id="studyPageSidebar">
        <button class="sp-close-btn" onclick="studyCloseSidebar()" title="Close panel"><i class="fas fa-times"></i></button>
        <?php if ($lastCourse): ?>
            <div class="sp-sidebar-header">
                <div class="sp-sidebar-title">Continue Learning</div>
                <a href="?page=learner/study-subpage/course&course_id=<?= $lastCourse['id'] ?>" class="sp-course-card">
                    <div class="sp-course-name"><?= htmlspecialchars($lastCourse['title']) ?></div>
                    <div class="sp-course-meta"><?= htmlspecialchars($lastCourse['instructor_name'] ?? '') ?></div>
                    <div class="sp-progress">
                        <div class="sp-progress-label"><?= $lastProgress ?>% complete</div>
                        <div class="sp-progress-track">
                            <div class="sp-progress-fill" style="width:<?= $lastProgress ?>%;"></div>
                        </div>
                    </div>
                </a>
            </div>
            <nav class="sp-nav">
                <?php if (empty($lastModules)): ?>
                    <div class="sp-empty"><i class="fas fa-folder-open"></i>No modules yet</div>
                <?php else: ?>
                    <?php foreach ($lastModules as $mIdx => $lm): ?>
                        <div class="sp-mod">
                            <div class="sp-mod-head" onclick="studyToggleMod(this)">
                                <span class="sp-mod-num"><?= $mIdx + 1 ?></span>
                                <span class="sp-mod-title"><?= htmlspecialchars($lm['title']) ?></span>
                                <i class="fas fa-chevron-right sp-chevron"></i>
                            </div>
                            <div class="sp-items">
                                <?php foreach ($lm['lessons'] as $sl): $done = $sl['progress_status'] === 'completed'; ?>
                                    <a href="?page=learner/study-subpage/lesson&lesson_id=<?= $sl['id'] ?>&course_id=<?= $lastCourse['id'] ?>" class="sp-item <?= $done ? 'done' : '' ?>">
                                        <span class="sp-item-icon <?= $done ? 'ok' : '' ?>"><?= $done ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-file-alt"></i>' ?></span>
                                        <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($sl['title']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                                <?php foreach ($lm['quizzes'] as $sq): $qDone = $sq['attempt_status'] === 'passed'; ?>
                                    <a href="?page=learner/study-subpage/quiz&quiz_id=<?= $sq['id'] ?>&course_id=<?= $lastCourse['id'] ?>" class="sp-item <?= $qDone ? 'done' : '' ?>">
                                        <span class="sp-item-icon <?= $qDone ? 'ok' : '' ?>"><?= $qDone ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-question-circle"></i>' ?></span>
                                        <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($sq['title']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
        <?php else: ?>
            <div class="sp-sidebar-header"><div class="sp-empty" style="padding:2rem 1rem;"><i class="fas fa-book-open"></i>No courses yet</div></div>
        <?php endif; ?>
    </aside>

    <button class="sp-toggle-btn" id="spToggleBtn" onclick="studyToggleSidebar()" title="Toggle Course Panel">
        <i class="fas fa-chevron-right"></i>
    </button>
        <div class="module-content study-page">
            <div class="study-header">
                <div>
                    <h1>My Learning</h1>
                    <p><?= count($enrolledCourses) ?> enrolled course<?= count($enrolledCourses) !== 1 ? 's' : '' ?> &mdash; continue where you left off</p>
                </div>
                <div class="study-header-stats">
                    <div class="study-stat-box"><i class="fas fa-fire" style="color:#f59e0b; font-size:1.1rem;"></i><div><div class="study-stat-value"><?= $studyStreak ?></div><div class="study-stat-label">Day Streak</div></div></div>
                    <div class="study-stat-box"><i class="fas fa-clock" style="color:var(--primary); font-size:1.1rem;"></i><div><div class="study-stat-value"><?= $studyTimeDisplay ?></div><div class="study-stat-label">Study Time</div></div></div>
                </div>
                <div class="study-header-actions">
                    <div class="study-search"><i class="fas fa-search"></i><input type="search" id="study-search-input" placeholder="Search your courses..." /></div>
                    <div class="study-sort">
                        <label for="study-sort-select" style="font-size:0.78rem; color:var(--muted,#888); white-space:nowrap;">Sort by</label>
                        <select id="study-sort-select" style="padding:0.55rem 0.75rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.82rem; background:var(--surface,#fff); color:var(--text,#333); cursor:pointer;">
                            <option value="last_accessed">Last Accessed</option>
                            <option value="alpha">Alphabetical</option>
                            <option value="progress">Progress</option>
                        </select>
                    </div>
                </div>
            </div>

            <?php
            $counts = ['all' => count($enrolledCourses), 'in_progress' => 0, 'completed' => 0, 'invited' => 0];
            foreach ($enrolledCourses as $c) {
                $s = $c['enrollment_status'];
                if ($s === 'completed') $counts['completed']++;
                elseif ($s === 'in_progress' || $s === 'enrolled') $counts['in_progress']++;
                elseif ($s === 'invited') $counts['invited']++;
            }
            ?>
            <div class="study-tabs" id="study-tabs">
                <button type="button" class="study-tab active" data-tab="all">All <span class="study-tab-count"><?= $counts['all'] ?></span></button>
                <button type="button" class="study-tab" data-tab="in_progress">In Progress <span class="study-tab-count"><?= $counts['in_progress'] ?></span></button>
                <button type="button" class="study-tab" data-tab="completed">Completed <span class="study-tab-count"><?= $counts['completed'] ?></span></button>
                <button type="button" class="study-tab" data-tab="invited">Invitations <span class="study-tab-count"><?= $counts['invited'] ?></span></button>
            </div>

            <div class="study-grid" id="study-grid">
            <?php foreach ($enrolledCourses as $course):
                $id = (int)$course['id'];
                $title = htmlspecialchars($course['title'] ?? 'Untitled');
                $desc = htmlspecialchars(mb_substr(strip_tags($course['description'] ?? ''), 0, 100));
                $category = htmlspecialchars($course['category'] ?? 'General');
                $status = $course['enrollment_status'] ?? 'enrolled';
                $instructor = htmlspecialchars($course['instructor_name'] ?? '');
                $modules = (int)($course['module_count'] ?? 0);
                $lessons = (int)($course['lesson_count'] ?? 0);
                $pct = (int)($course['progress_pct'] ?? 0);
                $gradient = getStudyGradient($course['category'] ?? '', $catGradients);
                $statusLabel = ucfirst(str_replace('_', ' ', $status));
                $statusBg = ($status === 'completed') ? 'rgba(16,185,129,0.9)' : (($status === 'in_progress') ? 'rgba(59,130,246,0.9)' : 'rgba(245,158,11,0.9)');
                $barColor = ($pct >= 100) ? '#10b981' : (($pct > 0) ? '#3b82f6' : '#e5e7eb');
                $lastAccessed = $course['last_accessed_at'] ?? null;
                $timeAgo = '';
                if ($lastAccessed) { $diff = time() - strtotime($lastAccessed); if ($diff < 60) $timeAgo = 'Just now'; elseif ($diff < 3600) $timeAgo = floor($diff / 60) . 'm ago'; elseif ($diff < 86400) $timeAgo = floor($diff / 3600) . 'h ago'; else $timeAgo = floor($diff / 86400) . 'd ago'; }
            ?>
                <article class="study-card" data-course-id="<?= $id ?>" data-status="<?= htmlspecialchars($status) ?>" data-search="<?= strtolower($course['title'] . ' ' . $category . ' ' . $instructor) ?>" data-sort-alpha="<?= strtolower($course['title'] ?? '') ?>" data-sort-progress="<?= (int)($course['progress_pct'] ?? 0) ?>" data-sort-time="<?= strtotime($course['last_accessed_at'] ?? '1970-01-01') ?>">
                    <div class="study-card-thumb" style="background:<?= $gradient ?>"><i class="fas fa-graduation-cap study-card-thumb-icon"></i><span class="study-card-badge" style="background:rgba(0,0,0,0.25);color:#fff;"><?= $category ?></span><span class="study-card-status" style="background:<?= $statusBg ?>;color:#fff;"><?= $statusLabel ?></span></div>
                    <div class="study-card-body">
                        <h3 class="study-card-title"><?= $title ?></h3>
                        <?php if ($instructor): ?><div class="study-card-instructor"><i class="fas fa-user-tie"></i> <?= $instructor ?></div><?php endif; ?>
                        <p class="study-card-desc"><?= $desc ?></p>
                        <div class="study-card-stats"><span class="study-card-stat"><i class="fas fa-folder"></i> <?= $modules ?> modules</span><span class="study-card-stat"><i class="fas fa-file-alt"></i> <?= $lessons ?> lessons</span></div>
                        <div class="study-card-progress"><div class="study-card-progress-header"><span class="study-card-progress-label">Progress</span><span class="study-card-progress-pct"><?= $pct ?>%</span></div><div class="study-card-progress-bar"><div class="study-card-progress-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div></div></div>
                        <?php $quizScore = (int)($course['avg_quiz_score'] ?? 0); $quizzesPassed = (int)($course['quizzes_passed'] ?? 0); $quizzesTotal = (int)($course['quizzes_total'] ?? 0); $hasCert = !empty($course['certificate']); if ($quizzesTotal > 0 || $quizScore > 0): ?>
                        <div class="study-card-quiz-score"><span class="study-card-quiz-label"><i class="fas fa-check-circle" style="margin-right:0.3rem;"></i>Quiz Score</span><span class="study-card-quiz-value <?= $quizScore >= 70 ? 'passed' : 'failed' ?>"><?= $quizScore > 0 ? $quizScore . '% (' . $quizzesPassed . '/' . $quizzesTotal . ')' : 'No attempts' ?></span></div>
                        <?php endif; ?>
                        <?php if ($hasCert): $certCode = htmlspecialchars($course['certificate']['verification_code'] ?? ''); ?>
                        <div class="study-card-cert"><span class="study-card-cert-label"><i class="fas fa-award"></i> Certificate earned</span><a href="?page=public/verify-certificate&code=<?= $certCode ?>" target="_blank" class="study-card-cert-btn" onclick="event.stopPropagation();"><i class="fas fa-external-link-alt"></i> View</a></div>
                        <?php endif; ?>
                    </div>
                    <div class="study-card-footer"><span class="study-card-time"><?= $timeAgo ? '<i class="fas fa-clock" style="margin-right:0.25rem;"></i>' . $timeAgo : '' ?></span><span class="study-card-action"><?= $status === 'completed' ? 'View Results <i class="fas fa-arrow-right"></i>' : 'Continue <i class="fas fa-arrow-right"></i>' ?></span></div>
                </article>
            <?php endforeach; ?>
            </div>

            <?php if (empty($enrolledCourses)): ?>
            <div class="study-empty"><i class="fas fa-book-open"></i><h3>No courses yet</h3><p>Visit the <a href="?page=learner/catalog" style="color:var(--primary);font-weight:600;">catalog</a> to find courses to take.</p></div>
            <?php endif; ?>
        </div>



<script>
(function() {
    'use strict';
    var currentTab = 'all', searchQuery = '';
    var grid = document.getElementById('study-grid');
    var allCards = Array.from(grid.querySelectorAll('.study-card'));
    function filterCards() { allCards.forEach(function(card) { var tabMatch = currentTab === 'all' || card.dataset.status === currentTab; var searchMatch = !searchQuery || card.dataset.search.indexOf(searchQuery) !== -1; card.style.display = tabMatch && searchMatch ? '' : 'none'; }); }
    document.querySelectorAll('.study-tab').forEach(function(btn) { btn.addEventListener('click', function() { document.querySelectorAll('.study-tab').forEach(function(b) { b.classList.remove('active'); }); btn.classList.add('active'); currentTab = btn.dataset.tab; filterCards(); }); });
    var st; document.getElementById('study-search-input').addEventListener('input', function() { clearTimeout(st); st = setTimeout(function() { searchQuery = document.getElementById('study-search-input').value.trim().toLowerCase(); filterCards(); }, 200); });
    document.getElementById('study-sort-select').addEventListener('change', function() { var v = this.value; allCards.sort(function(a, b) { if (v === 'alpha') return a.dataset.sortAlpha.localeCompare(b.dataset.sortAlpha); if (v === 'progress') return parseInt(b.dataset.sortProgress) - parseInt(a.dataset.sortProgress); return parseInt(b.dataset.sortTime) - parseInt(a.dataset.sortTime); }); allCards.forEach(function(c) { grid.appendChild(c); }); });
    allCards.forEach(function(card) { card.addEventListener('click', function() { window.location.href = card.dataset.status === 'completed' ? '?page=learner/result' : '?page=learner/study-subpage/course&course_id=' + card.dataset.courseId; }); });
})();

function studyToggleMod(h) { var items = h.nextElementSibling; var ch = h.querySelector('.sp-chevron'); if (!items) return; if (items.classList.contains('expanded')) { items.classList.remove('expanded'); if (ch) ch.classList.remove('open'); } else { items.classList.add('expanded'); if (ch) ch.classList.add('open'); } }

function studyToggleSidebar() { var s = document.getElementById('studyPageSidebar'); var o = document.getElementById('sidebarOverlay'); if (!s) return; if (s.classList.contains('open')) { studyCloseSidebar(); } else { s.classList.add('open'); if (o) o.classList.add('visible'); document.body.style.overflow = 'hidden'; } }
function studyCloseSidebar() { var s = document.getElementById('studyPageSidebar'); var o = document.getElementById('sidebarOverlay'); if (s) s.classList.remove('open'); if (o) o.classList.remove('visible'); document.body.style.overflow = ''; }
document.addEventListener('click', function(e) { if (e.target.id === 'sidebarOverlay') studyCloseSidebar(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') studyCloseSidebar(); });


/* ── Sync course panel position with nav sidebar state ──────── */
(function() {
    var navSidebar = document.querySelector('.sidebar');
    if (!navSidebar) return;
    function getNavWidth() {
        return navSidebar.classList.contains('hidden') ? 0 : 252;
    }
    function syncPositions() {
        var w = getNavWidth();
        var panel = document.getElementById('studyPageSidebar');
        var btn = document.getElementById('spToggleBtn');
        var overlay = document.getElementById('sidebarOverlay');
        if (panel) panel.style.left = w + 'px';
        if (btn) btn.style.left = w + 'px';
        if (overlay) overlay.style.left = w + 'px';
    }
    var observer = new MutationObserver(syncPositions);
    observer.observe(navSidebar, { attributes: true, attributeFilter: ['class'] });
    var hamburger = document.querySelector('.hamburger');
    if (hamburger) hamburger.addEventListener('click', function() { setTimeout(syncPositions, 50); });
    syncPositions();
})();
</script>
