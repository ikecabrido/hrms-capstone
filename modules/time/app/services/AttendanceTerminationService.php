<?php
/**
 * Attendance Termination Service
 * Reuses the existing 'near termination' rule implemented in EmployeeShift::getEmployeesNearTermination.
 * Provides helpers to check if an employee requires termination action and to list flagged employees.
 */

namespace App\Services;

require_once __DIR__ . '/../core/TimeDatabase.php';
require_once __DIR__ . '/../models/EmployeeShift.php';

class AttendanceTerminationService
{
    private $conn;
    private $attendance_table = 'ta_attendance';
    private $employees_table = 'em_employees';
    private $employeeShiftModel;
    private $policies_table = 'ta_absence_late_policies';

    public function __construct()
    {
        $db = \TimeDatabase::getInstance();
        $this->conn = $db->getConnection();
        $this->employeeShiftModel = new \EmployeeShift($this->conn);
    }

    /**
     * Read active absence/late policy and return warning_after_absent_count
     * Falls back to 1 if not found (defensive but not a hardcoded termination rule).
     * @return int
     */
    public function getAbsenceTerminationThreshold()
    {
      $query = "SELECT warning_after_absent_count FROM {$this->policies_table} WHERE is_active = 1 LIMIT 1";
      $stmt = $this->conn->prepare($query);
      if ($stmt->execute()) {
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && isset($row['warning_after_absent_count'])) {
          return (int)$row['warning_after_absent_count'];
        }
      }

      // If no policy found, return 1 as a safe fallback (still not hardcoded as business change)
      return 1;
    }

    /**
     * Check whether a specific employee meets the existing "near termination" rule.
     * Reuses the same criteria as EmployeeShift::getEmployeesNearTermination (missed ABSENT days).
     *
     * @param int $employeeId
     * @param int $daysBack
     * @return array|null  Returns structured info when threshold met, otherwise null.
     */
    public function isTerminationActionRequired($employeeId, $daysBack = 7)
    {
        $query = "SELECT COUNT(attendance_id) AS missed_shift_days, MAX(attendance_date) AS last_missed_shift
                  FROM {$this->attendance_table}
                  WHERE employee_id = :employee_id
                    AND status = 'ABSENT'
                    AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL :days_back DAY)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employeeId, \PDO::PARAM_INT);
        $stmt->bindParam(':days_back', $daysBack, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $missed = (int)($row['missed_shift_days'] ?? 0);

        // read threshold from policy
        $threshold = $this->getAbsenceTerminationThreshold();
        if ($missed < $threshold) {
          return null;
        }

        // gather employee info
        $empQ = "SELECT e.employee_id, CONCAT(COALESCE(e.first_name,''),' ',COALESCE(e.last_name,'')) AS full_name, COALESCE(d.department_name,'') AS department
                  FROM {$this->employees_table} e
                  LEFT JOIN em_departments d ON e.department_id = d.department_id
                  WHERE e.employee_id = :employee_id LIMIT 1";

        $es = $this->conn->prepare($empQ);
        $es->bindParam(':employee_id', $employeeId, \PDO::PARAM_INT);
        $es->execute();
        $emp = $es->fetch(\PDO::FETCH_ASSOC) ?: ['employee_id' => $employeeId, 'full_name' => '', 'department' => ''];

        // list recent absence details
        $listQ = "SELECT attendance_id, attendance_date, status
                  FROM {$this->attendance_table}
                  WHERE employee_id = :employee_id
                    AND status = 'ABSENT'
                    AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL :days_back DAY)
                  ORDER BY attendance_date DESC";

        $ls = $this->conn->prepare($listQ);
        $ls->bindParam(':employee_id', $employeeId, \PDO::PARAM_INT);
        $ls->bindParam(':days_back', $daysBack, \PDO::PARAM_INT);
        $ls->execute();
        $violations = $ls->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'employee' => $emp,
            'missed_shift_days' => $missed,
          'threshold' => $threshold,
            'last_missed_shift' => $row['last_missed_shift'] ?? null,
            'violations' => $violations,
          'reason' => 'termination_threshold_reached'
        ];
    }

    /**
     * Return list of employees meeting the existing near-termination criteria.
     * This mirrors EmployeeShift::getEmployeesNearTermination but returns the raw rows.
     *
     * @param int $daysBack
     * @param int $limit
     * @return array
     */
    public function getEmployeesRequiringTerminationAction($daysBack = 7, $limit = 100)
    {
      // Read threshold from policy and use it in HAVING clause
      $threshold = $this->getAbsenceTerminationThreshold();

      // We reuse the same query pattern used by EmployeeShift::getEmployeesNearTermination
      $query = "SELECT
                    e.employee_id,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                    COALESCE(d.department_name, '') AS department,
                    COALESCE(p.position_name, '') AS position,
                    COUNT(a.attendance_id) AS missed_shift_days,
                    MAX(a.attendance_date) AS last_missed_shift
                  FROM {$this->employees_table} e
                  INNER JOIN ta_employee_shifts es ON e.employee_id = es.employee_id
                    AND es.is_active = 1
                    AND es.effective_from <= CURDATE()
                    AND (es.effective_to IS NULL OR es.effective_to >= CURDATE())
                  LEFT JOIN em_departments d ON e.department_id = d.department_id
                  LEFT JOIN em_positions p ON e.position_id = p.position_id
                  LEFT JOIN {$this->attendance_table} a ON a.employee_id = e.employee_id
                    AND a.status = 'ABSENT'
                    AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL :days_back DAY)
                  WHERE LOWER(e.employment_status) = 'active'
                  GROUP BY e.employee_id, e.first_name, e.last_name, d.department_name, p.position_name
                  HAVING COUNT(a.attendance_id) >= :threshold
                  ORDER BY missed_shift_days DESC, last_missed_shift DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days_back', $daysBack, \PDO::PARAM_INT);
        $stmt->bindParam(':threshold', $threshold, \PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

?>
