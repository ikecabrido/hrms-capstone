<?php
header('Content-Type: application/json');
session_start();

include_once dirname(__DIR__, 3) . '/classes/employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $employeeClass = new Employee();
    $instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
    $format = $_GET['format'] ?? 'csv';

    $pdo = (new Database())->getConnection();

    $sql = "SELECT 
                emp.first_name, emp.last_name, emp.email,
                c.title AS course_title,
                e.status, e.enrolled_at, e.completed_at,
                COALESCE(g.final_score, 0) AS final_score,
                g.status AS grade_status
            FROM ld_enrollment e
            JOIN ld_course c ON c.id = e.course_id
            JOIN em_employees emp ON emp.employee_id = e.learner_id
            LEFT JOIN ld_grade g ON g.learner_id = e.learner_id AND g.course_id = e.course_id";
    $params = [];

    if ($courseId > 0) {
        $sql .= " WHERE e.course_id = :cid";
        $params[':cid'] = $courseId;
    }

    $sql .= " ORDER BY emp.last_name, emp.first_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="progress-report-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF'));
        fputcsv($output, ['First Name', 'Last Name', 'Email', 'Course', 'Status', 'Score', 'Enrolled', 'Completed']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['first_name'],
                $row['last_name'],
                $row['email'],
                $row['course_title'],
                $row['status'],
                $row['final_score'] . '%',
                $row['enrolled_at'],
                $row['completed_at'],
            ]);
        }

        fclose($output);
        exit;
    }

    // JSON fallback
    http_response_code(200);
    echo json_encode(['success' => true, 'rows' => $rows, 'total' => count($rows)]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
