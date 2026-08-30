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
    $certificates = [];
}

function certTimeAgo($dt) {
    if (!$dt) return 'N/A';
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 604800) return floor($d / 86400) . 'd ago';
    return date('M j, Y', strtotime($dt));
}
?>
<div class="module-header">
    <h1 class="module-header-title">My Certificates</h1>
    <p class="module-header-subtitle">View and manage your earned course completion certificates.</p>
</div>

<div class="module-content">
    <!-- Stats Row -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; margin-bottom:2rem;">
        <div style="padding:1.25rem; border-radius:14px; border:1.5px solid rgba(16,185,129,0.2); background:rgba(16,185,129,0.04);">
            <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:#10b981; font-weight:700;">Certificates Earned</div>
            <div style="font-size:2rem; font-weight:800; color:var(--text); margin-top:0.3rem;"><?= count($certificates) ?></div>
        </div>
        <div style="padding:1.25rem; border-radius:14px; border:1.5px solid rgba(59,130,246,0.2); background:rgba(59,130,246,0.04);">
            <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:#3b82f6; font-weight:700;">Courses Completed</div>
            <div style="font-size:2rem; font-weight:800; color:var(--text); margin-top:0.3rem;"><?= $completedCourses ?></div>
        </div>
        <div style="padding:1.25rem; border-radius:14px; border:1.5px solid rgba(245,158,11,0.2); background:rgba(245,158,11,0.04);">
            <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:#f59e0b; font-weight:700;">In Progress</div>
            <div style="font-size:2rem; font-weight:800; color:var(--text); margin-top:0.3rem;"><?= $totalCourses - $completedCourses ?></div>
        </div>
    </div>

    <?php if (empty($certificates)): ?>
        <div class="mode-card" style="text-align:center; padding:3rem;">
            <div style="font-size:3rem; margin-bottom:1rem; opacity:0.3; color:var(--primary);"><i class="fas fa-award"></i></div>
            <h3 style="color:var(--text); margin:0 0 0.5rem;">No Certificates Yet</h3>
            <p style="color:rgba(32,0,130,0.5); max-width:40ch; margin:0 auto;">
                Complete all lessons and quizzes in your enrolled courses to earn certificates automatically.
            </p>
            <a href="?page=learner/catalog" style="display:inline-block; margin-top:1.5rem; padding:0.75rem 1.5rem; background:var(--primary); color:var(--surface); border-radius:999px; font-weight:700; text-decoration:none;">
                Browse Courses
            </a>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(340px, 1fr)); gap:1.25rem;">
            <?php foreach ($certificates as $certItem):
                $isExpired = $certItem['valid_until'] && strtotime($certItem['valid_until']) < time();
                $statusColor = $isExpired ? '#ef4444' : '#10b981';
                $statusLabel = $isExpired ? 'Expired' : 'Active';
                $verifyUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/../../index.php?page=verify-certificate&code=' . urlencode($certItem['verification_code']);
            ?>
                <div class="mode-card" style="padding:0; overflow:hidden; border:2px solid rgba(16,185,129,0.15);">
                    <!-- Certificate Header -->
                    <div style="background:linear-gradient(135deg, rgba(16,185,129,0.12), rgba(52,211,153,0.08)); padding:1.5rem; text-align:center; border-bottom:2px solid rgba(16,185,129,0.15);">
                        <div style="font-size:2.5rem; margin-bottom:0.5rem; color:#10b981;"><i class="fas fa-award"></i></div>
                        <h3 style="margin:0; color:var(--text); font-size:1.1rem;"><?= htmlspecialchars($certItem['course_title'] ?? 'Course') ?></h3>
                        <p style="margin:0.3rem 0 0; color:rgba(32,0,130,0.5); font-size:0.85rem;">Certificate of Completion</p>
                    </div>

                    <!-- Certificate Body -->
                    <div style="padding:1.25rem;">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
                            <div>
                                <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Issued</div>
                                <div style="margin-top:0.2rem; color:var(--text); font-size:0.9rem;">
                                    <?= $certItem['issued_at'] ? date('M j, Y', strtotime($certItem['issued_at'])) : 'N/A' ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Valid Until</div>
                                <div style="margin-top:0.2rem; color:var(--text); font-size:0.9rem;">
                                    <?= $certItem['valid_until'] ? date('M j, Y', strtotime($certItem['valid_until'])) : 'No expiry' ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Status</div>
                                <div style="margin-top:0.2rem;">
                                    <span style="padding:0.2rem 0.6rem; border-radius:999px; font-size:0.72rem; font-weight:700; background:<?= $statusColor ?>15; color:<?= $statusColor ?>;">
                                        <?= $statusLabel ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Verification</div>
                                <div style="margin-top:0.2rem; color:var(--text); font-size:0.75rem; font-family:monospace; word-break:break-all;">
                                    <?= htmlspecialchars($certItem['verification_code']) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; border-top:1px solid rgba(32,0,130,0.08); padding-top:1rem;">
                            <button type="button" onclick="copyVerifyCode('<?= htmlspecialchars($certItem['verification_code']) ?>')" style="flex:1; padding:0.5rem 0.75rem; border:1px solid rgba(32,0,130,0.2); background:transparent; color:var(--primary); border-radius:8px; font-size:0.78rem; font-weight:700; cursor:pointer; white-space:nowrap;">
                                Copy Code
                            </button>
                            <button type="button" onclick="shareCertificate('<?= htmlspecialchars($certItem['verification_code']) ?>', '<?= htmlspecialchars($certItem['course_title'] ?? '') ?>')" style="flex:1; padding:0.5rem 0.75rem; border:none; background:var(--primary); color:var(--surface); border-radius:8px; font-size:0.78rem; font-weight:700; cursor:pointer; white-space:nowrap;">
                                Share
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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

function shareCertificate(code, courseTitle) {
    var text = 'I completed "' + courseTitle + '"! Verify my certificate: ' + code;
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
