<?php
namespace App\Controllers;

use App\Models\GroupMember;

class GroupMemberController
{
    private $groupMemberModel;

    public function __construct()
    {
        $this->groupMemberModel = new GroupMember();
    }

    public function addMember($groupId, $employeeId)
    {
        if (empty($groupId) || empty($employeeId)) {
            throw new \InvalidArgumentException('Group ID and Employee ID are required.');
        }

        return $this->groupMemberModel->addMember($groupId, $employeeId);
    }

    public function getMembersByGroup($groupId)
    {
        if (empty($groupId)) {
            throw new \InvalidArgumentException('Group ID is required.');
        }

        return $this->groupMemberModel->getMembersByGroup($groupId);
    }

    public function removeMember($groupMemberId)
    {
        if (empty($groupMemberId)) {
            throw new \InvalidArgumentException('Group Member ID is required.');
        }

        $this->groupMemberModel->removeMember($groupMemberId);
    }

    public function assignRole($groupId, $employeeId, $role)
    {
        if (empty($groupId) || empty($employeeId) || empty($role)) {
            throw new \InvalidArgumentException('Group ID, Employee ID, and Role are required.');
        }

        return $this->groupMemberModel->assignRole($groupId, $employeeId, $role);
    }
}