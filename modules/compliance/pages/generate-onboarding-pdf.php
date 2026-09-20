<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../assets/logs/onboarding_pdf_error.log');

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$htmlPath = __DIR__ . '/../assets/documents/onboarding/onboarding.html';
if (!is_file($htmlPath)) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$html = file_get_contents($htmlPath);
if ($html === false) {
    http_response_code(500);
    echo 'Failed to read file.';
    exit;
}

require_once __DIR__ . '/../lib/vendor/autoload.php';

$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('dpi', 96);

try {
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();
} catch (Throwable $e) {
    error_log('Dompdf error: ' . $e->getMessage());
    http_response_code(500);
    echo 'PDF generation failed.';
    exit;
}

if ($pdfOutput === false || strlen($pdfOutput) < 100 || strpos($pdfOutput, '%PDF') !== 0) {
    error_log('PDF output invalid, length: ' . (is_string($pdfOutput) ? strlen($pdfOutput) : 'null') . ', starts with: ' . (is_string($pdfOutput) ? substr($pdfOutput, 0, 20) : 'null'));
    http_response_code(500);
    echo 'PDF generation failed.';
    exit;
}

$filename = 'onboarding_' . date('Ymd') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfOutput));
echo $pdfOutput;
exit;
