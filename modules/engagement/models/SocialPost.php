<?php
namespace App\Models;

class SocialPost extends BaseModel
{
    private function getAuthorTypeColumn()
    {
        $column = $this->execute("SHOW COLUMNS FROM eer_social_posts LIKE 'author_type'")->fetch();
        return $column ? 'author_type' : 'user_type';
    }

    public function getPosts()
    {
        $typeCol = $this->getAuthorTypeColumn();
        $nameSql = $this->getEmployeeNameSql('he', 'author_name');

        $sql = "SELECT p.*, $nameSql,
                SUM(CASE WHEN r.type = 'like' THEN 1 ELSE 0 END) AS like_count,
                SUM(CASE WHEN r.type = 'heart' THEN 1 ELSE 0 END) AS heart_count,
                SUM(CASE WHEN r.type = 'wow' THEN 1 ELSE 0 END) AS wow_count
                FROM eer_social_posts p
            LEFT JOIN em_employees he ON p.employee_id = he.employee_id AND p.$typeCol = 'employee'
                LEFT JOIN eer_reactions r ON p.eer_social_post_id = r.post_id
                WHERE p.item_type = 'post'
                GROUP BY p.eer_social_post_id
                ORDER BY p.created_at DESC";

        return $this->execute($sql)->fetchAll();
    }

    public function createPost($author_id, $content, $author_type = 'employee', $description = '')
    {
        $typeCol = $this->getAuthorTypeColumn();
        if ($author_type === 'user') {
            // The users table may not exist in this environment; preserve the author_type value
            // but only store the user_id if provided.
            $sql = "INSERT INTO eer_social_posts (user_id, content, description, item_type, created_at, $typeCol) VALUES (:user_id, :content, :description, 'post', NOW(), :author_type)";
            $params = ['user_id' => $author_id, 'content' => $content, 'description' => $description, 'author_type' => $author_type];
        } else {
            $sql = "INSERT INTO eer_social_posts (employee_id, content, description, item_type, created_at, $typeCol) VALUES (:employee_id, :content, :description, 'post', NOW(), :author_type)";
            $params = ['employee_id' => $author_id, 'content' => $content, 'description' => $description, 'author_type' => $author_type];
        }
        $this->execute($sql, $params);
        $postId = $this->db->lastInsertId();
        (new Notification())->notifyHr('A new social post was published.', 'social', [(int)$author_id]);
        return $postId;
    }

    public function deletePost($post_id)
    {
        $sql = 'DELETE FROM eer_social_posts WHERE eer_social_post_id = :post_id';
        $this->execute($sql, ['post_id' => $post_id]);
    }

    public function editPost($post_id, $content)
    {
        $sql = 'UPDATE eer_social_posts SET content = :content WHERE eer_social_post_id = :post_id';
        $this->execute($sql, ['post_id' => $post_id, 'content' => $content]);
    }
}

