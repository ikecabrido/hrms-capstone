<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * EmployeeHistory
 *
 * Wraps `employee_change_history`, the existing audit-log table.
 * FK employee_id -> em_employees.employee_id ON DELETE CASCADE.
 *
 * Columns (verified): change_id, employee_id, change_type, user_id,
 * field_name, old_value, new_value, effective_date, remarks, updated_by,
 * ip_address, change_reason, created_at
 */
class EmployeeHistory
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

    public function getHistoryForEmployee($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM employee_change_history
             WHERE employee_id = :employee_id
             ORDER BY created_at DESC"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recent changes across all employees, joined with employee name
     * (used on the Employee History page's global log view).
     */
    public function getRecentHistory($limit = 50)
    {
        $limit = (int) $limit;
        $stmt = $this->conn->prepare(
            "SELECT h.*, e.first_name, e.last_name, e.employee_code
             FROM employee_change_history h
             LEFT JOIN em_employees e ON h.employee_id = e.employee_id
             ORDER BY h.created_at DESC
             LIMIT $limit"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Log a single field change. Called by the controller whenever
     * Employee::updateEmployee() reports a changed field.
     */
    public function logChange($employeeId, $changeType, $fieldName, $oldValue, $newValue, $updatedBy = null, $userId = null, $remarks = null, $changeReason = null)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO employee_change_history
                (employee_id, change_type, user_id, field_name, old_value, new_value,
                 effective_date, remarks, updated_by, ip_address, change_reason)
             VALUES
                (:employee_id, :change_type, :user_id, :field_name, :old_value, :new_value,
                 CURDATE(), :remarks, :updated_by, :ip_address, :change_reason)"
        );
        return $stmt->execute([
            ':employee_id'   => $employeeId,
            ':change_type'   => $changeType,
            ':user_id'       => $userId ?: null,
            ':field_name'    => $fieldName,
            ':old_value'     => $oldValue,
            ':new_value'     => $newValue,
            ':remarks'       => $remarks,
            ':updated_by'    => $updatedBy,
            ':ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
            ':change_reason' => $changeReason,
        ]);
    }

    /**
     * Log a document upload event (used by EmployeeDocuments flow).
     */
    public function logDocumentUpload($employeeId, $documentLabel, $category, $updatedBy = null, $userId = null)
    {
        return $this->logChange(
            $employeeId,
            'Document Uploaded',
            '',
            'N/A',
            $documentLabel,
            $updatedBy,
            $userId,
            "Uploaded document: $documentLabel ($category)"
        );
    }
}
