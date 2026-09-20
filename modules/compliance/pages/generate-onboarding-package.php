<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../lib/ajax/document_template_helper.php';

$pageTitle = 'Generate Onboarding Package';

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

@file_put_contents(__DIR__ . '/../assets/logs/debug_trace.log', date('Y-m-d H:i:s') . ' - Script started' . "\n", FILE_APPEND);

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
    echo 'You are not authorized to generate onboarding document packages.';
    exit;
}

$db = (new Database())->getConnection();
if (!$db instanceof PDO) {
    throw new RuntimeException('Database connection is unavailable.');
}

$applicationId = isset($_GET['application_id']) ? trim((string) $_GET['application_id']) : '';
$generate = isset($_GET['generate']) && $_GET['generate'] === '1';

if ($applicationId === '' || !ctype_digit($applicationId)) {
    http_response_code(400);
    echo 'Invalid or missing application_id.';
    exit;
}

if (!$generate) {
    @file_put_contents(__DIR__ . '/../assets/logs/debug_trace.log', date('Y-m-d H:i:s') . ' - Redirecting: generate=0' . "\n", FILE_APPEND);
    header('Location: ?page=onboarding-package&application_id=' . urlencode($applicationId));
    exit;
}

@file_put_contents(__DIR__ . '/../assets/logs/debug_trace.log', date('Y-m-d H:i:s') . ' - Entering try block' . "\n", FILE_APPEND);
try {
    $employee = op_get_employee_for_package($db, $applicationId);
    if (!$employee) {
        throw new RuntimeException('Employee not found.');
    }

    $userId = (int) ($user['user_id'] ?? $user['id'] ?? 0);

    if (!function_exists('op_generate_package_number')) {
        throw new RuntimeException('Missing required function: op_generate_package_number');
    }
    $packageNumber = op_generate_package_number($db);

    $contractTemplate = dg_get_document_template($db, 'employment_contract');
    $handbookTemplate = dg_get_document_template($db, 'employee_handbook');
    $ndaTemplate = dg_get_document_template($db, 'nda');

    if (!$contractTemplate || !$handbookTemplate || !$ndaTemplate) {
        $missing = [];
        if (!$contractTemplate) $missing[] = 'employment_contract';
        if (!$handbookTemplate) $missing[] = 'employee_handbook';
        if (!$ndaTemplate) $missing[] = 'nda';
        throw new RuntimeException('Missing document templates: ' . implode(', ', $missing) . '. Please contact the Legal team.');
    }

    $saveDir = __DIR__ . '/../assets/documents/onboarding/';
    @file_put_contents(__DIR__ . '/../assets/logs/debug_trace.log', date('Y-m-d H:i:s') . ' - Templates loaded, saveDir=' . $saveDir . "\n", FILE_APPEND);

    $contractVersion = $contractTemplate['version'] ?? '1.0';
    $handbookVersion = $handbookTemplate['version'] ?? '1.0';
    $ndaVersion = $ndaTemplate['version'] ?? '1.0';

    if (!is_dir($saveDir)) {
        if (!mkdir($saveDir, 0775, true) && !is_dir($saveDir)) {
            throw new RuntimeException('Failed to create documents directory. Please check permissions. Path: ' . $saveDir);
        }
    }

    if (!is_writable($saveDir)) {
        throw new RuntimeException('Documents directory is not writable. Path: ' . $saveDir);
    }

    $logDir = __DIR__ . '/../assets/logs/';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    $packageFilename = 'onboarding_' . preg_replace('/[^A-Za-z0-9]/', '', $applicationId) . '_' . date('Ymd') . '.html';
    $packageSavePath = $saveDir . $packageFilename;

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $packageFileUrl = $protocol . $host . '/hrms-capstone/modules/compliance/assets/documents/onboarding/' . $packageFilename;

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            INSERT INTO lc_onboarding_packages
                (employee_id, package_number, status, contract_template_version, handbook_template_version, nda_template_version, generated_by, notes)
            VALUES
                (:employee_id, :package_number, 'Generated', :contract_ver, :handbook_ver, :nda_ver, :generated_by, :notes)
        ");
        $stmt->execute([
            ':employee_id' => (int) $applicationId,
            ':package_number' => $packageNumber,
            ':contract_ver' => $contractVersion,
            ':handbook_ver' => $handbookVersion,
            ':nda_ver' => $ndaVersion,
            ':generated_by' => $userId > 0 ? $userId : null,
            ':notes' => 'Onboarding package generated for application #' . $applicationId,
        ]);
        $packageId = (int) $db->lastInsertId();

        $contractId = null;
        $contractNumber = null;
        $contractStartDate = !empty($employee['date_hired']) ? date('Y-m-d', strtotime($employee['date_hired'])) : null;
        $contractEndDate = $contractStartDate !== null ? date('Y-m-d', strtotime('+1 year', strtotime($contractStartDate))) : null;
        $contractType = $employee['employment_status'] ?? '';
        $monthlySalary = $employee['monthly_salary'] ?? $employee['negotiated_salary'] ?? null;
        $governingLaw = $contractTemplate['governing_law'] ?? 'Philippine Labor Code (PD 442)';
        $categoryId = $contractTemplate['category_id'] ?? null;

        if (function_exists('dg_generate_contract_number')) {
            $contractNumber = dg_generate_contract_number($db);
        } else {
            $nextId = (int) $db->query("SELECT COALESCE(MAX(contract_id), 0) + 1 FROM lc_contracts")->fetchColumn();
            $contractNumber = 'CTR-' . date('Y') . '-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);
        }

        $stmt = $db->prepare("
            INSERT INTO lc_contracts (employee_id, contract_number, contract_type, governing_law, jurisdiction, category_id, requires_dual_sig, digital_signature_status, start_date, end_date, status, monthly_salary, working_hours_per_week, notes, created_by, created_by_role)
            VALUES (:employee_id, :contract_number, :contract_type, :governing_law, :jurisdiction, :category_id, 1, 'none', :start_date, :end_date, 'Draft', :monthly_salary, 40, :notes, :created_by, 'hr')
        ");
        $stmt->execute([
            ':employee_id' => (int) $applicationId,
            ':contract_number' => $contractNumber,
            ':contract_type' => $contractType ?: 'Regular',
            ':governing_law' => $governingLaw,
            ':jurisdiction' => 'Philippines',
            ':category_id' => $categoryId,
            ':start_date' => $contractStartDate ?: date('Y-m-d'),
            ':end_date' => $contractEndDate,
            ':monthly_salary' => $monthlySalary !== null ? number_format((float) $monthlySalary, 2, '.', '') : null,
            ':notes' => 'Generated from onboarding package #' . $packageNumber,
            ':created_by' => $userId,
        ]);
        $contractId = (int) $db->lastInsertId();

        $db->prepare("UPDATE lc_contracts SET file_path = :file_path, file_name = :file_name WHERE contract_id = :id")->execute([
            ':file_path' => $packageFileUrl,
            ':file_name' => $packageFilename,
            ':id' => $contractId,
        ]);

        $stmt = $db->prepare("
            INSERT INTO lc_document_requests (employee_id, rao_hired_id, document_type, request_status, priority, notes, requires_signature, signature_status, template_code)
            VALUES (:employee_id, :rao_hired_id, :document_type, 'completed', 'Medium', NULL, 1, 'none', :template_code)
        ");
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare Employment Contract document request insert.');
        }
        $stmt->execute([
            ':employee_id' => (int) $applicationId,
            ':rao_hired_id' => !empty($employee['id']) && !empty($employee['candidate_id']) ? (int) $employee['id'] : null,
            ':document_type' => 'Employment Contract (New Hire)',
            ':template_code' => 'employment_contract',
        ]);
        $contractRequestId = (int) $db->lastInsertId();

        $stmt = $db->prepare("
            INSERT INTO lc_document_requests (employee_id, rao_hired_id, document_type, request_status, priority, notes, requires_signature, signature_status, template_code)
            VALUES (:employee_id, :rao_hired_id, :document_type, 'completed', 'Medium', NULL, 1, 'none', :template_code)
        ");
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare Employee Handbook document request insert.');
        }
        $stmt->execute([
            ':employee_id' => (int) $applicationId,
            ':rao_hired_id' => !empty($employee['id']) && !empty($employee['candidate_id']) ? (int) $employee['id'] : null,
            ':document_type' => 'Employee Handbook',
            ':template_code' => 'employee_handbook',
        ]);
        $handbookRequestId = (int) $db->lastInsertId();

        $stmt = $db->prepare("
            INSERT INTO lc_document_requests (employee_id, rao_hired_id, document_type, request_status, priority, notes, requires_signature, signature_status, template_code)
            VALUES (:employee_id, :rao_hired_id, :document_type, 'completed', 'Medium', NULL, 1, 'none', :template_code)
        ");
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare NDA document request insert.');
        }
        $stmt->execute([
            ':employee_id' => (int) $applicationId,
            ':rao_hired_id' => !empty($employee['id']) && !empty($employee['candidate_id']) ? (int) $employee['id'] : null,
            ':document_type' => 'Non-Disclosure Agreement (NDA)',
            ':template_code' => 'nda',
        ]);
        $ndaRequestId = (int) $db->lastInsertId();

        if (!function_exists('op_generate_package_html')) {
            throw new RuntimeException('Missing required function: op_generate_package_html');
        }
        $packageHtml = op_generate_package_html($db, $employee);

        $debugHtmlPath = __DIR__ . '/../assets/logs/onboarding_debug_' . preg_replace('/[^A-Za-z0-9]/', '', $applicationId) . '_' . date('Ymd_His') . '.html';
        @file_put_contents($debugHtmlPath, $packageHtml);

        $bytesWritten = null;
        $maxRetries = 5;
        $retryDelayMs = 200;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $tempPath = $packageSavePath . '.tmp.' . getmypid();
            $handle = @fopen($tempPath, 'wb');
            if ($handle === false) {
                if ($attempt === $maxRetries) {
                    throw new RuntimeException('Failed to open temporary file for writing HTML at: ' . $tempPath);
                }
                usleep($retryDelayMs * 1000);
                continue;
            }
            $written = @fwrite($handle, $packageHtml);
            @fflush($handle);
            @fclose($handle);
            if ($written === false || $written < strlen($packageHtml)) {
                @unlink($tempPath);
                if ($attempt === $maxRetries) {
                    throw new RuntimeException('Failed to write HTML to temporary file at: ' . $tempPath);
                }
                usleep($retryDelayMs * 1000);
                continue;
            }
            if (!@rename($tempPath, $packageSavePath)) {
                @unlink($tempPath);
                if ($attempt < $maxRetries) {
                    if (@file_exists($packageSavePath)) {
                        @unlink($packageSavePath);
                    }
                    usleep($retryDelayMs * 1000);
                    continue;
                }
                throw new RuntimeException('Failed to move temporary HTML to final path: ' . $packageSavePath);
            }
            $bytesWritten = $written;
            break;
        }
        if ($bytesWritten === null) {
            throw new RuntimeException('Failed to write HTML file to disk at: ' . $packageSavePath);
        }

        $db->prepare("UPDATE lc_onboarding_packages SET file_path = :file_path, file_name = :file_name, status = 'Generated' WHERE package_id = :id")->execute([
            ':file_path' => $packageFileUrl,
            ':file_name' => $packageFilename,
            ':id' => $packageId,
        ]);

        $db->prepare("
            INSERT INTO lc_onboarding_package_documents (package_id, document_type, template_code, template_version, request_id, contract_id, file_path, file_name)
            VALUES (:package_id, :document_type, :template_code, :template_version, :request_id, :contract_id, :file_path, :file_name)
        ")->execute([
            ':package_id' => $packageId,
            ':document_type' => 'Employment Contract',
            ':template_code' => 'employment_contract',
            ':template_version' => $contractVersion,
            ':request_id' => $contractRequestId,
            ':contract_id' => $contractId,
            ':file_path' => $packageFileUrl,
            ':file_name' => $packageFilename,
        ]);

        $db->prepare("
            INSERT INTO lc_onboarding_package_documents (package_id, document_type, template_code, template_version, request_id, file_path, file_name)
            VALUES (:package_id, :document_type, :template_code, :template_version, :request_id, :file_path, :file_name)
        ")->execute([
            ':package_id' => $packageId,
            ':document_type' => 'Employee Handbook',
            ':template_code' => 'employee_handbook',
            ':template_version' => $handbookVersion,
            ':request_id' => $handbookRequestId,
            ':file_path' => $packageFileUrl,
            ':file_name' => $packageFilename,
        ]);

        $db->prepare("
            INSERT INTO lc_onboarding_package_documents (package_id, document_type, template_code, template_version, request_id, file_path, file_name)
            VALUES (:package_id, :document_type, :template_code, :template_version, :request_id, :file_path, :file_name)
        ")->execute([
            ':package_id' => $packageId,
            ':document_type' => 'Non-Disclosure Agreement',
            ':template_code' => 'nda',
            ':template_version' => $ndaVersion,
            ':request_id' => $ndaRequestId,
            ':file_path' => $packageFileUrl,
            ':file_name' => $packageFilename,
        ]);

        $db->commit();

        $stmt = $db->prepare("SELECT id FROM rao_hired WHERE application_id = :aid LIMIT 1");
        $stmt->execute([':aid' => (int) $applicationId]);
        $raoHiredId = $stmt->fetchColumn();
        if ($raoHiredId) {
            $db->prepare("DELETE FROM rao_hired WHERE id = :id")->execute([':id' => (int) $raoHiredId]);
        }
    } catch (\Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        @file_put_contents(__DIR__ . '/../assets/logs/debug_trace.log', date('Y-m-d H:i:s') . ' - DB transaction error: ' . $e->getMessage() . "\n", FILE_APPEND);
        throw $e;
    }

    @file_put_contents(__DIR__ . '/../assets/logs/debug_trace.log', date('Y-m-d H:i:s') . ' - After DB transaction, file_exists=' . (file_exists($packageSavePath) ? 'yes' : 'no') . "\n", FILE_APPEND);

    if (!file_exists($packageSavePath)) {
        throw new RuntimeException('Failed to save package HTML to disk.');
    }

    $redirectUrl = '?page=employment-contracts';
    if (!headers_sent()) {
        header('Location: ' . $redirectUrl);
        exit;
    }

    echo '<script type="text/javascript">window.location.href = ' . json_encode($redirectUrl) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirectUrl) . '"><a href="' . htmlspecialchars($redirectUrl) . '">Click here if not redirected</a></noscript>';
    exit;
} catch (\Exception $e) {
    $errorMsg = 'Onboarding package generation failed: ' . $e->getMessage();
    error_log($errorMsg);
    @file_put_contents(__DIR__ . '/../../assets/logs/onboarding_error.log', date('Y-m-d H:i:s') . ' - ' . $errorMsg . "\n", FILE_APPEND);
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo $errorMsg;
    exit;
}
