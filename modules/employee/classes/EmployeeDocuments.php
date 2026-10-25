<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * EmployeeDocuments
 *
 * Wraps `em_documents` (uploaded files) and `em_requirements`
 * (document checklist), both FK employee_id -> em_employees.employee_id
 * ON DELETE CASCADE. em_requirements.document_id FK ->
 * em_documents.document_id ON DELETE SET NULL.
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

    // ---------- em_documents ----------

    public function getDocuments($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM em_documents WHERE employee_id = :employee_id ORDER BY created_at DESC"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllDocuments(array $filters = [])
    {
        $sql = "SELECT
                    d.*,
                    e.employee_code,
                    e.first_name,
                    e.last_name
                FROM em_documents AS d
                INNER JOIN em_employees AS e ON e.employee_id = d.employee_id
                WHERE 1 = 1";
        $params = [];

        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $search = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim((string) $filters['search']));
            $like = '%' . $search . '%';
            $sql .= " AND (
                        e.first_name LIKE :search_first_name ESCAPE '!'
                        OR e.last_name LIKE :search_last_name ESCAPE '!'
                        OR CONCAT_WS(' ', e.first_name, e.last_name) LIKE :search_full_name ESCAPE '!'
                        OR e.employee_code LIKE :search_employee_code ESCAPE '!'
                        OR d.document_name LIKE :search_document_name ESCAPE '!'
                        OR d.document_type LIKE :search_document_type ESCAPE '!'
                        OR d.file_name LIKE :search_file_name ESCAPE '!'
                    )";
            $params[':search_first_name'] = $like;
            $params[':search_last_name'] = $like;
            $params[':search_full_name'] = $like;
            $params[':search_employee_code'] = $like;
            $params[':search_document_name'] = $like;
            $params[':search_document_type'] = $like;
            $params[':search_file_name'] = $like;
        }

        foreach ([
            'document_type' => 'd.document_type',
            'category' => 'd.category',
            'verification_status' => 'd.verification_status',
        ] as $filter => $column) {
            if (isset($filters[$filter]) && $filters[$filter] !== '') {
                $sql .= " AND $column = :$filter";
                $params[":$filter"] = $filters[$filter];
            }
        }

        $sql .= " ORDER BY d.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDocumentById($documentId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM em_documents WHERE document_id = :id LIMIT 1"
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
            "INSERT INTO em_documents
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
            "DELETE FROM em_documents WHERE document_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([':id' => $documentId, ':employee_id' => $employeeId]);
    }

    // ---------- em_requirements ----------

    public function getRequirements($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT r.*, d.file_name, d.file_path
             FROM em_requirements r
             LEFT JOIN em_documents d ON r.document_id = d.document_id
             WHERE r.employee_id = :employee_id
             ORDER BY r.requirement_id"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRequirement($employeeId, array $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO em_requirements
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
            "UPDATE em_requirements
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
            "DELETE FROM em_requirements WHERE requirement_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([':id' => $requirementId, ':employee_id' => $employeeId]);
    }

    /**
     * Counts of requirement statuses across all employees (for dashboard).
     */
    public function getRequirementStatusCounts()
    {
        $stmt = $this->conn->query(
            "SELECT status, COUNT(*) AS cnt FROM em_requirements GROUP BY status"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
