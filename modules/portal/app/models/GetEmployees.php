<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class GetEmployees
{
    private $conn;

    private $usersTable = 'ep_users';
    private $employeesTable = 'em_employees';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function sendAll()
    {
        $query = "
        SELECT
            eu.*,
            ee.*
        FROM {$this->usersTable} eu
        LEFT JOIN {$this->employeesTable} ee
            ON eu.id = ee.user_id
        ORDER BY eu.id DESC
    ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}