<?php
namespace App\Models;

class Reaction extends BaseModel
{
    private function hasUserIdColumn()
    {
        $column = $this->execute("SHOW COLUMNS FROM eer_reactions LIKE 'user_id'")->fetch();
        return (bool) $column;
    }

    private function ensureUserIdColumnExists()
    {
        if (!$this->hasUserIdColumn()) {
            $this->execute('ALTER TABLE eer_reactions ADD COLUMN user_id int(11) DEFAULT NULL AFTER employee_id');
        }
    }

    private function getAuthorTypeColumn()
    {
        $column = $this->execute("SHOW COLUMNS FROM eer_reactions LIKE 'author_type'")->fetch();
        return $column ? 'author_type' : 'user_type';
    }

    public function addReaction($postId, $employeeId, $userId, $type)
    {
        $userIdExists = $this->hasUserIdColumn();
        if (!$userIdExists) {
            $this->ensureUserIdColumnExists();
            $userIdExists = $this->hasUserIdColumn();
        }

        $existing = $this->getReactionByPostAndActor($postId, $employeeId, $userId, $userIdExists);
        if ($existing) {
            if ($existing['type'] === $type) {
                $this->removeReaction($existing['eer_reaction_id']);
                return [
                    'status' => 'removed',
                    'reaction_id' => $existing['eer_reaction_id'],
                    'type' => $type
                ];
            }
            $sql = 'UPDATE eer_reactions SET type = :type, created_at = NOW() WHERE eer_reaction_id = :reaction_id';
            $this->execute($sql, ['type' => $type, 'reaction_id' => $existing['eer_reaction_id']]);
            return [
                'status' => 'changed',
                'reaction_id' => $existing['eer_reaction_id'],
                'old_type' => $existing['type'],
                'new_type' => $type
            ];
        }

        $typeCol = $this->getAuthorTypeColumn();
        $columns = ['post_id', 'employee_id', 'type', 'created_at', $typeCol];
        $params = ['post_id' => $postId, 'employee_id' => $employeeId, 'type' => $type, 'author_type' => !empty($employeeId) ? 'employee' : 'user'];

        if ($userIdExists) {
            $columns = ['post_id', 'employee_id', 'user_id', 'type', 'created_at', $typeCol];
            $params['user_id'] = $userId;
            $sql = "INSERT INTO eer_reactions (" . implode(', ', $columns) . ") VALUES (:post_id, :employee_id, :user_id, :type, NOW(), :author_type)";
        } else {
            $sql = "INSERT INTO eer_reactions (" . implode(', ', $columns) . ") VALUES (:post_id, :employee_id, :type, NOW(), :author_type)";
        }

        $this->execute($sql, $params);
        return [
            'status' => 'added',
            'reaction_id' => $this->db->lastInsertId(),
            'type' => $type
        ];
    }

    public function getReactionByPostAndActor($postId, $employeeId, $userId, $userIdExists = true)
    {
        if (!empty($employeeId)) {
            $sql = 'SELECT * FROM eer_reactions WHERE post_id = :post_id AND employee_id = :employee_id LIMIT 1';
            return $this->execute($sql, ['post_id' => $postId, 'employee_id' => $employeeId])->fetch();
        }

        if (!empty($userId) && $userIdExists) {
            $sql = 'SELECT * FROM eer_reactions WHERE post_id = :post_id AND user_id = :user_id LIMIT 1';
            return $this->execute($sql, ['post_id' => $postId, 'user_id' => $userId])->fetch();
        }

        return null;
    }

    public function getReactionsByPost($postId)
    {
        $sql = 'SELECT * FROM eer_reactions WHERE post_id = :post_id';
        return $this->execute($sql, ['post_id' => $postId])->fetchAll();
    }

    public function removeReaction($reactionId)
    {
        $sql = 'DELETE FROM eer_reactions WHERE eer_reaction_id = :reaction_id';
        $this->execute($sql, ['reaction_id' => $reactionId]);
    }
}