<?php

require_once __DIR__ . '/../../auth/database.php';
require_once __DIR__ . '/../models/AllowanceDeductionModel.php';
require_once __DIR__ . '/../controllers/payrollController.php';

class AllowanceDeductionController
{
    private AllowanceDeductionModel $model;
    private PayrollController $payrollController;

    public function __construct()
    {
        $db = Database::getInstance()->getConnection();
        $this->model = new AllowanceDeductionModel($db);
        $this->payrollController = new PayrollController();
    }

    public function getEmployees()
    {
        return $this->model->getEmployees();
    }

    public function index($periodId = null, $employeeId = null)
    {
        return $this->model->getRecords($periodId, $employeeId);
    }

    public function getTotals($periodId = null, $employeeId = null)
    {
        return $this->model->getTotals($periodId, $employeeId);
    }

    public function store($data)
    {
        return $this->model->store($data);
    }
    public function close(int $periodId)
    {
        return $this->model->isPeriodClosed($periodId);
    }
    public function handleRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (isset($_POST['add'])) {
            $this->add();
        }

        if (isset($_POST['edit'])) {
            $this->edit();
        }

        if (isset($_POST['delete'])) {
            $this->delete();
        }
    }
    private function add()
    {
        $employeeId = (int) $_POST['employee_id'];
        $type       = $_POST['type'];
        $desc       = trim($_POST['description']);
        $amount     = (float) $_POST['amount'];
        $periodId   = (int) $_POST['period_id'];

        // Prevent edit if closed
        if ($this->payrollController->isClosed($periodId)) {
            $_SESSION['error'] = "Payroll period is closed.";
            $this->redirect();
        }

        $this->model->addAdjustment(
            $employeeId,
            $type,
            $desc,
            $amount,
            $periodId
        );

        $_SESSION['success'] = "Adjustment added.";

        $this->redirect();
    }
    private function edit()
    {
        $id     = (int) $_POST['id'];
        $desc   = trim($_POST['description']);
        $amount = (float) $_POST['amount'];

        $this->model->updateAdjustment($id, $desc, $amount);

        $_SESSION['success'] = "Adjustment updated.";

        $this->redirect();
    }
    private function delete()
    {
        $id = (int) $_POST['id'];

        $this->model->deleteAdjustment($id);

        $_SESSION['success'] = "Adjustment deleted.";

        $this->redirect();
    }
    private function redirect()
    {
        header("Location: allowance.php");
        exit;
    }
}
