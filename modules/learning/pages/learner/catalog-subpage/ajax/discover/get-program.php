<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/learningpath.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized.'
        ]);
        exit;
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    $database = new Database();
    $pdo = $database->getConnection();
    $pathModel = new LearningPath($pdo);

    if ($id > 0) {
        $path = $pathModel->getById($id);

        if (!$path || $path['status'] !== 'active') {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Learning path not found.'
            ]);
            exit;
        }

        /*
         * assigned_to:
         * NULL = generally available learning path
         * learner ID = specifically assigned to this learner
         */
        if (
            $path['assigned_to'] !== null &&
            (int) $path['assigned_to'] !== $learnerId
        ) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'This learning path is not assigned to you.'
            ]);
            exit;
        }

        $itemStmt = $pdo->prepare("
            SELECT
                id,
                learning_path_id,
                item_type,
                reference_id,
                order_index,
                status
            FROM ld_learning_path_item
            WHERE learning_path_id = :learning_path_id
              AND status = 'active'
            ORDER BY order_index ASC, id ASC
        ");

        $itemStmt->execute([
            ':learning_path_id' => $id
        ]);

        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'item' => $path,
            'items' => $items,
            'count' => count($items)
        ]);

        exit;
    }

    $paths = $pathModel->getList();

    $paths = array_values(array_filter(
        $paths,
        static function (array $path) use ($learnerId): bool {
            if (!isset($path['status']) || $path['status'] !== 'active') {
                return false;
            }

            return $path['assigned_to'] === null
                || (int) $path['assigned_to'] === $learnerId;
        }
    ));

    echo json_encode([
        'success' => true,
        'items' => $paths,
        'count' => count($paths)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load learning paths.'
    ]);
}