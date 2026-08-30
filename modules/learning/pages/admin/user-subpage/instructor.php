<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeId = (int)($_GET['id'] ?? 0);
$profile = null;
$courses = [];
$recentActivity = [];

try {
    $pdo = (new Database())->getConnection();

    // Fetch employee profile
    $stmt = $pdo->prepare("SELECT * FROM em_employees WHERE employee_id = :eid");
    $stmt->execute([':eid' => $employeeId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    // profile not found handled below

    // Fetch courses taught by this instructor
    $courseStmt = $pdo->prepare("
        SELECT c.*,
               COUNT(DISTINCT e.id) AS enrollment_count,
               SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
               ROUND(COALESCE(AVG(g.final_score), 0), 1) AS avg_score
        FROM ld_course c
        LEFT JOIN ld_enrollment e ON e.course_id = c.id
        LEFT JOIN ld_grade g ON g.course_id = c.id
        WHERE c.instructor_id = :eid AND c.status != 'archived'
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $courseStmt->execute([':eid' => $employeeId]);
    $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch learners enrolled in instructor's courses
    $learnerStmt = $pdo->prepare("
        SELECT DISTINCT emp.employee_id, emp.first_name, emp.last_name, emp.email,
               e.status AS enrollment_status, e.enrolled_at, e.last_accessed_at,
               c.title AS course_title, c.id AS course_id
        FROM em_employees emp
        JOIN ld_enrollment e ON e.learner_id = emp.employee_id
        JOIN ld_course c ON c.id = e.course_id
        WHERE c.instructor_id = :eid
        ORDER BY e.enrolled_at DESC
    ");
    $learnerStmt->execute([':eid' => $employeeId]);
    $learners = $learnerStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent activity from audit log
    $actStmt = $pdo->prepare("
        SELECT al.*
        FROM ld_audit_log al
        WHERE al.user_id = :eid
        ORDER BY al.created_at DESC
        LIMIT 20
    ");
    $actStmt->execute([':eid' => $employeeId]);
    $recentActivity = $actStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $profile = null;
    $courses = [];
    $learners = [];
    $recentActivity = [];
}

function instTimeAgo($dt) {
    if (!$dt) return 'N/A';
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 604800) return floor($d / 86400) . 'd ago';
    return date('M j, Y', strtotime($dt));
}

$initials = $profile ? strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1)) : '??';
$fullName = $profile ? htmlspecialchars($profile['first_name'] . ' ' . ($profile['middle_name'] ? $profile['middle_name'] . ' ' : '') . $profile['last_name']) : 'Unknown';
?>
<div class="module-header">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
        <div>
            <h1 class="module-header-title">Instructor Profile</h1>
            <p class="module-header-subtitle">Detailed view of instructor profile, courses, and activity.</p>
        </div>
        <a href="?page=admin/user" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; border:1px solid rgba(32,0,130,0.2); border-radius:999px; font-size:0.85rem; font-weight:700; color:var(--primary); text-decoration:none; white-space:nowrap;">
            <i class="fas fa-arrow-left" style="font-size:0.8rem;"></i> Back to Users
        </a>
    </div>
</div>

<div class="module-content">
    <?php if (!$profile): ?>
        <div class="mode-card">
            <div class="content-card-body">
                <h3>Instructor not found</h3>
                <p>The requested instructor could not be found. <a href="?page=admin/user">Go back to User Management</a></p>
            </div>
        </div>
    <?php else: ?>
        <!-- Profile Card -->
        <div style="display:grid; grid-template-columns:300px 1fr; gap:1.5rem; margin-bottom:2rem;">
            <div class="mode-card" style="text-align:center; padding:2rem;">
                <div style="width:100px; height:100px; border-radius:50%; background:linear-gradient(135deg, rgba(32,0,130,0.9), rgba(91,85,255,0.75)); color:var(--surface); display:flex; align-items:center; justify-content:center; font-size:2.2rem; font-weight:800; margin:0 auto 1rem;"><?= $initials ?></div>
                <h2 style="margin:0 0 0.25rem; color:var(--text);"><?= $fullName ?></h2>
                <p style="margin:0 0 0.5rem; color:var(--primary); font-weight:600;">Instructor</p>
                <p style="margin:0 0 1rem; color:rgba(32,0,130,0.6); font-size:0.9rem;"><?= htmlspecialchars($profile['email'] ?? '') ?></p>
                <div style="display:flex; justify-content:center; gap:1rem; flex-wrap:wrap; margin-top:1rem;">
                    <div style="padding:0.5rem 1rem; border-radius:10px; background:rgba(32,0,130,0.06);">
                        <div style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Courses</div>
                        <div style="font-size:1.4rem; font-weight:800; color:var(--text);"><?= count($courses) ?></div>
                    </div>
                    <div style="padding:0.5rem 1rem; border-radius:10px; background:rgba(16,185,129,0.06);">
                        <div style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:#10b981; font-weight:700;">Learners</div>
                        <div style="font-size:1.4rem; font-weight:800; color:var(--text);"><?= count($learners) ?></div>
                    </div>
                </div>
            </div>

            <!-- Info Panel -->
            <div class="mode-card">
                <h3 style="margin:0 0 1rem; color:var(--text);">Personal Information</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Employee Code</div>
                        <div style="margin-top:0.3rem; color:var(--text);"><?= htmlspecialchars($profile['employee_code'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Email</div>
                        <div style="margin-top:0.3rem; color:var(--text);"><?= htmlspecialchars($profile['email'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Mobile</div>
                        <div style="margin-top:0.3rem; color:var(--text);"><?= htmlspecialchars($profile['mobile_no'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Employment Status</div>
                        <div style="margin-top:0.3rem; color:var(--text);"><?= htmlspecialchars($profile['employment_status'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Hire Date</div>
                        <div style="margin-top:0.3rem; color:var(--text);"><?= $profile['hire_date'] ? date('M j, Y', strtotime($profile['hire_date'])) : 'N/A' ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Ranking</div>
                        <div style="margin-top:0.3rem; color:var(--text);"><?= htmlspecialchars($profile['ranking'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Courses Section -->
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h3 style="margin:0 0 1rem; color:var(--text);">Courses Taught (<?= count($courses) ?>)</h3>
            <?php if (empty($courses)): ?>
                <p style="color:rgba(32,0,130,0.5);">No courses assigned yet.</p>
            <?php else: ?>
                <div style="display:grid; gap:0.75rem;">
                    <?php foreach ($courses as $c): ?>
                        <div style="display:grid; grid-template-columns:1fr auto auto auto; gap:1rem; align-items:center; padding:1rem 1.25rem; border:1px solid rgba(32,0,130,0.1); border-radius:12px; background:rgba(32,0,130,0.02);">
                            <div>
                                <div style="font-weight:700; color:var(--text);"><?= htmlspecialchars($c['title']) ?></div>
                                <div style="font-size:0.85rem; color:rgba(32,0,130,0.6);"><?= htmlspecialchars($c['category'] ?? 'Uncategorized') ?></div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:0.7rem; text-transform:uppercase; color:var(--primary); font-weight:700;">Enrolled</div>
                                <div style="font-size:1.1rem; font-weight:800; color:var(--text);"><?= $c['enrollment_count'] ?></div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:0.7rem; text-transform:uppercase; color:#10b981; font-weight:700;">Completed</div>
                                <div style="font-size:1.1rem; font-weight:800; color:var(--text);"><?= $c['completed_count'] ?></div>
                            </div>
                            <div>
                                <span style="padding:0.35rem 0.7rem; border-radius:999px; font-size:0.75rem; font-weight:700; background:<?= $c['status'] === 'active' ? 'rgba(16,185,129,0.1)' : ($c['status'] === 'draft' ? 'rgba(245,158,11,0.1)' : 'rgba(107,114,128,0.1)') ?>; color:<?= $c['status'] === 'active' ? '#10b981' : ($c['status'] === 'draft' ? '#f59e0b' : '#6b7280') ?>;">
                                    <?= ucfirst($c['status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Learners Section -->
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h3 style="margin:0 0 1rem; color:var(--text);">Assigned Learners (<?= count($learners) ?>)</h3>
            <?php if (empty($learners)): ?>
                <p style="color:rgba(32,0,130,0.5);">No learners enrolled in your courses yet.</p>
            <?php else: ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1rem;">
                    <?php foreach ($learners as $lrn):
                        $lInitials = strtoupper(substr($lrn['first_name'], 0, 1) . substr($lrn['last_name'], 0, 1));
                        $lName = htmlspecialchars($lrn['first_name'] . ' ' . $lrn['last_name']);
                        $statusColor = $lrn['enrollment_status'] === 'completed' ? '#10b981' : ($lrn['enrollment_status'] === 'in_progress' ? '#3b82f6' : '#f59e0b');
                    ?>
                        <div style="display:flex; gap:0.75rem; padding:1rem; border:1px solid rgba(32,0,130,0.1); border-radius:12px; background:var(--surface, #fff); align-items:center;">
                            <div style="width:42px; height:42px; min-width:42px; border-radius:50%; background:linear-gradient(135deg, rgba(16,185,129,0.9), rgba(52,211,153,0.75)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem;"><?= $lInitials ?></div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= $lName ?></div>
                                <div style="font-size:0.8rem; color:rgba(32,0,130,0.5); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($lrn['course_title']) ?></div>
                            </div>
                            <span style="padding:0.25rem 0.6rem; border-radius:999px; font-size:0.7rem; font-weight:700; background:<?= $statusColor ?>15; color:<?= $statusColor ?>; white-space:nowrap;">
                                <?= ucfirst(str_replace('_', ' ', $lrn['enrollment_status'])) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Activity Timeline -->
        <div class="mode-card">
            <h3 style="margin:0 0 1rem; color:var(--text);">Recent Activity</h3>
            <?php if (empty($recentActivity)): ?>
                <p style="color:rgba(32,0,130,0.5);">No recent activity recorded.</p>
            <?php else: ?>
                <div style="position:relative; padding-left:1.5rem;">
                    <div style="position:absolute; left:0.5rem; top:0; bottom:0; width:2px; background:rgba(32,0,130,0.12);"></div>
                    <?php foreach ($recentActivity as $act): ?>
                        <div style="position:relative; padding:0.75rem 0 0.75rem 1.5rem;">
                            <div style="position:absolute; left:-0.25rem; top:1rem; width:10px; height:10px; border-radius:50%; background:var(--primary); border:2px solid var(--surface);"></div>
                            <div style="font-weight:600; color:var(--text);"><?= htmlspecialchars($act['action'] ?? 'Activity') ?></div>
                            <div style="font-size:0.85rem; color:rgba(32,0,130,0.6);"><?= htmlspecialchars(($act['item_type'] ? $act['item_type'] . ' #' . $act['reference_id'] : '') . ' ' . ($act['details'] ?? '')) ?></div>
                            <div style="font-size:0.75rem; color:rgba(32,0,130,0.4); margin-top:0.2rem;"><?= instTimeAgo($act['created_at']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
