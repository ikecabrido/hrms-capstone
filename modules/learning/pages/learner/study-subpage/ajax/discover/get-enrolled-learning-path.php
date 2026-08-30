<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
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

    $pathId = isset($_GET['learning_path_id'])
        ? (int) $_GET['learning_path_id']
        : 0;

    $database = new Database();
    $pdo = $database->getConnection();

    $enrollment = new Enrollment($pdo);

    $enrolledCourses = array_values(array_filter(
        $enrollment->getByLearner($learnerId),
        static function (array $item): bool {
            return ($item['status'] ?? '') !== 'withdrawn';
        }
    ));

    $courseIds = array_values(array_unique(array_map(
        static function (array $item): int {
            return (int) $item['course_id'];
        },
        $enrolledCourses
    )));

    /*
     * A learner is "enrolled" in a learning path when the path is
     * assigned to them directly, or when the path contains at least
     * one course item they are currently enrolled in.
     */
    $assignedStmt = $pdo->prepare("
        SELECT id, instructor_id, title, description, assigned_to, status, created_at, updated_at
        FROM ld_learning_path
        WHERE assigned_to = :learner_id
    ");

    $assignedStmt->execute([':learner_id' => $learnerId]);
    $assignedPaths = $assignedStmt->fetchAll(PDO::FETCH_ASSOC);

    $viaCoursePathIds = [];

    if (!empty($courseIds)) {
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));

        $itemStmt = $pdo->prepare("
            SELECT DISTINCT learning_path_id
            FROM ld_learning_path_item
            WHERE item_type = 'course'
              AND reference_id IN ($placeholders)
              AND status = 'active'
        ");

        $itemStmt->execute($courseIds);
        $viaCoursePathIds = array_map(
            static function (array $row): int {
                return (int) $row['learning_path_id'];
            },
            $itemStmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    $pathIds = array_values(array_unique(array_merge(
        array_map(static function (array $p): int {
            return (int) $p['id'];
        }, $assignedPaths),
        $viaCoursePathIds
    )));

    if (empty($pathIds)) {
        echo json_encode([
            'success' => true,
            'items' => [],
            'count' => 0
        ]);

        exit;
    }

    /*
     * Single enrolled learning path, with ordered items.
     */
    if ($pathId > 0) {
        if (!in_array($pathId, $pathIds, true)) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'You are not enrolled in this learning path.'
            ]);

            exit;
        }

        $pathStmt = $pdo->prepare("
            SELECT id, instructor_id, title, description, assigned_to, status, created_at, updated_at
            FROM ld_learning_path
            WHERE id = :id
            LIMIT 1
        ");

        $pathStmt->execute([':id' => $pathId]);
        $path = $pathStmt->fetch(PDO::FETCH_ASSOC);

        if (!$path) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Learning path not found.'
            ]);

            exit;
        }

        $itemsStmt = $pdo->prepare("
            SELECT id, item_type, reference_id, order_index, status
            FROM ld_learning_path_item
            WHERE learning_path_id = :path_id
              AND status = 'active'
            ORDER BY order_index ASC
        ");

        $itemsStmt->execute([':path_id' => $pathId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $item['is_enrolled'] = $item['item_type'] === 'course'
                && in_array((int) $item['reference_id'], $courseIds, true);
        }

        unset($item);

        $path['items'] = $items;

        echo json_encode([
            'success' => true,
            'item' => $path
        ]);

        exit;
    }

    /*
     * All learning paths the learner is enrolled in.
     */
    $pathPlaceholders = implode(',', array_fill(0, count($pathIds), '?'));

    $pathsStmt = $pdo->prepare("
        SELECT id, instructor_id, title, description, assigned_to, status, created_at, updated_at
        FROM ld_learning_path
        WHERE id IN ($pathPlaceholders)
        ORDER BY title ASC
    ");

    $pathsStmt->execute($pathIds);
    $paths = $pathsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => $paths,
        'count' => count($paths)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load enrolled learning paths.'
    ]);
}
