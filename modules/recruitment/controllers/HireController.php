<?php

require_once __DIR__ . '/../classes/Hire.php';
require_once __DIR__ . '/../../../database/db.php';

class HireController
{
    private $hireModel;
    private $db;

    public function __construct()
    {
        // Use the same PDO connection pattern used throughout HRMS
        $database = new Database();
        $this->db = $database->getConnection();

        // Pass the PDO connection to the model
        $this->hireModel = new Hire($this->db);
    }

    public function hire()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Invalid request method.');
        }

        $appId = (int) ($_POST['application_id'] ?? 0);

        if ($appId <= 0) {
            http_response_code(400);
            die('Invalid application ID.');
        }

        try {
            // Start PDO transaction
            $this->db->beginTransaction();

            /*
             * Create hired applicant record.
             *
             * Hire::create() should throw an Exception
             * when the hiring operation fails.
             */
            $this->hireModel->create($appId);

            /*
             * Mark the application as hired.
             */
            $this->hireModel->markAsHired($appId);

            // Commit transaction
            $this->db->commit();

            header(
                'Location: index.php?page=interview-tracking&msg=success'
            );
            exit;
        } catch (Throwable $e) {

            // Roll back only if a transaction is active
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            http_response_code(500);

            echo '<h1>Hiring Error</h1>';
            echo '<p><strong>Message:</strong> '
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
                . '</p>';

            /*
             * PDO does not have $this->db->error.
             *
             * The actual database exception is contained in
             * $e->getMessage().
             */
            echo '<p><strong>Application ID:</strong> '
                . $appId
                . '</p>';

            exit;
        }
    }
}
