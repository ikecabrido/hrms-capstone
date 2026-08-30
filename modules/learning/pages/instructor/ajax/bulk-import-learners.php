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

    $input = json_decode(file_get_contents('php://input'), true);
    $rawInput = $input['text'] ?? '';
    $courseId = (int) ($input['course_id'] ?? 0);

    if (empty($rawInput)) {
        http_response_code(400);
        die(json_encode(['error' => 'No input text provided']));
    }

    $pdo = (new Database())->getConnection();

    // Parse: could be emails, employee IDs, or "First Last" names
    // Split by newlines, commas, or semicolons
    $lines = preg_split('/[\n\r,;]+/', $rawInput);
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, function ($l) { return !empty($l); });

    $found = [];
    $notFound = [];

    foreach ($lines as $line) {
        // Try as email
        if (filter_var($line, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("SELECT employee_id, first_name, last_name, email FROM em_employees WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $line]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($emp) {
                $emp['matched_by'] = 'email';
                $emp['original'] = $line;
                $found[] = $emp;
                continue;
            }
        }

        // Try as employee ID
        if (is_numeric($line) && (int) $line > 0) {
            $stmt = $pdo->prepare("SELECT employee_id, first_name, last_name, email FROM em_employees WHERE employee_id = :eid LIMIT 1");
            $stmt->execute([':eid' => (int) $line]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($emp) {
                $emp['matched_by'] = 'employee_id';
                $emp['original'] = $line;
                $found[] = $emp;
                continue;
            }
        }

        // Try as name (first name or first + last)
        $stmt = $pdo->prepare("
            SELECT employee_id, first_name, last_name, email 
            FROM em_employees 
            WHERE CONCAT(first_name, ' ', last_name) LIKE :name 
               OR first_name LIKE :name2
            ORDER BY first_name ASC 
            LIMIT 5
        ");
        $stmt->execute([':name' => "%$line%", ':name2' => "%$line%"]);
        $emps = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($emps) === 1) {
            $emps[0]['matched_by'] = 'name';
            $emps[0]['original'] = $line;
            $found[] = $emps[0];
        } elseif (count($emps) > 1) {
            // Multiple matches — include all but flag
            foreach ($emps as $emp) {
                $emp['matched_by'] = 'name_ambiguous';
                $emp['original'] = $line;
                $found[] = $emp;
            }
        } else {
            $notFound[] = $line;
        }
    }

    // Deduplicate found by employee_id
    $unique = [];
    foreach ($found as $f) {
        $unique[$f['employee_id']] = $f;
    }
    $found = array_values($unique);

    http_response_code(200);
    echo json_encode([
        'found' => $found,
        'not_found' => $notFound,
        'total_parsed' => count($lines),
        'total_found' => count($found),
        'total_not_found' => count($notFound),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
