<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

$stats = ['my_courses' => 0, 'my_modules' => 0, 'my_learners' => 0, 'my_programs' => 0, 'completed' => 0, 'avg_score' => 0];
$recentEnrollments = [];
$topCourses = [];
$atRiskCount = 0;

try {
    $pdo = (new Database())->getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_course WHERE instructor_id = :iid AND status IN ('active','draft')");
    $stmt->execute([':iid' => $instructorId]);
    $stats['my_courses'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_module m JOIN ld_course c ON c.id = m.course_id WHERE c.instructor_id = :iid AND m.status = 'active'");
    $stmt->execute([':iid' => $instructorId]);
    $stats['my_modules'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.learner_id) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :iid");
    $stmt->execute([':iid' => $instructorId]);
    $stats['my_learners'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_program WHERE instructor_id = :iid AND status = 'active'");
    $stmt->execute([':iid' => $instructorId]);
    $stats['my_programs'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :iid AND e.status = 'completed'");
    $stmt->execute([':iid' => $instructorId]);
    $stats['completed'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT ROUND(COALESCE(AVG(g.final_score),0),1) FROM ld_grade g JOIN ld_course c ON c.id = g.course_id WHERE c.instructor_id = :iid");
    $stmt->execute([':iid' => $instructorId]);
    $stats['avg_score'] = (float) $stmt->fetchColumn();
    $recentEnrollments = $pdo->prepare("SELECT e.learner_id, e.status, e.enrolled_at, c.title AS course_title, CONCAT(emp.first_name,' ',emp.last_name) AS learner_name FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id WHERE c.instructor_id = :iid ORDER BY e.enrolled_at DESC LIMIT 5");
    $recentEnrollments->execute([':iid' => $instructorId]);
    $recentEnrollments = $recentEnrollments->fetchAll(PDO::FETCH_ASSOC);
    $topCourses = $pdo->prepare("SELECT c.title, COUNT(e.id) AS enrollment_count, ROUND(COALESCE(AVG(g.final_score),0),1) AS avg_score, SUM(CASE WHEN e.status='completed' THEN 1 ELSE 0 END) AS completed_count FROM ld_course c LEFT JOIN ld_enrollment e ON e.course_id = c.id LEFT JOIN ld_grade g ON g.course_id = c.id AND g.learner_id = e.learner_id WHERE c.instructor_id = :iid AND c.status = 'active' GROUP BY c.id,c.title ORDER BY enrollment_count DESC LIMIT 5");
    $topCourses->execute([':iid' => $instructorId]);
    $topCourses = $topCourses->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :iid AND e.status IN ('enrolled','in_progress') AND DATEDIFF(NOW(), e.last_accessed_at) > 14");
    $stmt->execute([':iid' => $instructorId]);
    $atRiskCount = (int) $stmt->fetchColumn();
} catch (Throwable $e) {}

function instrTimeAgo($dt) {
    if (!$dt) return '';
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    return floor($d / 86400) . 'd ago';
}
?>
<div class="module-content home-module">
    <div class="home-dashboard">

        <!-- Hero -->
        <section class="home-hero">
            <div class="home-hero-main">
                <h1><?= htmlspecialchars($employeeClass->getGreeting()) ?></h1>
                <p>Here is how your courses are doing today.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-book"></i> <?= (int) $stats['my_courses'] ?> course<?= $stats['my_courses'] !== 1 ? 's' : '' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-users"></i> <?= (int) $stats['my_learners'] ?> learner<?= $stats['my_learners'] !== 1 ? 's' : '' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-star"></i> <?= $stats['avg_score'] ?>% avg score</span>
                </div>
            </div>
        </section>

        <!-- Key stats -->
        <div class="home-stats">
            <a class="home-stat" href="?page=instructor/elearning">
                <span class="home-stat-icon tone-violet"><i class="fas fa-book"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">My Courses</span>
                    <span class="home-stat-value"><?= (int) $stats['my_courses'] ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['my_modules'] ?> modules</span>
                </span>
            </a>
            <a class="home-stat" href="?page=instructor/analytics">
                <span class="home-stat-icon tone-indigo"><i class="fas fa-users"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">My Learners</span>
                    <span class="home-stat-value"><?= (int) $stats['my_learners'] ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['completed'] ?> completed</span>
                </span>
            </a>
            <a class="home-stat" href="?page=instructor/analytics">
                <span class="home-stat-icon tone-emerald"><i class="fas fa-star"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Avg Score</span>
                    <span class="home-stat-value"><?= $stats['avg_score'] ?>%</span>
                    <span class="home-stat-sub">across all courses</span>
                </span>
            </a>
            <div class="home-stat">
                <span class="home-stat-icon <?= $atRiskCount > 0 ? 'tone-amber' : 'tone-slate' ?>"><i class="fas fa-exclamation-triangle"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">At-Risk</span>
                    <span class="home-stat-value"><?= (int) $atRiskCount ?></span>
                    <span class="home-stat-sub">inactive 14+ days</span>
                </span>
            </div>
        </div>

        <!-- Main column + sidebar -->
        <div class="home-columns">
            <div class="home-col">
                <!-- Top Courses -->
                <div class="home-card">
                    <div class="home-card-head">
                        <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-trophy"></i></span> Top Courses</h3>
                        <a class="home-card-link" href="?page=instructor/analytics">View analytics <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (empty($topCourses)): ?>
                        <div class="home-empty"><i class="fas fa-trophy"></i><p>No courses yet</p></div>
                    <?php else: ?>
                        <div class="home-list">
                            <?php foreach ($topCourses as $idx => $tc): $compRate = $tc['enrollment_count'] > 0 ? round(($tc['completed_count'] / $tc['enrollment_count']) * 100) : 0; ?>
                            <div class="home-row">
                                <span class="home-row-icon tone-violet"><?= $idx + 1 ?></span>
                                <span class="home-row-body">
                                    <span class="home-row-title"><?= htmlspecialchars($tc['title']) ?></span>
                                    <span class="home-row-meta"><?= (int) $tc['enrollment_count'] ?> enrolled &bull; <?= $tc['avg_score'] ?>% avg</span>
                                </span>
                                <span class="home-progress" title="<?= (int) $tc['completed_count'] ?> of <?= (int) $tc['enrollment_count'] ?> enrollments completed">
                                    <span class="home-progress-track"><span class="home-progress-fill" style="width:<?= $compRate ?>%"></span></span>
                                    <span class="home-progress-val"><?= $compRate ?>%</span>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Enrollments -->
                <div class="home-card">
                    <div class="home-card-head">
                        <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-clock"></i></span> Recent Enrollments</h3>
                    </div>
                    <?php if (empty($recentEnrollments)): ?>
                        <div class="home-empty"><i class="fas fa-user-plus"></i><p>No enrollments yet</p></div>
                    <?php else: ?>
                        <div class="home-list">
                            <?php foreach ($recentEnrollments as $re): ?>
                            <div class="home-row">
                                <span class="home-row-icon tone-<?= $re['status'] === 'completed' ? 'emerald' : 'amber' ?>"><i class="fas fa-user"></i></span>
                                <span class="home-row-body">
                                    <a class="home-row-title" href="?page=instructor/learner-profile&amp;id=<?= (int) $re['learner_id'] ?>"><?= htmlspecialchars($re['learner_name']) ?></a>
                                    <span class="home-row-meta"><?= htmlspecialchars(mb_substr($re['course_title'], 0, 40)) ?></span>
                                </span>
                                <span class="home-badge <?= $re['status'] === 'completed' ? 'is-success' : 'is-warn' ?>"><?= htmlspecialchars(str_replace('_', ' ', $re['status'])) ?></span>
                                <span class="home-row-meta" style="margin:0;"><?= instrTimeAgo($re['enrolled_at']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="home-col">
                <!-- Quick Access -->
                <div class="home-card">
                    <div class="home-card-head">
                        <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-bolt"></i></span> Quick Access</h3>
                    </div>
                    <div class="home-quick">
                        <?php $links = [
                            ['icon'=>'fa-book','label'=>'E-Learning','url'=>'?page=instructor/elearning','tone'=>'indigo'],
                            ['icon'=>'fa-person-hiking','label'=>'Trainings','url'=>'?page=instructor/training','tone'=>'amber'],
                            ['icon'=>'fa-chart-line','label'=>'Analytics','url'=>'?page=instructor/analytics','tone'=>'indigo'],
                            ['icon'=>'fa-award','label'=>'Certificates','url'=>'?page=instructor/certificate','tone'=>'amber'],
                            ['icon'=>'fa-bell','label'=>'Notifications','url'=>'?page=instructor/notification','tone'=>'indigo'],
                            ['icon'=>'fa-user-circle','label'=>'My Profile','url'=>'?page=instructor/profile','tone'=>'violet'],
                        ]; foreach ($links as $lk): ?>
                        <a class="home-quick-link" href="<?= $lk['url'] ?>">
                            <span class="home-quick-icon tone-<?= $lk['tone'] ?>"><i class="fas <?= $lk['icon'] ?>"></i></span>
                            <span class="home-quick-label"><?= $lk['label'] ?></span>
                            <i class="fas fa-chevron-right home-quick-chev"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- At-Risk -->
                <?php if ($atRiskCount > 0): ?>
                <div class="home-card">
                    <div class="home-card-head">
                        <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-exclamation-triangle"></i></span> At-Risk Students</h3>
                    </div>
                    <p style="margin:0;font-size:0.88rem;color:var(--muted);"><?= (int) $atRiskCount ?> student<?= $atRiskCount !== 1 ? 's' : '' ?> haven't accessed courses in 14+ days.</p>
                    <a class="home-cta" style="margin-top:0.85rem;" href="?page=instructor/analytics">View details</a>
                </div>
                <?php endif; ?>

                <!-- Summary -->
                <div class="home-card">
                    <div class="home-card-head">
                        <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-chart-pie"></i></span> Summary</h3>
                    </div>
                    <div class="home-kv"><span class="home-kv-label"><i class="fas fa-book"></i> Active Courses</span><span class="home-kv-value"><?= (int) $stats['my_courses'] ?></span></div>
                    <div class="home-kv"><span class="home-kv-label"><i class="fas fa-users"></i> Total Learners</span><span class="home-kv-value"><?= (int) $stats['my_learners'] ?></span></div>
                    <div class="home-kv"><span class="home-kv-label"><i class="fas fa-check-circle"></i> Completed</span><span class="home-kv-value"><?= (int) $stats['completed'] ?></span></div>
                    <div class="home-kv"><span class="home-kv-label"><i class="fas fa-star"></i> Avg Score</span><span class="home-kv-value"><?= $stats['avg_score'] ?>%</span></div>
                    <div class="home-kv"><span class="home-kv-label"><i class="fas fa-project-diagram"></i> Programs</span><span class="home-kv-value"><?= (int) $stats['my_programs'] ?></span></div>
                </div>
            </div>
        </div>
    </div>
</div>