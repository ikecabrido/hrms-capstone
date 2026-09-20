<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/DocumentData.php';
require_once __DIR__ . '/../lib/ajax/document_template_helper.php';

class DocumentPreviewController
{
    private PDO $db;
    private array $user;

    public function __construct(PDO $db, array $user = [])
    {
        $this->db = $db;
        $this->user = $user;
    }

    public function handleRequest(): void
    {
        $requestId     = isset($_GET['request_id']) ? trim((string) $_GET['request_id']) : '';
        $contractId    = isset($_GET['contract_id']) ? trim((string) $_GET['contract_id']) : '';
        $employeeId    = isset($_GET['employee_id']) ? trim((string) $_GET['employee_id']) : (isset($_GET['application_id']) ? trim((string) $_GET['application_id']) : '');
        $documentType  = isset($_GET['document_type']) ? trim((string) $_GET['document_type']) : '';
        $templateFile  = isset($_GET['template']) ? trim((string) $_GET['template']) : '';
        $templateCode  = isset($_GET['template_code']) ? trim((string) $_GET['template_code']) : '';
        $hrSignatory   = isset($_GET['hr_signatory']) ? (string) $_GET['hr_signatory'] : '';
        $mode          = isset($_GET['mode']) ? trim((string) $_GET['mode']) : 'preview';
        $savedVersionId = isset($_GET['version_id']) ? trim((string) $_GET['version_id']) : '';

        if ($templateCode !== 'employee_handbook' && $employeeId !== '' && !lc_can_access_employee_document(
            (int) ($this->user['employee_id'] ?? $_SESSION['employee_id'] ?? 0),
            (string) ($this->user['role_name'] ?? $_SESSION['role_name'] ?? $_SESSION['role'] ?? ''),
            (int) $employeeId
        )) {
            $this->renderError('You are not authorized to view this document.');
            return;
        }

        error_log("DEBUG preview: requestId=$requestId, contractId=$contractId, employeeId=$employeeId, documentType=$documentType, templateCode=$templateCode");

        if (
            ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply_salary_rectification')
            || ($_GET['action'] ?? '') === 'apply_salary_rectification'
        ) {
            $this->handleSalaryRectificationApply();
            return;
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && ($_POST['action'] ?? '') === 'apply_employment_contract'
        ) {
            $this->handleEmploymentContractApply();
            return;
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && ($_POST['action'] ?? '') === 'apply_contract_renewal'
        ) {
            $this->handleContractRenewalApply();
            return;
        }

        if ($requestId !== '' && !$this->loadFromRequest($requestId, $employeeId, $documentType, $templateCode)) {
            $this->renderError('Document request #' . htmlspecialchars($requestId) . ' not found.');
            return;
        } elseif ($contractId !== '' && $requestId === '') {
            $this->loadFromContract($contractId, $employeeId, $documentType, $templateCode);
        }

        error_log("DEBUG preview after load: employeeId=$employeeId, documentType=$documentType, templateCode=$templateCode");

        if ($employeeId === '' || $documentType === '' || $templateCode === '') {
            if (!in_array($templateCode, ['employee_handbook', 'onboarding_package'], true)) {
                $this->renderError('Missing required parameters for preview.');
                return;
            }
            $documentType = $documentType ?: ($templateCode === 'onboarding_package' ? 'Onboarding Document Package' : 'Employee Handbook');
        }

        $templateFileMap = [
            'coe' => 'coe.php',
            'employment_contract' => 'employment_contract.php',
            'contract_renewal' => 'contract_renewal.php',
            'contract_extension' => 'contract_extension.php',
            'salary_rectification' => 'salary_rectification_agreement.php',
            'leave_agreement' => 'leave_agreement.php',
            'study_leave' => 'study_leave.php',
            'suspension_notice' => 'suspension_notice.php',
            'notice_of_decision' => 'notice_of_decision.php',
            'return_service' => 'return_to_work_agreement.php',
            'nda' => 'nda.php',
            'clearance_survey' => 'clearance_survey.php',
            'training_bond' => 'training_bond.php',
            'non_compete' => 'non_compete.php',
            'written_warning' => 'written_warning.php',
            'nte' => 'nte.php',
            'exit_clearance' => 'exit_clearance.php',
            'exit_acknowledgement' => 'exit_acknowledgement.php',
            'quitclaim' => 'quitclaim.php',
            'termination_decision' => 'termination_decision.php',
            'onboarding_package' => 'onboarding_package.php',
        ];

        if ($templateFile === '' && $templateCode !== '') {
            $templateFile = $templateFileMap[$templateCode] ?? ($templateCode . '.php');
        }

        $docTypeToCodeMap = [
            'Certificate of Employment (COE)' => 'coe',
            'Contract Extension' => 'contract_extension',
            'Contract Renewal' => 'contract_renewal',
            'Employment Contract (New Hire)' => 'employment_contract',
            'Exit Acknowledgement' => 'exit_acknowledgement',
            'Exit Clearance' => 'exit_clearance',
            'Leave Agreement' => 'leave_agreement',
            'Return-to-Work Agreement' => 'return_service',
            'Non-Disclosure Agreement (NDA)' => 'nda',
            'Training Bond' => 'training_bond',
            'Study Leave Agreement' => 'study_leave',
            'Non-Compete Agreement' => 'non_compete',
            'Notice to Explain (NTE)' => 'nte',
            'Written Warning' => 'written_warning',
            'Suspension Notice' => 'suspension_notice',
            'Notice of Decision' => 'notice_of_decision',
            'Termination Decision' => 'termination_decision',
            'Clearance Survey' => 'clearance_survey',
            'Quitclaim and Release' => 'quitclaim',
            'Salary Rectification Agreement' => 'salary_rectification',
            'Employee Handbook' => 'employee_handbook',
        ];

        if ($templateCode === '' && $documentType !== '') {
            $templateCode = $docTypeToCodeMap[$documentType] ?? $documentType;
        }

        $legacyTemplatePath = __DIR__ . '/../lib/templates/' . $templateFile;
        $templateCodeToDirMap = [
            'salary_rectification' => 'salary_rectification_agreement',
            'return_service' => 'return_to_work_agreement',
        ];
        $newTemplateDir = __DIR__ . '/../pages/templates/' . ($templateCodeToDirMap[$templateCode] ?? $templateCode);

        if ($templateCode === 'onboarding_package') {
            $this->renderOnboardingPackage($employeeId, $documentType, $hrSignatory, $mode);
            return;
        }

        if ($templateCode === 'salary_rectification') {
            if (empty($_GET['contract_start_date']) || empty($_GET['contract_end_date'])) {
                try {
                    $stmt = $this->db->prepare("
                        SELECT start_date, end_date
                        FROM lc_contracts
                        WHERE employee_id = :eid AND status = 'Active'
                        ORDER BY contract_id DESC LIMIT 1
                    ");
                    $stmt->execute([':eid' => $employeeId]);
                    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($contract) {
                        if (empty($_GET['contract_start_date']) && !empty($contract['start_date'])) {
                            $_GET['contract_start_date'] = date('Y-m-d', strtotime($contract['start_date']));
                        }
                        if (empty($_GET['contract_end_date']) && !empty($contract['end_date'])) {
                            $_GET['contract_end_date'] = date('Y-m-d', strtotime($contract['end_date']));
                        }
                    }

                    if (empty($_GET['contract_start_date']) || empty($_GET['contract_end_date'])) {
                        $dateColumn = 'hire_date';
                        $stmt = $this->db->prepare("SELECT {$dateColumn} FROM em_employees WHERE employee_id = :eid LIMIT 1");
                        $stmt->execute([':eid' => $employeeId]);
                        $empDate = $stmt->fetchColumn();

                        if (empty($_GET['contract_start_date']) && !empty($empDate)) {
                            $_GET['contract_start_date'] = date('Y-m-d', strtotime($empDate));
                        }
                        if (empty($_GET['contract_end_date'])) {
                            $baseDate = !empty($_GET['contract_start_date']) ? $_GET['contract_start_date'] : ($empDate ?: date('Y-m-d'));
                            $_GET['contract_end_date'] = date('Y-m-d', strtotime('+1 year', strtotime($baseDate)));
                        }
                    }
                } catch (Throwable $e) {
                    if (empty($_GET['contract_start_date'])) {
                        $_GET['contract_start_date'] = date('Y-m-d');
                    }
                    if (empty($_GET['contract_end_date'])) {
                        $_GET['contract_end_date'] = date('Y-m-d', strtotime('+1 year'));
                    }
                }
            }
        }

        $useNewTemplate = is_dir($newTemplateDir) && file_exists($newTemplateDir . '/preview.php');

        if ($useNewTemplate) {
            $this->renderNewTemplate($newTemplateDir, $templateCode, $employeeId, $documentType, $hrSignatory, $mode, $savedVersionId, $templateFile);
        } else {
            if (!is_file($legacyTemplatePath)) {
                $this->renderError('Template file not found. ' . htmlspecialchars($legacyTemplatePath));
                return;
            }
            $this->renderLegacyTemplate($legacyTemplatePath, $templateCode, $employeeId, $documentType, $templateFile, $hrSignatory, $mode, $savedVersionId, $contractId);
        }
    }

    private function loadFromRequest(string $requestId, string &$employeeId, string &$documentType, string &$templateCode): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT request_id, employee_id, document_type, template_code, request_status, priority, notes, requires_signature, signature_status, created_at
                FROM lc_document_requests
                WHERE request_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $requestId]);
            $requestData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$requestData) {
                return false;
            }

            $employeeId   = (string) ($requestData['employee_id'] ?? $employeeId);
            $documentType = (string) ($requestData['document_type'] ?? $documentType);
            $templateCode = (string) ($requestData['template_code'] ?? $templateCode);

            if ($templateCode === '' || $templateCode === 'null') {
                $docTypeToCodeMap = [
                    'Certificate of Employment (COE)' => 'coe',
                    'Contract Extension' => 'contract_extension',
                    'Contract Renewal' => 'contract_renewal',
                    'Employment Contract (New Hire)' => 'employment_contract',
                    'Exit Acknowledgement' => 'exit_acknowledgement',
                    'Exit Clearance' => 'exit_clearance',
                    'Leave Agreement' => 'leave_agreement',
                    'Return-to-Work Agreement' => 'return_service',
                    'Non-Disclosure Agreement (NDA)' => 'nda',
                    'Training Bond' => 'training_bond',
                    'Study Leave Agreement' => 'study_leave',
                    'Non-Compete Agreement' => 'non_compete',
                    'Notice to Explain (NTE)' => 'nte',
                    'Written Warning' => 'written_warning',
                    'Suspension Notice' => 'suspension_notice',
                    'Notice of Decision' => 'notice_of_decision',
                    'Termination Decision' => 'termination_decision',
                    'Clearance Survey' => 'clearance_survey',
                    'Quitclaim and Release' => 'quitclaim',
                    'Salary Rectification Agreement' => 'salary_rectification',
                ];
                $templateCode = $docTypeToCodeMap[$documentType] ?? $documentType;
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function loadFromContract(string $contractId, string &$employeeId, string &$documentType, string &$templateCode): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.contract_id, c.employee_id, c.contract_type, c.start_date, c.end_date, c.status, c.monthly_salary,
                       CONCAT(e.first_name, ' ', e.last_name) AS full_name, e.employment_status, e.hire_date
                FROM lc_contracts c
                LEFT JOIN em_employees e ON e.employee_id = c.employee_id
                WHERE c.contract_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $contractId]);
            $contract = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$contract || empty($contract['employee_id']) || empty($contract['full_name'])) {
                $this->renderError('Contract not found or has no linked employee record.');
                exit;
            }

            $employeeId = (string) ($contract['employee_id'] ?? $employeeId);

            $contractType = strtolower((string) ($contract['contract_type'] ?? ''));
            $contractType = str_replace(' ', '_', $contractType);
            if ($contractType === 'renewal' || $contractType === 'extension') {
                $templateCode = $contractType === 'renewal' ? 'contract_renewal' : 'contract_extension';
            } elseif (in_array($contractType, ['regular', 'probationary', 'fixed-term', 'project', 'seasonal', 'casual', 'part-time', 'employment_contract'], true)) {
                $templateCode = $templateCode ?: 'employment_contract';
            } else {
                $templateCode = $templateCode ?: $contractType;
            }

            $docTypeToLabelMap = [
                'contract_renewal' => 'Contract Renewal',
                'contract_extension' => 'Contract Extension',
                'employment_contract' => 'Employment Contract (New Hire)',
                'salary_rectification' => 'Salary Rectification Agreement',
            ];
            $documentType = $docTypeToLabelMap[$templateCode] ?? ucfirst(str_replace('_', ' ', $templateCode));

            if (!empty($contract['start_date']) && empty($_GET['contract_start_date'])) {
                $_GET['contract_start_date'] = date('Y-m-d', strtotime($contract['start_date']));
            }
            if (!empty($contract['end_date']) && empty($_GET['contract_end_date'])) {
                $_GET['contract_end_date'] = date('Y-m-d', strtotime($contract['end_date']));
            }
            if (!empty($contract['monthly_salary']) && empty($_GET['contract_salary_input'])) {
                $_GET['contract_salary_input'] = (string) $contract['monthly_salary'];
            }
            if (!empty($contract['employment_status']) && empty($_GET['contract_type'])) {
                $_GET['contract_type'] = (string) $contract['employment_status'];
            }
        } catch (Throwable $e) {
            $this->renderError('Error loading contract: ' . htmlspecialchars($e->getMessage()));
            exit;
        }
    }

    public function renderPreviewContent(string $templateDir, string $templateCode, string $employeeId, string $documentType): string
    {
        $data = DocumentData::load($this->db, $templateCode, $employeeId, $documentType);
        $db = $this->db;

        $previewPath = $templateDir . '/preview.php';

        if (!file_exists($previewPath)) {
            return '';
        }

        $documentCss = 'css/document-preview.css?v=' . (file_exists(__DIR__ . '/../../css/document-preview.css') ? filemtime(__DIR__ . '/../../css/document-preview.css') : time());
        $documentCssLink = '<link rel="stylesheet" href="' . htmlspecialchars($documentCss) . '">';

        ob_start();
        echo $documentCssLink;
        include $previewPath;
        return ob_get_clean();
    }

    private function renderNewTemplate(string $templateDir, string $templateCode, string $employeeId, string $documentType, string $hrSignatory, string $mode, string $savedVersionId, string $templateFile = ''): void
    {
        $data = DocumentData::load($this->db, $templateCode, $employeeId, $documentType);

        $editorPath = $templateDir . '/editor.php';
        $previewPath = $templateDir . '/preview.php';

        if (!file_exists($previewPath)) {
            $this->renderError('Preview template not found.');
            return;
        }

        $rectificationError = isset($_GET['rectification_error']) && $_GET['rectification_error'] !== '' ? (string) $_GET['rectification_error'] : '';

        ob_start();
        if ($rectificationError !== '') {
            echo '<div class="dg-template-frame"><div class="dg-empty" style="color:#a3272a;">' . htmlspecialchars($rectificationError) . '</div></div>';
        }
        if ($mode === 'edit' && file_exists($editorPath)) {
            include $editorPath;
        }
        $editorHtml = ob_get_clean();

        $previewHtml = $this->renderPreviewContent($templateDir, $templateCode, $employeeId, $documentType);

        $this->renderTemplateShell($editorHtml, $previewHtml, $data, $templateCode, $employeeId, $documentType, $hrSignatory, $mode, $savedVersionId, $templateFile);
    }

    private function renderLegacyTemplate(string $templatePath, string $templateCode, string $employeeId, string $documentType, string $templateFile, string $hrSignatory, string $mode, string $savedVersionId, string $contractId = ''): void
    {
        $_GET['employee_id']   = $employeeId;
        $_GET['document_type'] = $documentType;
        $_GET['hr_signatory']  = $hrSignatory;
        if ($templateCode !== '') {
            $_GET['template_code'] = $templateCode;
        }

        if ($contractId !== '') {
            try {
                $stmt = $this->db->prepare("
                    SELECT contract_type, start_date, end_date, monthly_salary
                    FROM lc_contracts
                    WHERE contract_id = :id
                    LIMIT 1
                ");
                $stmt->execute([':id' => $contractId]);
                $contract = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($contract) {
                    $_GET['contract_type'] = (string) ($contract['contract_type'] ?? '');
                    $_GET['contract_start_date'] = (string) ($contract['start_date'] ?? '');
                    $_GET['contract_end_date'] = (string) ($contract['end_date'] ?? '');
                    $_GET['contract_salary_input'] = (string) ($contract['monthly_salary'] ?? '');
                }
            } catch (Throwable $e) {
                // ignore and fall back to existing GET values if present
            }
        }

        $db = $this->db;

        $documentCss = 'css/document-preview.css?v=' . (file_exists(__DIR__ . '/../../css/document-preview.css') ? filemtime(__DIR__ . '/../../css/document-preview.css') : time());
        $documentCssLink = '<link rel="stylesheet" href="' . htmlspecialchars($documentCss) . '">';

        ob_start();
        include $templatePath;
        $rendered = ob_get_clean();

        $rendered = $documentCssLink . $rendered;

        $rendered = preg_replace('#<div class="dg-template-frame">(.*?)</div>#si', '$1', $rendered, 1);

        $data = DocumentData::load($this->db, $templateCode, $employeeId, $documentType);

        $this->renderTemplateShell('', $rendered, $data, $templateCode, $employeeId, $documentType, $hrSignatory, $mode, $savedVersionId, $templateFile);
    }

    private function renderOnboardingPackage(string $employeeId, string $documentType, string $hrSignatory, string $mode): void
    {
        $employee = op_get_employee_for_package($this->db, $employeeId);
        if (!$employee) {
            $this->renderError('Employee not found for onboarding package.');
            return;
        }

        $previewUrl = '?page=preview-document&mode=onboarding_package&application_id=' . urlencode($employeeId) . '&template_code=onboarding_package';
        $generateUrl = '?page=generate-onboarding-package&application_id=' . urlencode($employeeId) . '&generate=1';

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $absGenerateUrl = $protocol . $host . '/hrms-capstone/modules/compliance/pages/' . $generateUrl;

        $attachmentName = 'Onboarding_Package_' . preg_replace('/[^A-Za-z0-9]/', '', ($employee['full_name'] ?? 'Employee')) . '.html';

        $sendToEmailUrl = '?page=notification-compose&mode=reply&notification_key=warning';
        $sendToEmailUrl .= '&to_recipient_email=' . urlencode($employee['email'] ?? '');
        $sendToEmailUrl .= '&to_recipient_name=' . urlencode($employee['full_name'] ?? 'Employee');
        $sendToEmailUrl .= '&template_code=onboarding_package';
        $sendToEmailUrl .= '&scenario=general';
        $sendToEmailUrl .= '&attachment_url=' . urlencode($absGenerateUrl);
        $sendToEmailUrl .= '&attachment_name=' . urlencode($attachmentName);
        $sendToEmailUrl .= '&document_name=Onboarding Document Package';

        $sendToEmailButtonHtml = '<a href="' . htmlspecialchars($sendToEmailUrl) . '" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>';

        $documentCss = 'css/document-preview.css?v=' . (file_exists(__DIR__ . '/../../css/document-preview.css') ? filemtime(__DIR__ . '/../../css/document-preview.css') : time());
        $documentCssLink = '<link rel="stylesheet" href="' . htmlspecialchars($documentCss) . '">';

        $packageHtml = op_generate_package_html($this->db, $employee);

        $toolbarHtml = '<div class="cd-toolbar">';

        $toolbarHtml .= '<a href="?page=onboarding-package" class="cd-btn-cancel"><i class="bi bi-arrow-left"></i> Back</a>';
        $toolbarHtml .= '<a href="' . htmlspecialchars($generateUrl) . '" class="cd-btn-save" target="_blank"><i class="bi bi-file-earmark-code"></i> Generate HTML</a>';
        $toolbarHtml .= '</div>';

        $combinedCardHtml = '<div class="cd-main-layout">' .
            '<div class="card-x">' .
            '<div class="cx-head"><h2>Onboarding Document Package</h2></div>' .
            '<div class="cx-body">' .
            '<p class="cd-section-title">Employee Information <span style="font-size:.72rem; color:var(--text-400,#8b93a1); font-weight:normal;">— Read-only from employee record</span></p>' .
            '<div class="cd-field-row">' .
            '<div class="cd-field"><label>Full Name</label><div class="cd-readonly-value">' . htmlspecialchars($employee['full_name'] ?? '') . '</div></div>' .
            '<div class="cd-field"><label>Position</label><div class="cd-readonly-value">' . htmlspecialchars($employee['position_name'] ?? '') . '</div></div>' .
            '</div>' .
            '<div class="cd-field-row">' .
            '<div class="cd-field"><label>Department</label><div class="cd-readonly-value">' . htmlspecialchars($employee['department_name'] ?? '') . '</div></div>' .
            '<div class="cd-field"><label>Employment Status</label><div class="cd-readonly-value">' . htmlspecialchars($employee['employment_status'] ?? '') . '</div></div>' .
            '</div>' .
            '<div class="cd-field-row">' .
            '<div class="cd-field"><label>Employee No.</label><div class="cd-readonly-value">' . htmlspecialchars($employee['employee_code'] ?? $employee['employee_no'] ?? '') . '</div></div>' .
            '<div class="cd-field"><label>Email</label><div class="cd-readonly-value">' . htmlspecialchars($employee['email'] ?? '') . '</div></div>' .
            '</div>' .
            '<div class="cd-field-row">' .
            '<div class="cd-field"><label>Date Hired</label><div class="cd-readonly-value">' . htmlspecialchars($employee['date_hired'] ?? $employee['hire_date'] ?? '') . '</div></div>' .
            '<div class="cd-field"><label>Documents</label><div class="cd-readonly-value">Employment Contract, Employee Handbook, NDA</div></div>' .
            '</div>' .
            '<div class="cd-field cd-field--split-actions-2">' . $sendToEmailButtonHtml . '</div>' .
            '</div>' .
            '</div>' .
            '<div class="card-x preview-card">' .
            '<div class="cx-head"><h2>Document Preview</h2></div>' .
            '<div class="cx-body">' . $documentCssLink . '<div class="document-preview">' . $packageHtml . '</div></div>' .
            '</div>' .
            '</div>';

        $pageContent = $toolbarHtml . $combinedCardHtml;

        echo $pageContent;
    }

    private function renderTemplateShell(string $editorHtml, string $previewHtml, array $data, string $templateCode, string $employeeId, string $documentType, string $hrSignatory, string $mode, string $savedVersionId, string $templateFile = ''): void
    {
        $generateUrl = 'generate-document.php?employee_id=' . urlencode($employeeId) . '&document_type=' . urlencode($documentType) . '&hr_signatory=' . urlencode($hrSignatory);
        if ($templateCode !== '') {
            $generateUrl .= '&template_code=' . urlencode($templateCode);
        }
        $templateFileFromCode = $templateCode !== '' ? ($templateCode . '.php') : '';
        if ($templateFileFromCode !== '' && file_exists(__DIR__ . '/../lib/templates/' . $templateFileFromCode)) {
            $generateUrl .= '&template=' . urlencode($templateFileFromCode);
        } elseif ($templateFile !== '') {
            $generateUrl .= '&template=' . urlencode($templateFile);
        }

        if ($templateCode === 'employment_contract' || $templateCode === 'contract_renewal' || $templateCode === 'contract_extension' || $templateCode === 'salary_rectification') {
            $generateUrl .= '&contract_type=' . urlencode((string) ($_GET['contract_type'] ?? $data['raw_employment_status'] ?? 'Regular'));
            $generateUrl .= '&contract_start_date=' . urlencode((string) ($_GET['contract_start_date'] ?? ''));
            $generateUrl .= '&contract_end_date=' . urlencode((string) ($_GET['contract_end_date'] ?? ''));
            $generateUrl .= '&contract_salary_input=' . urlencode((string) ($_GET['contract_salary_input'] ?? ''));
        }

        $sendToEmailUrl = '?page=notification-compose&mode=reply&notification_key=warning';
        $sendToEmailUrl .= '&to_recipient_email=' . urlencode($data['employee_email'] ?? '');
        $sendToEmailUrl .= '&to_recipient_name=' . urlencode($data['employee_full_name'] ?? '');
        $sendToEmailUrl .= '&template_code=' . urlencode($templateCode);
        $sendToEmailUrl .= '&scenario=general';

        $documentName = ucfirst(str_replace('_', ' ', $documentType));

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (!isset($_SESSION['lc_body_tokens']) || !is_array($_SESSION['lc_body_tokens'])) {
            $_SESSION['lc_body_tokens'] = [];
        }
        foreach ($_SESSION['lc_body_tokens'] as $k => $v) {
            if ((time() - (int)($v['ts'] ?? 0)) > 3600) {
                unset($_SESSION['lc_body_tokens'][$k]);
            }
        }
        $bodyToken = bin2hex(random_bytes(8));
        $_SESSION['lc_body_tokens'][$bodyToken] = ['body' => 'Please see the attached ' . $documentName . ' for your review.', 'ts' => time()];
        $sendToEmailUrl .= '&body_token=' . $bodyToken;
        $sendToEmailUrl .= '&document_name=' . urlencode($documentName);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $absGenerateUrl = $protocol . $host . '/hrms-capstone/modules/compliance/pages/' . $generateUrl;

        $attachmentNameMap = [
            'coe' => 'COE',
            'employment_contract' => 'Employment_Contract',
            'contract_renewal' => 'Contract_Renewal',
            'contract_extension' => 'Contract_Extension',
            'salary_rectification' => 'Salary_Rectification_Agreement',
            'leave_agreement' => 'Leave_Agreement',
            'study_leave' => 'Study_Leave_Agreement',
            'suspension_notice' => 'Suspension_Notice',
            'notice_of_decision' => 'Notice_of_Decision',
            'termination_decision' => 'Termination_Decision',
            'nda' => 'NDA',
            'final_pay' => 'Final_Pay',
            'clearance_survey' => 'Clearance_Survey',
            'training_bond' => 'Training_Bond',
            'non_compete' => 'Non_Compete_Agreement',
            'written_warning' => 'Written_Warning',
            'nte' => 'NTE',
            'return_service' => 'Return_to_Work_Agreement',
            'exit_clearance' => 'Exit_Clearance',
            'exit_acknowledgement' => 'Exit_Acknowledgement',
            'employee_handbook' => 'Employee_Handbook',
        ];
        $friendlyDocName = $attachmentNameMap[$templateCode] ?? ucfirst(str_replace('_', ' ', $documentType));
        $attachmentName = $friendlyDocName . '_' . preg_replace('/[^A-Za-z0-9]/', '', $data['employee_full_name'] ?? '') . '.html';

        $sendToEmailUrl .= '&attachment_url=' . urlencode($absGenerateUrl);
        $sendToEmailUrl .= '&attachment_name=' . urlencode($attachmentName);

        $sendToEmailButtonHtml = '<a href="' . htmlspecialchars($sendToEmailUrl) . '" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>';

        $extraCss = 'css/contract_details.css?v=' . (file_exists(__DIR__ . '/../../css/contract_details.css') ? filemtime(__DIR__ . '/../../css/contract_details.css') : time());
        $extraCssLink = '<link rel="stylesheet" href="' . htmlspecialchars($extraCss) . '">';

        $documentCss = 'css/document-preview.css?v=' . (file_exists(__DIR__ . '/../../css/document-preview.css') ? filemtime(__DIR__ . '/../../css/document-preview.css') : time());
        $documentCssLink = '<link rel="stylesheet" href="' . htmlspecialchars($documentCss) . '">';

        $toolbarHtml = '<div class="cd-toolbar">';

        $previewDrawerControls = '<button class="cd-preview-toggle" id="cdPreviewToggle" type="button" aria-label="Open document preview" aria-expanded="false" aria-controls="cdPreviewDrawer"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Preview</button>' .
            '<div class="cd-preview-overlay" id="cdPreviewOverlay" aria-hidden="true"></div>';

        if ($mode === 'edit') {
            $toolbarHtml .= '<button type="button" class="cd-btn-save" id="dgSaveDocument">Save Document</button>';
            $toolbarHtml .= '<button type="button" class="cd-btn-cancel" id="dgCancelEdit">Cancel</button>';
        }

        $toolbarHtml .= '</div>';

        $editScript = '';
        if ($mode === 'edit') {
            $editScript = <<<HTML
<script>
(function(){
  var EDITABLE_SELECTOR = '.dg-document-body';
  var saveBtn = document.getElementById('dgSaveDocument');
  var cancelBtn = document.getElementById('dgCancelEdit');
  var editableAreas = document.querySelectorAll(EDITABLE_SELECTOR);

  if (editableAreas.length > 0) {
    editableAreas.forEach(function(el){
      el.setAttribute('contenteditable', 'true');
      el.style.outline = '1px dashed #3b82c4';
      el.style.outlineOffset = '4px';
    });
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', function(){
      window.location.href = window.location.pathname + '?employee_id=' + encodeURIComponent('$employeeId') + '&document_type=' + encodeURIComponent('$documentType') + '&template_code=' + encodeURIComponent('$templateCode') + '&hr_signatory=' + encodeURIComponent('$hrSignatory') + '&mode=preview';
    });
  }

  if (saveBtn) {
    saveBtn.addEventListener('click', function(){
      var payload = {
        employee_id: '$employeeId',
        template_code: '$templateCode',
        document_title: document.querySelector('.dg-document-body h2') ? document.querySelector('.dg-document-body h2').textContent.trim() : '',
        document_html: '',
        status: 'draft'
      };

      var editableAreas = document.querySelectorAll(EDITABLE_SELECTOR);
      var htmlParts = [];
      editableAreas.forEach(function(el){
        htmlParts.push(el.innerHTML);
      });
      payload.document_html = htmlParts.join('<!--dg-document-body-separator-->');

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      var xhr = new XMLHttpRequest();
      xhr.open('POST', '../../api/save_document_content.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function(){
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Document';
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            var res = JSON.parse(xhr.responseText);
            if (res && res.success) {
              window.location.href = window.location.pathname + '?employee_id=' + encodeURIComponent('$employeeId') + '&document_type=' + encodeURIComponent('$documentType') + '&template_code=' + encodeURIComponent('$templateCode') + '&hr_signatory=' + encodeURIComponent('$hrSignatory') + '&mode=preview&version_id=' + encodeURIComponent(res.version_id);
            } else {
              alert(res && res.message ? res.message : 'Failed to save document.');
            }
          } catch (e) {
            alert('Invalid server response.');
          }
        } else {
          alert('Request failed with status ' + xhr.status);
        }
      };
      xhr.onerror = function(){
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Document';
        alert('Network error while saving.');
      };
      xhr.send('employee_id=' + encodeURIComponent('$employeeId') + '&template_code=' + encodeURIComponent('$templateCode') + '&document_title=' + encodeURIComponent(payload.document_title) + '&document_html=' + encodeURIComponent(payload.document_html) + '&status=' + encodeURIComponent('draft'));
    });
  }
})();
</script>
HTML;
        }

        $combinedCardHtml = $extraCssLink . $previewDrawerControls . '<div class="cd-main-layout">' .
            '<div class="card-x">' .
            '<div class="cx-head"><h2>Document Details</h2></div>' .
            '<div class="cx-body">' .
            '<p class="cd-section-title">Employee Information</p>' .
            '<div class="cd-field-row">' .
            '<div class="cd-field"><label>Full Name</label><div class="cd-readonly-value">' . htmlspecialchars($data['employee_full_name'] ?: '') . '</div></div>' .
            '<div class="cd-field"><label>Position</label><div class="cd-readonly-value">' . htmlspecialchars($data['employee_position'] ?: '') . '</div></div>' .
            '</div>' .
            '<div class="cd-field"><label>Department</label><div class="cd-readonly-value">' . htmlspecialchars($data['employee_department'] ?: '') . '</div></div>';

        if ($editorHtml !== '') {
            $combinedCardHtml .= $editorHtml;
        } elseif ($templateCode === 'salary_rectification') {
            $currentSalary = !empty($_GET['contract_salary_input']) ? $_GET['contract_salary_input'] : '';

            $positionMinWage = 18000.00;
            $employeePositionId = 0;
            try {
                $stmt = $this->db->prepare("SELECT position_id FROM em_employees WHERE employee_id = :id LIMIT 1");
                $stmt->execute([':id' => $data['employee_id']]);
                $empRow = $stmt->fetch(PDO::FETCH_ASSOC);
                $employeePositionId = (int) ($empRow['position_id'] ?? 0);

                if ($employeePositionId > 0) {
                    $stmt = $this->db->prepare("SELECT minimum_wage FROM lc_minimum_wage WHERE status = 'Active' AND is_global = 'No' AND position_id = :pid LIMIT 1");
                    $stmt->execute([':pid' => $employeePositionId]);
                    $mwRow = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($mwRow && !empty($mwRow['minimum_wage'])) {
                        $positionMinWage = max(18000.00, (float) $mwRow['minimum_wage']);
                    }
                }
            } catch (Throwable $e) {
                $positionMinWage = 18000.00;
            }

            $salaryOptions = [];
            $step = 5000;
            $start = (int) ceil($positionMinWage / $step) * $step;
            for ($i = 0; $i < 3; $i++) {
                $val = $start + ($i * $step);
                $label = '₱' . number_format($val, 0);
                $salaryOptions[$val] = $label;
            }

            $selectedSalary = $currentSalary !== '' ? (float) $currentSalary : $positionMinWage;
            $hasExactMatch = false;
            foreach ($salaryOptions as $val => $label) {
                if (abs($selectedSalary - $val) < 0.01) {
                    $hasExactMatch = true;
                    break;
                }
            }

            $combinedCardHtml .= '<form class="cd-date-form" method="get" action="" data-skip>' .
                '<input type="hidden" name="action" value="apply_salary_rectification">' .
                '<input type="hidden" name="employee_id" value="' . htmlspecialchars($data['employee_id']) . '">' .
                '<input type="hidden" name="document_type" value="' . htmlspecialchars($data['document_type']) . '">' .
                '<input type="hidden" name="template_code" value="' . htmlspecialchars($data['template_code']) . '">' .
                '<input type="hidden" name="hr_signatory" value="' . htmlspecialchars($_GET['hr_signatory'] ?? '') . '">' .
                '<div class="cd-field-row">' .
                    '<div class="cd-field">' .
                        '<label for="dgRectificationSalary">New Monthly Salary</label>' .
                        '<select id="dgRectificationSalary" name="contract_salary_input" onchange="toggleSalaryInput(this)">';

            foreach ($salaryOptions as $val => $label) {
                $selected = (abs($selectedSalary - $val) < 0.01) ? ' selected' : '';
                $combinedCardHtml .= '<option value="' . htmlspecialchars($val) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
            }

            if (!$hasExactMatch && $currentSalary !== '') {
                $combinedCardHtml .= '<option value="' . htmlspecialchars($currentSalary) . '" selected>Custom: ₱' . number_format((float) $currentSalary, 2) . '</option>';
            }

            $combinedCardHtml .= '<option value="__other__">Other</option>' .
                        '</select>' .
                        '<input type="text" id="dgSalaryOther" name="contract_salary_input" placeholder="Enter custom amount" style="display:none;" inputmode="decimal" disabled>' .
                    '</div>' .
                '</div>' .
                '<div class="cd-field cd-field--split-actions-2">' .
                    $sendToEmailButtonHtml .
                    '<button type="submit" class="dg-btn dg-btn-primary"><i class="bi bi-check-circle"></i> Apply</button>' .
                '</div>' .
            '</form>' .
            '<script>
                function toggleSalaryInput(select) {
                    var otherInput = document.getElementById("dgSalaryOther");
                    if (select.value === "__other__") {
                        select.style.display = "none";
                        select.disabled = true;
                        otherInput.style.display = "block";
                        otherInput.disabled = false;
                        otherInput.focus();
                    } else {
                        otherInput.style.display = "none";
                        otherInput.disabled = true;
                        otherInput.value = "";
                        select.style.display = "block";
                        select.disabled = false;
                    }
                }

                document.addEventListener("DOMContentLoaded", function() {
                    var select = document.getElementById("dgRectificationSalary");
                    var otherInput = document.getElementById("dgSalaryOther");
                    if (select && otherInput) {
                        if (select.value === "__other__") {
                            select.disabled = true;
                            otherInput.disabled = false;
                        } else {
                            otherInput.disabled = true;
                            select.disabled = false;
                        }
                    }
                });
            </script>';
        } else {
            $combinedCardHtml .= '<div class="cd-field cd-field--split-actions-2">' . $sendToEmailButtonHtml . '</div>';
        }

        $combinedCardHtml .= '</div>' .
            '</div>' .
            '<div class="card-x preview-card" id="cdPreviewDrawer" role="dialog" aria-modal="true" aria-labelledby="cdPreviewDrawerTitle">' .
            '<div class="cd-preview-drawer-head"><h2 id="cdPreviewDrawerTitle">Document Preview</h2><button class="cd-preview-close" id="cdPreviewClose" type="button" aria-label="Close document preview"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>' .
            '<div class="cx-body">' . $previewHtml . '<div class="lc-template-email-action" style="margin-top:20px; text-align:center;"></div></div>' .
            '</div>' .
            '</div>';

        $previewDrawerScript = <<<'HTML'
<script>
(function () {
    var drawer = document.getElementById('cdPreviewDrawer');
    var toggle = document.getElementById('cdPreviewToggle');
    var closeButton = document.getElementById('cdPreviewClose');
    var overlay = document.getElementById('cdPreviewOverlay');
    var mobilePreview = window.matchMedia('(max-width: 767px)');

    if (!drawer || !toggle || !overlay) return;

    function openPreview() {
        document.body.classList.add('cd-preview-drawer-open');
        toggle.setAttribute('aria-expanded', 'true');
        drawer.setAttribute('aria-hidden', 'false');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (closeButton) closeButton.focus();
    }

    function closePreview(restoreFocus) {
        document.body.classList.remove('cd-preview-drawer-open');
        toggle.setAttribute('aria-expanded', 'false');
        drawer.setAttribute('aria-hidden', 'true');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (restoreFocus) toggle.focus();
    }

    function syncPreviewAccessibility() {
        if (mobilePreview.matches) {
            drawer.setAttribute('aria-hidden', document.body.classList.contains('cd-preview-drawer-open') ? 'false' : 'true');
        } else {
            closePreview(false);
            drawer.removeAttribute('aria-hidden');
        }
    }

    toggle.addEventListener('click', openPreview);
    if (closeButton) closeButton.addEventListener('click', function () { closePreview(true); });
    overlay.addEventListener('click', function () { closePreview(true); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && document.body.classList.contains('cd-preview-drawer-open')) closePreview(true);
    });

    syncPreviewAccessibility();
    if (mobilePreview.addEventListener) mobilePreview.addEventListener('change', syncPreviewAccessibility);
    else mobilePreview.addListener(syncPreviewAccessibility);
})();
</script>
HTML;

        $pageContent = $toolbarHtml . $combinedCardHtml . $editScript . $previewDrawerScript;

        echo $pageContent;
    }

    private function handleEmploymentContractApply(): void
    {
        $source = $_POST;
        $employeeId    = isset($source['employee_id']) ? trim((string) $source['employee_id']) : '';
        $documentType  = isset($source['document_type']) ? trim((string) $source['document_type']) : 'Employment Contract (New Hire)';
        $templateCode  = isset($source['template_code']) ? trim((string) $source['template_code']) : 'employment_contract';
        $hrSignatory   = isset($source['hr_signatory']) ? (string) $source['hr_signatory'] : '';
        $contractType  = isset($source['contract_type']) ? trim((string) $source['contract_type']) : '';
        $startDate     = isset($source['contract_start_date']) ? trim((string) $source['contract_start_date']) : '';
        $endDate       = isset($source['contract_end_date']) ? trim((string) $source['contract_end_date']) : '';
        $salaryInput   = isset($source['contract_salary_input']) ? trim((string) $source['contract_salary_input']) : '';

        if ($employeeId === '') {
            $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, 'Missing employee ID.');
            return;
        }

        if ($contractType === '' || $startDate === '' || $endDate === '' || $salaryInput === '' || !is_numeric($salaryInput)) {
            $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, 'Missing or invalid contract details.');
            return;
        }

        $salary = (float) $salaryInput;
        if ($salary < 18000.00) {
            $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, 'Salary must be at least ₱18,000.00.');
            return;
        }

        try {
            $newHireData = null;
            $candidateId = (int) $employeeId;
            $stmt = $this->db->prepare("SELECT * FROM new_hire_table WHERE candidate_id = :id LIMIT 1");
            $stmt->execute([':id' => $candidateId]);
            $newHireData = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if (!$newHireData) {
                $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, 'New hire record not found.');
                return;
            }

            $this->db->beginTransaction();

            $employeeIdInt = (int) $employeeId;
            if (function_exists('dg_ensure_employee_in_employees')) {
                $employeeIdInt = dg_ensure_employee_in_employees($this->db, $employeeIdInt, $newHireData);
            } else {
                $stmt = $this->db->prepare("SELECT employee_id FROM em_employees WHERE employee_id = :id LIMIT 1");
                $stmt->execute([':id' => $employeeIdInt]);
                if (!$stmt->fetchColumn()) {
                    $nameParts = [];
                    if (function_exists('dg_split_full_name')) {
                        $nameParts = dg_split_full_name($newHireData['full_name'] ?? '');
                    } else {
                        $parts = preg_split('/\s+/', trim($newHireData['full_name'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
                        $nameParts = ['first_name' => $parts[0] ?? '', 'middle_name' => implode(' ', array_slice($parts, 1, -1)) ?? '', 'last_name' => $parts[count($parts) - 1] ?? ''];
                    }
                    $mappedStatus = 'Probationary';
                    if (function_exists('dg_map_employment_status')) {
                        $mappedStatus = dg_map_employment_status($newHireData['employment_status'] ?? 'Regular');
                    }
                    $this->db->prepare("
                        INSERT INTO em_employees (employee_id, first_name, middle_name, last_name, gender, birth_date, civil_status, email, mobile_no, current_address, permanent_address, department_id, position_id, hire_date, employment_status, credentials, citizenship, created_at, updated_at)
                        VALUES (:employee_id, :first_name, :middle_name, :last_name, :gender, :birth_date, :civil_status, :email, :mobile_no, :current_address, :permanent_address, :department_id, :position_id, :hire_date, :employment_status, :credentials, :citizenship, NOW(), NOW())
                    ")->execute([
                        ':employee_id' => $employeeIdInt,
                        ':first_name' => $nameParts['first_name'] ?? '',
                        ':middle_name' => $nameParts['middle_name'] ?? '',
                        ':last_name' => $nameParts['last_name'] ?? '',
                        ':gender' => $newHireData['sex'] ?? 'Male',
                        ':birth_date' => $newHireData['birthdate'] ?? null,
                        ':civil_status' => $newHireData['marital_status'] ?? 'Single',
                        ':email' => $newHireData['email'] ?: 'employee' . $employeeIdInt . '@hrms.local',
                        ':mobile_no' => $newHireData['phone_number'] ?? null,
                        ':current_address' => $newHireData['address'] ?? null,
                        ':permanent_address' => $newHireData['address'] ?? null,
                        ':department_id' => $newHireData['department_id'] ?? null,
                        ':position_id' => $newHireData['position_id'] ?? null,
                        ':hire_date' => $newHireData['date_hired'] ?? null,
                        ':employment_status' => $mappedStatus,
                        ':credentials' => $newHireData['teacher_qualification'] ?? null,
                        ':citizenship' => 'Filipino',
                    ]);
                }
            }

            $contractNumber = 'CTR-' . date('Y') . '-' . str_pad((string)((int) $this->db->query("SELECT COALESCE(MAX(contract_id), 0) + 1 FROM lc_contracts")->fetchColumn() - 1), 4, '0', STR_PAD_LEFT);
            if (function_exists('dg_generate_contract_number')) {
                $contractNumber = dg_generate_contract_number($this->db);
            }

            $governingLaw = 'Philippine Labor Code (PD 442)';
            $categoryId = null;

            $this->db->prepare("
                INSERT INTO lc_contracts (employee_id, contract_number, contract_type, governing_law, jurisdiction, category_id, requires_dual_sig, digital_signature_status, start_date, end_date, status, monthly_salary, working_hours_per_week, notes, created_by, created_by_role)
                VALUES (:employee_id, :contract_number, :contract_type, :governing_law, :jurisdiction, :category_id, 1, 'none', :start_date, :end_date, 'Draft', :monthly_salary, 40, :notes, :created_by, 'hr')
            ")->execute([
                ':employee_id' => $employeeIdInt,
                ':contract_number' => $contractNumber,
                ':contract_type' => $contractType,
                ':governing_law' => $governingLaw,
                ':jurisdiction' => 'Philippines',
                ':category_id' => $categoryId,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':monthly_salary' => number_format($salary, 2, '.', ''),
                ':notes' => null,
                ':created_by' => (int) ($this->user['user_id'] ?? $this->user['id'] ?? 0),
            ]);
            $contractId = (int) $this->db->lastInsertId();

            $this->db->prepare("
                UPDATE em_employees
                SET negotiated_salary = :salary,
                    updated_at = NOW()
                WHERE employee_id = :employee_id
            ")->execute([
                ':salary' => number_format($salary, 2, '.', ''),
                ':employee_id' => $employeeIdInt,
            ]);

            $this->db->prepare("
                DELETE FROM new_hire_table WHERE candidate_id = :candidate_id
            ")->execute([
                ':candidate_id' => $candidateId,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, 'Failed to apply changes: ' . $e->getMessage());
            return;
        }

        $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, '');
    }

    private function handleContractRenewalApply(): void
    {
        $source = $_POST;
        $employeeId    = isset($source['employee_id']) ? trim((string) $source['employee_id']) : '';
        $documentType  = isset($source['document_type']) ? trim((string) $source['document_type']) : 'Contract Renewal';
        $templateCode  = isset($source['template_code']) ? trim((string) $source['template_code']) : 'contract_renewal';
        $hrSignatory   = isset($source['hr_signatory']) ? (string) $source['hr_signatory'] : '';
        $startDate     = isset($source['contract_start_date']) ? trim((string) $source['contract_start_date']) : '';
        $endDate       = isset($source['contract_end_date']) ? trim((string) $source['contract_end_date']) : '';
        $salaryInput   = isset($source['contract_salary_input']) ? trim((string) $source['contract_salary_input']) : '';

        if ($employeeId === '' || $salaryInput === '' || !is_numeric($salaryInput)) {
            $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, 'Missing or invalid salary input.');
            return;
        }

        $newSalary = (float) $salaryInput;

        try {
            $this->db->beginTransaction();

            $this->db->prepare("
                UPDATE em_employees
                SET negotiated_salary = :salary,
                    updated_at = NOW()
                WHERE employee_id = :eid
            ")->execute([
                ':salary' => number_format($newSalary, 2, '.', ''),
                ':eid' => $employeeId,
            ]);

            $this->db->prepare("
                DELETE FROM lc_document_requests
                WHERE employee_id = :eid
                  AND document_type = 'Contract Renewal'
                  AND template_code = 'contract_renewal'
                ORDER BY created_at DESC
                LIMIT 1
            ")->execute([':eid' => $employeeId]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, 'Failed to apply changes: ' . $e->getMessage());
            return;
        }

        $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, '');
    }

    private function handleSalaryRectificationApply(): void
    {
        $source = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        $employeeId    = isset($source['employee_id']) ? trim((string) $source['employee_id']) : '';
        $documentType  = isset($source['document_type']) ? trim((string) $source['document_type']) : 'salary_rectification';
        $templateCode  = isset($source['template_code']) ? trim((string) $source['template_code']) : 'salary_rectification';
        $hrSignatory   = isset($source['hr_signatory']) ? (string) $source['hr_signatory'] : '';
        $startDate     = isset($source['contract_start_date']) ? trim((string) $source['contract_start_date']) : '';
        $endDate       = isset($source['contract_end_date']) ? trim((string) $source['contract_end_date']) : '';
        $salaryInput   = isset($source['contract_salary_input']) ? trim((string) $source['contract_salary_input']) : '';

        if ($employeeId === '' || $salaryInput === '' || !is_numeric($salaryInput)) {
            $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, 'Missing or invalid salary input.');
            return;
        }

        $newSalary = (float) $salaryInput;
        if ($newSalary < 18000.00) {
            $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, 'Salary must be at least ₱18,000.00.');
            return;
        }

        try {
            $contractId = null;
            if (!empty($_GET['contract_id']) && ctype_digit((string) $_GET['contract_id'])) {
                $contractId = (int) $_GET['contract_id'];
            }
            if (!$contractId) {
                $stmt = $this->db->prepare("
                    SELECT contract_id FROM lc_contracts
                    WHERE employee_id = :eid AND status = 'Active'
                    ORDER BY contract_id DESC LIMIT 1
                ");
                $stmt->execute([':eid' => $employeeId]);
                $contractId = (int) ($stmt->fetchColumn() ?: 0);
            }

            $this->db->beginTransaction();

            if ($contractId > 0) {
                $this->db->prepare("
                    UPDATE lc_contracts
                    SET monthly_salary = :salary,
                        start_date = COALESCE(:start_date, start_date),
                        end_date = COALESCE(:end_date, end_date),
                        updated_at = NOW()
                    WHERE contract_id = :id
                ")->execute([
                    ':salary' => number_format($newSalary, 2, '.', ''),
                    ':start_date' => $startDate !== '' ? $startDate : null,
                    ':end_date' => $endDate !== '' ? $endDate : null,
                    ':id' => $contractId,
                ]);
            }

            $this->db->prepare("
                UPDATE em_employees
                SET negotiated_salary = :salary,
                    updated_at = NOW()
                WHERE employee_id = :eid
            ")->execute([
                ':salary' => number_format($newSalary, 2, '.', ''),
                ':eid' => $employeeId,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, 'Failed to apply changes: ' . $e->getMessage());
            return;
        }

        $this->redirectToPreview($employeeId, $documentType, $templateCode, $hrSignatory, $startDate, $endDate, $salaryInput, '');
    }

    private function redirectToPreview(string $employeeId, string $documentType, string $templateCode, string $hrSignatory, string $startDate, string $endDate, string $salaryInput, string $error): void
    {
        $url = '?page=preview-document&employee_id=' . urlencode($employeeId)
             . '&document_type=' . urlencode($documentType)
             . '&template_code=' . urlencode($templateCode)
             . '&hr_signatory=' . urlencode($hrSignatory)
             . '&contract_start_date=' . urlencode($startDate)
             . '&contract_end_date=' . urlencode($endDate)
             . '&contract_salary_input=' . urlencode($salaryInput);

        if ($error !== '') {
            $url .= '&rectification_error=' . urlencode($error);
        }

        if (headers_sent() === false) {
            header('Location: ' . $url);
            exit;
        }

        echo '<script>window.location.href = ' . htmlspecialchars(json_encode($url)) . ';</script>';
        exit;
    }

    private function renderError(string $message): void
    {
        echo '<div class="dg-template-frame"><div class="dg-empty">' . htmlspecialchars($message) . '</div></div>';
    }
}


