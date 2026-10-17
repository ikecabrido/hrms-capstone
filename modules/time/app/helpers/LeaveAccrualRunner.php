<?php
/**
 * Monthly leave accrual runner. Grants monthly_accrual_days to every active
 * employee's balance for leave types with accrual_frequency = 'monthly' once per
 * calendar month, while supporting safe backfills for missed months.
 */

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../core/TimeDatabase.php';

class LeaveAccrualRunner
{
    public static function run(array $options = [])
    {
        date_default_timezone_set('Asia/Manila');

        $dryRun = !empty($options['dry_run']);
        $fromMonth = isset($options['from']) && $options['from'] !== null && trim((string)$options['from']) !== '' ? trim((string)$options['from']) : null;
        $nowTs = isset($options['now']) ? (int)$options['now'] : time();
        $now = new DateTimeImmutable('@' . $nowTs, new DateTimeZone('Asia/Manila'));
        $currentMonth = $now->format('Y-m');
        $currentYear = (int)$now->format('Y');
        $results = [
            'success' => false,
            'error' => null,
            'credited' => [],
            'skipped' => 0,
            'months_backfilled' => 0,
            'skipped_reason' => null
        ];

        try {
            $db = TimeDatabase::getInstance();
            $conn = $db->getConnection();

            $lock = self::acquireLock($conn, 'ta_leave_accrual');
            if ($lock === false) {
                $results['success'] = true;
                $results['skipped_reason'] = 'locked';
                return $results;
            }

            try {
                $typesStmt = $conn->prepare("SELECT leave_type_id, leave_type_name, monthly_accrual_days FROM ta_leave_types WHERE accrual_frequency = 'monthly' AND monthly_accrual_days IS NOT NULL");
                $typesStmt->execute();
                $monthlyTypes = $typesStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($monthlyTypes)) {
                    $results['success'] = true;
                    return $results;
                }

                $employeesStmt = $conn->prepare("SELECT employee_id, hire_date, employment_status FROM em_employees WHERE employment_status = 'Active'");
                $employeesStmt->execute();
                $employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($employees as $employee) {
                    $employeeId = (int)$employee['employee_id'];
                    foreach ($monthlyTypes as $type) {
                        $creditInfo = self::computeMonthlyCreditPlan($conn, $employeeId, (int)$type['leave_type_id'], (float)$type['monthly_accrual_days'], $currentYear, $currentMonth, $fromMonth, $employee['hire_date'] ?? null, $now);

                        if (!$creditInfo['eligible']) {
                            $results['skipped']++;
                            continue;
                        }

                        $months = $creditInfo['months'];
                        if (empty($months)) {
                            $results['skipped']++;
                            continue;
                        }

                        $totalDays = $creditInfo['days'];
                        $resultingBalance = $creditInfo['resulting_balance'];
                        $monthsBackfilled = count($months);
                        $results['months_backfilled'] += $monthsBackfilled;

                        if ($dryRun) {
                            $results['credited'][] = [
                                'employee_id' => $employeeId,
                                'leave_type' => $type['leave_type_name'],
                                'months' => $months,
                                'days' => $totalDays,
                                'resulting_balance' => $resultingBalance,
                                'current_month' => $currentMonth
                            ];
                            continue;
                        }

                        $existingStmt = $conn->prepare("SELECT leave_balance_id, opening_balance, remaining_balance, last_monthly_accrual_month FROM ta_leave_balances WHERE employee_id = :employee_id AND leave_type_id = :leave_type_id AND year = :year FOR UPDATE");
                        $existingStmt->execute([':employee_id' => $employeeId, ':leave_type_id' => $type['leave_type_id'], ':year' => $currentYear]);
                        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

                        if ($existing) {
                            $updateStmt = $conn->prepare("UPDATE ta_leave_balances SET opening_balance = opening_balance + :credit, remaining_balance = remaining_balance + :credit, last_monthly_accrual_month = :current_month WHERE leave_balance_id = :leave_balance_id AND (last_monthly_accrual_month IS NULL OR last_monthly_accrual_month < :current_month)");
                            $updateStmt->execute([
                                ':credit' => $totalDays,
                                ':current_month' => $currentMonth,
                                ':leave_balance_id' => $existing['leave_balance_id']
                            ]);

                            if ($updateStmt->rowCount() === 0) {
                                $results['skipped']++;
                                continue;
                            }
                        } else {
                            $insertStmt = $conn->prepare("INSERT INTO ta_leave_balances (employee_id, leave_type_id, year, opening_balance, used_balance, remaining_balance, last_monthly_accrual_month, created_at, updated_at) VALUES (:employee_id, :leave_type_id, :year, :opening, 0, :opening, :current_month, NOW(), NOW())");
                            try {
                                $insertStmt->execute([
                                    ':employee_id' => $employeeId,
                                    ':leave_type_id' => $type['leave_type_id'],
                                    ':year' => $currentYear,
                                    ':opening' => $totalDays,
                                    ':current_month' => $currentMonth
                                ]);
                            } catch (PDOException $e) {
                                if ((int)$e->getCode() !== 1062) {
                                    throw $e;
                                }
                                $dupeStmt = $conn->prepare("UPDATE ta_leave_balances SET opening_balance = opening_balance + :credit, remaining_balance = remaining_balance + :credit, last_monthly_accrual_month = :current_month WHERE employee_id = :employee_id AND leave_type_id = :leave_type_id AND year = :year AND (last_monthly_accrual_month IS NULL OR last_monthly_accrual_month < :current_month)");
                                $dupeStmt->execute([
                                    ':credit' => $totalDays,
                                    ':current_month' => $currentMonth,
                                    ':employee_id' => $employeeId,
                                    ':leave_type_id' => $type['leave_type_id'],
                                    ':year' => $currentYear
                                ]);
                                if ($dupeStmt->rowCount() === 0) {
                                    $results['skipped']++;
                                    continue;
                                }
                            }
                        }

                        $results['credited'][] = [
                            'employee_id' => $employeeId,
                            'leave_type' => $type['leave_type_name'],
                            'months' => $months,
                            'days' => $totalDays,
                            'resulting_balance' => $resultingBalance,
                            'current_month' => $currentMonth
                        ];
                    }
                }

                $results['success'] = true;
                return $results;
            } finally {
                self::releaseLock($conn, 'ta_leave_accrual');
            }
        } catch (Exception $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
            return $results;
        }
    }

    private static function acquireLock(PDO $conn, $name)
    {
        $stmt = $conn->prepare("SELECT GET_LOCK(:name, 0)");
        $stmt->execute([':name' => $name]);
        $result = $stmt->fetchColumn();
        return ($result === '1' || $result === 1 || $result === true);
    }

    private static function releaseLock(PDO $conn, $name)
    {
        try {
            $stmt = $conn->prepare("SELECT RELEASE_LOCK(:name)");
            $stmt->execute([':name' => $name]);
        } catch (Throwable $e) {
            // Ignore lock release failures; the lock will be cleaned up by MySQL.
        }
    }

    private static function computeMonthlyCreditPlan(PDO $conn, $employeeId, $leaveTypeId, $daysPerMonth, $currentYear, $currentMonth, $fromMonth, $hireDate, DateTimeImmutable $now)
    {
        $basePlan = [
            'eligible' => false,
            'months' => [],
            'days' => 0.0,
            'resulting_balance' => 0.0
        ];

        $existingStmt = $conn->prepare("SELECT leave_balance_id, last_monthly_accrual_month, opening_balance, remaining_balance FROM ta_leave_balances WHERE employee_id = :employee_id AND leave_type_id = :leave_type_id AND year = :year LIMIT 1");
        $existingStmt->execute([':employee_id' => $employeeId, ':leave_type_id' => $leaveTypeId, ':year' => $currentYear]);
        $currentYearRow = $existingStmt->fetch(PDO::FETCH_ASSOC);

        $previousYear = $currentYear - 1;
        $prevRowStmt = $conn->prepare("SELECT leave_balance_id, last_monthly_accrual_month FROM ta_leave_balances WHERE employee_id = :employee_id AND leave_type_id = :leave_type_id AND year = :year LIMIT 1");
        $prevRowStmt->execute([':employee_id' => $employeeId, ':leave_type_id' => $leaveTypeId, ':year' => $previousYear]);
        $previousYearRow = $prevRowStmt->fetch(PDO::FETCH_ASSOC);

        $startMonth = null;
        if ($currentYearRow && !empty($currentYearRow['last_monthly_accrual_month'])) {
            $lastMonth = $currentYearRow['last_monthly_accrual_month'];
            $startMonth = self::nextMonth($lastMonth);
        } elseif ($previousYearRow) {
            $startMonth = '01';
        } else {
            $startMonth = $fromMonth ?: $currentMonth;
        }

        $yearStart = $currentYear . '-01';
        $yearEnd = $currentYear . '-12';
        $startMonth = self::clampMonthToRange($startMonth, $currentYear, $yearStart, $yearEnd, $currentMonth);

        if ($hireDate) {
            $hireDateObj = new DateTimeImmutable($hireDate, new DateTimeZone('Asia/Manila'));
            $hireMonth = $hireDateObj->format('Y-m');
            $startMonth = max($startMonth, $hireMonth, $yearStart);
        }

        $months = [];
        $loopMonth = $startMonth;
        while (self::compareMonth($loopMonth, $currentMonth) <= 0) {
            if ($currentYearRow && !empty($currentYearRow['last_monthly_accrual_month']) && self::compareMonth($loopMonth, $currentYearRow['last_monthly_accrual_month']) <= 0) {
                $loopMonth = self::nextMonth($loopMonth);
                continue;
            }

            if ($hireDate) {
                $hireDateObj = new DateTimeImmutable($hireDate, new DateTimeZone('Asia/Manila'));
                $hireMonth = $hireDateObj->format('Y-m');
                if (self::compareMonth($loopMonth, $hireMonth) < 0) {
                    $loopMonth = self::nextMonth($loopMonth);
                    continue;
                }
            }

            $months[] = $loopMonth;
            $loopMonth = self::nextMonth($loopMonth);
        }

        $months = array_values(array_unique($months));
        $totalDays = (float)count($months) * (float)$daysPerMonth;
        if ($totalDays <= 0 || empty($months)) {
            return $basePlan;
        }

        $currentBalance = (float)($currentYearRow['remaining_balance'] ?? 0.0);
        $resultingBalance = $currentBalance + $totalDays;

        return [
            'eligible' => true,
            'months' => $months,
            'days' => $totalDays,
            'resulting_balance' => $resultingBalance
        ];
    }

    private static function nextMonth($month)
    {
        $date = new DateTimeImmutable($month . '-01 00:00:00', new DateTimeZone('Asia/Manila'));
        return $date->modify('+1 month')->format('Y-m');
    }

    private static function compareMonth($left, $right)
    {
        if ($left === null || $right === null) {
            return 0;
        }
        return strcmp($left, $right);
    }

    private static function clampMonthToRange($month, $year, $yearStart, $yearEnd, $currentMonth)
    {
        if ($month === null || $month === '') {
            return $currentMonth;
        }

        if (strlen($month) !== 7 || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $currentMonth;
        }

        if ($month < $yearStart) {
            return $yearStart;
        }
        if ($month > $yearEnd) {
            return $yearEnd;
        }
        if ($month > $currentMonth) {
            return $currentMonth;
        }
        return $month;
    }
}
