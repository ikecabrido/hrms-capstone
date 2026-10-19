<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../database/db.php';


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/
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

    /*
    |--------------------------------------------------------------------------
    | DATABASE CONNECTION
    |--------------------------------------------------------------------------
    */
    $database = new Database();
    $db = $database->getConnection();

    if (!$db instanceof PDO) {
        throw new RuntimeException(
            'Database connection failed.'
        );
    }

    $db->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );


    /*
    |--------------------------------------------------------------------------
    | ALLOWED CHECKLIST ITEMS
    |--------------------------------------------------------------------------
    */
    $allowedItems = [
        'requirements',
        'medical',
        'contract',
        'biometric',
        'company_id',
        'bdo_cash_card'
    ];


    /*
    |--------------------------------------------------------------------------
    | CHECKLIST TOTAL
    |--------------------------------------------------------------------------
    */
    $totalChecklistItems = count($allowedItems);


    /*
    |--------------------------------------------------------------------------
    | ACTION
    |--------------------------------------------------------------------------
    */
    $action = $_GET['action']
        ?? $_POST['action']
        ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATE ONBOARDING ID
    |--------------------------------------------------------------------------
    */
    $getOnboardingId = static function () use ($db): int {

        $id = filter_var(
            $_POST['onboarding_id']
                ?? $_GET['onboarding_id']
                ?? null,
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

        $stmt->execute([
            'id' => $id
        ]);


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
    | CALCULATE ONBOARDING PROGRESS
    |--------------------------------------------------------------------------
    |
    | Reads the actual saved checklist items from:
    |
    | rao_onboarding_checklist
    |
    | There are always 6 checklist items:
    |
    | requirements
    | medical
    | contract
    | biometric
    | company_id
    | bdo_cash_card
    |
    |--------------------------------------------------------------------------
    */
    $calculateProgress = static function (int $id) use (
        $db,
        $allowedItems,
        $totalChecklistItems
    ): float {

        if ($totalChecklistItems <= 0) {
            return 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Count completed checklist items
        |--------------------------------------------------------------------------
        */
        $placeholders = [];

        foreach ($allowedItems as $index => $item) {
            $placeholders[] = ':item_' . $index;
        }


        $sql = '
            SELECT COUNT(*) AS completed
            FROM rao_onboarding_checklist
            WHERE onboarding_id = :onboarding_id
              AND item_key IN (' .
            implode(', ', $placeholders) .
            ')
              AND is_completed = 1
        ';


        $stmt = $db->prepare($sql);


        $params = [
            'onboarding_id' => $id
        ];


        foreach ($allowedItems as $index => $item) {
            $params['item_' . $index] = $item;
        }


        $stmt->execute($params);


        $result = $stmt->fetch(PDO::FETCH_ASSOC);


        $completed = (int) (
            $result['completed'] ?? 0
        );


        /*
        |--------------------------------------------------------------------------
        | Calculate percentage
        |--------------------------------------------------------------------------
        */
        $progress = (
            $completed / $totalChecklistItems
        ) * 100;


        return round(
            min(100, max(0, $progress)),
            2
        );
    };


    /*
    |--------------------------------------------------------------------------
    | UPDATE PARENT ONBOARDING PROGRESS
    |--------------------------------------------------------------------------
    |
    | This is the important part.
    |
    | The checklist is stored in rao_onboarding_checklist,
    | while the tracking page reads $record['progress'].
    |
    | Therefore we synchronize rao_onboarding.progress after
    | every checklist change.
    |
    |--------------------------------------------------------------------------
    */
    $updateParentProgress = static function (
        int $id,
        float $progress
    ) use ($db): void {

        $stmt = $db->prepare(
            'UPDATE rao_onboarding
             SET progress = :progress
             WHERE id = :id'
        );


        $stmt->execute([
            'progress' => $progress,
            'id' => $id
        ]);
    };


    /*
    |--------------------------------------------------------------------------
    | SAVE CHECKLIST ITEM
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


        $stmt->execute([
            'id' => $id
        ]);


        $items = [];


        foreach (
            $stmt->fetchAll(PDO::FETCH_ASSOC)
            as $row
        ) {

            $items[$row['item_key']] = [
                'completed' => (bool) $row['is_completed'],
                'file_path' => $row['file_path'],
                'account_number' => $row['account_number']
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Get current progress
        |--------------------------------------------------------------------------
        */
        $progress = $calculateProgress($id);


        /*
        |--------------------------------------------------------------------------
        | Synchronize parent record
        |--------------------------------------------------------------------------
        */
        $updateParentProgress(
            $id,
            $progress
        );


        jsonResponse([
            'success' => true,
            'items' => $items,
            'progress' => $progress
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


        /*
        |--------------------------------------------------------------------------
        | Validate checklist key
        |--------------------------------------------------------------------------
        */
        if (!in_array(
            $key,
            $allowedItems,
            true
        )) {

            jsonResponse([
                'success' => false,
                'message' => 'Invalid checklist item.'
            ], 400);
        }


        /*
        |--------------------------------------------------------------------------
        | Get completion value
        |--------------------------------------------------------------------------
        */
        $completed = (
            ($_POST['is_completed'] ?? '') === '1'
        )
            ? 1
            : 0;


        /*
        |--------------------------------------------------------------------------
        | BDO ACCOUNT NUMBER
        |--------------------------------------------------------------------------
        */
        $accountNumber = null;


        if ($key === 'bdo_cash_card') {

            if (
                isset($_POST['account_number'])
            ) {

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
                    'message' =>
                    'Account number is too long.'
                ], 400);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Save checklist item
        |--------------------------------------------------------------------------
        */
        $saveItem(
            $id,
            $key,
            $completed,
            null,
            $accountNumber
        );


        /*
        |--------------------------------------------------------------------------
        | RECALCULATE PROGRESS
        |--------------------------------------------------------------------------
        */
        $progress = $calculateProgress($id);


        /*
        |--------------------------------------------------------------------------
        | UPDATE rao_onboarding.progress
        |--------------------------------------------------------------------------
        */
        $updateParentProgress(
            $id,
            $progress
        );


        /*
        |--------------------------------------------------------------------------
        | RETURN RESULT
        |--------------------------------------------------------------------------
        */
        jsonResponse([
            'success' => true,
            'message' => 'Checklist saved.',
            'progress' => $progress
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


        /*
        |--------------------------------------------------------------------------
        | Only medical and contract allow uploads.
        |--------------------------------------------------------------------------
        */
        if (!in_array(
            $key,
            ['medical', 'contract'],
            true
        )) {

            jsonResponse([
                'success' => false,
                'message' =>
                'Uploads are not allowed for this item.'
            ], 400);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate uploaded file
        |--------------------------------------------------------------------------
        */
        if (
            !isset($_FILES['document']) ||
            !isset($_FILES['document']['error']) ||
            $_FILES['document']['error'] !== UPLOAD_ERR_OK
        ) {

            jsonResponse([
                'success' => false,
                'message' =>
                'Please select a valid file.'
            ], 400);
        }


        $file = $_FILES['document'];


        if (
            empty($file['tmp_name']) ||
            !is_uploaded_file(
                $file['tmp_name']
            )
        ) {

            jsonResponse([
                'success' => false,
                'message' =>
                'Invalid uploaded file.'
            ], 400);
        }


        /*
        |--------------------------------------------------------------------------
        | 10 MB LIMIT
        |--------------------------------------------------------------------------
        */
        if (
            $file['size'] >
            10 * 1024 * 1024
        ) {

            jsonResponse([
                'success' => false,
                'message' =>
                'Maximum file size is 10 MB.'
            ], 400);
        }


        /*
        |--------------------------------------------------------------------------
        | Allowed MIME types
        |--------------------------------------------------------------------------
        */
        $allowedTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];


        $extension = strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );


        if (
            !isset(
                $allowedTypes[$extension]
            )
        ) {

            jsonResponse([
                'success' => false,
                'message' =>
                'Only PDF, JPG, and PNG files are allowed.'
            ], 400);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate actual MIME type
        |--------------------------------------------------------------------------
        */
        if (!class_exists('finfo')) {

            throw new RuntimeException(
                'The PHP Fileinfo extension is not enabled.'
            );
        }


        $finfo = new finfo(
            FILEINFO_MIME_TYPE
        );


        $mime = $finfo->file(
            $file['tmp_name']
        );


        if (
            $allowedTypes[$extension] !== $mime
        ) {

            jsonResponse([
                'success' => false,
                'message' =>
                'The uploaded file type is invalid.'
            ], 400);
        }


        /*
        |--------------------------------------------------------------------------
        | Find application ID
        |--------------------------------------------------------------------------
        */
        $stmt = $db->prepare(
            'SELECT application_id
             FROM rao_onboarding
             WHERE id = :id
             LIMIT 1'
        );


        $stmt->execute([
            'id' => $id
        ]);


        $onboarding =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            !$onboarding ||
            !isset(
                $onboarding['application_id']
            ) ||
            (int) $onboarding['application_id'] < 1
        ) {

            jsonResponse([
                'success' => false,
                'message' =>
                'The applicant ID could not be found for this onboarding record.'
            ], 404);
        }


        $applicationId =
            (int) $onboarding['application_id'];


        /*
        |--------------------------------------------------------------------------
        | Applicant upload folder
        |--------------------------------------------------------------------------
        |
        | modules/recruitment/uploads/{application_id}/
        |
        |--------------------------------------------------------------------------
        */
        $uploadRoot =
            dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'uploads';


        $folder =
            $uploadRoot
            . DIRECTORY_SEPARATOR
            . $applicationId;


        /*
        |--------------------------------------------------------------------------
        | Create folder if needed
        |--------------------------------------------------------------------------
        */
        if (
            !is_dir($folder) &&
            !mkdir(
                $folder,
                0755,
                true
            ) &&
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


        /*
        |--------------------------------------------------------------------------
        | Generate safe filename
        |--------------------------------------------------------------------------
        */
        $filename =
            $key . '_'
            . bin2hex(
                random_bytes(12)
            )
            . '.'
            . $extension;


        $destination =
            $folder
            . DIRECTORY_SEPARATOR
            . $filename;


        /*
        |--------------------------------------------------------------------------
        | Move uploaded file
        |--------------------------------------------------------------------------
        */
        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $destination
            )
        ) {

            throw new RuntimeException(
                'Could not save the uploaded document.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Relative database path
        |--------------------------------------------------------------------------
        */
        $relativePath =
            'uploads/'
            . $applicationId
            . '/'
            . $filename;


        try {

            /*
            |--------------------------------------------------------------------------
            | Save checklist item as completed
            |--------------------------------------------------------------------------
            */
            $saveItem(
                $id,
                $key,
                1,
                $relativePath,
                null
            );


            /*
            |--------------------------------------------------------------------------
            | RECALCULATE PROGRESS
            |--------------------------------------------------------------------------
            */
            $progress =
                $calculateProgress($id);


            /*
            |--------------------------------------------------------------------------
            | UPDATE PARENT PROGRESS
            |--------------------------------------------------------------------------
            */
            $updateParentProgress(
                $id,
                $progress
            );
        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Remove uploaded file if DB save fails
            |--------------------------------------------------------------------------
            */
            if (is_file($destination)) {
                @unlink($destination);
            }

            throw $e;
        }


        /*
        |--------------------------------------------------------------------------
        | Return upload result
        |--------------------------------------------------------------------------
        */
        jsonResponse([
            'success' => true,
            'message' =>
            'File uploaded successfully.',
            'file_path' => $relativePath,
            'file_name' => $filename,
            'progress' => $progress
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

    error_log(
        'OnboardingChecklistController ERROR: '
            . $e->getMessage()
    );

    error_log(
        'File: '
            . $e->getFile()
    );

    error_log(
        'Line: '
            . $e->getLine()
    );

    error_log(
        'Trace: '
            . $e->getTraceAsString()
    );


    jsonResponse([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
