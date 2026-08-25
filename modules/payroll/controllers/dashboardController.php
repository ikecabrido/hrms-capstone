<?php

require_once __DIR__ . '/../classes/dashboardModel.php';

class DashboardController
{
    private DashboardModel $model;


    public function __construct(PDO $db)
    {
        $this->model =
            new DashboardModel($db);
    }


    /* ============================================================
       COMPLETE DASHBOARD
       ============================================================ */

    /**
     * Get all data required by the payroll dashboard.
     *
     * This controller is read-only.
     *
     * It does not:
     * - calculate payroll
     * - create payroll runs
     * - finalize payroll
     * - modify employees
     * - modify payroll records
     */
    public function getStats(): array
    {
        /* --------------------------------------------------------
           EMPLOYEES
           -------------------------------------------------------- */

        $activeEmployees =
            $this->model
            ->getActiveEmployeeCount();


        $employeeByDepartment =
            $this->model
            ->getEmployeeDistributionByDepartment();


        $employeeByType =
            $this->model
            ->getEmployeeDistributionByEmploymentType();


        /* --------------------------------------------------------
           PAYROLL PERIOD
           -------------------------------------------------------- */

        $activePeriod =
            $this->model
            ->getActivePeriod();


        $latestPeriod =
            $this->model
            ->getLatestPeriod();


        /* --------------------------------------------------------
           LATEST FINALIZED PAYROLL
           -------------------------------------------------------- */

        $latestPayroll =
            $this->model
            ->getLatestFinalizedPayroll();


        /* --------------------------------------------------------
           CURRENT PAYROLL RUN
           -------------------------------------------------------- */

        $progress = [
            'total' => 0,
            'processed' => 0,
            'pending' => 0
        ];

        $currentRun = null;

        if ($activePeriod) {

            $currentRun =
                $this->model->getCurrentRun(
                    (int)$activePeriod['period_id']
                );

            if ($currentRun) {

                $progress =
                    $this->model->getRunProgress(
                        (int)$currentRun['run_id']
                    );
            }
        }


        /* --------------------------------------------------------
           PROCESSING PERCENTAGE
           -------------------------------------------------------- */

        $processingPercentage = 0;

        if (($progress['total'] ?? 0) > 0) {

            $processingPercentage =
                round(
                    (
                        $progress['processed']
                        /
                        $progress['total']
                    ) * 100,
                    1
                );
        }


        /* --------------------------------------------------------
           ADJUSTMENTS
           -------------------------------------------------------- */

        $adjustmentPeriodId = null;

        if ($activePeriod) {

            $adjustmentPeriodId =
                (int)$activePeriod['period_id'];
        } elseif ($latestPayroll) {

            $adjustmentPeriodId =
                (int)$latestPayroll['period_id'];
        }


        $adjustments =
            $this->model->getAdjustmentTotals(
                $adjustmentPeriodId
            );


        /* --------------------------------------------------------
           GRAPH 1
           PAYROLL TREND
           -------------------------------------------------------- */

        $payrollTrend =
            $this->model->getPayrollTrend();


        /* --------------------------------------------------------
           GRAPH 2
           PAYROLL COMPOSITION
           -------------------------------------------------------- */

        $payrollComposition =
            $this->model->getPayrollComposition();


        /* --------------------------------------------------------
           GRAPH 3
           DEDUCTION BREAKDOWN
           -------------------------------------------------------- */

        $deductionBreakdown =
            $this->model->getDeductionBreakdown();


        /* --------------------------------------------------------
           GRAPH 4
           PAYROLL BY DEPARTMENT
           -------------------------------------------------------- */

        $payrollByDepartment =
            $this->model->getPayrollByDepartment();


        /* --------------------------------------------------------
           GRAPH 5
           EMPLOYEE DISTRIBUTION BY DEPARTMENT
           -------------------------------------------------------- */

        $employeeDistributionByDepartment =
            $employeeByDepartment;


        /* --------------------------------------------------------
           GRAPH 6
           EMPLOYEE DISTRIBUTION BY EMPLOYMENT TYPE
           -------------------------------------------------------- */

        $employeeDistributionByType =
            $employeeByType;


        /* --------------------------------------------------------
           PAYROLL METRICS
           -------------------------------------------------------- */

        $averageNetPay =
            $this->model
            ->getAverageNetPay();


        $pendingRuns =
            $this->model
            ->getPendingRunCount();


        $finalizedRuns =
            $this->model
            ->getFinalizedRunCount();


        $lifetimeNetPayroll =
            $this->model
            ->getLifetimeNetPayroll();


        /* --------------------------------------------------------
           RECENT PAYROLL RUNS
           -------------------------------------------------------- */

        $recentRuns =
            $this->model
            ->getRecentPayrollRuns(5);


        /* --------------------------------------------------------
           RETURN DASHBOARD DATA
           -------------------------------------------------------- */

        return [

            /* ====================================================
               EMPLOYEE INFORMATION
               ==================================================== */

            'employees' => [

                'active' =>
                $activeEmployees,

                'by_department' =>
                $employeeDistributionByDepartment,

                'by_employment_type' =>
                $employeeDistributionByType
            ],


            /* ====================================================
               PAYROLL PERIOD
               ==================================================== */

            'period' => $activePeriod,

            'latest_period' =>
            $latestPeriod,


            /* ====================================================
               CURRENT PAYROLL RUN
               ==================================================== */

            'current_run' =>
            $currentRun,


            /* ====================================================
               LATEST FINALIZED PAYROLL
               ==================================================== */

            'latest_payroll' =>
            $latestPayroll,


            /* ====================================================
               PAYROLL KPI
               ==================================================== */

            'payroll' => [

                'gross' =>
                $latestPayroll['gross_pay']
                    ?? 0,

                'deductions' =>
                $latestPayroll['deductions']
                    ?? 0,

                'net' =>
                $latestPayroll['net_pay']
                    ?? 0,

                'average_net_pay' =>
                $averageNetPay,

                'lifetime_net_payroll' =>
                $lifetimeNetPayroll
            ],


            /* ====================================================
               PAYROLL PROCESSING
               ==================================================== */

            'progress' => [

                'total' =>
                $progress['total'],

                'processed' =>
                $progress['processed'],

                'pending' =>
                $progress['pending'],

                'percentage' =>
                $processingPercentage
            ],


            /* ====================================================
               MANUAL ADJUSTMENTS
               ==================================================== */

            'adjustments' => [

                'allowances' =>
                $adjustments['allowances'],

                'deductions' =>
                $adjustments['deductions']
            ],


            /* ====================================================
               GRAPH DATA
               ==================================================== */

            'graphs' => [

                /*
                 * Line chart:
                 * Gross / Deductions / Net
                 */
                'payroll_trend' =>
                $payrollTrend,


                /*
                 * Doughnut chart:
                 * Gross / Deductions / Net
                 */
                'payroll_composition' =>
                $payrollComposition,


                /*
                 * Doughnut/bar chart:
                 * SSS / PhilHealth / Pag-IBIG /
                 * Tax / Loans / Other
                 */
                'deduction_breakdown' =>
                $deductionBreakdown,


                /*
                 * Bar chart:
                 * Payroll cost by department
                 */
                'payroll_by_department' =>
                $payrollByDepartment,


                /*
                 * Bar/doughnut chart:
                 * Active employees by department
                 */
                'employees_by_department' =>
                $employeeDistributionByDepartment,


                /*
                 * Doughnut chart:
                 * Full-time / Part-time /
                 * Laboratory / OJT
                 */
                'employees_by_employment_type' =>
                $employeeDistributionByType
            ],


            /* ====================================================
               PAYROLL RUN COUNTERS
               ==================================================== */

            'pending_runs' =>
            $pendingRuns,

            'finalized_runs' =>
            $finalizedRuns,


            /* ====================================================
               RECENT PAYROLL ACTIVITY
               ==================================================== */

            'recent_runs' =>
            $recentRuns
        ];
    }


    /* ============================================================
       INDIVIDUAL DASHBOARD DATA METHODS
       ============================================================ */

    /**
     * Get payroll trend chart data.
     */
    public function getPayrollTrend(): array
    {
        return $this->model
            ->getPayrollTrend();
    }


    /**
     * Get payroll composition chart data.
     */
    public function getPayrollComposition(): array
    {
        return $this->model
            ->getPayrollComposition();
    }


    /**
     * Get deduction breakdown chart data.
     */
    public function getDeductionBreakdown(): array
    {
        return $this->model
            ->getDeductionBreakdown();
    }


    /**
     * Get payroll by department chart data.
     */
    public function getPayrollByDepartment(): array
    {
        return $this->model
            ->getPayrollByDepartment();
    }


    /**
     * Get employee distribution by department.
     */
    public function getEmployeeDistributionByDepartment(): array
    {
        return $this->model
            ->getEmployeeDistributionByDepartment();
    }


    /**
     * Get employee distribution by employment type.
     */
    public function getEmployeeDistributionByEmploymentType(): array
    {
        return $this->model
            ->getEmployeeDistributionByEmploymentType();
    }


    /**
     * Get current payroll period.
     */
    public function getActivePeriod(): ?array
    {
        return $this->model
            ->getActivePeriod();
    }


    /**
     * Get latest finalized payroll.
     */
    public function getLatestFinalizedPayroll(): array
    {
        return $this->model
            ->getLatestFinalizedPayroll();
    }


    /**
     * Get recent finalized payroll runs.
     */
    public function getRecentPayrollRuns(
        int $limit = 5
    ): array {

        return $this->model
            ->getRecentPayrollRuns($limit);
    }
}
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {

    require_once __DIR__ . '/../../../database/db.php';
    require_once __DIR__ . '/../../../auth/session.php';
    require_once __DIR__ . '/../../../auth/guard.php';

    header('Content-Type: application/json');

    function dash_respond(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        echo json_encode($payload);
        exit;
    }

    try {
        $db = (new Database())->getConnection();
        $controller = new DashboardController($db);
    } catch (Throwable $e) {
        dash_respond(['success' => false, 'message' => 'Unable to connect to the database.'], 500);
    }

    $action = $_REQUEST['action'] ?? 'stats';

    switch ($action) {

        case 'stats': {
                try {
                    $stats = $controller->getStats();
                    dash_respond(['success' => true, 'data' => $stats]);
                } catch (Throwable $e) {
                    dash_respond(['success' => false, 'message' => 'Failed to load dashboard data.'], 500);
                }
                break;
            }

        default:
            dash_respond(['success' => false, 'message' => 'Invalid or missing action.'], 400);
    }
}
