<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/progress.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int)($employeeClass->getEmployeeId() ?? 0);

// Pagination parameters
$page = max(1, (int)($_GET['p'] ?? 1));
$pageSize = min(50, max(1, (int)($_GET['ps'] ?? 12)));
$currentTab = (string) ($_GET['tab'] ?? 'all');
if (!in_array($currentTab, ['all', 'in_progress', 'completed', 'invited'], true)) {
    $currentTab = 'all';
}
$tabCondition = [
    'all' => '',
    'in_progress' => " AND e.status IN ('enrolled', 'in_progress')",
    'completed' => " AND e.status = 'completed'",
    'invited' => " AND e.status = 'invited'",
][$currentTab];

// Free-text search over course title / description / category / instructor.
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$searchCondition = '';
if ($searchQuery !== '') {
    $searchCondition = " AND (c.title LIKE :search OR c.description LIKE :search OR c.category LIKE :search"
        . " OR CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.last_name, '')) LIKE :search)";
}

// Result ordering.
$sort = (string) ($_GET['sort'] ?? 'last_accessed');
if (!in_array($sort, ['last_accessed', 'alpha', 'progress'], true)) {
    $sort = 'last_accessed';
}
$studyOrderBy = [
    'last_accessed' => 'e.last_accessed_at DESC, e.enrolled_at DESC',
    'alpha' => 'c.title ASC, c.id ASC',
    'progress' => "(CASE WHEN (SELECT COUNT(*) FROM ld_module m WHERE m.course_id = c.id AND m.status = 'active') = 0"
        . " THEN 0 ELSE (SELECT COUNT(DISTINCT p.item_type, p.reference_id) FROM ld_progress p WHERE p.enrollment_id = e.id AND p.status = 'completed')"
        . " / (SELECT COUNT(*) FROM ld_module m WHERE m.course_id = c.id AND m.status = 'active') END) DESC, e.last_accessed_at DESC",
][$sort];

$enrolledCourses = [];
$studyPaths = [];
$pathInvitations = [];
$totalCourses = 0;
$tabCounts = ['all' => 0, 'in_progress' => 0, 'completed' => 0, 'invited' => 0];
$lastCourse = null;
$lastModules = [];
$lastEnrollment = null;
$lastProgress = 0;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get total count for pagination
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE e.learner_id = :learner_id{$tabCondition}{$searchCondition}
    ");
    $countParams = [':learner_id' => $learnerId];
    if ($searchQuery !== '') {
        $countParams[':search'] = '%' . $searchQuery . '%';
    }
    $countStmt->execute($countParams);
    $totalCourses = (int)$countStmt->fetchColumn();

    $tabCountStmt = $pdo->prepare('SELECT status, COUNT(*) AS total FROM ld_enrollment WHERE learner_id = :learner_id GROUP BY status');
    $tabCountStmt->execute([':learner_id' => $learnerId]);
    foreach ($tabCountStmt->fetchAll(PDO::FETCH_ASSOC) as $statusCount) {
        $status = (string) $statusCount['status'];
        $total = (int) $statusCount['total'];
        $tabCounts['all'] += $total;
        if ($status === 'enrolled' || $status === 'in_progress') $tabCounts['in_progress'] += $total;
        elseif ($status === 'completed') $tabCounts['completed'] += $total;
        elseif ($status === 'invited') $tabCounts['invited'] += $total;
    }

        $pathStmt = $pdo->prepare("        SELECT lp.id, lp.instructor_id, lp.title, lp.description,
                                                                            CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name
                                                             FROM ld_learning_path lp
                                                             LEFT JOIN em_employees emp ON emp.employee_id = lp.instructor_id
                                                             LEFT JOIN ld_learning_path_invitation pi
                                                                 ON pi.learning_path_id = lp.id AND pi.employee_id = :invited_learner
                                                             WHERE lp.status = 'active'
                                                                 AND (lp.assigned_to = :assigned_learner OR pi.status = 'enrolled')
                                                             ORDER BY lp.updated_at DESC, lp.id DESC");
        $pathStmt->execute([':invited_learner' => $learnerId, ':assigned_learner' => $learnerId]);
        $studyPaths = $pathStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($studyPaths as &$studyPath) {
                $pathProgressStmt = $pdo->prepare("SELECT COUNT(*) AS course_count,
                                                                                                    SUM(CASE WHEN EXISTS (
                                                                                                            SELECT 1 FROM ld_enrollment e
                                                                                                            WHERE e.course_id = lpi.reference_id
                                                                                                                AND e.learner_id = :learner_id
                                                                                                                AND e.status = 'completed'
                                                                                                    ) THEN 1 ELSE 0 END) AS completed_count
                                                                                     FROM ld_learning_path_item lpi
                                                                                     WHERE lpi.learning_path_id = :path_id
                                                                                         AND lpi.item_type = 'course' AND lpi.status = 'active'");
                $pathProgressStmt->execute([':learner_id' => $learnerId, ':path_id' => (int) $studyPath['id']]);
                $pathProgress = $pathProgressStmt->fetch(PDO::FETCH_ASSOC);
                $courseCount = (int) ($pathProgress['course_count'] ?? 0);
                $completedCount = (int) ($pathProgress['completed_count'] ?? 0);
                $studyPath['course_count'] = $courseCount;
                $studyPath['completed_count'] = $completedCount;
                $studyPath['progress_pct'] = $courseCount > 0 ? (int) round($completedCount * 100 / $courseCount) : 0;
                $studyPath['study_status'] = $courseCount > 0 && $completedCount >= $courseCount ? 'completed' : 'in_progress';
                $pathItemStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_learning_path_item WHERE learning_path_id = :path_id AND status = 'active'");
                $pathItemStmt->execute([':path_id' => (int) $studyPath['id']]);
                $studyPath['step_count'] = (int) $pathItemStmt->fetchColumn();
                $tabCounts['all']++;
                $tabCounts[$studyPath['study_status']]++;
        }
        unset($studyPath);

        $pathInvitationStmt = $pdo->prepare("SELECT pi.id AS invitation_id, lp.id AS path_id, lp.instructor_id, lp.title, lp.description,
                                                                                                CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name
                                                                                 FROM ld_learning_path_invitation pi
                                                                                 JOIN ld_learning_path lp ON lp.id = pi.learning_path_id
                                                                                 LEFT JOIN em_employees emp ON emp.employee_id = lp.instructor_id
                                                                                 WHERE pi.employee_id = :learner_id AND pi.status = 'invited' AND lp.status = 'active'
                                                                                 ORDER BY pi.invited_at DESC");
        $pathInvitationStmt->execute([':learner_id' => $learnerId]);
        $pathInvitations = $pathInvitationStmt->fetchAll(PDO::FETCH_ASSOC);
        $tabCounts['invited'] += count($pathInvitations);

    $offset = ($page - 1) * $pageSize;

    $stmt = $pdo->prepare("
        SELECT e.id AS enrollment_id, e.status AS enrollment_status, e.enrolled_at, e.last_accessed_at,
               c.id, c.instructor_id, c.title, c.description, c.category, c.status, c.thumbnail_path, c.start_date, c.enrollment_deadline,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
               (SELECT COUNT(*) FROM ld_module m WHERE m.course_id = c.id AND m.status = 'active') AS module_count,
               (SELECT COUNT(*) FROM ld_lesson l JOIN ld_module m2 ON m2.id = l.module_id WHERE m2.course_id = c.id AND l.status = 'active') AS lesson_count,
               (SELECT COUNT(*) FROM ld_enrollment e2 WHERE e2.course_id = c.id) AS enrollment_count
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE e.learner_id = :learner_id{$tabCondition}{$searchCondition}
        ORDER BY {$studyOrderBy}
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':learner_id', $learnerId, PDO::PARAM_INT);
    if ($searchQuery !== '') {
        $stmt->bindValue(':search', '%' . $searchQuery . '%', PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
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

                $lqStmt = $pdo->prepare("SELECT q.id, q.title, (SELECT CASE WHEN qs.passed = 1 OR q.passing_score IS NULL THEN 'passed' ELSE 'failed' END FROM ld_quiz_session qs WHERE qs.learner_id = :lid AND qs.item_type = 'quiz' AND qs.reference_id = q.id AND qs.status IN ('submitted','expired') ORDER BY qs.submitted_at DESC, qs.id DESC LIMIT 1) AS attempt_status FROM ld_quiz q WHERE q.module_id = :mid AND q.status = 'active' ORDER BY q.id ASC");
                $lqStmt->execute([':mid' => $lm['id'], ':lid' => $learnerId]);
                $lm['quizzes'] = $lqStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($lm);
        }
    }

    $studyStreak = (new Progress($pdo))->getStreakData($learnerId)['streak'];

    $totalStudySeconds = 0;
    try {
        $timeStmt = $pdo->prepare('SELECT COALESCE(SUM(active_seconds), 0) FROM ld_study_time_log WHERE learner_id = :learner_id');
        $timeStmt->execute([':learner_id' => $learnerId]);
        $totalStudySeconds = max(0, (int) $timeStmt->fetchColumn());
    } catch (Throwable $timeError) {
        error_log('[learning] Study-time total unavailable: ' . $timeError->getMessage());
    }
    $studyHours = intdiv($totalStudySeconds, 3600);
    $studyMinutes = intdiv($totalStudySeconds % 3600, 60);
    $studyTimeDisplay = $studyHours > 0
        ? $studyHours . 'h ' . $studyMinutes . 'm'
        : ($studyMinutes > 0 ? $studyMinutes . 'm' : ($totalStudySeconds > 0 ? $totalStudySeconds . 's' : '0m'));

} catch (Throwable $e) {
    DbError::capture($e, 'learner/study');
    $enrolledCourses = [];
    $studyPaths = [];
    $pathInvitations = [];
    $studyStreak = 0;
    $studyTimeDisplay = '0m';
}

$visibleStudyPaths = array_values(array_filter($studyPaths, static function (array $path) use ($currentTab): bool {
    return $currentTab === 'all' || $path['study_status'] === $currentTab;
}));

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


<!-- Overlay -->
<div class="sp-overlay" id="sidebarOverlay"></div>

<!-- Sidebar drawer -->
<aside class="study-page-sidebar" id="studyPageSidebar">
        <button class="sp-close-btn" onclick="studyCloseSidebar()" title="Close panel"><i class="fas fa-times"></i></button>
        <?php if ($lastCourse): ?>
            <div class="sp-sidebar-header">
                <div class="sp-sidebar-title">Continue Learning</div>
                <div class="sp-course-card">
                    <div class="sp-course-name"><a href="?page=learner/study-subpage/course&course_id=<?= $lastCourse['id'] ?>"><?= htmlspecialchars($lastCourse['title']) ?></a></div>
                    <div class="sp-course-meta"><?php if ((int) ($lastCourse['instructor_id'] ?? 0) > 0): ?><a href="<?= htmlspecialchars(LearningProfileLink::url((int) $lastCourse['instructor_id'], 'instructor'), ENT_QUOTES, 'UTF-8') ?>" onclick="event.stopPropagation();"><?= htmlspecialchars($lastCourse['instructor_name'] ?? '') ?></a><?php else: ?><?= htmlspecialchars($lastCourse['instructor_name'] ?? '') ?><?php endif; ?></div>
                    <div class="sp-progress">
                        <div class="sp-progress-label"><?= $lastProgress ?>% complete</div>
                        <div class="sp-progress-track">
                            <div class="sp-progress-fill" style="width:<?= $lastProgress ?>%;"></div>
                        </div>
                    </div>
                </div>
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
                                        <span class="sp-item-label"><?= htmlspecialchars($sl['title']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                                <?php foreach ($lm['quizzes'] as $sq): $qDone = $sq['attempt_status'] === 'passed'; ?>
                                    <a href="?page=learner/study-subpage/quiz&quiz_id=<?= $sq['id'] ?>&course_id=<?= $lastCourse['id'] ?>" class="sp-item <?= $qDone ? 'done' : '' ?>">
                                        <span class="sp-item-icon <?= $qDone ? 'ok' : '' ?>"><?= $qDone ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-question-circle"></i>' ?></span>
                                        <span class="sp-item-label"><?= htmlspecialchars($sq['title']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
        <?php else: ?>
            <div class="sp-sidebar-header"><div class="sp-empty"><i class="fas fa-book-open"></i>No courses yet</div></div>
        <?php endif; ?>
    </aside>

        <div class="module-content study-page">
            <div class="home-dashboard study-dashboard">

            <!-- Hero -->
            <section class="home-hero study-hero">
                <div class="home-hero-main">
                    <h1>My Learning</h1>
                    <p><?= (int) $totalCourses ?> course<?= $totalCourses === 1 ? '' : 's' ?><?php if (!empty($studyPaths)): ?> and <?= count($studyPaths) ?> learning path<?= count($studyPaths) === 1 ? '' : 's' ?><?php endif; ?> &mdash; pick up where you left off</p>
                </div>
                <div class="home-hero-aside">
                    <div class="home-hero-chips">
                        <span class="home-hero-chip"><i class="fas fa-fire"></i> <?= (int) $studyStreak ?> day<?= $studyStreak === 1 ? '' : 's' ?> streak</span>
                        <span class="home-hero-chip"><i class="fas fa-clock"></i> <?= htmlspecialchars($studyTimeDisplay) ?> studied</span>
                        <span class="home-hero-chip"><i class="fas fa-book-open"></i> <?= (int) $tabCounts['in_progress'] ?> in progress</span>
                    </div>
                    <button type="button" class="study-hero-btn" onclick="studyToggleSidebar()" title="Open the course panel">
                        <i class="fas fa-list-ul"></i> Course Panel
                    </button>
                </div>
            </section>

            <!-- Browse bar -->
            <div class="study-bar">
                <div class="study-tabs" id="study-tabs">
                    <button type="button" class="study-tab<?= $currentTab === 'all' ? ' active' : '' ?>" data-tab="all" aria-label="All courses" title="All courses"><i class="fas fa-layer-group" aria-hidden="true"></i> All <span class="study-tab-count"><?= $tabCounts['all'] ?></span></button>
                    <button type="button" class="study-tab<?= $currentTab === 'in_progress' ? ' active' : '' ?>" data-tab="in_progress" aria-label="In progress" title="In progress"><i class="fas fa-spinner" aria-hidden="true"></i> In Progress <span class="study-tab-count"><?= $tabCounts['in_progress'] ?></span></button>
                    <button type="button" class="study-tab<?= $currentTab === 'completed' ? ' active' : '' ?>" data-tab="completed" aria-label="Completed" title="Completed"><i class="fas fa-circle-check" aria-hidden="true"></i> Completed <span class="study-tab-count"><?= $tabCounts['completed'] ?></span></button>
                    <button type="button" class="study-tab<?= $currentTab === 'invited' ? ' active' : '' ?>" data-tab="invited" aria-label="Invitations" title="Invitations"><i class="fas fa-envelope-open-text" aria-hidden="true"></i> Invitations <span class="study-tab-count"><?= $tabCounts['invited'] ?></span></button>
                </div>
                <div class="study-filters">
                    <div class="study-search"><i class="fas fa-search"></i><input type="search" id="study-search-input" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search your courses..." /></div>
                    <div class="study-field">
                        <label for="study-sort-select">Sort</label>
                        <select id="study-sort-select">
                            <option value="last_accessed"<?= $sort === 'last_accessed' ? ' selected' : '' ?>>Last accessed</option>
                            <option value="alpha"<?= $sort === 'alpha' ? ' selected' : '' ?>>Alphabetical</option>
                            <option value="progress"<?= $sort === 'progress' ? ' selected' : '' ?>>Progress</option>
                        </select>
                    </div>
                    <div class="study-field">
                        <label for="study-page-size">Show</label>
                        <select id="study-page-size">
                            <option value="12">12 / page</option>
                            <option value="24">24 / page</option>
                            <option value="36">36 / page</option>
                        </select>
                    </div>
                </div>
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
                    <div class="study-card-thumb" style="background:<?= $gradient ?>"><i class="fas fa-graduation-cap study-card-thumb-icon"></i><span class="study-card-badge"><?= $category ?></span><span class="study-card-status" style="background:<?= $statusBg ?>;"><?= $statusLabel ?></span></div>
                    <div class="study-card-body">
                        <h3 class="study-card-title"><?= $title ?></h3>
                        <?php if ($instructor): ?><div class="study-card-instructor"><i class="fas fa-user-tie"></i> <a href="<?= htmlspecialchars(LearningProfileLink::url((int) ($course['instructor_id'] ?? 0), 'instructor'), ENT_QUOTES, 'UTF-8') ?>" onclick="event.stopPropagation();"><?= $instructor ?></a></div><?php endif; ?>
                        <p class="study-card-desc"><?= $desc ?></p>
                        <div class="study-card-stats"><span class="study-card-stat"><i class="fas fa-folder"></i> <?= $modules ?> module<?= $modules === 1 ? '' : 's' ?></span><span class="study-card-stat"><i class="fas fa-file-alt"></i> <?= $lessons ?> lesson<?= $lessons === 1 ? '' : 's' ?></span></div>
                        <div class="study-card-progress"><div class="study-card-progress-header"><span class="study-card-progress-label">Progress</span><span class="study-card-progress-pct"><?= $pct ?>%</span></div><div class="study-card-progress-bar"><div class="study-card-progress-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div></div></div>
                        <?php $quizScore = (int)($course['avg_quiz_score'] ?? 0); $quizzesPassed = (int)($course['quizzes_passed'] ?? 0); $quizzesTotal = (int)($course['quizzes_total'] ?? 0); $hasCert = !empty($course['certificate']); if ($quizzesTotal > 0 || $quizScore > 0): ?>
                        <div class="study-card-quiz-score"><span class="study-card-quiz-label"><i class="fas fa-check-circle"></i>Quiz Score</span><span class="study-card-quiz-value <?= $quizScore >= 70 ? 'passed' : 'failed' ?>"><?= $quizScore > 0 ? $quizScore . '% (' . $quizzesPassed . '/' . $quizzesTotal . ')' : 'No attempts' ?></span></div>
                        <?php endif; ?>
                        <?php if ($hasCert): $certCode = htmlspecialchars($course['certificate']['verification_code'] ?? ''); ?>
                        <div class="study-card-cert"><span class="study-card-cert-label"><i class="fas fa-award"></i> Certificate earned</span><a href="?page=public/verify-certificate&back=learner/study&code=<?= $certCode ?>" target="_blank" class="study-card-cert-btn" onclick="event.stopPropagation();"><i class="fas fa-external-link-alt"></i> View</a></div>
                        <?php endif; ?>
                    </div>
                    <div class="study-card-footer"><span class="study-card-time"><?= $timeAgo ? '<i class="fas fa-clock"></i>' . $timeAgo : '' ?></span><?php if ($status === 'invited'): ?><div class="study-invitation-actions"><button type="button" class="study-invitation-action" data-kind="course" data-action="decline" data-id="<?= (int) $course['enrollment_id'] ?>">Decline</button><button type="button" class="study-invitation-action" data-kind="course" data-action="accept" data-id="<?= (int) $course['enrollment_id'] ?>">Enroll</button></div><?php else: ?><span class="study-card-action"><?= $status === 'completed' ? 'View Results <i class="fas fa-arrow-right"></i>' : 'Continue <i class="fas fa-arrow-right"></i>' ?></span><?php endif; ?></div>
                </article>
            <?php endforeach; ?>
            <?php foreach ($visibleStudyPaths as $studyPath):
                $pathId = (int) $studyPath['id'];
                $pathProgress = (int) $studyPath['progress_pct'];
            ?>
                <article class="study-card" data-search="<?= htmlspecialchars(strtolower(($studyPath['title'] ?? '') . ' ' . ($studyPath['instructor_name'] ?? '')), ENT_QUOTES) ?>">
                    <div class="study-card-thumb" style="background:linear-gradient(135deg,#a34b16,#e5a525);"><i class="fas fa-route study-card-thumb-icon"></i><span class="study-card-badge">Learning Path</span><span class="study-card-status" style="background:<?= $studyPath['study_status'] === 'completed' ? 'rgba(16,185,129,0.9)' : 'rgba(59,130,246,0.9)' ?>;"><?= $studyPath['study_status'] === 'completed' ? 'Completed' : 'In Progress' ?></span></div>
                    <div class="study-card-body"><h3 class="study-card-title"><?= htmlspecialchars($studyPath['title']) ?></h3><?php if (!empty($studyPath['instructor_name'])): ?><div class="study-card-instructor"><i class="fas fa-user-tie"></i> <a href="<?= htmlspecialchars(LearningProfileLink::url((int) ($studyPath['instructor_id'] ?? 0), 'instructor'), ENT_QUOTES, 'UTF-8') ?>" onclick="event.stopPropagation();"><?= htmlspecialchars($studyPath['instructor_name']) ?></a></div><?php endif; ?><p class="study-card-desc"><?= htmlspecialchars(mb_substr(strip_tags($studyPath['description'] ?? ''), 0, 100)) ?></p><div class="study-card-stats"><span class="study-card-stat"><i class="fas fa-list-ol"></i> <?= (int) $studyPath['step_count'] ?> step<?= (int) $studyPath['step_count'] === 1 ? '' : 's' ?></span><span class="study-card-stat"><i class="fas fa-graduation-cap"></i> <?= (int) $studyPath['course_count'] ?> course<?= (int) $studyPath['course_count'] === 1 ? '' : 's' ?></span></div><div class="study-card-progress"><div class="study-card-progress-header"><span class="study-card-progress-label">Progress</span><span class="study-card-progress-pct"><?= $pathProgress ?>%</span></div><div class="study-card-progress-bar"><div class="study-card-progress-fill" style="width:<?= $pathProgress ?>%;background:<?= $pathProgress >= 100 ? '#10b981' : '#3b82f6' ?>;"></div></div></div></div>
                    <div class="study-card-footer"><span class="study-card-time">My Learning Path</span><a class="study-card-action" href="?page=learner/my-learning-path&amp;path_id=<?= $pathId ?>">Open path <i class="fas fa-arrow-right"></i></a></div>
                </article>
            <?php endforeach; ?>
            <?php if ($currentTab === 'invited'): foreach ($pathInvitations as $pathInvitation): ?>
                <article class="study-card" data-search="<?= htmlspecialchars(strtolower(($pathInvitation['title'] ?? '') . ' ' . ($pathInvitation['instructor_name'] ?? '')), ENT_QUOTES) ?>">
                    <div class="study-card-thumb" style="background:linear-gradient(135deg,#a34b16,#e5a525);"><i class="fas fa-route study-card-thumb-icon"></i><span class="study-card-badge">Learning Path</span><span class="study-card-status" style="background:rgba(245,158,11,0.9);">Invited</span></div>
                    <div class="study-card-body"><h3 class="study-card-title"><?= htmlspecialchars($pathInvitation['title']) ?></h3><?php if (!empty($pathInvitation['instructor_name'])): ?><div class="study-card-instructor"><i class="fas fa-user-tie"></i> From <a href="<?= htmlspecialchars(LearningProfileLink::url((int) ($pathInvitation['instructor_id'] ?? 0), 'instructor'), ENT_QUOTES, 'UTF-8') ?>" onclick="event.stopPropagation();"><?= htmlspecialchars($pathInvitation['instructor_name']) ?></a></div><?php endif; ?><p class="study-card-desc"><?= htmlspecialchars(mb_substr(strip_tags($pathInvitation['description'] ?? ''), 0, 100)) ?></p></div>
                    <div class="study-card-footer"><span class="study-card-time">Path invitation</span><div class="study-invitation-actions"><button type="button" class="study-invitation-action" data-kind="path" data-action="decline" data-id="<?= (int) $pathInvitation['invitation_id'] ?>">Reject</button><button type="button" class="study-invitation-action" data-kind="path" data-action="accept" data-id="<?= (int) $pathInvitation['invitation_id'] ?>">Enroll</button></div></div>
                </article>
            <?php endforeach; endif; ?>
            </div>

            <?php
$totalPages = max(1, (int)ceil($totalCourses / $pageSize));
if ($totalPages > 1):
?>
            <div class="pagination-row" id="study-pagination">
                <button type="button" class="page-btn" data-action="prev" <?= $page <= 1 ? 'disabled' : '' ?>>Prev</button>
                <div class="pagination-pages" aria-label="Study pages">
                    <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                        <button type="button" class="page-btn<?= $pageNumber === $page ? ' active' : '' ?>" data-page="<?= $pageNumber ?>" aria-label="Page <?= $pageNumber ?>" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></button>
                    <?php endfor; ?>
                </div>
                <span class="page-indicator" id="study-page-indicator" aria-live="polite">Page <?= $page ?> of <?= $totalPages ?></span>
                <button type="button" class="page-btn" data-action="next" <?= $page >= $totalPages ? 'disabled' : '' ?>>Next</button>
            </div>
            <?php endif; ?>

            <?php if (empty($enrolledCourses) && empty($visibleStudyPaths) && ($currentTab !== 'invited' || empty($pathInvitations))): ?>
            <div class="study-empty">
                <i class="fas <?= $searchQuery !== '' ? 'fa-search' : 'fa-book-open' ?>"></i>
                <?php if ($searchQuery !== ''): ?>
                    <h3>No matching courses</h3>
                    <p>Nothing matches &ldquo;<?= htmlspecialchars($searchQuery) ?>&rdquo;. Try another search or browse the <a class="home-card-link" href="?page=learner/catalog">catalog</a>.</p>
                <?php else: ?>
                    <h3>No courses yet</h3>
                    <p>Visit the <a class="home-card-link" href="?page=learner/catalog">catalog</a> to find courses to take.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            </div>
        </div>



<script>
(function() {
    'use strict';
    var currentTab = <?= json_encode($currentTab) ?>, searchQuery = <?= json_encode(mb_strtolower($searchQuery)) ?>, currentPage = <?= $page ?>, pageSize = <?= $pageSize ?>, totalPages = <?= $totalPages ?>;
    var grid = document.getElementById('study-grid');
    var pagination = document.getElementById('study-pagination');
    var indicator = pagination ? pagination.querySelector('.page-indicator') : null;
    var pageSizeSelect = document.getElementById('study-page-size');
    var searchInput = document.getElementById('study-search-input');
    var sortSelect = document.getElementById('study-sort-select');
    var tabs = document.querySelectorAll('.study-tab');
    var isLoading = false;

    var urlParams = new URLSearchParams(window.location.search);
    var tabParam = urlParams.get('tab');
    if (['all', 'in_progress', 'completed', 'invited'].indexOf(tabParam || '') !== -1) {
        currentTab = tabParam;
    }

    function syncStudyTabs() {
        tabs.forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.tab === currentTab);
        });
    }

    function buildUrl(page) {
        var params = new URLSearchParams();
        params.set('page', 'learner/study');
        params.set('p', page);
        params.set('ps', pageSize);
        if (currentTab !== 'all') params.set('tab', currentTab);
        if (searchQuery) params.set('q', searchQuery);
        var sortVal = sortSelect ? sortSelect.value : 'last_accessed';
        if (sortVal !== 'last_accessed') params.set('sort', sortVal);
        return 'page-loader.php?' + params.toString();
    }

    function showLoading() {
        isLoading = true;
        if (grid) grid.style.opacity = '0.5';
        if (pagination) pagination.style.opacity = '0.5';
    }

    function hideLoading() {
        isLoading = false;
        if (grid) grid.style.opacity = '';
        if (pagination) pagination.style.opacity = '';
    }

    function updatePagination(page, total) {
        currentPage = page;
        totalPages = total;
        if (indicator) indicator.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        if (pagination) {
            var prevBtn = pagination.querySelector('[data-action="prev"]');
            var nextBtn = pagination.querySelector('[data-action="next"]');
            if (prevBtn) prevBtn.disabled = currentPage <= 1;
            if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
            pagination.querySelectorAll('[data-page]').forEach(function(pageButton) {
                var isCurrent = parseInt(pageButton.dataset.page, 10) === currentPage;
                pageButton.classList.toggle('active', isCurrent);
                if (isCurrent) pageButton.setAttribute('aria-current', 'page');
                else pageButton.removeAttribute('aria-current');
            });
            pagination.style.display = totalPages <= 1 ? 'none' : '';
        }
    }

    function fetchPage(page) {
        if (isLoading) return;
        showLoading();
        fetch(buildUrl(page), { credentials: 'same-origin' })
            .then(function(r) {
                if (r.status === 401) return r.json().then(function(d) { window.location.href = d.redirect; });
                if (!r.ok) throw new Error('Network error');
                var rendered = r.headers.get('X-Rendered-Page') || 'learner/study';
                return r.text().then(function(html) { return { html: html, rendered: rendered }; });
            })
            .then(function(result) {
                if (!result) return;
                var container = document.querySelector('.container');
                if (!container) { window.location.href = buildUrl(page); return; }
                while (container.firstChild) container.removeChild(container.firstChild);
                container.innerHTML = result.html;
                container.setAttribute('data-page', result.rendered);
                container.querySelectorAll('script').forEach(function(old) {
                    var s = document.createElement('script');
                    Array.from(old.attributes).forEach(function(attr) { s.setAttribute(attr.name, attr.value); });
                    s.textContent = old.textContent;
                    old.parentNode.replaceChild(s, old);
                });
                document.querySelectorAll('.menu-link, .active-menu-link').forEach(function(el) { el.className = 'menu-link'; });
                var active = document.querySelector('a[data-page="' + result.rendered + '"]');
                if (active) active.className = 'active-menu-link';
                history.pushState({ page: result.rendered }, '', '?page=' + encodeURIComponent(result.rendered) + '&p=' + page + '&ps=' + pageSize + (currentTab !== 'all' ? '&tab=' + currentTab : '') + (searchQuery ? '&q=' + encodeURIComponent(searchQuery) : '') + (sortSelect && sortSelect.value !== 'last_accessed' ? '&sort=' + sortSelect.value : ''));
                syncStudyTabs();
                window.dispatchEvent(new CustomEvent('page:loaded', { detail: { page: result.rendered } }));
                var newPagination = document.getElementById('study-pagination');
                var newIndicator = newPagination ? newPagination.querySelector('.page-indicator') : null;
                if (newIndicator) {
                    var match = newIndicator.textContent.match(/Page (\d+) of (\d+)/);
                    if (match) updatePagination(parseInt(match[1]), parseInt(match[2]));
                }
            })
            .catch(function(err) {
                console.error('Pagination failed', err);
            })
            .finally(hideLoading);
    }

    if (pageSizeSelect) {
        pageSizeSelect.value = String(pageSize);
        pageSizeSelect.addEventListener('change', function() {
            pageSize = parseInt(this.value, 10) || 12;
            currentPage = 1;
            fetchPage(1);
        });
    }

    tabs.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var selectedTab = btn.dataset.tab || 'all';
            window.location.href = '?page=learner/study&tab=' + encodeURIComponent(selectedTab);
        });
    });

    syncStudyTabs();

    var st;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(st);
            st = setTimeout(function() {
                searchQuery = searchInput.value.trim().toLowerCase();
                currentPage = 1;
                fetchPage(1);
            }, 200);
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            currentPage = 1;
            fetchPage(1);
        });
    }

    if (pagination) {
        pagination.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-action], [data-page]');
            if (!btn || btn.disabled || isLoading) return;
            var nextPage = btn.dataset.page
                ? parseInt(btn.dataset.page, 10)
                : currentPage + (btn.dataset.action === 'next' ? 1 : -1);
            if (nextPage < 1 || nextPage > totalPages) return;
            fetchPage(nextPage);
        });
    }

    grid.addEventListener('click', function(e) {
        var invitationButton = e.target.closest('.study-invitation-action');
        if (invitationButton) {
            e.preventDefault();
            e.stopPropagation();
            invitationButton.disabled = true;
            var isPathInvitation = invitationButton.dataset.kind === 'path';
            var endpoint = isPathInvitation
                ? 'pages/learner/ajax/respond-learning-path-invitation.php'
                : invitationButton.dataset.action === 'accept'
                    ? 'pages/learner/catalog-subpage/ajax/enrollment/accept-invitation.php'
                    : 'pages/learner/catalog-subpage/ajax/enrollment/decline-invitation.php';
            var request = isPathInvitation
                ? fetch(endpoint, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': window.CSRF_TOKEN || '' },
                    body: JSON.stringify({ invitation_id: Number(invitationButton.dataset.id), action: invitationButton.dataset.action })
                })
                : fetch(endpoint, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ enrollment_id: invitationButton.dataset.id })
                });
            request.then(function(response) {
                return response.json().then(function(data) {
                    if (!response.ok || !data.success) throw new Error(data.message || 'Unable to update invitation.');
                });
            }).then(function() {
                window.location.reload();
            }).catch(function(error) {
                window.alert(error.message || 'Unable to update invitation.');
                invitationButton.disabled = false;
            });
            return;
        }
        var card = e.target.closest('.study-card');
        if (card && card.dataset.courseId) {
            window.location.href = card.dataset.status === 'completed' ? '?page=learner/result' : '?page=learner/study-subpage/course&course_id=' + card.dataset.courseId;
        }
    });
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
        var overlay = document.getElementById('sidebarOverlay');
        if (panel) panel.style.left = w + 'px';
        if (overlay) overlay.style.left = w + 'px';
    }
    var observer = new MutationObserver(syncPositions);
    observer.observe(navSidebar, { attributes: true, attributeFilter: ['class'] });
    var hamburger = document.querySelector('.hamburger');
    if (hamburger) hamburger.addEventListener('click', function() { setTimeout(syncPositions, 50); });
    syncPositions();
})();
</script>
