<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
require_once dirname(__FILE__, 5) . '/classes/dberror.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    /*
     * Quiz and evaluation questions share one table, keyed by
     * (item_type, reference_id) — a quiz or evaluation has no id column of its own
     * in ld_quiz_question. This endpoint previously filtered on `qq.quiz_id`, a
     * column that does not exist, so it could only ever fail.
     */
    $itemType = trim((string) ($_GET['item_type'] ?? 'quiz'));
    if (!in_array($itemType, ['quiz', 'evaluation'], true)) {
        $itemType = 'quiz';
    }

    $referenceId = (int) ($_GET['reference_id'] ?? $_GET['quiz_id'] ?? $_GET['evaluation_id'] ?? 0);
    if ($referenceId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'reference_id is required.']);
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT qq.*, GROUP_CONCAT(DISTINCT qqo.id) AS option_ids
         FROM ld_quiz_question qq
         LEFT JOIN ld_quiz_question_option qqo ON qqo.question_id = qq.id
         WHERE qq.item_type = :item_type AND qq.reference_id = :reference_id
         GROUP BY qq.id
         ORDER BY qq.order_index ASC, qq.id ASC'
    );
    $stmt->execute([':item_type' => $itemType, ':reference_id' => $referenceId]);

    echo json_encode(['success' => true, 'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    DbError::json($e, 'instructor/get-quiz-question');
}
