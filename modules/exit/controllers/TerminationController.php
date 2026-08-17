<?php

require_once __DIR__ . '/../models/TerminationModel.php';

class TerminationController extends ExitManagementController
{
    private TerminationModel $terminationModel;

    public function __construct()
    {
        parent::__construct();
        $this->terminationModel = new TerminationModel();
    }

    public function submitTermination(array $data): array
    {
        try {
            $required = ['employee_id', 'termination_reason', 'effective_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }

            $eligibility = $this->terminationModel->checkEmployeeEligibility($data['employee_id']);
            if (!$eligibility['eligible']) {
                return ['success' => false, 'message' => $eligibility['reason']];
            }

            $data['submitted_by'] = $_SESSION['employee_id'] ?? 0;
            $terminationId = $this->terminationModel->submitTermination($data);

            return [
                'success' => true,
                'message' => 'Termination submitted successfully.',
                'termination_id' => $terminationId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getTermination(int $terminationId): array
    {
        $termination = $this->terminationModel->getTerminationById($terminationId);
        if (!$termination) {
            return ['error' => 'Termination not found'];
        }

        return $termination;
    }

    public function processTermination(int $terminationId, string $action, int $approvedBy, string $status = '', ?string $comments = null): array
    {
        try {
            $termination = $this->terminationModel->getTerminationById($terminationId);
            if (!$termination) {
                return ['success' => false, 'message' => 'Termination not found'];
            }

            $currentStatus = $termination['status'] ?? '';
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
                return ['success' => false, 'message' => 'Invalid termination action for the current status'];
            }

            $success = $this->terminationModel->updateTerminationStatus($terminationId, $targetStatus, $approvedBy, $comments);
            if ($success) {
                return ['success' => true, 'message' => "Termination updated to $targetStatus successfully"];
            }

            return ['success' => false, 'message' => 'Failed to update termination status'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getTerminations(?string $status = null, int $page = 1, int $limit = 10, string $search = ''): array
    {
        return $this->terminationModel->getTerminations($status, $page, $limit, $search);
    }

    public function getArchivedTerminations(int $page = 1, int $limit = 10, string $search = ''): array
    {
        return $this->terminationModel->getArchivedTerminations($page, $limit, $search);
    }

    public function checkEmployeeEligibility(string $employeeId): array
    {
        try {
            if (empty($employeeId)) {
                return ['success' => false, 'message' => 'Employee ID is required'];
            }

            $eligibility = $this->terminationModel->checkEmployeeEligibility($employeeId);
            return ['success' => $eligibility['eligible'], 'message' => $eligibility['reason']];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function archiveTermination(int $terminationId): array
    {
        try {
            $archiveReason = $_POST['archive_reason'] ?? 'Manual archive';
            $success = $this->terminationModel->archiveTermination($terminationId, $archiveReason);

            if ($success) {
                return ['success' => true, 'message' => 'Termination archived successfully'];
            }

            return ['success' => false, 'message' => 'Failed to archive termination'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function unarchiveTermination(int $terminationId): array
    {
        try {
            $success = $this->terminationModel->unarchiveTermination($terminationId);
            if ($success) {
                return ['success' => true, 'message' => 'Termination unarchived successfully'];
            }

            return ['success' => false, 'message' => 'Failed to unarchive termination'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getTerminationDetails(int $terminationId): array
    {
        try {
            $termination = $this->terminationModel->getTerminationById($terminationId);
            if (!$termination) {
                return ['success' => false, 'message' => 'Termination not found'];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $termination['id'],
                    'employee_id' => $termination['employee_id'],
                    'employee_name' => $termination['employee_name'],
                    'termination_reason' => $termination['termination_reason'],
                    'effective_date' => $termination['effective_date'],
                    'comments' => $termination['comments'] ?? '',
                    'status' => $termination['status'] ?? 'pending_review'
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function handleAjaxRequest(string $action, array $data = []): array
    {
        switch ($action) {
            case 'submit_termination':
                return $this->submitTermination($data);
            case 'update_termination':
            case 'process_termination':
                return $this->processTermination(
                    $data['termination_id'] ?? 0,
                    $data['action'] ?? '',
                    $data['approved_by'] ?? ($_SESSION['employee_id'] ?? 0),
                    $data['status'] ?? '',
                    $data['comments'] ?? $data['approval_comments'] ?? null
                );
            case 'get_termination':
                return $this->getTermination($data['termination_id'] ?? 0);
            case 'get_terminations':
                $status = $data['status'] ?? null;
                $page = (int)($data['page'] ?? 1);
                $limit = (int)($data['limit'] ?? 10);
                $search = $data['search'] ?? '';
                return $this->getTerminations($status, $page, $limit, $search);
            case 'archive_termination':
                return $this->archiveTermination($data['termination_id'] ?? 0);
            case 'unarchive_termination':
                return $this->unarchiveTermination($data['termination_id'] ?? 0);
            case 'get_archived_terminations':
                $page = (int)($data['page'] ?? 1);
                $limit = (int)($data['limit'] ?? 10);
                $search = $data['search'] ?? '';
                return $this->getArchivedTerminations($page, $limit, $search);
            case 'get_termination_details':
                return $this->getTerminationDetails($data['termination_id'] ?? 0);
            case 'check_termination_eligibility':
                return $this->checkEmployeeEligibility($data['employee_id'] ?? '');
            default:
                return parent::handleAjaxRequest($action, $data);
        }
    }
}
