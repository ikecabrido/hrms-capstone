<?php
require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/payrollPeriodModel.php';

class PeriodController
{
    private PDO $db;
    private PayrollPeriodModel $periodModel;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->periodModel = new PayrollPeriodModel($this->db);
    }

    public function index()
    {
        return $this->periodModel->getAll();
    }

    public function create(array $data): bool
    {
        return $this->periodModel->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->periodModel->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->periodModel->delete($id);
    }

    public function getById(int $id): ?array
    {
        return $this->periodModel->getById($id);
    }

    public function getNextPeriod(): array
    {
        return $this->periodModel->generateNextPeriod();
    }
    public function updateStatus(int $id, string $status): bool
    {
        return $this->periodModel->updateStatus($id, $status);
    }
    public function close(int $id): bool
    {
        return $this->periodModel->close($id);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {

    require_once __DIR__ . '/../../../auth/session.php';
    require_once __DIR__ . '/../../../auth/guard.php';

    header('Content-Type: application/json');

    function pm_respond(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        echo json_encode($payload);
        exit;
    }

    function pm_validate_period_data(array $data): ?string
    {
        $name  = trim($data['period_name'] ?? '');
        $start = trim($data['start_date'] ?? '');
        $end   = trim($data['end_date'] ?? '');
        $pay   = trim($data['pay_date'] ?? '');

        if ($name === '' || $start === '' || $end === '' || $pay === '') {
            return 'Please fill in all required fields.';
        }

        $dateRe = '/^\d{4}-\d{2}-\d{2}$/';
        foreach (['start_date' => $start, 'end_date' => $end, 'pay_date' => $pay] as $label => $val) {
            if (!preg_match($dateRe, $val)) {
                return "Invalid date format for {$label}.";
            }
        }

        $startTs = strtotime($start);
        $endTs   = strtotime($end);
        $payTs   = strtotime($pay);

        if ($startTs === false || $endTs === false || $payTs === false) {
            return 'One or more dates are invalid.';
        }

        if ($startTs > $endTs) {
            return 'Start date must be on or before the end date.';
        }

        if ($payTs < $endTs) {
            return 'Pay date must be on or after the end date.';
        }

        return null; // valid
    }

    $controller = new PeriodController();
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {

        case 'list': {
                $periods = $controller->index();

                $summary = [
                    'total'  => count($periods),
                    'open'   => 0,
                    'closed' => 0,
                    'current' => null,
                ];

                foreach ($periods as $p) {
                    if (($p['status'] ?? '') === 'open') {
                        $summary['open']++;
                        if ($summary['current'] === null) {
                            $summary['current'] = $p;
                        }
                    } elseif (($p['status'] ?? '') === 'closed') {
                        $summary['closed']++;
                    }
                }

                pm_respond(['success' => true, 'data' => $periods, 'summary' => $summary]);
                break;
            }

        case 'get': {
                $id = intval($_GET['id'] ?? 0);
                if (!$id) {
                    pm_respond(['success' => false, 'message' => 'No period ID provided.']);
                }

                $period = $controller->getById($id);
                if (!$period) {
                    pm_respond(['success' => false, 'message' => 'Payroll period not found.']);
                }

                pm_respond(['success' => true, 'data' => $period]);
                break;
            }

        case 'next_period': {
                try {
                    $preview = $controller->getNextPeriod();
                    pm_respond(['success' => true, 'data' => $preview]);
                } catch (Throwable $e) {
                    pm_respond(['success' => false, 'message' => 'Failed to generate the next payroll period.']);
                }
                break;
            }

        case 'create': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    pm_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $error = pm_validate_period_data($_POST);
                if ($error) {
                    pm_respond(['success' => false, 'message' => $error]);
                }

                $data = [
                    'period_name' => trim($_POST['period_name']),
                    'start_date'  => trim($_POST['start_date']),
                    'end_date'    => trim($_POST['end_date']),
                    'pay_date'    => trim($_POST['pay_date']),
                ];

                try {
                    $ok = $controller->create($data);
                } catch (Throwable $e) {
                    $ok = false;
                }

                if ($ok) {
                    pm_respond(['success' => true, 'message' => 'Payroll period created successfully.']);
                }
                pm_respond(['success' => false, 'message' => 'Failed to create payroll period.']);
                break;
            }

        case 'update': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    pm_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $id = intval($_POST['period_id'] ?? 0);
                if (!$id) {
                    pm_respond(['success' => false, 'message' => 'No period ID provided.']);
                }

                $existing = $controller->getById($id);
                if (!$existing) {
                    pm_respond(['success' => false, 'message' => 'Payroll period not found.']);
                }

                if ($existing['status'] !== 'open') {
                    pm_respond(['success' => false, 'message' => 'Period cannot be edited because it is closed.']);
                }

                $error = pm_validate_period_data($_POST);
                if ($error) {
                    pm_respond(['success' => false, 'message' => $error]);
                }

                $data = [
                    'period_name' => trim($_POST['period_name']),
                    'start_date'  => trim($_POST['start_date']),
                    'end_date'    => trim($_POST['end_date']),
                    'pay_date'    => trim($_POST['pay_date']),
                ];

                try {
                    $ok = $controller->update($id, $data);
                } catch (Throwable $e) {
                    $ok = false;
                }

                if ($ok) {
                    pm_respond(['success' => true, 'message' => 'Payroll period updated successfully.']);
                }
                pm_respond(['success' => false, 'message' => 'Failed to update payroll period.']);
                break;
            }

        case 'close': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    pm_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $id = intval($_POST['period_id'] ?? 0);
                if (!$id) {
                    pm_respond(['success' => false, 'message' => 'No period ID provided.']);
                }

                $existing = $controller->getById($id);
                if (!$existing) {
                    pm_respond(['success' => false, 'message' => 'Payroll period not found.']);
                }

                if ($existing['status'] !== 'open') {
                    pm_respond(['success' => false, 'message' => 'This payroll period is already closed.']);
                }

                try {
                    $ok = $controller->close($id);
                } catch (Throwable $e) {
                    $ok = false;
                }

                if ($ok) {
                    pm_respond(['success' => true, 'message' => 'Payroll period closed successfully.']);
                }
                pm_respond(['success' => false, 'message' => 'Failed to close payroll period.']);
                break;
            }

        case 'delete': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    pm_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $id = intval($_POST['period_id'] ?? 0);
                if (!$id) {
                    pm_respond(['success' => false, 'message' => 'No period ID provided.']);
                }

                $existing = $controller->getById($id);
                if (!$existing) {
                    pm_respond(['success' => false, 'message' => 'Payroll period not found.']);
                }

                if ($existing['status'] !== 'open') {
                    pm_respond(['success' => false, 'message' => 'Closed payroll periods cannot be deleted.']);
                }

                try {
                    $ok = $controller->delete($id);
                } catch (Throwable $e) {
                    $ok = false;
                }

                if ($ok) {
                    pm_respond(['success' => true, 'message' => 'Payroll period deleted successfully.']);
                }
                pm_respond(['success' => false, 'message' => 'Period cannot be deleted because payroll processing already exists for it.']);
                break;
            }

        default:
            pm_respond(['success' => false, 'message' => 'Invalid or missing action.'], 400);
    }
}
