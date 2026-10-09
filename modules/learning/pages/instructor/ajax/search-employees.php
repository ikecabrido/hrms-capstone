<?php
header('Content-Type: application/json');
session_start();

include_once dirname(__DIR__, 3) . '/classes/employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $employeeClass = new Employee();
    $instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

    if ($instructorId <= 0) {
        http_response_code(401);
        die(json_encode(['error' => 'Unauthorized']));
    }

    $query = trim($_GET['q'] ?? '');
    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

    if (strlen($query) < 2) {
        http_response_code(400);
        die(json_encode(['error' => 'Query must be at least 2 characters']));
    }

    $pdo = (new Database())->getConnection();

    $sql = "SELECT DISTINCT emp.employee_id, emp.first_name, emp.last_name, emp.email
            FROM em_employees emp
            WHERE (emp.first_name LIKE :q1 OR emp.last_name LIKE :q2 OR emp.email LIKE :q3 OR CONCAT(emp.first_name, ' ', emp.last_name) LIKE :q4)
            AND emp.employee_id != :iid";
    $params = [
        ':q1' => "%$query%",
        ':q2' => "%$query%",
        ':q3' => "%$query%",
        ':q4' => "%$query%",
        ':iid' => $instructorId,
    ];

    // If course_id specified, exclude already enrolled learners
    if ($courseId > 0) {
        $sql .= " AND emp.employee_id NOT IN (SELECT learner_id FROM ld_enrollment WHERE course_id = :cid AND status NOT IN ('withdrawn','archived'))";
        $params[':cid'] = $courseId;
    }

    $sql .= " ORDER BY emp.first_name ASC, emp.last_name ASC LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['employees' => $employees]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
