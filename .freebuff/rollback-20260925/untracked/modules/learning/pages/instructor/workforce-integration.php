<?php
/**
 * Instructor — Workforce Integration (read-only, scoped to the viewer's department).
 *
 * The same three Workforce Analytics signals the L&D admins triage, but only the
 * part an instructor can act on: the records addressed to their own department,
 * plus the follow-up training requests raised for it. There is no status control
 * and no "raise request" action here — those belong to the admin view — so this
 * page loads no CSRF-bearing endpoint.
 *
 * Reporting requirements are intentionally absent: ld_reporting_requirement has no
 * department column (they are organisation-wide reporting targets), so they stay
 * with the L&D admins rather than being shown to every department.
 *
 * Scope comes from the department on the viewer's employee record, never from the
 * URL — a query parameter cannot widen it.
 *
 * This page has no nav entry (see Page::getNavItems): it is reached from a Workforce
 * Analytics notification, which deep-links to ?tab=<dataset>&highlight=<id>. A
 * deep-linked record outside the viewer's department is reported as such rather than
 * shown, because the scope above is not negotiable over the URL.
 */
include_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/workforceintegration.php';
require_once __DIR__ . '/../../classes/workforceintegrationview.php';
require_once __DIR__ . '/../../classes/cron.php';

$employeeClass = new Employee();
$integration = new WorkforceIntegration();
$employeeId = (int) ($employeeClass->getEmployeeId() ?? 0);

// The session carries the department from login; fall back to the employee record.
$department = trim((string) ($_SESSION['department_name'] ?? ''));
if ($department === '' && $employeeId > 0) {
    try {
        $pdo = (new Database())->getConnection();
        $stmt = $pdo->prepare(
            'SELECT d.department_name FROM em_employees e'
            . ' LEFT JOIN em_departments d ON d.department_id = e.department_id'
            . ' WHERE e.employee_id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $employeeId]);
        $department = trim((string) ($stmt->fetchColumn() ?: ''));
    } catch (Throwable $e) {
        DbError::capture($e, 'instructor/workforce-integration department');
    }
}

if ($department === '') {
    ?>
    <div class="module-header">
        <h1 class="module-header-title">Workforce Integration</h1>
    </div>
    <div class="module-content">
        <div class="mode-card" style="text-align:center;padding:2.5rem 1.5rem;">
            <i class="fas fa-building-user" style="font-size:1.8rem;color:var(--muted);display:block;margin-bottom:0.75rem;"></i>
            <h3 style="margin:0 0 0.4rem;font-size:1rem;color:var(--text);">Your department could not be determined</h3>
            <p style="margin:0;font-size:0.85rem;color:var(--muted);">
                These records are shown per department, and your employee profile has no department set.
                Ask a Learning &amp; Development admin to set it, then reload this page.
            </p>
        </div>
    </div>
    <?php
    return;
}

$tabs = [
    'engagement'    => ['label' => 'Training engagement',      'icon' => 'fa-graduation-cap'],
    'turnover_risk' => ['label' => 'Turnover risk',            'icon' => 'fa-triangle-exclamation'],
    'competency'    => ['label' => 'Skill / competency needs', 'icon' => 'fa-book-open'],
];

// 'requests' is not a dataset — it is the view's separate follow-up list tab, and a
// training-request notification opens it directly (Notification::linkFor).
$requestedTab = (string) ($_GET['tab'] ?? '');
$activeTab = ($requestedTab === 'requests' || isset($tabs[$requestedTab])) ? $requestedTab : 'engagement';

// Only the free-text and status filters travel in the URL; the department is fixed.
$query = [
    'q'      => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];

// Every read is filtered by the viewer's department, server-side.
$scopedFilters = $query + ['dept' => $department];

$perPage = 15;
$page = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

$loaders = [
    'engagement'    => static fn() => $integration->getTrainingEngagement($scopedFilters, $perPage, $offset),
    'turnover_risk' => static fn() => $integration->getTurnoverRisk($scopedFilters, $perPage, $offset),
    'competency'    => static fn() => $integration->getCompetencyRequirements($scopedFilters, $perPage, $offset),
];

$rows = [];
$totals = [];
foreach (array_keys($tabs) as $dataset) {
    $rows[$dataset] = $loaders[$dataset]();
    $totals[$dataset] = $integration->countRecords($dataset, $scopedFilters);
}

// The notification a viewer followed here names one record (?tab=&highlight=). It is
// pinned to the top of its tab when the list did not already carry it, and refused with
// a note when it belongs to another department — the scope above stays authoritative.
$highlight = (int) ($_GET['highlight'] ?? 0);
$pinned = [];
$notice = '';
if ($highlight > 0) {
    $record = $integration->getRecord($activeTab, $highlight);
    $recordDepartment = $record === null ? '' : WorkforceIntegration::recordDepartment($activeTab, $record);

    if ($record !== null && $recordDepartment !== '' && $recordDepartment !== $department) {
        $notice = 'The record this notification came from is for <strong>' . htmlspecialchars($recordDepartment)
            . '</strong>. L&amp;D admins own the organisation-wide view; this page shows '
            . htmlspecialchars($department) . ' only.';
    } elseif ($record !== null && !in_array($highlight, array_column($rows[$activeTab] ?? [], 'id'), true)) {
        $pinned[$activeTab] = $record;
    }
}

$requests = $integration->getTrainingRequests($department, 100);

// Who triaged the records on screen — the same audit trail the admin view reads.
$history = [];
foreach (array_keys($tabs) as $dataset) {
    if (!$rows[$dataset]) {
        continue;
    }
    $history[$dataset] = $integration->getRecordHistory($dataset, array_column($rows[$dataset], 'id'));
    if (isset($pinned[$dataset])) {
        $history[$dataset] += $integration->getRecordHistory($dataset, [(int) $pinned[$dataset]['id']]);
    }
}

// Schedule of the snapshot job behind the risk panel; read-only here (running it is an
// admin action), so the panel shows the last run without a "run now" button.
$cron = new LearningCron();

echo WorkforceIntegrationView::page([
    'title'    => 'Workforce Integration',
    'subtitle' => $department . ' — what Workforce Analytics sent for your department.',
    'hint'     => 'Read-only. L&amp;D admins triage these records and own the organisation-wide view; '
        . 'requests raised from them appear under <strong>Follow-up requests</strong>.',
    'tabs'      => $tabs,
    'activeTab' => $activeTab,
    'rows'      => $rows,
    'totals'    => $totals,
    'summary'   => $integration->getSummary($department),
    'snapshot'  => $integration->getSnapshotRiskSummary($department),
    'atRisk'    => $integration->getAtRiskLearners(10, $department),
    'requests'  => $requests,
    'requestsTotal' => count($requests),
    'highlight' => $highlight,
    'pinned'    => $pinned,
    'notice'    => $notice,
    'filters'   => ['q' => $query['q'], 'dept' => '', 'status' => $query['status']],
    'departments' => [],          // no department picker: the scope is fixed
    'scopeLabel'  => $department,
    'page'        => $page,
    'perPage'     => $perPage,
    'baseQuery'   => [
        'page'   => 'instructor/workforce-integration',
        'q'      => $query['q'],
        'status' => $query['status'],
    ],
    'writable'   => false,
    'canRequest' => false,
    'history'    => $history,
    'cron'       => $cron->getStatus(LearningCron::DEFAULT_JOB),
    'canRunCron' => false,
]);
