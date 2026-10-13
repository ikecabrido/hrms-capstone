<?php
/**
 * Monthly leave accrual runner. Grants monthly_accrual_days to every active
 * employee's balance for leave types with accrual_frequency = 'monthly'
 * (currently Vacation and Sick Leave), once per calendar month, accumulating
 * on top of any existing unused balance (no reset/expiry).
 * Safe to call repeatedly - tracks last_monthly_accrual_month per balance row
 * to prevent crediting the same month twice.
 */

require_once __DIR__ . '/../core/TimeDatabase.php';

class LeaveAccrualRunner
{
    public static function run()
    {
        $currentMonth = date('Y-m');
        $currentYear = (int)date('Y');
        $results = ['success' => false, 'credited' => [], 'skipped' => 0];

        try {
            $db = TimeDatabase::getInstance();
            $conn = $db->getConnection();

            $typesStmt = $conn->prepare("SELECT leave_type_id, leave_type_name, monthly_accrual_days FROM ta_leave_types WHERE accrual_frequency = 'monthly' AND monthly_accrual_days IS NOT NULL");
            $typesStmt->execute();
            $monthlyTypes = $typesStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($monthlyTypes)) {
                $results['success'] = true;
                return $results;
            }

            $employeesStmt = $conn->prepare("SELECT employee_id FROM em_employees WHERE employment_status = 'Active'");
            $employeesStmt->execute();
            $employees = $employeesStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($employees as $employeeId) {
                foreach ($monthlyTypes as $type) {
                    $leaveTypeId = $type['leave_type_id'];
                    $accrualDays = (float)$type['monthly_accrual_days'];

                    $existingStmt = $conn->prepare("SELECT leave_balance_id, remaining_balance, opening_balance, last_monthly_accrual_month FROM ta_leave_balances WHERE employee_id = :employee_id AND leave_type_id = :leave_type_id AND year = :year");
                    $existingStmt->execute([':employee_id' => $employeeId, ':leave_type_id' => $leaveTypeId, ':year' => $currentYear]);
                    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        if ($existing['last_monthly_accrual_month'] === $currentMonth) {
                            $results['skipped']++;
                            continue;
                        }
                        $updateStmt = $conn->prepare("UPDATE ta_leave_balances SET remaining_balance = remaining_balance + :accrual, opening_balance = opening_balance + :accrual, last_monthly_accrual_month = :month WHERE leave_balance_id = :leave_balance_id");
                        $updateStmt->execute([':accrual' => $accrualDays, ':month' => $currentMonth, ':leave_balance_id' => $existing['leave_balance_id']]);
                    } else {
                        $insertStmt = $conn->prepare("INSERT INTO ta_leave_balances (employee_id, leave_type_id, year, opening_balance, used_balance, remaining_balance, last_monthly_accrual_month) VALUES (:employee_id, :leave_type_id, :year, :accrual, 0, :accrual, :month)");
                        $insertStmt->execute([':employee_id' => $employeeId, ':leave_type_id' => $leaveTypeId, ':year' => $currentYear, ':accrual' => $accrualDays, ':month' => $currentMonth]);
                    }

                    $results['credited'][] = ['employee_id' => $employeeId, 'leave_type' => $type['leave_type_name'], 'days' => $accrualDays];
                }
            }

            $results['success'] = true;
        } catch (Exception $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
        }

        return $results;
    }
}
