<?php

class EmployeeModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getActiveEmployees(): array
    {
        $stmt = $this->db->query("
            SELECT e.*
            FROM employees e
            WHERE e.employment_status = 'Active'
            ORDER BY e.full_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) as total 
            FROM employees 
            WHERE employment_status = 'Active'
        ");
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}
