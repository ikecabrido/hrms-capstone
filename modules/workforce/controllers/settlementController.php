<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/finalSettlementModel.php';

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
