<?php
namespace App\Models;

class EngagementScore extends BaseModel
{
    public function getAllScores()
    {
        $sql = 'SELECT * FROM eer_engagement_scores';
        return $this->execute($sql)->fetchAll();
    }
}