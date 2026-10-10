<?php

require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

$db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$incidentId = isset($_GET['incident_id']) ? (int) $_GET['incident_id'] : 0;
$stepKey = isset($_GET['workflow_step_key']) ? trim($_GET['workflow_step_key']) : '';

$method = $_SERVER['REQUEST_METHOD'];

$workflowStepLabels = [
    'incident_occurs'      => 'Incident Occurs',
    'legal_receives'       => 'Legal & Compliance Receives',
    'review_classify'      => 'Review & Classification',
    'investigation'        => 'Investigation Conducted',
    'hazard_check'         => 'Hazard Found?',
    'hazard_remediated_check' => 'Has the hazard been remediated?',
    'corrective_action'    => 'Corrective Action / Fix',
    'compliance_verify'    => 'Compliance Verification',
    'close_archive'        => 'Close & Archive',
];

if ($method === 'GET') {
    $incidentId = isset($_GET['incident_id']) ? (int) $_GET['incident_id'] : 0;
    $stepKey = isset($_GET['workflow_step_key']) ? trim($_GET['workflow_step_key']) : '';

    if ($incidentId <= 0 || $stepKey === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

    try {
        $incidentStmt = $db->prepare("SELECT status, description, title, incident_type, severity, requires_corrective_action FROM lc_incident_report WHERE id = :id");
        $incidentStmt->execute([':id' => $incidentId]);
        $incident = $incidentStmt->fetch();

        if (!$incident) {
            echo json_encode(['success' => false, 'message' => 'Incident not found.']);
            exit;
        }

        $stepLabel = $workflowStepLabels[$stepKey] ?? ucwords(str_replace('_', ' ', $stepKey));

        $localQuestionId = isset($_GET['local_question_id']) ? trim($_GET['local_question_id']) : '';

        if ($localQuestionId !== '') {
            try {
                $existingStmt = $db->prepare("SELECT id, question, purpose, can_advance_after_answer, answer, answered_at FROM lc_incident_workflow_questions WHERE incident_id = :incident_id AND workflow_step_key = :step_key AND local_question_id = :local_q AND answer IS NULL LIMIT 1");
                $existingStmt->execute([
                    ':incident_id' => $incidentId,
                    ':step_key'    => $stepKey,
                    ':local_q'     => $localQuestionId,
                ]);
                $existing = $existingStmt->fetch();

                if ($existing) {
                    echo json_encode([
                        'success' => true,
                        'question' => [
                            'id' => (int) $existing['id'],
                            'local_question_id' => $localQuestionId,
                            'current_step' => $stepLabel,
                            'question' => $existing['question'],
                            'purpose' => $existing['purpose'],
                            'can_advance_after_answer' => (bool) $existing['can_advance_after_answer'],
                            'answer' => $existing['answer'] ?? null,
                            'answered_at' => $existing['answered_at'] ?? null,
                        ],
                    ]);
                    exit;
                }

                $questionText = isset($_GET['question']) ? trim($_GET['question']) : '';
                $purposeText = isset($_GET['purpose']) ? trim($_GET['purpose']) : '';
                $canAdvance = isset($_GET['can_advance']) ? (bool) $_GET['can_advance'] : false;

                if ($questionText === '') {
                    echo json_encode(['success' => false, 'message' => 'Question text is required for local questions.']);
                    exit;
                }

                $insert = $db->prepare("INSERT INTO lc_incident_workflow_questions (incident_id, workflow_step_key, workflow_step_label, local_question_id, question, purpose, can_advance_after_answer, created_by) VALUES (:incident_id, :step_key, :step_label, :local_q, :question, :purpose, :can_advance, :created_by)");
                $insert->execute([
                    ':incident_id' => $incidentId,
                    ':step_key'    => $stepKey,
                    ':step_label'  => $stepLabel,
                    ':local_q'     => $localQuestionId,
                    ':question'    => $questionText,
                    ':purpose'     => $purposeText,
                    ':can_advance' => $canAdvance ? 1 : 0,
                    ':created_by'  => $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 1,
                ]);

                $questionId = (int) $db->lastInsertId();

                echo json_encode([
                    'success' => true,
                    'question' => [
                        'id' => $questionId,
                        'local_question_id' => $localQuestionId,
                        'current_step' => $stepLabel,
                        'question' => $questionText,
                        'purpose' => $purposeText,
                        'can_advance_after_answer' => $canAdvance,
                        'answer' => null,
                        'answered_at' => null,
                    ],
                ]);
                exit;
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
                exit;
            }
        }

        $existingStmt = $db->prepare("SELECT id, question, purpose, can_advance_after_answer, answer, answered_at FROM lc_incident_workflow_questions WHERE incident_id = :incident_id AND workflow_step_key = :step_key AND answer IS NULL ORDER BY id ASC LIMIT 1");
        $existingStmt->execute([
            ':incident_id' => $incidentId,
            ':step_key'    => $stepKey,
        ]);
        $existing = $existingStmt->fetch();

        if ($existing) {
            echo json_encode([
                'success' => true,
                'question' => [
                    'id' => (int) $existing['id'],
                    'current_step' => $stepLabel,
                    'question' => $existing['question'],
                    'purpose' => $existing['purpose'],
                    'can_advance_after_answer' => (bool) $existing['can_advance_after_answer'],
                    'answer' => $existing['answer'] ?? null,
                    'answered_at' => $existing['answered_at'] ?? null,
                ],
            ]);
            exit;
        }

        $question = '';
        $purpose = '';
        $canAdvance = false;

        $questionTemplates = [
            'incident_occurs' => [
                ['q' => 'What specifically happened during the incident?', 'p' => 'To establish the factual basis of the incident.'],
                ['q' => 'When did the incident occur? Please provide the date and time.', 'p' => 'To document the timeline accurately.'],
                ['q' => 'Where did the incident take place?', 'p' => 'To record the location for reporting and prevention.'],
                ['q' => 'Who was involved in or witnessed the incident?', 'p' => 'To identify all relevant persons.'],
            ],
            'legal_receives' => [
                ['q' => 'Has the submitted report been received and logged by Legal & Compliance?', 'p' => 'To confirm receipt and initiate formal review.'],
                ['q' => 'Is any required documentation or information missing from the submission?', 'p' => 'To identify gaps before proceeding.'],
            ],
            'review_classify' => [
                ['q' => 'What is the appropriate severity classification for this incident?', 'p' => 'To assign the correct severity level.'],
                ['q' => 'Which category or type best describes this incident?', 'p' => 'To classify the incident for reporting.'],
                ['q' => 'What is the estimated impact or consequence of this incident?', 'p' => 'To evaluate the significance of the incident.'],
                ['q' => 'Are there any applicable compliance requirements triggered by this incident?', 'p' => 'To identify regulatory obligations.'],
            ],
            'investigation' => [
                ['q' => 'Has an investigation been initiated, and has evidence been collected?', 'p' => 'To confirm investigation status.'],
                ['q' => 'Have all relevant witnesses been identified and interviewed?', 'p' => 'To verify information gathering.'],
                ['q' => 'Have relevant records and documentation been reviewed?', 'p' => 'To ensure factual basis.'],
                ['q' => 'Is there sufficient evidence to establish what happened?', 'p' => 'To determine readiness for root cause analysis.', 'can_advance' => true],
            ],
            'hazard_check' => [
                ['q' => 'Does the property need to fix this hazard?', 'p' => 'To determine if the property owner is responsible for corrective action.'],
            ],
            'compliance_verify' => [
                ['q' => 'Have all required compliance controls been implemented and verified?', 'p' => 'To confirm compliance status.', 'can_advance' => true],
            ],
            'close_archive' => [
                ['q' => 'Are all required actions complete, and is the incident ready to be formally closed and archived?', 'p' => 'To obtain final closure confirmation.', 'can_advance' => true],
            ],
        ];

        $templates = $questionTemplates[$stepKey] ?? [
            ['q' => 'What information is still needed to complete this step?', 'p' => 'To identify missing information.'],
        ];

        $collectedCount = 0;
        $countStmt = $db->prepare("SELECT COUNT(DISTINCT workflow_step_key) AS cnt FROM lc_incident_workflow_questions WHERE incident_id = :incident_id AND answer IS NOT NULL");
        $countStmt->execute([':incident_id' => $incidentId]);
        $countRow = $countStmt->fetch();
        $collectedCount = (int) ($countRow['cnt'] ?? 0);

        $templateIndex = min($collectedCount, count($templates) - 1);
        $selected = $templates[$templateIndex];
        $question = $selected['q'];
        $purpose = $selected['p'];
        $canAdvance = isset($selected['can_advance']) ? (bool) $selected['can_advance'] : false;

        $insert = $db->prepare("INSERT INTO lc_incident_workflow_questions (incident_id, workflow_step_key, workflow_step_label, question, purpose, can_advance_after_answer, created_by) VALUES (:incident_id, :step_key, :step_label, :question, :purpose, :can_advance, :created_by)");
        $insert->execute([
            ':incident_id' => $incidentId,
            ':step_key'    => $stepKey,
            'step_label'   => $stepLabel,
            ':question'    => $question,
            ':purpose'     => $purpose,
            ':can_advance' => $canAdvance ? 1 : 0,
            ':created_by'  => $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 1,
        ]);

        $questionId = (int) $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'question' => [
                'id' => $questionId,
                'current_step' => $stepLabel,
                'question' => $question,
                'purpose' => $purpose,
                'can_advance_after_answer' => $canAdvance,
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

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $questionId = isset($input['id']) ? $input['id'] : '';
    $localQuestionId = isset($input['local_question_id']) ? trim((string) $input['local_question_id']) : '';
    $answer = isset($input['answer']) ? trim((string) $input['answer']) : '';
    $questionText = isset($input['question']) ? trim((string) $input['question']) : '';
    $purposeText = isset($input['purpose']) ? trim((string) $input['purpose']) : '';
    $canAdvance = isset($input['can_advance']) ? (bool) $input['can_advance'] : false;

    $postIncidentId = isset($input['incident_id']) ? (int) $input['incident_id'] : 0;
    $postStepKey = isset($input['workflow_step_key']) ? trim((string) $input['workflow_step_key']) : '';

    $resolvedIncidentId = $postIncidentId > 0 ? $postIncidentId : $incidentId;
    $resolvedStepKey = $postStepKey !== '' ? $postStepKey : $stepKey;

    $dbQuestionId = null;

    if ($localQuestionId !== '') {
        $stmt = $db->prepare("SELECT id FROM lc_incident_workflow_questions WHERE incident_id = :incident_id AND workflow_step_key = :step_key AND local_question_id = :local_q AND answer IS NULL LIMIT 1");
        $stmt->execute([
            ':incident_id' => $resolvedIncidentId,
            ':step_key'    => $resolvedStepKey,
            ':local_q'     => $localQuestionId,
        ]);
        $row = $stmt->fetch();
        if ($row) {
            $dbQuestionId = (int) $row['id'];
        } elseif ($questionText !== '') {
            $stepLabel = $workflowStepLabels[$resolvedStepKey] ?? ucwords(str_replace('_', ' ', $resolvedStepKey));
            $insert = $db->prepare("INSERT INTO lc_incident_workflow_questions (incident_id, workflow_step_key, workflow_step_label, local_question_id, question, purpose, can_advance_after_answer, created_by) VALUES (:incident_id, :step_key, :step_label, :local_q, :question, :purpose, :can_advance, :created_by)");
            $insert->execute([
                ':incident_id' => $resolvedIncidentId,
                ':step_key'    => $resolvedStepKey,
                ':step_label'  => $stepLabel,
                ':local_q'     => $localQuestionId,
                ':question'    => $questionText,
                ':purpose'     => $purposeText,
                ':can_advance' => $canAdvance ? 1 : 0,
                ':created_by'  => $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 1,
            ]);
            $dbQuestionId = (int) $db->lastInsertId();
        }
    } elseif ($questionId !== '' && is_numeric($questionId)) {
        $dbQuestionId = (int) $questionId;
    }

    if ($dbQuestionId <= 0 || $answer === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid request.', 'debug' => [
            'dbQuestionId' => $dbQuestionId,
            'answerEmpty' => $answer === '',
            'resolvedIncidentId' => $resolvedIncidentId ?? null,
            'resolvedStepKey' => $resolvedStepKey ?? null,
            'localQuestionId' => $localQuestionId,
        ]]);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT id FROM lc_incident_workflow_questions WHERE id = :id AND answer IS NULL");
        $stmt->execute([':id' => $dbQuestionId]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Question not found or already answered.']);
            exit;
        }

        $update = $db->prepare("UPDATE lc_incident_workflow_questions SET answer = :answer, answered_at = NOW() WHERE id = :id");
        $update->execute([
            ':answer' => $answer,
            ':id'     => $dbQuestionId,
        ]);

        echo json_encode(['success' => true, 'message' => 'Answer saved.']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
exit;
