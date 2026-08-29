<?php

namespace App\Models;

use Exception;
use App\Config\Database;
use PDO;

class Enrollment
{
    private $conn;

    private string $enrollmentTable = 'ld_course';

    public function __construct()
    {
        $database = new Database;
        $this->conn = $database->getConnection();
    }
}