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

    /**
     * Create and dispatch job offer
     */
    public function sendOffer()
    {
        // Only accept POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=offer");
            exit;
        }

        $db = null;

        try {

            // =========================================================
            // 1. GET POST DATA
            // =========================================================

            $application_id = (int) ($_POST['application_id'] ?? 0);

            $selectedSalary = trim(
                $_POST['salary'] ?? ''
            );

            $benefit_package = trim(
                $_POST['benefit_package'] ?? ''
            );

            $additional_note = trim(
                $_POST['additional_note'] ?? ''
            );


            // =========================================================
            // 2. VALIDATE APPLICATION
            // =========================================================

            if ($application_id <= 0) {
                throw new Exception(
                    "Please select an applicant."
                );
            }


            // =========================================================
            // 3. VALIDATE SALARY
            // =========================================================

            if ($selectedSalary === '') {
                throw new Exception(
                    "Please select a salary offer."
                );
            }

            if (!is_numeric($selectedSalary)) {
                throw new Exception(
                    "Invalid salary offer."
                );
            }

            $selectedSalary = (float) $selectedSalary;

            if ($selectedSalary <= 0) {
                throw new Exception(
                    "Salary offer must be greater than zero."
                );
            }


            // =========================================================
            // 4. DATABASE
            // =========================================================

            $db = $this->getDB();


            // =========================================================
            // 5. GET APPLICATION
            // =========================================================

            $check = $db->prepare("
            SELECT
                a.id,
                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,
                a.phone,
                a.address,
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
            // 6. CHECK ARCHIVED
            // =========================================================

            if (
                (int) $application['is_archived'] === 1
            ) {
                throw new Exception(
                    "This application is archived."
                );
            }


            // =========================================================
            // 7. CHECK READY FOR OFFER
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
            // 8. CHECK JOB ID
            // =========================================================

            $job_id = (int) (
                $application['job_id'] ?? 0
            );

            if ($job_id <= 0) {
                throw new Exception(
                    "No job is assigned to this application."
                );
            }


            // =========================================================
            // 9. GET JOB + POSITION + DEPARTMENT + SALARY
            // =========================================================
            //
            // Relationship:
            //
            // rao_applications.job_id
            //          ↓
            // rao_jobs.id
            //
            // rao_jobs.position_id
            //          ↓
            // em_positions.position_id
            //
            // em_positions.position_id
            //          ↓
            // pr_salary_structures.position_id
            //
            // =========================================================

            $jobStmt = $db->prepare("
            SELECT

                j.id AS job_id,

                j.title AS base_job,

                j.position_id AS job_position_id,

                j.position AS job_position,

                j.department_id AS job_department_id,

                j.department AS job_department,

                p.position_id,

                p.position_name,

                p.department_id AS position_department_id,

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

            $jobStmt->execute([
                ':job_id' => $job_id
            ]);

            $jobData = $jobStmt->fetch(PDO::FETCH_ASSOC);


            // =========================================================
            // 10. CHECK JOB
            // =========================================================

            if (!$jobData) {
                throw new Exception(
                    "Job information not found for Job ID "
                        . $job_id
                        . "."
                );
            }


            // =========================================================
            // 11. CHECK POSITION
            // =========================================================

            if (
                empty($jobData['position_id']) ||
                empty($jobData['position_name'])
            ) {
                throw new Exception(
                    "The selected job does not have a valid employee position."
                );
            }


            // =========================================================
            // 12. CHECK DEPARTMENT
            // =========================================================

            $department = trim(
                $jobData['job_department'] ?? ''
            );

            if ($department === '') {

                // Fallback to department from em_positions
                if (
                    !empty($jobData['position_department_id'])
                ) {

                    $departmentStmt = $db->prepare("
                    SELECT department_name
                    FROM em_departments
                    WHERE department_id = :department_id
                    AND status = 'Active'
                    LIMIT 1
                ");

                    $departmentStmt->execute([
                        ':department_id' =>
                        (int) $jobData['position_department_id']
                    ]);

                    $departmentRow =
                        $departmentStmt->fetch(PDO::FETCH_ASSOC);

                    if ($departmentRow) {
                        $department =
                            trim(
                                $departmentRow['department_name'] ?? ''
                            );
                    }
                }
            }

            if ($department === '') {
                throw new Exception(
                    "The selected job does not have a valid department."
                );
            }


            // =========================================================
            // 13. CHECK SALARY STRUCTURE
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
            // 14. GET SALARY VALUES
            // =========================================================

            $minimumSalary = (float) (
                $jobData['minimum_salary'] ?? 0
            );

            $midpointSalary = (float) (
                $jobData['midpoint_salary'] ?? 0
            );

            $maximumSalary = (float) (
                $jobData['maximum_salary'] ?? 0
            );


            // =========================================================
            // 15. VALIDATE SALARY STRUCTURE
            // =========================================================

            if ($minimumSalary <= 0) {
                throw new Exception(
                    "The minimum salary for \""
                        . $jobData['position_name']
                        . "\" is invalid."
                );
            }

            if ($midpointSalary <= 0) {
                throw new Exception(
                    "The midpoint salary for \""
                        . $jobData['position_name']
                        . "\" is invalid."
                );
            }

            if ($maximumSalary <= 0) {
                throw new Exception(
                    "The maximum salary for \""
                        . $jobData['position_name']
                        . "\" is invalid."
                );
            }


            // =========================================================
            // 16. DETERMINE WHICH SALARY WAS SELECTED
            // =========================================================
            //
            // The form sends the actual salary amount:
            //
            // Minimum  → minimum_salary
            // Midpoint  → midpoint_salary
            // Maximum   → maximum_salary
            //
            // =========================================================

            $salaryType = null;
            $salary = null;

            $tolerance = 0.01;


            if (
                abs($selectedSalary - $minimumSalary)
                < $tolerance
            ) {

                $salaryType = 'Minimum';
                $salary = $minimumSalary;
            } elseif (
                abs($selectedSalary - $midpointSalary)
                < $tolerance
            ) {

                $salaryType = 'Midpoint';
                $salary = $midpointSalary;
            } elseif (
                abs($selectedSalary - $maximumSalary)
                < $tolerance
            ) {

                $salaryType = 'Maximum';
                $salary = $maximumSalary;
            } else {

                throw new Exception(
                    "The selected salary is not a valid salary option for this position."
                );
            }


            // =========================================================
            // 17. FINAL OFFER VALUES
            // =========================================================

            $base_job = trim(
                $jobData['base_job'] ?? ''
            );

            $position = trim(
                $jobData['position_name'] ?? ''
            );


            // =========================================================
            // 18. VALIDATE FINAL VALUES
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

            if (
                $salary === null ||
                $salary <= 0
            ) {
                throw new Exception(
                    "The selected salary is invalid."
                );
            }


            // =========================================================
            // 19. CHECK EXISTING OFFER
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
                    trim(
                        $existingOffer['status'] ?? ''
                    );


                // -----------------------------------------------------
                // Already sent
                // -----------------------------------------------------

                if (
                    in_array(
                        $existingStatus,
                        [
                            'Sent',
                            'Accepted'
                        ],
                        true
                    )
                ) {
                    throw new Exception(
                        "An offer has already been sent to this candidate."
                    );
                }


                // -----------------------------------------------------
                // Rejected offers can be replaced
                // Negotiating offers can also be updated/replaced
                // -----------------------------------------------------
            }


            // =========================================================
            // 20. VALIDATE EMAIL
            // =========================================================

            $email = trim(
                $application['email'] ?? ''
            );

            if ($email === '') {
                throw new Exception(
                    "The applicant does not have an email address."
                );
            }

            if (
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                throw new Exception(
                    "The applicant's email address is invalid."
                );
            }


            // =========================================================
            // 21. BEGIN TRANSACTION
            // =========================================================

            $db->beginTransaction();


            // =========================================================
            // 22. INSERT OFFER
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
            // 23. VERIFY OFFER INSERT
            // =========================================================

            $offerId = (int) $db->lastInsertId();

            if ($offerId <= 0) {
                throw new Exception(
                    "Failed to create the job offer."
                );
            }


            // =========================================================
            // 24. SEND OFFER EMAIL
            // =========================================================

            $recipientName = trim(
                ($application['first_name'] ?? '')
                    . ' '
                    . ($application['last_name'] ?? '')
            );


            if ($recipientName === '') {
                $recipientName = 'Candidate';
            }


            // ---------------------------------------------------------
            // OfferMailer
            // ---------------------------------------------------------

            $mailer =
                new \Controllers\OfferMailer();


            $emailSent =
                $mailer->sendOfferEmail(
                    $email,
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

                        'salary_type' =>
                        $salaryType,

                        'salary_grade' =>
                        $jobData['salary_grade']
                            ?? null,

                        'benefits' =>
                        $benefit_package,

                        'additional_note' =>
                        $additional_note
                    ]
                );


            // =========================================================
            // 25. CHECK EMAIL RESULT
            // =========================================================

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
            // 26. UPDATE APPLICATION STATUS
            // =========================================================

            $update = $db->prepare("
            UPDATE rao_applications

            SET
                status = 'offered'

            WHERE id = :id
        ");

            $update->execute([
                ':id' =>
                $application_id
            ]);


            // =========================================================
            // 27. VERIFY APPLICATION UPDATE
            // =========================================================

            $verify = $db->prepare("
            SELECT
                status
            FROM rao_applications
            WHERE id = :id
            LIMIT 1
        ");

            $verify->execute([
                ':id' =>
                $application_id
            ]);

            $updatedApplication =
                $verify->fetch(PDO::FETCH_ASSOC);


            if (
                !$updatedApplication ||
                strtolower(
                    trim(
                        $updatedApplication['status'] ?? ''
                    )
                ) !== 'offered'
            ) {
                throw new Exception(
                    "Failed to update the applicant status to offered."
                );
            }


            // =========================================================
            // 28. COMMIT
            // =========================================================

            $db->commit();


            // =========================================================
            // 29. SUCCESS MESSAGE
            // =========================================================

            $_SESSION['success'] =
                "Offer created successfully for "
                . ($application['first_name'] ?? '')
                . ' '
                . ($application['last_name'] ?? '')
                . ". Salary: ₱"
                . number_format(
                    $salary,
                    2
                )
                . " ("
                . $salaryType
                . ").";


            // =========================================================
            // 30. REDIRECT
            // =========================================================

            header(
                "Location: index.php?page=offered-list"
            );

            exit;
        } catch (Throwable $e) {

            // =========================================================
            // ROLLBACK
            // =========================================================

            if (
                $db instanceof PDO &&
                $db->inTransaction()
            ) {
                $db->rollBack();
            }


            // =========================================================
            // LOG ERROR
            // =========================================================

            error_log(
                "ApplicationController::sendOffer error: "
                    . $e->getMessage()
                    . " | File: "
                    . $e->getFile()
                    . " | Line: "
                    . $e->getLine()
            );


            // =========================================================
            // ERROR MESSAGE
            // =========================================================

            $_SESSION['error'] =
                "Failed to create offer: "
                . $e->getMessage();


            // =========================================================
            // REDIRECT BACK TO OFFER PAGE
            // =========================================================

            header(
                "Location: index.php?page=offer"
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
        // Only accept POST requests.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=create-new-applicant");
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

            $job_id = (int) ($_POST['job_id'] ?? 0);

            if ($job_id <= 0) {
                throw new Exception("Please select a valid job position.");
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

            $job = $jobStmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                throw new Exception("Selected job does not exist.");
            }

            // =========================
            // APPLICANT INFORMATION
            // =========================

            $first_name = trim($_POST['first_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $birthplace = trim($_POST['birthplace'] ?? '');

            $birthdate = !empty($_POST['birthdate'])
                ? $_POST['birthdate']
                : null;

            $age = isset($_POST['age']) && $_POST['age'] !== ''
                ? (int) $_POST['age']
                : null;

            $civil_status = trim($_POST['civil_status'] ?? '');
            $citizenship = trim($_POST['citizenship'] ?? '');
            $religion = trim($_POST['religion'] ?? '');

            $current_address = trim($_POST['address'] ?? '');
            $permanent_address = trim($_POST['permanent_address'] ?? '');

            $source = trim($_POST['source'] ?? 'Direct Apply');

            if ($source === '') {
                $source = 'Direct Apply';
            }

            // =========================
            // VALIDATE APPLICANT
            // =========================

            if ($first_name === '') {
                throw new Exception("First name is required.");
            }

            if ($last_name === '') {
                throw new Exception("Last name is required.");
            }

            if ($email === '') {
                throw new Exception("Email is required.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please enter a valid email address.");
            }

            // =========================
            // CHECK RESUME UPLOAD
            // =========================

            $hasResume = (
                isset($_FILES['resume']) &&
                ($_FILES['resume']['error'] ?? UPLOAD_ERR_NO_FILE)
                !== UPLOAD_ERR_NO_FILE
            );

            if ($hasResume) {
                if ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception(
                        "Resume upload failed. Error code: " .
                            $_FILES['resume']['error']
                    );
                }

                // Allow PDF files only.
                $extension = strtolower(
                    pathinfo(
                        $_FILES['resume']['name'],
                        PATHINFO_EXTENSION
                    )
                );

                if ($extension !== 'pdf') {
                    throw new Exception("Only PDF resumes are allowed.");
                }

                // Validate the actual MIME type.
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($_FILES['resume']['tmp_name']);

                if ($mimeType !== 'application/pdf') {
                    throw new Exception(
                        "The uploaded resume must be a valid PDF file."
                    );
                }

                // Limit the resume to 10 MB.
                if ($_FILES['resume']['size'] > 10 * 1024 * 1024) {
                    throw new Exception(
                        "The resume must not exceed 10 MB."
                    );
                }

                if (
                    !is_uploaded_file($_FILES['resume']['tmp_name'])
                ) {
                    throw new Exception("Invalid uploaded resume.");
                }
            }

            // =========================
            // INSERT APPLICATION
            // =========================

            // Insert first to generate the application ID.
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
                ':job_id' => $job_id,
                ':first_name' => $first_name,
                ':middle_name' => $middle_name !== '' ? $middle_name : null,
                ':last_name' => $last_name,
                ':email' => $email,
                ':phone' => $phone !== '' ? $phone : null,
                ':gender' => $gender !== '' ? $gender : null,
                ':birthplace' => $birthplace !== '' ? $birthplace : null,
                ':birthdate' => $birthdate,
                ':age' => $age,
                ':civil_status' => $civil_status !== '' ? $civil_status : null,
                ':citizenship' => $citizenship !== '' ? $citizenship : null,
                ':religion' => $religion !== '' ? $religion : null,
                ':address' => $current_address !== '' ? $current_address : null,
                ':current_address' => $current_address !== '' ? $current_address : null,
                ':permanent_address' => $permanent_address !== '' ? $permanent_address : null,
                ':source' => $source
            ]);

            // =========================
            // GET APPLICATION ID
            // =========================

            $application_id = (int) $db->lastInsertId();

            if ($application_id <= 0) {
                throw new Exception("Unable to get application ID.");
            }

            // =========================
            // RESUME UPLOAD
            // =========================

            if ($hasResume) {
                /*
             * Controller directory:
             * modules/recruitment/controllers/
             *
             * Upload directory:
             * modules/recruitment/JobPortal/uploads/{application_id}/
             */

                $uploadRoot = dirname(__DIR__) . '/JobPortal/uploads';
                $uploadDir = $uploadRoot . '/' . $application_id;

                // Create the root upload directory if necessary.
                if (
                    !is_dir($uploadRoot) &&
                    !mkdir($uploadRoot, 0755, true) &&
                    !is_dir($uploadRoot)
                ) {
                    throw new Exception(
                        "Unable to create the upload root directory."
                    );
                }

                // Create the applicant-specific directory.
                if (
                    !is_dir($uploadDir) &&
                    !mkdir($uploadDir, 0755, true) &&
                    !is_dir($uploadDir)
                ) {
                    throw new Exception(
                        "Unable to create the applicant upload directory."
                    );
                }

                if (!is_writable($uploadDir)) {
                    throw new Exception(
                        "The applicant upload directory is not writable."
                    );
                }

                // Generate a unique filename.
                $fileName = 'resume_' .
                    date('Ymd_His') . '_' .
                    bin2hex(random_bytes(4)) . '.pdf';

                $destination = $uploadDir . '/' . $fileName;

                // Move the uploaded file to its destination.
                if (
                    !move_uploaded_file(
                        $_FILES['resume']['tmp_name'],
                        $destination
                    )
                ) {
                    throw new Exception(
                        "Unable to save the uploaded resume."
                    );
                }

                // Save a relative path in the database.
                $resumePath = 'JobPortal/uploads/' .
                    $application_id . '/' . $fileName;

                $updateResume = $db->prepare("
                UPDATE rao_applications
                SET resume = :resume
                WHERE id = :id
            ");

                $updateResume->execute([
                    ':resume' => $resumePath,
                    ':id' => $application_id
                ]);
            }

            // =========================
            // SUCCESS
            // =========================

            $_SESSION['success'] =
                "Applicant created successfully.";

            header("Location: index.php?page=applications");
            exit;
        } catch (Throwable $e) {
            error_log(
                "ApplicationController::store error: " .
                    $e->getMessage()
            );

            $_SESSION['error'] =
                "Unable to save applicant: " . $e->getMessage();

            header("Location: index.php?page=create-new-applicant");
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
        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            empty($_POST['offer_id'])
        ) {
            $_SESSION['error'] = "Invalid offer request.";
            header("Location: index.php?page=offered-list");
            exit;
        }

        $offerId = (int) $_POST['offer_id'];

        if ($offerId <= 0) {
            $_SESSION['error'] = "Invalid offer ID.";
            header("Location: index.php?page=offered-list");
            exit;
        }

        require_once __DIR__ . '/../classes/Offer.php';

        try {
            $offerModel = new Offer();

            // Get the offer details.
            $offer = $offerModel->getOfferById($offerId);

            if (!$offer) {
                throw new Exception("Job offer not found.");
            }

            $currentStatus = strtolower(
                trim($offer['offer_status'] ?? '')
            );

            // Prevent accepting an already accepted or hired offer again.
            if ($currentStatus === 'accepted') {
                if ($offerModel->alreadyHired((int) $offer['application_id'])) {
                    $_SESSION['success'] =
                        "This offer has already been accepted and the applicant is hired.";
                } else {
                    // Finish hiring if the offer was accepted previously
                    // but the hired record does not exist.
                    $offerModel->hireApplicant($offerId);

                    $_SESSION['success'] =
                        "Offer accepted and applicant automatically marked as Hired.";
                }

                header("Location: index.php?page=offered-list");
                exit;
            }

            if (!in_array($currentStatus, ['sent', 'negotiating'], true)) {
                throw new Exception(
                    "This offer cannot be accepted. Current status: " .
                        ($offer['offer_status'] ?? 'Unknown')
                );
            }

            // Mark the offer as Accepted first.
            $offerModel->updateStatus($offerId, 'Accepted');

            // Automatically create the hired record and update the application.
            $offerModel->hireApplicant($offerId);

            $_SESSION['success'] =
                "Offer accepted successfully! The applicant is now Hired.";
        } catch (Throwable $e) {
            error_log("Accept offer error: " . $e->getMessage());

            $_SESSION['error'] =
                "Unable to accept the offer: " . $e->getMessage();
        }

        header("Location: index.php?page=offered-list");
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
