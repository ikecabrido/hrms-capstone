<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * ContractRenewal
 *
 * Wraps the EXISTING `em_contract_renewals` table (created manually,
 * not by this codebase — see hrms.sql). This class does not create,
 * rename, or alter that table in any way.
 *
 * Verified columns (from hrms.sql):
 *   contract_renewal_id  int PK, auto_increment
 *   employee_id           int, FK -> em_employees.employee_id (ON DELETE/UPDATE CASCADE)
 *   contract_start_date   date, NOT NULL
 *   contract_end_date     date, NOT NULL
 *   employment_type       varchar(100), nullable
 *   salary                decimal(12,2), nullable
 *   renewal_status         enum('Active','Expired','Cancelled'), default 'Active'
 *   remarks                text, nullable
 *   created_at             timestamp
 *   updated_at             timestamp
 *
 * "Current contract" is determined by renewal_status = 'Active', not by
 * the highest contract_renewal_id — an employee could theoretically have
 * their most recent row be 'Cancelled', so the latest ID is not a safe
 * signal. If more than one 'Active' row exists for an employee (data
 * inconsistency), the most recent by contract_start_date/created_at wins.
 */
class ContractRenewal
{
    private $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    /**
     * The employee's current (Active) contract, or null if none exists yet.
     */
    public function getCurrentContract($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM em_contract_renewals
             WHERE employee_id = :employee_id AND renewal_status = 'Active'
             ORDER BY contract_start_date DESC, created_at DESC
             LIMIT 1"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Full contract history for the employee, most recent first.
     */
    public function getRenewalHistory($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM em_contract_renewals
             WHERE employee_id = :employee_id
             ORDER BY contract_start_date DESC, created_at DESC"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new renewal record. The previous Active record (if any) is
     * marked Expired — never overwritten or deleted, only its status column
     * is updated — so both rows remain in history. Runs inside a transaction
     * so a failure never leaves two Active rows or an orphaned Expired flip.
     *
     * Returns the new contract_renewal_id.
     */
    public function createRenewal($employeeId, array $data)
    {
        $this->conn->beginTransaction();
        try {
            $expire = $this->conn->prepare(
                "UPDATE em_contract_renewals
                 SET renewal_status = 'Expired'
                 WHERE employee_id = :employee_id AND renewal_status = 'Active'"
            );
            $expire->execute([':employee_id' => $employeeId]);

            $insert = $this->conn->prepare(
                "INSERT INTO em_contract_renewals
                    (employee_id, contract_start_date, contract_end_date,
                     employment_type, salary, renewal_status, remarks)
                 VALUES
                    (:employee_id, :contract_start_date, :contract_end_date,
                     :employment_type, :salary, 'Active', :remarks)"
            );
            $insert->execute([
                ':employee_id'         => $employeeId,
                ':contract_start_date' => $data['contract_start_date'],
                ':contract_end_date'   => $data['contract_end_date'],
                ':employment_type'     => $data['employment_type'] ?: null,
                ':salary'              => $data['salary'] !== '' && $data['salary'] !== null ? $data['salary'] : null,
                ':remarks'             => $data['remarks'] ?: null,
            ]);

            $newId = $this->conn->lastInsertId();
            $this->conn->commit();
            return $newId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
