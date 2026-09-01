<?php
namespace App\Models;

class AnonymousFeedback extends BaseModel
{
    public function getAllFeedback()
    {
        $sql = 'SELECT * FROM eer_anonymous_feedback';
        return $this->execute($sql)->fetchAll();
    }
}