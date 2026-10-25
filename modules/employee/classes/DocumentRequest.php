<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * DocumentRequest (Employee module / HR-Admin management side)
 *
 * Wraps the EXISTING `em_lc_document_requests` table (same table as
 * modules/portal/classes/DocumentRequest.php, which handles the employee
 * self-service side). Column list as given:
 *   request_id, employee_id, rao_hired_id, document_type, request_status,
 *   archived, signature_status, requires_signature, created_at, required_by,
 *   assigned_to, priority, notes, template_code, verified, reminder_sent_at
 *
 * No schema changes — this class only reads/writes the columns above.
 */
class DocumentRequest
{
    private $conn;

    // Whitelisted columns HR/Admin may update. Kept deliberately narrow —
    // any key not in this list is silently ignored by updateRequest().
    private $updatableFields = [
        'request_status',
        'assigned_to',
        'verified',
        'archived',
        'signature_status',
        'notes',
    ];

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
     * All requests, joined to the requesting employee's name, most recent
     * first. Optional filters: request_status, archived (0/1), search.
     */
    public function getAllRequests(array $filters = [])
    {
        $sql = "SELECT
                    dr.*,
                    e.first_name,
                    e.last_name,
                    e.employee_code
                FROM em_lc_document_requests AS dr
                LEFT JOIN em_employees AS e ON e.employee_id = dr.employee_id
                WHERE 1 = 1";
        $params = [];

        if (!empty($filters['request_status'])) {
            $sql .= " AND dr.request_status = :request_status";
            $params[':request_status'] = $filters['request_status'];
        }

        if (isset($filters['archived']) && $filters['archived'] !== '') {
            $sql .= " AND dr.archived = :archived";
            $params[':archived'] = (int) $filters['archived'];
        }

        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $search = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim((string) $filters['search']));
            $like = '%' . $search . '%';
            $sql .= " AND (
                        e.first_name LIKE :search_first_name ESCAPE '!'
                        OR e.last_name LIKE :search_last_name ESCAPE '!'
                        OR CONCAT_WS(' ', e.first_name, e.last_name) LIKE :search_full_name ESCAPE '!'
                        OR e.employee_code LIKE :search_employee_code ESCAPE '!'
                        OR dr.document_type LIKE :search_document_type ESCAPE '!'
                        OR dr.notes LIKE :search_notes ESCAPE '!'
                    )";
            $params[':search_first_name'] = $like;
            $params[':search_last_name'] = $like;
            $params[':search_full_name'] = $like;
            $params[':search_employee_code'] = $like;
            $params[':search_document_type'] = $like;
            $params[':search_notes'] = $like;
        }

        $sql .= " ORDER BY dr.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createRequest(array $data, $assignedTo = null)
    {
        $employeeId = filter_var($data['employee_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$employeeId || $employeeId < 1) {
            throw new InvalidArgumentException('A valid employee is required.');
        }

        $employeeStmt = $this->conn->prepare(
            "SELECT employee_id FROM em_employees WHERE employee_id = :employee_id LIMIT 1"
        );
        $employeeStmt->execute([':employee_id' => $employeeId]);
        if (!$employeeStmt->fetchColumn()) {
            throw new InvalidArgumentException('The selected employee does not exist.');
        }

        $documentType = $data['document_type'] ?? null;
        if (!is_string($documentType) || trim($documentType) === '') {
            throw new InvalidArgumentException('Document type is required.');
        }
        $documentType = trim($documentType);

        $priority = $data['priority'] ?? null;
        $priority = is_string($priority) ? trim($priority) : '';
        if (!in_array($priority, ['Low', 'Medium', 'High'], true)) {
            throw new InvalidArgumentException('Priority must be Low, Medium, or High.');
        }

        $requiredBy = $data['required_by'] ?? null;
        $notes = $data['notes'] ?? '';
        if (($requiredBy !== null && $requiredBy !== '' && !is_string($requiredBy)) || !is_string($notes)) {
            throw new InvalidArgumentException('Invalid request details.');
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO em_lc_document_requests
                (employee_id, document_type, request_status, priority, required_by, notes, requires_signature, assigned_to)
             VALUES
                (:employee_id, :document_type, 'Pending', :priority, :required_by, :notes, :requires_signature, :assigned_to)"
        );
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':document_type' => $documentType,
            ':priority' => $priority,
            ':required_by' => !empty($requiredBy) ? $requiredBy : null,
            ':notes' => trim($notes) ?: null,
            ':requires_signature' => !empty($data['requires_signature']) ? 1 : 0,
            ':assigned_to' => $assignedTo,
        ]);

        return $this->conn->lastInsertId();
    }

    public function getRequestById($requestId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM em_lc_document_requests WHERE request_id = :request_id LIMIT 1"
        );
        $stmt->execute([':request_id' => $requestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Update only whitelisted fields (see $updatableFields). Returns false
     * if nothing valid to update was supplied, true otherwise.
     */
    public function updateRequest($requestId, array $fields)
    {
        if (isset($fields['request_status']) && !in_array(
            $fields['request_status'],
            ['Pending', 'Processing', 'Approved', 'Released', 'Rejected'],
            true
        )) {
            throw new InvalidArgumentException('Invalid document request status.');
        }

        $setClauses = [];
        $params = [':request_id' => $requestId];

        foreach ($fields as $key => $value) {
            if (!in_array($key, $this->updatableFields, true)) {
                continue; // ignore anything not explicitly whitelisted
            }
            $setClauses[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        if (!$setClauses) {
            return false;
        }

        $sql = "UPDATE em_lc_document_requests SET " . implode(', ', $setClauses) . " WHERE request_id = :request_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return true;
    }
}
