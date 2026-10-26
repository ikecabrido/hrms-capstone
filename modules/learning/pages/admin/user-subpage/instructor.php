<?php
/**
 * Admin view of an instructor's profile.
 *
 * Shared profile partials provide the same header, bio, personal/contact and
 * grade-book cards used by the sidebar profile. The teaching-specific cards
 * (courses, learners, documents, activity) remain below the shared learning
 * activity sections.
 */
require_once __DIR__ . '/../../../classes/LearningRole.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeId = (int) ($_GET['id'] ?? 0);
$profileViewKind = 'instructor';
$profileReadOnly = true;
$profileEmployeeId = $employeeId;
$profileRoleLabel = 'Trainer';

require_once __DIR__ . '/../../shared/includes/profile-data.php';

$profile = $profileData['employee'];
$profileFound = trim((string) ($profile['first_name'] ?? '')) !== ''
    || trim((string) ($profile['last_name'] ?? '')) !== '';
$profileLearningRole = ($pdo && $profileFound) ? LearningRole::forEmployee($pdo, $employeeId) : 'learner';
$profileData['role_label'] = LearningRole::label($profileLearningRole);

$courses = [];
$learners = [];
$recentActivity = [];
$documents = [];
$documentsAvailable = false;
$enrollments = [];
$quizAttempts = [];
$certificates = [];
$ktPlans = [];
$ktSuccessors = [];
$teaching = ['courses' => 0, 'learners' => 0, 'completed' => 0, 'avg_score' => 0.0, 'avg_rating' => 0.0];
$expertise = [];

$runQuery = static function (PDO $connection, string $sql, array $params = [], string $context = 'admin/user-subpage/instructor'): array {
    try {
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('[learning] ' . $context . ' query failed: ' . $e->getMessage());
        return [];
    }
};

$runScalar = static function (PDO $connection, string $sql, array $params = [], string $context = 'admin/user-subpage/instructor'): mixed {
    try {
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('[learning] ' . $context . ' query failed: ' . $e->getMessage());
        return 0;
    }
};

if ($pdo && $profileFound) {
    $courses = $runQuery($pdo, "
        SELECT c.*,
               COUNT(DISTINCT e.id) AS enrollment_count,
               SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
               ROUND(COALESCE(AVG(g.final_score), 0), 1) AS avg_score
        FROM ld_course c
        LEFT JOIN ld_enrollment e ON e.course_id = c.id
        LEFT JOIN ld_grade g ON g.course_id = c.id
        WHERE c.instructor_id = :eid AND c.status != 'archived'
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ", [':eid' => $employeeId]);

    $learners = $runQuery($pdo, "
        SELECT DISTINCT emp.employee_id, emp.first_name, emp.last_name, emp.email,
               e.status AS enrollment_status, e.enrolled_at, e.last_accessed_at,
               c.title AS course_title, c.id AS course_id
        FROM em_employees emp
        JOIN ld_enrollment e ON e.learner_id = emp.employee_id
        JOIN ld_course c ON c.id = e.course_id
        WHERE c.instructor_id = :eid
        ORDER BY e.enrolled_at DESC
    ", [':eid' => $employeeId]);

    $recentActivity = $runQuery($pdo, "
        SELECT al.*
        FROM ld_audit_log al
        WHERE al.user_id = :eid
        ORDER BY al.created_at DESC
        LIMIT 20
    ", [':eid' => $employeeId]);

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

    $teaching['courses'] = count(array_filter($courses, static function ($row): bool {
        return in_array((string) ($row['status'] ?? ''), ['active', 'draft'], true);
    }));
    $teaching['learners'] = count(array_unique(array_map('intval', array_column($learners, 'employee_id'))));
    $teaching['completed'] = count(array_filter($learners, static function ($row): bool {
        return (string) ($row['enrollment_status'] ?? '') === 'completed';
    }));
    $teaching['avg_score'] = (float) $runScalar($pdo, "
        SELECT ROUND(COALESCE(AVG(g.final_score), 0), 1)
        FROM ld_grade g
        JOIN ld_course c ON c.id = g.course_id
        WHERE c.instructor_id = :eid
    ", [':eid' => $employeeId], 'admin/user-subpage/instructor avg score');
    $teaching['avg_rating'] = (float) $runScalar($pdo, "
        SELECT ROUND(COALESCE(AVG(r.rating), 0), 1)
        FROM ld_rating r
        JOIN ld_course c ON c.id = r.course_id
        WHERE c.instructor_id = :eid
    ", [':eid' => $employeeId], 'admin/user-subpage/instructor avg rating');

    $expertise = $runQuery($pdo, "
        SELECT s.name, COUNT(*) AS course_count
        FROM ld_course_skill cs
        JOIN ld_course c ON c.id = cs.course_id
        JOIN ld_skill s ON s.id = cs.skill_id
        WHERE c.instructor_id = :eid
        GROUP BY s.id, s.name
        ORDER BY course_count DESC, s.name ASC
        LIMIT 8
    ", [':eid' => $employeeId], 'admin/user-subpage/instructor expertise');

    try {
        $tableCheck = $pdo->prepare("SELECT 1 FROM information_schema.tables
                                     WHERE table_schema = DATABASE()
                                       AND table_name = :table_name
                                     LIMIT 1");
        $tableCheck->execute([':table_name' => 'employee_documents']);
        $documentsAvailable = (bool) $tableCheck->fetchColumn();

        if ($documentsAvailable) {
            $documents = $runQuery($pdo, "
                SELECT ed.*, CONCAT(up.first_name, ' ', IFNULL(CONCAT(up.middle_name, ' '), ''), up.last_name) AS uploaded_by_name
                FROM employee_documents ed
                LEFT JOIN em_employees up ON up.employee_id = ed.uploaded_by
                WHERE ed.employee_id = :eid
                ORDER BY ed.created_at DESC
            ", [':eid' => $employeeId], 'admin/user-subpage/instructor documents');
        }
    } catch (Throwable $documentError) {
        error_log('[learning] Trainer document lookup unavailable: ' . $documentError->getMessage());
        $documents = [];
        $documentsAvailable = false;
    }
}

$formatRelative = static function ($value): string {
    if (empty($value)) {
        return 'N/A';
    }
    $ts = strtotime((string) $value);
    if (!$ts) {
        return 'N/A';
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . 'm ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . 'h ago';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . 'd ago';
    }
    return date('M j, Y', $ts);
};

$courseStatusClass = static function (string $status): string {
    if ($status === 'active') {
        return 'is-green';
    }
    if ($status === 'draft') {
        return 'is-amber';
    }
    return 'is-purple';
};

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
            <h3 class="pfl-section-title"><i class="fas fa-user-slash" aria-hidden="true"></i> Trainer not found</h3>
            <p class="pfl-empty">
                <?= $pdo ? 'The requested trainer could not be found.' : 'Profile data is unavailable while the database connection is down.' ?>
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
            <h3 class="pfl-section-title"><i class="fas fa-chalkboard-user" aria-hidden="true"></i> Teaching Overview</h3>
            <div class="pfl-stats">
                <div class="pfl-stat">
                    <div class="pfl-stat-label"><i class="fas fa-book" aria-hidden="true"></i> Courses</div>
                    <div class="pfl-stat-value"><?= (int) $teaching['courses'] ?></div>
                    <div class="pfl-stat-sub">created</div>
                </div>
                <div class="pfl-stat">
                    <div class="pfl-stat-label"><i class="fas fa-users" aria-hidden="true"></i> Trainees</div>
                    <div class="pfl-stat-value is-teal"><?= (int) $teaching['learners'] ?></div>
                    <div class="pfl-stat-sub"><?= (int) $teaching['completed'] ?> course<?= (int) $teaching['completed'] !== 1 ? 's' : '' ?> completed</div>
                </div>
                <div class="pfl-stat">
                    <div class="pfl-stat-label"><i class="fas fa-star" aria-hidden="true"></i> Avg Score</div>
                    <div class="pfl-stat-value is-green"><?= number_format((float) $teaching['avg_score'], 1) ?>%</div>
                    <div class="pfl-stat-sub">student average</div>
                </div>
                <div class="pfl-stat">
                    <div class="pfl-stat-label"><i class="fas fa-thumbs-up" aria-hidden="true"></i> Avg Rating</div>
                    <div class="pfl-stat-value is-amber"><?= number_format((float) $teaching['avg_rating'], 1) ?><span style="font-size:1rem;">/5</span></div>
                    <div class="pfl-stat-sub">from course reviews</div>
                </div>
            </div>

            <div style="margin-top:1.25rem; padding-top:1.1rem; border-top:1px solid rgba(0,0,0,0.06);">
                <div class="pfl-stat-label" style="margin-bottom:0.6rem;"><i class="fas fa-lightbulb" aria-hidden="true"></i> Areas of Expertise</div>
                <?php if (empty($expertise)): ?>
                    <p class="pfl-empty">Tag courses with skills to build this trainer's expertise profile.</p>
                <?php else: ?>
                    <div class="pfl-chips">
                        <?php foreach ($expertise as $skill): ?>
                            <span class="pfl-chip"><?= htmlspecialchars((string) $skill['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mode-card" style="margin-bottom:1.5rem;">
            <div class="pfl-card-head">
                <h3 class="pfl-section-title" style="margin:0;">
                    <i class="fas fa-chalkboard" aria-hidden="true"></i> Courses Taught
                </h3>
                <span class="pfl-count-badge"><?= count($courses) ?></span>
            </div>
            <?php if (empty($courses)): ?>
                <div class="pfl-empty-state">
                    <i class="fas fa-chalkboard" aria-hidden="true"></i>
                    <p>No courses are assigned to this trainer yet.</p>
                </div>
            <?php else: ?>
                <div class="pfl-list">
                    <?php foreach ($courses as $course):
                        $courseStatus = strtolower((string) ($course['status'] ?? ''));
                    ?>
                        <div class="pfl-list-item">
                            <div class="pfl-list-head">
                                <div>
                                    <div class="pfl-list-title"><?= htmlspecialchars((string) ($course['title'] ?? 'Untitled course'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="pfl-list-sub"><?= htmlspecialchars((string) (($course['category'] ?? '') !== '' ? $course['category'] : 'Uncategorized'), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <span class="pfl-badge <?= $courseStatusClass($courseStatus) ?>"><?= htmlspecialchars(ucfirst($courseStatus !== '' ? $courseStatus : 'unknown'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="pfl-meta">
                                <span><i class="fas fa-user-plus" aria-hidden="true"></i>Enrolled: <strong><?= (int) ($course['enrollment_count'] ?? 0) ?></strong></span>
                                <span><i class="fas fa-check" aria-hidden="true"></i>Completed: <strong><?= (int) ($course['completed_count'] ?? 0) ?></strong></span>
                                <span><i class="fas fa-star" aria-hidden="true"></i>Avg score: <strong><?= number_format((float) ($course['avg_score'] ?? 0), 1) ?>%</strong></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mode-card" style="margin-bottom:1.5rem;">
            <div class="pfl-card-head">
                <h3 class="pfl-section-title" style="margin:0;">
                    <i class="fas fa-users" aria-hidden="true"></i> Assigned Trainees
                </h3>
                <span class="pfl-count-badge"><?= count($learners) ?></span>
            </div>
            <?php if (empty($learners)): ?>
                <div class="pfl-empty-state">
                    <i class="fas fa-users" aria-hidden="true"></i>
                    <p>No trainees are enrolled in this trainer's courses yet.</p>
                </div>
            <?php else: ?>
                <div class="pfl-person-grid">
                    <?php foreach ($learners as $learner):
                        $learnerInitials = strtoupper(substr((string) ($learner['first_name'] ?? ''), 0, 1) . substr((string) ($learner['last_name'] ?? ''), 0, 1));
                        $learnerName = trim((string) ($learner['first_name'] ?? '') . ' ' . (string) ($learner['last_name'] ?? ''));
                        $enrollmentStatus = strtolower((string) ($learner['enrollment_status'] ?? ''));
                        $enrollmentClass = $enrollmentStatus === 'completed' ? 'is-green' : ($enrollmentStatus === 'in_progress' ? 'is-blue' : 'is-amber');
                    ?>
                        <div class="pfl-person">
                            <div class="pfl-person-avatar"><?= htmlspecialchars($learnerInitials !== '' ? $learnerInitials : '??', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="pfl-person-main">
                                <div class="pfl-person-name"><a href="<?= htmlspecialchars(LearningProfileLink::url((int) $learner['employee_id'], 'learner'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($learnerName !== '' ? $learnerName : 'Unknown trainee', ENT_QUOTES, 'UTF-8') ?></a></div>
                                <div class="pfl-person-sub"><?= htmlspecialchars((string) ($learner['course_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <span class="pfl-badge <?= $enrollmentClass ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $enrollmentStatus !== '' ? $enrollmentStatus : 'unknown')), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php include __DIR__ . '/../../shared/includes/profile-learning-activity.php'; ?>

        <div class="mode-card" style="margin-bottom:1.5rem;">
            <div class="pfl-card-head">
                <h3 class="pfl-section-title" style="margin:0;">
                    <i class="fas fa-folder" aria-hidden="true"></i> Documents
                </h3>
                <?php if ($documentsAvailable): ?>
                    <a class="pfl-link-btn" href="?page=admin/user-subpage/documents&amp;id=<?= $employeeId ?>">
                        <i class="fas fa-upload" aria-hidden="true"></i> Manage Documents
                    </a>
                <?php endif; ?>
                <span class="pfl-count-badge"><?= count($documents) ?></span>
            </div>
            <?php if (!$documentsAvailable): ?>
                <div class="pfl-empty-state">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                    <p>Document storage is not configured in this database.</p>
                </div>
            <?php elseif (empty($documents)): ?>
                <div class="pfl-empty-state">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                    <p>No documents uploaded yet.</p>
                </div>
            <?php else: ?>
                <div class="pfl-table-wrap">
                    <table class="pfl-table">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Uploaded</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $document):
                                $documentType = strtolower((string) ($document['document_type'] ?? ''));
                                $documentIcon = 'fa-file';
                                $documentColor = 'rgba(32,0,130,0.5)';
                                if (in_array($documentType, ['resume/cv', 'resume', 'cv'], true)) {
                                    $documentIcon = 'fa-file-lines';
                                    $documentColor = '#6366f1';
                                } elseif ($documentType === 'contract') {
                                    $documentIcon = 'fa-file-signature';
                                    $documentColor = '#f59e0b';
                                } elseif (in_array($documentType, ['certificate', 'certification'], true)) {
                                    $documentIcon = 'fa-certificate';
                                    $documentColor = '#10b981';
                                } elseif (in_array($documentType, ['identification', 'id'], true)) {
                                    $documentIcon = 'fa-id-card';
                                    $documentColor = '#3b82f6';
                                } elseif ($documentType === 'medical') {
                                    $documentIcon = 'fa-heart-pulse';
                                    $documentColor = '#ef4444';
                                } elseif ($documentType === 'legal') {
                                    $documentIcon = 'fa-scale-balanced';
                                    $documentColor = '#8b5cf6';
                                }
                                $documentCreated = !empty($document['created_at']) ? strtotime((string) $document['created_at']) : false;
                            ?>
                                <tr>
                                    <td>
                                        <div class="pfl-doc-cell">
                                            <div class="pfl-doc-icon">
                                                <i class="fas <?= htmlspecialchars($documentIcon, ENT_QUOTES, 'UTF-8') ?>" style="color:<?= htmlspecialchars($documentColor, ENT_QUOTES, 'UTF-8') ?>;font-size:0.85rem;" aria-hidden="true"></i>
                                            </div>
                                            <div style="min-width:0;">
                                                <div class="pfl-doc-name"><?= htmlspecialchars((string) ($document['document_name'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="pfl-doc-file"><?= htmlspecialchars((string) ($document['file_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="pfl-badge"><?= htmlspecialchars((string) ($document['document_type'] ?? 'Other'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars((string) ($document['category'] ?? 'Other'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $documentCreated ? htmlspecialchars(date('M j, Y', $documentCreated), ENT_QUOTES, 'UTF-8') : 'N/A' ?></td>
                                    <td style="text-align:center;">
                                        <a class="pfl-link-btn" href="pages/admin/ajax/download-document.php?doc_id=<?= (int) ($document['document_id'] ?? 0) ?>" download="<?= htmlspecialchars((string) ($document['file_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-download" aria-hidden="true"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="mode-card">
            <h3 class="pfl-section-title"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i> Recent Activity</h3>
            <?php if (empty($recentActivity)): ?>
                <p class="pfl-empty">No recent activity recorded.</p>
            <?php else: ?>
                <div>
                    <?php foreach ($recentActivity as $activity):
                        $activityLabel = (string) ($activity['action'] ?? 'Activity');
                        $activityDetails = trim((string) ($activity['details'] ?? ''));
                        if ($activityDetails === '' && !empty($activity['item_type'])) {
                            $activityDetails = (string) $activity['item_type'] . ' #' . (string) ($activity['reference_id'] ?? '');
                        }
                    ?>
                        <div class="pfl-activity">
                            <span class="pfl-activity-dot" style="background:var(--primary);"></span>
                            <div class="pfl-activity-main">
                                <span class="pfl-activity-title"><?= htmlspecialchars($activityLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="pfl-activity-date">
                                    <?php if ($activityDetails !== ''): ?><?= htmlspecialchars($activityDetails, ENT_QUOTES, 'UTF-8') ?> &bull; <?php endif; ?><?= htmlspecialchars($formatRelative($activity['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
