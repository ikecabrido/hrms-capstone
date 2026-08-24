<?php
/**
 * Action Report Summary API
 * Returns summary counts for performance action tracking.
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../../auth/database.php';

try {
    $db = Database::getInstance()->getConnection();
    if (!$db) {
        throw new Exception('Database connection unavailable');
    }

    $statusSql = "SELECT status, COUNT(*) as count FROM wfa_performance_actions GROUP BY status";
    $stmt = $db->query($statusSql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'total_actions' => 0,
        'pending' => 0,
        'ongoing' => 0,
        'completed' => 0,
        'failed' => 0,
        'cancelled' => 0,
        'overdue' => 0
    ];

    foreach ($rows as $row) {
        $status = strtolower($row['status'] ?? 'unknown');
        $count = (int)$row['count'];
        if (array_key_exists($status, $summary)) {
            $summary[$status] = $count;
        }
        $summary['total_actions'] += $count;
    }

    $overdueStmt = $db->query("SELECT COUNT(*) as count FROM wfa_performance_actions WHERE status != 'completed' AND target_date < CURDATE()");
    if ($overdueStmt) {
        $overdueRow = $overdueStmt->fetch(PDO::FETCH_ASSOC);
        $summary['overdue'] = (int)($overdueRow['count'] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'summary' => $summary
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
