<?php
require_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);
$profileEmployeeId = isset($_GET['id']) && is_scalar($_GET['id']) && ctype_digit((string) $_GET['id'])
    ? (int) $_GET['id']
    : 0;
$profileAuthorized = false;
$profileLoadFailed = false;
$courseActivity = [];

try {
    $pdo = (new Database())->getConnection();
    if ($instructorId > 0 && $profileEmployeeId > 0) {
        $accessCheck = $pdo->prepare(
            "SELECT 1
             FROM ld_enrollment e
             JOIN ld_course c ON c.id = e.course_id
             WHERE c.instructor_id = :instructor_id
               AND e.learner_id = :learner_id
               AND e.status != 'archived'
             LIMIT 1"
        );
        $accessCheck->execute([
            ':instructor_id' => $instructorId,
            ':learner_id' => $profileEmployeeId,
        ]);
        $profileAuthorized = (bool) $accessCheck->fetchColumn();
        if (!$profileAuthorized) {
            $requestCheck = $pdo->prepare(
                "SELECT 1 FROM ld_training_requests
                 WHERE instructor_id = :instructor_id AND learner_id = :learner_id
                 LIMIT 1"
            );
            $requestCheck->execute([
                ':instructor_id' => $instructorId,
                ':learner_id' => $profileEmployeeId,
            ]);
            $profileAuthorized = (bool) $requestCheck->fetchColumn();
        }
    }
} catch (Throwable $e) {
    error_log('[learning] Trainer trainee profile lookup failed: ' . $e->getMessage());
    $profileLoadFailed = true;
}

if ($profileAuthorized) {
    $profileRoleLabel = 'Trainee';
    require __DIR__ . '/../shared/includes/profile-data.php';

    try {
        $activityQuery = $pdo->prepare(
            "SELECT c.id AS course_id, c.title AS course_title, e.status, e.enrolled_at,
                    (SELECT COUNT(*) FROM ld_progress p WHERE p.enrollment_id = e.id) AS total_items,
                    (SELECT COUNT(*) FROM ld_progress p WHERE p.enrollment_id = e.id AND p.status = 'completed') AS completed_items
             FROM ld_enrollment e
             JOIN ld_course c ON c.id = e.course_id
             WHERE c.instructor_id = :instructor_id
               AND e.learner_id = :learner_id
               AND e.status != 'archived'
             ORDER BY e.enrolled_at DESC, c.title ASC"
        );
        $activityQuery->execute([
            ':instructor_id' => $instructorId,
            ':learner_id' => $profileEmployeeId,
        ]);
        $courseActivity = $activityQuery->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('[learning] Trainer trainee profile activity lookup failed: ' . $e->getMessage());
        $profileLoadFailed = true;
    }
}
?>
<?php if ($profileAuthorized): ?>
    <?php include __DIR__ . '/../shared/includes/profile-assets.php'; ?>
    <div class="module-content">
        <div style="margin-bottom:1rem;">
            <a href="?page=instructor/gradebook" class="pfl-admin-back">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Grade Book
            </a>
        </div>
        <?php $profileReadOnly = true; include __DIR__ . '/../shared/includes/profile-header.php'; ?>
        <?php include __DIR__ . '/../shared/includes/profile-bio.php'; ?>

        <div class="mode-card" style="margin-top:1.5rem;">
            <h2 class="pfl-section-title"><i class="fas fa-book-open" aria-hidden="true"></i> Course activity</h2>
            <?php if ($profileLoadFailed && empty($courseActivity)): ?>
                <p class="pfl-empty">Course activity is temporarily unavailable.</p>
            <?php elseif (empty($courseActivity)): ?>
                <p class="pfl-empty">No course activity is available.</p>
            <?php else: ?>
                <div class="an-table-wrap">
                    <table class="an-table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Content completed</th>
                                <th>Enrolled</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courseActivity as $activity):
                                $totalItems = (int) $activity['total_items'];
                                $completedItems = (int) $activity['completed_items'];
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $activity['course_title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $activity['status'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $completedItems ?> / <?= $totalItems ?></td>
                                    <td><?= $activity['enrolled_at'] ? htmlspecialchars(date('M j, Y', strtotime((string) $activity['enrolled_at'])), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="module-content">
        <div class="mode-card">
            <h2 class="pfl-section-title"><i class="fas fa-user-slash" aria-hidden="true"></i> Trainee profile unavailable</h2>
            <p class="pfl-empty">
                <?= $profileLoadFailed
                    ? 'The trainee profile could not be loaded. Please try again later.'
                    : 'This trainee is not enrolled in one of your courses, or the profile could not be found.' ?>
                <a href="?page=instructor/gradebook">Return to Grade Book</a>
            </p>
        </div>
    </div>
<?php endif; ?>
