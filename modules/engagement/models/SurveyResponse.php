<?php
namespace App\Models;

class SurveyResponse extends BaseModel
{
    public function submit($data)
    {
        $surveyId = (int)($data['survey_id'] ?? 0);
        if ($surveyId <= 0) {
            throw new \InvalidArgumentException('survey_id is required.');
        }

        if (!array_key_exists('answers', $data)) {
            throw new \InvalidArgumentException('answers are required.');
        }

        $answers = is_array($data['answers'])
            ? json_encode($data['answers'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            : (string)$data['answers'];

        $sql = 'INSERT INTO eer_survey_responses (survey_id, employee_id, answers) VALUES (:survey_id, :employee_id, :answers)';
        $this->execute($sql, [
            'survey_id' => $surveyId,
            'employee_id' => $data['employee_id'] ?? null,
            'answers' => $answers,
        ]);
        $responseId = $this->db->lastInsertId();
        (new Notification())->notifyHr('A new survey response was submitted for survey #' . $surveyId . '.', 'survey');
        return $responseId;
    }

    public function getBySurvey($survey_id)
    {
        $nameSql = $this->getEmployeeNameSql('e', 'employee_name');
        $sql = "SELECT r.*, $nameSql FROM eer_survey_responses r LEFT JOIN em_employees e ON r.employee_id = e.employee_id WHERE r.survey_id = :survey_id ORDER BY r.eer_survey_response_id DESC";
        return $this->execute($sql, ['survey_id' => $survey_id])->fetchAll();
    }

    public function addFeedback($surveyId, $userId, $comment)
    {
        $sql = 'INSERT INTO eer_survey_feedback_id (employee_id, comment, rating, survey_id) VALUES (:employee_id, :comment, :rating, :survey_id)';
        $this->execute($sql, [
            'employee_id' => $userId,
            'rating' => 3,
            'comment' => $comment,
            'survey_id' => $surveyId
        ]);
    }

    public function getResponses($surveyId)
    {
        $sql = 'SELECT * FROM eer_survey_responses WHERE survey_id = :survey_id';
        return $this->execute($sql, ['survey_id' => $surveyId])->fetchAll();
    }
}

