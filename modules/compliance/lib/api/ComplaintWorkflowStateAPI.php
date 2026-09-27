<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/WorkflowEngine.php';

use Compliance\Workflow\WorkflowEngine;

header('Content-Type: application/json');

$sendJson = function (array $payload, int $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
};

try {
    $db = (new Database())->getConnection();
    if (!($db instanceof PDO)) {
        $sendJson(['success' => false, 'message' => 'Database connection unavailable.'], 500);
    }
} catch (Throwable $e) {
    $sendJson(['success' => false, 'message' => 'Database connection unavailable.'], 500);
}

try {
    $cols = $db->query("SHOW COLUMNS FROM lc_complaints WHERE Field IN ('current_step_key','current_state','version')")->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_diff(['current_step_key','current_state','version'], $cols);
    if ($missing !== []) {
        $db->beginTransaction();
        if (in_array('current_step_key', $missing, true)) {
            $db->exec("ALTER TABLE lc_complaints ADD COLUMN current_step_key VARCHAR(100) NULL AFTER `status`");
        }
        if (in_array('current_state', $missing, true)) {
            $db->exec("ALTER TABLE lc_complaints ADD COLUMN current_state VARCHAR(100) NULL AFTER current_step_key");
        }
        if (in_array('version', $missing, true)) {
            $db->exec("ALTER TABLE lc_complaints ADD COLUMN version INT UNSIGNED NOT NULL DEFAULT 1 AFTER current_state");
        }
        $db->commit();
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
}

$complaintId = isset($_GET['complaint_id']) ? (int) $_GET['complaint_id'] : 0;
if ($complaintId <= 0) {
    $sendJson(['success' => false, 'message' => 'Invalid complaint ID.']);
}

$userId = $_SESSION['employee_id'] ?? 0;

try {
    $stmt = $db->prepare("SELECT id, status, current_step_key, current_state, version FROM lc_complaints WHERE id = :id");
    $stmt->execute([':id' => $complaintId]);
    $case = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$case) {
        $sendJson(['success' => false, 'message' => 'Case not found.']);
    }
} catch (Throwable $e) {
    $sendJson(['success' => false, 'message' => 'Failed to load case: ' . $e->getMessage()], 500);
}

$userRole = '';
if ($userId > 0) {
    $stmt = $db->prepare("
        SELECT r.role_name
        FROM user_account u
        JOIN em_roles r ON r.role_id = u.role_id
        WHERE u.employee_id = :uid
        LIMIT 1
    ");
    $stmt->execute([':uid' => $userId]);
    $userRole = strtolower((string) $stmt->fetchColumn());
}

$workflowSteps = [
    ['key' => 'complaint_submitted',    'label' => 'Complaint Submitted', 'order' => 1],
    ['key' => 'assign_officer',         'label' => 'Assign Compliance Officer', 'order' => 2],
    ['key' => 'evidence_check',         'label' => 'Check Evidence and Complainant Testimony', 'order' => 3],
    ['key' => 'nte_issued',             'label' => 'Send NTE (Notice to Explain) to the Employee', 'order' => 4],
    ['key' => 'employee_hearing',       'label' => 'Hearing (Recording Employee Response)', 'order' => 5],
    ['key' => 'decision_made',          'label' => 'Decision', 'order' => 6],
    ['key' => 'termination_employee_reply', 'label' => 'Email Reply', 'order' => 7],
    ['key' => 'termination_review',     'label' => 'Review Action', 'order' => 8],
];

$currentStepKey = (string) ($case['current_step_key'] ?? 'complaint_submitted');
$currentState = strtolower((string) ($case['current_state'] ?? 'under_initial_review'));

$currentOrder = 0;
$stepOrderMap = [];
foreach ($workflowSteps as $s) {
    $stepOrderMap[$s['key']] = (int) $s['order'];
    if ($s['key'] === $currentStepKey) {
        $currentOrder = (int) $s['order'];
    }
}

$availableActions = [];
try {
    $engine = new WorkflowEngine($db);
    $allActions = $engine->allowedActions($currentStepKey, $currentState);

    foreach ($allActions as $actionKey => $transition) {
        if (!empty($transition['required_role']) && strtolower($transition['required_role']) !== $userRole) {
            continue;
        }

        $disabled = false;
        $disabledReason = '';

        if (!empty($transition['guard_expression'])) {
            $guard = str_replace(
                ['{complaint_id}', '{employee_id}'],
                [(int) $complaintId, (int) ($case['employee_id'] ?? 0)],
                $transition['guard_expression']
            );
            $allowed = (bool) \eval('return ' . $guard . ';');
            if (!$allowed) {
                $disabled = true;
                $disabledReason = 'Business prerequisites not met.';
            }
        }

        $availableActions[] = [
            'action' => $actionKey,
            'disabled' => $disabled,
            'disabled_reason' => $disabledReason,
        ];
    }
} catch (Throwable $e) {
    $availableActions = [];
}

$stepList = [];
foreach ($workflowSteps as $s) {
    $status = 'pending';
    $completedAt = null;

    if ($s['key'] === $currentStepKey) {
        $status = 'current';
    } elseif ($s['order'] < $currentOrder) {
        $status = 'completed';
        $completedAt = $case['updated_at'] ?? null;
    }

    $stepList[] = [
        'step_key' => $s['key'],
        'step_name' => $s['label'],
        'step_order' => (int) $s['order'],
        'status' => $status,
        'completed_at' => $completedAt,
    ];
}

$sendJson([
    'success' => true,
    'case_id' => (int) $case['id'],
    'case_number' => 'CMP-' . str_pad((string) $case['id'], 5, '0', \STR_PAD_LEFT),
    'current_step' => $currentStepKey,
    'current_state' => $currentState,
    'version' => (int) ($case['version'] ?? 1),
    'status' => $case['status'],
    'user_role' => $userRole,
    'steps' => $stepList,
    'available_actions' => $availableActions,
]);
