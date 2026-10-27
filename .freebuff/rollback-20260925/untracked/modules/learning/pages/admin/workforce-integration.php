<?php
/**
 * Admin — Workforce Integration.
 *
 * The receiving side of the L&D ↔ Workforce Analytics integration: everything the
 * Workforce module hands to Learning, in one place.
 *
 *   Training engagement          ld_training_engagement
 *   Turnover risk                ld_turnover_risk + the Learning-computed
 *                                ld_engagement_snapshot (risk bands, at-risk learners)
 *   Predicted skill needs        ld_competency_requirement
 *   Reporting requirements       ld_reporting_requirement
 *
 * This file only loads the data; the tabs, tables, status control and the
 * "raise training request" action are rendered by WorkforceIntegrationView, which
 * the instructor view shares (pages/instructor/workforce-integration.php) so the
 * two audiences cannot drift apart.
 * * Two actions are possible here, both audited:
 *   * move a row along its status flow   → ajax/workforce-integration-update-status.php
 *   * raise follow-up training from a     → ajax/workforce-integration-create-request.php
 *     competency need / turnover risk
 *
 * There is no nav entry for this page (see Page::getNavItems): it is opened from a
 * Workforce Analytics notification, which deep-links to ?tab=<dataset>&highlight=<id>.
 *
 * Two things are read alongside the records:
 *   * each record's triage history (ld_audit_log) — who moved it and when, since the
 *     reception tables only hold the current status
 *   * the schedule of the engagement-snapshot job (LearningCron) — when it last ran,
 *     when it is next due, and the “run now” / due-on-load trigger that keeps the
 *     schedule on a deployment with no OS scheduler
 */
include_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/workforceintegration.php';
require_once __DIR__ . '/../../classes/workforceintegrationview.php';
require_once __DIR__ . '/../../classes/cron.php';

$employeeClass = new Employee();
$integration = new WorkforceIntegration();

$tabs = [
    'engagement'    => ['label' => 'Training engagement',      'icon' => 'fa-graduation-cap'],
    'turnover_risk' => ['label' => 'Turnover risk',            'icon' => 'fa-triangle-exclamation'],
    'competency'    => ['label' => 'Skill / competency needs', 'icon' => 'fa-book-open'],
    'reporting'     => ['label' => 'Reporting requirements',   'icon' => 'fa-file-lines'],
];

$activeTab = isset($_GET['tab']) && isset($tabs[$_GET['tab']]) ? (string) $_GET['tab'] : 'engagement';

$filters = [
    'q'      => trim((string) ($_GET['q'] ?? '')),
    'dept'   => trim((string) ($_GET['dept'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];

$perPage = 15;
$page = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

$summary = $integration->getSummary();
$departments = $integration->getDepartmentOptions();

// Every tab is rendered at once (client-side tabs), so load all four datasets.
$loaders = [
    'engagement'    => static fn() => $integration->getTrainingEngagement($filters, $perPage, $offset),
    'turnover_risk' => static fn() => $integration->getTurnoverRisk($filters, $perPage, $offset),
    'competency'    => static fn() => $integration->getCompetencyRequirements($filters, $perPage, $offset),
    'reporting'     => static fn() => $integration->getReportingRequirements($filters, $perPage, $offset),
];

$rows = [];
$totals = [];
foreach (array_keys($tabs) as $dataset) {
    $rows[$dataset] = $loaders[$dataset]();
    $totals[$dataset] = $integration->countRecords($dataset, $filters);
}

// A workforce_alert notification arrives as ?tab=<dataset>&highlight=<record id>
// (Notification::linkFor). The tab is opened by the line above; here the record itself is
// fetched, because the row it names may be outside the current filters or on another page.
// It is pinned to the top of its tab below when the list did not already contain it.
$highlight = (int) ($_GET['highlight'] ?? 0);
$pinned = [];
if ($highlight > 0) {
    $record = $integration->getRecord($activeTab, $highlight);
    if ($record !== null && !in_array($highlight, array_column($rows[$activeTab] ?? [], 'id'), true)) {
        $pinned[$activeTab] = $record;
    }
}

// Prefilled follow-up request fields, per source row (see REQUEST_SOURCES).
$drafts = [];
foreach (array_keys(WorkforceIntegration::REQUEST_SOURCES) as $dataset) {
    foreach ($rows[$dataset] ?? [] as $row) {
        $drafts[$dataset][(int) $row['id']] = $integration->requestDraft($dataset, $row);
    }
}

// Triage history for the rows actually on screen — two small indexed reads per dataset,
// not one query per record.
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

// The scheduled snapshot job behind the risk panel: schedule, last run, next due.
$cron = new LearningCron();

echo WorkforceIntegrationView::page([
    'title'     => 'Workforce Integration',
    'hint'      => 'Records are sent by the <strong>Workforce Analytics</strong> module. Turnover risk also shows the engagement snapshot Learning computes itself.',
    'tabs'      => $tabs,
    'activeTab' => $activeTab,
    'rows'      => $rows,
    'totals'    => $totals,
    'summary'   => $summary,
    'snapshot'  => $integration->getSnapshotRiskSummary(),
    'atRisk'    => $integration->getAtRiskLearners(10),
    'filters'   => $filters,
    'departments' => $departments,
    'page'      => $page,
    'perPage'   => $perPage,
    // Filters travel with the pagination links, so page 2 keeps the current view.
    'baseQuery' => [
        'page'   => 'admin/workforce-integration',
        'q'      => $filters['q'],
        'dept'   => $filters['dept'],
        'status' => $filters['status'],
    ],
    'highlight'  => $highlight,
    'pinned'     => $pinned,
    'writable'      => true,
    'canRequest'    => true,
    'drafts'        => $drafts,
    'history'       => $history,
    'cron'          => $cron->getStatus(LearningCron::DEFAULT_JOB),
    'canRunCron'    => true,
]);
