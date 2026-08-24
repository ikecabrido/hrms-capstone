<?php
require_once __DIR__ . '/KpiModel.php';

class KpiController
{
    private $model;

    public function __construct($pdo = null)
    {
        $this->model = new KpiModel($pdo);
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $action = trim((string) ($_POST['action'] ?? ''));

        if ($action === 'add_kpi') {
            $data = [
                'employee_id' => (int) ($_POST['employee_id'] ?? 0),
                'department_id' => (int) ($_POST['department_id'] ?? 0),
                'kpi_title' => trim((string) ($_POST['kpi_title'] ?? '')),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'target_value' => (float) ($_POST['target_value'] ?? 0),
                'actual_value' => (float) ($_POST['actual_value'] ?? 0),
                'unit' => trim((string) ($_POST['unit'] ?? '%')),
                'weight' => (float) ($_POST['weight'] ?? 0),
                'start_date' => trim((string) ($_POST['start_date'] ?? '')),
                'due_date' => trim((string) ($_POST['due_date'] ?? '')),
            ];

            $errors = $this->validateKpi($data);
            if (!empty($errors)) {
                $_SESSION['kpi_message'] = implode(' ', $errors);
                $_SESSION['kpi_message_type'] = 'error';
                $this->redirect();
            }

            if ($this->model->createKpi($data)) {
                $_SESSION['kpi_message'] = '';
                $_SESSION['kpi_message_type'] = 'success';
            } else {
                $_SESSION['kpi_message'] = 'Unable to create the KPI record.';
                $_SESSION['kpi_message_type'] = 'error';
            }

            $this->redirect();
        }

        if ($action === 'update_kpi') {
            $kpiId = (int) ($_POST['kpi_id'] ?? 0);
            if ($kpiId <= 0) {
                $_SESSION['kpi_message'] = 'Select a valid KPI to update.';
                $_SESSION['kpi_message_type'] = 'error';
                $this->redirect();
            }

            $data = [
                'employee_id' => (int) ($_POST['employee_id'] ?? 0),
                'department_id' => (int) ($_POST['department_id'] ?? 0),
                'kpi_title' => trim((string) ($_POST['kpi_title'] ?? '')),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'target_value' => (float) ($_POST['target_value'] ?? 0),
                'actual_value' => (float) ($_POST['actual_value'] ?? 0),
                'unit' => trim((string) ($_POST['unit'] ?? '%')),
                'weight' => (float) ($_POST['weight'] ?? 0),
                'start_date' => trim((string) ($_POST['start_date'] ?? '')),
                'due_date' => trim((string) ($_POST['due_date'] ?? '')),
                'status' => trim((string) ($_POST['status'] ?? '')),
            ];

            $errors = $this->validateKpi($data);
            if (!empty($errors)) {
                $_SESSION['kpi_message'] = implode(' ', $errors);
                $_SESSION['kpi_message_type'] = 'error';
                $this->redirect();
            }

            if ($this->model->updateKpi($kpiId, $data)) {
                $_SESSION['kpi_message'] = 'KPI updated successfully.';
                $_SESSION['kpi_message_type'] = 'success';
            } else {
                $_SESSION['kpi_message'] = 'Unable to update the KPI record.';
                $_SESSION['kpi_message_type'] = 'error';
            }

            $this->redirect();
        }

        $_SESSION['kpi_message'] = 'Unsupported KPI action.';
        $_SESSION['kpi_message_type'] = 'error';
        $this->redirect();
    }

    private function validateKpi(array $data): array
    {
        $errors = [];

        if ((int) ($data['employee_id'] ?? 0) <= 0) {
            $errors[] = 'Please select an employee.';
        }

        if (trim((string) ($data['kpi_title'] ?? '')) === '') {
            $errors[] = 'KPI title is required.';
        }

        if ((float) ($data['target_value'] ?? 0) <= 0) {
            $errors[] = 'Target value must be greater than zero.';
        }

        if ((float) ($data['weight'] ?? 0) < 0) {
            $errors[] = 'Weight cannot be negative.';
        }

        return $errors;
    }

    private function redirect(): void
    {
        header('Location: ?page=kpi-tracking');
        exit;
    }

    public function getDashboardData(array $filters = []): array
    {
        $this->model->ensureSchema();

        $summary = $this->model->getDashboardStats();
        $departments = $this->model->getDepartments();
        $employees = $this->model->getEmployees();
        $kpis = $this->model->getKpiRows($filters);
        $distribution = $this->model->getStatusDistribution($filters);
        $trend = $this->model->getMonthlyTrend($filters['semester'] ?? 'this-semester');
        $historyMap = $this->model->getKpiHistoryMap();

        $selectedEmployeeId = !empty($filters['employee_id']) ? (int) $filters['employee_id'] : null;
        if ($selectedEmployeeId === null && !empty($kpis)) {
            $selectedEmployeeId = (int) ($kpis[0]['employee_id'] ?? 0);
        }

        $employeeSummary = $this->model->getEmployeeSummary($selectedEmployeeId);

        if (!isset($_SESSION['kpi_message'])) {
            $_SESSION['kpi_message'] = '';
            $_SESSION['kpi_message_type'] = 'info';
        }

        return [
            'summary' => $summary,
            'departments' => $departments,
            'employees' => $employees,
            'kpis' => $kpis,
            'status_distribution' => $distribution,
            'monthly_trend' => $trend,
            'employee_summary' => $employeeSummary,
            'history_map' => $historyMap,
            'chart_labels' => $trend['labels'],
            'chart_values' => $trend['data'],
            'message' => $_SESSION['kpi_message'] ?? '',
            'message_type' => $_SESSION['kpi_message_type'] ?? 'info',
        ];
    }

    public function getMessage(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $message = $_SESSION['kpi_message'] ?? '';
        $type = $_SESSION['kpi_message_type'] ?? 'info';

        unset($_SESSION['kpi_message'], $_SESSION['kpi_message_type']);

        return ['message' => $message, 'type' => $type];
    }
}
