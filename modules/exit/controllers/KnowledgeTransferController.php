<?php

require_once __DIR__ . '/../models/KnowledgeTransferModel.php';

class KnowledgeTransferController extends ExitManagementController
{
    private KnowledgeTransferModel $transferModel;

    public function __construct()
    {
        parent::__construct();
        $this->transferModel = new KnowledgeTransferModel();
    }

    private function isValidDate(string $date): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date;
    }

    private function employeeExists($employeeId): bool
    {
        return !empty($this->transferModel->getEmployeeById($employeeId));
    }

    private function isEmployeeEligibleForKnowledgeTransfer($employeeId): bool
    {
        foreach ($this->transferModel->getEmployeesNeedingKnowledgeTransfer() as $employee) {
            if ((string)($employee['id'] ?? '') === (string)$employeeId) {
                return true;
            }
        }

        return false;
    }

    private function validateTransferItem(array $item, int $index): array
    {
        $allowedTypes = ['document', 'process', 'contact', 'system', 'other'];
        $allowedPriorities = ['low', 'medium', 'high'];
        $allowedStatuses = ['pending', 'in_progress', 'completed'];

        $type = trim($item['type'] ?? '');
        $title = trim($item['title'] ?? '');
        $priority = trim($item['priority'] ?? 'medium');
        $status = trim($item['status'] ?? 'pending');
        $notes = isset($item['notes']) ? trim($item['notes']) : null;
        $description = trim($item['description'] ?? '');
        $notesEmpty = $notes === null || $notes === '';

        if ($type === '' && $title === '' && $description === '' && $notesEmpty) {
            return ['success' => true, 'item' => null];
        }

        if ($type === '') {
            return ['success' => false, 'message' => "Item #" . ($index + 1) . " type is required"];
        }

        if (!in_array($type, $allowedTypes, true)) {
            return ['success' => false, 'message' => "Item #" . ($index + 1) . " has invalid type"];
        }

        if ($title === '') {
            return ['success' => false, 'message' => "Item #" . ($index + 1) . " title is required"];
        }

        if (strlen($title) > 255) {
            return ['success' => false, 'message' => "Item #" . ($index + 1) . " title cannot exceed 255 characters"];
        }

        if ($priority !== '' && !in_array($priority, $allowedPriorities, true)) {
            return ['success' => false, 'message' => "Item #" . ($index + 1) . " has invalid priority"];
        }

        if ($status !== '' && !in_array($status, $allowedStatuses, true)) {
            return ['success' => false, 'message' => "Item #" . ($index + 1) . " has invalid status"];
        }

        return ['success' => true, 'item' => [
            'type' => $type,
            'title' => $title,
            'description' => trim($item['description'] ?? '' ) ?: null,
            'notes' => $notes,
            'priority' => $priority ?: 'medium',
            'status' => $status ?: 'pending',
            'id' => isset($item['id']) ? (int)$item['id'] : null
        ]];
    }

    private function validateTransferItems(array $items): array
    {
        $validated = [];

        foreach ($items as $index => $item) {
            $result = $this->validateTransferItem($item, $index);
            if (!$result['success']) {
                return $result;
            }

            if ($result['item'] !== null) {
                $validated[] = $result['item'];
            }
        }

        return ['success' => true, 'items' => $validated];
    }

    private function validateTransferPlanData(array $data): array
    {
        $required = ['employee_id', 'successor_id', 'start_date', 'end_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field '$field' is required"];
            }
        }

        if (!$this->isValidDate($data['start_date'])) {
            return ['success' => false, 'message' => 'Start date is invalid'];
        }

        if (!$this->isValidDate($data['end_date'])) {
            return ['success' => false, 'message' => 'End date is invalid'];
        }

        if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
            return ['success' => false, 'message' => 'Start date must be before or equal to end date'];
        }

        if (!$this->employeeExists($data['employee_id'])) {
            return ['success' => false, 'message' => 'Employee ID is invalid'];
        }

        if (!$this->isEmployeeEligibleForKnowledgeTransfer($data['employee_id'])) {
            return ['success' => false, 'message' => 'Employee is not eligible for knowledge transfer.'];
        }

        if (!$this->employeeExists($data['successor_id'])) {
            return ['success' => false, 'message' => 'Successor ID is invalid'];
        }

        return ['success' => true];
    }

    /**
     * Create knowledge transfer plan
     */
    public function createTransferPlan(array $data): array
    {
        try {
            $validation = $this->validateTransferPlanData($data);
            if (!$validation['success']) {
                return $validation;
            }

            $items = [];
            if (isset($data['items']) && is_array($data['items'])) {
                $itemsValidation = $this->validateTransferItems($data['items']);
                if (!$itemsValidation['success']) {
                    return $itemsValidation;
                }
                $items = $itemsValidation['items'];
            }

            $data['created_by'] = $_SESSION['employee_id'] ?? 0;
            $data['items'] = $items;

            $planId = $this->transferModel->createTransferPlan($data);

            return [
                'success' => true,
                'message' => 'Knowledge transfer plan created successfully',
                'plan_id' => $planId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update a knowledge transfer plan
     */
    public function updateTransferPlan(array $data): array
    {
        try {
            $planId = (int)($data['plan_id'] ?? 0);
            if ($planId <= 0) {
                return ['success' => false, 'message' => 'Invalid transfer plan ID'];
            }

            $validation = $this->validateTransferPlanData($data);
            if (!$validation['success']) {
                return $validation;
            }

            $items = [];
            if (isset($data['items']) && is_array($data['items'])) {
                $itemsValidation = $this->validateTransferItems($data['items']);
                if (!$itemsValidation['success']) {
                    return $itemsValidation;
                }
                $items = $itemsValidation['items'];
            }

            $updated = $this->transferModel->updateTransferPlan($planId, $data);
            if (!$updated) {
                return ['success' => false, 'message' => 'Failed to update transfer plan'];
            }

            $this->transferModel->deleteTransferItemsByPlanId($planId);
            if (count($items) > 0) {
                $this->addTransferItems($planId, $items);
            }

            return [
                'success' => true,
                'message' => 'Knowledge transfer plan updated successfully',
                'plan_id' => $planId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Add items to transfer plan
     */
    public function addTransferItems(int $planId, array $items): array
    {
        try {
            if ($planId <= 0) {
                return ['success' => false, 'message' => 'Invalid transfer plan ID'];
            }

            $itemsValidation = $this->validateTransferItems($items);
            if (!$itemsValidation['success']) {
                return $itemsValidation;
            }

            $success = $this->transferModel->addTransferItems($planId, $itemsValidation['items']);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Transfer items added successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to add transfer items'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update transfer item status
     */
    public function updateItemStatus(int $itemId, string $status, ?string $notes = null): array
    {
        try {
            if ($itemId <= 0) {
                return ['success' => false, 'message' => 'Invalid item ID'];
            }

            $allowedStatuses = ['pending', 'in_progress', 'completed'];
            if (!in_array($status, $allowedStatuses, true)) {
                return ['success' => false, 'message' => 'Invalid item status'];
            }

            $success = $this->transferModel->updateItemStatus($itemId, $status, $notes);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Item status updated successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update item status'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get transfer plan details
     */
    public function getTransferPlan(int $planId): array
    {
        $plan = $this->transferModel->getTransferPlanById($planId);

        if (!$plan) {
            return ['error' => 'Transfer plan not found'];
        }

        // Get transfer items
        $items = $this->transferModel->getTransferItems($planId);
        $plan['items'] = $items;

        return $plan;
    }

    /**
     * Render printable transfer plan page
     */
    public function renderTransferPrintPage(int $planId): string
    {
        $plan = $this->getTransferPlan($planId);
        if (isset($plan['error'])) {
            return '<!doctype html><html><head><title>Transfer Plan Not Found</title></head><body><h1>Transfer plan not found</h1></body></html>';
        }

        $employeeName = htmlspecialchars($plan['employee_name'] ?? 'Unknown', ENT_QUOTES);
        $successorName = htmlspecialchars($plan['successor_name'] ?? 'Unassigned', ENT_QUOTES);
        $startDate = htmlspecialchars($plan['start_date'] ?? 'N/A', ENT_QUOTES);
        $endDate = htmlspecialchars($plan['end_date'] ?? 'N/A', ENT_QUOTES);
        $status = htmlspecialchars(ucfirst((string)($plan['status'] ?? 'N/A')), ENT_QUOTES);
        $createdAt = htmlspecialchars($plan['created_at'] ?? 'N/A', ENT_QUOTES);
        $updatedAt = htmlspecialchars($plan['updated_at'] ?? 'N/A', ENT_QUOTES);

        $itemsHtml = '';
        if (!empty($plan['items'])) {
            foreach ($plan['items'] as $item) {
                $itemsHtml .= '<tr>' .
                    '<td>' . htmlspecialchars($item['item_type'] ?? $item['type'] ?? 'N/A', ENT_QUOTES) . '</td>' .
                    '<td>' . htmlspecialchars($item['title'] ?? 'N/A', ENT_QUOTES) . '</td>' .
                    '<td>' . htmlspecialchars($item['priority'] ?? 'N/A', ENT_QUOTES) . '</td>' .
                    '<td>' . htmlspecialchars(isset($item['status']) ? ucwords(str_replace('_', ' ', $item['status'])) : 'N/A', ENT_QUOTES) . '</td>' .
                    '<td>' . htmlspecialchars($item['description'] ?? '', ENT_QUOTES) . '</td>' .
                    '<td>' . htmlspecialchars($item['notes'] ?? '', ENT_QUOTES) . '</td>' .
                '</tr>';
            }
        } else {
            $itemsHtml = '<tr><td colspan="6">No transfer items found.</td></tr>';
        }

        // Use same header and signatory layout as settlement for consistent preview
        $header = '<div class="school-header"><img src="/capstone_hr_management_system2/assets/pics/bcpLogo.png" alt="Bestlink College of the Philippines logo"><div><div class="school-name">Bestlink College of the Philippines - Bulacan Campus</div><div class="school-details">Lot 1 Ipo Road Brgy. Minuyan Proper, City of San Jose Del Monte, Bulacan.<br>Tel. No.: (044)792-1992</div></div></div>';

        $signatories = '<div class="signatories">'
            . '<div><strong>Prepared by:</strong><br><br>HR Staff</div>'
            . '<div><strong>Reviewed/Approved by:</strong><br><br>HR Administrator</div>'
            . '<div><strong>Employee Acknowledgment:</strong><br><br>Employee</div>'
            . '</div>';

        return '<!doctype html><html><head><meta charset="UTF-8"><title>Knowledge Transfer Plan</title>' .
            '<style>body{font-family:Arial,sans-serif;margin:24px;color:#172b4d;}h1,h2{margin-bottom:0.5rem;}table{width:100%;border-collapse:collapse;margin-top:1rem;}th,td{padding:10px;border:1px solid #ddd;text-align:left;}th{background:#f8f9fa;}.section{margin-top:24px;} .section-title{font-size:1rem;font-weight:700;margin-bottom:12px;} .panel{padding:16px;background:#f7f9fc;border:1px solid #e3e8ef;border-radius:6px;}.school-header{display:flex;align-items:center;border-bottom:2px solid #1f5fbf;padding-bottom:14px;margin-bottom:20px;}.school-header img{width:86px;height:86px;object-fit:contain;margin-right:18px;}.school-name{font-size:20px;font-weight:700;color:#174a8b;}.school-details{font-size:12px;line-height:1.6;color:#333;margin-top:4px;}table{width:100%;border-collapse:collapse;margin-top:1rem;}th,td{padding:8px;border:1px solid #ddd;text-align:left;}th{background:#f4f4f4;} .signatories{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:52px;page-break-inside:avoid;color:#172b4d;}.signatories>div{min-height:72px;border-top:1px solid #172b4d;padding-top:8px;font-size:12px;line-height:1.5;}</style>' .
            '</head><body>' .
            $header .
            '<h1>Knowledge Transfer Plan</h1>' .
            '<div class="panel"><strong>Status:</strong> ' . $status . '</div>' .
            '<div class="section"><div class="section-title">Plan Summary</div>' .
            '<table><tbody>' .
            '<tr><th>Employee</th><td>' . $employeeName . '</td></tr>' .
            '<tr><th>Successor</th><td>' . $successorName . '</td></tr>' .
            '<tr><th>Start Date</th><td>' . $startDate . '</td></tr>' .
            '<tr><th>End Date</th><td>' . $endDate . '</td></tr>' .
            '<tr><th>Created At</th><td>' . $createdAt . '</td></tr>' .
            '<tr><th>Updated At</th><td>' . $updatedAt . '</td></tr>' .
            '</tbody></table></div>' .
            '<div class="section"><div class="section-title">Transfer Items</div>' .
            '<table><thead><tr><th>Type</th><th>Title</th><th>Priority</th><th>Status</th><th>Description</th><th>Notes</th></tr></thead><tbody>' . $itemsHtml . '</tbody></table></div>' .
            $signatories .
            '</body></html>';
    }

    /**
     * Complete transfer plan
     */
    public function completeTransferPlan(int $planId): array
    {
        try {
            $success = $this->transferModel->completeTransferPlan($planId);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Transfer plan completed successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to complete transfer plan'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete transfer plan
     */
    public function deleteTransferPlan(int $planId): array
    {
        try {
            $success = $this->transferModel->deleteTransferPlan($planId);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Knowledge transfer plan deleted successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to delete transfer plan'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get active transfer plans
     */
    public function getActiveTransferPlans(): array
    {
        return $this->transferModel->getActiveTransferPlans();
    }

    /**
     * Get all transfer plans with optional status filter
     */
    public function getTransferPlans(?string $status = null): array
    {
        return $this->transferModel->getAllTransferPlans($status);
    }

    /**
     * Get transfer items for a plan
     */
    public function getTransferItems(int $planId): array
    {
        return $this->transferModel->getTransferItems($planId);
    }

    /**
     * Handle AJAX requests for knowledge transfer
     */
    public function handleAjaxRequest(string $action, array $data = []): array
    {
        switch ($action) {
            case 'submit_transfer_plan':
                return $this->createTransferPlan($data);

            case 'update_transfer_plan':
                return $this->updateTransferPlan($data);

            case 'create_transfer_plan':
                return $this->createTransferPlan($data);

            case 'add_transfer_items':
                return $this->addTransferItems(
                    $data['plan_id'] ?? 0,
                    $data['items'] ?? []
                );

            case 'update_item_status':
                return $this->updateItemStatus(
                    $data['item_id'] ?? 0,
                    $data['status'] ?? '',
                    $data['notes'] ?? null
                );

            case 'get_transfer_plan':
                return $this->getTransferPlan($data['plan_id'] ?? 0);

            case 'complete_transfer_plan':
                return $this->completeTransferPlan($data['plan_id'] ?? 0);

            case 'delete_transfer_plan':
                return $this->deleteTransferPlan($data['plan_id'] ?? 0);

            case 'get_active_plans':
                return $this->getActiveTransferPlans();

            case 'get_transfer_plans':
                return $this->transferModel->getAllTransferPlans(
                    $data['status'] ?? null,
                    $data['page'] ?? 1,
                    $data['limit'] ?? 10,
                    $data['search'] ?? ''
                );

            case 'get_archived_transfer_plans':
                $page = (int)($data['page'] ?? 1);
                $limit = (int)($data['limit'] ?? 10);
                $search = $data['search'] ?? '';
                return $this->transferModel->getArchivedTransferPlans($page, $limit, $search);

            case 'get_transfer_items':
                return $this->getTransferItems($data['plan_id'] ?? 0);

            case 'archive_transfer_plan':
                return $this->archiveTransferPlan(
                    $data['plan_id'] ?? 0,
                    $data['reason'] ?? $data['archive_reason'] ?? ''
                );

            case 'unarchive_transfer_plan':
                return $this->unarchiveTransferPlan($data['plan_id'] ?? 0);

            case 'archive_transfer_item':
                return $this->archiveTransferItem(
                    $data['item_id'] ?? 0,
                    $data['reason'] ?? $data['archive_reason'] ?? ''
                );

            case 'unarchive_transfer_item':
                return $this->unarchiveTransferItem($data['item_id'] ?? 0);

            case 'get_transfer_item_details':
                return $this->getTransferItemDetails($data['item_id'] ?? 0);

            default:
                return parent::handleAjaxRequest($action, $data);
        }
    }

    /**
     * Archive a transfer plan
     */
    private function archiveTransferPlan(int $planId, string $reason): array
    {
        try {
            if (empty($planId) || empty($reason)) {
                return [
                    'success' => false,
                    'message' => 'Plan ID and archive reason are required'
                ];
            }

            $result = $this->transferModel->archiveTransferPlan($planId, $reason);

            return [
                'success' => $result,
                'message' => $result ? 'Transfer plan archived successfully' : 'Failed to archive transfer plan'
            ];
        } catch (Exception $e) {
            error_log("Error archiving transfer plan: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while archiving the transfer plan'
            ];
        }
    }

    /**
     * Unarchive a transfer plan
     */
    private function unarchiveTransferPlan(int $planId): array
    {
        try {
            if (empty($planId)) {
                return [
                    'success' => false,
                    'message' => 'Plan ID is required'
                ];
            }

            $result = $this->transferModel->unarchiveTransferPlan($planId);

            return [
                'success' => $result,
                'message' => $result ? 'Transfer plan unarchived successfully' : 'Failed to unarchive transfer plan'
            ];
        } catch (Exception $e) {
            error_log("Error unarchiving transfer plan: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while unarchiving the transfer plan'
            ];
        }
    }

    /**
     * Archive a transfer item
     */
    private function archiveTransferItem(int $itemId, string $reason): array
    {
        try {
            if (empty($itemId) || empty($reason)) {
                return [
                    'success' => false,
                    'message' => 'Item ID and archive reason are required'
                ];
            }

            $result = $this->transferModel->archiveTransferItem($itemId, $reason);

            return [
                'success' => $result,
                'message' => $result ? 'Transfer item archived successfully' : 'Failed to archive transfer item'
            ];
        } catch (Exception $e) {
            error_log("Error archiving transfer item: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while archiving the transfer item'
            ];
        }
    }

    /**
     * Unarchive a transfer item
     */
    private function unarchiveTransferItem(int $itemId): array
    {
        try {
            if (empty($itemId)) {
                return [
                    'success' => false,
                    'message' => 'Item ID is required'
                ];
            }

            $result = $this->transferModel->unarchiveTransferItem($itemId);

            return [
                'success' => $result,
                'message' => $result ? 'Transfer item unarchived successfully' : 'Failed to unarchive transfer item'
            ];
        } catch (Exception $e) {
            error_log("Error unarchiving transfer item: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while unarchiving the transfer item'
            ];
        }
    }

    /**
     * Get transfer item details for archiving
     */
    private function getTransferItemDetails(int $itemId): array
    {
        try {
            if (empty($itemId)) {
                return [
                    'success' => false,
                    'message' => 'Item ID is required'
                ];
            }

            $item = $this->transferModel->getTransferItem($itemId);

            if (!$item) {
                return [
                    'success' => false,
                    'message' => 'Transfer item not found'
                ];
            }

            // Get plan to find employee
            $plan = $this->transferModel->getTransferPlanById($item['plan_id']);
            $employee = null;
            if ($plan) {
                $employee = $this->transferModel->getEmployeeById($plan['employee_id']);
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $item['id'],
                    'employee_id' => $plan ? $plan['employee_id'] : 0,
                    'employee_name' => $employee ? trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')) : 'Unknown',
                    'type' => $item['item_type'] ?? $item['type'] ?? '',
                    'title' => $item['title'],
                    'status' => $item['status']
                ]
            ];
        } catch (Exception $e) {
            error_log("Error getting transfer item details: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while retrieving transfer item details'
            ];
        }
    }
}