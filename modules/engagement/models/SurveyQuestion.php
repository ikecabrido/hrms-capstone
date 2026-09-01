<?php
namespace App\Models;

class SurveyQuestion extends BaseModel
{
    public function create($data)
    {
        $sql = 'INSERT INTO eer_survey_questions (survey_id, question_text, type) VALUES (:survey_id, :question_text, :type)';
        $this->execute($sql, $data);
        return $this->db->lastInsertId();
    }

    public function findBySurvey($survey_id)
    {
        return $this->execute('SELECT * FROM eer_survey_questions WHERE survey_id = :survey_id', ['survey_id' => $survey_id])->fetchAll();
    }
}
