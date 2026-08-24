<?php
require_once __DIR__ . '/../classes/AppraisalController.php';

$appraisalController = new AppraisalController();
$dashboard = $appraisalController->getDashboardData();
$stats = $dashboard['stats'] ?? [];
$statusSummary = $dashboard['statusSummary'] ?? [];
$cycles = $dashboard['cycles'] ?? [];
$employees = $appraisalController->getEmployees();
$messages = $appraisalController->getMessages();
$csrfToken = $appraisalController->getCsrfToken();

$filters = [
	'search' => trim((string) ($_GET['search'] ?? '')),
	'status' => trim((string) ($_GET['status'] ?? '')),
	'employee_id' => trim((string) ($_GET['employee_id'] ?? '')),
	'department' => trim((string) ($_GET['department'] ?? '')),
	'cycle_id' => trim((string) ($_GET['cycle_id'] ?? '')),
	'from_date' => trim((string) ($_GET['from_date'] ?? '')),
	'to_date' => trim((string) ($_GET['to_date'] ?? '')),
];

$appraisals = $appraisalController->getAppraisals($filters);
$selectedId = (int) ($_GET['appraisal_id'] ?? 0);
$selectedAppraisal = $selectedId > 0 ? $appraisalController->getSelectedAppraisal($selectedId) : null;
$selectedItems = $selectedAppraisal ? $appraisalController->getAppraisalItems($selectedId) : [];
$departments = array_values(array_unique(array_filter(array_map(
	static fn (array $employee): string => trim((string) ($employee['department'] ?? '')),
	$employees
))));
$statuses = ['Not Started', 'Pending', 'In Progress', 'Completed', 'Overdue'];
$totalStatus = max(1, array_sum(array_map('intval', $statusSummary)));

$formatDate = static function (?string $date): string {
	if (empty($date)) {
		return 'N/A';
	}

	$timestamp = strtotime($date);
	return $timestamp ? date('M d, Y', $timestamp) : 'N/A';
};

$statusClass = static function (string $status): string {
	return strtolower(str_replace([' ', '_'], '-', trim($status)));
};

$statCards = [
	['label' => 'Total Appraisals', 'value' => $stats['total_appraisals'] ?? 0, 'meta' => 'All records', 'icon' => 'fa-users', 'tone' => 'blue'],
	['label' => 'Pending', 'value' => $stats['pending_appraisals'] ?? 0, 'meta' => 'Awaiting start', 'icon' => 'fa-clock', 'tone' => 'amber'],
	['label' => 'In Progress', 'value' => $stats['in_progress_appraisals'] ?? 0, 'meta' => 'Under review', 'icon' => 'fa-file-lines', 'tone' => 'indigo'],
	['label' => 'Completed', 'value' => $stats['completed_appraisals'] ?? 0, 'meta' => 'Finalized', 'icon' => 'fa-circle-check', 'tone' => 'green'],
	['label' => 'Overdue', 'value' => $stats['overdue_appraisals'] ?? 0, 'meta' => 'Past due date', 'icon' => 'fa-circle-exclamation', 'tone' => 'red'],
	['label' => 'Average Rating', 'value' => $stats['average_rating'] !== null ? number_format((float) $stats['average_rating'], 1) : 'N/A', 'meta' => 'Across completed ratings', 'icon' => 'fa-star', 'tone' => 'violet'],
	['label' => 'Active Cycles', 'value' => $stats['active_cycles'] ?? 0, 'meta' => 'Live review periods', 'icon' => 'fa-arrows-rotate', 'tone' => 'teal'],
];
?>

<link rel="stylesheet" href="css/appraisals.css">

<div class="appraisals-page">
	<div class="appraisals-titlebar">
		<div>
			<h1>Appraisals &amp; Review</h1>
			<p>Manage review cycles, employee appraisals, ratings, and performance evaluations.</p>
		</div>
		<div class="appraisals-title-actions">
			<button class="appraisal-button secondary" type="button" data-modal-open="cycle-modal"><i class="fa-regular fa-calendar"></i> New Cycle</button>
			<button class="appraisal-button primary" type="button" data-modal-open="appraisal-detail-modal"><i class="fa-solid fa-plus"></i> New Appraisal</button>
		</div>
	</div>

	<?php if ($messages['success'] !== ''): ?><div class="appraisal-alert success" role="alert"><?= htmlspecialchars($messages['success']) ?></div><?php endif; ?>
	<?php if ($messages['error'] !== ''): ?><div class="appraisal-alert error" role="alert"><?= htmlspecialchars($messages['error']) ?></div><?php endif; ?>

	<section class="appraisal-stat-grid" aria-label="Appraisal summary">
		<?php foreach ($statCards as $card): ?>
			<article class="appraisal-stat-card">
				<div class="stat-card-top"><span><?= htmlspecialchars($card['label']) ?></span><i class="fa-solid <?= htmlspecialchars($card['icon']) ?> <?= htmlspecialchars($card['tone']) ?>"></i></div>
				<strong><?= htmlspecialchars((string) $card['value']) ?></strong>
				<small><?= htmlspecialchars($card['meta']) ?></small>
			</article>
		<?php endforeach; ?>
	</section>

	<section class="appraisal-insights">
		<article class="appraisal-panel status-panel">
			<div class="panel-heading"><h2>Appraisals by Status</h2></div>
			<?php foreach (['Completed', 'In Progress', 'Pending', 'Overdue', 'Not Started'] as $status): ?>
				<?php $count = (int) ($statusSummary[$status] ?? 0); $percentage = (int) round(($count / $totalStatus) * 100); ?>
				<div class="status-row">
					<span><i class="status-dot <?= htmlspecialchars($statusClass($status)) ?>"></i><?= htmlspecialchars($status) ?></span>
					<div class="status-track"><i class="<?= htmlspecialchars($statusClass($status)) ?>" style="width: <?= $percentage ?>%"></i></div>
					<b><?= $percentage ?>%</b>
				</div>
			<?php endforeach; ?>
		</article>

		<article class="appraisal-panel cycles-panel">
			<div class="panel-heading"><h2>Review Cycles</h2><span>Progress</span></div>
			<?php if ($cycles): ?>
				<?php foreach ($cycles as $cycle): ?>
					<?php $cycleCount = (int) ($cycle['appraisal_count'] ?? 0); $cycleTotal = max(1, count(array_filter($appraisals, static fn (array $row): bool => (int) ($row['review_cycle_id'] ?? 0) === (int) ($cycle['cycle_id'] ?? 0)))); $cycleProgress = min(100, (int) round(($cycleCount / $cycleTotal) * 100)); ?>
					<div class="cycle-row">
						<div class="cycle-icon"><i class="fa-regular fa-calendar-check"></i></div>
						<div class="cycle-info"><strong><?= htmlspecialchars($cycle['title'] ?? 'Untitled cycle') ?></strong><small><?= htmlspecialchars($cycle['cycle_period'] ?? 'No period') ?> &middot; <?= $cycleCount ?> appraisals</small><em><?= htmlspecialchars($cycle['status'] ?? 'Inactive') ?></em></div>
						<div class="cycle-progress"><span>Progress</span><b><?= $cycleCount ?> / <?= $cycleTotal ?></b><i><u style="width: <?= $cycleProgress ?>%"></u></i><small><?= $cycleProgress ?>%</small></div>
					</div>
				<?php endforeach; ?>
			<?php else: ?><div class="appraisal-empty">No review cycles have been created yet.</div><?php endif; ?>
			<button class="text-action" type="button" data-modal-open="cycle-modal">View all cycles <i class="fa-solid fa-arrow-right"></i></button>
		</article>
	</section>

	<section class="appraisal-panel management-panel">
		<div class="management-heading"><div><h2>Appraisal Management</h2><p>Search, filter, and review employee appraisal records.</p></div><button class="appraisal-button secondary" type="button" onclick="window.print()"><i class="fa-solid fa-download"></i> Export</button></div>
		<form class="appraisal-filters" method="get">
			<input type="hidden" name="page" value="appraisals-review">
			<label class="search-field"><span>Search appraisals...</span><input type="search" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Search appraisals..."><i class="fa-solid fa-magnifying-glass"></i></label>
			<label><span>Employee</span><select name="employee_id"><option value="">All employees</option><?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['employee_id'] ?>" <?= (string) $employee['employee_id'] === $filters['employee_id'] ? 'selected' : '' ?>><?= htmlspecialchars($employee['employee_name'] ?? 'Unknown') ?></option><?php endforeach; ?></select></label>
			<label><span>Department</span><select name="department"><option value="">All departments</option><?php foreach ($departments as $department): ?><option value="<?= htmlspecialchars($department) ?>" <?= $department === $filters['department'] ? 'selected' : '' ?>><?= htmlspecialchars($department) ?></option><?php endforeach; ?></select></label>
			<label><span>Review Cycle</span><select name="cycle_id"><option value="">All cycles</option><?php foreach ($cycles as $cycle): ?><option value="<?= (int) $cycle['cycle_id'] ?>" <?= (string) $cycle['cycle_id'] === $filters['cycle_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cycle['title'] ?? 'Untitled') ?></option><?php endforeach; ?></select></label>
			<label><span>From</span><input type="date" name="from_date" value="<?= htmlspecialchars($filters['from_date']) ?>"></label>
			<label><span>To</span><input type="date" name="to_date" value="<?= htmlspecialchars($filters['to_date']) ?>"></label>
			<label><span>Status</span><select name="status"><option value="">All statuses</option><?php foreach ($statuses as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= $status === $filters['status'] ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option><?php endforeach; ?></select></label>
			<button class="appraisal-button primary filter-submit" type="submit">Apply</button><a class="appraisal-button secondary" href="?page=appraisals-review">Reset</a>
		</form>

		<div class="appraisal-table-wrap"><table class="appraisal-table"><thead><tr><th>ID</th><th>Employee</th><th>Department</th><th>Review Cycle</th><th>Reviewer</th><th>Status</th><th>Rating</th><th>Due Date</th><th>Actions</th></tr></thead><tbody>
			<?php if ($appraisals): foreach ($appraisals as $appraisal): ?>
				<tr><td>#APP-<?= str_pad((string) ($appraisal['appraisal_id'] ?? 0), 5, '0', STR_PAD_LEFT) ?></td><td><strong><?= htmlspecialchars($appraisal['employee_name'] ?? 'Unknown') ?></strong><small>ID <?= (int) ($appraisal['employee_id'] ?? 0) ?></small></td><td><?= htmlspecialchars($appraisal['department'] ?? 'N/A') ?></td><td><strong><?= htmlspecialchars($appraisal['cycle_title'] ?? 'N/A') ?></strong><small><?= htmlspecialchars($appraisal['appraisal_period'] ?? '') ?></small></td><td><?= htmlspecialchars($appraisal['reviewer_name'] ?? 'Unassigned') ?></td><td><span class="status-pill <?= htmlspecialchars($statusClass($appraisal['status'] ?? 'Not Started')) ?>"><?= htmlspecialchars($appraisal['status'] ?? 'Not Started') ?></span></td><td><strong><?= $appraisal['overall_rating'] !== null ? number_format((float) $appraisal['overall_rating'], 1) : 'N/A' ?></strong><?php if ($appraisal['overall_rating'] !== null): ?><small class="stars">★★★★★</small><?php endif; ?></td><td><?= htmlspecialchars($formatDate($appraisal['due_date'] ?? null)) ?></td><td><a class="icon-action" href="?page=appraisals-review&amp;appraisal_id=<?= (int) $appraisal['appraisal_id'] ?>" title="Review appraisal"><i class="fa-solid fa-ellipsis-vertical"></i></a></td></tr>
			<?php endforeach; else: ?><tr><td colspan="9"><div class="appraisal-empty">No appraisal records match the current filters.</div></td></tr><?php endif; ?>
		</tbody></table></div>
	</section>
</div>

<div class="appraisal-modal-layer <?= $selectedAppraisal ? 'is-open' : '' ?>" data-modal="appraisal-detail-modal" aria-hidden="<?= $selectedAppraisal ? 'false' : 'true' ?>">
	<div class="appraisal-modal"><button class="modal-close" type="button" data-modal-close><i class="fa-solid fa-xmark"></i></button><h2><?= $selectedAppraisal ? 'Review Appraisal' : 'New Appraisal' ?></h2>
		<?php if ($selectedAppraisal): ?><p class="modal-subtitle"><?= htmlspecialchars($selectedAppraisal['employee_name'] ?? 'Employee') ?> &middot; <?= htmlspecialchars($selectedAppraisal['cycle_title'] ?? 'No cycle') ?></p><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="action" value="update_status"><input type="hidden" name="appraisal_id" value="<?= (int) $selectedAppraisal['appraisal_id'] ?>"><label>Status<select name="status"><?php foreach ($statuses as $status): ?><option <?= ($selectedAppraisal['status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option><?php endforeach; ?></select></label><label>Review comment<textarea name="status_comment" rows="3" placeholder="Add a review note..."></textarea></label><button class="appraisal-button primary" type="submit">Save status</button></form>
		<?php else: ?><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="action" value="create_appraisal"><label>Employee<select name="employee_id" required><option value="">Select employee</option><?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['employee_id'] ?>" data-name="<?= htmlspecialchars($employee['employee_name'] ?? '') ?>" data-department="<?= htmlspecialchars($employee['department'] ?? '') ?>"><?= htmlspecialchars($employee['employee_name'] ?? 'Unknown') ?></option><?php endforeach; ?></select></label><input type="hidden" name="employee_name"><input type="hidden" name="department"><label>Review cycle<select name="review_cycle_id"><option value="">Select cycle</option><?php foreach ($cycles as $cycle): ?><option value="<?= (int) $cycle['cycle_id'] ?>"><?= htmlspecialchars($cycle['title'] ?? 'Untitled') ?></option><?php endforeach; ?></select></label><label>Due date<input type="date" name="due_date"></label><button class="appraisal-button primary" type="submit">Create appraisal</button></form><?php endif; ?>
	</div>
</div>

<div class="appraisal-modal-layer" data-modal="cycle-modal" aria-hidden="true"><div class="appraisal-modal"><button class="modal-close" type="button" data-modal-close><i class="fa-solid fa-xmark"></i></button><h2>New Review Cycle</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="action" value="create_cycle"><label>Cycle title<input type="text" name="title" required placeholder="2026 Annual Review"></label><label>Cycle period<input type="text" name="cycle_period" placeholder="Jan 1, 2026 - Dec 31, 2026"></label><label>Start date<input type="date" name="start_date"></label><label>End date<input type="date" name="end_date"></label><input type="hidden" name="status" value="Active"><button class="appraisal-button primary" type="submit">Create cycle</button></form></div></div>

<script>
document.querySelectorAll('[data-modal-open]').forEach(function (button) {
	button.addEventListener('click', function () {
		var modal = document.querySelector('[data-modal="' + button.dataset.modalOpen + '"]');
		if (modal) { modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); }
	});
});
document.querySelectorAll('[data-modal-close]').forEach(function (button) {
	button.addEventListener('click', function () { var modal = button.closest('.appraisal-modal-layer'); modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); });
});
document.querySelectorAll('.appraisal-modal-layer').forEach(function (modal) {
	modal.addEventListener('click', function (event) { if (event.target === modal) { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); } });
});
var employeeSelect = document.querySelector('select[name="employee_id"]');
if (employeeSelect) employeeSelect.addEventListener('change', function () { var option = employeeSelect.options[employeeSelect.selectedIndex]; document.querySelector('input[name="employee_name"]').value = option.dataset.name || ''; document.querySelector('input[name="department"]').value = option.dataset.department || ''; });
</script>
