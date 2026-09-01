<?php
namespace App\Models;

class User extends BaseModel
{
    public function all()
    {
        if (!$this->hasTable('user_account') && !$this->hasTable('users')) {
            return [];
        }

        $table = $this->hasTable('user_account') ? 'user_account' : 'users';
        $sql = 'SELECT * FROM ' . $table;
        return $this->execute($sql)->fetchAll();
    }

    public function find($id)
    {
        if (!$this->hasTable('user_account') && !$this->hasTable('users')) {
            return false;
        }

        $table = $this->hasTable('user_account') ? 'user_account' : 'users';
        $sql = 'SELECT * FROM ' . $table . ' WHERE user_id = :id';
        return $this->execute($sql, ['id' => $id])->fetch();
    }

    public function create($data)
    {
        if ($this->hasTable('user_account')) {
            $sql = 'INSERT INTO user_account (employee_id, email, password, created_at) VALUES (:employee_id, :email, :password, NOW())';
            $this->execute($sql, [
                'employee_id' => $data['employee_id'] ?? null,
                'email' => $data['email'] ?? null,
                'password' => $data['password'] ?? '',
            ]);
            return $this->db->lastInsertId();
        }

        if ($this->hasTable('users')) {
            $sql = 'INSERT INTO users (employee_id, username, email, password, full_name, role, status, theme) VALUES (:employee_id, :username, :email, :password, :full_name, :role, :status, :theme)';
            $this->execute($sql, $data);
            return $this->db->lastInsertId();
        }

        return false;
    }
}
