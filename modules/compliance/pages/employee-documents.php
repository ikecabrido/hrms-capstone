<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../../../auth/session.php';

$pageTitle = 'Document Repository';

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
$hasVerificationStatus = false;
$search = trim((string)($_GET['search'] ?? ''));

try {
    $whereSql = " WHERE 1=1";
    $params = [];

    try {
        $stmt = $db->query("SHOW COLUMNS FROM employee_documents LIKE 'verification_status'");
        $hasVerificationStatus = $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        $hasVerificationStatus = false;
    }

    $currentFilter = $_GET['status'] ?? 'All';
    $validStatusFilters = ['All', 'Pending', 'Verified', 'Expiring Soon', 'Expired'];
    if (!in_array($currentFilter, $validStatusFilters, true)) {
        $currentFilter = 'All';
    }

    if ($hasVerificationStatus) {
        if ($currentFilter === 'Pending') {
            $whereSql .= " AND (d.verification_status = 'Pending' OR d.verification_status = 'Rejected' OR d.verification_status IS NULL)";
        } elseif ($currentFilter === 'Verified') {
            $whereSql .= " AND d.verification_status = 'Verified'";
        }
    }

    if ($currentFilter === 'Expiring Soon') {
        $whereSql .= " AND d.expiry_date IS NOT NULL AND d.expiry_date >= CURDATE() AND DATEDIFF(d.expiry_date, CURDATE()) BETWEEN 1 AND 30";
    } elseif ($currentFilter === 'Expired') {
        $whereSql .= " AND d.expiry_date IS NOT NULL AND d.expiry_date < CURDATE()";
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

    $verificationSelect = $hasVerificationStatus ? "d.verification_status" : "NULL AS verification_status";

    $countSql = "
        SELECT COUNT(*)
        FROM employee_documents d
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
            CONCAT(e.first_name, ' ', e.last_name) AS full_name,
            e.employee_code AS employee_no,
            e.email,
            dep.department_name,
            pos.position_name,
            (SELECT COUNT(*) FROM lc_notifications ln WHERE ln.type = 'document_reminder' AND ln.email = e.email AND ln.module = 'compliance' LIMIT 1) AS reminder_sent
        FROM employee_documents d
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

if (!isset($currentFilter) || !in_array($currentFilter, ['All', 'Expiring Soon', 'Expired'], true)) {
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

$currentFilter = $_GET['status'] ?? 'All';
$validStatusFilters = ['All', 'Expiring Soon', 'Expired'];
if (!in_array($currentFilter, $validStatusFilters, true)) {
    $currentFilter = 'All';
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
    return ($current === $target) ? ' empd-kpi--active' : '';
}

$kpiTotal = 0;
$kpiPending = 0;
$kpiVerified = 0;
$kpiExpiring = 0;
$kpiExpired = 0;
try {
    $kpiTotal = (int)$db->query("SELECT COUNT(*) FROM employee_documents")->fetchColumn();
    if ($hasVerificationStatus) {
        $kpiPending = (int)$db->query("SELECT COUNT(*) FROM employee_documents WHERE verification_status IN ('Pending', 'Rejected') OR verification_status IS NULL")->fetchColumn();
        $kpiVerified = (int)$db->query("SELECT COUNT(*) FROM employee_documents WHERE verification_status = 'Verified'")->fetchColumn();
    } else {
        $kpiPending = $kpiTotal;
        $kpiVerified = 0;
    }
    $kpiExpiring = (int)$db->query("SELECT COUNT(*) FROM employee_documents WHERE expiry_date IS NOT NULL AND expiry_date >= CURDATE() AND DATEDIFF(expiry_date, CURDATE()) BETWEEN 1 AND 30")->fetchColumn();
    $kpiExpired = (int)$db->query("SELECT COUNT(*) FROM employee_documents WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE()")->fetchColumn();
} catch (Throwable $e) {
    $kpiTotal = $kpiPending = $kpiVerified = $kpiExpiring = $kpiExpired = 0;
}

function empd_status_class(string $status): string {
    $s = strtolower($status);
    return match ($s) {
        'verified' => 'empd-stamp-verified',
        'pending' => 'empd-stamp-pending',
        'rejected' => 'empd-stamp-rejected',
        'expired' => 'empd-stamp-expired',
        default => 'empd-stamp-gray',
    };
}

function empd_status_stamp(string $status): string {
    $label = $status === '' ? 'Pending' : ucfirst($status);
    $cls = empd_status_class($label);
    return '<span class="empd-stamp ' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

function empd_na_stamp(string $value): string {
    $display = trim($value);
    if ($display === '' || strcasecmp($display, 'N/A') === 0 || strcasecmp($display, 'NA') === 0) {
        return '<span class="empd-na-stamp">Not Applicable</span>';
    }
    return htmlspecialchars($display);
}
?>
<section class="empd-module">
  <div class="empd-kpi-grid">
    <a class="empd-kpi empd-kpi-blue<?= empd_kpi_class('All Documents', $currentFilter) ?>" href="?page=employee-documents&status=All">
      <div class="icon-wrap"><i class="bi bi-files"></i></div>
      <div class="empd-kpi-body">
        <div class="empd-kpi-value"><?= number_format($kpiTotal) ?></div>
        <div class="empd-kpi-label">All Documents</div>
      </div>
    </a>
    <a class="empd-kpi empd-kpi-amber<?= empd_kpi_class('Pending', $currentFilter) ?>" href="?page=employee-documents&status=Pending">
      <div class="icon-wrap"><i class="bi bi-clock"></i></div>
      <div class="empd-kpi-body">
        <div class="empd-kpi-value"><?= number_format($kpiPending) ?></div>
        <div class="empd-kpi-label">Pending</div>
      </div>
    </a>
    <a class="empd-kpi empd-kpi-green<?= empd_kpi_class('Verified Items', $currentFilter) ?>" href="?page=employee-documents&status=Verified">
      <div class="icon-wrap"><i class="bi bi-check-circle"></i></div>
      <div class="empd-kpi-body">
        <div class="empd-kpi-value"><?= number_format($kpiVerified) ?></div>
        <div class="empd-kpi-label">Verified Items</div>
      </div>
    </a>
    <a class="empd-kpi empd-kpi-amber<?= empd_kpi_class('Expiring Soon', $currentFilter) ?>" href="?page=employee-documents&status=Expiring+Soon">
      <div class="icon-wrap"><i class="bi bi-clock"></i></div>
      <div class="empd-kpi-body">
        <div class="empd-kpi-value"><?= number_format($kpiExpiring) ?></div>
        <div class="empd-kpi-label">Expiring Soon</div>
      </div>
    </a>
    <a class="empd-kpi empd-kpi-red<?= empd_kpi_class('Expired', $currentFilter) ?>" href="?page=employee-documents&status=Expired">
      <div class="icon-wrap"><i class="bi bi-exclamation-octagon-fill"></i></div>
      <div class="empd-kpi-body">
        <div class="empd-kpi-value"><?= number_format($kpiExpired) ?></div>
        <div class="empd-kpi-label">Expired</div>
      </div>
    </a>
  </div>

  <div class="empd-card">
    <div class="empd-card-head">
      <h3><i class="bi bi-folder2-open"></i> Document Repository</h3>
    </div>

    <?php if ($errorMessage !== ''): ?>
      <div class="empd-message empd-message-error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php elseif (empty($rows)): ?>
      <div class="empd-empty">No documents found.</div>
    <?php else: ?>
      <div class="empd-table-wrap">
        <table class="empd-table">
          <thead>
              <tr>
                <th>Employee Name</th>
                <th>Employee No.</th>
                <th>Department</th>
                <th>Position</th>
                <th>Document</th>
                <th>Category</th>
                <th>Upload Date</th>
                <th>Status</th>
                <th>Expiry Date</th>
                <th class="empd-actions-header">Actions</th>
              </tr>
          </thead>
          <tbody>
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
            ?>
              <tr>
                <td data-label="Employee Name"><?= htmlspecialchars($r['full_name'] ?? 'Unknown') ?></td>
                <td data-label="Employee No."><?= htmlspecialchars($r['employee_no'] ?? '') ?></td>
                <td data-label="Department"><?= empd_na_stamp($r['department_name'] ?? '') ?></td>
                <td data-label="Position"><?= empd_na_stamp($r['position_name'] ?? '') ?></td>
                <td data-label="Document"><?= htmlspecialchars($r['document_name'] ?? '') ?></td>
                <td data-label="Category"><?= htmlspecialchars($r['category'] ?? 'Other') ?></td>
                <td data-label="Upload Date"><?= htmlspecialchars($r['upload_date'] ?? '') ?></td>
                <td data-label="Status"><?= empd_status_stamp((!empty($r['expiry_date']) && $r['expiry_date'] < date('Y-m-d') ? 'Expired' : ($r['verification_status'] ?? 'Pending'))) ?></td>
                <td data-label="Expiry Date"><?= empd_na_stamp($r['expiry_date'] ?? '') ?></td>
                <td class="empd-actions-cell" data-label="Actions">
                  <div class="lc-act-group" style="border: 2px solid #e5e7eb; border-radius: 8px; padding: 4px; transition: border-color 120ms ease, box-shadow 120ms ease;">
                    <a class="lc-act ghost sm icon-only" href="<?= htmlspecialchars($viewHref) ?>" target="_blank" rel="noopener noreferrer" title="View" onclick="event.stopPropagation()">
                      <i class="bi bi-eye"></i>
                    </a>
                    <?php if (($r['verification_status'] ?? 'Pending') !== 'Verified'): ?>
                      <button type="button" class="lc-act ghost sm icon-only empd-verify-btn" data-empd-verify="<?= (int)($r['document_id'] ?? 0) ?>" title="Verify">
                        <i class="bi bi-check-lg"></i>
                      </button>
                    <?php else: ?>
                      <button type="button" class="lc-act ghost sm icon-only" disabled title="Verified" onclick="event.stopPropagation()">
                        <i class="bi bi-check-lg"></i>
                      </button>
                    <?php endif; ?>
                    <?php if ((int)($r['reminder_sent'] ?? 0) > 0): ?>
                      <button type="button" class="lc-act ghost sm icon-only" disabled title="Reminder Sent" onclick="event.stopPropagation()">
                        <i class="bi bi-envelope-check"></i>
                      </button>
                    <?php else: ?>
                      <?php
                        $isExpiredForReminder = !empty($r['expiry_date']) && $r['expiry_date'] < date('Y-m-d');
                        $isPendingForReminder = in_array(($r['verification_status'] ?? ''), ['Pending', 'Rejected', ''], true) || empty($r['verification_status']);
                        $isVerified = (($r['verification_status'] ?? '') === 'Verified');
                        $reminderHref = htmlspecialchars($webBase) . 'index.php?page=notification-compose&mode=reply&notification_id=0&to_recipient_email=' . urlencode($r['email'] ?? '');
                        if ($isExpiredForReminder) {
                            $reminderHref .= '&subject=' . urlencode('Action Required: Please Renew Your Document - ' . ($r['document_name'] ?? 'Document'));
                            $reminderHref .= '&body=' . urlencode("Dear " . ($r['full_name'] ?? 'Employee') . ",\n\nThis is a reminder that your document \"" . ($r['document_name'] ?? '') . "\" has expired as of " . ($r['expiry_date'] ?? '') . ".\n\nPlease submit a renewal copy as soon as possible. If you have any questions or need assistance, feel free to reach out to the HR department.\n\nBest regards,\nHR Department");
                        } elseif ($isPendingForReminder) {
                            $reminderHref .= '&subject=' . urlencode('Action Required: Please Resubmit Your Document - ' . ($r['document_name'] ?? 'Document'));
                            $reminderHref .= '&body=' . urlencode("Dear " . ($r['full_name'] ?? 'Employee') . ",\n\nThis is a reminder that your submitted document \"" . ($r['document_name'] ?? '') . "\" requires your attention.\n\nReason for resubmission: The document is currently pending verification or was rejected during review. Please review the document requirements and submit a valid copy.\n\nHow to resubmit:\n1. Ensure the document is clear, legible, and complete.\n2. Make sure all required fields and signatures are present.\n3. Upload the corrected document through the HRMS portal under the Documents section.\n4. If you have any questions or need assistance, please contact the HR department.\n\nPlease resubmit this document as soon as possible to avoid any delays in processing.\n\nBest regards,\nHR Department");
                        }
                      ?>
                      <?php if ($currentFilter === 'Expired'): ?>
                        <a class="lc-act ghost sm icon-only" href="<?= $reminderHref ?>" title="Send Renewal Reminder" onclick="event.stopPropagation()">
                          <i class="bi bi-envelope"></i>
                        </a>
                      <?php elseif ($isVerified): ?>
                        <button type="button" class="lc-act ghost sm icon-only" disabled title="Verified" onclick="event.stopPropagation()">
                          <i class="bi bi-envelope"></i>
                        </button>
                      <?php else: ?>
                        <a class="lc-act ghost sm icon-only" href="<?= $reminderHref ?>" title="<?= $isExpiredForReminder ? 'Send Renewal Reminder' : ($isPendingForReminder ? 'Send Resubmission Reminder' : 'Send Reminder') ?>" onclick="event.stopPropagation()">
                          <i class="bi bi-envelope"></i>
                        </a>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
      <div class="empd-pagination-wrap">
        <div class="empd-pagination-info">
          Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $pageSize, $totalRows)) ?> of <?= number_format($totalRows) ?> documents
        </div>
        <nav class="empd-pagination" aria-label="Document pagination">
          <?php if ($currentPageNum > 1): ?>
            <a class="empd-page-link" href="?page=employee-documents&p=<?= $currentPageNum - 1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">Prev</a>
          <?php endif; ?>

          <?php
            $range = 2;
            $startPage = max(1, $currentPageNum - $range);
            $endPage = min($totalPages, $currentPageNum + $range);
            if ($startPage > 1) {
                echo '<a class="empd-page-link" href="?page=employee-documents&p=1&search=' . urlencode($search) . '">1</a>';
                if ($startPage > 2) echo '<span class="empd-page-dots">...</span>';
            }
            for ($i = $startPage; $i <= $endPage; $i++):
          ?>
            <a class="empd-page-link<?= $i === $currentPageNum ? ' empd-page-link--active' : '' ?>" href="?page=employee-documents&p=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
          <?php endfor; ?>

          <?php
            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) echo '<span class="empd-page-dots">...</span>';
                echo '<a class="empd-page-link" href="?page=employee-documents&p=' . $totalPages . '&search=' . urlencode($search) . '">' . $totalPages . '</a>';
            }
          ?>

          <?php if ($currentPageNum < $totalPages): ?>
            <a class="empd-page-link" href="?page=employee-documents&p=<?= $currentPageNum + 1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">Next</a>
          <?php endif; ?>
        </nav>
      </div>
    <?php endif; ?>
  </div>
</section>

<style>
.empd-module { padding: 4px 2px 24px; }

.empd-kpi-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:18px; }
@media (max-width:1200px){ .empd-kpi-grid { grid-template-columns:repeat(3,1fr);} }
@media (max-width:860px){ .empd-kpi-grid { grid-template-columns:repeat(2,1fr);} }
@media (max-width:560px){ .empd-kpi-grid { grid-template-columns:1fr;} }
.empd-kpi { display:flex; gap:14px; align-items:center; padding:18px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); text-decoration:none; color:inherit; cursor:pointer; transition: box-shadow 140ms ease, transform 140ms ease, border-color 140ms ease; }
.empd-kpi:hover { box-shadow: var(--shadow-md, 0 4px 16px rgba(14, 28, 51, 0.08)); transform: translateY(-1px); border-color: var(--border-strong, #d3d9e2); }
.empd-kpi--active { outline: 2px solid var(--info-blue, #3b82c4); outline-offset: -2px; }
.empd-kpi .icon-wrap { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
.empd-kpi-green .icon-wrap { background:rgba(47,158,110,.12); color:#1f7a52; }
.empd-kpi-blue .icon-wrap { background:rgba(59,130,196,.12); color:#1c5a8a; }
.empd-kpi-red .icon-wrap { background:rgba(214,72,74,.12); color:#a3272a; }
.empd-kpi-amber .icon-wrap { background:rgba(217,154,43,.14); color:#a86b13; }
.empd-kpi-gray .icon-wrap { background:rgba(139,147,161,.12); color:#5a616d; }
.empd-kpi-body { display:flex; flex-direction:column; min-width:0; }
.empd-kpi-value { font-size:1.6rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.empd-kpi-label { font-size:0.8rem; font-weight:600; color:var(--text-700,#3b4252); margin-top:4px; }

.empd-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; }
.empd-card-head { margin-bottom:0; background:var(--card-bg,#fff); padding:2px 0 10px; }
.empd-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }

.empd-table-wrap { overflow: visible; max-height: none; }
.empd-table { width:100%; border-collapse:collapse; table-layout:fixed; font-size:0.8rem; }
.empd-table thead, .empd-table tbody tr { display: table; width: 100%; table-layout: fixed; }
.empd-table tbody { display: block; max-height: 600px; overflow-y: auto; }
.empd-table th { text-align:left; font-size:0.7rem; text-transform:uppercase; letter-spacing:.03em; color:var(--text-400,#8b93a1); padding:8px 10px; border-bottom:1px solid var(--border,#e4e8ee); vertical-align:middle; }
.empd-table td { padding:10px; border-bottom:1px solid var(--border,#e4e8ee); vertical-align:middle; }
.empd-table tr:last-child td { border-bottom:none; }

.empd-stamp { font-size:0.66rem; font-weight:700; padding:2px 9px; border-radius:999px; white-space:nowrap; }
.empd-stamp-verified { background:rgba(47,158,110,.12); color:#1f7a52; }
.empd-stamp-pending { background:rgba(217,154,43,.14); color:#a86b13; }
.empd-stamp-rejected { background:rgba(214,72,74,.14); color:#a3272a; }
.empd-stamp-expired { background:rgba(214,72,74,.14); color:#a3272a; }
.empd-stamp-gray { background:rgba(139,147,161,.12); color:#5a616d; }

.empd-actions-header { text-align:center !important; }
.empd-actions-cell { text-align:center !important; }
.empd-actions-cell .lc-act-group { justify-content:center; gap:2px; }

.empd-table td .lc-act-group { flex-wrap: nowrap; }
.empd-actions-cell .lc-act-group { padding: 2px !important; }

.empd-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.84rem; }

.empd-pagination-wrap { display:flex; align-items:center; justify-content:space-between; margin-top:16px; padding-top:12px; border-top:1px solid var(--border,#e4e8ee); gap:12px; flex-wrap:wrap; }
.empd-pagination-info { font-size:0.8rem; color:var(--text-500,#6b7280); }
.empd-pagination { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.empd-page-link { display:inline-flex; align-items:center; justify-content:center; min-width:36px; height:36px; padding:0 10px; border-radius:10px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); color:var(--text-700,#3b4252); font-size:0.82rem; font-weight:600; text-decoration:none; cursor:pointer; transition: background 120ms ease, border-color 120ms ease, color 120ms ease; }
.empd-page-link:hover { background:var(--bg-soft,#f3f5f9); border-color:var(--border-strong,#d3d9e2); color:var(--text-900,#1b2430); }
.empd-page-link--active { background:var(--info-blue,#3b82c4); border-color:var(--info-blue,#3b82c4); color:#fff; }
.empd-page-link--active:hover { background:var(--info-blue,#3b82c4); color:#fff; }
.empd-page-dots { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; color:var(--text-400,#8b93a1); font-size:0.8rem; }

.empd-na-stamp { display:inline-block; padding:1px 6px; border:1px solid #93c5fd; border-radius:3px; color:#1d4ed8; font-size:0.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em; background:#eff6ff; opacity:.9; white-space:nowrap; }

.empd-message { padding:12px 16px; border-radius:10px; font-size:0.85rem; font-weight:600; }
.empd-message-success { background:rgba(47,158,110,.12); color:#1f7a52; border:1px solid rgba(47,158,110,.25); }
.empd-message-error { background:rgba(214,72,74,.10); color:#a3272a; border:1px solid rgba(214,72,74,.22); }

.empd-actions-cell .lc-act.ghost.sm:not(.icon-only) {
  background: transparent !important;
  color: var(--text-700,#3b4252) !important;
  border-color: transparent !important;
  box-shadow: none !important;
  text-decoration: none !important;
  padding: 0 !important;
}
.empd-actions-cell .lc-act.ghost.sm.icon-only {
  background: transparent !important;
  color: var(--text-700,#3b4252) !important;
  border-color: transparent !important;
  box-shadow: none !important;
  text-decoration: none !important;
}
.empd-actions-cell .lc-act.ghost.sm.icon-only:hover {
  background: transparent !important;
  border-color: transparent !important;
  color: var(--info-blue,#3b82c4) !important;
  text-decoration: none !important;
}
.empd-actions-cell .lc-act.ghost.sm.icon-only i {
  font-size: 1rem;
}
.empd-actions-cell .lc-act.ghost.sm.icon-only {
  padding: 4px !important;
}
.empd-actions-cell .lc-act.ghost.sm.icon-only:disabled {
  opacity: 0.45 !important;
  cursor: not-allowed !important;
  color: var(--text-400,#8b93a1) !important;
}

@media (max-width: 1100px) {
  .empd-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
  .empd-kpi-grid { grid-template-columns: 1fr; }
}

/* Mobile repository cards: keep every document field and action readable. */
@media (max-width: 767px) {
  .empd-module,
  .empd-card,
  .empd-table-wrap,
  .empd-table,
  .empd-table tbody,
  .empd-table tr,
  .empd-table td {
    box-sizing: border-box;
    min-width: 0;
    max-width: 100%;
  }

  .empd-module {
    width: 100%;
    padding: 6px;
    overflow-x: hidden;
  }

  .empd-kpi-grid {
    grid-template-columns: 1fr;
    gap: 8px;
    margin-bottom: 12px;
  }

  .empd-kpi {
    padding: 12px;
  }

  .empd-card {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
  }

  .empd-card-head {
    padding: 2px 0 10px;
  }

  .empd-table-wrap {
    width: 100%;
    max-height: none;
    overflow: visible;
  }

  .empd-table,
  .empd-table tbody {
    display: block;
    width: 100%;
  }

  .empd-table thead {
    display: none;
  }

  .empd-table tbody {
    max-height: none;
    overflow: visible;
  }

  .empd-table tbody tr {
    display: block;
    width: 100%;
    margin: 0 0 10px;
    padding: 12px;
    border: 1px solid var(--border, #e4e8ee);
    border-radius: 10px;
    background: var(--card-bg, #fff);
    box-shadow: 0 1px 3px rgba(13, 27, 46, .06);
  }

  .empd-table td {
    display: grid;
    grid-template-columns: 1fr;
    gap: 3px;
    width: 100%;
    padding: 7px 0;
    border-bottom: 1px solid var(--border, #e4e8ee);
    font-size: .82rem;
    overflow-wrap: anywhere;
    word-break: break-word;
  }

  .empd-table td::before {
    content: attr(data-label);
    color: var(--text-500, #6b7280);
    font-size: .66rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
  }

  .empd-table td:first-child {
    padding-top: 0;
    font-size: .92rem;
    font-weight: 700;
  }

  .empd-table td:last-child {
    padding-bottom: 0;
    border-bottom: 0;
  }

  .empd-actions-cell,
  .empd-actions-cell .lc-act-group {
    justify-content: flex-start;
    text-align: left !important;
  }

  .empd-actions-cell .lc-act-group {
    width: max-content;
    max-width: 100%;
  }

  .empd-pagination-wrap {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .empd-pagination-info {
    text-align: center;
    overflow-wrap: anywhere;
  }

  .empd-pagination {
    justify-content: center;
    gap: 5px;
  }

  .empd-page-link,
  .empd-page-dots {
    min-width: 40px;
    height: 40px;
  }
}

@media (max-width: 480px) {
  .empd-module { padding: 4px; }
  .empd-card { padding: 10px; }
  .empd-table tbody tr { padding: 10px; }
  .empd-page-link { min-width: 36px; height: 36px; padding: 0 8px; }
  .empd-page-dots { width: 36px; height: 36px; }
}

@media (max-width: 360px) {
  .empd-kpi { padding: 10px; }
  .empd-kpi .icon-wrap { width: 40px; height: 40px; font-size: 1.1rem; }
  .empd-table td { font-size: .78rem; padding: 6px 0; }
  .empd-table td::before { font-size: .63rem; }
  .empd-page-link { min-width: 32px; height: 32px; padding: 0 6px; font-size: .75rem; }
  .empd-page-dots { width: 32px; height: 32px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.empd-actions-cell .lc-act-group').forEach(function(group) {
    var viewLink = group.querySelector('.lc-act[title="View"]');
    var verifyBtn = group.querySelector('.empd-verify-btn');

    if (viewLink) {
      viewLink.addEventListener('mouseenter', function() {
        group.style.borderColor = '#facc15';
        group.style.boxShadow = '0 0 0 3px rgba(250, 204, 21, 0.25)';
      });
      viewLink.addEventListener('mouseleave', function() {
        group.style.borderColor = '#e5e7eb';
        group.style.boxShadow = 'none';
      });
      viewLink.addEventListener('mousedown', function() {
        group.style.borderColor = '#facc15';
        group.style.boxShadow = '0 0 0 3px rgba(250, 204, 21, 0.25)';
      });
      viewLink.addEventListener('mouseup', function() {
        group.style.borderColor = '#e5e7eb';
        group.style.boxShadow = 'none';
      });
    }

    if (verifyBtn) {
      verifyBtn.addEventListener('mouseenter', function() {
        group.style.borderColor = '#22c55e';
        group.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.25)';
      });
      verifyBtn.addEventListener('mouseleave', function() {
        group.style.borderColor = '#e5e7eb';
        group.style.boxShadow = 'none';
      });
      verifyBtn.addEventListener('mousedown', function() {
        group.style.borderColor = '#22c55e';
        group.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.25)';
      });
      verifyBtn.addEventListener('mouseup', function() {
        group.style.borderColor = '#e5e7eb';
        group.style.boxShadow = 'none';
      });
    }
  });
});

document.addEventListener('click', function(e) {
  var btn = e.target.closest('.empd-verify-btn');
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
