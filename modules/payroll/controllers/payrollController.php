<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../models/payrollModel.php';
require_once __DIR__ . '/../classes/payrollPeriodModel.php';
require_once __DIR__ . '/../models/payslipModel.php';

class PayrollController
{
    private PDO $db;
    private ?PDO $smsDb;

    private PayrollModel $payrollModel;
    private PayrollPeriodModel $periodModel;
    private PayslipModel $payslipModel;

    /**
     * $db    = HRIS / Payroll database connection
     * $smsDb = School Management System database connection
     *
     * The PayrollModel uses the SMS database only for faculty
     * schedule / subject information.
     */
    public function __construct(PDO $db, ?PDO $smsDb = null)
    {
        $this->db = $db;
        $this->smsDb = $smsDb;

        $this->payrollModel = new PayrollModel(
            $this->db,
            $this->smsDb
        );

        $this->periodModel = new PayrollPeriodModel($this->db);
        $this->payslipModel = new PayslipModel($this->db);
    }

    /* ============================================================
       PAYROLL PROCESSING PAGE
       ============================================================ */

    /**
     * Get payroll periods available to the payroll processing page.
     */
    public function index(): array
    {
        return $this->payrollModel->getPayrollPeriods();
    }


    /* ============================================================
       PAYROLL PERIODS
       ============================================================ */

    /**
     * Get all payroll periods.
     */
    public function getPeriods(): array
    {
        return $this->payrollModel->getPayrollPeriods();
    }

    /**
     * Get one payroll period.
     */
    public function getPeriod(int $periodId): ?array
    {
        return $this->payrollModel->getPayrollPeriod($periodId);
    }

    /**
     * Check whether a payroll period is already closed.
     */
    public function isClosed(int $periodId): bool
    {
        return $this->payrollModel->isPeriodClosed($periodId);
    }


    /* ============================================================
       EMPLOYEES
       ============================================================ */

    /**
     * Get all active employees who can be included in payroll.
     */
    public function getEmployees(int $periodId): array
    {
        return $this->payrollModel->getAllActiveEmployeesForPeriod(
            $periodId
        );
    }

    /**
     * Get a single employee.
     */
    public function getEmployee(int $employeeId): ?array
    {
        return $this->payrollModel->getEmployee($employeeId);
    }


    /* ============================================================
       PAYROLL CALCULATION / PREVIEW
       ============================================================ */

    /**
     * Calculate payroll for every active employee in a period.
     *
     * This is intended for the payroll-processing preview.
     */
    public function calculate(
        int $periodId,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {

        if ($this->isClosed($periodId)) {
            throw new Exception(
                'Payroll period is already closed. Cannot recalculate.'
            );
        }

        $period = $this->payrollModel->getPayrollPeriod($periodId);

        if (!$period) {
            throw new Exception(
                'Payroll period not found.'
            );
        }

        return $this->payrollModel->getPayrollPreview(
            $periodId,
            $schoolYearId,
            $semesterId
        );
    }

    /**
     * Get payroll preview.
     *
     * Kept as a separate method because the UI may request a preview
     * without treating it as a calculation/finalization action.
     */
    public function previewPayroll(
        int $periodId,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {

        if ($this->isClosed($periodId)) {
            throw new Exception(
                'Payroll period is already closed.'
            );
        }

        return $this->payrollModel->getPayrollPreview(
            $periodId,
            $schoolYearId,
            $semesterId
        );
    }

    /**
     * Calculate one employee's payroll.
     *
     * Useful when the payroll UI has a "View Breakdown" action.
     */
    public function calculateEmployee(
        int $employeeId,
        int $periodId,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {

        if ($this->isClosed($periodId)) {
            throw new Exception(
                'Payroll period is already closed.'
            );
        }

        $result = $this->payrollModel->calculateEmployeePayroll(
            $employeeId,
            $periodId,
            $schoolYearId,
            $semesterId
        );

        if (!$result) {
            throw new Exception(
                'Unable to calculate payroll for this employee.'
            );
        }

        return $result;
    }


    /* ============================================================
       ATTENDANCE
       ============================================================ */

    /**
     * Get attendance metrics for one employee during a payroll period.
     */
    public function getAttendance(
        int $employeeId,
        int $periodId
    ): array {

        $period = $this->payrollModel->getPayrollPeriod($periodId);

        if (!$period) {
            throw new Exception(
                'Payroll period not found.'
            );
        }

        return $this->payrollModel->getTimeAttendanceMetrics(
            $employeeId,
            $period['start_date'],
            $period['end_date']
        );
    }

    /**
     * Get detailed attendance records for one employee.
     */
    public function getAttendanceRecords(
        int $employeeId,
        int $periodId
    ): array {

        $period = $this->payrollModel->getPayrollPeriod($periodId);

        if (!$period) {
            throw new Exception(
                'Payroll period not found.'
            );
        }

        return $this->payrollModel->getAttendanceRecords(
            $employeeId,
            $period['start_date'],
            $period['end_date']
        );
    }


    /* ============================================================
       FACULTY SCHEDULE
       ============================================================ */

    /**
     * Get the faculty member's recurring schedule from SMS.
     */
    public function getFacultySchedule(
        int $employeeId,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {

        return $this->payrollModel->getFacultySchedule(
            $employeeId,
            $schoolYearId,
            $semesterId
        );
    }

    /**
     * Get faculty classes for a specific date.
     */
    public function getFacultyClassesForDate(
        int $employeeId,
        string $date,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {

        return $this->payrollModel->getFacultyClassesForDate(
            $employeeId,
            $date,
            $schoolYearId,
            $semesterId
        );
    }


    /* ============================================================
       PAYROLL RUN
       ============================================================ */

    /**
     * Create a draft payroll run.
     *
     * IMPORTANT:
     * This does not finalize the payroll.
     */
    public function createRun(int $periodId): int
    {
        if ($this->isClosed($periodId)) {
            throw new Exception(
                'Payroll period is already closed.'
            );
        }

        return $this->payrollModel->createPayrollRun(
            $periodId
        );
    }


    /* ============================================================
       FINALIZE PAYROLL
       ============================================================ */

    /**
     * Generate payslips and finalize a payroll run.
     *
     * The payroll calculations are performed using the same
     * PayrollModel calculation logic.
     */
    public function finalize(
        int $periodId,
        ?int $schoolYearId = null,
        ?int $semesterId = null,
        ?int $finalizedBy = null
    ): array {

        if ($this->isClosed($periodId)) {
            throw new Exception(
                'Payroll period is already closed.'
            );
        }

        /*
         * Verify that the period exists before creating the run.
         */
        $period = $this->payrollModel->getPayrollPeriod(
            $periodId
        );

        if (!$period) {
            throw new Exception(
                'Payroll period not found.'
            );
        }

        /*
         * Create draft payroll run.
         */
        $runId = $this->payrollModel->createPayrollRun(
            $periodId
        );

        try {

            /*
             * Calculate the payroll preview again immediately
             * before generating the payslips.
             */
            $employees =
                $this->payrollModel->getAllActiveEmployeesForPeriod(
                    $periodId
                );

            $generatedPayslips = [];
            $skippedEmployees = [];

            foreach ($employees as $employee) {

                $employeeId = (int)$employee['employee_id'];

                $payroll =
                    $this->payrollModel->calculateEmployeePayroll(
                        $employeeId,
                        $periodId,
                        $schoolYearId,
                        $semesterId
                    );

                if (!$payroll) {
                    $skippedEmployees[] = $employeeId;
                    continue;
                }

                /*
                 * Do not generate an empty payslip.
                 */
                if (
                    !isset($payroll['gross_pay']) ||
                    (float)$payroll['gross_pay'] <= 0
                ) {
                    $skippedEmployees[] = $employeeId;
                    continue;
                }

                $payslipId =
                    $this->payrollModel->generatePayslip(
                        $runId,
                        $employeeId,
                        $payroll
                    );

                if ($payslipId > 0) {
                    $generatedPayslips[] = [
                        'employee_id' => $employeeId,
                        'payslip_id' => $payslipId
                    ];
                }
            }

            /*
             * Finalize the payroll run.
             */
            $finalized =
                $this->payrollModel->finalizeRun(
                    $runId,
                    $finalizedBy
                );

            if (!$finalized) {
                throw new Exception(
                    'Unable to finalize payroll run.'
                );
            }

            /*
             * Close the payroll period only after the run
             * has successfully been finalized.
             */
            $closed =
                $this->payrollModel->closePayrollPeriod(
                    $periodId
                );

            if (!$closed) {
                throw new Exception(
                    'Payroll run was finalized, but the payroll period could not be closed.'
                );
            }

            return [
                'run_id' => $runId,
                'period_id' => $periodId,
                'generated_payslips' => $generatedPayslips,
                'generated_count' => count($generatedPayslips),
                'skipped_employees' => $skippedEmployees,
                'skipped_count' => count($skippedEmployees),
                'status' => 'finalized'
            ];
        } catch (Throwable $e) {

            /*
             * The model does not currently provide a rollback/delete
             * method for a failed draft run, so do not pretend that
             * the run was rolled back.
             *
             * Re-throw the original exception so the controller's
             * AJAX layer can report the actual failure.
             */
            throw $e;
        }
    }


    /* ============================================================
       PAYSLIPS
       ============================================================ */

    /**
     * Get a complete payslip including earnings and deductions.
     */
    public function getPayslip(int $payslipId): ?array
    {
        return $this->payrollModel->getPayslipById(
            $payslipId
        );
    }
}
