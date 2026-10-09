<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once dirname(__DIR__, 6) . '/database/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$courseId = (int) ($input['course_id'] ?? $_POST['course_id'] ?? 0);
$requiredCourseId = !empty($input['required_course_id']) ? (int) $input['required_course_id'] : null;
$requiredSkillId = !empty($input['required_skill_id']) ? (int) $input['required_skill_id'] : null;

if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'course_id is required']);
    exit;
}

if (!$requiredCourseId && !$requiredSkillId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Either required_course_id or required_skill_id is required']);
    exit;
}

if ($requiredCourseId === $courseId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'A course cannot be its own prerequisite']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();

    // Check for duplicate
    $stmt = $pdo->prepare("SELECT 1 FROM ld_prerequisite WHERE course_id = :cid AND (required_course_id = :rcid OR required_skill_id = :rsid) LIMIT 1");
    $params = [':cid' => $courseId];
    if ($requiredCourseId) { $params[':rcid'] = $requiredCourseId; $params[':rsid'] = 0; }
    else { $params[':rcid'] = 0; $params[':rsid'] = $requiredSkillId; }
    $stmt->execute($params);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'This prerequisite already exists']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO ld_prerequisite (course_id, required_course_id, required_skill_id) VALUES (:cid, :rcid, :rsid)");
    $stmt->execute([':cid' => $courseId, ':rcid' => $requiredCourseId, ':rsid' => $requiredSkillId]);

    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
