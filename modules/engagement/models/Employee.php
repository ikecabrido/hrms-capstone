<?php
namespace App\Models;

class Employee extends BaseModel
{
    public function all()
    {
        return $this->execute('SELECT * FROM em_employees')->fetchAll();
    }

    public function find($id)
    {
        return $this->execute('SELECT * FROM em_employees WHERE employee_id = :id', ['id' => $id])->fetch();
    }

    public function findByNameOrEmail($name, $email)
    {
        $sql = 'SELECT * FROM em_employees WHERE email = :email OR CONCAT_WS(" ", first_name, middle_name, last_name) = :name LIMIT 1';
        return $this->execute($sql, ['name' => $name, 'email' => $email])->fetch();
    }

    public function create($data)
    {
        $sql = 'INSERT INTO em_employees (employee_code, first_name, middle_name, last_name, department_id, position_id, email, mobile_no, employment_status, created_at) 
                VALUES (:employee_code, :first_name, :middle_name, :last_name, :department_id, :position_id, :email, :mobile_no, :employment_status, NOW())';
        $this->execute($sql, $data);
        return $this->db->lastInsertId();
    }
}