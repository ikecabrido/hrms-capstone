<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    // No dedicated class method filters by assigned_to yet, so this
    // queries ld_learning_path directly for paths assigned to this learner.
    $stmt = $pdo->prepare(
        "SELECT * FROM ld_learning_path WHERE assigned_to = :learner_id AND status = 'active' ORDER BY title ASC"
    );
    $stmt->execute([':learner_id' => $learnerId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'title' => $item['title'],
                'description' => $item['description'],
            ];
        }, $items),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load learning paths.',
        'error' => $e->getMessage(),
    ]);
}
