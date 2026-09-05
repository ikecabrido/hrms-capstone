<?php

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

header('Content-Type: application/json');

$db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId   = $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 0;
$userName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
if ($userName === '') {
    $userName = $_SESSION['employee_name'] ?? $_SESSION['user_name'] ?? 'User';
}

$action = $_POST['action'] ?? '';
$debug  = ['session_user_id' => $userId, 'session_user_name' => $userName, 'action' => $action, 'post' => $_POST];

function generate_report_pdf(PDO $db, string $reportKey, string $reportCode): ?string {
    $reportsDir = __DIR__ . '/../../assets/documents/reports';
    if (!is_dir($reportsDir)) {
        @mkdir($reportsDir, 0775, true);
    }

    $reportTables = [
        'employee_master_list' => 'em_employees',
        'employee_compliance' => 'lc_compliance_records',
        'employee_documents' => 'lc_employee_documents',
        'document_expiration' => 'lc_employee_documents',
        'training_certifications' => 'lc_trainings',
        'policy_acknowledgement' => 'lc_acknowledgment_log',
        'leave_summary' => 'leave_requests',
        'disciplinary_actions' => 'lc_disciplinary_actions',
        'anonymous_reports' => 'lc_complaints',
        'legal_cases' => 'lc_compliance_violations',
        'audit_findings' => 'lc_audit_findings',
        'recruitment_summary' => 'lc_recruitment',
        'new_employees' => 'em_employees',
        'exit_clearance' => 'lc_exit_clearance',
        'exit_summary' => 'exit_resignations',
        'job_posting_approval' => 'lc_job_posting_requests',
        'vacancy_reports' => 'lc_vacant_positions',
    ];

    $rows = [];
    $columns = [];
    if (isset($reportTables[$reportKey])) {
        try {
            $stmt = $db->query("SELECT * FROM `{$reportTables[$reportKey]}` ORDER BY 1 DESC LIMIT 500");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columns = $rows ? array_keys($rows[0]) : [];
        } catch (Throwable $e) {
            $rows = [];
            $columns = [];
        }
    }

    $exportDate = date('Y-m-d H:i:s');
    $reportTitle = ucwords(str_replace(['_', '-'], ' ', $reportKey)) . ' Report';
    $isLargeTable = count($rows) >= 8;

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($reportTitle) ?></title>
        <style>
            *, *::before, *::after { box-sizing: border-box; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1b2430; margin: 0; padding: 0; line-height: 1.35; }
            table { width: 100%; border-collapse: collapse; margin-top: 8px; table-layout: fixed; }
            th, td { padding: 4px 5px; border-bottom: 1px solid #eef1f5; font-size: 8pt; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; white-space: normal; vertical-align: top; }
            th { background: #f3f5f9; text-align: left; border-bottom: 2px solid #dde3ea; color: #5b6472; text-transform: uppercase; font-weight: bold; }
            tr:nth-child(even) td { background: #fafbfc; }
            .report-header { border-bottom: 2px solid #a8791f; padding-bottom: 8px; margin-bottom: 10px; }
            .report-header h1 { font-size: 1rem; margin: 0 0 2px; }
            .report-header p { margin: 0; font-size: 0.75rem; color: #5b6472; }
            .no-data { text-align: center; color: #8b95a4; padding: 14px; font-style: italic; }
            .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #dde3ea; font-size: 0.75rem; color: #8b95a4; text-align: center; }
            @page { margin: 12mm; size: A4 portrait; }
            <?php if ($isLargeTable): ?>
            body { font-size: 7pt; }
            th, td { font-size: 6.5pt; padding: 2px 3px; }
            .report-header h1 { font-size: 0.85rem; }
            .report-header p { font-size: 0.65rem; }
            .footer { font-size: 0.65rem; margin-top: 12px; padding-top: 6px; }
            .report-header { padding-bottom: 6px; margin-bottom: 8px; }
            @page { margin: 10mm; size: A1 landscape; }
            <?php endif; ?>
        </style>
    </head>
    <body>
        <div class="report-header">
            <h1><?= htmlspecialchars($reportTitle) ?></h1>
            <p>Generated: <?= htmlspecialchars($exportDate) ?></p>
        </div>
        <?php if (!empty($columns)): ?>
        <table>
            <thead>
                <tr>
                    <?php foreach ($columns as $col): ?>
                    <th><?= htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', $col))) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                    <td><?= htmlspecialchars((string)$cell) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="no-data">No records found.</div>
        <?php endif; ?>
        <div class="footer">This report was auto-generated by the HR Legal Compliance Management System on <?= htmlspecialchars($exportDate) ?></div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    if (!class_exists('Dompdf\Dompdf')) {
        require_once __DIR__ . '/../../lib/vendor/autoload.php';
    }
    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isPhpEnabled', true);
    $options->set('dpi', 96);
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper($isLargeTable ? 'A1' : 'A4', $isLargeTable ? 'landscape' : 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();

    $filename = $reportCode . '.pdf';
    $savePath = $reportsDir . '/' . $filename;
    $bytesWritten = @file_put_contents($savePath, $pdfOutput);

    if ($bytesWritten === false) {
        return null;
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . $host . '/hrms-capstone/modules/compliance/assets/documents/reports/' . $filename;
}

try {
    switch ($action) {

        case 'send_report':
            $reportKey   = trim($_POST['report_key'] ?? '');
            $reportLabel = trim($_POST['report_label'] ?? $reportKey);

            if ($reportKey === '') {
                echo json_encode(['success' => false, 'message' => 'Missing report.', 'debug' => $debug]);
                exit;
            }

            $reportCode = 'RPT-' . strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($reportKey, 0, 8))) . '-' . date('YmdHis');
            $filePath = generate_report_pdf($db, $reportKey, $reportCode);
            $stmt = $db->prepare("INSERT INTO lc_generated_reports (id, report_code, report_key, report_date, status, file_format, generated_by, submitted_by, submitted_at, created_at, file_path) VALUES (NULL, :code, :key, CURDATE(), 'Submitted', 'PDF', :uid, :uid, NOW(), NOW(), :file_path)");
            $stmt->execute([':code' => $reportCode, ':key' => $reportKey, ':uid' => $userId, ':file_path' => $filePath]);

            $reportId = (int) $db->lastInsertId();

            try {
                $db->prepare("INSERT INTO lc_report_history (id, report_id, action, performed_by, performed_by_name, notes, created_at) VALUES (NULL, :rid, 'submit', :uid, :uname, :notes, NOW())")
                    ->execute([':rid' => $reportId, ':uid' => $userId, ':uname' => $userName, ':notes' => 'Report ' . $reportLabel . ' submitted to Directress']);
            } catch (Throwable $e) {
                $debug['history_error'] = $e->getMessage();
            }

            try {
                $stmt = $db->prepare("INSERT INTO lc_notifications (id, title, message, type, module, is_read, created_at) VALUES (NULL, :title, :message, 'info', 'Audit & Reporting', 0, NOW())");
                $stmt->execute([':title' => 'Report Submitted', ':message' => $reportLabel . ' has been submitted to the Directress.']);
            } catch (Throwable $e) {
                $debug['notification_error'] = $e->getMessage();
            }

            echo json_encode(['success' => true, 'message' => $reportLabel . ' submitted to Directress.', 'report_id' => $reportId, 'debug' => $debug]);
            exit;

        case 'schedule_report':
            $reportKey   = trim($_POST['report_key'] ?? '');
            $reportName  = trim($_POST['report_name'] ?? $reportKey);
            $frequency   = trim($_POST['frequency'] ?? 'Monthly');
            $dayOfMonth  = isset($_POST['day_of_month']) ? (int) $_POST['day_of_month'] : 1;
            $sendNow     = !empty($_POST['send_now']);

            if ($reportKey === '') {
                echo json_encode(['success' => false, 'message' => 'Missing report.', 'debug' => $debug]);
                exit;
            }

            $validFreq = ['Daily', 'Weekly', 'Monthly', 'Quarterly', 'Annual', 'Anytime'];
            if (!in_array($frequency, $validFreq, true)) {
                $frequency = 'Monthly';
            }
            if ($dayOfMonth < 1) $dayOfMonth = 1;
            if ($dayOfMonth > 31) $dayOfMonth = 31;

            $nextRun = date('Y-m-d H:i:s', strtotime('+' . ($frequency === 'Daily' ? 1 : ($frequency === 'Weekly' ? 7 : ($frequency === 'Monthly' ? 1 : ($frequency === 'Quarterly' ? 3 : 12)))) . ' days'));

            $stmt = $db->prepare("INSERT INTO lc_report_schedule (report_key, report_name, module, frequency, day_of_month, next_run, active, created_at, updated_at) VALUES (:key, :name, 'Audit & Reporting', :freq, :dom, :next, 1, NOW(), NOW())");
            $stmt->execute([':key' => $reportKey, ':name' => $reportName, ':freq' => $frequency, ':dom' => $dayOfMonth, ':next' => $nextRun]);
            $scheduleId = (int) $db->lastInsertId();

            if ($sendNow) {
                $reportCode = 'RPT-' . strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($reportKey, 0, 8))) . '-' . date('YmdHis');
                $filePath = generate_report_pdf($db, $reportKey, $reportCode);
                $stmt2 = $db->prepare("INSERT INTO lc_generated_reports (id, report_code, report_key, report_date, status, file_format, generated_by, submitted_by, submitted_at, created_at, file_path) VALUES (NULL, :code, :key, CURDATE(), 'Submitted', 'PDF', :uid, :uid, NOW(), NOW(), :file_path)");
                $stmt2->execute([':code' => $reportCode, ':key' => $reportKey, ':uid' => $userId, ':file_path' => $filePath]);
            }

            try {
                $stmt = $db->prepare("INSERT INTO lc_notifications (id, title, message, type, module, is_read, created_at) VALUES (NULL, :title, :message, 'info', 'Audit & Reporting', 0, NOW())");
                $stmt->execute([':title' => 'Report Scheduled', ':message' => $reportName . ' has been scheduled (' . $frequency . ').']);
            } catch (Throwable $e) {}

            echo json_encode(['success' => true, 'message' => $reportName . ' scheduled successfully.', 'debug' => $debug]);
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action.', 'debug' => $debug]);
            exit;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'debug' => $debug]);
}
