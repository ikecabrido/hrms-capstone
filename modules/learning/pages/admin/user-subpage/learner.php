<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeId = (int)($_GET['id'] ?? 0);
$profile = null;
$enrollments = [];
$quizAttempts = [];
$certificates = [];

try {
    $pdo = (new Database())->getConnection();

    // Fetch employee profile
    $stmt = $pdo->prepare("SELECT * FROM em_employees WHERE employee_id = :eid");
    $stmt->execute([':eid' => $employeeId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    // profile not found handled below

    // Fetch enrollments with course info and progress
    $enrollStmt = $pdo->prepare("
        SELECT e.*, c.title AS course_title, c.category, c.thumbnail_path, c.instructor_id,
               emp.first_name AS inst_first, emp.last_name AS inst_last,
               g.final_score, g.status AS grade_status,
               (SELECT COUNT(*) FROM ld_progress p WHERE p.enrollment_id = e.id AND p.status = 'completed') AS completed_items,
               (SELECT COUNT(*) FROM ld_progress p WHERE p.enrollment_id = e.id) AS total_items
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        LEFT JOIN ld_grade g ON g.learner_id = e.learner_id AND g.course_id = e.course_id
        WHERE e.learner_id = :eid
        ORDER BY e.enrolled_at DESC
    ");
    $enrollStmt->execute([':eid' => $employeeId]);
    $enrollments = $enrollStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch quiz attempts
    $quizStmt = $pdo->prepare("
        SELECT qa.*, q.title AS quiz_title, m.title AS module_title, c.title AS course_title
        FROM ld_quiz_attempt qa
        JOIN ld_quiz q ON q.id = qa.quiz_id
        LEFT JOIN ld_module m ON m.id = q.module_id
        LEFT JOIN ld_course c ON c.id = m.course_id
        WHERE qa.learner_id = :eid
        ORDER BY qa.attempted_at DESC
        LIMIT 20
    ");
    $quizStmt->execute([':eid' => $employeeId]);
    $quizAttempts = $quizStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch certificates
    $certStmt = $pdo->prepare("
        SELECT cert.*, c.title AS course_title
        FROM ld_certificate cert
        JOIN ld_course c ON c.id = cert.course_id
        WHERE cert.learner_id = :eid
        ORDER BY cert.issued_at DESC
    ");
    $certStmt->execute([':eid' => $employeeId]);
    $certificates = $certStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch knowledge transfer plans for this employee
    $ktStmt = $pdo->prepare("
        SELECT p.id, p.employee_id, p.successor_id, p.start_date, p.end_date, p.status, p.created_at,
               CONCAT(e.first_name, ' ', IFNULL(CONCAT(e.middle_name, ' '), ''), e.last_name) AS employee_name,
               CONCAT(s.first_name, ' ', IFNULL(CONCAT(s.middle_name, ' '), ''), s.last_name) AS successor_name,
               (SELECT COUNT(*) FROM exit_knowledge_transfer_items WHERE plan_id = p.id) AS item_count,
               (SELECT COUNT(*) FROM exit_knowledge_transfer_items WHERE plan_id = p.id AND status = 'completed') AS items_done
        FROM exit_knowledge_transfer_plans p
        LEFT JOIN em_employees e ON p.employee_id = e.employee_id
        LEFT JOIN em_employees s ON p.successor_id = s.employee_id
        WHERE p.employee_id = :eid
        ORDER BY p.created_at DESC
    ");
    $ktStmt->execute([':eid' => $employeeId]);
    $ktPlans = $ktStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    DbError::capture($e, 'admin/user-subpage/learner');
    $profile = null;
    $enrollments = [];
    $quizAttempts = [];
    $certificates = [];
    $ktPlans = [];
}

function lrnTimeAgo($dt) {
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

// Calculate stats
$totalEnrolled = count($enrollments);
$completedEnrollments = count(array_filter($enrollments, fn($e) => $e['status'] === 'completed'));
$inProgressEnrollments = count(array_filter($enrollments, fn($e) => $e['status'] === 'in_progress'));
$avgScore = 0;
$scoredEnrollments = array_filter($enrollments, fn($e) => $e['final_score'] !== null);
if (count($scoredEnrollments) > 0) {
    $avgScore = round(array_sum(array_map(fn($e) => $e['final_score'], $scoredEnrollments)) / count($scoredEnrollments), 1);
}
$quizPassed = count(array_filter($quizAttempts, fn($q) => $q['passed']));
?>
<div class="module-header">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
        <div>
            <h1 class="module-header-title">Learner Profile</h1>
            <p class="module-header-subtitle">Detailed view of learner enrollment, progress, and performance.</p>
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
                <h3>Learner not found</h3>
                <p>The requested learner could not be found. <a href="?page=admin/user">Go back to User Management</a></p>
            </div>
        </div>
    <?php else: ?>
        <!-- Profile Card -->
        <div style="display:grid; grid-template-columns:300px 1fr; gap:1.5rem; margin-bottom:2rem;">
            <div class="mode-card" style="text-align:center; padding:2rem;">
                <div style="width:100px; height:100px; border-radius:50%; background:linear-gradient(135deg, rgba(16,185,129,0.9), rgba(52,211,153,0.75)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:2.2rem; font-weight:800; margin:0 auto 1rem;"><?= $initials ?></div>
                <h2 style="margin:0 0 0.25rem; color:var(--text);"><?= $fullName ?></h2>
                <p style="margin:0 0 0.5rem; color:#10b981; font-weight:600;">Learner</p>
                <p style="margin:0 0 1rem; color:rgba(32,0,130,0.6); font-size:0.9rem;"><?= htmlspecialchars($profile['email'] ?? '') ?></p>
                <div style="display:flex; justify-content:center; gap:0.75rem; flex-wrap:wrap; margin-top:1rem;">
                    <div style="padding:0.5rem 1rem; border-radius:10px; background:rgba(32,0,130,0.06);">
                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Enrolled</div>
                        <div style="font-size:1.3rem; font-weight:800; color:var(--text);"><?= $totalEnrolled ?></div>
                    </div>
                    <div style="padding:0.5rem 1rem; border-radius:10px; background:rgba(16,185,129,0.06);">
                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:#10b981; font-weight:700;">Done</div>
                        <div style="font-size:1.3rem; font-weight:800; color:var(--text);"><?= $completedEnrollments ?></div>
                    </div>
                    <div style="padding:0.5rem 1rem; border-radius:10px; background:rgba(59,130,246,0.06);">
                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:#3b82f6; font-weight:700;">Avg Score</div>
                        <div style="font-size:1.3rem; font-weight:800; color:var(--text);"><?= $avgScore ?>%</div>
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
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Department</div>
                        <div style="margin-top:0.3rem; color:var(--text);"><?= htmlspecialchars($profile['department_id'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Employment Status</div>
                        <div style="margin-top:0.3rem; color:var(--text);"><?= htmlspecialchars($profile['employment_status'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Hire Date</div>
                        <div style="margin-top:0.3rem; color:var(--text);"><?= $profile['hire_date'] ? date('M j, Y', strtotime($profile['hire_date'])) : 'N/A' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrollments Section -->
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h3 style="margin:0 0 1rem; color:var(--text);">Course Enrollments (<?= $totalEnrolled ?>)</h3>
            <?php if (empty($enrollments)): ?>
                <p style="color:rgba(32,0,130,0.5);">No course enrollments yet.</p>
            <?php else: ?>
                <div style="display:grid; gap:0.75rem;">
                    <?php foreach ($enrollments as $en):
                        $progress = $en['total_items'] > 0 ? round(($en['completed_items'] / $en['total_items']) * 100) : 0;
                        $statusColor = $en['status'] === 'completed' ? '#10b981' : ($en['status'] === 'in_progress' ? '#3b82f6' : '#f59e0b');
                        $instName = $en['inst_first'] ? htmlspecialchars($en['inst_first'] . ' ' . $en['inst_last']) : 'N/A';
                    ?>
                        <div style="padding:1.25rem; border:1px solid rgba(32,0,130,0.1); border-radius:12px; background:var(--surface, #fff);">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:0.75rem;">
                                <div>
                                    <div style="font-weight:700; color:var(--text); font-size:1rem;"><?= htmlspecialchars($en['course_title']) ?></div>
                                    <div style="font-size:0.85rem; color:rgba(32,0,130,0.5);">by <?= $instName ?></div>
                                </div>
                                <span style="padding:0.3rem 0.65rem; border-radius:999px; font-size:0.72rem; font-weight:700; background:<?= $statusColor ?>15; color:<?= $statusColor ?>; white-space:nowrap;">
                                    <?= ucfirst(str_replace('_', ' ', $en['status'])) ?>
                                </span>
                            </div>
                            <!-- Progress Bar -->
                            <div style="margin-bottom:0.5rem;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:0.3rem;">
                                    <span style="font-size:0.75rem; color:rgba(32,0,130,0.6);">Progress</span>
                                    <span style="font-size:0.75rem; font-weight:700; color:var(--text);"><?= $progress ?>%</span>
                                </div>
                                <div style="height:6px; border-radius:999px; background:rgba(32,0,130,0.08); overflow:hidden;">
                                    <div style="height:100%; width:<?= $progress ?>%; border-radius:999px; background:linear-gradient(90deg, var(--primary), var(--accent));"></div>
                                </div>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                                <div style="display:flex; gap:1rem; font-size:0.8rem; color:rgba(32,0,130,0.6);">
                                    <?php if ($en['final_score'] !== null): ?>
                                        <span>Score: <strong style="color:var(--text);"><?= $en['final_score'] ?>%</strong></span>
                                    <?php endif; ?>
                                    <span>Items: <?= $en['completed_items'] ?>/<?= $en['total_items'] ?></span>
                                </div>
                                <span style="font-size:0.75rem; color:rgba(32,0,130,0.4);">Enrolled <?= lrnTimeAgo($en['enrolled_at']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quiz Attempts Section -->
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h3 style="margin:0 0 1rem; color:var(--text);">Quiz Attempts (<?= count($quizAttempts) ?>)</h3>
            <?php if (empty($quizAttempts)): ?>
                <p style="color:rgba(32,0,130,0.5);">No quiz attempts recorded yet.</p>
            <?php else: ?>
                <div style="display:grid; gap:0.5rem;">
                    <div style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr; gap:0.75rem; padding:0.6rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; border-bottom:2px solid rgba(32,0,130,0.1);">
                        <span>Quiz</span>
                        <span>Score</span>
                        <span>Items</span>
                        <span>Result</span>
                        <span>Date</span>
                    </div>
                    <?php foreach ($quizAttempts as $qa):
                        $passColor = $qa['passed'] ? '#10b981' : '#ef4444';
                    ?>
                        <div style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr; gap:0.75rem; padding:0.7rem 1rem; border:1px solid rgba(32,0,130,0.08); border-radius:8px; align-items:center;">
                            <div>
                                <div style="font-weight:600; color:var(--text); font-size:0.9rem;"><?= htmlspecialchars($qa['quiz_title']) ?></div>
                                <div style="font-size:0.75rem; color:rgba(32,0,130,0.5);"><?= htmlspecialchars($qa['course_title'] ?? '') ?></div>
                            </div>
                            <div style="font-weight:700; color:var(--text);"><?= $qa['score'] ?>%</div>
                            <div style="font-size:0.85rem; color:rgba(32,0,130,0.6);"><?= $qa['total_items'] ?></div>
                            <div>
                                <span style="padding:0.2rem 0.5rem; border-radius:999px; font-size:0.7rem; font-weight:700; background:<?= $passColor ?>15; color:<?= $passColor ?>;">
                                    <?= $qa['passed'] ? 'Passed' : 'Failed' ?>
                                </span>
                            </div>
                            <div style="font-size:0.8rem; color:rgba(32,0,130,0.5);"><?= lrnTimeAgo($qa['attempted_at']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Certificates Section -->
        <div class="mode-card">
            <h3 style="margin:0 0 1rem; color:var(--text);">Certificates (<?= count($certificates) ?>)</h3>
            <?php if (empty($certificates)): ?>
                <p style="color:rgba(32,0,130,0.5);">No certificates earned yet.</p>
            <?php else: ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1rem;">
                    <?php foreach ($certificates as $cert): ?>
                        <div style="padding:1.25rem; border:2px solid rgba(16,185,129,0.3); border-radius:12px; background:rgba(16,185,129,0.03); text-align:center;">
                            <div style="font-size:2rem; margin-bottom:0.5rem; color:#10b981;"><i class="fas fa-award"></i></div>
                            <div style="font-weight:700; color:var(--text);"><?= htmlspecialchars($cert['course_title']) ?></div>
                            <div style="font-size:0.8rem; color:rgba(32,0,130,0.5); margin-top:0.3rem;">Issued <?= $cert['issued_at'] ? date('M j, Y', strtotime($cert['issued_at'])) : 'N/A' ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>


        <!-- Knowledge Transfer Section -->
        <div class="mode-card" style="margin-top:1.5rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
                <h3 style="margin:0; color:var(--text);"><i class="fas fa-exchange-alt" style="color:var(--primary); margin-right:0.4rem;"></i>Knowledge Transfer</h3>
                <button id="kt-create-btn" style="padding:0.45rem 0.9rem; background:var(--primary); color:#fff; border:none; border-radius:999px; font-size:0.8rem; font-weight:700; cursor:pointer;">
                    <i class="fas fa-plus" style="margin-right:0.3rem;"></i>Create KT Plan
                </button>
            </div>
            <?php if (empty($ktPlans)): ?>
                <div style="text-align:center; padding:2rem; color:rgba(32,0,130,0.4);">
                    <i class="fas fa-exchange-alt" style="font-size:1.5rem; display:block; margin-bottom:0.5rem; opacity:0.5;"></i>
                    <p style="margin:0; font-size:0.88rem;">No knowledge transfer plans for this employee yet.</p>
                </div>
            <?php else: ?>
                <div style="display:grid; gap:0.75rem;">
                    <?php foreach ($ktPlans as $ktp): 
                        $ktStatusColor = $ktp['status'] === 'active' ? '#059669' : ($ktp['status'] === 'completed' ? '#2563eb' : '#dc3545');
                        $ktEmpName = $ktp['employee_name'] ?? 'N/A';
                        $ktSuccessorName = $ktp['successor_name'] ?? '—';
                        $ktItemsDone = (int) ($ktp['items_done'] ?? 0);
                        $ktItemsTotal = (int) ($ktp['item_count'] ?? 0);
                    ?>
                        <div style="display:flex; align-items:center; gap:1rem; padding:1rem; background:rgba(32,0,130,0.03); border:1px solid rgba(32,0,130,0.08); border-radius:10px;">
                            <div style="width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg, rgba(32,0,130,0.8), rgba(91,85,255,0.6)); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="fas fa-exchange-alt" style="font-size:1rem;"></i>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:700; color:var(--text); font-size:0.92rem;"><?= htmlspecialchars($ktEmpName) ?></div>
                                <div style="font-size:0.8rem; color:rgba(32,0,130,0.5); margin-top:0.2rem;">
                                    Successor: <?= htmlspecialchars($ktSuccessorName) ?> &bull; 
                                    <?= date('M j, Y', strtotime($ktp['start_date'])) ?> – <?= date('M j, Y', strtotime($ktp['end_date'])) ?> &bull; 
                                    <?= $ktItemsDone ?>/<?= $ktItemsTotal ?> items done
                                </div>
                            </div>
                            <span style="padding:0.25rem 0.6rem; border-radius:999px; font-size:0.7rem; font-weight:700; background:<?= $ktStatusColor ?>15; color:<?= $ktStatusColor ?>; white-space:nowrap;">
                                <?= ucfirst($ktp['status']) ?>
                            </span>
                            <button class="kt-view-btn" data-id="<?= $ktp['id'] ?>" style="padding:0.35rem 0.7rem; font-size:0.72rem; background:rgba(32,0,130,0.08); color:var(--primary); border:1px solid rgba(32,0,130,0.15); border-radius:999px; cursor:pointer; font-weight:600; white-space:nowrap;">
                                <i class="fas fa-eye" style="margin-right:0.2rem;"></i>View
                            </button>
                            <button class="kt-edit-btn" data-id="<?= $ktp['id'] ?>" style="padding:0.35rem 0.7rem; font-size:0.72rem; background:rgba(245,158,11,0.1); color:#d97706; border:1px solid rgba(245,158,11,0.2); border-radius:999px; cursor:pointer; font-weight:600; white-space:nowrap;">
                                <i class="fas fa-pen" style="margin-right:0.2rem;"></i>Edit
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- KT Create/Edit Modal -->
<div id="kt-modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); backdrop-filter:blur(3px); z-index:10000; align-items:center; justify-content:center;">
    <div style="background:var(--surface); border-radius:18px; width:90%; max-width:520px; box-shadow:0 20px 60px rgba(0,0,0,0.25); padding:1.5rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
            <h3 id="kt-modal-title" style="margin:0; font-size:1.1rem; font-weight:800; color:var(--text);">Create Knowledge Transfer Plan</h3>
            <button id="kt-modal-close" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--muted);"><i class="fas fa-times"></i></button>
        </div>
        <div style="display:grid; gap:1rem;">
            <div>
                <label style="display:block; font-size:0.72rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.35rem;">Employee</label>
                <input type="text" id="kt-employee-name" readonly style="width:100%; padding:0.6rem 0.75rem; border:1.5px solid rgba(32,0,130,0.1); border-radius:8px; font-size:0.88rem; background:rgba(32,0,130,0.03); outline:none;" />
            </div>
            <div>
                <label style="display:block; font-size:0.72rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.35rem;">Successor (person receiving the transfer)</label>
                <select id="kt-successor" style="width:100%; padding:0.6rem 0.75rem; border:1.5px solid rgba(32,0,130,0.1); border-radius:8px; font-size:0.88rem; outline:none; background:#fff;">
                    <option value="">Select successor...</option>
                </select>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div>
                    <label style="display:block; font-size:0.72rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.35rem;">Start Date</label>
                    <input type="date" id="kt-start-date" style="width:100%; padding:0.6rem 0.75rem; border:1.5px solid rgba(32,0,130,0.1); border-radius:8px; font-size:0.88rem; outline:none;" />
                </div>
                <div>
                    <label style="display:block; font-size:0.72rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.35rem;">End Date</label>
                    <input type="date" id="kt-end-date" style="width:100%; padding:0.6rem 0.75rem; border:1.5px solid rgba(32,0,130,0.1); border-radius:8px; font-size:0.88rem; outline:none;" />
                </div>
            </div>
            <div>
                <label style="display:block; font-size:0.72rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.35rem;">Status</label>
                <select id="kt-status" style="width:100%; padding:0.6rem 0.75rem; border:1.5px solid rgba(32,0,130,0.1); border-radius:8px; font-size:0.88rem; outline:none; background:#fff;">
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <div style="display:flex; gap:0.6rem; justify-content:flex-end; margin-top:1.5rem;">
            <button type="button" id="kt-modal-cancel" style="padding:0.5rem 1rem; background:transparent; color:var(--text); border:1.5px solid rgba(32,0,130,0.15); border-radius:999px; font-weight:700; font-size:0.82rem; cursor:pointer;">Cancel</button>
            <button type="button" id="kt-modal-save" style="padding:0.5rem 1rem; background:var(--primary); color:var(--surface); border:none; border-radius:999px; font-weight:700; font-size:0.82rem; cursor:pointer;">Save Plan</button>
        </div>
    </div>
</div>

<script>
(function() {
    var employeeId = <?= $employeeId ?>;
    var employeeName = '<?= addslashes($fullName) ?>';
    var editingPlanId = null;

    var successorSelect = document.getElementById('kt-successor');
    var startDateInput = document.getElementById('kt-start-date');
    var endDateInput = document.getElementById('kt-end-date');
    var statusInput = document.getElementById('kt-status');
    var modalOverlay = document.getElementById('kt-modal-overlay');
    var modalTitle = document.getElementById('kt-modal-title');
    var saveBtn = document.getElementById('kt-modal-save');
    var cancelBtn = document.getElementById('kt-modal-cancel');
    var closeBtn = document.getElementById('kt-modal-close');
    var empNameInput = document.getElementById('kt-employee-name');

    fetch('../../modules/exit/pages/ajax/get-employees.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            data.employees.forEach(function(emp) {
                if (emp.employee_id == employeeId) return;
                var opt = document.createElement('option');
                opt.value = emp.employee_id;
                opt.textContent = emp.full_name;
                successorSelect.appendChild(opt);
            });
        })
        .catch(function() { console.error('Failed to load employee list.'); });

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.kt-view-btn');
        if (btn) {
            e.preventDefault();
            var planId = btn.dataset.id;
            fetch('../../modules/exit/pages/ajax/get-knowledge-transfer.php?id=' + planId, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.plan) { alert('Unable to load plan details.'); return; }
                modalTitle.textContent = 'Knowledge Transfer Plan #' + planId;
                empNameInput.value = data.plan.employee_name || 'N/A';
                successorSelect.value = data.plan.successor_id || '';
                startDateInput.value = data.plan.start_date || '';
                endDateInput.value = data.plan.end_date || '';
                statusInput.value = data.plan.status || 'active';
                editingPlanId = planId;
                modalOverlay.style.display = 'flex';
            })
            .catch(function() { alert('Failed to load plan.'); });
            return;
        }

        var editBtn = e.target.closest('.kt-edit-btn');
        if (editBtn) {
            e.preventDefault();
            var planId = editBtn.dataset.id;
            fetch('../../modules/exit/pages/ajax/get-knowledge-transfer.php?id=' + planId, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.plan) { alert('Unable to load plan details.'); return; }
                modalTitle.textContent = 'Edit Knowledge Transfer Plan #' + planId;
                empNameInput.value = data.plan.employee_name || 'N/A';
                successorSelect.value = data.plan.successor_id || '';
                startDateInput.value = data.plan.start_date || '';
                endDateInput.value = data.plan.end_date || '';
                statusInput.value = data.plan.status || 'active';
                editingPlanId = planId;
                modalOverlay.style.display = 'flex';
            })
            .catch(function() { alert('Failed to load plan.'); });
            return;
        }

        var createBtn = e.target.closest('#kt-create-btn');
        if (createBtn && !createBtn.classList.contains('kt-view-btn') && !createBtn.classList.contains('kt-edit-btn')) {
            e.preventDefault();
            editingPlanId = null;
            modalTitle.textContent = 'Create Knowledge Transfer Plan';
            empNameInput.value = employeeName;
            successorSelect.value = '';
            startDateInput.value = '';
            endDateInput.value = '';
            statusInput.value = 'active';
            modalOverlay.style.display = 'flex';
        }
    });

    function closeModal() { modalOverlay.style.display = 'none'; editingPlanId = null; }
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', function(e) { if (e.target === modalOverlay) closeModal(); });

    saveBtn.addEventListener('click', function() {
        var successorId = parseInt(successorSelect.value) || null;
        var startDate = startDateInput.value;
        var endDate = endDateInput.value;
        var status = statusInput.value;

        if (!successorId) { alert('Please select a successor.'); return; }
        if (!startDate) { alert('Please select a start date.'); return; }
        if (!endDate) { alert('Please select an end date.'); return; }

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        var payload = { employee_id: employeeId, successor_id: successorId, start_date: startDate, end_date: endDate, status: status };
        if (editingPlanId) payload.id = editingPlanId;

        fetch('../../modules/learning/pages/admin/ajax/save-kt-plan.php', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) { alert('KT plan ' + (editingPlanId ? 'updated' : 'created') + ' successfully.'); window.location.reload(); }
            else { alert('Error: ' + (data.error || 'Unknown error')); saveBtn.disabled = false; saveBtn.textContent = 'Save Plan'; }
        })
        .catch(function() { alert('Network error.'); saveBtn.disabled = false; saveBtn.textContent = 'Save Plan'; });
    });
})();
</script>
