<?php
/**
 * Learner profile. Layout comes from the shared profile partials; this file
 * adds the learning stats and recent activity feed.
 */
$profileRoleLabel = 'Learner';
require_once __DIR__ . '/../shared/includes/profile-data.php';

$learning = [
    'enrolled' => 0,
    'completed' => 0,
    'certificates' => 0,
    'study_seconds' => 0,
    'streak' => 0,
];
$recentActivity = [];

if ($pdo && $profileId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid AND status IN ('invited','enrolled','in_progress')");
        $stmt->execute([':lid' => $profileId]);
        $learning['enrolled'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid AND status = 'completed'");
        $stmt->execute([':lid' => $profileId]);
        $learning['completed'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_certificate WHERE learner_id = :lid AND status = 'active'");
        $stmt->execute([':lid' => $profileId]);
        $learning['certificates'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(active_seconds), 0) FROM ld_study_time_log WHERE learner_id = :lid");
        $stmt->execute([':lid' => $profileId]);
        $learning['study_seconds'] = (int) $stmt->fetchColumn();

        // Current streak: consecutive days (ending today or yesterday) with logged study time.
        $daysStmt = $pdo->prepare("SELECT DISTINCT DATE(recorded_at) AS d FROM ld_study_time_log WHERE learner_id = :lid ORDER BY d DESC LIMIT 90");
        $daysStmt->execute([':lid' => $profileId]);
        $days = array_map('strval', $daysStmt->fetchAll(PDO::FETCH_COLUMN));

        if (!empty($days)) {
            $today = new DateTimeImmutable('today');
            $cursor = new DateTimeImmutable($days[0]);
            // Allow the streak to be "alive" if the last activity was today or yesterday.
            if ($cursor->diff($today)->days <= 1) {
                $streak = 0;
                foreach ($days as $day) {
                    $current = new DateTimeImmutable($day);
                    if ($current->format('Y-m-d') === $cursor->format('Y-m-d')) {
                        $streak++;
                        $cursor = $cursor->modify('-1 day');
                    } elseif ($current < $cursor) {
                        break;
                    }
                }
                $learning['streak'] = $streak;
            }
        }

        $stmt = $pdo->prepare(
            "SELECT e.status, e.enrolled_at, c.title
             FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id
             WHERE e.learner_id = :lid
             ORDER BY e.last_accessed_at DESC
             LIMIT 5"
        );
        $stmt->execute([':lid' => $profileId]);
        $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        // keep defaults
    }
}

// Human-friendly study time for the stat tile.
$seconds = (int) $learning['study_seconds'];
if ($seconds >= 3600) {
    $studyTimeLabel = floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
} elseif ($seconds >= 60) {
    $studyTimeLabel = floor($seconds / 60) . 'm';
} elseif ($seconds > 0) {
    $studyTimeLabel = $seconds . 's';
} else {
    $studyTimeLabel = '0m';
}

$gradebookLink = [
    'href' => '?page=learner/skill-gap',
    'page' => 'learner/skill-gap',
    'label' => 'Open full skill gap analysis',
];
?>
<?php include __DIR__ . '/../shared/includes/profile-assets.php'; ?>
<div class="module-content">
    <?php include __DIR__ . '/../shared/includes/profile-header.php'; ?>
    <?php include __DIR__ . '/../shared/includes/profile-completion.php'; ?>
    <?php include __DIR__ . '/../shared/includes/profile-bio.php'; ?>

    <div class="pfl-grid" style="margin-bottom:1.5rem;">
        <?php include __DIR__ . '/../shared/includes/profile-info.php'; ?>
        <?php include __DIR__ . '/../shared/includes/profile-contact.php'; ?>
    </div>

    <div class="mode-card" style="margin-bottom:1.5rem;">
        <h3 class="pfl-section-title"><i class="fas fa-graduation-cap" aria-hidden="true"></i> Learning Overview</h3>
        <div class="pfl-stats">
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-book-open" aria-hidden="true"></i> Enrolled</div>
                <div class="pfl-stat-value"><?= (int) $learning['enrolled'] ?></div>
                <div class="pfl-stat-sub">active enrollments</div>
            </div>
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-check-circle" aria-hidden="true"></i> Completed</div>
                <div class="pfl-stat-value is-green"><?= (int) $learning['completed'] ?></div>
                <div class="pfl-stat-sub">courses finished</div>
            </div>
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-certificate" aria-hidden="true"></i> Certificates</div>
                <div class="pfl-stat-value is-amber"><?= (int) $learning['certificates'] ?></div>
                <div class="pfl-stat-sub">earned</div>
            </div>
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-clock" aria-hidden="true"></i> Study Time</div>
                <div class="pfl-stat-value is-teal"><?= htmlspecialchars($studyTimeLabel) ?></div>
                <div class="pfl-stat-sub"><?= (int) $learning['streak'] ?>-day streak</div>
            </div>
        </div>
    </div>

    <div class="pfl-grid" style="margin-bottom:1.5rem;">
        <div class="mode-card">
            <h3 class="pfl-section-title"><i class="fas fa-history" aria-hidden="true"></i> Recent Activity</h3>
            <div>
                <?php if (empty($recentActivity)): ?>
                    <p class="pfl-empty" style="padding:1rem 0;">No activity yet.</p>
                <?php else: ?>
                    <?php foreach ($recentActivity as $act):
                        $statusColor = $act['status'] === 'completed' ? '#059669' : ($act['status'] === 'in_progress' ? '#d97706' : '#0d9488');
                        $enrolledTs = !empty($act['enrolled_at']) ? strtotime($act['enrolled_at']) : false;
                    ?>
                        <div class="pfl-activity">
                            <span class="pfl-activity-dot" style="background:<?= $statusColor ?>;"></span>
                            <div class="pfl-activity-main">
                                <span class="pfl-activity-title"><?= htmlspecialchars($act['title']) ?></span>
                                <span class="pfl-activity-date"><?= $enrolledTs !== false ? htmlspecialchars(date('M j', $enrolledTs)) : '—' ?></span>
                            </div>
                            <span class="pfl-status" style="background:<?= $statusColor ?>1f; color:<?= $statusColor ?>;"><?= ucfirst(str_replace('_', ' ', $act['status'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php include __DIR__ . '/../shared/includes/profile-security.php'; ?>
    </div>

    <?php include __DIR__ . '/../shared/includes/profile-gradebook.php'; ?>

    <?php include __DIR__ . '/../shared/includes/profile-preferences.php'; ?>
</div>
<?php include __DIR__ . '/../shared/includes/profile-scripts.php'; ?>
