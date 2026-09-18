<?php

if (!class_exists('Database', false)) {
    require_once __DIR__ . '/../../../database/db.php';
}
if (!class_exists('Database', false)) {
    class Database {
        public function getConnection() {
            return null;
        }
    }
}
require_once __DIR__ . '/../lib/ajax/document_template_helper.php';

$pageTitle = 'Document Requests';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$db = (new Database())->getConnection();
if ($db === null) {
    throw new RuntimeException('Database connection is unavailable.');
}

$docType = isset($_GET['doc_type']) ? trim((string) $_GET['doc_type']) : '';
$filter = isset($_GET['filter']) ? trim((string) $_GET['filter']) : 'all';
$statusFilter = isset($_GET['status']) ? trim((string) $_GET['status']) : 'all';
$searchQuery = isset($_GET['search']) ? trim((string) $_GET['search']) : '';

$docTypeLabels = [
    'coe'                      => 'Certificate of Employment (COE)',
    'quitclaim'                => 'Quitclaim and Release',
    'exit_acknowledgement'     => 'Exit Acknowledgement',
    'leave_agreement'          => 'Leave Agreement',
    'return_to_work_agreement' => 'Return-to-Work Agreement',
    'nda'                      => 'Non-Disclosure Agreement (NDA)',
    'training_bond'            => 'Training Bond',
    'study_leave'              => 'Study Leave Agreement',
    'non_compete'              => 'Non-Compete Agreement',
    'nte'                      => 'Notice to Explain (NTE)',
    'written_warning'          => 'Written Warning',
    'suspension_notice'        => 'Suspension Notice',
    'employee_handbook'        => 'Employee Handbook',
    'notice_of_decision'       => 'Notice of Decision',
    'termination_decision'     => 'Termination Decision',
    'exit_clearance'           => 'Exit Clearance',
    'clearance_survey'         => 'Clearance Survey',
];

$docTypeLabelToCode = array_flip($docTypeLabels);

$docTypeLabel = $docTypeLabels[$docType] ?? ucwords(str_replace('_', ' ', $docType));
$pageTitle = $docType !== '' ? ($docTypeLabel ?? 'Document Requests') : 'Document Requests';

$records = [];
$totalAll = 0;
$totalPending = 0;
$totalCompleted = 0;

function dr_value(?PDO $db, string $sql, $default = 0, array $params = []) {
    if ($db === null) {
        return $default;
    }
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function dr_all(?PDO $db, string $sql, array $params = []): array {
    if ($db === null) {
        return [];
    }
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('dr_all error: ' . $e->getMessage() . ' SQL: ' . substr($sql, 0, 200));
        return [];
    }
}

$pageSize = 10;
$currentPage = max(1, (int)($_GET['p'] ?? 1));
$offset = ($currentPage - 1) * $pageSize;

if ($docType === '') {
    $where = ['dr.employee_id IS NOT NULL', "dr.document_type IN ('coe', 'quitclaim', 'leave_agreement', 'training_bond', 'study_leave', 'non_compete', 'return_to_work_agreement', 'clearance_survey', 'exit_clearance')"];
    $params = [];
    if ($statusFilter !== 'all') {
        $where[] = 'dr.request_status = :status';
        $params[':status'] = $statusFilter;
    }
    if ($searchQuery !== '') {
        $where[] = "(CONCAT(e.first_name, ' ', e.last_name) LIKE :search OR dr.document_type LIKE :search OR dr.request_id LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }
    $whereClause = implode(' AND ', $where);
    $countSql = "
        SELECT COUNT(*)
          FROM (
              SELECT dr.employee_id, dr.document_type, dr.request_status
                FROM em_lc_document_requests dr
                INNER JOIN em_employees e ON dr.employee_id = e.employee_id
                LEFT JOIN em_departments d ON e.department_id = d.department_id
                LEFT JOIN em_positions p ON e.position_id = p.position_id
              WHERE $whereClause
              GROUP BY dr.employee_id, dr.document_type, dr.request_status
          ) dedup
    ";
    $totalRows = (int) dr_value($db, $countSql, 0, $params);
    $totalPages = (int) ceil($totalRows / $pageSize);
    if ($totalPages < 1) $totalPages = 1;
    if ($currentPage > $totalPages) $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $pageSize;
    try {
        $sql = "
            SELECT 
                MAX(dr.request_id) as request_id,
                dr.document_type,
                dr.request_status,
                dr.priority,
                dr.requires_signature,
                dr.signature_status,
                MAX(dr.created_at) as created_at,
                dr.notes,
                e.employee_id,
                CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                d.department_name,
                p.position_name
              FROM em_lc_document_requests dr
              INNER JOIN em_employees e ON dr.employee_id = e.employee_id
              LEFT JOIN em_departments d ON e.department_id = d.department_id
              LEFT JOIN em_positions p ON e.position_id = p.position_id
            WHERE $whereClause
            GROUP BY dr.employee_id, dr.document_type, dr.request_status, dr.priority, dr.requires_signature, dr.signature_status, dr.notes, e.employee_id, e.first_name, e.last_name, e.employee_code, d.department_name, p.position_name
            ORDER BY MAX(dr.created_at) DESC
            LIMIT :limit OFFSET :offset
        ";
        if ($db instanceof PDO) {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        $records = [];
    }
    $totalAll = $totalRows;
    $totalPending = (int) dr_value($db, "SELECT COUNT(*) FROM em_lc_document_requests WHERE LOWER(request_status) = 'pending' AND document_type IN ('coe', 'quitclaim', 'leave_agreement', 'training_bond', 'study_leave', 'non_compete', 'return_to_work_agreement', 'clearance_survey', 'exit_clearance')" . ($docType !== '' ? " AND document_type = :dt" : ""), 0, $docType !== '' ? [':dt' => $docTypeLabel] : []);
    $totalCompleted = (int) dr_value($db, "SELECT COUNT(*) FROM em_lc_document_requests WHERE LOWER(request_status) = 'completed' AND document_type IN ('coe', 'quitclaim', 'leave_agreement', 'training_bond', 'study_leave', 'non_compete', 'return_to_work_agreement', 'clearance_survey', 'exit_clearance')" . ($docType !== '' ? " AND document_type = :dt" : ""), 0, $docType !== '' ? [':dt' => $docTypeLabel] : []);
} else {
    $dbType = $docType;
    $where = ["r.document_type = :dt", "r.employee_id IS NOT NULL"];
    $params = [':dt' => $dbType];
    if ($statusFilter !== 'all') {
        $where[] = 'r.request_status = :status';
        $params[':status'] = $statusFilter;
    }
    if ($searchQuery !== '') {
        $where[] = "(CONCAT(e.first_name, ' ', e.last_name) LIKE :search OR r.request_id LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }
    $whereSql = implode(' AND ', $where);
    $countSql = "
        SELECT COUNT(*)
          FROM (
              SELECT r.employee_id, r.document_type, r.request_status
                FROM em_lc_document_requests r
                INNER JOIN em_employees e ON e.employee_id = r.employee_id
                LEFT JOIN em_departments d ON e.department_id = d.department_id
                LEFT JOIN em_positions p ON e.position_id = p.position_id
              WHERE {$whereSql}
              GROUP BY r.employee_id, r.document_type, r.request_status
          ) dedup
    ";
    $totalRows = (int) dr_value($db, $countSql, 0, $params);
    $totalPages = (int) ceil($totalRows / $pageSize);
    if ($totalPages < 1) $totalPages = 1;
    if ($currentPage > $totalPages) $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $pageSize;
    $sql = "
       SELECT 
           MAX(r.request_id) as request_id,
           r.document_type,
           r.request_status,
           r.archived,
           r.signature_status,
           r.requires_signature,
           MAX(r.created_at) as created_at,
           r.priority,
           r.notes,
           e.employee_id, CONCAT(e.first_name, ' ', e.last_name) AS full_name, e.employee_code,
           d.department_name, p.position_name
           FROM em_lc_document_requests r
           INNER JOIN em_employees e ON r.employee_id = e.employee_id
           LEFT JOIN em_departments d ON e.department_id = d.department_id
           LEFT JOIN em_positions p ON e.position_id = p.position_id
         WHERE {$whereSql}
         GROUP BY r.employee_id, r.document_type, r.request_status, r.archived, r.signature_status, r.requires_signature, r.priority, r.notes, e.employee_id, e.first_name, e.last_name, e.employee_code, d.department_name, p.position_name
         ORDER BY MAX(r.created_at) DESC
         LIMIT :limit OFFSET :offset
     ";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalAll = $totalRows;
    $totalPending = (int) dr_value($db, "SELECT COUNT(*) FROM em_lc_document_requests WHERE document_type = :dt AND LOWER(request_status) = 'pending'", 0, [':dt' => $docTypeLabel]);
    $totalCompleted = (int) dr_value($db, "SELECT COUNT(*) FROM em_lc_document_requests WHERE document_type = :dt AND LOWER(request_status) = 'completed'", 0, [':dt' => $docTypeLabel]);
}

$statuses = ['Pending', 'Processing', 'Generated', 'Released', 'Completed', 'Rejected'];
$statusBadgeClass = [
    'Pending' => 'badge-warning',
    'Processing' => 'badge-info',
    'Generated' => 'badge-primary',
    'Released' => 'badge-success',
    'Completed' => 'badge-success',
    'Rejected' => 'badge-danger',
];

$backUrl = '?page=document-requests';
if ($docType !== '') {
    $backUrl = '?page=document-requests';
}
?>
<div class="module-content">

    <?php if ($docType): ?>
    <div class="dl-summary-bar">
        <a class="dl-summary-item <?= $filter === 'all' ? 'dl-summary-active' : '' ?>" href="?page=document-requests&doc_type=<?= htmlspecialchars($docType) ?>&filter=all">
            <div class="dl-summary-icon blue"><i class="bi bi-journal-check"></i></div>
            <div>
                <div class="dl-summary-value"><?= number_format($totalAll) ?></div>
                <div class="dl-summary-label">Total Requests</div>
            </div>
        </a>
        <a class="dl-summary-item <?= $filter === 'pending' ? 'dl-summary-active' : '' ?>" href="?page=document-requests&doc_type=<?= htmlspecialchars($docType) ?>&filter=pending">
            <div class="dl-summary-icon amber"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="dl-summary-value"><?= number_format($totalPending) ?></div>
                <div class="dl-summary-label">Pending</div>
            </div>
        </a>
        <a class="dl-summary-item <?= $filter === 'completed' ? 'dl-summary-active' : '' ?>" href="?page=document-requests&doc_type=<?= htmlspecialchars($docType) ?>&filter=completed">
            <div class="dl-summary-icon green"><i class="bi bi-check-circle"></i></div>
            <div>
                <div class="dl-summary-value"><?= number_format($totalCompleted) ?></div>
                <div class="dl-summary-label">Completed</div>
            </div>
        </a>
    </div>
    <?php endif; ?>

    <button class="dl-drawer-toggle" id="dlDrawerToggle" type="button" aria-label="Open Quicklinks" aria-expanded="false" aria-controls="dlDrawer">
        <i class="bi bi-link-45deg" aria-hidden="true"></i> Quicklinks
    </button>

    <div class="dl-drawer-overlay" id="dlDrawerOverlay" aria-hidden="true"></div>

    <div class="dl-row">
        <div class="dl-col dl-col-main">
            <div class="dl-card">
                <div class="dl-card-head">
                    <h3>
                        <i class="bi bi-list-ul"></i>
                        Requests
                     </h3>
                 </div>
                <div class="dl-card-body">
                    <?php if (empty($records)): ?>
                        <div class="dl-empty">No records found.</div>
                    <?php else: ?>
                    <div class="dl-table-wrap">
                        <table class="dl-table" id="dlTable">
                            <thead>
                                <tr>
                                    <th>Request No.</th>
                                    <th>Employee</th>
                                    <th>Position</th>
                                    <th>Document Type</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Date Requested</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $r):
                                    $rid = (int)($r['request_id'] ?? 0);
                                    $employeeId = (int)($r['employee_id'] ?? 0);
                                    $nonContractTemplateMap = [
                                        'coe' => ['template' => 'coe.php', 'template_code' => 'coe'],
                                        'quitclaim' => ['template' => 'quitclaim.php', 'template_code' => 'quitclaim'],
                                        'exit_acknowledgement' => ['template' => 'exit_acknowledgement.php', 'template_code' => 'exit_acknowledgement'],
                                        'leave_agreement' => ['template' => 'leave_agreement.php', 'template_code' => 'leave_agreement'],
                                        'return_to_work_agreement' => ['template' => 'return_service.php', 'template_code' => 'return_service'],
                                        'nda' => ['template' => 'nda.php', 'template_code' => 'nda'],
                                        'training_bond' => ['template' => 'training_bond.php', 'template_code' => 'training_bond'],
                                        'study_leave' => ['template' => 'study_leave_agreement.php', 'template_code' => 'study_leave'],
                                        'non_compete' => ['template' => 'non_compete.php', 'template_code' => 'non_compete'],
                                        'nte' => ['template' => 'nte.php', 'template_code' => 'nte'],
                                        'written_warning' => ['template' => 'written_warning.php', 'template_code' => 'written_warning'],
                                        'suspension_notice' => ['template' => 'suspension_notice.php', 'template_code' => 'suspension_notice'],
                                        'employee_handbook' => ['template' => 'employee_handbook.php', 'template_code' => 'employee_handbook'],
                                        'notice_of_decision' => ['template' => 'notice_of_decision.php', 'template_code' => 'notice_of_decision'],
                                        'termination_decision' => ['template' => 'termination_decision.php', 'template_code' => 'termination_decision'],
                                        'exit_clearance' => ['template' => 'exit_clearance.php', 'template_code' => 'exit_clearance'],
                                        'clearance_survey' => ['template' => 'clearance_survey.php', 'template_code' => 'clearance_survey'],
                                    ];
                                    $recordDocType = (string)($r['document_type'] ?? '');
                                    $templateCodeKey = $docTypeLabelToCode[$recordDocType] ?? $recordDocType;
                                    $templateInfo = $nonContractTemplateMap[$templateCodeKey] ?? ['template' => $templateCodeKey . '.php', 'template_code' => $templateCodeKey];
                                    $rowHref = '?page=preview-document&employee_id=' . urlencode($employeeId) . '&document_type=' . urlencode($recordDocType) . '&template=' . urlencode($templateInfo['template']) . '&template_code=' . urlencode($templateInfo['template_code']);
                                    $status = strtolower((string)($r['request_status'] ?? 'pending'));
                                    $stampCls = 'pending';
                                    if ($status === 'completed') $stampCls = 'compliant';
                                    elseif ($status === 'archived') $stampCls = 'info';
                                    $priority = ucfirst(strtolower((string)($r['priority'] ?? 'Medium')));
                                    $priCls = 'medium';
                                    if (strtolower($priority) === 'critical') $priCls = 'violation';
                                    elseif (strtolower($priority) === 'high') $priCls = 'review';
                                    elseif (strtolower($priority) === 'low') $priCls = 'compliant';
                                ?>
                                <tr style="cursor:pointer;" onclick="window.location.href='<?= htmlspecialchars($rowHref) ?>'">
                                    <td data-label="Request No.">REQ-<?= str_pad((string)$rid, 4, '0', STR_PAD_LEFT) ?></td>
                                    <td data-label="Employee"><?= htmlspecialchars($r['full_name'] ?? 'Unknown') ?></td>
                                    <td data-label="Position"><?= htmlspecialchars($r['position_name'] ?? '—') ?></td>
                                    <td data-label="Document Type"><?= htmlspecialchars($recordDocType) ?></td>
                                    <td data-label="Status"><span class="dl-stamp dl-stamp-<?= $stampCls ?>"><?= htmlspecialchars(ucfirst($r['request_status'] ?? 'Draft')) ?></span></td>
                                    <td data-label="Priority"><span class="dl-priority dl-priority-<?= $priCls ?>"><?= htmlspecialchars($priority) ?></span></td>
                                    <td class="date-cell" data-label="Date Requested"><?php
                                        $reqRaw = $r['created_at'] ?? '';
                                        $reqTs = $reqRaw !== '' && $reqRaw !== null ? strtotime($reqRaw) : false;
                                        echo ($reqTs !== false && $reqTs > 0) ? date('M d, Y', $reqTs) : '—';
                                    ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($totalPages) && $totalPages > 1): ?>
                    <div class="dl-pagination-wrap">
                        <div class="dl-pagination-info">
                            Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $pageSize, $totalRows)) ?> of <?= number_format($totalRows) ?> records
                        </div>
                        <nav class="dl-pagination" aria-label="Table pagination">
                            <?php if ($currentPage > 1): ?>
                                <a class="dl-page-link" href="?page=document-requests&p=<?= $currentPage - 1 ?><?= $docType ? '&doc_type=' . htmlspecialchars($docType) : '' ?><?= $filter !== 'all' ? '&filter=' . htmlspecialchars($filter) : '' ?><?= $statusFilter !== 'all' ? '&status=' . htmlspecialchars($statusFilter) : '' ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>">&laquo; Prev</a>
                            <?php endif; ?>

                            <?php
                                $range = 2;
                                $startPage = max(1, $currentPage - $range);
                                $endPage = min($totalPages, $currentPage + $range);
                                if ($startPage > 1) {
                                    echo '<a class="dl-page-link" href="?page=document-requests&p=1' . ($docType ? '&doc_type=' . htmlspecialchars($docType) : '') . ($filter !== 'all' ? '&filter=' . htmlspecialchars($filter) : '') . ($statusFilter !== 'all' ? '&status=' . htmlspecialchars($statusFilter) : '') . ($searchQuery ? '&search=' . urlencode($searchQuery) : '') . '">1</a>';
                                    if ($startPage > 2) echo '<span class="dl-page-dots">&hellip;</span>';
                                }
                                for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <a class="dl-page-link<?= $i === $currentPage ? ' dl-page-link--active' : '' ?>" href="?page=document-requests&p=<?= $i ?><?= $docType ? '&doc_type=' . htmlspecialchars($docType) : '' ?><?= $filter !== 'all' ? '&filter=' . htmlspecialchars($filter) : '' ?><?= $statusFilter !== 'all' ? '&status=' . htmlspecialchars($statusFilter) : '' ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1) echo '<span class="dl-page-dots">&hellip;</span>'; ?>
                                <a class="dl-page-link" href="?page=document-requests&p=<?= $totalPages ?><?= $docType ? '&doc_type=' . htmlspecialchars($docType) : '' ?><?= $filter !== 'all' ? '&filter=' . htmlspecialchars($filter) : '' ?><?= $statusFilter !== 'all' ? '&status=' . htmlspecialchars($statusFilter) : '' ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>"><?= $totalPages ?></a>
                            <?php endif; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a class="dl-page-link" href="?page=document-requests&p=<?= $currentPage + 1 ?><?= $docType ? '&doc_type=' . htmlspecialchars($docType) : '' ?><?= $filter !== 'all' ? '&filter=' . htmlspecialchars($filter) : '' ?><?= $statusFilter !== 'all' ? '&status=' . htmlspecialchars($statusFilter) : '' ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>">Next &raquo;</a>
                            <?php endif; ?>
                        </nav>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="dl-col dl-col-side dl-drawer" id="dlDrawer" role="dialog" aria-modal="true" aria-labelledby="dlDrawerTitle">
            <div class="dl-card dl-finder-card">
                <div class="dl-card-head">
                    <h3 id="dlDrawerTitle"><i class="bi bi-link-45deg" aria-hidden="true"></i> Quicklinks</h3>
                    <button class="dl-drawer-close" id="dlDrawerClose" type="button" aria-label="Close Quicklinks">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="dl-card-body">
                    <div class="dl-about-text">
                        <p><strong>Document Services</strong></p>
                        <p><a href="?page=document-requests&doc_type=coe">Certificate of Employment</a></p>
                        <p><a href="?page=document-requests&doc_type=quitclaim">Quitclaim and Release</a></p>
                        <p><a href="?page=document-requests&doc_type=leave_agreement">Leave Agreement</a></p>
                        <p><a href="?page=document-requests&doc_type=training_bond">Training Bond</a></p>
                        <p><a href="?page=document-requests&doc_type=study_leave">Study Leave Agreement</a></p>
                        <p><a href="?page=document-requests&doc_type=non_compete">Non-Compete Agreement</a></p>
                        <p><a href="?page=document-requests&doc_type=return_to_work_agreement">Return-to-Work Agreement</a></p>
                        <p><a href="?page=document-requests&doc_type=clearance_survey">Clearance Survey</a></p>
                        <p><a href="?page=document-requests&doc_type=exit_clearance">Exit Clearance</a></p>
                        <p><a href="/hrms-capstone/modules/compliance/assets/documents/onboarding/onboarding.html">New Hire Documents</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
 </div>

 <style>
 .dl-module { padding: 4px 2px 24px; }

 /* Drawer base (hidden on desktop) */
 .dl-drawer-toggle,
 .dl-drawer-close {
   display: none;
 }

 .dl-drawer-overlay {
   position: fixed;
   inset: 0;
   background: rgba(15, 23, 42, 0.55);
   z-index: 999;
   opacity: 0;
   visibility: hidden;
   transition: opacity 0.3s ease, visibility 0.3s ease;
 }

 .dl-drawer-open .dl-drawer-overlay {
   opacity: 1;
   visibility: visible;
 }

 .dl-type-quick-filters { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
 .dl-type-quick-label { font-size: 0.72rem; font-weight: 700; color: var(--text-400, #8b93a1); text-transform: uppercase; letter-spacing: 0.06em; }
 .dl-type-quick-list { display: flex; gap: 8px; flex-wrap: wrap; justify-content: space-between; margin-top: 0; }
 .dl-type-quick-item { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; background: var(--card-bg, #fff); border: 1px solid var(--border, #e4e8ee); font-size: 0.78rem; font-weight: 600; color: var(--text-700, #3b4252); text-decoration: none; transition: all .15s ease; flex: 1 1 auto; min-width: 140px; justify-content: center; }
 .dl-type-quick-item:hover { border-color: var(--info-blue, #3b82c4); color: var(--info-blue, #3b82c4); transform: translateY(-1px); }

 .dl-summary-bar { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
 .dl-summary-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:160px; text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
 .dl-summary-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); border-color:var(--info-blue,#3b82c4); }
 .dl-summary-active { outline:2px solid var(--info-blue,#3b82c4); outline-offset:-2px; box-shadow:0 0 0 3px rgba(59,130,196,.15) !important; }
 .dl-summary-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
 .dl-summary-icon.green { background:rgba(47,158,110,.12); color:#1f7a52; }
 .dl-summary-icon.blue { background:rgba(59,130,196,.12); color:#1c5a8a; }
 .dl-summary-icon.amber { background:rgba(217,154,43,.14); color:#a86b13; }
 .dl-summary-icon.red { background:rgba(214,72,74,.12); color:#a3272a; }
 .dl-summary-icon.seal { background:rgba(168,121,31,.12); color:#8a6318; }
 .dl-summary-value { font-size:1.5rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
 .dl-summary-label { font-size:0.8rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }

 .dl-row { display:grid; grid-template-columns:1fr 250px; gap:16px; align-items:start; }
 .dl-col-main { min-width:0; }
 .dl-col-side { width:250px; flex-shrink:0; }

 .dl-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
 .dl-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
 .dl-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
 .dl-back-link { color: var(--info-blue, #3b82c4); text-decoration: none; font-size: 0.82rem; font-weight: 600; padding: 4px 10px; border-radius: 6px; transition: all .15s ease; border: 1px solid transparent; }
 .dl-back-link:hover { background: rgba(59,130,196,.08); border-color: rgba(59,130,196,.25); text-decoration: none; }
 .dl-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.84rem; }

 .dl-card-body {
   overflow: visible;
 }
 .dl-table-wrap {
   max-height: 520px;
   overflow-y: auto;
   min-height: 0;
 }
 .dl-pagination-wrap {
   margin-top: 12px;
 }
 .dl-table { width:100%; border-collapse:separate; border-spacing: 0 5px; font-size:0.78rem; }
 .dl-table thead th {
   position: sticky;
   top: 0;
   background:#fafbfc;
   z-index: 2;
   border-bottom: 1px solid var(--border, #e4e8ee);
 }
 .dl-table th { text-align:left; padding:7px 10px; font-size:0.7rem; font-weight:700; text-transform:uppercase; color:var(--text-400,#8b93a1); border-bottom:1px solid var(--border,#e4e8ee); }
 .dl-table td { padding:7px 10px; border-bottom:none; }
 .dl-table tbody tr { background: var(--card-bg, #fff); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(13,27,46,.04); }
 .dl-table tbody tr:first-child td:first-child { border-top-left-radius: 10px; }
 .dl-table tbody tr:first-child td:last-child { border-top-right-radius: 10px; }
 .dl-table tbody tr:last-child td:first-child { border-bottom-left-radius: 10px; }
 .dl-table tbody tr:last-child td:last-child { border-bottom-right-radius: 10px; }
 .dl-table tr:last-child td { border-bottom:none; }
 .dl-doc-link { color:var(--info-blue,#3b82c4); text-decoration:none; font-weight:600; }
 .dl-doc-link:hover { text-decoration:underline; }
 .dl-stamp { display:inline-block; font-size:0.66rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
 .dl-stamp-compliant { background:rgba(47,158,110,.12); color:#1f7a52; }
 .dl-stamp-pending { background:rgba(217,154,43,.14); color:#a86b13; }
 .dl-stamp-violation { background:rgba(214,72,74,.12); color:#a3272a; }
 .dl-stamp-info { background:rgba(59,130,196,.12); color:#1c5a8a; }
 .dl-stamp-review { background:rgba(107,88,199,.12); color:#4a3d8c; }

 .dl-priority { display:inline-block; font-size:0.66rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
 .dl-priority-critical { background:rgba(214,72,74,.12); color:#a3272a; }
 .dl-priority-high { background:rgba(217,154,43,.14); color:#a86b13; }
 .dl-priority-medium { background:rgba(59,130,196,.12); color:#1c5a8a; }
 .dl-priority-low { background:rgba(47,158,110,.12); color:#1f7a52; }
 .dl-priority-compliant { background:rgba(47,158,110,.12); color:#1f7a52; }

 .dl-finder-card { border-color:rgba(59,130,196,.2); }
 .dl-about-text { font-size:0.82rem; color:var(--text-700,#3b4252); line-height:1.6; }
 .dl-about-text p { margin:0 0 10px; }
 .dl-about-text p:last-child { margin-bottom:0; }

 .date-cell { white-space:nowrap; font-size:0.78rem; }
 .salary-cell { white-space:nowrap; font-size:0.78rem; }

 .dl-card-body::-webkit-scrollbar,
 .dl-table-wrap::-webkit-scrollbar {
   width: 4px;
 }

 .dl-card-body::-webkit-scrollbar-track,
 .dl-table-wrap::-webkit-scrollbar-track {
   background: transparent;
 }

 .dl-card-body::-webkit-scrollbar-thumb,
 .dl-table-wrap::-webkit-scrollbar-thumb {
   background: var(--border, #e4e8ee);
   border-radius: 4px;
 }

 .dl-card-body::-webkit-scrollbar-thumb:hover,
 .dl-table-wrap::-webkit-scrollbar-thumb:hover {
   background: var(--border-strong, #d3d9e2);
 }

 /* --------------------------------------------------------------------------
    Pagination
    -------------------------------------------------------------------------- */
 .dl-pagination-wrap { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; padding-top:10px; border-top:1px solid rgba(59,130,196,.1); flex-wrap:wrap; }
 .dl-pagination-info { font-size:0.78rem; color:var(--text-500,#6b7280); font-weight:500; }
 .dl-pagination { display:flex; align-items:center; gap:5px; flex-wrap:wrap; }
 .dl-page-link { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 9px; border-radius:10px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); color:var(--text-700,#3b4252); font-size:0.8rem; font-weight:600; text-decoration:none; cursor:pointer; transition: background 120ms ease, border-color 120ms ease, color 120ms ease; }
 .dl-page-link:hover { background:var(--bg-soft,#f3f5f9); border-color:var(--border-strong,#d3d9e2); color:var(--text-900,#1b2430); }
 .dl-page-link--active { background:var(--info-blue,#3b82c4); border-color:var(--info-blue,#3b82c4); color:#fff; }
 .dl-page-dots { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; color:var(--text-400,#8b93a1); font-size:0.8rem; }

 @media (max-width: 1100px) {
   .dl-row { grid-template-columns:1fr; }
   .dl-col-side { position:static; }
 }

 /* ==========================================================
    RESPONSIVE / MOBILE
    ========================================================== */

 @media (max-width: 767px) {
   .module-content {
     width: 100%;
     padding: 8px;
     overflow-x: hidden;
     box-sizing: border-box;
   }

   .dl-card {
     padding: 14px;
     border-radius: 12px;
     box-sizing: border-box;
   }

   .dl-summary-bar {
     display: grid;
     grid-template-columns: repeat(2, 1fr);
     gap: 10px;
   }

   .dl-summary-item {
     flex: 1 1 auto;
     min-width: 0;
     padding: 14px 12px;
     gap: 10px;
   }

   .dl-summary-icon {
     width: 40px;
     height: 40px;
     font-size: 1rem;
     border-radius: 10px;
   }

   .dl-summary-value {
     font-size: 1.25rem;
   }

   .dl-summary-label {
     font-size: 0.75rem;
   }

   .dl-row {
     display: block !important;
     width: 100%;
   }

   /* The fixed Quicklinks drawer must not reserve width for requests. */
   .dl-col-main,
   .dl-col-main .dl-card {
     width: 100%;
     max-width: 100%;
     min-width: 0;
     box-sizing: border-box;
   }

   .dl-drawer-toggle {
     display: flex;
     position: fixed;
     right: 0;
     left: auto;
     top: 50%;
     transform: translateY(-50%);
     z-index: 999;
     background: var(--info-blue, #3b82c4);
     color: #fff;
     border: none;
     border-radius: 10px 0 0 10px;
     padding: 10px 12px;
     min-width: 42px;
     min-height: 42px;
     align-items: center;
     gap: 6px;
     font-size: 0.78rem;
     font-weight: 600;
     cursor: pointer;
     box-shadow: 2px 2px 12px rgba(0, 0, 0, 0.18);
     font-family: inherit;
     transition: background 0.15s ease, padding-right 0.15s ease;
   }

   .dl-drawer-toggle:hover {
     background: #2c6db0;
     padding-right: 22px;
   }

   .dl-drawer {
     position: fixed;
     right: 0;
     left: auto;
     top: 0;
     height: 100vh;
     height: 100dvh;
     width: 300px;
     max-width: 85vw;
     transform: translateX(100%);
     transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
     z-index: 1000;
     overflow-y: auto;
     background: var(--bg-body, #f3f5f9);
     padding: 16px;
   }

   .dl-drawer-open .dl-drawer {
     transform: translateX(0);
   }

   .dl-drawer-overlay {
     position: fixed;
     inset: 0;
     background: rgba(15, 23, 42, 0.55);
     z-index: 999;
     opacity: 0;
     visibility: hidden;
     transition: opacity 0.3s ease, visibility 0.3s ease;
   }

   .dl-drawer-open .dl-drawer-overlay {
     opacity: 1;
     visibility: visible;
   }

   .dl-drawer-close {
     display: flex;
     width: 42px;
     height: 42px;
     align-items: center;
     justify-content: center;
     border: 1px solid var(--border, #e4e8ee);
     border-radius: 8px;
     background: #fff;
     cursor: pointer;
     color: var(--text-700, #3b4252);
     font-size: 1.1rem;
     transition: all 0.15s ease;
     flex-shrink: 0;
   }

   .dl-drawer-close:hover {
     background: var(--bg-soft, #f3f5f9);
     border-color: var(--border-strong, #d3d9e2);
   }

   .dl-drawer .dl-card {
     border: none;
     box-shadow: none;
     background: transparent;
     padding: 0;
   }

   .dl-drawer .dl-card-head {
     padding-bottom: 12px;
     margin-bottom: 12px;
     border-bottom: 1px solid rgba(59, 130, 196, 0.1);
   }

   .dl-drawer .dl-card-body {
     padding: 0;
   }

   .dl-drawer .dl-about-text a {
     background: #fff;
     border: 1px solid var(--border, #e4e8ee);
     margin-bottom: 8px;
   }

   .dl-drawer .dl-about-text a:hover {
     background: var(--bg-soft, #f3f5f9);
     padding-left: 14px;
   }

   .dl-drawer .dl-about-text a::before {
     display: block;
   }

   .dl-drawer .dl-about-text p > strong {
     display: block;
     font-size: 0.68rem;
     font-weight: 700;
     text-transform: uppercase;
     letter-spacing: 0.06em;
     color: var(--text-400, #8b93a1);
     margin-bottom: 6px;
     padding: 0 4px;
   }

   .dl-about-text p {
     margin: 0 0 6px;
   }

   .dl-about-text a {
     display: flex;
     align-items: center;
     padding: 11px 14px;
     border-radius: 8px;
     background: var(--bg-soft, #f3f5f9);
     margin-bottom: 6px;
     text-decoration: none;
     color: var(--text-700, #3b4252);
     font-weight: 500;
     font-size: 0.84rem;
     min-height: 44px;
   }

   .dl-about-text a:hover {
     background: var(--border, #e4e8ee);
     text-decoration: none;
   }

   .dl-table-wrap {
     max-height: none;
     overflow-y: visible;
   }

   .dl-table,
   .dl-table tbody,
   .dl-table tr {
     display: block;
   }

   .dl-table thead {
     display: none;
   }

   .dl-table tbody tr {
     background: var(--card-bg, #fff);
     border: 1px solid var(--border, #e4e8ee);
     border-radius: 12px;
     padding: 14px;
     margin-bottom: 12px;
     box-shadow: 0 1px 3px rgba(13,27,46,.06);
     cursor: pointer;
     transition: box-shadow 120ms ease, border-color 120ms ease;
   }

   .dl-table tbody tr:active {
     box-shadow: 0 2px 6px rgba(13,27,46,.1);
     border-color: var(--info-blue, #3b82c4);
   }

   .dl-table tbody tr td {
     display: grid;
     grid-template-columns: 1fr;
     gap: 3px;
     padding: 7px 0;
     border-bottom: 1px solid var(--border, #e4e8ee);
     font-size: 0.82rem;
     min-width: 0;
     word-break: break-word;
     overflow-wrap: anywhere;
   }

   .dl-table tbody tr td:last-child {
     border-bottom: none;
     padding-bottom: 0;
   }

   .dl-table tbody tr td:first-child {
     display: block;
     font-weight: 700;
     font-size: 0.92rem;
     color: var(--text-900, #1b2430);
     padding-bottom: 10px;
     margin-bottom: 6px;
     border-bottom: 1px solid var(--border, #e4e8ee);
     text-align: left;
     word-break: break-word;
   }

   .dl-table tbody tr td:first-child::before {
     display: none;
   }

   .dl-table tbody tr td[data-label]::before {
     content: attr(data-label);
     font-weight: 600;
     color: var(--text-700, #3b4252);
     font-size: 0.68rem;
     text-transform: uppercase;
     letter-spacing: 0.04em;
     display: block;
     margin-bottom: 2px;
     word-break: break-word;
   }

   .dl-stamp,
   .dl-priority {
     font-size: 0.72rem;
     padding: 4px 10px;
     white-space: normal;
     word-break: break-word;
     display: inline-block;
   }

   .dl-pagination-wrap {
     flex-direction: column;
     align-items: stretch;
     gap: 10px;
   }

   .dl-pagination-info {
     text-align: center;
     order: -1;
   }

   .dl-pagination {
     justify-content: center;
     gap: 6px;
   }

   .dl-page-link {
     min-width: 40px;
     height: 40px;
     padding: 0 12px;
     font-size: 0.85rem;
   }

   .dl-page-dots {
     min-width: 40px;
     height: 40px;
   }
 }

 @media (max-width: 480px) {
   .dl-pagination {
     gap: 4px;
   }

   .dl-pagination-wrap {
     overflow-wrap: anywhere;
   }

   .dl-drawer-toggle {
     padding: 9px 10px;
     font-size: 0.75rem;
   }
 }

 @media (max-width: 360px) {
   .module-content {
     padding: 6px;
   }

   .dl-summary-bar {
     grid-template-columns: 1fr;
     gap: 8px;
   }

   .dl-summary-item {
     padding: 12px;
   }

   .dl-summary-icon {
     width: 36px;
     height: 36px;
     font-size: 0.9rem;
   }

   .dl-summary-value {
     font-size: 1.1rem;
   }

   .dl-summary-label {
     font-size: 0.72rem;
   }

   .dl-card {
     padding: 12px;
     border-radius: 10px;
   }

   .dl-table tbody tr {
     padding: 12px;
     border-radius: 10px;
     margin-bottom: 10px;
   }

   .dl-table tbody tr td {
     font-size: 0.78rem;
     padding: 5px 0;
   }

   .dl-table tbody tr td:first-child {
     font-size: 0.85rem;
     padding-bottom: 8px;
   }

   .dl-page-link {
     min-width: 36px;
     height: 36px;
     padding: 0 10px;
     font-size: 0.8rem;
   }

   .dl-page-dots {
     min-width: 36px;
     height: 36px;
   }

   .dl-about-text a {
     padding: 10px 12px;
     font-size: 0.8rem;
     min-height: 40px;
   }
 }
 </style>

 <script>
 (function() {
     const drawer = document.getElementById('dlDrawer');
     const toggle = document.getElementById('dlDrawerToggle');
     const closeBtn = document.getElementById('dlDrawerClose');
     const overlay = document.getElementById('dlDrawerOverlay');
     const mobileDrawer = window.matchMedia('(max-width: 767px)');

     if (!drawer || !toggle || !overlay) return;

     function openDrawer() {
         document.body.classList.add('dl-drawer-open');
         toggle.setAttribute('aria-expanded', 'true');
         drawer.setAttribute('aria-hidden', 'false');
         overlay.setAttribute('aria-hidden', 'false');
         document.body.style.overflow = 'hidden';
         closeBtn && closeBtn.focus();
     }

     function closeDrawer() {
         document.body.classList.remove('dl-drawer-open');
         toggle.setAttribute('aria-expanded', 'false');
         drawer.setAttribute('aria-hidden', 'true');
         overlay.setAttribute('aria-hidden', 'true');
         document.body.style.overflow = '';
         toggle.focus();
     }

     function syncDrawerAccessibility() {
         if (!mobileDrawer.matches) {
             document.body.classList.remove('dl-drawer-open');
             document.body.style.overflow = '';
             toggle.setAttribute('aria-expanded', 'false');
             overlay.setAttribute('aria-hidden', 'true');
             drawer.removeAttribute('aria-hidden');
             return;
         }

         drawer.setAttribute(
             'aria-hidden',
             document.body.classList.contains('dl-drawer-open') ? 'false' : 'true'
         );
     }

     toggle.addEventListener('click', openDrawer);
     closeBtn && closeBtn.addEventListener('click', closeDrawer);
     overlay.addEventListener('click', closeDrawer);

     document.addEventListener('keydown', function(e) {
         if (e.key === 'Escape' && document.body.classList.contains('dl-drawer-open')) {
             closeDrawer();
         }
     });

     syncDrawerAccessibility();
     if (mobileDrawer.addEventListener) {
         mobileDrawer.addEventListener('change', syncDrawerAccessibility);
     } else {
         mobileDrawer.addListener(syncDrawerAccessibility);
     }
 })();
 </script>
