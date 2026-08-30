<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    /*
     * ---------------------------------------------------------
     * Learner authentication
     * ---------------------------------------------------------
     *
     * Authentication is required for learner APIs.
     * Enrollment is NOT required to inspect an available
     * learning path and its available items.
     */
    $learnerId = isset($_SESSION['employee_id'])
        ? (int) $_SESSION['employee_id']
        : 0;

    if ($learnerId <= 0) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Learning path ID
     * ---------------------------------------------------------
     */
    $learningPathId = isset($_GET['learning_path_id'])
        ? (int) $_GET['learning_path_id']
        : 0;

    /*
     * Also accept "id" for consistency with the other
     * discovery endpoints.
     */
    if ($learningPathId <= 0 && isset($_GET['id'])) {
        $learningPathId = (int) $_GET['id'];
    }

    if ($learningPathId <= 0) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Learning path ID is required.'
        ]);

        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    /*
     * ---------------------------------------------------------
     * Verify that the learning path is available
     * ---------------------------------------------------------
     */
    $pathStmt = $pdo->prepare(
        'SELECT
            id,
            instructor_id,
            title,
            description,
            assigned_to,
            status,
            created_at,
            updated_at
         FROM ld_learning_path
         WHERE id = :id
           AND status = :status
         LIMIT 1'
    );

    $pathStmt->execute([
        ':id' => $learningPathId,
        ':status' => 'active'
    ]);

    $learningPath = $pathStmt->fetch(PDO::FETCH_ASSOC);

    if (!$learningPath) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Learning path not found or unavailable.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Get active learning-path items
     * ---------------------------------------------------------
     *
     * The schema stores the referenced entity in reference_id
     * and identifies its type through item_type.
     *
     * We return the path items in their configured order.
     */
    $itemStmt = $pdo->prepare(
        'SELECT
            id,
            learning_path_id,
            item_type,
            reference_id,
            order_index,
            status
         FROM ld_learning_path_item
         WHERE learning_path_id = :learning_path_id
           AND status = :status
         ORDER BY order_index ASC, id ASC'
    );

    $itemStmt->execute([
        ':learning_path_id' => $learningPathId,
        ':status' => 'active'
    ]);

    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'learning_path' => $learningPath,
        'items' => $items,
        'count' => count($items)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load learning path items.'
    ]);
}