<?php
require_once __DIR__ . '/../../../database/db.php';
$dbClass = new Database();
$db = $dbClass->getConnection();

// Create a simple employee if not exists
$employeeCode = 'TESTTERM01';
$stmt = $db->prepare('SELECT employee_id FROM em_employees WHERE employee_code = ?');
$stmt->execute([$employeeCode]);
$exists = $stmt->fetch(PDO::FETCH_ASSOC);
if ($exists) {
    echo "Employee already exists: " . $exists['employee_id'] . "\n";
    exit;
}

$now = date('Y-m-d H:i:s');
$stmt = $db->prepare("INSERT INTO em_employees (employee_code, first_name, last_name, email, employment_status, created_at, updated_at) VALUES (?, ?, ?, ?, 'active', ?, ?)");
$stmt->execute([$employeeCode, 'Test', 'Term', 'test.term@example.com', $now, $now]);
$employeeId = $db->lastInsertId();

// If lastInsertId returns 0 or empty, attempt to fetch by code
if (empty($employeeId)) {
    $stmt = $db->prepare('SELECT employee_id FROM em_employees WHERE employee_code = ?');
    $stmt->execute([$employeeCode]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $employeeId = $row['employee_id'] ?? null;
}

if ($employeeId) {
    echo "Inserted test employee: id=$employeeId code=$employeeCode\n";
} else {
    echo "Failed to insert employee\n";
}

// Create a termination alert record in exit_terminations if table exists
$stmt = $db->prepare("SHOW TABLES LIKE 'exit_terminations'");
$stmt->execute();
if ($stmt->fetch()) {
    // Insert a termination record flagged as pending_review or pending
    $effectiveDate = date('Y-m-d', strtotime('+7 days'));
    $ins = $db->prepare("INSERT INTO exit_terminations (employee_id, termination_reason, effective_date, submitted_by, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'pending_review', NOW(), NOW())");
    $ins->execute([$employeeId, 'Performance - auto test', $effectiveDate, 1]);
    echo "Inserted termination record for employee_id=$employeeId\n";
} else {
    echo "exit_terminations table not found; skipping termination insert\n";
}

// Output current eligible employees via the same query used by the UI model
$stmt = $db->query("SELECT e.employee_id AS id, CONCAT(e.first_name, ' ', e.last_name) AS full_name, e.employee_code AS username FROM em_employees e WHERE LOWER(TRIM(e.employment_status)) = 'active' ORDER BY e.first_name, e.last_name ASC LIMIT 20");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
echo "Active employees returned: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo " - {$r['id']}: {$r['full_name']} ({$r['username']})\n";
}
