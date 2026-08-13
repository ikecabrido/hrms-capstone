<?php

namespace App\Controllers;

use Exception;
use App\Models\Users;
use App\Models\Profile;
use App\Models\Employee;

class ProfileController
{
    private Users $userModel;
    private Profile $profileModel;
    private Employee $employeeModel;

    public function __construct()
    {
        $this->userModel = new Users();
        $this->profileModel = new Profile();
        $this->employeeModel = new Employee();
    }
    public function index()
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            header(
                'Location: /hrms-capstone/modules/portal/index.php?url=auth-index'
            );
            exit;
        }

        $userInfos = $this->userModel->findById($userId);
        $employeeProfileInfo = $this->employeeModel->findByUserId($userId);

        $title = "Employee Profile";
        $content = __DIR__ . '/../views/employee-portal/profile/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function updatePassword(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $password = trim($_POST['password'] ?? '');

        try {

            if (!$userId) {
                $_SESSION['error'] = "You must be logged in to change your password.";

            } elseif ($password === '') {
                $_SESSION['error'] = "Password is required.";

            } elseif (strlen($password) < 6) {
                $_SESSION['error'] = "Password must be at least 6 characters.";

            } else {

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                if (
                    $this->profileModel->updatePassword(
                        $userId,
                        $hashedPassword
                    )
                ) {

                    $_SESSION['success'] = "Password updated successfully.";

                } else {

                    $_SESSION['error'] = "Failed to update password.";
                }
            }

        } catch (Exception $e) {

            $_SESSION['error'] = $e->getMessage()
                ?: "Something went wrong while updating the password.";
        }

        $redirectTo = $_SERVER['HTTP_REFERER']
            ?? 'index.php?url=user-profile';

        header("Location: " . $redirectTo);
        exit;
    }
    public function updateProfile(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $_SESSION['error'] = 'You must be logged in.';
            header('Location: index.php?url=user-profile');
            exit;
        }

        try {
            $data = [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'middle_name' => trim($_POST['middle_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'suffix' => trim($_POST['suffix'] ?? ''),
                'gender' => trim($_POST['gender'] ?? ''),
                'birth_date' => $_POST['birth_date'] ?? null,
                'civil_status' => trim($_POST['civil_status'] ?? ''),
                'mobile_no' => trim($_POST['mobile_no'] ?? ''),
                'current_address' => trim($_POST['current_address'] ?? ''),
            ];

            $email = trim($_POST['email'] ?? '');

            if ($data['first_name'] === '' || $data['last_name'] === '') {

                $_SESSION['error'] = 'First name and last name are required.';

            } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $_SESSION['error'] = 'Please enter a valid email address.';

            } elseif (!$this->profileModel->updateProfile($userId, $data)) {

                $_SESSION['error'] = 'Failed to update profile.';

            } else {

                // Update email in users table
                if ($email !== '') {
                    $this->profileModel->updateEmail($userId, $email);
                }

                $_SESSION['success'] = 'Profile updated successfully.';
            }

        } catch (Exception $e) {

            $_SESSION['error'] = $e->getMessage()
                ?: 'Something went wrong while updating your profile.';
        }

        header('Location: index.php?url=user-profile');
        exit;
    }
    public function updateProfileImage(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $_SESSION['error'] = 'You must be logged in.';
            header('Location: index.php?url=user-profile');
            exit;
        }

        try {
            if (
                !isset($_FILES['profile_image']) ||
                $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK
            ) {
                $_SESSION['error'] = 'Please select an image.';
            } else {

                $file = $_FILES['profile_image'];

                if ($file['size'] > 5 * 1024 * 1024) {
                    throw new Exception('Image must not exceed 5MB.');
                }

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                $mimeType = mime_content_type($file['tmp_name']);

                if (!isset($allowedTypes[$mimeType])) {
                    throw new Exception('Only JPG, PNG and WEBP images are allowed.');
                }

                $extension = $allowedTypes[$mimeType];

                $uploadDir = __DIR__ . '/../../public/assets/uploads/profile/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = 'profile_' . $userId . '_' . time() . '.' . $extension;

                $destination = $uploadDir . $fileName;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new Exception('Failed to upload image.');
                }

                if ($this->profileModel->updateProfileImage($userId, $fileName)) {
                    $_SESSION['success'] = 'Profile photo updated successfully.';
                } else {
                    unlink($destination);
                    $_SESSION['error'] = 'Failed to update profile photo.';
                }
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: index.php?url=user-profile');
        exit;
    }
}
