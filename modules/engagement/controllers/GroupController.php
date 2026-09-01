<?php
namespace App\Controllers;

use App\Models\Group;

class GroupController
{
    private $groupModel;

    public function __construct()
    {
        $this->groupModel = new Group();
    }

    public function createGroup($name, $description = null, $createdBy = null)
    {
        return $this->groupModel->createGroup($name, $description, $createdBy);
    }

    public function getGroups()
    {
        return $this->groupModel->getGroups();
    }

    public function deleteGroup($groupId)
    {
        $this->groupModel->deleteGroup($groupId);
    }

    public function trackGroupActivity($groupId)
    {
        return $this->groupModel->getActivityLog($groupId);
    }
}