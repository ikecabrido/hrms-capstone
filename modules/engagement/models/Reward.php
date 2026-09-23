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
        $tier = $this->getTier($pointsRequired);
        $nextRewardId = (int)$this->execute(
            'SELECT COALESCE(MAX(eer_reward_id), 0) + 1 FROM eer_rewards'
        )->fetchColumn();
        $sql = 'INSERT INTO eer_rewards
            (eer_reward_id, name, description, points_required, category, icon, tier, performance_requirement, created_at)
            VALUES (:reward_id, :name, :description, :points_required, :category, :icon, :tier, :performance_requirement, NOW())';
        $params = [
            'reward_id' => $nextRewardId,
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'points_required' => $pointsRequired,
            'category' => 'general',
            'icon' => 'fas fa-gift',
            'tier' => $tier,
            'performance_requirement' => $this->performanceRequirementForTier($tier)
        ];
        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $pointsRequired = max(1, (int)($data['points_required'] ?? 0));
        $tier = $this->getTier($pointsRequired);
        return $this->execute(
            'UPDATE eer_rewards
             SET name = :name,
                 description = :description,
                 points_required = :points_required,
                 tier = :tier,
                 icon = :icon,
                 performance_requirement = :performance_requirement
             WHERE eer_reward_id = :id',
            [
                'name' => trim((string)($data['name'] ?? '')),
                'description' => trim((string)($data['description'] ?? '')),
                'points_required' => $pointsRequired,
                'tier' => $tier,
                'icon' => 'fas fa-gift',
                'performance_requirement' => $this->performanceRequirementForTier($tier),
                'id' => (int)$id
            ]
        )->rowCount();
    }

    private function syncTiers()
    {
        $this->execute("UPDATE eer_rewards SET
            icon = COALESCE(icon, 'fas fa-gift'),
            performance_requirement = COALESCE(performance_requirement, CASE
                WHEN points_required >= 500 THEN 95
                WHEN points_required >= 300 THEN 90
                WHEN points_required >= 100 THEN 80
                ELSE 70
            END),
            tier = CASE
            WHEN points_required >= 500 THEN 'platinum'
            WHEN points_required >= 300 THEN 'gold'
            WHEN points_required >= 100 THEN 'silver'
            ELSE 'bronze'
        END");
    }

    private function getTier($points)
    {
        if ($points >= 500) return 'platinum';
        if ($points >= 300) return 'gold';
        if ($points >= 100) return 'silver';
        return 'bronze';
    }

    private function performanceRequirementForTier($tier)
    {
        return [
            'bronze' => 70,
            'silver' => 80,
            'gold' => 90,
            'platinum' => 95
        ][$tier] ?? 70;
    }
}
