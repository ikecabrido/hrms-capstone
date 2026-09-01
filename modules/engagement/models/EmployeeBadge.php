<?php
namespace App\Models;

class EmployeeBadge extends BaseModel
{
    public function all()
    {
        $employeeName = $this->getEmployeeNameSql('e', 'employee_name');
        $sql = 'SELECT eb.*, ' . $employeeName . ', b.name AS badge_name, eb.awarded_at 
                FROM eer_employee_badges eb 
                LEFT JOIN em_employees e ON eb.employee_id = e.employee_id 
                LEFT JOIN eer_badges b ON eb.badge_id = b.eer_badge_id';
        return $this->execute($sql)->fetchAll();
    }

    public function find($id)
    {
        return $this->execute('SELECT * FROM eer_employee_badges WHERE eer_employee_badge_id = :id', ['id' => $id])->fetch();
    }

    public function create($data)
    {
        $sql = 'INSERT INTO eer_employee_badges (employee_id, badge_id, awarded_at) 
                VALUES (:employee_id, :badge_id, NOW())';
        $this->execute($sql, $data);
        return $this->db->lastInsertId();
    }
}

