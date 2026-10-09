<?php
/**
 * Inbound: receive-workforce-notification.php
 * Receives a Workforce notification (type + title + message) and, when the payload
 * carries a `data` block, the matching domain record; then stores one ld_notification
 * row per resolved recipient.
 *
 * Contract: modules/learning/rainz/updated learndev and workforce md integration.md
 * → "▶ START HERE — instruction for the Workforce team", section W3 (Path B).
 *
 * POST /modules/learning/api/inbound/receive-workforce-notification.php
 * Header: X-API-Key: <ld_api_key.api_key WHERE module_name = 'workforce-analytics'>
 * Body (JSON): notification_id, type, title, message, audience,
 *              [reference_type], [reference_id], [recipients], [data]
 *
 * Idempotency: ld_integration_event (module_name='workforce-analytics',
 * external_reference_id='wa-notif-<notification_id>'). A re-sent notification_id returns
 * 200 {"success":true,"duplicate":true} and writes nothing.
 *
 * Dedupe note (live schema): only ld_turnover_risk and ld_training_engagement carry an
 * external_reference_id column, so a repeated business record is de-duplicated there
 * when the sender provides one; ld_competency_requirement and ld_reporting_requirement
 * have no such column — the event-level notification_id is their only dedupe key.
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once dirname(__FILE__, 3) . '/classes/apiauth.php';
require_once dirname(__FILE__, 3) . '/classes/integrationlog.php';
require_once dirname(__FILE__, 5) . '/database/db.php';

const WF_MODULE = 'workforce-analytics';
const WF_ENDPOINT = 'receive-workforce-notification';

/** The only types accepted (START HERE W3.1). */
const WF_ALLOWED_TYPES = [
    'workforce_competency_requirement',
    'workforce_turnover_risk',
    'workforce_reporting_requirement',
    'workforce_training_engagement',
    'workforce_alert',
];

/** The four types that must carry a `data` block and land a domain row (START HERE W3.2). */
const WF_DOMAIN_TABLES = [
    'workforce_competency_requirement' => 'ld_competency_requirement',
    'workforce_turnover_risk'          => 'ld_turnover_risk',
    'workforce_reporting_requirement'  => 'ld_reporting_requirement',
    'workforce_training_engagement'    => 'ld_training_engagement',
];

function wf_respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

function wf_uint($value): bool
{
    return (is_int($value) && $value >= 0) || (is_string($value) && ctype_digit($value) && $value !== '');
}

function wf_positive_int($value): bool
{
    return wf_uint($value) && (int) $value >= 1;
}

function wf_is_date($value): bool
{
    if (!is_string($value)) {
        return false;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

/** A string or scalar number, trimmed; null when the value is an array/object. */
function wf_text($value): ?string
{
    if (is_string($value) || is_int($value) || is_float($value)) {
        return trim((string) $value);
    }
    return null;
}

/**
 * Envelope validation (START HERE W3.1). Returns the first failed rule, or null when valid.
 */
function wf_validate_envelope(array $input): ?string
{
    $notificationId = wf_text($input['notification_id'] ?? null);
    if ($notificationId === null || $notificationId === '') {
        return 'notification_id is required';
    }
    if (mb_strlen($notificationId) > 120) {
        return 'notification_id must be at most 120 characters';
    }

    $type = wf_text($input['type'] ?? null);
    if ($type === null || $type === '') {
        return 'type is required';
    }
    if (!in_array($type, WF_ALLOWED_TYPES, true)) {
        return 'type must be one of: ' . implode(', ', WF_ALLOWED_TYPES);
    }

    $title = wf_text($input['title'] ?? null);
    if ($title === null || $title === '') {
        return 'title is required';
    }
    if (mb_strlen($title) > 255) {
        return 'title must be at most 255 characters';
    }

    $message = wf_text($input['message'] ?? null);
    if ($message === null || $message === '') {
        return 'message is required';
    }
    if (mb_strlen($message) > 2000) {
        return 'message must be at most 2000 characters';
    }

    if (array_key_exists('reference_type', $input) && $input['reference_type'] !== null && $input['reference_type'] !== '') {
        $referenceType = wf_text($input['reference_type']);
        if ($referenceType === null) {
            return 'reference_type must be a string';
        }
        if (mb_strlen($referenceType) > 50) {
            return 'reference_type must be at most 50 characters';
        }
    }

    if (array_key_exists('reference_id', $input) && $input['reference_id'] !== null && $input['reference_id'] !== '') {
        if (!wf_positive_int($input['reference_id'])) {
            return 'reference_id must be a positive integer';
        }
    }

    $audience = wf_text($input['audience'] ?? null);
    if ($audience === null || !in_array($audience, ['ld_staff', 'explicit'], true)) {
        return "audience must be 'ld_staff' or 'explicit'";
    }

    if ($audience === 'explicit') {
        $recipients = $input['recipients'] ?? null;
        if (!is_array($recipients) || count($recipients) < 1 || count($recipients) > 100) {
            return 'recipients must be an array of 1-100 employee ids';
        }
        foreach ($recipients as $recipient) {
            if (!wf_positive_int($recipient)) {
                return 'recipients must be an array of 1-100 positive integer employee ids';
            }
        }
    }

    if (in_array($type, array_keys(WF_DOMAIN_TABLES), true)) {
        if (!isset($input['data']) || !is_array($input['data'])) {
            return "data is required for type $type";
        }
    }

    return null;
}

/**
 * Per-type `data` validation against the live column rules (START HERE W3.2).
 * Returns the first failed field rule, or null when valid.
 */
function wf_validate_data(string $type, array $data): ?string
{
    $maxLength = function (string $field, int $max) use ($data): ?string {
        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return null;
        }
        $value = wf_text($data[$field]);
        if ($value === null || mb_strlen($value) > $max) {
            return "$field must be a string of at most $max characters";
        }
        return null;
    };
    $requiredText = function (string $field, int $max) use ($data): ?string {
        $value = wf_text($data[$field] ?? null);
        if ($value === null || $value === '') {
            return "$field is required";
        }
        if (mb_strlen($value) > $max) {
            return "$field must be at most $max characters";
        }
        return null;
    };
    $optionalEnum = function (string $field, array $allowed, ?string $default) use ($data): ?string {
        if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
            return null; // the column default applies
        }
        if (!in_array($data[$field], $allowed, true)) {
            return "$field must be one of: " . implode(', ', $allowed);
        }
        return null;
    };

    switch ($type) {
        case 'workforce_competency_requirement':
            if ($error = $requiredText('skill_name', 150)) return $error;
            if ($error = $requiredText('target_department', 150)) return $error;
            if ($error = $requiredText('target_proficiency', 50)) return $error;
            if ($error = $requiredText('expected_learning_output', 255)) return $error;
            if (!array_key_exists('employees_needing_training', $data) || !wf_uint($data['employees_needing_training'])) {
                return 'employees_needing_training must be zero or a positive whole number';
            }
            if ($error = $optionalEnum('priority_level', ['low', 'medium', 'high', 'critical'], 'medium')) return $error;
            if ($error = $maxLength('notes', 65535)) return $error;
            return null;

        case 'workforce_turnover_risk':
            if (!array_key_exists('risk_level', $data) || !in_array($data['risk_level'], ['low', 'medium', 'high', 'critical'], true)) {
                return 'risk_level must be one of: low, medium, high, critical';
            }
            if ($error = $maxLength('external_reference_id', 120)) return $error;
            if (array_key_exists('employee_id', $data) && $data['employee_id'] !== null && !wf_positive_int($data['employee_id'])) {
                return 'employee_id must be a positive integer';
            }
            if ($error = $maxLength('employee_name', 150)) return $error;
            if ($error = $maxLength('department', 150)) return $error;
            if ($error = $maxLength('position_name', 150)) return $error;
            if (array_key_exists('risk_score', $data) && $data['risk_score'] !== null && $data['risk_score'] !== '') {
                if (!is_numeric($data['risk_score']) || (float) $data['risk_score'] < 0 || (float) $data['risk_score'] > 100) {
                    return 'risk_score must be a number between 0 and 100';
                }
            }
            if ($error = $maxLength('risk_factors', 65535)) return $error;
            if ($error = $maxLength('recommended_action', 65535)) return $error;
            if (array_key_exists('assessment_date', $data) && $data['assessment_date'] !== null && $data['assessment_date'] !== '' && !wf_is_date($data['assessment_date'])) {
                return 'assessment_date must be a YYYY-MM-DD date';
            }
            return null;

        case 'workforce_reporting_requirement':
            if ($error = $requiredText('target_name', 150)) return $error;
            if (!wf_is_date($data['reporting_period_start'] ?? null)) {
                return 'reporting_period_start must be a YYYY-MM-DD date';
            }
            if (!wf_is_date($data['reporting_period_end'] ?? null)) {
                return 'reporting_period_end must be a YYYY-MM-DD date';
            }
            if ($data['reporting_period_end'] < $data['reporting_period_start']) {
                return 'reporting_period_end must be on or after reporting_period_start';
            }
            if (!array_key_exists('completion_target', $data) || !is_numeric($data['completion_target'])
                || (float) $data['completion_target'] < 0 || (float) $data['completion_target'] > 100) {
                return 'completion_target must be a number between 0 and 100';
            }
            if ($error = $requiredText('expected_output', 255)) return $error;
            if ($error = $optionalEnum('reporting_frequency', ['weekly', 'monthly', 'quarterly', 'annual'], 'monthly')) return $error;
            if ($error = $maxLength('notes', 65535)) return $error;
            return null;

        case 'workforce_training_engagement':
            if ($error = $requiredText('department', 150)) return $error;
            if (!array_key_exists('training_activity', $data) || !in_array($data['training_activity'], ['course', 'learning-path', 'workshop', 'compliance-training', 'other'], true)) {
                return 'training_activity must be one of: course, learning-path, workshop, compliance-training, other';
            }
            if (!array_key_exists('engagement_status', $data) || !in_array($data['engagement_status'], ['active', 'in-progress', 'low', 'inactive'], true)) {
                return 'engagement_status must be one of: active, in-progress, low, inactive';
            }
            if ($error = $optionalEnum('learner_team_type', ['individual-learner', 'department-team', 'role-group'], 'individual-learner')) return $error;
            if (array_key_exists('learner_id', $data) && $data['learner_id'] !== null && !wf_positive_int($data['learner_id'])) {
                return 'learner_id must be a positive integer';
            }
            if ($error = $maxLength('learner_name', 150)) return $error;
            if ($error = $maxLength('engagement_data', 65535)) return $error;
            if ($error = $maxLength('recommended_action', 65535)) return $error;
            if ($error = $maxLength('notes', 65535)) return $error;
            if ($error = $maxLength('external_reference_id', 120)) return $error;
            return null;
    }

    return null;
}

/**
 * Resolve the notification audience (START HERE W4).
 * `ld_staff` = active role_id 1/7 accounts, mapped to em_employees.employee_id.
 * `explicit` = the payload list, unknown ids skipped and reported back.
 *
 * @param list<int> $skipped Receives ids that are not live employees.
 * @return list<int>
 */
function wf_resolve_recipients(PDO $pdo, array $input, array &$skipped): array
{
    $skipped = [];

    if ($input['audience'] === 'ld_staff') {
        $stmt = $pdo->query(
            "SELECT DISTINCT u.employee_id
               FROM user_account u
              WHERE u.account_status = 'Active' AND u.role_id IN (1, 7)
              ORDER BY u.employee_id"
        );
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    $check = $pdo->prepare('SELECT 1 FROM em_employees WHERE employee_id = :id LIMIT 1');
    $recipients = [];
    foreach (array_values(array_unique(array_map('intval', $input['recipients']))) as $employeeId) {
        $check->execute([':id' => $employeeId]);
        if ($check->fetchColumn() !== false) {
            $recipients[] = $employeeId;
        } else {
            $skipped[] = $employeeId;
        }
    }

    return $recipients;
}

/**
 * Insert the domain row for a `data` payload and return its id (START HERE W3.2).
 * For the two tables that carry external_reference_id, an existing workforce row with
 * the same reference is reused instead of inserted twice.
 */
function wf_store_domain_row(PDO $pdo, string $type, array $data): ?int
{
    $text = fn($field, $default = null) => array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== ''
        ? wf_text($data[$field])
        : $default;

    switch ($type) {
        case 'workforce_competency_requirement':
            $stmt = $pdo->prepare(
                'INSERT INTO ld_competency_requirement
                     (source_module, skill_name, target_department, priority_level,
                      employees_needing_training, target_proficiency, expected_learning_output, notes)
                 VALUES
                     (:source_module, :skill_name, :target_department, :priority_level,
                      :employees_needing_training, :target_proficiency, :expected_learning_output, :notes)'
            );
            $stmt->execute([
                ':source_module'             => 'workforce',
                ':skill_name'                => $text('skill_name'),
                ':target_department'         => $text('target_department'),
                ':priority_level'            => $text('priority_level', 'medium'),
                ':employees_needing_training'=> (int) $data['employees_needing_training'],
                ':target_proficiency'        => $text('target_proficiency'),
                ':expected_learning_output'  => $text('expected_learning_output'),
                ':notes'                     => $text('notes'),
            ]);
            return (int) $pdo->lastInsertId();

        case 'workforce_reporting_requirement':
            $stmt = $pdo->prepare(
                'INSERT INTO ld_reporting_requirement
                     (source_module, target_name, reporting_period_start, reporting_period_end,
                      reporting_frequency, completion_target, expected_output, notes)
                 VALUES
                     (:source_module, :target_name, :reporting_period_start, :reporting_period_end,
                      :reporting_frequency, :completion_target, :expected_output, :notes)'
            );
            $stmt->execute([
                ':source_module'        => 'workforce',
                ':target_name'          => $text('target_name'),
                ':reporting_period_start'=> $text('reporting_period_start'),
                ':reporting_period_end' => $text('reporting_period_end'),
                ':reporting_frequency'  => $text('reporting_frequency', 'monthly'),
                ':completion_target'    => (float) $data['completion_target'],
                ':expected_output'      => $text('expected_output'),
                ':notes'                => $text('notes'),
            ]);
            return (int) $pdo->lastInsertId();

        case 'workforce_turnover_risk':
            $referenceId = $text('external_reference_id');
            if ($referenceId !== null) {
                $existing = $pdo->prepare(
                    "SELECT id FROM ld_turnover_risk
                      WHERE source_module = 'workforce' AND external_reference_id = :ref
                      LIMIT 1"
                );
                $existing->execute([':ref' => $referenceId]);
                $id = $existing->fetchColumn();
                if ($id !== false) {
                    return (int) $id;
                }
            }
            $stmt = $pdo->prepare(
                'INSERT INTO ld_turnover_risk
                     (source_module, external_reference_id, employee_id, employee_name, department,
                      position_name, risk_level, risk_score, risk_factors, recommended_action, assessment_date)
                 VALUES
                     (:source_module, :external_reference_id, :employee_id, :employee_name, :department,
                      :position_name, :risk_level, :risk_score, :risk_factors, :recommended_action, :assessment_date)'
            );
            $stmt->execute([
                ':source_module'         => 'workforce',
                ':external_reference_id' => $referenceId,
                ':employee_id'           => isset($data['employee_id']) ? (int) $data['employee_id'] : null,
                ':employee_name'         => $text('employee_name'),
                ':department'            => $text('department'),
                ':position_name'         => $text('position_name'),
                ':risk_level'            => $text('risk_level', 'medium'),
                ':risk_score'            => isset($data['risk_score']) ? (float) $data['risk_score'] : null,
                ':risk_factors'          => $text('risk_factors'),
                ':recommended_action'    => $text('recommended_action'),
                ':assessment_date'       => $text('assessment_date'),
            ]);
            return (int) $pdo->lastInsertId();

        case 'workforce_training_engagement':
            $referenceId = $text('external_reference_id');
            if ($referenceId !== null) {
                $existing = $pdo->prepare(
                    "SELECT id FROM ld_training_engagement
                      WHERE source_module = 'workforce' AND external_reference_id = :ref
                      LIMIT 1"
                );
                $existing->execute([':ref' => $referenceId]);
                $id = $existing->fetchColumn();
                if ($id !== false) {
                    return (int) $id;
                }
            }
            $stmt = $pdo->prepare(
                'INSERT INTO ld_training_engagement
                     (source_module, external_reference_id, learner_team_type, learner_id, learner_name,
                      department, training_activity, engagement_status, engagement_data, recommended_action, notes)
                 VALUES
                     (:source_module, :external_reference_id, :learner_team_type, :learner_id, :learner_name,
                      :department, :training_activity, :engagement_status, :engagement_data, :recommended_action, :notes)'
            );
            $stmt->execute([
                ':source_module'         => 'workforce',
                ':external_reference_id' => $referenceId,
                ':learner_team_type'     => $text('learner_team_type', 'individual-learner'),
                ':learner_id'            => isset($data['learner_id']) ? (int) $data['learner_id'] : null,
                ':learner_name'          => $text('learner_name'),
                ':department'            => $text('department'),
                ':training_activity'     => $text('training_activity', 'course'),
                ':engagement_status'     => $text('engagement_status', 'active'),
                ':engagement_data'       => $text('engagement_data'),
                ':recommended_action'    => $text('recommended_action'),
                ':notes'                 => $text('notes'),
            ]);
            return (int) $pdo->lastInsertId();
    }

    return null;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();

    ApiAuth::requireAuth($pdo, WF_MODULE);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        wf_respond(400, ['success' => false, 'message' => 'Invalid JSON payload']);
    }

    $error = wf_validate_envelope($input);
    if ($error !== null) {
        wf_respond(422, ['success' => false, 'error' => $error]);
    }

    $notificationId = trim(wf_text($input['notification_id']));
    $type = wf_text($input['type']);
    $title = trim(wf_text($input['title']));
    $message = trim(wf_text($input['message']));
    $referenceType = array_key_exists('reference_type', $input) && $input['reference_type'] !== null && $input['reference_type'] !== ''
        ? wf_text($input['reference_type'])
        : null;
    $referenceId = array_key_exists('reference_id', $input) && $input['reference_id'] !== null && $input['reference_id'] !== ''
        ? (int) $input['reference_id']
        : null;
    $data = isset($input['data']) && is_array($input['data']) ? $input['data'] : null;

    if ($data !== null) {
        $error = wf_validate_data($type, $data);
        if ($error !== null) {
            wf_respond(422, ['success' => false, 'error' => $error]);
        }
    }

    $log = new IntegrationLog($pdo);
    $extRefId = 'wa-notif-' . $notificationId;

    if ($log->isDuplicate(WF_MODULE, $extRefId)) {
        wf_respond(200, ['success' => true, 'message' => 'Already processed', 'duplicate' => true]);
    }

    $skipped = [];
    $recipients = wf_resolve_recipients($pdo, $input, $skipped);

    // Steps 3-5 of START HERE W3.3 commit together: the event marker, the domain row and
    // every notification row land as one unit, or none of them does.
    $pdo->beginTransaction();
    try {
        if (!$log->markProcessed(WF_MODULE, $extRefId, $type)) {
            $pdo->rollBack();
            wf_respond(200, ['success' => true, 'message' => 'Already processed', 'duplicate' => true]);
        }

        $recordId = $data !== null ? wf_store_domain_row($pdo, $type, $data) : null;
        if ($recordId !== null) {
            $referenceType = $referenceType ?? WF_DOMAIN_TABLES[$type];
            $referenceId = $referenceId ?? $recordId;
        }

        $insertNotification = $pdo->prepare(
            'INSERT INTO ld_notification (user_id, type, title, message, reference_type, reference_id, is_read)
             VALUES (:user_id, :type, :title, :message, :reference_type, :reference_id, 0)'
        );
        $notificationIds = [];
        foreach ($recipients as $employeeId) {
            $insertNotification->execute([
                ':user_id'        => $employeeId,
                ':type'           => $type,
                ':title'          => $title,
                ':message'        => $message,
                ':reference_type' => $referenceType,
                ':reference_id'   => $referenceId,
            ]);
            $notificationIds[] = (int) $pdo->lastInsertId();
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $log->logCall('inbound', WF_MODULE, WF_ENDPOINT, 'success', $input);

    $body = [
        'success'          => true,
        'message'          => 'Notification stored for ' . count($recipients) . ' recipient(s)',
        'notification_ids' => $notificationIds,
        'recipients'       => $recipients,
        'record_id'        => $recordId,
        'duplicate'        => false,
    ];
    if ($skipped !== []) {
        $body['skipped_recipients'] = $skipped;
    }
    wf_respond(200, $body);
} catch (Throwable $e) {
    if (isset($pdo) && isset($log)) {
        $log->logCall('inbound', WF_MODULE, WF_ENDPOINT, 'failed', $input ?? null, $e->getMessage());
    }
    wf_respond(500, ['success' => false, 'error' => $e->getMessage()]);
}
