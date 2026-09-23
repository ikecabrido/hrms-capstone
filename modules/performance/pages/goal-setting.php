<?php
require_once __DIR__ . '/../controller/GoalSettingController.php';

$controller = new GoalSettingController();
$overview = $controller->getDashboardData();
$employees = $controller->getEmployees();
$filters = [];
foreach (['search', 'status', 'priority', 'category', 'employee_id', 'from_date', 'to_date'] as $key) {
	$filters[$key] = trim((string) ($_GET[$key] ?? ''));
}
$goals = $controller->getGoals($filters);
$selectedId = (int) ($_GET['goal_id'] ?? 0);
$selectedGoal = $selectedId > 0 ? $controller->getSelectedGoal($selectedId) : null;
$isEdit = ($_GET['mode'] ?? '') === 'edit';
$csrfToken = $controller->getCsrfToken();
$stats = $overview['stats'] ?? [];
$statuses = ['Draft', 'Pending', 'Active', 'In Progress', 'Completed', 'Overdue', 'Cancelled'];
$priorities = ['Low', 'Medium', 'High'];
$categories = ['Performance', 'Productivity', 'Quality', 'Professional Development', 'Teamwork', 'Leadership', 'Operational', 'Strategic'];
$success = $_SESSION['goal_success'] ?? '';
$error = $_SESSION['goal_error'] ?? '';
unset($_SESSION['goal_success'], $_SESSION['goal_error']);
$month = max(1, min(12, (int) ($_GET['month'] ?? date('n'))));
$year = (int) ($_GET['year'] ?? date('Y'));
$monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
$daysInMonth = (int) $monthStart->format('t');
$firstWeekday = (int) $monthStart->format('N');
$monthLabel = $monthStart->format('F Y');
$previous = $monthStart->modify('-1 month');
$next = $monthStart->modify('+1 month');
$events = $controller->getEvents($monthStart->format('Y-m-d'), $monthStart->modify('last day of this month')->format('Y-m-d'));
$calendarEvents = [];
foreach ($events as $event) {
	$calendarEvents[$event['start_date']][] = $event;
}
$calendarGoals = [];
foreach ($goals as $goal) {
	if (empty($goal['due_date'])) continue;
	$calendarGoals[$goal['due_date']][] = $goal;
}
$calendarGoalPriorities = [];
foreach ($calendarGoals as $date => $dateGoals) {
	foreach ($dateGoals as $goal) {
		$title = (string) ($goal['goal_title'] ?? 'Goal');
		$calendarGoalPriorities[$date][$title] = strtolower((string) ($goal['priority_level'] ?? 'Medium'));
	}
}
$recentRecords = $goals;
$recentEvents = $controller->getEvents('2000-01-01', '2100-12-31');
foreach ($recentEvents as $event) {
	$recentRecords[] = [
		'event_id' => $event['event_id'],
		'event_title' => $event['event_title'],
		'employee_name' => $event['employee_name'] ?: 'All employees',
		'due_date' => $event['start_date'],
		'priority_level' => $event['event_type'],
		'progress_percentage' => null,
		'status' => $event['status'],
		'is_calendar_event' => true,
	];
}
?>
<link rel="stylesheet" href="css/pages/goal-setting.css?v=6">
<style>
.goal-calendar-toolbar,.goal-calendar-grid{display:flex}.goal-calendar-toolbar{align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem}.goal-calendar-toolbar h3{margin:0;font-size:1.15rem}.calendar-nav{display:flex;align-items:center;gap:.5rem}.calendar-nav a,.calendar-nav button{border:1px solid rgba(148,163,184,.3);border-radius:10px;background:#fff;color:#334155;padding:.55rem .75rem;text-decoration:none;cursor:pointer}.goal-calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));border-top:1px solid #e2e8f0;border-left:1px solid #e2e8f0}.calendar-weekday,.calendar-day{min-height:92px;padding:.55rem;border-right:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0}.calendar-weekday{min-height:auto;background:#f8fafc;color:#64748b;font-size:.72rem;font-weight:700;text-transform:uppercase}.calendar-day.muted{background:#f8fafc;color:#cbd5e1}.calendar-day-number{font-size:.78rem;font-weight:700;color:#475569}.calendar-event{display:block;width:100%;margin-top:.35rem;padding:.28rem .4rem;border:0;border-radius:6px;background:#e0f2fe;color:#075985;text-align:left;font-size:.7rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer}.calendar-event:hover{background:#bae6fd}.goal-detail-actions{display:flex;align-items:center;flex-wrap:wrap;gap:.75rem;margin-top:1rem}.goal-detail-actions form{margin:0}.danger-btn{border:0;border-radius:12px;padding:.7rem 1rem;background:#fee2e2;color:#b91c1c;font-weight:600;cursor:pointer}.event-modal .goal-form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}@media(max-width:720px){.calendar-day{min-height:72px;padding:.35rem}.calendar-event{font-size:.62rem}.event-modal .goal-form-grid{grid-template-columns:1fr}}
</style>
<style>
.goal-insight-grid > .insight-card:nth-child(2),
.goal-insight-grid > .insight-card:nth-child(3) {
	display: none;
}
.goal-insight-grid {
	grid-template-columns: 1fr;
}
#goal-management .goal-panel-header h3 {
	font-size: 0;
}
#goal-management .goal-panel-header h3::after {
	content: 'Goal Management';
	font-size: 1.17rem;
}
.goal-setting-module {
	display: grid;
	grid-template-columns: minmax(0, 1fr) minmax(220px, 280px);
	grid-template-areas:
		'toolbar toolbar'
		'stats stats'
		'calendar upcoming'
		'management management';
	gap: 1rem;
}
.goal-setting-module > .goal-toolbar { grid-area: toolbar; }
.goal-setting-module > .goal-dashboard-grid { grid-area: stats; }
.goal-setting-module > .goal-panel:not(#goal-management) { grid-area: calendar; }
.goal-setting-module > .goal-insight-grid { grid-area: upcoming; display: block; }
.goal-setting-module > #goal-management { grid-area: management; }
.goal-insight-grid > .insight-card:first-child { height: 100%; }
.upcoming-pagination { display: flex; align-items: center; gap: .35rem; margin-top: 1rem; flex-wrap: wrap; }
.upcoming-pagination button { min-width: 2rem; height: 2rem; padding: 0 .45rem; border: 1px solid #dbe3ef; border-radius: 6px; background: #fff; color: #475569; cursor: pointer; }
.upcoming-pagination button[aria-current="page"] { background: #2563eb; border-color: #2563eb; color: #fff; }
.goal-pagination-wrap { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
.goal-pagination { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.goal-page-buttons { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
.goal-page-btn, .goal-pagination-nav { min-width: 2.2rem; height: 2.2rem; border: 1px solid #dfe7f3; border-radius: 8px; background: #fff; color: #475569; font-weight: 600; cursor: pointer; padding: 0 .7rem; }
.goal-page-btn.is-active, .goal-page-btn:hover:not(:disabled), .goal-pagination-nav:hover:not(:disabled) { background: #2563eb; border-color: #2563eb; color: #fff; }
.goal-pagination-nav:disabled, .goal-page-btn:disabled { opacity: .45; cursor: not-allowed; }
.goal-pagination-summary { color: #64748b; font-size: .85rem; }
.insight-card { display: flex; flex-direction: column; min-height: 240px; overflow: hidden; }
.upcoming-events-box { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
.upcoming-events-box .deadline-list { margin: 0; flex: 1 1 auto; min-height: 0; }
.upcoming-events-box .upcoming-pagination { margin-top: auto; padding-top: 1rem; display: flex; align-items: center; justify-content: center; gap: .45rem; width: 100%; border-top: 1px solid rgba(148,163,184,.15); }
.upcoming-events-box .upcoming-pagination button { min-width: 2rem; height: 2rem; padding: 0 .5rem; }
.deadline-list li[hidden] { display: none; }
@media (max-width: 900px) {
	.goal-setting-module {
		grid-template-columns: 1fr;
		grid-template-areas: 'toolbar' 'stats' 'calendar' 'upcoming' 'management';
	}
	.goal-pagination-wrap {
		flex-direction: column;
		align-items: flex-start;
	}
}
</style>
<div class="goal-setting-module">
	<div class="goal-toolbar"><div><h2>Goal Setting</h2><p>Goal Setting and School Year Calendar</p></div><div class="goal-toolbar-actions"><button type="button" class="primary-btn" data-open-modal="goal-form-modal"><i class="fa-solid fa-plus"></i> Add Goal</button><button type="button" class="secondary-btn" data-open-modal="event-form-modal"><i class="fa-solid fa-calendar-plus"></i> Add Event</button></div></div>
	<?php if ($success !== ''): ?><div class="alert success" role="alert"><?= htmlspecialchars($success) ?></div><?php endif; ?><?php if ($error !== ''): ?><div class="alert error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
	<div class="goal-panel"><div class="goal-calendar-toolbar"><h3><?= htmlspecialchars($monthLabel) ?></h3><div class="calendar-nav"><a href="?page=goal-setting&amp;month=<?= $previous->format('n') ?>&amp;year=<?= $previous->format('Y') ?>" aria-label="Previous month">&lsaquo;</a><a href="?page=goal-setting" aria-label="Today">Today</a><a href="?page=goal-setting&amp;month=<?= $next->format('n') ?>&amp;year=<?= $next->format('Y') ?>" aria-label="Next month">&rsaquo;</a><button type="button" data-open-modal="event-form-modal"><i class="fa-solid fa-plus"></i> Add Event</button></div></div><div class="goal-calendar-grid"><?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): ?><div class="calendar-weekday"><?= $day ?></div><?php endforeach; ?><?php for ($blank=1;$blank<$firstWeekday;$blank++): ?><div class="calendar-day muted"></div><?php endfor; ?><?php for ($day=1;$day<=$daysInMonth;$day++): $date=sprintf('%04d-%02d-%02d',$year,$month,$day); ?><div class="calendar-day"><div class="calendar-day-number"><?= $day ?></div><?php foreach (($calendarGoals[$date] ?? []) as $goal): ?><button type="button" class="calendar-event" data-event-title="<?= htmlspecialchars($goal['goal_title'] ?? 'Goal', ENT_QUOTES) ?>" data-event-date="<?= htmlspecialchars($date, ENT_QUOTES) ?>"><?= htmlspecialchars($goal['goal_title'] ?? 'Goal') ?></button><?php endforeach; ?><?php foreach (($calendarEvents[$date] ?? []) as $event): ?><button type="button" class="calendar-event" data-event-title="<?= htmlspecialchars($event['event_title'], ENT_QUOTES) ?>" data-event-date="<?= htmlspecialchars($event['start_date'], ENT_QUOTES) ?>"><?= htmlspecialchars($event['event_title']) ?></button><?php endforeach; ?><button type="button" class="calendar-event" data-open-modal="event-form-modal" data-event-date="<?= htmlspecialchars($date, ENT_QUOTES) ?>"><i class="fa-solid fa-plus"></i> Add</button></div><?php endfor; ?></div></div>
	<div class="goal-insight-grid"><div class="insight-card"><h3>Upcoming Events</h3><div class="upcoming-events-box"><ul class="deadline-list"><?php foreach (array_slice($goals,0,5) as $goal): ?><li><div class="deadline-icon"><i class="fa-solid fa-bullseye"></i></div><div class="deadline-body"><strong><?= htmlspecialchars($goal['goal_title'] ?? 'Goal') ?></strong><span><?= htmlspecialchars($goal['employee_name'] ?? 'Unknown') ?></span><small><?= !empty($goal['due_date']) ? htmlspecialchars(date('M d, Y',strtotime($goal['due_date']))) : 'No target date' ?></small></div></li><?php endforeach; ?><?php if (!$goals): ?><li class="empty-state compact"><p>No upcoming events.</p></li><?php endif; ?></ul></div></div><div class="insight-card"><h3>Event Types</h3><div class="priority-list"><div class="priority-row"><div class="priority-label-row"><span>Goal / Deadline</span><strong><?= (int) ($stats['total_goals'] ?? 0) ?></strong></div><div class="priority-track"><span style="width:70%"></span></div></div><div class="priority-row"><div class="priority-label-row"><span>Review / Evaluation</span><strong><?= (int) ($stats['in_progress_goals'] ?? 0) ?></strong></div><div class="priority-track"><span style="width:45%"></span></div></div></div></div><div class="insight-card"><h3>Recent Goals</h3><div class="empty-state compact"><strong><?= count($goals) ?> goals tracked</strong><p>Review progress in the table below.</p><a class="view-all-link" href="#goal-management">View all goals →</a></div></div></div>
	<div class="goal-panel" id="goal-management"><div class="goal-panel-header"><h3>Recent Goals</h3><a class="secondary-btn" href="?page=goal-setting"><i class="fa-solid fa-rotate-left"></i> Reset</a></div><form method="GET" class="goal-filters"><input type="hidden" name="page" value="goal-setting"><div class="field"><label for="search">Search</label><input id="search" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Goal title / employee"></div><div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option><?php foreach ($statuses as $item): ?><option <?= $filters['status']===$item?'selected':'' ?>><?= $item ?></option><?php endforeach; ?></select></div><div class="field"><label for="priority">Priority</label><select id="priority" name="priority"><option value="">All priorities</option><?php foreach ($priorities as $item): ?><option <?= $filters['priority']===$item?'selected':'' ?>><?= $item ?></option><?php endforeach; ?></select></div><div class="field"><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $item): ?><option <?= $filters['category']===$item?'selected':'' ?>><?= $item ?></option><?php endforeach; ?></select></div><button class="secondary-btn" type="submit"><i class="fa-solid fa-filter"></i> Apply</button></form><div class="goal-table-wrap"><table class="data-table"><thead><tr><th>Goal</th><th>Employee</th><th>Target Date</th><th>Priority</th><th>Progress</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($recentRecords as $goal): $progress=(int)($goal['progress_percentage']??0); $isEvent=!empty($goal['is_calendar_event']); ?><tr><td><strong><?= htmlspecialchars($isEvent ? ($goal['event_title']??'Untitled Event') : ($goal['goal_title']??'Untitled')) ?></strong><small><?= htmlspecialchars($isEvent ? 'Calendar Event' : ($goal['goal_category']??'Performance')) ?></small></td><td><?= htmlspecialchars($goal['employee_name']??'N/A') ?></td><td><?= !empty($goal['due_date'])?htmlspecialchars(date('M d, Y',strtotime($goal['due_date']))):'N/A' ?></td><td><?php if ($isEvent): ?><span class="badge event-badge"><?= htmlspecialchars($goal['priority_level']) ?></span><?php else: ?><span class="badge <?= strtolower($goal['priority_level'] ?? 'medium') ?>"><?= htmlspecialchars($goal['priority_level'] ?? 'Medium') ?></span><?php endif; ?></td><td><?php if ($isEvent): ?><span class="event-record-label">Event</span><?php else: ?><div class="goal-progress"><div class="progress-bar"><div class="progress-fill" style="width:<?= min(100,max(0,$progress)) ?>%"></div></div><span><?= $progress ?>%</span></div><?php endif; ?></td><td><span class="badge <?= strtolower(str_replace(' ','-', $goal['status']??'draft')) ?>"><?= htmlspecialchars($goal['status']??'Draft') ?></span></td><td><?php if ($isEvent): ?><button type="button" class="icon-btn" title="Calendar event" data-event-title="<?= htmlspecialchars($goal['event_title'], ENT_QUOTES) ?>"><i class="fa-solid fa-calendar-day"></i></button><?php else: ?><a class="icon-btn" href="?page=goal-setting&amp;goal_id=<?= (int)$goal['goal_id'] ?>" title="View"><i class="fa-solid fa-eye"></i></a><a class="icon-btn" href="?page=goal-setting&amp;goal_id=<?= (int)$goal['goal_id'] ?>&amp;mode=edit" title="Edit"><i class="fa-solid fa-pen"></i></a><?php endif; ?></td></tr><?php endforeach; ?><?php if (!$recentRecords): ?><tr><td colspan="7"><div class="empty-state"><h4>No Goals Yet</h4><p>No goals or calendar events match the current filters.</p></div></td></tr><?php endif; ?></tbody></table>
<div class="goal-pagination-wrap">
	<div class="goal-pagination-summary">Showing <span id="goal-pagination-range">1-5</span> of <span id="goal-pagination-total"><?= count($recentRecords) ?></span></div>
	<nav class="goal-pagination" aria-label="Goal Management pages">
		<button type="button" class="goal-pagination-nav" data-goal-page-action="prev" aria-label="Previous goal page" disabled>&laquo; Prev</button>
		<div class="goal-page-buttons" aria-live="polite"></div>
		<button type="button" class="goal-pagination-nav" data-goal-page-action="next" aria-label="Next goal page">Next &raquo;</button>
	</nav>
</div>
</div></div>
</div>
<?php $formGoal=$isEdit?($selectedGoal??[]):[]; ?><div class="goal-modal <?= $isEdit?'open':'' ?>" id="goal-form-modal"><div class="goal-modal-content"><div class="goal-modal-header"><h3><?= $isEdit?'Edit Goal':'Create Goal' ?></h3><button type="button" class="close-modal" data-close-modal="goal-form-modal" aria-label="Close">&times;</button></div><form method="POST" class="goal-detail-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="action" value="<?= $isEdit?'update_goal':'create_goal' ?>"><?php if($isEdit): ?><input type="hidden" name="goal_id" value="<?= (int)($formGoal['goal_id']??0) ?>"><?php endif; ?><div class="goal-form-grid"><div class="field wide"><label for="goal_title">Goal Title</label><input id="goal_title" name="goal_title" required value="<?= htmlspecialchars($formGoal['goal_title']??'') ?>"></div><div class="field wide"><label for="goal_description">Description</label><textarea id="goal_description" name="goal_description" rows="3" required><?= htmlspecialchars($formGoal['goal_description']??'') ?></textarea></div><div class="field"><label for="employee_input">Employee</label><select id="employee_input" name="employee_id" required><option value="">Select employee</option><?php foreach($employees as $employee): ?><option value="<?= (int)$employee['employee_id'] ?>" <?= (string)($formGoal['employee_id']??'')===(string)$employee['employee_id']?'selected':'' ?>><?= htmlspecialchars($employee['employee_name']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="goal_category">Category</label><select name="goal_category"><?php foreach($categories as $item): ?><option <?= ($formGoal['goal_category']??'Performance')===$item?'selected':'' ?>><?= $item ?></option><?php endforeach; ?></select></div><div class="field"><label for="start_date">Start Date</label><input id="start_date" type="date" name="start_date" value="<?= htmlspecialchars($formGoal['start_date']??'') ?>"></div><div class="field"><label for="due_date">Target Date</label><input id="due_date" type="date" name="due_date" value="<?= htmlspecialchars($formGoal['due_date']??'') ?>"></div><div class="field"><label for="priority_level">Priority</label><select name="priority_level"><?php foreach($priorities as $item): ?><option <?= ($formGoal['priority_level']??'Medium')===$item?'selected':'' ?>><?= $item ?></option><?php endforeach; ?></select></div><div class="field"><label for="progress_percentage">Progress</label><input id="progress_percentage" type="number" min="0" max="100" name="progress_percentage" value="<?= (int)($formGoal['progress_percentage']??0) ?>"></div></div><div class="goal-button-row"><button type="button" class="ghost-btn" data-close-modal="goal-form-modal">Cancel</button><button type="submit" class="primary-btn"><?= $isEdit?'Save Changes':'Create Goal' ?></button></div></form></div></div>
<?php if($selectedGoal&&!$isEdit): ?><div class="goal-modal open" id="goal-detail-modal"><div class="goal-modal-content"><div class="goal-modal-header"><h3><?= htmlspecialchars($selectedGoal['goal_title']) ?></h3><button class="close-modal" data-close-modal="goal-detail-modal" aria-label="Close">&times;</button></div><div class="detail-block"><p><?= nl2br(htmlspecialchars($selectedGoal['goal_description']??'No description.')) ?></p><p><strong>Employee:</strong> <?= htmlspecialchars($selectedGoal['employee_name']??'N/A') ?></p><p><strong>Progress:</strong> <?= (int)($selectedGoal['progress_percentage']??0) ?>%</p></div><div class="goal-detail-actions"><a class="primary-btn" href="?page=goal-setting&amp;goal_id=<?= (int)$selectedGoal['goal_id'] ?>&amp;mode=edit">Edit</a><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="action" value="delete_goal"><input type="hidden" name="goal_id" value="<?= (int)$selectedGoal['goal_id'] ?>"><button class="danger-btn" type="submit" onclick="return confirm('Delete this goal?');">Delete</button></form><button class="ghost-btn" data-close-modal="goal-detail-modal">Close</button></div></div></div><?php endif; ?>
<div class="goal-modal event-modal" id="event-form-modal"><div class="goal-modal-content"><div class="goal-modal-header"><h3>Event Details</h3><button type="button" class="close-modal" data-close-modal="event-form-modal" aria-label="Close">&times;</button></div><form method="POST" class="goal-detail-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="action" value="create_event"><div class="goal-form-grid"><div class="field wide"><label for="event_title">Event Title</label><input id="event_title" name="event_title" required placeholder="Leadership Training"></div><div class="field"><label for="event_type">Event Type</label><select id="event_type" name="event_type"><option>Goal / Goal Deadline</option><option>Employee Training</option><option>School Event</option><option>Performance Review</option><option>Appraisal / Evaluation</option><option>Other HR Event</option></select></div><div class="field"><label for="event_employee">Employee / Employees</label><select id="event_employee" name="employee_name"><option>All employees</option><?php foreach($employees as $employee): ?><option><?= htmlspecialchars($employee['employee_name']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="event_start">Start Date</label><input id="event_start" name="start_date" type="date" required></div><div class="field"><label for="event_end">End Date</label><input id="event_end" name="end_date" type="date"></div><div class="field"><label for="event_start_time">Start Time</label><input id="event_start_time" name="start_time" type="time"></div><div class="field"><label for="event_end_time">End Time</label><input id="event_end_time" name="end_time" type="time"></div><div class="field"><label for="event_location">Location</label><input id="event_location" name="location" placeholder="School campus"></div><div class="field wide"><label for="event_description">Description</label><textarea id="event_description" name="description" rows="3"></textarea></div></div><div class="goal-button-row"><button type="button" class="ghost-btn" data-close-modal="event-form-modal">Cancel</button><button type="submit" class="primary-btn">Save Event</button></div></form></div></div>
<script>
var calendarGoalPriorities = <?= json_encode($calendarGoalPriorities, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
document.querySelectorAll('.calendar-event[data-event-title][data-event-date]').forEach(function(button){
	var date = button.dataset.eventDate;
	var title = button.dataset.eventTitle;
	var priority = calendarGoalPriorities[date] && calendarGoalPriorities[date][title];
	if (priority) button.classList.add('priority-' + priority);
});
document.querySelectorAll('[data-open-modal]').forEach(function(button){button.addEventListener('click',function(){var modal=document.getElementById(button.dataset.openModal);if(modal){var date=button.dataset.eventDate;if(date)document.getElementById('event_start').value=date;modal.classList.add('open');}});});
document.querySelectorAll('[data-close-modal]').forEach(function(button){button.addEventListener('click',function(){var modal=document.getElementById(button.dataset.closeModal);if(modal)modal.classList.remove('open');});});
document.querySelectorAll('.calendar-event[data-event-title]').forEach(function(button){button.addEventListener('click',function(){alert(button.dataset.eventTitle+'\nDate: '+button.dataset.eventDate);});});
document.querySelectorAll('.deadline-list').forEach(function(list){
	var items = Array.prototype.slice.call(list.querySelectorAll('li:not(.empty-state)'));
	if (!items.length) return;
	var pageSize = 4;
	var pageCount = Math.ceil(items.length / pageSize);
	var currentPage = 1;
	var pagination = document.createElement('nav');
	pagination.className = 'upcoming-pagination';
	pagination.setAttribute('aria-label', 'Upcoming events pages');
	var prevBtn = document.createElement('button');
	prevBtn.type = 'button';
	prevBtn.textContent = '← Previous';
	prevBtn.disabled = true;
	var pageGroup = document.createElement('div');
	pageGroup.style.display = 'flex';
	pageGroup.style.alignItems = 'center';
	pageGroup.style.gap = '.35rem';
	var nextBtn = document.createElement('button');
	nextBtn.type = 'button';
	nextBtn.textContent = 'Next →';
	nextBtn.disabled = pageCount <= 1;
	function buildPageButtons() {
		pageGroup.innerHTML = '';
		for (var page = 1; page <= pageCount; page++) {
			var button = document.createElement('button');
			button.type = 'button';
			button.dataset.page = page;
			button.textContent = String(page);
			button.toggleAttribute('aria-current', page === currentPage);
			button.setAttribute('aria-label', 'Go to upcoming events page ' + page);
			if (page === currentPage) {
				button.setAttribute('aria-current', 'page');
			}
			button.addEventListener('click', function() { showPage(Number(this.dataset.page)); });
			pageGroup.appendChild(button);
		}
	}
	function showPage(page) {
		currentPage = Math.min(Math.max(page, 1), pageCount);
		items.forEach(function(item, index) {
			var isHidden = index < (currentPage - 1) * pageSize || index >= currentPage * pageSize;
			item.hidden = isHidden;
			item.style.display = isHidden ? 'none' : '';
		});
		buildPageButtons();
		prevBtn.disabled = currentPage === 1;
		nextBtn.disabled = currentPage === pageCount;
	}
	prevBtn.addEventListener('click', function() {
		if (currentPage > 1) showPage(currentPage - 1);
	});
	nextBtn.addEventListener('click', function() {
		if (currentPage < pageCount) showPage(currentPage + 1);
	});
	pagination.appendChild(prevBtn);
	pagination.appendChild(pageGroup);
	pagination.appendChild(nextBtn);
	var container = list.closest('.upcoming-events-box');
	if (!container) {
		container = document.createElement('div');
		container.className = 'upcoming-events-box';
		list.parentNode.insertBefore(container, list);
		container.appendChild(list);
	}
	container.appendChild(pagination);
	showPage(1);
});
(function() {
	var table = document.querySelector('#goal-management .data-table');
	if (!table) return;
	var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
	if (!rows.length) return;
	var pageSize = 5;
	var totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
	var currentPage = 1;
	var rangeEl = document.getElementById('goal-pagination-range');
	var totalEl = document.getElementById('goal-pagination-total');
	var pageButtons = document.querySelector('#goal-management .goal-page-buttons');
	var prevBtn = document.querySelector('#goal-management [data-goal-page-action="prev"]');
	var nextBtn = document.querySelector('#goal-management [data-goal-page-action="next"]');
	if (totalEl) totalEl.textContent = String(rows.length);
	function renderPage(page) {
		currentPage = Math.min(Math.max(page, 1), totalPages);
		rows.forEach(function(row, index) {
			var isVisible = index >= (currentPage - 1) * pageSize && index < currentPage * pageSize;
			row.hidden = !isVisible;
			row.style.display = isVisible ? '' : 'none';
		});
		if (rangeEl) {
			var start = (currentPage - 1) * pageSize + 1;
			var end = Math.min(currentPage * pageSize, rows.length);
			rangeEl.textContent = start + '-' + end;
		}
		if (prevBtn) prevBtn.disabled = currentPage === 1;
		if (nextBtn) nextBtn.disabled = currentPage === totalPages;
		if (pageButtons) {
			pageButtons.innerHTML = '';
			for (var page = 1; page <= totalPages; page++) {
				var button = document.createElement('button');
				button.type = 'button';
				button.className = 'goal-page-btn' + (page === currentPage ? ' is-active' : '');
				button.textContent = String(page);
				button.setAttribute('aria-label', 'Go to page ' + page);
				if (page === currentPage) button.setAttribute('aria-current', 'page');
				button.addEventListener('click', function() {
					renderPage(Number(this.textContent));
				});
				pageButtons.appendChild(button);
			}
		}
	}
	if (prevBtn) {
		prevBtn.addEventListener('click', function() {
			if (currentPage > 1) renderPage(currentPage - 1);
		});
	}
	if (nextBtn) {
		nextBtn.addEventListener('click', function() {
			if (currentPage < totalPages) renderPage(currentPage + 1);
		});
	}
	renderPage(1);
})();
</script>
