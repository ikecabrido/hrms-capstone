<?php

require_once __DIR__ . '/../../../database/db.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$webBase = '/hrms-capstone/modules/compliance/';

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
    $pdfContent = generateSamplePdf($documentName, $employeeName, $department, $position, $issuedDate);
    file_put_contents($filePath, $pdfContent);
}

if (isset($_GET['download']) && $_GET['download'] === '1') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;

function generateSamplePdf(string $documentName, string $employeeName, string $department, string $position, string $issuedDate): string
{
    $lines = [
        ['text' => $documentName, 'size' => 18, 'bold' => true],
        ['text' => '', 'size' => 12, 'bold' => false],
        ['text' => 'Employee Name: ' . $employeeName, 'size' => 12, 'bold' => false],
        ['text' => 'Department: ' . $department, 'size' => 12, 'bold' => false],
        ['text' => 'Position: ' . $position, 'size' => 12, 'bold' => false],
        ['text' => 'Issued Date: ' . $issuedDate, 'size' => 12, 'bold' => false],
        ['text' => '', 'size' => 12, 'bold' => false],
        ['text' => 'This is a system-generated sample document developed solely for academic,', 'size' => 11, 'bold' => false],
        ['text' => 'research, and demonstration purposes as part of the Human Resource', 'size' => 11, 'bold' => false],
        ['text' => 'Management System with Legal Compliance Module undergraduate thesis project.', 'size' => 11, 'bold' => false],
        ['text' => '', 'size' => 11, 'bold' => false],
        ['text' => 'Any resemblance to actual persons, organizations, institutions, or events', 'size' => 11, 'bold' => false],
        ['text' => 'is purely coincidental.', 'size' => 11, 'bold' => false],
    ];

    $pageContent = '';
    $y = 720;
    foreach ($lines as $line) {
        $text = str_replace('\\', '\\\\', $line['text']);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        $size = (int) $line['size'];
        $bold = $line['bold'] ? 'F1' : 'F2';
        $pageContent .= "BT\n/$bold $size Tf\n72 $y Td\n($text) Tj\nET\n";
        $y -= $size + 4;
    }

    $stream = gzcompress($pageContent, 9);

    $objects = [];
    $offsets = [];

    $content = '%PDF-1.4' . "\n";

    $offsets[] = 0;
    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';

    $offsets[] = 0;
    $objects[] = '<< /Type /Pages /Kids [4 0 R] /Count 1 >>';

    $offsets[] = 0;
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

    $offsets[] = 0;
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

    $offsets[] = 0;
    $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 6 0 R /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> >>';

    $offsets[] = 0;
    $objects[] = '<< /Length ' . strlen($stream) . ' /Filter /FlateDecode >>';
    $objects[count($objects) - 1] .= "\nstream\n" . $stream . "\nendstream";

    $output = $content;
    for ($i = 0; $i < count($objects); $i++) {
        $offsets[$i + 1] = strlen($output);
        $output .= ($i + 1) . ' 0 obj' . "\n" . $objects[$i] . "\nendobj\n";
    }

    $xrefOffset = strlen($output);
    $output .= "xref\n0 " . (count($objects) + 1) . "\n";
    $output .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $output .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . ' 00000 n ' . "\n";
    }

    $output .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n";
    $output .= $xrefOffset . "\n%%EOF\n";

    return $output;
}
