<?php

$key = $_GET['key'] ?? '';
$format = strtolower((string)($_GET['format'] ?? 'html'));
$allowedFormats = ['pdf', 'csv', 'html'];
if (!in_array($format, $allowedFormats, true)) {
    $format = 'html';
}

$db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

function cv_value(PDO $db, string $sql, $default = 0) {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
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

$reportTitle = ucwords(str_replace(['_', '-'], ' ', $key)) . ' Report';
$rows = [];
$columns = [];

if (isset($reportTables[$key])) {
    try {
        $stmt = $db->query("SELECT * FROM `{$reportTables[$key]}` ORDER BY 1 DESC LIMIT 500");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columns = $rows ? array_keys($rows[0]) : [];
    } catch (Throwable $e) {
        $rows = [];
        $columns = [];
    }
}

$exportDate = date('Y-m-d H:i:s');

if ($format === 'csv') {
    $filename = preg_replace('/[^A-Za-z0-9_-]/', '-', strtolower($key)) . '-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, [$reportTitle]);
    fputcsv($out, ['Generated on: ' . $exportDate]);
    fputcsv($out, []);
    if (!empty($columns)) {
        fputcsv($out, $columns);
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
    }
    fclose($out);
    exit;
}

if ($format === 'pdf') {
    $reportsDir = __DIR__ . '/../../assets/documents/reports';
    if (!is_dir($reportsDir)) {
        @mkdir($reportsDir, 0775, true);
    }

    $reportTitle = ucwords(str_replace(['_', '-'], ' ', $key)) . ' Report';
    $exportDate = date('Y-m-d H:i:s');
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

    $filename = preg_replace('/[^A-Za-z0-9_-]/', '-', strtolower($key)) . '-' . date('Ymd-His') . '.pdf';
    $savePath = $reportsDir . '/' . $filename;
    @file_put_contents($savePath, $pdfOutput);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfOutput));
    echo $pdfOutput;
    exit;
}

header('Content-Type: text/html; charset=utf-8');
$safeTitle = htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8');
$safeDate = htmlspecialchars($exportDate, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $safeTitle ?></title>
    <style>
        body { font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 10px; color: #1b2430; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: auto; }
        th { background: #f3f5f9; text-align: left; padding: 5px 6px; border-bottom: 2px solid #dde3ea; font-size: 0.65rem; text-transform: uppercase; color: #5b6472; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
        td { padding: 5px 6px; border-bottom: 1px solid #eef1f5; font-size: 0.7rem; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
        tr:nth-child(even) td { background: #fafbfc; }
        .report-header { border-bottom: 3px solid #a8791f; padding-bottom: 10px; margin-bottom: 14px; }
        .report-header h1 { font-size: 1rem; margin: 0 0 3px; }
        .report-header p { margin: 0; font-size: 0.7rem; color: #5b6472; }
        .no-data { text-align: center; color: #8b95a4; padding: 16px; font-style: italic; }
        .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #dde3ea; font-size: 0.7rem; color: #8b95a4; text-align: center; }
        @media print { body { padding: 20px; } }
    </style>
</head>
<body>
    <div class="report-header">
        <h1><?= $safeTitle ?></h1>
        <p>Generated: <?= $safeDate ?></p>
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
    <div class="footer">This report was auto-generated by the HR Legal Compliance Management System on <?= $safeDate ?></div>
</body>
</html>

