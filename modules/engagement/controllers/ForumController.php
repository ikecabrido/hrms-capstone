<?php
namespace App\Controllers;

use App\Models\Forum;

class ForumController
{
    private $forum;

    public function __construct()
    {
        $this->forum = new Forum();
    }

    public function createForum($title, $description, $category, $createdBy)
    {
        return $this->forum->createForum($title, $description, $category, $createdBy);
    }

    public function getForums()
    {
        return $this->forum->getAllForums();
    }

    public function getForum($id)
    {
        return $this->forum->getForumById($id);
    }

    public function updateForum($id, $data)
    {
        return $this->forum->updateForum($id, $data);
    }

    public function deleteForum($id)
    {
        return $this->forum->deleteForum($id);
    }
}