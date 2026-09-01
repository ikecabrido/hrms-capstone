<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\GrievanceController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$sessionUser = $_SESSION['user'] ?? [];
$sessionRole = strtolower(trim((string)($sessionUser['role_name'] ?? $sessionUser['role'] ?? $_SESSION['role_name'] ?? $_SESSION['role'] ?? '')));
$sessionRoleId = (int)($sessionUser['role_id'] ?? $_SESSION['role_id'] ?? 0);
$isHrAdmin = in_array($sessionRoleId, [1, 12], true)
    || preg_match('/(^|[^a-z])(admin|hr|human resources|human resource|employee relations|engagement)([^a-z]|$)/', $sessionRole) === 1;
$action = $_GET['action'] ?? ($_POST['action'] ?? ($_REQUEST['action'] ?? 'list'));
$hasSessionUser = !empty($_SESSION['user']) || !empty($_SESSION['employee_id']) || !empty($_SESSION['user_id']);
if (!$hasSessionUser && $action !== 'list') {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$ctrl = new GrievanceController();
$action = $_GET['action'] ?? 'list';
$data = inputData();

try {
    switch ($action) {
        case 'list':
            $grievances = $ctrl->getGrievances();
            $updates = [];
            foreach ($grievances as $g) {
                if (method_exists($ctrl, 'history')) {
                    $updates[$g['eer_grievance_id']] = $ctrl->history($g['eer_grievance_id']);
                }
            }
            jsonResponse([
                'success' => true,
                'data' => $grievances,
                'grievance_updates' => $updates
            ]);
            break;
        case 'create':
            foreach (['subject', 'description'] as $f) {
                if (empty($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }
            $employeeId = $_SESSION['user']['employee_id'] ?? ($_SESSION['employee_id'] ?? ($_POST['employee_id'] ?? null));
            $creatorId = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? ($_SESSION['employee_id'] ?? null));
            $payslipId = isset($data['payslip_id']) ? (int) $data['payslip_id'] : null;
            $payslipInformation = isset($data['payslip_information']) ? trim($data['payslip_information']) : null;
            $attachmentPath = $_POST['attachment_path'] ?? null;
            if (!empty($_FILES['supporting_document']['name']) && is_uploaded_file($_FILES['supporting_document']['tmp_name'])) {
                $uploadDir = __DIR__ . '/../uploads/grievances/';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                $extension = pathinfo($_FILES['supporting_document']['name'], PATHINFO_EXTENSION);
                $fileName = 'grievance_' . time() . '_' . uniqid() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['supporting_document']['tmp_name'], $targetPath)) {
                    $attachmentPath = 'uploads/grievances/' . $fileName;
                }
            }
            $id = $ctrl->fileGrievance(
                $employeeId,
                $data['subject'], 
                $data['description'], 
                $data['category'] ?? 'Workplace Conflict', 
                $data['anonymous'] ?? 0, 
                $attachmentPath,
                $creatorId,
                $payslipId,
                $payslipInformation
            );
            jsonResponse(['id' => $id], 201);
            break;
        case 'update_management':
            if (!$isHrAdmin) {
                jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $currentUserId = $_SESSION['employee_id']
              ?? $_SESSION['user']['employee_id']
              ?? $_SESSION['user']['id']
              ?? $_SESSION['user']['user_id']
              ?? $_SESSION['user_id']
              ?? null;
            if (empty($currentUserId)) {
                jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $grievanceId = !empty($_POST['grievance_id']) ? (int)$_POST['grievance_id'] : 0;
            if (!$grievanceId) {
                jsonResponse(['success' => false, 'message' => 'Please select a grievance record.'], 400);
            }

            $status = trim($_POST['status'] ?? '');
            $hrRemarks = trim($_POST['hr_remarks'] ?? '');
            $resolution = trim($_POST['final_resolution'] ?? '');
            if ($status === '') {
                jsonResponse(['success' => false, 'message' => 'Please select a status.'], 400);
            }
            if ($hrRemarks === '') {
                jsonResponse(['success' => false, 'message' => 'Please fill in HR Remarks.'], 400);
            }
            if ($resolution === '') {
                jsonResponse(['success' => false, 'message' => 'Please fill in Final Resolution.'], 400);
            }
            if (strtolower($status) === 'escalated' && trim($_POST['escalation_reason'] ?? '') === '') {
                jsonResponse(['success' => false, 'message' => 'Please fill in the Escalation Reason.'], 400);
            }

            $uploadDir = __DIR__ . '/../uploads/grievances/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

            $dataUpdate = [];
            $dataUpdate['status'] = $status;
            $dataUpdate['resolution_of_complaint'] = $resolution;
            $dataUpdate['action_taken'] = $hrRemarks;
            $dataUpdate['confidential'] = isset($_POST['confidential']) ? 1 : 0;
            $dataUpdate['escalation_level'] = strtolower($status) === 'escalated'
              ? max(1, (int)($_POST['escalation_level'] ?? 1))
              : null;
            $dataUpdate['escalation_reason'] = strtolower($status) === 'escalated'
              ? trim($_POST['escalation_reason'] ?? '')
              : null;
            if ($ctrl->hasColumn('eer_grievances', 'compliance_record_id') && $ctrl->hasTable('lc_compliance_records')) {
                $dataUpdate['compliance_record_id'] = !empty($_POST['compliance_record_id'])
                    ? (int)$_POST['compliance_record_id']
                    : null;
            }

            if (!empty($_FILES['supporting_document']['name']) && is_uploaded_file($_FILES['supporting_document']['tmp_name'])) {
                $extension = pathinfo($_FILES['supporting_document']['name'], PATHINFO_EXTENSION);
                $fileName = 'grievance_' . $grievanceId . '_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['supporting_document']['tmp_name'], $targetPath)) {
                    $dataUpdate['attachment_path'] = 'uploads/grievances/' . $fileName;
                }
            }

            $ctrl->updateGrievanceManagement($grievanceId, $dataUpdate, $currentUserId);

            $investigationNotes = trim($_POST['investigation_notes'] ?? '');
            if ($investigationNotes !== '') {
                $ctrl->addUpdate($grievanceId, $investigationNotes, $currentUserId);
            }
            $ctrl->addUpdate($grievanceId, 'HR Remarks: ' . $hrRemarks, $currentUserId);
            $ctrl->addUpdate($grievanceId, 'Final Resolution: ' . $resolution, $currentUserId);

            jsonResponse(['success' => true, 'message' => 'Grievance management updated successfully.']);
            break;
        case 'payslips':
            $employeeId = $_SESSION['user']['employee_id'] ?? ($_SESSION['employee_id'] ?? null);
            if (empty($employeeId)) {
                jsonResponse(['error' => 'Unauthorized'], 401);
            }
            $payslips = $ctrl->getEmployeePayslips((int) $employeeId);
            jsonResponse(['success' => true, 'data' => $payslips]);
            break;
        case 'payslip_items':
            $employeeId = $_SESSION['user']['employee_id'] ?? ($_SESSION['employee_id'] ?? null);
            $payslipId = isset($data['payslip_id']) ? (int) $data['payslip_id'] : 0;
            if (empty($employeeId) || $payslipId <= 0) {
                jsonResponse(['error' => 'Invalid request'], 400);
            }
            $items = $ctrl->getPayslipItems((int) $employeeId, $payslipId);
            jsonResponse(['success' => true, 'data' => $items]);
            break;
        case 'update':
            if (empty($data['id']) || empty($data['status'])) jsonResponse(['error' => 'id and status required'], 400);
            error_log('Update Grievance Data: ' . json_encode($data));
            $res = $ctrl->updateStatus((int)$data['id'], $data['status']);
            if (!empty($data['comment'])) {
                $ctrl->addUpdate((int)$data['id'], $data['comment'], $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? $_SESSION['employee_id']);
            }
            jsonResponse($res);
            break;
        case 'add_notes':
            if (empty($data['id']) || empty($data['notes'])) jsonResponse(['error' => 'id and notes are required'], 400);
            $ctrl->addInvestigationNotes((int)$data['id'], $data['notes'], $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? $_SESSION['employee_id']);
            jsonResponse(['success' => true]);
            break;
        case 'mark_confidential':
            if (empty($data['id']) || !isset($data['confidential'])) jsonResponse(['error' => 'id and confidential flag are required'], 400);
            $ctrl->markConfidential((int)$data['id'], (bool)$data['confidential']);
            jsonResponse(['success' => true]);
            break;
        case 'resolve':
            if (empty($data['id']) || empty($data['resolution'])) jsonResponse(['error' => 'id and resolution details are required'], 400);
            $ctrl->resolveGrievance((int)$data['id'], $data['resolution'], $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? $_SESSION['employee_id']);
            jsonResponse(['success' => true]);
            break;
        case 'escalate':
            if (empty($data['id']) || empty($data['escalation_reason'])) jsonResponse(['error' => 'id and escalation reason are required'], 400);
            $newLevel = !empty($data['new_escalation_level']) ? (int)$data['new_escalation_level'] : 1;
            $ctrl->escalateGrievance((int)$data['id'], $data['escalation_reason'], $newLevel);
            jsonResponse(['success' => true, 'message' => 'Grievance escalated successfully.']);
            break;
        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

