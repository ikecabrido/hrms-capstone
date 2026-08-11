<?php
require_once __DIR__ . '/../models/reportModel.php';
require_once __DIR__ . '/../../auth/auth.php';

class ReportController
{
    private ReportModel $model;
    private Auth $auth;

    public function __construct(PDO $db)
    {
        $this->model = new ReportModel($db);
        $this->auth = new Auth();
    }

    public function index(int $periodId = 0): array
    {
        $periods = $this->model->getPeriods();
        $summary = $this->model->getPayrollSummary($periodId);
        $payroll = [];
        $period = null;

        if ($periodId > 0) {
            $payroll = $this->model->getPayrollOverview($periodId);
            $period = $this->model->getPeriodById($periodId);
        }

        return [
            'periods' => $periods,
            'payroll' => $payroll,
            'period' => $period
        ];
    }
}
