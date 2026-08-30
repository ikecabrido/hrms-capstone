<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();

$pendingReports = [];
$reviewedReports = [];
$archivedReports = [];

try {
    $pdo = (new Database())->getConnection();
    $pendingReports = $pdo->query(
        "SELECT r.id, r.item_type, r.reference_id, r.reason, r.status, r.created_at,
                CONCAT(e.first_name, ' ', e.last_name) AS learner_name
         FROM ld_report r
         LEFT JOIN hrms_employee e ON e.employee_id = r.learner_id
         WHERE r.status = 'pending'
         ORDER BY r.created_at DESC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);

    $reviewedReports = $pdo->query(
        "SELECT r.id, r.item_type, r.reference_id, r.reason, r.status, r.created_at,
                CONCAT(e.first_name, ' ', e.last_name) AS learner_name
         FROM ld_report r
         LEFT JOIN hrms_employee e ON e.employee_id = r.learner_id
         WHERE r.status = 'reviewed'
         ORDER BY r.created_at DESC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);

    $archivedReports = $pdo->query(
        "SELECT r.id, r.item_type, r.reference_id, r.reason, r.status, r.created_at,
                CONCAT(e.first_name, ' ', e.last_name) AS learner_name
         FROM ld_report r
         LEFT JOIN hrms_employee e ON e.employee_id = r.learner_id
         WHERE r.status = 'archived'
         ORDER BY r.created_at DESC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $pendingReports = [];
    $reviewedReports = [];
    $archivedReports = [];
}

function renderReportCards($items, $emptyText) {
    if (empty($items)) {
        echo '<div class="mode-card"><div class="content-card-body"><h3>No reports found</h3><p>' . htmlspecialchars($emptyText) . '</p></div></div>';
        return;
    }

    echo '<div class="cards-grid" data-page-size="12">';
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        $type = strtoupper(trim((string) ($item['item_type'] ?? 'content')));
        $reason = trim((string) ($item['reason'] ?? 'No reason provided.'));
        $status = trim((string) ($item['status'] ?? 'pending'));
        $learner = trim((string) ($item['learner_name'] ?? 'Unknown learner'));
        $date = !empty($item['created_at']) ? date('M d, Y', strtotime((string) $item['created_at'])) : 'Recently';

        echo '<article class="content-card-item" data-entity-id="' . $id . '" data-entity-type="report" style="cursor:pointer;">';
        echo '<div class="content-card-thumb">' . htmlspecialchars(substr($type, 0, 2) ?: 'RP') . '</div>';
        echo '<div class="content-card-body">';
        echo '<div class="content-card-meta"><span class="pill">' . htmlspecialchars($type) . '</span><span>' . htmlspecialchars($status) . '</span></div>';
        echo '<h3>' . htmlspecialchars('Report #' . $id) . '</h3>';
        echo '<p>' . htmlspecialchars(mb_substr(strip_tags($reason), 0, 120)) . '</p>';
        echo '<div class="content-card-footer"><span>' . htmlspecialchars($learner . ' • ' . $date) . '</span></div>';
        echo '</div>';
        echo '</article>';
    }
    echo '</div>';
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" placeholder="Search moderation reports..." aria-label="Search moderation reports" />
        </div>
        <div class="toolbar-actions">
            <select class="toolbar-filter" aria-label="Filter reports">
                <option value="all">All</option>
                <option value="pending">Pending</option>
                <option value="reviewed">Reviewed</option>
                <option value="archived">Archived</option>
            </select>
            <a href="?page=admin/user" class="toolbar-add-btn" title="Back to users">Users</a>
        </div>
    </div>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-pending">Pending</button>
            <button type="button" class="tab-item" data-tab="tab-reviewed">Reviewed</button>
            <button type="button" class="tab-item" data-tab="tab-archived">Archived</button>
        </div>

        <div class="tab-content active" data-tab="tab-pending">
            <div class="mode-card">
                <h2>Pending moderation</h2>
                <p>Review flagged content and learner reports that still require an instructor or admin response.</p>
                <?php renderReportCards($pendingReports, 'There are no pending moderation reports at the moment.'); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-reviewed">
            <div class="mode-card">
                <h2>Reviewed reports</h2>
                <p>See responding actions already taken and the final disposition of earlier reports.</p>
                <?php renderReportCards($reviewedReports, 'There are no reviewed reports in the queue.'); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-archived">
            <div class="mode-card">
                <h2>Archived reports</h2>
                <p>History of closed issues retained for compliance, mistakes, and prior decisions.</p>
                <?php renderReportCards($archivedReports, 'There are no archived reports at the moment.'); ?>
            </div>
        </div>
    </div>
</div>
