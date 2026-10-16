<?php
require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Application.php';
require_once __DIR__ . '/../classes/Interview.php';
require_once __DIR__ . '/Mailer.php';

use Controllers\Mailer;

class InterviewController
{
    private $applicationModel;
    private $interviewModel;

    public function __construct()
    {
        $this->applicationModel = new Application();
        $this->interviewModel   = new Interview();
    }

    public function schedule()
    {
        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            die("Invalid application ID");
        }

        $id = (int) $id;
        $app = $this->applicationModel->findById($id);
        if (!$app) {
            die("Application not found");
        }

        $interviews = $this->interviewModel->getByApplicationId($id) ?: [];

        $stages = [
            1 => 'Initial Interview',
            2 => 'Technical Interview',
            3 => 'Final Interview'
        ];

        $nextStageOrder = 1;
        $nextType = $stages[1];

        $previousStagePending = false;
        $previousStageFailed = false;
        $isCompleted = false;

        if (!empty($interviews)) {
            $latest = end($interviews);
            $latestStage = (int) ($latest['stage_order'] ?? 1);
            $latestResult = strtolower(trim($latest['result'] ?? 'pending'));

            if ($latestResult === 'pending') {
                $previousStagePending = true;
                $nextStageOrder = $latestStage;
                $nextType = $stages[$latestStage] ?? null;
            } elseif ($latestResult === 'failed') {
                $previousStageFailed = true;
                $nextStageOrder = $latestStage;
                $nextType = $stages[$latestStage] ?? null;
            } elseif ($latestResult === 'passed') {
                $nextStageOrder = $latestStage + 1;
                if ($nextStageOrder > 3) {
                    $isCompleted = true;
                    $nextType = null;
                } else {
                    $nextType = $stages[$nextStageOrder];
                }
            }
        }

        // Match existing interview record based on numerical stage order
        $interview = null;
        if ($nextStageOrder <= 3) {
            foreach ($interviews as $i) {
                if ((int)$i['stage_order'] === $nextStageOrder) {
                    $interview = $i;
                    break;
                }
            }
        }

        require __DIR__ . '/../pages/schedule-interview.php';
    }

    public function result()
    {
        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            die("Invalid application ID");
        }

        $id = (int) $id;
        $application = $this->applicationModel->findById($id);
        if (!$application) {
            die("Application not found");
        }

        $interviews = $this->interviewModel->getByApplicationId($id) ?: [];

        require __DIR__ . '/../pages/interviews/show.php';
    }

    public function hire()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("Invalid request");
        }

        $id = (int)($_POST['application_id'] ?? 0);
        if ($id <= 0) {
            die("Invalid application ID");
        }

        $result = $this->applicationModel->hire($id);
        $message = $result['message'] ?? 'Unable to hire applicant.';

        header('Location: index.php?page=hired-applicants&message=' . urlencode($message));
        exit;
    }

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("Invalid request");
        }

        try {

            // -----------------------------
            // Get POST data
            // -----------------------------
            $appId = (int) ($_POST['application_id'] ?? 0);
            $type  = trim($_POST['interview_type'] ?? '');

            $interviewDate = trim($_POST['interview_date'] ?? '');
            $interviewTime = trim($_POST['interview_time'] ?? '');
            $interviewMode = trim($_POST['interview_mode'] ?? '');
            $meetingLink   = trim($_POST['meeting_link'] ?? '');
            $interviewer   = trim($_POST['interviewer'] ?? '');

            // -----------------------------
            // Basic validation
            // -----------------------------
            if ($appId <= 0) {
                die("Invalid application ID.");
            }

            if (
                empty($interviewDate) ||
                empty($interviewTime) ||
                empty($type) ||
                empty($interviewMode) ||
                empty($meetingLink) ||
                empty($interviewer)
            ) {
                die("Please complete all required fields.");
            }

            // -----------------------------
            // Stage mapping
            // -----------------------------
            $stages = [
                'Initial Interview'   => 1,
                'Technical Interview' => 2,
                'Final Interview'     => 3
            ];

            if (!isset($stages[$type])) {
                die("Invalid interview stage.");
            }

            $stageOrder = $stages[$type];

            // -----------------------------
            // Check whether previous
            // stage has been passed
            // -----------------------------
            if (!$this->interviewModel->canProceed($appId, $stageOrder)) {

                header(
                    "Location: index.php?page=schedule-interview&id={$appId}&msg=" .
                        urlencode("You must pass the previous stage first.")
                );
                exit;
            }

            // -----------------------------
            // Prevent duplicate stage
            // -----------------------------
            if ($this->interviewModel->stageExists($appId, $type)) {

                header(
                    "Location: index.php?page=schedule-interview&id={$appId}&msg=" .
                        urlencode("This interview stage already exists.")
                );
                exit;
            }

            // -----------------------------
            // Prepare interview data
            // -----------------------------
            $dbData = [
                'application_id' => $appId,
                'interview_date' => $interviewDate,
                'interview_time' => $interviewTime,
                'interview_type' => $type,
                'stage_order'    => $stageOrder,
                'interview_mode' => $interviewMode,
                'meeting_link'   => $meetingLink,
                'interviewer'    => $interviewer
            ];

            // -----------------------------
            // Save interview
            // -----------------------------
            $created = $this->interviewModel->create($dbData);

            if (!$created) {
                die("Failed to save interview to database.");
            }

            // -----------------------------
            // Get applicant
            // -----------------------------
            $app = $this->applicationModel->findById($appId);

            if (!$app) {
                die("Application not found.");
            }

            if (empty($app['email'])) {
                die("Applicant email address is missing.");
            }

            $fullName = trim(
                ($app['first_name'] ?? '') . ' ' .
                    ($app['last_name'] ?? '')
            );

            // -----------------------------
            // Prepare email data
            // -----------------------------
            $emailData = [
                'date'        => date('F j, Y', strtotime($interviewDate)),
                'time'        => date('g:i A', strtotime($interviewTime)),
                'type'        => $type,
                'mode'        => $interviewMode,
                'link'        => $meetingLink,
                'interviewer' => $interviewer
            ];

            // -----------------------------
            // Send email
            // -----------------------------
            $mailer = new Mailer();

            $sent = $mailer->sendInterviewEmail(
                $app['email'],
                $fullName,
                $emailData
            );

            // -----------------------------
            // Email result
            // -----------------------------
            if ($sent) {

                $msg = "Interview scheduled and email sent successfully.";
            } else {

                $error = '';

                if (property_exists($mailer, 'last_error')) {
                    $error = $mailer->last_error;
                }

                error_log(
                    "Interview email failed for application ID {$appId}. " .
                        $error
                );

                $msg = "Interview scheduled, but the email failed to send.";
            }

            // -----------------------------
            // Redirect
            // -----------------------------
            header(
                "Location: index.php?page=schedule-interview&id={$appId}&msg=" .
                    urlencode($msg)
            );
            exit;
        } catch (Throwable $e) {

            error_log(
                "Interview scheduling error: " .
                    $e->getMessage()
            );

            error_log(
                $e->getTraceAsString()
            );

            die("Interview scheduling failed: " .
                htmlspecialchars($e->getMessage()));
        }
    }

    public function updateResult()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("Invalid request");
        }

        $id     = (int) ($_POST['id'] ?? 0);
        $result = strtolower(trim($_POST['result'] ?? ''));

        if ($id <= 0 || !in_array($result, ['passed', 'failed'], true)) {
            die("Invalid request data.");
        }

        $interview = $this->interviewModel->getById($id);
        if (!$interview) {
            die("Interview not found");
        }

        if (strtolower(trim($interview['result'] ?? 'pending')) !== 'pending') {
            header("Location: index.php?page=schedule-interview&id={$interview['application_id']}&msg=" . urlencode("Already decided"));
            exit;
        }

        $this->interviewModel->updateResult($id, $result);
        header("Location: index.php?page=schedule-interview&id={$interview['application_id']}&msg=" . urlencode("Result updated"));
        exit;
    }
    private function getDB()
    {
        $database = new Database();
        return $database->getConnection();
    }
    public function submitFeedback()
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json');

        try {

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method.');
            }

            $interview_id = (int)($_POST['interview_id'] ?? 0);
            $application_id = (int)($_POST['application_id'] ?? 0);
            $rating = (int)($_POST['rating'] ?? 0);
            $feedback = trim($_POST['feedback'] ?? '');

            /*
         * =========================
         * VALIDATION
         * =========================
         */

            if ($interview_id <= 0) {
                throw new Exception('Invalid interview ID.');
            }

            if ($application_id <= 0) {
                throw new Exception('Invalid application ID.');
            }

            if ($rating < 1 || $rating > 5) {
                throw new Exception('Please provide a rating from 1 to 5.');
            }


            /*
         * =========================
         * DATABASE
         * =========================
         */

            $db = $this->getDB();

            /*
         * Start transaction
         */
            $db->beginTransaction();


            /*
         * =========================
         * CHECK INTERVIEW
         * =========================
         */

            $checkSql = "
            SELECT
                id,
                application_id,
                stage_order,
                result
            FROM rao_interviews
            WHERE id = :interview_id
              AND application_id = :application_id
            LIMIT 1
        ";

            $stmt = $db->prepare($checkSql);

            $stmt->execute([
                ':interview_id'   => $interview_id,
                ':application_id' => $application_id
            ]);

            $interview = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$interview) {
                throw new Exception('Interview record not found.');
            }


            /*
         * =========================
         * CHECK FINAL STAGE
         * =========================
         */

            $stage = (int)$interview['stage_order'];

            if ($stage !== 3) {
                throw new Exception(
                    'Feedback can only be submitted for the final interview.'
                );
            }


            /*
         * =========================
         * SAVE INTERVIEW FEEDBACK
         * =========================
         *
         * IMPORTANT:
         * Keep Ready for Offer here.
         */

            $updateInterviewSql = "
            UPDATE rao_interviews
            SET
                rating = :rating,
                feedback = :feedback,
                feedback_date = NOW(),
                result = 'passed',
                status = 'Ready for Offer'
            WHERE id = :interview_id
              AND application_id = :application_id
        ";

            $updateInterview = $db->prepare($updateInterviewSql);

            $updateInterview->execute([
                ':rating'         => $rating,
                ':feedback'       => $feedback,
                ':interview_id'   => $interview_id,
                ':application_id' => $application_id
            ]);


            /*
         * =========================
         * MARK APPLICATION
         * READY FOR OFFER
         * =========================
         *
         * This is important because
         * your rao_applications table
         * has ready_for_offer.
         */

            $updateApplicationSql = "
            UPDATE rao_applications
            SET ready_for_offer = 1
            WHERE id = :application_id
        ";

            $updateApplication = $db->prepare($updateApplicationSql);

            $updateApplication->execute([
                ':application_id' => $application_id
            ]);


            /*
         * =========================
         * COMMIT
         * =========================
         */

            $db->commit();


            /*
         * =========================
         * SUCCESS RESPONSE
         * =========================
         */

            echo json_encode([
                'success'    => true,
                'message'    => 'Feedback saved successfully. Candidate is now Ready for Offer.',
                'stage'      => $stage,
                'finalStage' => true,
                'readyForOffer' => true
            ]);

            exit;
        } catch (PDOException $e) {

            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);

            exit;
        } catch (Throwable $e) {

            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);

            exit;
        }
    }
}
