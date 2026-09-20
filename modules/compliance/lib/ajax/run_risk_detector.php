<?php

require_once __DIR__ . '/../../../../auth/session.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$scope = $input['scope'] ?? '';

try {
    $db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($scope !== 'all_departments') {
        throw new Exception('Invalid scope. Only "all_departments" is supported.');
    }

    $today = date('Y-m-d');
    $totalNew = 0;
    $totalExisting = 0;
    $warnings = [];
    $departments = [];
    $allRisks = [];

    function tableExists(PDO $db, string $table): bool {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
            $stmt->execute([$table]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    function addRisk(array &$risks, array $risk): void {
        $risk['detected_at'] = date('Y-m-d H:i:s');
        $risks[] = $risk;
    }

    function isDuplicate(PDO $db, array $risk): bool {
        $sql = "SELECT id FROM lc_risks 
                WHERE employee_id = :employee_id 
                AND risk_type = :risk_type 
                AND source_module = :source_module 
                AND detection_rule = :detection_rule 
                AND source_record_id = :source_record_id 
                AND archived = 0
                AND status IN ('new_report', 'under_review')
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':employee_id' => $risk['employee_id'],
            ':risk_type' => $risk['risk_type'],
            ':source_module' => $risk['source_module'],
            ':detection_rule' => $risk['detection_rule'],
            ':source_record_id' => $risk['source_record_id'] ?? 0,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    function insertRisk(PDO $db, array $risk, int &$totalNew, int &$totalExisting): void {
        if (isDuplicate($db, $risk)) {
            $totalExisting++;
            return;
        }

        $sql = "INSERT INTO lc_risks 
            (employee_id, risk_type, severity, description, mitigation_plan, status, archived, source_module, source_record_id, detection_rule, detected_at, affected_record_count, suggested_likelihood, suggested_impact, created_at, updated_at) 
            VALUES 
            (:employee_id, :risk_type, :severity, :description, :mitigation_plan, :status, 0, :source_module, :source_record_id, :detection_rule, :detected_at, :affected_record_count, :suggested_likelihood, :suggested_impact, NOW(), NOW())";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':employee_id' => $risk['employee_id'],
            ':risk_type' => $risk['risk_type'],
            ':severity' => $risk['severity'],
            ':description' => $risk['description'],
            ':mitigation_plan' => $risk['mitigation_plan'],
            ':status' => 'new_report',
            ':source_module' => $risk['source_module'],
            ':source_record_id' => $risk['source_record_id'] ?? 0,
            ':detection_rule' => $risk['detection_rule'],
            ':detected_at' => date('Y-m-d H:i:s'),
            ':affected_record_count' => $risk['affected_record_count'] ?? 1,
            ':suggested_likelihood' => $risk['suggested_likelihood'] ?? 3,
            ':suggested_impact' => $risk['suggested_impact'] ?? 3,
        ]);
        $totalNew++;
    }

    // =====================================================================
    // 1. RECRUITMENT & ONBOARDING RISK DETECTION
    // =====================================================================
    $recruitmentOnboardingCount = 0;
    if (tableExists($db, 'rao_hired')) {
        try {
            $stmt = $db->query("SELECT id, first_name, last_name, email, phone, address, position, department, hired_at FROM rao_hired WHERE hired_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)");
            $hired = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($hired as $row) {
                $issues = [];
                if (empty($row['email']) || strpos($row['email'], '@') === false) $issues[] = 'missing or invalid email';
                if (empty($row['phone'])) $issues[] = 'missing contact number';
                if (empty($row['address'])) $issues[] = 'missing address';
                
                if (!empty($issues)) {
                    addRisk($allRisks, [
                        'employee_id' => null,
                        'risk_type' => 'Incomplete Recruitment Record',
                        'severity' => 'Medium',
                        'description' => 'Hired candidate ' . $row['first_name'] . ' ' . $row['last_name'] . ' for ' . $row['position'] . ' has ' . implode(', ', $issues) . '.',
                        'mitigation_plan' => 'Complete new hire onboarding requirements and verify contact information.',
                        'source_module' => 'rao_hired',
                        'source_record_id' => $row['id'],
                        'detection_rule' => 'incomplete_recruitment_record',
                        'affected_record_count' => 1,
                        'suggested_likelihood' => 3,
                        'suggested_impact' => 3,
                    ]);
                    $recruitmentOnboardingCount++;
                }
            }
        } catch (Throwable $e) {
            $warnings[] = 'Recruitment detector error: ' . $e->getMessage();
        }
    } else {
        $warnings[] = 'Recruitment detector skipped: rao_hired table not available.';
    }

    if (tableExists($db, 'employee_requirements')) {
        try {
            $stmt = $db->prepare("SELECT requirement_id, employee_id, requirement_name, status, follow_up_date FROM employee_requirements WHERE status = 'Missing' AND (follow_up_date IS NULL OR follow_up_date <= :today)");
            $stmt->execute([':today' => $today]);
            $missing = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($missing as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Incomplete Onboarding Requirements',
                    'severity' => 'Medium',
                    'description' => 'Employee has missing onboarding requirement: ' . $row['requirement_name'] . '.',
                    'mitigation_plan' => 'Complete missing onboarding requirements and verify required documents.',
                    'source_module' => 'employee_requirements',
                    'source_record_id' => $row['requirement_id'],
                    'detection_rule' => 'missing_onboarding_requirement',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 3,
                    'suggested_impact' => 3,
                ]);
                $recruitmentOnboardingCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Onboarding detector error: ' . $e->getMessage();
        }
    } else {
        $warnings[] = 'Onboarding detector skipped: employee_requirements table not available.';
    }
    $departments['recruitment_onboarding'] = $recruitmentOnboardingCount;

    // =====================================================================
    // 3. EMPLOYEE MANAGEMENT RISK DETECTION
    // =====================================================================
    $employeeManagementCount = 0;
    if (tableExists($db, 'em_employees')) {
        try {
            // Check for missing emergency contacts
            if (tableExists($db, 'personal_information')) {
                $stmt = $db->prepare("SELECT pi.employee_id, CONCAT(e.first_name, ' ', e.last_name) AS full_name FROM personal_information pi JOIN em_employees e ON e.employee_id = pi.employee_id WHERE pi.emergency_contact_name IS NULL OR pi.emergency_contact_name = '' OR pi.emergency_contact_number IS NULL OR pi.emergency_contact_number = ''");
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    addRisk($allRisks, [
                        'employee_id' => $row['employee_id'],
                        'risk_type' => 'Missing Emergency Contact',
                        'severity' => 'Low',
                        'description' => 'Employee ' . $row['full_name'] . ' has missing or incomplete emergency contact information.',
                        'mitigation_plan' => 'Update employee personal information with valid emergency contact details.',
                        'source_module' => 'personal_information',
                        'source_record_id' => $row['employee_id'],
                        'detection_rule' => 'missing_emergency_contact',
                        'affected_record_count' => 1,
                        'suggested_likelihood' => 2,
                        'suggested_impact' => 2,
                    ]);
                    $employeeManagementCount++;
                }
            }

            // Check for missing government IDs
            if (tableExists($db, 'em_government_ids')) {
                $stmt = $db->prepare("SELECT g.gov_id, g.employee_id, CONCAT(e.first_name, ' ', e.last_name) AS full_name FROM em_government_ids g JOIN em_employees e ON e.employee_id = g.employee_id WHERE g.sss_no IS NULL OR g.sss_no = '' OR g.philhealth_no IS NULL OR g.philhealth_no = '' OR g.pagibig_no IS NULL OR g.pagibig_no = '' OR g.tin_no IS NULL OR g.tin_no = ''");
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    addRisk($allRisks, [
                        'employee_id' => $row['employee_id'],
                        'risk_type' => 'Incomplete Employee Profile',
                        'severity' => 'Medium',
                        'description' => 'Employee ' . $row['full_name'] . ' has incomplete government ID information.',
                        'mitigation_plan' => 'Update employee government ID records with valid SSS, PhilHealth, Pag-IBIG, and TIN numbers.',
                        'source_module' => 'em_government_ids',
                        'source_record_id' => $row['gov_id'],
                        'detection_rule' => 'missing_government_ids',
                        'affected_record_count' => 1,
                        'suggested_likelihood' => 3,
                        'suggested_impact' => 3,
                    ]);
                    $employeeManagementCount++;
                }
            }

            // Check for expired employee documents
            if (tableExists($db, 'employee_documents')) {
                $stmt = $db->prepare("SELECT document_id, employee_id, document_name, document_type, expiry_date FROM employee_documents WHERE expiry_date IS NOT NULL AND expiry_date < :today AND verification_status != 'Expired'");
                $stmt->execute([':today' => $today]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    addRisk($allRisks, [
                        'employee_id' => $row['employee_id'],
                        'risk_type' => 'Expired Employee Document',
                        'severity' => 'High',
                        'description' => ucfirst($row['document_type']) . ' expired on ' . date('Y-m-d', strtotime($row['expiry_date'])) . ' for employee.',
                        'mitigation_plan' => 'Request updated document from employee and verify the new expiry date.',
                        'source_module' => 'employee_documents',
                        'source_record_id' => $row['document_id'],
                        'detection_rule' => 'expired_employee_document',
                        'affected_record_count' => 1,
                        'suggested_likelihood' => 4,
                        'suggested_impact' => 4,
                    ]);
                    $employeeManagementCount++;
                }
            }
        } catch (Throwable $e) {
            $warnings[] = 'Employee Management detector error: ' . $e->getMessage();
        }
    } else {
        $warnings[] = 'Employee Management detector skipped: em_employees table not available.';
    }
    $departments['employee_management'] = $employeeManagementCount;

    // =====================================================================
    // 4. PAYROLL RISK DETECTION
    // =====================================================================
    $payrollCount = 0;
    if (tableExists($db, 'pr_payslips')) {
        try {
            // Check for employees without recent payslips
            $stmt = $db->query("SELECT e.employee_id, CONCAT(e.first_name, ' ', e.last_name) AS full_name FROM em_employees e WHERE e.employment_status = 'Active' AND NOT EXISTS (SELECT 1 FROM pr_payslips p WHERE p.employee_id = e.employee_id AND p.generated_at >= DATE_SUB(NOW(), INTERVAL 60 DAY))");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Missing Payroll Record',
                    'severity' => 'High',
                    'description' => 'Active employee ' . $row['full_name'] . ' has no payslip generated in the last 60 days.',
                    'mitigation_plan' => 'Verify payroll processing and generate missing payslips.',
                    'source_module' => 'pr_payslips',
                    'source_record_id' => $row['employee_id'],
                    'detection_rule' => 'missing_payslip',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 4,
                    'suggested_impact' => 4,
                ]);
                $payrollCount++;
            }

            // Check for unusual payroll adjustments
            if (tableExists($db, 'pr_employee_adjustments')) {
                $stmt = $db->prepare("SELECT adjustment_id, employee_id, description, amount FROM pr_employee_adjustments WHERE amount > 10000 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $severity = $row['amount'] > 50000 ? 'Critical' : 'Medium';
                    $likelihood = $severity === 'Critical' ? 5 : 3;
                    $impact = $severity === 'Critical' ? 5 : 3;
                    addRisk($allRisks, [
                        'employee_id' => $row['employee_id'],
                        'risk_type' => 'Unusual Payroll Adjustment',
                        'severity' => $severity,
                        'description' => 'Large payroll adjustment of ' . number_format($row['amount'], 2) . ' for: ' . $row['description'] . '.',
                        'mitigation_plan' => 'Review and verify large payroll adjustments for accuracy.',
                        'source_module' => 'pr_employee_adjustments',
                        'source_record_id' => $row['adjustment_id'],
                        'detection_rule' => 'large_payroll_adjustment',
                        'affected_record_count' => 1,
                        'suggested_likelihood' => $likelihood,
                        'suggested_impact' => $impact,
                    ]);
                    $payrollCount++;
                }
            }
        } catch (Throwable $e) {
            $warnings[] = 'Payroll detector error: ' . $e->getMessage();
        }
    } else {
        $warnings[] = 'Payroll detector skipped: pr_payslips table not available.';
    }
    $departments['payroll'] = $payrollCount;

    // =====================================================================
    // 5. LEGAL & COMPLIANCE RISK DETECTION
    // =====================================================================
    $complianceCount = 0;

    // Missing policy acknowledgments
    if (tableExists($db, 'lc_policy_assignments') && tableExists($db, 'lc_policies')) {
        try {
            $stmt = $db->query("SELECT pa.id, pa.employee_id, p.title, pa.due_date FROM lc_policy_assignments pa JOIN lc_policies p ON p.id = pa.policy_id WHERE pa.status != 'Acknowledged' AND pa.due_date IS NOT NULL AND pa.due_date < DATE_SUB(NOW(), INTERVAL 14 DAY)");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $daysOverdue = (new DateTime())->diff(new DateTime($row['due_date']))->days;
                $severity = $daysOverdue > 30 ? 'Critical' : 'Medium';
                $likelihood = $severity === 'Critical' ? 5 : 3;
                $impact = $severity === 'Critical' ? 5 : 3;
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Missing Policy Acknowledgment',
                    'severity' => $severity,
                    'description' => 'Employee has not acknowledged policy: ' . $row['title'] . ' (overdue by ' . $daysOverdue . ' days).',
                    'mitigation_plan' => 'Send reminder to employee to acknowledge the required policy.',
                    'source_module' => 'lc_policy_assignments',
                    'source_record_id' => $row['id'],
                    'detection_rule' => 'missing_policy_acknowledgment',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => $likelihood,
                    'suggested_impact' => $impact,
                ]);
                $complianceCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Compliance policy detector error: ' . $e->getMessage();
        }
    }

    // Open incidents
    if (tableExists($db, 'lc_incident_report')) {
        try {
            $stmt = $db->query("SELECT id, assigned_to, incident_type, severity, status FROM lc_incident_report WHERE status IN ('submitted', 'under_review', 'investigation', 'escalated') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $sev = ucfirst($row['severity']);
                if ($sev === 'Critical' || $sev === 'High') {
                    addRisk($allRisks, [
                        'employee_id' => $row['assigned_to'],
                        'risk_type' => 'Open Compliance Incident',
                        'severity' => $sev === 'Critical' ? 'Critical' : 'High',
                        'description' => 'Open ' . strtolower($sev) . ' incident: ' . $row['incident_type'] . '.',
                        'mitigation_plan' => 'Review and resolve the open incident promptly.',
                        'source_module' => 'lc_incident_report',
                        'source_record_id' => $row['id'],
                        'detection_rule' => 'open_incident',
                        'affected_record_count' => 1,
                        'suggested_likelihood' => 4,
                        'suggested_impact' => 4,
                    ]);
                    $complianceCount++;
                }
            }
        } catch (Throwable $e) {
            $warnings[] = 'Compliance incident detector error: ' . $e->getMessage();
        }
    }

    // Overdue contributions
    if (tableExists($db, 'lc_sss_contributions')) {
        try {
            $stmt = $db->prepare("SELECT id, employee_id, status FROM lc_sss_contributions WHERE status IN ('Pending', 'Overdue', 'Rejected') AND created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Overdue Statutory Contribution',
                    'severity' => 'High',
                    'description' => 'SSS contribution status is ' . $row['status'] . ' for employee.',
                    'mitigation_plan' => 'Process overdue SSS contribution and update status.',
                    'source_module' => 'lc_sss_contributions',
                    'source_record_id' => $row['id'],
                    'detection_rule' => 'overdue_contribution',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 4,
                    'suggested_impact' => 4,
                ]);
                $complianceCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Compliance contribution detector error: ' . $e->getMessage();
        }
    }

    if (tableExists($db, 'lc_bir_contributions')) {
        try {
            $stmt = $db->prepare("SELECT id, employee_id, status FROM lc_bir_contributions WHERE status IN ('Pending', 'Overdue', 'Rejected') AND created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Overdue Tax Contribution',
                    'severity' => 'High',
                    'description' => 'BIR contribution status is ' . $row['status'] . ' for employee.',
                    'mitigation_plan' => 'Process overdue BIR contribution and update status.',
                    'source_module' => 'lc_bir_contributions',
                    'source_record_id' => $row['id'],
                    'detection_rule' => 'overdue_bir_contribution',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 4,
                    'suggested_impact' => 4,
                ]);
                $complianceCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'BIR contribution detector error: ' . $e->getMessage();
        }
    }

    $departments['compliance'] = $complianceCount;

    // =====================================================================
    // 6. EMPLOYEE PORTAL RISK DETECTION
    // =====================================================================
    $portalCount = 0;
    if (tableExists($db, 'eer_notifications')) {
        try {
            $stmt = $db->query("SELECT id, employee_id, message, is_read FROM eer_notifications WHERE is_read = 0 AND created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Unread Employee Notification',
                    'severity' => 'Low',
                    'description' => 'Employee has unread notification: ' . mb_substr($row['message'], 0, 100) . '...',
                    'mitigation_plan' => 'Follow up on pending employee notification or request.',
                    'source_module' => 'eer_notifications',
                    'source_record_id' => $row['id'],
                    'detection_rule' => 'unread_notification',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 2,
                    'suggested_impact' => 2,
                ]);
                $portalCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Employee portal detector error: ' . $e->getMessage();
        }
    }
    $departments['employee_portal'] = $portalCount;

    // =====================================================================
    // 7. ADMIN PORTAL RISK DETECTION
    // =====================================================================
    $adminCount = 0;
    if (tableExists($db, 'user_account') && tableExists($db, 'em_employees')) {
        try {
            $stmt = $db->query("SELECT u.user_id, e.employee_id, CONCAT(e.first_name, ' ', e.last_name) AS full_name FROM user_account u JOIN em_employees e ON e.employee_id = u.employee_id WHERE e.employment_status = 'Active' AND (u.account_status IS NULL OR u.account_status != 'active')");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Inactive Employee Account',
                    'severity' => 'Medium',
                    'description' => 'Active employee ' . $row['full_name'] . ' has an inactive user account.',
                    'mitigation_plan' => 'Reactivate or create user account for the employee.',
                    'source_module' => 'user_account',
                    'source_record_id' => $row['user_id'],
                    'detection_rule' => 'inactive_user_account',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 3,
                    'suggested_impact' => 3,
                ]);
                $adminCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Admin portal detector error: ' . $e->getMessage();
        }
    }
    $departments['admin_portal'] = $adminCount;

    // =====================================================================
    // 8. TIME AND ATTENDANCE RISK DETECTION
    // =====================================================================
    $attendanceCount = 0;
    if (tableExists($db, 'ta_attendance')) {
        try {
            // Frequent late arrivals
            $stmt = $db->prepare("SELECT employee_id, COUNT(*) AS late_count, SUM(late_minutes) AS total_late FROM ta_attendance WHERE status = 'LATE' AND attendance_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY employee_id HAVING late_count >= :threshold");
            $stmt->execute([':threshold' => 5]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Frequent Late',
                    'severity' => 'High',
                    'description' => 'Employee has ' . $row['late_count'] . ' late arrivals in the last 30 days with a total of ' . $row['total_late'] . ' minutes late.',
                    'mitigation_plan' => 'Conduct attendance counseling and monitor punctuality.',
                    'source_module' => 'ta_attendance',
                    'source_record_id' => $row['employee_id'],
                    'detection_rule' => 'frequent_late',
                    'affected_record_count' => (int) $row['late_count'],
                    'suggested_likelihood' => 4,
                    'suggested_impact' => 4,
                ]);
                $attendanceCount++;
            }

            // Excessive absences
            $stmt = $db->prepare("SELECT employee_id, COUNT(*) AS absent_count FROM ta_attendance WHERE status = 'ABSENT' AND attendance_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY employee_id HAVING absent_count >= :threshold");
            $stmt->execute([':threshold' => 3]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Excessive Absences',
                    'severity' => 'High',
                    'description' => 'Employee has ' . $row['absent_count'] . ' unexcused absences in the last 30 days.',
                    'mitigation_plan' => 'Issue notice to explain and require medical certificates or valid justification.',
                    'source_module' => 'ta_attendance',
                    'source_record_id' => $row['employee_id'],
                    'detection_rule' => 'excessive_absences',
                    'affected_record_count' => (int) $row['absent_count'],
                    'suggested_likelihood' => 4,
                    'suggested_impact' => 4,
                ]);
                $attendanceCount++;
            }

            // Excessive overtime
            $stmt = $db->prepare("SELECT employee_id, SUM(overtime_hours) AS total_ot FROM ta_attendance WHERE overtime_hours > 0 AND attendance_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY employee_id HAVING total_ot > :threshold");
            $stmt->execute([':threshold' => 20]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Excessive Overtime',
                    'severity' => 'Medium',
                    'description' => 'Employee has accumulated ' . number_format($row['total_ot'], 1) . ' overtime hours in the last 30 days.',
                    'mitigation_plan' => 'Review workload distribution and ensure compliance with labor regulations.',
                    'source_module' => 'ta_attendance',
                    'source_record_id' => $row['employee_id'],
                    'detection_rule' => 'excessive_overtime',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 3,
                    'suggested_impact' => 3,
                ]);
                $attendanceCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Attendance detector error: ' . $e->getMessage();
        }
    } else {
        $warnings[] = 'Attendance detector skipped: ta_attendance table not available.';
    }
    $departments['attendance'] = $attendanceCount;

    // =====================================================================
    // 9. WORKFORCE MANAGEMENT RISK DETECTION
    // =====================================================================
    $workforceCount = 0;
    if (tableExists($db, 'em_departments') && tableExists($db, 'em_positions') && tableExists($db, 'em_employees')) {
        try {
            $stmt = $db->query("SELECT d.department_id, d.department_name, COUNT(e.employee_id) AS emp_count, SUM(p.slot_count) AS total_slots FROM em_departments d LEFT JOIN em_positions p ON p.department_id = d.department_id AND p.status = 'Active' LEFT JOIN em_employees e ON e.department_id = d.department_id AND e.employment_status = 'Active' AND e.is_archived = 0 GROUP BY d.department_id, d.department_name");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($row['total_slots'] > 0 && $row['emp_count'] < $row['total_slots']) {
                    $fillRate = $row['emp_count'] / $row['total_slots'];
                    $severity = $fillRate < 0.3 ? 'Critical' : 'Medium';
                    $likelihood = $severity === 'Critical' ? 5 : 3;
                    $impact = $severity === 'Critical' ? 5 : 3;
                    addRisk($allRisks, [
                        'employee_id' => null,
                        'risk_type' => 'Department Understaffing',
                        'severity' => $severity,
                        'description' => 'Department ' . $row['department_name'] . ' is understaffed with ' . $row['emp_count'] . ' of ' . $row['total_slots'] . ' positions filled (' . number_format($fillRate * 100, 1) . '% fill rate).',
                        'mitigation_plan' => 'Initiate recruitment process to fill vacant positions.',
                        'source_module' => 'em_departments',
                        'source_record_id' => $row['department_id'],
                        'detection_rule' => 'department_understaffing',
                        'affected_record_count' => (int) ($row['total_slots'] - $row['emp_count']),
                        'suggested_likelihood' => $likelihood,
                        'suggested_impact' => $impact,
                    ]);
                    $workforceCount++;
                }
            }
        } catch (Throwable $e) {
            $warnings[] = 'Workforce detector error: ' . $e->getMessage();
        }
    } else {
        $warnings[] = 'Workforce detector skipped: required tables not available.';
    }
    $departments['workforce'] = $workforceCount;

    // =====================================================================
    // 10. PERFORMANCE MANAGEMENT RISK DETECTION
    // =====================================================================
    $performanceCount = 0;
    if (tableExists($db, 'pm_appraisals')) {
        try {
            $stmt = $db->prepare("SELECT appraisal_id, employee_id, employee_name, overall_rating, due_date FROM pm_appraisals WHERE overall_rating IS NOT NULL AND overall_rating < 3.0 AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Low Performance Rating',
                    'severity' => 'Medium',
                    'description' => 'Employee ' . $row['employee_name'] . ' has a low performance rating of ' . $row['overall_rating'] . '.',
                    'mitigation_plan' => 'Place employee under Performance Improvement Plan (PIP) and set measurable targets.',
                    'source_module' => 'pm_appraisals',
                    'source_record_id' => $row['appraisal_id'],
                    'detection_rule' => 'low_performance_rating',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 3,
                    'suggested_impact' => 3,
                ]);
                $performanceCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Performance detector error: ' . $e->getMessage();
        }
    }

    if (tableExists($db, 'pm_goals')) {
        try {
            $stmt = $db->prepare("SELECT goal_id, employee_id, goal_title, due_date, progress_percentage FROM pm_goals WHERE due_date < :today AND progress_percentage < 100 AND status NOT IN ('Completed', 'Cancelled')");
            $stmt->execute([':today' => $today]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Overdue Performance Goal',
                    'severity' => 'Medium',
                    'description' => 'Performance goal "' . $row['goal_title'] . '" is overdue with only ' . $row['progress_percentage'] . '% completion.',
                    'mitigation_plan' => 'Review goal progress and provide necessary support to complete the goal.',
                    'source_module' => 'pm_goals',
                    'source_record_id' => $row['goal_id'],
                    'detection_rule' => 'overdue_performance_goal',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 3,
                    'suggested_impact' => 3,
                ]);
                $performanceCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Performance goal detector error: ' . $e->getMessage();
        }
    }

    if (tableExists($db, 'kpi_entries')) {
        try {
            $stmt = $db->prepare("SELECT entry_id, assignment_id, performance_status FROM kpi_entries WHERE performance_status IN ('At Risk', 'Behind') AND entry_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => null,
                    'risk_type' => 'Failed KPI',
                    'severity' => 'High',
                    'description' => 'KPI entry status is "' . $row['performance_status'] . '" indicating poor performance.',
                    'mitigation_plan' => 'Review KPI performance and implement corrective action plan.',
                    'source_module' => 'kpi_entries',
                    'source_record_id' => $row['entry_id'],
                    'detection_rule' => 'failed_kpi',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 4,
                    'suggested_impact' => 4,
                ]);
                $performanceCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'KPI detector error: ' . $e->getMessage();
        }
    }

    $departments['performance'] = $performanceCount;

    // =====================================================================
    // 11. ENGAGEMENT MANAGEMENT RISK DETECTION
    // =====================================================================
    $engagementCount = 0;
    if (tableExists($db, 'eer_grievances')) {
        try {
            $stmt = $db->query("SELECT eer_grievance_id, employee_id, subject, priority, status FROM eer_grievances WHERE status IN ('pending', 'Escalated') AND created_at <= DATE_SUB(NOW(), INTERVAL 14 DAY)");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $priority = strtolower($row['priority'] ?? '');
                $severity = ($priority === 'high' || $priority === 'critical') ? 'Critical' : 'Medium';
                $likelihood = $severity === 'Critical' ? 5 : 3;
                $impact = $severity === 'Critical' ? 5 : 3;
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Unresolved Grievance',
                    'severity' => $severity,
                    'description' => 'Employee grievance is unresolved: ' . $row['subject'] . '. Priority: ' . $row['priority'],
                    'mitigation_plan' => 'Review and resolve employee grievance promptly.',
                    'source_module' => 'eer_grievances',
                    'source_record_id' => $row['eer_grievance_id'],
                    'detection_rule' => 'unresolved_grievance',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => $likelihood,
                    'suggested_impact' => $impact,
                ]);
                $engagementCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Engagement detector error: ' . $e->getMessage();
        }
    }

    if (tableExists($db, 'eer_survey_feedback')) {
        try {
            $stmt = $db->prepare("SELECT eer_survey_feedback_id, employee_id, rating, comment FROM eer_survey_feedback WHERE rating IS NOT NULL AND rating <= 2");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Low Engagement Score',
                    'severity' => 'Low',
                    'description' => 'Employee submitted low engagement rating: ' . $row['rating'] . '/5.',
                    'mitigation_plan' => 'Follow up with employee to understand concerns and improve engagement.',
                    'source_module' => 'eer_survey_feedback',
                    'source_record_id' => $row['eer_survey_feedback_id'],
                    'detection_rule' => 'low_engagement_score',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 2,
                    'suggested_impact' => 2,
                ]);
                $engagementCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Engagement survey detector error: ' . $e->getMessage();
        }
    }

    $departments['engagement'] = $engagementCount;

    // =====================================================================
    // 12. CLINIC / HEALTH RISK DETECTION
    // =====================================================================
    $clinicCount = 0;

    // Check for expired medical certificates in employee_documents
    if (tableExists($db, 'employee_documents')) {
        try {
            $stmt = $db->prepare("SELECT document_id, employee_id, document_name, expiry_date FROM employee_documents WHERE document_type IN ('Medical Certificate', 'Medical Clearance') AND expiry_date IS NOT NULL AND expiry_date < :today");
            $stmt->execute([':today' => $today]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Expired Medical Clearance',
                    'severity' => 'High',
                    'description' => 'Medical certificate expired on ' . date('Y-m-d', strtotime($row['expiry_date'])) . '.',
                    'mitigation_plan' => 'Request updated medical clearance from employee.',
                    'source_module' => 'employee_documents',
                    'source_record_id' => $row['document_id'],
                    'detection_rule' => 'expired_medical_clearance',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 4,
                    'suggested_impact' => 4,
                ]);
                $clinicCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Health detector error: ' . $e->getMessage();
        }
    }

    // Check for active emergency cases
    if (tableExists($db, 'cm_emergency_cases')) {
        try {
            $stmt = $db->query("SELECT case_id, patient_id, incident_type, severity_level, case_status FROM cm_emergency_cases WHERE case_status IN ('Active', 'Open') AND incident_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $sev = ucfirst($row['severity_level']);
                if ($sev === 'Critical' || $sev === 'High') {
                    addRisk($allRisks, [
                        'employee_id' => $row['patient_id'],
                        'risk_type' => 'Work Injury',
                        'severity' => $sev === 'Critical' ? 'Critical' : 'High',
                        'description' => 'Active ' . strtolower($sev) . ' emergency case: ' . $row['incident_type'] . '.',
                        'mitigation_plan' => 'Monitor case status and ensure proper medical follow-up.',
                        'source_module' => 'cm_emergency_cases',
                        'source_record_id' => $row['case_id'],
                        'detection_rule' => 'active_emergency_case',
                        'affected_record_count' => 1,
                        'suggested_likelihood' => 4,
                        'suggested_impact' => 5,
                    ]);
                    $clinicCount++;
                }
            }
        } catch (Throwable $e) {
            $warnings[] = 'Emergency case detector error: ' . $e->getMessage();
        }
    }

    $departments['clinic'] = $clinicCount;

    // =====================================================================
    // 13. EXIT MANAGEMENT RISK DETECTION
    // =====================================================================
    $exitCount = 0;

    if (tableExists($db, 'exit_resignations')) {
        try {
            $stmt = $db->query("SELECT id, employee_id, status, last_working_date FROM exit_resignations WHERE status IN ('pending_review', 'approved') AND last_working_date <= DATE_ADD(NOW(), INTERVAL 14 DAY)");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Pending Exit Clearance',
                    'severity' => 'Medium',
                    'description' => 'Employee resignation is pending clearance with last working date: ' . date('Y-m-d', strtotime($row['last_working_date'])) . '.',
                    'mitigation_plan' => 'Complete exit clearance process and verify returned assets.',
                    'source_module' => 'exit_resignations',
                    'source_record_id' => $row['id'],
                    'detection_rule' => 'pending_exit_clearance',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 3,
                    'suggested_impact' => 3,
                ]);
                $exitCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Exit detector error: ' . $e->getMessage();
        }
    }

    if (tableExists($db, 'exit_employee_settlements')) {
        try {
            $stmt = $db->query("SELECT settlement_id, employee_id, status FROM exit_employee_settlements WHERE status IN ('pending', 'requested', 'processing') AND created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Pending Final Settlement',
                    'severity' => 'Medium',
                    'description' => 'Exit settlement is in status: ' . $row['status'] . '.',
                    'mitigation_plan' => 'Complete final settlement processing and approve payment.',
                    'source_module' => 'exit_employee_settlements',
                    'source_record_id' => $row['settlement_id'],
                    'detection_rule' => 'pending_final_settlement',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 3,
                    'suggested_impact' => 3,
                ]);
                $exitCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Exit settlement detector error: ' . $e->getMessage();
        }
    }

    $departments['exit'] = $exitCount;

    // =====================================================================
    // 14. LEARNING AND DEVELOPMENT RISK DETECTION
    // =====================================================================
    $trainingCount = 0;

    if (tableExists($db, 'pm_employee_training')) {
        try {
            $stmt = $db->prepare("SELECT employee_training_id, employee_id, completion_status, end_date FROM pm_employee_training WHERE completion_status != 'Completed' AND end_date IS NOT NULL AND end_date < :today");
            $stmt->execute([':today' => $today]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $daysOverdue = (new DateTime())->diff(new DateTime($row['end_date']))->days;
                $severity = $daysOverdue > 60 ? 'Critical' : 'High';
                $likelihood = $severity === 'Critical' ? 5 : 4;
                $impact = $severity === 'Critical' ? 5 : 4;
                addRisk($allRisks, [
                    'employee_id' => $row['employee_id'],
                    'risk_type' => 'Overdue Mandatory Training',
                    'severity' => $severity,
                    'description' => 'Mandatory training is overdue by ' . $daysOverdue . ' days with status: ' . $row['completion_status'] . '.',
                    'mitigation_plan' => 'Enroll employee in required training and monitor completion.',
                    'source_module' => 'pm_employee_training',
                    'source_record_id' => $row['employee_training_id'],
                    'detection_rule' => 'overdue_training',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => $likelihood,
                    'suggested_impact' => $impact,
                ]);
                $trainingCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'Training detector error: ' . $e->getMessage();
        }
    }

    if (tableExists($db, 'ld_enrollment')) {
        try {
            $stmt = $db->prepare("SELECT id, learner_id, status FROM ld_enrollment WHERE status IN ('invited', 'enrolled', 'in_progress') AND last_accessed_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                addRisk($allRisks, [
                    'employee_id' => $row['learner_id'],
                    'risk_type' => 'Incomplete Training',
                    'severity' => 'Medium',
                    'description' => 'Employee training enrollment is incomplete with status: ' . $row['status'] . ' and no recent activity.',
                    'mitigation_plan' => 'Follow up with employee to complete the required training.',
                    'source_module' => 'ld_enrollment',
                    'source_record_id' => $row['id'],
                    'detection_rule' => 'incomplete_training',
                    'affected_record_count' => 1,
                    'suggested_likelihood' => 3,
                    'suggested_impact' => 3,
                ]);
                $trainingCount++;
            }
        } catch (Throwable $e) {
            $warnings[] = 'L&D enrollment detector error: ' . $e->getMessage();
        }
    }

    $departments['learning_development'] = $trainingCount;

    // =====================================================================
    // INSERT ALL RISKS WITH DUPLICATE PREVENTION
    // =====================================================================
    foreach ($allRisks as $risk) {
        insertRisk($db, $risk, $totalNew, $totalExisting);
    }

    $message = 'Risk detection completed across all HRMS departments.';
    if (!empty($warnings)) {
        $message = 'Risk detection completed with some module warnings.';
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'total_scanned' => 14,
        'total_detected' => count($allRisks),
        'total_new' => $totalNew,
        'total_existing' => $totalExisting,
        'warnings' => $warnings,
        'departments' => $departments,
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
