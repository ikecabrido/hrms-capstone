<?php
namespace App\Models;

class SurveyTarget extends BaseModel
{
    public function all()
    {
        $employeeName = $this->getEmployeeNameSql('e', 'employee_name');
        $sql = 'SELECT st.*, ' . $employeeName . ', s.title AS survey_title 
                FROM eer_survey_targets st 
                LEFT JOIN em_employees e ON st.employee_id = e.employee_id 
                LEFT JOIN eer_surveys s ON st.survey_id = s.eer_survey_id';
        return $this->execute($sql)->fetchAll();
    }

    public function find($id)
    {
        return $this->execute('SELECT * FROM eer_survey_targets WHERE eer_survey_target_id = :id', ['id' => $id])->fetch();
    }

    public function create($data)
    {
        $sql = 'INSERT INTO eer_survey_targets (survey_id, employee_id, status) 
                VALUES (:survey_id, :employee_id, :status)';
        $this->execute($sql, $data);
        return $this->db->lastInsertId();
    }
}

