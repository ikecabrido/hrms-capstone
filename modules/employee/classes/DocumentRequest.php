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
     * first. Optional filters: request_status, archived (0/1).
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

        $sql .= " ORDER BY dr.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
