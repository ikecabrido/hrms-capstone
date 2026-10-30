<?php

namespace App\Controllers;

use App\Models\JobPosting;
use Exception;
use Throwable;

class JobPostingController
{
    private JobPosting $jobPostingModel;

    public function __construct()
    {
        $this->jobPostingModel = new JobPosting();
    }
    public function adminIndex()
    {
        $title = "Admin Job Posting";
        $jobPostings = $this->jobPostingModel->all();
        $content = __DIR__ . '/../views/admin-portal/job-posting/content.php';
        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function create()
    {
        $uploadedFilePath = null;

        try {
            $compName = trim($_POST['comp_name'] ?? '');
            $compEmail = trim($_POST['comp_email'] ?? '');
            $compNumber = trim($_POST['comp_number'] ?? '');
            $compAddress = trim($_POST['comp_address'] ?? '');

            if (
                empty($compName) ||
                empty($compEmail) ||
                empty($compNumber) ||
                empty($compAddress)
            ) {
                throw new Exception('Please complete all required company information.');
            }

            if (!filter_var($compEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid company email address.');
            }

            $parseLines = function ($value) {
                $lines = preg_split('/\r\n|\r|\n/', trim($value ?? ''));

                return array_values(array_filter(
                    array_map('trim', $lines),
                    fn($line) => $line !== ''
                ));
            };

            $openPositions = $parseLines($_POST['open_positions'] ?? '');
            $qualifications = $parseLines($_POST['qualifications'] ?? '');
            $benefits = $parseLines($_POST['benefits'] ?? '');

            if (empty($openPositions)) {
                throw new Exception('Please provide at least one open position.');
            }

            $logoPath = null;

            if (
                isset($_FILES['comp_logo']) &&
                $_FILES['comp_logo']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                $file = $_FILES['comp_logo'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('There was a problem uploading the company logo.');
                }

                if ($file['size'] > 2 * 1024 * 1024) {
                    throw new Exception('Company logo must not exceed 2MB.');
                }

                if (!is_uploaded_file($file['tmp_name'])) {
                    throw new Exception('Invalid uploaded file.');
                }

                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowedTypes[$mimeType])) {
                    throw new Exception('Only PNG, JPG, JPEG, and WEBP images are allowed.');
                }

                if (@getimagesize($file['tmp_name']) === false) {
                    throw new Exception('The uploaded file is not a valid image.');
                }

                $uploadDirectory = dirname(__DIR__, 2)
                    . '/public/assets/uploads/job-postings/';

                if (
                    !is_dir($uploadDirectory) &&
                    !mkdir($uploadDirectory, 0755, true)
                ) {
                    throw new Exception('Unable to create the logo upload directory.');
                }

                if (!is_writable($uploadDirectory)) {
                    throw new Exception('The logo upload directory is not writable.');
                }

                $fileName = bin2hex(random_bytes(16))
                    . '.' . $allowedTypes[$mimeType];

                $destination = $uploadDirectory . $fileName;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new Exception('Failed to save the company logo.');
                }

                $logoPath = 'assets/uploads/job-postings/' . $fileName;

                $uploadedFilePath = $destination;
            }

            $data = [
                'comp_name' => $compName,
                'comp_logo' => $logoPath,
                'comp_email' => $compEmail,
                'comp_number' => $compNumber,
                'comp_address' => $compAddress,
                'open_positions' => json_encode($openPositions, JSON_THROW_ON_ERROR),
                'qualifications' => json_encode($qualifications, JSON_THROW_ON_ERROR),
                'benefits' => json_encode($benefits, JSON_THROW_ON_ERROR)
            ];

            $result = $this->jobPostingModel->create($data);

            if (!$result) {
                throw new Exception('Failed to save the job posting.');
            }

            $_SESSION['success'] = 'Job posting created successfully.';

        } catch (Throwable $e) {

            if ($uploadedFilePath && file_exists($uploadedFilePath)) {
                unlink($uploadedFilePath);
            }

            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . appUrl('index.php?url=admin-job-posting'));
        exit;
    }
}