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
                  (employee_id, leave_type_id, start_date, end_date, details, status)
                  VALUES (:employee_id, :leave_type_id, :start_date, :end_date, :details, 'PENDING')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $data['employee_id'], PDO::PARAM_STR);
        $stmt->bindParam(':leave_type_id', $data['leave_type_id'], PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $data['start_date']);
        $stmt->bindParam(':end_date', $data['end_date']);

        $details = $data['details'] ?? $data['reason'] ?? '';
        $stmt->bindParam(':details', $details);

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
                         CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name, e.department, lt.leave_type_name,
                         DATEDIFF(lr.end_date, lr.start_date) + 1 AS total_days
                  FROM ta_leave_requests lr
                  INNER JOIN em_employees e ON lr.employee_id = e.employee_id
                  INNER JOIN ta_leave_types lt ON lr.leave_type_id = lt.leave_type_id
                  INNER JOIN department_heads dh ON dh.department = e.department
                  WHERE dh.user_id = :user_id AND lr.status IN ('Pending', 'PENDING')
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
                  INNER JOIN department_heads dh ON dh.department = e.department
                  WHERE dh.user_id = :user_id AND lr.status IN ('Pending', 'PENDING')";

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
                         e.department, 
                         lt.leave_type_name,
                         DATEDIFF(lr.end_date, lr.start_date) + 1 AS total_days
                  FROM ta_leave_requests lr
                  INNER JOIN em_employees e ON lr.employee_id = e.employee_id
                  INNER JOIN ta_leave_types lt ON lr.leave_type_id = lt.leave_type_id
                  WHERE lr.status IN ('Pending', 'PENDING', 'APPROVED_BY_HEAD')
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
                  WHERE lr.status IN ('Pending', 'PENDING', 'APPROVED_BY_HEAD')";

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
            // Update status in ta_leave_requests
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

            // If approved, transfer to lc_leave_requests for Legal & Compliance
            if ($status === 'Approved' || $status === 'APPROVED_BY_HR') {
                error_log('Preparing to transfer approved leave to lc_leave_requests for ID: ' . $leave_request_id);
                
                // Get the leave request details with leave type name
                $leaveQuery = "SELECT lr.id, lr.employee_id, lr.leave_type_id, lr.start_date, lr.end_date, lr.details,
                                      lt.leave_type_name
                               FROM ta_leave_requests lr
                               LEFT JOIN ta_leave_types lt ON lr.leave_type_id = lt.leave_type_id
                               WHERE lr.id = :id";
                $leaveStmt = $this->conn->prepare($leaveQuery);
                $leaveStmt->bindParam(':id', $leave_request_id, PDO::PARAM_INT);
                
                if (!$leaveStmt->execute()) {
                    error_log('Failed to fetch leave request details. Error: ' . implode(' ', $leaveStmt->errorInfo()));
                    return false;
                }
                
                $leaveRequest = $leaveStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$leaveRequest) {
                    error_log('Leave request not found: ' . $leave_request_id);
                    return false;
                }

                error_log('Found leave request: ' . json_encode($leaveRequest));

                // Calculate total days using SQL
                $daysQuery = "SELECT DATEDIFF(:end_date, :start_date) + 1 AS days";
                $daysStmt = $this->conn->prepare($daysQuery);
                $startDate = $leaveRequest['start_date'];
                $endDate = $leaveRequest['end_date'];
                $daysStmt->bindParam(':start_date', $startDate);
                $daysStmt->bindParam(':end_date', $endDate);
                
                if (!$daysStmt->execute()) {
                    error_log('Failed to calculate days');
                    return false;
                }
                
                $daysResult = $daysStmt->fetch(PDO::FETCH_ASSOC);
                $totalDays = $daysResult['days'] ?? 1;
                
                error_log('Calculated total days: ' . $totalDays);

                // Transfer to lc_leave_requests
                $lcInsertQuery = "INSERT INTO lc_leave_requests 
                                 (employee_id, leave_type, start_date, end_date, total_days, reason, status, checked_by, checked_at, hr_comments)
                                 VALUES 
                                 (:employee_id, :leave_type, :start_date, :end_date, :total_days, :reason, 'pending', :checked_by, NOW(), :hr_comments)";

                $lcStmt = $this->conn->prepare($lcInsertQuery);
                
                // Get variables to bind - use leave_type_name if available, otherwise use details as fallback
                $empId = $leaveRequest['employee_id'];
                $leaveType = $leaveRequest['leave_type_name'] ?? $leaveRequest['details'] ?? 'Leave';
                $sDate = $leaveRequest['start_date'];
                $eDate = $leaveRequest['end_date'];
                $reason = $leaveRequest['details'] ?? '';
                $checkedBy = $user_id;
                $hrComments = $remarks;
                
                error_log('Transfer data - Leave Type: ' . $leaveType . ', Reason: ' . $reason);
                
                $lcStmt->bindParam(':employee_id', $empId, PDO::PARAM_INT);
                $lcStmt->bindParam(':leave_type', $leaveType);
                $lcStmt->bindParam(':start_date', $sDate);
                $lcStmt->bindParam(':end_date', $eDate);
                $lcStmt->bindParam(':total_days', $totalDays);
                $lcStmt->bindParam(':reason', $reason);
                $lcStmt->bindParam(':checked_by', $checkedBy, PDO::PARAM_INT);
                $lcStmt->bindParam(':hr_comments', $hrComments);

                if (!$lcStmt->execute()) {
                    error_log('Failed to insert into lc_leave_requests. Error: ' . implode(' ', $lcStmt->errorInfo()));
                    return false;
                }
                
                error_log('Successfully inserted into lc_leave_requests');
                
                // Get the newly inserted lc_leave_requests ID
                $lcLeaveId = $this->conn->lastInsertId();
                error_log('Inserted lc_leave_requests with ID: ' . $lcLeaveId);
                
                // Transfer documents to lc_leave_documents if they exist
                if (!empty($leaveRequest['documents'])) {
                    error_log('Found documents to transfer: ' . $leaveRequest['documents']);
                    $documents = json_decode($leaveRequest['documents'], true);
                    
                    if (is_array($documents) && count($documents) > 0) {
                        error_log('Parsed ' . count($documents) . ' documents for transfer');
                        
                        foreach ($documents as $docPath) {
                            // Extract document type/name from path
                            $docName = basename($docPath);
                            
                            // Normalize the path - if it starts with 'uploads/', prepend 'employee_portal/public/'
                            // This ensures the path is consistent across all modules
                            if (strpos($docPath, 'uploads/') === 0) {
                                // Path is in the format 'uploads/leave_documents/file.ext'
                                // Convert to 'employee_portal/public/uploads/leave_documents/file.ext'
                                $normalizedPath = 'employee_portal/public/' . $docPath;
                            } else {
                                // Path is already in full format or custom format, use as is
                                $normalizedPath = $docPath;
                            }
                            
                            error_log('Document path conversion: ' . $docPath . ' -> ' . $normalizedPath);
                            
                            $docQuery = "INSERT INTO lc_leave_documents 
                                        (leave_id, document_type, file_path)
                                        VALUES 
                                        (:leave_id, :document_type, :file_path)";
                            
                            $docStmt = $this->conn->prepare($docQuery);
                            $docStmt->bindParam(':leave_id', $lcLeaveId, PDO::PARAM_INT);
                            $docStmt->bindParam(':document_type', $docName);
                            $docStmt->bindParam(':file_path', $normalizedPath);
                            
                            if (!$docStmt->execute()) {
                                error_log('Failed to transfer document: ' . $docPath . '. Error: ' . implode(' ', $docStmt->errorInfo()));
                                // Continue with other documents even if one fails
                            } else {
                                error_log('Successfully transferred document: ' . $docPath . ' as ' . $normalizedPath);
                            }
                        }
                    }
                } else {
                    error_log('No documents to transfer for leave ID: ' . $leave_request_id);
                }
            }

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
                  AND year = YEAR(CURDATE())";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_STR);
        $stmt->bindParam(':leave_type_id', $leave_type_id, PDO::PARAM_INT);
        $stmt->bindParam(':days', $days_to_deduct, PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Get leave balance details for an employee
     */
    public function getLeaveBalance($employee_id, $leave_type_id = null)
    {
        $query = "SELECT lb.*, lt.leave_type_name, lt.days_per_year,
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
     * Provision default leave balances for a single employee.
     */
    public function provisionLeaveBalancesForEmployee($employee_id, $year = null)
    {
        $year = (int)($year ?? date('Y'));

        $leaveTypes = $this->getLeaveTypes();
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
     * Provision default leave balances for all active employees.
     */
    public function provisionLeaveBalancesForActiveEmployees($year = null)
    {
        $year = (int)($year ?? date('Y'));

        $query = "SELECT employee_id FROM em_employees WHERE employment_status = 'Active'";
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
