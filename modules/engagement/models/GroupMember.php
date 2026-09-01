<?php
namespace App\Models;

require_once __DIR__ . '/BaseModel.php';

class GroupMember extends BaseModel
{
    public function addMember($groupId, $employeeId)
    {
        $sql = 'INSERT INTO eer_group_members (group_id, employee_id) VALUES (:group_id, :employee_id)';
        $this->execute($sql, ['group_id' => $groupId, 'employee_id' => $employeeId]);
        return $this->db->lastInsertId();
    }

    public function getMembersByGroup($groupId)
    {
        $employeeName = $this->getEmployeeNameSql('he', 'employee_name');
        $sql = 'SELECT gm.eer_group_member_id, gm.group_id, gm.employee_id, ' . $employeeName . '
            FROM eer_group_members gm
            LEFT JOIN em_employees he ON he.employee_id = gm.employee_id
            WHERE gm.group_id = :group_id';
        $rows = $this->execute($sql, ['group_id' => $groupId])->fetchAll();

        return $rows;
    }

    public function removeMember($groupMemberId)
    {
        $sql = 'DELETE FROM eer_group_members WHERE eer_group_member_id = :group_member_id';
        $this->execute($sql, ['group_member_id' => $groupMemberId]);
    }

    public function assignRole($groupId, $employeeId, $role)
    {
        $sql = 'UPDATE eer_group_members SET role = :role WHERE group_id = :group_id AND employee_id = :employee_id';
        $this->execute($sql, ['group_id' => $groupId, 'employee_id' => $employeeId, 'role' => $role]);
    }
}
