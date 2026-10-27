<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 3) . '/database/db.php';
require_once dirname(__DIR__, 3) . '/includes/app-base.php';

$dashboardUrl = AppBase::pathFor('modules/workforce/index.php') . '?page=dashboard-overview';

$certificateId = filter_input(INPUT_GET, 'certificate_id', FILTER_VALIDATE_INT);
$certificate = null;
$error = '';

if (!$certificateId || $certificateId < 1) {
    $error = 'Invalid certificate ID.';
} else {
    try {
        $pdo = (new Database())->getConnection();
        $stmt = $pdo->prepare("
            SELECT cert.*, course.title AS course_title,
                   CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name
            FROM ld_certificate cert
            INNER JOIN ld_course course ON course.id = cert.course_id
            INNER JOIN em_employees emp ON emp.employee_id = cert.learner_id
            WHERE cert.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $certificateId]);
        $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$certificate) {
            $error = 'Certificate not found.';
        }
    } catch (Throwable $exception) {
        error_log('[workforce] Certificate print failed: ' . $exception->getMessage());
        $error = 'Unable to load this certificate.';
    }
}

$isExpired = $certificate && $certificate['valid_until'] !== null
    && $certificate['valid_until'] < date('Y-m-d');
$status = !$certificate ? '' : ($certificate['status'] === 'archived'
    ? 'Archived'
    : ($isExpired ? 'Expired' : 'Active'));
$statusColor = $isExpired ? '#a61b1b' : '#176b4d';
$statusBackground = $isExpired ? '#fce8e8' : '#e5f4ec';
?>
<style>
    .certificate-print-page { max-width:1100px; margin:2rem auto; }
    .certificate-toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
    .certificate-card { position:relative; overflow:hidden; background:#fffdf8; border:1px solid #b79b57; padding:4rem 5rem 3rem; text-align:center; box-shadow:0 12px 35px rgba(32,0,130,.14); }
    .certificate-card::before { content:""; position:absolute; inset:12px; border:3px double #320082; pointer-events:none; }
    .certificate-card::after { content:""; position:absolute; inset:22px; border:1px solid rgba(183,155,87,.7); pointer-events:none; }
    .certificate-content { position:relative; z-index:1; }
    .certificate-logo { width:82px; height:82px; object-fit:contain; margin-bottom:.5rem; }
    .certificate-institution { color:#320082; font-size:.8rem; font-weight:800; letter-spacing:.16em; text-transform:uppercase; }
    .certificate-kicker { color:#a27d2e; font-size:.72rem; font-weight:800; letter-spacing:.3em; text-transform:uppercase; margin-top:2rem; }
    .certificate-card h1 { color:#320082; margin:.65rem 0 .35rem; font-family:Georgia,serif; font-size:2.65rem; letter-spacing:.02em; }
    .certificate-subtitle { color:#6d6478; font-size:.88rem; letter-spacing:.08em; }
    .certificate-presented { color:#555; margin:2rem 0 .65rem; font-style:italic; font-family:Georgia,serif; }
    .certificate-card h2 { color:#1d1730; font-family:Georgia,serif; font-size:2rem; margin:.25rem auto .6rem; padding:0 .75rem .5rem; border-bottom:1px solid #b79b57; max-width:680px; }
    .certificate-course { color:#320082; font-size:1.15rem; font-weight:800; margin:.35rem auto; max-width:720px; }
    .certificate-status { display:inline-block; margin:1rem 0 .25rem; padding:.35rem .95rem; border-radius:999px; background:<?= $statusBackground ?>; color:<?= $statusColor ?>; font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .certificate-meta { display:grid; grid-template-columns:repeat(3, 1fr); gap:1rem; margin:2rem auto 1.75rem; max-width:760px; text-align:center; }
    .certificate-meta div { padding:.7rem .5rem; border-top:1px solid #d8c99f; }
    .certificate-meta small { display:block; color:#8b7650; text-transform:uppercase; font-size:.64rem; font-weight:800; letter-spacing:.1em; margin-bottom:.35rem; }
    .certificate-meta span { color:#2d2636; font-size:.82rem; }
    .certificate-signatures { display:grid; grid-template-columns:1fr 1fr; gap:4rem; max-width:650px; margin:1rem auto 0; }
    .certificate-signature { border-top:1px solid #4a4251; padding-top:.4rem; color:#51495c; font-size:.72rem; }
    .certificate-seal { position:absolute; right:3.2rem; bottom:2.5rem; width:72px; height:72px; border:2px solid #b79b57; border-radius:50%; color:#a27d2e; display:flex; align-items:center; justify-content:center; font-size:.58rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; transform:rotate(-12deg); }
    @media print {
        @page { size:landscape; margin:10mm; }
        .sidebar, header, footer, .certificate-toolbar { display:none !important; }
        .main-content, .container { margin:0 !important; padding:0 !important; max-width:none !important; }
        .certificate-print-page { margin:0; max-width:none; }
        .certificate-card { box-shadow:none; min-height:180mm; }
    }
    @media (max-width: 700px) {
        .certificate-card { padding:3rem 1.5rem 2rem; }
        .certificate-card h1 { font-size:1.8rem; }
        .certificate-card h2 { font-size:1.45rem; }
        .certificate-meta, .certificate-signatures { grid-template-columns:1fr; gap:.75rem; }
        .certificate-seal { display:none; }
    }
</style>

<div class="certificate-print-page">
    <div class="certificate-toolbar">
        <a class="wa-text-link" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Back to dashboard</a>
        <?php if ($certificate): ?>
            <button class="wa-button" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Print certificate</button>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <article class="wa-panel"><p class="wa-empty"><?= htmlspecialchars($error) ?></p></article>
    <?php else: ?>
        <article class="certificate-card">
            <div class="certificate-content">
                <img class="certificate-logo" src="assets/bcp-logo.png" alt="Bestlink College of the Philippines logo">
                <div class="certificate-institution">Bestlink College of the Philippines</div>
                <div class="certificate-kicker">Official recognition</div>
                <h1>Certificate of Completion</h1>
                <div class="certificate-subtitle">This certificate is proudly presented to</div>
                <div class="certificate-presented">In recognition of successful completion by</div>
                <h2><?= htmlspecialchars($certificate['learner_name']) ?></h2>
                <div class="certificate-presented" style="margin:1rem 0 .25rem;">for completing the requirements of</div>
                <div class="certificate-course"><?= htmlspecialchars($certificate['course_title']) ?></div>
                <span class="certificate-status"><?= htmlspecialchars($status) ?></span>
                <div class="certificate-meta">
                    <div><small>Issued date</small><span><?= htmlspecialchars(date('F j, Y', strtotime($certificate['issued_at']))) ?></span></div>
                    <div><small>Expiry date</small><span><?= $certificate['valid_until'] ? htmlspecialchars(date('F j, Y', strtotime($certificate['valid_until']))) : 'No expiry' ?></span></div>
                    <div><small>Verification code</small><span><?= htmlspecialchars($certificate['verification_code']) ?></span></div>
                </div>
                <div class="certificate-signatures">
                    <div class="certificate-signature">Authorized signature</div>
                    <div class="certificate-signature">Bestlink College of the Philippines</div>
                </div>
            </div>
            <div class="certificate-seal">BCP<br>Verified</div>
        </article>
    <?php endif; ?>
</div>
