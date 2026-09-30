<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create log file
$log_file = __DIR__ . '/email_debug.log';

function writeLog($message)
{
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

writeLog("=== Starting interview scheduling ===");

require_once "../app/config/Database.php";
require_once "../app/controllers/Mailer.php";

use Controllers\Mailer;

$db = Database::connect();

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    writeLog("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    die("Invalid request.");
}

// Validate required fields
if (
    empty($_POST['application_id']) ||
    empty($_POST['interview_date']) ||
    empty($_POST['interview_time']) ||
    empty($_POST['interviewer'])
) {
    writeLog("Missing required fields");
    die("Please fill in all required fields.");
}

$application_id = (int) $_POST['application_id'];
writeLog("Application ID: $application_id");

// Validate application_id is positive
if ($application_id <= 0) {
    writeLog("Invalid application ID: $application_id");
    die("Invalid application ID.");
}

// Format date & time
$raw_date = $_POST['interview_date'];
$raw_time = $_POST['interview_time'];
writeLog("Raw date: $raw_date, Raw time: $raw_time");

// Validate date
$interview_timestamp = strtotime($raw_date . ' ' . $raw_time);
if (!$interview_timestamp) {
    writeLog("Invalid date/time format");
    die("Invalid date or time format.");
}

$formatted_date = date("F d, Y", $interview_timestamp);
$formatted_time = date("h:i A", $interview_timestamp);
writeLog("Formatted date: $formatted_date, Formatted time: $formatted_time");

// Store values
$data = [
    'date'        => $formatted_date,
    'time'        => $formatted_time,
    'type'        => $_POST['interview_type'] ?? '',
    'mode'        => $_POST['interview_mode'] ?? '',
    'link'        => $_POST['meeting_link'] ?? '',
    'interviewer' => $_POST['interviewer']
];
writeLog("Interview data: " . json_encode($data));

// Get applicant info
$stmt = $db->prepare("SELECT first_name, last_name, email FROM rao_applications WHERE id=?");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();
$app = $result->fetch_assoc();

if (!$app) {
    writeLog("Applicant not found for ID: $application_id");
    die("Applicant not found.");
}

$full_name = $app['first_name'] . ' ' . $app['last_name'];
$email     = $app['email'];
writeLog("Applicant: $full_name, Email: $email");

// Check if interview already exists
// Check if interview already exists
$check = $db->prepare("SELECT id FROM rao_interviews WHERE application_id = ?");
$check->bind_param("i", $application_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    writeLog("Interview already exists for application ID: $application_id");

    // Use a specific message that the JS will recognize
    $msg = "Warning: Interview has already been scheduled for this applicant.";

    header("Location: index.php?page=schedule-interview&id=$application_id&msg=" . urlencode($msg));
    exit;
}
$check->close();

// Save interview
$insert = $db->prepare("
    INSERT INTO rao_interviews 
    (application_id, interview_date, interview_time, interview_type, interview_mode, meeting_link, interviewer)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$insert->bind_param(
    "issssss",
    $application_id,
    $raw_date,
    $raw_time,
    $data['type'],
    $data['mode'],
    $data['link'],
    $data['interviewer']
);

if (!$insert->execute()) {
    writeLog("Database insert failed: " . $db->error);
    die("Failed to schedule interview: " . $db->error);
}

writeLog("Interview saved to database successfully");

// Send email
writeLog("Initializing Mailer...");
try {
    $mailer = new Mailer();
    writeLog("Mailer initialized successfully");

    writeLog("Attempting to send email to: $email");
    $sent = $mailer->sendInterviewEmail($email, $full_name, $data);

    if ($sent) {
        writeLog("Email sent successfully to: $email");
    } else {
        writeLog("❌ Email failed to send to: $email");

        if (!empty($mailer->last_error)) {
            writeLog("PHPMailer Error: " . $mailer->last_error);
        }
    }
} catch (Exception $e) {
    writeLog("❌ Exception caught: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
    $sent = false;
}

// Log email status
if (!$sent) {
    error_log("Failed to send interview email to: $email for application ID: $application_id");
}

// Feedback
writeLog("=== Process completed ===");

$msg = $sent
    ? "Interview scheduled and email sent successfully"
    : "Interview saved but email failed. Check logs.";

header("Location: index.php?page=schedule-interview&id=$application_id&msg=" . urlencode($msg));
exit;
