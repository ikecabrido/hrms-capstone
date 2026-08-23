<?php

namespace App\Models;

use Exception;
use App\Config\Database;
use PDO;

class Complaint
{
    private $conn;
    private string $table = 'lc_incidents';
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
    public function getComplaint(int $reporterEmployeeId): array
    {
        $query = "
        SELECT *
        FROM {$this->table}
        WHERE reporter_employee_id = :reporter_employee_id
        ORDER BY created_at DESC
    ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':reporter_employee_id' => $reporterEmployeeId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(array $data): bool
    {
        try {

            $this->conn->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Get Reporter
            |--------------------------------------------------------------------------
            */
            $reporterSql = "
            SELECT
                id,
                first_name,
                last_name,
                department,
                position_id
            FROM em_employees
            WHERE id = :employee_id
            LIMIT 1
        ";

            $stmt = $this->conn->prepare($reporterSql);

            $stmt->execute([
                ':employee_id' => $data['reporter_employee_id']
            ]);

            $reporter = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reporter) {
                throw new Exception('Reporter employee not found.');
            }


            /*
            |--------------------------------------------------------------------------
            | Get Respondent
            |--------------------------------------------------------------------------
            */
            $respondentSql = "
            SELECT
                id,
                first_name,
                last_name,
                department,
                position_id
            FROM em_employees
            WHERE id = :employee_id
            LIMIT 1
        ";

            $stmt = $this->conn->prepare($respondentSql);

            $stmt->execute([
                ':employee_id' => $data['respondent_employee_id']
            ]);

            $respondent = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$respondent) {
                throw new Exception('Respondent employee not found.');
            }


            /*
            |--------------------------------------------------------------------------
            | Generate Incident ID
            |--------------------------------------------------------------------------
            */
            $year = date('Y');

            $countSql = "
            SELECT COUNT(*)
            FROM {$this->table}
            WHERE YEAR(created_at) = :year
        ";

            $stmt = $this->conn->prepare($countSql);

            $stmt->execute([
                ':year' => $year
            ]);

            $count = (int) $stmt->fetchColumn();

            $incidentId = 'INC-' . $year . '-' .
                str_pad($count + 1, 4, '0', STR_PAD_LEFT);


            /*
            |--------------------------------------------------------------------------
            | Insert Complaint
            |--------------------------------------------------------------------------
            */
            $sql = "
            INSERT INTO {$this->table} (

                incident_id,

                reporter_employee_id,
                reporter_name,
                reporter_department,
                reporter_position,
                reporter_role,
                reporter_type,

                respondent_employee_id,
                respondent_name,
                respondent_department,
                respondent_position,
                respondent_relationship,

                incident_type,
                type,
                severity,

                incident_date,
                incident_time,
                location,
                title,
                description,

                status,
                created_at,
                updated_at

            ) VALUES (

                :incident_id,

                :reporter_employee_id,
                :reporter_name,
                :reporter_department,
                :reporter_position,
                :reporter_role,
                :reporter_type,

                :respondent_employee_id,
                :respondent_name,
                :respondent_department,
                :respondent_position,
                :respondent_relationship,

                :incident_type,
                :type,
                :severity,

                :incident_date,
                :incident_time,
                :location,
                :title,
                :description,

                :status,
                NOW(),
                NOW()
            )
        ";

            $stmt = $this->conn->prepare($sql);

            $success = $stmt->execute([

                ':incident_id' => $incidentId,

                /*
                |--------------------------------------------------------------------------
                | Reporter
                |--------------------------------------------------------------------------
                */
                ':reporter_employee_id' => $reporter['id'],

                ':reporter_name' =>
                    trim(
                        $reporter['first_name'] . ' ' .
                        $reporter['last_name']
                    ),

                ':reporter_department' =>
                    $reporter['department'],

                ':reporter_position' =>
                    $reporter['position_id'],

                ':reporter_role' => 'reporter',

                ':reporter_type' => 'employee',


                /*
                |--------------------------------------------------------------------------
                | Respondent
                |--------------------------------------------------------------------------
                */
                ':respondent_employee_id' =>
                    $respondent['id'],

                ':respondent_name' =>
                    trim(
                        $respondent['first_name'] . ' ' .
                        $respondent['last_name']
                    ),

                ':respondent_department' =>
                    $respondent['department'],

                ':respondent_position' =>
                    $respondent['position_id'],

                ':respondent_relationship' =>
                    $data['respondent_relationship'],


                /*
                |--------------------------------------------------------------------------
                | Incident
                |--------------------------------------------------------------------------
                */
                ':incident_type' =>
                    $data['incident_type'],

                ':type' =>
                    $data['type'] ?? 'other',

                ':severity' =>
                    $data['severity'] ?? 'medium',

                ':incident_date' =>
                    $data['incident_date'],

                ':incident_time' =>
                    $data['incident_time'],

                ':location' =>
                    $data['location'],

                ':title' =>
                    $data['title'],

                ':description' =>
                    $data['description'],

                ':status' =>
                    'submitted'
            ]);


            if (!$success) {

                $error = $stmt->errorInfo();

                throw new Exception(
                    $error[2] ?? 'Failed to insert complaint.'
                );
            }


            $this->conn->commit();

            return true;

        } catch (\Throwable $e) {

            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $e;
        }
    }
}