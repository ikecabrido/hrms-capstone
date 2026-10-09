<?php
$pdo = new PDO("mysql:host=localhost;dbname=hrms", "root", "");

echo "=== ALL ld_ tables and row counts ===\n";
$tables = $pdo->query("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema=DATABASE() AND TABLE_NAME RLIKE '^ld_' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $cols = $pdo->query("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t'")->fetchAll(PDO::FETCH_ASSOC);
    $colStr = implode(", ", array_map(fn($c) => "$c[COLUMN_NAME]$c[COLUMN_KEY]", $cols));
    echo "  $t ($count rows): $colStr\n";
}
echo "\n";

echo "=== Learning Path coverage ===\n";
$lps = $pdo->query("SELECT lp.id, lp.title, lp.type, lp.assigned_to, lp.instructor_id, lp.is_public, lp.kt_plan_id, COUNT(lpi.id) AS item_count FROM ld_learning_path lp LEFT JOIN ld_learning_path_item lpi ON lpi.learning_path_id=lp.id GROUP BY lp.id ORDER BY lp.id")->fetchAll(PDO::FETCH_ASSOC);
$assigneeStmt = $pdo->prepare("SELECT first_name, last_name, employee_code FROM em_employees WHERE employee_id = :eid");
$instructorStmt = $pdo->prepare("SELECT first_name, last_name FROM em_employees WHERE employee_id = :eid");

foreach ($lps as $lp) {
    $aid = (int)($lp['assigned_to'] ?? 0);
    $iid = (int)($lp['instructor_id'] ?? 0);
    $assignee = $aid > 0 ? ($assigneeStmt->execute([':eid' => $aid]), $assigneeStmt->fetch(PDO::FETCH_ASSOC)) : null;
    $instructor = $iid > 0 ? ($instructorStmt->execute([':eid' => $iid]), $instructorStmt->fetch(PDO::FETCH_ASSOC)) : null;
    echo "  lp.id={$lp['id']} \"{$lp['title']}\" type={$lp['type']} assigned_to={$lp['assigned_to']} (", ($assignee ? $assignee['first_name']." ".$assignee['last_name'] : 'NONE'), ") instructor={$lp['instructor_id']} (", ($instructor ? $instructor['first_name']." ".$instructor['last_name'] : 'NONE'), ") items={$lp['item_count']} public=".($lp['is_public']?'yes':'no')." kt_plan_id=".($lp['kt_plan_id']??'none')."\n";
}
echo "\n";

echo "=== ld_learning_path_item detail (all) ===\n";
$items = $pdo->query("SELECT lpi.id, lpi.learning_path_id, lpi.item_type, lpi.reference_id, lpi.order_index, lpi.status, lp.title AS path_title FROM ld_learning_path_item lpi JOIN ld_learning_path lp ON lp.id=lpi.learning_path_id ORDER BY lpi.learning_path_id, lpi.order_index")->fetchAll(PDO::FETCH_ASSOC);
if (empty($items)) echo "  NONE\n";
foreach ($items as $it) echo "  lp={$it['path_title']} (id={$it['learning_path_id']}) item_type={$it['item_type']} ref_id={$it['reference_id']} order={$it['order_index']} status={$it['status']}\n";
echo "\n";

echo "=== Enrollment coverage by learner ===\n";
$enr = $pdo->query("SELECT e.learner_id, emp.first_name, emp.last_name, emp.employee_code, COUNT(e.id) AS enrollment_count, SUM(CASE WHEN e.status='completed' THEN 1 ELSE 0 END) AS completed_count FROM ld_enrollment e JOIN em_employees emp ON emp.employee_id=e.learner_id GROUP BY e.learner_id ORDER BY e.learner_id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($enr as $e) echo "  learner_id={$e['learner_id']} {$e['first_name']} {$e['last_name']} ({$e['employee_code']}) enrollments={$e['enrollment_count']} completed={$e['completed_count']}\n";
echo "\n";

echo "=== Course coverage ===\n";
$courses = $pdo->query("SELECT c.id, c.title, c.instructor_id, c.status, COUNT(DISTINCT e.learner_id) AS enrolled_count FROM ld_course c LEFT JOIN ld_enrollment e ON e.course_id=c.id WHERE c.status!='archived' GROUP BY c.id ORDER BY c.id")->fetchAll(PDO::FETCH_ASSOC);
$instructorStmt2 = $pdo->prepare("SELECT first_name, last_name FROM em_employees WHERE employee_id = :eid");

foreach ($courses as $c) {
    $inst = $instructorStmt2->execute([':eid' => (int)($c['instructor_id'] ?? 0)]), $instructorStmt2->fetch(PDO::FETCH_ASSOC);
    echo "  c.id={$c['id']} \"{$c['title']}\" instructor={$c['instructor_id']} (", ($inst? $inst['first_name']." ".$inst['last_name'] : 'NONE'), ") status={$c['status']} enrolled={$c['enrolled_count']}\n";
}
echo "\n";

echo "=== Quiz attempt coverage ===\n";
$qa = $pdo->query("SELECT qa.learner_id, emp.first_name, emp.last_name, COUNT(qa.id) AS attempt_count, SUM(qa.passed) AS passed_count, ROUND(AVG(qa.score),1) AS avg_score FROM ld_quiz_attempt qa JOIN em_employees emp ON emp.employee_id=qa.learner_id GROUP BY qa.learner_id ORDER BY qa.learner_id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($qa as $q) echo "  learner={$q['learner_id']} {$q['first_name']} {$q['last_name']} attempts={$q['attempt_count']} passed={$q['passed_count']} avg_score={$q['avg_score']}%\n";
echo "\n";

echo "=== Certificate coverage ===\n";
$certs = $pdo->query("SELECT cert.learner_id, emp.first_name, emp.last_name, cert.course_id, c.title AS course_title, cert.status, cert.issued_at FROM ld_certificate cert JOIN em_employees emp ON emp.employee_id=cert.learner_id JOIN ld_course c ON c.id=cert.course_id ORDER BY cert.issued_at DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($certs as $c) echo "  learner={$c['learner_id']} {$c['first_name']} {$c['last_name']} course={$c['course_title']} (id={$c['course_id']}) status={$c['status']} issued={$c['issued_at']}\n";
echo "\n";

echo "=== Recommendation coverage ===\n";
$recs = $pdo->query("SELECT * FROM ld_training_recommendation ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
if (empty($recs)) echo "  NONE\n";
foreach ($recs as $r) echo "  id={$r['id']} employee_id={$r['employee_id']} course_id={$r['course_id']} area={$r['development_area']} priority={$r['priority_level']} status={$r['status']}\n";
echo "\n";

echo "=== API key coverage ===\n";
$keys = $pdo->query("SELECT module_name, is_active FROM ld_api_key ORDER BY module_name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($keys as $k) echo "  {$k['module_name']}: active=".($k['is_active']?'yes':'no')."\n";
echo "\n";

echo "=== User account role coverage ===\n";
$users = $pdo->query("SELECT u.user_id, u.employee_id, e.first_name, e.last_name, e.employee_code, r.role_name, u.account_status FROM user_account u JOIN em_employees e ON e.employee_id=u.employee_id JOIN em_roles r ON r.role_id=u.role_id ORDER BY u.employee_id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) echo "  user_id={$u['user_id']} emp_id={$u['employee_id']} {$u['first_name']} {$u['last_name']} ({$u['employee_code']}) role={$u['role_name']} status={$u['account_status']}\n";
echo "\n";

echo "=== Employee coverage (all in em_employees) ===\n";
$emps = $pdo->query("SELECT employee_id, first_name, last_name, employee_code, position_id, department_id, employment_status FROM em_employees ORDER BY employee_id")->fetchAll(PDO::FETCH_ASSOC);
$posStmt = $pdo->prepare("SELECT position_name FROM em_positions WHERE position_id = :pid");
$deptStmt = $pdo->prepare("SELECT department_name FROM em_departments WHERE department_id = :did");

foreach ($emps as $e) {
    $pos = $posStmt->execute([':pid' => (int)($e['position_id'] ?? 0)]), $posStmt->fetch(PDO::FETCH_ASSOC);
    $dept = $deptStmt->execute([':did' => (int)($e['department_id'] ?? 0)]), $deptStmt->fetch(PDO::FETCH_ASSOC);
    echo "  emp_id={$e['employee_id']} {$e['first_name']} {$e['last_name']} ({$e['employee_code']}) pos=", ($pos?$pos['position_name']:'NONE'), " dept=", ($dept?$dept['department_name']:'NONE'), " status={$e['employment_status']}\n";
}
echo "\n";

echo "=== Cross-module integration log ===\n";
$logs = $pdo->query("SELECT id, direction, module_name, endpoint, status, created_at FROM ld_integration_log ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($logs as $l) echo "  id={$l['id']} {$l['direction']} {$l['module_name']} {$l['endpoint']} status={$l['status']} at={$l['created_at']}\n";
echo "\n";

echo "=== Files in modules/learning/pages/ by role ===\n";
$dirs = ['admin', 'instructor', 'learner'];
foreach ($dirs as $role) {
    $files = glob("modules/learning/pages/$role/**/*.php", GLOB_BRACE);
    echo "  $role/ (" . count($files) . " php files):\n";
    foreach ($files as $f) echo "    " . str_replace("modules/learning/pages/$role/", "", $f) . "\n";
    $ajax = glob("modules/learning/pages/$role/**/ajax/*.php", GLOB_BRACE);
    echo "    ajax/ (" . count($ajax) . "):\n";
    foreach ($ajax as $a) echo "      " . str_replace("modules/learning/pages/$role/", "", $a) . "\n";
}
echo "\n";

echo "=== API files ===\n";
$inbound = glob("modules/learning/api/inbound/*.php");
echo "  inbound/ (" . count($inbound) . "):\n";
foreach ($inbound as $f) echo "    " . basename($f) . "\n";
$outbound = glob("modules/learning/api/outbound/*.php");
echo "  outbound/ (" . count($outbound) . "):\n";
foreach ($outbound as $f) echo "    " . basename($f) . "\n";

echo "\n=== Done ===\n";
