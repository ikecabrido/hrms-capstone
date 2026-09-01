<?php
namespace App\Models;

class Event extends BaseModel
{
    protected $table = 'eer_events';

    public function postEvent($title, $date, $description, $created_by)
    {
        $sql = "INSERT INTO $this->table (title, event_date, description, created_by) VALUES (:title, :event_date, :description, :created_by)";
        return $this->execute($sql, [
            'title' => $title,
            'event_date' => $date,
            'description' => $description,
            'created_by' => $created_by
        ]);
    }

    public function getEvents()
    {
        $sql = "SELECT * FROM $this->table ORDER BY event_date ASC";
        return $this->execute($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}