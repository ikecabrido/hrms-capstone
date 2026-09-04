<?php

class SettlementModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /* ============================================================
       CONSTANTS
       ============================================================ */

    private const EXIT_STATUSES = [
        'pending',
        'requested',
        'processing',
        'calculated',
        'for_approval',
        'approved',
        'paid',
        'cancelled'
    ];

    private const PAYROLL_STATUSES = [
        'draft',
        'processing',
        'calculated',
        'for_approval',
        'approved',
        'paid',
        'cancelled'
    ];

    private const ITEM_TYPES = [
        'earning',
        'deduction'
    ];


    /* ============================================================
       SETTLEMENT REQUESTS FROM EXIT MANAGEMENT
       ============================================================ */

    /**
     * Get settlement requests sent by Exit Management.
     *
     * Default behavior:
     * Show requests that Payroll still needs to process.
     */
    public function getSettlementRequests(
        ?string $search = null,
        ?string $status = null,
        ?string $exitType = null
    ): array {

        $sql = "
            SELECT
                es.settlement_id AS exit_settlement_id,
                es.employee_id,
                es.exit_case_type,
                es.exit_case_id,
                es.last_working_date,
                es.payroll_settlement_id,
                es.status,
                es.requested_at,
                es.completed_at,
                es.remarks,
                es.created_by,
                es.created_at,
                es.updated_at,

                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,

                CONCAT(
                    e.first_name,
                    CASE
                        WHEN e.middle_name IS NOT NULL
                             AND e.middle_name <> ''
                        THEN CONCAT(' ', e.middle_name)
                        ELSE ''
                    END,
                    ' ',
                    e.last_name
                ) AS employee_name,

                ps.settlement_date,
                ps.total_earnings,
                ps.total_deductions,
                ps.net_settlement,
                ps.status AS payroll_status,
                ps.approved_at,
                ps.paid_at,
                ps.payment_reference,
                ps.payment_method

            FROM exit_employee_settlements es

            INNER JOIN em_employees e
                ON e.employee_id = es.employee_id

            LEFT JOIN pr_final_settlements ps
                ON ps.settlement_id = es.payroll_settlement_id

            WHERE 1 = 1
        ";

        $params = [];


        /* --------------------------------------------------------
           SEARCH
           -------------------------------------------------------- */

        if ($search !== null && trim($search) !== '') {

            $search = trim($search);

            /*
             * If the user searches only a number such as:
             *
             * 35
             *
             * also search:
             *
             * EMP-000035
             */
            if (ctype_digit($search)) {

                $employeeCode = 'EMP-' .
                    str_pad($search, 6, '0', STR_PAD_LEFT);

                $sql .= "
                    AND (
                        e.employee_code = :employee_code
                        OR e.employee_code LIKE :employee_code_suffix
                        OR CONCAT(
                            e.first_name,
                            ' ',
                            e.last_name
                        ) LIKE :search_name
                        OR e.first_name LIKE :search_name_first
                        OR e.last_name LIKE :search_name_last
                    )
                ";

                $params[':employee_code'] =
                    $employeeCode;

                $params[':employee_code_suffix'] =
                    '%' . $search;

                $params[':search_name'] =
                    '%' . $search . '%';

                $params[':search_name_first'] =
                    '%' . $search . '%';

                $params[':search_name_last'] =
                    '%' . $search . '%';
            } else {

                $sql .= "
                    AND (
                        e.employee_code LIKE :search
                        OR e.first_name LIKE :search_name
                        OR e.middle_name LIKE :search_middle
                        OR e.last_name LIKE :search_last
                        OR CONCAT(
                            e.first_name,
                            ' ',
                            e.last_name
                        ) LIKE :search_full_name
                    )
                ";

                $params[':search'] =
                    '%' . $search . '%';

                $params[':search_name'] =
                    '%' . $search . '%';

                $params[':search_middle'] =
                    '%' . $search . '%';

                $params[':search_last'] =
                    '%' . $search . '%';

                $params[':search_full_name'] =
                    '%' . $search . '%';
            }
        }


        /* --------------------------------------------------------
           STATUS FILTER
           -------------------------------------------------------- */

        if ($status !== null && $status !== '') {

            if (!in_array(
                $status,
                self::EXIT_STATUSES,
                true
            )) {
                throw new InvalidArgumentException(
                    'Invalid settlement request status.'
                );
            }

            $sql .= "
                AND es.status = :status
            ";

            $params[':status'] = $status;
        }


        /* --------------------------------------------------------
           EXIT TYPE FILTER
           -------------------------------------------------------- */

        if ($exitType !== null && $exitType !== '') {

            if (!in_array(
                $exitType,
                ['resignation', 'termination'],
                true
            )) {
                throw new InvalidArgumentException(
                    'Invalid exit type.'
                );
            }

            $sql .= "
                AND es.exit_case_type = :exit_case_type
            ";

            $params[':exit_case_type'] =
                $exitType;
        }


        $sql .= "
            ORDER BY
                CASE es.status
                    WHEN 'requested' THEN 1
                    WHEN 'processing' THEN 2
                    WHEN 'calculated' THEN 3
                    WHEN 'for_approval' THEN 4
                    WHEN 'approved' THEN 5
                    WHEN 'paid' THEN 6
                    ELSE 7
                END,
                es.requested_at DESC,
                es.settlement_id DESC
        ";


        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['exit_settlement_id'] =
                (int)$row['exit_settlement_id'];

            $row['employee_id'] =
                (int)$row['employee_id'];

            if ($row['payroll_settlement_id'] !== null) {
                $row['payroll_settlement_id'] =
                    (int)$row['payroll_settlement_id'];
            }

            $row['total_earnings'] =
                (float)($row['total_earnings'] ?? 0);

            $row['total_deductions'] =
                (float)($row['total_deductions'] ?? 0);

            $row['net_settlement'] =
                (float)($row['net_settlement'] ?? 0);
        }

        return $rows;
    }


    /**
     * Get one Exit Management settlement request.
     */
    public function getExitSettlementById(
        int $exitSettlementId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                es.*,

                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,

                CONCAT(
                    e.first_name,
                    CASE
                        WHEN e.middle_name IS NOT NULL
                             AND e.middle_name <> ''
                        THEN CONCAT(' ', e.middle_name)
                        ELSE ''
                    END,
                    ' ',
                    e.last_name
                ) AS employee_name

            FROM exit_employee_settlements es

            INNER JOIN em_employees e
                ON e.employee_id = es.employee_id

            WHERE es.settlement_id = :settlement_id

            LIMIT 1
        ");

        $stmt->execute([
            ':settlement_id' => $exitSettlementId
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['settlement_id'] =
            (int)$row['settlement_id'];

        $row['employee_id'] =
            (int)$row['employee_id'];

        if ($row['payroll_settlement_id'] !== null) {
            $row['payroll_settlement_id'] =
                (int)$row['payroll_settlement_id'];
        }

        return $row;
    }


    /* ============================================================
       CREATE PAYROLL SETTLEMENT FROM EXIT REQUEST
       ============================================================ */

    /**
     * Accept an Exit Management request and create
     * the corresponding Payroll final settlement.
     *
     * Workflow:
     *
     * Exit Management:
     * requested
     *
     * Payroll:
     * processing
     */
    public function createFromExitRequest(
        int $exitSettlementId,
        ?int $createdBy = null
    ): int {

        $this->db->beginTransaction();

        try {

            $exitRequest =
                $this->getExitSettlementById(
                    $exitSettlementId
                );

            if (!$exitRequest) {
                throw new RuntimeException(
                    'Settlement request not found.'
                );
            }


            /* ----------------------------------------------------
               Validate request status
               ---------------------------------------------------- */

            if ($exitRequest['status'] !== 'requested') {

                if (
                    $exitRequest['status'] === 'processing'
                    && !empty($exitRequest['payroll_settlement_id'])
                ) {
                    throw new RuntimeException(
                        'This settlement request is already being processed.'
                    );
                }

                throw new RuntimeException(
                    'Only requested settlement requests can be accepted.'
                );
            }


            /* ----------------------------------------------------
               Check for duplicate payroll settlement
               ---------------------------------------------------- */

            if (
                !empty($exitRequest['payroll_settlement_id'])
            ) {
                throw new RuntimeException(
                    'A Payroll settlement already exists for this request.'
                );
            }


            /* ----------------------------------------------------
               Create Payroll settlement
               ---------------------------------------------------- */

            $stmt = $this->db->prepare("
                INSERT INTO pr_final_settlements
                (
                    employee_id,
                    exit_settlement_id,
                    exit_case_type,
                    exit_case_id,
                    last_working_date,
                    status,
                    created_by
                )
                VALUES
                (
                    :employee_id,
                    :exit_settlement_id,
                    :exit_case_type,
                    :exit_case_id,
                    :last_working_date,
                    'processing',
                    :created_by
                )
            ");

            $stmt->execute([
                ':employee_id' =>
                $exitRequest['employee_id'],

                ':exit_settlement_id' =>
                $exitSettlementId,

                ':exit_case_type' =>
                $exitRequest['exit_case_type'],

                ':exit_case_id' =>
                $exitRequest['exit_case_id'],

                ':last_working_date' =>
                $exitRequest['last_working_date'],

                ':created_by' =>
                $createdBy
            ]);

            $payrollSettlementId =
                (int)$this->db->lastInsertId();


            /* ----------------------------------------------------
               Update Exit Management request
               ---------------------------------------------------- */

            $updateExit = $this->db->prepare("
                UPDATE exit_employee_settlements

                SET
                    payroll_settlement_id =
                        :payroll_settlement_id,

                    status = 'processing'

                WHERE settlement_id =
                    :settlement_id
            ");

            $updateExit->execute([
                ':payroll_settlement_id' =>
                $payrollSettlementId,

                ':settlement_id' =>
                $exitSettlementId
            ]);


            $this->db->commit();

            return $payrollSettlementId;
        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    /* ============================================================
       PAYROLL SETTLEMENTS
       ============================================================ */

    /**
     * Get Payroll final settlements.
     */
    public function getAll(
        ?string $search = null,
        ?string $status = null,
        ?string $exitType = null
    ): array {

        $sql = "
            SELECT
                fs.*,

                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,

                CONCAT(
                    e.first_name,
                    CASE
                        WHEN e.middle_name IS NOT NULL
                             AND e.middle_name <> ''
                        THEN CONCAT(' ', e.middle_name)
                        ELSE ''
                    END,
                    ' ',
                    e.last_name
                ) AS employee_name

            FROM pr_final_settlements fs

            INNER JOIN em_employees e
                ON e.employee_id = fs.employee_id

            WHERE 1 = 1
        ";

        $params = [];


        if ($search !== null && trim($search) !== '') {

            $search = trim($search);

            if (ctype_digit($search)) {

                $employeeCode =
                    'EMP-' .
                    str_pad(
                        $search,
                        6,
                        '0',
                        STR_PAD_LEFT
                    );

                $sql .= "
                    AND (
                        e.employee_code = :employee_code
                        OR e.employee_code LIKE :employee_code_suffix
                        OR e.first_name LIKE :search_name
                        OR e.last_name LIKE :search_last
                    )
                ";

                $params[':employee_code'] =
                    $employeeCode;

                $params[':employee_code_suffix'] =
                    '%' . $search;

                $params[':search_name'] =
                    '%' . $search . '%';

                $params[':search_last'] =
                    '%' . $search . '%';
            } else {

                $sql .= "
                    AND (
                        e.employee_code LIKE :search
                        OR e.first_name LIKE :search_first
                        OR e.middle_name LIKE :search_middle
                        OR e.last_name LIKE :search_last
                    )
                ";

                $params[':search'] =
                    '%' . $search . '%';

                $params[':search_first'] =
                    '%' . $search . '%';

                $params[':search_middle'] =
                    '%' . $search . '%';

                $params[':search_last'] =
                    '%' . $search . '%';
            }
        }


        if ($status !== null && $status !== '') {

            if (!in_array(
                $status,
                self::PAYROLL_STATUSES,
                true
            )) {
                throw new InvalidArgumentException(
                    'Invalid payroll settlement status.'
                );
            }

            $sql .= "
                AND fs.status = :status
            ";

            $params[':status'] = $status;
        }


        if ($exitType !== null && $exitType !== '') {

            if (!in_array(
                $exitType,
                ['resignation', 'termination'],
                true
            )) {
                throw new InvalidArgumentException(
                    'Invalid exit type.'
                );
            }

            $sql .= "
                AND fs.exit_case_type = :exit_case_type
            ";

            $params[':exit_case_type'] =
                $exitType;
        }


        $sql .= "
            ORDER BY
                fs.created_at DESC,
                fs.settlement_id DESC
        ";


        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['settlement_id'] =
                (int)$row['settlement_id'];

            $row['employee_id'] =
                (int)$row['employee_id'];

            if ($row['exit_settlement_id'] !== null) {
                $row['exit_settlement_id'] =
                    (int)$row['exit_settlement_id'];
            }

            $row['total_earnings'] =
                (float)$row['total_earnings'];

            $row['total_deductions'] =
                (float)$row['total_deductions'];

            $row['net_settlement'] =
                (float)$row['net_settlement'];
        }

        return $rows;
    }


    /**
     * Get one Payroll settlement.
     */
    public function getById(
        int $settlementId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                fs.*,

                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,

                CONCAT(
                    e.first_name,
                    CASE
                        WHEN e.middle_name IS NOT NULL
                             AND e.middle_name <> ''
                        THEN CONCAT(' ', e.middle_name)
                        ELSE ''
                    END,
                    ' ',
                    e.last_name
                ) AS employee_name

            FROM pr_final_settlements fs

            INNER JOIN em_employees e
                ON e.employee_id = fs.employee_id

            WHERE fs.settlement_id = :settlement_id

            LIMIT 1
        ");

        $stmt->execute([
            ':settlement_id' => $settlementId
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['settlement_id'] =
            (int)$row['settlement_id'];

        $row['employee_id'] =
            (int)$row['employee_id'];

        $row['total_earnings'] =
            (float)$row['total_earnings'];

        $row['total_deductions'] =
            (float)$row['total_deductions'];

        $row['net_settlement'] =
            (float)$row['net_settlement'];

        if ($row['exit_settlement_id'] !== null) {
            $row['exit_settlement_id'] =
                (int)$row['exit_settlement_id'];
        }

        return $row;
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

        $stmt = $this->db->prepare("
            SELECT
                item_id,
                settlement_id,
                item_type,
                item_category,
                item_code,
                description,
                amount,
                source_type,
                source_id,
                sort_order,
                created_at

            FROM pr_final_settlement_items

            WHERE settlement_id = :settlement_id

            ORDER BY
                item_type ASC,
                sort_order ASC,
                item_id ASC
        ");

        $stmt->execute([
            ':settlement_id' => $settlementId
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['item_id'] =
                (int)$row['item_id'];

            $row['settlement_id'] =
                (int)$row['settlement_id'];

            $row['amount'] =
                (float)$row['amount'];

            if ($row['source_id'] !== null) {
                $row['source_id'] =
                    (int)$row['source_id'];
            }

            $row['sort_order'] =
                (int)$row['sort_order'];
        }

        return $rows;
    }


    /**
     * Add a settlement item.
     */
    public function addItem(
        int $settlementId,
        string $itemType,
        string $itemCategory,
        string $description,
        float $amount,
        ?string $itemCode = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        int $sortOrder = 0
    ): int {

        if (!in_array(
            $itemType,
            self::ITEM_TYPES,
            true
        )) {
            throw new InvalidArgumentException(
                'Invalid settlement item type.'
            );
        }

        $description = trim($description);
        $itemCategory = trim($itemCategory);

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


        $settlement =
            $this->getById($settlementId);

        if (!$settlement) {
            throw new RuntimeException(
                'Final settlement not found.'
            );
        }


        if (!in_array(
            $settlement['status'],
            ['draft', 'processing'],
            true
        )) {
            throw new RuntimeException(
                'Settlement items can only be modified while the settlement is being processed.'
            );
        }


        $stmt = $this->db->prepare("
            INSERT INTO pr_final_settlement_items
            (
                settlement_id,
                item_type,
                item_category,
                item_code,
                description,
                amount,
                source_type,
                source_id,
                sort_order
            )
            VALUES
            (
                :settlement_id,
                :item_type,
                :item_category,
                :item_code,
                :description,
                :amount,
                :source_type,
                :source_id,
                :sort_order
            )
        ");

        $stmt->execute([
            ':settlement_id' =>
            $settlementId,

            ':item_type' =>
            $itemType,

            ':item_category' =>
            $itemCategory,

            ':item_code' =>
            $itemCode,

            ':description' =>
            $description,

            ':amount' =>
            $amount,

            ':source_type' =>
            $sourceType,

            ':source_id' =>
            $sourceId,

            ':sort_order' =>
            $sortOrder
        ]);

        return (int)$this->db->lastInsertId();
    }


    /**
     * Update a settlement item.
     */
    public function updateItem(
        int $itemId,
        string $itemType,
        string $itemCategory,
        string $description,
        float $amount,
        ?string $itemCode = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        int $sortOrder = 0
    ): bool {

        if (!in_array(
            $itemType,
            self::ITEM_TYPES,
            true
        )) {
            throw new InvalidArgumentException(
                'Invalid settlement item type.'
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Item amount must be greater than zero.'
            );
        }

        $stmt = $this->db->prepare("
            SELECT
                i.*,
                fs.status AS settlement_status

            FROM pr_final_settlement_items i

            INNER JOIN pr_final_settlements fs
                ON fs.settlement_id = i.settlement_id

            WHERE i.item_id = :item_id

            LIMIT 1
        ");

        $stmt->execute([
            ':item_id' => $itemId
        ]);

        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            throw new RuntimeException(
                'Settlement item not found.'
            );
        }

        if (!in_array(
            $existing['settlement_status'],
            ['draft', 'processing'],
            true
        )) {
            throw new RuntimeException(
                'This settlement can no longer be modified.'
            );
        }

        $stmt = $this->db->prepare("
            UPDATE pr_final_settlement_items

            SET
                item_type = :item_type,
                item_category = :item_category,
                item_code = :item_code,
                description = :description,
                amount = :amount,
                source_type = :source_type,
                source_id = :source_id,
                sort_order = :sort_order

            WHERE item_id = :item_id
        ");

        return $stmt->execute([
            ':item_type' =>
            $itemType,

            ':item_category' =>
            trim($itemCategory),

            ':item_code' =>
            $itemCode,

            ':description' =>
            trim($description),

            ':amount' =>
            $amount,

            ':source_type' =>
            $sourceType,

            ':source_id' =>
            $sourceId,

            ':sort_order' =>
            $sortOrder,

            ':item_id' =>
            $itemId
        ]);
    }


    /**
     * Delete a settlement item.
     */
    public function deleteItem(
        int $itemId
    ): bool {

        $stmt = $this->db->prepare("
            SELECT
                i.settlement_id,
                fs.status

            FROM pr_final_settlement_items i

            INNER JOIN pr_final_settlements fs
                ON fs.settlement_id = i.settlement_id

            WHERE i.item_id = :item_id

            LIMIT 1
        ");

        $stmt->execute([
            ':item_id' => $itemId
        ]);

        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new RuntimeException(
                'Settlement item not found.'
            );
        }

        if (!in_array(
            $item['status'],
            ['draft', 'processing'],
            true
        )) {
            throw new RuntimeException(
                'This settlement can no longer be modified.'
            );
        }

        $stmt = $this->db->prepare("
            DELETE FROM pr_final_settlement_items
            WHERE item_id = :item_id
        ");

        return $stmt->execute([
            ':item_id' => $itemId
        ]);
    }


    /* ============================================================
       CALCULATION
       ============================================================ */

    /**
     * Calculate settlement totals from settlement items.
     *
     * Net settlement =
     * Total earnings - Total deductions
     */
    public function calculate(
        int $settlementId
    ): array {

        $settlement =
            $this->getById($settlementId);

        if (!$settlement) {
            throw new RuntimeException(
                'Final settlement not found.'
            );
        }

        if (!in_array(
            $settlement['status'],
            ['draft', 'processing'],
            true
        )) {
            throw new RuntimeException(
                'This settlement cannot be calculated in its current status.'
            );
        }


        $stmt = $this->db->prepare("
            SELECT

                COALESCE(
                    SUM(
                        CASE
                            WHEN item_type = 'earning'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_earnings,

                COALESCE(
                    SUM(
                        CASE
                            WHEN item_type = 'deduction'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_deductions

            FROM pr_final_settlement_items

            WHERE settlement_id = :settlement_id
        ");

        $stmt->execute([
            ':settlement_id' => $settlementId
        ]);

        $totals =
            $stmt->fetch(PDO::FETCH_ASSOC);

        $totalEarnings =
            (float)($totals['total_earnings'] ?? 0);

        $totalDeductions =
            (float)($totals['total_deductions'] ?? 0);

        $netSettlement =
            $totalEarnings - $totalDeductions;


        $update = $this->db->prepare("
            UPDATE pr_final_settlements

            SET
                total_earnings = :total_earnings,
                total_deductions = :total_deductions,
                net_settlement = :net_settlement,
                settlement_date = CURDATE(),
                status = 'calculated'

            WHERE settlement_id = :settlement_id
        ");

        $update->execute([
            ':total_earnings' =>
            $totalEarnings,

            ':total_deductions' =>
            $totalDeductions,

            ':net_settlement' =>
            $netSettlement,

            ':settlement_id' =>
            $settlementId
        ]);


        /*
         * Synchronize Exit Management.
         */
        $this->updateExitSettlementStatus(
            $settlementId,
            'calculated'
        );


        return [
            'total_earnings' =>
            $totalEarnings,

            'total_deductions' =>
            $totalDeductions,

            'net_settlement' =>
            $netSettlement
        ];
    }


    /* ============================================================
       WORKFLOW STATUS CHANGES
       ============================================================ */

    /**
     * Submit a calculated settlement for approval.
     */
    public function submitForApproval(
        int $settlementId
    ): bool {

        $settlement =
            $this->getById($settlementId);

        if (!$settlement) {
            throw new RuntimeException(
                'Final settlement not found.'
            );
        }

        if ($settlement['status'] !== 'calculated') {
            throw new RuntimeException(
                'Only calculated settlements can be submitted for approval.'
            );
        }

        $stmt = $this->db->prepare("
            UPDATE pr_final_settlements

            SET status = 'for_approval'

            WHERE settlement_id = :settlement_id
              AND status = 'calculated'
        ");

        $result = $stmt->execute([
            ':settlement_id' => $settlementId
        ]);

        if ($result) {
            $this->updateExitSettlementStatus(
                $settlementId,
                'for_approval'
            );
        }

        return $result;
    }


    /**
     * Approve a settlement.
     */
    public function approve(
        int $settlementId,
        ?int $approvedBy = null
    ): bool {

        $settlement =
            $this->getById($settlementId);

        if (!$settlement) {
            throw new RuntimeException(
                'Final settlement not found.'
            );
        }

        if ($settlement['status'] !== 'for_approval') {
            throw new RuntimeException(
                'Only settlements awaiting approval can be approved.'
            );
        }

        $stmt = $this->db->prepare("
            UPDATE pr_final_settlements

            SET
                status = 'approved',
                approved_by = :approved_by,
                approved_at = NOW()

            WHERE settlement_id = :settlement_id
              AND status = 'for_approval'
        ");

        $result = $stmt->execute([
            ':approved_by' =>
            $approvedBy,

            ':settlement_id' =>
            $settlementId
        ]);

        if ($result) {
            $this->updateExitSettlementStatus(
                $settlementId,
                'approved'
            );
        }

        return $result;
    }


    /**
     * Release/pay the final settlement.
     */
    public function release(
        int $settlementId,
        string $paymentMethod,
        ?string $paymentReference = null
    ): bool {

        $settlement =
            $this->getById($settlementId);

        if (!$settlement) {
            throw new RuntimeException(
                'Final settlement not found.'
            );
        }

        if ($settlement['status'] !== 'approved') {
            throw new RuntimeException(
                'Only approved settlements can be released.'
            );
        }

        $paymentMethod =
            trim($paymentMethod);

        if ($paymentMethod === '') {
            throw new InvalidArgumentException(
                'Payment method is required.'
            );
        }


        $this->db->beginTransaction();

        try {

            $stmt = $this->db->prepare("
                UPDATE pr_final_settlements

                SET
                    status = 'paid',
                    paid_at = NOW(),
                    payment_method = :payment_method,
                    payment_reference = :payment_reference

                WHERE settlement_id = :settlement_id
                  AND status = 'approved'
            ");

            $stmt->execute([
                ':payment_method' =>
                $paymentMethod,

                ':payment_reference' =>
                $paymentReference,

                ':settlement_id' =>
                $settlementId
            ]);


            /*
             * Update Exit Management.
             */
            $exitStmt = $this->db->prepare("
                UPDATE exit_employee_settlements

                SET
                    status = 'paid',
                    completed_at = NOW()

                WHERE payroll_settlement_id =
                    :settlement_id
            ");

            $exitStmt->execute([
                ':settlement_id' => $settlementId
            ]);

            // If payroll marked the settlement as paid, update employee status
            if ($stmt->rowCount() > 0) {
                $sel = $this->db->prepare(
                    "SELECT employee_id, exit_case_type FROM exit_employee_settlements WHERE payroll_settlement_id = :pid"
                );

                $sel->execute([':pid' => $settlementId]);

                $upd = $this->db->prepare(
                    "UPDATE em_employees SET employment_status = :new_status, updated_at = NOW() WHERE employee_id = :eid AND LOWER(TRIM(employment_status)) = 'active'"
                );

                while ($r = $sel->fetch(PDO::FETCH_ASSOC)) {
                    if (empty($r['employee_id']) || empty($r['exit_case_type'])) {
                        continue;
                    }

                    $employeeId = (int)$r['employee_id'];
                    $caseType = strtolower(trim($r['exit_case_type']));

                    if ($caseType === 'resignation') {
                        $newStatus = 'Resigned';
                    } elseif ($caseType === 'termination') {
                        $newStatus = 'Terminated';
                    } else {
                        continue; // only handle resignation/termination
                    }

                    $upd->execute([':new_status' => $newStatus, ':eid' => $employeeId]);
                }
            }


            $this->db->commit();

            return true;
        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    /**
     * Cancel a settlement.
     */
    public function cancel(
        int $settlementId,
        ?string $remarks = null
    ): bool {

        $settlement =
            $this->getById($settlementId);

        if (!$settlement) {
            throw new RuntimeException(
                'Final settlement not found.'
            );
        }

        if (in_array(
            $settlement['status'],
            ['approved', 'paid'],
            true
        )) {
            throw new RuntimeException(
                'Approved or paid settlements cannot be cancelled.'
            );
        }

        $stmt = $this->db->prepare("
            UPDATE pr_final_settlements

            SET
                status = 'cancelled',
                remarks = :remarks

            WHERE settlement_id = :settlement_id
        ");

        $result = $stmt->execute([
            ':remarks' =>
            $remarks,

            ':settlement_id' =>
            $settlementId
        ]);

        if ($result) {
            $this->updateExitSettlementStatus(
                $settlementId,
                'cancelled'
            );
        }

        return $result;
    }


    /* ============================================================
       SUMMARY
       ============================================================ */

    /**
     * Get dashboard/summary statistics.
     */
    public function getSummary(): array
    {
        $stmt = $this->db->query("
            SELECT

                COUNT(*) AS total_settlements,

                SUM(
                    CASE
                        WHEN status = 'draft'
                        THEN 1 ELSE 0
                    END
                ) AS draft_count,

                SUM(
                    CASE
                        WHEN status = 'processing'
                        THEN 1 ELSE 0
                    END
                ) AS processing_count,

                SUM(
                    CASE
                        WHEN status = 'calculated'
                        THEN 1 ELSE 0
                    END
                ) AS calculated_count,

                SUM(
                    CASE
                        WHEN status = 'for_approval'
                        THEN 1 ELSE 0
                    END
                ) AS for_approval_count,

                SUM(
                    CASE
                        WHEN status = 'approved'
                        THEN 1 ELSE 0
                    END
                ) AS approved_count,

                SUM(
                    CASE
                        WHEN status = 'paid'
                        THEN 1 ELSE 0
                    END
                ) AS paid_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'paid'
                            THEN net_settlement
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_paid

            FROM pr_final_settlements
        ");

        $row =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_settlements' =>
            (int)($row['total_settlements'] ?? 0),

            'draft_count' =>
            (int)($row['draft_count'] ?? 0),

            'processing_count' =>
            (int)($row['processing_count'] ?? 0),

            'calculated_count' =>
            (int)($row['calculated_count'] ?? 0),

            'for_approval_count' =>
            (int)($row['for_approval_count'] ?? 0),

            'approved_count' =>
            (int)($row['approved_count'] ?? 0),

            'paid_count' =>
            (int)($row['paid_count'] ?? 0),

            'total_paid' =>
            (float)($row['total_paid'] ?? 0)
        ];
    }


    /**
     * Get incoming settlement request summary.
     */
    public function getRequestSummary(): array
    {
        $stmt = $this->db->query("
            SELECT

                COUNT(*) AS total_requests,

                SUM(
                    CASE
                        WHEN status = 'requested'
                        THEN 1 ELSE 0
                    END
                ) AS pending_requests,

                SUM(
                    CASE
                        WHEN status = 'processing'
                        THEN 1 ELSE 0
                    END
                ) AS processing_requests,

                SUM(
                    CASE
                        WHEN status = 'for_approval'
                        THEN 1 ELSE 0
                    END
                ) AS review_requests,

                SUM(
                    CASE
                        WHEN status = 'paid'
                        THEN 1 ELSE 0
                    END
                ) AS released_requests

            FROM exit_employee_settlements
        ");

        $row =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_requests' =>
            (int)($row['total_requests'] ?? 0),

            'pending_requests' =>
            (int)($row['pending_requests'] ?? 0),

            'processing_requests' =>
            (int)($row['processing_requests'] ?? 0),

            'review_requests' =>
            (int)($row['review_requests'] ?? 0),

            'released_requests' =>
            (int)($row['released_requests'] ?? 0)
        ];
    }


    /* ============================================================
       EXIT STATUS SYNCHRONIZATION
       ============================================================ */

    /**
     * Synchronize the Payroll settlement status
     * back to Exit Management.
     */
    private function updateExitSettlementStatus(
        int $payrollSettlementId,
        string $status
    ): bool {

        if (!in_array(
            $status,
            self::EXIT_STATUSES,
            true
        )) {
            throw new InvalidArgumentException(
                'Invalid Exit Management settlement status.'
            );
        }

        $stmt = $this->db->prepare("
            UPDATE exit_employee_settlements

            SET status = :status

            WHERE payroll_settlement_id =
                :payroll_settlement_id
        ");

        $ok = $stmt->execute([
            ':status' => $status,
            ':payroll_settlement_id' => $payrollSettlementId
        ]);

        if ($ok && $status === 'paid') {
            $sel = $this->db->prepare(
                "SELECT employee_id, exit_case_type FROM exit_employee_settlements WHERE payroll_settlement_id = :pid"
            );

            $sel->execute([':pid' => $payrollSettlementId]);

            $upd = $this->db->prepare(
                "UPDATE em_employees SET employment_status = :new_status, updated_at = NOW() WHERE employee_id = :eid AND LOWER(TRIM(employment_status)) = 'active'"
            );

            while ($r = $sel->fetch(PDO::FETCH_ASSOC)) {
                if (empty($r['employee_id']) || empty($r['exit_case_type'])) {
                    continue;
                }

                $employeeId = (int)$r['employee_id'];
                $caseType = strtolower(trim($r['exit_case_type']));

                if ($caseType === 'resignation') {
                    $newStatus = 'Resigned';
                } elseif ($caseType === 'termination') {
                    $newStatus = 'Terminated';
                } else {
                    continue;
                }

                $upd->execute([':new_status' => $newStatus, ':eid' => $employeeId]);
            }
        }

        return $ok;
    }
}

