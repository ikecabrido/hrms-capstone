<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();

$instructors = [];
$learners = [];

try {
    $pdo = (new Database())->getConnection();

    $instructors = $pdo->query("
        SELECT emp.employee_id, emp.first_name, emp.last_name, emp.email,
               COUNT(DISTINCT c.id) AS course_count,
               COUNT(DISTINCT e.learner_id) AS learner_count,
               SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_count
        FROM em_employees emp
        LEFT JOIN ld_course c ON c.instructor_id = emp.employee_id AND c.status != 'archived'
        LEFT JOIN ld_enrollment e ON e.course_id = c.id
        WHERE emp.employee_id IN (SELECT DISTINCT instructor_id FROM ld_course WHERE instructor_id IS NOT NULL)
        GROUP BY emp.employee_id, emp.first_name, emp.last_name, emp.email
        ORDER BY course_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $learners = $pdo->query("
        SELECT emp.employee_id, emp.first_name, emp.last_name, emp.email,
               COUNT(e.id) AS enrollment_count,
               SUM(CASE WHEN e.status IN ('enrolled','in_progress') THEN 1 ELSE 0 END) AS active_count,
               SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
               ROUND(COALESCE(AVG(g.final_score), 0), 1) AS avg_score,
               MAX(COALESCE(e.last_accessed_at, e.enrolled_at)) AS last_active
        FROM em_employees emp
        LEFT JOIN ld_enrollment e ON e.learner_id = emp.employee_id
        LEFT JOIN ld_grade g ON g.learner_id = emp.employee_id
        WHERE emp.employee_id IN (SELECT DISTINCT learner_id FROM ld_enrollment)
        GROUP BY emp.employee_id, emp.first_name, emp.last_name, emp.email
        ORDER BY last_active DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $instructors = [];
    $learners = [];
}

function userTimeAgo($dt) {
    if (!$dt) return 'never active';
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    return floor($d / 86400) . 'd ago';
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" id="user-search" placeholder="Search users by name or email..." aria-label="Search users" />
        </div>
        <div class="toolbar-actions">
            <select class="toolbar-filter" id="user-role-filter" aria-label="Filter by role">
                <option value="all">All Roles</option>
                <option value="instructor">Instructors</option>
                <option value="learner">Learners</option>
            </select>
            <select class="toolbar-page-size" aria-label="Rows per page">
                <option value="12" selected>12 rows</option>
                <option value="24">24 rows</option>
                <option value="36">36 rows</option>
            </select>
            <a href="?page=admin/user-subpage/instructor" style="display:inline-flex; align-items:center; gap:0.4rem; font-size:0.85em; background:var(--primary); color:var(--surface); padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-weight:700; width:auto; height:auto; min-width:auto;">+ Add Instructor</a>
            <a href="?page=admin/user-subpage/learner" style="display:inline-flex; align-items:center; gap:0.4rem; font-size:0.85em; background:#10b981; color:#fff; padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-weight:700; width:auto; height:auto; min-width:auto;">+ Add Learner</a>
        </div>
    </div>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-instructors">Instructors (<?= count($instructors) ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-learners">Learners (<?= count($learners) ?>)</button>
        </div>

        <!-- Instructors Tab -->
        <div class="tab-content active" data-tab="tab-instructors">
            <?php if (empty($instructors)): ?>
                <div class="mode-card">
                    <div class="content-card-body">
                        <h3>No instructors found</h3>
                        <p>There are no instructors yet. Add one to get started.</p>
                    </div>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.92rem;">
                        <thead>
                            <tr style="border-bottom:2px solid rgba(32,0,130,0.12); text-align:left;">
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Name</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Email</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Courses</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Learners</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Completed</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($instructors as $inst):
                                $initials = strtoupper(substr($inst['first_name'],0,1) . substr($inst['last_name'],0,1));
                                $name = htmlspecialchars($inst['first_name'] . ' ' . $inst['last_name']);
                                $completionRate = $inst['course_count'] > 0 ? min(100, round(($inst['completed_count'] / $inst['course_count']) * 100)) : 0;
                            ?>
                                <tr class="user-list-row" data-search="<?= strtolower($name . ' ' . $inst['email']) ?>" style="border-bottom:1px solid rgba(32,0,130,0.06); cursor:pointer; transition:background 0.15s;" onmouseover="this.style.background='rgba(32,0,130,0.03)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding:0.85rem 1rem;">
                                        <div style="display:flex; align-items:center; gap:0.75rem;">
                                            <div style="width:38px; height:38px; min-width:38px; border-radius:50%; background:linear-gradient(135deg, rgba(32,0,130,0.9), rgba(91,85,255,0.75)); color:var(--surface); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem;">
                                                <?= $initials ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:700; color:var(--text);"><?= $name ?></div>
                                                <div style="font-size:0.78rem; color:rgba(32,0,130,0.5);">Emp #<?= $inst['employee_id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:0.85rem 1rem; color:rgba(32,0,130,0.6);"><?= htmlspecialchars($inst['email']) ?></td>
                                    <td style="padding:0.85rem 1rem; text-align:center; font-weight:700; color:var(--text);"><?= $inst['course_count'] ?></td>
                                    <td style="padding:0.85rem 1rem; text-align:center; font-weight:700; color:var(--text);"><?= $inst['learner_count'] ?></td>
                                    <td style="padding:0.85rem 1rem; text-align:center;">
                                        <span style="font-weight:700; color:<?= $completionRate >= 80 ? '#10b981' : ($completionRate >= 50 ? '#f59e0b' : 'rgba(32,0,130,0.5)') ?>;">
                                            <?= $completionRate ?>%
                                        </span>
                                    </td>
                                    <td style="padding:0.85rem 1rem; text-align:center;">
                                        <a href="?page=admin/user-subpage/instructor&id=<?= $inst['employee_id'] ?>" style="display:inline-block; padding:0.4rem 0.8rem; border-radius:999px; font-size:0.78rem; font-weight:700; border:1px solid rgba(32,0,130,0.2); color:var(--primary); text-decoration:none;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Learners Tab -->
        <div class="tab-content" data-tab="tab-learners">
            <?php if (empty($learners)): ?>
                <div class="mode-card">
                    <div class="content-card-body">
                        <h3>No learners found</h3>
                        <p>There are no enrolled learners yet. Enroll one to get started.</p>
                    </div>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.92rem;">
                        <thead>
                            <tr style="border-bottom:2px solid rgba(32,0,130,0.12); text-align:left;">
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Name</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Email</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Enrolled</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Active</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Avg Score</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Last Active</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($learners as $lrn):
                                $initials = strtoupper(substr($lrn['first_name'],0,1) . substr($lrn['last_name'],0,1));
                                $name = htmlspecialchars($lrn['first_name'] . ' ' . $lrn['last_name']);
                                $scoreColor = $lrn['avg_score'] >= 80 ? '#10b981' : ($lrn['avg_score'] >= 60 ? '#f59e0b' : '#ef4444');
                            ?>
                                <tr class="user-list-row" data-search="<?= strtolower($name . ' ' . $lrn['email']) ?>" style="border-bottom:1px solid rgba(32,0,130,0.06); cursor:pointer; transition:background 0.15s;" onmouseover="this.style.background='rgba(32,0,130,0.03)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding:0.85rem 1rem;">
                                        <div style="display:flex; align-items:center; gap:0.75rem;">
                                            <div style="width:38px; height:38px; min-width:38px; border-radius:50%; background:linear-gradient(135deg, rgba(16,185,129,0.9), rgba(52,211,153,0.75)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem;">
                                                <?= $initials ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:700; color:var(--text);"><?= $name ?></div>
                                                <div style="font-size:0.78rem; color:rgba(32,0,130,0.5);">Emp #<?= $lrn['employee_id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:0.85rem 1rem; color:rgba(32,0,130,0.6);"><?= htmlspecialchars($lrn['email']) ?></td>
                                    <td style="padding:0.85rem 1rem; text-align:center; font-weight:700; color:var(--text);"><?= $lrn['enrollment_count'] ?></td>
                                    <td style="padding:0.85rem 1rem; text-align:center;">
                                        <span style="padding:0.25rem 0.6rem; border-radius:999px; font-size:0.72rem; font-weight:700; background:rgba(59,130,246,0.1); color:#3b82f6;">
                                            <?= $lrn['active_count'] ?>
                                        </span>
                                    </td>
                                    <td style="padding:0.85rem 1rem; text-align:center;">
                                        <span style="font-weight:700; color:<?= $scoreColor ?>;">
                                            <?= $lrn['avg_score'] ?>%
                                        </span>
                                    </td>
                                    <td style="padding:0.85rem 1rem; font-size:0.85rem; color:rgba(32,0,130,0.5);">
                                        <?= userTimeAgo($lrn['last_active']) ?>
                                    </td>
                                    <td style="padding:0.85rem 1rem; text-align:center;">
                                        <a href="?page=admin/user-subpage/learner&id=<?= $lrn['employee_id'] ?>" style="display:inline-block; padding:0.4rem 0.8rem; border-radius:999px; font-size:0.78rem; font-weight:700; border:1px solid rgba(32,0,130,0.2); color:var(--primary); text-decoration:none;">View</a>
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

<style>
    @media (max-width: 768px) {
        .user-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .user-table-wrap table { min-width: 640px; }
    }
</style>

<script>
(function() {
    var searchInput = document.getElementById('user-search');
    var roleFilter = document.getElementById('user-role-filter');

    function filterCards() {
        var query = (searchInput.value || '').toLowerCase().trim();
        var role = roleFilter.value;

        // Show/hide tabs based on role filter
        var tabs = document.querySelectorAll('.tab-item');
        var contents = document.querySelectorAll('.tab-content');

        if (role === 'instructor') {
            tabs[0].style.display = '';
            tabs[1].style.display = 'none';
            contents[0].classList.add('active');
            contents[1].classList.remove('active');
        } else if (role === 'learner') {
            tabs[0].style.display = 'none';
            tabs[1].style.display = '';
            contents[0].classList.remove('active');
            contents[1].classList.add('active');
        } else {
            tabs[0].style.display = '';
            tabs[1].style.display = '';
        }

        // Search filter on list rows
        document.querySelectorAll('.user-list-row').forEach(function(row) {
            var searchText = (row.getAttribute('data-search') || '').toLowerCase();
            if (query === '' || searchText.indexOf(query) > -1) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterCards);
    if (roleFilter) roleFilter.addEventListener('change', filterCards);
})();
</script>
