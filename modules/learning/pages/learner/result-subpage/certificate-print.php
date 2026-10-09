<?php
/**
 * Print-friendly certificate view — matches the verify-certificate.php design.
 * Access: ?page=learner/result-subpage/certificate-print&certificate_id=X
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$learnerId = isset($_SESSION['employee_id']) ? (int)$_SESSION['employee_id'] : 0;
if ($learnerId <= 0) {
    require_once dirname(__DIR__, 5) . '/includes/app-base.php';
    header('Location: ' . AppBase::pathFor('modules/learning/index.php'));
    exit;
}

require_once dirname(__DIR__, 5) . '/modules/learning/classes/certificate.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $cert = new Certificate($pdo);

    $certId = (int)($_GET['certificate_id'] ?? 0);
    if ($certId <= 0) { echo 'Invalid certificate ID'; exit; }

    $stmt = $pdo->prepare("
        SELECT ct.*, c.title AS course_title, c.category,
               CONCAT(e.first_name, ' ', e.last_name) AS learner_name,
               e.first_name, e.last_name
        FROM ld_certificate ct
        JOIN ld_course c ON c.id = ct.course_id
        JOIN em_employees e ON e.employee_id = ct.learner_id
        WHERE ct.id = :id AND ct.learner_id = :lid
    ");
    $stmt->execute([':id' => $certId, ':lid' => $learnerId]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificate) { echo 'Certificate not found'; exit; }

    $issuedDate = date('F j, Y', strtotime($certificate['issued_at']));
    $validUntil = !empty($certificate['valid_until']) ? date('F j, Y', strtotime($certificate['valid_until'])) : 'No Expiry';
    $verifyCode = htmlspecialchars($certificate['verification_code'] ?? 'N/A');
    $fullName = htmlspecialchars($certificate['learner_name']);
    $courseTitle = htmlspecialchars($certificate['course_title']);
    $isExpired = $certificate['valid_until'] && strtotime($certificate['valid_until']) < time();
    $statusClass = $isExpired ? 'expired' : 'valid';
    $statusLabel = $isExpired ? 'Expired' : 'Valid Certificate';
    $brandPrimary = '#320082';
    $certBg = $isExpired ? 'rgba(239,68,68,0.1)' : 'rgba(16,185,129,0.1)';
    $certColor = $isExpired ? '#ef4444' : '#10b981';
} catch (Exception $e) { echo 'Error: ' . $e->getMessage(); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate — <?= $courseTitle ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --primary: #320082; --text: #1a1a2e; --muted: #888; --surface: #fff; --border: rgba(186,186,186,0.3); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f5f5; padding: 2rem; }

        @media print {
            .sidebar, .topbar, .header, .cert-toolbar, footer { display: none !important; }
            body { background: none; padding: 0; }
            .cert-print-wrapper { box-shadow: none !important; border-radius: 0 !important; border: none !important; max-width: 100% !important; }
        }

        .cert-toolbar {
            max-width: 680px; margin: 0 auto 1.25rem;
            display: flex; align-items: center; justify-content: space-between;
            gap: 0.75rem; flex-wrap: wrap;
            padding: 0.75rem 1.25rem;
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; box-shadow: 0 1px 3px rgba(32,0,130,0.04);
        }
        .cert-toolbar-left a {
            color: var(--primary); text-decoration: none; font-weight: 600;
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.4rem 0.75rem; border-radius: 8px; transition: all 0.2s; font-size: 0.85rem;
        }
        .cert-toolbar-left a:hover { background: rgba(32,0,130,0.06); text-decoration: none; }
        .cert-actions { display: flex; gap: 0.5rem; }
        .cert-btn {
            padding: 0.55rem 1.1rem; border: none; border-radius: 8px;
            font-size: 0.82rem; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.2s;
        }
        .cert-btn-print { background: rgba(32,0,130,0.08); color: var(--primary); }
        .cert-btn-print:hover { background: rgba(32,0,130,0.15); }
        .cert-btn-pdf { background: var(--primary); color: #fff; box-shadow: 0 2px 8px rgba(32,0,130,0.3); }
        .cert-btn-pdf:hover { background: #250066; }

        .cert-print-wrapper {
            max-width: 680px; margin: 0 auto; border-radius: 16px;
            overflow: hidden; box-shadow: 0 4px 20px rgba(32,0,130,0.1);
            background: var(--surface);
        }

        .toast-notification {
            position: fixed; bottom: 2rem; left: 50%;
            transform: translateX(-50%) translateY(20px);
            padding: 0.7rem 1.2rem; background: #333; color: #fff;
            border-radius: 8px; font-size: 0.82rem; font-weight: 600;
            z-index: 100001; opacity: 0; transition: all 0.3s ease; pointer-events: none;
        }
        .toast-notification.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body>
    <!-- Toolbar -->
    <div class="cert-toolbar">
        <div class="cert-toolbar-left">
            <?= BackLink::anchor('learner/result-subpage/certificate') ?>
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
        <!-- Header -->
        <div style="background:linear-gradient(135deg, <?= $brandPrimary ?>, #5b21b6); color:#fff; padding:2.5rem 2rem; text-align:center; position:relative; overflow:hidden;">
            <div style="position:absolute; top:-30px; right:-30px; width:120px; height:120px; background:rgba(255,255,255,0.06); border-radius:50%;"></div>
            <div style="position:absolute; bottom:-40px; left:-20px; width:100px; height:100px; background:rgba(255,255,255,0.04); border-radius:50%;"></div>
            <div style="font-size:2.5rem; margin-bottom:0.75rem; position:relative;"><i class="fas fa-award"></i></div>
            <h1 style="font-size:1.6rem; margin:0 0 0.3rem; font-weight:800; letter-spacing:-0.02em; position:relative;">Certificate of Completion</h1>
            <p style="opacity:0.8; font-size:0.9rem; margin:0; position:relative;">Official Course Completion Certificate</p>
        </div>

        <!-- Body -->
        <div style="padding:2.5rem 2rem; text-align:center; background:var(--surface);">
            <span style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.4rem 1rem; border-radius:999px; font-size:0.78rem; font-weight:700; margin-bottom:1.5rem; background:<?= $certBg ?>; color:<?= $certColor ?>;">
                <i class="fas fa-<?= $isExpired ? 'times-circle' : 'check-circle' ?>"></i> <?= $statusLabel ?>
            </span>

            <div style="font-size:1.4rem; font-weight:700; color:<?= $brandPrimary ?>; margin-bottom:0.5rem; line-height:1.3;"><?= $courseTitle ?></div>
            <div style="font-size:1rem; color:var(--muted); margin-bottom:2rem;">Awarded to <strong style="color:var(--text);"><?= $fullName ?></strong></div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem; text-align:left;">
                <div style="padding:0.8rem 1rem; background:rgba(32,0,130,0.04); border-radius:10px;">
                    <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; margin-bottom:0.25rem;">Issued</div>
                    <div style="font-size:0.95rem; color:var(--text); font-weight:600;"><?= $issuedDate ?></div>
                </div>
                <div style="padding:0.8rem 1rem; background:rgba(32,0,130,0.04); border-radius:10px;">
                    <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; margin-bottom:0.25rem;">Valid Until</div>
                    <div style="font-size:0.95rem; color:var(--text); font-weight:600;"><?= $validUntil ?></div>
                </div>
            </div>

            <div style="font-family:monospace; font-size:0.82rem; background:rgba(32,0,130,0.04); padding:0.75rem 1rem; border-radius:10px; border:1px dashed rgba(32,0,130,0.12); color:var(--muted); word-break:break-all; margin-bottom:1.5rem;">
                <i class="fas fa-fingerprint" style="color:var(--primary); margin-right:0.3rem;"></i>
                Verification Code: <strong style="color:var(--text); font-size:0.85rem;"><?= $verifyCode ?></strong>
            </div>

            <p style="font-size:0.8rem; color:#999; margin:0; padding-top:0.5rem; border-top:1px solid var(--border);"><i class="fas fa-shield-alt" style="margin-right:0.3rem; color:var(--primary);"></i> This certificate was automatically issued upon successful course completion.</p>
        </div>
    </div>
</body>

<div class="toast-notification" id="certToast"></div>

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
        scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false
    }).then(function(canvas) {
        var jsPDF = window.jspdf.jsPDF;
        var imgData = canvas.toDataURL('image/png');
        var imgWidth = canvas.width;
        var imgHeight = canvas.height;
        var pdfWidth = 297;
        var pdfHeight = (imgHeight * pdfWidth) / imgWidth;
        var pdf = new jsPDF({
            orientation: pdfHeight > pdfWidth ? 'portrait' : 'landscape',
            unit: 'mm', format: [pdfWidth, pdfHeight + 10]
        });
        pdf.addImage(imgData, 'PNG', 0, 5, pdfWidth, pdfHeight);
        pdf.save('certificate-<?= $verifyCode ?>.pdf');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download"></i> Download PDF';
        showToast('PDF downloaded successfully');
    }).catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download"></i> Download PDF';
        showToast('Failed to generate PDF');
    });
}
</script>
</html>
