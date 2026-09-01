<?php
namespace App\Controllers;

use App\Models\Feedback;

class FeedbackController
{
    private $feedback;

    public function __construct()
    {
        $this->feedback = new Feedback();
    }

    public function index($employee_id = null)
    {
        return $this->feedback->getFeedback($employee_id);
    }

    public function store(
        $employee_id,
        $comment,
        $rating = null,
        $evaluator_type = 'Self',
        $category = 'Performance',
        $is_anonymous = 0,
        $evaluation_date = null
    ) {
        return $this->feedback->createFeedback(
            $employee_id,
            $comment,
            $rating,
            null, // survey_id
            $category,
            $is_anonymous,
            $evaluation_date,
            $evaluator_type
        );
    }

    public function generateSurveyResults($surveyId)
    {
        return $this->feedback->getSurveyResults($surveyId);
    }

    public function performSentimentAnalysis($surveyId)
    {
        return $this->feedback->analyzeSentiment($surveyId);
    }
}
