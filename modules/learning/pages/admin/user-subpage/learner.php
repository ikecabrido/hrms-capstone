<?php
/**
 * Admin view of a learner's profile.
 *
 * The layout and read-only profile cards come from the shared profile partials
 * so this page matches the profile reached from the sidebar. Admin-only
 * additions are the back link, the compact Learning role control and the
 * activity sections below.
 */
require_once __DIR__ . '/../../../classes/LearningRole.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeId = (int) ($_GET['id'] ?? 0);
$profileViewKind = 'learner';
$profileReadOnly = true;
$profileEmployeeId = $employeeId;
$profileRoleLabel = 'Trainee';

require_once __DIR__ . '/../../shared/includes/profile-data.php';

$profile = $profileData['employee'];
$profileFound = trim((string) ($profile['first_name'] ?? '')) !== ''
    || trim((string) ($profile['last_name'] ?? '')) !== '';
$profileLearningRole = ($pdo && $profileFound) ? LearningRole::forEmployee($pdo, $employeeId) : 'learner';
$profileData['role_label'] = LearningRole::label($profileLearningRole);

$enrollments = [];
$quizAttempts = [];
$certificates = [];
$ktPlans = [];
$ktSuccessors = [];
$learning = [
    'enrolled' => 0,
    'completed' => 0,
    'certificates' => 0,
    'study_seconds' => 0,
    'streak' => 0,
];

$runQuery = static function (PDO $connection, string $sql, array $params = [], string $context = 'admin/user-subpage/learner'): array {
    try {
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('[learning] ' . $context . ' query failed: ' . $e->getMessage());
        return [];
    }
};

if ($pdo && $profileFound) {
    $enrollments = $runQuery($pdo, "
        SELECT e.*, c.title AS course_title, c.category, c.thumbnail_path, c.instructor_id,
               emp.first_name AS inst_first, emp.last_name AS inst_last,
               g.final_score, g.status AS grade_status,
               (SELECT COUNT(*) FROM ld_progress p WHERE p.enrollment_id = e.id AND p.status = 'completed') AS completed_items,
               (SELECT COUNT(*) FROM ld_progress p WHERE p.enrollment_id = e.id) AS total_items
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        LEFT JOIN ld_grade g ON g.learner_id = e.learner_id AND g.course_id = e.course_id
        WHERE e.learner_id = :eid
        ORDER BY e.enrolled_at DESC
    ", [':eid' => $employeeId]);

    $quizAttempts = $runQuery($pdo, "
        SELECT qa.*, q.title AS quiz_title, m.title AS module_title, c.title AS course_title
        FROM ld_quiz_attempt qa
        JOIN ld_quiz q ON q.id = qa.quiz_id
        LEFT JOIN ld_module m ON m.id = q.module_id
        LEFT JOIN ld_course c ON c.id = m.course_id
        WHERE qa.learner_id = :eid
        ORDER BY qa.attempted_at DESC
        LIMIT 20
    ", [':eid' => $employeeId]);

    $certificates = $runQuery($pdo, "
        SELECT cert.*, c.title AS course_title
        FROM ld_certificate cert
        JOIN ld_course c ON c.id = cert.course_id
        WHERE cert.learner_id = :eid
        ORDER BY cert.issued_at DESC
    ", [':eid' => $employeeId]);

    $ktPlans = $runQuery($pdo, "
        SELECT p.id, lp.id AS learning_path_id, p.employee_id, p.successor_id,
               p.start_date, p.end_date, p.status, p.created_at,
               CONCAT(e.first_name, ' ', IFNULL(CONCAT(e.middle_name, ' '), ''), e.last_name) AS employee_name,
               CONCAT(s.first_name, ' ', IFNULL(CONCAT(s.middle_name, ' '), ''), s.last_name) AS successor_name,
               (SELECT COUNT(*) FROM exit_knowledge_transfer_items WHERE plan_id = p.id) AS item_count,
               (SELECT COUNT(*) FROM exit_knowledge_transfer_items WHERE plan_id = p.id AND status = 'completed') AS items_done
        FROM exit_knowledge_transfer_plans p
        LEFT JOIN ld_learning_path lp ON lp.kt_plan_id = p.id
        LEFT JOIN em_employees e ON p.employee_id = e.employee_id
        LEFT JOIN em_employees s ON p.successor_id = s.employee_id
        WHERE p.employee_id = :employee_id OR p.successor_id = :successor_id
        ORDER BY p.created_at DESC
    ", [
        ':employee_id' => $employeeId,
        ':successor_id' => $employeeId,
    ]);

    $ktSuccessors = $runQuery($pdo, "
        SELECT employee_id, CONCAT(first_name, ' ', IFNULL(CONCAT(middle_name, ' '), ''), last_name) AS full_name
        FROM em_employees
        WHERE employment_status = 'Active' AND employee_id <> :eid
        ORDER BY first_name ASC, last_name ASC
    ", [':eid' => $employeeId]);

    $learning['enrolled'] = count(array_filter($enrollments, static function ($row): bool {
        return in_array((string) ($row['status'] ?? ''), ['invited', 'enrolled', 'in_progress'], true);
    }));
    $learning['completed'] = count(array_filter($enrollments, static function ($row): bool {
        return (string) ($row['status'] ?? '') === 'completed';
    }));
    $learning['certificates'] = count(array_filter($certificates, static function ($row): bool {
        return (string) ($row['status'] ?? '') === 'active';
    }));

    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(active_seconds), 0) FROM ld_study_time_log WHERE learner_id = :lid");
        $stmt->execute([':lid' => $employeeId]);
        $learning['study_seconds'] = (int) $stmt->fetchColumn();

        $daysStmt = $pdo->prepare("SELECT DISTINCT DATE(recorded_at) AS d FROM ld_study_time_log WHERE learner_id = :lid ORDER BY d DESC LIMIT 90");
        $daysStmt->execute([':lid' => $employeeId]);
        $days = array_map('strval', $daysStmt->fetchAll(PDO::FETCH_COLUMN));

        if (!empty($days)) {
            $today = new DateTimeImmutable('today');
            $cursor = new DateTimeImmutable($days[0]);
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
    } catch (Throwable $e) {
        error_log('[learning] admin/user-subpage/learner study time query failed: ' . $e->getMessage());
    }
}

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

$gradebookLink = null;
?>
<?php include __DIR__ . '/../../shared/includes/profile-assets.php'; ?>
<div class="module-content">
    <div class="pfl-admin-strip">
        <a class="pfl-admin-back" href="?page=admin/user">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to User Management
        </a>
        <?php if ($profileFound): ?>
            <div class="pfl-admin-role-wrap">
                <?php $roleControlCompact = true; include __DIR__ . '/role-control.php'; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$profileFound): ?>
        <div class="mode-card">
            <h3 class="pfl-section-title"><i class="fas fa-user-slash" aria-hidden="true"></i> Trainee not found</h3>
            <p class="pfl-empty">
                <?= $pdo ? 'The requested trainee could not be found.' : 'Profile data is unavailable while the database connection is down.' ?>
                <a href="?page=admin/user">Go back to User Management</a>
            </p>
        </div>
    <?php else: ?>
        <?php include __DIR__ . '/../../shared/includes/profile-header.php'; ?>
        <?php include __DIR__ . '/../../shared/includes/profile-completion.php'; ?>
        <?php include __DIR__ . '/../../shared/includes/profile-bio.php'; ?>

        <div class="pfl-grid" style="margin-bottom:1.5rem;">
            <?php include __DIR__ . '/../../shared/includes/profile-info.php'; ?>
            <?php include __DIR__ . '/../../shared/includes/profile-contact.php'; ?>
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
                    <div class="pfl-stat-value is-teal"><?= htmlspecialchars($studyTimeLabel, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="pfl-stat-sub"><?= (int) $learning['streak'] ?>-day streak</div>
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/../../shared/includes/profile-learning-activity.php'; ?>
    <?php endif; ?>
</div>
