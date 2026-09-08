<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\SurveyController;
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$action = $_GET['action'] ?? 'list';
if (!isset($_SESSION['user']) && empty($_SESSION['employee_id']) && empty($_SESSION['user_id']) && $action !== 'list') {
   jsonResponse(['error' => 'Unauthorized'], 401);
}

$ctrl = new SurveyController();
$action = $_GET['action'] ?? 'list';
$data = array_merge($_GET, inputData());

try {
    switch ($action) {
        case 'list':
            jsonResponse($ctrl->index());
            break;
        case 'view':
            $surveyId = $data['id'] ?? null;
            if (empty($surveyId)) jsonResponse(['error' => 'id is required'], 400);
            jsonResponse($ctrl->show((int)$surveyId));
            break;
        case 'create':
            $title = trim((string)($data['title'] ?? ''));
            $questionsRaw = (string)($data['questions_raw'] ?? '');
            $questions = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $questionsRaw)), static function ($question) {
                return $question !== '';
            }));
            $employeeId = (int)($_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);

            if ($title === '') jsonResponse(['error' => 'title is required'], 400);
            if (empty($questions)) jsonResponse(['error' => 'At least one question is required'], 400);
            if ($employeeId <= 0) jsonResponse(['error' => 'Employee ID was not found in the current session'], 401);

            $surveyData = [
                'title' => $title,
                'description' => trim((string)($data['description'] ?? '')),
                'survey_type' => ($data['survey_type'] ?? 'satisfaction') === 'pulse' ? 'pulse' : 'satisfaction',
                'is_anonymous' => !empty($data['is_anonymous']) ? 1 : 0
            ];
            $formattedQuestions = array_map(static function ($question) {
                return ['question_text' => $question];
            }, $questions);
            $id = $ctrl->store($surveyData, $formattedQuestions, $employeeId);
            jsonResponse(['success' => true, 'id' => $id, 'data' => $ctrl->show($id)], 201);
            break;
        case 'submit':
            if (empty($data['survey_id']) || empty($data['answers'])) {
                jsonResponse(['error' => 'survey_id and answers are required'], 400);
            }
            $submitterId = $_SESSION['employee_id']
                ?? $_SESSION['user']['employee_id']
                ?? $_SESSION['user']['id']
                ?? $_SESSION['user_id']
                ?? 0;
            $id = $ctrl->submit((int)$data['survey_id'], (int)$submitterId, $data['answers']);
            jsonResponse(['id' => $id], 201);
            break;
        case 'results':
            $surveyId = $data['id'] ?? null;
            if (empty($surveyId)) jsonResponse(['error' => 'id is required'], 400);
            jsonResponse($ctrl->getSurveyAnalytics((int)$surveyId));
            break;
        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

