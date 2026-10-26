<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/program.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $program = new Program($pdo);

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Program ID required']);
        exit;
    }

    $programData = $program->getById($id);

    if (!$programData) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Program not found']);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $programData,
        'courses' => $program->getCourses($id),
        'skills' => $program->getSkills($id),
        'trainers' => $program->getTrainers($id),
        'participants' => $program->getParticipants($id),
        'video_conference' => $program->getVideoConference($id),
        'assessment_summary' => $program->getAssessmentSummary($id),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
