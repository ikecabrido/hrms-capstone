<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/LearningRole.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();

$admins = [];
$instructors = [];
$learners = [];
$allUsers = [];

try {
    $pdo = (new Database())->getConnection();

    $instructors = $pdo->query("
        SELECT emp.employee_id, emp.first_name, emp.last_name, emp.email,
               emp.role_id, hr_role.role_name, dept.department_name,
               ld_role.learning_role AS assigned_learning_role,
               COUNT(DISTINCT c.id) AS course_count,
               COUNT(DISTINCT e.learner_id) AS learner_count,
               SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_count
        FROM em_employees emp
        INNER JOIN user_account account ON account.employee_id = emp.employee_id
        LEFT JOIN em_roles hr_role ON hr_role.role_id = emp.role_id
        LEFT JOIN em_departments dept ON dept.department_id = emp.department_id
        LEFT JOIN ld_user_role ld_role ON ld_role.employee_id = emp.employee_id
        LEFT JOIN ld_course c ON c.instructor_id = emp.employee_id AND c.status != 'archived'
        LEFT JOIN ld_enrollment e ON e.course_id = c.id
        WHERE emp.employment_status = 'Active'
        GROUP BY emp.employee_id, emp.first_name, emp.last_name, emp.email,
                 emp.role_id, hr_role.role_name, dept.department_name, ld_role.learning_role
        ORDER BY course_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($instructors as &$instructor) {
        $instructor['learning_role'] = LearningRole::resolveWithAssignment(
            $instructor,
            $instructor['assigned_learning_role'] ?? null
        );
    }
    unset($instructor);
    $admins = array_values(array_filter($instructors, static function ($instructor) {
        return $instructor['learning_role'] === 'admin';
    }));
    $instructors = array_values(array_filter($instructors, static function ($instructor) {
        return $instructor['learning_role'] === 'instructor';
    }));

    $learners = $pdo->query("
         SELECT emp.employee_id, emp.first_name, emp.last_name, emp.email,
             emp.role_id, hr_role.role_name, dept.department_name,
             ld_role.learning_role AS assigned_learning_role,
               COUNT(e.id) AS enrollment_count,
               SUM(CASE WHEN e.status IN ('enrolled','in_progress') THEN 1 ELSE 0 END) AS active_count,
               SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
               ROUND(COALESCE(AVG(g.final_score), 0), 1) AS avg_score,
               MAX(COALESCE(e.last_accessed_at, e.enrolled_at)) AS last_active
        FROM em_employees emp
        INNER JOIN user_account account ON account.employee_id = emp.employee_id
        LEFT JOIN em_roles hr_role ON hr_role.role_id = emp.role_id
        LEFT JOIN em_departments dept ON dept.department_id = emp.department_id
        LEFT JOIN ld_user_role ld_role ON ld_role.employee_id = emp.employee_id
        LEFT JOIN ld_enrollment e ON e.learner_id = emp.employee_id
        LEFT JOIN ld_grade g ON g.learner_id = emp.employee_id
        WHERE emp.employment_status = 'Active'
        GROUP BY emp.employee_id, emp.first_name, emp.last_name, emp.email,
                 emp.role_id, hr_role.role_name, dept.department_name, ld_role.learning_role
        ORDER BY last_active DESC, emp.employee_id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($learners as &$learner) {
        $learner['learning_role'] = LearningRole::resolveWithAssignment(
            $learner,
            $learner['assigned_learning_role'] ?? null
        );
    }
    unset($learner);
    $allUsers = $learners;
    $learners = array_values(array_filter($learners, static function ($learner) {
        return $learner['learning_role'] === 'learner';
    }));

} catch (Throwable $e) {
    DbError::capture($e, 'admin/user');
    $admins = [];
    $instructors = [];
    $learners = [];
    $allUsers = [];
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
<div class="user-admin-layout">
    <?php include __DIR__ . '/../../includes/admin-user-navigation.php'; ?>
    <div class="module-content" style="flex:1; min-width:0;">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" id="user-search" placeholder="Search users by name or email..." aria-label="Search users" />
        </div>
        <div class="toolbar-actions">
            <select class="toolbar-page-size" id="user-page-size" aria-label="Rows per page">
                <option value="12" selected>12 rows</option>
                <option value="24">24 rows</option>
                <option value="36">36 rows</option>
            </select>
        </div>
    </div>
    <div id="user-pagination" style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; flex-wrap:wrap; margin:0.75rem 0 1rem;">
        <span id="user-pagination-summary" aria-live="polite" style="font-size:0.82rem; color:var(--muted);">Showing 0 users</span>
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <button type="button" id="user-page-previous" class="mode-button" style="padding:0.45rem 0.75rem;" aria-label="Previous page">Previous</button>
            <span id="user-page-indicator" aria-live="polite" style="min-width:5rem; text-align:center; font-size:0.82rem;">Page 1 of 1</span>
            <button type="button" id="user-page-next" class="mode-button" style="padding:0.45rem 0.75rem;" aria-label="Next page">Next</button>
        </div>
    </div>
    <p id="user-empty-results" style="display:none; text-align:center; padding:1.5rem; color:var(--muted);">No users match the selected filters.</p>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-all-users">All Users (<?= count($allUsers) ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-admins">Admins (<?= count($admins) ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-instructors">Instructors (<?= count($instructors) ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-learners">Learners (<?= count($learners) ?>)</button>
        </div>

        <!-- Instructors Tab -->
        <div class="tab-content" data-tab="tab-instructors">
            <?php if (empty($instructors)): ?>
                <div class="mode-card">
                    <div class="content-card-body">
                        <h3>No instructors found</h3>
                        <p>Active users with the Instructor Learning role will appear here.</p>
                    </div>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.92rem;">
                        <thead>
                            <tr style="border-bottom:2px solid rgba(32,0,130,0.12); text-align:left;">
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Name</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Email</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Learning Role</th>
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
                                <tr class="user-list-row" data-role="<?= htmlspecialchars($inst['learning_role'], ENT_QUOTES, 'UTF-8') ?>" data-search="<?= strtolower($name . ' ' . $inst['email']) ?>" style="border-bottom:1px solid rgba(32,0,130,0.06); cursor:pointer; transition:background 0.15s;" onmouseover="this.style.background='rgba(32,0,130,0.03)'" onmouseout="this.style.background='transparent'">
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
                                    <td style="padding:0.85rem 1rem;"><?= htmlspecialchars(ucfirst($inst['learning_role'])) ?></td>
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
                        <p>Active employee accounts with the Learner role will appear here.</p>
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
                                <tr class="user-list-row" data-name="<?= htmlspecialchars($lrn['first_name'] . ' ' . $lrn['last_name'], ENT_QUOTES, 'UTF-8') ?>" data-role="<?= htmlspecialchars($lrn['learning_role'], ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(strtolower($name . ' ' . $lrn['email']), ENT_QUOTES, 'UTF-8') ?>" style="border-bottom:1px solid rgba(32,0,130,0.06); transition:background 0.15s;" onmouseover="this.style.background='rgba(32,0,130,0.03)'" onmouseout="this.style.background='transparent'">
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
                                        <a href="?page=admin/user-subpage/learner&id=<?= (int) $lrn['employee_id'] ?>" style="display:inline-block; padding:0.4rem 0.8rem; border-radius:999px; font-size:0.78rem; font-weight:700; border:1px solid rgba(32,0,130,0.2); color:var(--primary); text-decoration:none;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-content active" data-tab="tab-all-users">
            <?php if (empty($allUsers)): ?>
                <div class="mode-card">
                    <div class="content-card-body">
                        <h3>No user accounts found</h3>
                        <p>Active employee accounts will appear here.</p>
                    </div>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.92rem;">
                        <thead>
                            <tr style="border-bottom:2px solid rgba(32,0,130,0.12); text-align:left;">
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Name</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Email</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Learning Role</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $user):
                                $name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
                                $profileType = $user['learning_role'] === 'instructor' || $user['learning_role'] === 'admin'
                                    ? 'instructor'
                                    : 'learner';
                            ?>
                                <tr class="user-list-row" data-role="<?= htmlspecialchars($user['learning_role'], ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(strtolower($name . ' ' . $user['email']), ENT_QUOTES, 'UTF-8') ?>" style="border-bottom:1px solid rgba(32,0,130,0.06);">
                                    <td style="padding:0.85rem 1rem;">
                                        <div style="font-weight:700; color:var(--text);"> <?= $name ?></div>
                                        <div style="font-size:0.78rem; color:rgba(32,0,130,0.5);">Emp #<?= (int) $user['employee_id'] ?></div>
                                    </td>
                                    <td style="padding:0.85rem 1rem; color:rgba(32,0,130,0.6);"> <?= htmlspecialchars($user['email']) ?></td>
                                    <td style="padding:0.85rem 1rem;"><?= htmlspecialchars(ucfirst($user['learning_role'])) ?></td>
                                    <td style="padding:0.85rem 1rem; text-align:center;">
                                        <a href="?page=admin/user-subpage/<?= $profileType ?>&amp;id=<?= (int) $user['employee_id'] ?>" style="display:inline-block; padding:0.4rem 0.8rem; border-radius:999px; font-size:0.78rem; font-weight:700; border:1px solid rgba(32,0,130,0.2); color:var(--primary); text-decoration:none;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-content" data-tab="tab-admins">
            <?php if (empty($admins)): ?>
                <div class="mode-card">
                    <div class="content-card-body">
                        <h3>No admins found</h3>
                        <p>Active users with the Admin Learning role will appear here.</p>
                    </div>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.92rem;">
                        <thead>
                            <tr style="border-bottom:2px solid rgba(32,0,130,0.12); text-align:left;">
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Name</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Email</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Learning Role</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin):
                                $adminName = htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']);
                            ?>
                                <tr class="user-list-row" data-role="admin" data-search="<?= htmlspecialchars(strtolower($adminName . ' ' . $admin['email']), ENT_QUOTES, 'UTF-8') ?>" style="border-bottom:1px solid rgba(32,0,130,0.06);">
                                    <td style="padding:0.85rem 1rem;">
                                        <div style="font-weight:700; color:var(--text);"><?= $adminName ?></div>
                                        <div style="font-size:0.78rem; color:rgba(32,0,130,0.5);">Emp #<?= (int) $admin['employee_id'] ?></div>
                                    </td>
                                    <td style="padding:0.85rem 1rem; color:rgba(32,0,130,0.6);"><?= htmlspecialchars($admin['email']) ?></td>
                                    <td style="padding:0.85rem 1rem;">Admin</td>
                                    <td style="padding:0.85rem 1rem; text-align:center;">
                                        <a href="?page=admin/user-subpage/instructor&amp;id=<?= (int) $admin['employee_id'] ?>" style="display:inline-block; padding:0.4rem 0.8rem; border-radius:999px; font-size:0.78rem; font-weight:700; border:1px solid rgba(32,0,130,0.2); color:var(--primary); text-decoration:none;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div id="user-pagination-bottom" aria-label="User table pagination" style="display:flex; align-items:center; justify-content:flex-end; gap:0.5rem; flex-wrap:wrap; margin:1rem 0;">
        <button type="button" class="user-page-previous mode-button" style="padding:0.45rem 0.75rem;" aria-label="Previous page">Previous</button>
        <span class="user-page-indicator" aria-live="polite" style="min-width:5rem; text-align:center; font-size:0.82rem;">Page 1 of 1</span>
        <button type="button" class="user-page-next mode-button" style="padding:0.45rem 0.75rem;" aria-label="Next page">Next</button>
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
    var pageSizeSelect = document.getElementById('user-page-size');
    var pagination = document.getElementById('user-pagination');
    var summary = document.getElementById('user-pagination-summary');
    var pageIndicators = document.querySelectorAll('.user-page-indicator, #user-page-indicator');
    var previousButtons = document.querySelectorAll('.user-page-previous, #user-page-previous');
    var nextButtons = document.querySelectorAll('.user-page-next, #user-page-next');
    var emptyResults = document.getElementById('user-empty-results');
    var currentPage = 1;
    var activeTab = 'tab-all-users';

    function renderUsers(resetPage) {
        var query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        var pageSize = pageSizeSelect ? (parseInt(pageSizeSelect.value, 10) || 12) : 12;
        if (resetPage) currentPage = 1;

        var tabs = document.querySelectorAll('.tab-item');
        var contents = document.querySelectorAll('.tab-content');

        tabs.forEach(function(tab) {
            var tabName = tab.getAttribute('data-tab');
            tab.classList.toggle('active', tabName === activeTab);
        });
        contents.forEach(function(content) {
            content.classList.toggle('active', content.getAttribute('data-tab') === activeTab);
        });

        var allRows = Array.from(document.querySelectorAll('.user-list-row'));
        allRows.forEach(function(row) { row.style.display = 'none'; });

        var activeContent = document.querySelector('.tab-content[data-tab="' + activeTab + '"]');
        var matchingRows = activeContent
            ? Array.from(activeContent.querySelectorAll('.user-list-row')).filter(function(row) {
            var searchText = (row.getAttribute('data-search') || '').toLowerCase();
                return query === '' || searchText.indexOf(query) > -1;
            })
            : [];

        var totalPages = Math.max(1, Math.ceil(matchingRows.length / pageSize));
        currentPage = Math.min(currentPage, totalPages);
        var start = (currentPage - 1) * pageSize;
        matchingRows.slice(start, start + pageSize).forEach(function(row) { row.style.display = ''; });

        if (summary) {
            summary.textContent = matchingRows.length
                ? 'Showing ' + (start + 1) + '–' + Math.min(start + pageSize, matchingRows.length) + ' of ' + matchingRows.length + ' users'
                : 'Showing 0 users';
        }
        pageIndicators.forEach(function(indicator) { indicator.textContent = 'Page ' + currentPage + ' of ' + totalPages; });
        previousButtons.forEach(function(button) { button.disabled = currentPage <= 1; });
        nextButtons.forEach(function(button) { button.disabled = currentPage >= totalPages; });
        if (pagination) pagination.style.display = matchingRows.length ? 'flex' : 'none';
        if (emptyResults) emptyResults.style.display = matchingRows.length ? 'none' : '';
    }

    if (searchInput) searchInput.addEventListener('input', function() { renderUsers(true); });
    if (pageSizeSelect) pageSizeSelect.addEventListener('change', function() { renderUsers(true); });
    document.querySelectorAll('.tab-item').forEach(function(tab) {
        tab.addEventListener('click', function() {
            activeTab = tab.getAttribute('data-tab') || 'tab-all-users';
            renderUsers(true);
        });
    });
    previousButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                renderUsers(false);
            }
        });
    });
    nextButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            currentPage++;
            renderUsers(false);
        });
    });

    renderUsers(true);
})();
</script>
