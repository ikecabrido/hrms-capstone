<?php
namespace App\Models;

class Reward extends BaseModel
{
    public function all()
    {
        return $this->execute('SELECT * FROM eer_rewards ORDER BY points_required')->fetchAll();
    }

    public function find($id)
    {
        return $this->execute('SELECT * FROM eer_rewards WHERE eer_reward_id = :id', ['id' => $id])->fetch();
    }

    public function create($data)
    {
        $sql = 'INSERT INTO eer_rewards (name, description, points_required, created_at) 
                VALUES (:name, :description, :points_required, NOW())';
        $params = [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'points_required' => (int)($data['points_required'] ?? 0)
        ];
        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }
}
