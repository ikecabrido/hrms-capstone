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

/** First/last initials for the avatar chip. */
function userInitials(array $row): string {
    return strtoupper(substr((string) $row['first_name'], 0, 1) . substr((string) $row['last_name'], 0, 1));
}

/** Lowercased "name email" string the search box matches against. */
function userSearchKey(array $row): string {
    $raw = $row['first_name'] . ' ' . $row['last_name'] . ' ' . $row['email'];
    return htmlspecialchars(strtolower($raw), ENT_QUOTES, 'UTF-8');
}

/** Role class used by the avatar and role badge. */
function userRoleClass(?string $role): string {
    return in_array($role, ['admin', 'instructor', 'learner'], true) ? (string) $role : 'learner';
}

/** Email cell text — a dash keeps rows readable when the record has none. */
function userEmailCell(array $row): string {
    $email = trim((string) ($row['email'] ?? ''));
    return $email === '' ? '<span class="user-none">&mdash;</span>' : htmlspecialchars($email);
}
?>
<div class="module-content user-admin-page">
    <?php include __DIR__ . '/../../includes/admin-user-navigation.php'; ?>

    <section class="home-hero">
        <div class="home-hero-main">
            <h1>Users</h1>
            <p>Everyone with a Learning &amp; Development account, grouped by learning role.</p>
        </div>
    </section>

    <div class="user-toolbar">
        <label class="user-search">
            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" id="user-search" placeholder="Search users by name or email..." aria-label="Search users" />
        </label>
        <label class="user-rows">
            <span>Rows per page</span>
            <select id="user-page-size" data-display-pref="page_size" aria-label="Rows per page">
                <option value="12" selected>12</option>
                <option value="24">24</option>
                <option value="36">36</option>
            </select>
        </label>
    </div>

    <div id="user-pagination" class="user-pagination">
        <span id="user-pagination-summary" class="user-pagination__info" aria-live="polite">Showing 0 users</span>
        <div class="user-pagination__controls">
            <button type="button" id="user-page-previous" class="user-page-btn user-page-previous" aria-label="Previous page">Previous</button>
            <span id="user-page-indicator" class="user-page-indicator" aria-live="polite">Page 1 of 1</span>
            <button type="button" id="user-page-next" class="user-page-btn user-page-next" aria-label="Next page">Next</button>
        </div>
    </div>

    <p id="user-empty-results" class="user-empty-results" style="display:none;">No users match the selected filters.</p>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-all-users" aria-label="All users" title="All users"><i class="fas fa-users" aria-hidden="true"></i> All Users (<?= count($allUsers) ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-admins" aria-label="Admins" title="Admins"><i class="fas fa-user-shield" aria-hidden="true"></i> Admins (<?= count($admins) ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-instructors" aria-label="Instructors" title="Instructors"><i class="fas fa-chalkboard-user" aria-hidden="true"></i> Instructors (<?= count($instructors) ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-learners" aria-label="Learners" title="Learners"><i class="fas fa-user-graduate" aria-hidden="true"></i> Learners (<?= count($learners) ?>)</button>
        </div>

        <!-- All users -->
        <div class="tab-content active" id="tab-all-users" data-tab="tab-all-users">
            <?php if (empty($allUsers)): ?>
                <div class="user-empty">
                    <i class="fas fa-users-slash" aria-hidden="true"></i>
                    <h3>No user accounts found</h3>
                    <p>Active employee accounts will appear here.</p>
                </div>
            <?php else: ?>
                <div class="user-table-wrap">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Learning Role</th>
                                <th class="is-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $user):
                                $roleClass = userRoleClass($user['learning_role'] ?? null);
                                $profileType = ($roleClass === 'instructor' || $roleClass === 'admin') ? 'instructor' : 'learner';
                            ?>
                                <tr class="user-list-row" data-role="<?= htmlspecialchars($roleClass, ENT_QUOTES, 'UTF-8') ?>" data-search="<?= userSearchKey($user) ?>">
                                    <td>
                                        <div class="user-cell-name">
                                            <span class="user-avatar<?= $roleClass === 'learner' ? ' is-learner' : '' ?>"><?= userInitials($user) ?></span>
                                            <span class="user-name-block">
                                                <span class="user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
                                                <span class="user-meta">Emp #<?= (int) $user['employee_id'] ?></span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="user-cell-email"><?= userEmailCell($user) ?></td>
                                    <td><span class="user-role is-<?= $roleClass ?>"><?= htmlspecialchars(ucfirst($roleClass)) ?></span></td>
                                    <td class="is-center">
                                        <a class="user-view-btn" href="?page=admin/user-subpage/<?= $profileType ?>&amp;id=<?= (int) $user['employee_id'] ?>">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Admins -->
        <div class="tab-content" id="tab-admins" data-tab="tab-admins">
            <?php if (empty($admins)): ?>
                <div class="user-empty">
                    <i class="fas fa-user-shield" aria-hidden="true"></i>
                    <h3>No admins found</h3>
                    <p>Active users with the Admin Learning role will appear here.</p>
                </div>
            <?php else: ?>
                <div class="user-table-wrap">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Learning Role</th>
                                <th class="is-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr class="user-list-row" data-role="admin" data-search="<?= userSearchKey($admin) ?>">
                                    <td>
                                        <div class="user-cell-name">
                                            <span class="user-avatar"><?= userInitials($admin) ?></span>
                                            <span class="user-name-block">
                                                <span class="user-name"><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></span>
                                                <span class="user-meta">Emp #<?= (int) $admin['employee_id'] ?></span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="user-cell-email"><?= userEmailCell($admin) ?></td>
                                    <td><span class="user-role is-admin">Admin</span></td>
                                    <td class="is-center">
                                        <a class="user-view-btn" href="?page=admin/user-subpage/instructor&amp;id=<?= (int) $admin['employee_id'] ?>">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Instructors -->
        <div class="tab-content" id="tab-instructors" data-tab="tab-instructors">
            <?php if (empty($instructors)): ?>
                <div class="user-empty">
                    <i class="fas fa-chalkboard-user" aria-hidden="true"></i>
                    <h3>No instructors found</h3>
                    <p>Active users with the Instructor Learning role will appear here.</p>
                </div>
            <?php else: ?>
                <div class="user-table-wrap">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Learning Role</th>
                                <th class="is-center">Courses</th>
                                <th class="is-center">Learners</th>
                                <th class="is-center">Completed</th>
                                <th class="is-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($instructors as $inst):
                                $completionRate = $inst['course_count'] > 0 ? min(100, round(($inst['completed_count'] / $inst['course_count']) * 100)) : 0;
                                $rateClass = $completionRate >= 80 ? 'is-good' : ($completionRate >= 50 ? 'is-warn' : 'is-low');
                            ?>
                                <tr class="user-list-row" data-role="instructor" data-search="<?= userSearchKey($inst) ?>">
                                    <td>
                                        <div class="user-cell-name">
                                            <span class="user-avatar"><?= userInitials($inst) ?></span>
                                            <span class="user-name-block">
                                                <span class="user-name"><?= htmlspecialchars($inst['first_name'] . ' ' . $inst['last_name']) ?></span>
                                                <span class="user-meta">Emp #<?= (int) $inst['employee_id'] ?></span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="user-cell-email"><?= userEmailCell($inst) ?></td>
                                    <td><span class="user-role is-instructor">Instructor</span></td>
                                    <td class="is-center user-stat"><?= (int) $inst['course_count'] ?></td>
                                    <td class="is-center user-stat"><?= (int) $inst['learner_count'] ?></td>
                                    <td class="is-center"><span class="user-rate <?= $rateClass ?>"><?= $completionRate ?>%</span></td>
                                    <td class="is-center">
                                        <a class="user-view-btn" href="?page=admin/user-subpage/instructor&amp;id=<?= (int) $inst['employee_id'] ?>">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Learners -->
        <div class="tab-content" id="tab-learners" data-tab="tab-learners">
            <?php if (empty($learners)): ?>
                <div class="user-empty">
                    <i class="fas fa-user-graduate" aria-hidden="true"></i>
                    <h3>No learners found</h3>
                    <p>Active employee accounts with the Learner role will appear here.</p>
                </div>
            <?php else: ?>
                <div class="user-table-wrap">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th class="is-center">Enrolled</th>
                                <th class="is-center">Active</th>
                                <th class="is-center">Avg Score</th>
                                <th>Last Active</th>
                                <th class="is-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($learners as $lrn):
                                $scoreClass = $lrn['avg_score'] >= 80 ? 'is-good' : ($lrn['avg_score'] >= 60 ? 'is-warn' : 'is-low');
                            ?>
                                <tr class="user-list-row" data-role="learner" data-search="<?= userSearchKey($lrn) ?>">
                                    <td>
                                        <div class="user-cell-name">
                                            <span class="user-avatar is-learner"><?= userInitials($lrn) ?></span>
                                            <span class="user-name-block">
                                                <span class="user-name"><?= htmlspecialchars($lrn['first_name'] . ' ' . $lrn['last_name']) ?></span>
                                                <span class="user-meta">Emp #<?= (int) $lrn['employee_id'] ?></span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="user-cell-email"><?= userEmailCell($lrn) ?></td>
                                    <td class="is-center user-stat"><?= (int) $lrn['enrollment_count'] ?></td>
                                    <td class="is-center"><span class="user-pill"><?= (int) $lrn['active_count'] ?></span></td>
                                    <td class="is-center"><span class="user-rate <?= $scoreClass ?>"><?= $lrn['avg_score'] ?>%</span></td>
                                    <td class="user-meta"><?= userTimeAgo($lrn['last_active']) ?></td>
                                    <td class="is-center">
                                        <a class="user-view-btn" href="?page=admin/user-subpage/learner&amp;id=<?= (int) $lrn['employee_id'] ?>">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="user-pagination-bottom" class="user-pagination user-pagination--bottom" aria-label="User table pagination">
        <div class="user-pagination__controls">
            <button type="button" class="user-page-btn user-page-previous" aria-label="Previous page">Previous</button>
            <span class="user-page-indicator" aria-live="polite">Page 1 of 1</span>
            <button type="button" class="user-page-btn user-page-next" aria-label="Next page">Next</button>
        </div>
    </div>
</div>

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
