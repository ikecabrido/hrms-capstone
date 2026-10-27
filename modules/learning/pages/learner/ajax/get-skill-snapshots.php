<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS ld_skill_snapshot (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        learner_id INT UNSIGNED NOT NULL,
        total_skills INT UNSIGNED NOT NULL DEFAULT 0,
        acquired_count INT UNSIGNED NOT NULL DEFAULT 0,
        gap_count INT UNSIGNED NOT NULL DEFAULT 0,
        acquired_skills JSON DEFAULT NULL,
        gap_skills JSON DEFAULT NULL,
        snapshot_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_learner_date (learner_id, snapshot_date),
        INDEX idx_learner (learner_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Get all snapshots for this learner, ordered by date
    $stmt = $pdo->prepare("
        SELECT snapshot_date, total_skills, acquired_count, gap_count, acquired_skills, gap_skills
        FROM ld_skill_snapshot
        WHERE learner_id = :lid
        ORDER BY snapshot_date ASC
    ");
    $stmt->execute([':lid' => $learnerId]);
    $snapshots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for chart
    $chartData = [];
    foreach ($snapshots as $snap) {
        $total = (int) $snap['total_skills'];
        $acquired = (int) $snap['acquired_count'];
        $pct = $total > 0 ? round(($acquired / $total) * 100) : 0;
        $chartData[] = [
            'date' => $snap['snapshot_date'],
            'total_skills' => $total,
            'acquired_count' => $acquired,
            'gap_count' => (int) $snap['gap_count'],
            'percentage' => $pct,
        ];
    }

    echo json_encode([
        'success' => true,
        'snapshots' => $chartData,
        'count' => count($chartData),
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load snapshots.',
        'error' => $e->getMessage(),
    ]);
}
