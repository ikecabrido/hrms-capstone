<?php
namespace App\Controllers;

use App\Models\EmployeeBadge;

class EmployeeBadgeController
{
    private $employeeBadge;

    public function __construct()
    {
        $this->employeeBadge = new EmployeeBadge();
    }

    public function index()
    {
        return $this->employeeBadge->all();
    }

    public function show($id)
    {
        return $this->employeeBadge->find($id);
    }

    public function store($data)
    {
        return $this->employeeBadge->create($data);
    }

    public function revokeBadge($employeeId, $badgeId)
    {
        return $this->employeeBadge->revokeFromEmployee($employeeId, $badgeId);
    }
}
