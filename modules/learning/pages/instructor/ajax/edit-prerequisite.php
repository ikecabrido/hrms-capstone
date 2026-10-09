<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

include dirname(__FILE__, 6) . '/database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid request data']));
    }

    $prerequisiteId = (int)$data['id'];
    $courseId = $data['course_id'] ?? null;
    $requiredCourseId = $data['required_course_id'] ?? null;
    $minimumGrade = $data['minimum_grade'] ?? null;
    $completionRequired = (bool)($data['completion_required'] ?? true);

    if (!$courseId || !$requiredCourseId) {
        http_response_code(400);
        die(json_encode(['error' => 'Course ID and required course ID are required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_prerequisite 
        SET course_id = ?, required_course_id = ?, minimum_grade = ?, 
            completion_required = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $courseId,
        $requiredCourseId,
        $minimumGrade,
        $completionRequired ? 1 : 0,
        $prerequisiteId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $prerequisiteId,
        'message' => 'Prerequisite updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
