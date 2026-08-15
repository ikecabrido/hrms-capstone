<?php

namespace App\Controllers;

use Exception;
use App\Models\Employee;
use App\Models\Grievance;

class GrievanceController
{
    private Employee $employeeModel;
    private Grievance $grievanceModel;

    public function __construct()
    {
        $this->employeeModel = new Employee;
        $this->grievanceModel = new Grievance;
    }

    public function index()
    {
        $userId = $_SESSION['user_id'];
        $employee = $this->employeeModel->getByUserId($userId);
        $employeeGrievances = $this->grievanceModel->getGrievance($employee['id']);


        $title = "Employee Grievance";
        $content = __DIR__ . '/../views/employee-portal/grievance/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }

    public function store()
    {
        try {

            $userId = $_SESSION['user_id'] ?? null;

            if (!$userId) {
                throw new Exception('User session not found.');
            }

            $employee = $this->employeeModel->getByUserId($userId);

            if (!$employee) {
                throw new Exception('Employee record not found.');
            }

            $category = trim($_POST['category'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $subject = trim($_POST['subject'] ?? '');

            if (empty($category)) {
                throw new Exception('Grievance category is required.');
            }

            if (empty($description)) {
                throw new Exception('Grievance description is required.');
            }

            if (empty($subject)) {
                $subject = 'Other Workplace Concern';
            }


            /*
            |--------------------------------------------------------------------------
            | Anonymous / Confidential
            |--------------------------------------------------------------------------
            */

            $anonymous = isset($_POST['anonymous']) ? 1 : 0;
            $confidential = isset($_POST['confidential']) ? 1 : 0;


            /*
            |--------------------------------------------------------------------------
            | Attachment
            |--------------------------------------------------------------------------
            */

            $attachmentPath = null;

            if (
                isset($_FILES['attachment']) &&
                $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('Failed to upload attachment.');
                }

                $allowedExtensions = [
                    'jpg',
                    'jpeg',
                    'png',
                    'pdf'
                ];

                $fileName = $_FILES['attachment']['name'];
                $tmpName = $_FILES['attachment']['tmp_name'];
                $fileSize = $_FILES['attachment']['size'];

                $extension = strtolower(
                    pathinfo($fileName, PATHINFO_EXTENSION)
                );

                if (!in_array($extension, $allowedExtensions, true)) {
                    throw new Exception(
                        'Invalid attachment type. JPG, PNG, and PDF files only.'
                    );
                }

                // 5 MB maximum
                if ($fileSize > 5 * 1024 * 1024) {
                    throw new Exception(
                        'Attachment must not exceed 5 MB.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Upload Directory
                |--------------------------------------------------------------------------
                */

                $uploadDirectory =
                    __DIR__ .
                    '/../public/assets/uploads/grievance/';

                if (!is_dir($uploadDirectory)) {
                    if (!mkdir($uploadDirectory, 0755, true)) {
                        throw new Exception(
                            'Unable to create upload directory.'
                        );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Generate Safe Filename
                |--------------------------------------------------------------------------
                */

                $newFileName =
                    'grievance_' .
                    bin2hex(random_bytes(16)) .
                    '.' .
                    $extension;

                $destination =
                    $uploadDirectory .
                    $newFileName;


                if (!move_uploaded_file($tmpName, $destination)) {
                    throw new Exception(
                        'Unable to save attachment.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Path stored in database
                |--------------------------------------------------------------------------
                */

                $attachmentPath =
                    'uploads/grievance/' .
                    $newFileName;
            }


            /*
            |--------------------------------------------------------------------------
            | Grievance Data
            |--------------------------------------------------------------------------
            */

            $data = [
                'employee_id' => $employee['id'],

                'subject' => $subject,

                'description' => $description,

                'status' => 'pending',

                'priority' => 'low',

                'category' => $category,

                'anonymous' => $anonymous,

                'attachment_path' => $attachmentPath,

                'confidential' => $confidential,

                'created_by_user_id' => $userId
            ];


            /*
            |--------------------------------------------------------------------------
            | Create Grievance
            |--------------------------------------------------------------------------
            */

            $success = $this->grievanceModel->create($data);

            if (!$success) {

                // Remove uploaded file if database insert fails
                if ($attachmentPath) {

                    $uploadedFile =
                        __DIR__ .
                        '/../public/assets/' .
                        $attachmentPath;

                    if (file_exists($uploadedFile)) {
                        unlink($uploadedFile);
                    }
                }

                throw new Exception(
                    'Grievance could not be created.'
                );
            }


            $_SESSION['success'] =
                'Your grievance has been submitted successfully.';

            header(
                'Location: index.php?url=grievance'
            );

            exit;

        } catch (\Throwable $e) {

    error_log(
        'Grievance submission error: ' .
        $e->getMessage() .
        ' | File: ' .
        $e->getFile() .
        ' | Line: ' .
        $e->getLine()
    );

    $_SESSION['error'] =
        'Grievance Error: ' . $e->getMessage();

    header('Location: index.php?url=grievance');
    exit;
}
    }
}