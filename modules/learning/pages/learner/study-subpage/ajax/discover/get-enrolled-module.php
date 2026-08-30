<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
require_once dirname(__FILE__, 6) . '/classes/module.php';
require_once dirname(__FILE__, 6) . '/classes/progress.php';
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

    $moduleId = isset($_GET['module_id'])
        ? (int) $_GET['module_id']
        : 0;

    $courseId = isset($_GET['course_id'])
        ? (int) $_GET['course_id']
        : 0;

    $database = new Database();
    $pdo = $database->getConnection();

    $enrollment = new Enrollment($pdo);
    $progress = new Progress($pdo);

    /*
     * Single module.
     */
    if ($moduleId > 0) {
        $moduleStmt = $pdo->prepare("
            SELECT
                m.id,
                m.course_id,
                m.title,
                m.description,
                m.status,
                m.order_index
            FROM ld_module m
            WHERE m.id = :module_id
            LIMIT 1
        ");

        $moduleStmt->execute([
            ':module_id' => $moduleId
        ]);

        $module = $moduleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$module || $module['status'] !== 'active') {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Module not found.'
            ]);
            exit;
        }

        $enrollmentRow = $enrollment->getByLearnerAndCourse(
            $learnerId,
            (int) $module['course_id']
        );

        if (
            !$enrollmentRow ||
            $enrollmentRow['status'] === 'withdrawn'
        ) {
            http_response_code(403);

            echo json_encode([
                'success' => false,
                'message' => 'You are not enrolled in this module\'s course.'
            ]);

            exit;
        }

        $moduleProgress = $progress->getItem(
            (int) $enrollmentRow['id'],
            'module',
            $moduleId
        );

        $module['progress'] = $moduleProgress ?: [
            'status' => 'not_started'
        ];

        echo json_encode([
            'success' => true,
            'item' => $module,
            'enrollment' => $enrollmentRow
        ]);

        exit;
    }

    /*
     * Course ID is required for a module list.
     */
    if ($courseId <= 0) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Course ID is required.'
        ]);

        exit;
    }

    $enrollmentRow = $enrollment->getByLearnerAndCourse(
        $learnerId,
        $courseId
    );

    if (
        !$enrollmentRow ||
        $enrollmentRow['status'] === 'withdrawn'
    ) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'You are not enrolled in this course.'
        ]);

        exit;
    }

    $stmt = $pdo->prepare("
        SELECT
            id,
            course_id,
            title,
            description,
            status,
            order_index
        FROM ld_module
        WHERE course_id = :course_id
          AND status = 'active'
        ORDER BY order_index ASC, id ASC
    ");

    $stmt->execute([
        ':course_id' => $courseId
    ]);

    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($modules as &$module) {
        $moduleProgress = $progress->getItem(
            (int) $enrollmentRow['id'],
            'module',
            (int) $module['id']
        );

        $module['progress_status'] =
            $moduleProgress['status'] ?? 'not_started';
    }

    unset($module);

    echo json_encode([
        'success' => true,
        'items' => $modules,
        'count' => count($modules),
        'enrollment_id' => (int) $enrollmentRow['id']
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load enrolled modules.'
    ]);
}