<?php
namespace App\Controllers;

use App\Models\Project;

class ProjectController
{
    private $project;

    public function __construct()
    {
        $this->project = new Project();
    }

    public function createProject($name, $description, $deadline, $status, $createdBy)
    {
        return $this->project->createProject($name, $description, $deadline, $status, $createdBy);
    }

    public function getProjects()
    {
        return $this->project->getAllProjects();
    }

    public function getProject($id)
    {
        return $this->project->getProjectById($id);
    }

    public function updateProjectStatus($id, $status)
    {
        return $this->project->updateProjectStatus($id, $status);
    }

    public function deleteProject($id)
    {
        return $this->project->deleteProject($id);
    }
}