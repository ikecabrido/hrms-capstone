<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
}

try {
    $userId = (int) ($_SESSION['employee_id'] ?? 0);

    switch ($method) {
        case 'GET':
            $start = $_GET['start'] ?? null;
            $end   = $_GET['end'] ?? null;

            $sql = "
                SELECT
                    c.id,
                    c.title,
                    c.description,
                    c.date          AS start_time,
                    c.date          AS end_time,
                    1               AS all_day,
                    c.location,
                    c.event_type,
                    c.status,
                    c.priority,
                    c.color,
                    NULL            AS employee_id,
                    NULL            AS department_id,
                    c.created_by,
                    c.created_at,
                    c.updated_at,
                    NULL            AS employee_name,
                    NULL            AS employee_email,
                    NULL            AS employee_department,
                    NULL            AS department_name
                FROM lc_calendar c
                WHERE 1=1
            ";
            $params = [];

            if ($start && $end) {
                $sql .= " AND c.date >= :start AND c.date <= :end";
                $params[':start'] = $start;
                $params[':end']   = $end;
            } elseif ($start) {
                $sql .= " AND c.date >= :start";
                $params[':start'] = $start;
            }

            $sql .= " ORDER BY c.date ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data'    => $rows,
                'count'   => count($rows),
            ]);
            break;

        case 'POST':
            $title         = trim((string) ($body['title'] ?? ''));
            $description   = trim((string) ($body['description'] ?? ''));
            $startTime     = (string) ($body['start_time'] ?? '');
            $endTime       = !empty($body['end_time']) ? (string) $body['end_time'] : null;
            $allDay        = !empty($body['all_day']) ? 1 : 0;
            $location      = trim((string) ($body['location'] ?? ''));
            $eventType     = (string) ($body['event_type'] ?? 'Other');
            $status        = (string) ($body['status'] ?? 'Scheduled');
            $priority      = (string) ($body['priority'] ?? 'medium');
            $color         = trim((string) ($body['color'] ?? '#3B82C4'));
            $employeeId    = !empty($body['employee_id']) ? (int) $body['employee_id'] : null;
            $departmentId  = !empty($body['department_id']) ? (int) $body['department_id'] : null;
            $notifyEmpId   = !empty($body['notify_employee_id']) ? (int) $body['notify_employee_id'] : null;

            if ($title === '' || $startTime === '') {
                echo json_encode(['success' => false, 'message' => 'Title and start_time are required.']);
                exit;
            }

            $allowedTypes = ['Compliance', 'Training', 'Audit', 'Legal Case', 'Government Contribution', 'Document Expiration', 'Meeting', 'Policy', 'Recruitment', 'Exit', 'Holiday', 'Other'];
            $allowedStatus = ['Scheduled', 'Completed', 'Cancelled', 'Postponed'];
            $allowedPriority = ['low', 'medium', 'high', 'urgent'];

            if (!in_array($eventType, $allowedTypes, true)) {
                $eventType = 'Other';
            }
            if (!in_array($status, $allowedStatus, true)) {
                $status = 'Scheduled';
            }
            if (!in_array($priority, $allowedPriority, true)) {
                $priority = 'medium';
            }

            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO lc_calendar
                    (title, description, date, location,
                     event_type, status, priority, color, created_by)
                VALUES
                    (:title, :description, :event_date, :location,
                     :event_type, :status, :priority, :color, :created_by)
            ");
            $stmt->execute([
                ':title'        => $title,
                ':description'  => $description ?: null,
                ':event_date'   => $startTime,
                ':location'     => $location ?: null,
                ':event_type'   => $eventType,
                ':status'       => $status,
                ':priority'     => $priority,
                ':color'        => $color,
                ':created_by'   => $userId,
            ]);
            $newId = (int) $db->lastInsertId();

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Event created successfully.',
                'data'    => ['id' => $newId],
            ]);
            break;

        case 'PUT':
        case 'PATCH':
            if (empty($body['id'])) {
                echo json_encode(['success' => false, 'message' => 'Event ID is required for update.']);
                exit;
            }

            $updateId = (int) $body['id'];

            $fields = [];
            $params = [':id' => $updateId];

            $map = [
                'title'         => 'title',
                'description'   => 'description',
                'event_date'    => 'date',
                'location'      => 'location',
                'event_type'    => 'event_type',
                'status'        => 'status',
                'priority'      => 'priority',
                'color'         => 'color',
            ];

            foreach ($map as $key => $col) {
                if (array_key_exists($key, $body)) {
                    $val = $body[$key];
                    if ($key === 'all_day') {
                        $val = !empty($val) ? 1 : 0;
                    } elseif ($key === 'employee_id' || $key === 'department_id') {
                        $val = !empty($val) ? (int) $val : null;
                    } elseif ($val === '' || $val === null) {
                        $val = null;
                    }
                    $fields[] = "`$col` = :$key";
                    $params[":$key"] = $val;
                }
            }

            if (empty($fields)) {
                echo json_encode(['success' => false, 'message' => 'No fields to update.']);
                exit;
            }

            $sqlUpdate = "UPDATE lc_calendar SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $db->prepare($sqlUpdate);
            $stmt->execute($params);

            echo json_encode([
                'success' => true,
                'message' => 'Event updated successfully.',
                'data'    => ['id' => $updateId],
            ]);
            break;

        case 'DELETE':
            if (empty($body['id'])) {
                echo json_encode(['success' => false, 'message' => 'Event ID is required for deletion.']);
                exit;
            }

            $delId = (int) $body['id'];

            $stmt = $db->prepare('SELECT id FROM lc_calendar WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $delId]);
            if ($stmt->fetch(PDO::FETCH_ASSOC) === null) {
                echo json_encode(['success' => false, 'message' => 'Event not found.']);
                exit;
            }

            $stmt = $db->prepare('DELETE FROM lc_calendar WHERE id = :id');
            $stmt->execute([':id' => $delId]);

            echo json_encode([
                'success' => true,
                'message' => 'Event deleted successfully.',
                'data'    => ['id' => $delId],
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data'    => [],
    ]);
}
