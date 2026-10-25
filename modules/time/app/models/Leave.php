<?php
require_once __DIR__ . '/../core/TimeDatabase.php';

class Leave
{
    private $conn;
    private $table = 'ta_leave_requests';

    public function __construct()
    {
        $database = TimeDatabase::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Create a new leave request
     */
    public function createRequest($data)
    {
        if (empty($data['employee_id']) || empty($data['leave_type_id']) || empty($data['start_date']) || empty($data['end_date'])) {
            return false;
        }

        $query = "INSERT INTO `ta_leave_requests` 
                  (employee_id, leave_type_id, start_date, end_date, details, supporting_document, document_uploaded_at, status)
                  VALUES (:employee_id, :leave_type_id, :start_date, :end_date, :details, :supporting_document, :document_uploaded_at, 'Pending')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $data['employee_id'], PDO::PARAM_STR);
        $stmt->bindParam(':leave_type_id', $data['leave_type_id'], PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $data['start_date']);
        $stmt->bindParam(':end_date', $data['end_date']);

        $details = $data['details'] ?? $data['reason'] ?? '';
        $stmt->bindParam(':details', $details);
        // Optional supporting document path and upload timestamp
        $supportingDocument = $data['supporting_document'] ?? null;
        $documentUploadedAt = $data['document_uploaded_at'] ?? null;
        $stmt->bindParam(':supporting_document', $supportingDocument);
        $stmt->bindParam(':document_uploaded_at', $documentUploadedAt);

        return $stmt->execute();
    }

    /**
     * Check if the employee already has an overlapping leave request in the same period.
     */
    public function hasOverlappingRequest($employee_id, $leave_type_id, $start_date, $end_date, $exclude_request_id = null)
    {
        $query = "SELECT id
                  FROM ta_leave_requests
                  WHERE employee_id = :employee_id
                    AND leave_type_id = :leave_type_id
                    AND status NOT IN ('REJECTED', 'CANCELLED', 'CANCELED')
                    AND (
                        (start_date BETWEEN :start_date AND :end_date)
                        OR (end_date BETWEEN :start_date AND :end_date)
                        OR (:start_date BETWEEN start_date AND end_date)
                        OR (:end_date BETWEEN start_date AND end_date)
                    )";

        if ($exclude_request_id) {
            $query .= " AND id != :exclude_request_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_STR);
        $stmt->bindParam(':leave_type_id', $leave_type_id, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);

        if ($exclude_request_id) {
            $stmt->bindParam(':exclude_request_id', $exclude_request_id, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    /**
     * Get all pending requests for a department head
     */
    public function getPendingByDepartmentHead($deptHeadUserId, $limit = null, $offset = null)
    {
         $query = "SELECT lr.id, lr.employee_id, lr.leave_type_id, lr.start_date, lr.end_date, lr.details, lr.status, lr.date_submitted,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                    COALESCE(d.department_name, '') AS department,
                    lt.leave_type_name,
                    DATEDIFF(lr.end_date, lr.start_date) + 1 AS total_days
                FROM ta_leave_requests lr
                INNER JOIN em_employees e ON lr.employee_id = e.employee_id
                LEFT JOIN em_departments d ON e.department_id = d.department_id
                INNER JOIN ta_leave_types lt ON lr.leave_type_id = lt.leave_type_id
                INNER JOIN department_heads dh ON dh.department = COALESCE(d.department_name, e.department)
                WHERE dh.user_id = :user_id AND lr.status = 'Pending'
                ORDER BY lr.date_submitted DESC";

        if ($limit !== null) {
            $query .= " LIMIT :limit";
            if ($offset !== null) {
                $query .= " OFFSET :offset";
            }
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $deptHeadUserId, PDO::PARAM_INT);
        if ($limit !== null) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            if ($offset !== null) {
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            }
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count pending requests for a department head
     */
    public function countPendingByDepartmentHead($deptHeadUserId)
    {
        $query = "SELECT COUNT(*) AS total
                  FROM ta_leave_requests lr
                  INNER JOIN em_employees e ON lr.employee_id = e.employee_id
                  LEFT JOIN em_departments d ON e.department_id = d.department_id
                  INNER JOIN department_heads dh ON dh.department = COALESCE(d.department_name, e.department)
                  WHERE dh.user_id = :user_id AND lr.status = 'Pending'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $deptHeadUserId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get pending and head-approved requests for HR admin
     */
    public function getForHRApproval($limit = null, $offset = null)
    {
         $query = "SELECT lr.id,
                    lr.employee_id,
                    lr.leave_type_id,
                    lr.start_date,
                    lr.end_date,
                    lr.details,
                    lr.status,
                    lr.date_submitted,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                    COALESCE(d.department_name, '') AS department,
                    lt.leave_type_name,
                    DATEDIFF(lr.end_date, lr.start_date) + 1 AS total_days
                FROM ta_leave_requests lr
                INNER JOIN em_employees e ON lr.employee_id = e.employee_id
                LEFT JOIN em_departments d ON e.department_id = d.department_id
                INNER JOIN ta_leave_types lt ON lr.leave_type_id = lt.leave_type_id
                WHERE lr.status = 'Pending'
                ORDER BY lr.date_submitted DESC";

        if ($limit !== null) {
            $query .= " LIMIT :limit";
            if ($offset !== null) {
                $query .= " OFFSET :offset";
            }
        }

        $stmt = $this->conn->prepare($query);
        if ($limit !== null) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            if ($offset !== null) {
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            }
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count pending and head-approved requests for HR admin
     */
    public function countForHRApproval()
    {
        $query = "SELECT COUNT(*) AS total
              FROM ta_leave_requests lr
              WHERE lr.status = 'Pending'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Update leave request status with approver details
     */
    public function updateStatus($leave_request_id, $status, $user_id, $remarks = '')
    {
        try {
            $updateQuery = "UPDATE ta_leave_requests 
                           SET status = :status,
                               reject_reason = :remarks,
                               updated_at = NOW()
                           WHERE id = :id";

            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':status', $status);
            $updateStmt->bindParam(':remarks', $remarks);
            $updateStmt->bindParam(':id', $leave_request_id, PDO::PARAM_INT);

            if (!$updateStmt->execute()) {
                error_log('Failed to update ta_leave_requests for ID: ' . $leave_request_id);
                return false;
            }

            error_log('Successfully updated ta_leave_requests. Status: ' . $status);
            return true;
        } catch (Exception $e) {
            error_log('Leave updateStatus exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get leave request by ID
     */
    public function getById($leave_request_id)
    {
        $query = "SELECT * FROM ta_leave_requests WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $leave_request_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get leave request with leave type name by ID
     */
    public function getRequestWithType($leave_request_id)
    {
        $query = "SELECT lr.*, lt.leave_type_name FROM ta_leave_requests lr JOIN ta_leave_types lt ON lr.leave_type_id = lt.leave_type_id WHERE lr.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $leave_request_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if employee has sufficient leave balance
     */
    public function checkLeaveBalance($employee_id, $leave_type_id, $requested_days)
    {
        $query = "SELECT COALESCE(remaining_balance, remaining_days, 0) AS remaining_balance,
                         COALESCE(opening_balance, 0) AS opening_balance
                  FROM ta_leave_balances 
                  WHERE employee_id = :employee_id 
                  AND leave_type_id = :leave_type_id 
                  AND year = YEAR(CURDATE())";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_STR);
        $stmt->bindParam(':leave_type_id', $leave_type_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return ['status' => false, 'message' => 'Leave balance record not found'];
        }

        $remaining_balance = (float)($result['remaining_balance'] ?? 0);
        if ($remaining_balance < (float)$requested_days) {
            return [
                'status' => false,
                'message' => 'Insufficient leave balance. Available: ' . number_format($remaining_balance, 2) . ' days'
            ];
        }

        return ['status' => true, 'remaining_balance' => $remaining_balance];
    }

    /**
     * Deduct days from leave balance after approval
     */
    public function deductLeaveBalance($employee_id, $leave_type_id, $days_to_deduct)
    {
        $query = "UPDATE ta_leave_balances 
                  SET used_balance = COALESCE(used_balance, 0) + :days,
                      remaining_balance = COALESCE(remaining_balance, opening_balance, 0) - :days,
                      updated_at = NOW()
                  WHERE employee_id = :employee_id 
                  AND leave_type_id = :leave_type_id 
                  AND year = YEAR(CURDATE())
                  AND COALESCE(remaining_balance, 0) >= :days";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_STR);
        $stmt->bindParam(':leave_type_id', $leave_type_id, PDO::PARAM_INT);
        $stmt->bindParam(':days', $days_to_deduct, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->rowCount() > 0;
    }

    /**
     * Get leave balance details for an employee
     */
    public function getLeaveBalance($employee_id, $leave_type_id = null)
    {
        $query = "SELECT lb.*, lt.leave_type_name, lt.days_per_year, lt.accrual_frequency,
                         COALESCE(lb.opening_balance, lt.days_per_year) AS total_days,
                         COALESCE(lb.used_balance, 0) AS used_days,
                         COALESCE(lb.remaining_balance, lt.days_per_year) AS remaining_days
                  FROM ta_leave_balances lb
                  JOIN ta_leave_types lt ON lb.leave_type_id = lt.leave_type_id
                  WHERE lb.employee_id = :employee_id 
                  AND lb.year = YEAR(CURDATE())";

        if ($leave_type_id) {
            $query .= " AND lb.leave_type_id = :leave_type_id";
        }

        $query .= " ORDER BY lt.leave_type_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_STR);
        if ($leave_type_id) {
            $stmt->bindParam(':leave_type_id', $leave_type_id, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $leave_type_id ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all leave type definitions.
     */
    public function getLeaveTypes()
    {
        $query = "SELECT * FROM ta_leave_types ORDER BY leave_type_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get leave history including optional filters. If $employee_id is null, return global history.
     */
    public function getHistory($employee_id = null, $limit = 50, $offset = 0, array $filters = [])
    {
        $query = "SELECT lr.id, lr.employee_id, lr.leave_type_id, lr.start_date, lr.end_date, lr.details, lr.reason, lr.documents, lr.status,
                         lr.date_submitted, lr.reject_reason, lr.supporting_document, lr.updated_at,
                         lt.leave_type_name, CONCAT(COALESCE(e.first_name,''),' ',COALESCE(e.last_name,'')) AS full_name,
                         DATEDIFF(lr.end_date, lr.start_date) + 1 AS total_days
                  FROM ta_leave_requests lr
                  INNER JOIN ta_leave_types lt ON lr.leave_type_id = lt.leave_type_id
                  LEFT JOIN em_employees e ON lr.employee_id = e.employee_id
                  WHERE 1 = 1";

        if ($employee_id) {
            $query .= " AND lr.employee_id = :employee_id";
        }

        $fromDate = trim((string)($filters['from_date'] ?? $filters['start_date'] ?? ''));
        $toDate = trim((string)($filters['to_date'] ?? $filters['end_date'] ?? ''));
        $leaveTypeId = isset($filters['leave_type_id']) ? (int)$filters['leave_type_id'] : 0;
        $statusFilter = trim((string)($filters['status'] ?? ''));
        $searchTerm = trim((string)($filters['search'] ?? ''));

        $statusValue = '';
        if ($statusFilter !== '') {
            $normalizedStatus = strtolower($statusFilter);
            if (in_array($normalizedStatus, ['approved', 'rejected'], true)) {
                $statusValue = ucfirst($normalizedStatus);
            }
        }

        if ($fromDate !== '' || $toDate !== '') {
            if ($fromDate !== '') {
                $query .= " AND lr.end_date >= :from_date";
            }
            if ($toDate !== '') {
                $query .= " AND lr.start_date <= :to_date";
            }
        }

        if ($leaveTypeId > 0) {
            $query .= " AND lr.leave_type_id = :leave_type_id";
        }
        if ($statusValue !== '') {
            $query .= " AND lr.status = :status";
        } else {
            $query .= " AND lr.status IN ('Approved', 'Rejected')";
        }
        if ($searchTerm !== '') {
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);
            $query .= " AND (
                CONCAT(COALESCE(e.first_name,''), ' ', COALESCE(e.last_name,'')) LIKE :search ESCAPE '\\\\'
                OR lt.leave_type_name LIKE :search ESCAPE '\\\\'
                OR lr.details LIKE :search ESCAPE '\\\\'
                OR lr.reject_reason LIKE :search ESCAPE '\\\\'
                OR lr.employee_id LIKE :search ESCAPE '\\\\'
            )";
        }

        $query .= " ORDER BY lr.updated_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        if ($employee_id) {
            $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        }
        if ($fromDate !== '') {
            $stmt->bindParam(':from_date', $fromDate);
        }
        if ($toDate !== '') {
            $stmt->bindParam(':to_date', $toDate);
        }
        if ($leaveTypeId > 0) {
            $stmt->bindParam(':leave_type_id', $leaveTypeId, PDO::PARAM_INT);
        }
        if ($statusValue !== '') {
            $stmt->bindParam(':status', $statusValue);
        }
        if ($searchTerm !== '') {
            $searchTerm = '%' . $escapedSearch . '%';
            $stmt->bindParam(':search', $searchTerm);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count leave history rows using the same filters as getHistory.
     */
    public function countHistory($employee_id = null, array $filters = [])
    {
        $query = "SELECT COUNT(*) AS total
                  FROM ta_leave_requests lr
                  INNER JOIN ta_leave_types lt ON lr.leave_type_id = lt.leave_type_id
                  LEFT JOIN em_employees e ON lr.employee_id = e.employee_id
                  WHERE 1 = 1";

        if ($employee_id) {
            $query .= " AND lr.employee_id = :employee_id";
        }

        $fromDate = trim((string)($filters['from_date'] ?? $filters['start_date'] ?? ''));
        $toDate = trim((string)($filters['to_date'] ?? $filters['end_date'] ?? ''));
        $leaveTypeId = isset($filters['leave_type_id']) ? (int)$filters['leave_type_id'] : 0;
        $statusFilter = trim((string)($filters['status'] ?? ''));
        $searchTerm = trim((string)($filters['search'] ?? ''));

        $statusValue = '';
        if ($statusFilter !== '') {
            $normalizedStatus = strtolower($statusFilter);
            if (in_array($normalizedStatus, ['approved', 'rejected'], true)) {
                $statusValue = ucfirst($normalizedStatus);
            }
        }

        if ($fromDate !== '' || $toDate !== '') {
            if ($fromDate !== '') {
                $query .= " AND lr.end_date >= :from_date";
            }
            if ($toDate !== '') {
                $query .= " AND lr.start_date <= :to_date";
            }
        }

        if ($leaveTypeId > 0) {
            $query .= " AND lr.leave_type_id = :leave_type_id";
        }
        if ($statusValue !== '') {
            $query .= " AND lr.status = :status";
        } else {
            $query .= " AND lr.status IN ('Approved', 'Rejected')";
        }
        if ($searchTerm !== '') {
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);
            $query .= " AND (
                CONCAT(COALESCE(e.first_name,''), ' ', COALESCE(e.last_name,'')) LIKE :search ESCAPE '\\\\'
                OR lt.leave_type_name LIKE :search ESCAPE '\\\\'
                OR lr.details LIKE :search ESCAPE '\\\\'
                OR lr.reject_reason LIKE :search ESCAPE '\\\\'
                OR lr.employee_id LIKE :search ESCAPE '\\\\'
            )";
        }

        $stmt = $this->conn->prepare($query);
        if ($employee_id) {
            $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        }
        if ($fromDate !== '') {
            $stmt->bindParam(':from_date', $fromDate);
        }
        if ($toDate !== '') {
            $stmt->bindParam(':to_date', $toDate);
        }
        if ($leaveTypeId > 0) {
            $stmt->bindParam(':leave_type_id', $leaveTypeId, PDO::PARAM_INT);
        }
        if ($statusValue !== '') {
            $stmt->bindParam(':status', $statusValue);
        }
        if ($searchTerm !== '') {
            $searchTerm = '%' . $escapedSearch . '%';
            $stmt->bindParam(':search', $searchTerm);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Provision default leave balances for a single employee.
     */
    public function provisionLeaveBalancesForEmployee($employee_id, $year = null)
    {
        $year = (int)($year ?? date('Y'));

        // Only provision leave types that are explicitly yearly —
        // this prevents double-crediting when monthly accruals run.
        $query = "SELECT * FROM ta_leave_types WHERE accrual_frequency = 'yearly' ORDER BY leave_type_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $leaveTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($leaveTypes)) {
            return false;
        }

        $insertQuery = "INSERT INTO ta_leave_balances
                        (employee_id, leave_type_id, year, opening_balance, used_balance, remaining_balance, notes, created_at, updated_at)
                        VALUES
                        (:employee_id, :leave_type_id, :year, :opening_balance, 0, :remaining_balance, :notes, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE leave_balance_id = leave_balance_id";
        $insertStmt = $this->conn->prepare($insertQuery);

        foreach ($leaveTypes as $type) {
            $leaveTypeName = trim($type['leave_type_name'] ?? '');
            $openingBalance = (float)($type['days_per_year'] ?? 0);
            $notes = trim($type['description'] ?: $leaveTypeName . ' allocation');

            if ($openingBalance <= 0) {
                continue;
            }

            $insertStmt->execute([
                ':employee_id' => $employee_id,
                ':leave_type_id' => $type['leave_type_id'],
                ':year' => $year,
                ':opening_balance' => $openingBalance,
                ':remaining_balance' => $openingBalance,
                ':notes' => $notes,
            ]);
        }

        return true;
    }

    /**
     * Get active employees that are missing leave balances for the given year.
     * Returns array of ['employee_id' => ..., 'full_name' => ...]
     */
    public function getActiveEmployeesMissingBalances($year = null)
    {
        $year = (int)($year ?? date('Y'));

                // Return employees who are missing balances for any yearly-accrual leave type.
                // This ensures the provisioning flow only seeds yearly types and
                // runs even if monthly balances already exist for the employee.
                $query = "SELECT DISTINCT e.employee_id, CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name
                                    FROM em_employees e
                                    JOIN ta_leave_types lt ON lt.accrual_frequency = 'yearly'
                                    LEFT JOIN ta_leave_balances lb ON e.employee_id = lb.employee_id
                                        AND lb.year = :year AND lb.leave_type_id = lt.leave_type_id
                                    WHERE LOWER(COALESCE(e.employment_status, '')) = 'active'
                                        AND lb.leave_type_id IS NULL";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':year', $year, PDO::PARAM_INT);
                $stmt->execute();

                return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Provision default leave balances for all active employees.
     */
    public function provisionLeaveBalancesForActiveEmployees($year = null)
    {
        $year = (int)($year ?? date('Y'));

        // Use case-insensitive match for employment_status to catch variations like 'Active', 'ACTIVE', 'active'
        $query = "SELECT employee_id FROM em_employees WHERE LOWER(COALESCE(employment_status, '')) = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($employees as $employee) {
            if (!empty($employee['employee_id']) && $this->provisionLeaveBalancesForEmployee($employee['employee_id'], $year)) {
                $count++;
            }
        }

        return $count;
    }
}
