<?php
namespace App\Models;

class GrievanceUpdate extends BaseModel
{
    public function create($data)
    {
        $sql = 'INSERT INTO eer_grievance_updates (grievance_id, update_text, updated_by_employee_id, updated_at) VALUES (:grievance_id, :update_text, :updated_by_employee_id, NOW())';
        // map key if caller provided updated_by_user_id
        if (isset($data['updated_by_user_id']) && !isset($data['updated_by_employee_id'])) {
            $data['updated_by_employee_id'] = $data['updated_by_user_id'];
            unset($data['updated_by_user_id']);
        }
        $this->execute($sql, $data);
        return $this->db->lastInsertId();
    }

    public function getByGrievance($grievance_id)
    {
        $nameSql = $this->getEmployeeNameSql('e', 'updated_by_name');
        $sql = "SELECT gu.*, $nameSql FROM eer_grievance_updates gu 
            LEFT JOIN em_employees e ON gu.updated_by_employee_id = e.employee_id
                WHERE gu.grievance_id = :grievance_id 
                ORDER BY gu.updated_at ASC";
        return $this->execute($sql, ['grievance_id' => $grievance_id])->fetchAll();
    }
}

