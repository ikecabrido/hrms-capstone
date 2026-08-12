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

        $user = $this->userModel->findById($userId);
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
}
