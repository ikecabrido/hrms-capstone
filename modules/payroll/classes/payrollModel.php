<?php
class PayrollModel
{
    private PDO $db;
    private ?PDO $smsDb;
    private const NON_TEACHING_DAYS_PER_MONTH = 26;
    private const FULL_TIME_REGULAR_LOAD = 30;
    private const FULL_TIME_MAX_LOAD = 45;
    private const PART_TIME_MAX_LOAD = 15;

    /**
     * Auto-classifies em_positions.position_name into 'teaching' vs
     * 'non-teaching' by keyword, so payroll can pick the correct
     * pr_position_deduction_rates row (position_type 'Teacher' vs
     * 'Other') without a category column on em_positions, and
     * without needing a code update every time a new position is
     * added.
     *
     * Rule: if the position name contains any of these keywords
     * (case-insensitive), it's 'teaching'. Every current teaching
     * position name (Instructor, Professor, etc.) matches this.
     */
    private const TEACHING_KEYWORDS = [
        'instructor',
        'professor',
    ];

    /**
     * Exact-name overrides for positions the keyword rule would
     * misclassify — e.g. a support role whose title happens to
     * contain "Instructor" but isn't actually a teaching load, or
     * a teaching role whose title doesn't contain a teaching
     * keyword. Add entries here as those cases come up; checked
     * before the keyword rule. Empty for now — none of the current
     * positions need an override.
     *
     * Example: 'Laboratory Instructor' => 'non-teaching',
     */
    private const POSITION_OVERRIDES = [];
    /**
     * $db    = HRIS/payroll database
     * $smsDb = SMS database containing faculty schedules/subjects
     */
    public function __construct(PDO $db, ?PDO $smsDb = null)
    {
        $this->db = $db;
        $this->smsDb = $smsDb;
    }
    public function getPayrollPeriods(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM pr_periods
            ORDER BY start_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getPayrollPeriod(int $periodId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM pr_periods
            WHERE period_id = :period_id
            LIMIT 1
        ");
        $stmt->execute([
            ':period_id' => $periodId
        ]);
        $period = $stmt->fetch(PDO::FETCH_ASSOC);
        return $period ?: null;
    }
    public function isPeriodClosed(int $periodId): bool
    {
        $period = $this->getPayrollPeriod($periodId);

        return $period && $period['status'] === 'closed';
    }
    public function getAllActiveEmployeesForPeriod(int $periodId): array
    {
        $stmt = $this->db->query("
            SELECT
                e.employee_id,
                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,
                e.email,
                e.position_id,
                p.position_name,
                e.employment_status,
                e.employment_type,
                e.unit_load,
                e.graduate_level,
                e.negotiated_salary
            FROM em_employees e
            LEFT JOIN em_positions p
                ON p.position_id = e.position_id
            WHERE e.employment_status = 'Active'
              AND e.is_archived = 0
            ORDER BY e.last_name, e.first_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getEmployee(int $employeeId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.*,
                p.position_name
            FROM em_employees e
            LEFT JOIN em_positions p
                ON p.position_id = e.position_id
            WHERE e.employee_id = :employee_id
            LIMIT 1
        ");
        $stmt->execute([
            ':employee_id' => $employeeId
        ]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        return $employee ?: null;
    }
    /**
     * Classify a position name as 'teaching' or 'non-teaching'.
     *
     * 1. Check POSITION_OVERRIDES for an exact-name override.
     * 2. Otherwise, 'teaching' if the name contains any
     *    TEACHING_KEYWORDS (case-insensitive), else 'non-teaching'.
     *
     * Returns 'unknown' only when there's no position name at all
     * (e.g. employee has no position_id set), in which case the
     * caller falls back to the employee's faculty-schedule status.
     */
    private function classifyPosition(?string $positionName): string
    {
        if ($positionName === null || trim($positionName) === '') {
            return 'unknown';
        }
        $normalized = strtolower(trim($positionName));

        foreach (self::POSITION_OVERRIDES as $name => $group) {
            if (strtolower($name) === $normalized) {
                return $group;
            }
        }
        foreach (self::TEACHING_KEYWORDS as $keyword) {
            if (strpos($normalized, $keyword) !== false) {
                return 'teaching';
            }
        }
        return 'non-teaching';
    }
    public function getTimeAttendanceMetrics(
        int $employeeId,
        string $startDate,
        string $endDate
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(
                    CASE
                        WHEN status IN ('PRESENT', 'LATE', 'EARLY_OUT')
                        THEN 1
                    END
                ) AS present_days,
                COUNT(
                    CASE
                        WHEN status = 'ABSENT'
                        THEN 1
                    END
                ) AS absent_days,
                COUNT(
                    CASE
                        WHEN status = 'LATE'
                        THEN 1
                    END
                ) AS late_days,
                COALESCE(SUM(total_hours_worked), 0) AS total_hours_worked,
                COALESCE(SUM(late_minutes), 0) AS total_late_minutes,
                COALESCE(SUM(early_out_minutes), 0) AS total_early_out_minutes
            FROM ta_attendance
            WHERE employee_id = :employee_id
              AND attendance_date BETWEEN :start_date AND :end_date
              AND is_approved = 1
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'present_days' => 0,
            'absent_days' => 0,
            'late_days' => 0,
            'total_hours_worked' => 0,
            'total_late_minutes' => 0,
            'total_early_out_minutes' => 0
        ];
    }
    public function getAttendanceRecords(
        int $employeeId,
        string $startDate,
        string $endDate
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                attendance_id,
                attendance_date,
                time_in,
                time_out,
                status,
                total_hours_worked,
                regular_hours,
                late_minutes,
                early_out_minutes,
                is_approved
            FROM ta_attendance
            WHERE employee_id = :employee_id
              AND attendance_date BETWEEN :start_date AND :end_date
            ORDER BY attendance_date ASC
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /* ============================================================
       LEAVE LOOKUP
       ============================================================
       Note: this model no longer uses a flat per-absence/per-leave
       peso deduction. Absence and leave are now handled by simply
       not generating earnings for unworked, non-leave, non-holiday
       days (see calculateFacultyDailyPay() and
       calculateRegularPayroll()). getApprovedLeaves() is kept
       because getApprovedPaidLeaveDates() still depends on it.
       ============================================================ */
    private function getApprovedLeaves(
        int $employeeId,
        string $startDate,
        string $endDate
    ): array {
        $stmt = $this->db->prepare("
        SELECT
            lr.id AS leave_request_id,
            lr.employee_id,
            lr.leave_type_id,
            lr.start_date,
            lr.end_date,
            lr.status,
            lr.details,
            lr.reason,
            lt.leave_type_name,
            lt.days_per_year,
            lt.is_deductible
        FROM ta_leave_requests lr
        INNER JOIN ta_leave_types lt
            ON lt.leave_type_id = lr.leave_type_id
        WHERE lr.employee_id = :employee_id
          AND lr.status = 'Approved'
          AND lr.start_date <= :end_date
          AND lr.end_date >= :start_date
        ORDER BY lr.start_date ASC
    ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /* ============================================================
       FACULTY SCHEDULE / UNITS
       ============================================================ */

    /**
     * Find the SMS faculty record corresponding to the HR employee.
     *
     * Current bridge:
     * cc_faculty.employee_id = em_employees.employee_id
     */
    private function getSmsFaculty(int $employeeId): ?array
    {
        if (!$this->smsDb) {
            return null;
        }

        // The current SMS schema contains a direct employee_id bridge.
        $stmt = $this->smsDb->prepare("
            SELECT
                id,
                employee_id,
                faculty_code,
                first_name,
                last_name,
                email
            FROM cc_faculty
            WHERE employee_id = :employee_id
            LIMIT 1
        ");
        $stmt->execute([
            ':employee_id' => $employeeId
        ]);

        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
        return $faculty ?: null;
    }
    public function getFacultySchedule(
        int $employeeId,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {
        if (!$this->smsDb) {
            return [];
        }
        $faculty = $this->getSmsFaculty($employeeId);
        if (!$faculty) {
            return [];
        }
        $sql = "
            SELECT
                s.id AS schedule_id,
                s.faculty_load_id,
                s.day_of_week,
                s.start_time,
                s.end_time,
                s.subject_id,
                s.section_id,
                s.school_year_id,
                s.semester_id,
                sub.code AS subject_code,
                sub.name AS subject_name,
                sub.units
            FROM cc_schedule s
            LEFT JOIN rgr_subjects sub
                ON sub.id = s.subject_id
            WHERE s.faculty_id = :faculty_id
              AND s.schedule_type = 'Class'
              AND s.status <> 'Cancelled'
        ";
        $params = [
            ':faculty_id' => $faculty['id']
        ];

        if ($schoolYearId !== null) {
            $sql .= " AND s.school_year_id = :school_year_id";
            $params[':school_year_id'] = $schoolYearId;
        }

        if ($semesterId !== null) {
            $sql .= " AND s.semester_id = :semester_id";
            $params[':semester_id'] = $semesterId;
        }
        $sql .= "
            ORDER BY
                FIELD(
                    s.day_of_week,
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday',
                    'Saturday'
                ),
                s.start_time
        ";
        $stmt = $this->smsDb->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Get all scheduled classes for a particular calendar date.
     */
    public function getFacultyClassesForDate(
        int $employeeId,
        string $date,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {
        $dayOfWeek = date('l', strtotime($date));
        $schedule = $this->getFacultySchedule(
            $employeeId,
            $schoolYearId,
            $semesterId
        );
        return array_values(
            array_filter(
                $schedule,
                fn($row) => $row['day_of_week'] === $dayOfWeek
            )
        );
    }
    /**
     * Calculates:
     *
     * daily units = sum(subject units)
     *
     * daily faculty pay =
     * daily units × qualification rate
     */
    /**
     * Return the faculty load classification for the current teaching
     * assignment. The College Coordinator rule is:
     *
     * Full-time: first 30 units are regular; assignments after 30 units
     * are overload, up to a maximum total load of 45 units.
     * Part-time: maximum 15 regular units and no overload.
     *
     * A whole subject assignment is classified as regular or overload;
     * a subject is never split between the two categories.
     */
    private function getFacultyLoadClassification(
        int $employeeId,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {
        if (!$this->smsDb) {
            return [];
        }

        $faculty = $this->getSmsFaculty($employeeId);
        if (!$faculty) {
            return [];
        }

        $employee = $this->getEmployee($employeeId);
        if (!$employee) {
            return [];
        }

        $sql = "
            SELECT
                fl.id AS faculty_load_id,
                fl.section_id,
                fl.subject_id,
                fl.school_year_id,
                fl.semester_id,
                sub.code AS subject_code,
                sub.name AS subject_name,
                sub.units
            FROM cc_faculty_load fl
            LEFT JOIN rgr_subjects sub
                ON sub.id = fl.subject_id
            WHERE fl.faculty_id = :faculty_id
        ";
        $params = [':faculty_id' => (int)$faculty['id']];

        if ($schoolYearId !== null) {
            $sql .= " AND fl.school_year_id = :school_year_id";
            $params[':school_year_id'] = $schoolYearId;
        }
        if ($semesterId !== null) {
            $sql .= " AND fl.semester_id = :semester_id";
            $params[':semester_id'] = $semesterId;
        }

        $sql .= " ORDER BY fl.id ASC";
        $stmt = $this->smsDb->prepare($sql);
        $stmt->execute($params);
        $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $classification = [];
        $regularLimit = $employee['employment_type'] === 'Part-time'
            ? self::PART_TIME_MAX_LOAD
            : self::FULL_TIME_REGULAR_LOAD;
        $maxLoad = $employee['employment_type'] === 'Part-time'
            ? self::PART_TIME_MAX_LOAD
            : self::FULL_TIME_MAX_LOAD;

        $cumulativeUnits = 0.0;

        foreach ($loads as $load) {
            $units = (float)($load['units'] ?? 0);
            if ($units <= 0) {
                continue;
            }

            // The entire next subject becomes overload once the regular
            // threshold has been reached or would be crossed.
            $isOverload = false;
            if ($employee['employment_type'] === 'Full-time') {
                $isOverload = $cumulativeUnits >= $regularLimit;
            }

            // Never allow an assignment beyond the maximum load to become
            // payable. It is retained as invalid/excess for validation.
            $isExcess = ($cumulativeUnits + $units) > $maxLoad;

            $classification[(int)$load['faculty_load_id']] = [
                'classification' => $isOverload ? 'overload' : 'regular',
                'is_excess' => $isExcess,
                'units' => $units,
                'subject_code' => $load['subject_code'] ?? null,
                'subject_name' => $load['subject_name'] ?? null,
                'section_id' => $load['section_id'] ?? null,
                'subject_id' => $load['subject_id'] ?? null
            ];

            // Once the max load is reached, subsequent assignments remain
            // invalid/excess and are not payable.
            $cumulativeUnits += $units;
        }

        return [
            'assignments' => $classification,
            'regular_limit' => $regularLimit,
            'max_load' => $maxLoad,
            'total_assigned_units' => $cumulativeUnits
        ];
    }

    /**
     * Calculates faculty pay using scheduled teaching hours and the
     * qualification rate stored in pr_teacher_qualification_rates.pay_per_unit.
     *
     * In the revised payroll rule, that database value is interpreted as
     * the configured hourly rate. The column is retained for compatibility
     * with the current database schema.
     *
     * Normal teaching days: actual approved attendance hours are paid,
     * with regular-load hours consumed before overload hours.
     * Approved leave and paid holidays: regular scheduled hours are paid;
     * overload hours are not paid for those dates.
     */
    public function calculateFacultyDailyPay(
        int $employeeId,
        string $date,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {
        $employee = $this->getEmployee($employeeId);
        if (!$employee) {
            return [
                'date' => $date,
                'classes' => [],
                'regular_hours' => 0,
                'overload_hours' => 0,
                'total_hours' => 0,
                'hourly_rate' => 0,
                'regular_gross' => 0,
                'overload_gross' => 0,
                'gross' => 0,
                'is_holiday' => false,
                'is_paid_leave' => false
            ];
        }

        $classes = $this->getFacultyClassesForDate(
            $employeeId,
            $date,
            $schoolYearId,
            $semesterId
        );

        $loadInfo = $this->getFacultyLoadClassification(
            $employeeId,
            $schoolYearId,
            $semesterId
        );
        $assignments = $loadInfo['assignments'] ?? [];

        $regularScheduledHours = 0.0;
        $overloadScheduledHours = 0.0;
        $classifiedClasses = [];

        foreach ($classes as $class) {
            $start = $class['start_time'] ?? null;
            $end = $class['end_time'] ?? null;
            if (!$start || !$end) {
                continue;
            }

            $startTs = strtotime($date . ' ' . $start);
            $endTs = strtotime($date . ' ' . $end);
            if ($startTs === false || $endTs === false || $endTs <= $startTs) {
                continue;
            }

            $hours = ($endTs - $startTs) / 3600;
            $classification = 'regular';
            $isExcess = false;

            $facultyLoadId = isset($class['faculty_load_id'])
                ? (int)$class['faculty_load_id']
                : 0;

            if ($facultyLoadId > 0 && isset($assignments[$facultyLoadId])) {
                $classification = $assignments[$facultyLoadId]['classification'];
                $isExcess = (bool)$assignments[$facultyLoadId]['is_excess'];
            } else {
                // Current test data may contain schedule rows without a
                // faculty_load_id. Match by subject/section where possible.
                foreach ($assignments as $assignment) {
                    if ((int)($assignment['subject_id'] ?? 0) !== (int)($class['subject_id'] ?? 0)) {
                        continue;
                    }
                    if (
                        $assignment['section_id'] !== null &&
                        $class['section_id'] !== null &&
                        (int)$assignment['section_id'] !== (int)$class['section_id']
                    ) {
                        continue;
                    }
                    $classification = $assignment['classification'];
                    $isExcess = (bool)$assignment['is_excess'];
                    break;
                }
            }

            if ($isExcess) {
                continue;
            }

            if ($classification === 'overload') {
                $overloadScheduledHours += $hours;
            } else {
                $regularScheduledHours += $hours;
            }

            $class['scheduled_hours'] = round($hours, 2);
            $class['load_classification'] = $classification;
            $classifiedClasses[] = $class;
        }

        $isHoliday = $this->isPaidHoliday($date);
        $isPaidLeave = $this->isPaidLeaveDate(
            $employeeId,
            $date
        );

        if ($isHoliday || $isPaidLeave) {
            // Paid holiday/leave covers regular teaching load only.
            $regularHours = $regularScheduledHours;
            $overloadHours = 0.0;
        } else {
            $attendance = $this->getDailyApprovedAttendance(
                $employeeId,
                $date
            );
            $attendedHours = $attendance['hours'];

            // Regular teaching hours are paid first. Any remaining actual
            // approved hours may be applied to eligible overload hours.
            $regularHours = min($attendedHours, $regularScheduledHours);
            $remainingHours = max(0.0, $attendedHours - $regularHours);
            $overloadHours = min($remainingHours, $overloadScheduledHours);
        }

        $hourlyRate = $this->getFacultyHourlyRate(
            $employee['graduate_level']
        );

        $regularGross = $regularHours * $hourlyRate;
        $overloadGross = $overloadHours * $hourlyRate;
        $gross = $regularGross + $overloadGross;

        return [
            'date' => $date,
            'classes' => $classifiedClasses,
            'regular_hours' => round($regularHours, 2),
            'overload_hours' => round($overloadHours, 2),
            'total_hours' => round($regularHours + $overloadHours, 2),
            'scheduled_regular_hours' => round($regularScheduledHours, 2),
            'scheduled_overload_hours' => round($overloadScheduledHours, 2),
            'hourly_rate' => $hourlyRate,
            'regular_gross' => round($regularGross, 2),
            'overload_gross' => round($overloadGross, 2),
            'gross' => round($gross, 2),
            'is_holiday' => $isHoliday,
            'is_paid_leave' => $isPaidLeave,
            'qualification' => $employee['graduate_level'] ?? 'None'
        ];
    }

    /** Get faculty earnings for the complete payroll period. */
    public function calculateFacultyPeriodEarnings(
        int $employeeId,
        string $startDate,
        string $endDate,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {
        $days = [];
        $current = new DateTime($startDate);
        $end = new DateTime($endDate);
        $totalRegularHours = 0.0;
        $totalOverloadHours = 0.0;
        $totalGross = 0.0;

        while ($current <= $end) {
            $date = $current->format('Y-m-d');
            $daily = $this->calculateFacultyDailyPay(
                $employeeId,
                $date,
                $schoolYearId,
                $semesterId
            );

            if ($daily['total_hours'] > 0 || $daily['is_holiday'] || $daily['is_paid_leave']) {
                $days[] = $daily;
                $totalRegularHours += $daily['regular_hours'];
                $totalOverloadHours += $daily['overload_hours'];
                $totalGross += $daily['gross'];
            }

            $current->modify('+1 day');
        }

        return [
            'days' => $days,
            'total_regular_hours' => round($totalRegularHours, 2),
            'total_overload_hours' => round($totalOverloadHours, 2),
            'total_hours' => round($totalRegularHours + $totalOverloadHours, 2),
            'gross' => round($totalGross, 2)
        ];
    }

    private function getDailyApprovedAttendance(
        int $employeeId,
        string $date
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE
                    WHEN status IN ('PRESENT','LATE','EARLY_OUT')
                    THEN COALESCE(regular_hours, total_hours_worked, 0)
                    ELSE 0
                END), 0) AS hours,
                COALESCE(SUM(CASE
                    WHEN status IN ('PRESENT','LATE','EARLY_OUT')
                    THEN COALESCE(late_minutes, 0)
                    ELSE 0
                END), 0) AS late_minutes
            FROM ta_attendance
            WHERE employee_id = :employee_id
              AND attendance_date = :attendance_date
              AND is_approved = 1
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':attendance_date' => $date
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'hours' => max(0.0, (float)($row['hours'] ?? 0)),
            'late_minutes' => max(0, (int)($row['late_minutes'] ?? 0))
        ];
    }

    private function isPaidHoliday(string $date): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM ta_holidays
            WHERE holiday_date = :holiday_date
              AND is_active = 1
              AND is_working_day = 0
        ");
        $stmt->execute([':holiday_date' => $date]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function isPaidLeaveDate(int $employeeId, string $date): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM ta_leave_requests lr
            INNER JOIN ta_leave_types lt
                ON lt.leave_type_id = lr.leave_type_id
            WHERE lr.employee_id = :employee_id
              AND lr.status = 'Approved'
              AND lr.start_date <= :leave_date
              AND lr.end_date >= :leave_date
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':leave_date' => $date
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /* ============================================================
       QUALIFICATION RATE
       ============================================================ */
    private function getFacultyHourlyRate(string $graduateLevel): float
    {
        $qualification = match ($graduateLevel) {
            'LPT' => 'LPT',
            'Masteral' => 'Masteral',
            'Doctoral' => 'Doctoral',
            default => 'ProfEd'
        };

        // The current database still names this column pay_per_unit.
        // Under the revised payroll rule it stores the faculty hourly rate.
        $stmt = $this->db->prepare("
            SELECT pay_per_unit
            FROM pr_teacher_qualification_rates
            WHERE qualification = :qualification
              AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':qualification' => $qualification
        ]);
        $rate = $stmt->fetchColumn();
        return $rate !== false ? max(0.0, (float)$rate) : 0.00;
    }

    /* ============================================================
       QUALIFICATION RATE
       ============================================================ */
    /* ============================================================
       PART-TIME HOURLY RATE
       ============================================================ */
    private function getPartTimeHourlyRate(
        int $employeeId,
        string $effectiveDate
    ): float {
        $stmt = $this->db->prepare("
            SELECT hourly_rate
            FROM pr_part_time_rates
            WHERE employee_id = :employee_id
              AND status = 'active'
              AND effective_date <= :effective_date
              AND (
                    end_date IS NULL
                    OR end_date >= :effective_date
              )
            ORDER BY effective_date DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':effective_date' => $effectiveDate
        ]);
        $rate = $stmt->fetchColumn();
        return $rate !== false ? (float)$rate : 0.00;
    }
    /* ============================================================
       LEGAL CONTRIBUTION ELIGIBILITY
       ============================================================ */
    private function hasSubmittedContribution(
        string $table,
        int $employeeId
    ): bool {
        $allowedTables = [
            'lc_sss_contributions',
            'lc_philhealth_contributions',
            'lc_pagibig_contributions',
            'lc_bir_contributions'
        ];
        if (!in_array($table, $allowedTables, true)) {
            throw new InvalidArgumentException(
                'Invalid contribution table.'
            );
        }
        $sql = "
            SELECT COUNT(*)
            FROM {$table}
            WHERE employee_id = :employee_id
              AND status = 'Submitted'
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':employee_id' => $employeeId
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }
    private function getContributionEligibility(
        int $employeeId
    ): array {
        return [
            'sss' => $this->hasSubmittedContribution(
                'lc_sss_contributions',
                $employeeId
            ),
            'philhealth' => $this->hasSubmittedContribution(
                'lc_philhealth_contributions',
                $employeeId
            ),
            'pagibig' => $this->hasSubmittedContribution(
                'lc_pagibig_contributions',
                $employeeId
            ),
            'bir' => $this->hasSubmittedContribution(
                'lc_bir_contributions',
                $employeeId
            )
        ];
    }
    /* ============================================================
       STATUTORY CONTRIBUTION CALCULATIONS
       ============================================================ */
    private function calculateSSS(float $monthlyBase): float
    {
        $stmt = $this->db->prepare("
        SELECT
            monthly_salary_credit,
            employee_rate
        FROM pr_sss_contribution_rates
        WHERE :salary >= min_compensation
          AND (
                max_compensation IS NULL
                OR :salary <= max_compensation
              )
          AND effective_from <= CURDATE()
          AND (
                effective_to IS NULL
                OR effective_to >= CURDATE()
              )
          AND is_active = 1
        ORDER BY effective_from DESC
        LIMIT 1
    ");
        $stmt->execute([
            ':salary' => $monthlyBase
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 0.00;
        }
        return round(
            (float)$row['monthly_salary_credit']
                * (float)$row['employee_rate'],
            2
        );
    }
    private function calculatePhilHealth(float $monthlyBase): float
    {
        $stmt = $this->db->prepare("
        SELECT
            premium_rate,
            employee_share
        FROM pr_philhealth_rates
        WHERE :salary >= min_salary
          AND (
                max_salary IS NULL
                OR :salary <= max_salary
              )
          AND effective_from <= CURDATE()
          AND (
                effective_to IS NULL
                OR effective_to >= CURDATE()
              )
          AND is_active = 1
        ORDER BY effective_from DESC
        LIMIT 1
    ");
        $stmt->execute([
            ':salary' => $monthlyBase
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 0.00;
        }
        $base = max(
            10000,
            min($monthlyBase, 100000)
        );
        return round(
            $base * (float)$row['employee_share'],
            2
        );
    }
    private function calculatePagIBIG(float $monthlyBase): float
    {
        $stmt = $this->db->prepare("
        SELECT
            employee_rate,
            employee_max_contribution
        FROM pr_pagibig_rates
        WHERE :salary >= min_salary
          AND (
                max_salary IS NULL
                OR :salary <= max_salary
              )
          AND effective_from <= CURDATE()
          AND (
                effective_to IS NULL
                OR effective_to >= CURDATE()
              )
          AND is_active = 1
        ORDER BY effective_from DESC
        LIMIT 1
    ");
        $stmt->execute([
            ':salary' => $monthlyBase
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 0.00;
        }
        $contribution =
            $monthlyBase * (float)$row['employee_rate'];
        if ($row['employee_max_contribution'] !== null) {
            $contribution = min(
                $contribution,
                (float)$row['employee_max_contribution']
            );
        }
        return round($contribution, 2);
    }
    /* ============================================================
       WITHHOLDING TAX
       ============================================================ */
    private function calculateWithholdingTax(
        float $taxableIncome,
        string $payFrequency = 'semi_monthly'
    ): float {
        $stmt = $this->db->prepare("
        SELECT
            min_income,
            max_income,
            tax_rate,
            fixed_tax
        FROM pr_tax_tables
        WHERE pay_frequency = :pay_frequency
          AND :income >= min_income
          AND (
                max_income IS NULL
                OR :income <= max_income
              )
          AND effective_from <= CURDATE()
          AND (
                effective_to IS NULL
                OR effective_to >= CURDATE()
              )
          AND is_active = 1
        ORDER BY min_income DESC
        LIMIT 1
    ");
        $stmt->execute([
            ':pay_frequency' => $payFrequency,
            ':income' => $taxableIncome
        ]);
        $bracket = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$bracket) {
            return 0.00;
        }
        $minIncome = (float)$bracket['min_income'];
        $taxRate   = (float)$bracket['tax_rate'];
        $fixedTax  = (float)$bracket['fixed_tax'];
        $excess = max(
            0,
            $taxableIncome - $minIncome
        );
        return round(
            $fixedTax + ($excess * $taxRate),
            2
        );
    }
    /* ============================================================
       ADJUSTMENTS
       ============================================================ */
    private function getEmployeeAdjustments(
        int $employeeId,
        int $periodId
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                adjustment_id,
                type,
                description,
                amount,
                deduction_subtype
            FROM pr_employee_adjustments
            WHERE employee_id = :employee_id
              AND period_id = :period_id
            ORDER BY adjustment_id
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':period_id' => $periodId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /* ============================================================
   LATE DEDUCTION
   ============================================================ */
    private function calculateLateDeduction(
        int $employeeId,
        string $startDate,
        string $endDate,
        string $positionGroup = 'non-teaching'
    ): array {
        $stmt = $this->db->prepare("
        SELECT
            COALESCE(SUM(late_minutes), 0) AS total_late_minutes
        FROM ta_attendance
        WHERE employee_id = :employee_id
          AND attendance_date BETWEEN :start_date AND :end_date
          AND is_approved = 1
    ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        $lateMinutes = (int)$stmt->fetchColumn();
        if ($lateMinutes <= 0) {
            return [
                'late_minutes' => 0,
                'rate_per_minute' => 0,
                'deduction' => 0
            ];
        }
        /*
     * Get the configured late deduction rate for this employee's
     * position group (see classifyPosition()). 'teaching' maps to
     * the 'Teacher' rate row; everything else uses 'Other'.
     */
        $positionType = $positionGroup === 'teaching' ? 'Teacher' : 'Other';
        $stmt = $this->db->prepare("
        SELECT late_per_minute_rate
        FROM pr_position_deduction_rates
        WHERE position_type = :position_type
          AND is_active = 1
        LIMIT 1
    ");
        $stmt->execute([
            ':position_type' => $positionType
        ]);
        $rate = (float)$stmt->fetchColumn();
        if ($rate <= 0) {
            return [
                'late_minutes' => $lateMinutes,
                'rate_per_minute' => 0,
                'deduction' => 0
            ];
        }
        $deduction = $lateMinutes * $rate;
        return [
            'late_minutes' => $lateMinutes,
            'rate_per_minute' => $rate,
            'deduction' => round($deduction, 2)
        ];
    }
    /* ============================================================
       PART-TIME PAYROLL
       ============================================================ */
    private function calculatePartTimePayroll(
        array $employee,
        array $period
    ): array {
        $attendance = $this->getTimeAttendanceMetrics(
            (int)$employee['employee_id'],
            $period['start_date'],
            $period['end_date']
        );
        $hourlyRate = $this->getPartTimeHourlyRate(
            (int)$employee['employee_id'],
            $period['start_date']
        );
        $hoursWorked = (float)$attendance['total_hours_worked'];
        $gross = round(
            $hoursWorked * $hourlyRate,
            2
        );
        return [
            'gross' => $gross,
            'hours_worked' => $hoursWorked,
            'hourly_rate' => $hourlyRate,
            'days_worked' => (int)$attendance['present_days'],
            'absent_days' => (int)$attendance['absent_days']
        ];
    }
    /* ============================================================
       FULL-TIME NON-FACULTY PAYROLL
       ============================================================ */
    private function calculateRegularPayroll(
        array $employee,
        array $period
    ): array {
        $salary = max(0.0, (float)($employee['negotiated_salary'] ?? 0));
        $dailyRate = $salary / self::NON_TEACHING_DAYS_PER_MONTH;
        $employeeId = (int)$employee['employee_id'];

        $attendance = $this->getTimeAttendanceMetrics(
            $employeeId,
            $period['start_date'],
            $period['end_date']
        );
        $approvedLeaveDates = $this->getApprovedPaidLeaveDates(
            $employeeId,
            $period['start_date'],
            $period['end_date']
        );
        $holidayDates = $this->getPaidHolidayDates(
            $period['start_date'],
            $period['end_date']
        );

        $payableDates = [];

        // Approved attendance counts as a payable workday.
        $attendanceRows = $this->getAttendanceRecords(
            $employeeId,
            $period['start_date'],
            $period['end_date']
        );
        foreach ($attendanceRows as $row) {
            if ((int)$row['is_approved'] !== 1) {
                continue;
            }
            if (in_array($row['status'], ['PRESENT', 'LATE', 'EARLY_OUT'], true)) {
                $payableDates[$row['attendance_date']] = true;
            }
        }

        // Approved leave is paid.
        foreach ($approvedLeaveDates as $date => $_leave) {
            $payableDates[$date] = true;
        }

        // Non-working holidays are paid when they fall on a normal
        // Monday-Saturday workday. Existing attendance on that date is
        // already represented by the same date key, preventing double pay.
        foreach ($holidayDates as $date) {
            $dayOfWeek = date('N', strtotime($date));
            if ($dayOfWeek <= 6) {
                $payableDates[$date] = true;
            }
        }

        $payableDays = count($payableDates);
        $gross = $dailyRate * $payableDays;

        return [
            'gross' => round($gross, 2),
            'salary' => $salary,
            'daily_rate' => round($dailyRate, 2),
            'payable_days' => $payableDays,
            'days_worked' => (int)$attendance['present_days'],
            'absent_days' => (int)$attendance['absent_days'],
            'paid_leave_days' => count($approvedLeaveDates),
            'paid_holiday_days' => count($holidayDates),
            'hours_worked' => (float)$attendance['total_hours_worked'],
            'late_minutes' => (int)$attendance['total_late_minutes']
        ];
    }

    private function getApprovedPaidLeaveDates(
        int $employeeId,
        string $startDate,
        string $endDate
    ): array {
        $leaves = $this->getApprovedLeaves(
            $employeeId,
            $startDate,
            $endDate
        );
        $dates = [];

        foreach ($leaves as $leave) {
            $leaveStart = max($leave['start_date'], $startDate);
            $leaveEnd = min($leave['end_date'], $endDate);
            $current = new DateTime($leaveStart);
            $end = new DateTime($leaveEnd);

            while ($current <= $end) {
                $date = $current->format('Y-m-d');
                $dates[$date] = [
                    'leave_request_id' => (int)$leave['leave_request_id'],
                    'leave_type_id' => (int)$leave['leave_type_id'],
                    'leave_type_name' => $leave['leave_type_name']
                ];
                $current->modify('+1 day');
            }
        }

        return $dates;
    }

    private function getPaidHolidayDates(
        string $startDate,
        string $endDate
    ): array {
        $stmt = $this->db->prepare("
            SELECT holiday_date
            FROM ta_holidays
            WHERE holiday_date BETWEEN :start_date AND :end_date
              AND is_active = 1
              AND is_working_day = 0
            ORDER BY holiday_date
        ");
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        return array_map(
            static fn($row) => $row['holiday_date'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /* ============================================================
       MAIN PAYROLL CALCULATION
       ============================================================ */
    public function calculateEmployeePayroll(
        int $employeeId,
        int $periodId,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {
        $employee = $this->getEmployee($employeeId);
        $period = $this->getPayrollPeriod($periodId);

        if (!$employee || !$period) {
            return [];
        }

        $earnings = [];
        $deductions = [];
        $grossPay = 0.00;
        $totalDeductions = 0.00;

        $employmentType = $employee['employment_type'] ?? null;
        $isOjt = $employmentType === 'OJT/Training';
        $isPartTime = $employmentType === 'Part-time';

        $positionGroup = $this->classifyPosition(
            $employee['position_name'] ?? null
        );
        $faculty = $this->getSmsFaculty($employeeId);
        $hasSmsFacultyRecord = $faculty !== null;
        // Route by the employee's actual position, not by whether an SMS
        // record happens to exist yet — a teaching position with no SMS
        // schedule/subject load should be paid ₱0 for this period (see
        // below), not silently fall back to the non-teaching daily rate.
        $isFaculty = $positionGroup === 'teaching'
            || ($positionGroup === 'unknown' && $hasSmsFacultyRecord);

        /* ========================================================
           1. EARNINGS
           ======================================================== */
        if ($isOjt) {
            $ojtAllowance = $this->getOjtAllowance(
                $employeeId,
                $period['start_date'],
                $period['end_date']
            );

            if ($ojtAllowance > 0) {
                $grossPay = round($ojtAllowance, 2);
                $earnings[] = [
                    'description' => 'OJT Training Allowance',
                    'amount' => $grossPay
                ];
            }
        } elseif ($isFaculty) {
            $facultyPayroll = $this->calculateFacultyPeriodEarnings(
                $employeeId,
                $period['start_date'],
                $period['end_date'],
                $schoolYearId,
                $semesterId
            );
            $grossPay = $facultyPayroll['gross'];

            foreach ($facultyPayroll['days'] as $day) {
                if ($day['regular_gross'] > 0) {
                    $label = $day['is_holiday']
                        ? 'Faculty Regular Holiday Pay'
                        : ($day['is_paid_leave']
                            ? 'Faculty Regular Paid Leave'
                            : 'Faculty Regular Teaching Pay');

                    $earnings[] = [
                        'description' => $label . ' - ' .
                            date('M d, Y', strtotime($day['date'])) .
                            ' (' . number_format($day['regular_hours'], 2) .
                            ' hrs × ₱' . number_format($day['hourly_rate'], 2) . ')',
                        'amount' => $day['regular_gross']
                    ];
                }

                if ($day['overload_gross'] > 0) {
                    $earnings[] = [
                        'description' => 'Faculty Overload Pay - ' .
                            date('M d, Y', strtotime($day['date'])) .
                            ' (' . number_format($day['overload_hours'], 2) .
                            ' hrs × ₱' . number_format($day['hourly_rate'], 2) . ')',
                        'amount' => $day['overload_gross']
                    ];
                }
            }

            if ($grossPay <= 0) {
                $earnings[] = [
                    'description' => $hasSmsFacultyRecord
                        ? 'No scheduled teaching load found for this period — no pay'
                        : 'No SMS faculty/schedule record found for this employee — no pay',
                    'amount' => 0
                ];
            }
        } elseif ($isPartTime) {
            // Non-faculty part-time employees remain on their employee-specific
            // hourly rate. Faculty part-time employees are handled above.
            $result = $this->calculatePartTimePayroll(
                $employee,
                $period
            );
            $grossPay = $result['gross'];
            $earnings[] = [
                'description' => 'Part-time Hours (' .
                    number_format($result['hours_worked'], 2) .
                    ' hrs × ₱' . number_format($result['hourly_rate'], 2) . ')',
                'amount' => $grossPay
            ];
        } else {
            $result = $this->calculateRegularPayroll(
                $employee,
                $period
            );
            $grossPay = $result['gross'];

            $earnings[] = [
                'description' => 'Daily Rate (' .
                    number_format($result['daily_rate'], 2) .
                    ' × ' . number_format($result['payable_days'], 2) .
                    ' payable days)',
                'amount' => $grossPay
            ];
        }

        /* ========================================================
           2. OTHER EMPLOYEE DEDUCTIONS
           ======================================================== */
        $adjustments = $this->getEmployeeAdjustments(
            $employeeId,
            $periodId
        );

        foreach ($adjustments as $adjustment) {
            $amount = max(0.0, (float)$adjustment['amount']);
            if ($amount <= 0) {
                continue;
            }

            $deductions[] = [
                'description' => $adjustment['description'],
                'amount' => $amount
            ];
            $totalDeductions += $amount;
        }

        /* ========================================================
           3. LATE DEDUCTION
           ======================================================== */
        if (!$isOjt && $grossPay > 0) {
            $positionGroup = $this->classifyPosition(
                $employee['position_name'] ?? null
            );
            if ($positionGroup === 'unknown') {
                // Employee has no position_name at all. Fall back to the
                // faculty-schedule signal so this doesn't silently
                // default everyone to the non-teaching rate.
                $positionGroup = $isFaculty ? 'teaching' : 'non-teaching';
            }

            $lateDeduction = $this->calculateLateDeduction(
                $employeeId,
                $period['start_date'],
                $period['end_date'],
                $positionGroup
            );

            if ($lateDeduction['deduction'] > 0) {
                $deductions[] = [
                    'description' => 'Late (' .
                        $lateDeduction['late_minutes'] .
                        ' minutes × ₱' .
                        number_format($lateDeduction['rate_per_minute'], 2) . ')',
                    'amount' => $lateDeduction['deduction']
                ];
                $totalDeductions += $lateDeduction['deduction'];
            }
        }

        /* ========================================================
           4. ABSENCE / LEAVE RULE
           ========================================================
           Approved leaves are paid. We therefore do NOT add the old
           fixed ₱1,000 deductible-leave charge.

           For daily-rate employees, an unexcused absence is already
           unpaid because the gross calculation counts payable days.
           Faculty and hourly employees are paid only for actual approved
           teaching/attendance hours, except paid leave and holidays.
        */

        /* ========================================================
           5. GOVERNMENT CONTRIBUTIONS
           ======================================================== */
        $eligibility = $this->getContributionEligibility($employeeId);

        // OJT allowance is intentionally not treated as salary/hourly pay.
        // Contributions are only calculated when the payroll earning path
        // has a positive compensable gross amount and the employee has a
        // submitted registration record.
        if ($grossPay > 0 && !$isOjt) {
            $monthlyEquivalent = $grossPay * 2;

            if ($eligibility['sss']) {
                $sss = $this->calculateSSS($monthlyEquivalent);
                if ($sss > 0) {
                    $amount = round($sss / 2, 2);
                    $deductions[] = ['description' => 'SSS', 'amount' => $amount];
                    $totalDeductions += $amount;
                }
            }

            if ($eligibility['philhealth']) {
                $philhealth = $this->calculatePhilHealth($monthlyEquivalent);
                if ($philhealth > 0) {
                    $amount = round($philhealth / 2, 2);
                    $deductions[] = ['description' => 'PhilHealth', 'amount' => $amount];
                    $totalDeductions += $amount;
                }
            }

            if ($eligibility['pagibig']) {
                $pagibig = $this->calculatePagIBIG($monthlyEquivalent);
                if ($pagibig > 0) {
                    $amount = round($pagibig / 2, 2);
                    $deductions[] = ['description' => 'Pag-IBIG', 'amount' => $amount];
                    $totalDeductions += $amount;
                }
            }

            /* ====================================================
               6. WITHHOLDING TAX
               ==================================================== */
            if ($eligibility['bir']) {
                $taxableSemiMonthly = max(0.0, $grossPay - $totalDeductions);
                $withholdingTax = $this->calculateWithholdingTax(
                    $taxableSemiMonthly,
                    'semi_monthly'
                );

                if ($withholdingTax > 0) {
                    $withholdingTax = round($withholdingTax, 2);
                    $deductions[] = [
                        'description' => 'Withholding Tax',
                        'amount' => $withholdingTax
                    ];
                    $totalDeductions += $withholdingTax;
                }
            }
        }

        /* ========================================================
           7. NET PAY
           ======================================================== */
        if ($grossPay <= 0) {
            $grossPay = 0.00;
            $totalDeductions = 0.00;
            $deductions = [];
            $netPay = 0.00;
        } else {
            $totalDeductions = min(
                round($totalDeductions, 2),
                round($grossPay, 2)
            );
            $netPay = max(
                0.00,
                round($grossPay - $totalDeductions, 2)
            );
        }

        return [
            'employee_id' => $employeeId,
            'period_id' => $periodId,
            'employment_type' => $employmentType,
            'graduate_level' => $employee['graduate_level'],
            'gross_pay' => round($grossPay, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_pay' => $netPay,
            'earnings' => $earnings,
            'deductions' => $deductions,
            'leave_summary' => [
                'deductible_leave_count' => 0,
                'non_deductible_leave_count' => 0,
                'leave_deduction' => 0.00
            ],
            'contribution_status' => $eligibility,
            'is_faculty' => $isFaculty,
            'is_part_time' => $isPartTime,
            'is_ojt' => $isOjt
        ];
    }

    /**
     * OJT allowance lookup.
     *
     * The supplied for_test_payroll(4).sql does not currently contain an
     * OJT allowance table. This method therefore checks for the table at
     * runtime and returns zero when it is not installed, instead of
     * incorrectly treating negotiated_salary as an OJT salary.
     *
     * Expected optional table:
     * pr_ojt_allowances(employee_id, allowance_amount, effective_date,
     * end_date, status).
     */
    private function getOjtAllowance(
        int $employeeId,
        string $startDate,
        string $endDate
    ): float {
        try {
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'pr_ojt_allowances'");
            if (!$tableCheck || !$tableCheck->fetchColumn()) {
                return 0.00;
            }

            $stmt = $this->db->prepare("
                SELECT allowance_amount
                FROM pr_ojt_allowances
                WHERE employee_id = :employee_id
                  AND status = 'Active'
                  AND effective_date <= :end_date
                  AND (end_date IS NULL OR end_date >= :start_date)
                ORDER BY effective_date DESC, allowance_id DESC
                LIMIT 1
            ");
            $stmt->execute([
                ':employee_id' => $employeeId,
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            $allowance = $stmt->fetchColumn();
            return $allowance !== false ? max(0.0, (float)$allowance) : 0.00;
        } catch (Throwable $e) {
            // Payroll should not fail because the optional OJT table has not
            // yet been installed in the current test database.
            return 0.00;
        }
    }

    /* ============================================================
       PAYROLL PREVIEW
       ============================================================ */
    public function getPayrollPreview(
        int $periodId,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {
        $employees =
            $this->getAllActiveEmployeesForPeriod(
                $periodId
            );
        $preview = [];
        foreach ($employees as $employee) {
            $payroll =
                $this->calculateEmployeePayroll(
                    (int)$employee['employee_id'],
                    $periodId,
                    $schoolYearId,
                    $semesterId
                );
            if (!$payroll) {
                continue;
            }
            $preview[] =
                array_merge(
                    $employee,
                    $payroll
                );
        }
        return $preview;
    }
    /* ============================================================
       PAYROLL RUN
       ============================================================ */
    public function createPayrollRun(int $periodId): int
    {
        $period = $this->getPayrollPeriod($periodId);
        if (!$period) {
            throw new RuntimeException(
                'Payroll period not found.'
            );
        }
        if ($period['status'] === 'closed') {
            throw new RuntimeException(
                'Cannot process a closed payroll period.'
            );
        }
        $stmt = $this->db->prepare("
            INSERT INTO pr_runs
                (
                    period_id,
                    processed_at,
                    status
                )
            VALUES
                (
                    :period_id,
                    NOW(),
                    'draft'
                )
        ");
        $stmt->execute([
            ':period_id' => $periodId
        ]);
        return (int)$this->db->lastInsertId();
    }
    /* ============================================================
       PAYSLIP GENERATION
       ============================================================ */
    public function generatePayslip(
        int $runId,
        int $employeeId,
        array $data
    ): int {
        if (
            !isset($data['gross_pay']) ||
            $data['gross_pay'] <= 0
        ) {
            return 0;
        }
        $stmt = $this->db->prepare("
            INSERT INTO pr_payslips
                (
                    run_id,
                    employee_id,
                    gross_pay,
                    total_deductions,
                    net_pay
                )
            VALUES
                (
                    :run_id,
                    :employee_id,
                    :gross_pay,
                    :total_deductions,
                    :net_pay
                )
        ");
        $stmt->execute([
            ':run_id' =>
            $runId,
            ':employee_id' =>
            $employeeId,
            ':gross_pay' =>
            $data['gross_pay'],
            ':total_deductions' =>
            $data['total_deductions'],
            ':net_pay' =>
            $data['net_pay']
        ]);
        $payslipId =
            (int)$this->db->lastInsertId();
        /*
         * Store detailed earnings.
         */
        $itemStmt = $this->db->prepare("
            INSERT INTO pr_payslip_items
                (
                    payslip_id,
                    item_type,
                    description,
                    amount
                )
            VALUES
                (
                    :payslip_id,
                    :item_type,
                    :description,
                    :amount
                )
        ");
        foreach (
            ($data['earnings'] ?? [])
            as $earning
        ) {
            $itemStmt->execute([
                ':payslip_id' =>
                $payslipId,
                ':item_type' =>
                'earning',
                ':description' =>
                $earning['description'],
                ':amount' =>
                $earning['amount']
            ]);
        }
        /*
         * Store detailed deductions.
         */
        foreach (
            ($data['deductions'] ?? [])
            as $deduction
        ) {
            $itemStmt->execute([
                ':payslip_id' =>
                $payslipId,
                ':item_type' =>
                'deduction',
                ':description' =>
                $deduction['description'],
                ':amount' =>
                $deduction['amount']
            ]);
        }
        return $payslipId;
    }
    /* ============================================================
       FINALIZE RUN
       ============================================================ */
    public function finalizeRun(
        int $runId,
        ?int $finalizedBy = null
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE pr_runs
            SET
                status = 'finalized',
                finalized_by = :finalized_by,
                processed_at = NOW()
            WHERE run_id = :run_id
              AND status = 'draft'
        ");
        return $stmt->execute([
            ':run_id' =>
            $runId,
            ':finalized_by' =>
            $finalizedBy
        ]);
    }
    /* ============================================================
       PAYSLIP RETRIEVAL
       ============================================================ */
    public function getPayslipById(
        int $payslipId
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT
                p.*,
                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,
                e.employment_type,
                e.graduate_level,
                e.negotiated_salary
            FROM pr_payslips p
            INNER JOIN em_employees e
                ON e.employee_id = p.employee_id
            WHERE p.payslip_id = :payslip_id
            LIMIT 1
        ");
        $stmt->execute([
            ':payslip_id' => $payslipId
        ]);
        $payslip =
            $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$payslip) {
            return null;
        }
        $itemStmt = $this->db->prepare("
            SELECT
                payslip_item_id,
                item_type,
                description,
                amount
            FROM pr_payslip_items
            WHERE payslip_id = :payslip_id
            ORDER BY payslip_item_id
        ");
        $itemStmt->execute([
            ':payslip_id' => $payslipId
        ]);
        $items =
            $itemStmt->fetchAll(
                PDO::FETCH_ASSOC
            );
        $payslip['earnings'] = [];
        $payslip['deductions'] = [];
        foreach ($items as $item) {
            if ($item['item_type'] === 'earning') {
                $payslip['earnings'][] = $item;
            } else {
                $payslip['deductions'][] = $item;
            }
        }
        return $payslip;
    }
    /* ============================================================
       CLOSE PERIOD
       ============================================================ */
    public function closePayrollPeriod(
        int $periodId
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE pr_periods
            SET status = 'closed'
            WHERE period_id = :period_id
              AND status <> 'closed'
        ");
        return $stmt->execute([
            ':period_id' => $periodId
        ]);
    }
}
