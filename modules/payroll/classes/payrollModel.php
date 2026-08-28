<?php

class PayrollModel
{
    private PDO $db;
    private ?PDO $smsDb;
    private const ABSENCE_DEDUCTION = 1000.00;

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
                e.employment_status,
                e.employment_type,
                e.unit_load,
                e.graduate_level,
                e.negotiated_salary
            FROM em_employees e
            WHERE e.employment_status = 'Active'
              AND e.is_archived = 0
            ORDER BY e.last_name, e.first_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getEmployee(int $employeeId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM em_employees
            WHERE employee_id = :employee_id
            LIMIT 1
        ");

        $stmt->execute([
            ':employee_id' => $employeeId
        ]);

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        return $employee ?: null;
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
   ABSENCE DEDUCTION
   ============================================================ */


    /**
     * Get absence records for an employee during a payroll period.
     */
    private function getAbsenceRecords(
        int $employeeId,
        string $startDate,
        string $endDate
    ): array {
        $stmt = $this->db->prepare("
        SELECT
            record_id,
            absence_date,
            type,
            excuse_status,
            reason,
            approval_notes
        FROM ta_absence_late_records
        WHERE employee_id = :employee_id
          AND absence_date BETWEEN :start_date AND :end_date
          AND type = 'ABSENT'
        ORDER BY absence_date ASC
    ");

        $stmt->execute([
            ':employee_id' => $employeeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Check whether an employee has an approved excuse
     * for a specific absence date.
     */
    private function hasApprovedAbsenceExcuse(
        int $employeeId,
        string $date
    ): bool {
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM ta_absence_late_records
        WHERE employee_id = :employee_id
          AND absence_date = :absence_date
          AND type = 'ABSENT'
          AND excuse_status = 'APPROVED'
    ");

        $stmt->execute([
            ':employee_id' => $employeeId,
            ':absence_date' => $date
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }


    /**
     * Get actual unexcused absences for payroll deduction.
     *
     * An absence is deductible when:
     *
     * 1. ta_attendance says ABSENT
     * 2. The attendance record is approved
     * 3. There is no APPROVED excuse in
     *    ta_absence_late_records
     *
     * Deduction:
     * ₱1,000 per unexcused absence.
     */
    private function getUnexcusedAbsences(
        int $employeeId,
        string $startDate,
        string $endDate
    ): array {

        $stmt = $this->db->prepare("
        SELECT
            attendance_id,
            attendance_date,
            status
        FROM ta_attendance
        WHERE employee_id = :employee_id
          AND attendance_date BETWEEN :start_date AND :end_date
          AND status = 'ABSENT'
          AND is_approved = 1
        ORDER BY attendance_date ASC
    ");

        $stmt->execute([
            ':employee_id' => $employeeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        $attendanceAbsences =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

        /*
     * Get approved leaves for the payroll period once.
     * This avoids repeatedly querying the leave tables
     * for every absence date.
     */
        $approvedLeaveDates =
            $this->getApprovedLeaveDates(
                $employeeId,
                $startDate,
                $endDate
            );

        $unexcused = [];

        foreach ($attendanceAbsences as $absence) {

            $date = $absence['attendance_date'];

            /*
         * ========================================================
         * 1. CHECK APPROVED LEAVE
         * ========================================================
         */
            if (isset($approvedLeaveDates[$date])) {

                $leave = $approvedLeaveDates[$date];

                /*
             * Non-deductible approved leave means the employee
             * was legitimately on leave and should NOT receive
             * an absence deduction.
             *
             * Deductible leave is handled separately through
             * calculateLeaveDeduction().
             */
                if ($leave['is_deductible'] == 0) {
                    continue;
                }

                /*
             * If the leave is deductible, do not add this as
             * an ordinary absence because the leave deduction
             * will be recorded separately.
             */
                if ($leave['is_deductible'] == 1) {
                    continue;
                }
            }

            /*
         * ========================================================
         * 2. CHECK APPROVED ABSENCE EXCUSE
         * ========================================================
         */
            if (
                $this->hasApprovedAbsenceExcuse(
                    $employeeId,
                    $date
                )
            ) {
                continue;
            }

            /*
         * ========================================================
         * 3. ORDINARY UNEXCUSED ABSENCE
         * ========================================================
         */
            $unexcused[] = [
                'attendance_id' =>
                (int)$absence['attendance_id'],

                'date' =>
                $date,

                'deduction' =>
                self::ABSENCE_DEDUCTION
            ];
        }

        return $unexcused;
    }


    /**
     * Calculate total absence deduction for the payroll period.
     */
    private function calculateAbsenceDeduction(
        int $employeeId,
        string $startDate,
        string $endDate
    ): array {

        $absences = $this->getUnexcusedAbsences(
            $employeeId,
            $startDate,
            $endDate
        );

        $total = count($absences) * self::ABSENCE_DEDUCTION;

        return [
            'absence_count' => count($absences),
            'rate_per_absence' => self::ABSENCE_DEDUCTION,
            'total_deduction' => round($total, 2),
            'records' => $absences
        ];
    }

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


    /**
     * Get approved leave dates for an employee during a payroll period.
     *
     * The returned dates are clipped to the payroll period so that
     * leave outside the payroll period does not affect payroll.
     */
    private function getApprovedLeaveDates(
        int $employeeId,
        string $startDate,
        string $endDate
    ): array {

        $leaves = $this->getApprovedLeaves(
            $employeeId,
            $startDate,
            $endDate
        );

        $leaveDates = [];

        foreach ($leaves as $leave) {

            $leaveStart = max(
                $leave['start_date'],
                $startDate
            );

            $leaveEnd = min(
                $leave['end_date'],
                $endDate
            );

            $current = new DateTime($leaveStart);
            $end = new DateTime($leaveEnd);

            while ($current <= $end) {

                $date = $current->format('Y-m-d');

                $leaveDates[$date] = [
                    'leave_request_id' =>
                    (int)$leave['leave_request_id'],

                    'leave_type_id' =>
                    (int)$leave['leave_type_id'],

                    'leave_type_name' =>
                    $leave['leave_type_name'],

                    'is_deductible' =>
                    (int)$leave['is_deductible']
                ];

                $current->modify('+1 day');
            }
        }

        return $leaveDates;
    }


    /**
     * Calculate leave deduction during a payroll period.
     *
     * Only approved deductible leaves are included here.
     *
     * The current system uses ₱1,000 as the absence deduction rate,
     * so deductible leave uses the same rate for now.
     */
    private function calculateLeaveDeduction(
        int $employeeId,
        string $startDate,
        string $endDate
    ): array {

        $leaveDates = $this->getApprovedLeaveDates(
            $employeeId,
            $startDate,
            $endDate
        );

        $deductibleLeaves = [];
        $nonDeductibleLeaves = [];

        foreach ($leaveDates as $date => $leave) {

            if ($leave['is_deductible'] == 1) {

                $deductibleLeaves[] = [
                    'date' => $date,
                    'leave_request_id' =>
                    $leave['leave_request_id'],
                    'leave_type_id' =>
                    $leave['leave_type_id'],
                    'leave_type_name' =>
                    $leave['leave_type_name'],
                    'deduction' =>
                    self::ABSENCE_DEDUCTION
                ];
            } else {

                $nonDeductibleLeaves[] = [
                    'date' => $date,
                    'leave_request_id' =>
                    $leave['leave_request_id'],
                    'leave_type_id' =>
                    $leave['leave_type_id'],
                    'leave_type_name' =>
                    $leave['leave_type_name']
                ];
            }
        }

        $totalDeduction =
            count($deductibleLeaves)
            * self::ABSENCE_DEDUCTION;

        return [
            'deductible_leave_count' =>
            count($deductibleLeaves),

            'non_deductible_leave_count' =>
            count($nonDeductibleLeaves),

            'rate_per_leave' =>
            self::ABSENCE_DEDUCTION,

            'total_deduction' =>
            round($totalDeduction, 2),

            'deductible_records' =>
            $deductibleLeaves,

            'non_deductible_records' =>
            $nonDeductibleLeaves
        ];
    }


    /* ============================================================
       FACULTY SCHEDULE / UNITS
       ============================================================ */

    /**
     * Find the SMS faculty record corresponding to the HR employee.
     *
     * Current bridge:
     * em_employees.email = sms.cc_faculty.email
     *
     * This is being used because cc_faculty does not contain
     * employee_id in the supplied SMS schema.
     */
    private function getSmsFaculty(int $employeeId): ?array
    {
        if (!$this->smsDb) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT email
            FROM em_employees
            WHERE employee_id = :employee_id
            LIMIT 1
        ");

        $stmt->execute([
            ':employee_id' => $employeeId
        ]);

        $email = $stmt->fetchColumn();

        if (!$email) {
            return null;
        }

        $stmt = $this->smsDb->prepare("
            SELECT
                id,
                faculty_code,
                first_name,
                last_name,
                email
            FROM cc_faculty
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => $email
        ]);

        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

        return $faculty ?: null;
    }


    /**
     * Get recurring faculty schedule from SMS.
     *
     * cc_schedule contains:
     * - faculty_id
     * - subject_id
     * - day_of_week
     * - start_time
     * - end_time
     * - school_year_id
     * - semester_id
     */
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
                'total_units' => 0,
                'rate_per_unit' => 0,
                'gross' => 0
            ];
        }

        $classes = $this->getFacultyClassesForDate(
            $employeeId,
            $date,
            $schoolYearId,
            $semesterId
        );

        $totalUnits = 0;

        foreach ($classes as $class) {
            $totalUnits += (float)($class['units'] ?? 0);
        }

        $rate = $this->getQualificationRate(
            $employee['graduate_level']
        );

        $gross = $totalUnits * $rate;

        return [
            'date' => $date,
            'classes' => $classes,
            'total_units' => $totalUnits,
            'rate_per_unit' => $rate,
            'qualification' => $employee['graduate_level'],
            'gross' => round($gross, 2)
        ];
    }


    /**
     * Calculate all faculty teaching earnings for a payroll period.
     */
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

        $totalUnits = 0;
        $totalGross = 0;

        while ($current <= $end) {
            $date = $current->format('Y-m-d');

            $daily = $this->calculateFacultyDailyPay(
                $employeeId,
                $date,
                $schoolYearId,
                $semesterId
            );

            if ($daily['total_units'] > 0) {
                $days[] = $daily;

                $totalUnits += $daily['total_units'];
                $totalGross += $daily['gross'];
            }

            $current->modify('+1 day');
        }

        return [
            'days' => $days,
            'total_units' => $totalUnits,
            'gross' => round($totalGross, 2)
        ];
    }


    /* ============================================================
       QUALIFICATION RATE
       ============================================================ */

    private function getQualificationRate(string $graduateLevel): float
    {
        /**
         * Existing table:
         * pr_teacher_qualification_rates
         *
         * Existing values include:
         * ProfEd  = 128
         * LPT     = 130
         * Masteral = 250
         *
         * We map "None" to ProfEd for now.
         */
        $qualification = match ($graduateLevel) {
            'LPT' => 'LPT',
            'Masteral' => 'Masteral',
            'Doctoral' => 'Doctoral',
            default => 'ProfEd'
        };

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

        return $rate !== false ? (float)$rate : 0.00;
    }


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
        string $endDate
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
     * Get the configured late deduction rate.
     */
        $stmt = $this->db->query("
        SELECT late_per_minute_rate
        FROM pr_position_deduction_rates
        WHERE position_type = 'Teacher'
          AND is_active = 1
        LIMIT 1
    ");

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
        $salary = (float)($employee['negotiated_salary'] ?? 0);

        /**
         * Negotiated salary is the monthly salary.
         *
         * Semi-monthly payroll:
         * monthly salary / 2
         */
        $semiMonthlySalary = $salary / 2;

        $attendance = $this->getTimeAttendanceMetrics(
            (int)$employee['employee_id'],
            $period['start_date'],
            $period['end_date']
        );

        return [
            'gross' => round($semiMonthlySalary, 2),
            'salary' => $salary,
            'days_worked' => (int)$attendance['present_days'],
            'absent_days' => (int)$attendance['absent_days'],
            'hours_worked' => (float)$attendance['total_hours_worked'],
            'late_minutes' => (int)$attendance['total_late_minutes']
        ];
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

        /*
         * ========================================================
         * 1. DETERMINE PAYROLL TYPE
         * ========================================================
         */

        $employmentType = $employee['employment_type'];

        $isPartTime = $employmentType === 'Part-time';

        /*
         * A faculty member is determined by having an active
         * SMS schedule matching the employee.
         */
        $facultySchedule = [];

        if (!$isPartTime) {
            $facultySchedule = $this->getFacultySchedule(
                $employeeId,
                $schoolYearId,
                $semesterId
            );
        }

        $isFaculty = !empty($facultySchedule);


        /*
         * ========================================================
         * 2. EARNINGS
         * ========================================================
         */

        if ($isPartTime) {

            /*
             * PART-TIME:
             * hours worked × hourly rate
             */
            $result = $this->calculatePartTimePayroll(
                $employee,
                $period
            );

            $grossPay = $result['gross'];

            $earnings[] = [
                'description' =>
                'Part-time Hours (' .
                    number_format($result['hours_worked'], 2) .
                    ' hrs × ₱' .
                    number_format($result['hourly_rate'], 2) .
                    ')',

                'amount' => $grossPay
            ];
        } elseif ($isFaculty) {

            /*
             * FULL-TIME FACULTY:
             *
             * subject units × qualification rate
             * calculated per scheduled teaching day.
             */
            $faculty = $this->calculateFacultyPeriodEarnings(
                $employeeId,
                $period['start_date'],
                $period['end_date'],
                $schoolYearId,
                $semesterId
            );

            $grossPay = $faculty['gross'];

            foreach ($faculty['days'] as $day) {

                $earnings[] = [
                    'description' =>
                    'Faculty Teaching Pay - ' .
                        date(
                            'M d, Y',
                            strtotime($day['date'])
                        ) .
                        ' (' .
                        number_format($day['total_units'], 2) .
                        ' units × ₱' .
                        number_format(
                            $day['rate_per_unit'],
                            2
                        ) .
                        ')',

                    'amount' => $day['gross']
                ];
            }
        } else {

            /*
             * FULL-TIME NON-FACULTY:
             * negotiated monthly salary / 2.
             */
            $result = $this->calculateRegularPayroll(
                $employee,
                $period
            );

            $grossPay = $result['gross'];

            $earnings[] = [
                'description' =>
                'Semi-Monthly Salary (' .
                    number_format(
                        $result['salary'],
                        2
                    ) .
                    ' ÷ 2)',

                'amount' => $grossPay
            ];
        }

        /*
 * ========================================================
 * 3. EMPLOYEE ADJUSTMENTS
 * ========================================================
 */

        $adjustments = $this->getEmployeeAdjustments(
            $employeeId,
            $periodId
        );

        foreach ($adjustments as $adjustment) {

            $amount = (float)$adjustment['amount'];

            if ($amount <= 0) {
                continue;
            }

            $deductions[] = [
                'description' => $adjustment['description'],
                'amount' => $amount
            ];

            $totalDeductions += $amount;
        }


        /*
 * ========================================================
 * 4. LATE DEDUCTION
 * ========================================================
 *
 * Late minutes come directly from ta_attendance.
 *
 * Example:
 * 10 late minutes × ₱rate per minute
 */

        $lateDeduction = $this->calculateLateDeduction(
            $employeeId,
            $period['start_date'],
            $period['end_date']
        );

        if ($lateDeduction['deduction'] > 0) {

            $deductions[] = [
                'description' =>
                'Late (' .
                    $lateDeduction['late_minutes'] .
                    ' minutes × ₱' .
                    number_format(
                        $lateDeduction['rate_per_minute'],
                        2
                    ) .
                    ')',

                'amount' =>
                $lateDeduction['deduction']
            ];

            $totalDeductions +=
                $lateDeduction['deduction'];
        }


        /*
 * ========================================================
 * 5. ABSENCE DEDUCTION
 * ========================================================
 */

        $absenceDeduction = $this->calculateAbsenceDeduction(
            $employeeId,
            $period['start_date'],
            $period['end_date']
        );

        if ($absenceDeduction['total_deduction'] > 0) {

            foreach ($absenceDeduction['records'] as $absence) {

                $deductions[] = [
                    'description' =>
                    'Unexcused Absence - ' .
                        date(
                            'M d, Y',
                            strtotime($absence['date'])
                        ) .
                        ' (₱1,000.00)',

                    'amount' =>
                    self::ABSENCE_DEDUCTION
                ];

                $totalDeductions +=
                    self::ABSENCE_DEDUCTION;
            }
        }

        $leaveDeduction = $this->calculateLeaveDeduction(
            $employeeId,
            $period['start_date'],
            $period['end_date']
        );

        /*
 * Add deductible approved leaves to deductions.
 */
        if ($leaveDeduction['total_deduction'] > 0) {

            foreach (
                $leaveDeduction['deductible_records']
                as $leave
            ) {

                $deductions[] = [
                    'description' =>
                    'Deductible Leave - ' .
                        $leave['leave_type_name'] .
                        ' (' .
                        date(
                            'M d, Y',
                            strtotime($leave['date'])
                        ) .
                        ')',

                    'amount' =>
                    self::ABSENCE_DEDUCTION
                ];

                $totalDeductions +=
                    self::ABSENCE_DEDUCTION;
            }
        }

        /*
         * ========================================================
         * 4. LEGAL CONTRIBUTION ELIGIBILITY
         * ========================================================
         */

        $eligibility = $this->getContributionEligibility(
            $employeeId
        );


        /*
         * ========================================================
         * 5. GOVERNMENT CONTRIBUTIONS
         * ========================================================
         */

        /*
         * For semi-monthly payroll we use the monthly-equivalent
         * earnings as the contribution basis.
         */
        $monthlyEquivalent = $grossPay * 2;

        if ($grossPay > 0 && $eligibility['sss']) {

            $sss = $this->calculateSSS(
                $monthlyEquivalent
            );

            if ($sss > 0) {

                /*
                 * If contribution is calculated monthly,
                 * deduct half on each semi-monthly payroll.
                 */
                $sssSemiMonthly = round($sss / 2, 2);

                $deductions[] = [
                    'description' => 'SSS',
                    'amount' => $sssSemiMonthly
                ];

                $totalDeductions += $sssSemiMonthly;
            }
        }

        if ($grossPay > 0 && $eligibility['philhealth']) {

            $philhealth = $this->calculatePhilHealth(
                $monthlyEquivalent
            );

            if ($philhealth > 0) {

                $philhealthSemiMonthly =
                    round($philhealth / 2, 2);

                $deductions[] = [
                    'description' => 'PhilHealth',
                    'amount' => $philhealthSemiMonthly
                ];

                $totalDeductions +=
                    $philhealthSemiMonthly;
            }
        }

        if ($grossPay > 0 && $eligibility['pagibig']) {

            $pagibig = $this->calculatePagIBIG(
                $monthlyEquivalent
            );

            if ($pagibig > 0) {

                $pagibigSemiMonthly =
                    round($pagibig / 2, 2);

                $deductions[] = [
                    'description' => 'Pag-IBIG',
                    'amount' => $pagibigSemiMonthly
                ];

                $totalDeductions +=
                    $pagibigSemiMonthly;
            }
        }


        /*
         * ========================================================
         * 6. WITHHOLDING TAX
         * ========================================================
         */

        if ($grossPay > 0 && $eligibility['bir']) {

            /*
             * Simplified semi-monthly taxable base.
             *
             * Statutory deductions are subtracted before tax.
             */
            $taxableSemiMonthly =
                max(
                    0,
                    $grossPay - $totalDeductions
                );

            $withholdingTax =
                $this->calculateWithholdingTax(
                    $taxableSemiMonthly,
                    'semi_monthly'
                );

            if ($withholdingTax > 0) {

                $withholdingTax =
                    round($withholdingTax, 2);

                $deductions[] = [
                    'description' =>
                    'Withholding Tax',
                    'amount' =>
                    $withholdingTax
                ];

                $totalDeductions +=
                    $withholdingTax;
            }
        }


        /*
 * ========================================================
 * 7. NET PAY
 * ========================================================
 *
 * Payroll must never produce negative net pay.
 *
 * Total deductions cannot exceed gross pay.
 */

        if ($grossPay <= 0) {

            /*
     * No earnings means there should be no payable
     * employee deductions for this payroll period.
     */
            $totalDeductions = 0.00;

            $deductions = [];

            $netPay = 0.00;
        } else {

            /*
     * Prevent deductions from exceeding gross pay.
     */
            $totalDeductions = min(
                $totalDeductions,
                $grossPay
            );

            $totalDeductions = round(
                $totalDeductions,
                2
            );

            $netPay = round(
                $grossPay - $totalDeductions,
                2
            );

            /*
     * Final safety check.
     */
            if ($netPay < 0) {
                $netPay = 0.00;
            }
        }
        return [
            'employee_id' => $employeeId,
            'period_id' => $periodId,

            'employment_type' =>
            $employee['employment_type'],

            'graduate_level' =>
            $employee['graduate_level'],

            'gross_pay' =>
            round($grossPay, 2),

            'total_deductions' =>
            round($totalDeductions, 2),

            'net_pay' =>
            $netPay,

            'earnings' =>
            $earnings,

            'deductions' =>
            $deductions,

            'leave_summary' => [
                'deductible_leave_count' =>
                $leaveDeduction['deductible_leave_count'],

                'non_deductible_leave_count' =>
                $leaveDeduction['non_deductible_leave_count'],

                'leave_deduction' =>
                $leaveDeduction['total_deduction']
            ],

            'contribution_status' =>
            $eligibility,

            'is_faculty' =>
            $isFaculty,

            'is_part_time' =>
            $isPartTime

        ];
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
                e.employee_num,
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
