<?php

$pageTitle = 'Labor Law Reference Detail';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$currentEmployeeId = (int) ($_SESSION['employee_id'] ?? 0);
$currentRole       = (int) ($_SESSION['role'] ?? 0);
$canManage         = in_array($currentRole, [1, 2, 3], true);

require_once __DIR__ . '/../classes/LaborLawReference.php';

if (!isset($db)) {
    if (class_exists('Database')) {
        $db = (new Database())->getConnection();
    } else {
        require_once __DIR__ . '/../../../database/db.php';
        $db = (new Database())->getConnection();
    }
}

$model = new LaborLawReference($db);

$referenceId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($referenceId <= 0) {
    header('Location: ?page=labor-law-references&msg=error|Invalid reference ID');
    exit;
}

$reference = $model->getReferenceById($referenceId);

if (!$reference) {
    $pdfDir = __DIR__ . '/../assets/labor-law-pdf/';
    $pdfBaseUrl = '/modules/compliance/assets/labor-law-pdf/';
    if (is_dir($pdfDir)) {
        foreach (glob($pdfDir . '*.pdf') as $pdfPath) {
            $filename = basename($pdfPath);
            $pdfId = (int) (crc32($filename) & 0x7FFFFFFF);
            if ($pdfId === $referenceId) {
                $title = preg_replace('/\.pdf$/i', '', $filename);
                $title = str_replace(['-', '_'], ' ', $title);
                $title = ucwords(strtolower($title));
                $reference = [
                    'id' => $pdfId,
                    'reference_type' => 'PDF Document',
                    'reference_number' => $title,
                    'title' => $title,
                    'short_title' => $title,
                    'category_id' => null,
                    'category_name' => null,
                    'description' => null,
                    'date_issued' => null,
                    'effectivity_date' => null,
                    'issuing_authority' => null,
                    'status' => 'For Reference',
                    'keywords' => $title,
                    'source_url' => '',
                    'document_path' => $pdfBaseUrl . rawurlencode($filename),
                    'related_law' => null,
                    'summary' => null,
                    'remarks' => 'Loaded from PDF directory',
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => null,
                    'updated_at' => null,
                ];
                break;
            }
        }
    }
}

if (!$reference) {
    header('Location: ?page=labor-law-references&msg=error|Reference not found');
    exit;
}

$pageTitle = $reference['title'] ?? 'Labor Law Reference Detail';

$localBase = 'C:/xampp/htdocs/hrms-capstone/';
$webRoot = '/';

$docUrl = !empty($reference['document_path']) ? $reference['document_path'] : '';

if ($docUrl) {
    if (str_starts_with($docUrl, $localBase)) {
        $docUrl = $webRoot . ltrim(substr($docUrl, strlen($localBase)), '/');
    } elseif (str_starts_with($docUrl, 'http://127.0.0.1/hrms-capstone/')) {
        $docUrl = '/' . ltrim(substr($docUrl, strlen('http://127.0.0.1/hrms-capstone/')), '/');
    } elseif (str_starts_with($docUrl, 'http://localhost/hrms-capstone/')) {
        $docUrl = '/' . ltrim(substr($docUrl, strlen('http://localhost/hrms-capstone/')), '/');
    } elseif (strpos($docUrl, '://') === false && strpos($docUrl, '/') !== 0) {
        $docUrl = $webRoot . ltrim($docUrl, '/');
    }
}

$sourceUrl = !empty($reference['source_url']) ? $reference['source_url'] : '';
if ($sourceUrl && strpos($sourceUrl, '://') === false && strpos($sourceUrl, '/') !== 0) {
    $sourceUrl = '/' . ltrim($sourceUrl, '/');
}

?>

<link rel="stylesheet" href="/modules/compliance/css/pages/labor-law-reference-detail.css">

<section class="llr-detail-module">
    <div class="llr-detail-card">
        <div class="llr-detail-header">
            <div>
            </div>
            <div class="llr-detail-header-right">
            </div>
        </div>

        <div class="llr-detail-fields">
            <div class="llr-detail-row">
                <div class="llr-detail-label">Title</div>
                <div class="llr-detail-value"><?= htmlspecialchars($reference['short_title'] ?? '—') ?></div>
            </div>
            <div class="llr-detail-row">
                <div class="llr-detail-label">Issuing Authority</div>
                <div class="llr-detail-value"><?= htmlspecialchars($reference['issuing_authority'] ?? '—') ?></div>
            </div>
            <?php if (!empty($reference['related_law'])): ?>
            <div class="llr-detail-row">
                <div class="llr-detail-label">Related Law</div>
                <div class="llr-detail-value"><?= htmlspecialchars($reference['related_law']) ?></div>
            </div>
            <?php endif; ?>
            <div class="llr-detail-row">
                <div class="llr-detail-label">Reference</div>
                <div class="llr-detail-value">
                    <?= htmlspecialchars($reference['reference_type'] ?? '') ?>
                    <?php if (!empty($reference['reference_number'])): ?>
                        — <?= htmlspecialchars($reference['reference_number']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($reference['summary'])): ?>
        <div class="llr-detail-section">
            <h4>Summary</h4>
            <p><?= nl2br(htmlspecialchars($reference['summary'])) ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($reference['description'])): ?>
        <div class="llr-detail-section">
            <h4>Description</h4>
            <p><?= nl2br(htmlspecialchars($reference['description'])) ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($reference['remarks'])): ?>
        <div class="llr-detail-section">
            <h4>Remarks</h4>
            <p><?= nl2br(htmlspecialchars($reference['remarks'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="llr-detail-actions">
            <?php if ($docUrl): ?>
                <a href="<?= htmlspecialchars($docUrl) ?>" target="_blank" rel="noopener noreferrer" class="llr-btn llr-btn-primary">
                    View Document
                </a>
            <?php endif; ?>
            <?php if ($sourceUrl): ?>
                <a href="<?= htmlspecialchars($sourceUrl) ?>" target="_blank" rel="noopener noreferrer" class="llr-btn">
                    Official Source
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>



