<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

$stats = ['my_courses' => 0, 'my_modules' => 0, 'my_learners' => 0, 'my_programs' => 0, 'completed' => 0, 'in_progress' => 0, 'avg_score' => 0, 'pass_rate' => 0, 'total_quizzes' => 0];
$coursePerformance = [];
$studentLeaderboard = [];
$atRiskStudents = [];
$recentGrades = [];
$monthlyEnrollments = [];
$gradeDistribution = [];
$courseEngagement = [];

try {
    $pdo = (new Database())->getConnection();

    // Core stats
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

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :iid AND e.status IN ('enrolled','in_progress')");
    $stmt->execute([':iid' => $instructorId]);
    $stats['in_progress'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT ROUND(COALESCE(AVG(g.final_score),0),1) FROM ld_grade g JOIN ld_course c ON c.id = g.course_id WHERE c.instructor_id = :iid");
    $stmt->execute([':iid' => $instructorId]);
    $stats['avg_score'] = (float) $stmt->fetchColumn();

    $totalEnrollments = $stats['completed'] + $stats['in_progress'];
    $stats['pass_rate'] = $totalEnrollments > 0 ? round(($stats['completed'] / $totalEnrollments) * 100) : 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_quiz q JOIN ld_module m ON m.id = q.module_id JOIN ld_course c ON c.id = m.course_id WHERE c.instructor_id = :iid AND q.status = 'active'");
    $stmt->execute([':iid' => $instructorId]);
    $stats['total_quizzes'] = (int) $stmt->fetchColumn();

    // Course performance
    $coursePerformance = $pdo->prepare("
        SELECT c.id, c.title, c.status,
            COUNT(DISTINCT e.id) AS enrollment_count,
            SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN e.status IN ('enrolled','in_progress') THEN 1 ELSE 0 END) AS active_count,
            ROUND(COALESCE(AVG(g.final_score),0),1) AS avg_score,
            (SELECT COUNT(*) FROM ld_module m WHERE m.course_id = c.id AND m.status = 'active') AS module_count,
            (SELECT COUNT(*) FROM ld_lesson l JOIN ld_module m2 ON m2.id = l.module_id WHERE m2.course_id = c.id AND l.status = 'active') AS lesson_count
        FROM ld_course c
        LEFT JOIN ld_enrollment e ON e.course_id = c.id
        LEFT JOIN ld_grade g ON g.course_id = c.id AND g.learner_id = e.learner_id
        WHERE c.instructor_id = :iid AND c.status IN ('active','draft')
        GROUP BY c.id, c.title, c.status
        ORDER BY enrollment_count DESC
    ");
    $coursePerformance->execute([':iid' => $instructorId]);
    $coursePerformance = $coursePerformance->fetchAll(PDO::FETCH_ASSOC);

    // Student leaderboard
    $studentLeaderboard = $pdo->prepare("
        SELECT e.learner_id,
            CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name,
            emp.position_id AS learner_position,
            COUNT(DISTINCT e.course_id) AS courses_enrolled,
            SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS courses_completed,
            ROUND(COALESCE(AVG(g.final_score),0),1) AS avg_score,
            MAX(e.last_accessed_at) AS last_active
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id
        LEFT JOIN ld_grade g ON g.learner_id = e.learner_id AND g.course_id = e.course_id
        WHERE c.instructor_id = :iid AND e.status IN ('enrolled','in_progress','completed')
        GROUP BY e.learner_id, emp.first_name, emp.last_name, emp.position_id
        ORDER BY avg_score DESC, courses_completed DESC
        LIMIT 20
    ");
    $studentLeaderboard->execute([':iid' => $instructorId]);
    $studentLeaderboard = $studentLeaderboard->fetchAll(PDO::FETCH_ASSOC);

    // At-risk students (enrolled but inactive for 14+ days, or failing)
    $atRiskStudents = $pdo->prepare("
        SELECT e.learner_id,
            CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name,
            c.title AS course_title,
            e.status AS enrollment_status,
            e.last_accessed_at,
            DATEDIFF(NOW(), e.last_accessed_at) AS days_inactive,
            ROUND(COALESCE(AVG(g.final_score),0),1) AS avg_score,
            CASE
                WHEN DATEDIFF(NOW(), e.last_accessed_at) > 14 THEN 'inactive'
                WHEN AVG(g.final_score) < 60 AND AVG(g.final_score) IS NOT NULL THEN 'failing'
                ELSE 'slow_progress'
            END AS risk_reason
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id
        LEFT JOIN ld_grade g ON g.learner_id = e.learner_id AND g.course_id = e.course_id
        WHERE c.instructor_id = :iid AND e.status IN ('enrolled','in_progress')
        GROUP BY e.learner_id, emp.first_name, emp.last_name, c.title, e.status, e.last_accessed_at
        HAVING days_inactive > 14 OR avg_score < 60
        ORDER BY days_inactive DESC
        LIMIT 15
    ");
    $atRiskStudents->execute([':iid' => $instructorId]);
    $atRiskStudents = $atRiskStudents->fetchAll(PDO::FETCH_ASSOC);

    // Recent grades
    $recentGrades = $pdo->prepare("
        SELECT g.final_score, g.status, g.issued_at, c.title AS course_title,
            CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name
        FROM ld_grade g
        JOIN ld_course c ON c.id = g.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = g.learner_id
        WHERE c.instructor_id = :iid
        ORDER BY g.issued_at DESC LIMIT 15
    ");
    $recentGrades->execute([':iid' => $instructorId]);
    $recentGrades = $recentGrades->fetchAll(PDO::FETCH_ASSOC);

    // Monthly enrollments trend
    $monthlyEnrollments = $pdo->prepare("
        SELECT DATE_FORMAT(e.enrolled_at, '%Y-%m') AS month, COUNT(*) AS cnt
        FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id
        WHERE c.instructor_id = :iid AND e.enrolled_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month ORDER BY month ASC
    ");
    $monthlyEnrollments->execute([':iid' => $instructorId]);
    $monthlyEnrollments = $monthlyEnrollments->fetchAll(PDO::FETCH_ASSOC);

    // Grade distribution
    $gradeDistribution = $pdo->prepare("
        SELECT
            SUM(CASE WHEN g.final_score >= 90 THEN 1 ELSE 0 END) AS grade_a,
            SUM(CASE WHEN g.final_score >= 80 AND g.final_score < 90 THEN 1 ELSE 0 END) AS grade_b,
            SUM(CASE WHEN g.final_score >= 70 AND g.final_score < 80 THEN 1 ELSE 0 END) AS grade_c,
            SUM(CASE WHEN g.final_score < 70 THEN 1 ELSE 0 END) AS grade_f,
            COUNT(*) AS total
        FROM ld_grade g JOIN ld_course c ON c.id = g.course_id WHERE c.instructor_id = :iid
    ");
    $gradeDistribution->execute([':iid' => $instructorId]);
    $gradeDistribution = $gradeDistribution->fetch(PDO::FETCH_ASSOC);

    // Course engagement (per-course breakdown)
    $courseEngagement = $pdo->prepare("
        SELECT c.title,
            (SELECT COUNT(*) FROM ld_lesson l JOIN ld_module m ON m.id = l.module_id WHERE m.course_id = c.id AND l.status = 'active') AS total_lessons,
            (SELECT COUNT(DISTINCT p.reference_id) FROM ld_progress p JOIN ld_enrollment e2 ON e2.id = p.enrollment_id WHERE e2.course_id = c.id AND p.item_type = 'lesson' AND p.status = 'completed') AS completed_lessons,
            (SELECT COUNT(*) FROM ld_quiz q JOIN ld_module m2 ON m2.id = q.module_id WHERE m2.course_id = c.id AND q.status = 'active') AS total_quizzes,
            (SELECT COUNT(*) FROM ld_quiz_session qs JOIN ld_enrollment e3 ON e3.course_id = c.id WHERE qs.learner_id = e3.learner_id AND qs.item_type = 'quiz' AND qs.status IN ('submitted','expired')) AS quizzes_taken,
            (SELECT ROUND(COALESCE(AVG(qs2.score),0),1) FROM ld_quiz_session qs2 JOIN ld_enrollment e4 ON e4.course_id = c.id WHERE qs2.learner_id = e4.learner_id AND qs2.item_type = 'quiz' AND qs2.status = 'submitted') AS avg_quiz_score
        FROM ld_course c
        WHERE c.instructor_id = :iid AND c.status = 'active'
        ORDER BY c.title ASC
    ");
    $courseEngagement->execute([':iid' => $instructorId]);
    $courseEngagement = $courseEngagement->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {}

$totalEnroll = $stats['completed'] + $stats['in_progress'];
$completionRate = $totalEnroll > 0 ? round(($stats['completed'] / $totalEnroll) * 100) : 0;
?>
<div class="module-content analytics-dashboard">
    <div class="toolbar">
        
    </div>

    <!-- Primary Stats -->
    <div class="analytics-cards" style="margin-bottom:1.5rem;">
        <div class="analytics-card" style="border-top:3px solid var(--primary);cursor:pointer;" onclick="document.querySelector('[data-tab=tab-overview]').click()">
            <h2><i class="fas fa-graduation-cap" style="margin-right:0.4rem;opacity:0.6;"></i> Courses</h2>
            <p class="analytics-value"><?= $stats['my_courses'] ?></p>
            <div style="font-size:0.85rem;color:#999;"><?= $stats['my_modules'] ?> modules &bull; <?= $stats['total_quizzes'] ?> quizzes</div>
        </div>
        <div class="analytics-card" style="border-top:3px solid var(--primary);cursor:pointer;" onclick="document.querySelector('[data-tab=tab-students]').click()">
            <h2><i class="fas fa-users" style="margin-right:0.4rem;opacity:0.6;"></i> Learners</h2>
            <p class="analytics-value"><?= $stats['my_learners'] ?></p>
            <div style="font-size:0.85rem;color:#999;"><?= $stats['completed'] ?> completed &bull; <?= $stats['in_progress'] ?> active</div>
        </div>
        <div class="analytics-card" style="border-top:3px solid var(--accent);">
            <h2><i class="fas fa-star" style="margin-right:0.4rem;opacity:0.6;"></i> Avg Score</h2>
            <p class="analytics-value"><?= $stats['avg_score'] ?>%</p>
            <div style="font-size:0.85rem;color:#999;"><?= $stats['pass_rate'] ?>% completion rate</div>
        </div>
        <div class="analytics-card" style="border-top:3px solid var(--danger);cursor:pointer;" onclick="document.querySelector('[data-tab=tab-students]').click()">
            <h2><i class="fas fa-exclamation-triangle" style="margin-right:0.4rem;opacity:0.6;"></i> At-Risk</h2>
            <p class="analytics-value" style="color:<?= count($atRiskStudents) > 0 ? 'var(--danger)' : 'inherit' ?>;"><?= count($atRiskStudents) ?></p>
            <div style="font-size:0.85rem;color:#999;">students need attention</div>
        </div>
    </div>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-overview">Overview</button>
            <button type="button" class="tab-item" data-tab="tab-students">Students</button>
            <button type="button" class="tab-item" data-tab="tab-courses">Courses</button>
            <button type="button" class="tab-item" data-tab="tab-grades">Grades</button>
        </div>

        <!-- ==================== OVERVIEW TAB ==================== -->
        <div class="tab-content active" data-tab="tab-overview">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                <!-- Completion Gauge -->
                <div class="mode-card">
                    <h3><i class="fas fa-chart-pie" style="color:var(--primary);margin-right:0.5rem;"></i> Course Completion</h3>
                    <div style="display:flex;align-items:center;gap:2rem;padding:1.5rem 0;">
                        <div style="position:relative;width:120px;height:120px;">
                            <svg width="120" height="120" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="var(--border)" stroke-width="12"/>
                                <circle cx="60" cy="60" r="50" fill="none" stroke="var(--primary)" stroke-width="12" stroke-dasharray="<?= $completionRate * 3.14 ?> <?= 314 - ($completionRate * 3.14) ?>" stroke-dashoffset="78.5" stroke-linecap="round" style="transform:rotate(-90deg);transform-origin:center;"/>
                            </svg>
                            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:1.5rem;font-weight:700;color:var(--primary);"><?= $completionRate ?>%</div>
                        </div>
                        <div>
                            <p style="margin:0;font-size:1.1rem;"><strong><?= $stats['completed'] ?></strong> courses completed</p>
                            <p style="margin:0.25rem 0;color:#666;">out of <?= $totalEnroll ?> total enrollments</p>
                            <p style="margin:0.25rem 0;color:#999;font-size:0.9rem;"><?= $stats['in_progress'] ?> currently in progress</p>
                        </div>
                    </div>
                </div>

                <!-- Enrollment Trend -->
                <div class="mode-card">
                    <h3><i class="fas fa-chart-line" style="color:var(--primary);margin-right:0.5rem;"></i> Enrollment Trend</h3>
                    <?php if (empty($monthlyEnrollments)): ?>
                        <p style="color:#999;text-align:center;padding:2rem;">No enrollment data yet.</p>
                    <?php else: ?>
                        <div style="padding:1.5rem 0;">
                            <div style="display:flex;align-items:flex-end;gap:0.5rem;height:140px;">
                                <?php
                                $maxCnt = max(array_column($monthlyEnrollments, 'cnt'));
                                $maxCnt = max($maxCnt, 1);
                                foreach ($monthlyEnrollments as $me):
                                    $h = round(($me['cnt'] / $maxCnt) * 120);
                                ?>
                                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.25rem;">
                                        <span style="font-size:0.75rem;font-weight:600;color:var(--primary);"><?= $me['cnt'] ?></span>
                                        <div style="width:100%;max-width:50px;height:<?= $h ?>px;background:linear-gradient(180deg,var(--primary),var(--text));border-radius:6px 6px 0 0;"></div>
                                        <span style="font-size:0.7rem;color:#999;"><?= date('M', strtotime($me['month'].'-01')) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Top Courses -->
                <div class="mode-card">
                    <h3><i class="fas fa-trophy" style="color:var(--primary);margin-right:0.5rem;"></i> Top Courses</h3>
                    <?php if (empty($coursePerformance)): ?>
                        <p style="color:#999;text-align:center;padding:1rem;">No courses yet.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($coursePerformance, 0, 5) as $idx => $cp):
                            $cpCompRate = $cp['enrollment_count'] > 0 ? round(($cp['completed_count'] / $cp['enrollment_count']) * 100) : 0;
                        ?>
                            <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem;background:var(--bg-subtle);border-radius:8px;margin-bottom:0.5rem;">
                                <div style="width:28px;height:28px;border-radius:6px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;flex-shrink:0;"><?= $idx + 1 ?></div>
                                <div style="flex:1;min-width:0;">
                                    <strong style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:0.9rem;"><?= htmlspecialchars($cp['title']) ?></strong>
                                    <span style="color:#999;font-size:0.8rem;"><?= $cp['enrollment_count'] ?> enrolled &bull; <?= $cp['avg_score'] ?>% avg</span>
                                </div>
                                <div style="width:60px;background:var(--border);height:6px;border-radius:3px;overflow:hidden;flex-shrink:0;">
                                    <div style="background:<?= $cpCompRate >= 70 ? 'var(--primary)' : ($cpCompRate >= 40 ? 'var(--accent)' : 'var(--danger)') ?>;height:100%;width:<?= $cpCompRate ?>%;"></div>
                                </div>
                                <span style="font-size:0.8rem;font-weight:600;color:var(--primary);min-width:35px;text-align:right;"><?= $cpCompRate ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- At-Risk Alert -->
                <div class="mode-card" style="<?= count($atRiskStudents) > 0 ? 'border-left:4px solid var(--danger);' : '' ?>">
                    <h3><i class="fas fa-exclamation-triangle" style="color:var(--danger);margin-right:0.5rem;"></i> At-Risk Students</h3>
                    <?php if (empty($atRiskStudents)): ?>
                        <div style="text-align:center;padding:1.5rem;">
                            <div style="font-size:2rem;color:var(--primary);margin-bottom:0.5rem;"><i class="fas fa-check-circle"></i></div>
                            <p style="color:var(--primary);font-weight:500;">All students are on track!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($atRiskStudents, 0, 5) as $ar):
                            $riskColor = $ar['risk_reason'] === 'inactive' ? 'var(--danger)' : ($ar['risk_reason'] === 'failing' ? 'var(--accent)' : 'var(--accent)');
                            $riskLabel = $ar['risk_reason'] === 'inactive' ? $ar['days_inactive'].'d inactive' : ($ar['risk_reason'] === 'failing' ? $ar['avg_score'].'% avg' : 'Slow progress');
                        ?>
                            <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem;background:<?= $riskColor ?>08;border-radius:8px;margin-bottom:0.5rem;border-left:3px solid <?= $riskColor ?>;">
                                <div style="width:32px;height:32px;border-radius:50%;background:<?= $riskColor ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;flex-shrink:0;"><?= strtoupper(substr($ar['learner_name'],0,2)) ?></div>
                                <div style="flex:1;min-width:0;">
                                    <strong style="font-size:0.9rem;display:block;"><?= htmlspecialchars($ar['learner_name']) ?></strong>
                                    <span style="font-size:0.8rem;color:#999;"><?= htmlspecialchars(mb_substr($ar['course_title'],0,30)) ?></span>
                                </div>
                                <span style="font-size:0.75rem;padding:0.2rem 0.5rem;background:<?= $riskColor ?>;color:#fff;border-radius:4px;font-weight:500;"><?= $riskLabel ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($atRiskStudents) > 5): ?>
                            <p style="text-align:center;margin:0.5rem 0 0;font-size:0.85rem;color:#999;">+<?= count($atRiskStudents) - 5 ?> more at-risk students</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ==================== STUDENTS TAB ==================== -->
        <div class="tab-content" data-tab="tab-students">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                <!-- Student Leaderboard -->
                <div class="mode-card" style="grid-column:span 2;">
                    <h3><i class="fas fa-trophy" style="color:var(--primary);margin-right:0.5rem;"></i> Student Leaderboard</h3>
                    <?php if (empty($studentLeaderboard)): ?>
                        <p style="color:#999;text-align:center;padding:2rem;">No student data yet.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;margin-top:1rem;">
                            <table style="width:100%;border-collapse:collapse;font-size:0.95rem;">
                                <thead style="background:var(--bg-subtle);border-bottom:2px solid #ddd;">
                                    <tr>
                                        <th style="padding:1rem;text-align:center;width:50px;">#</th>
                                        <th style="padding:1rem;text-align:left;">Student</th>
                                        <th style="padding:1rem;text-align:center;">Enrolled</th>
                                        <th style="padding:1rem;text-align:center;">Completed</th>
                                        <th style="padding:1rem;text-align:center;">Avg Score</th>
                                        <th style="padding:1rem;text-align:center;">Last Active</th>
                                        <th style="padding:1rem;text-align:center;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($studentLeaderboard as $idx => $sl):
                                        $daysSinceActive = $sl['last_active'] ? floor((time() - strtotime($sl['last_active'])) / 86400) : 999;
                                        $statusColor = $daysSinceActive <= 3 ? 'var(--primary)' : ($daysSinceActive <= 7 ? 'var(--accent)' : 'var(--danger)');
                                        $statusText = $daysSinceActive <= 3 ? 'Active' : ($daysSinceActive <= 7 ? 'Recent' : 'Inactive');
                                        $medalColor = $idx === 0 ? 'var(--accent)' : ($idx === 1 ? 'var(--border)' : ($idx === 2 ? '#cd7f32' : 'var(--primary)'));
                                    ?>
                                        <tr style="border-bottom:1px solid #eee;">
                                            <td style="padding:0.75rem 1rem;text-align:center;">
                                                <span style="width:28px;height:28px;border-radius:50%;background:<?= $medalColor ?>;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;"><?= $idx + 1 ?></span>
                                            </td>
                                            <td style="padding:0.75rem 1rem;">
                                                <strong><?= htmlspecialchars($sl['learner_name']) ?></strong>
                                                <?php if (!empty($sl['learner_position'])): ?>
                                                    <div style="font-size:0.8rem;color:#999;"><?= htmlspecialchars($sl['learner_position']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding:0.75rem 1rem;text-align:center;"><?= $sl['courses_enrolled'] ?></td>
                                            <td style="padding:0.75rem 1rem;text-align:center;"><?= $sl['courses_completed'] ?></td>
                                            <td style="padding:0.75rem 1rem;text-align:center;font-weight:700;color:<?= $sl['avg_score'] >= 80 ? 'var(--primary)' : ($sl['avg_score'] >= 60 ? 'var(--accent)' : 'var(--danger)') ?>;"><?= $sl['avg_score'] ?>%</td>
                                            <td style="padding:0.75rem 1rem;text-align:center;color:#999;font-size:0.85rem;"><?= $sl['last_active'] ? date('M j', strtotime($sl['last_active'])) : 'Never' ?></td>
                                            <td style="padding:0.75rem 1rem;text-align:center;">
                                                <span style="padding:0.2rem 0.6rem;border-radius:4px;font-size:0.8rem;font-weight:500;background:<?= $statusColor ?>15;color:<?= $statusColor ?>;"><?= $statusText ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- At-Risk Detail -->
                <div class="mode-card" style="grid-column:span 2;<?= count($atRiskStudents) > 0 ? 'border-left:4px solid var(--danger);' : '' ?>">
                    <h3><i class="fas fa-exclamation-triangle" style="color:var(--danger);margin-right:0.5rem;"></i> Students Needing Attention</h3>
                    <?php if (empty($atRiskStudents)): ?>
                        <p style="color:var(--primary);text-align:center;padding:1.5rem;">No students flagged as at-risk.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;margin-top:1rem;">
                            <table style="width:100%;border-collapse:collapse;font-size:0.95rem;">
                                <thead style="background:var(--bg-subtle);border-bottom:2px solid #ddd;">
                                    <tr>
                                        <th style="padding:1rem;text-align:left;">Student</th>
                                        <th style="padding:1rem;text-align:left;">Course</th>
                                        <th style="padding:1rem;text-align:center;">Status</th>
                                        <th style="padding:1rem;text-align:center;">Avg Score</th>
                                        <th style="padding:1rem;text-align:center;">Days Inactive</th>
                                        <th style="padding:1rem;text-align:center;">Risk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($atRiskStudents as $ar):
                                        $riskColor = $ar['risk_reason'] === 'inactive' ? 'var(--danger)' : ($ar['risk_reason'] === 'failing' ? 'var(--accent)' : 'var(--accent)');
                                        $riskLabel = $ar['risk_reason'] === 'inactive' ? 'Inactive' : ($ar['risk_reason'] === 'failing' ? 'Failing' : 'Slow');
                                    ?>
                                        <tr style="border-bottom:1px solid #eee;background:<?= $riskColor ?>05;">
                                            <td style="padding:0.75rem 1rem;"><strong><?= htmlspecialchars($ar['learner_name']) ?></strong></td>
                                            <td style="padding:0.75rem 1rem;color:#666;"><?= htmlspecialchars(mb_substr($ar['course_title'],0,35)) ?></td>
                                            <td style="padding:0.75rem 1rem;text-align:center;font-size:0.85rem;color:<?= $ar['enrollment_status'] === 'in_progress' ? 'var(--accent)' : 'var(--accent)' ?>;"><?= ucfirst(str_replace('_',' ',$ar['enrollment_status'])) ?></td>
                                            <td style="padding:0.75rem 1rem;text-align:center;font-weight:600;color:<?= $ar['avg_score'] >= 70 ? 'var(--primary)' : 'var(--danger)' ?>;"><?= $ar['avg_score'] ?>%</td>
                                            <td style="padding:0.75rem 1rem;text-align:center;"><?= $ar['days_inactive'] ?>d</td>
                                            <td style="padding:0.75rem 1rem;text-align:center;">
                                                <span style="padding:0.2rem 0.6rem;border-radius:4px;font-size:0.8rem;font-weight:600;background:<?= $riskColor ?>;color:#fff;"><?= $riskLabel ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ==================== COURSES TAB ==================== -->
        <div class="tab-content" data-tab="tab-courses">
            <!-- Per-Course Engagement -->
            <div class="mode-card" style="margin-bottom:1.5rem;">
                <h3><i class="fas fa-book-open" style="color:var(--primary);margin-right:0.5rem;"></i> Course Engagement</h3>
                <?php if (empty($courseEngagement)): ?>
                    <p style="color:#999;text-align:center;padding:2rem;">No active courses.</p>
                <?php else: ?>
                    <?php foreach ($courseEngagement as $ce):
                        $lessonComp = $ce['total_lessons'] > 0 ? round(($ce['completed_lessons'] / $ce['total_lessons']) * 100) : 0;
                        $quizComp = $ce['total_quizzes'] > 0 ? round(($ce['quizzes_taken'] / max(1, $ce['total_quizzes'] * max(1, $stats['my_learners']))) * 100) : 0;
                    ?>
                        <div style="padding:1.25rem;background:var(--bg-subtle);border-radius:10px;margin-bottom:0.75rem;">
                            <strong style="font-size:1rem;"><?= htmlspecialchars($ce['title']) ?></strong>
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-top:0.75rem;">
                                <div>
                                    <div style="font-size:0.8rem;color:#999;margin-bottom:0.25rem;">Lessons Completed</div>
                                    <div style="display:flex;align-items:center;gap:0.5rem;">
                                        <div style="flex:1;background:var(--border);height:6px;border-radius:3px;overflow:hidden;"><div style="background:var(--primary);height:100%;width:<?= $lessonComp ?>%;"></div></div>
                                        <span style="font-size:0.85rem;font-weight:600;"><?= $ce['completed_lessons'] ?>/<?= $ce['total_lessons'] ?></span>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size:0.8rem;color:#999;margin-bottom:0.25rem;">Quizzes</div>
                                    <div style="font-weight:600;font-size:1.1rem;"><?= $ce['quizzes_taken'] ?> <span style="font-size:0.8rem;color:#999;font-weight:normal;">/ <?= $ce['total_quizzes'] ?> total</span></div>
                                </div>
                                <div>
                                    <div style="font-size:0.8rem;color:#999;margin-bottom:0.25rem;">Avg Quiz Score</div>
                                    <div style="font-weight:600;font-size:1.1rem;color:<?= $ce['avg_quiz_score'] >= 70 ? 'var(--primary)' : 'var(--danger)' ?>;"><?= $ce['avg_quiz_score'] ?>%</div>
                                </div>
                                <div>
                                    <div style="font-size:0.8rem;color:#999;margin-bottom:0.25rem;">Lesson Completion</div>
                                    <div style="font-weight:600;font-size:1.1rem;color:<?= $lessonComp >= 70 ? 'var(--primary)' : ($lessonComp >= 40 ? 'var(--accent)' : 'var(--danger)') ?>;"><?= $lessonComp ?>%</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Course Overview Table -->
            <div class="mode-card">
                <h3><i class="fas fa-table" style="color:var(--primary);margin-right:0.5rem;"></i> Course Overview</h3>
                <?php if (empty($coursePerformance)): ?>
                    <p style="color:#999;text-align:center;padding:2rem;">No courses yet.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;margin-top:1rem;">
                        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                            <thead style="background:var(--bg-subtle);border-bottom:2px solid #ddd;">
                                <tr>
                                    <th style="padding:0.75rem 1rem;text-align:left;">Course</th>
                                    <th style="padding:0.75rem 1rem;text-align:center;">Status</th>
                                    <th style="padding:0.75rem 1rem;text-align:center;">Enrolled</th>
                                    <th style="padding:0.75rem 1rem;text-align:center;">Completed</th>
                                    <th style="padding:0.75rem 1rem;text-align:center;">Active</th>
                                    <th style="padding:0.75rem 1rem;text-align:center;">Completion %</th>
                                    <th style="padding:0.75rem 1rem;text-align:center;">Avg Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coursePerformance as $cp):
                                    $cpRate = $cp['enrollment_count'] > 0 ? round(($cp['completed_count'] / $cp['enrollment_count']) * 100) : 0;
                                ?>
                                    <tr style="border-bottom:1px solid #eee;">
                                        <td style="padding:0.75rem 1rem;"><strong><?= htmlspecialchars(mb_substr($cp['title'],0,40)) ?></strong></td>
                                        <td style="padding:0.75rem 1rem;text-align:center;">
                                            <span style="padding:0.2rem 0.5rem;border-radius:4px;font-size:0.8rem;background:<?= $cp['status']==='active'?'var(--bg-subtle)':'var(--bg-subtle)' ?>;color:<?= $cp['status']==='active'?'var(--primary)':'var(--accent)' ?>;"><?= ucfirst($cp['status']) ?></span>
                                        </td>
                                        <td style="padding:0.75rem 1rem;text-align:center;"><?= $cp['enrollment_count'] ?></td>
                                        <td style="padding:0.75rem 1rem;text-align:center;color:var(--primary);font-weight:600;"><?= $cp['completed_count'] ?></td>
                                        <td style="padding:0.75rem 1rem;text-align:center;color:var(--accent);font-weight:600;"><?= $cp['active_count'] ?></td>
                                        <td style="padding:0.75rem 1rem;text-align:center;">
                                            <div style="display:flex;align-items:center;gap:0.4rem;justify-content:center;">
                                                <div style="width:50px;background:var(--border);height:5px;border-radius:3px;overflow:hidden;"><div style="background:var(--primary);height:100%;width:<?= $cpRate ?>%;"></div></div>
                                                <span><?= $cpRate ?>%</span>
                                            </div>
                                        </td>
                                        <td style="padding:0.75rem 1rem;text-align:center;font-weight:600;color:<?= $cp['avg_score']>=70?'var(--primary)':($cp['avg_score']>=50?'var(--accent)':'var(--danger)') ?>;"><?= $cp['avg_score'] ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==================== GRADES TAB ==================== -->
        <div class="tab-content" data-tab="tab-grades">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                <!-- Grade Distribution -->
                <div class="mode-card">
    <h3><i class="fas fa-chart-bar" style="color:var(--primary);margin-right:0.5rem;"></i> Grade Distribution</h3>
    <?php
    $gdTotal = $gradeDistribution['total'] ?? 0;
    $gA = $gradeDistribution['grade_a'] ?? 0;
    $gB = $gradeDistribution['grade_b'] ?? 0;
    $gC = $gradeDistribution['grade_c'] ?? 0;
    $gD = 0;
    $gF = $gradeDistribution['grade_f'] ?? 0;
    $gdMax = max(max($gA,$gB,$gC,$gD,$gF),1);
    $bluePalette=['#1a0052','var(--primary)','var(--accent)','rgba(81,70,183,0.6)','rgba(81,70,183,0.35)'];
    $gradeLabels=[['label'=>'A','desc'=>'90-100'],['label'=>'B','desc'=>'80-89'],['label'=>'C','desc'=>'70-79'],['label'=>'D','desc'=>'60-69'],['label'=>'F','desc'=>'<60']];
    $gradeCounts=[$gA,$gB,$gC,$gD,$gF];
    ?>
    <div style="display:flex;align-items:flex-end;gap:0.6rem;height:160px;padding:1rem 0.5rem 0;">
        <?php for($i=0;$i<5;$i++):
            $cnt=$gradeCounts[$i];
            $barH=$gdMax>0?($cnt/$gdMax)*100:0;
            $pct=$gdTotal>0?round(($cnt/$gdTotal)*100):0;
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.25rem;height:100%;justify-content:flex-end;">
            <span style="font-size:0.75rem;font-weight:800;color:var(--text);"><?= $cnt ?></span>
            <span style="font-size:0.6rem;color:rgba(32,0,130,0.4);"><?= $pct ?>%</span>
            <div style="width:100%;height:<?= $barH ?>%;min-height:4px;background:<?= $bluePalette[$i] ?>;border-radius:5px 5px 2px 2px;"></div>
        </div>
        <?php endfor; ?>
    </div>
    <div style="display:flex;gap:0.6rem;padding:0.4rem 0.5rem 0;">
        <?php for($i=0;$i<5;$i++): ?>
        <div style="flex:1;text-align:center;">
            <div style="width:10px;height:10px;border-radius:2px;background:<?= $bluePalette[$i] ?>;margin:0 auto 0.15rem;"></div>
            <div style="font-size:0.7rem;font-weight:800;color:var(--text);"><?= $gradeLabels[$i]["label"] ?></div>
            <div style="font-size:0.55rem;color:rgba(32,0,130,0.4);"><?= $gradeLabels[$i]["desc"] ?></div>
        </div>
        <?php endfor; ?>
    </div>
    <?php if($gdTotal===0): ?><p style="color:#999;text-align:center;margin-top:0.5rem;">No grades recorded.</p><?php endif; ?>
</div>

                <!-- Score Summary -->
                <div class="mode-card">
                    <h3><i class="fas fa-chart-line" style="color:var(--primary);margin-right:0.5rem;"></i> Score Summary</h3>
                    <div style="padding:1.5rem 0;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div style="text-align:center;padding:1rem;background:var(--bg-subtle);border-radius:10px;">
                                <div style="font-size:2rem;font-weight:700;color:var(--primary);"><?= $stats['avg_score'] ?>%</div>
                                <div style="font-size:0.85rem;color:#999;">Average Score</div>
                            </div>
                            <div style="text-align:center;padding:1rem;background:var(--bg-subtle);border-radius:10px;">
                                <div style="font-size:2rem;font-weight:700;color:var(--primary);"><?= $stats['pass_rate'] ?>%</div>
                                <div style="font-size:0.85rem;color:#999;">Pass Rate</div>
                            </div>
                            <div style="text-align:center;padding:1rem;background:var(--bg-subtle);border-radius:10px;">
                                <div style="font-size:2rem;font-weight:700;color:var(--accent);"><?= $gdTotal ?></div>
                                <div style="font-size:0.85rem;color:#999;">Total Grades</div>
                            </div>
                            <div style="text-align:center;padding:1rem;background:var(--bg-subtle);border-radius:10px;">
                                <div style="font-size:2rem;font-weight:700;color:var(--accent);"><?= $stats['total_quizzes'] ?></div>
                                <div style="font-size:0.85rem;color:#999;">Quizzes Created</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Grades -->
                <div class="mode-card" style="grid-column:span 2;">
                    <h3><i class="fas fa-clock" style="color:var(--primary);margin-right:0.5rem;"></i> Recent Grades</h3>
                    <?php if (empty($recentGrades)): ?>
                        <p style="color:#999;text-align:center;padding:2rem;">No grades recorded yet.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;margin-top:1rem;">
                            <table style="width:100%;border-collapse:collapse;font-size:0.95rem;">
                                <thead style="background:var(--bg-subtle);border-bottom:2px solid #ddd;">
                                    <tr>
                                        <th style="padding:1rem;text-align:left;">Student</th>
                                        <th style="padding:1rem;text-align:left;">Course</th>
                                        <th style="padding:1rem;text-align:center;">Score</th>
                                        <th style="padding:1rem;text-align:center;">Status</th>
                                        <th style="padding:1rem;text-align:center;">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentGrades as $rg): ?>
                                        <tr style="border-bottom:1px solid #eee;">
                                            <td style="padding:0.75rem 1rem;"><strong><?= htmlspecialchars($rg['learner_name']) ?></strong></td>
                                            <td style="padding:0.75rem 1rem;color:#666;"><?= htmlspecialchars(mb_substr($rg['course_title'],0,35)) ?></td>
                                            <td style="padding:0.75rem 1rem;text-align:center;font-weight:700;color:<?= $rg['final_score']>=70?'var(--primary)':($rg['final_score']>=50?'var(--accent)':'var(--danger)') ?>;"><?= round($rg['final_score'],1) ?>%</td>
                                            <td style="padding:0.75rem 1rem;text-align:center;">
                                                <span style="padding:0.2rem 0.6rem;border-radius:4px;font-size:0.8rem;font-weight:500;background:<?= $rg['status']==='passed'?'var(--bg-subtle)':'var(--bg-subtle)' ?>;color:<?= $rg['status']==='passed'?'var(--primary)':'var(--danger)' ?>;"><?= ucfirst($rg['status']) ?></span>
                                            </td>
                                            <td style="padding:0.75rem 1rem;text-align:center;color:#999;font-size:0.9rem;"><?= $rg['issued_at'] ? date('M j, Y', strtotime($rg['issued_at'])) : '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
