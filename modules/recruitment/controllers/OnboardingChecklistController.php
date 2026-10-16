
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../database/db.php';

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db instanceof PDO) {
        throw new RuntimeException('Database connection failed.');
    }

    // Return database errors as exceptions.
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $allowedItems = [
        'requirements',
        'medical',
        'contract',
        'biometric',
        'company_id',
        'bdo_cash_card'
    ];

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Validate onboarding ID
    |--------------------------------------------------------------------------
    */
    $getOnboardingId = static function () use ($db): int {
        $id = filter_var(
            $_POST['onboarding_id'] ?? $_GET['onboarding_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id || $id < 1) {
            jsonResponse([
                'success' => false,
                'message' => 'Invalid onboarding ID.'
            ], 400);
        }

        $stmt = $db->prepare(
            'SELECT id
             FROM rao_onboarding
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute(['id' => $id]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            jsonResponse([
                'success' => false,
                'message' => 'Onboarding record not found.'
            ], 404);
        }

        return (int) $id;
    };

    /*
    |--------------------------------------------------------------------------
    | Save checklist item
    |--------------------------------------------------------------------------
    */
    $saveItem = static function (
        int $id,
        string $key,
        int $completed,
        ?string $filePath = null,
        ?string $accountNumber = null
    ) use ($db): void {
        $stmt = $db->prepare(
            'INSERT INTO rao_onboarding_checklist
                (
                    onboarding_id,
                    item_key,
                    is_completed,
                    file_path,
                    account_number
                )
             VALUES
                (
                    :id,
                    :item_key,
                    :completed,
                    :file_path,
                    :account_number
                )
             ON DUPLICATE KEY UPDATE
                is_completed = VALUES(is_completed),
                file_path = COALESCE(
                    VALUES(file_path),
                    file_path
                ),
                account_number = CASE
                    WHEN VALUES(account_number) IS NOT NULL
                    THEN VALUES(account_number)
                    ELSE account_number
                END'
        );

        $stmt->execute([
            'id' => $id,
            'item_key' => $key,
            'completed' => $completed,
            'file_path' => $filePath,
            'account_number' => $accountNumber
        ]);
    };

    /*
    |--------------------------------------------------------------------------
    | LOAD CHECKLIST
    |--------------------------------------------------------------------------
    */
    if ($action === 'load') {
        $id = $getOnboardingId();

        $stmt = $db->prepare(
            'SELECT
                item_key,
                is_completed,
                file_path,
                account_number
             FROM rao_onboarding_checklist
             WHERE onboarding_id = :id'
        );

        $stmt->execute(['id' => $id]);

        $items = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[$row['item_key']] = [
                'completed' => (bool) $row['is_completed'],
                'file_path' => $row['file_path'],
                'account_number' => $row['account_number']
            ];
        }

        jsonResponse([
            'success' => true,
            'items' => $items
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE CHECKLIST ITEM
    |--------------------------------------------------------------------------
    */
    if ($action === 'save') {
        $id = $getOnboardingId();

        $key = $_POST['item_key'] ?? '';

        if (!in_array($key, $allowedItems, true)) {
            jsonResponse([
                'success' => false,
                'message' => 'Invalid checklist item.'
            ], 400);
        }

        $completed = (
            ($_POST['is_completed'] ?? '') === '1'
        ) ? 1 : 0;

        $accountNumber = null;

        if ($key === 'bdo_cash_card') {
            if (isset($_POST['account_number'])) {
                $accountNumber = trim(
                    (string) $_POST['account_number']
                );
            }

            if (
                $accountNumber !== null &&
                strlen($accountNumber) > 100
            ) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Account number is too long.'
                ], 400);
            }
        }

        $saveItem(
            $id,
            $key,
            $completed,
            null,
            $accountNumber
        );

        jsonResponse([
            'success' => true,
            'message' => 'Checklist saved.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPLOAD CHECKLIST DOCUMENT
    |--------------------------------------------------------------------------
    */
    if ($action === 'upload') {
        $id = $getOnboardingId();

        $key = $_POST['item_key'] ?? '';

        // Only medical and contract documents can be uploaded.
        if (!in_array($key, ['medical', 'contract'], true)) {
            jsonResponse([
                'success' => false,
                'message' => 'Uploads are not allowed for this item.'
            ], 400);
        }

        if (
            !isset($_FILES['document']) ||
            !isset($_FILES['document']['error']) ||
            $_FILES['document']['error'] !== UPLOAD_ERR_OK
        ) {
            jsonResponse([
                'success' => false,
                'message' => 'Please select a valid file.'
            ], 400);
        }

        $file = $_FILES['document'];

        if (
            empty($file['tmp_name']) ||
            !is_uploaded_file($file['tmp_name'])
        ) {
            jsonResponse([
                'success' => false,
                'message' => 'Invalid uploaded file.'
            ], 400);
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            jsonResponse([
                'success' => false,
                'message' => 'Maximum file size is 10 MB.'
            ], 400);
        }

        // Validate file extension and actual MIME type.
        $allowedTypes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png'
        ];

        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        if (!isset($allowedTypes[$extension])) {
            jsonResponse([
                'success' => false,
                'message' => 'Only PDF, JPG, and PNG files are allowed.'
            ], 400);
        }

        if (!class_exists('finfo')) {
            throw new RuntimeException(
                'The PHP Fileinfo extension is not enabled.'
            );
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if ($allowedTypes[$extension] !== $mime) {
            jsonResponse([
                'success' => false,
                'message' => 'The uploaded file type is invalid.'
            ], 400);
        }

        /*
        | Find the original application ID associated with this
        | onboarding record.
        */
        $stmt = $db->prepare(
            'SELECT application_id
             FROM rao_onboarding
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute(['id' => $id]);

        $onboarding = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$onboarding ||
            !isset($onboarding['application_id']) ||
            (int) $onboarding['application_id'] < 1
        ) {
            jsonResponse([
                'success' => false,
                'message' =>
                'The applicant ID could not be found for this onboarding record.'
            ], 404);
        }

        $applicationId = (int) $onboarding['application_id'];

        /*
        | Save files in the existing applicant folder:
        | modules/recruitment/uploads/{application_id}/
        */
        $uploadRoot = dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'uploads';

        $folder = $uploadRoot
            . DIRECTORY_SEPARATOR
            . $applicationId;

        if (
            !is_dir($folder) &&
            !mkdir($folder, 0755, true) &&
            !is_dir($folder)
        ) {
            throw new RuntimeException(
                'Could not create the applicant upload folder.'
            );
        }

        if (!is_writable($folder)) {
            throw new RuntimeException(
                'The applicant upload folder is not writable.'
            );
        }

        $filename = $key . '_'
            . bin2hex(random_bytes(12))
            . '.'
            . $extension;

        $destination = $folder
            . DIRECTORY_SEPARATOR
            . $filename;

        if (!move_uploaded_file(
            $file['tmp_name'],
            $destination
        )) {
            throw new RuntimeException(
                'Could not save the uploaded document.'
            );
        }

        // Store a relative path in the database.
        $relativePath = 'uploads/'
            . $applicationId
            . '/'
            . $filename;

        try {
            $saveItem(
                $id,
                $key,
                1,
                $relativePath,
                null
            );
        } catch (Throwable $e) {
            // Remove the new file if the database save fails.
            if (is_file($destination)) {
                @unlink($destination);
            }

            throw $e;
        }

        jsonResponse([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'file_path' => $relativePath,
            'file_name' => $filename
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UNKNOWN ACTION
    |--------------------------------------------------------------------------
    */
    jsonResponse([
        'success' => false,
        'message' => 'Unknown action.'
    ], 400);
} catch (Throwable $e) {
    // Keep the technical details in the server's error log.
    error_log(
        'Onboarding checklist error: '
            . $e->getMessage()
            . ' | File: ' . $e->getFile()
            . ' | Line: ' . $e->getLine()
    );

    // Do not expose database credentials or internal paths to the browser.
    jsonResponse([
        'success' => false,
        'message' => 'The request could not be completed. Check the PHP error log.'
    ], 500);
}
