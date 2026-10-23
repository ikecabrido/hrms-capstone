<?php
require_once dirname(__DIR__, 4) . '/database/db.php';
require_once __DIR__ . '/../../classes/CSRF.php';

$pdo = (new Database())->getConnection();
$inboxError = '';
$inboxMessage = '';
$reviewTypes = [
    'competency' => [
        'label' => 'Competency requirement',
        'table' => 'ld_competency_requirement',
        'statuses' => ['received', 'planned', 'completed'],
    ],
    'reporting' => [
        'label' => 'Reporting requirement',
        'table' => 'ld_reporting_requirement',
        'statuses' => ['received', 'in_progress', 'completed'],
    ],
    'engagement' => [
        'label' => 'Engagement record',
        'table' => 'ld_training_engagement',
        'statuses' => ['received', 'monitoring', 'completed'],
    ],
    'risk' => [
        'label' => 'Risk assessment',
        'table' => 'ld_turnover_risk',
        'statuses' => ['received', 'monitoring', 'mitigated', 'escalated'],
    ],
    'training_request' => [
        'label' => 'Training action',
        'table' => 'ld_training_requests',
        'statuses' => ['pending', 'accepted', 'rejected', 'completed'],
    ],
];

/** The tab a record type lives in, so a posted action lands back where it was taken. */
$tabForRecord = static function (string $type): string {
    if (in_array($type, ['risk', 'engagement'], true)) {
        return 'wf-signals';
    }
    if ($type === 'training_request') {
        return 'wf-actions';
    }
    return 'wf-requirements';
};

/** "turnover_risk:1" -> "Turnover risk #1"; anything else is left as it is. */
$prettyReference = static function ($reference): string {
    $reference = trim((string) $reference);
    if ($reference === '' || !str_contains($reference, ':')) {
        return $reference;
    }
    [$kind, $id] = explode(':', $reference, 2);
    return ucfirst(str_replace('_', ' ', $kind)) . ' #' . $id;
};

/**
 * Resolve a "course:12" / "path:7" selection into something linkable, or null when
 * the row is missing or is no longer offered (archived course, non-active path).
 */
$resolveLearningTarget = static function (PDO $pdo, string $selection): ?array {
    if (!preg_match('/^(course|path):([0-9]+)$/', $selection, $matches)) {
        return null;
    }
    $targetId = (int) $matches[2];
    if ($targetId < 1) {
        return null;
    }

    if ($matches[1] === 'course') {
        $stmt = $pdo->prepare('SELECT id, title, status FROM ld_course WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $targetId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['status'] === 'archived') {
            return null;
        }
        return ['type' => 'course', 'id' => (int) $row['id'], 'title' => (string) $row['title']];
    }

    $stmt = $pdo->prepare('SELECT id, title, status FROM ld_learning_path WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $targetId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || $row['status'] !== 'active') {
        return null;
    }
    return ['type' => 'path', 'id' => (int) $row['id'], 'title' => (string) $row['title']];
};

/**
 * Invite a request's learner to a linked learning path. Mirrors the admin Assign
 * flow (pages/admin/ajax/assign-learning-path.php) so both routes agree on status.
 */
$inviteLearnerToPath = static function (PDO $pdo, int $pathId, $learnerId, int $actorId): string {
    $learnerId = (int) $learnerId;
    if ($learnerId < 1) {
        return '';
    }

    $check = $pdo->prepare("SELECT 1 FROM em_employees WHERE employee_id = :id AND employment_status = 'Active' LIMIT 1");
    $check->execute([':id' => $learnerId]);
    if ($check->fetchColumn() === false) {
        return '';
    }

    $pdo->prepare(
        "INSERT INTO ld_learning_path_invitation (learning_path_id, employee_id, status, invited_by)
         VALUES (:path_id, :employee_id, 'invited', :invited_by)
         ON DUPLICATE KEY UPDATE
             status = IF(status IN ('declined', 'withdrawn'), 'invited', status),
             invited_by = VALUES(invited_by)"
    )->execute([
        ':path_id'     => $pathId,
        ':employee_id' => $learnerId,
        ':invited_by'  => $actorId > 0 ? $actorId : null,
    ]);

    return ' Employee #' . $learnerId . ' was invited to it.';
};

/**
 * True when the employee may own a training action: active, and either explicitly
 * given the instructor learning role or designated in ld_instructor. This is the same
 * pool the admin profile counts as instructors, so the inbox cannot route work to
 * someone who cannot open the instructor pages.
 */
$isDesignatedInstructor = static function (PDO $pdo, int $employeeId): bool {
    if ($employeeId < 1) {
        return false;
    }
    $stmt = $pdo->prepare(
        "SELECT 1
           FROM em_employees e
           LEFT JOIN ld_user_role role ON role.employee_id = e.employee_id
           LEFT JOIN ld_instructor designated ON designated.employee_id = e.employee_id
          WHERE e.employee_id = :id
            AND e.employment_status = 'Active'
            AND (role.learning_role = 'instructor' OR designated.employee_id IS NOT NULL)
          LIMIT 1"
    );
    $stmt->execute([':id' => $employeeId]);

    return $stmt->fetchColumn() !== false;
};

/**
 * Create the draft course that answers a training action: named after the requested
 * skill, categorised by the department that asked, and tagged with the skill when one
 * of that name exists. Content is deliberately left to the course builder.
 */
$createDraftCourse = static function (PDO $pdo, array $requestRow, int $actorId): array {
    $title = trim((string) ($requestRow['skill_name'] ?? ''));
    if ($title === '') {
        $title = 'Training action #' . (int) $requestRow['id'];
    }
    $title = mb_substr($title, 0, 255);

    $department = trim((string) ($requestRow['target_department'] ?? ''));
    $category = $department !== '' ? mb_substr(ucwords(strtolower($department)), 0, 100) : null;

    $message = trim((string) ($requestRow['message'] ?? ''));
    $description = 'Created from the Workforce Inbox for the "' . $title . '" training action.'
        . ($message !== '' ? "\n\nRequested: " . $message : '');

    $pdo->prepare(
        'INSERT INTO ld_course (instructor_id, title, description, category, status, delivery_mode)
         VALUES (:instructor_id, :title, :description, :category, :status, :delivery_mode)'
    )->execute([
        ':instructor_id' => $actorId > 0 ? $actorId : null,
        ':title'         => $title,
        ':description'   => $description,
        ':category'      => $category,
        ':status'        => 'draft',
        ':delivery_mode' => 'online',
    ]);

    $courseId = (int) $pdo->lastInsertId();

    // Tag the skill the request is about so the new course joins skill matching.
    $skillId = (int) ($requestRow['skill_id'] ?? 0);
    if ($skillId < 1 && $title !== '') {
        $skill = $pdo->prepare("SELECT id FROM ld_skill WHERE status = 'active' AND LOWER(name) = LOWER(:name) LIMIT 1");
        $skill->execute([':name' => $title]);
        $skillId = (int) ($skill->fetchColumn() ?: 0);
    }
    if ($skillId > 0) {
        $pdo->prepare('INSERT INTO ld_course_skill (course_id, skill_id) VALUES (:cid, :sid)')
            ->execute([':cid' => $courseId, ':sid' => $skillId]);
    }

    return ['id' => $courseId, 'title' => $title];
};

$postAction = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? (string) ($_POST['action'] ?? '') : '';

if ($postAction === 'update_status') {
    $recordType = (string) ($_POST['record_type'] ?? '');
    $recordId = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT);
    $status = (string) ($_POST['status'] ?? '');

    if (!CSRF::validate(isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        $inboxError = 'Your session token expired. Reload this page and try again.';
    } elseif (!isset($reviewTypes[$recordType]) || $recordId === false || $recordId < 1
        || !in_array($status, $reviewTypes[$recordType]['statuses'], true)) {
        http_response_code(400);
        $inboxError = 'The requested review update is invalid.';
    } else {
        $table = $reviewTypes[$recordType]['table'];
        $stmt = $pdo->prepare("UPDATE {$table} SET status = :status WHERE id = :id AND source_module = 'workforce'");
        $stmt->execute([':status' => $status, ':id' => $recordId]);
        $prettyStatus = ucwords(str_replace('_', ' ', $status));
        $inboxMessage = $stmt->rowCount() > 0
            ? $reviewTypes[$recordType]['label'] . ' marked as ' . $prettyStatus . '.'
            : 'No Workforce record needed an update.';
    }
} elseif ($postAction === 'attach_learning') {
    // Attach the course or learning path that answers a training action, create that
    // draft course, or clear the link. Only ld_training_requests rows sourced from
    // Workforce are touched, so the learner-driven requests stay out of scope.
    $requestId = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT);
    $mode = (string) ($_POST['mode'] ?? '');
    $selection = (string) ($_POST['item'] ?? '');
    $actorId = (int) ($_SESSION['employee_id'] ?? 0);

    if (!CSRF::validate(isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        $inboxError = 'Your session token expired. Reload this page and try again.';
    } elseif ($requestId === false || $requestId < 1 || !in_array($mode, ['link', 'create', 'unlink'], true)) {
        http_response_code(400);
        $inboxError = 'The requested learning link is invalid.';
    } else {
        try {
            $lookup = $pdo->prepare(
                "SELECT id, skill_id, skill_name, message, target_department, learner_id
                   FROM ld_training_requests
                  WHERE id = :id AND source_module = 'workforce'
                  LIMIT 1"
            );
            $lookup->execute([':id' => $requestId]);
            $requestRow = $lookup->fetch(PDO::FETCH_ASSOC);

            if (!$requestRow) {
                http_response_code(400);
                $inboxError = 'That training action is no longer in the Workforce inbox.';
            } elseif ($mode === 'unlink') {
                $pdo->prepare('UPDATE ld_training_requests SET course_id = NULL, learning_path_id = NULL WHERE id = :id')
                    ->execute([':id' => $requestId]);
                $inboxMessage = 'Learning link removed from this training action.';
            } elseif ($mode === 'create') {
                $pdo->beginTransaction();
                $created = $createDraftCourse($pdo, $requestRow, $actorId);
                $pdo->prepare('UPDATE ld_training_requests SET course_id = :cid, learning_path_id = NULL WHERE id = :id')
                    ->execute([':cid' => $created['id'], ':id' => $requestId]);
                $pdo->commit();
                $inboxMessage = 'Draft course "' . $created['title'] . '" created and linked to this training action.';
            } else {
                $target = $resolveLearningTarget($pdo, $selection);
                if ($target === null) {
                    http_response_code(400);
                    $inboxError = 'Choose a course or learning path that is still available.';
                } else {
                    $pdo->beginTransaction();
                    $invited = '';
                    if ($target['type'] === 'course') {
                        $pdo->prepare('UPDATE ld_training_requests SET course_id = :cid, learning_path_id = NULL WHERE id = :id')
                            ->execute([':cid' => $target['id'], ':id' => $requestId]);
                        $label = 'course';
                    } else {
                        $pdo->prepare('UPDATE ld_training_requests SET learning_path_id = :pid, course_id = NULL WHERE id = :id')
                            ->execute([':pid' => $target['id'], ':id' => $requestId]);
                        $label = 'learning path';
                        $invited = $inviteLearnerToPath($pdo, $target['id'], $requestRow['learner_id'], $actorId);
                    }
                    $pdo->commit();
                    $inboxMessage = 'Linked ' . $label . ' "' . $target['title'] . '" to this training action.' . $invited;
                }
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            DbError::capture($exception, 'admin/workforce-integration');
            $inboxError = 'The learning link could not be saved.';
        }
    }
} elseif ($postAction === 'assign_instructor') {
    // Route a training action to the instructor who should answer it, or clear the
    // owner. The instructor page's own Accept button writes this same column, so an
    // assignment made in either place shows up in both.
    $requestId = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT);
    $mode = (string) ($_POST['mode'] ?? '');
    $instructorId = filter_var($_POST['instructor_id'] ?? null, FILTER_VALIDATE_INT);
    $instructorId = $instructorId === false ? 0 : (int) $instructorId;

    if (!CSRF::validate(isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        $inboxError = 'Your session token expired. Reload this page and try again.';
    } elseif ($requestId === false || $requestId < 1 || !in_array($mode, ['assign', 'clear'], true)) {
        http_response_code(400);
        $inboxError = 'The requested instructor update is invalid.';
    } else {
        try {
            $lookup = $pdo->prepare(
                "SELECT id FROM ld_training_requests WHERE id = :id AND source_module = 'workforce' LIMIT 1"
            );
            $lookup->execute([':id' => $requestId]);

            if ($lookup->fetchColumn() === false) {
                http_response_code(400);
                $inboxError = 'That training action is no longer in the Workforce inbox.';
            } elseif ($mode === 'clear') {
                $pdo->prepare('UPDATE ld_training_requests SET instructor_id = NULL WHERE id = :id')
                    ->execute([':id' => $requestId]);
                $inboxMessage = 'Instructor unassigned from this training action.';
            } elseif (!$isDesignatedInstructor($pdo, $instructorId)) {
                http_response_code(400);
                $inboxError = 'Choose an instructor who is currently designated.';
            } else {
                $pdo->prepare('UPDATE ld_training_requests SET instructor_id = :iid WHERE id = :id')
                    ->execute([':iid' => $instructorId, ':id' => $requestId]);
                $nameStmt = $pdo->prepare("SELECT CONCAT_WS(' ', first_name, last_name) FROM em_employees WHERE employee_id = :id");
                $nameStmt->execute([':id' => $instructorId]);
                $inboxMessage = trim((string) $nameStmt->fetchColumn()) . ' assigned to this training action.';
            }
        } catch (Throwable $exception) {
            DbError::capture($exception, 'admin/workforce-integration');
            $inboxError = 'The instructor assignment could not be saved.';
        }
    }
}

// Keep the tab the action was taken from open across the reload.
$activeTab = $postAction !== '' ? $tabForRecord((string) ($_POST['record_type'] ?? '')) : 'wf-requirements';

$records = [
    'competency' => [],
    'reporting' => [],
    'engagement' => [],
    'risk' => [],
    'training_request' => [],
];
$linkableCourses = [];
$linkablePaths = [];
$instructorOptions = [];

try {
    $records['competency'] = $pdo->query(
        "SELECT id, skill_name, target_department, priority_level, employees_needing_training,
                target_proficiency, expected_learning_output, notes, status, created_at
         FROM ld_competency_requirement
         WHERE source_module = 'workforce'
         ORDER BY created_at DESC, id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $records['reporting'] = $pdo->query(
        "SELECT id, target_name, reporting_period_start, reporting_period_end, reporting_frequency,
                completion_target, expected_output, notes, status, created_at
         FROM ld_reporting_requirement
         WHERE source_module = 'workforce'
         ORDER BY created_at DESC, id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $records['engagement'] = $pdo->query(
        "SELECT id, external_reference_id, learner_team_type, learner_id, learner_name, department,
                training_activity, engagement_status, engagement_data, recommended_action, notes,
                status, created_at, updated_at
         FROM ld_training_engagement
         WHERE source_module = 'workforce'
         ORDER BY created_at DESC, id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $records['risk'] = $pdo->query(
        "SELECT id, external_reference_id, employee_id, employee_name, department, position_name,
                risk_level, risk_score, risk_factors, recommended_action, assessment_date,
                status, created_at, updated_at
         FROM ld_turnover_risk
         WHERE source_module = 'workforce'
         ORDER BY created_at DESC, id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $records['training_request'] = $pdo->query(
        "SELECT tr.id, tr.learner_id, tr.skill_id, tr.skill_name, tr.message, tr.instructor_id,
                tr.instructor_note, tr.target_department, tr.priority_level, tr.status,
                tr.external_reference_id, tr.course_id, tr.learning_path_id,
                tr.created_at, tr.updated_at,
                course.title AS course_title, path.title AS path_title,
                CONCAT_WS(' ', assigned.first_name, assigned.last_name) AS instructor_name
         FROM ld_training_requests tr
         LEFT JOIN ld_course course ON course.id = tr.course_id
         LEFT JOIN ld_learning_path path ON path.id = tr.learning_path_id
         LEFT JOIN em_employees assigned ON assigned.employee_id = tr.instructor_id
         WHERE tr.source_module = 'workforce'
         ORDER BY tr.created_at DESC, tr.id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    // What a training action can be linked to: any course that is not archived, and
    // any active learning path (an invitation to an archived path would be pointless).
    $linkableCourses = $pdo->query(
        "SELECT id, title FROM ld_course WHERE status <> 'archived' ORDER BY title ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
    $linkablePaths = $pdo->query(
        "SELECT id, title FROM ld_learning_path WHERE status = 'active' ORDER BY title ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    DbError::capture($exception, 'admin/workforce-integration');
    $inboxError = 'Workforce records could not be loaded. Check the Learning database connection and table setup.';
}

// Who a training action can be routed to: the instructor pool the admin profile reports
// (ld_user_role) plus the explicit designations in ld_instructor, restricted to active
// employees because instructor_id references em_employees. Each source is read on its
// own because ld_instructor is created on demand by cron/designate-instructor.php, and
// a missing table there must not take the whole inbox down with it.
$instructorPool = [];
foreach ([
    "SELECT e.employee_id, CONCAT_WS(' ', e.first_name, e.last_name) AS full_name
       FROM em_employees e
       JOIN ld_user_role role ON role.employee_id = e.employee_id AND role.learning_role = 'instructor'
      WHERE e.employment_status = 'Active'",
    "SELECT e.employee_id, CONCAT_WS(' ', e.first_name, e.last_name) AS full_name
       FROM ld_instructor designated
       JOIN em_employees e ON e.employee_id = designated.employee_id
      WHERE e.employment_status = 'Active'",
] as $instructorSql) {
    try {
        foreach ($pdo->query($instructorSql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $instructorPool[(int) $row['employee_id']] = trim((string) $row['full_name']);
        }
    } catch (Throwable $exception) {
        DbError::capture($exception, 'admin/workforce-integration');
    }
}
natcasesort($instructorPool);
foreach ($instructorPool as $employeeId => $fullName) {
    $instructorOptions[] = ['employee_id' => $employeeId, 'full_name' => $fullName];
}

$counts = array_map('count', $records);
$formatDate = static function ($value): string {
    return $value ? date('M j, Y', strtotime((string) $value)) : '—';
};
$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

/** Tone class for the current status chip and its action buttons, so state reads at a glance. */
$statusTone = static function (string $status): string {
    if (in_array($status, ['completed', 'accepted', 'mitigated'], true)) {
        return 'is-done';
    }
    if (in_array($status, ['in_progress', 'monitoring', 'planned'], true)) {
        return 'is-active';
    }
    if (in_array($status, ['rejected', 'escalated'], true)) {
        return 'is-warn';
    }
    return '';
};

/** The verb and icon shown on the button that moves a record into each status. */
$statusActions = [
    'received' => ['label' => 'Reopen', 'icon' => 'fa-rotate-left'],
    'pending' => ['label' => 'Reopen', 'icon' => 'fa-rotate-left'],
    'planned' => ['label' => 'Plan training', 'icon' => 'fa-calendar-check'],
    'in_progress' => ['label' => 'Start work', 'icon' => 'fa-play'],
    'monitoring' => ['label' => 'Monitor', 'icon' => 'fa-eye'],
    'mitigated' => ['label' => 'Mitigate', 'icon' => 'fa-shield-halved'],
    'escalated' => ['label' => 'Escalate', 'icon' => 'fa-arrow-up-right-dots'],
    'accepted' => ['label' => 'Accept', 'icon' => 'fa-check'],
    'rejected' => ['label' => 'Reject', 'icon' => 'fa-xmark'],
    'completed' => ['label' => 'Complete', 'icon' => 'fa-circle-check'],
];

/**
 * The action cell for one record: the status it is in now, then one button for
 * every other status it can move to. All buttons are submits of the same form
 * (name="status" value="..."), so the control still needs no JavaScript and
 * posts the same update_status request the endpoint already validates.
 *
 * data-skip keeps the module's shared AJAX form handler out of it: that handler
 * posts new FormData(form), which drops the clicked button's name/value, so the
 * status would never reach the server. A native submit keeps the button value
 * and reloads the page, which is what renders the confirmation notice.
 */
$renderReviewActions = static function (string $type, array $record) use ($reviewTypes, $statusTone, $statusActions, $escape): void {
    $current = (string) ($record['status'] ?? '');
    $currentLabel = ucwords(str_replace('_', ' ', $current));

    echo '<form method="post" class="wf-review" data-skip>';
    echo CSRF::field();
    echo '<input type="hidden" name="action" value="update_status">';
    echo '<input type="hidden" name="record_type" value="' . $escape($type) . '">';
    echo '<input type="hidden" name="record_id" value="' . (int) $record['id'] . '">';
    echo '<span class="wf-state ' . $escape($statusTone($current)) . '" title="Current status: ' . $escape($currentLabel) . '">';
    echo '<i class="fas fa-circle" aria-hidden="true"></i><span>' . $escape($currentLabel) . '</span>';
    echo '</span>';
    echo '<div class="wf-actions" role="group" aria-label="Change status">';
    foreach ($reviewTypes[$type]['statuses'] as $status) {
        if ($status === $current) {
            continue;
        }
        $action = $statusActions[$status] ?? ['label' => ucwords(str_replace('_', ' ', $status)), 'icon' => 'fa-bolt'];
        echo '<button type="submit" name="status" value="' . $escape($status) . '" class="wf-action ' . $escape($statusTone($status)) . '">';
        echo '<i class="fas ' . $escape($action['icon']) . '" aria-hidden="true"></i><span>' . $escape($action['label']) . '</span>';
        echo '</button>';
    }
    echo '</div>';
    echo '</form>';
};

/**
 * The learning cell for one training action: whatever is attached now, then one form
 * that links an existing course or path, creates a draft course named after the need,
 * or clears the link.
 *
 * Every button is a submit of the same form (name="mode" value="..."), so the control
 * works without JavaScript and posts the request the page validates. data-skip matters
 * here for the same reason as the status buttons: the shared form handler posts
 * new FormData(form), which would drop the clicked button's mode.
 */
$renderLearningLink = static function (array $record) use ($linkableCourses, $linkablePaths, $escape): void {
    $courseId = (int) ($record['course_id'] ?? 0);
    $pathId = (int) ($record['learning_path_id'] ?? 0);
    $courseTitle = (string) ($record['course_title'] ?? '');
    $pathTitle = (string) ($record['path_title'] ?? '');
    $isLinked = ($courseId > 0 && $courseTitle !== '') || ($pathId > 0 && $pathTitle !== '');

    if ($courseId > 0 && $courseTitle !== '') {
        echo '<a class="wf-link-chip is-course" href="?page=instructor/elearning-subpage/course-structure&amp;course_id=' . $courseId . '" title="' . $escape($courseTitle) . ' — open in the course builder">';
        echo '<i class="fas fa-book" aria-hidden="true"></i><span>' . $escape($courseTitle) . '</span><em>Course</em>';
        echo '</a>';
    } elseif ($pathId > 0 && $pathTitle !== '') {
        echo '<a class="wf-link-chip is-path" href="?page=instructor/learning-path" title="' . $escape($pathTitle) . ' — open the learning path builder">';
        echo '<i class="fas fa-route" aria-hidden="true"></i><span>' . $escape($pathTitle) . '</span><em>Path</em>';
        echo '</a>';
    } else {
        echo '<span class="wf-link-none"><i class="fas fa-link-slash" aria-hidden="true"></i>Not linked</span>';
    }

    echo '<form method="post" class="wf-link" data-skip>';
    echo CSRF::field();
    echo '<input type="hidden" name="action" value="attach_learning">';
    echo '<input type="hidden" name="record_type" value="training_request">';
    echo '<input type="hidden" name="record_id" value="' . (int) $record['id'] . '">';

    if ($linkableCourses || $linkablePaths) {
        $current = $courseId > 0 ? 'course:' . $courseId : ($pathId > 0 ? 'path:' . $pathId : '');
        echo '<select name="item" class="wf-link-select" aria-label="Course or learning path to link">';
        if ($linkableCourses) {
            echo '<optgroup label="Courses">';
            foreach ($linkableCourses as $course) {
                $value = 'course:' . (int) $course['id'];
                echo '<option value="' . $escape($value) . '"' . ($value === $current ? ' selected' : '') . '>' . $escape($course['title']) . '</option>';
            }
            echo '</optgroup>';
        }
        if ($linkablePaths) {
            echo '<optgroup label="Learning paths">';
            foreach ($linkablePaths as $path) {
                $value = 'path:' . (int) $path['id'];
                echo '<option value="' . $escape($value) . '"' . ($value === $current ? ' selected' : '') . '>' . $escape($path['title']) . '</option>';
            }
            echo '</optgroup>';
        }
        echo '</select>';
    } else {
        echo '<span class="wf-link-none">No courses or learning paths exist yet.</span>';
    }

    echo '<div class="wf-link-actions">';
    if ($linkableCourses || $linkablePaths) {
        echo '<button type="submit" name="mode" value="link" class="wf-action"><i class="fas fa-link" aria-hidden="true"></i><span>Link</span></button>';
    }
    echo '<button type="submit" name="mode" value="create" class="wf-action"><i class="fas fa-plus" aria-hidden="true"></i><span>New course</span></button>';
    if ($isLinked) {
        echo '<button type="submit" name="mode" value="unlink" class="wf-action is-warn"><i class="fas fa-link-slash" aria-hidden="true"></i><span>Unlink</span></button>';
    }
    echo '</div>';
    echo '</form>';
};

/**
 * The instructor control for one training action, rendered under its status actions so
 * the table keeps its column budget. It shows who owns the request now, then one form
 * that routes it to a designated instructor or clears the owner. Shares the stacked
 * form styling with the learning cell above the fold of this page.
 */
$renderInstructorControl = static function (array $record) use ($instructorOptions, $escape): void {
    $assignedId = (int) ($record['instructor_id'] ?? 0);
    $assignedName = trim((string) ($record['instructor_name'] ?? ''));

    echo '<div class="wf-assign">';
    echo '<span class="wf-assign-label"><i class="fas fa-user-tie" aria-hidden="true"></i>Instructor</span>';
    echo '<span class="wf-assign-name' . ($assignedName === '' ? ' is-empty' : '') . '">'
        . $escape($assignedName !== '' ? $assignedName : 'Unassigned') . '</span>';

    if (!$instructorOptions) {
        echo '<span class="wf-link-none">No instructors are designated yet.</span>';
        echo '</div>';
        return;
    }

    echo '<form method="post" class="wf-link" data-skip>';
    echo CSRF::field();
    echo '<input type="hidden" name="action" value="assign_instructor">';
    echo '<input type="hidden" name="record_type" value="training_request">';
    echo '<input type="hidden" name="record_id" value="' . (int) $record['id'] . '">';
    echo '<select name="instructor_id" class="wf-link-select" aria-label="Designated instructor to assign">';
    echo '<option value="">Choose an instructor…</option>';
    foreach ($instructorOptions as $option) {
        $value = (int) $option['employee_id'];
        echo '<option value="' . $value . '"' . ($value === $assignedId ? ' selected' : '') . '>'
            . $escape($option['full_name']) . '</option>';
    }
    echo '</select>';
    echo '<div class="wf-link-actions">';
    echo '<button type="submit" name="mode" value="assign" class="wf-action"><i class="fas fa-user-check" aria-hidden="true"></i><span>Assign</span></button>';
    if ($assignedId > 0) {
        echo '<button type="submit" name="mode" value="clear" class="wf-action is-warn"><i class="fas fa-user-slash" aria-hidden="true"></i><span>Unassign</span></button>';
    }
    echo '</div>';
    echo '</form>';
    echo '</div>';
};

/** Priority / risk level as a toned badge. */
$levelBadge = static function ($level) use ($escape): string {
    $key = strtolower(trim((string) $level));
    if ($key === '') {
        return '<span class="wf-muted">—</span>';
    }
    $tone = in_array($key, ['critical', 'escalated'], true) ? 'is-critical'
        : (in_array($key, ['high', 'urgent'], true) ? 'is-high'
        : (in_array($key, ['medium', 'moderate'], true) ? 'is-medium' : 'is-low'));
    return '<span class="wf-badge ' . $tone . '">' . $escape(ucfirst($key)) . '</span>';
};
?>
<div class="module-content wf-page">
    <div class="home-dashboard">

        <section class="home-hero">
            <div class="home-hero-main">
                <h1>Workforce Inbox</h1>
                <p>Review incoming requirements, risk signals, engagement updates, and training actions.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-lightbulb" aria-hidden="true"></i> <b><?= (int) $counts['competency'] ?></b> competency needs</span>
                    <span class="home-hero-chip"><i class="fas fa-chart-column" aria-hidden="true"></i> <b><?= (int) $counts['reporting'] ?></b> reporting</span>
                    <span class="home-hero-chip"><i class="fas fa-users" aria-hidden="true"></i> <b><?= (int) $counts['engagement'] ?></b> engagement</span>
                    <span class="home-hero-chip"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> <b><?= (int) $counts['risk'] ?></b> risk</span>
                    <span class="home-hero-chip"><i class="fas fa-graduation-cap" aria-hidden="true"></i> <b><?= (int) $counts['training_request'] ?></b> actions</span>
                </div>
            </div>
        </section>

        <?php if ($inboxMessage !== ''): ?>
            <p class="wf-notice" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i><span><?= $escape($inboxMessage) ?></span></p>
        <?php endif; ?>
        <?php if ($inboxError !== ''): ?>
            <p class="wf-notice is-error" role="alert"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><span><?= $escape($inboxError) ?></span></p>
        <?php endif; ?>

        <div class="tab-container">
            <div class="tab-list" role="tablist" aria-label="Workforce records">
                <button type="button" class="tab-item <?= $activeTab === 'wf-requirements' ? 'active' : '' ?>" data-tab="wf-requirements" role="tab" aria-selected="<?= $activeTab === 'wf-requirements' ? 'true' : 'false' ?>">
                    <i class="fas fa-clipboard-list" aria-hidden="true"></i> Requirements
                    <span class="wf-tab-count"><?= (int) ($counts['competency'] + $counts['reporting']) ?></span>
                </button>
                <button type="button" class="tab-item <?= $activeTab === 'wf-signals' ? 'active' : '' ?>" data-tab="wf-signals" role="tab" aria-selected="<?= $activeTab === 'wf-signals' ? 'true' : 'false' ?>">
                    <i class="fas fa-shield-halved" aria-hidden="true"></i> Risk &amp; engagement
                    <span class="wf-tab-count"><?= (int) ($counts['risk'] + $counts['engagement']) ?></span>
                </button>
                <button type="button" class="tab-item <?= $activeTab === 'wf-actions' ? 'active' : '' ?>" data-tab="wf-actions" role="tab" aria-selected="<?= $activeTab === 'wf-actions' ? 'true' : 'false' ?>">
                    <i class="fas fa-graduation-cap" aria-hidden="true"></i> Training actions
                    <span class="wf-tab-count"><?= (int) $counts['training_request'] ?></span>
                </button>
            </div>

            <section class="tab-content <?= $activeTab === 'wf-requirements' ? 'active' : '' ?>" id="wf-requirements" data-tab="wf-requirements">
                <div class="wf-card">
                    <div class="wf-card-head">
                        <h2 class="wf-card-title">
                            <span class="wf-card-icon tone-violet"><i class="fas fa-lightbulb" aria-hidden="true"></i></span>
                            Competency requirements
                        </h2>
                        <span class="wf-card-count"><?= (int) $counts['competency'] ?></span>
                    </div>
                    <?php if (!$records['competency']): ?>
                        <div class="wf-empty"><i class="fas fa-inbox" aria-hidden="true"></i><p>No Workforce competency requirements have arrived.</p></div>
                    <?php else: ?>
                        <div class="wf-table-wrap">
                            <table class="wf-table">
                                <thead>
                                    <tr><th>Need</th><th>Department</th><th>Priority</th><th>Learners</th><th>Expected output</th><th>Received</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records['competency'] as $record): ?>
                                        <tr>
                                            <td>
                                                <span class="wf-cell-title"><?= $escape($record['skill_name']) ?></span>
                                                <?php if ((string) $record['target_proficiency'] !== ''): ?><span class="wf-cell-sub"><?= $escape($record['target_proficiency']) ?></span><?php endif; ?>
                                            </td>
                                            <td><?= $escape($record['target_department']) ?></td>
                                            <td><?= $levelBadge($record['priority_level']) ?></td>
                                            <td class="wf-cell-strong"><?= (int) $record['employees_needing_training'] ?></td>
                                            <td><?= $escape($record['expected_learning_output']) ?></td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['created_at'])) ?></td>
                                            <td><?php $renderReviewActions('competency', $record); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="wf-card">
                    <div class="wf-card-head">
                        <h2 class="wf-card-title">
                            <span class="wf-card-icon tone-indigo"><i class="fas fa-chart-column" aria-hidden="true"></i></span>
                            Reporting requirements
                        </h2>
                        <span class="wf-card-count"><?= (int) $counts['reporting'] ?></span>
                    </div>
                    <?php if (!$records['reporting']): ?>
                        <div class="wf-empty"><i class="fas fa-inbox" aria-hidden="true"></i><p>No Workforce reporting requirements have arrived.</p></div>
                    <?php else: ?>
                        <div class="wf-table-wrap">
                            <table class="wf-table">
                                <thead>
                                    <tr><th>Target</th><th>Period</th><th>Frequency</th><th>Goal</th><th>Expected output</th><th>Received</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records['reporting'] as $record): ?>
                                        <tr>
                                            <td>
                                                <span class="wf-cell-title"><?= $escape($record['target_name']) ?></span>
                                                <?php if ((string) $record['notes'] !== ''): ?><span class="wf-cell-sub"><?= $escape($record['notes']) ?></span><?php endif; ?>
                                            </td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['reporting_period_start'])) ?> &ndash; <?= $escape($formatDate($record['reporting_period_end'])) ?></td>
                                            <td><?= $escape(ucfirst($record['reporting_frequency'])) ?></td>
                                            <td class="wf-cell-strong"><?= $escape($record['completion_target']) ?>%</td>
                                            <td><?= $escape($record['expected_output']) ?></td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['created_at'])) ?></td>
                                            <td><?php $renderReviewActions('reporting', $record); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="tab-content <?= $activeTab === 'wf-signals' ? 'active' : '' ?>" id="wf-signals" data-tab="wf-signals">
                <div class="wf-card">
                    <div class="wf-card-head">
                        <h2 class="wf-card-title">
                            <span class="wf-card-icon tone-rose"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i></span>
                            Turnover risk assessments
                        </h2>
                        <span class="wf-card-count"><?= (int) $counts['risk'] ?></span>
                    </div>
                    <?php if (!$records['risk']): ?>
                        <div class="wf-empty"><i class="fas fa-inbox" aria-hidden="true"></i><p>No Workforce risk assessments have arrived.</p></div>
                    <?php else: ?>
                        <div class="wf-table-wrap">
                            <table class="wf-table">
                                <thead>
                                    <tr><th>Employee</th><th>Department / role</th><th>Risk</th><th>Factors</th><th>Recommended action</th><th>Assessed</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records['risk'] as $record): ?>
                                        <tr>
                                            <td><span class="wf-cell-title"><?= $escape($record['employee_name'] ?: ($record['employee_id'] ? 'Employee #' . $record['employee_id'] : 'Workforce group')) ?></span></td>
                                            <td>
                                                <span class="wf-cell-title"><?= $escape($record['department']) ?></span>
                                                <?php if ((string) $record['position_name'] !== ''): ?><span class="wf-cell-sub"><?= $escape($record['position_name']) ?></span><?php endif; ?>
                                            </td>
                                            <td class="wf-cell-nowrap">
                                                <?= $levelBadge($record['risk_level']) ?>
                                                <?php if ($record['risk_score'] !== null): ?><span class="wf-cell-sub"><?= $escape($record['risk_score']) ?>%</span><?php endif; ?>
                                            </td>
                                            <td><?= $escape($record['risk_factors']) ?></td>
                                            <td><?= $escape($record['recommended_action']) ?></td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['assessment_date'] ?: $record['created_at'])) ?></td>
                                            <td><?php $renderReviewActions('risk', $record); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="wf-card">
                    <div class="wf-card-head">
                        <h2 class="wf-card-title">
                            <span class="wf-card-icon tone-amber"><i class="fas fa-users" aria-hidden="true"></i></span>
                            Training engagement
                        </h2>
                        <span class="wf-card-count"><?= (int) $counts['engagement'] ?></span>
                    </div>
                    <?php if (!$records['engagement']): ?>
                        <div class="wf-empty"><i class="fas fa-inbox" aria-hidden="true"></i><p>No Workforce engagement records have arrived.</p></div>
                    <?php else: ?>
                        <div class="wf-table-wrap">
                            <table class="wf-table">
                                <thead>
                                    <tr><th>Learner / group</th><th>Department</th><th>Activity</th><th>Engagement</th><th>Recommended action</th><th>Received</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records['engagement'] as $record): ?>
                                        <tr>
                                            <td><span class="wf-cell-title"><?= $escape($record['learner_name'] ?: ($record['learner_id'] ? 'Employee #' . $record['learner_id'] : ucwords(str_replace('-', ' ', $record['learner_team_type'])))) ?></span></td>
                                            <td><?= $escape($record['department']) ?></td>
                                            <td><?= $escape(ucwords(str_replace('-', ' ', $record['training_activity']))) ?></td>
                                            <td>
                                                <span class="wf-cell-title"><?= $escape(ucwords(str_replace('-', ' ', $record['engagement_status']))) ?></span>
                                                <?php if ((string) $record['engagement_data'] !== ''): ?><span class="wf-cell-sub"><?= $escape($record['engagement_data']) ?></span><?php endif; ?>
                                            </td>
                                            <td><?= $escape($record['recommended_action']) ?></td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['created_at'])) ?></td>
                                            <td><?php $renderReviewActions('engagement', $record); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="tab-content <?= $activeTab === 'wf-actions' ? 'active' : '' ?>" id="wf-actions" data-tab="wf-actions">
                <div class="wf-card">
                    <div class="wf-card-head">
                        <h2 class="wf-card-title">
                            <span class="wf-card-icon tone-emerald"><i class="fas fa-graduation-cap" aria-hidden="true"></i></span>
                            Training actions
                        </h2>
                        <span class="wf-card-count"><?= (int) $counts['training_request'] ?></span>
                    </div>
                    <p class="wf-card-note">Requests raised by Workforce records. Link each one to the course or learning path that answers it, or create a draft course from the requested skill, then accept, reject or complete the request.</p>
                    <?php if (!$records['training_request']): ?>
                        <div class="wf-empty"><i class="fas fa-inbox" aria-hidden="true"></i><p>No Workforce training actions have arrived.</p></div>
                    <?php else: ?>
                        <div class="wf-table-wrap">
                            <table class="wf-table wf-table-actions">
                                <thead>
                                    <tr><th>Request</th><th>Priority</th><th>Request details</th><th>Received</th><th class="wf-col-learning">Learning</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records['training_request'] as $record): ?>
                                        <?php
                                        // Department, learner and source record read as one meta line
                                        // under the skill, so the table keeps a column for the control.
                                        $requestMeta = [];
                                        if (trim((string) $record['target_department']) !== '') {
                                            $requestMeta[] = trim((string) $record['target_department']);
                                        }
                                        if ($record['learner_id']) {
                                            $requestMeta[] = 'Employee #' . (int) $record['learner_id'];
                                        }
                                        if (trim((string) $record['external_reference_id']) !== '') {
                                            $requestMeta[] = 'From ' . $prettyReference($record['external_reference_id']);
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="wf-cell-title"><?= $escape($record['skill_name']) ?></span>
                                                <?php if ($requestMeta): ?><span class="wf-cell-sub"><?= $escape(implode(' · ', $requestMeta)) ?></span><?php endif; ?>
                                            </td>
                                            <td><?= $levelBadge($record['priority_level']) ?></td>
                                            <td><?= $escape($record['message']) ?></td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['created_at'])) ?></td>
                                            <td class="wf-col-learning"><?php $renderLearningLink($record); ?></td>
                                            <td><?php $renderReviewActions('training_request', $record); $renderInstructorControl($record); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
