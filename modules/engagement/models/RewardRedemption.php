<?php
namespace App\Models;

class RewardRedemption extends BaseModel
{
    public function getAllRedemptions()
    {
        $employeeName = $this->getEmployeeNameSql('e', 'employee_name');
        $sql = 'SELECT rr.*, ' . $employeeName . ', r.name AS reward_name, rr.redeemed_at 
                FROM eer_reward_redemptions rr 
                LEFT JOIN em_employees e ON rr.employee_id = e.employee_id 
                LEFT JOIN eer_rewards r ON rr.reward_id = r.eer_reward_id';
        return $this->execute($sql)->fetchAll();
    }

    public function createRedemption($employee_id, $reward_id, $points_used)
    {
        $sql = 'INSERT INTO eer_reward_redemptions (employee_id, reward_id, points_used, redeemed_at) 
                VALUES (:employee_id, :reward_id, :points_used, NOW())';
        $params = [
            'employee_id' => $employee_id,
            'reward_id' => $reward_id,
            'points_used' => $points_used,
        ];
        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }
}
