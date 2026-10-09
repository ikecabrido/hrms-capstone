<?php
session_start();

include_once dirname(__DIR__, 3) . '/classes/employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $employeeClass = new Employee();
    $instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

    if ($instructorId <= 0) {
        http_response_code(401);
        die('Unauthorized');
    }

    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

    $pdo = (new Database())->getConnection();

    $sql = "SELECT emp.first_name, emp.last_name, emp.email, 
                   c.title AS course_title,
                   e.status, e.enrolled_at, e.completed_at, e.last_accessed_at
            FROM ld_enrollment e
            JOIN ld_course c ON c.id = e.course_id
            JOIN em_employees emp ON emp.employee_id = e.learner_id
            WHERE c.instructor_id = :iid";
    $params = [':iid' => $instructorId];

    if ($courseId > 0) {
        $sql .= " AND c.id = :cid";
        $params[':cid'] = $courseId;
    }

    $sql .= " ORDER BY emp.last_name ASC, emp.first_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output as CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="enrollment-export-' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    // BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header row
    fputcsv($output, ['First Name', 'Last Name', 'Email', 'Course', 'Status', 'Enrolled At', 'Completed At', 'Last Accessed']);

    foreach ($rows as $row) {
        fputcsv($output, [
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['course_title'],
            $row['status'],
            $row['enrolled_at'],
            $row['completed_at'],
            $row['last_accessed_at'],
        ]);
    }

    fclose($output);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    die('Export failed: ' . $e->getMessage());
}
