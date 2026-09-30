<?php


require_once __DIR__ . '/../../../database/db.php';

try {

    // Create Database object
    $database = new Database();

    // Get PDO connection
    $db = $database->getConnection();

    // Only allow POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['error'] = "Invalid request.";
        header("Location: index.php?page=tracking");
        exit;
    }

    // Get application ID
    $application_id = isset($_POST['application_id'])
        ? (int) $_POST['application_id']
        : 0;

    if ($application_id <= 0) {
        $_SESSION['error'] = "Invalid candidate selected.";
        header("Location: index.php?page=tracking");
        exit;
    }

    /*
     * Mark candidate as Ready for Offer
     */
    $stmt = $db->prepare("
        UPDATE rao_applications
        SET ready_for_offer = 1
        WHERE application_id = ?
    ");

    $stmt->execute([$application_id]);

    /*
     * Check if candidate exists
     */
    if ($stmt->rowCount() > 0) {

        $_SESSION['success'] =
            "Candidate marked as Ready for Offer.";
    } else {

        /*
         * The UPDATE can return 0 if ready_for_offer
         * is already 1.
         */
        $check = $db->prepare("
            SELECT application_id, ready_for_offer
            FROM rao_applications
            WHERE application_id = ?
        ");

        $check->execute([$application_id]);

        $candidate = $check->fetch(PDO::FETCH_ASSOC);

        if (!$candidate) {

            $_SESSION['error'] =
                "Candidate was not found.";
        } elseif ((int)$candidate['ready_for_offer'] === 1) {

            $_SESSION['success'] =
                "Candidate is already Ready for Offer.";
        } else {

            $_SESSION['error'] =
                "Candidate could not be updated.";
        }
    }
} catch (PDOException $e) {

    $_SESSION['error'] =
        "Database error: " . $e->getMessage();
}

/*
 * Return to tracking page
 */
header("Location: index.php?page=tracking");
exit;
