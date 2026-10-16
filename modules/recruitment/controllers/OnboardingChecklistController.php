
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../database/db.php';
function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db instanceof PDO) {
        throw new RuntimeException('Database connection failed.');
    }

    $allowedItems = [
        'requirements',
        'medical',
        'contract',
        'biometric',
        'company_id',
        'bdo_cash_card'
    ];

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    $getOnboardingId = static function () use ($db): int {
        $id = filter_var(
            $_POST['onboarding_id'] ?? $_GET['onboarding_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id || $id < 1) {
            jsonResponse(['success' => false, 'message' => 'Invalid onboarding ID.'], 400);
        }

        // Confirm the onboarding record exists.
        $stmt = $db->prepare(
            'SELECT id FROM rao_onboarding WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            jsonResponse(['success' => false, 'message' => 'Onboarding record not found.'], 404);
        }

        return $id;
    };

    $saveItem = static function (
        int $id,
        string $key,
        int $completed,
        ?string $filePath = null,
        ?string $accountNumber = null
    ) use ($db): void {
        $stmt = $db->prepare(
            'INSERT INTO rao_onboarding_checklist
                (onboarding_id, item_key, is_completed, file_path, account_number)
             VALUES
                (:id, :item_key, :completed, :file_path, :account_number)
             ON DUPLICATE KEY UPDATE
                is_completed = VALUES(is_completed),
                file_path = COALESCE(VALUES(file_path), file_path),
                account_number = VALUES(account_number)'
        );

        $stmt->execute([
            'id' => $id,
            'item_key' => $key,
            'completed' => $completed,
            'file_path' => $filePath,
            'account_number' => $accountNumber
        ]);
    };

    if ($action === 'load') {
        $id = $getOnboardingId();

        $stmt = $db->prepare(
            'SELECT item_key, is_completed, file_path, account_number
             FROM rao_onboarding_checklist
             WHERE onboarding_id = :id'
        );
        $stmt->execute(['id' => $id]);

        $items = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[$row['item_key']] = [
                'completed' => (bool)$row['is_completed'],
                'file_path' => $row['file_path'],
                'account_number' => $row['account_number']
            ];
        }

        jsonResponse(['success' => true, 'items' => $items]);
    }

    if ($action === 'save') {
        $id = $getOnboardingId();
        $key = $_POST['item_key'] ?? '';

        if (!in_array($key, $allowedItems, true)) {
            jsonResponse(['success' => false, 'message' => 'Invalid checklist item.'], 400);
        }

        $completed = ($_POST['is_completed'] ?? '') === '1' ? 1 : 0;
        $accountNumber = null;

        if ($key === 'bdo_cash_card') {
            $accountNumber = trim($_POST['account_number'] ?? '');

            if (strlen($accountNumber) > 100) {
                jsonResponse(['success' => false, 'message' => 'Account number is too long.'], 400);
            }
        }

        $saveItem($id, $key, $completed, null, $accountNumber);

        jsonResponse(['success' => true, 'message' => 'Checklist saved.']);
    }


    if ($action === 'upload') {
        $id = $getOnboardingId();
        $key = $_POST['item_key'] ?? '';

        if (!in_array($key, ['medical', 'contract'], true)) {
            jsonResponse([
                'success' => false,
                'message' => 'Uploads are not allowed for this item.'
            ], 400);
        }

        if (
            !isset($_FILES['document']) ||
            $_FILES['document']['error'] !== UPLOAD_ERR_OK
        ) {
            jsonResponse([
                'success' => false,
                'message' => 'Please select a valid file.'
            ], 400);
        }

        $file = $_FILES['document'];

        if (!is_uploaded_file($file['tmp_name'])) {
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

        // Validate the file extension and actual MIME type.
        $allowedTypes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png'
        ];

        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (
            !isset($allowedTypes[$extension]) ||
            $allowedTypes[$extension] !== $mime
        ) {
            jsonResponse([
                'success' => false,
                'message' => 'Only PDF, JPG, and PNG files are allowed.'
            ], 400);
        }

        // Find the original applicant ID linked to this onboarding record.
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
                'message' => 'The applicant ID could not be found for this onboarding record.'
            ], 404);
        }

        $applicationId = (int) $onboarding['application_id'];

        // Existing folder:
        // modules/recruitment/uploads/{application_id}/
        $uploadRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';

        $folder = $uploadRoot
            . DIRECTORY_SEPARATOR
            . $applicationId;

        // Reuse the existing folder; create it only if missing.
        if (
            !is_dir($folder) &&
            !mkdir($folder, 0755, true) &&
            !is_dir($folder)
        ) {
            throw new RuntimeException(
                'Could not create the applicant upload folder.'
            );
        }

        $filename = $key . '_' . bin2hex(random_bytes(12))
            . '.' . $extension;

        $destination = $folder . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException(
                'Could not save the uploaded document.'
            );
        }

        // Store the relative path in the checklist database.
        $relativePath = 'uploads/'
            . $applicationId
            . '/'
            . $filename;

        try {
            $saveItem($id, $key, 1, $relativePath, null);
        } catch (Throwable $e) {
            if (is_file($destination)) {
                unlink($destination);
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

    jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
} catch (Throwable $e) {
    error_log('Onboarding checklist error: ' . $e->getMessage());

    jsonResponse([
        'success' => false,
        'message' => 'The request could not be completed. Check the PHP error log.'
    ], 500);
}
