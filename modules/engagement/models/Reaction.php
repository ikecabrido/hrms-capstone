<?php
namespace App\Models;

class Reaction extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTargetColumnsExist();
        $this->ensureAngryReactionType();
    }

    private function hasReactionColumn($column)
    {
        return (bool)$this->execute("SHOW COLUMNS FROM eer_reactions LIKE :column_name", ['column_name' => $column])->fetch();
    }

    private function ensureTargetColumnsExist()
    {
        if (!$this->hasReactionColumn('target_type')) {
            $this->execute("ALTER TABLE eer_reactions ADD COLUMN target_type VARCHAR(20) NOT NULL DEFAULT 'post' AFTER post_id");
        }
        if (!$this->hasReactionColumn('target_id')) {
            $this->execute('ALTER TABLE eer_reactions ADD COLUMN target_id INT(11) NULL AFTER target_type');
        }
        $this->execute("UPDATE eer_reactions SET target_type = 'post', target_id = post_id WHERE target_id IS NULL");
    }

    private function ensureAngryReactionType()
    {
        $column = $this->execute("SHOW COLUMNS FROM eer_reactions LIKE 'type'")->fetch();
        if (!$column || strpos((string)($column['Type'] ?? ''), "'angry'") !== false) {
            return;
        }

        $this->execute("ALTER TABLE eer_reactions MODIFY type ENUM('like','heart','wow','angry') DEFAULT 'like'");
    }

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

    public function addReaction($postId, $employeeId, $userId, $type, $targetType = 'post', $targetId = null)
    {
        $userIdExists = $this->hasUserIdColumn();
        if (!$userIdExists) {
            $this->ensureUserIdColumnExists();
            $userIdExists = $this->hasUserIdColumn();
        }

        $targetId = (int)($targetId ?: $postId);
        $existing = $this->getReactionByTargetAndActor($targetType, $targetId, $employeeId, $userId, $userIdExists);
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
        $columns = ['post_id', 'target_type', 'target_id', 'employee_id', 'type', 'created_at', $typeCol];
        $params = ['post_id' => $postId, 'target_type' => $targetType, 'target_id' => $targetId, 'employee_id' => $employeeId, 'type' => $type, 'author_type' => !empty($employeeId) ? 'employee' : 'user'];

        if ($userIdExists) {
            $columns = ['post_id', 'target_type', 'target_id', 'employee_id', 'user_id', 'type', 'created_at', $typeCol];
            $params['user_id'] = $userId;
            $sql = "INSERT INTO eer_reactions (" . implode(', ', $columns) . ") VALUES (:post_id, :target_type, :target_id, :employee_id, :user_id, :type, NOW(), :author_type)";
        } else {
            $sql = "INSERT INTO eer_reactions (" . implode(', ', $columns) . ") VALUES (:post_id, :target_type, :target_id, :employee_id, :type, NOW(), :author_type)";
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
        return $this->getReactionByTargetAndActor('post', (int)$postId, $employeeId, $userId, $userIdExists);
    }

    public function getReactionByTargetAndActor($targetType, $targetId, $employeeId, $userId, $userIdExists = true)
    {
        if (!empty($employeeId)) {
            $sql = 'SELECT * FROM eer_reactions WHERE target_type = :target_type AND target_id = :target_id AND employee_id = :employee_id LIMIT 1';
            return $this->execute($sql, ['target_type' => $targetType, 'target_id' => $targetId, 'employee_id' => $employeeId])->fetch();
        }

        if (!empty($userId) && $userIdExists) {
            $sql = 'SELECT * FROM eer_reactions WHERE target_type = :target_type AND target_id = :target_id AND user_id = :user_id LIMIT 1';
            return $this->execute($sql, ['target_type' => $targetType, 'target_id' => $targetId, 'user_id' => $userId])->fetch();
        }

        return null;
    }

    public function getReactionsByPost($postId)
    {
        return $this->getReactionsByTarget('post', (int)$postId);
    }

    public function getReactionsByTarget($targetType, $targetId)
    {
        $sql = 'SELECT * FROM eer_reactions WHERE target_type = :target_type AND target_id = :target_id';
        return $this->execute($sql, ['target_type' => $targetType, 'target_id' => (int)$targetId])->fetchAll();
    }

    public function getReactionCounts($targetType, $targetId)
    {
        $sql = "SELECT type, COUNT(*) AS reaction_count FROM eer_reactions WHERE target_type = :target_type AND target_id = :target_id GROUP BY type";
        $counts = ['like' => 0, 'heart' => 0, 'wow' => 0, 'angry' => 0];
        foreach ($this->execute($sql, ['target_type' => $targetType, 'target_id' => (int)$targetId])->fetchAll() as $row) {
            $counts[$row['type']] = (int)$row['reaction_count'];
        }
        return $counts;
    }

    public function removeReaction($reactionId)
    {
        $sql = 'DELETE FROM eer_reactions WHERE eer_reaction_id = :reaction_id';
        $this->execute($sql, ['reaction_id' => $reactionId]);
    }
}