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

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id > 0) {
        // Single video conference
        $stmt = $pdo->prepare("
            SELECT vc.id, vc.title, vc.platform, vc.meeting_link, vc.scheduled_at, vc.duration_minutes,
                   vc.status, vc.created_at,
                   CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
                   vc.course_id, vc.program_id,
                   c.title AS course_title,
                   p.title AS program_title
            FROM ld_video_conference vc
            LEFT JOIN em_employees emp ON emp.employee_id = vc.instructor_id
            LEFT JOIN ld_course c ON c.id = vc.course_id
            LEFT JOIN ld_program p ON p.id = vc.program_id
            WHERE vc.id = :id AND vc.status = 'scheduled'
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Video conference not found.']);
            exit;
        }

        $attendStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_conference_attendance WHERE video_conference_id = :vcid");
        $attendStmt->execute([':vcid' => $id]);
        $attendeeCount = (int) $attendStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'item' => [
                'id' => (int) $item['id'],
                'title' => $item['title'],
                'platform' => $item['platform'],
                'scheduled_at' => $item['scheduled_at'],
                'duration_minutes' => $item['duration_minutes'] !== null ? (int) $item['duration_minutes'] : null,
                'instructor_name' => $item['instructor_name'],
                'course_id' => $item['course_id'] !== null ? (int) $item['course_id'] : null,
                'course_title' => $item['course_title'],
                'program_id' => $item['program_id'] !== null ? (int) $item['program_id'] : null,
                'program_title' => $item['program_title'],
                'attendee_count' => $attendeeCount,
                'status' => $item['status'],
                'created_at' => $item['created_at'],
            ],
        ]);
    } else {
        // List all active video conferences
        $stmt = $pdo->prepare("
            SELECT vc.id, vc.title, vc.platform, vc.scheduled_at, vc.duration_minutes,
                   vc.status,
                   CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name
            FROM ld_video_conference vc
            LEFT JOIN em_employees emp ON emp.employee_id = vc.instructor_id
            WHERE vc.status = 'scheduled'
            ORDER BY vc.scheduled_at ASC
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'items' => array_map(function ($item) {
                return [
                    'id' => (int) $item['id'],
                    'title' => $item['title'],
                    'platform' => $item['platform'],
                    'scheduled_at' => $item['scheduled_at'],
                    'duration_minutes' => $item['duration_minutes'] !== null ? (int) $item['duration_minutes'] : null,
                    'instructor_name' => $item['instructor_name'],
                    'status' => $item['status'],
                ];
            }, $items),
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load video conferences.', 'error' => $e->getMessage()]);
}
