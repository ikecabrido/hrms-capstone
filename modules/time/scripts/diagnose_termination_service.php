<?php
require_once __DIR__ . '/../app/core/TimeDatabase.php';
require_once __DIR__ . '/../app/models/EmployeeShift.php';
require_once __DIR__ . '/../app/services/AttendanceTerminationService.php';

use App\Services\AttendanceTerminationService;

$db = \TimeDatabase::getInstance()->getConnection();
$es = new EmployeeShift($db);
$svc = new AttendanceTerminationService();

$daysBack = 7;
$limit = 100;

echo "Running EmployeeShift::getEmployeesNearTermination({$daysBack}, {$limit})\n";
$orig = $es->getEmployeesNearTermination($daysBack, $limit);
echo "Found: " . count($orig) . " rows\n";
echo json_encode(array_slice($orig,0,10), JSON_PRETTY_PRINT) . "\n\n";

echo "Running AttendanceTerminationService::getEmployeesRequiringTerminationAction({$daysBack}, {$limit})\n";
$new = $svc->getEmployeesRequiringTerminationAction($daysBack, $limit);
echo "Found: " . count($new) . " rows\n";
echo json_encode(array_slice($new,0,10), JSON_PRETTY_PRINT) . "\n\n";

// Also show threshold and demonstrate classification for a few sample employees
$threshold = method_exists($svc, 'getAbsenceTerminationThreshold') ? $svc->getAbsenceTerminationThreshold() : 1;
echo "Policy threshold (warning_after_absent_count) = {$threshold}\n\n";

// Build a combined view of NORMAL / NEAR / TERMINATION for employees seen in either list
$allIds = array_unique(array_merge(array_map(function($r){ return $r['employee_id']; }, $orig), array_map(function($r){ return $r['employee_id']; }, $new)));
echo "Sample classification (NORMAL / NEAR / TERMINATION):\n";
foreach ($allIds as $eid) {
	// count absents in daysBack
	$q = $db->prepare("SELECT COUNT(attendance_id) AS cnt FROM ta_attendance WHERE employee_id = :eid AND status = 'ABSENT' AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL :days_back DAY)");
	$q->bindParam(':eid', $eid, PDO::PARAM_INT);
	$q->bindParam(':days_back', $daysBack, PDO::PARAM_INT);
	$q->execute();
	$r = $q->fetch(PDO::FETCH_ASSOC);
	$cnt = (int)($r['cnt'] ?? 0);
	$state = 'NORMAL';
	if ($cnt >= $threshold) $state = 'TERMINATION';
	else if ($cnt >= 1) $state = 'NEAR_TERMINATION';
	echo "Employee {$eid}: absents={$cnt} -> {$state}\n";
}


$origIds = array_map(function($r){ return $r['employee_id']; }, $orig);
$newIds = array_map(function($r){ return $r['employee_id']; }, $new);

$diff1 = array_diff($origIds, $newIds);
$diff2 = array_diff($newIds, $origIds);

echo "IDs in original but not in new: " . json_encode(array_values($diff1)) . "\n";
echo "IDs in new but not in original: " . json_encode(array_values($diff2)) . "\n";

echo "Diagnostic complete.\n";

?>
