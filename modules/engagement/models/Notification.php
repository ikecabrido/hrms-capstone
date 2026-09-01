<?php
namespace App\Models;

class Notification extends BaseModel
{
    public function notifyEmployees(array $employeeIds, string $message, string $type = 'info', ?string $title = null)
    {
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));
        $storedMessage = $title ? $title . ': ' . $message : $message;
        foreach ($employeeIds as $employeeId) {
            $this->execute(
                'INSERT INTO eer_notifications (employee_id, message, type, is_read, created_at)
                 VALUES (:employee_id, :message, :type, 0, NOW())',
                [
                    'employee_id' => $employeeId,
                    'message' => $storedMessage,
                    'type' => $type
                ]
            );
        }
        return count($employeeIds);
    }

    public function notifyHr(string $message, string $type = 'info', array $excludeEmployeeIds = [])
    {
        $employees = $this->execute(
            "SELECT DISTINCT e.employee_id
             FROM em_employees e
             LEFT JOIN em_departments d ON d.department_id = e.department_id
             LEFT JOIN em_positions p ON p.position_id = e.position_id
             WHERE LOWER(d.department_name) IN ('hr', 'human resources', 'employee relations')
                OR LOWER(p.position_name) LIKE '%hr%'
                OR LOWER(p.position_name) LIKE '%human resource%'",
            []
        )->fetchAll();
        $employeeIds = array_column($employees, 'employee_id');
        $employeeIds = array_diff($employeeIds, array_map('intval', $excludeEmployeeIds));
        return $this->notifyEmployees($employeeIds, $message, $type);
    }

    public function getAll()
    {
        $nameSql = $this->getEmployeeNameSql('he', 'employee_name');
        $sql = "SELECT n.*, he.employee_id AS hr_employee_id, $nameSql
            FROM eer_notifications n
                LEFT JOIN em_employees he ON he.employee_id = n.employee_id
                ORDER BY n.created_at DESC";
        return $this->execute($sql)->fetchAll();
    }

    public function markAsRead($notification_id)
    {
        $sql = 'UPDATE eer_notifications SET is_read = 1 WHERE id = :id';
        $this->execute($sql, ['id' => $notification_id]);
    }

    public function postHRNotification($title, $content, $notificationType, $targetEmployees)
    {
        // Combine title and content for the message
        $message = $title . ': ' . $content;
        
        // Build the target employees list based on selections
        $selectedEmployees = [];
        
        if (!empty($targetEmployees) && is_array($targetEmployees)) {
            foreach ($targetEmployees as $target) {
                if ($target === 'all') {
                    // Get all employee IDs from em_employees
                    $sql = 'SELECT employee_id FROM em_employees WHERE employee_id IS NOT NULL';
                    $results = $this->execute($sql)->fetchAll();
                    foreach ($results as $row) {
                        $selectedEmployees[] = $row['employee_id'];
                    }
                    break; // No need to process other selections if "all" is selected
                } elseif ($target === 'management') {
                    // Get management employees - join with em_positions to check for management roles
                    $sql = 'SELECT e.employee_id FROM em_employees e 
                            LEFT JOIN em_positions p ON e.position_id = p.position_id 
                            WHERE p.position_name IN ("Manager", "Supervisor", "Director", "Head") OR e.employment_status = "Management"';
                    $results = $this->execute($sql)->fetchAll();
                    foreach ($results as $row) {
                        $selectedEmployees[] = $row['employee_id'];
                    }
                } elseif (!empty($target)) {
                    // Treat as department name - get employees from that department
                    $sql = 'SELECT e.employee_id FROM em_employees e 
                            LEFT JOIN em_departments d ON e.department_id = d.department_id 
                            WHERE d.department_name = :department';
                    $results = $this->execute($sql, ['department' => $target])->fetchAll();
                    foreach ($results as $row) {
                        $selectedEmployees[] = $row['employee_id'];
                    }
                } elseif (is_numeric($target)) {
                    // Direct employee ID
                    $selectedEmployees[] = (int)$target;
                }
            }
        }
        
        // Remove duplicates
        $selectedEmployees = array_unique($selectedEmployees);
        
        if (empty($selectedEmployees)) {
            // If no selection or no results, send to all (use em_employees)
            $this->notifyEmployees(
                array_column($this->execute('SELECT employee_id FROM em_employees WHERE employee_id IS NOT NULL')->fetchAll(), 'employee_id'),
                $message,
                $notificationType
            );
        } else {
            // Send to selected employees
            foreach ($selectedEmployees as $employeeId) {
                if ($employeeId > 0) {
                    $this->notifyEmployees([(int)$employeeId], $message, $notificationType);
                }
            }
        }
    }
}