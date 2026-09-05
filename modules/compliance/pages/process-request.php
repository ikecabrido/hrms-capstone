<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../lib/ajax/document_template_helper.php';

$pageTitle = 'Process Document Request';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$db = (new Database())->getConnection();

if ($db === null) {
    http_response_code(500);
    echo 'Database connection failed.';
    exit;
}

$requestId = isset($_GET['request_id']) ? trim((string) $_GET['request_id']) : '';
if ($requestId === '' || !ctype_digit($requestId)) {
    http_response_code(400);
    echo 'Invalid request ID.';
    exit;
}

$requestId = (int) $requestId;

try {
    $stmt = $db->prepare("
        SELECT 
            dr.*,
            e.employee_id,
            CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                e.department_id,
                e.position_id,
                e.email,
                e.employment_status,
                e.hire_date,
                e.birth_date,
                e.civil_status,
                e.gender,
                e.current_address,
                e.mobile_no,
                e.phone_no,
                d.department_name,
                p.position_name,
                rh.id AS rao_hired_id,
                rh.position AS rao_position,
                rh.department AS rao_department,
                rh.salary AS rao_salary,
                rh.hired_at AS rao_hired_at
            FROM lc_document_requests dr
            LEFT JOIN em_employees e ON dr.employee_id = e.employee_id
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
            LEFT JOIN rao_hired rh ON rh.application_id = dr.employee_id
            WHERE dr.request_id = :id
            LIMIT 1
        ");
    $stmt->execute([':id' => $requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $request = false;
}

if (!$request) {
    http_response_code(404);
    echo 'Document request not found.';
    exit;
}

$documentType = $request['document_type'];
$requestStatus = $request['request_status'];

$docTypeMap = [
    'Employment Contract (New Hire)' => 'employment_contract',
    'Contract Renewal' => 'contract_renewal',
    'Contract Extension' => 'contract_extension',
    'Salary Rectification Agreement' => 'salary_rectification',
    'Certificate of Employment (COE)' => 'coe',
    'Quitclaim and Release' => 'quitclaim',
    'Exit Acknowledgement' => 'exit_acknowledgement',
    'Leave Agreement' => 'leave_agreement',
    'Return-to-Work Agreement' => 'return_service',
    'Non-Disclosure Agreement (NDA)' => 'nda',
    'Training Bond' => 'training_bond',
    'Study Leave Agreement' => 'study_leave',
    'Non-Compete Agreement' => 'non_compete',
    'Notice to Explain (NTE)' => 'nte',
    'Written Warning' => 'written_warning',
    'Suspension Notice' => 'suspension_notice',
    'Employee Handbook' => 'employee_handbook',
    'Notice of Decision' => 'notice_of_decision',
    'Termination Decision' => 'termination_decision',
    'Exit Clearance' => 'exit_clearance',
    'Clearance Survey' => 'clearance_survey',
];

$templateCode = $docTypeMap[$documentType] ?? strtolower(str_replace([' ', '(', ')', '-'], ['_', '', '', '_'], $documentType));

$availableTemplates = [];
$libPath = __DIR__ . '/../lib/';
if (is_dir($libPath)) {
    foreach (glob($libPath . '*.php') as $file) {
        $filename = basename($file);
        $code = str_replace('.php', '', $filename);
        $availableTemplates[] = [
            'code' => $code,
            'filename' => $filename,
            'path' => $file,
        ];
    }
}

$selectedTemplate = $templateCode;
if (isset($_POST['selected_template']) && !empty($_POST['selected_template'])) {
    $selectedTemplate = trim((string) $_POST['selected_template']);
}

$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
$generateError = '';
$generateSuccess = false;
$generatedFilePath = '';
$generatedFileName = '';

if ($action === 'generate' && $requestStatus !== 'Completed' && $requestStatus !== 'Released') {
    if (!in_array($selectedTemplate . '.php', array_column($availableTemplates, 'filename'), true)) {
        $generateError = 'Selected template is not available.';
    } else {
        $templatePath = $libPath . $selectedTemplate . '.php';
        $employeeId = $request['employee_id'];
        
        $sourceTableMap = [
            'employment_contract' => 'new_hire_table',
            'contract_renewal' => 'em_employees',
            'contract_extension' => 'em_employees',
            'salary_rectification' => 'em_employees',
            'leave_agreement' => 'em_employees',
            'study_leave' => 'em_employees',
            'suspension_notice' => 'em_employees',
            'employee_handbook' => 'new_hire_table',
            'notice_of_decision' => 'em_employees',
            'termination_decision' => 'em_employees',
            'return_service' => 'em_employees',
        ];
        $sourceTable = $sourceTableMap[$selectedTemplate] ?? 'em_employees';
        $idColumn = $sourceTable === 'new_hire_table' ? 'candidate_id' : 'employee_id';

        $employeeData = $request;
        if ($sourceTable === 'em_employees' && $employeeId) {
            try {
                $stmt = $db->prepare("
                    SELECT e.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
                    FROM em_employees e
                    LEFT JOIN em_departments d ON d.department_id = e.department_id
                    LEFT JOIN em_positions p ON p.position_id = e.position_id
                    WHERE e.employee_id = :id LIMIT 1
                ");
                $stmt->execute([':id' => $employeeId]);
                $employeeData = $stmt->fetch(PDO::FETCH_ASSOC) ?: $request;
            } catch (Throwable $e) {
                $employeeData = $request;
            }
        }

        $employer = dg_get_active_employer($db);
        $templateRecord = dg_get_document_template($db, $selectedTemplate);

        ob_start();
        include $templatePath;
        $rendered = ob_get_clean();

        $rendered = dg_replace_placeholders($rendered, $employeeData, $employer);

        if (!class_exists('Dompdf\Dompdf')) {
            require_once __DIR__ . '/../lib/vendor/autoload.php';
        }
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isPhpEnabled', true);
        $options->set('dpi', 96);
        $dompdf = new \Dompdf\Dompdf($options);
        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' . htmlspecialchars($documentType) . '</title><style>body{font-family:DejaVu Sans,sans-serif;font-size:11pt;color:#1b2430;margin:0;padding:0;}table{border-collapse:collapse;width:100%;}hr{border:0;border-top:1px solid #e4e8ee;margin:10px 0 16px;}@page{margin:12mm;size:A4 portrait;}</style></head><body><div class="page">' . $rendered . '</div></body></html>';
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        $saveDir = __DIR__ . '/../pages/labor-law-compliance/uploads/contracts/';
        if (!is_dir($saveDir)) {
            mkdir($saveDir, 0775, true);
        }
        $contractNumber = dg_generate_contract_number($db);
        $contractFilename = 'REQ-' . str_pad((string)$requestId, 4, '0', STR_PAD_LEFT) . '_' . preg_replace('/[^A-Za-z0-9]/', '', $contractNumber) . '.pdf';
        $savePath = $saveDir . $contractFilename;

        $db->beginTransaction();
        try {
            file_put_contents($savePath, $pdfOutput);
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $fileUrl = $protocol . $host . '/hrms-capstone/modules/compliance/assets/uploads/contracts/' . $contractFilename;
            
            $db->prepare("
                INSERT INTO lc_contracts (employee_id, contract_number, contract_type, governing_law, jurisdiction, category_id, requires_dual_sig, digital_signature_status, start_date, end_date, status, monthly_salary, working_hours_per_week, notes, created_by, created_by_role)
                VALUES (:employee_id, :contract_number, :contract_type, :governing_law, :jurisdiction, :category_id, 1, 'none', :start_date, :end_date, 'Draft', :monthly_salary, 40, :notes, :created_by, 'hr')
            ")->execute([
                ':employee_id' => (int) $employeeId,
                ':contract_number' => $contractNumber,
                ':contract_type' => $request['employment_status'] ?: 'Regular',
                ':governing_law' => $templateRecord['governing_law'] ?? 'Philippine Labor Code (PD 442)',
                ':jurisdiction' => 'Philippines',
                ':category_id' => $templateRecord['category_id'] ?? null,
                ':start_date' => date('Y-m-d'),
                ':end_date' => date('Y-m-d', strtotime('+1 year')),
                ':monthly_salary' => '0.00',
                ':notes' => 'Generated from request #' . $requestId,
                ':created_by' => (int) ($user['user_id'] ?? $user['id'] ?? 0),
            ]);
            
            $contractId = (int) $db->lastInsertId();
            $db->prepare("UPDATE lc_contracts SET file_path = :file_path, file_name = :file_name WHERE contract_id = :id")->execute([
                ':file_path' => $fileUrl,
                ':file_name' => $contractFilename,
                ':id' => $contractId,
            ]);

            $db->prepare("
                INSERT INTO lc_document_requests (employee_id, rao_hired_id, document_type, request_status, priority, notes, requires_signature, signature_status)
                VALUES (:employee_id, :rao_hired_id, :document_type, 'Generated', 'Medium', NULL, 1, 'none')
            ")->execute([
                ':employee_id' => (int) $employeeId,
                ':rao_hired_id' => !empty($request['rao_hired_id']) ? (int) $request['rao_hired_id'] : null,
                ':document_type' => $documentType,
            ]);

            $db->commit();
            $generateSuccess = true;
            $generatedFilePath = $savePath;
            $generatedFileName = $contractFilename;
            
            $requestStatus = 'Generated';
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $generateError = 'Failed to generate document: ' . $e->getMessage();
        }
    }
}

if ($requestStatus === 'Pending') {
    $db->prepare("UPDATE lc_document_requests SET request_status = 'Processing' WHERE request_id = :id")->execute([':id' => $requestId]);
    $requestStatus = 'Processing';
}

ob_start();
?>

<div class="module-content">
    <div class="dg-process-header">
        <div class="dg-process-header-left">
            <h2>Process Document Request</h2>
            <p class="dg-process-subtitle">Request #<?= htmlspecialchars($requestId) ?> — <?= htmlspecialchars($documentType) ?></p>
        </div>
        <div class="dg-process-header-right">
            <a href="?page=document-requests" class="dg-btn dg-btn-ghost">
                <i class="bi bi-arrow-left"></i> Back to Requests
            </a>
        </div>
    </div>

    <?php if ($generateSuccess): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            Document generated successfully! 
            <a href="?page=document-requests">Return to request list</a>
        </div>
    <?php elseif ($generateError !== ''): ?>
        <div class="alert alert-danger">
            <i class="bi bi-x-circle"></i>
            <?= htmlspecialchars($generateError) ?>
        </div>
    <?php endif; ?>

    <div class="dg-process-layout">
        <div class="dg-process-main">
            <div class="dg-process-section">
                <h3>Request Information</h3>
                <div class="dg-info-card">
                    <div class="dg-info-row">
                        <div class="dg-info-item">
                            <label>Request ID</label>
                            <div class="dg-info-value">#<?= htmlspecialchars($requestId) ?></div>
                        </div>
                        <div class="dg-info-item">
                            <label>Document Type</label>
                            <div class="dg-info-value"><?= htmlspecialchars($documentType) ?></div>
                        </div>
                    </div>
                    <div class="dg-info-row">
                        <div class="dg-info-item">
                            <label>Priority</label>
                            <div class="dg-info-value">
                                <span class="badge badge-<?= $request['priority'] === 'High' ? 'danger' : ($request['priority'] === 'Medium' ? 'warning' : 'info') ?>">
                                    <?= htmlspecialchars($request['priority']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="dg-info-item">
                            <label>Status</label>
                            <div class="dg-info-value">
                                <span class="badge badge-<?= $requestStatus === 'Pending' ? 'warning' : ($requestStatus === 'Processing' ? 'info' : ($requestStatus === 'Generated' ? 'primary' : 'success')) ?>">
                                    <?= htmlspecialchars($requestStatus) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="dg-info-row">
                        <div class="dg-info-item">
                            <label>Requested</label>
                            <div class="dg-info-value"><?= date('F j, Y g:i A', strtotime($request['created_at'])) ?></div>
                        </div>
                        <?php if (!empty($request['notes'])): ?>
                            <div class="dg-info-item">
                                <label>Notes</label>
                                <div class="dg-info-value"><?= htmlspecialchars($request['notes']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="dg-process-section">
                <h3>Employee Information</h3>
                <div class="dg-info-card">
                    <div class="dg-info-row">
                        <div class="dg-info-item">
                            <label>Full Name</label>
                            <div class="dg-info-value"><?= htmlspecialchars($request['full_name'] ?: 'Unknown') ?></div>
                        </div>
                        <div class="dg-info-item">
                            <label>Email</label>
                            <div class="dg-info-value"><?= htmlspecialchars($request['email'] ?: '—') ?></div>
                        </div>
                    </div>
                    <div class="dg-info-row">
                        <div class="dg-info-item">
                            <label>Department</label>
                            <div class="dg-info-value"><?= htmlspecialchars($request['department_name'] ?: $request['department'] ?: '—') ?></div>
                        </div>
                        <div class="dg-info-item">
                            <label>Position</label>
                            <div class="dg-info-value"><?= htmlspecialchars($request['position_name'] ?: $request['position'] ?: '—') ?></div>
                        </div>
                    </div>
                    <div class="dg-info-row">
                        <div class="dg-info-item">
                            <label>Employment Status</label>
                            <div class="dg-info-value"><?= htmlspecialchars($request['employment_status'] ?: '—') ?></div>
                        </div>
                        <div class="dg-info-item">
                            <label>Hire Date</label>
                            <div class="dg-info-value"><?= !empty($request['hire_date']) ? date('F j, Y', strtotime($request['hire_date'])) : '—' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($requestStatus !== 'Completed' && $requestStatus !== 'Released'): ?>
            <div class="dg-process-section">
                <h3>Select Template</h3>
                <p class="dg-process-help">Choose a template from the library to generate this document.</p>
                
                <div class="dg-template-grid">
                    <?php if (empty($availableTemplates)): ?>
                        <div class="dg-empty-state">No templates available in the library.</div>
                    <?php else: ?>
                        <?php foreach ($availableTemplates as $tpl): ?>
                            <div class="dg-template-card <?= $selectedTemplate === $tpl['code'] ? 'dg-template-card--selected' : '' ?>">
                                <form method="post" action="" class="dg-template-form">
                                    <input type="hidden" name="selected_template" value="<?= htmlspecialchars($tpl['code']) ?>">
                                    <div class="dg-template-info">
                                        <div class="dg-template-name"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $tpl['code']))) ?></div>
                                        <div class="dg-template-file"><?= htmlspecialchars($tpl['filename']) ?></div>
                                    </div>
                                    <button type="submit" class="dg-btn dg-btn-primary">
                                        <?= $selectedTemplate === $tpl['code'] ? 'Selected' : 'Use Template' ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($selectedTemplate && $requestStatus !== 'Completed' && $requestStatus !== 'Released'): ?>
            <div class="dg-process-section">
                <h3>Generate Document</h3>
                <div class="dg-generate-card">
                    <div class="dg-generate-info">
                        <div class="dg-generate-label">Selected Template</div>
                        <div class="dg-generate-value"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $selectedTemplate))) ?></div>
                    </div>
                    <div class="dg-generate-info">
                        <div class="dg-generate-label">Employee</div>
                        <div class="dg-generate-value"><?= htmlspecialchars($request['full_name'] ?: 'Unknown') ?></div>
                    </div>
                    <div class="dg-generate-actions">
                         <a href="?page=preview-document&request_id=<?= urlencode($requestId) ?>" class="dg-btn dg-btn-secondary" target="_blank">
                             <i class="bi bi-eye"></i> Preview
                         </a>
                        <a href="?page=generate-document&employee_id=<?= urlencode($request['employee_id']) ?>&document_type=<?= urlencode($templateCode) ?>&template=<?= urlencode($selectedTemplate . '.php') ?>&template_code=<?= urlencode($selectedTemplate) ?>&hr_signatory=&generate=1" class="dg-btn dg-btn-primary" onclick="return confirm('Generate PDF document? This will create a new document record.')">
                            <i class="bi bi-file-earmark-pdf"></i> Generate PDF
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="dg-process-side">
            <div class="dg-side-card">
                <div class="dg-side-title">Request Details</div>
                <div class="dg-side-row">
                    <div class="dg-side-label">Request ID</div>
                    <div class="dg-side-value">#<?= htmlspecialchars($requestId) ?></div>
                </div>
                <div class="dg-side-row">
                    <div class="dg-side-label">Employee</div>
                    <div class="dg-side-value"><?= htmlspecialchars($request['full_name'] ?: 'Unknown') ?></div>
                </div>
                <div class="dg-side-row">
                    <div class="dg-side-label">Document</div>
                    <div class="dg-side-value"><?= htmlspecialchars($documentType) ?></div>
                </div>
                <div class="dg-side-row">
                    <div class="dg-side-label">Status</div>
                    <div class="dg-side-value"><?= htmlspecialchars($requestStatus) ?></div>
                </div>
            </div>

            <div class="dg-side-card">
                <div class="dg-side-title">Workflow</div>
                <div class="dg-workflow">
                    <div class="dg-workflow-step dg-workflow-step--done">
                        <div class="dg-workflow-dot"></div>
                        <div class="dg-workflow-label">Request Submitted</div>
                    </div>
                    <div class="dg-workflow-line"></div>
                    <div class="dg-workflow-step <?= in_array($requestStatus, ['Processing', 'Generated', 'Released', 'Completed']) ? 'dg-workflow-step--done' : '' ?>">
                        <div class="dg-workflow-dot"></div>
                        <div class="dg-workflow-label">Processing</div>
                    </div>
                    <div class="dg-workflow-line"></div>
                    <div class="dg-workflow-step <?= in_array($requestStatus, ['Generated', 'Released', 'Completed']) ? 'dg-workflow-step--done' : '' ?>">
                        <div class="dg-workflow-dot"></div>
                        <div class="dg-workflow-label">Generated</div>
                    </div>
                    <div class="dg-workflow-line"></div>
                    <div class="dg-workflow-step <?= in_array($requestStatus, ['Released', 'Completed']) ? 'dg-workflow-step--done' : '' ?>">
                        <div class="dg-workflow-dot"></div>
                        <div class="dg-workflow-label">Released</div>
                    </div>
                    <div class="dg-workflow-line"></div>
                    <div class="dg-workflow-step <?= $requestStatus === 'Completed' ? 'dg-workflow-step--done' : '' ?>">
                        <div class="dg-workflow-dot"></div>
                        <div class="dg-workflow-label">Completed</div>
                    </div>
                </div>
            </div>

            <?php if ($generateSuccess): ?>
            <div class="dg-side-card">
                <div class="dg-side-title">Generated Document</div>
                <div class="dg-side-row">
                    <div class="dg-side-label">File</div>
                    <div class="dg-side-value"><?= htmlspecialchars($generatedFileName) ?></div>
                </div>
                <div class="dg-side-actions">
                    <a href="?page=document-requests" class="dg-btn dg-btn-primary" style="width: 100%; justify-content: center;">
                        <i class="bi bi-check"></i> Done
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dg-process-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border, #e5e7eb);
}

.dg-process-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-900, #1b2430);
    margin: 0 0 4px;
}

.dg-process-subtitle {
    color: var(--text-500, #6b7280);
    font-size: 0.9rem;
    margin: 0;
}

.dg-process-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 20px;
    align-items: start;
}

.dg-process-main {
    min-width: 0;
}

.dg-process-side {
    width: 340px;
    flex-shrink: 0;
}

.dg-process-section {
    margin-bottom: 24px;
}

.dg-process-section h3 {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-500, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 12px;
}

.dg-process-help {
    color: var(--text-500, #6b7280);
    font-size: 0.88rem;
    margin: 0 0 16px;
}

.dg-info-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 10px;
    padding: 16px;
}

.dg-info-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.dg-info-row + .dg-info-row {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border, #e5e7eb);
}

.dg-info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.dg-info-item label {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-400, #8b93a1);
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.dg-info-value {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--text-900, #1b2430);
}

.dg-template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
}

.dg-template-card {
    background: var(--card-bg, #fff);
    border: 2px solid var(--border, #e5e7eb);
    border-radius: 10px;
    padding: 16px;
    transition: all 0.15s ease;
}

.dg-template-card--selected {
    border-color: var(--info-blue, #3b82c4);
    background: rgba(59, 130, 196, 0.04);
}

.dg-template-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dg-template-info {
    flex: 1;
}

.dg-template-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-900, #1b2430);
    margin-bottom: 4px;
}

.dg-template-file {
    font-size: 0.78rem;
    color: var(--text-400, #8b93a1);
    font-family: monospace;
}

.dg-generate-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 10px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.dg-generate-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.dg-generate-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-400, #8b93a1);
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.dg-generate-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-900, #1b2430);
}

.dg-generate-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.dg-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.15s ease;
}

.dg-btn-primary {
    background: #2563eb;
    color: #fff;
}

.dg-btn-primary:hover {
    background: #1d4ed8;
    color: #fff;
}

.dg-btn-secondary {
    background: #fff;
    border: 1px solid var(--border, #e5e7eb);
    color: var(--text-700, #3b4252);
}

.dg-btn-secondary:hover {
    border-color: var(--info-blue, #3b82c4);
    color: var(--info-blue, #3b82c4);
}

.dg-btn-ghost {
    background: transparent;
    border: 1px solid var(--border, #e5e7eb);
    color: var(--text-700, #3b4252);
}

.dg-btn-ghost:hover {
    background: var(--paper, #eef1f5);
}

.dg-side-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 16px;
}

.dg-side-title {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-500, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.dg-side-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
    padding: 6px 0;
}

.dg-side-row + .dg-side-row {
    border-top: 1px solid var(--border, #e5e7eb);
}

.dg-side-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-400, #8b93a1);
}

.dg-side-value {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-900, #1b2430);
    text-align: right;
}

.dg-workflow {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.dg-workflow-step {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
}

.dg-workflow-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--border, #e5e7eb);
    border: 2px solid var(--text-400, #8b93a1);
    flex-shrink: 0;
}

.dg-workflow-step--done .dg-workflow-dot {
    background: #2563eb;
    border-color: #2563eb;
}

.dg-workflow-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-600, #5b6472);
}

.dg-workflow-step--done .dg-workflow-label {
    color: #2563eb;
}

.dg-workflow-line {
    width: 2px;
    height: 16px;
    background: var(--border, #e5e7eb);
    margin-left: 4px;
}

.dg-side-actions {
    margin-top: 12px;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #16a34a;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #dc2626;
}

@media (max-width: 1024px) {
    .dg-process-layout {
        grid-template-columns: 1fr;
    }
    
    .dg-process-side {
        width: 100%;
    }
    
    .dg-process-header {
        flex-direction: column;
        gap: 12px;
    }
}
</style>





