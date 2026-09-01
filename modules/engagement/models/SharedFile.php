<?php
namespace App\Models;

class SharedFile extends BaseModel
{
    public function createFile($fileName, $filePath, $fileSize, $fileType, $uploadedBy, $description, $content = null)
    {
        $sql = 'INSERT INTO eer_social_posts (user_id, author_type, item_type, file_name, file_path, file_size, file_type, description, content, created_at) 
                VALUES (:user_id, :author_type, :item_type, :file_name, :file_path, :file_size, :file_type, :description, :content, NOW())';
        
        $this->execute($sql, [
            'user_id' => $uploadedBy,
            'author_type' => 'user',
            'item_type' => 'file',
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_type' => $fileType,
            'description' => $description,
            'content' => $content
        ]);
        
        return $this->db->lastInsertId();
    }

    public function getAllFiles()
    {
        $nameSql = $this->getEmployeeNameSql('e', 'uploader_name');
        return $this->execute("SELECT sp.*, $nameSql FROM eer_social_posts sp 
                              LEFT JOIN em_employees e ON sp.user_id = e.employee_id
                              WHERE sp.item_type = 'file'
                              ORDER BY sp.created_at DESC")->fetchAll();
    }

    public function getFileById($id)
    {
        return $this->execute('SELECT * FROM eer_social_posts WHERE eer_social_post_id = :id AND item_type = :item_type', ['id' => $id, 'item_type' => 'file'])->fetch();
    }

    public function deleteFile($id)
    {
        $sql = 'DELETE FROM eer_social_posts WHERE eer_social_post_id = :id AND item_type = :item_type';
        return $this->execute($sql, ['id' => $id, 'item_type' => 'file']);
    }
}

