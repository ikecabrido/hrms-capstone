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
    $pdfBaseUrl = '/hrms-capstone/modules/compliance/assets/labor-law-pdf/';
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
$webRoot = '/hrms-capstone/';

$docUrl = !empty($reference['document_path']) ? $reference['document_path'] : '';

if ($docUrl) {
    if (str_starts_with($docUrl, $localBase)) {
        $docUrl = $webRoot . ltrim(substr($docUrl, strlen($localBase)), '/');
    } elseif (str_starts_with($docUrl, 'http://127.0.0.1/hrms-capstone/')) {
        $docUrl = '/hrms-capstone/' . ltrim(substr($docUrl, strlen('http://127.0.0.1/hrms-capstone/')), '/');
    } elseif (str_starts_with($docUrl, 'http://localhost/hrms-capstone/')) {
        $docUrl = '/hrms-capstone/' . ltrim(substr($docUrl, strlen('http://localhost/hrms-capstone/')), '/');
    } elseif (strpos($docUrl, '://') === false && strpos($docUrl, '/') !== 0) {
        $docUrl = $webRoot . ltrim($docUrl, '/');
    }
}

$sourceUrl = !empty($reference['source_url']) ? $reference['source_url'] : '';
if ($sourceUrl && strpos($sourceUrl, '://') === false && strpos($sourceUrl, '/') !== 0) {
    $sourceUrl = '/hrms-capstone/' . ltrim($sourceUrl, '/');
}

?>

<style>
.llr-detail-module { padding: 4px 2px 24px; }
.llr-detail-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:14px; }
.llr-detail-header { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.llr-detail-header h2 { margin:0; font-size:1.1rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1.3; }
.llr-detail-meta { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-top:8px; }
.llr-detail-header-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }

.llr-detail-row { display:grid; grid-template-columns:160px 1fr; gap:10px; padding:10px 0; border-bottom:1px solid var(--border,#e4e8ee); font-size:0.85rem; }
.llr-detail-row:last-child { border-bottom:none; }
.llr-detail-label { font-weight:700; color:var(--text-700,#3b4252); }
.llr-detail-value { color:var(--text-900,#1b2430); word-break:break-word; }
.llr-detail-value a { color:var(--info-blue,#3b82c4); text-decoration:none; font-weight:600; }
.llr-detail-value a:hover { text-decoration:underline; }

.llr-detail-section { margin-top:16px; }
.llr-detail-section h4 { margin:0 0 10px; font-size:0.85rem; font-weight:700; color:var(--text-900,#1b2430); text-transform:uppercase; letter-spacing:.3px; }
.llr-detail-section p { margin:0; font-size:0.85rem; color:var(--text-600,#5b6472); line-height:1.6; white-space:pre-wrap; }

.llr-detail-actions { display:flex; gap:10px; margin-top:18px; flex-wrap:wrap; }
.llr-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; color:var(--text-700,#3b4252); font-size:0.82rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all .15s ease; }
.llr-btn:hover { border-color:var(--info-blue,#3b82c4); color:var(--info-blue,#3b82c4); }
.llr-btn-primary { background:var(--info-blue,#3b82c4); border-color:var(--info-blue,#3b82c4); color:#fff; }
.llr-btn-primary:hover { background:#1c5a8a; border-color:#1c5a8a; color:#fff; }

@media (max-width: 768px) {
    .llr-detail-row { grid-template-columns:1fr; gap:4px; }
}
</style>

<section class="llr-detail-module">
    <div class="llr-detail-card">
        <div class="llr-detail-header">
            <div>
                <h2><?= htmlspecialchars($reference['title'] ?? 'Untitled Reference') ?></h2>
                <div class="llr-detail-meta">
                    <span style="font-size:0.78rem; color:var(--text-500,#6b7280);"><?= htmlspecialchars($reference['reference_type'] ?? '') ?></span>
                    <?php if (!empty($reference['reference_number'])): ?>
                        <span style="font-size:0.78rem; color:var(--text-500,#6b7280);"><?= htmlspecialchars($reference['reference_number']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="llr-detail-header-right">
            </div>
        </div>

        <div class="llr-detail-row">
            <div class="llr-detail-label">Short Title</div>
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
                    <i class="bi bi-file-earmark-pdf"></i> View Document
                </a>
            <?php endif; ?>
            <?php if ($sourceUrl): ?>
                <a href="<?= htmlspecialchars($sourceUrl) ?>" target="_blank" rel="noopener noreferrer" class="llr-btn">
                    <i class="bi bi-link-45deg"></i> Official Source
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
