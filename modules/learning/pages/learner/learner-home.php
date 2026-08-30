<?php
include_once __DIR__ . '/../../classes/Employee.php';
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

    // Study streak
    $stmt = $pdo->prepare("SELECT DISTINCT DATE(last_accessed_at) AS active_day FROM ld_enrollment WHERE learner_id = :lid AND last_accessed_at IS NOT NULL ORDER BY active_day DESC LIMIT 30");
    $stmt->execute([':lid' => $employeeId]);
    $activeDays = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $today = (new DateTime())->format('Y-m-d');
    $yesterday = (new DateTime('-1 day'))->format('Y-m-d');
    if (!empty($activeDays) && in_array($today, $activeDays)) {
        $streak = 1;
        $check = new DateTime();
        for ($i = 1; $i < count($activeDays); $i++) {
            $check->modify('-1 day');
            if (in_array($check->format('Y-m-d'), $activeDays)) {
                $streak++;
            } else { break; }
        }
    } elseif (!empty($activeDays) && $activeDays[0] === $yesterday) {
        $streak = 1;
        $check = new DateTime('-1 day');
        for ($i = 1; $i < count($activeDays); $i++) {
            $check->modify('-1 day');
            if (in_array($check->format('Y-m-d'), $activeDays)) {
                $streak++;
            } else { break; }
        }
    }

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
        $reminders[] = ['icon' => 'fa-clock', 'bg' => 'rgba(245,158,11,0.1)', 'color' => '#f59e0b', 'title' => 'You haven\'t accessed "' . htmlspecialchars($ic['title']) . '" in ' . $ic['days_inactive'] . ' days', 'link' => '?page=learner/study-subpage/course&course_id=' . $ic['course_id']];
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
        $reminders[] = ['icon' => 'fa-calendar-check', 'bg' => 'rgba(99,102,241,0.1)', 'color' => '#6366f1', 'title' => '"' . htmlspecialchars($ss['title']) . '" starts in ' . ($daysUntil === 0 ? 'less than a day' : $daysUntil . ' day' . ($daysUntil !== 1 ? 's' : '')), 'link' => '?page=learner/study-subpage/course&course_id=' . $ss['id']];
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
        $reminders[] = ['icon' => 'fa-redo', 'bg' => 'rgba(245,158,11,0.1)', 'color' => '#f59e0b', 'title' => 'You scored ' . round($fq['score']) . '% on "' . htmlspecialchars($fq['quiz_title']) . '" — retake available', 'link' => '?page=learner/study-subpage/quiz&quiz_id=' . $fq['quiz_id'] . '&course_id=' . $fq['course_id']];
    }

    // Recent grades
    $stmt = $pdo->prepare("SELECT g.final_score, g.status, c.title FROM ld_grade g JOIN ld_course c ON c.id = g.course_id WHERE g.learner_id = :lid ORDER BY g.issued_at DESC LIMIT 3");
    $stmt->execute([':lid' => $employeeId]);
    $recentGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Bookmarks
    $stmt = $pdo->prepare("SELECT b.item_type, b.reference_id FROM ld_bookmark b WHERE b.learner_id = :lid ORDER BY b.created_at DESC LIMIT 5");
    $stmt->execute([':lid' => $employeeId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $title = $link = '';
        if ($b['item_type'] === 'course') { $t = $pdo->prepare("SELECT title FROM ld_course WHERE id = :id"); $t->execute([':id' => $b['reference_id']]); $title = $t->fetchColumn() ?: 'Unknown'; $link = '?page=learner/catalog-subpage/course&course_id=' . $b['reference_id']; }
        elseif ($b['item_type'] === 'lesson') { $t = $pdo->prepare("SELECT title FROM ld_lesson WHERE id = :id"); $t->execute([':id' => $b['reference_id']]); $title = $t->fetchColumn() ?: 'Unknown'; $link = '?page=learner/catalog-subpage/lesson&lesson_id=' . $b['reference_id']; }
        elseif ($b['item_type'] === 'module') { $t = $pdo->prepare("SELECT title FROM ld_module WHERE id = :id"); $t->execute([':id' => $b['reference_id']]); $title = $t->fetchColumn() ?: 'Unknown'; $link = '?page=learner/catalog-subpage/module&module_id=' . $b['reference_id']; }
        $bookmarks[] = ['title' => $title, 'link' => $link];
    }

    // Favorites
    $stmt = $pdo->prepare("SELECT f.item_type, f.reference_id FROM ld_favorite f WHERE f.learner_id = :lid ORDER BY f.created_at DESC LIMIT 5");
    $stmt->execute([':lid' => $employeeId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $title = $link = '';
        if ($f['item_type'] === 'course') { $t = $pdo->prepare("SELECT title FROM ld_course WHERE id = :id"); $t->execute([':id' => $f['reference_id']]); $title = $t->fetchColumn() ?: 'Unknown'; $link = '?page=learner/catalog-subpage/course&course_id=' . $f['reference_id']; }
        $favorites[] = ['title' => $title, 'link' => $link];
    }

    // Notifications
    $stmt = $pdo->prepare("SELECT type, title, message, is_read, created_at FROM ld_notification WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5");
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
<div class="module-header">
    <h1 class="module-header-title"><?= htmlspecialchars($employeeClass->getGreeting()) ?></h1>
</div>

<div class="module-content">
    <!-- Study Streak Banner -->
    <?php if ($streak > 0 || !empty($activeDays)): ?>
    <div class="mode-card" style="background:linear-gradient(135deg, rgba(255,152,0,0.1), rgba(255,193,7,0.05)); border-left:4px solid var(--accent); margin-bottom:1.5rem; display:flex; align-items:center; gap:1.5rem; padding:1.25rem 1.5rem;">
        <div style="font-size:2.5rem; color:var(--accent); flex-shrink:0;"><i class="fas fa-fire"></i></div>
        <div style="flex:1;">
            <h2 style="margin:0; color:var(--accent); font-size:1.5rem;"><?= $streak ?> Day<?= $streak !== 1 ? 's' : '' ?> Streak!</h2>
            <p style="margin:0.25rem 0 0 0; color:#666; font-size:0.9rem;"><?= $streak > 0 ? "Great momentum! Keep learning daily to build your streak." : "Start learning today to build your streak!" ?></p>
        </div>
        <!-- Mini heatmap -->
        <div style="display:flex; gap:3px; flex-shrink:0;">
            <?php
            $startDate = new DateTime('-27 days');
            for ($i = 0; $i < 28; $i++) {
                $d = clone $startDate;
                $d->modify('+' . $i . ' days');
                $ds = $d->format('Y-m-d');
                $count = $heatmap[$ds] ?? 0;
                $bg = $count >= 3 ? 'var(--primary)' : ($count >= 2 ? 'var(--primary)' : ($count >= 1 ? 'var(--primary)' : '#eee'));
            ?>
                <div style="width:10px; height:10px; border-radius:2px; background:<?= $bg ?>;" title="<?= $ds ?>: <?= $count ?> courses"></div>
            <?php } ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Overview + Quick Access side by side -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
        <!-- Overview -->
        <div class="mode-card">
            <h3 style="margin:0 0 1rem 0;"><i class="fas fa-chart-line" style="color:var(--primary);margin-right:0.5rem;"></i> Overview</h3>
            <div class="analytics-cards">
                <div class="analytics-card" style="cursor:pointer;" onclick="location.href='?page=learner/study'">
                    <h2><i class="fas fa-book-open" style="margin-right:0.4rem;opacity:0.6;"></i> My Courses</h2>
                    <p class="analytics-value"><?= $stats['my_courses'] ?></p>
                    <div style="font-size:0.85rem;color:#999;"><?= $stats['in_progress'] ?> active</div>
                </div>
                <div class="analytics-card" style="cursor:pointer;" onclick="location.href='?page=learner/result'">
                    <h2><i class="fas fa-check-circle" style="margin-right:0.4rem;opacity:0.6;"></i> Completed</h2>
                    <p class="analytics-value"><?= $stats['completed'] ?></p>
                    <div style="font-size:0.85rem;color:#999;"><?= $stats['certificates'] ?> certificates</div>
                </div>
                <div class="analytics-card">
                    <h2><i class="fas fa-star" style="margin-right:0.4rem;opacity:0.6;"></i> Avg Score</h2>
                    <p class="analytics-value"><?= $stats['avg_score'] ?>%</p>
                    <div style="font-size:0.85rem;color:#999;">overall</div>
                </div>
                <div class="analytics-card">
                    <h2><i class="fas fa-fire" style="margin-right:0.4rem;opacity:0.6;"></i> Streak</h2>
                    <p class="analytics-value"><?= $streak ?></p>
                    <div style="font-size:0.85rem;color:#999;">day<?= $streak !== 1 ? 's' : '' ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="mode-card">
            <h3><i class="fas fa-bolt" style="color:var(--accent);margin-right:0.5rem;"></i> Quick Access</h3>
            <div style="display:grid;gap:0.5rem;margin-top:0.75rem;">
                <?php
                $links = [
                    ['icon' => 'fa-graduation-cap', 'label' => 'Browse Catalog', 'url' => '?page=learner/catalog', 'bg' => 'rgba(99,102,241,0.1)', 'color' => '#6366f1'],
                    ['icon' => 'fa-book-open', 'label' => 'My Study', 'url' => '?page=learner/study', 'bg' => 'rgba(99,102,241,0.1)', 'color' => '#6366f1'],
                    ['icon' => 'fa-check-circle', 'label' => 'Results', 'url' => '?page=learner/result', 'bg' => 'rgba(99,102,241,0.1)', 'color' => '#6366f1'],
                    ['icon' => 'fa-calendar', 'label' => 'Calendar', 'url' => '?page=learner/calendar', 'bg' => 'rgba(245,158,11,0.1)', 'color' => '#f59e0b'],
                    ['icon' => 'fa-sticky-note', 'label' => 'Notes', 'url' => '?page=learner/notes', 'bg' => 'rgba(245,158,11,0.1)', 'color' => '#f59e0b'],
                    ['icon' => 'fa-user-circle', 'label' => 'My Profile', 'url' => '?page=learner/profile', 'bg' => 'rgba(245,158,11,0.1)', 'color' => '#f59e0b'],
                ];
                foreach ($links as $lk): ?>
                <a href="<?= $lk['url'] ?>" style="display:flex;align-items:center;gap:0.75rem;padding:0.7rem 1rem;background:var(--bg-subtle);border-radius:8px;text-decoration:none;color:var(--text);font-size:0.9rem;">
                    <div style="width:32px;height:32px;border-radius:8px;background:<?= $lk['bg'] ?>;color:<?= $lk['color'] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas <?= $lk['icon'] ?>"></i></div>
                    <span style="flex:1;font-weight:500;"><?= $lk['label'] ?></span>
                    <i class="fas fa-chevron-right" style="font-size:0.7rem;color:#999;"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:2fr 1fr; gap:1.5rem;">
        <div>
            <!-- Personalized Reminders -->
            <?php if (!empty($reminders)): ?>
            <div class="mode-card" style="margin-bottom:1.5rem; border-left:4px solid var(--accent);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h3 style="margin:0;"><i class="fas fa-lightbulb" style="color:var(--accent); margin-right:0.5rem;"></i> Reminders</h3>
                    <span style="font-size:0.8rem; color:#999;"><?= count($reminders) ?> item<?= count($reminders) !== 1 ? 's' : '' ?></span>
                </div>
                <div style="display:grid; gap:0.5rem;">
                    <?php foreach ($reminders as $rm): ?>
                        <a href="<?= $rm['link'] ?>" style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; background:rgba(23,162,184,0.05); border-radius:8px; text-decoration:none; color:#333; transition:background 0.2s;">
                            <div style="width:32px; height:32px; border-radius:8px; background:<?= $rm['color'] ?>20; color:<?= $rm['color'] ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fas <?= $rm['icon'] ?>"></i></div>
                            <span style="flex:1; font-size:0.9rem; line-height:1.4;"><?= $rm['title'] ?></span>
                            <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.8rem;"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Continue Learning with Progress -->
            <?php if (!empty($continueLearning)): ?>
            <div class="mode-card" style="margin-bottom:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h3 style="margin:0;"><i class="fas fa-play-circle" style="color:var(--primary); margin-right:0.5rem;"></i> Continue Learning</h3>
                    <a href="?page=learner/study" style="color:var(--primary); font-size:0.9rem; text-decoration:none;">View All &rarr;</a>
                </div>
                <div style="display:grid; gap:0.75rem;">
                    <?php foreach ($continueLearning as $cl):
                        $total = (int) ($cl['total_lessons'] ?? 1);
                        $done = (int) ($cl['completed_lessons'] ?? 0);
                        $pct = $total > 0 ? round(($done / $total) * 100) : 0;
                    ?>
                    <a href="?page=learner/study-subpage/course&course_id=<?= (int) $cl['course_id'] ?>" style="display:flex; align-items:center; gap:1rem; padding:1rem; background:var(--bg-subtle); border-radius:10px; text-decoration:none; color:#333; transition:background 0.2s;">
                        <div style="width:48px; height:48px; border-radius:10px; background:linear-gradient(135deg,var(--primary),var(--text)); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fas fa-graduation-cap"></i></div>
                        <div style="flex:1; min-width:0;">
                            <strong style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($cl['title']) ?></strong>
                            <span style="color:#999; font-size:0.85rem; display:block;"><?= htmlspecialchars($cl['instructor_name'] ?? '') ?> &bull; <?= $done ?>/<?= $total ?> lessons</span>
                            <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.35rem;">
                                <div style="flex:1; background:var(--border); height:5px; border-radius:3px; overflow:hidden;">
                                    <div style="background:<?= $pct >= 70 ? 'var(--primary)' : ($pct >= 30 ? 'var(--accent)' : 'var(--primary)') ?>; height:100%; width:<?= $pct ?>%;"></div>
                                </div>
                                <span style="font-size:0.75rem; font-weight:600; color:var(--primary); min-width:30px; text-align:right;"><?= $pct ?>%</span>
                            </div>
                        </div>
                        <div style="text-align:right; flex-shrink:0;">
                            <span style="color:#999; font-size:0.75rem; display:block;"><?= homeTimeAgo($cl['last_accessed_at']) ?></span>
                            <i class="fas fa-chevron-right" style="color:#ccc; margin-top:0.25rem;"></i>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Upcoming Deadlines -->
            <?php if (!empty($upcomingDeadlines)): ?>
            <div class="mode-card" style="margin-bottom:1.5rem; border-left:4px solid var(--accent);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h3 style="margin:0;"><i class="fas fa-clock" style="color:var(--accent); margin-right:0.5rem;"></i> Upcoming Deadlines</h3>
                    <a href="?page=learner/calendar" style="color:var(--primary); font-size:0.9rem; text-decoration:none;">Calendar &rarr;</a>
                </div>
                <div style="display:grid; gap:0.5rem;">
                    <?php foreach ($upcomingDeadlines as $dl):
                        $dl2 = homeDaysLeft($dl['enrollment_deadline']);
                        $urgent = ($dl2 === 'Today' || $dl2 === 'Tomorrow' || $dl2 === 'Overdue');
                    ?>
                    <a href="?page=learner/study-subpage/course&course_id=<?= $dl['course_id'] ?>" style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; background:<?= $urgent ? 'var(--bg-subtle)' : 'var(--bg-subtle)' ?>; border-radius:8px; text-decoration:none; color:#333;">
                        <i class="fas <?= $urgent ? 'fa-exclamation-triangle' : 'fa-calendar-day' ?>" style="color:<?= $urgent ? 'var(--accent)' : '#666' ?>;"></i>
                        <span style="flex:1; font-size:0.95rem;"><?= htmlspecialchars($dl['title']) ?></span>
                        <span style="font-size:0.8rem; font-weight:600; color:<?= $urgent ? 'var(--accent)' : '#666' ?>;"><?= $dl2 ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Upcoming Video Conferences -->
            <?php if (!empty($upcomingVC)): ?>
            <div class="mode-card" style="margin-bottom:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h3 style="margin:0;"><i class="fas fa-video" style="color:var(--accent); margin-right:0.5rem;"></i> Live Sessions</h3>
                    <a href="?page=learner/calendar" style="color:var(--primary); font-size:0.9rem; text-decoration:none;">View All &rarr;</a>
                </div>
                <div style="display:grid; gap:0.5rem;">
                    <?php foreach ($upcomingVC as $vc):
                        $isSoon = (strtotime($vc['scheduled_at']) - time()) < 86400;
                    ?>
                    <div style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; background:<?= $isSoon ? 'rgba(0,123,255,0.08)' : 'var(--bg-subtle)' ?>; border-radius:8px; border-left:3px solid <?= $isSoon ? 'var(--accent)' : 'var(--border)' ?>;">
                        <div style="width:36px; height:36px; border-radius:8px; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.85rem;"><i class="fas fa-video"></i></div>
                        <div style="flex:1; min-width:0;">
                            <strong style="display:block; font-size:0.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($vc['title']) ?></strong>
                            <span style="font-size:0.8rem; color:#666;"><?= htmlspecialchars($vc['platform'] ?? '') ?> &bull; <?= date('M j, g:i A', strtotime($vc['scheduled_at'])) ?></span>
                        </div>
                        <?php if ($isSoon && !empty($vc['meeting_link'])): ?>
                            <a href="<?= htmlspecialchars($vc['meeting_link']) ?>" target="_blank" style="padding:0.4rem 0.8rem; background:var(--accent); color:#fff; border:none; border-radius:6px; text-decoration:none; font-size:0.8rem; font-weight:500;">Join</a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Grades -->
            <?php if (!empty($recentGrades)): ?>
            <div class="mode-card" style="margin-bottom:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h3 style="margin:0;"><i class="fas fa-chart-line" style="color:var(--primary); margin-right:0.5rem;"></i> Recent Results</h3>
                    <a href="?page=learner/result" style="color:var(--primary); font-size:0.9rem; text-decoration:none;">View All &rarr;</a>
                </div>
                <div style="display:grid; gap:0.5rem;">
                    <?php foreach ($recentGrades as $rg): ?>
                    <div style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0; border-bottom:1px solid var(--bg-subtle);">
                        <div style="width:40px; height:40px; border-radius:8px; background:<?= $rg['status'] === 'passed' ? 'var(--bg-subtle)' : 'var(--bg-subtle)' ?>; color:<?= $rg['status'] === 'passed' ? 'var(--primary)' : 'var(--accent)' ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-weight:700; font-size:0.85rem;"><?= round($rg['final_score']) ?>%</div>
                        <div style="flex:1; min-width:0;">
                            <strong style="display:block; font-size:0.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($rg['title']) ?></strong>
                            <span style="font-size:0.8rem; color:<?= $rg['status'] === 'passed' ? 'var(--primary)' : 'var(--accent)' ?>; font-weight:500;"><?= ucfirst($rg['status']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Course Recommendations -->
            <div id="home-recs-section" class="mode-card" style="margin-bottom:1.5rem; display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h3 style="margin:0;"><i class="fas fa-magic" style="color:var(--primary); margin-right:0.5rem;"></i> Suggested For You</h3>
                    <a href="?page=learner/catalog" style="color:var(--primary); font-size:0.9rem; text-decoration:none;">Browse Catalog &rarr;</a>
                </div>
                <div id="home-recs-grid" style="display:grid; grid-template-columns:1fr; gap:0.75rem;"></div>
            </div>


        </div>

        <!-- Sidebar -->
        <div>
            <!-- Bookmarks -->
            <div class="mode-card" style="margin-bottom:1.5rem;">
                <h3 style="margin:0 0 0.75rem 0; font-size:1rem;"><i class="fas fa-bookmark" style="color:var(--primary); margin-right:0.4rem;"></i> Bookmarks</h3>
                <?php if (empty($bookmarks)): ?>
                    <p style="color:#999; font-size:0.9rem; text-align:center; padding:1rem 0;">No bookmarks yet</p>
                <?php else: ?>
                    <?php foreach ($bookmarks as $bm): ?>
                    <a href="<?= htmlspecialchars($bm['link']) ?>" style="display:block; padding:0.6rem 0; border-bottom:1px solid var(--bg-subtle); text-decoration:none; color:#333; font-size:0.9rem;">
                        <i class="fas fa-bookmark" style="color:var(--accent); margin-right:0.4rem; font-size:0.8rem;"></i><?= htmlspecialchars($bm['title']) ?>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Favorites -->
            <div class="mode-card" style="margin-bottom:1.5rem;">
                <h3 style="margin:0 0 0.75rem 0; font-size:1rem;"><i class="fas fa-heart" style="color:var(--accent); margin-right:0.4rem;"></i> Favorites</h3>
                <?php if (empty($favorites)): ?>
                    <p style="color:#999; font-size:0.9rem; text-align:center; padding:1rem 0;">No favorites yet</p>
                <?php else: ?>
                    <?php foreach ($favorites as $fav): ?>
                    <a href="<?= htmlspecialchars($fav['link']) ?>" style="display:block; padding:0.6rem 0; border-bottom:1px solid var(--bg-subtle); text-decoration:none; color:#333; font-size:0.9rem;">
                        <i class="fas fa-heart" style="color:var(--accent); margin-right:0.4rem; font-size:0.8rem;"></i><?= htmlspecialchars($fav['title']) ?>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Notifications -->
            <div class="mode-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                    <h3 style="margin:0; font-size:1rem;"><i class="fas fa-bell" style="color:var(--primary); margin-right:0.4rem;"></i> Notifications</h3>
                    <a href="?page=learner/notification" style="color:var(--primary); font-size:0.8rem; text-decoration:none;">View All &rarr;</a>
                </div>
                <?php if (empty($recentNotifications)): ?>
                    <p style="color:#999; font-size:0.9rem; text-align:center; padding:1rem 0;">No notifications</p>
                <?php else: ?>
                    <?php foreach ($recentNotifications as $notif): ?>
                    <div style="padding:0.6rem 0; border-bottom:1px solid var(--bg-subtle); font-size:0.85rem;">
                        <div style="display:flex; align-items:center; gap:0.4rem; margin-bottom:0.15rem;">
                            <?php if (!$notif['is_read']): ?>
                                <span style="width:6px; height:6px; background:var(--accent); border-radius:50%; display:inline-block;"></span>
                            <?php endif; ?>
                            <strong style="font-size:0.85rem;"><?= htmlspecialchars($notif['title']) ?></strong>
                        </div>
                        <p style="margin:0; color:#888; font-size:0.8rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars(mb_substr($notif['message'], 0, 60)) ?></p>
                    </div>
                    <?php endforeach; ?>
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
                var skills = rec.skills.length > 0 ? '<span style="font-size:0.7rem;color:#999;">' + rec.skills.slice(0,2).join(', ') + '</span>' : '';
                html += '<a href="' + rec.link + '" style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem;background:var(--bg-subtle);border-radius:8px;text-decoration:none;color:#333;">
                    <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--text));color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-graduation-cap"></i></div>
                    <div style="flex:1;min-width:0;">
                        <strong style="display:block;font-size:0.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + rec.title + '</strong>
                        <span style="font-size:0.8rem;color:#999;">' + rec.reasons[0] + '</span>
                    </div>
                    <i class="fas fa-chevron-right" style="color:#ccc;font-size:0.8rem;"></i>
                </a>';
            });
            grid.innerHTML = html;
            section.style.display = 'block';
        })
        .catch(function() {});
})();
</script>
