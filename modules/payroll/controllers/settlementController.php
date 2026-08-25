<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/settlementModel.php';

class SettlementController
{
    private PDO $db;
    private SettlementModel $model;


    public function __construct()
    {
        $this->db =
            (new Database())->getConnection();

        $this->model =
            new SettlementModel(
                $this->db
            );
    }


    /* ============================================================
       SETTLEMENT REQUESTS
       ============================================================ */

    /**
     * Get settlement requests received from Exit Management.
     */
    public function getRequests(
        ?string $search = null,
        ?string $status = null,
        ?string $exitType = null
    ): array {

        return $this->model->getSettlementRequests(
            $search,
            $status,
            $exitType
        );
    }


    /**
     * Get one Exit Management settlement request.
     */
    public function getRequest(
        int $exitSettlementId
    ): ?array {

        if ($exitSettlementId <= 0) {
            return null;
        }

        return $this->model->getExitSettlementById(
            $exitSettlementId
        );
    }


    /**
     * Accept an incoming settlement request.
     *
     * This creates the Payroll settlement.
     */
    public function acceptRequest(
        int $exitSettlementId,
        ?int $createdBy = null
    ): int {

        if ($exitSettlementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid settlement request.'
            );
        }

        return $this->model->createFromExitRequest(
            $exitSettlementId,
            $createdBy
        );
    }


    /* ============================================================
       PAYROLL SETTLEMENT LIST
       ============================================================ */

    /**
     * Get Payroll final settlements.
     */
    public function index(
        ?string $search = null,
        ?string $status = null,
        ?string $exitType = null
    ): array {

        return $this->model->getAll(
            $search,
            $status,
            $exitType
        );
    }


    /**
     * Get one Payroll settlement.
     */
    public function show(
        int $settlementId
    ): ?array {

        if ($settlementId <= 0) {
            return null;
        }

        return $this->model->getById(
            $settlementId
        );
    }


    /* ============================================================
       SETTLEMENT ITEMS
       ============================================================ */

    /**
     * Get all earning/deduction items.
     */
    public function getItems(
        int $settlementId
    ): array {

        if ($settlementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid settlement.'
            );
        }

        return $this->model->getItems(
            $settlementId
        );
    }


    /**
     * Add an earning or deduction item.
     */
    public function addItem(
        array $data
    ): int {

        $settlementId =
            (int)($data['settlement_id'] ?? 0);

        $itemType =
            strtolower(
                trim(
                    (string)(
                        $data['item_type'] ?? ''
                    )
                )
            );

        $itemCategory =
            trim(
                (string)(
                    $data['item_category'] ?? ''
                )
            );

        $description =
            trim(
                (string)(
                    $data['description'] ?? ''
                )
            );

        $amount =
            (float)(
                $data['amount'] ?? 0
            );

        $itemCode =
            isset($data['item_code'])
            ? trim((string)$data['item_code'])
            : null;

        $sourceType =
            isset($data['source_type'])
            ? trim((string)$data['source_type'])
            : null;

        $sourceId =
            isset($data['source_id'])
            ? (int)$data['source_id']
            : null;

        $sortOrder =
            (int)(
                $data['sort_order'] ?? 0
            );


        if ($settlementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid settlement.'
            );
        }

        if (!in_array(
            $itemType,
            ['earning', 'deduction'],
            true
        )) {
            throw new InvalidArgumentException(
                'Please select a valid item type.'
            );
        }

        if ($itemCategory === '') {
            throw new InvalidArgumentException(
                'Item category is required.'
            );
        }

        if ($description === '') {
            throw new InvalidArgumentException(
                'Item description is required.'
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Item amount must be greater than zero.'
            );
        }


        return $this->model->addItem(
            $settlementId,
            $itemType,
            $itemCategory,
            $description,
            $amount,
            $itemCode,
            $sourceType,
            $sourceId,
            $sortOrder
        );
    }


    /**
     * Update an earning/deduction item.
     */
    public function updateItem(
        array $data
    ): bool {

        $itemId =
            (int)($data['item_id'] ?? 0);

        $itemType =
            strtolower(
                trim(
                    (string)(
                        $data['item_type'] ?? ''
                    )
                )
            );

        $itemCategory =
            trim(
                (string)(
                    $data['item_category'] ?? ''
                )
            );

        $description =
            trim(
                (string)(
                    $data['description'] ?? ''
                )
            );

        $amount =
            (float)(
                $data['amount'] ?? 0
            );

        $itemCode =
            isset($data['item_code'])
            ? trim((string)$data['item_code'])
            : null;

        $sourceType =
            isset($data['source_type'])
            ? trim((string)$data['source_type'])
            : null;

        $sourceId =
            isset($data['source_id'])
            ? (int)$data['source_id']
            : null;

        $sortOrder =
            (int)(
                $data['sort_order'] ?? 0
            );


        if ($itemId <= 0) {
            throw new InvalidArgumentException(
                'Invalid settlement item.'
            );
        }

        if (!in_array(
            $itemType,
            ['earning', 'deduction'],
            true
        )) {
            throw new InvalidArgumentException(
                'Invalid settlement item type.'
            );
        }

        if ($itemCategory === '') {
            throw new InvalidArgumentException(
                'Item category is required.'
            );
        }

        if ($description === '') {
            throw new InvalidArgumentException(
                'Item description is required.'
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Item amount must be greater than zero.'
            );
        }


        return $this->model->updateItem(
            $itemId,
            $itemType,
            $itemCategory,
            $description,
            $amount,
            $itemCode,
            $sourceType,
            $sourceId,
            $sortOrder
        );
    }


    /**
     * Delete an earning/deduction item.
     */
    public function deleteItem(
        int $itemId
    ): bool {

        if ($itemId <= 0) {
            throw new InvalidArgumentException(
                'Invalid settlement item.'
            );
        }

        return $this->model->deleteItem(
            $itemId
        );
    }


    /* ============================================================
       CALCULATE
       ============================================================ */

    /**
     * Calculate final settlement totals.
     */
    public function calculate(
        int $settlementId
    ): array {

        if ($settlementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid settlement.'
            );
        }

        return $this->model->calculate(
            $settlementId
        );
    }


    /* ============================================================
       APPROVAL
       ============================================================ */

    /**
     * Submit settlement for approval.
     */
    public function submitForApproval(
        int $settlementId
    ): bool {

        if ($settlementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid settlement.'
            );
        }

        return $this->model->submitForApproval(
            $settlementId
        );
    }


    /**
     * Approve final settlement.
     */
    public function approve(
        int $settlementId,
        ?int $approvedBy = null
    ): bool {

        if ($settlementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid settlement.'
            );
        }

        return $this->model->approve(
            $settlementId,
            $approvedBy
        );
    }


    /* ============================================================
       RELEASE / PAYMENT
       ============================================================ */

    /**
     * Release the approved final settlement.
     */
    public function release(
        int $settlementId,
        array $data
    ): bool {

        if ($settlementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid settlement.'
            );
        }

        $paymentMethod =
            trim(
                (string)(
                    $data['payment_method'] ?? ''
                )
            );

        $paymentReference =
            isset($data['payment_reference'])
            ? trim(
                (string)$data['payment_reference']
            )
            : null;


        if ($paymentMethod === '') {
            throw new InvalidArgumentException(
                'Please select a payment method.'
            );
        }


        return $this->model->release(
            $settlementId,
            $paymentMethod,
            $paymentReference
        );
    }


    /* ============================================================
       CANCEL
       ============================================================ */

    /**
     * Cancel a settlement.
     */
    public function cancel(
        int $settlementId,
        ?string $remarks = null
    ): bool {

        if ($settlementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid settlement.'
            );
        }

        return $this->model->cancel(
            $settlementId,
            $remarks
        );
    }


    /* ============================================================
       SUMMARY
       ============================================================ */

    /**
     * Get Payroll settlement summary.
     */
    public function getSummary(): array
    {
        return $this->model->getSummary();
    }


    /**
     * Get Exit Management request summary.
     */
    public function getRequestSummary(): array
    {
        return $this->model->getRequestSummary();
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {

    require_once __DIR__ . '/../../../auth/session.php';
    require_once __DIR__ . '/../../../auth/guard.php';

    header('Content-Type: application/json');

    function fs_respond(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        echo json_encode($payload);
        exit;
    }

    $controller = new SettlementController();
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {

        /*
         * ------------------------------------------------------
         * SETTLEMENT REQUESTS (Exit Management -> Payroll)
         * ------------------------------------------------------
         */
        case 'requests': {
                $search = isset($_GET['search']) ? trim($_GET['search']) : null;
                $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
                $exitType = isset($_GET['exit_type']) && $_GET['exit_type'] !== '' ? $_GET['exit_type'] : null;

                try {
                    $requests = $controller->getRequests($search, $status, $exitType);
                    $requestSummary = $controller->getRequestSummary();
                    $summary = $controller->getSummary();

                    fs_respond([
                        'success' => true,
                        'data' => $requests,
                        'request_summary' => $requestSummary,
                        'summary' => $summary
                    ]);
                } catch (InvalidArgumentException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to load settlement requests.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * SINGLE SETTLEMENT REQUEST
         * ------------------------------------------------------
         */
        case 'request': {
                $id = intval($_GET['id'] ?? 0);
                if (!$id) {
                    fs_respond(['success' => false, 'message' => 'No settlement request ID provided.']);
                }

                try {
                    $request = $controller->getRequest($id);
                    if (!$request) {
                        fs_respond(['success' => false, 'message' => 'Settlement request not found.'], 404);
                    }
                    fs_respond(['success' => true, 'data' => $request]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to load settlement request.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * ACCEPT REQUEST -> creates the Payroll settlement
         * ------------------------------------------------------
         */
        case 'accept_request': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    fs_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $exitSettlementId = intval($_POST['exit_settlement_id'] ?? 0);
                $createdBy = $_SESSION['employee_id'] ?? null;

                try {
                    $newSettlementId = $controller->acceptRequest($exitSettlementId, $createdBy);
                    fs_respond([
                        'success' => true,
                        'message' => 'Settlement request accepted. Payroll processing has started.',
                        'data' => ['settlement_id' => $newSettlementId]
                    ]);
                } catch (InvalidArgumentException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to accept settlement request.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * PAYROLL SETTLEMENT LIST
         * ------------------------------------------------------
         */
        case 'list': {
                $search = isset($_GET['search']) ? trim($_GET['search']) : null;
                $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
                $exitType = isset($_GET['exit_type']) && $_GET['exit_type'] !== '' ? $_GET['exit_type'] : null;

                try {
                    $settlements = $controller->index($search, $status, $exitType);
                    $summary = $controller->getSummary();

                    fs_respond([
                        'success' => true,
                        'data' => $settlements,
                        'summary' => $summary
                    ]);
                } catch (InvalidArgumentException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to load final settlements.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * SINGLE SETTLEMENT + ITEMS (settlement detail workspace)
         * ------------------------------------------------------
         */
        case 'get_settlement': {
                $id = intval($_GET['id'] ?? 0);
                if (!$id) {
                    fs_respond(['success' => false, 'message' => 'No settlement ID provided.']);
                }

                try {
                    $settlement = $controller->show($id);
                    if (!$settlement) {
                        fs_respond(['success' => false, 'message' => 'Final settlement not found.'], 404);
                    }
                    $items = $controller->getItems($id);

                    fs_respond([
                        'success' => true,
                        'data' => $settlement,
                        'items' => $items
                    ]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to load settlement details.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * ADD SETTLEMENT ITEM (earning / deduction)
         * ------------------------------------------------------
         */
        case 'add_item': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    fs_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                try {
                    $itemId = $controller->addItem($_POST);
                    fs_respond([
                        'success' => true,
                        'message' => 'Settlement item added successfully.',
                        'data' => ['item_id' => $itemId]
                    ]);
                } catch (InvalidArgumentException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to add settlement item.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * UPDATE SETTLEMENT ITEM
         * ------------------------------------------------------
         */
        case 'update_item': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    fs_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                try {
                    $controller->updateItem($_POST);
                    fs_respond([
                        'success' => true,
                        'message' => 'Settlement item updated successfully.'
                    ]);
                } catch (InvalidArgumentException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to update settlement item.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * DELETE SETTLEMENT ITEM
         * ------------------------------------------------------
         */
        case 'delete_item': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    fs_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $itemId = intval($_POST['item_id'] ?? 0);

                try {
                    $controller->deleteItem($itemId);
                    fs_respond([
                        'success' => true,
                        'message' => 'Settlement item removed successfully.'
                    ]);
                } catch (InvalidArgumentException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to remove settlement item.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * CALCULATE SETTLEMENT
         * ------------------------------------------------------
         */
        case 'calculate': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    fs_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $settlementId = intval($_POST['settlement_id'] ?? 0);

                try {
                    $totals = $controller->calculate($settlementId);
                    fs_respond([
                        'success' => true,
                        'message' => 'Settlement calculated successfully.',
                        'data' => $totals
                    ]);
                } catch (InvalidArgumentException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to calculate settlement.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * SUBMIT FOR APPROVAL
         * ------------------------------------------------------
         */
        case 'submit_for_approval': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    fs_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $settlementId = intval($_POST['settlement_id'] ?? 0);

                try {
                    $controller->submitForApproval($settlementId);
                    fs_respond([
                        'success' => true,
                        'message' => 'Settlement submitted for approval.'
                    ]);
                } catch (InvalidArgumentException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to submit settlement for approval.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * APPROVE SETTLEMENT
         * ------------------------------------------------------
         */
        case 'approve': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    fs_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $settlementId = intval($_POST['settlement_id'] ?? 0);
                $approvedBy = $_SESSION['employee_id'] ?? null;

                try {
                    $controller->approve($settlementId, $approvedBy);
                    fs_respond([
                        'success' => true,
                        'message' => 'Settlement approved successfully.'
                    ]);
                } catch (InvalidArgumentException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to approve settlement.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * RELEASE / PAY SETTLEMENT
         * ------------------------------------------------------
         */
        case 'release': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    fs_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $settlementId = intval($_POST['settlement_id'] ?? 0);

                try {
                    $controller->release($settlementId, $_POST);
                    fs_respond([
                        'success' => true,
                        'message' => 'Settlement released and marked as paid.'
                    ]);
                } catch (InvalidArgumentException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to release settlement.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * CANCEL SETTLEMENT
         * ------------------------------------------------------
         */
        case 'cancel': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    fs_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $settlementId = intval($_POST['settlement_id'] ?? 0);
                $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : null;
                if ($remarks === '') {
                    $remarks = null;
                }

                try {
                    $controller->cancel($settlementId, $remarks);
                    fs_respond([
                        'success' => true,
                        'message' => 'Settlement cancelled.'
                    ]);
                } catch (InvalidArgumentException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    fs_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to cancel settlement.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * SUMMARY (settlement + request counters)
         * ------------------------------------------------------
         */
        case 'summary': {
                try {
                    $summary = $controller->getSummary();
                    $requestSummary = $controller->getRequestSummary();
                    fs_respond([
                        'success' => true,
                        'summary' => $summary,
                        'request_summary' => $requestSummary
                    ]);
                } catch (Throwable $e) {
                    fs_respond(['success' => false, 'message' => 'Failed to load summary.'], 500);
                }
                break;
            }

        default:
            fs_respond(['success' => false, 'message' => 'Invalid or missing action.'], 400);
    }
}
