<?php
include_once __DIR__ . '/../../../classes/Employee.php';
include_once __DIR__ . '/../../../classes/certificate.php';
include_once __DIR__ . '/../../../classes/progress.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeId = (int)($_SESSION['employee_id'] ?? 0);
$certificates = [];
$totalCourses = 0;
$completedCourses = 0;

try {
    $pdo = (new Database())->getConnection();
    $certClass = new Certificate($pdo);

    $certificates = $certClass->getByLearner($employeeId);

    // Get total and completed course counts
    $stats = $pdo->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
        FROM ld_enrollment
        WHERE learner_id = :eid
    ");
    $stats->execute([':eid' => $employeeId]);
    $statsRow = $stats->fetch(PDO::FETCH_ASSOC);
    $totalCourses = (int)($statsRow['total'] ?? 0);
    $completedCourses = (int)($statsRow['completed'] ?? 0);

} catch (Throwable $e) {
    DbError::capture($e, 'learner/result-subpage/certificate');
    $certificates = [];
}

// Absolute, shareable verification URL. Built from SCRIPT_NAME because REQUEST_URI
// carries the ?page=… query string, whose slashes corrupt dirname().
$scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
$scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
$verifyUrlBase = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $scriptDir
    . '/index.php?page=public/verify-certificate&code=';
?>
<div class="module-content cert-page">
    <div class="home-dashboard">
        <section class="home-hero" aria-labelledby="cert-title">
            <div class="home-hero-main">
                <h1 id="cert-title">My Certificates</h1>
                <p>View, verify and share the certificates you have earned by completing courses.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-award"></i> <?= count($certificates) ?> earned</span>
                    <span class="home-hero-chip"><i class="fas fa-circle-check"></i> <?= $completedCourses ?> completed</span>
                    <span class="home-hero-chip"><i class="fas fa-hourglass-half"></i> <?= max(0, $totalCourses - $completedCourses) ?> in progress</span>
                </div>
            </div>
        </section>

        <?php if (empty($certificates)): ?>
            <div class="cert-empty">
                <i class="fas fa-award" aria-hidden="true"></i>
                <strong>No certificates yet</strong>
                <p>Complete all lessons and quizzes in your enrolled courses to earn certificates automatically.</p>
                <a class="home-cta" href="?page=learner/catalog" style="margin-top:0.6rem;"><i class="fas fa-graduation-cap" aria-hidden="true"></i> Browse Courses</a>
            </div>
        <?php else: ?>
            <div class="cert-grid">
                <?php foreach ($certificates as $certItem):
                    $isExpired = $certItem['valid_until'] && strtotime($certItem['valid_until']) < time();
                    $statusLabel = $isExpired ? 'Expired' : 'Active';
                    $statusTone = $isExpired ? 'is-danger' : 'is-success';
                    $verifyUrl = $verifyUrlBase . urlencode($certItem['verification_code']);
                    $issuedText = $certItem['issued_at'] ? date('M j, Y', strtotime($certItem['issued_at'])) : 'N/A';
                    $validText = $certItem['valid_until'] ? date('M j, Y', strtotime($certItem['valid_until'])) : 'No expiry';
                ?>
                <article class="cert-card-item is-static">
                    <div class="cert-band">
                        <i class="fas fa-award cert-band-icon" aria-hidden="true"></i>
                        <div class="cert-band-title"><?= htmlspecialchars($certItem['course_title'] ?? 'Course') ?></div>
                        <div class="cert-flags">
                            <span class="cert-flag">Certificate of Completion</span>
                            <span class="cert-flag <?= $statusTone ?>"><?= $statusLabel ?></span>
                        </div>
                    </div>
                    <div class="cert-body">
                        <div class="cert-meta">
                            <div>
                                <span class="cert-meta-label">Issued</span>
                                <span class="cert-meta-value"><?= $issuedText ?></span>
                            </div>
                            <div>
                                <span class="cert-meta-label">Valid Until</span>
                                <span class="cert-meta-value"><?= $validText ?></span>
                            </div>
                        </div>
                        <div class="cert-code"><i class="fas fa-fingerprint" aria-hidden="true"></i><span><?= htmlspecialchars($certItem['verification_code']) ?></span></div>
                        <div class="cert-actions">
                            <a class="cert-btn is-primary" href="?page=learner/result-subpage/certificate-print&amp;certificate_id=<?= (int)$certItem['id'] ?>"><i class="fas fa-external-link-alt" aria-hidden="true"></i> View Certificate</a>
                            <button type="button" class="cert-btn is-icon is-outline" title="Copy verification code" aria-label="Copy verification code" onclick="copyVerifyCode(<?= htmlspecialchars(json_encode((string) $certItem['verification_code']), ENT_QUOTES) ?>)"><i class="fas fa-copy" aria-hidden="true"></i></button>
                            <button type="button" class="cert-btn is-icon is-outline" title="Share certificate" aria-label="Share certificate" onclick="shareCertificate(<?= htmlspecialchars(json_encode((string) $certItem['verification_code']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode((string) ($certItem['course_title'] ?? '')), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($verifyUrl), ENT_QUOTES) ?>)"><i class="fas fa-share-alt" aria-hidden="true"></i></button>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyVerifyCode(code) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(function() {
            showToast('Verification code copied!');
        });
    } else {
        var ta = document.createElement('textarea');
        ta.value = code;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('Verification code copied!');
    }
}

function shareCertificate(code, courseTitle, verifyUrl) {
    var text = 'I completed "' + courseTitle + '"! Verify my certificate: ' + (verifyUrl || code);
    if (navigator.share) {
        navigator.share({ title: 'Course Certificate', text: text });
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('Certificate share link copied to clipboard!');
        });
    }
}

function showToast(msg) {
    var toast = document.createElement('div');
    toast.textContent = msg;
    toast.style.cssText = 'position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);padding:0.75rem 1.5rem;background:var(--primary);color:var(--surface);border-radius:999px;font-weight:700;font-size:0.85rem;z-index:9999;box-shadow:0 8px 20px rgba(32,0,130,0.2);';
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 2500);
}
</script>
