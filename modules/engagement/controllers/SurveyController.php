<?php
namespace App\Controllers;

use App\Models\Survey;

class SurveyController
{
    private $surveyModel;

    public function __construct()
    {
        $this->surveyModel = new Survey();
    }

    public function show($surveyId)
    {
        return $this->surveyModel->getSurveyById($surveyId);
    }

    public function getSurveyResults($surveyId)
    {
        return $this->surveyModel->getSurveyResponses($surveyId);
    }

    public function calculateAverageRating($surveyId)
    {
        $responses = $this->getSurveyResults($surveyId);
        $total = 0;
        $count = 0;

        foreach ($responses as $response) {
            $answers = json_decode($response['answers'], true);
            foreach ($answers as $answer) {
                if (is_numeric($answer)) {
                    $total += $answer;
                    $count++;
                }
            }
        }

        return $count > 0 ? $total / $count : null;
    }

    public function index()
    {
        return $this->surveyModel->getSurveys();
    }

    public function getWithQuestions($surveyId)
    {
        return $this->surveyModel->getWithQuestions($surveyId);
    }

    public function submit($surveyId, $employeeId, $answers)
    {
        $surveyId = (int) $surveyId;
        $employeeId = (int) $employeeId;

        if ($surveyId <= 0) {
            throw new \InvalidArgumentException('Survey ID is required.');
        }

        if ($employeeId <= 0) {
            throw new \InvalidArgumentException('Employee ID is required.');
        }

        if (!is_array($answers) || empty($answers)) {
            throw new \InvalidArgumentException('At least one survey answer is required.');
        }

        return $this->surveyModel->submitResponse($surveyId, $employeeId, $answers);
    }

    public function store($surveyData, $questions, $created_by_employee_id)
    {
        // Create survey with additional fields
        $surveyId = $this->surveyModel->createSurveyWithDetails(
            $surveyData['title'],
            $created_by_employee_id,
            $surveyData['description'] ?? '',
            $surveyData['survey_type'] ?? 'satisfaction',
            $surveyData['is_anonymous'] ?? 0
        );

        // Add questions
        foreach ($questions as $question) {
            $this->surveyModel->addQuestion($surveyId, $question['question_text']);
        }

        return $surveyId;
    }
}
