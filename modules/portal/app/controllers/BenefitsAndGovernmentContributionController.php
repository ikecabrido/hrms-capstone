<?php

namespace App\Controllers;

use Exception;
use App\Helper\{Helper, ExceptionHelper};
use App\Models\Employee;
use App\Models\BenefitsAndGovernmentContribution;

class BenefitsAndGovernmentContributionController
{
    private Employee $employeeModel;
    private BenefitsAndGovernmentContribution $benefitsModel;
    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->benefitsModel = new BenefitsAndGovernmentContribution();
    }
    public function index()
    {
        $userId = $_SESSION['user_id'];
        $employeeBenefits = $this->employeeModel->getByUserId($userId);
        $benefits = $this->benefitsModel->getBenefits($employeeBenefits['employee_id']);

        $title = "Employee Benefits and Government Contribution";
        $content = __DIR__ . '/../views/employee-portal/benefits-and-government-contribution/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function adminIndex()
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            throw new Exception('User session not found.');
        }

        $employee = $this->employeeModel->getByUserId($userId);

        $employeesList = [];

        if ($employee && !empty($employee['employee_id'])) {

            $employeeId = (int) $employee['employee_id'];

            $employeesList = $this->employeeModel->getAllExcept($employeeId);
        }

        $benefitList = $this->benefitsModel->all();

        $title = "Benefits and Government Contribution";
        $content = __DIR__ . '/../views/admin-portal/benefit/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function store()
    {
        $userId = $_SESSION['user_id'] ?? null;
        $employeeInfo = $this->employeeModel->getByUserId($userId);
        $employeeId = $employeeInfo['employee_id'];

        try {
            $recordType = trim($_POST['record_type'] ?? '');
            $period = trim($_POST['period'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $allowedTypes = [
                'SSS',
                'PhilHealth',
                'Pag-IBIG',
                'Withholding Tax',
                'BIR Form 2316'
            ];

            if (!in_array($recordType, $allowedTypes, true)) {
                throw new Exception('Invalid record type.');
            }

            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
                throw new Exception('Invalid period.');
            }

            if (
                !isset($_FILES['document']) ||
                $_FILES['document']['error'] !== UPLOAD_ERR_OK
            ) {
                throw new Exception('Please select a document.');
            }

            $file = $_FILES['document'];

            if ($file['size'] > 10 * 1024 * 1024) {
                throw new Exception('File size must not exceed 10 MB.');
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'docx'], true)) {
                throw new Exception('Only PDF, JPG, JPEG, and PNG files are allowed.');
            }

            $directory = __DIR__ . '/../../public/assets/uploads/benefits/';

            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            $fileName = 'benefits' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
            $destination = $directory . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception('Failed to upload the document.');
            }

            $this->benefitsModel->create([
                'employee_id' => $employeeId,
                'record_type' => $recordType,
                'period' => $period,
                'description' => $description ?: null,
                'file_name' => $file['name'],
                'file_path' => 'assets/uploads/benefits/' . $fileName,
                'uploaded_by' => $_SESSION['user_id'] ?? $employeeId
            ]);

            $_SESSION['success'] = 'Document submitted successfully.';
        } catch (\Throwable $e) {
            ExceptionHelper::handle($e, $destination ?? null);
        }

        Helper::redirect('index.php?url=benefits-and-government-contribution');
    }
    public function adminStore()
    {
        $employeeId = $_POST['employee_id'];

        try {
            $recordType = trim($_POST['record_type'] ?? '');
            $period = trim($_POST['period'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $allowedTypes = [
                'SSS',
                'PhilHealth',
                'Pag-IBIG',
                'Withholding Tax',
                'BIR Form 2316'
            ];

            if (!in_array($recordType, $allowedTypes, true)) {
                throw new Exception('Invalid record type.');
            }

            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
                throw new Exception('Invalid period.');
            }

            if (
                !isset($_FILES['document']) ||
                $_FILES['document']['error'] !== UPLOAD_ERR_OK
            ) {
                throw new Exception('Please select a document.');
            }

            $file = $_FILES['document'];

            if ($file['size'] > 10 * 1024 * 1024) {
                throw new Exception('File size must not exceed 10 MB.');
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'docx'], true)) {
                throw new Exception('Only PDF, JPG, JPEG, and PNG files are allowed.');
            }

            $directory = __DIR__ . '/../../public/assets/uploads/benefits/';

            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            $fileName = 'benefits' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
            $destination = $directory . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception('Failed to upload the document.');
            }

            $this->benefitsModel->create([
                'employee_id' => $employeeId,
                'record_type' => $recordType,
                'period' => $period,
                'description' => $description ?: null,
                'file_name' => $file['name'],
                'file_path' => 'assets/uploads/benefits/' . $fileName,
                'uploaded_by' => $employeeId
            ]);

            $_SESSION['success'] = 'Document submitted successfully.';
        } catch (Exception $e) {
            if (isset($destination) && file_exists($destination)) {
                unlink($destination);
            }

            $_SESSION['error'] = $e->getMessage();
        }

        Helper::redirect('index.php?url=admin-benefits');
    }
    public function upload()
    {
        $benefitId = (int) ($_POST['benefit_id'] ?? 0);
        $file = $_FILES['document'] ?? null;

        if (!$benefitId || !$file || $file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Invalid file upload.';
            Helper::redirect('index.php?url=admin-benefits');
            return;
        }

        $uploadDir = __DIR__ . '/../public/assets/uploads/benefits/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        $fileName = 'benefits_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $_SESSION['error'] = 'Failed to upload file.';
            Helper::redirect('index.php?url=benefits');
            return;
        }

        $this->benefitsModel->updateFile(
            $benefitId,
            $file['name'],
            'assets/uploads/benefits/' . $fileName,
            $_SESSION['user_id']
        );

        $_SESSION['success'] = 'Benefit document uploaded successfully.';
        Helper::redirect('index.php?url=admin-benefits');
    }
}