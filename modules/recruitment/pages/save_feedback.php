<?php

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    /*
     * Get POST values
     */
    $interview_id = isset($_POST['interview_id'])
        ? (int) $_POST['interview_id']
        : 0;

    $application_id = isset($_POST['application_id'])
        ? (int) $_POST['application_id']
        : 0;

    $rating = isset($_POST['rating'])
        ? (int) $_POST['rating']
        : 0;

    $feedback = trim($_POST['feedback'] ?? '');


    /*
     * Validate
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
     * Make sure the interview exists
     */
    $checkSql = "
        SELECT id, application_id, stage_order
        FROM rao_interviews
        WHERE id = :interview_id
          AND application_id = :application_id
        LIMIT 1
    ";

    $checkStmt = $conn->prepare($checkSql);

    $checkStmt->execute([
        ':interview_id'   => $interview_id,
        ':application_id' => $application_id
    ]);

    $interview = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$interview) {
        throw new Exception('Interview record not found.');
    }


    /*
     * Get stage
     */
    $stage = (int) $interview['stage_order'];


    /*
     * Update the SPECIFIC interview
     */
    $updateSql = "
        UPDATE rao_interviews
        SET
            rating = :rating,
            feedback = :feedback,
            feedback_date = NOW(),
            result = 'passed'
        WHERE id = :interview_id
          AND application_id = :application_id
    ";

    $updateStmt = $conn->prepare($updateSql);

    $updateStmt->execute([
        ':rating'         => $rating,
        ':feedback'       => $feedback,
        ':interview_id'   => $interview_id,
        ':application_id' => $application_id
    ]);


    /*
     * If this is the FINAL interview,
     * mark the candidate as Ready for Offer.
     */
    if ($stage === 3) {

        $statusSql = "
            UPDATE rao_interviews
            SET status = 'Ready for Offer'
            WHERE id = :interview_id
              AND application_id = :application_id
        ";

        $statusStmt = $conn->prepare($statusSql);

        $statusStmt->execute([
            ':interview_id'   => $interview_id,
            ':application_id' => $application_id
        ]);
    }


    /*
     * Success response
     */
    echo json_encode([
        'success'   => true,
        'message'   => 'Feedback saved successfully.',
        'finalStage' => ($stage === 3),
        'stage'     => $stage
    ]);

    exit;
} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);

    exit;
} catch (Exception $e) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

    exit;
}
