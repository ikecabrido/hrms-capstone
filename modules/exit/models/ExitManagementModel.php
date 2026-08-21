<?php

require_once __DIR__ . '/../../../database/db.php';

class ExitManagementModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->ensureTableAutoIncrement('exit_employee_settlements');
        $this->ensureTableAutoIncrement('exit_archive');
        $this->ensureTableAutoIncrement('exit_knowledge_transfer_plans');
        $this->ensureTableAutoIncrement('exit_knowledge_transfer_items');
    }

    /**
     * Ensure a table primary key uses AUTO_INCREMENT and repair any id=0 rows.
     */
    protected function ensureTableAutoIncrement(string $tableName, string $primaryKey = 'id'): void
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$tableName} LIKE ?");
            $stmt->execute([$primaryKey]);
            $column = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$column) {
                return;
            }

            // Repair any existing rows that were created with a zero primary key.
            $rows = $this->db->query("SELECT {$primaryKey} FROM {$tableName} WHERE {$primaryKey} = 0")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $maxId = (int)$this->db->query("SELECT MAX({$primaryKey}) AS max_id FROM {$tableName}")->fetchColumn();
                $nextId = max(1, $maxId + 1);

                foreach ($rows as $row) {
                    $this->db->exec("UPDATE {$tableName} SET {$primaryKey} = {$nextId} WHERE {$primaryKey} = 0 LIMIT 1");
                    $nextId++;
                }
            }

            if (stripos($column['Extra'] ?? '', 'auto_increment') === false) {
                $indexStmt = $this->db->prepare("SHOW INDEX FROM {$tableName} WHERE Column_name = ?");
                $indexStmt->execute([$primaryKey]);
                $indexInfo = $indexStmt->fetchAll(PDO::FETCH_ASSOC);

                $hasPrimaryKey = false;
                foreach ($indexInfo as $indexRow) {
                    if (($indexRow['Key_name'] ?? '') === 'PRIMARY') {
                        $hasPrimaryKey = true;
                        break;
                    }
                }

                if (!$hasPrimaryKey) {
                    $this->db->exec("ALTER TABLE {$tableName} ADD PRIMARY KEY ({$primaryKey})");
                }

                $this->db->exec("ALTER TABLE {$tableName} MODIFY {$primaryKey} int(11) NOT NULL AUTO_INCREMENT");
            }
        } catch (Exception $e) {
            // Ignore repair failures in the model init; table may not be fully available yet.
        }
    }

    /**
     * Get database connection
     */
    public function getConnection(): PDO
    {
        return $this->db;
    }

    /**
     * Check whether a table exists in the current database
     */
    public function tableExists(string $tableName): bool
    {
        $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check whether a column exists in the current table
     */
    public function columnExists(string $tableName, string $columnName): bool
    {
        $stmt = $this->db->prepare("SHOW COLUMNS FROM {$tableName} LIKE ?");
        $stmt->execute([$columnName]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get approved exit cases for interviews
     */
    public function getApprovedExitCases(): array
    {
        return $this->getExitCases(['approved']);
    }

    /**
     * Get exit cases that are approved and have completed the full exit workflow required
     * before post-exit feedback can be recorded.
     */
    public function getEligiblePostExitFeedbackCases(): array
    {
        $approvedCases = $this->getExitCases(['approved']);
        $eligibleCases = [];

        foreach ($approvedCases as $case) {
            $exitCaseType = $case['exit_case_type'] ?? '';
            $exitCaseId = (int)($case['exit_case_id'] ?? 0);
            $employeeId = (int)($case['employee_id'] ?? 0);

            if ($exitCaseType !== '' && $exitCaseId > 0 && $this->isExitCaseEligibleForPostExitFeedback($exitCaseType, $exitCaseId, $employeeId)) {
                $eligibleCases[] = $case;
            }
        }

        return $eligibleCases;
    }

    public function isExitCaseEligibleForPostExitFeedback(string $exitCaseType, int $exitCaseId, int $employeeId = 0): bool
    {
        if (!in_array($exitCaseType, ['resignation', 'termination'], true)) {
            return false;
        }

        $approvedCaseQuery = $exitCaseType === 'resignation'
            ? "SELECT id, employee_id FROM exit_resignations WHERE id = ? AND status = 'approved'"
            : "SELECT id, employee_id FROM exit_terminations WHERE id = ? AND status = 'approved'";

        $stmt = $this->db->prepare($approvedCaseQuery);
        $stmt->execute([$exitCaseId]);
        $caseRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$caseRecord) {
            return false;
        }

        if ($employeeId > 0 && (string)($caseRecord['employee_id'] ?? '') !== (string)$employeeId) {
            return false;
        }

        $interviewStmt = $this->db->prepare(
            "SELECT id, employee_id, status FROM exit_interviews WHERE exit_case_type = ? AND exit_case_id = ? AND status = 'completed' ORDER BY updated_at DESC LIMIT 1"
        );
        $interviewStmt->execute([$exitCaseType, $exitCaseId]);
        $interview = $interviewStmt->fetch(PDO::FETCH_ASSOC);

        if (!$interview) {
            return false;
        }

        $assessmentRequired = false;
        if ($this->tableExists('exit_interview_hr_assessments')) {
            $assessmentStmt = $this->db->prepare(
                "SELECT knowledge_transfer_required FROM exit_interview_hr_assessments WHERE interview_id = ? LIMIT 1"
            );
            $assessmentStmt->execute([$interview['id']]);
            $assessment = $assessmentStmt->fetch(PDO::FETCH_ASSOC);
            $assessmentRequired = !empty($assessment['knowledge_transfer_required']) && (string)$assessment['knowledge_transfer_required'] !== '0';

            if ($assessmentRequired) {
                $orderCol = $this->columnExists('exit_knowledge_transfer_plans', 'completed_at') ? 'completed_at' : ($this->columnExists('exit_knowledge_transfer_plans', 'updated_at') ? 'updated_at' : 'id');
                // Accept plans marked as 'completed' or 'active' to accommodate varying workflows
                $transferSql = "SELECT id, status FROM exit_knowledge_transfer_plans WHERE employee_id = ? AND status IN ('completed','active') ORDER BY {$orderCol} DESC LIMIT 1";
                $transferStmt = $this->db->prepare($transferSql);
                $transferStmt->execute([$employeeId ?: (int)($interview['employee_id'] ?? 0)]);
                $transferPlan = $transferStmt->fetch(PDO::FETCH_ASSOC);

                if (!$transferPlan) {
                    return false;
                }
            }
        }

        if ($this->tableExists('exit_employee_settlements') && $this->columnExists('exit_employee_settlements', 'exit_case_type')) {
            $settOrder = $this->columnExists('exit_employee_settlements', 'updated_at') ? 'updated_at' : ($this->columnExists('exit_employee_settlements', 'created_at') ? 'created_at' : 'id');
            $settlementSql = "SELECT id, status FROM exit_employee_settlements WHERE exit_case_type = ? AND exit_case_id = ? AND status IN ('approved', 'paid') ORDER BY {$settOrder} DESC LIMIT 1";
            $settlementStmt = $this->db->prepare($settlementSql);
            $settlementStmt->execute([$exitCaseType, $exitCaseId]);
            $settlement = $settlementStmt->fetch(PDO::FETCH_ASSOC);

            if (!$settlement) {
                return false;
            }
        }

        if ($this->tableExists('exit_survey_responses')) {
            // Defensive: use whichever linkage columns are available to detect duplicates
            if ($this->columnExists('exit_survey_responses', 'exit_case_type') && $this->columnExists('exit_survey_responses', 'exit_case_id')) {
                if ($this->columnExists('exit_survey_responses', 'survey_type')) {
                    $duplicateSql = "SELECT COUNT(*) FROM exit_survey_responses WHERE exit_case_type = ? AND exit_case_id = ? AND survey_type = 'post_exit_feedback'";
                } else {
                    $duplicateSql = "SELECT COUNT(*) FROM exit_survey_responses WHERE exit_case_type = ? AND exit_case_id = ?";
                }
                $duplicateStmt = $this->db->prepare($duplicateSql);
                $duplicateStmt->execute([$exitCaseType, $exitCaseId]);
                $dupCount = (int)$duplicateStmt->fetchColumn();
            } elseif ($this->columnExists('exit_survey_responses', 'exit_case_id')) {
                $duplicateStmt = $this->db->prepare("SELECT COUNT(*) FROM exit_survey_responses WHERE exit_case_id = ?");
                $duplicateStmt->execute([$exitCaseId]);
                $dupCount = (int)$duplicateStmt->fetchColumn();
            } elseif ($this->columnExists('exit_survey_responses', 'employee_id')) {
                $duplicateStmt = $this->db->prepare("SELECT COUNT(*) FROM exit_survey_responses WHERE employee_id = ?");
                $duplicateStmt->execute([$employeeId]);
                $dupCount = (int)$duplicateStmt->fetchColumn();
            } else {
                $dupCount = 0; // cannot determine, assume none
            }

            if ($dupCount > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get active/approved exit cases for valid documentation and process linkage
     */
    public function getActiveExitCases(string $employeeId = ''): array
    {
        return $this->getExitCases(['pending_review', 'pending_legal_review', 'approved'], $employeeId);
    }

    public function getExitCaseDocumentationList(string $status = 'all', int $page = 1, int $limit = 10, string $search = ''): array
    {
        $cases = $this->getExitCases(['pending_review', 'pending_legal_review', 'approved']);
        $search = trim($search);
        $filtered = array_filter($cases, function ($case) use ($status, $search) {
            if ($status && $status !== 'all' && $status !== 'active') {
                if (strcasecmp($case['case_status'] ?? '', $status) !== 0) {
                    return false;
                }
            }

            if ($search !== '') {
                $needle = mb_strtolower($search);
                $haystack = mb_strtolower(($case['full_name'] ?? '') . ' ' . ($case['username'] ?? '') . ' ' . ($case['exit_reason'] ?? ''));
                if (strpos($haystack, $needle) === false) {
                    return false;
                }
            }

            return true;
        });

        $total = count($filtered);
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;
        $paged = array_slice($filtered, $offset, $limit);

        return [
            'data' => array_values($paged),
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * Get exit cases filtered by status and optionally employee
     */
    protected function getExitCases(array $statuses, string $employeeId = ''): array
    {
        $cases = [];

        if (empty($statuses)) {
            return $cases;
        }

        // Prepare placeholders and params for IN(...) and optional employee filter
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $employeeClause = '';
        $params = $statuses;
        if (!empty($employeeId)) {
            $employeeClause = ' AND e.employee_id = ?';
            $params[] = $employeeId;
        }

        if ($this->tableExists('exit_resignations')) {
            // include resignation_type column only if it exists in the table to avoid SQL errors on older schemas
            $includeResignationType = $this->columnExists('exit_resignations', 'resignation_type');
            $resignationSubtypeSelect = $includeResignationType ? "r.resignation_type AS exit_subtype" : "NULL AS exit_subtype";

            $sql = "SELECT
                'resignation' AS exit_case_type,
                r.id AS exit_case_id,
                e.employee_id AS employee_id,
                CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                e.employee_code AS username,
                e.email,
                e.department,
                e.position,
                e.hire_date,
                e.employment_status,
                '' AS manager_name,
                r.reason AS exit_reason,
                r.notice_date,
                r.last_working_date,
                r.last_working_date AS exit_date,
                r.approved_by,
                CONCAT(approver.first_name, ' ', approver.last_name) AS approved_by_name,
                r.approved_at,
                " . $resignationSubtypeSelect . "
            FROM exit_resignations r
            JOIN em_employees e ON r.employee_id = e.employee_id
            LEFT JOIN hrms_employee approver ON r.approved_by = approver.employee_id
            WHERE r.status IN ($placeholders){$employeeClause}
            ORDER BY e.first_name, e.last_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $cases = array_merge($cases, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        if ($this->tableExists('exit_terminations')) {
            $sql = "SELECT
                'termination' AS exit_case_type,
                t.id AS exit_case_id,
                e.employee_id AS employee_id,
                CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                e.employee_code AS username,
                e.email,
                e.department,
                e.position,
                e.hire_date,
                e.employment_status,
                '' AS manager_name,
                t.termination_reason AS exit_reason,
                t.effective_date,
                t.effective_date AS last_working_date,
                t.effective_date AS exit_date,
                t.approved_by,
                CONCAT(approver.first_name, ' ', approver.last_name) AS approved_by_name,
                t.approved_at,
                NULL AS exit_subtype
            FROM exit_terminations t
            JOIN em_employees e ON t.employee_id = e.employee_id
            LEFT JOIN hrms_employee approver ON t.approved_by = approver.employee_id
            WHERE t.status IN ($placeholders){$employeeClause}
            ORDER BY e.first_name, e.last_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $cases = array_merge($cases, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        usort($cases, function ($a, $b) {
            return strcasecmp($a['full_name'] ?? '', $b['full_name'] ?? '');
        });

        return $cases;
    }

    /**
     * Get approved exit case details by type and ID
     */
    public function getExitCaseDetails(string $exitCaseType, int $exitCaseId): ?array
    {
        if ($exitCaseType === 'resignation') {
            $stmt = $this->db->prepare("SELECT
                'resignation' AS exit_case_type,
                r.id AS exit_case_id,
                e.employee_id AS employee_id,
                CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                e.department,
                e.position,
                e.hire_date,
                e.employment_status,
                '' AS manager_name,
                r.reason AS exit_reason,
                r.notice_date,
                r.last_working_date,
                r.status AS case_status,
                r.approved_by,
                CONCAT(approver.first_name, ' ', approver.last_name) AS approved_by_name,
                r.approved_at
            FROM exit_resignations r
            JOIN em_employees e ON r.employee_id = e.employee_id
            LEFT JOIN hrms_employee approver ON r.approved_by = approver.employee_id
            WHERE r.id = ?");
        } elseif ($exitCaseType === 'termination') {
            $stmt = $this->db->prepare("SELECT
                'termination' AS exit_case_type,
                t.id AS exit_case_id,
                e.employee_id AS employee_id,
                CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                e.department,
                e.position,
                e.hire_date,
                e.employment_status,
                '' AS manager_name,
                t.termination_reason AS exit_reason,
                t.effective_date,
                t.effective_date AS last_working_date,
                t.status AS case_status,
                t.approved_by,
                CONCAT(approver.first_name, ' ', approver.last_name) AS approved_by_name,
                t.approved_at
            FROM exit_terminations t
            JOIN em_employees e ON t.employee_id = e.employee_id
            LEFT JOIN hrms_employee approver ON t.approved_by = approver.employee_id
            WHERE t.id = ?");
        } else {
            return null;
        }

        $stmt->execute([$exitCaseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get employee engagement-related records for an employee
     */
    public function getEngagementRecords(string $employeeId): array
    {
        $records = [
            'exit_surveys' => [],
            'grievances' => [],
            'feedback_history' => $this->getFeedbackHistoryByEmployee($employeeId)
        ];

        if ($this->tableExists('exit_survey_responses')) {
            $stmt = $this->db->prepare("SELECT
                sr.id AS response_id,
                sr.survey_id,
                sv.title AS survey_title,
                sr.responses,
                sr.submitted_at
            FROM exit_survey_responses sr
            LEFT JOIN exit_surveys sv ON sr.survey_id = sv.id
            WHERE sr.employee_id = ?
            ORDER BY sr.submitted_at DESC");
            $stmt->execute([$employeeId]);
            $records['exit_surveys'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($this->tableExists('grievances')) {
            $stmt = $this->db->prepare("SELECT
                g.id,
                g.subject,
                g.description,
                g.status,
                g.priority,
                g.created_at,
                g.updated_at,
                COALESCE(CONCAT(u.first_name, ' ', u.last_name), '') AS assigned_to_name
            FROM grievances g
            LEFT JOIN hrms_employee u ON g.assigned_to = u.employee_id
            WHERE g.employee_id = ?
            ORDER BY g.created_at DESC");
            $stmt->execute([$employeeId]);
            $records['grievances'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $records;
    }

    /**
     * Get exit interview feedback history for an employee
     */
    public function getFeedbackHistoryByEmployee(string $employeeId): array
    {
        if (!$this->tableExists('exit_interview_feedback')) {
            return [];
        }

        $stmt = $this->db->prepare("SELECT
                ei.id AS interview_id,
                ei.scheduled_date,
                ei.status,
                f.overall_satisfaction,
                f.submitted_at
            FROM exit_interviews ei
            JOIN exit_interview_feedback f ON ei.id = f.interview_id
            WHERE ei.employee_id = ?
            ORDER BY f.submitted_at DESC");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all employees eligible for exit management
     * Returns employees list, with optional user link and fallback.
     */
    public function getEligibleEmployees(): array
    {
        // Get all active employees for exit-related operations such as document uploads
        $stmt = $this->db->query("
            SELECT
                e.employee_id AS id,
                CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                e.employee_code AS username,
                e.email,
                e.department,
                e.position,
                e.employment_status AS employee_status
            FROM em_employees e
            WHERE LOWER(TRIM(e.employment_status)) = 'active'
            ORDER BY e.first_name, e.last_name ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee salary components from payroll system
     */
    public function getEmployeeSalaryComponents(string $employeeId): array
    {
        try {
            // Try to get salary from payroll database first
            $payrollDb = $this->getPayrollConnection();
            if ($payrollDb) {
                // Get current salary structure for employee
                $stmt = $payrollDb->prepare("
                    SELECT
                        es.rate,
                        ss.basic_salary,
                        ss.name as salary_structure_name
                    FROM pr_employee_salary es
                    LEFT JOIN pr_salary_structures ss ON es.salary_structure_id = ss.id
                    WHERE es.employee_id = ?
                    ORDER BY es.effective_date DESC
                    LIMIT 1
                ");
                $stmt->execute([$employeeId]);
                $salaryData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($salaryData) {
                    $basicSalary = $salaryData['rate'] ?: $salaryData['basic_salary'] ?: 0;

                    // Get employee allowances
                    $stmt = $payrollDb->prepare("
                        SELECT a.name, a.amount, a.type
                        FROM pr_employee_allowances ea
                        JOIN pr_allowances a ON ea.allowance_id = a.id
                        WHERE ea.employee_id = ?
                    ");
                    $stmt->execute([$employeeId]);
                    $employeeAllowances = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Calculate specific allowances
                    $hra = 0;
                    $conveyance = 0;
                    $lta = 0;
                    $medicalAllowance = 0;
                    $otherAllowances = 0;

                    foreach ($employeeAllowances as $allowance) {
                        $amount = (float)$allowance['amount'];
                        $name = strtolower($allowance['name']);

                        if (strpos($name, 'hra') !== false) {
                            $hra = $allowance['type'] === 'percentage' ? ($basicSalary * $amount / 100) : $amount;
                        } elseif (strpos($name, 'conveyance') !== false) {
                            $conveyance = $amount;
                        } elseif (strpos($name, 'lta') !== false || strpos($name, 'travel') !== false) {
                            $lta = $amount;
                        } elseif (strpos($name, 'medical') !== false) {
                            $medicalAllowance = $amount;
                        } else {
                            $otherAllowances += $amount;
                        }
                    }

                    // Get employee deductions for provident fund, gratuity calculations
                    $stmt = $payrollDb->prepare("
                        SELECT d.name, d.amount, d.type, d.is_statutory
                        FROM pr_employee_deductions ed
                        JOIN pr_deductions d ON ed.deduction_id = d.id
                        WHERE ed.employee_id = ?
                    ");
                    $stmt->execute([$employeeId]);
                    $employeeDeductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $providentFund = 0;
                    $gratuity = 0;

                    foreach ($employeeDeductions as $deduction) {
                        $amount = (float)$deduction['amount'];
                        $name = strtolower($deduction['name']);

                        if (strpos($name, 'provident') !== false || strpos($name, 'pf') !== false) {
                            $providentFund = $deduction['type'] === 'percentage' ? ($basicSalary * $amount / 100) : $amount;
                        }
                    }

                    // Calculate gratuity (typically 4.81% of basic salary per year of service)
                    // For simplicity, we'll use a standard calculation
                    $gratuity = $basicSalary * 0.0481 * 5; // Assuming 5 years of service

                    return [
                        'success' => true,
                        'basic_salary' => (float)$basicSalary,
                        'hra' => (float)$hra,
                        'conveyance' => (float)$conveyance,
                        'lta' => (float)$lta,
                        'medical_allowance' => (float)$medicalAllowance,
                        'other_allowances' => (float)$otherAllowances,
                        'provident_fund' => (float)$providentFund,
                        'gratuity' => (float)$gratuity,
                        'notice_pay' => 0, // Will be calculated based on notice period
                        'outstanding_loans' => 0, // Would need loan system integration
                        'other_deductions' => 0, // Additional deductions
                        'source' => 'payroll_system'
                    ];
                }
            }
        } catch (Exception $e) {
            // If payroll database is not available, continue to fallback
        }

        // Fallback: Try to get from employees table if salary field exists
        try {
            $stmt = $this->db->prepare("
                SELECT salary FROM em_employees WHERE employee_id = ?
            ");
            $stmt->execute([$employeeId]);
            $employeeSalary = $stmt->fetchColumn();

            if ($employeeSalary) {
                return [
                    'success' => true,
                    'basic_salary' => (float)$employeeSalary,
                    'hra' => (float)($employeeSalary * 0.4), // 40% of basic
                    'conveyance' => 19200, // Standard conveyance allowance
                    'lta' => 30000, // Standard LTA
                    'medical_allowance' => 5000, // Standard medical allowance
                    'other_allowances' => 3000, // Other allowances
                    'provident_fund' => (float)($employeeSalary * 0.12), // 12% of basic
                    'gratuity' => (float)($employeeSalary * 0.0481 * 5), // Gratuity for 5 years
                    'notice_pay' => 0,
                    'outstanding_loans' => 0,
                    'other_deductions' => 0,
                    'source' => 'fallback'
                ];
            }
        } catch (Exception $e) {
            // Continue to default fallback
        }

        // Default fallback values
        return [
            'success' => true,
            'basic_salary' => 25000,
            'hra' => 10000,
            'conveyance' => 19200,
            'lta' => 30000,
            'medical_allowance' => 5000,
            'other_allowances' => 3000,
            'provident_fund' => 3000,
            'gratuity' => 12000,
            'notice_pay' => 0,
            'outstanding_loans' => 0,
            'other_deductions' => 0,
            'source' => 'default'
        ];
    }

    /**
     * Get connection to payroll database
     */
    private function getPayrollConnection(): ?PDO
    {
        try {
            return new PDO(
                "mysql:host=localhost;dbname=payroll;charset=utf8mb4",
                "root",
                "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (Exception $e) {
            return null; // Payroll database not available
        }
    }

    /**
     * Get eligible interviewers (admins and managers)
     */
    public function getEligibleInterviewers(): array
    {
        $stmt = $this->db->query("
            SELECT
                he.employee_id AS id,
                CONCAT(he.first_name, ' ', he.last_name) AS full_name,
                he.employee_id AS username,
                hr.role_name AS role,
                'admin' AS type
            FROM hrms_employee he
            JOIN hrms_roles hr ON he.role = hr.role_id
            WHERE he.status = 'active'
            ORDER BY he.first_name, he.last_name
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee details by ID
     */
    public function getEmployeeById($employeeId): ?array
    {
        $stmt = $this->db->prepare("SELECT
                e.*,
                CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                NULL AS manager_id,
                '' AS manager_name
            FROM em_employees e
            WHERE e.employee_id = ?
            LIMIT 1");
        $stmt->execute([$employeeId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        return $employee ?: null;
    }

    /**
     * Get user details by ID
     */
    public function getUserById(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT
                employee_id AS id,
                employee_id,
                CONCAT(first_name, ' ', last_name) AS full_name,
                status
            FROM hrms_employee WHERE employee_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Update employee status
     */
    public function updateEmployeeStatus(int $employeeId, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE em_employees
            SET employment_status = ?
            WHERE employee_id = ?
        ");
        return $stmt->execute([$status, $employeeId]);
    }

    /**
     * Get employees who have submitted resignations
     */
    public function getEmployeesWithResignations(): array
    {
        $includeResignationType = $this->columnExists('exit_resignations', 'resignation_type');
        $resTypeSelect = $includeResignationType ? 'r.resignation_type' : 'NULL AS resignation_type';

        $sql = "SELECT DISTINCT
                e.employee_id AS id,
                CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                e.employee_code AS username,
                e.email,
                e.department,
                e.position,
                {$resTypeSelect},
                r.status as resignation_status,
                r.notice_date,
                r.last_working_date
            FROM em_employees e
            INNER JOIN exit_resignations r ON e.employee_id = r.employee_id
            WHERE r.status = 'approved'
            ORDER BY e.first_name, e.last_name";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employees eligible for knowledge transfer after completed exit interviews
     */
    public function getEmployeesNeedingKnowledgeTransfer(): array
    {
        if (!$this->tableExists('exit_interview_hr_assessments')) {
            return [];
        }

        $sql = "SELECT DISTINCT
                e.employee_id AS id,
                CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                e.employee_code AS username,
                e.email,
                e.department,
                e.position,
                ei.exit_case_type,
                ei.exit_case_id,
                COALESCE(r.last_working_date, t.effective_date) AS last_working_date,
                CASE WHEN ei.exit_case_type = 'resignation' THEN 'Resignation' ELSE 'Termination' END AS exit_type
            FROM exit_interviews ei
            JOIN exit_interview_hr_assessments hra ON hra.interview_id = ei.id AND hra.knowledge_transfer_required = 1
            JOIN em_employees e ON ei.employee_id = e.employee_id
            LEFT JOIN exit_resignations r ON ei.exit_case_type = 'resignation' AND ei.exit_case_id = r.id AND r.status = 'approved'
            LEFT JOIN exit_terminations t ON ei.exit_case_type = 'termination' AND ei.exit_case_id = t.id AND t.status = 'approved'
            LEFT JOIN exit_knowledge_transfer_plans ktp ON ktp.employee_id = e.employee_id AND ktp.status = 'active'
            WHERE ei.status IN ('scheduled', 'completed')
              AND ktp.id IS NULL
              AND ((ei.exit_case_type = 'resignation' AND r.id IS NOT NULL)
                   OR (ei.exit_case_type = 'termination' AND t.id IS NOT NULL))
            ORDER BY e.first_name, e.last_name";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}