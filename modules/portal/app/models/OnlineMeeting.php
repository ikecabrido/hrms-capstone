<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class OnlineMeeting
{
    private $conn;
    private string $table = 'ep_online_meetings';
    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function all()
    {
        $query = "SELECT * FROM {$this->table} ORDER BY meetings_id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table}
        (title, meeting_link, created_by, employee_id, scheduled_at, status)
        VALUES
        (:title, :meeting_link, :created_by, :employee_id, :scheduled_at, :status)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':title' => $data['title'],
            ':meeting_link' => $data['meeting_link'],
            ':created_by' => $data['created_by'],
            ':employee_id' => $data['employee_id'],
            ':scheduled_at' => $data['scheduled_at'],
            ':status' => $data['status']
        ]);
    }
    public function updateStatus($meetingId, $status)
    {
        $sql = "UPDATE {$this->table}
            SET status = :status
            WHERE meetings_id = :meetings_id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':status' => $status,
            ':meetings_id' => $meetingId
        ]);
    }
    public function delete($meetingId)
    {
        $sql = "DELETE FROM {$this->table}
            WHERE meetings_id = :meetings_id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':meetings_id' => $meetingId
        ]);
    }
}