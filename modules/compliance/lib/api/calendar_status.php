<?php
header('Content-Type: application/json');
header('Access-Control-Allow-ethods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$pdo = $database->getConnection();

try {
    if ($pdo === null) {
        throw new Exception('Database connection failed');
    }

    $today = date('Y-m-d');
    $windowEnd = date('Y-m-t', strtotime('+1 month'));

    $eventCount = 0;
    $nextEvent = null;

    try {
        $stmt = $pdo->prepare('SELECT id, title AS event_title, event_type AS category, date AS event_date, status, color FROM lc_calendar WHERE date IS NOT NULL AND date >= :start AND date <= :end');
        $stmt->execute([':start' => $today, ':end' => $windowEnd]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $eventCount++;
            if ($nextEvent === null || $row['event_date'] < $nextEvent['date']) {
                $nextEvent = [
                    'id'       => 'calendar_' . $row['id'],
                    'title'    => $row['event_title'],
                    'category' => $row['category'],
                    'date'     => $row['event_date'],
                    'status'   => $row['status'],
                    'color'    => $row['color'],
                ];
            }
        }
    } catch (Throwable $e) {
    }

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM lc_notifications WHERE is_read = 0 AND (notification_type IS NULL OR notification_type != 'email')");
        $unread = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $unread = 0;
    }

    $hasUpcomingEvents = $eventCount > 0;
    $hasUnreadNotifications = $unread > 0;
    $showIndicator = $hasUpcomingEvents || $hasUnreadNotifications;

    echo json_encode([
        'success'                => true,
        'upcoming_events'        => $eventCount,
        'has_upcoming_events'    => $hasUpcomingEvents,
        'unread_notifications'   => $unread,
        'has_unread_notifications' => $hasUnreadNotifications,
        'show_indicator'         => $showIndicator,
        'next_event'             => $nextEvent,
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success'         => false,
        'message'         => 'Database error: ' . $e->getMessage(),
        'show_indicator'  => false,
        'upcoming_events' => 0,
        'unread_notifications' => 0,
        'next_event'      => null,
    ]);
}
