<?php
namespace App\Models;

class Comment extends BaseModel
{
    protected $table = 'eer_comments';

    private function getAuthorTypeColumn()
    {
        $column = $this->execute("SHOW COLUMNS FROM eer_comments LIKE 'author_type'")->fetch();
        return $column ? 'author_type' : 'user_type';
    }

    private function resolveLinkedUserId($employeeId)
    {
        if (empty($employeeId)) {
            return null;
        }

        return $this->execute(
            'SELECT COALESCE(e.user_id, ua.user_id) AS user_id
             FROM em_employees e
             LEFT JOIN user_account ua ON ua.employee_id = e.employee_id
             WHERE e.employee_id = :employee_id
             LIMIT 1',
            ['employee_id' => $employeeId]
        )->fetchColumn() ?: null;
    }

    private function ensureCommentIdAutoIncrement()
    {
        $column = $this->execute("SHOW COLUMNS FROM eer_comments LIKE 'eer_comment_id'")->fetch();
        if (!$column || strpos((string)($column['Extra'] ?? ''), 'auto_increment') !== false) {
            return;
        }

        $zeroId = $this->execute('SELECT COUNT(*) FROM eer_comments WHERE eer_comment_id = 0')->fetchColumn();
        if ((int)$zeroId > 0) {
            $nextId = (int)$this->execute('SELECT COALESCE(MAX(eer_comment_id), 0) + 1 FROM eer_comments')->fetchColumn();
            $this->execute('UPDATE eer_comments SET eer_comment_id = :next_id WHERE eer_comment_id = 0', ['next_id' => $nextId]);
        }

        $this->execute('ALTER TABLE eer_comments MODIFY eer_comment_id INT(11) NOT NULL AUTO_INCREMENT');
    }

    public function getAllComments()
    {
        $sql = "SELECT * FROM $this->table";
        return $this->execute($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createComment($data)
    {
        $this->ensureCommentIdAutoIncrement();
        $typeCol = $this->getAuthorTypeColumn();
        $sql = "INSERT INTO $this->table (post_id, employee_id, user_id, comment, $typeCol) VALUES (:post_id, :employee_id, :user_id, :comment, :user_type)";
        if (!isset($data['user_type'])) {
            $data['user_type'] = 'employee';
        }
        $params = [
            'post_id' => $data['post_id'],
            'employee_id' => $data['user_type'] === 'employee' ? ($data['employee_id'] ?? null) : null,
            'user_id' => $data['user_type'] === 'employee'
                ? $this->resolveLinkedUserId($data['employee_id'] ?? null)
                : ($data['user_id'] ?? null),
            'comment' => $data['comment'],
            'user_type' => $data['user_type']
        ];
        return $this->execute($sql, $params);
    }

    public function deleteComment($id)
    {
        $sql = "DELETE FROM $this->table WHERE eer_comment_id = :id";
        return $this->execute($sql, ['id' => $id]);
    }

    public function getComments($post_id)
    {
        $typeCol = $this->getAuthorTypeColumn();
        $hasUsersTable = $this->hasTable('users');
        $joinSql = $hasUsersTable ? 'LEFT JOIN users u ON c.user_id = u.id' : '';
        $employeeNameSql = $this->getEmployeeNameSql('he', 'author_name');
        $nameSql = $hasUsersTable
            ? "COALESCE($employeeNameSql, u.full_name, u.username, 'hr_engagement') AS author_name"
            : "$employeeNameSql";

        $sql = "SELECT c.*, $nameSql FROM eer_comments c
            LEFT JOIN em_employees he ON c.employee_id = he.employee_id AND c.$typeCol = 'employee'
            $joinSql
            WHERE c.post_id = :post_id ORDER BY c.created_at ASC";

        return $this->execute($sql, ['post_id' => $post_id])->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function addComment($post_id, $author_id, $comment, $author_type = 'employee')
    {
        $this->ensureCommentIdAutoIncrement();
        $typeCol = $this->getAuthorTypeColumn();
        $sql = "INSERT INTO eer_comments (post_id, employee_id, user_id, comment, created_at, $typeCol) VALUES (:post_id, :employee_id, :user_id, :comment, NOW(), :author_type)";
        $params = [
            'post_id' => $post_id,
            'employee_id' => $author_type === 'employee' ? $author_id : null,
            'user_id' => $author_type === 'employee'
                ? $this->resolveLinkedUserId($author_id)
                : $author_id,
            'comment' => $comment,
            'author_type' => $author_type
        ];
        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }
}

