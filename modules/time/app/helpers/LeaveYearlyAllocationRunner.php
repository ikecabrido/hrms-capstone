<?php
/**
 * Yearly leave allocation runner.
 *
 * Credits `days_per_year` for leave types with accrual_frequency = 'yearly'.
 * Idempotent: tracks `last_yearly_allocation_year` on `ta_leave_balances`
 * to avoid double-crediting. Safe to call repeatedly.
 */

require_once __DIR__ . '/../core/TimeDatabase.php';

class LeaveYearlyAllocationRunner
{
    public static function run()
    {
        $currentYear = (int)date('Y');
        $results = ['success' => false, 'credited' => [], 'skipped' => 0];

        try {
            $db = TimeDatabase::getInstance();
            $conn = $db->getConnection();

            // Only yearly-accrual types
            $typesStmt = $conn->prepare("SELECT leave_type_id, leave_type_name, days_per_year FROM ta_leave_types WHERE accrual_frequency = 'yearly' AND days_per_year > 0");
            $typesStmt->execute();
            $yearlyTypes = $typesStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($yearlyTypes)) {
                $results['success'] = true;
                return $results;
            }

            $employeesStmt = $conn->prepare("SELECT employee_id FROM em_employees WHERE LOWER(COALESCE(employment_status, '')) = 'active'");
            $employeesStmt->execute();
            $employees = $employeesStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($employees as $employeeId) {
                foreach ($yearlyTypes as $type) {
                    $leaveTypeId = $type['leave_type_id'];
                    $creditDays = (float)$type['days_per_year'];

                    $existingStmt = $conn->prepare("SELECT leave_balance_id, remaining_balance, opening_balance, last_yearly_allocation_year FROM ta_leave_balances WHERE employee_id = :employee_id AND leave_type_id = :leave_type_id AND year = :year");
                    $existingStmt->execute([':employee_id' => $employeeId, ':leave_type_id' => $leaveTypeId, ':year' => $currentYear]);
                    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        if ((string)($existing['last_yearly_allocation_year'] ?? '') === (string)$currentYear) {
                            $results['skipped']++;
                            continue;
                        }

                        $updateStmt = $conn->prepare("UPDATE ta_leave_balances SET opening_balance = COALESCE(opening_balance, 0) + :credit, remaining_balance = COALESCE(remaining_balance, opening_balance, 0) + :credit, last_yearly_allocation_year = :year, updated_at = NOW() WHERE leave_balance_id = :leave_balance_id");
                        $updateStmt->execute([':credit' => $creditDays, ':year' => (string)$currentYear, ':leave_balance_id' => $existing['leave_balance_id']]);
                    } else {
                        $insertStmt = $conn->prepare("INSERT INTO ta_leave_balances (employee_id, leave_type_id, year, opening_balance, used_balance, remaining_balance, last_yearly_allocation_year, created_at, updated_at) VALUES (:employee_id, :leave_type_id, :year, :opening, 0, :opening, :year_marker, NOW(), NOW())");
                        $insertStmt->execute([':employee_id' => $employeeId, ':leave_type_id' => $leaveTypeId, ':year' => $currentYear, ':opening' => $creditDays, ':year_marker' => (string)$currentYear]);
                    }

                    $results['credited'][] = ['employee_id' => $employeeId, 'leave_type' => $type['leave_type_name'], 'days' => $creditDays];
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
