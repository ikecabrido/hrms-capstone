<?php
namespace App\Models;

class Badge extends BaseModel
{
    public function all()
    {
        return $this->execute('SELECT * FROM eer_badges')->fetchAll();
    }

    public function find($id)
    {
        return $this->execute('SELECT * FROM eer_badges WHERE eer_badge_id = :id', ['id' => $id])->fetch();
    }

    public function create($data)
    {
        $sql = 'INSERT INTO eer_badges
                (name, description, tier, points_value, category, requirement_type, requirement_value, status)
                VALUES (:name, :description, :tier, :points_value, :category, :requirement_type, :requirement_value, :status)';
        $params = [
            'name' => trim((string)($data['name'] ?? '')),
            'description' => trim((string)($data['description'] ?? '')),
            'tier' => $data['tier'] ?? 'bronze',
            'points_value' => max(1, (int)($data['points_value'] ?? 10)),
            'category' => trim((string)($data['category'] ?? 'achievement')),
            'requirement_type' => $data['requirement_type'] ?? 'manual',
            'requirement_value' => ($data['requirement_value'] ?? '') !== '' ? (int)$data['requirement_value'] : null,
            'status' => $data['status'] ?? 'active'
        ];
        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }
}
