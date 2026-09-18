<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 4) . '/classes/learningpath.php';
require_once dirname(__FILE__, 6) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $learningPath = new LearningPath($pdo);

    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Learning path ID required']);
        exit;
    }

    $pathData = $learningPath->getById($id);

    if (!$pathData) {
        http_response_code(404);
        echo json_encode(['error' => 'Learning path not found']);
        exit;
    }

    // Fetch items for this path
    $items = [];
    try {
        $itemStmt = $pdo->prepare('SELECT lpi.id, lpi.item_type, lpi.reference_id, lpi.order_index, lpi.status AS item_status
            FROM ld_learning_path_item lpi
            WHERE lpi.learning_path_id = :lpid
            ORDER BY lpi.order_index ASC, lpi.id ASC');
        $itemStmt->execute([':lpid' => $id]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        // Resolve titles for each item
        $tableMap = [
            'course' => 'ld_course',
            'module' => 'ld_module',
            'lesson' => 'ld_lesson',
            'quiz' => 'ld_quiz',
            'evaluation' => 'ld_evaluation',
            'program' => 'ld_program',
            'video-conference' => 'ld_video_conference',
        ];
        foreach ($items as &$item) {
            $table = $tableMap[$item['item_type']] ?? null;
            if ($table) {
                $tStmt = $pdo->prepare('SELECT title FROM ' . $table . ' WHERE id = :rid LIMIT 1');
                $tStmt->execute([':rid' => $item['reference_id']]);
                $tRow = $tStmt->fetch(PDO::FETCH_ASSOC);
                $item['title'] = $tRow ? ($tRow['title'] ?? 'Untitled') : 'Untitled';
            } else {
                $item['title'] = 'Untitled';
            }
        }
        unset($item);
    } catch (Throwable $e) { /* ignore */ }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $pathData,
        'items' => $items
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

