<?php
namespace App\Controllers;

use App\Models\RewardRedemption;

class RewardRedemptionController
{
    private $rewardRedemption;

    public function __construct()
    {
        $this->rewardRedemption = new RewardRedemption();
    }

    public function index()
    {
        return $this->rewardRedemption->getAllRedemptions();
    }

    public function create($data)
    {
        return $this->rewardRedemption->createRedemption($data['employee_id'], $data['reward_id'], $data['points_used']);
    }

    public function getRedemptionHistory($employeeId)
    {
        return $this->rewardRedemption->getHistoryByEmployee($employeeId);
    }
}
