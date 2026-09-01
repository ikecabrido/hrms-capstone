<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\SurveyAnswerController;

header('Content-Type: application/json');

$controller = new SurveyAnswerController();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($method) {

        case 'GET':

            if (isset($_GET['response_id']) && is_numeric($_GET['response_id'])) {
                $responseId = (int) $_GET['response_id'];
                $answers = $controller->getByResponse($responseId);

                // Debug log
            } elseif (isset($_GET['survey_id']) && is_numeric($_GET['survey_id'])) {
                $surveyId = (int) $_GET['survey_id'];
                $answers = $controller->getBySurvey($surveyId);

                // Debug log
            } else {
                $answers = $controller->getAll();

                // Debug log
            }

            echo json_encode([
                'success' => true,
                'data' => $answers
            ]);
            break;

        case 'POST':

            $data = json_decode(file_get_contents('php://input'), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid JSON format.'
                ]);
                break;
            }

            if (
                isset($data['response_id']) &&
                isset($data['question_id']) &&
                isset($data['answer'])
            ) {

                $result = $controller->create(
                    (int)$data['response_id'],
                    (int)$data['question_id'],
                    $data['answer']
                );

                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);

            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing required fields.'
                ]);
            }

            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed.'
            ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
