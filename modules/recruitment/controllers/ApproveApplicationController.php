<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Application.php';
require_once __DIR__ . '/../classes/ParsedResume.php';

class ApproveApplicationController
{
    private function getDB()
    {
        $database = new Database();
        return $database->getConnection();
    }

    public function approve()
    {
        /*
         * Validate application ID
         */
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

            header(
                "Location: index.php?page=applications&msg=" .
                    urlencode("Invalid application ID")
            );

            exit;
        }

        $id = (int) $_GET['id'];

        if ($id <= 0) {

            header(
                "Location: index.php?page=applications&msg=" .
                    urlencode("Invalid application ID")
            );

            exit;
        }

        try {

            $applicationModel = new Application();
            $parsedResumeModel = new ParsedResume();

            /*
             * Get application
             */
            $applicant = $applicationModel->findById($id);

            if (!$applicant) {

                header(
                    "Location: index.php?page=applications&msg=" .
                        urlencode("Application not found")
                );

                exit;
            }

            /*
             * Get job title if Application::findById()
             * does not already return it.
             */
            $db = $this->getDB();

            $jobStmt = $db->prepare("
                SELECT title
                FROM rao_jobs
                WHERE id = :job_id
                LIMIT 1
            ");

            $jobStmt->execute([
                ':job_id' => $applicant['job_id']
            ]);

            $job = $jobStmt->fetch(PDO::FETCH_ASSOC);

            $jobTitle = $job['title'] ?? '';

            /*
             * Check if already parsed
             */
            $checkStmt = $db->prepare("
                SELECT id
                FROM rao_parsed_resume
                WHERE application_id = :application_id
                LIMIT 1
            ");

            $checkStmt->execute([
                ':application_id' => $id
            ]);

            $existingParsed = $checkStmt->fetch(PDO::FETCH_ASSOC);

            /*
             * Insert into parsed resume
             */
            if (!$existingParsed) {

                $parsedResumeModel->insert([
                    'application_id' => $id,
                    'first_name'     => $applicant['first_name'] ?? '',
                    'last_name'      => $applicant['last_name'] ?? '',
                    'email'          => $applicant['email'] ?? '',
                    'phone'          => $applicant['phone'] ?? '',
                    'job_title'      => $jobTitle,
                    'resume'         => $applicant['resume'] ?? null
                ]);
            }

            /*
             * Update application status
             */
            $stmt = $db->prepare("
                UPDATE rao_applications
                SET status = 'approved'
                WHERE id = :id
                AND is_archived = 0
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            /*
             * Redirect
             */
            header(
                "Location: index.php?page=applications&status=approved&msg=" .
                    urlencode("Application approved successfully")
            );

            exit;
        } catch (Throwable $e) {

            die("<div style='font-family:Arial;padding:30px'>" .
                "<h2 style='color:red;'>Error approving application</h2>" .
                "<p><strong>Message:</strong> " .
                htmlspecialchars($e->getMessage()) .
                "</p>" .
                "<p><strong>File:</strong> " .
                htmlspecialchars($e->getFile()) .
                "</p>" .
                "<p><strong>Line:</strong> " .
                htmlspecialchars($e->getLine()) .
                "</p>" .
                "</div>");
        }
    }
}
