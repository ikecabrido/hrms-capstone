<?php
/**
 * Shared profile-settings endpoint (bio + profile picture) for every Learning
 * role. Any signed-in employee may update their own profile extras.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 4) . '/classes/CSRF.php';
require_once dirname(__FILE__, 4) . '/classes/UserProfile.php';
require_once dirname(__FILE__, 4) . '/classes/UserSession.php';
require_once dirname(__FILE__, 6) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

CSRF::requireValid();

try {
    $employeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($employeeId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();
    $userProfile = new UserProfile($pdo);

    $action = trim((string) ($_POST['action'] ?? 'save_bio'));

    if ($action === 'save_bio') {
        $bio = (string) ($_POST['bio'] ?? '');
        $result = $userProfile->updateBio($employeeId, $bio);
        http_response_code($result['success'] ? 200 : 422);
        echo json_encode($result);
        exit;
    }

    if ($action === 'remove_photo') {
        $existing = $userProfile->getForEmployee($employeeId);
        if (!empty($existing['profile_photo'])) {
            $uploadDir = dirname(__DIR__, 3) . '/uploads/profile-photos';
            $oldPath = $uploadDir . '/' . basename($existing['profile_photo']);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
        $result = $userProfile->updatePhoto($employeeId, null);
        echo json_encode($result);
        exit;
    }

    if ($action === 'upload_photo') {
        if (empty($_FILES['profile_photo'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
            exit;
        }

        $file = $_FILES['profile_photo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Upload failed (error code ' . (int) $file['error'] . ').']);
            exit;
        }

        $maxBytes = 2 * 1024 * 1024; // 2 MB
        if ((int) $file['size'] > $maxBytes) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Picture must be 2 MB or smaller.']);
            exit;
        }

        // Verify the file really is an image; derive a safe extension from the sniffed MIME.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WebP images are allowed.']);
            exit;
        }

        // getimagesize re-checks that the payload decodes as a real image.
        if (@getimagesize($file['tmp_name']) === false) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'The uploaded file is not a valid image.']);
            exit;
        }

        $uploadDir = dirname(__DIR__, 3) . '/uploads/profile-photos';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Upload directory is not available.']);
            exit;
        }

        $filename = 'employee_' . $employeeId . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Could not save the uploaded picture.']);
            exit;
        }

        // Remove the previous picture file, if any, now that the new one is stored.
        $existing = $userProfile->getForEmployee($employeeId);
        if (!empty($existing['profile_photo'])) {
            $oldPath = $uploadDir . '/' . basename($existing['profile_photo']);
            if (is_file($oldPath) && $oldPath !== $destination) {
                @unlink($oldPath);
            }
        }

        $result = $userProfile->updatePhoto($employeeId, $filename);
        $result['photo_url'] = 'uploads/profile-photos/' . $filename;
        echo json_encode($result);
        exit;
    }

    if ($action === 'save_contact') {
        $mobile  = trim((string) ($_POST['mobile_no'] ?? ''));
        $phone   = trim((string) ($_POST['phone_no'] ?? ''));
        $address = trim((string) ($_POST['current_address'] ?? ''));

        if (mb_strlen($mobile) > 20 || mb_strlen($phone) > 20) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Phone numbers must be 20 characters or fewer.']);
            exit;
        }

        if (mb_strlen($address) > 500) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Address must be 500 characters or fewer.']);
            exit;
        }

        // Phone fields accept digits, spaces and the usual punctuation only.
        if (!preg_match('/^[0-9+()\- ]*$/', $mobile) || !preg_match('/^[0-9+()\- ]*$/', $phone)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Phone numbers may only contain digits, spaces and + ( ) -.']);
            exit;
        }

        $stmt = $pdo->prepare(
            "UPDATE em_employees
             SET mobile_no = :mobile, phone_no = :phone, current_address = :address, updated_at = NOW()
             WHERE employee_id = :eid"
        );
        $stmt->execute([
            ':mobile'  => $mobile === '' ? null : $mobile,
            ':phone'   => $phone === '' ? null : $phone,
            ':address' => $address === '' ? null : $address,
            ':eid'     => $employeeId,
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Contact details updated.',
            'contact' => [
                'mobile_no'       => $mobile,
                'phone_no'        => $phone,
                'current_address' => $address,
            ],
        ]);
        exit;
    }

    if ($action === 'revoke_other_sessions') {
        $userSession = new UserSession($pdo);
        $revoked = $userSession->revokeOthers($employeeId, UserSession::currentToken());
        echo json_encode(['success' => true, 'revoked' => $revoked]);
        exit;
    }

    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
}
exit;
