<?php
namespace App\Models;

class Policy extends BaseModel
{
    public function getAllPolicies()
    {
        $sql = 'SELECT * FROM eer_policies';
        return $this->execute($sql)->fetchAll();
    }

    public function find($id)
    {
        $sql = 'SELECT * FROM eer_policies WHERE eer_policy_id = :id';
        return $this->execute($sql, ['id' => $id])->fetch();
    }

    public function postPolicy($title, $content, $category, $effectiveDate, $attachmentPath, $created_by)
    {
        $sql = 'INSERT INTO eer_policies (title, content, category, effective_date, attachment_path, created_by_employee_id, created_at) 
                VALUES (:title, :content, :category, :effective_date, :attachment_path, :created_by, NOW())';
        $this->execute($sql, [
            'title' => $title,
            'content' => $content,
            'category' => $category,
            'effective_date' => $effectiveDate,
            'attachment_path' => $attachmentPath,
            'created_by' => $created_by
        ]);
        return $this->db->lastInsertId();
    }

    public function deletePolicy($id)
    {
        $sql = 'DELETE FROM eer_policies WHERE eer_policy_id = :id';
        return $this->execute($sql, ['id' => $id]);
    }
}