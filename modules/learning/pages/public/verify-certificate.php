<?php
/**
 * Public certificate verification page.
 * Supports printing and PDF download.
 */
$code = trim($_GET['code'] ?? '');
$cert = null;
$error = '';

if ($code === '') {
    $error = 'No verification code provided.';
} else {
    try {
        require_once dirname(__DIR__, 2) . '/classes/certificate.php';
        require_once dirname(__DIR__, 4) . '/database/db.php';

        $pdo = (new Database())->getConnection();

        $stmt = $pdo->prepare("
            SELECT c.*, co.title AS course_title,
                   emp.first_name, emp.last_name
            FROM ld_certificate c
            JOIN ld_course co ON co.id = c.course_id
            JOIN em_employees emp ON emp.employee_id = c.learner_id
            WHERE c.verification_code = :code AND c.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([':code' => $code]);
        $cert = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cert) {
            $error = 'Certificate not found or has been revoked.';
        }
    } catch (Throwable $e) {
        $error = 'Unable to verify certificate.';
    }
}

// Where the visitor arrived from, so the back link returns them to the list they
// were browsing instead of always dropping them on the learner catalog. Only the
// destinations known to BackLink are accepted; anything else falls back to Catalog,
// which is also what a direct or shared visit gets.
$backPage = BackLink::resolve('learner/catalog', $_GET['back'] ?? '');
$backUrl = BackLink::url($backPage);
$backLabel = BackLink::label($backPage);

$isExpired = $cert ? ($cert['valid_until'] && strtotime($cert['valid_until']) < time()) : false;
$statusLabel = $isExpired ? 'Expired' : 'Valid Certificate';
$fullName = $cert ? htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']) : '';
$courseTitle = $cert ? htmlspecialchars($cert['course_title']) : '';
$issuedDate = $cert ? date('F j, Y', strtotime($cert['issued_at'])) : '';
$validUntil = $cert ? ($cert['valid_until'] ? date('F j, Y', strtotime($cert['valid_until'])) : 'No Expiry') : '';
$verifyCode = $cert ? htmlspecialchars($cert['verification_code']) : '';
?>

<div class="module-content cv-page">
    <?php if ($error): ?>
        <div class="cv-status-card">
            <div class="cv-status-icon"><i class="fas fa-times-circle" aria-hidden="true"></i></div>
            <h2>Verification Failed</h2>
            <p><?= htmlspecialchars($error) ?></p>
            <a class="cv-btn is-ghost" href="<?= htmlspecialchars($backUrl) ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i> <?= htmlspecialchars($backLabel) ?></a>
        </div>
    <?php else: ?>
        <!-- Action bar -->
        <div class="cv-bar">
            <a class="cv-back" id="certBackBtn" href="<?= htmlspecialchars($backUrl) ?>">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <span><?= htmlspecialchars($backLabel) ?></span>
            </a>
            <div class="cv-bar-actions">
                <button type="button" class="cv-btn is-ghost" onclick="window.print();">
                    <i class="fas fa-print" aria-hidden="true"></i> Print
                </button>
                <button type="button" class="cv-btn is-solid" id="downloadPdfBtn" onclick="downloadPdf();">
                    <i class="fas fa-download" aria-hidden="true"></i> Download PDF
                </button>
            </div>
        </div>

        <!-- The certificate (html2canvas target for the PDF) -->
        <article class="cv-card" id="certCard">
            <div class="cv-card-top">
                <span class="cv-seal"><i class="fas fa-award" aria-hidden="true"></i></span>
                <h1>Certificate of Completion</h1>
                <p>Official Course Completion Certificate</p>
            </div>

            <div class="cv-card-body">
                <span class="cv-pill<?= $isExpired ? ' is-expired' : '' ?>">
                    <i class="fas fa-<?= $isExpired ? 'times-circle' : 'check-circle' ?>" aria-hidden="true"></i> <?= htmlspecialchars($statusLabel) ?>
                </span>

                <h2 class="cv-course"><?= $courseTitle ?></h2>
                <p class="cv-awarded">Awarded to <strong><?= $fullName ?></strong></p>

                <div class="cv-meta">
                    <div class="cv-meta-item">
                        <span>Issued</span>
                        <strong><?= $issuedDate ?></strong>
                    </div>
                    <div class="cv-meta-item">
                        <span>Valid Until</span>
                        <strong><?= $validUntil ?></strong>
                    </div>
                </div>

                <div class="cv-code">
                    <i class="fas fa-fingerprint" aria-hidden="true"></i>
                    <span>Verification Code: <strong><?= $verifyCode ?></strong></span>
                </div>

                <p class="cv-note"><i class="fas fa-shield-alt" aria-hidden="true"></i> This certificate was automatically issued upon successful course completion.</p>
            </div>
        </article>
    <?php endif; ?>

    <!-- Toast -->
    <div class="cv-toast" id="certToast" role="status" aria-live="polite"></div>
</div>

<!-- html2canvas + jsPDF for PDF download -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function showToast(msg) {
    var t = document.getElementById('certToast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function() { t.classList.remove('show'); }, 3000);
}

function resetPdfButton() {
    var btn = document.getElementById('downloadPdfBtn');
    if (!btn) return;
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-download" aria-hidden="true"></i> Download PDF';
}

function downloadPdf() {
    var certCard = document.getElementById('certCard');
    var btn = document.getElementById('downloadPdfBtn');

    if (!certCard || typeof html2canvas !== 'function') {
        showToast('PDF generation is unavailable');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Generating...';

    // Drop the page-level border/shadow so the captured sheet is clean.
    certCard.classList.add('is-capturing');

    html2canvas(certCard, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff',
        logging: false
    }).then(function(canvas) {
        var jsPDF = window.jspdf.jsPDF;
        var imgData = canvas.toDataURL('image/png');
        var imgWidth = canvas.width;
        var imgHeight = canvas.height;

        // A4 landscape-ish proportions
        var pdfWidth = 297; // mm (A4 width landscape)
        var pdfHeight = (imgHeight * pdfWidth) / imgWidth;

        var pdf = new jsPDF({
            orientation: pdfHeight > pdfWidth ? 'portrait' : 'landscape',
            unit: 'mm',
            format: [pdfWidth, pdfHeight + 10]
        });

        pdf.addImage(imgData, 'PNG', 0, 5, pdfWidth, pdfHeight);
        pdf.save('certificate-<?= $verifyCode ?>.pdf');

        showToast('PDF downloaded successfully');
    }).catch(function(err) {
        console.error('PDF error:', err);
        showToast('Failed to generate PDF');
    }).then(function() {
        certCard.classList.remove('is-capturing');
        resetPdfButton();
    });
}
</script>
