<?php

class PayrollModel
{
    private PDO $db;
    private ?PDO $smsDb;

    /**
     * $db    = HRIS/payroll database
     * $smsDb = SMS database containing faculty schedules/subjects
     */
    public function __construct(PDO $db, ?PDO $smsDb = null)
    {
        $this->db = $db;
        $this->smsDb = $smsDb;
    }

    /* ============================================================
       PAYROLL PERIODS
       ============================================================ */

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


    /* ============================================================
       EMPLOYEES
       ============================================================ */

    public function getAllActiveEmployeesForPeriod(int $periodId): array
    {
        $stmt = $this->db->query("
            SELECT
                e.employee_id,
                e.employee_num,
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


    /* ============================================================
       ATTENDANCE
       ============================================================ */

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
            FROM payroll_part_time_rates
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
            'sss_contributions',
            'philhealth_contributions',
            'pagibig_contributions',
            'bir_contributions'
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
              AND status = 'submitted'
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
                'sss_contributions',
                $employeeId
            ),

            'philhealth' => $this->hasSubmittedContribution(
                'philhealth_contributions',
                $employeeId
            ),

            'pagibig' => $this->hasSubmittedContribution(
                'pagibig_contributions',
                $employeeId
            ),

            'bir' => $this->hasSubmittedContribution(
                'bir_contributions',
                $employeeId
            )
        ];
    }


    /* ============================================================
       STATUTORY CONTRIBUTION CALCULATIONS
       ============================================================ */

    private function calculateSSS(float $monthlyBase): float
    {
        /**
         * Do not hard-code the old 2024 table here.
         *
         * This method is intentionally isolated so the current
         * SSS contribution table/rates can be replaced with the
         * legal schedule you are using.
         *
         * Temporary implementation:
         * return 0 until the legal rate table is connected.
         */
        return 0.00;
    }

    private function calculatePhilHealth(float $monthlyBase): float
    {
        /**
         * Same principle:
         * contribution formula should follow the legal table
         * being used by the system.
         */
        return 0.00;
    }

    private function calculatePagIBIG(float $monthlyBase): float
    {
        /**
         * Same principle:
         * use the applicable Pag-IBIG contribution schedule.
         */
        return 0.00;
    }


    /* ============================================================
       WITHHOLDING TAX
       ============================================================ */

    private function calculateWithholdingTax(
        float $taxableIncome
    ): float {
        /**
         * Use pr_tax_tables rather than hard-coded TRAIN brackets.
         *
         * This expects the table to contain:
         * min_income
         * max_income
         * tax_rate
         * fixed_tax
         *
         * The exact BIR table should be populated according to
         * the tax schedule your capstone is implementing.
         */

        $stmt = $this->db->prepare("
            SELECT
                min_income,
                max_income,
                tax_rate,
                fixed_tax
            FROM pr_tax_tables
            WHERE :income >= min_income
              AND (
                    max_income IS NULL
                    OR :income <= max_income
                  )
            ORDER BY min_income DESC
            LIMIT 1
        ");

        $stmt->execute([
            ':income' => $taxableIncome
        ]);

        $bracket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bracket) {
            return 0.00;
        }

        $minIncome = (float)$bracket['min_income'];
        $taxRate = (float)$bracket['tax_rate'];
        $fixedTax = (float)$bracket['fixed_tax'];

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
              AND payroll_period_id = :period_id
            ORDER BY adjustment_id
        ");

        $stmt->execute([
            ':employee_id' => $employeeId,
            ':period_id' => $periodId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /* ============================================================
       LATE DEDUCTION FOR FACULTY
       ============================================================ */

    private function calculateFacultyLateDeduction(
        int $employeeId,
        string $date,
        ?int $schoolYearId = null,
        ?int $semesterId = null
    ): array {
        $attendanceStmt = $this->db->prepare("
            SELECT
                time_in,
                status
            FROM ta_attendance
            WHERE employee_id = :employee_id
              AND attendance_date = :attendance_date
              AND is_approved = 1
            ORDER BY attendance_id DESC
            LIMIT 1
        ");

        $attendanceStmt->execute([
            ':employee_id' => $employeeId,
            ':attendance_date' => $date
        ]);

        $attendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC);

        if (!$attendance || empty($attendance['time_in'])) {
            return [
                'late_minutes' => 0,
                'deduction' => 0
            ];
        }

        $classes = $this->getFacultyClassesForDate(
            $employeeId,
            $date,
            $schoolYearId,
            $semesterId
        );

        if (!$classes) {
            return [
                'late_minutes' => 0,
                'deduction' => 0
            ];
        }

        usort(
            $classes,
            fn($a, $b) =>
            strcmp($a['start_time'], $b['start_time'])
        );

        $firstClassStart = $classes[0]['start_time'];

        $scheduled = new DateTime(
            $date . ' ' . $firstClassStart
        );

        $actual = new DateTime(
            $attendance['time_in']
        );

        if ($actual <= $scheduled) {
            return [
                'late_minutes' => 0,
                'deduction' => 0
            ];
        }

        $lateMinutes = (int)(
            ($actual->getTimestamp() - $scheduled->getTimestamp())
            / 60
        );

        /**
         * Existing payroll table contains a late-per-minute
         * configuration.
         */
        $stmt = $this->db->query("
            SELECT late_per_minute_rate
            FROM pr_position_deduction_rates
            WHERE position_type = 'Teacher'
              AND is_active = 1
            LIMIT 1
        ");

        $rate = (float)$stmt->fetchColumn();

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

                /*
                 * Faculty late deduction:
                 * actual time-in vs first class start.
                 */
                $late = $this->calculateFacultyLateDeduction(
                    $employeeId,
                    $day['date'],
                    $schoolYearId,
                    $semesterId
                );

                if ($late['deduction'] > 0) {

                    $deductions[] = [
                        'description' =>
                        'Late (' .
                            $late['late_minutes'] .
                            ' minutes × ₱' .
                            number_format(
                                $late['rate_per_minute'],
                                2
                            ) .
                            ')',

                        'amount' => $late['deduction']
                    ];

                    $totalDeductions += $late['deduction'];
                }
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

        if ($eligibility['sss']) {

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

        if ($eligibility['philhealth']) {

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

        if ($eligibility['pagibig']) {

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

        if ($eligibility['bir']) {

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
                    $taxableSemiMonthly * 2
                ) / 2;

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
         */

        $netPay =
            round(
                $grossPay - $totalDeductions,
                2
            );


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
                    payroll_period_id,
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
                    payroll_run_id,
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
