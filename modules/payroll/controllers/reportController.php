<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/reportModel.php';

class ReportController
{
    private PDO $db;
    private ReportModel $model;


    public function __construct()
    {
        $this->db =
            (new Database())->getConnection();

        $this->model =
            new ReportModel($this->db);
    }


    /* ============================================================
       REPORT INDEX
       ============================================================ */

    /**
     * Get the main reports page data.
     *
     * If a period is supplied, the payroll
     * report and summary are loaded.
     */
    public function index(
        int $periodId = 0
    ): array {

        $periods =
            $this->model->getPeriods();

        $period = null;
        $summary = $this->emptySummary();
        $payroll = [];
        $itemSummary = [];
        $departmentSummary = [];
        $hasFinalizedPayroll = false;

        if ($periodId > 0) {

            $period =
                $this->model->getPeriodById(
                    $periodId
                );

            if (!$period) {
                throw new RuntimeException(
                    'Payroll period not found.'
                );
            }

            $hasFinalizedPayroll =
                $this->model->hasFinalizedPayroll(
                    $periodId
                );

            if ($hasFinalizedPayroll) {

                $summary =
                    $this->model->getPayrollSummary(
                        $periodId
                    );

                $payroll =
                    $this->model->getPayrollOverview(
                        $periodId
                    );

                $itemSummary =
                    $this->model->getPeriodItemSummary(
                        $periodId
                    );

                $departmentSummary =
                    $this->model->getDepartmentSummary(
                        $periodId
                    );
            }
        }

        return [
            'periods' =>
            $periods,

            'period' =>
            $period,

            'summary' =>
            $summary,

            'payroll' =>
            $payroll,

            'item_summary' =>
            $itemSummary,

            'department_summary' =>
            $departmentSummary,

            'has_finalized_payroll' =>
            $hasFinalizedPayroll
        ];
    }


    /* ============================================================
       PERIODS
       ============================================================ */

    /**
     * Get all payroll periods.
     */
    public function getPeriods(): array
    {
        return $this->model->getPeriods();
    }


    /**
     * Get one payroll period.
     */
    public function getPeriod(
        int $periodId
    ): ?array {

        if ($periodId <= 0) {
            return null;
        }

        return $this->model->getPeriodById(
            $periodId
        );
    }


    /* ============================================================
       PAYROLL SUMMARY
       ============================================================ */

    /**
     * Get payroll summary.
     */
    public function getPayrollSummary(
        int $periodId
    ): array {

        $this->validatePeriod(
            $periodId
        );

        $this->ensureFinalizedPayroll(
            $periodId
        );

        return $this->model->getPayrollSummary(
            $periodId
        );
    }


    /* ============================================================
       PAYROLL REGISTER
       ============================================================ */

    /**
     * Get detailed payroll register.
     */
    public function getPayrollOverview(
        int $periodId
    ): array {

        $this->validatePeriod(
            $periodId
        );

        $this->ensureFinalizedPayroll(
            $periodId
        );

        return $this->model->getPayrollOverview(
            $periodId
        );
    }


    /* ============================================================
       EMPLOYEE PAYROLL HISTORY
       ============================================================ */

    /**
     * Get payroll history for an employee.
     */
    public function getEmployeePayrollHistory(
        int $employeeId
    ): array {

        if ($employeeId <= 0) {
            throw new InvalidArgumentException(
                'Invalid employee.'
            );
        }

        $employee =
            $this->model->getEmployeeById(
                $employeeId
            );

        if (!$employee) {
            throw new RuntimeException(
                'Employee not found.'
            );
        }

        return [
            'employee' =>
            $employee,

            'history' =>
            $this->model->getEmployeePayrollHistory(
                $employeeId
            )
        ];
    }


    /* ============================================================
       EMPLOYEE SEARCH
       ============================================================ */

    /**
     * Search employees for the Reports page.
     *
     * Examples:
     *
     * 35
     * 000035
     * EMP-000035
     * John
     */
    public function searchEmployees(
        string $search = ''
    ): array {

        return $this->model->searchEmployees(
            $search
        );
    }


    /**
     * Get employee by ID.
     */
    public function getEmployee(
        int $employeeId
    ): ?array {

        if ($employeeId <= 0) {
            return null;
        }

        return $this->model->getEmployeeById(
            $employeeId
        );
    }


    /* ============================================================
       PAYSLIP BREAKDOWN
       ============================================================ */

    /**
     * Get individual payslip items.
     */
    public function getPayslipBreakdown(
        int $payslipId
    ): array {

        if ($payslipId <= 0) {
            throw new InvalidArgumentException(
                'Invalid payslip.'
            );
        }

        return $this->model->getPayslipBreakdown(
            $payslipId
        );
    }


    /* ============================================================
       ITEM SUMMARY
       ============================================================ */

    /**
     * Get earning/deduction summary
     * for a payroll period.
     */
    public function getPeriodItemSummary(
        int $periodId
    ): array {

        $this->validatePeriod(
            $periodId
        );

        $this->ensureFinalizedPayroll(
            $periodId
        );

        return $this->model->getPeriodItemSummary(
            $periodId
        );
    }


    /* ============================================================
       DEPARTMENT SUMMARY
       ============================================================ */

    /**
     * Get payroll summary by department.
     */
    public function getDepartmentSummary(
        int $periodId
    ): array {

        $this->validatePeriod(
            $periodId
        );

        $this->ensureFinalizedPayroll(
            $periodId
        );

        return $this->model->getDepartmentSummary(
            $periodId
        );
    }


    /* ============================================================
       FINALIZED RUN
       ============================================================ */

    /**
     * Get the finalized payroll run
     * for a period.
     */
    public function getFinalizedRun(
        int $periodId
    ): ?array {

        $this->validatePeriod(
            $periodId
        );

        return $this->model->getFinalizedRun(
            $periodId
        );
    }


    /* ============================================================
       VALIDATION
       ============================================================ */

    /**
     * Make sure the payroll period exists.
     */
    private function validatePeriod(
        int $periodId
    ): void {

        if ($periodId <= 0) {
            throw new InvalidArgumentException(
                'Please select a payroll period.'
            );
        }

        $period =
            $this->model->getPeriodById(
                $periodId
            );

        if (!$period) {
            throw new RuntimeException(
                'Payroll period not found.'
            );
        }
    }


    /**
     * Make sure finalized payroll exists
     * before generating financial reports.
     */
    private function ensureFinalizedPayroll(
        int $periodId
    ): void {

        if (
            !$this->model->hasFinalizedPayroll(
                $periodId
            )
        ) {
            throw new RuntimeException(
                'No finalized payroll is available '
                    . 'for this payroll period.'
            );
        }
    }


    /**
     * Empty summary used when no period
     * has been selected.
     */
    private function emptySummary(): array
    {
        return [
            'total_employees' => 0,
            'total_gross' => 0.00,
            'total_deductions' => 0.00,
            'total_net' => 0.00,
            'average_net' => 0.00
        ];
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {

    require_once __DIR__ . '/../../../auth/session.php';
    require_once __DIR__ . '/../../../auth/guard.php';

    header('Content-Type: application/json');

    function rp_respond(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        echo json_encode($payload);
        exit;
    }

    $controller = new ReportController();
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {

        /*
         * ------------------------------------------------------
         * PERIOD-BASED REPORT BUNDLE
         * (Payroll Register / Summary / Item Summary / Department
         * Summary — all four come back together, since index()
         * already loads them together for a given period.)
         * ------------------------------------------------------
         */
        case 'period_report': {
                $periodId = intval($_GET['period_id'] ?? 0);

                if ($periodId <= 0) {
                    rp_respond(['success' => false, 'message' => 'Please select a payroll period.']);
                }

                try {
                    $result = $controller->index($periodId);
                    rp_respond(['success' => true, 'data' => $result]);
                } catch (InvalidArgumentException $e) {
                    rp_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    rp_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    rp_respond(['success' => false, 'message' => 'Unable to generate the report. Please try again.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * EMPLOYEE SEARCH (for the Employee Payroll History type)
         * ------------------------------------------------------
         */
        case 'search_employees': {
                $term = trim($_GET['q'] ?? '');

                try {
                    $results = $controller->searchEmployees($term);
                    rp_respond(['success' => true, 'data' => $results]);
                } catch (Throwable $e) {
                    rp_respond(['success' => false, 'message' => 'Unable to search employees.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * EMPLOYEE PAYROLL HISTORY REPORT
         * ------------------------------------------------------
         */
        case 'employee_history': {
                $employeeId = intval($_GET['employee_id'] ?? 0);

                if ($employeeId <= 0) {
                    rp_respond(['success' => false, 'message' => 'Please select an employee.']);
                }

                try {
                    $result = $controller->getEmployeePayrollHistory($employeeId);
                    rp_respond(['success' => true, 'data' => $result]);
                } catch (InvalidArgumentException $e) {
                    rp_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    rp_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    rp_respond(['success' => false, 'message' => 'Unable to generate the report. Please try again.'], 500);
                }
                break;
            }

        default: {
                rp_respond(['success' => false, 'message' => 'Unknown action.'], 400);
            }
    }
}
