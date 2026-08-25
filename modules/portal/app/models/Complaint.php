<?php

namespace App\Models;

use Exception;
use App\Config\Database;
use PDO;

class Complaint
{
    private $conn;
    private string $table = 'lc_incidents';
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
    public function getComplaint(int $reporterEmployeeId): array
    {
        $query = "
        SELECT 
            i.*,

            CONCAT(
                r.first_name, ' ', r.last_name
            ) AS respondent_name

        FROM {$this->table} i

        LEFT JOIN {$this->employeeTable} r
            ON r.employee_id = i.respondent_id

        WHERE i.reporter_id = :reporter_id

        ORDER BY i.created_at DESC
    ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':reporter_id' => $reporterEmployeeId
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
                employee_id,
                first_name,
                last_name
            FROM {$this->employeeTable}
            WHERE employee_id = :employee_id
            LIMIT 1
        ";

            $stmt = $this->conn->prepare($reporterSql);

            $stmt->execute([
                ':employee_id' => $data['reporter_id']
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
                employee_id,
                first_name,
                last_name
            FROM {$this->employeeTable}
            WHERE employee_id = :employee_id
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
                str_pad($count + 1, 3, '0', STR_PAD_LEFT);


            /*
            |--------------------------------------------------------------------------
            | Insert Complaint
            |--------------------------------------------------------------------------
            |
            | Columns here match the actual lc_incidents table.
            |
            */
            $sql = "
            INSERT INTO {$this->table} (

                incident_id,
                type,
                incident_type,
                severity,
                title,
                description,
                incident_date,
                location,

                respondent_id,
                reporter_id,
                reporter_name,

                status,
                current_workflow_step,
                status_changed_at,

                is_confidential,

                nte_deadline,
                explanation_deadline,

                created_by,
                created_at,
                updated_at

            ) VALUES (

                :incident_id,
                :type,
                :incident_type,
                :severity,
                :title,
                :description,
                :incident_date,
                :location,

                :respondent_id,
                :reporter_id,
                :reporter_name,

                :status,
                :current_workflow_step,
                NOW(),

                :is_confidential,

                NULL,
                NULL,

                :created_by,
                NOW(),
                NOW()
            )
        ";

            $stmt = $this->conn->prepare($sql);

            $success = $stmt->execute([

                /*
                |--------------------------------------------------------------------------
                | Incident
                |--------------------------------------------------------------------------
                */
                ':incident_id' => $incidentId,

                ':type' =>
                    $data['type'] ?? null,

                ':incident_type' =>
                    $data['incident_type'] ?? null,

                ':severity' =>
                    $data['severity'] ?? 'medium',

                ':title' =>
                    $data['title'] ?? null,

                ':description' =>
                    $data['description'] ?? null,

                ':incident_date' =>
                    $data['incident_date'] ?? null,

                ':location' =>
                    $data['location'] ?? null,


                /*
                |--------------------------------------------------------------------------
                | Respondent
                |--------------------------------------------------------------------------
                */
                ':respondent_id' =>
                    $respondent['employee_id'],


                /*
                |--------------------------------------------------------------------------
                | Reporter
                |--------------------------------------------------------------------------
                */
                ':reporter_id' =>
                    $reporter['employee_id'],

                ':reporter_name' =>
                    trim(
                        $reporter['first_name'] . ' ' .
                        $reporter['last_name']
                    ),


                /*
                |--------------------------------------------------------------------------
                | Workflow
                |--------------------------------------------------------------------------
                */
                ':status' =>
                    'submitted',

                ':current_workflow_step' =>
                    'Initial Review',


                /*
                |--------------------------------------------------------------------------
                | Confidentiality
                |--------------------------------------------------------------------------
                */
                ':is_confidential' =>
                    !empty($data['is_confidential']) ? 1 : 0,


                /*
                |--------------------------------------------------------------------------
                | Created By
                |--------------------------------------------------------------------------
                */
                ':created_by' =>
                    $data['created_by'] ?? $data['reporter_id']
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