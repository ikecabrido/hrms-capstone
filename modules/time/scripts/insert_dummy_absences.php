<?php
/**
 * Insert dummy absence records for testing:
 * - one employee will be NEAR TERMINATION (1 absent in lookback)
 * - another will be TERMINATION REQUIRED (absents >= threshold)
 *
 * Usage: run from CLI: php insert_dummy_absences.php
 * Optional args: --near_id=123 --term_id=456 --days_back=7
 */

require_once __DIR__ . '/../app/core/TimeDatabase.php';
require_once __DIR__ . '/../app/services/AttendanceTerminationService.php';

use App\Services\AttendanceTerminationService;

$opts = [];
foreach ($argv as $a) {
    if (strpos($a, '--') === 0) {
        $p = substr($a, 2);
        [$k, $v] = array_pad(explode('=', $p, 2), 2, null);
        $opts[$k] = $v;
    }
}

$db = \TimeDatabase::getInstance()->getConnection();
$svc = new AttendanceTerminationService();
$daysBack = isset($opts['days_back']) ? (int)$opts['days_back'] : 7;
$threshold = (int)$svc->getAbsenceTerminationThreshold();

echo "Using days_back={$daysBack}, policy threshold={$threshold}\n";

// Helper: find candidate employees with active shift
// If both IDs provided, use them directly; otherwise try to find candidates
if (isset($opts['near_id']) && isset($opts['term_id'])) {
    $nearId = (int)$opts['near_id'];
    $termId = (int)$opts['term_id'];
    echo "Using provided near_id={$nearId}, term_id={$termId}\n";
} else {
    $findStmt = $db->prepare("SELECT e.employee_id FROM em_employees e
    INNER JOIN ta_employee_shifts es ON e.employee_id = es.employee_id
    WHERE LOWER(e.employment_status) = 'active' AND es.is_active = 1
      AND es.effective_from <= CURDATE()
      AND (es.effective_to IS NULL OR es.effective_to >= CURDATE())
    GROUP BY e.employee_id LIMIT 10");
    $findStmt->execute();
    $candidates = $findStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($candidates)) {
        echo "No candidate employees with active/current shifts found. Aborting.\n";
        exit(1);
    }

    echo "Found candidate employee_ids: " . implode(', ', $candidates) . "\n";

    $nearId = (int)($candidates[0] ?? 0);
    $termId = (int)($candidates[1] ?? 0);

    if (!$nearId || !$termId) {
        echo "Could not determine two distinct employee IDs automatically. Please re-run with --near_id and --term_id specifying two distinct employees from the list above. Aborting.\n";
        exit(1);
    }

    if ($nearId === $termId) {
        echo "Selected near_id and term_id are the same ({$nearId}). Please re-run with two distinct employee IDs. Aborting.\n";
        exit(1);
    }

    echo "Selected near_id={$nearId}, term_id={$termId}\n";
}

// Count existing absents in lookback
$countQ = $db->prepare("SELECT COUNT(attendance_id) AS cnt FROM ta_attendance WHERE employee_id = :eid AND status = 'ABSENT' AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL :days_back DAY)");
$countQ->bindParam(':days_back', $daysBack, PDO::PARAM_INT);

// Insert function
$ins = $db->prepare("INSERT INTO ta_attendance (employee_id, shift_id, attendance_date, recorded_by, status, created_at, updated_at) VALUES (:employee_id, NULL, :attendance_date, 'SYSTEM', 'ABSENT', NOW(), NOW())");

try {
    $db->beginTransaction();
    // Ensure both employees exist and have active/current shifts; create assignment if missing
    $checkEmp = $db->prepare("SELECT employee_id, employment_status FROM em_employees WHERE employee_id = :eid LIMIT 1");
    $getShift = $db->prepare("SELECT employee_shift_id FROM ta_employee_shifts WHERE employee_id = :eid AND is_active = 1 AND effective_from <= CURDATE() AND (effective_to IS NULL OR effective_to >= CURDATE()) LIMIT 1");
    $getAnyShift = $db->prepare("SELECT shift_id FROM ta_shifts LIMIT 1");
    $createShift = $db->prepare("INSERT INTO ta_shifts (shift_name, start_time, end_time, created_at, updated_at) VALUES ('AutoShift', '09:00:00', '17:00:00', NOW(), NOW())");
    $insertEmpShift = $db->prepare("INSERT INTO ta_employee_shifts (employee_id, shift_id, effective_from, effective_to, is_active) VALUES (:employee_id, :shift_id, CURDATE(), NULL, 1)");

    foreach ([$nearId, $termId] as $eid) {
        // check existence
        $checkEmp->bindParam(':eid', $eid, PDO::PARAM_INT);
        $checkEmp->execute();
        $emp = $checkEmp->fetch(PDO::FETCH_ASSOC);
        if (!$emp) {
            throw new Exception("Employee {$eid} does not exist in em_employees");
        }
        if (strtolower($emp['employment_status']) !== 'active') {
            throw new Exception("Employee {$eid} is not 'Active' (status={$emp['employment_status']}). Aborting.");
        }

        // check shift assignment
        $getShift->bindParam(':eid', $eid, PDO::PARAM_INT);
        $getShift->execute();
        $shiftRow = $getShift->fetch(PDO::FETCH_ASSOC);
        if (!$shiftRow) {
            // pick any existing shift
            $getAnyShift->execute();
            $s = $getAnyShift->fetch(PDO::FETCH_ASSOC);
            if (!$s) {
                // create a minimal shift
                $createShift->execute();
                $shiftId = $db->lastInsertId();
            } else {
                $shiftId = $s['shift_id'];
            }

            // assign shift to employee
            $insertEmpShift->bindParam(':employee_id', $eid, PDO::PARAM_INT);
            $insertEmpShift->bindParam(':shift_id', $shiftId, PDO::PARAM_INT);
            $insertEmpShift->execute();
            echo "Created shift assignment for employee {$eid} (shift_id={$shiftId})\n";
        }
    }

    // Ensure near employee has exactly 1 absent in lookback (or add one if 0)
    $countQ->bindParam(':eid', $nearId, PDO::PARAM_INT);
    $countQ->execute();
    $nearCnt = (int)$countQ->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "Near employee current absents in lookback: {$nearCnt}\n";
    if ($nearCnt < 1) {
        // insert one absence dated today
        $date = date('Y-m-d');
        $ins->bindParam(':employee_id', $nearId, PDO::PARAM_INT);
        $ins->bindParam(':attendance_date', $date);
        $ins->execute();
        echo "Inserted 1 ABSENT for employee {$nearId} on {$date}\n";
    } else {
        echo "No insertion for near employee (already has >=1 absents).\n";
    }

    // Ensure term employee reaches threshold
    $countQ->bindParam(':eid', $termId, PDO::PARAM_INT);
    $countQ->execute();
    $termCnt = (int)$countQ->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "Term employee current absents in lookback: {$termCnt}\n";
    $needed = $threshold - $termCnt;
    if ($needed <= 0) {
        echo "Term employee already meets/exceeds threshold ({$threshold}). No insertion needed.\n";
    } else {
        // Insert `needed` absence rows on distinct recent dates within lookback
        $today = new DateTime();
        for ($i = 0; $i < $needed; $i++) {
            // choose dates: today - $i days (but ensure within lookback)
            $d = clone $today;
            $offset = $i; // 0..needed-1
            $d->modify("-{$offset} days");
            $adate = $d->format('Y-m-d');
            $ins->bindParam(':employee_id', $termId, PDO::PARAM_INT);
            $ins->bindParam(':attendance_date', $adate);
            $ins->execute();
            echo "Inserted ABSENT for term employee {$termId} on {$adate}\n";
        }
    }

    $db->commit();
    echo "Insertions complete.\n";

    // Run diagnostic script to show results
    echo "\nRunning diagnostic to show classifications:\n";
    passthru("\"" . PHP_BINARY . "\" \"" . __DIR__ . "/diagnose_termination_service.php\"");

} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>
