<?php

namespace App\Controllers;

use App\Models\SurveyAnswer;

class SurveyAnswerController
{
    private $model;

    public function __construct()
    {
        $this->model = new SurveyAnswer();
    }

    public function getAll()
    {
        return $this->model->getAll();
    }

    public function create($response_id, $question_id, $answer)
    {
        return $this->model->create($response_id, $question_id, $answer);
    }

    public function getByResponse($response_id)
    {
        return $this->model->getByResponse($response_id);
    }

    public function getBySurvey($survey_id)
    {
        return $this->model->getBySurvey($survey_id);
    }

    public function getByQuestion($question_id)
    {
        return $this->model->getByQuestion($question_id);
    }

    public function validateAnswers($responseId)
    {
        return $this->model->validate($responseId);
    }
}