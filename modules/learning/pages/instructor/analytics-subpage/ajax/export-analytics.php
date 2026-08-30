<?php
session_start();
require_once dirname(__FILE__, 5) . "/classes/employee.php";
require_once dirname(__FILE__, 7) . "/database/db.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $emp = new Employee();
    $instructorId = $emp->getSessionEmployeeId();
    if (!$instructorId) { http_response_code(401); exit; }
    $type = $_GET["type"] ?? "enrollment";
    header("Content-Type: text/csv; charset=utf-8");
    $filename = "analytics_{$type}_" . date("Y-m-d") . ".csv";
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $output = fopen("php://output", "w");
    if ($type === "enrollment") {
        fputcsv($output, ["Course", "Learner", "Status", "Enrolled At", "Completed At"]);
        $stmt = $pdo->prepare("SELECT c.title, CONCAT(e2.first_name, ' ', e2.last_name) AS learner_name, en.status, en.enrolled_at, en.completed_at FROM ld_enrollment en JOIN ld_course c ON c.id = en.course_id JOIN em_employees e2 ON e2.employee_id = en.learner_id WHERE c.instructor_id = :iid ORDER BY en.enrolled_at DESC");
        $stmt->execute([":iid" => $instructorId]);
        while ($row = $stmt->fetch()) { fputcsv($output, $row); }
    } elseif ($type === "quiz") {
        fputcsv($output, ["Quiz", "Course", "Learner", "Score", "Submitted At"]);
        $stmt = $pdo->prepare("SELECT q.title, c.title AS course_title, CONCAT(e2.first_name, ' ', e2.last_name) AS learner_name, qa.score, qa.submitted_at FROM ld_quiz_attempt qa JOIN ld_quiz q ON q.id = qa.quiz_id JOIN ld_module m ON m.id = q.module_id JOIN ld_course c ON c.id = m.course_id JOIN em_employees e2 ON e2.employee_id = qa.learner_id WHERE c.instructor_id = :iid ORDER BY qa.submitted_at DESC");
        $stmt->execute([":iid" => $instructorId]);
        while ($row = $stmt->fetch()) { fputcsv($output, $row); }
    }
    fclose($output);
    exit;
} catch (Exception $e) { http_response_code(500); echo "Export error: " . $e->getMessage(); }