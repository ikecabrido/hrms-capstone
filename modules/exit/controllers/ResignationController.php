<?php

require_once __DIR__ . '/../models/ResignationModel.php';

class ResignationController extends ExitManagementController
{
    private ResignationModel $resignationModel;

    public function __construct()
    {
        parent::__construct();
        $this->resignationModel = new ResignationModel();
    }

    /**
     * Submit resignation
     */
    public function submitResignation(array $data): array
    {
        try {
            // Validate required fields
            $required = ['employee_id', 'resignation_type', 'reason', 'notice_date', 'last_working_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }

            // Check employee eligibility
            $eligibility = $this->resignationModel->checkEmployeeEligibility($data['employee_id']);
            if (!$eligibility['eligible']) {
                return ['success' => false, 'message' => $eligibility['reason']];
            }

            // Add submitted_by from session
            $data['submitted_by'] = $_SESSION['employee_id'] ?? 0;

            $resignationId = $this->resignationModel->submitResignation($data);

            return [
                'success' => true,
                'message' => 'Resignation submitted successfully.',
                'resignation_id' => $resignationId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get resignation details
     */
    public function getResignation(int $resignationId): array
    {
        $resignation = $this->resignationModel->getResignationById($resignationId);

        if (!$resignation) {
            return ['error' => 'Resignation not found'];
        }

        return $resignation;
    }

    /**
     * Get employee's last attendance date from ta_attendance
     */
    public function getEmployeeLastAttendanceDate(string $employeeId): array
    {
        try {
            $lastAttendanceDate = $this->resignationModel->getEmployeeLastAttendanceDate($employeeId);
            
            return [
                'success' => true,
                'last_attendance_date' => $lastAttendanceDate,
                'employee_id' => $employeeId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'last_attendance_date' => null
            ];
        }
    }

    /**
     * Approve or reject resignation
     */
    public function processResignation(int $resignationId, string $action, int $approvedBy, string $status = '', ?string $comments = null): array
    {
        try {
            $resignation = $this->resignationModel->getResignationById($resignationId);
            if (!$resignation) {
                return ['success' => false, 'message' => 'Resignation not found'];
            }

            $currentStatus = $resignation['status'] ?? '';
            $targetStatus = $status;

            if (empty($targetStatus)) {
                if ($action === 'approve') {
                    if ($currentStatus === 'pending_review') {
                        $targetStatus = 'pending_legal_review';
                    } elseif ($currentStatus === 'pending_legal_review') {
                        $targetStatus = 'approved';
                    }
                } elseif ($action === 'reject') {
                    if ($currentStatus === 'pending_review') {
                        $targetStatus = 'rejected';
                    } elseif ($currentStatus === 'pending_legal_review') {
                        $targetStatus = 'rejected_by_legal';
                    }
                }
            }

            if (empty($targetStatus)) {
                return ['success' => false, 'message' => 'Invalid resignation action for the current status'];
            }

            $success = $this->resignationModel->updateResignationStatus($resignationId, $targetStatus, $approvedBy, $comments);

            if ($success) {
                return [
                    'success' => true,
                    'message' => "Resignation updated to $targetStatus successfully"
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update resignation status'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get pending resignations
     */
    public function getPendingResignations(): array
    {
        return $this->resignationModel->getResignations('pending');
    }

    /**
     * Get resignations by status
     */
    public function getResignations(?string $status = null): array
    {
        return $this->resignationModel->getResignations($status);
    }

    /**
     * Get archived resignations with pagination and search
     */
    public function getArchivedResignations(int $page = 1, int $limit = 10, string $search = ''): array
    {
        return $this->resignationModel->getResignations('archived', $page, $limit, $search);
    }

    /**
     * Unarchive resignation
     */
    public function unarchiveResignation(int $resignationId): array
    {
        try {
            $success = $this->resignationModel->unarchiveResignation($resignationId);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Resignation unarchived successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to unarchive resignation'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Archive resignation
     */
    public function archiveResignation(int $resignationId): array
    {
        try {
            $archiveReason = $_POST['archive_reason'] ?? 'Manual archive';
            $success = $this->resignationModel->archiveResignation($resignationId, $archiveReason);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Resignation archived successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to archive resignation'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get resignation details for modal
     */
    public function getResignationDetails(int $resignationId): array
    {
        try {
            $resignation = $this->resignationModel->getResignationById($resignationId);
            if ($resignation) {
                return [
                    'success' => true,
                    'data' => [
                        'id' => $resignation['id'],
                        'employee_id' => $resignation['emp_id'],
                        'employee_name' => $resignation['employee_name'],
                        'resignation_type' => $resignation['resignation_type'],
                        'reason' => $resignation['reason']
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Resignation not found'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Render printable resignation record page
     */
    public function renderResignationPrintPage(int $resignationId): string
    {
        $res = $this->resignationModel->getResignationById($resignationId);
        if (!$res) {
            return '<!doctype html><html><head><title>Resignation Not Found</title></head><body><h1>Resignation not found</h1></body></html>';
        }

        $employeeName = htmlspecialchars($res['employee_name'] ?? 'Unknown', ENT_QUOTES);
        $resignationType = htmlspecialchars($res['resignation_type'] ?? 'N/A', ENT_QUOTES);
        $reason = htmlspecialchars($res['reason'] ?? '', ENT_QUOTES);
        $noticeDate = htmlspecialchars($res['notice_date'] ?? 'N/A', ENT_QUOTES);
        $lastWorking = htmlspecialchars($res['last_working_date'] ?? 'N/A', ENT_QUOTES);
        $status = htmlspecialchars(isset($res['status']) ? ucwords(str_replace('_', ' ', $res['status'])) : 'N/A', ENT_QUOTES);

        $header = '<div style="border-bottom:2px solid #1f5fbf;padding-bottom:12px;margin-bottom:18px;display:flex;align-items:center"><img src="/capstone_hr_management_system2/assets/pics/bcpLogo.png" style="width:80px;height:80px;margin-right:16px"><div><h2 style="margin:0;color:#174a8b">Resignation Record</h2><div style="font-size:12px;color:#333">Bestlink College of the Philippines - Bulacan Campus</div></div></div>';

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Resignation Record</title><style>body{font-family:Arial,Helvetica,sans-serif;padding:20px;color:#172b4d}table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #ddd}th{background:#f4f4f4;text-align:left}</style></head><body>' . $header . '<table><tbody>' .
            '<tr><th>Employee</th><td>' . $employeeName . '</td></tr>' .
            '<tr><th>Resignation Type</th><td>' . $resignationType . '</td></tr>' .
            '<tr><th>Reason</th><td>' . $reason . '</td></tr>' .
            '<tr><th>Notice Date</th><td>' . $noticeDate . '</td></tr>' .
            '<tr><th>Last Working Day</th><td>' . $lastWorking . '</td></tr>' .
            '<tr><th>Status</th><td>' . $status . '</td></tr>' .
            '</tbody></table></body></html>';

        return $html;
    }

    /**
     * Check employee eligibility for resignation
     */
    public function checkEmployeeEligibility(string $employeeId): array
    {
        try {
            if (empty($employeeId)) {
                return ['success' => false, 'message' => 'Employee ID is required'];
            }

            $eligibility = $this->resignationModel->checkEmployeeEligibility($employeeId);

            return [
                'success' => $eligibility['eligible'],
                'message' => $eligibility['reason']
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle AJAX requests for resignations
     */
    public function handleAjaxRequest(string $action, array $data = []): array
    {
        switch ($action) {
            case 'submit_resignation':
                return ['success' => false, 'message' => 'Resignation creation is managed through the Employee Portal.'];
            case 'update_resignation':
                return $this->processResignation(
                    $data['resignation_id'] ?? 0,
                    $data['action'] ?? '',
                    $data['approved_by'] ?? ($_SESSION['employee_id'] ?? 0),
                    $data['status'] ?? '',
                    $data['comments'] ?? $data['approval_comments'] ?? null
                );

            case 'get_resignation':
                return $this->getResignation($data['resignation_id'] ?? 0);

            case 'process_resignation':
                return $this->processResignation(
                    $data['resignation_id'] ?? 0,
                    $data['action'] ?? '',
                    $data['approved_by'] ?? ($_SESSION['employee_id'] ?? 0),
                    $data['status'] ?? '',
                    $data['comments'] ?? null
                );

            case 'get_pending_resignations':
                return $this->getPendingResignations();

            case 'get_resignations':
                $status = $data['status'] ?? null;
                if ($status === null || $status === '') {
                    $status = 'active';
                }

                $page = (int)($data['page'] ?? 1);
                $limit = (int)($data['limit'] ?? 10);
                $search = $data['search'] ?? '';

                if ($status === 'archived') {
                    return $this->getArchivedResignations($page, $limit, $search);
                }
                if ($status === 'all') {
                    return $this->resignationModel->getResignations('all', $page, $limit, $search);
                }
                return $this->resignationModel->getResignations($status, $page, $limit, $search);

            case 'get_archived_resignations':
                $page = (int)($data['page'] ?? 1);
                $limit = (int)($data['limit'] ?? 10);
                $search = $data['search'] ?? '';
                return $this->getArchivedResignations($page, $limit, $search);

            case 'get_resignation_details':
                return $this->getResignationDetails($data['resignation_id'] ?? 0);

            case 'archive_resignation':
                return $this->archiveResignation($data['resignation_id'] ?? 0);

            case 'unarchive_resignation':
                return $this->unarchiveResignation($data['resignation_id'] ?? 0);

            case 'check_eligibility':
                return $this->checkEmployeeEligibility($data['employee_id'] ?? '');

            case 'get_employee_last_attendance_date':
                return $this->getEmployeeLastAttendanceDate($data['employee_id'] ?? '');

            default:
                return parent::handleAjaxRequest($action, $data);
        }
    }
}