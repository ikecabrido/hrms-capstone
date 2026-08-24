<?php
require_once __DIR__ . '/../classes/GoalController.php';

$controller = new GoalController();

$overview = $controller->getDashboardData();
$employees = $controller->getEmployees();

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'priority' => trim((string) ($_GET['priority'] ?? '')),
    'category' => trim((string) ($_GET['category'] ?? '')),
    'employee_id' => trim((string) ($_GET['employee_id'] ?? '')),
    'department' => trim((string) ($_GET['department'] ?? '')),
    'from_date' => trim((string) ($_GET['from_date'] ?? '')),
    'to_date' => trim((string) ($_GET['to_date'] ?? '')),
];

$goals = $controller->getGoals($filters);
$selectedGoalId = isset($_GET['goal_id']) ? (int) $_GET['goal_id'] : null;
$selectedGoal = $selectedGoalId ? $controller->getSelectedGoal($selectedGoalId) : null;
$goalHistory = $selectedGoal ? $controller->getSelectedGoalHistory((int) $selectedGoal['goal_id']) : [];
$progressEntries = $selectedGoal ? $controller->getSelectedGoalProgressEntries((int) $selectedGoal['goal_id']) : [];
$csrfToken = $controller->getCsrfToken();
$isEditMode = isset($_GET['mode']) && $_GET['mode'] === 'edit';

$goalStatuses = ['Draft', 'Pending', 'Active', 'In Progress', 'Completed', 'Overdue', 'Cancelled'];
$goalPriorities = ['Low', 'Medium', 'High', 'Critical'];
$goalCategories = ['Performance', 'Productivity', 'Quality', 'Professional Development', 'Teamwork', 'Leadership', 'Operational', 'Strategic'];
$goalTypes = ['Individual Goal', 'Team Goal', 'Department Goal', 'Organizational Goal', 'Development Goal'];

$successMessage = $_SESSION['goal_success'] ?? '';
$errorMessage = $_SESSION['goal_error'] ?? '';
unset($_SESSION['goal_success'], $_SESSION['goal_error']);

$statusSummary = [
    'In Progress' => (int) ($overview['stats']['in_progress_goals'] ?? 0),
    'Pending' => (int) ($overview['stats']['pending_goals'] ?? 0),
    'Completed' => (int) ($overview['stats']['completed_goals'] ?? 0),
    'Overdue' => (int) ($overview['stats']['overdue_goals'] ?? 0),
];

$prioritySummary = [
    'High' => 0,
    'Medium' => 0,
    'Low' => 0,
];
foreach ($goals as $goal) {
    $priority = (string) ($goal['priority_level'] ?? 'Medium');
    if (isset($prioritySummary[$priority])) {
        $prioritySummary[$priority]++;
    }
}

$totalGoalStatus = array_sum($statusSummary);
$statusChartStyle = '';
if ($totalGoalStatus > 0) {
    $segments = [];
    $start = 0;
    $statusColors = [
        'In Progress' => '#2db6d6',
        'Pending' => '#f4b942',
        'Completed' => '#4caf50',
        'Overdue' => '#f15d5d',
    ];
    foreach ($statusSummary as $label => $value) {
        if ($value <= 0) {
            continue;
        }
        $end = $start + ((float) $value / (float) $totalGoalStatus) * 100;
        $segments[] = $statusColors[$label] . ' ' . $start . '% ' . $end . '%';
        $start = $end;
    }
    $statusChartStyle = 'background: conic-gradient(' . implode(', ', $segments) . ');';
}

$upcomingDeadlines = [];
foreach ($goals as $goal) {
    $dueDate = $goal['due_date'] ?? null;
    if ($dueDate === null || $dueDate === '') {
        continue;
    }
    if (in_array((string) ($goal['status'] ?? ''), ['Completed', 'Cancelled'])) {
        continue;
    }
    $upcomingDeadlines[] = $goal;
}
usort($upcomingDeadlines, function ($a, $b) {
    $aDate = $a['due_date'] ?? null;
    $bDate = $b['due_date'] ?? null;
    if ($aDate === $bDate) return 0;
    return ($aDate < $bDate) ? -1 : 1;
});
$upcomingDeadlines = array_slice($upcomingDeadlines, 0, 5);

$priorityTotal = array_sum($prioritySummary);
$priorityChart = [];
foreach (['High', 'Medium', 'Low'] as $label) {
    $count = (int) ($prioritySummary[$label] ?? 0);
    $percentage = $priorityTotal > 0 ? round(($count / $priorityTotal) * 100) : 0;
    $priorityChart[$label] = ['count' => $count, 'percent' => $percentage];
}
?>

<link rel="stylesheet" href="css/pages/goal-setting.css">

<div class="goal-setting-module">
    <div class="goal-toolbar">
        <div>
            <h2>Goal Setting</h2>
            <p>Track employee objectives, monitoring progress, and performance alignment.</p>
        </div>
        <div class="goal-toolbar-actions">
            <button type="button" class="primary-btn" data-open-modal="goal-form-modal">
                <i class="fa-solid fa-plus"></i> Add Goal
            </button>
        </div>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="alert success" role="alert"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <div class="alert error" role="alert"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <div class="goal-dashboard-grid">
        <div class="goal-stat-card">
            <div class="stat-label">Total Goals</div>
            <div class="stat-value"><?= number_format((int) ($overview['stats']['total_goals'] ?? 0)) ?></div>
            <div class="stat-meta">All current goals</div>
        </div>
        <div class="goal-stat-card">
            <div class="stat-label">Active Goals</div>
            <div class="stat-value"><?= number_format((int) ($overview['stats']['active_goals'] ?? 0)) ?></div>
            <div class="stat-meta">Live assignments</div>
        </div>
        <div class="goal-stat-card">
            <div class="stat-label">Completed</div>
            <div class="stat-value"><?= number_format((int) ($overview['stats']['completed_goals'] ?? 0)) ?></div>
            <div class="stat-meta">Finished goals</div>
        </div>
        <div class="goal-stat-card">
            <div class="stat-label">In Progress</div>
            <div class="stat-value"><?= number_format((int) ($overview['stats']['in_progress_goals'] ?? 0)) ?></div>
            <div class="stat-meta">Ongoing work</div>
        </div>
        <div class="goal-stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?= number_format((int) ($overview['stats']['pending_goals'] ?? 0)) ?></div>
            <div class="stat-meta">Awaiting review</div>
        </div>
        <div class="goal-stat-card">
            <div class="stat-label">Overdue</div>
            <div class="stat-value"><?= number_format((int) ($overview['stats']['overdue_goals'] ?? 0)) ?></div>
            <div class="stat-meta">Past due dates</div>
        </div>
        <div class="goal-stat-card">
            <div class="stat-label">High Priority</div>
            <div class="stat-value"><?= number_format((int) ($overview['stats']['high_priority_goals'] ?? 0)) ?></div>
            <div class="stat-meta">Critical items</div>
        </div>
    </div>

    <div class="goal-insight-grid">
        <div class="insight-card">
            <h3>Goals by Status</h3>
            <?php if ($totalGoalStatus > 0): ?>
                <div class="status-chart-wrap">
                    <div class="donut-chart" style="<?= htmlspecialchars($statusChartStyle) ?>">
                        <div class="donut-center">
                            <strong><?= $totalGoalStatus ?></strong>
                            <span>Total</span>
                        </div>
                    </div>
                    <div class="status-legend">
                        <?php foreach ($statusSummary as $label => $count): ?>
                            <?php if ($count <= 0) continue; ?>
                            <?php $pct = $totalGoalStatus > 0 ? round(($count / $totalGoalStatus) * 100) : 0; ?>
                            <div class="legend-row">
                                <span class="legend-label"><i class="swatch" style="background: <?= ['In Progress' => '#2db6d6', 'Pending' => '#f4b942', 'Completed' => '#4caf50', 'Overdue' => '#f15d5d'][$label] ?? '#94a3b8' ?>;"></i><?= htmlspecialchars($label) ?></span>
                                <span class="legend-value"><?= $pct ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state compact">
                    <h4>No data available</h4>
                    <p>No goals available.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="insight-card">
            <h3>Goals by Priority</h3>
            <?php if ($priorityTotal > 0): ?>
                <div class="priority-list">
                    <?php foreach (['High', 'Medium', 'Low'] as $label): ?>
                        <?php $meta = $priorityChart[$label]; ?>
                        <div class="priority-row">
                            <div class="priority-label-row">
                                <span class="priority-label"><?= htmlspecialchars($label) ?></span>
                                <span class="priority-count"><?= $meta['count'] ?></span>
                            </div>
                            <div class="priority-track">
                                <span style="width: <?= max(4, $meta['percent']) ?>%; background: <?= $label === 'High' ? '#f15d5d' : ($label === 'Medium' ? '#f4b942' : '#4caf50') ?>;"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state compact">
                    <h4>No data available</h4>
                    <p>No priority data.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="insight-card deadlines-card">
            <h3>Upcoming Deadlines</h3>
            <?php if (!empty($upcomingDeadlines)): ?>
                <ul class="deadline-list">
                    <?php foreach ($upcomingDeadlines as $deadline): ?>
                        <?php $dueDate = $deadline['due_date'] ?? ''; ?>
                        <?php $diffDays = !empty($dueDate) ? max(0, (int) ((strtotime($dueDate) - time()) / 86400)) : 0; ?>
                        <li>
                            <div class="deadline-icon"><i class="fa-solid fa-bullseye"></i></div>
                            <div class="deadline-body">
                                <strong><?= htmlspecialchars((string) ($deadline['goal_title'] ?? 'Untitled Goal')) ?></strong>
                                <span><?= htmlspecialchars((string) ($deadline['employee_name'] ?? 'Unknown Employee')) ?></span>
                                <small>Due in <?= $diffDays ?> days</small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="view-all-link" href="#goal-management">View all deadlines →</a>
            <?php else: ?>
                <div class="empty-state compact">
                    <h4>No records yet</h4>
                    <p>No upcoming deadlines.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="goal-panel" id="goal-management">
        <div class="goal-panel-header">
            <h3>Goal Management</h3>
        </div>

        <form method="GET" class="goal-filters">
            <input type="hidden" name="page" value="goal-setting">
            <div class="field">
                <label for="search">Search</label>
                <input id="search" type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Goal title / employee">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    <?php foreach ($goalStatuses as $status): ?>
                        <option value="<?= htmlspecialchars($status) ?>" <?= ($filters['status'] === $status) ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="">All priorities</option>
                    <?php foreach ($goalPriorities as $priority): ?>
                        <option value="<?= htmlspecialchars($priority) ?>" <?= ($filters['priority'] === $priority) ? 'selected' : '' ?>><?= htmlspecialchars($priority) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($goalCategories as $category): ?>
                        <option value="<?= htmlspecialchars($category) ?>" <?= ($filters['category'] === $category) ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="employee_id">Employee</label>
                <select id="employee_id" name="employee_id">
                    <option value="">All employees</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= htmlspecialchars((string) ($employee['employee_id'] ?? '')) ?>" <?= ($filters['employee_id'] === (string) ($employee['employee_id'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($employee['employee_name'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="from_date">From</label>
                <input id="from_date" type="date" name="from_date" value="<?= htmlspecialchars($filters['from_date']) ?>">
            </div>
            <div class="field">
                <label for="to_date">To</label>
                <input id="to_date" type="date" name="to_date" value="<?= htmlspecialchars($filters['to_date']) ?>">
            </div>
            <div class="field">
                <label>&nbsp;</label>
                <button type="submit" class="secondary-btn"><i class="fa-solid fa-filter"></i> Apply</button>
            </div>
        </form>

        <?php if (!empty($goals)): ?>
            <div class="goal-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Goal ID</th>
                            <th>Employee</th>
                            <th>Goal Title</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Start Date</th>
                            <th>Target Date</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Assigned By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($goals as $goal): ?>
                            <?php $progressValue = (int) ($goal['progress_percentage'] ?? 0); ?>
                            <tr>
                                <td>#<?= (int) ($goal['goal_id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string) ($goal['employee_name'] ?? 'N/A')) ?></td>
                                <td><?= htmlspecialchars((string) ($goal['goal_title'] ?? 'Untitled Goal')) ?></td>
                                <td><?= htmlspecialchars((string) ($goal['goal_category'] ?? 'Performance')) ?></td>
                                <td><span class="badge <?= strtolower((string) ($goal['priority_level'] ?? 'Medium')) ?>"><?= htmlspecialchars((string) ($goal['priority_level'] ?? 'Medium')) ?></span></td>
                                <td><?= !empty($goal['start_date']) ? htmlspecialchars(date('M d, Y', strtotime($goal['start_date']))) : 'N/A' ?></td>
                                <td><?= !empty($goal['due_date']) ? htmlspecialchars(date('M d, Y', strtotime($goal['due_date']))) : 'N/A' ?></td>
                                <td>
                                    <div class="goal-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= min(100, max(0, $progressValue)) ?>%"></div>
                                        </div>
                                        <span><?= $progressValue ?>%</span>
                                    </div>
                                </td>
                                <td><span class="badge <?= strtolower(str_replace(' ', '-', (string) ($goal['status'] ?? 'Draft'))) ?>"><?= htmlspecialchars((string) ($goal['status'] ?? 'Draft')) ?></span></td>
                                <td><?= htmlspecialchars((string) ($goal['assigned_by_name'] ?? $goal['supervisor_name'] ?? 'System')) ?></td>
                                <td>
                                    <div class="goal-actions">
                                        <a class="icon-btn" href="?page=goal-setting&goal_id=<?= (int) ($goal['goal_id'] ?? 0) ?>" title="View goal"><i class="fa-solid fa-eye"></i></a>
                                        <a class="icon-btn" href="?page=goal-setting&goal_id=<?= (int) ($goal['goal_id'] ?? 0) ?>&mode=edit" title="Edit goal"><i class="fa-solid fa-pen-to-square"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h4>No Goals Yet</h4>
                <p>No employee goals have been created yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="goal-modal <?= $selectedGoal ? 'open' : '' ?>" id="goal-detail-modal">
    <div class="goal-modal-content">
        <div class="goal-modal-header">
            <h3><?= $selectedGoal ? htmlspecialchars((string) ($selectedGoal['goal_title'] ?? 'Goal Details')) : 'Goal Details' ?></h3>
            <button type="button" class="close-modal" aria-label="Close" data-close-modal="goal-detail-modal">×</button>
        </div>

        <?php if ($selectedGoal): ?>
            <div class="goal-detail-layout">
                <div class="goal-detail-card">
                    <div class="detail-block">
                        <div class="detail-head">
                            <h4><?= htmlspecialchars((string) ($selectedGoal['goal_title'] ?? 'Untitled Goal')) ?></h4>
                            <span class="badge <?= strtolower(str_replace(' ', '-', (string) ($selectedGoal['status'] ?? 'Draft'))) ?>"><?= htmlspecialchars((string) ($selectedGoal['status'] ?? 'Draft')) ?></span>
                        </div>
                        <div class="detail-metadata">
                            <div class="meta-item"><span>Employee</span><strong><?= htmlspecialchars((string) ($selectedGoal['employee_name'] ?? 'N/A')) ?></strong></div>
                            <div class="meta-item"><span>Department</span><strong><?= htmlspecialchars((string) ($selectedGoal['department'] ?? 'N/A')) ?></strong></div>
                            <div class="meta-item"><span>Category</span><strong><?= htmlspecialchars((string) ($selectedGoal['goal_category'] ?? 'Performance')) ?></strong></div>
                            <div class="meta-item"><span>Type</span><strong><?= htmlspecialchars((string) ($selectedGoal['goal_type'] ?? 'Individual Goal')) ?></strong></div>
                            <div class="meta-item"><span>Priority</span><strong><?= htmlspecialchars((string) ($selectedGoal['priority_level'] ?? 'Medium')) ?></strong></div>
                            <div class="meta-item"><span>Progress</span><strong><?= (int) ($selectedGoal['progress_percentage'] ?? 0) ?>%</strong></div>
                            <div class="meta-item"><span>Start Date</span><strong><?= !empty($selectedGoal['start_date']) ? htmlspecialchars(date('M d, Y', strtotime($selectedGoal['start_date']))) : 'N/A' ?></strong></div>
                            <div class="meta-item"><span>Target Date</span><strong><?= !empty($selectedGoal['due_date']) ? htmlspecialchars(date('M d, Y', strtotime($selectedGoal['due_date']))) : 'N/A' ?></strong></div>
                            <div class="meta-item"><span>Target Result</span><strong><?= htmlspecialchars((string) ($selectedGoal['target_completion_percentage'] ?? '0')) ?>%</strong></div>
                            <div class="meta-item"><span>Assigned By</span><strong><?= htmlspecialchars((string) ($selectedGoal['supervisor_name'] ?? $selectedGoal['assigned_by_name'] ?? 'System')) ?></strong></div>
                        </div>
                    </div>

                    <div class="detail-block">
                        <h4>Description</h4>
                        <p><?= nl2br(htmlspecialchars((string) ($selectedGoal['goal_description'] ?? 'No description provided.'))) ?></p>
                    </div>

                    <div class="detail-block">
                        <h4>Measurement Criteria & Expected Outcome</h4>
                        <p><?= nl2br(htmlspecialchars((string) ($selectedGoal['expected_outcome'] ?? 'No criteria provided.'))) ?></p>
                        <p><?= nl2br(htmlspecialchars((string) ($selectedGoal['kpi_target'] ?? 'No target details provided.'))) ?></p>
                    </div>

                    <div class="detail-block">
                        <h4>Notes</h4>
                        <p><?= nl2br(htmlspecialchars((string) ($selectedGoal['smart_notes'] ?? 'No notes available.'))) ?></p>
                    </div>
                </div>

                <div class="goal-side-panel">
                    <div class="detail-block">
                        <h4>Goal Progress</h4>
                        <div class="goal-progress" style="margin-bottom: 0.7rem;">
                            <div class="progress-bar" style="width: 100%;"><div class="progress-fill" style="width: <?= min(100, (int) ($selectedGoal['progress_percentage'] ?? 0)) ?>%"></div></div>
                            <span><?= (int) ($selectedGoal['progress_percentage'] ?? 0) ?>%</span>
                        </div>
                        <p><?= nl2br(htmlspecialchars((string) ($selectedGoal['progress_notes'] ?? 'No progress notes recorded.'))) ?></p>
                    </div>

                    <div class="detail-block">
                        <h4>Goal History</h4>
                        <?php if (!empty($goalHistory)): ?>
                            <ul class="goal-timeline">
                                <?php foreach ($goalHistory as $history): ?>
                                    <li>
                                        <strong><?= htmlspecialchars((string) ($history['action'] ?? 'Goal event')) ?></strong>
                                        <span><?= htmlspecialchars((string) ($history['details'] ?? 'No details')) ?> · <?= !empty($history['created_at']) ? htmlspecialchars(date('M d, Y', strtotime($history['created_at']))) : 'N/A' ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No history recorded yet.</p>
                        <?php endif; ?>
                    </div>

                    <div class="detail-block">
                        <h4>Progress Timeline</h4>
                        <?php if (!empty($progressEntries)): ?>
                            <ul class="goal-timeline">
                                <?php foreach ($progressEntries as $entry): ?>
                                    <li>
                                        <strong><?= (int) ($entry['progress_percentage'] ?? 0) ?>% progress</strong>
                                        <span><?= !empty($entry['progress_notes']) ? htmlspecialchars((string) $entry['progress_notes']) : 'No notes' ?> · <?= !empty($entry['update_date']) ? htmlspecialchars(date('M d, Y', strtotime($entry['update_date']))) : 'N/A' ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No progress updates available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="goal-modal <?= $isEditMode ? 'open' : '' ?>" id="goal-form-modal">
    <div class="goal-modal-content">
        <div class="goal-modal-header">
            <h3><?= $isEditMode ? 'Edit Goal' : 'Create Goal' ?></h3>
            <button type="button" class="close-modal" aria-label="Close" data-close-modal="goal-form-modal">×</button>
        </div>

        <?php $formGoal = $isEditMode ? ($selectedGoal ?? []) : []; ?>
        <form method="POST" class="goal-detail-form" data-skip>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $isEditMode ? 'update_goal' : 'create_goal' ?>">
            <?php if ($isEditMode && !empty($formGoal['goal_id'])): ?>
                <input type="hidden" name="goal_id" value="<?= (int) $formGoal['goal_id'] ?>">
            <?php endif; ?>

            <div class="goal-form-grid">
                <div class="field wide">
                    <label for="goal_title">Goal Title</label>
                    <input id="goal_title" name="goal_title" type="text" value="<?= htmlspecialchars((string) ($formGoal['goal_title'] ?? '')) ?>" required>
                </div>

                <div class="field wide">
                    <label for="goal_description">Goal Description</label>
                    <textarea id="goal_description" name="goal_description" rows="4" required><?= htmlspecialchars((string) ($formGoal['goal_description'] ?? '')) ?></textarea>
                </div>

                <div class="field">
                    <label for="employee_id_input">Employee</label>
                    <select id="employee_id_input" name="employee_id" required>
                        <option value="">Select employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= htmlspecialchars((string) ($employee['employee_id'] ?? '')) ?>" <?= (($formGoal['employee_id'] ?? '') === (string) ($employee['employee_id'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($employee['employee_name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="assigned_by">Assigned By</label>
                    <input id="assigned_by" name="supervisor_name" type="text" value="<?= htmlspecialchars((string) ($formGoal['supervisor_name'] ?? $_SESSION['employee_name'] ?? 'System')) ?>">
                </div>

                <div class="field">
                    <label for="goal_category">Goal Category</label>
                    <select id="goal_category" name="goal_category">
                        <?php foreach ($goalCategories as $category): ?>
                            <option value="<?= htmlspecialchars($category) ?>" <?= (($formGoal['goal_category'] ?? 'Performance') === $category) ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="goal_type">Goal Type</label>
                    <select id="goal_type" name="goal_type">
                        <?php foreach ($goalTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= (($formGoal['goal_type'] ?? 'Individual Goal') === $type) ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="start_date">Start Date</label>
                    <input id="start_date" name="start_date" type="date" value="<?= htmlspecialchars((string) ($formGoal['start_date'] ?? '')) ?>">
                </div>

                <div class="field">
                    <label for="due_date">Target Date</label>
                    <input id="due_date" name="due_date" type="date" value="<?= htmlspecialchars((string) ($formGoal['due_date'] ?? '')) ?>">
                </div>

                <div class="field">
                    <label for="priority_level">Priority</label>
                    <select id="priority_level" name="priority_level">
                        <?php foreach ($goalPriorities as $priority): ?>
                            <option value="<?= htmlspecialchars($priority) ?>" <?= (($formGoal['priority_level'] ?? 'Medium') === $priority) ? 'selected' : '' ?>><?= htmlspecialchars($priority) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php foreach ($goalStatuses as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>" <?= (($formGoal['status'] ?? 'Draft') === $status) ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="target_completion_percentage">Target/Expected Result</label>
                    <input id="target_completion_percentage" name="target_completion_percentage" type="number" min="0" max="100" value="<?= htmlspecialchars((string) ($formGoal['target_completion_percentage'] ?? 0)) ?>">
                </div>

                <div class="field">
                    <label for="progress_percentage">Current Progress</label>
                    <input id="progress_percentage" name="progress_percentage" type="number" min="0" max="100" value="<?= htmlspecialchars((string) ($formGoal['progress_percentage'] ?? 0)) ?>">
                </div>

                <div class="field wide">
                    <label for="expected_outcome">Measurement Criteria</label>
                    <textarea id="expected_outcome" name="expected_outcome" rows="3"><?= htmlspecialchars((string) ($formGoal['expected_outcome'] ?? '')) ?></textarea>
                </div>

                <div class="field wide">
                    <label for="smart_notes">Notes</label>
                    <textarea id="smart_notes" name="smart_notes" rows="3"><?= htmlspecialchars((string) ($formGoal['smart_notes'] ?? '')) ?></textarea>
                </div>
            </div>

            <div class="goal-button-row" style="margin-top: 1rem; justify-content: flex-end;">
                <button type="button" class="ghost-btn" data-close-modal="goal-form-modal">Cancel</button>
                <button type="submit" class="primary-btn"><?= $isEditMode ? 'Save Changes' : 'Create Goal' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalIds = ['goal-form-modal', 'goal-detail-modal'];
        const openButtons = document.querySelectorAll('[data-open-modal]');
        const closeButtons = document.querySelectorAll('[data-close-modal]');

        openButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-open-modal');
                const modal = document.getElementById(id);
                if (modal) {
                    modal.classList.add('open');
                }
            });
        });

        closeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-close-modal');
                const modal = document.getElementById(id);
                if (modal) {
                    modal.classList.remove('open');
                    if (id === 'goal-detail-modal') {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('goal_id');
                        url.searchParams.delete('mode');
                        window.history.replaceState({}, '', url);
                    }
                    if (id === 'goal-form-modal') {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('mode');
                        window.history.replaceState({}, '', url);
                    }
                }
            });
        });

        modalIds.forEach(function (id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    modal.classList.remove('open');
                }
            });
        });
    });
</script>

<style>
    .alert {
        padding: 0.85rem 1rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        font-weight: 600;
    }
    .alert.success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid rgba(34, 197, 94, 0.2);
    }
    .alert.error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
</style>
