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
        return [
            'employee_id' => $data['employee_id'] ?? '',
            'exit_case_type' => $data['exit_case_type'] ?? null,
            'exit_case_id' => !empty($data['exit_case_id']) ? (int)$data['exit_case_id'] : null,
            'resignation_id' => !empty($data['resignation_id']) ? (int)$data['resignation_id'] : null,
            'basic_salary' => 0,
            'remaining_salary' => 0,
            'unused_leave_conversion' => 0,
            'overtime_pay' => 0,
            'holiday_pay' => 0,
            'bonuses' => 0,
            'commission' => 0,
            'hra' => 0,
            'conveyance' => 0,
            'lta' => 0,
            'medical_allowance' => 0,
            'other_allowances' => 0,
            'separation_pay' => 0,
            'tax' => 0,
            'sss' => 0,
            'philhealth' => 0,
            'pagibig' => 0,
            'cash_advance' => 0,
            'company_loan' => 0,
            'equipment_damage' => 0,
            'missing_assets' => 0,
            'late_deductions' => 0,
            'absence_deductions' => 0,
            'provident_fund' => 0,
            'gratuity' => 0,
            'notice_pay' => 0,
            'outstanding_loans' => 0,
            'other_deductions' => 0,
            'net_payable' => 0,
            'settlement_date' => $data['settlement_date'] ?? null,
            'payment_date' => null,
            'status' => $existingSettlement['status'] ?? 'pending_approval'
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
            // Validate required fields
            $required = ['settlement_date', 'exit_case_type', 'exit_case_id'];
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
        $settlement = $this->settlementModel->getSettlementById($settlementId);
        if (!$settlement) {
            return '<!doctype html><html><head><title>Settlement Not Found</title></head><body><h1>Settlement not found</h1></body></html>';
        }

        $employeeName = htmlspecialchars($settlement['full_name'] ?? 'Unknown', ENT_QUOTES);
        $settlementDate = htmlspecialchars($settlement['settlement_date'] ?? '', ENT_QUOTES);
        $paymentDate = htmlspecialchars($settlement['payment_date'] ?? 'N/A', ENT_QUOTES);
        $statusValue = strtolower(trim((string)($settlement['status'] ?? '')));
        $statusLabels = [
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'paid' => 'Paid',
            'rejected' => 'Rejected',
            'draft' => 'Draft'
        ];
        $status = htmlspecialchars($statusLabels[$statusValue] ?? ucwords(str_replace('_', ' ', $statusValue)), ENT_QUOTES);
        $netPayable = number_format($settlement['net_payable'] ?? 0, 2);

        $html = '<!doctype html><html><head><meta charset="UTF-8"><title>Final Settlement</title>' .
            '<style>body{font-family:Arial,sans-serif;margin:24px;color:#172b4d;}h1,h2{margin-bottom:0.5rem;}.school-header{display:flex;align-items:center;border-bottom:2px solid #1f5fbf;padding-bottom:14px;margin-bottom:20px;}.school-header img{width:86px;height:86px;object-fit:contain;margin-right:18px;}.school-name{font-size:20px;font-weight:700;color:#174a8b;}.school-details{font-size:12px;line-height:1.6;color:#333;margin-top:4px;}table{width:100%;border-collapse:collapse;margin-top:1rem;}th,td{padding:8px;border:1px solid #ddd;text-align:left;}th{background:#f4f4f4;}.report-title{text-align:center;margin:12px 0 18px;}.signatories{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:52px;page-break-inside:avoid;color:#172b4d;}.signatories>div{min-height:72px;border-top:1px solid #172b4d;padding-top:8px;font-size:12px;line-height:1.5;}</style>' .
            '</head><body>' .
            '<div class="school-header"><img src="/capstone_hr_management_system2/assets/pics/bcpLogo.png" alt="Bestlink College of the Philippines logo"><div><div class="school-name">Bestlink College of the Philippines - Bulacan Campus</div><div class="school-details">Lot 1 Ipo Road Brgy. Minuyan Proper, City of San Jose Del Monte, Bulacan.<br>Tel. No.: (044)792-1992</div></div></div>' .
            '<h1 class="report-title">Final Settlement Report</h1>' .
            '<p><strong>Employee:</strong> ' . $employeeName . '</p>' .
            '<p><strong>Settlement Date:</strong> ' . $settlementDate . '</p>' .
            '<p><strong>Payment Date:</strong> ' . $paymentDate . '</p>' .
            '<p><strong>Status:</strong> ' . $status . '</p>' .
            '<table><thead><tr><th>Description</th><th>Amount</th></tr></thead><tbody>' .
            '<tr><td>Basic Salary</td><td>' . number_format($settlement['basic_salary'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Remaining Salary</td><td>' . number_format($settlement['remaining_salary'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Unused Leave Conversion</td><td>' . number_format($settlement['unused_leave_conversion'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Overtime Pay</td><td>' . number_format($settlement['overtime_pay'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Holiday Pay</td><td>' . number_format($settlement['holiday_pay'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Bonuses</td><td>' . number_format($settlement['bonuses'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Commission</td><td>' . number_format($settlement['commission'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>HRA</td><td>' . number_format($settlement['hra'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Conveyance</td><td>' . number_format($settlement['conveyance'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>LTA</td><td>' . number_format($settlement['lta'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Medical Allowance</td><td>' . number_format($settlement['medical_allowance'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Other Allowances</td><td>' . number_format($settlement['other_allowances'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Separation Pay</td><td>' . number_format($settlement['separation_pay'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Provident Fund</td><td>' . number_format($settlement['provident_fund'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Gratuity</td><td>' . number_format($settlement['gratuity'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Notice Pay</td><td>' . number_format($settlement['notice_pay'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Tax</td><td>' . number_format($settlement['tax'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>SSS</td><td>' . number_format($settlement['sss'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>PhilHealth</td><td>' . number_format($settlement['philhealth'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Pag-IBIG</td><td>' . number_format($settlement['pagibig'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Cash Advance</td><td>' . number_format($settlement['cash_advance'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Company Loan</td><td>' . number_format($settlement['company_loan'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Equipment Damage</td><td>' . number_format($settlement['equipment_damage'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Missing Assets</td><td>' . number_format($settlement['missing_assets'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Late Deductions</td><td>' . number_format($settlement['late_deductions'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Absence Deductions</td><td>' . number_format($settlement['absence_deductions'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Outstanding Loans</td><td>' . number_format($settlement['outstanding_loans'] ?? 0, 2) . '</td></tr>' .
            '<tr><td>Other Deductions</td><td>' . number_format($settlement['other_deductions'] ?? 0, 2) . '</td></tr>' .
            '<tr><th>Total Net Payable</th><th>' . $netPayable . '</th></tr>' .
            '</tbody></table>' .
            '<div class="signatories">' .
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
                'message' => 'Only approved, paid, or rejected settlements may be archived. Current status: ' . ($settlement['status'] ?? 'unknown')
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
                    'message' => 'Settlement must be approved, paid, or rejected before it can be archived.'
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
                    'id' => $settlement['id'],
                    'employee_id' => $settlement['employee_id'],
                    'employee_name' => $employee ? $employee['first_name'] . ' ' . $employee['last_name'] : 'Unknown',
                    'settlement_date' => $settlement['settlement_date'],
                    'net_payable' => $settlement['net_payable'],
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