<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../../../auth/session.php';

$pageTitle = 'Document Repository';
$pageSubtitle = 'Manage employee documents, verification, and expiry compliance.';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$db = (new Database())->getConnection();
if (!$db instanceof PDO) {
  throw new RuntimeException('Database connection unavailable.');
}
$webBase = '/hrms-capstone/modules/compliance/';

$rows = [];
$totalRows = 0;
$errorMessage = '';
$pageSize = 10;
$currentPageNum = max(1, (int)($_GET['p'] ?? 1));
$offset = ($currentPageNum - 1) * $pageSize;
$search = trim((string)($_GET['search'] ?? ''));
$departmentFilter = trim((string)($_GET['department'] ?? ''));
$categoryFilter = trim((string)($_GET['category'] ?? ''));
$expiryFilter = trim((string)($_GET['expiry'] ?? ''));

$departments = [];
$categories = [];
try {
    $departments = $db->query("SELECT DISTINCT department_name FROM em_departments WHERE department_name IS NOT NULL AND department_name != '' ORDER BY department_name")->fetchAll(PDO::FETCH_COLUMN);
    $categories = $db->query("SELECT DISTINCT category FROM em_documents WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $departments = [];
    $categories = [];
}

try {
    $whereSql = " WHERE 1=1";
    $params = [];

    $currentFilter = $_GET['status'] ?? 'All';
    $validStatusFilters = ['All', 'Pending', 'Verified', 'Expiring Soon', 'Expired'];
    if (!in_array($currentFilter, $validStatusFilters, true)) {
        $currentFilter = 'All';
    }

    if ($currentFilter === 'Pending') {
        $whereSql .= " AND (d.verification_status = 'Pending' OR d.verification_status = 'Rejected')";
    } elseif ($currentFilter === 'Verified') {
        $whereSql .= " AND d.verification_status = 'Verified'";
    }

    if ($currentFilter === 'Expiring Soon') {
        $whereSql .= " AND d.expiry_date IS NOT NULL AND d.expiry_date >= CURDATE() AND DATEDIFF(d.expiry_date, CURDATE()) BETWEEN 1 AND 30";
    } elseif ($currentFilter === 'Expired') {
        $whereSql .= " AND d.expiry_date IS NOT NULL AND d.expiry_date < CURDATE()";
    }

    if ($expiryFilter === 'expired') {
        $whereSql .= " AND d.expiry_date IS NOT NULL AND d.expiry_date < CURDATE()";
    } elseif ($expiryFilter === 'expiring_soon') {
        $whereSql .= " AND d.expiry_date IS NOT NULL AND d.expiry_date >= CURDATE() AND DATEDIFF(d.expiry_date, CURDATE()) BETWEEN 1 AND 30";
    } elseif ($expiryFilter === 'no_expiry') {
        $whereSql .= " AND d.expiry_date IS NULL";
    }

    if ($departmentFilter !== '') {
        $whereSql .= " AND dep.department_name = :department";
        $params[':department'] = $departmentFilter;
    }

    if ($categoryFilter !== '') {
        $whereSql .= " AND d.category = :category";
        $params[':category'] = $categoryFilter;
    }

    if ($search !== '') {
        $whereSql .= " AND (
            LOWER(d.document_name) LIKE :q
            OR LOWER(d.document_type) LIKE :q
            OR LOWER(CONCAT(e.first_name, ' ', e.last_name)) LIKE :q
            OR LOWER(e.employee_code) LIKE :q
            OR LOWER(dep.department_name) LIKE :q
            OR LOWER(pos.position_name) LIKE :q
        )";
        $params[':q'] = '%' . strtolower($search) . '%';
    }

    $verificationSelect = "d.verification_status";

    $countSql = "
        SELECT COUNT(*)
        FROM em_documents d
        INNER JOIN em_employees e ON e.employee_id = d.employee_id
        LEFT JOIN em_departments dep ON dep.department_id = e.department_id
        LEFT JOIN em_positions pos ON pos.position_id = e.position_id
        $whereSql
    ";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();

    $sql = "
        SELECT
            d.document_id,
            d.employee_id,
            d.document_name,
            d.document_type,
            d.file_path,
            d.file_name,
            d.file_size,
            d.mime_type,
            d.category,
            d.expiry_date,
            $verificationSelect,
            d.created_at AS upload_date,
            d.verified_by,
            d.verified_at,
            d.verification_notes,
            CONCAT(e.first_name, ' ', e.last_name) AS full_name,
            e.employee_code AS employee_no,
            e.email,
            dep.department_name,
            pos.position_name,
            (SELECT COUNT(*) FROM lc_notifications ln WHERE ln.type = 'document_reminder' AND ln.email = e.email AND ln.module = 'compliance' LIMIT 1) AS reminder_sent
        FROM em_documents d
        INNER JOIN em_employees e ON e.employee_id = d.employee_id
        LEFT JOIN em_departments dep ON dep.department_id = e.department_id
        LEFT JOIN em_positions pos ON pos.position_id = e.position_id
        $whereSql
        ORDER BY d.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $rows = [];
    $totalRows = 0;
    $errorMessage = 'Failed to load documents: ' . $e->getMessage();
}

if (!isset($currentFilter) || !in_array($currentFilter, ['All', 'Pending', 'Verified', 'Expiring Soon', 'Expired'], true)) {
    $currentFilter = 'All';
}

$totalPages = (int)ceil($totalRows / $pageSize);
if ($totalPages < 1) $totalPages = 1;
if ($currentPageNum > $totalPages) $currentPageNum = $totalPages;

function empd_download_url(?string $filePath): string {
    global $webBase;
    if (empty($filePath)) return '#';
    if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
        return htmlspecialchars($filePath);
    }
    return htmlspecialchars($webBase . ltrim($filePath, '/'));
}

function empd_kpi_class(string $label, string $current): string {
    $map = [
        'All Documents' => 'All',
        'Pending'        => 'Pending',
        'Verified Items' => 'Verified',
        'Expiring Soon'  => 'Expiring Soon',
        'Expired'        => 'Expired',
    ];
    $target = $map[$label] ?? '';
    return ($current === $target) ? ' edoc-kpi--active' : '';
}

$kpiTotal = 0;
$kpiPending = 0;
$kpiVerified = 0;
$kpiExpiring = 0;
$kpiExpired = 0;
try {
  $kpiTotal = (int)$db->query("SELECT COUNT(*) FROM em_documents d INNER JOIN em_employees e ON e.employee_id = d.employee_id")->fetchColumn();
  $kpiPending = (int)$db->query("SELECT COUNT(*) FROM em_documents d INNER JOIN em_employees e ON e.employee_id = d.employee_id WHERE d.verification_status = 'Pending' OR d.verification_status = 'Rejected'")->fetchColumn();
  $kpiVerified = (int)$db->query("SELECT COUNT(*) FROM em_documents d INNER JOIN em_employees e ON e.employee_id = d.employee_id WHERE d.verification_status = 'Verified'")->fetchColumn();
  $kpiExpiring = (int)$db->query("SELECT COUNT(*) FROM em_documents d INNER JOIN em_employees e ON e.employee_id = d.employee_id WHERE d.expiry_date IS NOT NULL AND d.expiry_date >= CURDATE() AND DATEDIFF(d.expiry_date, CURDATE()) BETWEEN 1 AND 30")->fetchColumn();
  $kpiExpired = (int)$db->query("SELECT COUNT(*) FROM em_documents d INNER JOIN em_employees e ON e.employee_id = d.employee_id WHERE d.expiry_date IS NOT NULL AND d.expiry_date < CURDATE()")->fetchColumn();
} catch (Throwable $e) {
    $kpiTotal = $kpiPending = $kpiVerified = $kpiExpiring = $kpiExpired = 0;
}

function empd_status_class(string $status): string {
    $s = strtolower($status);
    return match ($s) {
        'verified' => 'edoc-stamp-verified',
        'pending' => 'edoc-stamp-pending',
        'rejected' => 'edoc-stamp-rejected',
        'expired' => 'edoc-stamp-expired',
        default => 'edoc-stamp-gray',
    };
}

function empd_status_stamp(string $status): string {
    $label = $status === '' ? 'Pending' : ucfirst($status);
    $cls = empd_status_class($label);
    return '<span class="edoc-stamp ' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

function empd_na_stamp(string $value): string {
    $display = trim($value);
    if ($display === '' || strcasecmp($display, 'N/A') === 0 || strcasecmp($display, 'NA') === 0) {
        return '<span class="edoc-na-stamp">Not Applicable</span>';
    }
    return htmlspecialchars($display);
}
?>
<section class="edoc-module">
    <!-- KPI Overview -->
    <div class="edoc-kpi-grid edoc-kpi-grid--plain">
            <a class="edoc-kpi edoc-kpi--blue<?= empd_kpi_class('All Documents', $currentFilter) ?>" href="?page=employee-documents&status=All">
                <div class="edoc-kpi-body">
                    <div class="edoc-kpi-value"><?= number_format($kpiTotal) ?></div>
                    <div class="edoc-kpi-label">All Documents</div>
                </div>
            </a>
            <a class="edoc-kpi edoc-kpi--amber<?= empd_kpi_class('Pending', $currentFilter) ?>" href="?page=employee-documents&status=Pending">
                <div class="edoc-kpi-body">
                    <div class="edoc-kpi-value"><?= number_format($kpiPending) ?></div>
                    <div class="edoc-kpi-label">Pending</div>
                </div>
            </a>
            <a class="edoc-kpi edoc-kpi--green<?= empd_kpi_class('Verified Items', $currentFilter) ?>" href="?page=employee-documents&status=Verified">
                <div class="edoc-kpi-body">
                    <div class="edoc-kpi-value"><?= number_format($kpiVerified) ?></div>
                    <div class="edoc-kpi-label">Verified Items</div>
                </div>
            </a>
            <a class="edoc-kpi edoc-kpi--amber<?= empd_kpi_class('Expiring Soon', $currentFilter) ?>" href="?page=employee-documents&status=Expiring+Soon">
                <div class="edoc-kpi-body">
                    <div class="edoc-kpi-value"><?= number_format($kpiExpiring) ?></div>
                    <div class="edoc-kpi-label">Expiring Soon</div>
                </div>
            </a>
            <a class="edoc-kpi edoc-kpi--red<?= empd_kpi_class('Expired', $currentFilter) ?>" href="?page=employee-documents&status=Expired">
                <div class="edoc-kpi-body">
                    <div class="edoc-kpi-value"><?= number_format($kpiExpired) ?></div>
                    <div class="edoc-kpi-label">Expired</div>
                </div>
            </a>
        </div>

    <!-- Document Table -->
    <div class="edoc-card" style="padding: 0; overflow: hidden;">
        <div class="edoc-table-wrap">
            <table class="edoc-table" id="edocEmployeeTable">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Document</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Upload Date</th>
                        <th>Status</th>
                        <th>Expiry Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 32px; color: var(--edoc-text-muted);">
                                No documents found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r):
                            $filePath = $r['file_path'] ?? '';
                            if ($filePath !== '') {
                                if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
                                    $viewHref = $filePath;
                                } else {
                                    $viewHref = $webBase . ltrim($filePath, '/');
                                }
                                if (!str_starts_with($filePath, 'http://') && !str_starts_with($filePath, 'https://') && !file_exists(__DIR__ . '/../../' . ltrim($filePath, '/'))) {
                                    $viewHref = $webBase . 'assets/documents/sample/employee_36/masters.pdf';
                                }
                            } else {
                                $viewHref = $webBase . 'assets/documents/sample/employee_36/masters.pdf';
                            }

                            $uploadTs = strtotime($r['upload_date'] ?? '');
                            $uploadDateDisplay = $uploadTs !== false ? date('d M Y', $uploadTs) : '—';
                            $expiryDate = $r['expiry_date'] ?? '';
                            $expiryTs = $expiryDate !== '' && $expiryDate !== null ? strtotime($expiryDate) : false;
                            $expiryDisplay = $expiryTs !== false ? date('d M Y', $expiryTs) : 'No expiry';

                            $isExpired = !empty($expiryDate) && $expiryDate < date('Y-m-d');
                            $expiryClass = $isExpired ? 'edoc-row-expired' : '';
                            $expiryLabel = '';
                            if ($expiryTs !== false) {
                                $daysLeft = ceil(($expiryTs - time()) / 86400);
                                if ($daysLeft > 0 && $daysLeft <= 30) {
                                    $expiryLabel = 'Expires in ' . $daysLeft . ' day' . ($daysLeft > 1 ? 's' : '');
                        }
                            }
                        ?>
                        <tr class="<?= $expiryClass ?>">
                            <td data-label="Employee" class="edoc-col-primary">
                                <div><?= htmlspecialchars($r['full_name'] ?? 'Unknown') ?></div>
                                <div class="edoc-col-secondary"><?= htmlspecialchars($r['employee_no'] ?? '') ?></div>
                            </td>
                            <td data-label="Document" class="edoc-col-primary">
                                <div><?= htmlspecialchars($r['document_name'] ?? '') ?></div>
                                <div class="edoc-col-secondary"><?= htmlspecialchars($r['category'] ?? 'Other') ?></div>
                            </td>
                            <td data-label="Department" class="edoc-col-secondary">
                                <?= empd_na_stamp($r['department_name'] ?? '') ?>
                            </td>
                            <td data-label="Position" class="edoc-col-secondary">
                                <?= empd_na_stamp($r['position_name'] ?? '') ?>
                            </td>
                            <td data-label="Upload Date" class="edoc-col-date" title="<?= htmlspecialchars($r['upload_date'] ?? '') ?>">
                                <?= htmlspecialchars($uploadDateDisplay) ?>
                            </td>
                            <td data-label="Status">
                                <?= empd_status_stamp((!empty($r['expiry_date']) && $r['expiry_date'] < date('Y-m-d') ? 'Expired' : ($r['verification_status'] ?? 'Pending'))) ?>
                            </td>
                            <td data-label="Expiry Date" class="edoc-col-date">
                                <div><?= htmlspecialchars($expiryDisplay) ?></div>
                                <?php if ($expiryLabel !== ''): ?>
                                    <div class="edoc-expiry-label edoc-expiry-<?= $isExpired ? 'expired' : 'soon' ?>" style="font-size: 10px; margin-top: 2px;"><?= htmlspecialchars($expiryLabel) ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions" class="edoc-actions-cell">
                                <div class="edoc-actions">
                                <a class="edoc-action-icon" href="<?= htmlspecialchars($viewHref) ?>" target="_blank" rel="noopener noreferrer" title="View" onclick="event.stopPropagation()">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                    <?php if (($r['verification_status'] ?? 'Pending') !== 'Verified'): ?>
                                        <button type="button" class="edoc-action-icon edoc-action-icon--primary edoc-verify-btn" data-empd-verify="<?= (int)($r['document_id'] ?? 0) ?>" title="Verify">
                                            <i class="fa-regular fa-circle-check"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="edoc-action-icon edoc-action-link--disabled" aria-disabled="true" title="Verified">
                                            <i class="fa-regular fa-circle-check"></i>
                                        </span>
                                    <?php endif; ?>
                                    <?php
                                        $expiryDate = $r['expiry_date'] ?? '';
                                        $today = date('Y-m-d');
                                        $isExpiredForReminder = !empty($expiryDate) && $expiryDate < $today;
                                        $daysToExpiry = 0;
                                        if (!$isExpiredForReminder && !empty($expiryDate)) {
                                            $daysToExpiry = (new DateTime($today))->diff(new DateTime($expiryDate))->days;
                                        }
                                        $isExpiringSoonForReminder = !$isExpiredForReminder && $daysToExpiry >= 1 && $daysToExpiry <= 30;
                                        $bellDisabled = (($r['verification_status'] ?? '') === 'Verified') && (empty($expiryDate) || $daysToExpiry > 30);
                                    ?>
                                    <?php if ($bellDisabled): ?>
                                        <span class="edoc-action-icon edoc-action-link--disabled" aria-disabled="true" title="Verified">
                                            <i class="fa-regular fa-bell"></i>
                                        </span>
                                    <?php else: ?>
                                        <?php
                                            $isPendingForReminder = !$isExpiredForReminder && !$isExpiringSoonForReminder && (in_array(($r['verification_status'] ?? ''), ['Pending', 'Rejected', ''], true) || empty($r['verification_status']));
                                            $reminderHref = htmlspecialchars($webBase) . 'index.php?page=notification-compose&mode=reply&notification_id=0&to_recipient_email=' . urlencode($r['email'] ?? '');
                                            if ($isExpiredForReminder) {
                                                $reminderHref .= '&subject=' . urlencode('Action Required: Please Renew Your Document - ' . ($r['document_name'] ?? 'Document'));
                                                $reminderHref .= '&body=' . urlencode("Dear " . ($r['full_name'] ?? 'Employee') . ",\n\nThis is a reminder that your document \"" . ($r['document_name'] ?? '') . "\" has expired as of " . ($r['expiry_date'] ?? '') . ".\n\nPlease submit a renewal copy as soon as possible. If you have any questions or need assistance, feel free to reach out to the HR department.\n\nBest regards,\nHR Department");
                                            } elseif ($isExpiringSoonForReminder) {
                                                $reminderHref .= '&subject=' . urlencode('Action Required: Your Document Is Expiring Soon - ' . ($r['document_name'] ?? 'Document'));
                                                $reminderHref .= '&body=' . urlencode("Dear " . ($r['full_name'] ?? 'Employee') . ",\n\nThis is a reminder that your document \"" . ($r['document_name'] ?? '') . "\" will expire on " . ($r['expiry_date'] ?? '') . ".\n\nPlease submit a renewed copy before it expires. If you have any questions or need assistance, feel free to reach out to the HR department.\n\nBest regards,\nHR Department");
                                            } elseif ($isPendingForReminder) {
                                                $reminderHref .= '&subject=' . urlencode('Action Required: Please Resubmit Your Document - ' . ($r['document_name'] ?? 'Document'));
                                                $reminderHref .= '&body=' . urlencode("Dear " . ($r['full_name'] ?? 'Employee') . ",\n\nThis is a reminder that your submitted document \"" . ($r['document_name'] ?? '') . "\" requires your attention.\n\nReason for resubmission: The document is currently pending verification or was rejected during review. Please review the document requirements and submit a valid copy.\n\nHow to resubmit:\n1. Ensure the document is clear, legible, and complete.\n2. Make sure all required fields and signatures are present.\n3. Upload the corrected document through the HRMS portal under the Documents section.\n4. If you have any questions or need assistance, please contact the HR department.\n\nPlease resubmit this document as soon as possible to avoid any delays in processing.\n\nBest regards,\nHR Department");
                                            }
                                        ?>
                                        <?php if (!$isExpiredForReminder && !$isExpiringSoonForReminder && (int)($r['reminder_sent'] ?? 0) > 0): ?>
                                            <span class="edoc-action-icon edoc-action-link--disabled" aria-disabled="true" title="Reminder Sent">
                                                <i class="fa-regular fa-bell"></i>
                                            </span>
                                        <?php else: ?>
                                            <a class="edoc-action-icon" href="<?= $reminderHref ?>" title="<?= $isExpiredForReminder ? 'Send Renewal Reminder' : ($isExpiringSoonForReminder ? 'Send Expiry Reminder' : ($isPendingForReminder ? 'Send Resubmission Reminder' : 'Send Reminder')) ?>" onclick="event.stopPropagation()">
                                                <i class="fa-regular fa-bell"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="edoc-pagination" role="navigation" aria-label="Document pagination">
        <?php
        $baseUrl = '?page=employee-documents';
        $qs = [];
        if ($currentFilter !== 'All') $qs[] = 'status=' . urlencode($currentFilter);
        if ($departmentFilter !== '') $qs[] = 'department=' . urlencode($departmentFilter);
        if ($categoryFilter !== '') $qs[] = 'category=' . urlencode($categoryFilter);
        if ($expiryFilter !== '') $qs[] = 'expiry=' . urlencode($expiryFilter);
        if ($search !== '') $qs[] = 'search=' . urlencode($search);
        $baseQs = $baseUrl . ($qs ? '&' . implode('&', $qs) : '');
        $prevPage = $currentPageNum - 1;
        $nextPage = $currentPageNum + 1;
        ?>
        <span class="edoc-page-info">Showing <?= number_format(($currentPageNum - 1) * $pageSize + 1) ?>–<?= number_format(min($currentPageNum * $pageSize, $totalRows)) ?> of <?= number_format($totalRows) ?> documents</span>
        <a href="<?= $prevPage >= 1 ? $baseQs . '&p=' . $prevPage : '#' ?>" class="edoc-page-link" <?= $prevPage < 1 ? 'aria-disabled="true"' : '' ?>>&laquo; Previous</a>
        <?php
        $range = 2;
        $start = max(1, $currentPageNum - $range);
        $end = min($totalPages, $currentPageNum + $range);
        if ($start > 1): ?>
            <a href="<?= $baseQs . '&p=1' ?>" class="edoc-page-btn">1</a>
            <?php if ($start > 2): ?>
                <span class="edoc-page-ellipsis">…</span>
            <?php endif; ?>
        <?php endif;
        for ($i = $start; $i <= $end; $i++):
        ?>
            <a href="<?= $baseQs . '&p=' . $i ?>" class="edoc-page-btn <?= $i === $currentPageNum ? 'active' : '' ?>" <?= $i === $currentPageNum ? 'aria-current="page"' : '' ?>><?= $i ?></a>
        <?php endfor;
        if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
                <span class="edoc-page-ellipsis">…</span>
            <?php endif; ?>
            <a href="<?= $baseQs . '&p=' . $totalPages ?>" class="edoc-page-btn"><?= $totalPages ?></a>
        <?php endif; ?>
        <a href="<?= $nextPage <= $totalPages ? $baseQs . '&p=' . $nextPage : '#' ?>" class="edoc-page-link" <?= $nextPage > $totalPages ? 'aria-disabled="true"' : '' ?>>Next &raquo;</a>
    </nav>
    <?php endif; ?>
</section>

<style>
/* ============================================
   Employee Documents — Enterprise Styles
   ============================================ */

.edoc-module {
    --edoc-surface: #ffffff;
    --edoc-text: #20252b;
    --edoc-text-secondary: #667085;
    --edoc-text-muted: #8b93a1;
    --edoc-border: #dfe3e8;
    --edoc-border-light: #eef1f5;
    --edoc-accent: #1b18be;
    --edoc-accent-hover: #40b4d1;
    --edoc-accent-light: #faf6f0;
    --edoc-success: #1f7a52;
    --edoc-success-bg: rgba(47,158,110,.08);
    --edoc-warning: #a86b13;
    --edoc-warning-bg: rgba(217,154,43,.08);
    --edoc-danger: #a3272a;
    --edoc-danger-bg: rgba(214,72,74,.08);
    --edoc-radius: 6px;
    --edoc-radius-md: 8px;
    color: var(--edoc-text);
    padding: 4px 2px 24px;
    font-family: Arial, sans-serif;
}

/* Page Header */
.edoc-page-header {
    margin-bottom: 20px;
}

.edoc-breadcrumb {
    font-size: 10px;
    color: var(--edoc-text-muted);
    margin-bottom: 6px;
    font-family: Arial, sans-serif;
}

.edoc-breadcrumb a {
    color: var(--edoc-accent);
    text-decoration: none;
}

.edoc-breadcrumb a:hover {
    text-decoration: underline;
}

.edoc-page-title {
    font-size: 11.5px;
    color: var(--edoc-text);
    margin: 0 0 2px;
    letter-spacing: -0.01em;
    font-family: Arial, sans-serif;
}

.edoc-page-subtitle {
    font-size: 10px;
    color: var(--edoc-text-secondary);
    margin: 0;
    font-family: Arial, sans-serif;
}

/* Cards */
.edoc-card {
    background: var(--edoc-surface);
    border: 1px solid var(--edoc-border);
    border-radius: var(--edoc-radius);
    padding: 16px;
    margin-bottom: 16px;
}

.edoc-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.edoc-card-title {
    font-size: 11.5px;
    color: var(--edoc-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: Arial, sans-serif;
}

.edoc-card-title i {
    font-size: 11.5px;
    color: var(--edoc-accent);
}

.edoc-card-meta {
    font-size: 10px;
    color: var(--edoc-text-secondary);
    font-family: Arial, sans-serif;
}

.edoc-card-meta strong {
    color: var(--edoc-text);
}

/* KPI Grid */
.edoc-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1px;
    background: var(--edoc-border);
    border: 1px solid var(--edoc-border);
    border-radius: var(--edoc-radius);
    overflow: hidden;
    margin-bottom: 16px;
}

.edoc-kpi {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 24px 16px;
    background: var(--edoc-surface);
    text-decoration: none;
    color: inherit;
    transition: background 0.12s ease;
}

.edoc-kpi:hover {
    background: var(--edoc-accent-light);
}

.edoc-kpi--active {
    background: var(--edoc-accent-light);
    outline: 1px solid var(--edoc-accent);
    outline-offset: -1px;
}

.edoc-kpi-body {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.edoc-kpi-value {
    font-size: 15px;
    color: var(--edoc-text);
    line-height: 1.2;
    font-family: Arial, sans-serif;
    font-weight: 400;
}

.edoc-kpi-label {
    font-size: 12px;
    color: var(--edoc-text-secondary);
    margin-top: 2px;
    font-family: Arial, sans-serif;
    font-weight: 400;
}

/* Plain white KPI variant */
.edoc-kpi-grid--plain {
    gap: 12px;
    background: transparent;
    border: none;
    border-radius: 0;
    overflow: visible;
}

.edoc-kpi-grid--plain .edoc-kpi {
    border-radius: var(--edoc-radius);
    border: 1px solid var(--edoc-border);
    box-shadow: none;
    background: var(--edoc-surface);
}

.edoc-kpi-grid--plain .edoc-kpi--blue,
.edoc-kpi-grid--plain .edoc-kpi--amber,
.edoc-kpi-grid--plain .edoc-kpi--green,
.edoc-kpi-grid--plain .edoc-kpi--red {
    border-left: none;
}

.edoc-kpi-grid--plain .edoc-kpi--active {
    outline: none;
    border-color: var(--edoc-accent);
    box-shadow: 0 0 0 1px var(--edoc-accent);
    background: var(--edoc-surface);
}

/* Filter Bar */
.edoc-filter-bar {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.edoc-search-wrap {
    position: relative;
    flex: 1 1 220px;
    max-width: 320px;
}

.edoc-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--edoc-text-muted);
    font-size: 11.5px;
    pointer-events: none;
}

.edoc-search-input {
    width: 100%;
    padding: 7px 10px 7px 30px;
    border: 1px solid var(--edoc-border);
    border-radius: var(--edoc-radius-md);
    background: var(--edoc-surface);
    font-size: 10px;
    color: var(--edoc-text);
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.12s ease, box-shadow 0.12s ease;
    font-family: Arial, sans-serif;
}

.edoc-search-input:focus {
    border-color: var(--edoc-accent);
    box-shadow: 0 0 0 3px rgba(168,121,31,.1);
}

.edoc-search-input::placeholder {
    color: var(--edoc-text-muted);
}

.edoc-select {
    padding: 7px 28px 7px 10px;
    border: 1px solid var(--edoc-border);
    border-radius: var(--edoc-radius-md);
    background: var(--edoc-surface);
    font-size: 10px;
    color: var(--edoc-text);
    outline: none;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667085' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    transition: border-color 0.12s ease, box-shadow 0.12s ease;
    font-family: Arial, sans-serif;
}

.edoc-select:focus {
    border-color: var(--edoc-accent);
    box-shadow: 0 0 0 3px rgba(168,121,31,.1);
}

.edoc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: var(--edoc-radius-md);
    font-size: 10px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.12s ease;
    text-decoration: none;
    white-space: nowrap;
    font-family: Arial, sans-serif;
}

.edoc-btn-primary {
    background: var(--edoc-accent);
    color: #fff;
    border-color: var(--edoc-accent);
}

.edoc-btn-primary:hover {
    background: var(--edoc-accent-hover);
    border-color: var(--edoc-accent-hover);
}

.edoc-btn-secondary {
    background: var(--edoc-surface);
    color: var(--edoc-text);
    border-color: var(--edoc-border);
}

.edoc-btn-secondary:hover {
    background: var(--edoc-border-light);
    border-color: var(--edoc-text-muted);
}

/* Table */
.edoc-table-wrap {
    border: 1px solid var(--edoc-border);
    border-radius: var(--edoc-radius);
    overflow: hidden;
}

.edoc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
    table-layout: auto;
    font-family: Arial, sans-serif;
}

.edoc-table thead {
    background: var(--edoc-border-light);
}

.edoc-table th {
    text-align: left;
    padding: 9px 12px;
    font-size: 10px;
    font-family: Arial, sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--edoc-text-muted);
    border-bottom: 1px solid var(--edoc-border);
    white-space: nowrap;
    user-select: none;
}

.edoc-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--edoc-border-light);
    vertical-align: middle;
    color: var(--edoc-text);
    line-height: 1.3;
    font-size: 11.5px;
    font-family: Arial, sans-serif;
}

.edoc-table td.edoc-actions-cell {
    padding-left: 0;
    text-align: left;
}

.edoc-table tbody tr:last-child td {
    border-bottom: none;
}

.edoc-table tbody tr:hover {
    background: rgba(168,121,31,.02);
}

.edoc-table tbody tr {
    transition: background 0.08s ease;
}

/* Table column specific */
.edoc-col-primary {
    font-size: 11.5px;
    color: var(--edoc-text);
    font-family: Arial, sans-serif;
}

.edoc-col-secondary {
    font-size: 10px;
    color: var(--edoc-text-secondary);
    margin-top: 1px;
    font-family: Arial, sans-serif;
}

.edoc-col-code {
    font-family: Arial, sans-serif;
    font-size: 10px;
    color: var(--edoc-text-secondary);
    white-space: nowrap;
}

.edoc-col-date {
    font-size: 10px;
    color: var(--edoc-text-muted);
    white-space: nowrap;
    font-family: Arial, sans-serif;
}

/* Status */
.edoc-stamp {
    display: inline-flex;
    align-items: center;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 3px;
    white-space: nowrap;
    font-family: Arial, sans-serif;
}

.edoc-stamp-verified {
    background: rgba(47,158,110,.12);
    color: #1f7a52;
}

.edoc-stamp-pending {
    background: var(--edoc-warning-bg);
    color: var(--edoc-warning);
}

.edoc-stamp-rejected {
    background: var(--edoc-danger-bg);
    color: var(--edoc-danger);
}

.edoc-stamp-expired {
    background: var(--edoc-danger-bg);
    color: var(--edoc-danger);
}

.edoc-stamp-gray {
    background: rgba(139,147,161,.12);
    color: #5a616d;
}

.edoc-na-stamp {
    display: inline-block;
    padding: 1px 6px;
    border: 1px solid #93c5fd;
    border-radius: 3px;
    color: #1d4ed8;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .03em;
    background: #eff6ff;
    opacity: .9;
    white-space: nowrap;
    font-family: Arial, sans-serif;
}

/* Action buttons */
.edoc-actions {
    display: flex;
    align-items: center;
    gap: 2px;
    flex-wrap: wrap;
    justify-content: flex-start;
}

.edoc-action-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 4px;
    color: var(--edoc-text-muted);
    text-decoration: none;
    transition: color 120ms ease, background 120ms ease;
    border: 1px solid transparent;
    background: transparent;
    cursor: pointer;
    padding: 0;
}

.edoc-action-link:hover {
    color: var(--edoc-accent);
    background: transparent;
}

.edoc-action-link svg {
    width: 15px;
    height: 15px;
    pointer-events: none;
}

.edoc-action-link--disabled {
    color: var(--edoc-text-muted);
    cursor: not-allowed;
    pointer-events: none;
    opacity: 0.4;
    background: transparent;
    border-color: transparent;
}

.edoc-act-primary {
    color: var(--edoc-accent);
    background: transparent;
    border-color: transparent;
}

.edoc-act-primary:hover {
    color: var(--edoc-accent-hover);
    background: transparent;
    border-color: transparent;
}

.edoc-action-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 4px;
    color: var(--edoc-text-muted);
    text-decoration: none;
    transition: color 120ms ease, background 120ms ease;
    border: 1px solid transparent;
    background: transparent;
    cursor: pointer;
    padding: 0;
}

.edoc-action-icon:hover {
    color: var(--edoc-accent);
    background: transparent;
}

.edoc-action-icon--primary {
    color: var(--edoc-accent);
    background: transparent;
    border-color: transparent;
}

.edoc-action-icon--primary:hover {
    color: var(--edoc-accent-hover);
    background: transparent;
    border-color: transparent;
}

.edoc-action-icon svg,
.edoc-action-icon img {
    width: 15px;
    height: 15px;
    pointer-events: none;
}

.edoc-action-icon img {
    opacity: 1;
}

/* Pagination */
.edoc-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    margin-top: 16px;
    flex-wrap: wrap;
}

.edoc-page-info {
    font-size: 10px;
    color: var(--edoc-text-secondary);
    margin-right: 8px;
    font-family: Arial, sans-serif;
}

.edoc-page-link,
.edoc-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border: 1px solid var(--edoc-border);
    border-radius: var(--edoc-radius);
    font-size: 10px;
    font-family: Arial, sans-serif;
    text-decoration: none;
    color: var(--edoc-text);
    background: var(--edoc-surface);
    transition: all 0.12s ease;
}

.edoc-page-link:hover,
.edoc-page-btn:hover {
    background: var(--edoc-accent-light);
    border-color: var(--edoc-accent);
    color: var(--edoc-accent);
}

.edoc-page-btn.active {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}

.edoc-page-link[aria-disabled="true"],
.edoc-page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

.edoc-page-ellipsis {
    padding: 0 4px;
    color: var(--edoc-text-muted);
    font-size: 10px;
    font-family: Arial, sans-serif;
}

/* Empty state */
.edoc-empty {
    padding: 32px 24px;
    text-align: center;
    color: var(--edoc-text-muted);
    font-size: 11.5px;
}

.edoc-message {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 11.5px;
    margin-bottom: 12px;
}

.edoc-message-success {
    background: rgba(47,158,110,.12);
    color: #1f7a52;
    border: 1px solid rgba(47,158,110,.25);
}

.edoc-message-error {
    background: rgba(214,72,74,.10);
    color: #a3272a;
    border: 1px solid rgba(214,72,74,.22);
}

/* Responsive */
@media (max-width: 1200px) {
    .edoc-kpi-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 860px) {
    .edoc-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .edoc-module {
        padding: 4px 2px 20px;
    }

    .edoc-page-title {
        font-size: 11.5px;
        font-family: Arial, sans-serif;
    }

    .edoc-card {
        padding: 12px;
        border-radius: var(--edoc-radius-md);
    }

    .edoc-filter-bar {
        flex-wrap: wrap;
        gap: 6px;
    }

    .edoc-search-wrap {
        flex: 1 1 100%;
        max-width: none;
    }

    .edoc-select {
        flex: 1 1 auto;
        width: auto;
    }

    .edoc-kpi-grid {
        grid-template-columns: 1fr;
    }

    .edoc-kpi {
        padding: 12px 14px;
    }

    /* Mobile table cards */
    .edoc-table-wrap {
        border: none;
        border-radius: 0;
        overflow: visible;
    }

    .edoc-table,
    .edoc-table thead,
    .edoc-table tbody,
    .edoc-table th,
    .edoc-table td,
    .edoc-table tr {
        display: block;
        width: 100%;
    }

    .edoc-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .edoc-table thead {
        display: none;
    }

    .edoc-table tbody tr {
        background: var(--edoc-surface);
        border: 1px solid var(--edoc-border);
        border-radius: var(--edoc-radius-md);
        padding: 12px 14px;
        margin-bottom: 10px;
        box-shadow: 0 1px 2px rgba(13,27,46,.03);
    }

    .edoc-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding: 7px 0;
        border-bottom: 1px solid var(--edoc-border-light);
        text-align: right;
    }

    .edoc-table td:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .edoc-table td::before {
        content: attr(data-label);
        font-size: 10px;
        font-family: Arial, sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--edoc-text-muted);
        text-align: left;
        flex-shrink: 0;
    }

    .edoc-table td:last-child {
        justify-content: flex-end;
    }

    .edoc-col-code,
    .edoc-col-date {
        font-size: 10px;
        font-family: Arial, sans-serif;
    }

    .edoc-stamp {
        font-size: 10px;
        padding: 2px 7px;
        font-family: Arial, sans-serif;
    }

    .edoc-pagination {
        gap: 4px;
    }

    .edoc-page-link,
    .edoc-page-btn {
        min-width: 34px;
        height: 34px;
        font-size: 10px;
        font-family: Arial, sans-serif;
    }

    .edoc-page-info {
        width: 100%;
        text-align: center;
        margin-top: 6px;
        font-size: 10px;
        font-family: Arial, sans-serif;
    }

    /* Modal responsive */
    #edocModal {
        padding: 16px;
        align-items: flex-end;
    }

    @media (min-width: 769px) {
        #edocModal {
            align-items: center;
        }
    }

    .edoc-modal-overlay {
        max-height: calc(100vh - 32px);
        border-radius: var(--edoc-radius-md);
    }

    .edoc-modal-header {
        padding: 12px 16px;
    }

    .edoc-modal-body {
        padding: 16px;
    }

    .edoc-form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .edoc-form-actions {
        flex-wrap: wrap;
        gap: 6px;
    }

    .edoc-form-message {
        width: 100%;
        margin-right: 0;
        margin-bottom: 4px;
        text-align: center;
    }
}

@media (max-width: 400px) {
    .edoc-modal-overlay {
        max-height: calc(100vh - 16px);
    }

    .edoc-modal-body {
        padding: 14px;
    }
}
</style>

<script>
document.addEventListener('click', function(e) {
  var btn = e.target.closest('.edoc-verify-btn');
  if (!btn) return;
  e.preventDefault();
  var docId = btn.getAttribute('data-empd-verify');
  if (!docId) return;
  if (!confirm('Verify this document?')) return;
  btn.disabled = true;
  fetch('<?= htmlspecialchars($webBase, ENT_QUOTES) ?>lib/api/verify-employee-document.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
    body: JSON.stringify({ action: 'verify', document_id: parseInt(docId, 10), table: 'employee_documents' })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (!data || !data.success) throw new Error(data ? data.message : 'Failed');
    alert(data.message || 'Document verified.');
    location.reload();
  })
  .catch(function(err) { alert('Could not verify document: ' + err.message); })
  .finally(function() { btn.disabled = false; });
});
</script>
