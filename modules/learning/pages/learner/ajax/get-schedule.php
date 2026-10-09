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

    $limit = isset($_GET['limit']) ? max(1, min(50, (int) $_GET['limit'])) : 10;

    // Get upcoming events from the calendar and video conferences
    $events = [];

    // Calendar events
    $calStmt = $pdo->prepare("
        SELECT ce.id, ce.title, ce.description, ce.event_date AS event_time, ce.event_type,
               'calendar' AS source_type
        FROM ld_calendar_event ce
        WHERE ce.event_date >= CURDATE()
        ORDER BY ce.event_date ASC
        LIMIT :lim
    ");
    $calStmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $calStmt->execute();
    $calEvents = $calStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($calEvents as $e) {
        $events[] = [
            'id' => (int) $e['id'],
            'title' => $e['title'],
            'description' => $e['description'],
            'event_time' => $e['event_time'],
            'event_type' => $e['event_type'],
            'source_type' => $e['source_type'],
        ];
    }

    // Video conferences
    $vcStmt = $pdo->prepare("
        SELECT vc.id, vc.title, vc.scheduled_at AS event_time, vc.platform,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
               'video_conference' AS source_type
        FROM ld_video_conference vc
        LEFT JOIN em_employees emp ON emp.employee_id = vc.instructor_id
        WHERE vc.scheduled_at >= NOW() AND vc.status = 'scheduled'
        ORDER BY vc.scheduled_at ASC
        LIMIT :lim2
    ");
    $vcStmt->bindValue(':lim2', $limit, PDO::PARAM_INT);
    $vcStmt->execute();
    $vcEvents = $vcStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($vcEvents as $e) {
        $events[] = [
            'id' => (int) $e['id'],
            'title' => $e['title'],
            'instructor_name' => $e['instructor_name'],
            'event_time' => $e['event_time'],
            'platform' => $e['platform'],
            'source_type' => $e['source_type'],
        ];
    }

    // Sort by event_time
    usort($events, fn($a, $b) => strtotime($a['event_time']) - strtotime($b['event_time']));

    // Limit total
    $events = array_slice($events, 0, $limit);

    echo json_encode([
        'success' => true,
        'items' => $events,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load schedule.', 'error' => $e->getMessage()]);
}
