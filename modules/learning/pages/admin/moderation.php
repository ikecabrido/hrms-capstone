<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();

$pendingReports = [];
$reviewedReports = [];
$archivedReports = [];
$reportCounts = ['pending' => 0, 'reviewed' => 0, 'archived' => 0];

try {
    $pdo = (new Database())->getConnection();

    // Queue totals. The three lists below are capped at the 10 most recent
    // reports each, so the tiles count the table rather than the lists.
    $countRows = $pdo->query("SELECT status, COUNT(*) AS total FROM ld_report GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($countRows as $countRow) {
        $countStatus = strtolower(trim((string) ($countRow['status'] ?? '')));
        if (isset($reportCounts[$countStatus])) {
            $reportCounts[$countStatus] = (int) ($countRow['total'] ?? 0);
        }
    }

    $pendingReports = $pdo->query(
        "SELECT r.id, r.item_type, r.reference_id, r.reason, r.status, r.created_at,
                CONCAT(e.first_name, ' ', e.last_name) AS learner_name
         FROM ld_report r
         LEFT JOIN em_employees e ON e.employee_id = r.learner_id
         WHERE r.status = 'pending'
         ORDER BY r.created_at DESC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);

    $reviewedReports = $pdo->query(
        "SELECT r.id, r.item_type, r.reference_id, r.reason, r.status, r.created_at,
                CONCAT(e.first_name, ' ', e.last_name) AS learner_name
         FROM ld_report r
         LEFT JOIN em_employees e ON e.employee_id = r.learner_id
         WHERE r.status = 'reviewed'
         ORDER BY r.created_at DESC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);

    $archivedReports = $pdo->query(
        "SELECT r.id, r.item_type, r.reference_id, r.reason, r.status, r.created_at,
                CONCAT(e.first_name, ' ', e.last_name) AS learner_name
         FROM ld_report r
         LEFT JOIN em_employees e ON e.employee_id = r.learner_id
         WHERE r.status = 'archived'
         ORDER BY r.created_at DESC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    DbError::capture($e, 'admin/moderation');
    $pendingReports = [];
    $reviewedReports = [];
    $archivedReports = [];
}

$totalReports = $reportCounts['pending'] + $reportCounts['reviewed'] + $reportCounts['archived'];

/**
 * Renders the report queue for one status. The shared .cards-grid /
 * .content-card-item hooks are kept so the module's search handler keeps
 * filtering the queue; the visual treatment lives in css/pages/moderation.css.
 */
function renderReportCards($items, $emptyTitle, $emptyText) {
    if (empty($items)) {
        echo '<div class="mod-empty">'
            . '<i class="fas fa-inbox" aria-hidden="true"></i>'
            . '<strong>' . htmlspecialchars($emptyTitle) . '</strong>'
            . '<span>' . htmlspecialchars($emptyText) . '</span>'
            . '</div>';
        return;
    }

    echo '<div class="cards-grid" data-page-size="12">';
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        $type = strtoupper(trim((string) ($item['item_type'] ?? 'content')));
        $reason = trim((string) ($item['reason'] ?? 'No reason provided.'));
        $status = strtolower(trim((string) ($item['status'] ?? 'pending')));
        if (!in_array($status, ['pending', 'reviewed', 'archived'], true)) {
            $status = 'pending';
        }
        $learner = trim((string) ($item['learner_name'] ?? ''));
        if ($learner === '') {
            $learner = 'Unknown learner';
        }
        $date = !empty($item['created_at']) ? date('M d, Y', strtotime((string) $item['created_at'])) : 'Recently';
        $initials = strtoupper(substr($type, 0, 2)) ?: 'RP';

        echo '<article class="content-card-item" data-entity-id="' . $id . '" data-entity-type="report" data-status="' . htmlspecialchars($status) . '">';
        echo '<div class="content-card-thumb" aria-hidden="true">' . htmlspecialchars($initials) . '</div>';
        echo '<div class="content-card-body">';
        echo '<div class="content-card-meta">'
            . '<span class="pill">' . htmlspecialchars($type) . '</span>'
            . '<span class="mod-status is-' . htmlspecialchars($status) . '">' . htmlspecialchars(ucfirst($status)) . '</span>'
            . '</div>';
        echo '<h3>Report #' . $id . '</h3>';
        echo '<p>' . htmlspecialchars(mb_substr(strip_tags($reason), 0, 160)) . '</p>';
        echo '<div class="content-card-footer">'
            . '<span><i class="fas fa-user" aria-hidden="true"></i> ' . htmlspecialchars($learner) . '</span>'
            . '<span><i class="fas fa-calendar-day" aria-hidden="true"></i> ' . htmlspecialchars($date) . '</span>'
            . '</div>';
        echo '</div>';
        echo '</article>';
    }
    echo '</div>';
}
?>
<div class="admin-data-layout">
    <?php include __DIR__ . '/../../includes/admin-moderation-navigation.php'; ?>
    <div class="module-content mod-page">
        <div class="home-dashboard">
            <section class="home-hero">
                <div class="home-hero-main">
                    <h1>Moderation</h1>
                    <p>Review flagged content and learner reports that still need an instructor or admin decision.</p>
                </div>
            </section>

            <div class="home-stats">
                <div class="home-stat">
                    <span class="home-stat-icon tone-amber"><i class="fas fa-hourglass-half"></i></span>
                    <span class="home-stat-body">
                        <span class="home-stat-label">Pending</span>
                        <span class="home-stat-value"><?= (int) $reportCounts['pending'] ?></span>
                        <span class="home-stat-sub">awaiting a decision</span>
                    </span>
                </div>
                <div class="home-stat">
                    <span class="home-stat-icon tone-emerald"><i class="fas fa-circle-check"></i></span>
                    <span class="home-stat-body">
                        <span class="home-stat-label">Reviewed</span>
                        <span class="home-stat-value"><?= (int) $reportCounts['reviewed'] ?></span>
                        <span class="home-stat-sub">actions already taken</span>
                    </span>
                </div>
                <div class="home-stat">
                    <span class="home-stat-icon tone-slate"><i class="fas fa-box-archive"></i></span>
                    <span class="home-stat-body">
                        <span class="home-stat-label">Archived</span>
                        <span class="home-stat-value"><?= (int) $reportCounts['archived'] ?></span>
                        <span class="home-stat-sub">kept for compliance</span>
                    </span>
                </div>
            </div>

            <div class="mod-toolbar">
                <div class="toolbar-search">
                    <input type="search" placeholder="Search moderation reports..." aria-label="Search moderation reports" />
                </div>
                <span class="mod-count"><?= $totalReports ?> report<?= $totalReports === 1 ? '' : 's' ?> in the queue</span>
            </div>

            <div class="tab-container">
                <div class="tab-list">
                    <button type="button" class="tab-item active" data-tab="tab-pending"><i class="fas fa-hourglass-half"></i> Pending</button>
                    <button type="button" class="tab-item" data-tab="tab-reviewed"><i class="fas fa-circle-check"></i> Reviewed</button>
                    <button type="button" class="tab-item" data-tab="tab-archived"><i class="fas fa-box-archive"></i> Archived</button>
                </div>

                <div class="tab-content active" data-tab="tab-pending" id="tab-pending">
                    <div class="mod-panel">
                        <div class="mod-panel-head">
                            <h2 class="mod-panel-title"><i class="fas fa-hourglass-half"></i> Pending moderation</h2>
                            <span class="mod-badge"><?= (int) $reportCounts['pending'] ?></span>
                        </div>
                        <p class="mod-panel-sub">Review flagged content and learner reports that still require an instructor or admin response.</p>
                        <?php renderReportCards($pendingReports, 'No pending reports', 'There are no pending moderation reports at the moment.'); ?>
                    </div>
                </div>

                <div class="tab-content" data-tab="tab-reviewed" id="tab-reviewed">
                    <div class="mod-panel">
                        <div class="mod-panel-head">
                            <h2 class="mod-panel-title"><i class="fas fa-circle-check"></i> Reviewed reports</h2>
                            <span class="mod-badge"><?= (int) $reportCounts['reviewed'] ?></span>
                        </div>
                        <p class="mod-panel-sub">See responding actions already taken and the final disposition of earlier reports.</p>
                        <?php renderReportCards($reviewedReports, 'No reviewed reports', 'There are no reviewed reports in the queue.'); ?>
                    </div>
                </div>

                <div class="tab-content" data-tab="tab-archived" id="tab-archived">
                    <div class="mod-panel">
                        <div class="mod-panel-head">
                            <h2 class="mod-panel-title"><i class="fas fa-box-archive"></i> Archived reports</h2>
                            <span class="mod-badge"><?= (int) $reportCounts['archived'] ?></span>
                        </div>
                        <p class="mod-panel-sub">History of closed issues retained for compliance, mistakes, and prior decisions.</p>
                        <?php renderReportCards($archivedReports, 'No archived reports', 'There are no archived reports at the moment.'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
