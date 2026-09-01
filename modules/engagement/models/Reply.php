<?php
namespace App\Models;

class Reply extends BaseModel
{
    protected $table = 'eer_replies';

    private function getAuthorTypeColumn()
    {
        $column = $this->execute("SHOW COLUMNS FROM eer_replies LIKE 'author_type'")->fetch();
        return $column ? 'author_type' : 'user_type';
    }

    public function getAllReplies()
    {
        $sql = "SELECT * FROM $this->table";
        return $this->execute($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRepliesByComment($commentId)
    {
        $typeCol = $this->getAuthorTypeColumn();
        $hasUsersTable = $this->hasTable('users');
        $joinSql = $hasUsersTable ? 'LEFT JOIN users u ON r.user_id = u.id' : '';
        $employeeNameSql = $this->getEmployeeNameSql('he', 'author_name');
        $nameSql = $hasUsersTable
            ? "COALESCE($employeeNameSql, u.full_name, u.username, 'hr_engagement') AS author_name"
            : "$employeeNameSql";

        $sql = "SELECT r.*, $nameSql FROM eer_replies r
            LEFT JOIN em_employees he ON r.employee_id = he.employee_id AND r.$typeCol = 'employee'
            $joinSql
            WHERE r.comment_id = :comment_id
            ORDER BY r.created_at ASC";

        return $this->execute($sql, ['comment_id' => $commentId])->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRepliesByPost($postId)
    {
        $typeCol = $this->getAuthorTypeColumn();
        $hasUsersTable = $this->hasTable('users');
        $joinSql = $hasUsersTable ? 'LEFT JOIN users u ON r.user_id = u.id' : '';
        $employeeNameSql = $this->getEmployeeNameSql('he', 'author_name');
        $nameSql = $hasUsersTable
            ? "COALESCE($employeeNameSql, u.full_name, u.username, 'hr_engagement') AS author_name"
            : "$employeeNameSql";

        $sql = "SELECT r.*, $nameSql FROM eer_replies r
            LEFT JOIN em_employees he ON r.employee_id = he.employee_id AND r.$typeCol = 'employee'
            $joinSql
            WHERE r.post_id = :post_id
            ORDER BY r.created_at ASC";

        return $this->execute($sql, ['post_id' => $postId])->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function addReply($commentId, $postId, $authorId, $content, $authorType = 'employee', $parentReplyId = null, $mentionedUserId = null)
    {
        $params = [
            'comment_id' => $commentId,
            'post_id' => $postId,
            'parent_reply_id' => $parentReplyId,
            'employee_id' => null,
            'user_id' => null,
            'user_type' => $authorType,
            'content' => $content,
            'mentioned_user_id' => $mentionedUserId,
        ];

        if ($authorType === 'employee') {
            $params['employee_id'] = $authorId;
        } else {
            $params['user_id'] = $authorId;
        }

        $sql = "INSERT INTO eer_replies (comment_id, post_id, parent_reply_id, employee_id, user_id, user_type, content, mentioned_user_id, created_at)
                VALUES (:comment_id, :post_id, :parent_reply_id, :employee_id, :user_id, :user_type, :content, :mentioned_user_id, NOW())";

        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    public function deleteReply($replyId)
    {
        $sql = "DELETE FROM eer_replies WHERE eer_reply_id = :id";
        return $this->execute($sql, ['id' => $replyId]);
    }
}

