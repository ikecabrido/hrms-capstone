<?php
namespace App\Models;

class Message extends BaseModel
{
    public function create($data)
    {
        $sql = 'INSERT INTO eer_messages (sender_id, receiver_id, message, timestamp) 
                VALUES (:sender_id, :receiver_id, :message, NOW())';
        $this->execute($sql, $data);
        return $this->db->lastInsertId();
    }

    public function threads($employeeId)
    {
        $senderNameExpr = $this->getEmployeeNameExpression('hs');
        $receiverNameExpr = $this->getEmployeeNameExpression('hr');

        $sql = "SELECT m.*,
                COALESCE($senderNameExpr, m.sender_id) AS sender_name,
                COALESCE($receiverNameExpr, m.receiver_id) AS receiver_name
                FROM eer_messages m
                LEFT JOIN em_employees hs ON m.sender_id = hs.employee_id
                LEFT JOIN em_employees hr ON m.receiver_id = hr.employee_id
                WHERE m.sender_id = :employee_id OR m.receiver_id = :employee_id
                ORDER BY m.timestamp DESC";
            return $this->execute($sql, ['employee_id' => (string)$employeeId])->fetchAll();
    }

    public function allThreads()
    {
        $senderNameExpr = $this->getEmployeeNameExpression('hs');
        $receiverNameExpr = $this->getEmployeeNameExpression('hr');

        $sql = "SELECT m.*,
                COALESCE($senderNameExpr, m.sender_id) AS sender_name,
                COALESCE($receiverNameExpr, m.receiver_id) AS receiver_name
                FROM eer_messages m
                LEFT JOIN em_employees hs ON m.sender_id = hs.employee_id
                LEFT JOIN em_employees hr ON m.receiver_id = hr.employee_id
                ORDER BY m.timestamp DESC";
        return $this->execute($sql)->fetchAll();
    }
}

