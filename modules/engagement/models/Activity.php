<?php
namespace App\Models;

class Activity extends BaseModel
{
    public function getAllActivities()
    {
        $sql = 'SELECT * FROM eer_activities';
        return $this->execute($sql)->fetchAll();
    }

    public function getParticipants($activity_id)
    {
        $sql = 'SELECT * FROM eer_activity_participants WHERE activity_id = :activity_id';
        return $this->execute($sql, ['activity_id' => $activity_id])->fetchAll();
    }
}