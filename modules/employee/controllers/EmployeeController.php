<?php

/**
 * EmployeeController
 *
 * Single JSON action-dispatch controller for the Employee Management module.
 * Mirrors the pattern used by modules/recruitment/controllers/UserController.php,
 * but every query here targets the CURRENT hrms schema
 * (em_employees / em_departments / em_positions / em_roles) — no legacy
 * hrms_employee / hrms_department / hrms_position / hrms_roles references.
 *
 * Authentication: auth/session.php (must have a valid, non-expired session).
 * Authorization: only role_id 1 (System Administrator) or role_id 3
 * (Employee Management Staff) — per em_roles — may use this controller.
 */

include_once __DIR__ . '/../../../database/db.php';
include_once __DIR__ . '/../../../auth/session.php';
include_once __DIR__ . '/../../../auth/guard.php';
include_once __DIR__ . '/../classes/Employee.php';
include_once __DIR__ . '/../classes/Department.php';
include_once __DIR__ . '/../classes/Position.php';
include_once __DIR__ . '/../classes/EmployeeProfile.php';
include_once __DIR__ . '/../classes/EmployeeRecords.php';
include_once __DIR__ . '/../classes/EmployeeDocuments.php';
include_once __DIR__ . '/../classes/EmployeeHistory.php';
include_once __DIR__ . '/../classes/ContractRenewal.php';
include_once __DIR__ . '/../classes/DocumentRequest.php';

header('Content-Type: application/json');

// ── Authorization: module-level role check (guard.php already confirmed session) ──
$ALLOWED_ROLE_IDS = [1, 3]; // System Administrator, Employee Management Staff

if (!isset($_SESSION['role_id']) || !in_array((int) $_SESSION['role_id'], $ALLOWED_ROLE_IDS, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are not authorized to access this module.']);
    exit();
}

$db   = new Database();
$conn = $db->getConnection();

$currentUserId     = $_SESSION['user_id'] ?? null;
$currentEmployeeName = $_SESSION['employee_name'] ?? 'System';

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_employees':
        getEmployees();
        break;

    case 'search_employees':
        searchEmployees();
        break;

    case 'get_employee_details':
        getEmployeeDetails();
        break;

    case 'create_employee':
        createEmployee();
        break;

    case 'update_employee':
        updateEmployee();
        break;

    case 'archive_employee':
        archiveEmployee();
        break;

    case 'restore_employee':
        restoreEmployee();
        break;

    case 'get_departments':
        getDepartments();
        break;

    case 'get_positions':
        getPositions();
        break;

    case 'get_positions_by_department':
        getPositionsByDepartment();
        break;

    case 'get_dashboard_stats':
        getDashboardStats();
        break;

    case 'save_personal_information':
        savePersonalInformation();
        break;

    case 'save_family_background':
        saveFamilyBackground();
        break;

    case 'save_government_ids':
        saveGovernmentIds();
        break;

    case 'add_dependent':
        addDependent();
        break;

    case 'delete_dependent':
        deleteDependent();
        break;

    case 'add_emergency_contact':
        addEmergencyContact();
        break;

    case 'delete_emergency_contact':
        deleteEmergencyContact();
        break;

    case 'add_education':
        addEducation();
        break;

    case 'delete_education':
        deleteEducation();
        break;

    case 'add_certification':
        addCertification();
        break;

    case 'delete_certification':
        deleteCertification();
        break;

    case 'add_skill':
        addSkill();
        break;

    case 'delete_skill':
        deleteSkill();
        break;

    case 'add_language':
        addLanguage();
        break;

    case 'delete_language':
        deleteLanguage();
        break;

    case 'add_work_experience':
        addWorkExperience();
        break;

    case 'delete_work_experience':
        deleteWorkExperience();
        break;

    case 'get_documents':
        getDocuments();
        break;

    case 'upload_document':
        uploadDocument();
        break;

    case 'delete_document':
        deleteDocument();
        break;

    case 'get_requirements':
        getRequirements();
        break;

    case 'add_requirement':
        addRequirement();
        break;

    case 'update_requirement':
        updateRequirement();
        break;

    case 'delete_requirement':
        deleteRequirement();
        break;

    case 'get_history':
        getHistory();
        break;

    case 'get_contract_info':
        getContractInfo();
        break;

    case 'renew_contract':
        renewContract();
        break;

    case 'get_document_requests':
        getDocumentRequests();
        break;

    case 'update_document_request':
        updateDocumentRequest();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

// ─────────────────────────── Employee core ───────────────────────────

function getEmployees()
{
    global $conn;
    $employeeClass = new Employee($conn);
    echo json_encode(['success' => true, 'data' => $employeeClass->getEmployees()]);
}

function searchEmployees()
{
    global $conn;
    $employeeClass = new Employee($conn);

    $keyword         = trim($_GET['keyword'] ?? '');
    $departmentId    = intval($_GET['department_id'] ?? 0) ?: null;
    $positionId      = intval($_GET['position_id'] ?? 0) ?: null;
    $status          = trim($_GET['status'] ?? '') ?: null;
    $includeArchived = !empty($_GET['include_archived']);

    $data = $employeeClass->searchEmployees($keyword, $departmentId, $positionId, $status, $includeArchived);
    echo json_encode(['success' => true, 'data' => $data]);
}

function getEmployeeDetails()
{
    global $conn;

    $employeeId = intval($_GET['employee_id'] ?? 0);
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
        return;
    }

    $employeeClass = new Employee($conn);
    $employee = $employeeClass->getEmployeeById($employeeId);

    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee not found.']);
        return;
    }

    $profileClass  = new EmployeeProfile($conn);
    $recordsClass  = new EmployeeRecords($conn);

    echo json_encode([
        'success'  => true,
        'employee' => $employee,
        'profile'  => $profileClass->getFullProfile($employeeId),
        'records'  => $recordsClass->getFullRecords($employeeId),
    ]);
}

function createEmployee()
{
    global $conn, $currentUserId, $currentEmployeeName;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        return;
    }

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');

    if ($firstName === '' || $lastName === '' || $email === '') {
        echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required.']);
        return;
    }

    try {
        $employeeClass = new Employee($conn);
        $newId = $employeeClass->createEmployee($_POST);

        $historyClass = new EmployeeHistory($conn);
        $historyClass->logChange(
            $newId, 'Employee Created', '', 'N/A', "$firstName $lastName",
            $currentEmployeeName, $currentUserId, 'New employee record created.'
        );

        echo json_encode(['success' => true, 'message' => 'Employee created successfully.', 'employee_id' => $newId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to create employee: ' . $e->getMessage()]);
    }
}

function updateEmployee()
{
    global $conn, $currentUserId, $currentEmployeeName;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        return;
    }

    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
        return;
    }

    try {
        $employeeClass = new Employee($conn);
        $changes = $employeeClass->updateEmployee($employeeId, $_POST);

        if ($changes === false) {
            echo json_encode(['success' => false, 'message' => 'Employee not found.']);
            return;
        }

        if (!empty($changes)) {
            $historyClass = new EmployeeHistory($conn);
            foreach ($changes as $field => $pair) {
                [$oldValue, $newValue] = $pair;
                $historyClass->logChange(
                    $employeeId, 'Field Update', $field, $oldValue, $newValue,
                    $currentEmployeeName, $currentUserId
                );
            }
        }

        echo json_encode(['success' => true, 'message' => 'Employee updated successfully.', 'changed_fields' => array_keys($changes)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update employee: ' . $e->getMessage()]);
    }
}

function archiveEmployee()
{
    global $conn, $currentUserId, $currentEmployeeName;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        return;
    }

    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
        return;
    }

    $employeeClass = new Employee($conn);
    $result = $employeeClass->archiveEmployee($employeeId);

    if ($result) {
        $historyClass = new EmployeeHistory($conn);
        $historyClass->logChange(
            $employeeId, 'Employee Archived', 'is_archived', '0', '1',
            $currentEmployeeName, $currentUserId,
            $_POST['reason'] ?? null
        );
    }

    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Employee archived.' : 'Failed to archive employee.']);
}

function restoreEmployee()
{
    global $conn, $currentUserId, $currentEmployeeName;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        return;
    }

    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
        return;
    }

    $employeeClass = new Employee($conn);
    $result = $employeeClass->restoreEmployee($employeeId);

    if ($result) {
        $historyClass = new EmployeeHistory($conn);
        $historyClass->logChange(
            $employeeId, 'Employee Restored', 'is_archived', '1', '0',
            $currentEmployeeName, $currentUserId
        );
    }

    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Employee restored.' : 'Failed to restore employee.']);
}

// ─────────────────────────── Lookups ───────────────────────────

function getDepartments()
{
    global $conn;
    $departmentClass = new Department($conn);
    echo json_encode(['success' => true, 'data' => $departmentClass->getAllDepartments()]);
}

function getPositions()
{
    global $conn;
    $positionClass = new Position($conn);
    echo json_encode(['success' => true, 'data' => $positionClass->getAllPositions()]);
}

function getPositionsByDepartment()
{
    global $conn;

    $departmentId = intval($_GET['department_id'] ?? 0);
    if (!$departmentId) {
        echo json_encode(['success' => false, 'message' => 'No department ID provided.']);
        return;
    }

    $positionClass = new Position($conn);
    echo json_encode(['success' => true, 'data' => $positionClass->getPositionsByDepartment($departmentId)]);
}

function getDashboardStats()
{
    global $conn;
    $employeeClass = new Employee($conn);
    $docsClass = new EmployeeDocuments($conn);

    echo json_encode([
        'success' => true,
        'stats'   => $employeeClass->getDashboardStats(),
        'requirement_status_counts' => $docsClass->getRequirementStatusCounts(),
    ]);
}

// ─────────────────────────── Profile sub-tables ───────────────────────────

function savePersonalInformation()
{
    global $conn, $currentUserId, $currentEmployeeName;

    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
        return;
    }

    $profileClass = new EmployeeProfile($conn);
    $result = $profileClass->savePersonalInformation($employeeId, $_POST);

    if ($result) {
        $historyClass = new EmployeeHistory($conn);
        $historyClass->logChange(
            $employeeId, 'Personal Info Update', '', 'Previous data', 'Updated personal information',
            $currentEmployeeName, $currentUserId, 'Updated personal details'
        );
    }

    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Personal information saved.' : 'Failed to save personal information.']);
}

function saveFamilyBackground()
{
    global $conn;
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
        return;
    }
    $profileClass = new EmployeeProfile($conn);
    $result = $profileClass->saveFamilyBackground($employeeId, $_POST);
    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Family background saved.' : 'Failed to save family background.']);
}

function saveGovernmentIds()
{
    global $conn;
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
        return;
    }
    $profileClass = new EmployeeProfile($conn);
    $result = $profileClass->saveGovernmentIds($employeeId, $_POST);
    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Government IDs saved.' : 'Failed to save government IDs.']);
}

function addDependent()
{
    global $conn;
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId || empty($_POST['name'])) {
        echo json_encode(['success' => false, 'message' => 'Employee ID and dependent name are required.']);
        return;
    }
    $profileClass = new EmployeeProfile($conn);
    $result = $profileClass->addDependent($employeeId, $_POST);
    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Dependent added.' : 'Failed to add dependent.']);
}

function deleteDependent()
{
    global $conn;
    $id = intval($_POST['dependent_id'] ?? 0);
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$id || !$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        return;
    }
    $profileClass = new EmployeeProfile($conn);
    $result = $profileClass->deleteDependent($id, $employeeId);
    echo json_encode(['success' => (bool) $result]);
}

function addEmergencyContact()
{
    global $conn;
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId || empty($_POST['name']) || empty($_POST['contact_number'])) {
        echo json_encode(['success' => false, 'message' => 'Employee ID, name, and contact number are required.']);
        return;
    }
    $profileClass = new EmployeeProfile($conn);
    $result = $profileClass->addEmergencyContact($employeeId, $_POST);
    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Emergency contact added.' : 'Failed to add emergency contact.']);
}

function deleteEmergencyContact()
{
    global $conn;
    $id = intval($_POST['contact_id'] ?? 0);
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$id || !$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        return;
    }
    $profileClass = new EmployeeProfile($conn);
    $result = $profileClass->deleteEmergencyContact($id, $employeeId);
    echo json_encode(['success' => (bool) $result]);
}

// ─────────────────────────── Records sub-tables ───────────────────────────

function addEducation()
{
    global $conn;
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId || empty($_POST['level']) || empty($_POST['school_name'])) {
        echo json_encode(['success' => false, 'message' => 'Employee ID, level, and school name are required.']);
        return;
    }
    $recordsClass = new EmployeeRecords($conn);
    $result = $recordsClass->addEducation($employeeId, $_POST);
    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Education record added.' : 'Failed to add education record.']);
}

function deleteEducation()
{
    global $conn;
    $id = intval($_POST['education_id'] ?? 0);
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$id || !$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        return;
    }
    $recordsClass = new EmployeeRecords($conn);
    $result = $recordsClass->deleteEducation($id, $employeeId);
    echo json_encode(['success' => (bool) $result]);
}

function addCertification()
{
    global $conn, $currentUserId, $currentEmployeeName;
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId || empty($_POST['cert_name']) || empty($_POST['issuing_organization'])) {
        echo json_encode(['success' => false, 'message' => 'Employee ID, certificate name, and issuing organization are required.']);
        return;
    }
    $recordsClass = new EmployeeRecords($conn);
    $result = $recordsClass->addCertification($employeeId, $_POST);
    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Certification added.' : 'Failed to add certification.']);
}

function deleteCertification()
{
    global $conn;
    $id = intval($_POST['cert_id'] ?? 0);
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$id || !$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        return;
    }
    $recordsClass = new EmployeeRecords($conn);
    $result = $recordsClass->deleteCertification($id, $employeeId);
    echo json_encode(['success' => (bool) $result]);
}

function addSkill()
{
    global $conn;
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId || empty($_POST['skill_name'])) {
        echo json_encode(['success' => false, 'message' => 'Employee ID and skill name are required.']);
        return;
    }
    $recordsClass = new EmployeeRecords($conn);
    $result = $recordsClass->addSkill($employeeId, $_POST);
    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Skill added.' : 'Failed to add skill.']);
}

function deleteSkill()
{
    global $conn;
    $id = intval($_POST['skill_id'] ?? 0);
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$id || !$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        return;
    }
    $recordsClass = new EmployeeRecords($conn);
    $result = $recordsClass->deleteSkill($id, $employeeId);
    echo json_encode(['success' => (bool) $result]);
}

function addLanguage()
{
    global $conn;
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId || empty($_POST['language_name'])) {
        echo json_encode(['success' => false, 'message' => 'Employee ID and language name are required.']);
        return;
    }
    $recordsClass = new EmployeeRecords($conn);
    $result = $recordsClass->addLanguage($employeeId, $_POST);
    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Language added.' : 'Failed to add language.']);
}

function deleteLanguage()
{
    global $conn;
    $id = intval($_POST['language_id'] ?? 0);
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$id || !$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        return;
    }
    $recordsClass = new EmployeeRecords($conn);
    $result = $recordsClass->deleteLanguage($id, $employeeId);
    echo json_encode(['success' => (bool) $result]);
}

function addWorkExperience()
{
    global $conn;
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId || empty($_POST['company_name']) || empty($_POST['position']) || empty($_POST['start_date'])) {
        echo json_encode(['success' => false, 'message' => 'Employee ID, company name, position, and start date are required.']);
        return;
    }
    $recordsClass = new EmployeeRecords($conn);
    $result = $recordsClass->addWorkExperience($employeeId, $_POST);
    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Work experience added.' : 'Failed to add work experience.']);
}

function deleteWorkExperience()
{
    global $conn;
    $id = intval($_POST['work_exp_id'] ?? 0);
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$id || !$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        return;
    }
    $recordsClass = new EmployeeRecords($conn);
    $result = $recordsClass->deleteWorkExperience($id, $employeeId);
    echo json_encode(['success' => (bool) $result]);
}

// ─────────────────────────── Documents & requirements ───────────────────────────

function getDocuments()
{
    global $conn;
    $employeeId = intval($_GET['employee_id'] ?? 0);
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
        return;
    }
    $docsClass = new EmployeeDocuments($conn);
    echo json_encode(['success' => true, 'data' => $docsClass->getDocuments($employeeId)]);
}

function uploadDocument()
{
    global $conn, $currentUserId, $currentEmployeeName;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        return;
    }

    $employeeId = intval($_POST['employee_id'] ?? 0);
    $documentName = trim($_POST['document_name'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');
    $documentType = trim($_POST['document_type'] ?? 'Other');
    $expiryDate = trim($_POST['expiry_date'] ?? '') ?: null;

    if (!$employeeId || $documentName === '') {
        echo json_encode(['success' => false, 'message' => 'Employee ID and document name are required.']);
        return;
    }

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error occurred.']);
        return;
    }

    $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $originalName = $_FILES['file']['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions, true)) {
        echo json_encode(['success' => false, 'message' => 'File type not allowed. Allowed: ' . implode(', ', $allowedExtensions)]);
        return;
    }

    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($_FILES['file']['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File exceeds the 10MB size limit.']);
        return;
    }

    // Project root: modules/employee/controllers/ -> ../../../ = project root
    $year  = date('Y');
    $month = date('m');
    $relativeDir = "assets/documents/$year/$month";
    $absoluteDir = __DIR__ . "/../../../$relativeDir";

    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0755, true);
    }

    $safeName = uniqid('doc_', false) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
    $absolutePath = "$absoluteDir/$safeName";
    $storedRelativePath = "../../$relativeDir/$safeName"; // relative to modules/employee/pages/*

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $absolutePath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save the uploaded file.']);
        return;
    }

    try {
        $docsClass = new EmployeeDocuments($conn);
        $documentId = $docsClass->addDocument($employeeId, $currentUserId, [
            'document_name' => $documentName,
            'document_type' => $documentType,
            'file_path'     => $storedRelativePath,
            'file_name'     => $originalName,
            'file_size'     => (string) $_FILES['file']['size'],
            'mime_type'     => $_FILES['file']['type'] ?? null,
            'category'      => $category,
            'expiry_date'   => $expiryDate,
        ]);

        $historyClass = new EmployeeHistory($conn);
        $historyClass->logDocumentUpload($employeeId, $documentName, $category, $currentEmployeeName, $currentUserId);

        echo json_encode(['success' => true, 'message' => 'Document uploaded.', 'document_id' => $documentId]);
    } catch (Exception $e) {
        @unlink($absolutePath);
        echo json_encode(['success' => false, 'message' => 'Failed to save document record: ' . $e->getMessage()]);
    }
}

function deleteDocument()
{
    global $conn;

    $documentId = intval($_POST['document_id'] ?? 0);
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$documentId || !$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        return;
    }

    $docsClass = new EmployeeDocuments($conn);
    $doc = $docsClass->getDocumentById($documentId);

    $result = $docsClass->deleteDocument($documentId, $employeeId);

    if ($result && $doc) {
        $absolutePath = __DIR__ . '/../../../' . preg_replace('#^\.\./\.\./#', '', $doc['file_path']);
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Document deleted.' : 'Failed to delete document.']);
}

function getRequirements()
{
    global $conn;
    $employeeId = intval($_GET['employee_id'] ?? 0);
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
        return;
    }
    $docsClass = new EmployeeDocuments($conn);
    echo json_encode(['success' => true, 'data' => $docsClass->getRequirements($employeeId)]);
}

function addRequirement()
{
    global $conn;
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$employeeId || empty($_POST['requirement_name'])) {
        echo json_encode(['success' => false, 'message' => 'Employee ID and requirement name are required.']);
        return;
    }
    $docsClass = new EmployeeDocuments($conn);
    $result = $docsClass->addRequirement($employeeId, $_POST);
    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Requirement added.' : 'Failed to add requirement.']);
}

function updateRequirement()
{
    global $conn;
    $id = intval($_POST['requirement_id'] ?? 0);
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$id || !$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        return;
    }
    $docsClass = new EmployeeDocuments($conn);
    $result = $docsClass->updateRequirementStatus($id, $employeeId, $_POST);
    echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Requirement updated.' : 'Failed to update requirement.']);
}

function deleteRequirement()
{
    global $conn;
    $id = intval($_POST['requirement_id'] ?? 0);
    $employeeId = intval($_POST['employee_id'] ?? 0);
    if (!$id || !$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        return;
    }
    $docsClass = new EmployeeDocuments($conn);
    $result = $docsClass->deleteRequirement($id, $employeeId);
    echo json_encode(['success' => (bool) $result]);
}

// ─────────────────────────── History ───────────────────────────

function getHistory()
{
    global $conn;

    $employeeId = intval($_GET['employee_id'] ?? 0);
    $historyClass = new EmployeeHistory($conn);

    if ($employeeId) {
        $data = $historyClass->getHistoryForEmployee($employeeId);
    } else {
        $limit = intval($_GET['limit'] ?? 50);
        $data = $historyClass->getRecentHistory($limit ?: 50);
    }

    echo json_encode(['success' => true, 'data' => $data]);
}

// ─────────────────────────── Contract Renewal ───────────────────────────
// Uses the EXISTING em_contract_renewals table (see modules/employee/classes/ContractRenewal.php
// for the verified column list). No schema changes are made by this controller.

function getContractInfo()
{
    global $conn;

    $employeeId = intval($_GET['employee_id'] ?? 0);
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
        return;
    }

    $employeeClass = new Employee($conn);
    if (!$employeeClass->getEmployeeById($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee not found.']);
        return;
    }

    $contractClass = new ContractRenewal($conn);
    echo json_encode([
        'success' => true,
        'current' => $contractClass->getCurrentContract($employeeId),
        'history' => $contractClass->getRenewalHistory($employeeId),
    ]);
}

function renewContract()
{
    global $conn, $currentUserId, $currentEmployeeName;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        return;
    }

    $employeeId       = intval($_POST['employee_id'] ?? 0);
    $contractStart    = trim($_POST['contract_start_date'] ?? '');
    $contractEnd      = trim($_POST['contract_end_date'] ?? '');
    $employmentType   = trim($_POST['employment_type'] ?? '');
    $salary           = trim($_POST['salary'] ?? '');
    $remarks          = trim($_POST['remarks'] ?? '');

    // ── Validation ──
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
        return;
    }

    $employeeClass = new Employee($conn);
    if (!$employeeClass->getEmployeeById($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee not found.']);
        return;
    }

    if ($contractStart === '' || $contractEnd === '') {
        echo json_encode(['success' => false, 'message' => 'Contract start date and end date are required.']);
        return;
    }

    $startDt = DateTime::createFromFormat('Y-m-d', $contractStart);
    $endDt   = DateTime::createFromFormat('Y-m-d', $contractEnd);
    if (!$startDt || $startDt->format('Y-m-d') !== $contractStart) {
        echo json_encode(['success' => false, 'message' => 'Contract start date is not a valid date.']);
        return;
    }
    if (!$endDt || $endDt->format('Y-m-d') !== $contractEnd) {
        echo json_encode(['success' => false, 'message' => 'Contract end date is not a valid date.']);
        return;
    }
    if ($endDt < $startDt) {
        echo json_encode(['success' => false, 'message' => 'Contract end date cannot be earlier than the start date.']);
        return;
    }

    if ($salary !== '' && !is_numeric($salary)) {
        echo json_encode(['success' => false, 'message' => 'Salary must be a number.']);
        return;
    }

    try {
        $contractClass = new ContractRenewal($conn);

        // Capture the outgoing contract's end date (if any) for the audit log,
        // before it gets flipped to Expired inside createRenewal().
        $previousContract = $contractClass->getCurrentContract($employeeId);
        $previousEndDate  = $previousContract['contract_end_date'] ?? null;

        $newId = $contractClass->createRenewal($employeeId, [
            'contract_start_date' => $contractStart,
            'contract_end_date'   => $contractEnd,
            'employment_type'     => $employmentType,
            'salary'              => $salary,
            'remarks'             => $remarks,
        ]);

        // Optional contract document, reusing the existing document-upload
        // pipeline/table (employee_documents) — no new table created.
        if (!empty($_FILES['contract_document']) && $_FILES['contract_document']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['contract_document']['error'] !== UPLOAD_ERR_OK) {
                logContractRenewalHistory($employeeId, $previousEndDate, $contractEnd, $currentEmployeeName, $currentUserId, $remarks);
                echo json_encode([
                    'success' => true,
                    'message' => 'Contract renewed, but the document upload failed (error code ' . $_FILES['contract_document']['error'] . ').',
                    'contract_renewal_id' => $newId,
                ]);
                return;
            }

            $uploadResult = uploadContractDocument($employeeId, $contractStart, $contractEnd);
            if ($uploadResult !== true) {
                // Renewal itself already succeeded; report the document issue
                // separately rather than rolling back a valid contract record.
                logContractRenewalHistory($employeeId, $previousEndDate, $contractEnd, $currentEmployeeName, $currentUserId, $remarks);
                echo json_encode([
                    'success' => true,
                    'message' => 'Contract renewed, but the document could not be attached: ' . $uploadResult,
                    'contract_renewal_id' => $newId,
                ]);
                return;
            }
        }

        logContractRenewalHistory($employeeId, $previousEndDate, $contractEnd, $currentEmployeeName, $currentUserId, $remarks);

        echo json_encode(['success' => true, 'message' => 'Contract renewed successfully.', 'contract_renewal_id' => $newId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to renew contract: ' . $e->getMessage()]);
    }
}

/**
 * Logs the renewal to the existing employee_change_history audit table.
 * Kept separate from Contract Renewal History (em_contract_renewals) —
 * they serve different purposes, per the existing EmployeeHistory class.
 */
function logContractRenewalHistory($employeeId, $previousEndDate, $newEndDate, $updatedBy, $userId, $remarks)
{
    global $conn;
    $historyClass = new EmployeeHistory($conn);
    $historyClass->logChange(
        $employeeId,
        'Contract Renewal',
        'contract_end_date',
        $previousEndDate ?: 'N/A',
        $newEndDate,
        $updatedBy,
        $userId,
        $remarks ?: null
    );
}

/**
 * Attaches an optional contract document via the EXISTING document pipeline
 * (same validation/storage logic as uploadDocument() above, category fixed
 * to 'Contract'). Returns true on success, or an error message string.
 */
function uploadContractDocument($employeeId, $contractStart, $contractEnd)
{    global $conn, $currentUserId, $currentEmployeeName;

    $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $originalName = $_FILES['contract_document']['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions, true)) {
        return 'File type not allowed. Allowed: ' . implode(', ', $allowedExtensions);
    }

    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($_FILES['contract_document']['size'] > $maxSize) {
        return 'File exceeds the 10MB size limit.';
    }

    $year  = date('Y');
    $month = date('m');
    $relativeDir = "assets/documents/$year/$month";
    $absoluteDir = __DIR__ . "/../../../$relativeDir";

    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0755, true);
    }

    $safeName = uniqid('contract_', false) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
    $absolutePath = "$absoluteDir/$safeName";
    $storedRelativePath = "../../$relativeDir/$safeName";

    if (!move_uploaded_file($_FILES['contract_document']['tmp_name'], $absolutePath)) {
        return 'Failed to save the uploaded file.';
    }

    try {
        $docsClass = new EmployeeDocuments($conn);
        $docsClass->addDocument($employeeId, $currentUserId, [
            'document_name' => "Contract ($contractStart to $contractEnd)",
            'document_type' => 'Contract',
            'file_path'     => $storedRelativePath,
            'file_name'     => $originalName,
            'file_size'     => (string) $_FILES['contract_document']['size'],
            'mime_type'     => $_FILES['contract_document']['type'] ?? null,
            'category'      => 'Contract',
            'expiry_date'   => $contractEnd,
        ]);
        return true;
    } catch (Exception $e) {
        @unlink($absolutePath);
        return 'Failed to save document record: ' . $e->getMessage();
    }
}

// ───────────────────── Document Requests (HR/Admin management) ─────────────────────
// Uses the EXISTING em_lc_document_requests table (see modules/employee/classes/
// DocumentRequest.php for the verified column list). No schema changes.
// This is the HR-facing counterpart to modules/portal/controllers/DocumentRequestController.php,
// which handles the employee self-service side of the same table.

function getDocumentRequests()
{
    global $conn;

    $filters = [
        'request_status' => trim($_GET['request_status'] ?? ''),
        'archived'        => $_GET['archived'] ?? '',
    ];

    $docClass = new DocumentRequest($conn);
    echo json_encode(['success' => true, 'data' => $docClass->getAllRequests($filters)]);
}

function updateDocumentRequest()
{
    global $conn;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        return;
    }

    $requestId = intval($_POST['request_id'] ?? 0);
    if (!$requestId) {
        echo json_encode(['success' => false, 'message' => 'No request ID provided.']);
        return;
    }

    $docClass = new DocumentRequest($conn);
    if (!$docClass->getRequestById($requestId)) {
        echo json_encode(['success' => false, 'message' => 'Document request not found.']);
        return;
    }

    // Only pull in whitelisted fields the caller actually sent — updateRequest()
    // also whitelists internally, this just avoids passing empty-string noise.
    $fields = [];
    if (isset($_POST['request_status']) && $_POST['request_status'] !== '') {
        $fields['request_status'] = trim($_POST['request_status']);
    }
    if (isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '') {
        $fields['assigned_to'] = trim($_POST['assigned_to']);
    }
    if (isset($_POST['signature_status']) && $_POST['signature_status'] !== '') {
        $fields['signature_status'] = trim($_POST['signature_status']);
    }
    if (isset($_POST['notes'])) {
        $fields['notes'] = trim($_POST['notes']);
    }
    if (isset($_POST['verified'])) {
        $fields['verified'] = $_POST['verified'] ? 1 : 0;
    }
    if (isset($_POST['archived'])) {
        $fields['archived'] = $_POST['archived'] ? 1 : 0;
    }

    if (!$fields) {
        echo json_encode(['success' => false, 'message' => 'No valid fields to update.']);
        return;
    }

    try {
        $docClass->updateRequest($requestId, $fields);
        echo json_encode(['success' => true, 'message' => 'Document request updated.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update request: ' . $e->getMessage()]);
    }
}
