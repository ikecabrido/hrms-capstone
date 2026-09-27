<?php

require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

$db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$method = $_SERVER['REQUEST_METHOD'];

function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES);
}

$userId = $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 1;

if ($method === 'GET') {
    $complaintId = isset($_GET['complaint_id']) ? (int) $_GET['complaint_id'] : 0;
    $stepKey = isset($_GET['workflow_step_key']) ? trim($_GET['workflow_step_key']) : '';
    $force = isset($_GET['force']) ? trim((string)$_GET['force']) : '';

    if ($complaintId <= 0 || $stepKey === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT id, status, type, severity, description, assigned_to FROM lc_complaints WHERE id = :id");
        $stmt->execute([':id' => $complaintId]);
        $complaint = $stmt->fetch();

        if (!$complaint) {
            echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
            exit;
        }

        $workflowStepLabels = [
            'complaint_submitted'       => 'Complaint Submitted',
            'assign_officer'            => 'Assign Compliance Officer',
            'evidence_check'            => 'Check Evidence and Complainant Testimony',
            'nte_issued'                => 'Send NTE (Notice to Explain) to the Employee',
            'employee_hearing'          => 'Hearing (Recording Employee Response)',
            'decision_made'             => 'Decision',
            'termination_employee_reply'=> 'Email Reply',
            'termination_review'        => 'Review Action',
        ];

        $stepLabel = $workflowStepLabels[$stepKey] ?? ucwords(str_replace('_', ' ', $stepKey));

        if ($force === 'new') {
            $question = getQuestionForStep($stepKey, $complaint, $db);
            $insert = $db->prepare("INSERT INTO lc_complaint_workflow_questions (complaint_id, workflow_step_key, workflow_step_label, question, purpose, question_type, options, required, can_advance_after_answer, created_by) VALUES (:cid, :sk, :sl, :q, :p, :qt, :opts, 1, 0, :uid)");
            $insert->execute([
                ':cid' => $complaintId,
                ':sk' => $stepKey,
                ':sl' => $stepLabel,
                ':q' => $question['question'],
                ':p' => $question['purpose'],
                ':qt' => $question['type'],
                ':opts' => !empty($question['options']) ? json_encode($question['options']) : null,
                ':uid' => $userId,
            ]);
            $questionId = (int) $db->lastInsertId();
            echo json_encode([
                'success' => true,
                'question' => [
                    'id' => $questionId,
                    'current_step' => $stepLabel,
                    'question' => $question['question'],
                    'purpose' => $question['purpose'],
                    'type' => $question['type'],
                    'options' => $question['options'] ?? null,
                    'can_advance_after_answer' => false,
                    'answer' => null,
                    'answered_at' => null,
                ],
            ]);
            exit;
        }

        $existingStmt = $db->prepare("SELECT id, question, purpose, question_type, options, can_advance_after_answer, answer, answered_at FROM lc_complaint_workflow_questions WHERE complaint_id = :cid AND workflow_step_key = :sk AND answer IS NULL ORDER BY id ASC LIMIT 1");
        $existingStmt->execute([':cid' => $complaintId, ':sk' => $stepKey]);
        $existing = $existingStmt->fetch();

        if ($existing) {
            echo json_encode([
                'success' => true,
                'question' => [
                    'id' => (int) $existing['id'],
                    'current_step' => $stepLabel,
                    'question' => $existing['question'],
                    'purpose' => $existing['purpose'],
                    'type' => $existing['question_type'] ?? 'free_text',
                    'options' => $existing['options'] ?? null,
                    'can_advance_after_answer' => (bool)$existing['can_advance_after_answer'],
                    'answer' => $existing['answer'] ?? null,
                    'answered_at' => $existing['answered_at'] ?? null,
                ],
            ]);
            exit;
        }

        $question = getQuestionForStep($stepKey, $complaint, $db);

        $insert = $db->prepare("INSERT INTO lc_complaint_workflow_questions (complaint_id, workflow_step_key, workflow_step_label, question, purpose, question_type, options, required, can_advance_after_answer, created_by) VALUES (:cid, :sk, :sl, :q, :p, :qt, :opts, 1, 0, :uid)");
        $insert->execute([
            ':cid' => $complaintId,
            ':sk' => $stepKey,
            ':sl' => $stepLabel,
            ':q' => $question['question'],
            ':p' => $question['purpose'],
            ':qt' => $question['type'],
            ':opts' => !empty($question['options']) ? json_encode($question['options']) : null,
            ':uid' => $userId,
        ]);
        $questionId = (int) $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'question' => [
                'id' => $questionId,
                'current_step' => $stepLabel,
                'question' => $question['question'],
                'purpose' => $question['purpose'],
                'type' => $question['type'],
                'options' => $question['options'] ?? null,
                'can_advance_after_answer' => false,
                'answer' => null,
                'answered_at' => null,
            ],
        ]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
    exit;
}

function getQuestionForStep($stepKey, $complaint, $db) {
    $caseType = strtolower($complaint['type'] ?? '');
    $caseStatus = strtolower($complaint['status'] ?? '');
    $severity = strtolower($complaint['severity'] ?? 'medium');
    $hasResponse = !empty($complaint['employee_response']);

    $stepQuestions = [
        'complaint_submitted' => [
            ['q' => 'What is the primary type of this complaint?', 'p' => 'To classify the complaint and determine the appropriate workflow path.', 'type' => 'single_choice', 'options' => ['Voluntary Exit' => 'Voluntary Exit', 'Terminated' => 'Terminated', 'Policy Violation' => 'Policy Violation', 'Misconduct' => 'Misconduct', 'Performance Issue' => 'Performance Issue', 'Other' => 'Other'], 'can_advance' => true],
            ['q' => 'Was the complaint formally submitted in writing?', 'p' => 'To verify the formal basis of the complaint.', 'type' => 'yes_no'],
            ['q' => 'Is there a submitted complaint document or report available?', 'p' => 'To ensure supporting documentation exists.', 'type' => 'yes_no'],
        ],
        'assign_officer' => [
            ['q' => 'Has a Compliance Officer been assigned to this case?', 'p' => 'To confirm case ownership.', 'type' => 'yes_no'],
        ],
        'evidence_check' => [
            ['q' => 'Is the complaint / incident report available?', 'p' => 'To verify primary documentation.', 'type' => 'yes_no'],
            ['q' => 'Is the employee statement available?', 'p' => 'To verify employee response documentation.', 'type' => 'yes_no'],
            ['q' => 'Are witness statements available?', 'p' => 'To verify witness documentation.', 'type' => 'yes_no'],
            ['q' => 'Is the relevant policy documented?', 'p' => 'To verify applicable policy reference.', 'type' => 'yes_no'],
            ['q' => 'Are supporting documents available?', 'p' => 'To verify evidence completeness.', 'type' => 'yes_no'],
            ['q' => 'Are investigation notes available?', 'p' => 'To verify investigation record.', 'type' => 'yes_no'],
        ],
        'nte_issued' => [
            ['q' => 'Was an NTE issued to the employee?', 'p' => 'To verify NTE issuance.', 'type' => 'yes_no'],
            ['q' => 'What is the response deadline?', 'p' => 'To document the response timeline.', 'type' => 'date'],
            ['q' => 'Was the response received?', 'p' => 'To determine if employee responded.', 'type' => 'yes_no', 'can_advance' => true],
        ],
        'employee_hearing' => [
            ['q' => 'Does the response introduce new facts?', 'p' => 'To identify if new evidence requires investigation.', 'type' => 'yes_no'],
            ['q' => 'Does the response dispute the findings?', 'p' => 'To assess dispute status.', 'type' => 'yes_no'],
            ['q' => 'Does additional evidence need to be reviewed?', 'p' => 'To determine if review is needed.', 'type' => 'yes_no'],
        ],
        'decision_made' => [
            ['q' => 'What is the decision outcome?', 'p' => 'To record the decision.', 'type' => 'single_choice', 'options' => ['Written Warning' => 'Written Warning', 'Second Written Warning' => 'Second Written Warning', 'Final Written Warning' => 'Final Written Warning', 'Termination' => 'Termination'], 'can_advance' => true],
            ['q' => 'What is the rationale for this decision?', 'p' => 'To document the decision basis.', 'type' => 'free_text'],
        ],
        'termination_employee_reply' => [
            ['q' => 'Has the employee replied to the termination notice?', 'p' => 'To capture employee response to termination.', 'type' => 'yes_no'],
            ['q' => 'What is the nature of the employee response?', 'p' => 'To document the termination reply.', 'type' => 'single_choice', 'options' => ['Accepted' => 'Accepted', 'Disputed' => 'Disputed', 'No response' => 'No response'], 'can_advance' => true],
        ],
        'termination_review' => [
            ['q' => 'Was the termination reviewed and considered?', 'p' => 'To finalize the termination process.', 'type' => 'single_choice', 'options' => ['Rejected' => 'Rejected', 'Considered' => 'Considered'], 'can_advance' => true],
            ['q' => 'What is the basis for this review decision?', 'p' => 'To document the review rationale.', 'type' => 'free_text'],
        ],
    ];

    $templates = $stepQuestions[$stepKey] ?? [
        ['q' => 'Please provide any additional information needed for this step.', 'p' => 'To capture relevant information.', 'type' => 'free_text'],
    ];

    $collectedStmt = $db->prepare("SELECT COUNT(DISTINCT id) as cnt FROM lc_complaint_workflow_questions WHERE complaint_id = :cid AND workflow_step_key = :sk AND answer IS NOT NULL");
    $collectedStmt->execute([':cid' => $complaint['id'], ':sk' => $stepKey]);
    $collected = (int) ($collectedStmt->fetch()['cnt'] ?? 0);

    $templateIndex = min($collected, count($templates) - 1);
    $selected = $templates[$templateIndex];

    return [
        'question' => $selected['q'],
        'purpose' => $selected['p'],
        'type' => $selected['type'] ?? 'free_text',
        'options' => $selected['options'] ?? null,
        'can_advance' => isset($selected['can_advance']) ? (bool) $selected['can_advance'] : false,
    ];
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $questionId = isset($input['id']) ? (int) $input['id'] : 0;
    $answer = isset($input['answer']) ? $input['answer'] : '';
    $isRequired = isset($input['required']) ? (bool) $input['required'] : true;

    if ($questionId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid question ID.']);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT id, question_type, required, complaint_id, workflow_step_key FROM lc_complaint_workflow_questions WHERE id = :id AND answer IS NULL");
        $stmt->execute([':id' => $questionId]);
        $qRow = $stmt->fetch();

        if (!$qRow) {
            echo json_encode(['success' => false, 'message' => 'Question not found or already answered.']);
            exit;
        }

        if ($isRequired && $answer === '' && $answer !== 0 && $answer !== false) {
            echo json_encode(['success' => false, 'message' => 'This question requires an answer.', 'validation_error' => true]);
            exit;
        }

        $encodedAnswer = is_array($answer) ? json_encode($answer) : (string)$answer;

        $update = $db->prepare("UPDATE lc_complaint_workflow_questions SET answer = :answer, answered_at = NOW() WHERE id = :id");
        $update->execute([
            ':answer' => $encodedAnswer,
            ':id' => $questionId,
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Answer saved.',
            'next_question' => $answer !== '' && $answer !== 0 && $answer !== false,
        ]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
    exit;
}

$action = $input['action'] ?? '';

if ($action === 'save_decision') {
    $stepKey = isset($input['workflow_step_key']) ? trim((string)$input['workflow_step_key']) : '';
    $decision = isset($input['decision']) ? trim((string)$input['decision']) : '';
    $rationale = isset($input['rationale']) ? trim((string)$input['rationale']) : '';
    $answers = isset($input['answers']) ? $input['answers'] : [];

    if ($complaintId <= 0 || $stepKey === '' || $decision === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid decision data.']);
        exit;
    }

    try {
        $insert = $db->prepare("INSERT INTO lc_complaint_workflow_decisions (complaint_id, workflow_step_key, workflow_step_label, decision, rationale, answers, decided_by, decided_at) VALUES (:cid, :sk, :sl, :d, :r, :a, :uid, NOW())");
        $stepLabel = ucwords(str_replace('_', ' ', $stepKey));
        $insert->execute([
            ':cid' => $complaintId,
            ':sk' => $stepKey,
            ':sl' => $stepLabel,
            ':d' => $decision,
            ':r' => $rationale,
            ':a' => json_encode($answers),
            ':uid' => $userId,
        ]);
        echo json_encode(['success' => true, 'message' => 'Decision saved.']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_decisions') {
    try {
        $stmt = $db->prepare("SELECT * FROM lc_complaint_workflow_decisions WHERE complaint_id = :cid ORDER BY decided_at ASC");
        $stmt->execute([':cid' => $complaintId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'decisions' => $rows]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_progress') {
    try {
        $progress = json_encode([
            'decisions' => isset($input['decisions']) ? $input['decisions'] : [],
            'evidence' => isset($input['evidence']) ? $input['evidence'] : [],
            'current_question_id' => isset($input['current_question_id']) ? $input['current_question_id'] : null,
        ]);
        $update = $db->prepare("UPDATE lc_complaints SET workflow_progress = :p, updated_at = NOW() WHERE id = :id");
        $update->execute([':p' => $progress, ':id' => $complaintId]);
        echo json_encode(['success' => true, 'message' => 'Progress saved.']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'reopen') {
    $stepKey = isset($input['workflow_step_key']) ? trim((string)$input['workflow_step_key']) : '';
    $reason = isset($input['reason']) ? trim((string)$input['reason']) : '';
    $prevStatus = isset($input['previous_status']) ? trim((string)$input['previous_status']) : '';
    $newStatus = isset($input['new_status']) ? trim((string)$input['new_status']) : 'reopened';

    if ($complaintId <= 0 || $stepKey === '' || $reason === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid reopen data.']);
        exit;
    }

    try {
        $stepLabel = ucwords(str_replace('_', ' ', $stepKey));
        $insert = $db->prepare("INSERT INTO lc_complaint_workflow_reopens (complaint_id, workflow_step_key, workflow_step_label, reason, previous_status, new_status, decided_by, decided_at) VALUES (:cid, :sk, :sl, :r, :ps, :ns, :uid, NOW())");
        $insert->execute([
            ':cid' => $complaintId,
            ':sk' => $stepKey,
            ':sl' => $stepLabel,
            ':r' => $reason,
            ':ps' => $prevStatus,
            ':ns' => $newStatus,
            ':uid' => $userId,
        ]);
        echo json_encode(['success' => true, 'message' => 'Step reopened.']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
exit;

