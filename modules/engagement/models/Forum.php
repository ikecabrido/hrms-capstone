<?php
namespace App\Models;

class Forum extends BaseModel
{
    public function createForum($title, $description, $category, $createdBy)
    {
        try {
            $sql = 'INSERT INTO eer_forums (title, description, category, created_by_employee_id, created_at)
                VALUES (:title, :description, :category, :created_by_employee_id, NOW())';

            $this->execute($sql, [
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'created_by_employee_id' => $createdBy
            ]);

            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Forum creation error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllForums()
    {
        try {
            $nameSql = $this->getEmployeeNameSql('e', 'creator_name');
            return $this->execute("SELECT f.*, $nameSql FROM eer_forums f
                                  LEFT JOIN em_employees e ON f.created_by_employee_id = e.employee_id
                                  ORDER BY f.created_at DESC")->fetchAll();
        } catch (Exception $e) {
            error_log("Forum getAll error: " . $e->getMessage());
            // Fallback to simple query
            return $this->execute("SELECT f.*, 'Unknown' as creator_name FROM eer_forums f ORDER BY f.created_at DESC")->fetchAll();
        }
    }

    public function getForumById($id)
    {
        return $this->execute('SELECT * FROM eer_forums WHERE eer_forum_id = :id', ['id' => $id])->fetch();
    }

    public function updateForum($id, $data)
    {
        $sql = 'UPDATE eer_forums SET title = :title, description = :description, category = :category WHERE eer_forum_id = :id';
        return $this->execute($sql, [
            'id' => $id,
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'category' => $data['category'] ?? ''
        ]);
    }

    public function deleteForum($id)
    {
        $sql = 'DELETE FROM eer_forums WHERE eer_forum_id = :id';
        return $this->execute($sql, ['id' => $id]);
    }
}
