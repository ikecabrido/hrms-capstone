<?php
namespace App\Models;

class Survey extends BaseModel
{
    public function createSurvey($title, $created_by_employee_id)
    {
        $sql = 'INSERT INTO eer_surveys (title, created_by_employee_id) 
                VALUES (:title, :created_by_employee_id)';

        $params = [
            'title' => $title,
            'created_by_employee_id' => $created_by_employee_id,
        ];

        $this->execute($sql, $params);
        $responseId = $this->db->lastInsertId();
        (new Notification())->notifyHr('A new survey response was submitted for survey #' . (int)$survey_id . '.', 'survey');
        return $responseId;
    }

    public function createSurveyWithDetails($title, $created_by_employee_id, $description = '', $survey_type = 'satisfaction', $is_anonymous = 0)
    {
        $sql = 'INSERT INTO eer_surveys (title, created_by_employee_id, description, survey_type, is_anonymous, created_at)
            VALUES (:title, :created_by_employee_id, :description, :survey_type, :is_anonymous, NOW())';

        $params = [
            'title' => $title,
            'created_by_employee_id' => $created_by_employee_id,
            'description' => $description,
            'survey_type' => $survey_type,
            'is_anonymous' => $is_anonymous,
        ];

        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    public function addQuestion($survey_id, $question_text, $type = 'text')
    {
        $sql = 'INSERT INTO eer_survey_questions (survey_id, question_text, type) 
                VALUES (:survey_id, :question_text, :type)';

        $params = [
            'survey_id' => $survey_id,
            'question_text' => $question_text,
            'type' => $type,
        ];

        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    public function getSurveys()
    {
        $sql = 'SELECT s.*, ' . $this->getEmployeeNameSql('e', 'created_by_name') . ' '
            . 'FROM eer_surveys s '
            . 'LEFT JOIN em_employees e ON s.created_by_employee_id = e.employee_id '
            . 'ORDER BY s.eer_survey_id DESC';
        return $this->execute($sql)->fetchAll();
    }

    public function getWithQuestions($survey_id)
    {
        $survey = $this->execute(
            'SELECT * FROM eer_surveys WHERE eer_survey_id = :id',
            ['id' => $survey_id]
        )->fetch();

        if (!$survey) {
            return null;
        }

        $questions = $this->execute(
            'SELECT * FROM eer_survey_questions WHERE survey_id = :survey_id ORDER BY eer_survey_question_id ASC',
            ['survey_id' => $survey_id]
        )->fetchAll();

        $survey['questions'] = $questions;

        return $survey;
    }

    public function getSurveyById($surveyId)
    {
        $sql = 'SELECT * FROM eer_surveys WHERE eer_survey_id = :survey_id';
        $params = ['survey_id' => $surveyId];
        return $this->execute($sql, $params)->fetch();
    }

    public function submitResponse($survey_id, $employee_id, $answers)
    {
        $sql = 'INSERT INTO eer_survey_responses 
                (survey_id, employee_id, answers, submitted_at) 
                VALUES (:survey_id, :employee_id, :answers, NOW())';

        $params = [
            'survey_id' => $survey_id,
            'employee_id' => $employee_id,
            'answers' => is_array($answers)
                ? json_encode($answers, JSON_UNESCAPED_UNICODE)
                : $answers,
        ];

        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    public function generateResults($surveyId)
    {
        $responses = $this->execute(
            'SELECT answers FROM eer_survey_responses WHERE survey_id = :survey_id',
            ['survey_id' => $surveyId]
        )->fetchAll();

        $results = [];
        foreach ($responses as $response) {
            $answers = json_decode($response['answers'], true);
            foreach ($answers as $question => $answer) {
                if (!isset($results[$question])) {
                    $results[$question] = [];
                }
                if (!isset($results[$question][$answer])) {
                    $results[$question][$answer] = 0;
                }
                $results[$question][$answer]++;
            }
        }
        return $results;
    }

    public function getSurveyResponses($surveyId)
    {
        $sql = 'SELECT * FROM eer_survey_responses WHERE survey_id = :survey_id';
        $params = ['survey_id' => $surveyId];
        return $this->execute($sql, $params)->fetchAll();
    }
}
