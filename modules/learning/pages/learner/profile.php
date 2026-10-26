<?php
/**
 * Learner profile. Layout comes from the shared profile partials; this file
 * adds the learning overview and the learner's complete course record.
 */
$profileRoleLabel = 'Trainee';
require_once __DIR__ . '/../shared/includes/profile-data.php';

$learning = [
    'enrolled' => 0,
    'completed' => 0,
    'certificates' => 0,
    'study_seconds' => 0,
    'streak' => 0,
];
$courseHistory = [];
$quizAttempts = [];
$certificates = [];
$learningRecordsUnavailable = false;

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
            "SELECT e.id AS enrollment_id, e.status, e.enrolled_at, e.completed_at,
                    c.id AS course_id, c.title,
                    g.final_score,
                    (SELECT COUNT(*) FROM ld_progress p WHERE p.enrollment_id = e.id AND p.status = 'completed') AS completed_items,
                    (SELECT COUNT(*) FROM ld_progress p WHERE p.enrollment_id = e.id) AS total_items
             FROM ld_enrollment e
             JOIN ld_course c ON c.id = e.course_id
             LEFT JOIN ld_grade g ON g.learner_id = e.learner_id AND g.course_id = e.course_id
             WHERE e.learner_id = :lid
             ORDER BY COALESCE(e.last_accessed_at, e.enrolled_at) DESC"
        );
        $stmt->execute([':lid' => $profileId]);
        $courseHistory = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmt = $pdo->prepare(
            "SELECT qa.score, qa.passed, qa.attempted_at, q.title AS quiz_title, c.title AS course_title
             FROM ld_quiz_attempt qa
             JOIN ld_quiz q ON q.id = qa.quiz_id
             LEFT JOIN ld_module m ON m.id = q.module_id
             LEFT JOIN ld_course c ON c.id = m.course_id
             WHERE qa.learner_id = :lid
             ORDER BY qa.attempted_at DESC"
        );
        $stmt->execute([':lid' => $profileId]);
        $quizAttempts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmt = $pdo->prepare(
            "SELECT cert.*, c.title AS course_title
             FROM ld_certificate cert
             JOIN ld_course c ON c.id = cert.course_id
             WHERE cert.learner_id = :lid
             ORDER BY cert.issued_at DESC"
        );
        $stmt->execute([':lid' => $profileId]);
        $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('[learning] Trainee profile details failed: ' . $e->getMessage());
        $learningRecordsUnavailable = true;
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
<div class="module-content learner-profile-page">
    <?php include __DIR__ . '/../shared/includes/profile-header.php'; ?>
    <?php include __DIR__ . '/../shared/includes/profile-completion.php'; ?>
    <?php include __DIR__ . '/../shared/includes/profile-bio.php'; ?>

    <div class="pfl-grid pfl-personal-grid" style="margin-bottom:1.5rem;">
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

    <div class="mode-card pfl-history-card" style="margin-bottom:1.5rem;">
        <div class="pfl-card-head">
            <h3 class="pfl-section-title" style="margin:0;"><i class="fas fa-book-open" aria-hidden="true"></i> Course History</h3>
            <span class="pfl-count-badge"><?= count($courseHistory) ?></span>
        </div>
        <?php if ($learningRecordsUnavailable): ?>
            <p class="pfl-empty pfl-load-warning" role="status">Some learning records could not be loaded. The information on this page may be incomplete; please refresh or try again later.</p>
        <?php endif; ?>
        <?php if (empty($courseHistory)): ?>
            <div class="pfl-empty-state"><i class="fas fa-book-open" aria-hidden="true"></i><p>No course enrollments have been recorded yet.</p></div>
        <?php else: ?>
            <div class="pfl-list">
                <?php foreach ($courseHistory as $course):
                    $status = strtolower((string) ($course['status'] ?? 'unknown'));
                    $totalItems = (int) ($course['total_items'] ?? 0);
                    $completedItems = (int) ($course['completed_items'] ?? 0);
                    $progress = $totalItems > 0 ? (int) round(($completedItems / $totalItems) * 100) : 0;
                    $statusClass = $status === 'completed' ? 'is-green' : (in_array($status, ['in_progress', 'enrolled'], true) ? 'is-blue' : 'is-amber');
                    $score = $course['final_score'] ?? null;
                    $courseDate = !empty($course['completed_at']) ? $course['completed_at'] : ($course['enrolled_at'] ?? null);
                    $courseTimestamp = $courseDate ? strtotime((string) $courseDate) : false;
                ?>
                    <article class="pfl-list-item">
                        <div class="pfl-list-head">
                            <div>
                                <div class="pfl-list-title"><?= htmlspecialchars((string) $course['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="pfl-list-sub"><?= $status === 'completed' ? 'Completed' : 'Enrolled' ?><?= $courseTimestamp ? ' · ' . htmlspecialchars(date('M j, Y', $courseTimestamp), ENT_QUOTES, 'UTF-8') : '' ?></div>
                            </div>
                            <span class="pfl-badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="pfl-progress-head"><span>Course progress</span><strong><?= $progress ?>%</strong></div>
                        <div class="pfl-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $progress ?>" aria-label="<?= htmlspecialchars((string) $course['title'], ENT_QUOTES, 'UTF-8') ?> progress">
                            <span style="width:<?= $progress ?>%;"></span>
                        </div>
                        <div class="pfl-meta">
                            <span><i class="fas fa-list-check" aria-hidden="true"></i>Items: <?= $completedItems ?>/<?= $totalItems ?></span>
                            <?php if ($score !== null && $score !== ''): ?>
                                <span><i class="fas fa-star" aria-hidden="true"></i>Final score: <?= htmlspecialchars(rtrim(rtrim(number_format((float) $score, 1, '.', ''), '0'), '.') . '%', ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="pfl-grid pfl-record-grid" style="margin-bottom:1.5rem;">
        <section class="mode-card">
            <div class="pfl-card-head">
                <h3 class="pfl-section-title" style="margin:0;"><i class="fas fa-circle-question" aria-hidden="true"></i> Quiz Attempts</h3>
                <span class="pfl-count-badge"><?= count($quizAttempts) ?></span>
            </div>
            <?php if (empty($quizAttempts)): ?>
                <div class="pfl-empty-state"><i class="fas fa-circle-question" aria-hidden="true"></i><p>No quiz attempts have been recorded yet.</p></div>
            <?php else: ?>
                <div class="pfl-table-wrap">
                    <table class="pfl-table">
                        <thead><tr><th>Quiz</th><th>Score</th><th>Result</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($quizAttempts as $attempt):
                                $passed = !empty($attempt['passed']);
                                $attemptTimestamp = !empty($attempt['attempted_at']) ? strtotime((string) $attempt['attempted_at']) : false;
                                $attemptScore = $attempt['score'] !== null ? rtrim(rtrim(number_format((float) $attempt['score'], 1, '.', ''), '0'), '.') . '%' : '—';
                            ?>
                                <tr>
                                    <td>
                                        <div class="pfl-list-title"><?= htmlspecialchars((string) ($attempt['quiz_title'] ?? 'Quiz'), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="pfl-table-sub"><?= htmlspecialchars((string) ($attempt['course_title'] ?? 'Course unavailable'), ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($attemptScore, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="pfl-badge <?= $passed ? 'is-green' : 'is-red' ?>"><?= $passed ? 'Passed' : 'Not passed' ?></span></td>
                                    <td><?= $attemptTimestamp ? htmlspecialchars(date('M j, Y', $attemptTimestamp), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="mode-card">
            <div class="pfl-card-head">
                <h3 class="pfl-section-title" style="margin:0;"><i class="fas fa-award" aria-hidden="true"></i> Certificates</h3>
                <span class="pfl-count-badge"><?= count($certificates) ?></span>
            </div>
            <?php if (empty($certificates)): ?>
                <div class="pfl-empty-state"><i class="fas fa-award" aria-hidden="true"></i><p>No certificates have been earned yet.</p></div>
            <?php else: ?>
                <div class="pfl-cert-grid">
                    <?php foreach ($certificates as $certificate):
                        $issuedTimestamp = !empty($certificate['issued_at']) ? strtotime((string) $certificate['issued_at']) : false;
                    ?>
                        <article class="pfl-cert">
                            <div class="pfl-cert-icon"><i class="fas fa-award" aria-hidden="true"></i></div>
                            <div class="pfl-cert-title"><?= htmlspecialchars((string) ($certificate['course_title'] ?? 'Course certificate'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="pfl-cert-meta">Issued <?= $issuedTimestamp ? htmlspecialchars(date('M j, Y', $issuedTimestamp), ENT_QUOTES, 'UTF-8') : 'date unavailable' ?></div>
                            <?php if (!empty($certificate['verification_code'])): ?>
                                <div class="pfl-cert-code"><?= htmlspecialchars((string) $certificate['verification_code'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="pfl-grid" style="margin-bottom:1.5rem;">
        <?php include __DIR__ . '/../shared/includes/profile-security.php'; ?>
        <?php include __DIR__ . '/../shared/includes/profile-preferences.php'; ?>
    </div>

    <?php include __DIR__ . '/../shared/includes/profile-gradebook.php'; ?>
</div>
<?php include __DIR__ . '/../shared/includes/profile-scripts.php'; ?>
