<?php
/**
 * Bulk Policy Assignment Script
 *
 * Assigns all Published policies (requires_acknowledgement = 1) to all Active
 * employees who do not already have an assignment.
 *
 * Usage (CLI):
 *   php modules/compliance/classes/bulk_assign_policies.php
 *
 * Usage (Browser):
 *   http://127.0.0.1/hrms-capstone/modules/compliance/classes/bulk_assign_policies.php
 *
 * Safety:
 *   - Prevents duplicate assignments via UNIQUE KEY (policy_id, employee_id)
 *   - Only assigns to Active, non-archived employees
 *   - Only assigns Published policies that require acknowledgement
 */

require_once __DIR__ . '/../../../database/db.php';

$db = (new Database())->getConnection();
if (!$db instanceof PDO) {
    throw new RuntimeException('Database connection could not be established.');
}
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$log = [];

try {
    $db->beginTransaction();

    // ------------------------------------------------------------------
    // 1. Count current assignments for baseline reporting
    // ------------------------------------------------------------------
    $beforeCount = (int) $db->query("SELECT COUNT(*) FROM lc_policy_assignments")->fetchColumn();

    // ------------------------------------------------------------------
    // 2. Build bulk insert from valid policy-employee pairs
    // ------------------------------------------------------------------
    $sql = "
        INSERT INTO lc_policy_assignments
            (policy_id, employee_id, due_date, status, reminder_count, assigned_at)
        SELECT
            p.id,
            e.employee_id,
            COALESCE(p.acknowledgement_deadline, DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
            'Pending',
            0,
            NOW()
        FROM lc_policies p
        CROSS JOIN em_employees e
        WHERE p.status                 = 'Published'
          AND p.requires_acknowledgement = 1
          AND e.employment_status      = 'Active'
          AND e.is_archived            = 0
          AND NOT EXISTS (
              SELECT 1
              FROM lc_policy_assignments pa
              WHERE pa.policy_id   = p.id
                AND pa.employee_id = e.employee_id
          )
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    $inserted = $stmt->rowCount();

    // ------------------------------------------------------------------
    // 3. Fetch assignment details for reporting
    // ------------------------------------------------------------------
    $afterCount = (int) $db->query("SELECT COUNT(*) FROM lc_policy_assignments")->fetchColumn();

    $log[] = sprintf(
        "Policy assignment complete: %d new assignment(s) created (%d -> %d total).",
        $inserted,
        $beforeCount,
        $afterCount
    );

    // Show sample of what was assigned
    if ($inserted > 0) {
        $sample = $db->query("
            SELECT pa.id, p.title, CONCAT(e.first_name, ' ', e.last_name) AS employee_name, pa.due_date
            FROM lc_policy_assignments pa
            JOIN lc_policies p ON p.id = pa.policy_id
            JOIN em_employees e ON e.employee_id = pa.employee_id
            ORDER BY pa.id DESC
            LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);

        $log[] = "\nSample assignments (most recent 20):";
        $log[] = str_repeat('-', 80);
        $log[] = sprintf('%-4s %-35s %-25s %s', 'ID', 'Policy', 'Employee', 'Due Date');
        $log[] = str_repeat('-', 80);

        foreach ($sample as $row) {
            $log[] = sprintf(
                '%-4s %-35s %-25s %s',
                $row['id'],
                substr($row['title'], 0, 34),
                substr($row['employee_name'], 0, 24),
                $row['due_date']
            );
        }
    }

    $db->commit();

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $log[] = "ERROR: " . $e->getMessage();
}

// ------------------------------------------------------------------
// Output
// ------------------------------------------------------------------
if (PHP_SAPI === 'cli') {
    echo implode(PHP_EOL, $log) . PHP_EOL;
} else {
    echo '<!DOCTYPE html><html><head><title>Policy Assignment</title>';
    echo '<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}';
    echo '.log{background:#fff;padding:16px;border-radius:8px;border:1px solid #ddd;white-space:pre-wrap;font-size:13px;}';
    echo '.error{color:#a3272a;}.success{color:#1f7a52;}</style></head><body>';
    echo '<div class="log">' . htmlspecialchars(implode("\n", $log)) . '</div>';
    echo '</body></html>';
}
