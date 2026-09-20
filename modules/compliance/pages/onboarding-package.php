<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../lib/ajax/document_template_helper.php';

$pageTitle = 'Onboarding Document Package';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$viewerRole = strtolower((string) ($user['role_name'] ?? $user['role'] ?? $_SESSION['role_name'] ?? $_SESSION['role'] ?? ''));
$adminRoles = ['admin', 'system administrator', 'compliance', 'legal', 'hr', 'human resource', 'recruitment'];
$isAuthorized = false;
foreach ($adminRoles as $r) {
    if (str_contains($viewerRole, $r)) {
        $isAuthorized = true;
        break;
    }
}

if (!$isAuthorized) {
    http_response_code(403);
    echo 'You are not authorized to access the onboarding document package.';
    exit;
}

$db = (new Database())->getConnection();
if (!($db instanceof PDO)) {
    throw new RuntimeException('Unable to establish a database connection.');
}

$applicationId = isset($_GET['application_id']) ? trim((string) $_GET['application_id']) : '';
$action = isset($_REQUEST['action']) ? trim((string) $_REQUEST['action']) : '';
$message = '';
$messageType = '';
$packageNumber = '';

if (isset($_GET['generated']) && $_GET['generated'] === '1') {
    $message = 'Onboarding package generated successfully. You can view it from the Existing Packages section below.';
    $messageType = 'success';
}

$employees = [];
try {
    $stmt = $db->prepare("
        SELECT 
            rh.id AS rao_hired_id,
            rh.application_id AS employee_id,
            CONCAT(rh.first_name, ' ', rh.last_name) AS full_name,
            rh.first_name,
            rh.last_name,
            rh.email,
            rh.phone,
            rh.address,
            rh.position,
            rh.department,
            rh.salary,
            rh.hired_at,
            COALESCE(d.department_name, rh.department) AS department_name,
            COALESCE(p.position_name, rh.position) AS position_name
        FROM rao_hired rh
        LEFT JOIN em_departments d ON d.department_name = rh.department
        LEFT JOIN em_positions p ON p.position_name = rh.position
        ORDER BY rh.hired_at DESC, rh.last_name ASC, rh.first_name ASC
    ");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $employees = [];
}

$selectedEmployee = null;
if ($applicationId !== '') {
    foreach ($employees as $emp) {
        if ((string) ($emp['employee_id'] ?? $emp['rao_hired_id'] ?? $emp['candidate_id'] ?? '') === $applicationId) {
            $selectedEmployee = $emp;
            break;
        }
    }
}

$packageDocuments = [
    [
        'code' => 'employment_contract',
        'name' => 'Employment Contract',
        'description' => 'Official employment agreement between the employer and employee.',
        'checked' => true,
        'icon' => 'bi-file-earmark-text',
    ],
    [
        'code' => 'employee_handbook',
        'name' => 'Employee Handbook',
        'description' => 'Company policies, procedures, and workplace guidelines.',
        'checked' => true,
        'icon' => 'bi-book',
    ],
    [
        'code' => 'nda',
        'name' => 'Non-Disclosure Agreement',
        'description' => 'Confidentiality and non-disclosure obligations.',
        'checked' => true,
        'icon' => 'bi-shield-lock',
    ],
];

$contractTemplate = dg_get_document_template($db, 'employment_contract');
$handbookTemplate = dg_get_document_template($db, 'employee_handbook');
$ndaTemplate = dg_get_document_template($db, 'nda');

$contractVersion = $contractTemplate['version'] ?? '1.0';
$handbookVersion = $handbookTemplate['version'] ?? '1.0';
$ndaVersion = $ndaTemplate['version'] ?? '1.0';

function op_validate_employee_for_package(PDO $db, string $employeeId): array {
    $items = [];
    $passed = true;

    $sourceTable = 'em_employees';
    $idColumn = 'employee_id';

    try {
        $stmt = $db->prepare("
            SELECT e.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
            FROM em_employees e
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
            WHERE e.employee_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $employeeId]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            $stmt = $db->prepare("
                SELECT rh.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
                FROM rao_hired rh
                LEFT JOIN em_departments d ON rh.department = d.department_name
                LEFT JOIN em_positions p ON rh.position = p.position_name
                WHERE rh.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $employeeId]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($emp) {
                $sourceTable = 'rao_hired';
                $idColumn = 'id';
            }
        }

        if (!$emp) {
            $stmt = $db->prepare("
                SELECT rh.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
                FROM rao_hired rh
                LEFT JOIN em_departments d ON rh.department = d.department_name
                LEFT JOIN em_positions p ON rh.position = p.position_name
                WHERE rh.application_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $employeeId]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($emp) {
                $sourceTable = 'rao_hired';
                $idColumn = 'application_id';
            }
        }

        if (!$emp) {
            $items[] = ['label' => 'Employee Record', 'status' => 'fail', 'message' => 'Record not found'];
            $passed = false;
            return ['passed' => $passed, 'items' => $items, 'employee' => null, 'source_table' => $sourceTable];
        }

        $fullName = (string) ($emp['full_name'] ?? '');
        if ($fullName === '') {
            $parts = array_filter([$emp['first_name'] ?? '', $emp['middle_name'] ?? '', $emp['last_name'] ?? '']);
            $fullName = trim(implode(' ', $parts));
        }
        $items[] = ['label' => 'Employee Found', 'status' => 'pass', 'message' => $fullName ?: 'Employee #' . $employeeId];

        if (!empty($emp['department_id']) || !empty($emp['department_name'])) {
            $items[] = ['label' => 'Department', 'status' => 'pass', 'message' => (string) ($emp['department_name'] ?? 'Assigned')];
        } else {
            $items[] = ['label' => 'Department', 'status' => 'warn', 'message' => 'Not assigned'];
        }

        if (!empty($emp['position_id']) || !empty($emp['position_name'])) {
            $items[] = ['label' => 'Position', 'status' => 'pass', 'message' => (string) ($emp['position_name'] ?? 'Assigned')];
        } else {
            $items[] = ['label' => 'Position', 'status' => 'warn', 'message' => 'Not assigned'];
        }

        $email = (string) ($emp['email'] ?? '');
        if ($email !== '') {
            $items[] = ['label' => 'Email', 'status' => 'pass', 'message' => $email];
        } else {
            $items[] = ['label' => 'Email', 'status' => 'warn', 'message' => 'Not provided'];
        }

        $dateHired = (string) ($emp['date_hired'] ?? $emp['hire_date'] ?? '');
        if ($dateHired !== '') {
            $items[] = ['label' => 'Date Hired', 'status' => 'pass', 'message' => date('F d, Y', strtotime($dateHired))];
        } else {
            $items[] = ['label' => 'Date Hired', 'status' => 'warn', 'message' => 'Not set'];
        }

        $contractTemplate = dg_get_document_template($db, 'employment_contract');
        if (!$contractTemplate) {
            $items[] = ['label' => 'Contract Template', 'status' => 'fail', 'message' => 'No active contract template'];
            $passed = false;
        } else {
            $items[] = ['label' => 'Contract Template', 'status' => 'pass', 'message' => ($contractTemplate['template_name'] ?? 'Employment Contract') . ' v' . ($contractTemplate['version'] ?? '1.0')];
        }

        $handbookTemplate = dg_get_document_template($db, 'employee_handbook');
        if (!$handbookTemplate) {
            $items[] = ['label' => 'Handbook Template', 'status' => 'fail', 'message' => 'No active handbook template'];
            $passed = false;
        } else {
            $items[] = ['label' => 'Handbook Template', 'status' => 'pass', 'message' => ($handbookTemplate['template_name'] ?? 'Employee Handbook') . ' v' . ($handbookTemplate['version'] ?? '1.0')];
        }

        $ndaTemplate = dg_get_document_template($db, 'nda');
        if (!$ndaTemplate) {
            $items[] = ['label' => 'NDA Template', 'status' => 'fail', 'message' => 'No active NDA template'];
            $passed = false;
        } else {
            $items[] = ['label' => 'NDA Template', 'status' => 'pass', 'message' => ($ndaTemplate['template_name'] ?? 'NDA') . ' v' . ($ndaTemplate['version'] ?? '1.0')];
        }

        return ['passed' => $passed, 'items' => $items, 'employee' => $emp, 'source_table' => $sourceTable];
    } catch (Throwable $e) {
        $items[] = ['label' => 'Validation Error', 'status' => 'fail', 'message' => $e->getMessage()];
        return ['passed' => false, 'items' => $items, 'employee' => null, 'source_table' => $sourceTable];
    }
}

$validationResult = null;
if ($applicationId !== '' && $action === 'validate') {
    $validationResult = op_validate_employee_for_package($db, $applicationId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_package_status' && $applicationId !== '') {
    $packageId = isset($_POST['package_id']) ? (int) $_POST['package_id'] : 0;
    $newStatus = isset($_POST['new_status']) ? trim((string) $_POST['new_status']) : '';
    $allowedStatuses = ['Generated', 'For Legal Review', 'Verified', 'Ready to Send', 'Sent', 'Acknowledged'];
    if ($packageId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $stmt =$db->prepare("UPDATE lc_onboarding_packages SET status = :status, updated_at = NOW() WHERE package_id = :id AND employee_id = :eid");
        $stmt->execute([':status' => $newStatus, ':id' => $packageId, ':eid' => (int) $applicationId]);
        $message = 'Package status updated to ' . $newStatus . '.';
        $messageType = 'success';
    }
}

$existingPackages = [];
if ($applicationId !== '') {
    try {
        $stmt = $db->prepare("
            SELECT package_id, package_number, status, generated_at, file_path, file_name,
                   contract_template_version, handbook_template_version, nda_template_version
            FROM lc_onboarding_packages
            WHERE employee_id = :eid
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([':eid' => (int) $applicationId]);
        $existingPackages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $existingPackages = [];
    }
}
?>

<div class="module-content">
    <div class="gd-module">
        <div class="gd-layout">
            <div class="gd-main">
                <div class="gd-section">
                    <div class="gd-section-title">Employee Information</div>
                    <div class="gd-info-card">
                        <div class="gd-info-row">
                            <div class="gd-info-item">
                                <div class="gd-info-label">Full Name</div>
                                <div class="gd-info-value"><?= htmlspecialchars($selectedEmployee['full_name'] ?? trim(($selectedEmployee['first_name'] ?? '') . ' ' . ($selectedEmployee['middle_name'] ?? '') . ' ' . ($selectedEmployee['last_name'] ?? ''))) ?></div>
                            </div>
                            <div class="gd-info-item">
                                <div class="gd-info-label">Application ID</div>
                                <div class="gd-info-value"><?= htmlspecialchars($applicationId !== '' ? $applicationId : ($selectedEmployee['employee_code'] ?? $selectedEmployee['employee_no'] ?? '—')) ?></div>
                            </div>
                        </div>
                        <div class="gd-info-row">
                            <div class="gd-info-item">
                                <div class="gd-info-label">Department</div>
                                <div class="gd-info-value"><?= htmlspecialchars($selectedEmployee['department_name'] ?? '—') ?></div>
                            </div>
                            <div class="gd-info-item">
                                <div class="gd-info-label">Position</div>
                                <div class="gd-info-value"><?= htmlspecialchars($selectedEmployee['position_name'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="gd-info-row">
                            <div class="gd-info-item">
                                <div class="gd-info-label">Email</div>
                                <div class="gd-info-value"><?= htmlspecialchars($selectedEmployee['email'] ?? '—') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($validationResult): ?>
                <div class="gd-section">
                    <div class="gd-section-title">Validation Checklist</div>
                    <div class="gd-validation-list">
                        <?php foreach ($validationResult['items'] as $item): ?>
                            <div class="gd-validation-item gd-validation-item--<?= $item['status'] ?>">
                                <div class="gd-validation-icon">
                                    <i class="bi bi-<?= $item['status'] === 'pass' ? 'check-circle-fill' : ($item['status'] === 'fail' ? 'x-circle-fill' : 'exclamation-triangle-fill') ?>"></i>
                                </div>
                                <div class="gd-validation-content">
                                    <div class="gd-validation-label"><?= htmlspecialchars($item['label']) ?></div>
                                    <div class="gd-validation-message"><?= htmlspecialchars($item['message']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($selectedEmployee): ?>
                <div class="gd-section">
                    <div class="gd-section-title">Documents in Package</div>
                    <div class="gd-info-card">
                        <div style="display:flex; flex-direction:column; gap:14px;">
                            <?php foreach ($packageDocuments as $doc): ?>
                                <div style="display:flex; align-items:flex-start; gap:12px; padding:12px; border:1px solid var(--border,#e4e8ee); border-radius:8px; background:#fff;">
                                    <div style="margin-top:2px;">
                                        <i class="bi bi-<?= htmlspecialchars($doc['icon']) ?>" style="font-size:1.2rem; color:var(--info-blue,#3b82c4);"></i>
                                    </div>
                                    <div style="flex:1;">
                                        <div style="font-weight:700; color:var(--text-900,#1b2430); font-size:.92rem;"><?= htmlspecialchars($doc['name']) ?></div>
                                        <div style="font-size:.78rem; color:var(--text-500,#6b7280); margin-top:2px;"><?= htmlspecialchars($doc['description']) ?></div>
                                        <div style="font-size:.72rem; color:var(--text-400,#8b93a1); margin-top:4px;">
                                            Template v<?= htmlspecialchars($doc['code'] === 'employment_contract' ? $contractVersion : ($doc['code'] === 'employee_handbook' ? $handbookVersion : $ndaVersion)) ?>
                                        </div>
                                    </div>
                                    <div style="display:flex; align-items:center;">
                                        <i class="bi bi-check-circle-fill" style="color:#16a34a; font-size:1.1rem;"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top:14px; padding:10px 14px; border-radius:8px; background:rgba(59,130,196,.06); border:1px solid rgba(59,130,196,.15); font-size:.78rem; color:var(--text-600,#5b6472);">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Read-only workflow:</strong> All documents are populated from existing employee records. No salary, position, or department fields can be manually entered in this module.
                        </div>
                        <?php if ($validationResult && $validationResult['passed']): ?>
                        <div style="margin-top:14px;">
                            <a href="?page=generate-onboarding-package&application_id=<?= urlencode($applicationId) ?>&generate=1" class="gd-btn-generate" onclick="return confirm('Generate onboarding document package? This will create a compiled HTML document containing the Employment Contract, Employee Handbook, and NDA.')">
                                <i class="bi bi-file-earmark-code"></i> Generate HTML Package
                            </a>
                        </div>
                        <?php elseif ($validationResult && !$validationResult['passed']): ?>
                        <div style="margin-top:14px;">
                            <button class="gd-btn-generate" disabled style="opacity:0.6; cursor:not-allowed;">
                                <i class="bi bi-x-circle"></i> Validation Failed
                            </button>
                        </div>
                        <?php else: ?>
                        <div style="margin-top:14px;">
                            <a href="?page=generate-onboarding-package&application_id=<?= urlencode($applicationId) ?>&generate=1" class="gd-btn-generate" onclick="return confirm('Generate onboarding document package? This will create a compiled HTML document containing the Employment Contract, Employee Handbook, and NDA.')">
                                <i class="bi bi-file-earmark-code"></i> Generate HTML Package
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="gd-side">
                <?php if ($selectedEmployee): ?>
                <div class="gd-side-card">
                    <div class="gd-side-title">Summary</div>
                    <div class="gd-side-row">
                        <div class="gd-side-label">Employee</div>
                        <div class="gd-side-value"><?= htmlspecialchars($selectedEmployee['full_name'] ?? trim(($selectedEmployee['first_name'] ?? '') . ' ' . ($selectedEmployee['last_name'] ?? ''))) ?></div>
                    </div>
                    <div class="gd-side-row">
                        <div class="gd-side-label">Department</div>
                        <div class="gd-side-value"><?= htmlspecialchars($selectedEmployee['department_name'] ?? '—') ?></div>
                    </div>
                    <div class="gd-side-row">
                        <div class="gd-side-label">Position</div>
                        <div class="gd-side-value"><?= htmlspecialchars($selectedEmployee['position_name'] ?? '—') ?></div>
                    </div>
                    <div class="gd-side-row">
                        <div class="gd-side-label">Documents</div>
                        <div class="gd-side-value">3</div>
                    </div>
                    <div class="gd-side-row">
                        <div class="gd-side-label">Prepared By</div>
                        <div class="gd-side-value"><?= htmlspecialchars($user['name'] ?? $user['full_name'] ?? 'System') ?></div>
                    </div>
                    <div class="gd-side-row">
                        <div class="gd-side-label">Date</div>
                        <div class="gd-side-value"><?= date('F j, Y') ?></div>
                    </div>
                </div>

                <?php if (!empty($existingPackages)): ?>
                <div class="gd-side-card">
                    <div class="gd-side-title">Existing Packages</div>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach ($existingPackages as $pkg): 
                            $statusClass = match($pkg['status']) {
                                'Generated' => 'gd-status--ready',
                                'For Legal Review' => 'gd-status--blocked',
                                'Verified' => 'gd-status--ready',
                                'Ready to Send' => 'gd-status--ready',
                                'Sent' => 'gd-status--ready',
                                'Acknowledged' => 'gd-status--ready',
                                default => 'gd-status--blocked',
                            };
                        ?>
                        <div style="padding:10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; background:#fff;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:6px;">
                                <span style="font-size:.78rem; font-weight:700; color:var(--text-900,#1b2430);"><?= htmlspecialchars($pkg['package_number']) ?></span>
                                <span class="gd-status <?= $statusClass ?>" style="font-size:.65rem;"><?= htmlspecialchars($pkg['status']) ?></span>
                            </div>
                            <div style="font-size:.72rem; color:var(--text-500,#6b7280); margin-bottom:6px;">
                                v<?= htmlspecialchars($pkg['contract_template_version'] ?? '1.0') ?> / v<?= htmlspecialchars($pkg['handbook_template_version'] ?? '1.0') ?> / v<?= htmlspecialchars($pkg['nda_template_version'] ?? '1.0') ?>
                            </div>
                            <form method="post" action="" style="display:flex; gap:6px; align-items:center;">
                                <input type="hidden" name="action" value="update_package_status">
                                <input type="hidden" name="application_id" value="<?= htmlspecialchars($applicationId) ?>">
                                <input type="hidden" name="package_id" value="<?= (int) $pkg['package_id'] ?>">
                                <select name="new_status" style="flex:1; padding:4px 8px; border-radius:6px; border:1px solid var(--border,#e4e8ee); font-size:.72rem; background:#fff;">
                                    <?php 
                                    $statuses = ['Generated', 'For Legal Review', 'Verified', 'Ready to Send', 'Sent', 'Acknowledged'];
                                    foreach ($statuses as $st): 
                                    ?>
                                        <option value="<?= htmlspecialchars($st) ?>" <?= $pkg['status'] === $st ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($st) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" style="padding:4px 10px; border-radius:6px; border:1px solid var(--border,#e4e8ee); background:#fff; font-size:.72rem; cursor:pointer; font-weight:600;">
                                    Update
                                </button>
                            </form>
                            <?php if (!empty($pkg['file_path'])): ?>
                            <a href="<?= htmlspecialchars($pkg['file_path']) ?>" target="_blank" style="font-size:.72rem; color:var(--info-blue,#3b82c4); text-decoration:none; margin-top:4px; display:inline-block;">
                                <i class="bi bi-file-earmark-code"></i> View HTML Package
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="gd-side-card">
                    <div class="gd-side-title">Workflow</div>
                    <div class="gd-timeline">
                        <div class="gd-timeline-item">
                            <div class="gd-timeline-dot gd-timeline-dot--done"></div>
                            <div class="gd-timeline-content">
                                <div class="gd-timeline-title">Select Employee</div>
                                <div class="gd-timeline-meta">Current step</div>
                            </div>
                        </div>
                        <div class="gd-timeline-item">
                            <div class="gd-timeline-dot"></div>
                            <div class="gd-timeline-content">
                                <div class="gd-timeline-title">Validate Records</div>
                                <div class="gd-timeline-meta">Pending</div>
                            </div>
                        </div>
                        <div class="gd-timeline-item">
                            <div class="gd-timeline-dot"></div>
                            <div class="gd-timeline-content">
                                <div class="gd-timeline-title">Preview Package</div>
                                <div class="gd-timeline-meta">Pending</div>
                            </div>
                        </div>
                        <div class="gd-timeline-item">
                            <div class="gd-timeline-dot"></div>
                            <div class="gd-timeline-content">
                                <div class="gd-timeline-title">Generate HTML Package</div>
                                <div class="gd-timeline-meta">Pending</div>
                            </div>
                        </div>
                        <div class="gd-timeline-item">
                            <div class="gd-timeline-dot"></div>
                            <div class="gd-timeline-content">
                                <div class="gd-timeline-title">Legal Review</div>
                                <div class="gd-timeline-meta">Pending</div>
                            </div>
                        </div>
                        <div class="gd-timeline-item">
                            <div class="gd-timeline-dot"></div>
                            <div class="gd-timeline-content">
                                <div class="gd-timeline-title">Verified / Approved</div>
                                <div class="gd-timeline-meta">Pending</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
