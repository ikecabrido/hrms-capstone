<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * EmployeeDocuments
 *
 * Wraps `employee_documents` (uploaded files) and `employee_requirements`
 * (document checklist), both FK employee_id -> em_employees.employee_id
 * ON DELETE CASCADE. employee_requirements.document_id FK ->
 * employee_documents.document_id ON DELETE SET NULL.
 *
 * Uploaded files are stored on disk under /assets/documents/YYYY/MM/,
 * matching the path convention already present in the current data
 * (e.g. '../../assets/documents/2026/08/<hash>_<filename>').
 */
class EmployeeDocuments
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

    // ---------- employee_documents ----------

    public function getDocuments($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM employee_documents WHERE employee_id = :employee_id ORDER BY created_at DESC"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDocumentById($documentId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM employee_documents WHERE document_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $documentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Record a document upload. $fileMeta expects: document_name, document_type,
     * file_path, file_name, file_size, mime_type, category, expiry_date.
     * Physical file handling (move_uploaded_file) is the controller's job;
     * this class only writes the DB row.
     */
    public function addDocument($employeeId, $uploadedBy, array $fileMeta)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO employee_documents
                (employee_id, document_name, document_type, file_path, file_name,
                 file_size, uploaded_by, mime_type, category, expiry_date)
             VALUES
                (:employee_id, :document_name, :document_type, :file_path, :file_name,
                 :file_size, :uploaded_by, :mime_type, :category, :expiry_date)"
        );
        $stmt->execute([
            ':employee_id'    => $employeeId,
            ':document_name'  => $fileMeta['document_name'] ?? '',
            ':document_type'  => $fileMeta['document_type'] ?? 'Other',
            ':file_path'      => $fileMeta['file_path'] ?? '',
            ':file_name'      => $fileMeta['file_name'] ?? '',
            ':file_size'      => $fileMeta['file_size'] ?? null,
            ':uploaded_by'    => $uploadedBy ?: null,
            ':mime_type'      => $fileMeta['mime_type'] ?? null,
            ':category'       => $fileMeta['category'] ?? 'Other',
            ':expiry_date'    => $fileMeta['expiry_date'] ?: null,
        ]);

        return $this->conn->lastInsertId();
    }

    public function deleteDocument($documentId, $employeeId)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM employee_documents WHERE document_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([':id' => $documentId, ':employee_id' => $employeeId]);
    }

    // ---------- employee_requirements ----------

    public function getRequirements($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT r.*, d.file_name, d.file_path
             FROM employee_requirements r
             LEFT JOIN employee_documents d ON r.document_id = d.document_id
             WHERE r.employee_id = :employee_id
             ORDER BY r.requirement_id"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRequirement($employeeId, array $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO employee_requirements
                (employee_id, document_id, requirement_name, status, remarks, submitted_date, follow_up_date)
             VALUES
                (:employee_id, :document_id, :requirement_name, :status, :remarks, :submitted_date, :follow_up_date)"
        );
        return $stmt->execute([
            ':employee_id'      => $employeeId,
            ':document_id'      => $data['document_id'] ?: null,
            ':requirement_name' => $data['requirement_name'] ?? '',
            ':status'           => $data['status'] ?? 'Missing',
            ':remarks'          => $data['remarks'] ?? null,
            ':submitted_date'   => $data['submitted_date'] ?: null,
            ':follow_up_date'   => $data['follow_up_date'] ?: null,
        ]);
    }

    public function updateRequirementStatus($requirementId, $employeeId, array $data)
    {
        $stmt = $this->conn->prepare(
            "UPDATE employee_requirements
             SET status = :status,
                 remarks = :remarks,
                 submitted_date = :submitted_date,
                 follow_up_date = :follow_up_date,
                 document_id = :document_id
             WHERE requirement_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([
            ':status'         => $data['status'] ?? 'Missing',
            ':remarks'        => $data['remarks'] ?? null,
            ':submitted_date' => $data['submitted_date'] ?: null,
            ':follow_up_date' => $data['follow_up_date'] ?: null,
            ':document_id'    => $data['document_id'] ?: null,
            ':id'             => $requirementId,
            ':employee_id'    => $employeeId,
        ]);
    }

    public function deleteRequirement($requirementId, $employeeId)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM employee_requirements WHERE requirement_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([':id' => $requirementId, ':employee_id' => $employeeId]);
    }

    /**
     * Counts of requirement statuses across all employees (for dashboard).
     */
    public function getRequirementStatusCounts()
    {
        $stmt = $this->conn->query(
            "SELECT status, COUNT(*) AS cnt FROM employee_requirements GROUP BY status"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
