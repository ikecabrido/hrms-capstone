<?php
require_once __DIR__ . '/../../auth/database.php';
require_once __DIR__ . '/../models/payslipModel.php';
class PayslipController
{

    private PayslipModel $model;

    public function __construct()
    {
        $db = Database::getInstance()->getConnection();
        $this->model = new PayslipModel($db);
    }

    public function index($periodId = null, $employeeId = null)
    {
        return $this->model->getAll($periodId, $employeeId);
    }

    public function show(int $id)
    {
        return $this->model->getById($id);
    }
}
