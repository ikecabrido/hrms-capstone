<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
require_once dirname(__FILE__, 5) . '/classes/dberror.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    /*
     * Evaluation questions are stored in ld_quiz_question keyed by
     * (item_type = 'evaluation', reference_id = <evaluation id>).
     * This endpoint previously filtered on `quiz_id` and `module_id`, neither of
     * which exists, so it could only ever fail.
     */
    $referenceId = (int) ($_GET['evaluation_id'] ?? $_GET['reference_id'] ?? 0);
    if ($referenceId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'evaluation_id is required.']);
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT * FROM ld_quiz_question
         WHERE item_type = 'evaluation' AND reference_id = :eid
         ORDER BY order_index ASC, id ASC"
    );
    $stmt->execute([':eid' => $referenceId]);

    echo json_encode(['success' => true, 'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    DbError::json($e, 'instructor/get-evaluation-question');
}
