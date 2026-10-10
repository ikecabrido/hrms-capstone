<?php

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$db = $database->getConnection();

if (!($db instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

try {
    $tables = ['lc_external_case_events', 'lc_external_case_references', 'lc_external_case_notes'];
    $missing = [];
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE " . $db->quote($table));
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $missing[] = $table;
        }
    }
    if ($missing) {
        $sql = file_get_contents(__DIR__ . '/../../sql/external_cases.sql');
        if ($sql !== false) {
            $db->beginTransaction();
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                if ($statement !== '') {
                    $db->exec($statement);
                }
            }
            $db->commit();
        }
    }
    $stmt = $db->prepare("SHOW COLUMNS FROM lc_legal_cases LIKE 'specific_case_type'");
    $stmt->execute();
    if (!$stmt->fetchColumn()) {
        $db->exec("ALTER TABLE lc_legal_cases ADD COLUMN specific_case_type varchar(255) DEFAULT NULL AFTER case_type");
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

if (!function_exists('send_json')) {
    function send_json($payload, $code = 200) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        if ($code !== 200) { http_response_code($code); }
        header('Content-Type: application/json; charset=utf-8');
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to encode API response.', 'json_error' => json_last_error_msg()]);
        } else {
            echo $json;
        }
        exit;
    }
}

$currentEmployeeId = $_SESSION['employee_id'] ?? null;
if ($currentEmployeeId === null) {
    send_json(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$action = strtolower(trim((string) ($_POST['action'] ?? $_GET['action'] ?? '')));

try {
    require_once __DIR__ . '/../../classes/LegalCaseManager.php';
    require_once __DIR__ . '/../../classes/LaborLawReference.php';

    $manager = new LegalCaseManager($db);
    $llm     = new LaborLawReference($db);

    if ($action === '') {
        send_json(['success' => false, 'message' => 'Missing action parameter.'], 400);
    }

    switch ($action) {
        case 'list_cases': {
            $search     = trim((string) ($_GET['search'] ?? ''));
            $status     = trim((string) ($_GET['status'] ?? ''));
            $agency     = trim((string) ($_GET['agency'] ?? ''));
            $caseType   = trim((string) ($_GET['case_type'] ?? ''));
            $page       = max(1, (int) ($_GET['page'] ?? 1));
            $pageSize   = min(50, max(1, (int) ($_GET['page_size'] ?? 15)));

            $filters = [];
            if ($search !== '')   $filters['search'] = $search;
            if ($status !== '')   $filters['current_status'] = $status;
            if ($agency !== '')   $filters['external_agency'] = $agency;
            if ($caseType !== '') $filters['case_type'] = $caseType;

            $result = $manager->listCases($filters, $page, $pageSize);
            $stats = $manager->getExternalCaseStats();
            send_json(['success' => true, 'data' => $result['data'], 'total' => $result['total'], 'page' => $result['page'], 'total_pages' => $result['total_pages'], 'stats' => $stats]);
        }
        case 'case_detail': {
            $caseId = isset($_GET['case_id']) ? (int) $_GET['case_id'] : 0;
            if ($caseId <= 0) {
                send_json(['success' => false, 'message' => 'Invalid case ID.'], 400);
            }
            $case = $manager->getCase($caseId);
            if (!$case) {
                send_json(['success' => false, 'message' => 'Case not found.'], 404);
            }
            $relatedComplaint = null;
            $relatedType = null;
            $relatedRecord = null;
            if (!empty($case['complaint_id'])) {
                $relatedComplaint = $db->prepare("SELECT id, type, description, status, title FROM lc_complaints WHERE id = :id LIMIT 1");
                $relatedComplaint->execute([':id' => (int) $case['complaint_id']]);
                $relatedComplaint = $relatedComplaint->fetch(PDO::FETCH_ASSOC);
                if ($relatedComplaint) {
                    $relatedType = 'complaint';
                    $relatedRecord = $relatedComplaint;
                } else {
                    $relatedIncident = $db->prepare("SELECT id, incident_type, title, description, incident_id FROM lc_incident_report WHERE id = :id LIMIT 1");
                    $relatedIncident->execute([':id' => (int) $case['complaint_id']]);
                    $relatedIncident = $relatedIncident->fetch(PDO::FETCH_ASSOC);
                    if ($relatedIncident) {
                        $relatedType = 'incident';
                        $relatedRecord = $relatedIncident;
                    }
                }
            }
            $assignedName = null;
            if (!empty($case['assigned_to'])) {
                $stmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) AS assigned_name FROM em_employees WHERE employee_id = :id LIMIT 1");
                $stmt->execute([':id' => (int) $case['assigned_to']]);
                $assignedName = $stmt->fetchColumn();
            }
            if ($assignedName !== null) {
                $case['assigned_name'] = $assignedName;
            }
            send_json([
                'success' => true,
                'case' => $case,
                'events' => $manager->getExternalCaseEvents($caseId),
                'notes' => $manager->getExternalCaseNotes($caseId),
                'references' => $manager->getExternalCaseReferences($caseId),
                'documents' => $manager->getDocuments($caseId),
                'related_complaint' => $relatedComplaint,
                'related_type' => $relatedType,
                'related_record' => $relatedRecord,
            ]);
        }
        case 'create_case': {
            $required = ['case_source', 'case_title', 'date_received', 'current_status'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    send_json(['success' => false, 'message' => "Missing required field: {$field}."], 400);
                }
            }
            $caseType = trim((string) ($_POST['case_type'] ?? ''));
            $agency = !empty($_POST['external_agency']) ? trim((string) $_POST['external_agency']) : '';
            if ($caseType === '' && $agency !== '') {
                $mappings = [
                    'DOLE' => 'Labor / Employment',
                    'NLRC' => 'Labor / Employment',
                    'NCMB' => 'Labor / Employment',
                    'DepEd' => 'Academic / Education',
                    'CHED' => 'Regulatory Compliance',
                    'POEA/DMW' => 'Labor / Employment',
                    'Court' => 'Labor / Employment',
                    'Other Government Agency' => 'Regulatory Compliance',
                    'Other Regulatory Authority' => 'Regulatory Compliance'
                ];
                $caseType = $mappings[$agency] ?? 'Other';
            }
            $data = [
                'case_title'         => trim((string) $_POST['case_title']),
                'case_type'          => $caseType,
                'specific_case_type' => !empty($_POST['specific_case_type']) ? trim((string) $_POST['specific_case_type']) : null,
                'case_source'        => trim((string) $_POST['case_source']),
                'external_agency'    => $agency !== '' ? $agency : null,
                'external_reference_no' => !empty($_POST['external_reference_no']) ? trim((string) $_POST['external_reference_no']) : null,
                'docket_no'          => !empty($_POST['docket_no']) ? trim((string) $_POST['docket_no']) : null,
                'description'        => !empty($_POST['description']) ? trim((string) $_POST['description']) : null,
                'date_received'      => !empty($_POST['date_received']) ? trim((string) $_POST['date_received']) : null,
                'date_filed'         => !empty($_POST['date_filed']) ? trim((string) $_POST['date_filed']) : null,
                'priority'           => !empty($_POST['priority']) ? trim((string) $_POST['priority']) : 'Medium',
                'current_status'     => !empty($_POST['current_status']) ? trim((string) $_POST['current_status']) : 'Draft',
                'current_stage'      => !empty($_POST['current_stage']) ? trim((string) $_POST['current_stage']) : 'Case Intake',
                'assigned_to'        => !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null,
                'complaint_id'       => !empty($_POST['complaint_id']) ? (int) $_POST['complaint_id'] : null,
                'employee_id'        => !empty($_POST['employee_id']) ? (int) $_POST['employee_id'] : null,
                'created_by'         => $currentEmployeeId,
            ];
            $case = $manager->createCase($data);
            send_json(['success' => true, 'case' => $case, 'message' => 'Case created successfully.']);
        }
        case 'update_case': {
            $caseId = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 0;
            if ($caseId <= 0) {
                send_json(['success' => false, 'message' => 'Invalid case ID.'], 400);
            }
            $allowed = ['case_title', 'case_type', 'case_source', 'external_agency', 'external_reference_no', 'docket_no', 'description', 'date_received', 'date_filed', 'priority', 'current_status', 'current_stage', 'assigned_to', 'complaint_id', 'employee_id', 'resolution', 'date_resolved', 'date_closed', 'specific_case_type'];
            $data = [];
            foreach ($allowed as $field) {
                if (isset($_POST[$field])) {
                    $val = $_POST[$field];
                    $data[$field] = ($val === '' || $val === null) ? null : $val;
                }
            }
            $case = $manager->updateCase($caseId, $data);
            send_json(['success' => true, 'case' => $case, 'message' => 'Case updated successfully.']);
        }
        case 'add_event': {
            $caseId = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 0;
            if ($caseId <= 0) {
                send_json(['success' => false, 'message' => 'Invalid case ID.'], 400);
            }
            $eventData = [
                'event_type'  => trim((string) ($_POST['event_type'] ?? 'Other')),
                'title'       => trim((string) ($_POST['title'] ?? '')),
                'description' => !empty($_POST['description']) ? trim((string) $_POST['description']) : null,
                'event_date'  => !empty($_POST['event_date']) ? trim((string) $_POST['event_date']) : null,
                'start_time'  => !empty($_POST['start_time']) ? trim((string) $_POST['start_time']) : null,
                'end_time'    => !empty($_POST['end_time']) ? trim((string) $_POST['end_time']) : null,
                'location'    => !empty($_POST['location']) ? trim((string) $_POST['location']) : null,
                'status'      => !empty($_POST['status']) ? trim((string) $_POST['status']) : 'Scheduled',
                'created_by'  => $currentEmployeeId,
            ];
            $events = $manager->addExternalCaseEvent($caseId, $eventData);
            send_json(['success' => true, 'events' => $events, 'message' => 'Event added.']);
        }
        case 'update_event': {
            $caseId = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 0;
            $eventId = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
            if ($caseId <= 0 || $eventId <= 0) {
                send_json(['success' => false, 'message' => 'Invalid case or event ID.'], 400);
            }
            $update = [];
            foreach (['event_type','title','description','event_date','start_time','end_time','location','status'] as $f) {
                if (isset($_POST[$f])) {
                    $update[$f] = ($_POST[$f] === '' || $_POST[$f] === null) ? null : $_POST[$f];
                }
            }
            $events = $manager->updateExternalCaseEvent($eventId, $caseId, $update);
            send_json(['success' => true, 'events' => $events, 'message' => 'Event updated.']);
        }
        case 'delete_event': {
            $caseId = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 0;
            $eventId = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
            if ($caseId <= 0 || $eventId <= 0) {
                send_json(['success' => false, 'message' => 'Invalid case or event ID.'], 400);
            }
            $manager->deleteExternalCaseEvent($eventId, $caseId);
            send_json(['success' => true, 'message' => 'Event deleted.']);
        }
        case 'add_note': {
            $caseId = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 0;
            $note = trim((string) ($_POST['note'] ?? ''));
            if ($caseId <= 0 || $note === '') {
                send_json(['success' => false, 'message' => 'Invalid case ID or empty note.'], 400);
            }
            $notes = $manager->addExternalCaseNote($caseId, $note, $currentEmployeeId);
            send_json(['success' => true, 'notes' => $notes, 'message' => 'Note added.']);
        }
        case 'delete_note': {
            $caseId = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 0;
            $noteId = isset($_POST['note_id']) ? (int) $_POST['note_id'] : 0;
            if ($caseId <= 0 || $noteId <= 0) {
                send_json(['success' => false, 'message' => 'Invalid case or note ID.'], 400);
            }
            $manager->deleteExternalCaseNote($noteId, $caseId);
            send_json(['success' => true, 'message' => 'Note deleted.']);
        }
        case 'attach_reference': {
            $caseId = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 0;
            $referenceId = isset($_POST['reference_id']) ? (int) $_POST['reference_id'] : 0;
            $relationType = trim((string) ($_POST['relation_type'] ?? 'Attached'));
            $notes = !empty($_POST['notes']) ? trim((string) $_POST['notes']) : null;
            if ($caseId <= 0 || $referenceId <= 0) {
                send_json(['success' => false, 'message' => 'Invalid case or reference ID.'], 400);
            }
            $refs = $manager->attachExternalCaseReference($caseId, $referenceId, $relationType, $notes, $currentEmployeeId);
            send_json(['success' => true, 'references' => $refs, 'message' => 'Reference attached.']);
        }
        case 'detach_reference': {
            $caseId = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 0;
            $referenceId = isset($_POST['reference_id']) ? (int) $_POST['reference_id'] : 0;
            if ($caseId <= 0 || $referenceId <= 0) {
                send_json(['success' => false, 'message' => 'Invalid case or reference ID.'], 400);
            }
            $manager->detachExternalCaseReference($caseId, $referenceId);
            send_json(['success' => true, 'message' => 'Reference detached.']);
        }
        case 'search_references': {
            $q = trim((string) ($_GET['q'] ?? ''));
            $relatedType = trim((string) ($_GET['related_type'] ?? ''));
            $relatedId = !empty($_GET['related_id']) ? (int) $_GET['related_id'] : 0;
            $relatedContext = [];

            if ($relatedType && $relatedId) {
                if ($relatedType === 'complaint') {
                    $stmt = $db->prepare("SELECT title, description, type FROM lc_complaints WHERE id = :id LIMIT 1");
                    $stmt->execute([':id' => $relatedId]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $relatedContext = [
                            'title' => $row['title'],
                            'description' => $row['description'],
                            'type' => $row['type'],
                        ];
                    }
                } elseif ($relatedType === 'incident') {
                    $stmt = $db->prepare("SELECT title, description, incident_type FROM lc_incident_report WHERE id = :id LIMIT 1");
                    $stmt->execute([':id' => $relatedId]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $relatedContext = [
                            'title' => $row['title'],
                            'description' => $row['description'],
                            'type' => $row['incident_type'],
                        ];
                    }
                }
            }

            $results = $manager->searchLaborLawReferences($q, 20, $relatedContext);
            send_json(['success' => true, 'data' => $results]);
        }
        case 'upload_document': {
            $caseId = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 0;
            if ($caseId <= 0) {
                send_json(['success' => false, 'message' => 'Invalid case ID.'], 400);
            }
            if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                send_json(['success' => false, 'message' => 'No file uploaded or upload error.'], 400);
            }
            $docData = [
                'document_type' => !empty($_POST['document_type']) ? trim((string) $_POST['document_type']) : null,
                'document_date' => !empty($_POST['document_date']) ? trim((string) $_POST['document_date']) : null,
                'description'   => !empty($_POST['description']) ? trim((string) $_POST['description']) : null,
                'uploaded_by'   => $currentEmployeeId,
            ];
            $docs = $manager->addDocument($caseId, $_FILES['document'], $docData);
            send_json(['success' => true, 'documents' => $docs, 'message' => 'Document uploaded.']);
        }
        case 'delete_document': {
            $caseId = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 0;
            $documentId = isset($_POST['document_id']) ? (int) $_POST['document_id'] : 0;
            if ($caseId <= 0 || $documentId <= 0) {
                send_json(['success' => false, 'message' => 'Invalid case or document ID.'], 400);
            }
            $manager->deleteDocument($documentId, $caseId);
            send_json(['success' => true, 'message' => 'Document deleted.']);
        }
        case 'close_case': {
            $caseId = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 0;
            $resolution = trim((string) ($_POST['resolution'] ?? ''));
            $dateResolved = !empty($_POST['date_resolved']) ? trim((string) $_POST['date_resolved']) : null;
            if ($caseId <= 0 || $resolution === '') {
                send_json(['success' => false, 'message' => 'Invalid case ID or missing resolution.'], 400);
            }
            $case = $manager->closeCase($caseId, $resolution, $currentEmployeeId, $dateResolved);
            send_json(['success' => true, 'case' => $case, 'message' => 'Case closed successfully.']);
        }
        default:
            send_json(['success' => false, 'message' => 'Unknown action: ' . $action], 400);
    }
} catch (Throwable $e) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    http_response_code(500);
    $payload = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
    try {
        $logDir = __DIR__ . '/../../assets/logs';
        if (is_dir($logDir) && is_writable($logDir)) {
            file_put_contents($logDir . '/external-cases-api-error.log', '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    } catch (Throwable $ignore) {}
    echo json_encode($payload);
    exit;
}
