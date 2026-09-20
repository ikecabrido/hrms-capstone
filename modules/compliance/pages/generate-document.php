<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../lib/ajax/document_template_helper.php';
require_once __DIR__ . '/../classes/DocumentPreviewController.php';

$pageTitle = 'Generate Document';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$employeeId   = isset($_GET['employee_id']) ? trim((string) $_GET['employee_id']) : '';
$documentType = isset($_GET['document_type']) ? trim((string) $_GET['document_type']) : '';
$templateFile = isset($_GET['template']) ? trim((string) $_GET['template']) : '';
$templateCode = isset($_GET['template_code']) ? trim((string) $_GET['template_code']) : '';
$hrSignatory  = isset($_GET['hr_signatory']) ? (string) $_GET['hr_signatory'] : '';

if ($employeeId !== '' && !lc_can_access_employee_document(
    (int) ($_SESSION['employee_id'] ?? 0),
    (string) ($_SESSION['role_name'] ?? $_SESSION['role'] ?? ''),
    (int) $employeeId
)) {
    http_response_code(403);
    echo 'You are not authorized to generate documents for this employee.';
    exit;
}

if ($employeeId === '' || $documentType === '' || $templateFile === '') {
    http_response_code(400);
    echo 'Missing required parameters: employee_id, document_type, template';
    exit;
}

if ($templateCode === 'employee_handbook') {
    $templateFile = 'employee_handbook.php';
}

if (empty($_GET['generate'])) {
    $redirectUrl = '?page=preview-document';
    $params = [];
    foreach (['employee_id', 'document_type', 'template', 'template_code', 'hr_signatory', 'contract_type', 'contract_start_date', 'contract_end_date', 'contract_salary_input'] as $param) {
        if (isset($_GET[$param]) && $_GET[$param] !== '') {
            $params[] = urlencode($param) . '=' . urlencode($_GET[$param]);
        }
    }
    if (!empty($params)) {
        $redirectUrl .= '&' . implode('&', $params);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

$templatePath = __DIR__ . '/../lib/templates/' . $templateFile;
$templateDirMap = [
    'salary_rectification' => 'salary_rectification_agreement',
    'return_service' => 'return_to_work_agreement',
];
$resolvedTemplateDir = $templateDirMap[$templateCode] ?? $templateCode;
$newTemplateDir = __DIR__ . '/../pages/templates/' . ($templateCode !== '' ? $resolvedTemplateDir : pathinfo($templateFile, PATHINFO_FILENAME));
if (!is_file($templatePath) && is_dir($newTemplateDir) && is_file($newTemplateDir . '/preview.php')) {
    $templatePath = $newTemplateDir . '/preview.php';
}
if (!is_file($templatePath)) {
    http_response_code(404);
    echo 'Template file not found: ' . htmlspecialchars($templatePath);
    exit;
}

$sourceTableMap = [
    'employment_contract'     => 'new_hire_table',
    'nda'                     => 'new_hire_table',
    'contract_renewal'        => 'em_employees',
    'contract_extension'      => 'em_employees',
    'salary_rectification'    => 'em_employees',
    'leave_agreement'         => 'em_employees',
    'study_leave'             => 'em_employees',
    'suspension_notice'       => 'em_employees',
    'notice_of_decision'      => 'em_employees',
    'termination_decision'    => 'em_employees',
    'return_service'          => 'em_employees',
    'employee_handbook'       => 'new_hire_table',
];
$sourceTable = $sourceTableMap[$templateCode] ?? 'em_employees';
$idColumn    = $sourceTable === 'new_hire_table' ? 'candidate_id' : 'employee_id';

$employeeData     = null;
$employeeFullName = '';
$employeePosition = '';
$employeeDepartment = '';
$employeeEmail    = '';
$validationItems  = [];
$validationPassed = true;
$contractId       = null;
$contractNumber   = null;
$templateRecord   = null;

if ($employeeId !== '') {
    try {
        $stmt = $db->prepare("
            SELECT e.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
            FROM {$sourceTable} e
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions   p ON e.position_id = p.position_id
            WHERE e.{$idColumn} = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $employeeId]);
        $employeeData = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$employeeData && $sourceTable === 'new_hire_table') {
            $stmt = $db->prepare("
                SELECT e.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
                FROM em_employees e
                LEFT JOIN em_departments d ON e.department_id = d.department_id
                LEFT JOIN em_positions   p ON e.position_id = p.position_id
                WHERE e.employee_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $employeeId]);
            $employeeData = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($employeeData) {
                $sourceTable = 'em_employees';
                $idColumn = 'employee_id';
            }
        }

        if (!$employeeData) {
            $stmt = $db->prepare("
                SELECT rh.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
                FROM rao_hired rh
                LEFT JOIN em_departments d ON rh.department = d.department_name
                LEFT JOIN em_positions p ON rh.position = p.position_name
                WHERE rh.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $employeeId]);
            $employeeData = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($employeeData) {
                $sourceTable = 'rao_hired';
                $idColumn = 'id';
            }
        }

        if (!$employeeData) {
            $validationItems[] = ['label' => 'Employee Found', 'status' => 'fail', 'message' => 'Record not found'];
            $validationPassed = false;
        } else {
            $employeeFullName    = (string) ($employeeData['full_name'] ?? '');
            $employeePosition    = (string) ($employeeData['position_name'] ?? '');
            $employeeDepartment  = (string) ($employeeData['department_name'] ?? '');
            $employeeEmail       = (string) ($employeeData['email'] ?? '');
            $validationItems[]   = ['label' => 'Employee Found', 'status' => 'pass', 'message' => $employeeFullName];

            if ($templateCode === 'employment_contract' && $sourceTable === 'new_hire_table') {
                $applicantStatus = strtolower((string) ($employeeData['applicant_status'] ?? ''));
                if ($applicantStatus !== 'pending contract') {
                    $validationItems[] = ['label' => 'Eligibility', 'status' => 'fail', 'message' => 'Status: ' . ($employeeData['applicant_status'] ?? 'Unknown')];
                    $validationPassed = false;
                } else {
                    $validationItems[] = ['label' => 'Eligibility', 'status' => 'pass', 'message' => 'Pending contract'];
                }

                $requirementsComplete = (int) ($employeeData['requirements_complete'] ?? 0);
                if (!$requirementsComplete) {
                    $validationItems[] = ['label' => 'Requirements', 'status' => 'fail', 'message' => 'Incomplete pre-employment requirements'];
                    $validationPassed = false;
                } else {
                    $validationItems[] = ['label' => 'Requirements', 'status' => 'pass', 'message' => 'Complete'];
                }

                $medicalExam = strtolower((string) ($employeeData['medical_exam_status'] ?? ''));
                if ($medicalExam !== 'passed') {
                    $validationItems[] = ['label' => 'Medical Exam', 'status' => 'fail', 'message' => 'Status: ' . ($employeeData['medical_exam_status'] ?? 'Unknown')];
                    $validationPassed = false;
                } else {
                    $validationItems[] = ['label' => 'Medical Exam', 'status' => 'pass', 'message' => 'Passed'];
                }
            }

            if (!empty($employeeData['department_id']) || !empty($employeeData['department_name'])) {
                $validationItems[] = ['label' => 'Department', 'status' => 'pass', 'message' => $employeeDepartment ?: 'Assigned'];
            } else {
                $validationItems[] = ['label' => 'Department', 'status' => 'warn', 'message' => 'Not assigned'];
            }

            if (!empty($employeeData['position_id']) || !empty($employeeData['position_name'])) {
                $validationItems[] = ['label' => 'Position', 'status' => 'pass', 'message' => $employeePosition ?: 'Assigned'];
            } else {
                $validationItems[] = ['label' => 'Position', 'status' => 'warn', 'message' => 'Not assigned'];
            }
        }
    } catch (Throwable $e) {
        $validationItems[] = ['label' => 'Employee Lookup', 'status' => 'fail', 'message' => $e->getMessage()];
        $validationPassed = false;
    }
}

if ($templateCode !== '') {
    $templateRecord = null;
    try {
        $templateRecord = dg_get_document_template($db, $templateCode);
    } catch (Throwable $e) {}

    if (!$templateRecord) {
        $validationItems[] = ['label' => 'Template', 'status' => 'fail', 'message' => 'Template not found'];
        $validationPassed = false;
    } else {
        $validationItems[] = ['label' => 'Template', 'status' => 'pass', 'message' => $templateRecord['template_name'] ?? ucfirst(str_replace('_', ' ', $templateCode))];
    }
} else {
    $validationItems[] = ['label' => 'Template', 'status' => 'warn', 'message' => 'No template code specified'];
}

if ($templateCode === 'employment_contract') {
    $employeeIdInt = null;
    if (!empty($employeeData['email'])) {
        $stmt = $db->prepare("SELECT employee_id FROM em_employees WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $employeeData['email']]);
        $employeeIdInt = $stmt->fetchColumn();
    }
    if ($employeeIdInt && dg_has_employee_contract($db, (int) $employeeIdInt)) {
        $validationItems[] = ['label' => 'Existing Contract', 'status' => 'fail', 'message' => 'Active contract already exists'];
        $validationPassed = false;
    } else {
        $validationItems[] = ['label' => 'Existing Contract', 'status' => 'pass', 'message' => 'None found'];
    }
}

$docTypeLabel = ucwords(str_replace('_', ' ', $documentType));
$templateLabel = ucfirst(str_replace('_', ' ', $templateCode ?: $documentType));
$generatedFileName = 'document_' . preg_replace('/[^A-Za-z0-9]/', '', $employeeId) . '_' . date('Ymd') . '.pdf';
$generatedAt = '';
$generatedBy = '';
$fileSize = 'Not yet generated';

if (isset($_GET['generated']) && $_GET['generated'] === '1') {
    $generatedAt = date('F j, Y g:i A');
    $generatedBy = htmlspecialchars($user['name'] ?? $user['full_name'] ?? 'System');
}

if (isset($_GET['generate']) && $_GET['generate'] === '1') {
    $contractId = null;
    $contractNumber = null;
    $contractFilename = null;
    $saveDir = __DIR__ . '/../../assets/uploads/contracts/';
    if (!is_dir($saveDir)) {
        mkdir($saveDir, 0775, true);
    }



    $contractNumber = dg_generate_contract_number($db);
    $contractFilename = 'contract_' . preg_replace('/[^A-Za-z0-9]/', '', $contractNumber) . '.pdf';
    $savePath = $saveDir . $contractFilename;

    $templateRecord = dg_get_document_template($db, $templateCode);
    $governingLaw = $templateRecord['governing_law'] ?? 'Philippine Labor Code (PD 442)';
    $categoryId = $templateRecord['category_id'] ?? null;
    $contractType = $employeeData['employment_status'] ?? 'Regular';
    if (!empty($_GET['contract_type'])) {
        $contractType = (string) $_GET['contract_type'];
    }
    $startDate = !empty($_GET['contract_start_date']) ? $_GET['contract_start_date'] : date('Y-m-d');
    $endDate = !empty($_GET['contract_end_date']) ? $_GET['contract_end_date'] : date('Y-m-d', strtotime('+1 year'));
    $salary = !empty($_GET['contract_salary_input']) ? number_format((float) $_GET['contract_salary_input'], 2, '.', '') : null;
    $userId = (int) ($user['user_id'] ?? $user['id'] ?? 0);

    $stmt = $db->prepare("
        INSERT INTO lc_contracts (employee_id, contract_number, contract_type, governing_law, jurisdiction, category_id, requires_dual_sig, digital_signature_status, start_date, end_date, status, monthly_salary, working_hours_per_week, notes, created_by, created_by_role)
        VALUES (:employee_id, :contract_number, :contract_type, :governing_law, :jurisdiction, :category_id, 1, 'none', :start_date, :end_date, 'Draft', :monthly_salary, 40, :notes, :created_by, 'hr')
    ");
    $stmt->execute([
        ':employee_id' => (int) $employeeId, ':contract_number' => $contractNumber, ':contract_type' => $contractType,
        ':governing_law' => $governingLaw, ':jurisdiction' => 'Philippines', ':category_id' => $categoryId,
        ':start_date' => $startDate, ':end_date' => $endDate, ':monthly_salary' => $salary, ':notes' => null, ':created_by' => $userId,
    ]);
    $contractId = (int) $db->lastInsertId();



    ob_start();
    include $templatePath;
    $rendered = ob_get_clean();

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
    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' . htmlspecialchars($documentType) . '</title><style>@page{margin:12mm;size:A4 portrait;}body{font-family:"Times New Roman",Times,serif;font-size:10pt;line-height:1.45;color:#111;margin:0;padding:0;}table{border-collapse:collapse;width:100%;}hr{border:0;border-top:0.5pt solid #ccc;margin:6px 0 10px;}.document-preview{border:none!important;box-shadow:none!important;padding:0!important;margin:0!important;max-width:100%!important;}.document-title{margin:0;text-transform:uppercase;font-size:12pt;font-weight:700;color:#0f2b4d;text-align:center;}.document-subtitle{margin:2px 0 0;font-size:8pt;color:#555;text-align:center;}.document-separator{border:0;border-top:0.5pt solid #ccc;margin:0 0 6px;}.document-information{width:100%;border-collapse:collapse;margin-bottom:6px;font-size:9.5pt;}.document-information td{padding:2px 0;vertical-align:top;font-size:9.5pt;}.document-information .info-label{width:130px;font-weight:700;color:#374151;}.document-information .info-value{color:#111;}.document-body{text-align:justify;}.document-body p{margin:0 0 5px;}.document-body p:last-child{margin-bottom:0;}.document-body h1,.document-body h2,.document-body h3,.document-body h4,.document-body h5,.document-body h6{font-family:"Times New Roman",Times,serif;font-weight:700;color:#0f2b4d;text-transform:uppercase;margin:6px 0 2px;break-after:avoid;page-break-after:avoid;}.document-body h1{font-size:12pt;text-align:center;}.document-body h2{font-size:10pt;border-bottom:0.5pt solid #0f2b4d;padding-bottom:0;}.document-body h3{font-size:9.5pt;}.document-body ul,.document-body ol{margin:0 0 4px;padding-left:18px;}.document-body li{margin-bottom:0;break-inside:avoid;}</style></head><body><div class="page">' . $rendered . '</div></body></html>';
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();

    $db->beginTransaction();
    try {
        file_put_contents($savePath, $pdfOutput);
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $fileUrl = $protocol . $host . '/hrms-capstone/modules/compliance/assets/uploads/contracts/' . $contractFilename;
        $db->prepare("UPDATE lc_contracts SET file_path = :file_path, file_name = :file_name WHERE contract_id = :id")->execute([':file_path' => $fileUrl, ':file_name' => $contractFilename, ':id' => $contractId]);
        $requestDocType = ucwords(str_replace('_', ' ', $documentType));
        $requestTemplateCode = $templateCode !== '' ? $templateCode : null;
        $db->prepare("INSERT INTO lc_document_requests (employee_id, rao_hired_id, document_type, request_status, priority, notes, requires_signature, signature_status, template_code) VALUES (:employee_id, :rao_hired_id, :document_type, 'completed', 'Medium', NULL, 1, 'none', :template_code)")->execute([
            ':employee_id' => (int) $employeeId,
            ':rao_hired_id' => $sourceTable === 'rao_hired' ? (int) $employeeId : null,
            ':document_type' => $requestDocType,
            ':template_code' => $requestTemplateCode
        ]);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('PDF save/update failed: ' . $e->getMessage());
    }

    $redirectUrl = '?page=employment-contracts';
    if ($templateCode !== 'employment_contract') {
        $redirectUrl = '?page=generate-document';
    }
    if (!headers_sent()) {
        header('Location: ' . $redirectUrl);
        exit;
    }

    echo '<script type="text/javascript">window.location.href = ' . json_encode($redirectUrl) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirectUrl) . '"><a href="' . htmlspecialchars($redirectUrl) . '">Click here if not redirected</a></noscript>';
    exit;
}

ob_start();
?>
<section class="gd-module">
   <nav aria-label="breadcrumb" class="gd-breadcrumb">
     <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="?page=generate-document">Generate Documents</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($docTypeLabel) ?></li>
      </ol>
    </nav>

   <div class="gd-header">
     <div class="gd-header-left">
       <div class="gd-title"><?= htmlspecialchars($docTypeLabel) ?></div>
       <div class="gd-subtitle">
         <?php if ($employeeFullName): ?>
           <span class="gd-chip"><?= htmlspecialchars($employeeFullName) ?></span>
         <?php endif; ?>
         <span class="gd-status <?= $validationPassed ? 'gd-status--ready' : 'gd-status--blocked' ?>">
           <?= $validationPassed ? 'Ready to Generate' : 'Validation Issues' ?>
         </span>
       </div>
     </div>
     <div class="gd-header-actions">
       <?php if ($validationPassed): ?>
         <a href="?page=generate-document&employee_id=<?= urlencode($employeeId) ?>&document_type=<?= urlencode($documentType) ?>&template=<?= urlencode($templateFile) ?>&template_code=<?= urlencode($templateCode) ?>&hr_signatory=<?= urlencode($hrSignatory) ?>&contract_type=<?= urlencode($employeeData['employment_status'] ?? 'Regular') ?>&contract_start_date=<?= urlencode($_GET['contract_start_date'] ?? date('Y-m-d')) ?>&contract_end_date=<?= urlencode($_GET['contract_end_date'] ?? date('Y-m-d', strtotime('+1 year'))) ?>&contract_salary_input=<?= urlencode($_GET['contract_salary_input'] ?? '') ?>&generate=1" class="gd-btn gd-btn-primary">
           <i class="bi bi-file-earmark-pdf"></i> Generate PDF
         </a>
       <?php endif; ?>
      <a href="?page=generate-document" class="gd-btn gd-btn-ghost">Cancel</a>
    </div>
  </div>

  <div class="gd-progress">
    <div class="gd-progress-step <?= $employeeData ? 'gd-progress-step--done' : '' ?>">
      <div class="gd-progress-dot"></div>
      <div class="gd-progress-label">Employee</div>
    </div>
    <div class="gd-progress-line"></div>
    <div class="gd-progress-step <?= $templateRecord ? 'gd-progress-step--done' : '' ?>">
      <div class="gd-progress-dot"></div>
      <div class="gd-progress-label">Template</div>
    </div>
    <div class="gd-progress-line"></div>
    <div class="gd-progress-step <?= $validationPassed ? 'gd-progress-step--done' : '' ?>">
      <div class="gd-progress-dot"></div>
      <div class="gd-progress-label">Validation</div>
    </div>
    <div class="gd-progress-line"></div>
    <div class="gd-progress-step">
      <div class="gd-progress-dot"></div>
      <div class="gd-progress-label">Preview</div>
    </div>
    <div class="gd-progress-line"></div>
    <div class="gd-progress-step">
      <div class="gd-progress-dot"></div>
      <div class="gd-progress-label">Generate</div>
    </div>
  </div>

  <div class="gd-layout">
    <div class="gd-main">
      <div class="gd-section">
        <div class="gd-section-title">Employee Information</div>
        <div class="gd-info-card">
          <div class="gd-info-row">
            <div class="gd-info-item">
              <div class="gd-info-label">Full Name</div>
              <div class="gd-info-value"><?= $employeeFullName ? htmlspecialchars($employeeFullName) : '�' ?></div>
            </div>
            <div class="gd-info-item">
              <div class="gd-info-label">Position</div>
              <div class="gd-info-value"><?= $employeePosition ? htmlspecialchars($employeePosition) : '�' ?></div>
            </div>
          </div>
          <div class="gd-info-row">
            <div class="gd-info-item">
              <div class="gd-info-label">Department</div>
              <div class="gd-info-value"><?= $employeeDepartment ? htmlspecialchars($employeeDepartment) : '�' ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="gd-section">
        <div class="gd-section-title">Document Information</div>
        <div class="gd-info-card">
          <div class="gd-info-row">
            <div class="gd-info-item">
              <div class="gd-info-label">Document</div>
              <div class="gd-info-value"><?= htmlspecialchars($docTypeLabel) ?></div>
            </div>
            <div class="gd-info-item">
              <div class="gd-info-label">Template</div>
              <div class="gd-info-value"><?= htmlspecialchars($templateLabel) ?></div>
            </div>
          </div>
          <div class="gd-info-row">
            <div class="gd-info-item">
              <div class="gd-info-label">Output Format</div>
              <div class="gd-info-value">PDF</div>
            </div>
            <div class="gd-info-item">
              <div class="gd-info-label">Version</div>
              <div class="gd-info-value">v1.0</div>
            </div>
          </div>
        </div>
      </div>

      <?php if ($templateCode === 'employment_contract' || $templateCode === 'contract_renewal' || $templateCode === 'contract_extension' || $templateCode === 'salary_rectification'): ?>
      <div class="gd-section">
        <div class="gd-section-title">Contract Details</div>
        <div class="gd-info-card">
          <div class="gd-info-row">
            <div class="gd-info-item">
              <div class="gd-info-label">Contract Type</div>
              <div class="gd-info-value"><?= htmlspecialchars($employeeData['employment_status'] ?? 'Regular') ?></div>
            </div>
            <div class="gd-info-item">
              <div class="gd-info-label">Start Date</div>
              <div class="gd-info-value"><?= !empty($_GET['contract_start_date']) ? date('F j, Y', strtotime($_GET['contract_start_date'])) : '�' ?></div>
            </div>
          </div>
          <div class="gd-info-row">
            <div class="gd-info-item">
              <div class="gd-info-label">End Date</div>
              <div class="gd-info-value"><?= !empty($_GET['contract_end_date']) ? date('F j, Y', strtotime($_GET['contract_end_date'])) : '�' ?></div>
            </div>
            <div class="gd-info-item">
              <div class="gd-info-label">Monthly Salary</div>
              <div class="gd-info-value"><?= !empty($_GET['contract_salary_input']) ? '?' . number_format((float)$_GET['contract_salary_input'], 2) : '�' ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="gd-section">
        <div class="gd-section-title">Validation Checklist</div>
        <div class="gd-validation-list">
          <?php foreach ($validationItems as $item): ?>
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

      <div class="gd-section">
        <div class="gd-section-title">Document Preview</div>
        <div class="gd-preview-frame">
          <?php
          $controller = new DocumentPreviewController($db, $user ?? []);
          echo $controller->renderPreviewContent($newTemplateDir, $templateCode, $employeeId, $documentType);
          ?>
        </div>
      </div>
    </div>

    <div class="gd-side">
      <div class="gd-side-card">
        <div class="gd-side-title">Summary</div>
        <div class="gd-side-row">
          <div class="gd-side-label">Document</div>
          <div class="gd-side-value"><?= htmlspecialchars($docTypeLabel) ?></div>
        </div>
        <div class="gd-side-row">
          <div class="gd-side-label">Employee</div>
          <div class="gd-side-value"><?= $employeeFullName ? htmlspecialchars($employeeFullName) : '�' ?></div>
        </div>
        <div class="gd-side-row">
          <div class="gd-side-label">Generated By</div>
          <div class="gd-side-value"><?= htmlspecialchars($user['name'] ?? $user['full_name'] ?? 'System') ?></div>
        </div>
        <div class="gd-side-row">
          <div class="gd-side-label">Date</div>
          <div class="gd-side-value"><?= date('F j, Y') ?></div>
        </div>
        <div class="gd-side-row">
          <div class="gd-side-label">File</div>
          <div class="gd-side-value"><?= $generatedAt ? htmlspecialchars($generatedFileName) : 'Not yet generated' ?></div>
        </div>
      </div>

      <div class="gd-side-card">
        <div class="gd-side-title">Document Timeline</div>
        <div class="gd-timeline">
          <div class="gd-timeline-item">
            <div class="gd-timeline-dot gd-timeline-dot--done"></div>
            <div class="gd-timeline-content">
              <div class="gd-timeline-title">Created</div>
              <div class="gd-timeline-meta"><?= date('g:i A') ?></div>
            </div>
          </div>
          <div class="gd-timeline-item">
            <div class="gd-timeline-dot <?= $generatedAt ? 'gd-timeline-dot--done' : '' ?>"></div>
            <div class="gd-timeline-content">
              <div class="gd-timeline-title">Generated</div>
              <div class="gd-timeline-meta"><?= $generatedAt ?: 'Pending' ?></div>
            </div>
          </div>
          <div class="gd-timeline-item">
            <div class="gd-timeline-dot"></div>
            <div class="gd-timeline-content">
              <div class="gd-timeline-title">Signed</div>
              <div class="gd-timeline-meta">Pending</div>
            </div>
          </div>
          <div class="gd-timeline-item">
            <div class="gd-timeline-dot"></div>
            <div class="gd-timeline-content">
              <div class="gd-timeline-title">Archived</div>
              <div class="gd-timeline-meta">Pending</div>
            </div>
          </div>
        </div>
      </div>

      <div class="gd-side-card">
        <div class="gd-side-title">Actions</div>
        <div class="gd-actions-list">
          <?php if ($validationPassed): ?>
            <a href="?page=generate-document&employee_id=<?= urlencode($employeeId) ?>&document_type=<?= urlencode($documentType) ?>&template=<?= urlencode($templateFile) ?>&template_code=<?= urlencode($templateCode) ?>&hr_signatory=<?= urlencode($hrSignatory) ?>&contract_type=<?= urlencode($employeeData['employment_status'] ?? 'Regular') ?>&contract_start_date=<?= urlencode($_GET['contract_start_date'] ?? date('Y-m-d')) ?>&contract_end_date=<?= urlencode($_GET['contract_end_date'] ?? date('Y-m-d', strtotime('+1 year'))) ?>&contract_salary_input=<?= urlencode($_GET['contract_salary_input'] ?? '') ?>&generate=1" class="gd-action-btn gd-action-btn--primary">
            <i class="bi bi-file-earmark-pdf"></i> Generate PDF
          </a>
          <?php endif; ?>
          <a href="?page=preview-document&employee_id=<?= urlencode($employeeId) ?>&document_type=<?= urlencode($documentType) ?>&template=<?= urlencode($templateFile) ?>&template_code=<?= urlencode($templateCode) ?>&hr_signatory=<?= urlencode($hrSignatory) ?>" class="gd-action-btn gd-action-btn--secondary" target="_blank">
            <i class="bi bi-eye"></i> Preview
          </a>
          <?php if (!empty($employeeEmail)): ?>
             <a href="?page=generate-document" class="gd-action-btn gd-action-btn--secondary">
              <i class="bi bi-envelope"></i> Email Employee
            </a>
          <?php endif; ?>
          <button class="gd-action-btn gd-action-btn--secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
          </button>
           <a href="?page=generate-document" class="gd-action-btn gd-action-btn--ghost">
            <i class="bi bi-arrow-left"></i> Back
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.gd-module { padding: 4px 2px 24px; max-width: 1200px; margin: 0 auto; }
.gd-breadcrumb { margin-bottom: 16px; }
.gd-breadcrumb .breadcrumb { background: transparent; padding: 0; margin: 0; font-size: 0.8rem; }
.gd-breadcrumb .breadcrumb-item a { color: var(--info-blue, #3b82c4); text-decoration: none; }
.gd-breadcrumb .breadcrumb-item a:hover { text-decoration: underline; }
.gd-breadcrumb .breadcrumb-item.active { color: var(--text-500, #6b7280); }

.gd-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border, #e5e7eb); }
.gd-title { font-size: 1.5rem; font-weight: 700; color: var(--text-900, #1b2430); line-height: 1.2; }
.gd-subtitle { display: flex; align-items: center; gap: 10px; margin-top: 6px; flex-wrap: wrap; }
.gd-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; background: var(--paper, #eef1f5); border: 1px solid var(--border, #e5e7eb); font-size: 0.78rem; font-weight: 600; color: var(--text-700, #3b4252); }
.gd-status { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; }
.gd-status--ready { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
.gd-status--blocked { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
.gd-header-actions { display: flex; gap: 8px; flex-shrink: 0; }

.gd-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 0.82rem; font-weight: 600; text-decoration: none; cursor: pointer; border: none; transition: all 0.15s ease; }
.gd-btn-primary { background: #2563eb; color: #fff; }
.gd-btn-primary:hover { background: #1d4ed8; color: #fff; }
.gd-btn-ghost { background: transparent; border: 1px solid var(--border, #e5e7eb); color: var(--text-700, #3b4252); }
.gd-btn-ghost:hover { background: var(--paper, #eef1f5); border-color: var(--text-400, #8b93a1); }

.gd-progress { display: flex; align-items: center; justify-content: center; gap: 0; margin-bottom: 24px; padding: 16px; background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 12px; }
.gd-progress-step { display: flex; flex-direction: column; align-items: center; gap: 6px; min-width: 80px; }
.gd-progress-dot { width: 12px; height: 12px; border-radius: 50%; background: var(--border, #e5e7eb); border: 2px solid var(--text-400, #8b93a1); transition: all 0.15s ease; }
.gd-progress-step--done .gd-progress-dot { background: #2563eb; border-color: #2563eb; }
.gd-progress-label { font-size: 0.72rem; font-weight: 600; color: var(--text-600, #5b6472); text-align: center; }
.gd-progress-step--done .gd-progress-label { color: #2563eb; }
.gd-progress-line { flex: 1; height: 2px; background: var(--border, #e5e7eb); margin: 0 8px; margin-bottom: 18px; }

.gd-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
.gd-main { min-width: 0; }
.gd-side { width: 340px; flex-shrink: 0; }

.gd-section { margin-bottom: 20px; }
.gd-section-title { font-size: 0.78rem; font-weight: 700; color: var(--text-500, #6b7280); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }

.gd-info-card { background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 14px 16px; }
.gd-info-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.gd-info-row + .gd-info-row { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border, #e5e7eb); }
.gd-info-item { display: flex; flex-direction: column; gap: 2px; }
.gd-info-label { font-size: 0.68rem; font-weight: 700; color: var(--text-400, #8b93a1); text-transform: uppercase; letter-spacing: 0.3px; }
.gd-info-value { font-size: 0.85rem; font-weight: 600; color: var(--text-900, #1b2430); }

.gd-validation-list { display: flex; flex-direction: column; gap: 8px; }
.gd-validation-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border, #e5e7eb); background: var(--card-bg, #fff); }
.gd-validation-item--pass { border-color: rgba(34, 197, 94, 0.3); background: rgba(34, 197, 94, 0.04); }
.gd-validation-item--fail { border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.04); }
.gd-validation-item--warn { border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.04); }
.gd-validation-icon { font-size: 1rem; flex-shrink: 0; }
.gd-validation-item--pass .gd-validation-icon { color: #16a34a; }
.gd-validation-item--fail .gd-validation-icon { color: #dc2626; }
.gd-validation-item--warn .gd-validation-icon { color: #d97706; }
.gd-validation-label { font-size: 0.82rem; font-weight: 700; color: var(--text-900, #1b2430); }
.gd-validation-message { font-size: 0.75rem; color: var(--text-500, #6b7280); margin-top: 1px; }

.gd-preview-frame { background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 24px; max-height: 600px; overflow-y: auto; }
.gd-preview-frame .dg-template-frame { margin: 0; background: transparent; border: none; padding: 0; box-shadow: none; }

.gd-side-card { background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; }
.gd-side-title { font-size: 0.78rem; font-weight: 700; color: var(--text-500, #6b7280); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
.gd-side-row { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; padding: 6px 0; }
.gd-side-row + .gd-side-row { border-top: 1px solid var(--border, #e5e7eb); }
.gd-side-label { font-size: 0.75rem; font-weight: 600; color: var(--text-400, #8b93a1); }
.gd-side-value { font-size: 0.82rem; font-weight: 600; color: var(--text-900, #1b2430); text-align: right; }

.gd-timeline { display: flex; flex-direction: column; gap: 12px; }
.gd-timeline-item { display: flex; gap: 10px; align-items: flex-start; }
.gd-timeline-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--border, #e5e7eb); margin-top: 5px; flex-shrink: 0; }
.gd-timeline-dot--done { background: #2563eb; }
.gd-timeline-title { font-size: 0.82rem; font-weight: 600; color: var(--text-900, #1b2430); }
.gd-timeline-meta { font-size: 0.72rem; color: var(--text-400, #8b93a1); margin-top: 1px; }

.gd-actions-list { display: flex; flex-direction: column; gap: 8px; }
.gd-action-btn { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 600; text-decoration: none; cursor: pointer; border: none; transition: all 0.15s ease; }
.gd-action-btn--primary { background: #2563eb; color: #fff; }
.gd-action-btn--primary:hover { background: #1d4ed8; color: #fff; }
.gd-action-btn--secondary { background: #fff; border: 1px solid var(--border, #e5e7eb); color: var(--text-700, #3b4252); }
.gd-action-btn--secondary:hover { border-color: var(--info-blue, #3b82c4); color: var(--info-blue, #3b82c4); }
.gd-action-btn--ghost { background: transparent; border: 1px solid transparent; color: var(--text-500, #6b7280); }
.gd-action-btn--ghost:hover { color: var(--text-900, #1b2430); }

@media (max-width: 1024px) {
  .gd-layout { grid-template-columns: 1fr; }
  .gd-side { width: 100%; }
  .gd-progress { flex-wrap: wrap; gap: 8px; }
  .gd-progress-line { display: none; }
}
</style>

<script>
(function() {
  var generateBtn = document.querySelector('.gd-btn-primary[href*="generate=1"]');
  if (generateBtn) {
    generateBtn.addEventListener('click', function(e) {
      if (!confirm('Generate PDF document? This will create a new document record.')) {
        e.preventDefault();
      }
    });
  }
})();
</script>
<?php


