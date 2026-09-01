<?php
namespace App\Controllers;

use App\Models\User;

class UserController
{
    private $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function index()
    {
        return $this->user->all();
    }

    public function show($id)
    {
        return $this->user->find($id);
    }

    public function store($data)
    {
        return $this->user->create($data);
    }

    public function getUserActivity($userId)
    {
        return $this->user->getActivityLog($userId);
    }
}
