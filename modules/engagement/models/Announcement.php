<?php
namespace App\Models;

class Announcement extends BaseModel
{
    public function postAnnouncement($title, $content, $created_by_employee_id, $category = 'general', $priority = 'normal', $targetAudience = 'all')
    {
        return $this->createAnnouncement([
            'title' => $title,
            'content' => $content,
            'created_by_employee_id' => $created_by_employee_id,
            'type' => 'announcement',
            'category' => $category,
            'priority' => $priority,
            'target_audience' => $targetAudience
        ]);
    }

    public function postDepartmentUpdate($title, $content, $department, $priority, $created_by_employee_id)
    {
        return $this->createAnnouncement([
            'title' => $title,
            'content' => $content,
            'created_by_employee_id' => $created_by_employee_id,
            'type' => 'department_update',
            'department' => $department,
            'priority' => $priority
        ]);
    }
    protected $table = 'eer_announcements';

    public function find($id)
    {
        $sql = "SELECT * FROM $this->table WHERE eer_announcements_id = :id";
        return $this->execute($sql, ['id' => $id])->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAnnouncements($type = 'announcement')
    {
        // Removed JOIN since created_by_employee_id column doesn't exist
        $sql = "SELECT ea.*
            FROM $this->table ea
            WHERE ea.type = :type
            ORDER BY ea.created_at DESC";
        return $this->execute($sql, ['type' => $type])->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getDepartmentUpdates()
    {
        return $this->getAnnouncements('department_update');
    }

    public function getRecognitionAnnouncements()
    {
        return $this->getAnnouncements('recognition');
    }

    public function postRecognitionAnnouncement($title, $content, $created_by_employee_id)
    {
        return $this->createAnnouncement([
            'title' => $title,
            'content' => $content,
            'created_by_employee_id' => $created_by_employee_id,
            'type' => 'recognition'
        ]);
    }

    public function createAnnouncement($data)
    {
        $type = $data['type'] ?? 'announcement';
        $sql = "INSERT INTO $this->table (title, content, created_by_employee_id, type, category, priority, target_audience, department)
                VALUES (:title, :content, :created_by_employee_id, :type, :category, :priority, :target_audience, :department)";
        $params = [
            'title' => $data['title'],
            'content' => $data['content'],
            'created_by_employee_id' => $data['created_by_employee_id'] ?? null,
            'type' => $type,
            'category' => $data['category'] ?? 'general',
            'priority' => $data['priority'] ?? 'normal',
            'target_audience' => $data['target_audience'] ?? 'all',
            'department' => $data['department'] ?? null
        ];
        $stmt = $this->execute($sql, $params);
        // Return the inserted announcement ID on success, or false on failure
        try {
            $lastId = (int) $this->db->lastInsertId();
            if ($lastId > 0) {
                return $lastId;
            }
            // Fallback: if lastInsertId not available, use affected rows
            $affected = $stmt->rowCount();
            return $affected > 0 ? true : false;
        } catch (\Exception $e) {
            error_log('Announcement insert failed: ' . $e->getMessage());
            return false;
        }
    }

    public function shareFile($userId, $fileName, $filePath, $fileSize, $fileType, $description = null, $content = null, $authorType = 'user')
    {
        $authorColumn = $authorType === 'employee' ? 'employee_id' : 'user_id';
        $sql = "INSERT INTO eer_social_posts ($authorColumn, author_type, item_type, file_name, file_path, file_size, file_type, description, content, created_at)
            VALUES (:author_id, :author_type, 'post', :file_name, :file_path, :file_size, :file_type, :description, :content, NOW())";
        $this->execute($sql, [
            'author_id' => $userId,
            'author_type' => $authorType,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_type' => $fileType,
            'description' => $description,
            'content' => $content
        ]);
        return $this->db->lastInsertId();
    }

    public function getSharedFiles()
    {
        $uploaderName = $this->getEmployeeNameSql('he', 'uploader_name');
        $sql = "SELECT sp.*, $uploaderName
            FROM eer_social_posts sp
            LEFT JOIN em_employees he ON sp.user_id = he.employee_id
            WHERE sp.item_type = 'file'
            ORDER BY sp.created_at DESC";
        return $this->execute($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSharedFileById($id)
    {
        $uploaderName = $this->getEmployeeNameSql('he', 'uploader_name');
        $sql = "SELECT sp.*, $uploaderName
            FROM eer_social_posts sp
            LEFT JOIN em_employees he ON sp.user_id = he.employee_id
            WHERE sp.eer_social_post_id = :id AND sp.item_type = 'file'";
        return $this->execute($sql, ['id' => $id])->fetch(\PDO::FETCH_ASSOC);
    }

    public function deleteSharedFile($id)
    {
        $sql = "DELETE FROM eer_social_posts WHERE eer_social_post_id = :id AND item_type = 'file'";
        return $this->execute($sql, ['id' => $id]);
    }

    public function deleteAnnouncement($id)
    {
        $sql = "DELETE FROM $this->table WHERE eer_announcements_id = :id";
        return $this->execute($sql, ['id' => $id]);
    }

    public function getPolicyUpdates()
    {
        $sql = "SELECT * FROM $this->table WHERE category = 'Policy Update'";
        return $this->execute($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}

