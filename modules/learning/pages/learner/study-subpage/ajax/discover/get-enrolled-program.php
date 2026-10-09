<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
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

    $programId = isset($_GET['program_id'])
        ? (int) $_GET['program_id']
        : 0;

    $database = new Database();
    $pdo = $database->getConnection();

    $enrollment = new Enrollment($pdo);
    $progress = new Progress($pdo);

    /*
     * A "program" has no direct enrollment record of its own.
     * A learner is considered enrolled in a program when they hold
     * an active (non-withdrawn) enrollment in at least one course
     * that belongs to that program.
     */
    $enrolledCourses = array_values(array_filter(
        $enrollment->getByLearner($learnerId),
        static function (array $item): bool {
            return ($item['status'] ?? '') !== 'withdrawn';
        }
    ));

    if (empty($enrolledCourses)) {
        echo json_encode([
            'success' => true,
            'items' => [],
            'count' => 0
        ]);

        exit;
    }

    $courseIds = array_values(array_unique(array_map(
        static function (array $item): int {
            return (int) $item['course_id'];
        },
        $enrolledCourses
    )));

    $placeholders = implode(',', array_fill(0, count($courseIds), '?'));

    $courseStmt = $pdo->prepare("
        SELECT id, program_id
        FROM ld_course
        WHERE id IN ($placeholders)
          AND program_id IS NOT NULL
    ");

    $courseStmt->execute($courseIds);
    $courseRows = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

    $courseIdsByProgram = [];

    foreach ($courseRows as $row) {
        $pid = (int) $row['program_id'];
        $courseIdsByProgram[$pid][] = (int) $row['id'];
    }

    if (empty($courseIdsByProgram)) {
        echo json_encode([
            'success' => true,
            'items' => [],
            'count' => 0
        ]);

        exit;
    }

    /*
     * Single enrolled program.
     */
    if ($programId > 0) {
        if (!isset($courseIdsByProgram[$programId])) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'You are not enrolled in this program.'
            ]);

            exit;
        }

        $programStmt = $pdo->prepare("
            SELECT id, instructor_id, title, description, status, created_at, updated_at
            FROM ld_program
            WHERE id = :id
            LIMIT 1
        ");

        $programStmt->execute([':id' => $programId]);
        $program = $programStmt->fetch(PDO::FETCH_ASSOC);

        if (!$program) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Program not found.'
            ]);

            exit;
        }

        $program['enrolled_course_count'] = count($courseIdsByProgram[$programId]);

        echo json_encode([
            'success' => true,
            'item' => $program
        ]);

        exit;
    }

    /*
     * All programs the learner has at least one enrolled course in.
     */
    $programIds = array_keys($courseIdsByProgram);
    $programPlaceholders = implode(',', array_fill(0, count($programIds), '?'));

    $programsStmt = $pdo->prepare("
        SELECT id, instructor_id, title, description, status, created_at, updated_at
        FROM ld_program
        WHERE id IN ($programPlaceholders)
        ORDER BY title ASC
    ");

    $programsStmt->execute($programIds);
    $programs = $programsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($programs as &$program) {
        $program['enrolled_course_count'] = count(
            $courseIdsByProgram[(int) $program['id']] ?? []
        );
    }

    unset($program);

    echo json_encode([
        'success' => true,
        'items' => $programs,
        'count' => count($programs)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load enrolled programs.'
    ]);
}
