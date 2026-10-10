<?php
ob_start();

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../classes/ExitManagementController.php';

header('Content-Type: application/json');

function ea_json_response($data) {
    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode($data);
    exit;
}

try {
    $database = new Database();

    if ($database->hasConnectionError()) {
        ea_json_response([
            'success' => false,
            'message' => 'Database connection failed.'
        ]);
    }

    $db = $database->getConnection();

    if (!$db) {
        ea_json_response([
            'success' => false,
            'message' => 'Database connection failed.'
        ]);
    }
} catch (Throwable $e) {
    ea_json_response(['success' => false, 'message' => 'Database connection failed.']);
}

$exitId = isset($_POST['exit_id']) ? (int) $_POST['exit_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$remarks = isset($_POST['legal_remarks']) ? trim($_POST['legal_remarks']) : '';
$officerId = (int)($_SESSION['employee_id'] ?? 0);
$officerName = $_SESSION['user']['name'] ?? $_SESSION['employee_name'] ?? 'Legal Officer';

if ($officerId <= 0) {
    ea_json_response([
        'success' => false,
        'message' => 'Unable to identify the logged-in legal officer.'
    ]);
}

if ($exitId <= 0 || $action === '') {
    ea_json_response(['success' => false, 'message' => 'Invalid request.']);
}

try {
    $controller = new ExitManagementController($db);
    $exit = $controller->getExitRequestById($exitId);

    if (!$exit) {
        ea_json_response(['success' => false, 'message' => 'Exit record not found.']);
    }

    switch ($action) {
        case 'verify':
            if (strtolower($exit['legal_status'] ?? '') === 'confirmed') {
                ea_json_response(['success' => false, 'message' => 'Compliance has already been verified.']);
            }
            $updated = $controller->updateExitLegalStatus($exitId, 'Confirmed', $officerId, $remarks);
            if ($updated) {
                ea_json_response(['success' => true, 'message' => 'Compliance verified successfully.']);
            } else {
                ea_json_response(['success' => false, 'message' => 'Failed to verify compliance. Please try again.']);
            }
            break;

        case 'acknowledge':
            if (strtolower($exit['legal_status'] ?? '') === 'confirmed') {
                ea_json_response([
                    'success' => false,
                    'message' => 'Exit has already been acknowledged.'
                ]);
            }

            // Preclearance Desk Person must be completed first.
            $preclearanceCompleted = !empty(
                trim((string)($exit['preclearance_desk_person'] ?? ''))
            );

            // HR approval must be completed first.
            $hrApproved = !empty($exit['hr_approved_at']);

            $pendingItems = [];

            if (!$preclearanceCompleted) {
                $pendingItems[] = 'Preclearance Desk Person';
            }

            if (!$hrApproved) {
                $pendingItems[] = 'HR approval';
            }

            if (!empty($pendingItems)) {
                ea_json_response([
                    'success' => false,
                    'message' => 'Cannot acknowledge exit. The following must be completed first: ' .
                                 implode(' and ', $pendingItems) . '.'
                ]);
            }

            // All required compliance checks are complete.
            $updated1 = $controller->updateExitLegalStatus(
                $exitId,
                'Confirmed',
                $officerId,
                $remarks
            );

            $updated2 = $controller->updateExitRecruitmentStatus(
                $exitId,
                'Notified'
            );

            if ($updated1 && $updated2) {
                ea_json_response([
                    'success' => true,
                    'message' => 'Exit acknowledged successfully. Recruitment & Onboarding has been notified.'
                ]);
            }

            ea_json_response([
                'success' => false,
                'message' => 'Unable to acknowledge the exit because the legal status could not be updated. Please try again.'
            ]);
            break;

        case 'return':
            $updated = $controller->updateExitLegalStatus($exitId, 'Returned', $officerId, $remarks);
            if ($updated) {
                ea_json_response(['success' => true, 'message' => 'Exit record returned to Exit Management for clarification.']);
            } else {
                ea_json_response(['success' => false, 'message' => 'Failed to return exit record. Please try again.']);
            }
            break;

        default:
            ea_json_response(['success' => false, 'message' => 'Unknown action.']);
            break;
    }
} catch (Throwable $e) {
    ea_json_response(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
