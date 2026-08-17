<?php
require_once __DIR__ . '/../../auth/guard.php';

require_once __DIR__ . '/controllers/ExitManagementController.php';
require_once __DIR__ . '/controllers/ResignationController.php';
require_once __DIR__ . '/controllers/TerminationController.php';
require_once __DIR__ . '/controllers/ExitInterviewController.php';
require_once __DIR__ . '/controllers/KnowledgeTransferController.php';
require_once __DIR__ . '/controllers/SettlementController.php';
require_once __DIR__ . '/controllers/DocumentationController.php';
require_once __DIR__ . '/controllers/SurveyController.php';

// Controllers are instantiated on-demand to avoid heavy model initialization
// on every request (avoids running DB schema-repair calls for unrelated
// AJAX requests).
$exitController = null;
$resignationController = null;
$terminationController = null;
$interviewController = null;
$transferController = null;
$settlementController = null;
$documentationController = null;
$surveyController = null;

// ─── GET requests: document viewing / print views ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_action'])) {
    $action = $_GET['ajax_action'];

    if (($action === 'print_settlement' && isset($_GET['settlement_id']))
        || ($action === 'print_interview' && isset($_GET['interview_id']))
        || ($action === 'print_transfer' && isset($_GET['plan_id']))
        || ($action === 'print_resignation' && isset($_GET['resignation_id']))) {
        header('Content-Type: text/html; charset=UTF-8');

        if ($action === 'print_settlement') {
            $settlementController = $settlementController ?? new SettlementController();
            echo $settlementController->renderSettlementPrintPage((int)$_GET['settlement_id']);
        } elseif ($action === 'print_interview') {
            $interviewController = $interviewController ?? new ExitInterviewController();
            echo $interviewController->renderInterviewPrintPage((int)$_GET['interview_id']);
        } elseif ($action === 'print_transfer') {
            $transferController = $transferController ?? new KnowledgeTransferController();
            echo $transferController->renderTransferPrintPage((int)$_GET['plan_id']);
        } else {
            $resignationController = $resignationController ?? new ResignationController();
            echo $resignationController->renderResignationPrintPage((int)$_GET['resignation_id']);
        }

        exit;
    }

    header('Content-Type: application/json');

    if ($action === 'view_document' && isset($_GET['document_id'])) {
        $documentationController = $documentationController ?? new DocumentationController();
        $response = $documentationController->viewDocument((int)$_GET['document_id']);
        echo json_encode($response);
        exit;
    } elseif ($action === 'serve_document' && isset($_GET['document_id'])) {
        $documentationController = $documentationController ?? new DocumentationController();
        $documentationController->serveDocument((int)$_GET['document_id']);
        exit;
    } elseif ($action === 'download_document' && isset($_GET['document_id'])) {
        $documentationController = $documentationController ?? new DocumentationController();
        $response = $documentationController->downloadDocument((int)$_GET['document_id']);
        echo json_encode($response);
        exit;
    }
}

// ─── POST requests: main AJAX dispatch ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    // Don't leak PHP warnings into the JSON response; log them instead.
    ini_set('display_errors', 0);
    header('Content-Type: application/json');

    $action = $_POST['ajax_action'];
    $controller = $_POST['controller'] ?? 'exit_management';

    $data = $_POST;
    unset($data['ajax_action'], $data['controller']);

    switch ($controller) {
        case 'resignation':
            $resignationController = $resignationController ?? new ResignationController();
            $response = $resignationController->handleAjaxRequest($action, $data);
            break;
        case 'termination':
            $terminationController = $terminationController ?? new TerminationController();
            $response = $terminationController->handleAjaxRequest($action, $data);
            break;
        case 'interview':
            $interviewController = $interviewController ?? new ExitInterviewController();
            $response = $interviewController->handleAjaxRequest($action, $data);
            break;
        case 'transfer':
            $transferController = $transferController ?? new KnowledgeTransferController();
            $response = $transferController->handleAjaxRequest($action, $data);
            break;
        case 'settlement':
            $settlementController = $settlementController ?? new SettlementController();
            $response = $settlementController->handleAjaxRequest($action, $data);
            break;
        case 'documentation':
            $documentationController = $documentationController ?? new DocumentationController();
            $response = $documentationController->handleAjaxRequest($action, $data);
            break;
        case 'survey':
            $surveyController = $surveyController ?? new SurveyController();
            $response = $surveyController->handleAjaxRequest($action, $data);
            break;
        default:
            $exitController = $exitController ?? new ExitManagementController();
            $response = $exitController->handleAjaxRequest($action, $data);
    }

    echo json_encode($response);
    exit;
}

// Not a recognized AJAX request
header('Content-Type: application/json');
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
