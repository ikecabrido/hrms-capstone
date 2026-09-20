<?php

require_once __DIR__ . '/../models/SettlementModel.php';
require_once __DIR__ . '/../models/ResignationModel.php';

class SettlementController extends ExitManagementController
{
    private SettlementModel $settlementModel;
    private ResignationModel $resignationModel;

    public function __construct()
    {
        parent::__construct();
        $this->settlementModel = new SettlementModel();
        $this->resignationModel = new ResignationModel();
    }

    /**
     * Normalize settlement request data for HR request workflow
     */
    private function sanitizeSettlementRequestData(array $data, ?array $existingSettlement = null): array
    {
        $exitCaseType = $data['exit_case_type'] ?? ($existingSettlement['exit_case_type'] ?? null);
        $exitCaseId = !empty($data['exit_case_id']) ? (int)$data['exit_case_id'] : (!empty($existingSettlement['exit_case_id']) ? (int)$existingSettlement['exit_case_id'] : null);
        $lastWorkingDate = $data['last_working_date'] ?? ($data['settlement_date'] ?? null);

        if (empty($lastWorkingDate) && !empty($exitCaseType) && !empty($exitCaseId)) {
            $exitCase = $this->model->getExitCaseDetails($exitCaseType, $exitCaseId);
            if ($exitCase && !empty($exitCase['last_working_date'])) {
                $lastWorkingDate = $exitCase['last_working_date'];
            }
        }

        return [
            'employee_id' => $data['employee_id'] ?? '',
            'exit_case_type' => $exitCaseType,
            'exit_case_id' => $exitCaseId,
            'resignation_id' => !empty($data['resignation_id']) ? (int)$data['resignation_id'] : null,
            'last_working_date' => $lastWorkingDate,
            'remarks' => $data['remarks'] ?? null,
            'status' => $existingSettlement['status'] ?? 'requested',
            'requested_at' => $data['requested_at'] ?? ($existingSettlement['requested_at'] ?? date('Y-m-d H:i:s')),
            'created_by' => $_SESSION['employee_id'] ?? 0
        ];
    }

    /**
     * Create settlement
     */
    private function validateApprovedExitCase(array $data): array
    {
        $exitCaseType = $data['exit_case_type'] ?? '';
        $exitCaseId = !empty($data['exit_case_id']) ? (int)$data['exit_case_id'] : 0;

        if (!in_array($exitCaseType, ['resignation', 'termination'], true) || $exitCaseId <= 0) {
            return ['success' => false, 'message' => 'A valid approved exit case is required.'];
        }

        $exitCase = $this->model->getExitCaseDetails($exitCaseType, $exitCaseId);
        if (!$exitCase) {
            return ['success' => false, 'message' => 'The selected exit case does not exist or is not approved.'];
        }

        return ['success' => true, 'exit_case' => $exitCase];
    }

    public function createSettlement(array $data): array
    {
        try {
            // Validate the approved exit case handoff that HR is allowed to submit.
            $required = ['exit_case_type', 'exit_case_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }

            $validation = $this->validateApprovedExitCase($data);
            if (!$validation['success']) {
                return $validation;
            }

            $exitCase = $validation['exit_case'];
            $data['employee_id'] = $exitCase['employee_id'];
            $data['exit_case_type'] = $exitCase['exit_case_type'];
            $data['exit_case_id'] = $exitCase['exit_case_id'];
            $data['resignation_id'] = $data['exit_case_type'] === 'resignation' ? $data['exit_case_id'] : null;
            $data['last_working_date'] = $exitCase['last_working_date'] ?? null;

            if ($this->settlementModel->hasExistingSettlementForExitCase($data['exit_case_type'], (int)$data['exit_case_id'])) {
                return ['success' => false, 'message' => 'A settlement already exists for this approved exit case.'];
            }

            $data = $this->sanitizeSettlementRequestData($data);
            $data['created_by'] = $_SESSION['employee_id'] ?? 0;

            $settlementId = $this->settlementModel->createSettlement($data);
            if ($settlementId) {
                $this->resignationModel->createPayrollClearanceRequest($settlementId, $_SESSION['employee_id'] ?? null, 'HR requested payroll settlement calculation');
            }

            return [
                'success' => true,
                'message' => 'Settlement request created successfully',
                'settlement_id' => $settlementId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateSettlement(array $data): array
    {
        try {
            if (empty($data['settlement_id'])) {
                return ['success' => false, 'message' => 'Settlement ID is required for update'];
            }

            $settlementId = (int)$data['settlement_id'];
            $existingSettlement = $this->settlementModel->getSettlementById($settlementId);
            if (!$existingSettlement) {
                return ['success' => false, 'message' => 'Settlement not found'];
            }

            $validation = $this->validateApprovedExitCase($data);
            if (!$validation['success']) {
                return $validation;
            }

            $exitCase = $validation['exit_case'];
            $data['employee_id'] = $exitCase['employee_id'];
            $data['exit_case_type'] = $exitCase['exit_case_type'];
            $data['exit_case_id'] = $exitCase['exit_case_id'];
            $data['resignation_id'] = $data['exit_case_type'] === 'resignation' ? $data['exit_case_id'] : null;

            if ($this->settlementModel->hasExistingSettlementForExitCase($data['exit_case_type'], (int)$data['exit_case_id'], $settlementId)) {
                return ['success' => false, 'message' => 'A settlement already exists for this approved exit case'];
            }

            $data = $this->sanitizeSettlementRequestData($data, $existingSettlement);
            $data['updated_by'] = $_SESSION['employee_id'] ?? 0;
            $success = $this->settlementModel->updateSettlement($settlementId, $data);

            if ($success) {
                return ['success' => true, 'message' => 'Settlement updated successfully'];
            }

            return ['success' => false, 'message' => 'Failed to update settlement'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Calculate settlement components
     */
    public function calculateSettlement(array $data): array
    {
        try {
            $calculations = [];

            // Calculate gratuity if years of service provided
            if (isset($data['basic_salary']) && isset($data['years_of_service'])) {
                $calculations['gratuity'] = $this->settlementModel->calculateGratuity(
                    $data['basic_salary'],
                    $data['years_of_service']
                );
            }

            // Calculate PF
            if (isset($data['basic_salary'])) {
                $da = $data['da'] ?? 0;
                $calculations['provident_fund'] = $this->settlementModel->calculateProvidentFund(
                    $data['basic_salary'],
                    $da
                );
            }

            // Calculate notice pay
            if (isset($data['basic_salary']) && isset($data['notice_days'])) {
                $calculations['notice_pay'] = $this->settlementModel->calculateNoticePay(
                    $data['basic_salary'],
                    $data['notice_days']
                );
            }

            // Calculate total
            $total = $this->settlementModel->calculateTotalSettlement($data);
            $calculations['net_payable'] = $total;

            return [
                'success' => true,
                'calculations' => $calculations
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get settlement details
     */
    public function getSettlement(int $settlementId): array
    {
        $settlement = $this->settlementModel->getSettlementById($settlementId);

        if (!$settlement) {
            return ['error' => 'Settlement not found'];
        }

        return $settlement;
    }

    /**
     * Get full settlement details including payroll final settlement data when available.
     * This is read-only and only reads from the shared Exit/Payroll tables.
     */
    public function getSettlementFullDetails(int $settlementId): array
    {
        try {
            if (empty($settlementId)) {
                return ['success' => false, 'message' => 'Settlement ID is required'];
            }

            $settlement = $this->settlementModel->getSettlementById($settlementId);
            if (!$settlement) {
                return ['success' => false, 'message' => 'Settlement not found'];
            }

            $employee = $this->settlementModel->getEmployeeById($settlement['employee_id']);
            $payrollSettlement = null;
            $earnings = [];
            $deductions = [];
            $payrollSettlementId = $settlement['payroll_settlement_id'] ?? null;

            if (!empty($payrollSettlementId) && $this->settlementModel->tableExists('pr_final_settlements')) {
                $stmt = $this->settlementModel->getConnection()->prepare(
                    "SELECT * FROM pr_final_settlements WHERE settlement_id = ? LIMIT 1"
                );
                $stmt->execute([$payrollSettlementId]);
                $payrollSettlement = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

                if ($payrollSettlement && $this->settlementModel->tableExists('pr_final_settlement_items')) {
                    $itemStmt = $this->settlementModel->getConnection()->prepare(
                        "SELECT * FROM pr_final_settlement_items WHERE settlement_id = ? ORDER BY sort_order, item_id"
                    );
                    $itemStmt->execute([$payrollSettlementId]);
                    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    foreach ($items as $item) {
                        $itemType = strtolower((string)($item['item_type'] ?? ''));
                        if ($itemType === 'earning') {
                            $earnings[] = $item;
                        } elseif ($itemType === 'deduction') {
                            $deductions[] = $item;
                        }
                    }
                }
            }

            return [
                'success' => true,
                'data' => [
                    'settlement_id' => (int)($settlement['settlement_id'] ?? $settlementId),
                    'employee_id' => $settlement['employee_id'] ?? null,
                    'employee_code' => $employee['employee_code'] ?? '',
                    'employee_name' => trim((string)($employee['first_name'] ?? '') . ' ' . (string)($employee['last_name'] ?? '')) ?: ($settlement['full_name'] ?? ''),
                    'exit_case_type' => $settlement['exit_case_type'] ?? 'resignation',
                    'exit_case_id' => $settlement['exit_case_id'] ?? ($settlement['resignation_id'] ?? null),
                    'last_working_date' => $settlement['last_working_date'] ?? null,
                    'requested_at' => $settlement['requested_at'] ?? null,
                    'status' => $settlement['status'] ?? 'requested',
                    'payroll_settlement_id' => $payrollSettlementId,
                    'created_at' => $settlement['created_at'] ?? null,
                    'updated_at' => $settlement['updated_at'] ?? null,
                    'remarks' => $settlement['remarks'] ?? null,
                ],
                'employee' => $employee ?: null,
                'payroll_settlement' => $payrollSettlement,
                'earnings' => $earnings,
                'deductions' => $deductions,
            ];
        } catch (Exception $e) {
            error_log('Error getting settlement full details: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while retrieving settlement details'];
        }
    }

    /**
     * Approve settlement
     */
    public function approveSettlement(int $settlementId, int $approvedBy): array
    {
        try {
            $success = $this->settlementModel->updateSettlementStatus($settlementId, 'approved', $approvedBy);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Settlement approved successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to approve settlement'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get pending settlements
     */
    public function getPendingSettlements(): array
    {
        return $this->settlementModel->getPendingSettlements();
    }

    /**
     * Get all settlements (with optional status filter)
     */
    public function getSettlements(?string $status = null): array
    {
        return $this->settlementModel->getAllSettlements($status);
    }

    /**
     * Print settlement (placeholder for PDF generation)
     */
    public function printSettlement(int $settlementId)
    {
        $settlement = $this->settlementModel->getSettlementById($settlementId);
        if (!$settlement) {
            return ['success' => false, 'message' => 'Settlement not found'];
        }

        return [
            'success' => true,
            'settlement' => $settlement
        ];
    }

    public function renderSettlementPrintPage(int $settlementId): string
    {
        // Reuse the same full-detail data source used by the modal to avoid drift
        $full = $this->getSettlementFullDetails($settlementId);
        if (empty($full) || empty($full['success'])) {
            return '<!doctype html><html><head><title>Settlement Not Found</title></head><body><h1>Settlement not found</h1></body></html>';
        }

        $data = $full['data'] ?? [];
        $employee = $full['employee'] ?? [];
        $payroll = $full['payroll_settlement'] ?? null;
        $earnings = $full['earnings'] ?? [];
        $deductions = $full['deductions'] ?? [];

        $employeeName = htmlspecialchars($data['employee_name'] ?? ($employee['first_name'] ?? 'Unknown'), ENT_QUOTES);
        $employeeCode = htmlspecialchars($data['employee_code'] ?? ($employee['employee_code'] ?? ''), ENT_QUOTES);
        $settlementDate = htmlspecialchars($payroll['settlement_date'] ?? ($data['created_at'] ?? ''), ENT_QUOTES);
        $statusValue = strtolower(trim((string)($data['status'] ?? 'requested')));
        $statusLabels = [
            'pending' => 'Pending',
            'requested' => 'Requested',
            'processing' => 'Processing',
            'calculated' => 'Calculated',
            'for_approval' => 'For Approval',
            'approved' => 'Approved',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled'
        ];
        $status = htmlspecialchars($statusLabels[$statusValue] ?? ucwords(str_replace('_', ' ', $statusValue)), ENT_QUOTES);

        $html = '<!doctype html><html><head><meta charset="UTF-8"><title>Final Settlement</title>' .
            '<style>body{font-family:Arial,sans-serif;margin:24px;color:#172b4d;}h1,h2{margin-bottom:0.5rem;}.school-header{display:flex;align-items:center;border-bottom:2px solid #1f5fbf;padding-bottom:14px;margin-bottom:20px;}.school-header img{width:86px;height:86px;object-fit:contain;margin-right:18px;}.school-name{font-size:20px;font-weight:700;color:#174a8b;}.school-details{font-size:12px;line-height:1.6;color:#333;margin-top:4px;}table{width:100%;border-collapse:collapse;margin-top:1rem;}th,td{padding:8px;border:1px solid #ddd;text-align:left;}th{background:#f4f4f4;}.report-title{text-align:center;margin:12px 0 18px;}.signatories{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:52px;page-break-inside:avoid;color:#172b4d;}.signatories>div{min-height:72px;border-top:1px solid #172b4d;padding-top:8px;font-size:12px;line-height:1.5;}.muted{color:#6b7280;font-size:0.95rem;}</style>' .
            '</head><body>' .
            '<div class="school-header"><img src="/capstone_hr_management_system2/assets/pics/bcpLogo.png" alt="Bestlink College of the Philippines logo"><div><div class="school-name">Bestlink College of the Philippines - Bulacan Campus</div><div class="school-details">Lot 1 Ipo Road Brgy. Minuyan Proper, City of San Jose Del Monte, Bulacan.<br>Tel. No.: (044)792-1992</div></div></div>' .
            '<h1 class="report-title">Final Settlement Report</h1>' .
            '<p><strong>Employee:</strong> ' . $employeeName . ' &nbsp; <span class="muted">(' . $employeeCode . ')</span></p>' .
            '<p><strong>Status:</strong> ' . $status . '</p>';

        // Basic info block
        $html .= '<table><tbody>' .
            '<tr><th style="width:35%">Employee Code</th><td>' . $employeeCode . '</td></tr>' .
            '<tr><th>Employee Name</th><td>' . $employeeName . '</td></tr>' .
            '<tr><th>Employee ID</th><td>' . htmlspecialchars($data['employee_id'] ?? '', ENT_QUOTES) . '</td></tr>' .
            '<tr><th>Exit Type</th><td>' . htmlspecialchars(($data['exit_case_type'] ?? ''), ENT_QUOTES) . '</td></tr>' .
            '<tr><th>Exit Case ID</th><td>' . htmlspecialchars($data['exit_case_id'] ?? '', ENT_QUOTES) . '</td></tr>' .
            '<tr><th>Last Working Date</th><td>' . htmlspecialchars($data['last_working_date'] ?? '', ENT_QUOTES) . '</td></tr>' .
            '<tr><th>Exit Settlement ID</th><td>REQ-' . htmlspecialchars($data['settlement_id'] ?? $settlementId, ENT_QUOTES) . '</td></tr>' .
            '</tbody></table>';

        // If payroll settlement not present, show clear message and stop before financial tables
        if (empty($payroll) || empty($data['payroll_settlement_id'])) {
            $html .= '<div style="margin-top:18px;padding:14px;border:1px solid #f3f4f6;background:#fff7ed;color:#92400e;border-radius:8px;"><strong>Note:</strong> This settlement has not yet been processed by Payroll — no financial breakdown is available.</div>' .
                '<div class="signatories">' .
                '<div><strong>Prepared by:</strong><br><br>Payroll Staff</div>' .
                '<div><strong>Reviewed/Approved by:</strong><br><br>Payroll Administrator</div>' .
                '<div><strong>Employee Acknowledgment:</strong><br><br>Employee</div>' .
                '</div>' .
                '</body></html>';

            return $html;
        }

        // Build earnings table
        $totalEarnings = number_format((float)($payroll['total_earnings'] ?? 0), 2);
        $totalDeductions = number_format((float)($payroll['total_deductions'] ?? 0), 2);
        $netSettlement = number_format((float)($payroll['net_settlement'] ?? 0), 2);

        $html .= '<h2 style="margin-top:20px">Settlement Financial Breakdown</h2>' .
            '<div style="margin-top:8px"><strong>Settlement ID:</strong> STL-' . htmlspecialchars($payroll['settlement_id'] ?? $data['payroll_settlement_id'], ENT_QUOTES) . ' &nbsp; <strong>Created:</strong> ' . htmlspecialchars($payroll['created_at'] ?? $data['created_at'] ?? '', ENT_QUOTES) . '</div>';

        $html .= '<div style="margin-top:14px"><h3>Earnings</h3><table><thead><tr><th>Category</th><th>Description</th><th style="text-align:right">Amount</th></tr></thead><tbody>';
        if (!empty($earnings)) {
            foreach ($earnings as $item) {
                $html .= '<tr><td>' . htmlspecialchars($item['item_category'] ?? '', ENT_QUOTES) . '</td><td>' . htmlspecialchars($item['description'] ?? '', ENT_QUOTES) . '</td><td style="text-align:right">' . number_format((float)($item['amount'] ?? 0), 2) . '</td></tr>';
            }
        } else {
            $html .= '<tr><td colspan="3" style="text-align:center;color:#6b7280">No earnings recorded</td></tr>';
        }
        $html .= '<tr><th colspan="2">Total Earnings</th><th style="text-align:right">' . $totalEarnings . '</th></tr>' .
            '</tbody></table></div>';

        $html .= '<div style="margin-top:14px"><h3>Deductions</h3><table><thead><tr><th>Category</th><th>Description</th><th style="text-align:right">Amount</th></tr></thead><tbody>';
        if (!empty($deductions)) {
            foreach ($deductions as $item) {
                $html .= '<tr><td>' . htmlspecialchars($item['item_category'] ?? '', ENT_QUOTES) . '</td><td>' . htmlspecialchars($item['description'] ?? '', ENT_QUOTES) . '</td><td style="text-align:right">' . number_format((float)($item['amount'] ?? 0), 2) . '</td></tr>';
            }
        } else {
            $html .= '<tr><td colspan="3" style="text-align:center;color:#6b7280">No deductions recorded</td></tr>';
        }
        $html .= '<tr><th colspan="2">Total Deductions</th><th style="text-align:right">' . $totalDeductions . '</th></tr>' .
            '</tbody></table></div>';

        // Summary & payment info
        $html .= '<div style="margin-top:18px;display:flex;gap:24px;align-items:flex-start">' .
            '<div style="flex:1"><h3>Settlement Summary</h3><table><tbody>' .
            '<tr><th style="width:60%">Total Earnings</th><td style="text-align:right">' . $totalEarnings . '</td></tr>' .
            '<tr><th>Less: Total Deductions</th><td style="text-align:right">' . $totalDeductions . '</td></tr>' .
            '<tr><th>Net Settlement</th><td style="text-align:right;font-weight:700">' . $netSettlement . '</td></tr>' .
            '</tbody></table></div>';

        if (strtolower((string)($payroll['status'] ?? $statusValue)) === 'paid') {
            $html .= '<div style="width:320px"><h3>Payment Information</h3><table><tbody>' .
                '<tr><th style="width:50%">Payment Method</th><td>' . htmlspecialchars($payroll['payment_method'] ?? '', ENT_QUOTES) . '</td></tr>' .
                '<tr><th>Payment Reference</th><td>' . htmlspecialchars($payroll['payment_reference'] ?? '', ENT_QUOTES) . '</td></tr>' .
                '<tr><th>Paid Date</th><td>' . htmlspecialchars($payroll['paid_at'] ?? '', ENT_QUOTES) . '</td></tr>' .
                '</tbody></table></div>';
        }

        $html .= '</div>';

        // Activity
        $html .= '<div style="margin-top:18px"><h3>Activity</h3><table><tbody>' .
            '<tr><th style="width:35%">Requested At</th><td>' . htmlspecialchars($data['requested_at'] ?? '', ENT_QUOTES) . '</td></tr>' .
            '<tr><th>Approved At</th><td>' . htmlspecialchars($payroll['approved_at'] ?? '', ENT_QUOTES) . '</td></tr>' .
            '<tr><th>Paid At</th><td>' . htmlspecialchars($payroll['paid_at'] ?? '', ENT_QUOTES) . '</td></tr>' .
            '</tbody></table></div>';

        // Signatories block (preserve existing layout)
        $html .= '<div class="signatories">' .
            '<div><strong>Prepared by:</strong><br><br>Payroll Staff</div>' .
            '<div><strong>Reviewed/Approved by:</strong><br><br>Payroll Administrator</div>' .
            '<div><strong>Employee Acknowledgment:</strong><br><br>Employee</div>' .
            '</div>' .
            '</body></html>';

        return $html;
    }

    /**
     * Handle AJAX requests for settlements
     */
    public function handleAjaxRequest(string $action, array $data = []): array
    {
        switch ($action) {
            case 'submit_settlement':
            case 'create_settlement':
                return $this->createSettlement($data);

            case 'update_settlement':
                return $this->updateSettlement($data);

            case 'calculate_settlement':
                return $this->calculateSettlement($data);

            case 'get_settlement':
                return $this->getSettlement($data['settlement_id'] ?? 0);

            case 'approve_settlement':
                return $this->approveSettlement(
                    $data['settlement_id'] ?? 0,
                    $data['approved_by'] ?? 0
                );

            case 'get_pending_settlements':
                return $this->getPendingSettlements();

            case 'get_settlements':
                return $this->settlementModel->getAllSettlements(
                    $data['status'] ?? null,
                    $data['page'] ?? 1,
                    $data['limit'] ?? 10,
                    $data['search'] ?? ''
                );

            case 'get_archived_settlements':
                return $this->settlementModel->getArchivedSettlements(
                    $data['page'] ?? 1,
                    $data['limit'] ?? 10,
                    $data['search'] ?? ''
                );

            case 'print_settlement':
                return $this->printSettlement($data['settlement_id'] ?? 0);

            case 'archive_settlement':
                return $this->archiveSettlement($data['settlement_id'] ?? 0);

            case 'check_settlement_archive_eligibility':
                return $this->checkSettlementArchiveEligibility($data['settlement_id'] ?? 0);

            case 'unarchive_settlement':
                return $this->unarchiveSettlement($data['settlement_id'] ?? 0);

            case 'get_settlement_details':
                return $this->getSettlementDetails($data['settlement_id'] ?? 0);

            case 'get_settlement_full_details':
                return $this->getSettlementFullDetails($data['settlement_id'] ?? 0);

            default:
                return parent::handleAjaxRequest($action, $data);
        }
    }

    /**
     * Archive settlement
     */
    public function checkSettlementArchiveEligibility(int $settlementId): array
    {
        try {
            if (empty($settlementId)) {
                return ['success' => false, 'message' => 'Settlement ID is required'];
            }

            $settlement = $this->settlementModel->getSettlementById($settlementId);
            if (!$settlement) {
                return ['success' => false, 'message' => 'Settlement not found'];
            }

            if ($this->settlementModel->isSettlementArchivable($settlementId)) {
                return ['success' => true, 'message' => 'Settlement is eligible for archiving'];
            }

            return [
                'success' => false,
                'message' => 'Only approved, paid, or cancelled settlements may be archived. Current status: ' . ($settlement['status'] ?? 'unknown')
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function archiveSettlement(int $settlementId): array
    {
        try {
            if (!$this->settlementModel->isSettlementArchivable($settlementId)) {
                return [
                    'success' => false,
                    'message' => 'Settlement must be approved, paid, or cancelled before it can be archived.'
                ];
            }

            $archiveReason = $_POST['archive_reason'] ?? 'Manual archive';
            $success = $this->settlementModel->archiveSettlement($settlementId, $archiveReason);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Settlement archived successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to archive settlement'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Unarchive settlement
     */
    public function unarchiveSettlement(int $settlementId): array
    {
        try {
            $success = $this->settlementModel->unarchiveSettlement($settlementId);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Settlement unarchived successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to unarchive settlement'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get settlement details for archiving
     */
    private function getSettlementDetails(int $settlementId): array
    {
        try {
            if (empty($settlementId)) {
                return [
                    'success' => false,
                    'message' => 'Settlement ID is required'
                ];
            }

            $settlement = $this->settlementModel->getSettlementById($settlementId);

            if (!$settlement) {
                return [
                    'success' => false,
                    'message' => 'Settlement not found'
                ];
            }

            // Get employee name
            $employee = $this->settlementModel->getEmployeeById($settlement['employee_id']);

            return [
                'success' => true,
                'data' => [
                    'id' => $settlement['settlement_id'],
                    'employee_id' => $settlement['employee_id'],
                    'employee_name' => $employee ? $employee['first_name'] . ' ' . $employee['last_name'] : 'Unknown',
                    'settlement_date' => $settlement['settlement_date'],
                    'net_payable' => $settlement['net_payable'] ?? null,
                    'status' => $settlement['status']
                ]
            ];
        } catch (Exception $e) {
            error_log("Error getting settlement details: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while retrieving settlement details'
            ];
        }
    }
}