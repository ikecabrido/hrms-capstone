<?php
header('Content-Type: application/json');

require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->query("SELECT DISTINCT category, COUNT(*) as count FROM ld_course WHERE status = 'active' GROUP BY category ORDER BY count DESC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($categories);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
