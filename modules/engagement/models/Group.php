<?php
namespace App\Models;

class Group extends BaseModel
{
    public function createGroup($name, $description = null, $createdBy = null)
    {
        $nextIdResult = $this->execute('SELECT COALESCE(MAX(eer_group_id), 0) + 1 AS next_id FROM eer_groups')->fetch();
        $nextId = isset($nextIdResult['next_id']) ? (int) $nextIdResult['next_id'] : 1;

        $sql = 'INSERT INTO eer_groups (eer_group_id, name, created_by_employee_id, created_at)
            VALUES (:eer_group_id, :name, :created_by_employee_id, NOW())';

        $this->execute($sql, [
            'eer_group_id' => $nextId,
            'name' => $name,
            'created_by_employee_id' => $createdBy
        ]);

        return $nextId;
    }

    public function getGroups()
    {
        $sql = 'SELECT * FROM eer_groups';
        return $this->execute($sql)->fetchAll();
    }

    public function deleteGroup($groupId)
    {
        $sql = 'DELETE FROM eer_groups WHERE eer_group_id = :group_id';
        $this->execute($sql, ['group_id' => $groupId]);
    }
}