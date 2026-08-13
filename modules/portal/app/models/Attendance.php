<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Attendance
{
    private $conn;
    private $table = "em_employees";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

}