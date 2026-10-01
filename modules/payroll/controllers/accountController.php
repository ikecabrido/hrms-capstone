<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/accountModel.php';

class AccountController
{
    private PDO $db;
    private AccountModel $model;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->model = new AccountModel($this->db);
    }

    /**
     * Change the logged-in employee's password.
     *
     * Verifies the current password, checks the new password
     * against the app's minimum policy, makes sure the two "new
     * password" fields match, and (as good practice) rejects
     * re-using the current password.
     */
    public function changePassword(int $employeeId, array $data): void
    {
        if ($employeeId <= 0) {
            throw new InvalidArgumentException('You must be logged in to change your password.');
        }

        $currentPassword = (string) ($data['current_password'] ?? '');
        $newPassword = (string) ($data['new_password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            throw new InvalidArgumentException('Please fill in all password fields.');
        }

        $account = $this->model->getByEmployeeId($employeeId);

        if (!$account) {
            throw new RuntimeException('Account not found.');
        }

        if (!password_verify($currentPassword, $account['password'])) {
            throw new InvalidArgumentException('Current password is incorrect.');
        }

        if ($newPassword !== $confirmPassword) {
            throw new InvalidArgumentException('New password and confirmation do not match.');
        }

        $this->validatePasswordStrength($newPassword);

        if (password_verify($newPassword, $account['password'])) {
            throw new InvalidArgumentException('New password must be different from your current password.');
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updated = $this->model->updatePassword((int) $account['user_id'], $hash);

        if (!$updated) {
            throw new RuntimeException('Failed to update password.');
        }
    }

    /**
     * Minimum password policy: at least 8 characters, one
     * uppercase letter, one number, and one special character.
     */
    private function validatePasswordStrength(string $password): void
    {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('New password must be at least 8 characters long.');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException('New password must include at least one uppercase letter.');
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException('New password must include at least one number.');
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new InvalidArgumentException('New password must include at least one special character.');
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {

    require_once __DIR__ . '/../../../auth/session.php';
    require_once __DIR__ . '/../../../auth/guard.php';

    header('Content-Type: application/json');

    function ac_respond(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        echo json_encode($payload);
        exit;
    }

    $controller = new AccountController();
    $employeeId = (int) ($_SESSION['employee_id'] ?? 0);
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {

        /*
         * ------------------------------------------------------
         * CHANGE PASSWORD
         * ------------------------------------------------------
         */
        case 'changePassword': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    ac_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                try {
                    $controller->changePassword($employeeId, $_POST);

                    ac_respond([
                        'success' => true,
                        'message' => 'Password changed successfully.',
                    ]);
                } catch (InvalidArgumentException $e) {
                    ac_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    ac_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    ac_respond(['success' => false, 'message' => 'Failed to change password.'], 500);
                }
                break;
            }

        default: {
                ac_respond(['success' => false, 'message' => 'Unknown action.'], 400);
            }
    }
}
