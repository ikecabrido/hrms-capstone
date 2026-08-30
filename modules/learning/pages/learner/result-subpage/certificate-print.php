<?php
/**
 * Print-friendly certificate view.
 * Clean, no-sidebar layout for browser printing.
 * Access: ?page=learner/result-subpage/certificate-print&certificate_id=X
 */
session_start();
$learnerId = isset($_SESSION['employee_id']) ? (int)$_SESSION['employee_id'] : 0;
if ($learnerId <= 0) { header('Location: /itsar/modules/learning/index.php'); exit; }

require_once dirname(__DIR__, 6) . '/classes/certificate.php';
require_once dirname(__DIR__, 8) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $cert = new Certificate($pdo);

    $certId = (int)($_GET['certificate_id'] ?? 0);
    if ($certId <= 0) { echo 'Invalid certificate ID'; exit; }

    $stmt = $pdo->prepare("
        SELECT ct.*, c.title AS course_title, c.category,
               CONCAT(e.first_name, ' ', e.last_name) AS learner_name
        FROM ld_certificate ct
        JOIN ld_course c ON c.id = ct.course_id
        JOIN em_employees e ON e.employee_id = ct.learner_id
        WHERE ct.id = :id AND ct.learner_id = :lid
    ");
    $stmt->execute([':id' => $certId, ':lid' => $learnerId]);
    $certificate = $stmt->fetch();

    if (!$certificate) { echo 'Certificate not found'; exit; }

    $issuedDate = date('F j, Y', strtotime($certificate['issued_at']));
    $validUntil = !empty($certificate['valid_until']) ? date('F j, Y', strtotime($certificate['valid_until'])) : 'No Expiration';
} catch (Exception $e) { echo 'Error: ' . $e->getMessage(); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Completion — <?= htmlspecialchars($certificate['course_title']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #200082; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f5f5; display: flex; justify-content: center; padding: 2rem; }
        .certificate { width: 800px; min-height: 560px; background: #fff; border: 3px solid var(--primary); border-radius: 16px; padding: 3rem; text-align: center; position: relative; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .cert-border { position: absolute; inset: 12px; border: 1px solid rgba(32,0,130,0.2); border-radius: 12px; pointer-events: none; }
        .cert-header { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 4px; color: var(--primary); font-weight: 600; margin-bottom: 0.5rem; }
        .cert-title { font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #1a1a2e; margin-bottom: 1.5rem; }
        .cert-body { font-size: 0.95rem; color: #444; line-height: 1.8; margin-bottom: 1.5rem; }
        .cert-name { font-size: 1.8rem; font-weight: 700; color: var(--primary); margin: 0.5rem 0; }
        .cert-course { font-size: 1.1rem; font-weight: 600; color: #1a1a2e; margin: 0.5rem 0; }
        .cert-details { display: flex; justify-content: center; gap: 3rem; margin-top: 1.5rem; font-size: 0.8rem; color: #666; }
        .cert-detail span { display: block; font-weight: 600; color: #1a1a2e; margin-bottom: 0.25rem; }
        .cert-verification { margin-top: 1rem; font-size: 0.75rem; color: #999; }
        @media print {
            body { background: none; padding: 0; }
            .certificate { box-shadow: none; border-width: 2px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="position:fixed;top:1rem;right:1rem;z-index:9999;">
        <button onclick="window.print()" style="padding:0.5rem 1rem;background:var(--primary);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">Print</button>
    </div>
    <div class="certificate">
        <div class="cert-border"></div>
        <div class="cert-header">Certificate of Completion</div>
        <div class="cert-title">Learning &amp; Development</div>
        <div class="cert-body">
            This certifies that
            <div class="cert-name"><?= htmlspecialchars($certificate['learner_name']) ?></div>
            has successfully completed
            <div class="cert-course"><?= htmlspecialchars($certificate['course_title']) ?></div>
        </div>
        <div class="cert-details">
            <div><span>Issued</span><?= $issuedDate ?></div>
            <div><span>Valid Until</span><?= $validUntil ?></div>
            <div><span>Category</span><?= htmlspecialchars($certificate['category'] ?? 'General') ?></div>
        </div>
        <div class="cert-verification">
            Verification Code: <?= htmlspecialchars($certificate['verification_code'] ?? 'N/A') ?>
            | Verify at: <?= htmlspecialchars((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/itsar/modules/learning/index.php?page=public/verify-certificate&code=' . urlencode($certificate['verification_code'] ?? '')) ?>
        </div>
    </div>
</body>
</html>
