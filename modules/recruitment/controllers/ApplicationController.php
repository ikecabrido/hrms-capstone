<?php

require_once __DIR__ . '/../classes/ParsedResume.php';
require_once __DIR__ . '/../classes/Application.php';
require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Job.php';
require_once __DIR__ . '/OfferMailer.php';

class ApplicationController
{
    /**
     * Get database connection
     */
    private function getDB()
    {
        $database = new Database();
        return $database->getConnection();
    }

    /**
     * Display applications
     */
    public function index()
    {
        $applicationModel = new Application();

        $status = $_GET['status'] ?? 'pending';

        if ($status === 'all') {
            $applications = $applicationModel->getAll();
        } else {
            $applications = $applicationModel->getByStatus($status);
        }

        if ($applications instanceof PDOStatement) {
            $applications = $applications->fetchAll(PDO::FETCH_ASSOC);
        }

        require __DIR__ . '/../pages/applications.php';
    }

    /**
     * Display one application
     */
    public function show()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id <= 0) {
            die("Application not found");
        }

        try {
            $db = $this->getDB();

            // Application
            $stmt = $db->prepare("
                SELECT a.*, 
                       j.title AS job_title,
                       j.position,
                       j.department
                FROM rao_applications a
                LEFT JOIN rao_jobs j ON a.job_id = j.id
                WHERE a.id = :id
                LIMIT 1
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$application) {
                die("Application not found");
            }

            // Education
            $stmt = $db->prepare("
                SELECT *
                FROM rao_education
                WHERE application_id = :application_id
            ");

            $stmt->execute([
                ':application_id' => $id
            ]);

            $education = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Experience
            $stmt = $db->prepare("
                SELECT *
                FROM rao_experience
                WHERE application_id = :application_id
            ");

            $stmt->execute([
                ':application_id' => $id
            ]);

            $experience = $stmt->fetchAll(PDO::FETCH_ASSOC);

            require __DIR__ . '/../pages/applications/show.php';
        } catch (Throwable $e) {
            die("Error loading application: " . $e->getMessage());
        }
    }

    /**
     * Applicant self-application
     */
    public function apply()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        try {
            $job_id = isset($_POST['job_id'])
                ? (int) $_POST['job_id']
                : 0;

            if ($job_id <= 0) {
                throw new Exception("Invalid job selected.");
            }

            $applicationModel = new Application();

            // Check maximum applicants
            if (!$applicationModel->canApply($job_id)) {
                header(
                    "Location: index.php?page=jobs&msg=" .
                        urlencode("This job is already full")
                );
                exit;
            }

            $db = $this->getDB();

            $stmt = $db->prepare("
                INSERT INTO rao_applications
                (
                    job_id,
                    first_name,
                    last_name,
                    email,
                    phone,
                    age,
                    gender,
                    birthplace,
                    birthdate,
                    civil_status,
                    citizenship,
                    religion,
                    address,
                    permanent_address
                )
                VALUES
                (
                    :job_id,
                    :first_name,
                    :last_name,
                    :email,
                    :phone,
                    :age,
                    :gender,
                    :birthplace,
                    :birthdate,
                    :civil_status,
                    :citizenship,
                    :religion,
                    :address,
                    :permanent_address
                )
            ");

            $stmt->execute([
                ':job_id'     => $job_id,
                ':first_name' => trim($_POST['first_name'] ?? ''),
                ':last_name'  => trim($_POST['last_name'] ?? ''),
                ':email'      => trim($_POST['email'] ?? ''),
                ':phone'      => trim($_POST['phone'] ?? ''),
                ':age'        => ($_POST['age'] ?? '') !== '' ? (int) $_POST['age'] : null,
                ':gender'     => trim($_POST['gender'] ?? ''),
                ':birthplace' => trim($_POST['birthplace'] ?? ''),
                ':birthdate'  => ($_POST['birthdate'] ?? '') !== '' ? $_POST['birthdate'] : null,
                ':civil_status' => trim($_POST['civil_status'] ?? ''),
                ':citizenship' => trim($_POST['citizenship'] ?? ''),
                ':religion'   => trim($_POST['religion'] ?? ''),
                ':address'    => trim($_POST['address'] ?? ''),
                ':permanent_address' => trim($_POST['permanent_address'] ?? '')
            ]);

            header(
                "Location: index.php?page=jobs&msg=" .
                    urlencode("Application submitted successfully")
            );

            exit;
        } catch (Throwable $e) {
            die("Error submitting application: " . $e->getMessage());
        }
    }

    /**
     * Submit interview feedback
     */
    public function submitFeedback()
    {
        if (ob_get_level()) {
            ob_clean();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $interview_id = isset($_POST['interview_id'])
            ? (int) $_POST['interview_id']
            : 0;

        $rating = isset($_POST['rating'])
            ? (int) $_POST['rating']
            : 0;

        $feedback = trim($_POST['feedback'] ?? '');

        $appModel = new Application();

        $result = $appModel->saveFeedback(
            $interview_id,
            $rating,
            $feedback
        );

        header('Content-Type: application/json');

        echo json_encode([
            'success' => (bool) $result
        ]);

        exit;
    }

    /**
     * Candidates ready for offer
     */

    public function offer()
    {
        $applicationModel = new Application();

        try {

            // Get applicants who are ready for an offer
            $applications = $applicationModel->getReadyForOffer();

            if (!is_array($applications)) {
                $applications = [];
            }

            // Add job and salary information to each applicant
            foreach ($applications as &$application) {

                if (empty($application['job_id'])) {
                    continue;
                }

                $jobDetails = $applicationModel->getOfferJobDetails(
                    (int) $application['job_id']
                );

                if ($jobDetails && is_array($jobDetails)) {

                    $application = array_merge(
                        $application,
                        $jobDetails
                    );
                }
            }

            unset($application);
        } catch (Throwable $e) {

            $applications = [];

            $_SESSION['error'] =
                "Unable to load candidates: " . $e->getMessage();
        }

        require __DIR__ . '/../pages/offer.php';
    }



    public function hiredApplicants()
    {
        try {
            $applications = (new Application())->getHiredApplicants();
            $error = null;
        } catch (Throwable $e) {
            $applications = [];
            $error = $e->getMessage();
        }

        require __DIR__ . '/../pages/hired-applicants.php';
    }

    /**
     * Create and dispatch job offer
     */

    public function sendOffer()
    {
        // Only accept POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=offer");
            exit;
        }

        $db = null;

        try {

            // =========================================================
            // GET POST DATA
            // =========================================================

            $application_id = (int) ($_POST['application_id'] ?? 0);

            $benefit_package = trim(
                $_POST['benefit_package'] ?? ''
            );

            $additional_note = trim(
                $_POST['additional_note'] ?? ''
            );


            // =========================================================
            // VALIDATION
            // =========================================================

            if ($application_id <= 0) {
                throw new Exception(
                    "Please select an applicant."
                );
            }


            // =========================================================
            // DATABASE
            // =========================================================

            $db = $this->getDB();


            // =========================================================
            // GET APPLICATION
            // =========================================================

            $check = $db->prepare("
            SELECT
                a.id,
                a.first_name,
                a.last_name,
                a.email,
                a.job_id,
                a.ready_for_offer,
                a.is_archived,

                EXISTS (
                    SELECT 1
                    FROM rao_hired h
                    WHERE h.application_id = a.id
                ) AS is_hired_record

            FROM rao_applications a

            WHERE a.id = :id

            LIMIT 1
        ");

            $check->execute([
                ':id' => $application_id
            ]);

            $application = $check->fetch(PDO::FETCH_ASSOC);


            if (!$application) {
                throw new Exception(
                    "Application not found."
                );
            }


            // =========================================================
            // CHECK ARCHIVED
            // =========================================================

            if ((int) $application['is_archived'] === 1) {
                throw new Exception(
                    "This application is archived."
                );
            }


            // =========================================================
            // CHECK READY FOR OFFER
            // =========================================================

            if (
                (int) $application['ready_for_offer'] !== 1
                &&
                (int) $application['is_hired_record'] !== 1
            ) {
                throw new Exception(
                    "This candidate is not marked as Ready for Offer."
                );
            }


            // =========================================================
            // CHECK JOB ID
            // =========================================================

            $job_id = (int) ($application['job_id'] ?? 0);

            if ($job_id <= 0) {
                throw new Exception(
                    "No job is assigned to this application."
                );
            }


            // =========================================================
            // GET JOB + POSITION + SALARY
            // =========================================================
            //
            // CORRECT RELATIONSHIP:
            //
            // rao_jobs.position_id
            //          ↓
            // em_positions.position_id
            //          ↓
            // pr_salary_structures.position_id
            //
            // We do NOT use j.position.
            //
            // Example:
            //
            // Job:
            //     position_id = 10
            //     position    = Instructor
            //
            // Employee Position:
            //     position_id = 10
            //     position_name = IT Instructor
            //
            // Salary:
            //     position_id = 10
            //     midpoint_salary = 40000
            //
            // =========================================================

            $job = $db->prepare("
            SELECT

                j.id AS job_id,

                j.title AS base_job,

                j.position_id AS job_position_id,

                j.position AS job_position,

                j.department_id,

                j.department,

                p.position_id,

                p.position_name,

                ss.salary_structure_id,

                ss.salary_grade,

                ss.minimum_salary,

                ss.midpoint_salary,

                ss.maximum_salary,

                ss.effective_date,

                ss.end_date

            FROM rao_jobs j

            INNER JOIN em_positions p
                ON p.position_id = j.position_id

            LEFT JOIN pr_salary_structures ss
                ON ss.position_id = p.position_id

                AND ss.status = 'Active'

                AND ss.effective_date <= CURDATE()

                AND (
                    ss.end_date IS NULL
                    OR ss.end_date >= CURDATE()
                )

            WHERE j.id = :job_id

            ORDER BY
                ss.effective_date DESC,
                ss.salary_structure_id DESC

            LIMIT 1
        ");

            $job->execute([
                ':job_id' => $job_id
            ]);

            $jobData = $job->fetch(PDO::FETCH_ASSOC);


            // =========================================================
            // CHECK JOB
            // =========================================================

            if (!$jobData) {
                throw new Exception(
                    "Job information not found for Job ID "
                        . $job_id
                        . "."
                );
            }


            // =========================================================
            // CHECK POSITION
            // =========================================================

            if (
                empty($jobData['position_id'])
                ||
                empty($jobData['position_name'])
            ) {
                throw new Exception(
                    "The job does not have a valid employee position."
                );
            }


            // =========================================================
            // CHECK SALARY STRUCTURE
            // =========================================================

            if (
                empty($jobData['salary_structure_id'])
            ) {

                throw new Exception(
                    "No active salary structure found for position \""
                        . $jobData['position_name']
                        . "\" (Position ID: "
                        . $jobData['position_id']
                        . ")."
                );
            }


            // =========================================================
            // FINAL OFFER VALUES
            // =========================================================

            $base_job = trim(
                $jobData['base_job'] ?? ''
            );

            $position = trim(
                $jobData['position_name'] ?? ''
            );

            $department = trim(
                $jobData['department'] ?? ''
            );

            $salary = (float) (
                $jobData['midpoint_salary'] ?? 0
            );


            // =========================================================
            // VALIDATE FINAL VALUES
            // =========================================================

            if ($base_job === '') {
                throw new Exception(
                    "The selected job has no base job title."
                );
            }

            if ($position === '') {
                throw new Exception(
                    "The selected job has no position."
                );
            }

            if ($department === '') {
                throw new Exception(
                    "The selected job has no department."
                );
            }

            if ($salary <= 0) {
                throw new Exception(
                    "The salary structure for \""
                        . $position
                        . "\" has an invalid midpoint salary."
                );
            }


            // =========================================================
            // CHECK EXISTING OFFER
            // =========================================================

            $existing = $db->prepare("
            SELECT
                id,
                status

            FROM rao_offer

            WHERE application_id = :application_id

            ORDER BY id DESC

            LIMIT 1
        ");

            $existing->execute([
                ':application_id' => $application_id
            ]);

            $existingOffer =
                $existing->fetch(PDO::FETCH_ASSOC);


            if ($existingOffer) {

                $existingStatus =
                    $existingOffer['status'] ?? '';

                if (
                    in_array(
                        $existingStatus,
                        ['Sent', 'Accepted'],
                        true
                    )
                ) {
                    throw new Exception(
                        "An offer has already been sent to this candidate."
                    );
                }
            }


            // =========================================================
            // BEGIN TRANSACTION
            // =========================================================

            $db->beginTransaction();


            // =========================================================
            // INSERT OFFER
            // =========================================================

            $stmt = $db->prepare("
            INSERT INTO rao_offer
            (
                application_id,
                base_job,
                position,
                department,
                salary,
                benefit_package,
                additional_note,
                status,
                created_at
            )

            VALUES
            (
                :application_id,
                :base_job,
                :position,
                :department,
                :salary,
                :benefit_package,
                :additional_note,
                'Sent',
                NOW()
            )
        ");

            $stmt->execute([

                ':application_id' =>
                $application_id,

                ':base_job' =>
                $base_job,

                ':position' =>
                $position,

                ':department' =>
                $department,

                ':salary' =>
                $salary,

                ':benefit_package' =>
                $benefit_package,

                ':additional_note' =>
                $additional_note
            ]);


            // =========================================================
            // SEND OFFER EMAIL
            // =========================================================

            $recipientName = trim(
                ($application['first_name'] ?? '')
                    . ' '
                    . ($application['last_name'] ?? '')
            );


            if (
                empty($application['email'])
            ) {
                throw new Exception(
                    "The applicant does not have a valid email address."
                );
            }


            $mailer = new \Controllers\OfferMailer();


            $emailSent = $mailer->sendOfferEmail(
                $application['email'],
                $recipientName,
                [
                    'department' =>
                    $department,

                    'position' =>
                    $position,

                    'base_job' =>
                    $base_job,

                    'salary' =>
                    $salary,

                    'benefits' =>
                    $benefit_package,

                    'additional_note' =>
                    $additional_note
                ]
            );


            if (!$emailSent) {

                $mailerError =
                    $mailer->last_error
                    ??
                    'Unknown mailer error.';

                throw new Exception(
                    "Offer email could not be sent: "
                        . $mailerError
                );
            }


            // =========================================================
            // UPDATE APPLICATION STATUS
            // =========================================================

            $update = $db->prepare("
            UPDATE rao_applications

            SET status = 'offered'

            WHERE id = :id
        ");

            $update->execute([
                ':id' => $application_id
            ]);


            // =========================================================
            // COMMIT
            // =========================================================

            $db->commit();


            // =========================================================
            // SUCCESS
            // =========================================================

            $_SESSION['success'] =
                "Offer created successfully for "
                . ($application['first_name'] ?? '')
                . ' '
                . ($application['last_name'] ?? '')
                . ".";


            header(
                "Location: index.php?page=offered-list"
            );

            exit;
        } catch (Throwable $e) {

            // =========================================================
            // ROLLBACK
            // =========================================================

            if (
                $db instanceof PDO
                &&
                $db->inTransaction()
            ) {
                $db->rollBack();
            }


            // =========================================================
            // ERROR
            // =========================================================

            $_SESSION['error'] =
                "Failed to create offer: "
                . $e->getMessage();


            header(
                "Location: index.php?page=send-offer"
            );

            exit;
        }
    }


    /**
     * Archive / reject application
     */
    public function reject()
    {
        if (!isset($_GET['id'])) {
            header(
                "Location: index.php?page=applications"
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

        $applicationModel = new Application();

        if ($applicationModel->archive($id)) {

            header(
                "Location: index.php?page=applications&msg=" .
                    urlencode(
                        "Application rejected successfully"
                    )
            );
        } else {

            header(
                "Location: index.php?page=applications&msg=" .
                    urlencode("Failed to reject application")
            );
        }

        exit;
    }

    /**
     * Approve application
     */
    public function approve()
    {
        if (!isset($_GET['id'])) {
            header(
                "Location: index.php?page=applications"
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

            $db = $this->getDB();

            // =====================================================
            // GET APPLICATION
            // =====================================================

            $stmt = $db->prepare("
                SELECT
                    a.id,
                    a.job_id,
                    a.first_name,
                    a.last_name,
                    a.email,
                    a.phone,
                    a.resume,
                    j.title AS job_title
                FROM rao_applications a
                LEFT JOIN rao_jobs j
                    ON j.id = a.job_id
                WHERE a.id = :id
                AND a.is_archived = 0
                LIMIT 1
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $applicant =
                $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$applicant) {

                header(
                    "Location: index.php?page=applications&msg=" .
                        urlencode("Application not found")
                );

                exit;
            }

            // =====================================================
            // CHECK IF ALREADY PARSED
            // =====================================================

            $checkStmt = $db->prepare("
                SELECT id
                FROM rao_parsed_resume
                WHERE application_id = :application_id
                LIMIT 1
            ");

            $checkStmt->execute([
                ':application_id' => $id
            ]);

            $alreadyParsed =
                $checkStmt->fetch(PDO::FETCH_ASSOC);

            // =====================================================
            // INSERT INTO PARSED RESUME
            // =====================================================

            if (!$alreadyParsed) {

                $parsedResumeModel =
                    new ParsedResume();

                $parsedResumeModel->insert([
                    'application_id' =>
                    $applicant['id'],

                    'first_name' =>
                    $applicant['first_name'],

                    'last_name' =>
                    $applicant['last_name'],

                    'email' =>
                    $applicant['email'],

                    'phone' =>
                    $applicant['phone'],

                    'job_title' =>
                    $applicant['job_title'] ?? '',

                    'resume' =>
                    $applicant['resume'] ?? null
                ]);
            }

            // =====================================================
            // UPDATE APPLICATION STATUS
            // =====================================================

            $updateStmt = $db->prepare("
                UPDATE rao_applications
                SET status = 'approved'
                WHERE id = :id
                AND is_archived = 0
            ");

            $updateStmt->execute([
                ':id' => $id
            ]);

            // =====================================================
            // REDIRECT
            // =====================================================

            header(
                "Location: index.php?page=applications&status=approved&msg=" .
                    urlencode(
                        "Application approved successfully"
                    )
            );

            exit;
        } catch (Throwable $e) {

            die("<div style='font-family:Arial;padding:30px'>" .

                "<h2 style='color:red;'>" .
                "Error approving application" .
                "</h2>" .

                "<p><strong>Message:</strong> " .
                htmlspecialchars(
                    $e->getMessage()
                ) .
                "</p>" .

                "<p><strong>File:</strong> " .
                htmlspecialchars(
                    $e->getFile()
                ) .
                "</p>" .

                "<p><strong>Line:</strong> " .
                htmlspecialchars(
                    $e->getLine()
                ) .
                "</p>" .

                "</div>");
        }
    }

    /**
     * Create applicant form
     */
    public function create()
    {
        $jobModel = new Job();

        try {

            $jobs = $jobModel->getActiveJobs();

            $error = null;
        } catch (Throwable $e) {

            $jobs = [];

            $error = $e->getMessage();
        }

        require __DIR__ .
            '/../pages/create-new-applicant.php';
    }


    public function store()
    {
        // Make sure this is a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                "Location: index.php?page=create-new-applicant"
            );

            exit;
        }

        try {

            // =========================
            // DATABASE
            // =========================

            $db = $this->getDB();

            // =========================
            // JOB ID
            // =========================

            $job_id =
                (int) ($_POST['job_id'] ?? 0);

            if ($job_id <= 0) {
                throw new Exception(
                    "Please select a valid job position."
                );
            }

            // =========================
            // VERIFY JOB
            // =========================

            $jobStmt = $db->prepare("
            SELECT id, title
            FROM rao_jobs
            WHERE id = :job_id
            LIMIT 1
        ");

            $jobStmt->execute([
                ':job_id' => $job_id
            ]);

            $job =
                $jobStmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                throw new Exception(
                    "Selected job does not exist."
                );
            }

            // =========================
            // APPLICANT INFORMATION
            // =========================

            $first_name =
                trim($_POST['first_name'] ?? '');

            $middle_name =
                trim($_POST['middle_name'] ?? '');

            $last_name =
                trim($_POST['last_name'] ?? '');

            $email =
                trim($_POST['email'] ?? '');

            $phone =
                trim($_POST['phone'] ?? '');

            $gender =
                trim($_POST['gender'] ?? '');

            $birthplace =
                trim($_POST['birthplace'] ?? '');

            $birthdate =
                !empty($_POST['birthdate'])
                ? $_POST['birthdate']
                : null;

            $age =
                !empty($_POST['age'])
                ? (int) $_POST['age']
                : null;

            $civil_status =
                trim($_POST['civil_status'] ?? '');

            $citizenship =
                trim($_POST['citizenship'] ?? '');

            $religion =
                trim($_POST['religion'] ?? '');

            $current_address =
                trim($_POST['address'] ?? '');

            $permanent_address =
                trim($_POST['permanent_address'] ?? '');

            $source =
                trim(
                    $_POST['source'] ??
                        'Direct Apply'
                );



            $address =
                trim($_POST['address'] ?? '');

            $source =
                trim(
                    $_POST['source'] ??
                        'Direct Apply'
                );

            if ($first_name === '') {
                throw new Exception(
                    "First name is required."
                );
            }

            if ($last_name === '') {
                throw new Exception(
                    "Last name is required."
                );
            }

            if ($email === '') {
                throw new Exception(
                    "Email is required."
                );
            }

            if (
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                throw new Exception(
                    "Please enter a valid email address."
                );
            }

            // =========================
            // CHECK RESUME
            // =========================

            $hasResume = (
                isset($_FILES['resume']) &&
                $_FILES['resume']['error']
                !== UPLOAD_ERR_NO_FILE
            );

            if ($hasResume) {

                if (
                    $_FILES['resume']['error']
                    !== UPLOAD_ERR_OK
                ) {

                    throw new Exception(
                        "Resume upload failed. Error code: " .
                            $_FILES['resume']['error']
                    );
                }

                // Check extension
                $extension =
                    strtolower(
                        pathinfo(
                            $_FILES['resume']['name'],
                            PATHINFO_EXTENSION
                        )
                    );

                if ($extension !== 'pdf') {

                    throw new Exception(
                        "Only PDF resumes are allowed."
                    );
                }
            }

            // =========================
            // INSERT APPLICATION FIRST
            // =========================
            //
            // We insert the application first
            // so MySQL generates the application ID.
            //

            $stmt = $db->prepare("
    INSERT INTO rao_applications
    (
        job_id,
        first_name,
        middle_name,
        last_name,
        email,
        phone,
        gender,
        birthplace,
        birthdate,
        age,
        civil_status,
        citizenship,
        religion,
        address,
        current_address,
        permanent_address,
        resume,
        source
    )
    VALUES
    (
        :job_id,
        :first_name,
        :middle_name,
        :last_name,
        :email,
        :phone,
        :gender,
        :birthplace,
        :birthdate,
        :age,
        :civil_status,
        :citizenship,
        :religion,
        :address,
        :current_address,
        :permanent_address,
        NULL,
        :source
    )
");

            $stmt->execute([
                ':job_id' =>
                $job_id,

                ':first_name' =>
                $first_name,

                ':middle_name' =>
                $middle_name,

                ':last_name' =>
                $last_name,

                ':email' =>
                $email,

                ':phone' =>
                $phone,

                ':gender' =>
                $gender !== ''
                    ? $gender
                    : null,

                ':birthplace' =>
                $birthplace !== ''
                    ? $birthplace
                    : null,

                ':birthdate' =>
                $birthdate,

                ':age' =>
                $age,

                ':civil_status' =>
                $civil_status !== ''
                    ? $civil_status
                    : null,

                ':citizenship' =>
                $citizenship !== ''
                    ? $citizenship
                    : null,

                ':religion' =>
                $religion !== ''
                    ? $religion
                    : null,

                ':address' =>
                $current_address !== ''
                    ? $current_address
                    : null,

                ':current_address' =>
                $current_address !== ''
                    ? $current_address
                    : null,

                ':permanent_address' =>
                $permanent_address !== ''
                    ? $permanent_address
                    : null,

                ':source' =>
                $source
            ]);



            // =========================
            // GET APPLICATION ID
            // =========================

            $application_id =
                (int) $db->lastInsertId();

            if ($application_id <= 0) {
                throw new Exception(
                    "Unable to get application ID."
                );
            }

            // =========================
            // GENERATE RESUME FILENAME
            // =========================
            //
            // Format:
            // resume_YYYYMM####.pdf
            //
            // Examples:
            // ID 1    -> resume_2026080001.pdf
            // ID 25   -> resume_2026080025.pdf
            // ID 999  -> resume_2026080999.pdf
            // ID 2000 -> resume_2026082000.pdf
            //

            $datePrefix = date('Ym');

            $paddedId = str_pad(
                $application_id,
                4,
                '0',
                STR_PAD_LEFT
            );

            $fileName =
                'resume_' .
                $datePrefix .
                $paddedId .
                '.pdf';

            // Example:
            // resume_2026080001.pdf

            // =========================
            // RESUME UPLOAD
            // =========================

            $resumePath = null;

            if ($hasResume) {

                $uploadDir =
                    dirname(__DIR__) .
                    '/uploads/resumes/';

                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {

                    if (!mkdir($uploadDir, 0777, true)) {

                        throw new Exception(
                            "Unable to create upload directory: " .
                                $uploadDir
                        );
                    }
                }

                // Check directory writable
                if (!is_writable($uploadDir)) {

                    throw new Exception(
                        "Upload directory is not writable: " .
                            $uploadDir
                    );
                }

                // Full physical destination
                $destination =
                    $uploadDir .
                    $fileName;

                // Move uploaded resume
                if (!move_uploaded_file(
                    $_FILES['resume']['tmp_name'],
                    $destination
                )) {

                    throw new Exception(
                        "Unable to save uploaded resume."
                    );
                }

                // Path stored in database
                $resumePath =
                    'uploads/resumes/' .
                    $fileName;

                // =========================
                // UPDATE APPLICATION
                // =========================

                $updateResume =
                    $db->prepare("
            UPDATE rao_applications
            SET resume = :resume
            WHERE id = :id
        ");

                $updateResume->execute([
                    ':resume' => $resumePath,
                    ':id'     => $application_id
                ]);
            }



            // =========================
            // SUCCESS
            // =========================

            header(
                "Location: index.php?page=applications&msg=" .
                    urlencode(
                        "Applicant saved successfully for " .
                            $job['title']
                    )
            );

            exit;
        } catch (Throwable $e) {

            $_SESSION['error'] =
                "Unable to save applicant: " .
                $e->getMessage();

            header(
                "Location: index.php?page=create-new-applicant"
            );

            exit;
        }
    }


    /**
     * Archived applications
     */
    public function archived()
    {
        $applicationModel = new Application();

        $applications =
            $applicationModel->getArchived();

        if ($applications instanceof PDOStatement) {

            $applications =
                $applications->fetchAll(
                    PDO::FETCH_ASSOC
                );
        }

        require __DIR__ .
            '/../pages/Applications/archived.php';
    }

    /**
     * Restore archived application
     */
    public function restore()
    {
        if (!isset($_GET['id'])) {

            header(
                "Location: index.php?page=archived-applications"
            );

            exit;
        }

        $id = (int) $_GET['id'];

        $applicationModel =
            new Application();

        if ($applicationModel->restore($id)) {

            header(
                "Location: index.php?page=archived-applications&msg=" .
                    urlencode("Restored successfully")
            );
        } else {

            header(
                "Location: index.php?page=archived-applications&msg=" .
                    urlencode("Restore failed")
            );
        }

        exit;
    }

    /**
     * Mark candidate as Ready for Offer
     */
    /**
     * Mark candidate as Ready for Offer
     */
    public function markReadyForOffer()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=interview-results");
            exit;
        }

        try {

            // =========================
            // APPLICATION ID
            // =========================

            $application_id = filter_input(
                INPUT_POST,
                'application_id',
                FILTER_VALIDATE_INT
            );

            if (!$application_id || $application_id <= 0) {
                throw new Exception("Invalid application ID.");
            }

            $db = $this->getDB();

            // =========================
            // CHECK APPLICATION
            // =========================

            $check = $db->prepare("
            SELECT
                id,
                first_name,
                last_name,
                ready_for_offer
            FROM rao_applications
            WHERE id = :id
              AND is_archived = 0
            LIMIT 1
        ");

            $check->execute([
                ':id' => $application_id
            ]);

            $application = $check->fetch(PDO::FETCH_ASSOC);

            if (!$application) {
                throw new Exception("Application not found.");
            }

            // =========================
            // CHECK IF ALREADY READY
            // =========================

            if ((int)$application['ready_for_offer'] === 1) {
                $_SESSION['success'] =
                    "Candidate is already Ready for Offer.";

                header("Location: index.php?page=interview-results");
                exit;
            }

            // =========================
            // CHECK ALL INTERVIEWS
            // =========================

            $interviewStmt = $db->prepare("
            SELECT
                stage_order,
                result
            FROM rao_interviews
            WHERE application_id = :application_id
              AND stage_order IN (1, 2, 3)
        ");

            $interviewStmt->execute([
                ':application_id' => $application_id
            ]);

            $interviews = $interviewStmt->fetchAll(PDO::FETCH_ASSOC);

            // We need exactly 3 passed stages
            $passedStages = [];

            foreach ($interviews as $interview) {

                $stage = (int)$interview['stage_order'];
                $result = strtolower(trim($interview['result'] ?? ''));

                if ($result === 'passed') {
                    $passedStages[$stage] = true;
                }
            }

            // Check Initial, Technical and Final
            if (
                !isset($passedStages[1]) ||
                !isset($passedStages[2]) ||
                !isset($passedStages[3])
            ) {

                throw new Exception(
                    "The candidate must pass the Initial, Technical, and Final interviews before being marked Ready for Offer."
                );
            }

            // =========================
            // UPDATE APPLICATION
            // =========================

            $stmt = $db->prepare("
            UPDATE rao_applications
            SET ready_for_offer = 1
            WHERE id = :id
              AND is_archived = 0
              AND ready_for_offer = 0
        ");

            $stmt->execute([
                ':id' => $application_id
            ]);

            // =========================
            // VERIFY UPDATE
            // =========================

            $verify = $db->prepare("
            SELECT ready_for_offer
            FROM rao_applications
            WHERE id = :id
            LIMIT 1
        ");

            $verify->execute([
                ':id' => $application_id
            ]);

            $updated = $verify->fetch(PDO::FETCH_ASSOC);

            if (
                !$updated ||
                (int)$updated['ready_for_offer'] !== 1
            ) {
                throw new Exception(
                    "Database update failed. ready_for_offer was not set to 1."
                );
            }

            // =========================
            // SUCCESS
            // =========================

            $_SESSION['success'] =
                "Candidate marked as Ready for Offer.";

            header("Location: index.php?page=interview-results");
            exit;
        } catch (Throwable $e) {

            $_SESSION['error'] =
                "Ready for Offer Error: " . $e->getMessage();

            header("Location: index.php?page=interview-results");
            exit;
        }
    }
    /**
     * Offered applicants
     */
    public function offeredList()
    {
        $applicationModel =
            new Application();

        $offers =
            $applicationModel->getOfferedApplicants();

        if ($offers instanceof PDOStatement) {

            $offers =
                $offers->fetchAll(
                    PDO::FETCH_ASSOC
                );
        }

        require __DIR__ .
            '/../pages/offered-list.php';
    }

    /**
     * Accept offer
     */
    public function acceptOffer()
    {
        if (!isset($_POST['offer_id'])) {

            header(
                "Location: index.php?page=offered-list"
            );

            exit;
        }

        $offer_id =
            (int) $_POST['offer_id'];

        require_once __DIR__ .
            '/../classes/Offer.php';

        $offerModel =
            new Offer();

        // Update offer status
        $offerModel->updateStatus(
            $offer_id,
            'Accepted'
        );

        // Get offer details
        $offer =
            $offerModel->getOfferById(
                $offer_id
            );

        // Create hired record
        if (
            $offer &&
            !$offerModel->alreadyHired(
                $offer['application_id']
            )
        ) {

            $offerModel->insertHired(
                $offer
            );
        }

        $_SESSION['success'] =
            "Offer accepted and candidate hired!";

        header(
            "Location: index.php?page=offered-list"
        );

        exit;
    }

    /**
     * Reject offer
     */
    public function rejectOffer()
    {
        if (!isset($_POST['offer_id'])) {

            header(
                "Location: index.php?page=offered-list"
            );

            exit;
        }

        $offer_id =
            (int) $_POST['offer_id'];

        $model =
            new Application();

        $model->updateOfferStatus(
            $offer_id,
            'Rejected'
        );

        $_SESSION['success'] =
            "Offer rejected successfully!";

        header(
            "Location: index.php?page=offered-list"
        );

        exit;
    }

    /**
     * Negotiate offer
     */
    public function negotiateOffer()
    {
        if (!isset($_POST['offer_id'])) {

            header(
                "Location: index.php?page=offered-list"
            );

            exit;
        }

        $offer_id =
            (int) $_POST['offer_id'];

        $model =
            new Application();

        $model->updateOfferStatus(
            $offer_id,
            'Negotiating'
        );

        $_SESSION['success'] =
            "Negotiation request sent!";

        header(
            "Location: index.php?page=offered-list"
        );

        exit;
    }

    /**
     * Update offer salary
     */
    public function updateSalary()
    {
        if (
            !isset($_POST['offer_id']) ||
            !isset($_POST['new_salary'])
        ) {

            header(
                "Location: index.php?page=offered-list"
            );

            exit;
        }

        $offer_id =
            (int) $_POST['offer_id'];

        $new_salary =
            $_POST['new_salary'];

        $model =
            new Application();

        $model->updateSalary(
            $offer_id,
            $new_salary
        );

        $_SESSION['success'] =
            "Salary updated successfully!";

        header(
            "Location: index.php?page=offered-list"
        );

        exit;
    }
    /**
     * Hire applicant
     */
    public function hire()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=offered-list");
            exit;
        }

        $offerId = filter_input(
            INPUT_POST,
            'offer_id',
            FILTER_VALIDATE_INT
        );

        if (!$offerId || $offerId <= 0) {
            $_SESSION['error'] = 'Invalid offer ID.';
            header("Location: index.php?page=offered-list");
            exit;
        }

        try {

            $db = $this->getDB();

            $db->beginTransaction();

            // =====================================================
            // 1. GET OFFER + APPLICANT + POSITION + DEPARTMENT
            // =====================================================

            $stmt = $db->prepare("
            SELECT
                o.id AS offer_id,
                o.application_id,
                o.base_job,
                o.position,
                o.department,
                o.salary,
                o.status AS offer_status,

                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,
                a.phone,
                a.address,

                p.position_id,
                p.position_name,

                d.department_id,
                d.department_name

            FROM rao_offer o

            INNER JOIN rao_applications a
                ON a.id = o.application_id

            LEFT JOIN em_positions p
                ON TRIM(LOWER(p.position_name))
                   = TRIM(LOWER(o.position))

            LEFT JOIN em_departments d
                ON TRIM(LOWER(d.department_name))
                   = TRIM(LOWER(o.department))

            WHERE o.id = :offer_id

            LIMIT 1
        ");

            $stmt->execute([
                ':offer_id' => $offerId
            ]);

            $offer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$offer) {
                throw new Exception(
                    'Offer not found.'
                );
            }

            // =====================================================
            // 2. ONLY ACCEPTED OFFERS CAN BE HIRED
            // =====================================================

            if ($offer['offer_status'] !== 'Accepted') {
                throw new Exception(
                    'Only an accepted offer can be hired.'
                );
            }

            // =====================================================
            // 3. CHECK IF ALREADY HIRED
            // =====================================================

            $check = $db->prepare("
            SELECT id
            FROM rao_hired
            WHERE application_id = :application_id
            LIMIT 1
        ");

            $check->execute([
                ':application_id' =>
                $offer['application_id']
            ]);

            if ($check->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception(
                    'This applicant has already been hired.'
                );
            }

            // =====================================================
            // 4. CHECK POSITION
            // =====================================================

            if (empty($offer['position_id'])) {
                throw new Exception(
                    'Position could not be found in em_positions.'
                );
            }

            // =====================================================
            // 5. CHECK DEPARTMENT
            // =====================================================

            if (empty($offer['department_id'])) {
                throw new Exception(
                    'Department could not be found in em_departments.'
                );
            }

            // =====================================================
            // 6. INSERT INTO rao_hired
            // =====================================================

            $insert = $db->prepare("
            INSERT INTO rao_hired
            (
                application_id,
                position_id,
                department_id,
                first_name,
                middle_name,
                last_name,
                email,
                phone,
                address,
                position,
                department,
                base_job,
                salary,
                hired_at
            )
            VALUES
            (
                :application_id,
                :position_id,
                :department_id,
                :first_name,
                :middle_name,
                :last_name,
                :email,
                :phone,
                :address,
                :position,
                :department,
                :base_job,
                :salary,
                NOW()
            )
        ");

            $insert->execute([
                ':application_id' =>
                $offer['application_id'],

                ':position_id' =>
                $offer['position_id'],

                ':department_id' =>
                $offer['department_id'],

                ':first_name' =>
                $offer['first_name'],

                ':middle_name' =>
                $offer['middle_name'],

                ':last_name' =>
                $offer['last_name'],

                ':email' =>
                $offer['email'],

                ':phone' =>
                $offer['phone'],

                ':address' =>
                $offer['address'],

                ':position' =>
                $offer['position'],

                ':department' =>
                $offer['department'],

                ':base_job' =>
                $offer['base_job'],

                ':salary' =>
                $offer['salary']
            ]);

            // =====================================================
            // 7. VERIFY INSERT
            // =====================================================

            $hiredId = $db->lastInsertId();

            if (!$hiredId) {
                throw new Exception(
                    'Failed to create hired employee record.'
                );
            }

            // =====================================================
            // 8. UPDATE APPLICATION
            // =====================================================

            $updateApplication = $db->prepare("
            UPDATE rao_applications
            SET
                status = 'Hired',
                hired = 1
            WHERE id = :application_id
        ");

            $updateApplication->execute([
                ':application_id' =>
                $offer['application_id']
            ]);

            // =====================================================
            // 9. COMMIT
            // =====================================================

            $db->commit();

            $_SESSION['success'] =
                'Applicant has been successfully hired.';

            header(
                "Location: index.php?page=offered-list"
            );

            exit;
        } catch (Throwable $e) {

            if (
                isset($db) &&
                $db->inTransaction()
            ) {
                $db->rollBack();
            }

            $_SESSION['error'] =
                'Unable to hire applicant: ' .
                $e->getMessage();

            header(
                "Location: index.php?page=offered-list"
            );

            exit;
        }
    }
}
