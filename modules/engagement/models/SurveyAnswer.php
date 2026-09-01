<?php

namespace App\Models;

class SurveyAnswer extends BaseModel
{
    public function getAll()
    {
        $sql = 'SELECT * FROM eer_survey_answers ORDER BY response_id, question_id';
        return $this->execute($sql)->fetchAll();
    }

    public function create($response_id, $question_id, $answer)
    {
        $sql = 'INSERT INTO eer_survey_answers (response_id, question_id, answer) VALUES (:response_id, :question_id, :answer)';
        $params = [
            'response_id' => $response_id,
            'question_id' => $question_id,
            'answer' => $answer,
        ];
        return $this->execute($sql, $params);
    }

    public function getByResponse($response_id)
    {
        $sql = 'SELECT * FROM eer_survey_answers WHERE response_id = :response_id ORDER BY question_id';
        $params = ['response_id' => $response_id];
        return $this->execute($sql, $params)->fetchAll();
    }

    public function getBySurvey($survey_id)
    {
        $sql = 'SELECT a.* FROM eer_survey_answers a
                JOIN eer_survey_responses r ON a.response_id = r.eer_survey_response_id
                WHERE r.survey_id = :survey_id
                ORDER BY a.response_id, a.question_id';
        $params = ['survey_id' => $survey_id];
        return $this->execute($sql, $params)->fetchAll();
    }

    public function getByQuestion($question_id)
    {
        $sql = 'SELECT * FROM eer_survey_answers WHERE question_id = :question_id ORDER BY response_id';
        $params = ['question_id' => $question_id];
        return $this->execute($sql, $params)->fetchAll();
    }
}