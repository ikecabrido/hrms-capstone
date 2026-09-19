<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/allowanceDeductionModel.php';

class AllowanceDeductionController
{
    private PDO $db;
    private AllowanceDeductionModel $model;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();

        $this->model = new AllowanceDeductionModel(
            $this->db
        );
    }


    /* ============================================================
       EMPLOYEES
       ============================================================ */

    /**
     * Get active employees available for manual deductions.
     */
    public function getEmployees(): array
    {
        return $this->model->getEmployees();
    }

    /**
     * Search employees by code (with or without the "EMP-"
     * prefix / leading zeros), first name, or last name, for
     * the Deductions filter bar's employee search field.
     */
    public function searchEmployees(string $search): array
    {
        return $this->model->searchEmployees($search);
    }


    /* ============================================================
       PAYROLL PERIODS
       ============================================================ */

    /**
     * Get payroll periods that are still open.
     *
     * Used by the deduction form.
     */
    public function getOpenPeriods(): array
    {
        return $this->model->getOpenPeriods();
    }

    /**
     * Search payroll periods (open and closed) for the
     * Deductions filter bar's period search field.
     */
    public function searchPeriods(string $search): array
    {
        return $this->model->searchPeriods($search);
    }

    /**
     * Get one payroll period.
     */
    public function getPeriod(int $periodId): ?array
    {
        if ($periodId <= 0) {
            return null;
        }

        return $this->model->getPeriod($periodId);
    }

    /**
     * Determine whether a payroll period is closed.
     */
    public function isPeriodClosed(int $periodId): bool
    {
        $period = $this->getPeriod($periodId);

        if (!$period) {
            throw new RuntimeException(
                'Payroll period not found.'
            );
        }

        return strtolower(
            (string)($period['status'] ?? '')
        ) === 'closed';
    }

    /**
     * Ensure a payroll period exists and is still open.
     */
    private function validateOpenPeriod(int $periodId): void
    {
        if ($periodId <= 0) {
            throw new InvalidArgumentException(
                'Please select a payroll period.'
            );
        }

        $period = $this->getPeriod($periodId);

        if (!$period) {
            throw new RuntimeException(
                'Payroll period not found.'
            );
        }

        if (
            strtolower(
                (string)($period['status'] ?? '')
            ) === 'closed'
        ) {
            throw new RuntimeException(
                'This payroll period is already closed. '
                    . 'Deductions can no longer be modified.'
            );
        }
    }


    /* ============================================================
       DEDUCTION LIST
       ============================================================ */

    /**
     * Get manual deduction adjustments.
     *
     * Optional filters:
     * - periodId
     * - employeeId
     * - deductionSubtype
     */
    public function index(
        ?int $periodId = null,
        ?int $employeeId = null,
        ?string $deductionSubtype = null
    ): array {

        return $this->model->getAll(
            $periodId,
            $employeeId,
            $deductionSubtype
        );
    }


    /**
     * Get one deduction adjustment.
     */
    public function show(int $adjustmentId): ?array
    {
        if ($adjustmentId <= 0) {
            return null;
        }

        return $this->model->getById(
            $adjustmentId
        );
    }


    /**
     * Get all manual deductions for an employee
     * in a specific payroll period.
     */
    public function getEmployeeAdjustments(
        int $employeeId,
        int $periodId
    ): array {

        if ($employeeId <= 0) {
            throw new InvalidArgumentException(
                'Invalid employee.'
            );
        }

        $this->validateOpenPeriod($periodId);

        return $this->model->getEmployeeAdjustments(
            $employeeId,
            $periodId
        );
    }


    /* ============================================================
       SUMMARY
       ============================================================ */

    /**
     * Get deduction summary cards.
     *
     * Example UI:
     * - Number of deductions
     * - Total deduction amount
     */
    public function getSummary(
        ?int $periodId = null
    ): array {

        return $this->model->getSummary(
            $periodId
        );
    }


    /**
     * Get total deductions.
     *
     * Optional filter by payroll period
     * and deduction subtype.
     */
    public function getTotal(
        ?int $periodId = null,
        ?string $deductionSubtype = null
    ): float {

        return $this->model->getTotal(
            $periodId,
            $deductionSubtype
        );
    }


    /* ============================================================
       CREATE
       ============================================================ */

    /**
     * Create a manual employee deduction.
     *
     * Examples:
     *
     * - Damaged school property
     * - Salary advance repayment
     * - Employee cash advance
     * - Loan repayment
     * - Other approved employee deduction
     */
    public function store(array $data): int
    {
        $employeeId = (int)(
            $data['employee_id'] ?? 0
        );

        $periodId = (int)(
            $data['period_id'] ?? 0
        );

        $description = trim(
            (string)($data['description'] ?? '')
        );

        $amount = (float)(
            $data['amount'] ?? 0
        );

        $deductionSubtype = strtolower(
            trim(
                (string)(
                    $data['deduction_subtype']
                    ?? 'other'
                )
            )
        );

        $filePath = $data['file_path'] ?? null;


        /* --------------------------------------------------------
           VALIDATION
           -------------------------------------------------------- */

        if ($employeeId <= 0) {
            throw new InvalidArgumentException(
                'Please select an employee.'
            );
        }

        if ($periodId <= 0) {
            throw new InvalidArgumentException(
                'Please select a payroll period.'
            );
        }

        if ($description === '') {
            throw new InvalidArgumentException(
                'Please provide a deduction description.'
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Deduction amount must be greater than zero.'
            );
        }

        $this->validateOpenPeriod(
            $periodId
        );


        /* --------------------------------------------------------
           CREATE
           -------------------------------------------------------- */

        return $this->model->create(
            $employeeId,
            $periodId,
            $description,
            $amount,
            $deductionSubtype,
            $filePath
        );
    }


    /* ============================================================
       UPDATE
       ============================================================ */

    /**
     * Update an existing manual deduction.
     */
    public function update(array $data): bool
    {
        $adjustmentId = (int)(
            $data['adjustment_id'] ?? 0
        );

        $employeeId = (int)(
            $data['employee_id'] ?? 0
        );

        $periodId = (int)(
            $data['period_id'] ?? 0
        );

        $description = trim(
            (string)($data['description'] ?? '')
        );

        $amount = (float)(
            $data['amount'] ?? 0
        );

        $deductionSubtype = strtolower(
            trim(
                (string)(
                    $data['deduction_subtype']
                    ?? 'other'
                )
            )
        );


        /*
         * Only update the attachment when the caller
         * explicitly supplies a new file path.
         */
        $filePath = null;

        if (
            array_key_exists(
                'file_path',
                $data
            )
        ) {
            $filePath = $data['file_path'];
        }


        /* --------------------------------------------------------
           VALIDATION
           -------------------------------------------------------- */

        if ($adjustmentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid deduction adjustment.'
            );
        }

        if ($employeeId <= 0) {
            throw new InvalidArgumentException(
                'Please select an employee.'
            );
        }

        if ($periodId <= 0) {
            throw new InvalidArgumentException(
                'Please select a payroll period.'
            );
        }

        if ($description === '') {
            throw new InvalidArgumentException(
                'Please provide a deduction description.'
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Deduction amount must be greater than zero.'
            );
        }


        /*
         * Make sure the deduction actually exists.
         */
        $existing = $this->model->getById(
            $adjustmentId
        );

        if (!$existing) {
            throw new RuntimeException(
                'Deduction adjustment not found.'
            );
        }


        /*
         * Important:
         *
         * Validate the CURRENT period as well as the
         * submitted period.
         *
         * This prevents someone from editing a deduction
         * belonging to a closed payroll period by simply
         * submitting another period ID.
         */
        $existingPeriodId = (int)(
            $existing['period_id']
            ?? $existing['payroll_period_id']
            ?? 0
        );

        if ($existingPeriodId > 0) {
            $this->validateOpenPeriod(
                $existingPeriodId
            );
        }

        $this->validateOpenPeriod(
            $periodId
        );


        /* --------------------------------------------------------
           UPDATE
           -------------------------------------------------------- */

        return $this->model->update(
            $adjustmentId,
            $employeeId,
            $periodId,
            $description,
            $amount,
            $deductionSubtype,
            $filePath
        );
    }


    /* ============================================================
       DELETE
       ============================================================ */

    /**
     * Delete a manual deduction.
     *
     * Deletion is allowed only while the associated
     * payroll period remains open.
     */
    public function delete(
        int $adjustmentId
    ): bool {

        if ($adjustmentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid deduction adjustment.'
            );
        }


        /*
         * Get the existing deduction first so we know
         * which payroll period it belongs to.
         */
        $existing = $this->model->getById(
            $adjustmentId
        );

        if (!$existing) {
            throw new RuntimeException(
                'Deduction adjustment not found.'
            );
        }

        $periodId = (int)(
            $existing['period_id']
            ?? $existing['payroll_period_id']
            ?? 0
        );

        if ($periodId <= 0) {
            throw new RuntimeException(
                'The deduction is not associated with a valid payroll period.'
            );
        }


        /*
         * Do not allow deletion after the payroll period
         * has been closed.
         */
        $this->validateOpenPeriod(
            $periodId
        );


        return $this->model->delete(
            $adjustmentId
        );
    }


    /* ============================================================
       EMPLOYEE PERIOD TOTAL
       ============================================================ */

    /**
     * Get the total manual deductions for an employee
     * in a specific payroll period.
     */
    public function getEmployeeAdjustmentTotal(
        int $employeeId,
        int $periodId
    ): float {

        if ($employeeId <= 0) {
            throw new InvalidArgumentException(
                'Invalid employee.'
            );
        }

        if ($periodId <= 0) {
            throw new InvalidArgumentException(
                'Invalid payroll period.'
            );
        }

        return $this->model->getEmployeeAdjustmentTotal(
            $employeeId,
            $periodId
        );
    }


    /* ============================================================
       FILE UPLOAD
       ============================================================ */

    /**
     * Validate and store a supporting document.
     *
     * Accepted:
     * - PDF
     * - JPG
     * - JPEG
     * - PNG
     *
     * Maximum size:
     * - 5 MB
     */
    public function handleFileUpload(
        array $file
    ): ?string {

        if (
            !isset($file['tmp_name'])
            || !is_uploaded_file(
                $file['tmp_name']
            )
        ) {
            return null;
        }

        if (
            ($file['error'] ?? UPLOAD_ERR_NO_FILE)
            !== UPLOAD_ERR_OK
        ) {
            throw new RuntimeException(
                'Failed to upload supporting document.'
            );
        }


        /* --------------------------------------------------------
           FILE SIZE
           -------------------------------------------------------- */

        $maxSize = 5 * 1024 * 1024;

        if (
            (int)$file['size'] > $maxSize
        ) {
            throw new RuntimeException(
                'Supporting document must not exceed 5 MB.'
            );
        }


        /* --------------------------------------------------------
           FILE TYPE
           -------------------------------------------------------- */

        $allowedExtensions = [
            'pdf',
            'jpg',
            'jpeg',
            'png'
        ];

        $extension = strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );

        if (
            !in_array(
                $extension,
                $allowedExtensions,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid supporting document type.'
            );
        }


        /* --------------------------------------------------------
           MIME TYPE VALIDATION
           -------------------------------------------------------- */

        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png'
        ];

        $finfo = new finfo(
            FILEINFO_MIME_TYPE
        );

        $mimeType = $finfo->file(
            $file['tmp_name']
        );

        if (
            !in_array(
                $mimeType,
                $allowedMimeTypes,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid supporting document.'
            );
        }


        /* --------------------------------------------------------
           UPLOAD DIRECTORY
           -------------------------------------------------------- */

        $uploadDirectory =
            __DIR__
            . '/../uploads/deductions/';

        if (!is_dir($uploadDirectory)) {

            if (
                !mkdir(
                    $uploadDirectory,
                    0755,
                    true
                )
            ) {
                throw new RuntimeException(
                    'Unable to create upload directory.'
                );
            }
        }


        /* --------------------------------------------------------
           UNIQUE FILE NAME
           -------------------------------------------------------- */

        $filename =
            'deduction_'
            . bin2hex(
                random_bytes(16)
            )
            . '.'
            . $extension;

        $destination =
            $uploadDirectory
            . $filename;


        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $destination
            )
        ) {
            throw new RuntimeException(
                'Unable to save supporting document.'
            );
        }


        return 'uploads/deductions/'
            . $filename;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {

    require_once __DIR__ . '/../../../auth/session.php';
    require_once __DIR__ . '/../../../auth/guard.php';

    header('Content-Type: application/json');

    function dd_respond(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        echo json_encode($payload);
        exit;
    }

    $controller = new AllowanceDeductionController();
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {

        /*
         * ------------------------------------------------------
         * LIST DEDUCTIONS (+ summary for the current filter)
         * ------------------------------------------------------
         */
        case 'list': {
                $periodId = !empty($_GET['period_id']) ? (int)$_GET['period_id'] : null;
                $employeeId = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
                $subtype = !empty($_GET['deduction_subtype']) ? $_GET['deduction_subtype'] : null;

                try {
                    $records = $controller->index($periodId, $employeeId, $subtype);
                    $summary = $controller->getSummary($periodId);

                    dd_respond([
                        'success' => true,
                        'data' => $records,
                        'summary' => $summary
                    ]);
                } catch (Throwable $e) {
                    dd_respond([
                        'success' => false,
                        'message' => $e->getMessage()
                    ], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * SINGLE DEDUCTION
         * ------------------------------------------------------
         */
        case 'get': {
                $id = intval($_GET['id'] ?? 0);
                if (!$id) {
                    dd_respond(['success' => false, 'message' => 'No deduction ID provided.']);
                }

                $record = $controller->show($id);
                if (!$record) {
                    dd_respond(['success' => false, 'message' => 'Deduction adjustment not found.'], 404);
                }

                dd_respond(['success' => true, 'data' => $record]);
                break;
            }

            /*
         * ------------------------------------------------------
         * ADD/EDIT FORM OPTIONS
         * ------------------------------------------------------
         */
        case 'employees': {
                dd_respond(['success' => true, 'data' => $controller->getEmployees()]);
                break;
            }

        case 'open_periods': {
                dd_respond(['success' => true, 'data' => $controller->getOpenPeriods()]);
                break;
            }

            /*
         * ------------------------------------------------------
         * FILTER BAR SEARCH (employee / payroll period)
         * ------------------------------------------------------
         */
        case 'search_employees': {
                $term = trim($_GET['q'] ?? '');
                if ($term === '') {
                    dd_respond(['success' => true, 'data' => []]);
                }

                try {
                    $results = $controller->searchEmployees($term);
                    dd_respond(['success' => true, 'data' => $results]);
                } catch (Throwable $e) {
                    dd_respond(['success' => false, 'message' => $e->getMessage()], 500);
                }
                break;
            }

        case 'search_periods': {
                $term = trim($_GET['q'] ?? '');
                if ($term === '') {
                    dd_respond(['success' => true, 'data' => []]);
                }

                try {
                    $results = $controller->searchPeriods($term);
                    dd_respond(['success' => true, 'data' => $results]);
                } catch (Throwable $e) {
                    dd_respond(['success' => false, 'message' => $e->getMessage()], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * CREATE
         * ------------------------------------------------------
         */
        case 'create': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    dd_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $data = $_POST;

                try {
                    if (!empty($_FILES['supporting_document']['name'])) {
                        $data['file_path'] = $controller->handleFileUpload($_FILES['supporting_document']);
                    }

                    $newId = $controller->store($data);

                    dd_respond([
                        'success' => true,
                        'message' => 'Deduction added successfully.',
                        'data' => ['adjustment_id' => $newId]
                    ]);
                } catch (InvalidArgumentException $e) {
                    dd_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    dd_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    dd_respond(['success' => false, 'message' => 'Failed to add deduction.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * UPDATE
         * ------------------------------------------------------
         */
        case 'update': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    dd_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $data = $_POST;

                try {
                    if (!empty($_FILES['supporting_document']['name'])) {
                        $data['file_path'] = $controller->handleFileUpload($_FILES['supporting_document']);
                    }

                    $controller->update($data);

                    dd_respond([
                        'success' => true,
                        'message' => 'Deduction updated successfully.'
                    ]);
                } catch (InvalidArgumentException $e) {
                    dd_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    dd_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    dd_respond(['success' => false, 'message' => 'Failed to update deduction.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * DELETE
         * ------------------------------------------------------
         */
        case 'delete': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    dd_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                $id = intval($_POST['adjustment_id'] ?? 0);

                try {
                    $controller->delete($id);

                    dd_respond([
                        'success' => true,
                        'message' => 'Deduction deleted successfully.'
                    ]);
                } catch (InvalidArgumentException $e) {
                    dd_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    dd_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    dd_respond(['success' => false, 'message' => 'Failed to delete deduction.'], 500);
                }
                break;
            }

        default: {
                dd_respond(['success' => false, 'message' => 'Unknown action.'], 400);
            }
    }
}
