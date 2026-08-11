<?php

require_once __DIR__ . '/../models/dashboardModel.php';

class DashboardController
{
    private DashboardModel $model;

    public function __construct($db)
    {
        $this->model = new DashboardModel($db);
    }



    public function getStats()
    {
        $stats = [
            'employees' => $this->model->getEmployeeCount(),
            'period' => null,
            'total_payroll' => 0,
            'progress' => [
                'processed' => 0,
                'total' => 0
            ],

            // IMPORTANT
            'chart' => $this->model->getMonthlyTotals(),
            'lifetime' => $this->model->getLifetimePayroll(),
            'average_salary' => $this->model->getAverageSalary(),
            'total_allowances' => 0,
            'total_deductions' => 0
        ];

        /* ===== ACTIVE PERIOD (optional) ===== */

        $period = $this->model->getActivePeriod();

        if ($period) {

            $stats['period'] = $period;

            $run = $this->model->getCurrentRun($period['period_id']);

            if ($run) {

                $runId = $run['id'];

                $stats['progress'] =
                    $this->model->getRunProgress($runId);



                $total = $this->model->getLatestFinalizedRun();


                $stats['total_payroll'] = $total['totals'] ?? 0;
            } else {
                // No run for active period, show latest finalized run stats instead
                $latestRun = $this->model->getLatestFinalizedRunWithDetails();
                if ($latestRun) {
                    $stats['progress'] = [
                        'total' => $latestRun['total_employees'],
                        'processed' => $latestRun['processed']
                    ];
                    $stats['total_payroll'] = $latestRun['total_payroll'];
                    $stats['period'] = [
                        'period_name' => $latestRun['period_name'],
                        'start_date' => $latestRun['start_date'],
                        'end_date' => $latestRun['end_date']
                    ];
                }
            }

            // Add allowances and deductions for the period
            $stats['total_allowances'] = $this->model->getTotalAllowances($period['period_id']);
            $stats['total_deductions'] = $this->model->getTotalDeductions($period['period_id']);
        } else {
            // No active period, show latest finalized run stats
            $latestRun = $this->model->getLatestFinalizedRunWithDetails();
            if ($latestRun) {
                $stats['progress'] = [
                    'total' => $latestRun['total_employees'],
                    'processed' => $latestRun['processed']
                ];
                $stats['total_payroll'] = $latestRun['total_payroll'];
                $stats['period'] = [
                    'period_name' => $latestRun['period_name'],
                    'start_date' => $latestRun['start_date'],
                    'end_date' => $latestRun['end_date']
                ];
            }
        }

        return $stats;
    }
}
