<?php
/**
 * Get Day Schedule API
 * Returns all employee schedules and attendance for a specific date
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../../../database/db.php';

try {
    $date = $_GET['date'] ?? null;
    $employee_id = $_GET['employee_id'] ?? null;

    if (!$date) {
        throw new Exception('Date is required');
    }

    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();

    $query = "SELECT a.*, e.full_name
              FROM ta_attendance a
              JOIN employees e ON a.employee_id = e.employee_id
              WHERE a.attendance_date = ?";
    $params = [$date];

    if ($employee_id) {
        $query .= " AND a.employee_id = ?";
        $params[] = $employee_id;
    }

    $query .= " ORDER BY a.time_in DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $records,
        'date' => $date,
        'message' => 'Day schedule retrieved successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
