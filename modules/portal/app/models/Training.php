<?php

namespace App\Models;

use Exception;
use App\Config\Database;
use PDO;

class Training
{
    private $conn;
    private string $table = '';
    public function __construct()
    {
        $database = new Database;
        $this->conn = $database->getConnection();
    }
}