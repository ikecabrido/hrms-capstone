<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/program.php';
require_once dirname(__FILE__, 5) . '/classes/LearningRole.php';
require_once dirname(__FILE__, 5) . '/classes/CSRF.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

CSRF::requireValid();

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $program = new Program($pdo);

    $employeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
    if ($employeeId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $role = LearningRole::forEmployee($pdo, $employeeId);
    if (!in_array($role, ['admin', 'instructor'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only trainers and administrators can create programs.']);
        exit;
    }

    if (!$program->schemaReady()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'The program database update has not been applied. Import modules/learning/rainzui/ld_program_delivery_assessment_2026-10-26.sql first.',
        ]);
        exit;
    }

    $input = $_POST;
    if (!$input) {
        $decoded = json_decode(file_get_contents('php://input'), true);
        $input = is_array($decoded) ? $decoded : [];
    }

    $input['instructor_id'] = $employeeId;
    $input['actor_id'] = $employeeId;
    $input['learning_role'] = $role;
    $input['is_admin'] = $role === 'admin';

    $result = $program->create($input, $employeeId);

    if (!empty($result['success'])) {
        http_response_code(200);
        echo json_encode($result);
        exit;
    }

    http_response_code(422);
    echo json_encode($result);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create program.',
        'error' => $e->getMessage(),
    ]);
    exit;
}
