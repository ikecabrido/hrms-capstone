<?php
/**
 * Yearly leave allocation runner.
 *
 * Credits `days_per_year` for leave types with accrual_frequency = 'yearly'.
 * Idempotent: tracks `last_yearly_allocation_year` on `ta_leave_balances`
 * to avoid double-crediting. Safe to call repeatedly.
 */

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../core/TimeDatabase.php';

class LeaveYearlyAllocationRunner
{
    public static function run(array $options = [])
    {
        date_default_timezone_set('Asia/Manila');

        $dryRun = !empty($options['dry_run']);
        $nowTs = isset($options['now']) ? (int)$options['now'] : time();
        $currentYear = (int)(new DateTimeImmutable('@' . $nowTs, new DateTimeZone('Asia/Manila')))->format('Y');
        $results = ['success' => false, 'error' => null, 'credited' => [], 'skipped' => 0, 'months_backfilled' => 0];

        try {
            $db = TimeDatabase::getInstance();
            $conn = $db->getConnection();

            $lock = self::acquireLock($conn, 'ta_leave_yearly');
            if ($lock === false) {
                $results['success'] = true;
                $results['skipped_reason'] = 'locked';
                return $results;
            }

            try {
                $typesStmt = $conn->prepare("SELECT leave_type_id, leave_type_name, days_per_year FROM ta_leave_types WHERE accrual_frequency = 'yearly' AND days_per_year > 0");
                $typesStmt->execute();
                $yearlyTypes = $typesStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($yearlyTypes)) {
                    $results['success'] = true;
                    return $results;
                }

                $employeesStmt = $conn->prepare("SELECT employee_id FROM em_employees WHERE employment_status = 'Active'");
                $employeesStmt->execute();
                $employees = $employeesStmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($employees as $employeeId) {
                    foreach ($yearlyTypes as $type) {
                        $leaveTypeId = (int)$type['leave_type_id'];
                        $creditDays = (float)$type['days_per_year'];

                        $existingStmt = $conn->prepare("SELECT leave_balance_id, remaining_balance, opening_balance, last_yearly_allocation_year FROM ta_leave_balances WHERE employee_id = :employee_id AND leave_type_id = :leave_type_id AND year = :year LIMIT 1");
                        $existingStmt->execute([':employee_id' => $employeeId, ':leave_type_id' => $leaveTypeId, ':year' => $currentYear]);
                        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

                        if ($existing && ((string)($existing['last_yearly_allocation_year'] ?? '') === (string)$currentYear)) {
                            $results['skipped']++;
                            continue;
                        }

                        $resultingBalance = (float)($existing['remaining_balance'] ?? 0.0) + $creditDays;

                        if ($dryRun) {
                            $results['credited'][] = ['employee_id' => $employeeId, 'leave_type' => $type['leave_type_name'], 'days' => $creditDays, 'resulting_balance' => $resultingBalance, 'year' => $currentYear];
                            continue;
                        }

                        if ($existing) {
                            $updateStmt = $conn->prepare("UPDATE ta_leave_balances SET opening_balance = opening_balance + :credit, remaining_balance = remaining_balance + :credit, last_yearly_allocation_year = :year, updated_at = NOW() WHERE leave_balance_id = :leave_balance_id AND (last_yearly_allocation_year IS NULL OR last_yearly_allocation_year < :year)");
                            $updateStmt->execute([':credit' => $creditDays, ':year' => (string)$currentYear, ':leave_balance_id' => $existing['leave_balance_id']]);
                            if ($updateStmt->rowCount() === 0) {
                                $results['skipped']++;
                                continue;
                            }
                        } else {
                            $insertStmt = $conn->prepare("INSERT INTO ta_leave_balances (employee_id, leave_type_id, year, opening_balance, used_balance, remaining_balance, last_yearly_allocation_year, created_at, updated_at) VALUES (:employee_id, :leave_type_id, :year, :opening, 0, :opening, :year_marker, NOW(), NOW())");
                            try {
                                $insertStmt->execute([':employee_id' => $employeeId, ':leave_type_id' => $leaveTypeId, ':year' => $currentYear, ':opening' => $creditDays, ':year_marker' => (string)$currentYear]);
                            } catch (PDOException $e) {
                                if ((int)$e->getCode() !== 1062) {
                                    throw $e;
                                }
                                $dupeStmt = $conn->prepare("UPDATE ta_leave_balances SET opening_balance = opening_balance + :credit, remaining_balance = remaining_balance + :credit, last_yearly_allocation_year = :year, updated_at = NOW() WHERE employee_id = :employee_id AND leave_type_id = :leave_type_id AND year = :year AND (last_yearly_allocation_year IS NULL OR last_yearly_allocation_year < :year)");
                                $dupeStmt->execute([':credit' => $creditDays, ':year' => (string)$currentYear, ':employee_id' => $employeeId, ':leave_type_id' => $leaveTypeId]);
                                if ($dupeStmt->rowCount() === 0) {
                                    $results['skipped']++;
                                    continue;
                                }
                            }
                        }

                        $results['credited'][] = ['employee_id' => $employeeId, 'leave_type' => $type['leave_type_name'], 'days' => $creditDays, 'resulting_balance' => $resultingBalance, 'year' => $currentYear];
                    }
                }

                $results['success'] = true;
                return $results;
            } finally {
                self::releaseLock($conn, 'ta_leave_yearly');
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
}
