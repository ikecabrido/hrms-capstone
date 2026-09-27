<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/profileModel.php';

class ProfileController
{
    private PDO $db;
    private ProfileModel $model;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->model = new ProfileModel($this->db);
    }

    /**
     * Get the logged-in employee's profile.
     */
    public function show(int $employeeId): array
    {
        if ($employeeId <= 0) {
            throw new InvalidArgumentException('You must be logged in to view this page.');
        }

        $profile = $this->model->getProfile($employeeId);

        if (!$profile) {
            throw new RuntimeException('Profile not found.');
        }

        return $profile;
    }

    /**
     * Update the contact-info fields an employee may self-edit.
     */
    public function update(int $employeeId, array $data): void
    {
        if ($employeeId <= 0) {
            throw new InvalidArgumentException('You must be logged in to update your profile.');
        }

        $email = trim((string) ($data['email'] ?? ''));
        $mobile = trim((string) ($data['mobile_no'] ?? ''));
        $phone = trim((string) ($data['phone_no'] ?? ''));
        $currentAddress = trim((string) ($data['current_address'] ?? ''));
        $permanentAddress = trim((string) ($data['permanent_address'] ?? ''));

        if ($email === '') {
            throw new InvalidArgumentException('Email address is required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        if ($mobile !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $mobile)) {
            throw new InvalidArgumentException('Please enter a valid mobile number.');
        }

        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            throw new InvalidArgumentException('Please enter a valid phone number.');
        }

        if ($this->model->isEmailTaken($email, $employeeId)) {
            throw new InvalidArgumentException('That email address is already in use by another employee.');
        }

        $updated = $this->model->updateContactInfo($employeeId, [
            'email' => $email,
            'mobile_no' => $mobile,
            'phone_no' => $phone,
            'current_address' => $currentAddress,
            'permanent_address' => $permanentAddress,
        ]);

        if (!$updated) {
            throw new RuntimeException('Failed to update profile.');
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {

    require_once __DIR__ . '/../../../auth/session.php';
    require_once __DIR__ . '/../../../auth/guard.php';

    header('Content-Type: application/json');

    function pf_respond(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        echo json_encode($payload);
        exit;
    }

    $controller = new ProfileController();
    $employeeId = (int) ($_SESSION['employee_id'] ?? 0);
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {

        /*
         * ------------------------------------------------------
         * GET PROFILE
         * ------------------------------------------------------
         */
        case 'get': {
                try {
                    $profile = $controller->show($employeeId);
                    pf_respond(['success' => true, 'data' => $profile]);
                } catch (InvalidArgumentException $e) {
                    pf_respond(['success' => false, 'message' => $e->getMessage()], 401);
                } catch (RuntimeException $e) {
                    pf_respond(['success' => false, 'message' => $e->getMessage()], 404);
                } catch (Throwable $e) {
                    pf_respond(['success' => false, 'message' => 'Failed to load profile.'], 500);
                }
                break;
            }

            /*
         * ------------------------------------------------------
         * UPDATE PROFILE
         * ------------------------------------------------------
         */
        case 'update': {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    pf_respond(['success' => false, 'message' => 'Invalid request method.'], 405);
                }

                try {
                    $controller->update($employeeId, $_POST);

                    pf_respond([
                        'success' => true,
                        'message' => 'Profile updated successfully.',
                    ]);
                } catch (InvalidArgumentException $e) {
                    pf_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    pf_respond(['success' => false, 'message' => $e->getMessage()]);
                } catch (Throwable $e) {
                    pf_respond(['success' => false, 'message' => 'Failed to update profile.'], 500);
                }
                break;
            }

        default: {
                pf_respond(['success' => false, 'message' => 'Unknown action.'], 400);
            }
    }
}
