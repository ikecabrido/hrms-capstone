<?php
namespace App\Models;

class Reward extends BaseModel
{
    public function all()
    {
        $this->syncTiers();
        return $this->execute('SELECT * FROM eer_rewards ORDER BY points_required')->fetchAll();
    }

    public function find($id)
    {
        return $this->execute('SELECT * FROM eer_rewards WHERE eer_reward_id = :id', ['id' => $id])->fetch();
    }

    public function create($data)
    {
        $pointsRequired = (int)($data['points_required'] ?? 0);
        $sql = 'INSERT INTO eer_rewards (name, description, points_required, tier, created_at)
                VALUES (:name, :description, :points_required, :tier, NOW())';
        $params = [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'points_required' => $pointsRequired,
            'tier' => $this->getTier($pointsRequired)
        ];
        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    private function syncTiers()
    {
        $this->execute("UPDATE eer_rewards SET tier = CASE
            WHEN points_required >= 1000 THEN 'platinum'
            WHEN points_required >= 750 THEN 'gold'
            WHEN points_required >= 500 THEN 'silver'
            ELSE 'bronze'
        END");
    }

    private function getTier($points)
    {
        if ($points >= 1000) return 'platinum';
        if ($points >= 750) return 'gold';
        if ($points >= 500) return 'silver';
        return 'bronze';
    }
}
