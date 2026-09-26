<?php

namespace App\Models;

use Exception;
use App\Config\Database;
use PDO;

class Complaint
{
    private $conn;
    private string $table = 'lc_complaints';
    private string $employeeTable = 'em_employees';
    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function all()
    {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getComplaint(string $reporterName): array
    {
        $query = "
        SELECT
            c.*,
            c.employee_id AS respondent_id,
            CONCAT(
                e.first_name, ' ', e.last_name
            ) AS respondent_name
        FROM {$this->table} c
        LEFT JOIN {$this->employeeTable} e
            ON e.employee_id = c.employee_id
        WHERE c.reporter_name = :reporter_name
        ORDER BY c.created_at DESC
    ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':reporter_name' => $reporterName
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create($data)
    {
        $sql = "
        INSERT INTO {$this->table} (
            type,
            severity,
            status,
            current_step_key,
            current_state,
            version,
            employee_id,
            reporter_name,
            reporter_department,
            respondent_employee_id,
            respondent_name,
            incident_date,
            incident_time,
            location,
            title,
            description,
            assigned_to,
            assigned_name,
            workflow_progress,
            created_at,
            updated_at,
            employee_response,
            employee_response_date,
            termination_reply,
            termination_review_status
        ) VALUES (
            :type,
            :severity,
            :status,
            :current_step_key,
            :current_state,
            :version,
            :employee_id,
            :reporter_name,
            :reporter_department,
            :respondent_employee_id,
            :respondent_name,
            :incident_date,
            :incident_time,
            :location,
            :title,
            :description,
            :assigned_to,
            :assigned_name,
            :workflow_progress,
            :created_at,
            :updated_at,
            :employee_response,
            :employee_response_date,
            :termination_reply,
            :termination_review_status
        )
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':type' => $data['type'],
            ':severity' => $data['severity'],
            ':status' => $data['status'],

            ':current_step_key' => $data['current_step_key'],
            ':current_state' => $data['current_state'],
            ':version' => $data['version'],

            ':employee_id' => $data['employee_id'],

            ':reporter_name' => $data['reporter_name'],
            ':reporter_department' => $data['reporter_department'],

            ':respondent_employee_id' => $data['respondent_employee_id'],
            ':respondent_name' => $data['respondent_name'],

            ':incident_date' => $data['incident_date'],
            ':incident_time' => $data['incident_time'],
            ':location' => $data['location'],
            ':title' => $data['title'],
            ':description' => $data['description'],

            ':assigned_to' => $data['assigned_to'] ?? null,
            ':assigned_name' => $data['assigned_name'] ?? null,

            ':workflow_progress' => $data['workflow_progress'] ?? null,

            ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            ':updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s'),

            ':employee_response' => $data['employee_response'] ?? null,
            ':employee_response_date' => $data['employee_response_date'] ?? null,

            ':termination_reply' => $data['termination_reply'] ?? null,
            ':termination_review_status' => $data['termination_review_status'] ?? null
        ]);
    }
}