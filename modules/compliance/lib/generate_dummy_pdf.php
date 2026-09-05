<?php

require_once __DIR__ . '/../../../database/db.php';

$webBase = '/hrms-capstone/modules/compliance/';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$db = (new Database())->getConnection();

$documentName = $_GET['document_name'] ?? 'Sample Document';
$employeeName = $_GET['employee_name'] ?? 'Juan Dela Cruz';
$department = $_GET['department'] ?? 'Human Resources';
$position = $_GET['position'] ?? 'HR Officer';
$issuedDate = $_GET['issued_date'] ?? date('F d, Y');

$safeDocumentName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $documentName);
$fileName = 'dummy_' . $safeDocumentName . '_' . date('Ymd') . '.pdf';

$pdfDir = __DIR__ . '/../../generated_pdfs';
if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0777, true);
}

$filePath = $pdfDir . '/' . $fileName;

if (!file_exists($filePath)) {
    $pdfContent = generateMinimalPdf($documentName, $employeeName, $department, $position, $issuedDate);
    file_put_contents($filePath, $pdfContent);
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;

function generateMinimalPdf(string $documentName, string $employeeName, string $department, string $position, string $issuedDate): string
{
    $lines = [
        $documentName,
        '',
        'Employee Name: ' . $employeeName,
        'Department: ' . $department,
        'Position: ' . $position,
        'Issued Date: ' . $issuedDate,
        '',
        'This is a system-generated sample document developed solely for academic,',
        'research, and demonstration purposes as part of the Human Resource',
        'Management System with Legal Compliance Module undergraduate thesis project.',
        '',
        'Any resemblance to actual persons, organizations, institutions, or events',
        'is purely coincidental.',
    ];

    $fontFile = __DIR__ . '/../../assets/fonts/FreeSans.ttf';

    $content = '%PDF-1.4' . "\n";
    $objects = [];
    $offsets = [];

    $objIdCatalog = 2;
    $objIdPages = 3;
    $objIdFont = 4;
    $objIdContent = 5;

    $offsets[] = 0;
    $objects[] = '1 0 obj' . "\n" . '<< /Type /Catalog /Pages 2 0 R >>' . "\n" . 'endobj' . "\n";

    $pageContent = 'BT' . "\n";
    $pageContent .= '/F1 12 Tf' . "\n";
    $pageContent .= '72 720 Td' . "\n";

    foreach ($lines as $index => $line) {
        $safeLine = str_replace('\\', '\\\\', $line);
        $safeLine = str_replace('(', '\\(', $safeLine);
        $safeLine = str_replace(')', '\\)', $safeLine);
        if ($index === 0) {
            $pageContent .= 'BT' . "\n" . '/F1 16 Tf' . "\n";
            $pageContent .= '(' . $safeLine . ') Tj' . "\n";
            $pageContent .= 'ET' . "\n";
            $pageContent .= '0 -24 Td' . "\n";
        } else {
            $pageContent .= '(' . $safeLine . ') Tj' . "\n";
            $pageContent .= '0 -14 Td' . "\n";
        }
    }

    $pageContent .= 'ET' . "\n";
    $stream = gzcompress($pageContent, 9);

    $offsets[] = 0;
    $objects[] = '2 0 obj' . "\n" . '<< /Type /Pages /Kids [4 0 R] /Count 1 >>' . "\n" . 'endobj' . "\n";

    $offsets[] = 0;
    $objects[] = '3 0 obj' . "\n" . '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>' . "\n" . 'endobj' . "\n";

    $offsets[] = 0;
    $objects[] = '4 0 obj' . "\n" . '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 5 0 R /Resources << /Font << /F1 3 0 R >> >> >>' . "\n" . 'endobj' . "\n";

    $offsets[] = 0;
    $objects[] = '5 0 obj' . "\n" . '<< /Length ' . strlen($stream) . ' /Filter /FlateDecode >>' . "\n" . 'stream' . "\n";
    $objects[count($objects) - 1] .= $stream;
    $objects[count($objects) - 1] .= "\n" . 'endstream' . "\n" . 'endobj' . "\n";

    $xrefOffset = 0;
    $output = $content;
    foreach ($objects as $index => $obj) {
        $offsets[$index + 1] = strlen($output);
        $output .= ($index + 1) . ' 0 obj' . "\n";
        $output .= $obj;
    }

    $xrefOffset = strlen($output);
    $output .= 'xref' . "\n";
    $output .= '0 ' . (count($objects) + 1) . "\n";
    $output .= '0000000000 65535 f ' . "\n";
    for ($i = 1; $i <= count($objects); $i++) {
        $output .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . ' 00000 n ' . "\n";
    }

    $output .= 'trailer' . "\n" . '<< /Size ' . (count($objects) + 1) . ' /Root 1 0 R >>' . "\n" . 'startxref' . "\n";
    $output .= $xrefOffset . "\n";
    $output .= '%%EOF' . "\n";

    return $output;
}
