<?php
namespace App\Controllers;

use App\Models\Badge;

class BadgeController
{
    private $badge;

    public function __construct()
    {
        $this->badge = new Badge();
    }

    public function index()
    {
        return $this->badge->all();
    }

    public function show($id)
    {
        return $this->badge->find($id);
    }

    public function store($data)
    {
        return $this->badge->create($data);
    }

    public function assignBadge($employeeId, $badgeId)
    {
        return $this->badge->assignToEmployee($employeeId, $badgeId);
    }
}
