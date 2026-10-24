<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/progress.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$employeeId = (int) ($employeeClass->getEmployeeId() ?? 0);

$stats = ['my_courses' => 0, 'completed' => 0, 'in_progress' => 0, 'certificates' => 0, 'avg_score' => 0];
$continueLearning = [];
$upcomingDeadlines = [];
$upcomingVC = [];
$reminders = [];
$bookmarks = [];
$favorites = [];
$recentNotifications = [];
$recentGrades = [];
$streak = 0;
$activeDays = [];
$weeklyActivity = [];

try {
    $pdo = (new Database())->getConnection();

    // Core stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid AND status IN ('enrolled','in_progress','completed')");
    $stmt->execute([':lid' => $employeeId]);
    $stats['my_courses'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid AND status = 'completed'");
    $stmt->execute([':lid' => $employeeId]);
    $stats['completed'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid AND status IN ('enrolled','in_progress')");
    $stmt->execute([':lid' => $employeeId]);
    $stats['in_progress'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_certificate WHERE learner_id = :lid AND status = 'active'");
    $stmt->execute([':lid' => $employeeId]);
    $stats['certificates'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT ROUND(COALESCE(AVG(final_score),0),1) FROM ld_grade WHERE learner_id = :lid");
    $stmt->execute([':lid' => $employeeId]);
    $stats['avg_score'] = (float) $stmt->fetchColumn();

    // Continue Learning with progress
    $stmt = $pdo->prepare("
        SELECT e.course_id, e.last_accessed_at, e.enrolled_at, c.title, c.category, c.start_date, c.enrollment_deadline,
               c.instructor_id,
               CONCAT(emp.first_name,' ',emp.last_name) AS instructor_name,
               (SELECT COUNT(*) FROM ld_lesson l JOIN ld_module m ON m.id = l.module_id WHERE m.course_id = c.id AND l.status = 'active') AS total_lessons,
               (SELECT COUNT(DISTINCT p.reference_id) FROM ld_progress p JOIN ld_enrollment e2 ON e2.id = p.enrollment_id WHERE e2.course_id = c.id AND p.item_type = 'lesson' AND p.status = 'completed') AS completed_lessons
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE e.learner_id = :lid AND e.status IN ('enrolled','in_progress')
        ORDER BY e.last_accessed_at DESC LIMIT 5
    ");
    $stmt->execute([':lid' => $employeeId]);
    $continueLearning = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming deadlines (enhanced)
    $stmt = $pdo->prepare("
        SELECT c.id AS course_id, c.title, c.enrollment_deadline, c.start_date,
               e.status AS enrollment_status
        FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id
        WHERE e.learner_id = :lid AND e.status IN ('enrolled','in_progress')
          AND c.enrollment_deadline IS NOT NULL AND c.enrollment_deadline >= CURDATE()
        ORDER BY c.enrollment_deadline ASC LIMIT 5
    ");
    $stmt->execute([':lid' => $employeeId]);
    $upcomingDeadlines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming video conferences
    $stmt = $pdo->prepare("
        SELECT vc.id, vc.title, vc.platform, vc.scheduled_at, vc.duration_minutes, vc.meeting_link,
               CONCAT(emp.first_name,' ',emp.last_name) AS host_name
        FROM ld_video_conference vc
        LEFT JOIN em_employees emp ON emp.employee_id = vc.instructor_id
        WHERE vc.status = 'scheduled' AND vc.scheduled_at >= NOW()
        ORDER BY vc.scheduled_at ASC LIMIT 3
    ");
    $stmt->execute();
    $upcomingVC = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $streakData = (new Progress($pdo))->getStreakData($employeeId);
    $streak = $streakData['streak'];
    $activeDays = $streakData['active_days'];

    // Weekly activity heatmap (last 4 weeks)
    $stmt = $pdo->prepare("SELECT DATE(last_accessed_at) AS day, COUNT(DISTINCT course_id) AS courses_accessed FROM ld_enrollment WHERE learner_id = :lid AND last_accessed_at >= DATE_SUB(CURDATE(), INTERVAL 28 DAY) GROUP BY day");
    $stmt->execute([':lid' => $employeeId]);
    $weeklyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Personalized reminders
    // 1. Inactive courses (not accessed in 7+ days)
    $stmt = $pdo->prepare("
        SELECT e.course_id, c.title, DATEDIFF(NOW(), e.last_accessed_at) AS days_inactive
        FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id
        WHERE e.learner_id = :lid AND e.status IN ('enrolled','in_progress')
          AND DATEDIFF(NOW(), e.last_accessed_at) > 7
        ORDER BY e.last_accessed_at ASC LIMIT 3
    ");
    $stmt->execute([':lid' => $employeeId]);
    $inactiveCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($inactiveCourses as $ic) {
        $reminders[] = ['icon' => 'fa-clock', 'tone' => 'amber', 'title' => 'You haven\'t accessed "' . htmlspecialchars($ic['title']) . '" in ' . $ic['days_inactive'] . ' days', 'link' => '?page=learner/study-subpage/course&course_id=' . $ic['course_id']];
    }

    // 2. Courses starting soon
    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.start_date
        FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id
        WHERE e.learner_id = :lid AND e.status = 'enrolled' AND c.start_date IS NOT NULL AND c.start_date > CURDATE() AND c.start_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY c.start_date ASC LIMIT 2
    ");
    $stmt->execute([':lid' => $employeeId]);
    $startingSoon = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($startingSoon as $ss) {
        $daysUntil = floor((strtotime($ss['start_date']) - time()) / 86400);
        $reminders[] = ['icon' => 'fa-calendar-check', 'tone' => 'indigo', 'title' => '"' . htmlspecialchars($ss['title']) . '" starts in ' . ($daysUntil === 0 ? 'less than a day' : $daysUntil . ' day' . ($daysUntil !== 1 ? 's' : '')), 'link' => '?page=learner/study-subpage/course&course_id=' . $ss['id']];
    }

    // 3. Low quiz scores to retake
    $stmt = $pdo->prepare("
        SELECT qs.reference_id AS quiz_id, qs.score, q.title AS quiz_title, m.course_id
        FROM ld_quiz_session qs
        JOIN ld_quiz q ON q.id = qs.reference_id
        JOIN ld_module m ON m.id = q.module_id
        WHERE qs.learner_id = :lid AND qs.item_type = 'quiz' AND qs.status = 'submitted' AND qs.passed = 0
        ORDER BY qs.submitted_at DESC LIMIT 2
    ");
    $stmt->execute([':lid' => $employeeId]);
    $failedQuizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($failedQuizzes as $fq) {
        $reminders[] = ['icon' => 'fa-redo', 'tone' => 'amber', 'title' => 'You scored ' . round($fq['score']) . '% on "' . htmlspecialchars($fq['quiz_title']) . '" — retake available', 'link' => '?page=learner/study-subpage/quiz&quiz_id=' . $fq['quiz_id'] . '&course_id=' . $fq['course_id']];
    }

    // Recent grades
    $stmt = $pdo->prepare("SELECT g.final_score, g.status, c.title FROM ld_grade g JOIN ld_course c ON c.id = g.course_id WHERE g.learner_id = :lid ORDER BY g.issued_at DESC LIMIT 3");
    $stmt->execute([':lid' => $employeeId]);
    $recentGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Bookmarks - batch fetch titles
    $stmt = $pdo->prepare("SELECT b.item_type, b.reference_id FROM ld_bookmark b WHERE b.learner_id = :lid ORDER BY b.created_at DESC LIMIT 5");
    $stmt->execute([':lid' => $employeeId]);
    $bookmarkItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($bookmarkItems) {
        $courseIds = [];
        $lessonIds = [];
        $moduleIds = [];
        foreach ($bookmarkItems as $b) {
            if ($b['item_type'] === 'course') $courseIds[] = $b['reference_id'];
            elseif ($b['item_type'] === 'lesson') $lessonIds[] = $b['reference_id'];
            elseif ($b['item_type'] === 'module') $moduleIds[] = $b['reference_id'];
        }

        $titles = [];
        if ($courseIds) {
            $ph = implode(',', array_fill(0, count($courseIds), '?'));
            $t = $pdo->prepare("SELECT id, title FROM ld_course WHERE id IN ($ph)");
            $t->execute($courseIds);
            foreach ($t->fetchAll(PDO::FETCH_KEY_PAIR) as $id => $title) $titles["course:$id"] = $title;
        }
        if ($lessonIds) {
            $ph = implode(',', array_fill(0, count($lessonIds), '?'));
            $t = $pdo->prepare("SELECT id, title FROM ld_lesson WHERE id IN ($ph)");
            $t->execute($lessonIds);
            foreach ($t->fetchAll(PDO::FETCH_KEY_PAIR) as $id => $title) $titles["lesson:$id"] = $title;
        }
        if ($moduleIds) {
            $ph = implode(',', array_fill(0, count($moduleIds), '?'));
            $t = $pdo->prepare("SELECT id, title FROM ld_module WHERE id IN ($ph)");
            $t->execute($moduleIds);
            foreach ($t->fetchAll(PDO::FETCH_KEY_PAIR) as $id => $title) $titles["module:$id"] = $title;
        }

        foreach ($bookmarkItems as $b) {
            $key = $b['item_type'] . ':' . $b['reference_id'];
            $title = $titles[$key] ?? 'Unknown';
            $link = '';
            if ($b['item_type'] === 'course') $link = '?page=learner/study-subpage/course&course_id=' . $b['reference_id'];
            elseif ($b['item_type'] === 'lesson') $link = '?page=learner/study-subpage/lesson&lesson_id=' . $b['reference_id'];
            elseif ($b['item_type'] === 'module') $link = '?page=learner/study-subpage/module&module_id=' . $b['reference_id'];
            $bookmarks[] = ['title' => $title, 'link' => $link];
        }
    }

    // Favorites - batch fetch titles
    $stmt = $pdo->prepare("SELECT f.item_type, f.reference_id FROM ld_favorite f WHERE f.learner_id = :lid ORDER BY f.created_at DESC LIMIT 5");
    $stmt->execute([':lid' => $employeeId]);
    $favoriteItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($favoriteItems) {
        $courseIds = [];
        foreach ($favoriteItems as $f) {
            if ($f['item_type'] === 'course') $courseIds[] = $f['reference_id'];
        }
        $titles = [];
        if ($courseIds) {
            $ph = implode(',', array_fill(0, count($courseIds), '?'));
            $t = $pdo->prepare("SELECT id, title FROM ld_course WHERE id IN ($ph)");
            $t->execute($courseIds);
            foreach ($t->fetchAll(PDO::FETCH_KEY_PAIR) as $id => $title) $titles["course:$id"] = $title;
        }
        foreach ($favoriteItems as $f) {
            $key = $f['item_type'] . ':' . $f['reference_id'];
            $title = $titles[$key] ?? 'Unknown';
            $link = '';
            if ($f['item_type'] === 'course') $link = '?page=learner/study-subpage/course&course_id=' . $f['reference_id'];
            $favorites[] = ['title' => $title, 'link' => $link];
        }
    }

    // Notifications
    $stmt = $pdo->prepare("SELECT type, title, message, is_read, created_at FROM ld_notification_inbox WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([':uid' => $employeeId]);
    $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {}

function homeTimeAgo($dt) {
    if (!$dt) return '';
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    return floor($d / 86400) . 'd ago';
}

function homeDaysLeft($date) {
    $d = floor((strtotime($date) - time()) / 86400);
    if ($d < 0) return 'Overdue';
    if ($d === 0) return 'Today';
    if ($d === 1) return 'Tomorrow';
    return $d . ' days left';
}

$heatmap = [];
foreach ($weeklyActivity as $wa) { $heatmap[$wa['day']] = (int) $wa['courses_accessed']; }
?>
<div class="module-content home-module">
    <div class="home-dashboard">

        <!-- Hero -->
        <section class="home-hero">
            <div class="home-hero-main">
                <h1><?= htmlspecialchars($employeeClass->getGreeting()) ?></h1>
                <p>Here is your learning snapshot for today.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-fire"></i> <?= (int) $streak ?> day<?= $streak !== 1 ? 's' : '' ?> streak</span>
                    <span class="home-hero-chip"><i class="fas fa-book-open"></i> <?= (int) $stats['in_progress'] ?> in progress</span>
                    <span class="home-hero-chip"><i class="fas fa-certificate"></i> <?= (int) $stats['certificates'] ?> certificates</span>
                </div>
                <?php if ($streak > 0 || !empty($activeDays)): ?>
                <div class="home-heatmap" title="Your activity over the last 28 days">
                    <?php
                    $startDate = new DateTime('-27 days');
                    for ($i = 0; $i < 28; $i++) {
                        $d = clone $startDate;
                        $d->modify('+' . $i . ' days');
                        $ds = $d->format('Y-m-d');
                        $count = $heatmap[$ds] ?? 0;
                        $level = $count >= 3 ? 3 : ($count >= 2 ? 2 : ($count >= 1 ? 1 : 0));
                    ?>
                    <span class="home-heat<?= $level > 0 ? ' is-' . $level : '' ?>" title="<?= $ds ?>: <?= $count ?> course<?= $count !== 1 ? 's' : '' ?>"></span>
                    <?php } ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Key stats -->
        <div class="home-stats">
            <a class="home-stat" href="?page=learner/study">
                <span class="home-stat-icon tone-violet"><i class="fas fa-book-open"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">My Courses</span>
                    <span class="home-stat-value"><?= (int) $stats['my_courses'] ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['in_progress'] ?> active</span>
                </span>
            </a>
            <a class="home-stat" href="?page=learner/result">
                <span class="home-stat-icon tone-emerald"><i class="fas fa-check-circle"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Completed</span>
                    <span class="home-stat-value"><?= (int) $stats['completed'] ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['certificates'] ?> certificates</span>
                </span>
            </a>
            <div class="home-stat">
                <span class="home-stat-icon tone-amber"><i class="fas fa-star"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Avg Score</span>
                    <span class="home-stat-value"><?= $stats['avg_score'] ?>%</span>
                    <span class="home-stat-sub">overall</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-rose"><i class="fas fa-fire"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Streak</span>
                    <span class="home-stat-value"><?= (int) $streak ?></span>
                    <span class="home-stat-sub">day<?= $streak !== 1 ? 's' : '' ?></span>
                </span>
            </div>
        </div>

        <!-- Main column + sidebar -->
        <div class="home-columns">
        <div class="home-col">
            <!-- Personalized Reminders -->
            <?php if (!empty($reminders)): ?>
            <div class="home-card">
                <div class="home-card-head">
                    <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-lightbulb"></i></span> Reminders</h3>
                    <span class="home-card-count"><?= count($reminders) ?> item<?= count($reminders) !== 1 ? 's' : '' ?></span>
                </div>
                <div>
                    <?php foreach ($reminders as $rm): ?>
                    <a class="home-reminder" href="<?= $rm['link'] ?>">
                        <span class="home-reminder-icon tone-<?= $rm['tone'] ?>"><i class="fas <?= $rm['icon'] ?>"></i></span>
                        <span class="home-row-body"><span class="home-row-title is-wrap"><?= $rm['title'] ?></span></span>
                        <i class="fas fa-chevron-right home-row-chev"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Continue Learning with Progress -->
            <?php if (!empty($continueLearning)): ?>
            <div class="home-card">
                <div class="home-card-head">
                    <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-play-circle"></i></span> Continue Learning</h3>
                    <a class="home-card-link" href="?page=learner/study">View all <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="home-list">
                    <?php foreach ($continueLearning as $cl):
                        $total = (int) ($cl['total_lessons'] ?? 1);
                        $done = (int) ($cl['completed_lessons'] ?? 0);
                        $pct = $total > 0 ? round(($done / $total) * 100) : 0;
                    ?>
                    <div class="home-row">
                        <span class="home-row-icon tone-violet"><i class="fas fa-graduation-cap"></i></span>
                        <span class="home-row-body">
                            <a class="home-row-title" href="?page=learner/study-subpage/course&amp;course_id=<?= (int) $cl['course_id'] ?>"><?= htmlspecialchars($cl['title']) ?></a>
                            <span class="home-row-meta"><?php if ((int) ($cl['instructor_id'] ?? 0) > 0): ?><a href="<?= htmlspecialchars(LearningProfileLink::url((int) $cl['instructor_id'], 'instructor'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cl['instructor_name'] ?? '') ?></a><?php else: ?><?= htmlspecialchars($cl['instructor_name'] ?? '') ?><?php endif; ?> &bull; <?= $done ?>/<?= $total ?> lessons</span>
                            <span class="home-progress">
                                <span class="home-progress-track"><span class="home-progress-fill" style="width:<?= $pct ?>%"></span></span>
                                <span class="home-progress-val"><?= $pct ?>%</span>
                            </span>
                        </span>
                        <span class="home-row-trail">
                            <span class="home-row-meta"><?= homeTimeAgo($cl['last_accessed_at']) ?></span>
                            <i class="fas fa-chevron-right home-row-chev"></i>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Upcoming Deadlines -->
            <?php if (!empty($upcomingDeadlines)): ?>
            <div class="home-card">
                <div class="home-card-head">
                    <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-clock"></i></span> Upcoming Deadlines</h3>
                    <a class="home-card-link" href="?page=learner/calendar">Calendar <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="home-list">
                    <?php foreach ($upcomingDeadlines as $dl):
                        $dl2 = homeDaysLeft($dl['enrollment_deadline']);
                        $urgent = ($dl2 === 'Today' || $dl2 === 'Tomorrow' || $dl2 === 'Overdue');
                    ?>
                    <a class="home-row" href="?page=learner/study-subpage/course&course_id=<?= $dl['course_id'] ?>">
                        <span class="home-row-icon tone-<?= $urgent ? 'rose' : 'slate' ?>"><i class="fas <?= $urgent ? 'fa-exclamation-triangle' : 'fa-calendar-day' ?>"></i></span>
                        <span class="home-row-body"><span class="home-row-title"><?= htmlspecialchars($dl['title']) ?></span></span>
                        <span class="home-badge <?= $urgent ? 'is-danger' : 'is-muted' ?>"><?= $dl2 ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Upcoming Video Conferences -->
            <?php if (!empty($upcomingVC)): ?>
            <div class="home-card">
                <div class="home-card-head">
                    <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-video"></i></span> Online Training</h3>
                    <a class="home-card-link" href="?page=learner/calendar">View all <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="home-list">
                    <?php foreach ($upcomingVC as $vc):
                        $isSoon = (strtotime($vc['scheduled_at']) - time()) < 86400;
                    ?>
                    <div class="home-row">
                        <span class="home-row-icon tone-indigo"><i class="fas fa-video"></i></span>
                        <span class="home-row-body">
                            <span class="home-row-title"><?= htmlspecialchars($vc['title']) ?></span>
                            <span class="home-row-meta"><?= htmlspecialchars($vc['platform'] ?? '') ?> &bull; <?= date('M j, g:i A', strtotime($vc['scheduled_at'])) ?></span>
                        </span>
                        <?php if ($isSoon && !empty($vc['meeting_link'])): ?>
                            <a class="home-cta" href="<?= htmlspecialchars($vc['meeting_link']) ?>" target="_blank" rel="noopener">Join</a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Grades -->
            <?php if (!empty($recentGrades)): ?>
            <div class="home-card">
                <div class="home-card-head">
                    <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-chart-line"></i></span> Recent Results</h3>
                    <a class="home-card-link" href="?page=learner/result">View all <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="home-list">
                    <?php foreach ($recentGrades as $rg): $passed = ($rg['status'] === 'passed'); ?>
                    <div class="home-row">
                        <span class="home-score<?= $passed ? '' : ' is-fail' ?>"><?= round($rg['final_score']) ?>%</span>
                        <span class="home-row-body"><span class="home-row-title"><?= htmlspecialchars($rg['title']) ?></span></span>
                        <span class="home-badge <?= $passed ? 'is-success' : 'is-warn' ?>"><?= htmlspecialchars($rg['status']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Course Recommendations -->
            <div id="home-recs-section" class="home-card" style="display:none;">
                <div class="home-card-head">
                    <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-magic"></i></span> Suggested For You</h3>
                    <a class="home-card-link" href="?page=learner/catalog">Browse catalog <i class="fas fa-arrow-right"></i></a>
                </div>
                <div id="home-recs-grid" class="home-list"></div>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="home-col">
            <!-- Quick Access -->
            <div class="home-card">
                <div class="home-card-head">
                    <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-bolt"></i></span> Quick Access</h3>
                </div>
                <div class="home-quick">
                    <?php
                    $links = [
                        ['icon' => 'fa-graduation-cap', 'label' => 'Browse Catalog', 'url' => '?page=learner/catalog', 'tone' => 'indigo'],
                        ['icon' => 'fa-route', 'label' => 'My Learning Path', 'url' => '?page=learner/my-learning-path', 'tone' => 'indigo'],
                        ['icon' => 'fa-book-open', 'label' => 'My Study', 'url' => '?page=learner/study', 'tone' => 'indigo'],
                        ['icon' => 'fa-check-circle', 'label' => 'Grade Book', 'url' => '?page=learner/result', 'tone' => 'emerald'],
                        ['icon' => 'fa-calendar', 'label' => 'Calendar', 'url' => '?page=learner/calendar', 'tone' => 'amber'],
                        ['icon' => 'fa-sticky-note', 'label' => 'Notes', 'url' => '?page=learner/notes', 'tone' => 'amber'],
                        ['icon' => 'fa-user-circle', 'label' => 'My Profile', 'url' => '?page=learner/profile', 'tone' => 'violet'],
                    ];
                    foreach ($links as $lk): ?>
                    <a class="home-quick-link" href="<?= $lk['url'] ?>">
                        <span class="home-quick-icon tone-<?= $lk['tone'] ?>"><i class="fas <?= $lk['icon'] ?>"></i></span>
                        <span class="home-quick-label"><?= $lk['label'] ?></span>
                        <i class="fas fa-chevron-right home-quick-chev"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bookmarks -->
            <div class="home-card">
                <div class="home-card-head">
                    <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-bookmark"></i></span> Bookmarks</h3>
                    <span class="home-card-count"><?= count($bookmarks) ?></span>
                </div>
                <?php if (empty($bookmarks)): ?>
                    <div class="home-empty"><i class="fas fa-bookmark"></i><p>No bookmarks yet</p></div>
                <?php else: ?>
                    <div class="home-list">
                        <?php foreach ($bookmarks as $bm): ?>
                        <a class="home-row" href="<?= htmlspecialchars($bm['link']) ?>">
                            <span class="home-row-icon tone-violet"><i class="fas fa-bookmark"></i></span>
                            <span class="home-row-body"><span class="home-row-title"><?= htmlspecialchars($bm['title']) ?></span></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Favorites -->
            <div class="home-card">
                <div class="home-card-head">
                    <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-heart"></i></span> Favorites</h3>
                    <span class="home-card-count"><?= count($favorites) ?></span>
                </div>
                <?php if (empty($favorites)): ?>
                    <div class="home-empty"><i class="fas fa-heart"></i><p>No favorites yet</p></div>
                <?php else: ?>
                    <div class="home-list">
                        <?php foreach ($favorites as $fav): ?>
                        <a class="home-row" href="<?= htmlspecialchars($fav['link']) ?>">
                            <span class="home-row-icon tone-rose"><i class="fas fa-heart"></i></span>
                            <span class="home-row-body"><span class="home-row-title"><?= htmlspecialchars($fav['title']) ?></span></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Notifications -->
            <div class="home-card">
                <div class="home-card-head">
                    <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-bell"></i></span> Notifications</h3>
                    <a class="home-card-link" href="?page=learner/notification">View all <i class="fas fa-arrow-right"></i></a>
                </div>
                <?php if (empty($recentNotifications)): ?>
                    <div class="home-empty"><i class="fas fa-bell-slash"></i><p>No notifications</p></div>
                <?php else: ?>
                    <div class="home-list">
                        <?php foreach ($recentNotifications as $notif): ?>
                        <div class="home-row">
                            <span class="home-dot<?= $notif['is_read'] ? ' is-hidden' : '' ?>"></span>
                            <span class="home-row-body">
                                <span class="home-row-title"><?= htmlspecialchars($notif['title']) ?></span>
                                <span class="home-row-meta"><?= htmlspecialchars(mb_substr($notif['message'], 0, 60)) ?></span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    fetch('pages/learner/ajax/get-recommendations.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.recommendations || data.recommendations.length === 0) return;
            var section = document.getElementById('home-recs-section');
            var grid = document.getElementById('home-recs-grid');
            var html = '';
            data.recommendations.slice(0, 4).forEach(function(rec) {
                var reason = (rec.reasons && rec.reasons.length) ? rec.reasons[0] : '';
                html += '<a class="home-row" href="' + rec.link + '">'
                    + '<span class="home-row-icon tone-violet"><i class="fas fa-graduation-cap"></i></span>'
                    + '<span class="home-row-body">'
                    + '<span class="home-row-title">' + rec.title + '</span>'
                    + '<span class="home-row-meta">' + reason + '</span>'
                    + '</span>'
                    + '<i class="fas fa-chevron-right home-row-chev"></i>'
                    + '</a>';
            });
            grid.innerHTML = html;
            section.style.display = 'block';
        })
        .catch(function() {});
})();
</script>
