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

$isExpired = $cert ? ($cert['valid_until'] && strtotime($cert['valid_until']) < time()) : false;
$statusClass = $isExpired ? 'expired' : 'valid';
$statusLabel = $isExpired ? 'Expired' : 'Valid Certificate';
$fullName = $cert ? htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']) : '';
$courseTitle = $cert ? htmlspecialchars($cert['course_title']) : '';
$issuedDate = $cert ? date('F j, Y', strtotime($cert['issued_at'])) : '';
$validUntil = $cert ? ($cert['valid_until'] ? date('F j, Y', strtotime($cert['valid_until'])) : 'No Expiry') : '';
$verifyCode = $cert ? htmlspecialchars($cert['verification_code']) : '';

// CSS colors for PDF
$brandPrimary = '#320082';
$brandGreen = '#10b981';
$brandRed = '#ef4444';
$certBg = $isExpired ? 'rgba(239,68,68,0.1)' : 'rgba(16,185,129,0.1)';
$certColor = $isExpired ? $brandRed : $brandGreen;
?>

<!-- Print & PDF Styles -->
<style>
@media print {
    .sidebar, .topbar, .header, .dark-mode-toggle, .cert-actions, .module-header,
    .cert-toolbar, footer, .nav-badge { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .container { max-width: 100% !important; padding: 0 !important; }
    .cert-print-wrapper { box-shadow: none !important; border-radius: 0 !important; border: none !important; }
    .mode-card { border: none !important; background: #fff !important; }
}

.cert-toolbar {
    max-width: 680px;
    margin: 0 auto 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.75rem 1.25rem;
    background: var(--surface, #fff);
    border: 1px solid var(--border, rgba(186,186,186,0.3));
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(32,0,130,0.04);
}
.cert-toolbar-left {
    font-size: 0.85rem;
    color: var(--muted, #888);
}
.cert-toolbar-left a {
    color: var(--primary, #320082);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.4rem 0.75rem;
    border-radius: 8px;
    transition: all 0.2s;
}
.cert-toolbar-left a:hover {
    background: rgba(32,0,130,0.06);
    text-decoration: none;
}
.cert-actions {
    display: flex;
    gap: 0.5rem;
}
.cert-btn {
    padding: 0.55rem 1.1rem;
    border: none;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s;
    text-decoration: none;
}
.cert-btn-print {
    background: rgba(32,0,130,0.08);
    color: var(--primary, #320082);
}
.cert-btn-print:hover {
    background: rgba(32,0,130,0.15);
}
.cert-btn-pdf {
    background: var(--primary, #320082);
    color: #fff;
    box-shadow: 0 2px 8px rgba(32,0,130,0.3);
}
.cert-btn-pdf:hover {
    background: #250066;
    box-shadow: 0 4px 12px rgba(32,0,130,0.4);
}
.cert-btn-pdf:disabled {
    opacity: 0.6;
    cursor: wait;
}

/* Certificate card for PDF capture */
.cert-print-wrapper {
    max-width: 680px;
    margin: 0 auto;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(32,0,130,0.1);
    background: var(--surface, #fff);
}

.toast-notification {
    position: fixed;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    padding: 0.7rem 1.2rem;
    background: #333;
    color: #fff;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    z-index: 100001;
    opacity: 0;
    transition: all 0.3s ease;
    pointer-events: none;
}
.toast-notification.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
</style>

<div class="module-content">
    <?php if ($error): ?>
        <div class="mode-card" style="text-align:center; padding:3rem; max-width:500px; margin:2rem auto;">
            <div style="font-size:3rem; color:#ef4444; margin-bottom:1rem;"><i class="fas fa-times-circle"></i></div>
            <h2 style="color:var(--text); margin-bottom:0.5rem;">Verification Failed</h2>
            <p style="color:rgba(32,0,130,0.5);"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php else: ?>
        <!-- Toolbar -->
        <div class="cert-toolbar">
            <div class="cert-toolbar-left">
                <a href="index.php?page=learner/catalog" id="certBackBtn"><i class="fas fa-arrow-left" style="margin-right:0.3rem;"></i> Back to Catalog</a>
            </div>
            <div class="cert-actions">
                <button type="button" class="cert-btn cert-btn-print" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <button type="button" class="cert-btn cert-btn-pdf" id="downloadPdfBtn" onclick="downloadPdf();">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        </div>

        <!-- Certificate Card -->
        <div class="cert-print-wrapper" id="certCard">
            <!-- Certificate Header -->
            <div style="background:linear-gradient(135deg, <?= $brandPrimary ?>, #5b21b6); color:#fff; padding:2.5rem 2rem; text-align:center; position:relative; overflow:hidden;">
                <div style="position:absolute; top:-30px; right:-30px; width:120px; height:120px; background:rgba(255,255,255,0.06); border-radius:50%;"></div>
                <div style="position:absolute; bottom:-40px; left:-20px; width:100px; height:100px; background:rgba(255,255,255,0.04); border-radius:50%;"></div>
                <div style="font-size:2.5rem; margin-bottom:0.75rem; position:relative;"><i class="fas fa-award"></i></div>
                <h1 style="font-size:1.6rem; margin:0 0 0.3rem; font-weight:800; letter-spacing:-0.02em; position:relative;">Certificate of Completion</h1>
                <p style="opacity:0.8; font-size:0.9rem; margin:0; position:relative;">Official Course Completion Certificate</p>
            </div>

            <!-- Certificate Body -->
            <div style="padding:2.5rem 2rem; text-align:center; background:var(--surface, #fff);">
                <span style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.4rem 1rem; border-radius:999px; font-size:0.78rem; font-weight:700; margin-bottom:1.5rem; background:<?= $certBg ?>; color:<?= $certColor ?>;">
                    <i class="fas fa-<?= $isExpired ? 'times-circle' : 'check-circle' ?>"></i> <?= $statusLabel ?>
                </span>

                <div style="font-size:1.4rem; font-weight:700; color:<?= $brandPrimary ?>; margin-bottom:0.5rem; line-height:1.3;"><?= $courseTitle ?></div>
                <div style="font-size:1rem; color:var(--muted, #666); margin-bottom:2rem;">Awarded to <strong style="color:var(--text);"><?= $fullName ?></strong></div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem; text-align:left;">
                    <div style="padding:0.8rem 1rem; background:rgba(32,0,130,0.04); border-radius:10px;">
                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary, #320082); font-weight:700; margin-bottom:0.25rem;">Issued</div>
                        <div style="font-size:0.95rem; color:var(--text); font-weight:600;"><?= $issuedDate ?></div>
                    </div>
                    <div style="padding:0.8rem 1rem; background:rgba(32,0,130,0.04); border-radius:10px;">
                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary, #320082); font-weight:700; margin-bottom:0.25rem;">Valid Until</div>
                        <div style="font-size:0.95rem; color:var(--text); font-weight:600;"><?= $validUntil ?></div>
                    </div>
                </div>

                <div style="font-family:monospace; font-size:0.82rem; background:rgba(32,0,130,0.04); padding:0.75rem 1rem; border-radius:10px; border:1px dashed rgba(32,0,130,0.12); color:var(--muted, #888); word-break:break-all; margin-bottom:1.5rem;">
                    <i class="fas fa-fingerprint" style="color:var(--primary, #320082); margin-right:0.3rem;"></i>
                    Verification Code: <strong style="color:var(--text); font-size:0.85rem;"><?= $verifyCode ?></strong>
                </div>

                <p style="font-size:0.8rem; color:var(--muted, #999); margin:0; padding-top:0.5rem; border-top:1px solid var(--border, rgba(186,186,186,0.3));"><i class="fas fa-shield-alt" style="margin-right:0.3rem; color:var(--primary, #320082);"></i> This certificate was automatically issued upon successful course completion.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Toast -->
<div class="toast-notification" id="certToast"></div>

<!-- html2canvas + jsPDF for PDF download -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function showToast(msg) {
    var t = document.getElementById('certToast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function() { t.classList.remove('show'); }, 3000);
}

function downloadPdf() {
    var btn = document.getElementById('downloadPdfBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

    var certCard = document.getElementById('certCard');
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

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download"></i> Download PDF';
        showToast('PDF downloaded successfully');
    }).catch(function(err) {
        console.error('PDF error:', err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download"></i> Download PDF';
        showToast('Failed to generate PDF');
    });
}
</script>
