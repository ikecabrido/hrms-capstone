<?php

require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

$q           = trim((string) ($_GET['q'] ?? ''));
$documentType = trim((string) ($_GET['document_type'] ?? ''));

if ($q === '' || $documentType === '') {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    if ($db === null) {
        throw new Exception('Database connection failed');
    }

    $stmt = $db->prepare("
        CALL sp_search_employees_for_document(:doc_type, :query, 20)
    ");
    $stmt->execute([
        ':doc_type' => $documentType,
        ':query'    => $q,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $stmt->closeCursor();

    $data = [];
    foreach ($rows as $r) {
        $fullName = trim((string) ($r['full_name'] ?? ''));
        if ($fullName === '') {
            $fullName = (string) ($r['employee_id'] ?? 'Employee');
        }

        $data[] = [
            'id'              => $r['employee_id']     ?? null,
            'employee_id'     => $r['employee_id']     ?? '',
            'employee_no'     => $r['employee_no']     ?? '',
            'first_name'      => $fullName,
            'last_name'       => '',
            'full_name'       => $fullName,
            'email'           => $r['email']           ?? '',
            'job_title'       => $r['position_name']   ?? '',
            'department'      => $r['department_name'] ?? '',
            'employment_status' => $r['employment_status'] ?? '',
            'onboarding_stage' => $r['onboarding_stage'] ?? null,
            'data_source'     => $r['data_source']     ?? 'em_employees',
            'date_hired'      => $r['date_hired']      ?? '',
            'birthdate'       => $r['birthdate']       ?? '',
            'sex'             => $r['sex']             ?? '',
            'marital_status'  => $r['marital_status']  ?? '',
            'address'         => $r['address']         ?? '',
            'phone_number'    => $r['phone_number']    ?? '',
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'data' => []]);
}

