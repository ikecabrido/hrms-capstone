<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/payslipModel.php';

class PayslipController
{
    private PayslipModel $model;

    public function __construct()
    {
        $db = (new Database())->getConnection();

        $this->model = new PayslipModel($db);
    }


    /* ============================================================
       PAYSLIP LIST
       ============================================================ */

    /**
     * Get payslips for the payslip page.
     *
     * Optional filters:
     * - periodId
     * - employeeId
     */
    public function index(
        ?int $periodId = null,
        ?int $employeeId = null
    ): array {

        return $this->model->getAll(
            $periodId,
            $employeeId
        );
    }


    /* ============================================================
       SINGLE PAYSLIP
       ============================================================ */

    /**
     * Get one complete payslip.
     */
    public function show(int $payslipId): ?array
    {
        return $this->model->getById(
            $payslipId
        );
    }


    /* ============================================================
       EMPLOYEE + PERIOD PAYSLIP
       ============================================================ */

    /**
     * Get a specific employee's payslip
     * for a specific payroll period.
     */
    public function getByEmployeeAndPeriod(
        int $employeeId,
        int $periodId
    ): ?array {

        return $this->model->getByEmployeeAndPeriod(
            $employeeId,
            $periodId
        );
    }


    /* ============================================================
       SUMMARY
       ============================================================ */

    /**
     * Get payslip summary for the payslip page.
     *
     * Returns:
     * - payslip count
     * - total gross pay
     * - total deductions
     * - total net pay
     */
    public function getSummary(
        ?int $periodId = null
    ): array {

        return $this->model->getSummary(
            $periodId
        );
    }


    /* ============================================================
       COUNTS
       ============================================================ */

    /**
     * Get the number of generated payslips.
     */
    public function getCount(
        ?int $periodId = null
    ): int {

        return $this->model->getCount(
            $periodId
        );
    }


    /* ============================================================
       TOTAL GROSS PAY
       ============================================================ */

    public function getTotalGrossPay(
        ?int $periodId = null
    ): float {

        return $this->model->getTotalGrossPay(
            $periodId
        );
    }


    /* ============================================================
       TOTAL DEDUCTIONS
       ============================================================ */

    public function getTotalDeductions(
        ?int $periodId = null
    ): float {

        return $this->model->getTotalDeductions(
            $periodId
        );
    }


    /* ============================================================
       TOTAL NET PAY
       ============================================================ */

    public function getTotalNetPay(
        ?int $periodId = null
    ): float {

        return $this->model->getTotalNetPay(
            $periodId
        );
    }
    public function getFilterEmployees(): array
    {
        return $this->model->getFilterEmployees();
    }
}
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {

    require_once __DIR__ . '/../../../auth/session.php';
    require_once __DIR__ . '/../../../auth/guard.php';

    header('Content-Type: application/json');

    function ps_respond(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        echo json_encode($payload);
        exit;
    }

    $controller = new PayslipController();
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {

        /*
         * ------------------------------------------------------
         * LIST PAYSLIPS (+ summary for the current filter)
         * ------------------------------------------------------
         */
        case 'list': {
                $periodId   = !empty($_GET['period_id']) ? (int)$_GET['period_id'] : null;
                $employeeId = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

                try {
                    $payslips = $controller->index($periodId, $employeeId);
                    $summary  = $controller->getSummary($periodId);

                    ps_respond([
                        'success' => true,
                        'data' => $payslips,
                        'summary' => $summary
                    ]);
                } catch (Throwable $e) {
                    ps_respond([
                        'success' => false,
                        'message' => $e->getMessage()
                    ], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * SINGLE PAYSLIP (full detail: employee, earnings, deductions)
         * ------------------------------------------------------
         */
        case 'get': {
                $id = intval($_GET['id'] ?? 0);
                $payslip = [];
                if (!$id) {
                    ps_respond(['success' => false, 'message' => 'No payslip ID provided.']);
                }

                try {
                    $payslip = $controller->show($id);
                } catch (Throwable $e) {
                    ps_respond([
                        'success' => false,
                        'message' => 'Unable to load this payslip. Please try again.'
                    ], 500);
                }

                if (!$payslip) {
                    ps_respond(['success' => false, 'message' => 'Payslip not found.'], 404);
                }

                ps_respond(['success' => true, 'data' => $payslip]);
                break;
            }

            /*
         * ------------------------------------------------------
         * EMPLOYEE FILTER OPTIONS
         * ------------------------------------------------------
         */
        case 'employees': {
                try {
                    $employees = $controller->getFilterEmployees();
                    ps_respond(['success' => true, 'data' => $employees]);
                } catch (Throwable $e) {
                    ps_respond([
                        'success' => false,
                        'message' => 'Unable to load employees. Please try again.'
                    ], 500);
                }
                break;
            }

        default: {
                ps_respond(['success' => false, 'message' => 'Unknown action.'], 400);
            }
    }
}
