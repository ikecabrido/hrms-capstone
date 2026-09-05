<?php

require_once __DIR__ . '/../../../database/db.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();

if (!$db) {
    die('Database connection failed.');
}

$employees = [];
try {
    $stmt = $db->query("SELECT employee_id, department_id, employment_status FROM em_employees WHERE employment_status = 'Active'");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Failed to fetch employees: ' . $e->getMessage());
}

if (!$employees) {
    die('No active employees found.');
}

$db->beginTransaction();

try {
    $db->exec("DELETE FROM lc_compliance_summary");

    $insert = $db->prepare("
        INSERT INTO lc_compliance_summary
            (employee_id, department_id, employment_score, leave_score, benefits_score,
             working_conditions_score, workplace_protection_score, data_privacy_score,
             overall_score, status, critical_issues, high_risks, medium_risks, low_risks,
             last_checked, created_at, updated_at)
        VALUES
            (:employee_id, :department_id, :employment_score, :leave_score, :benefits_score,
             :working_conditions_score, :workplace_protection_score, :data_privacy_score,
             :overall_score, :status, :critical_issues, :high_risks, :medium_risks, :low_risks,
             NOW(), NOW(), NOW())
    ");

    foreach ($employees as $emp) {
        $eid = (int) $emp['employee_id'];
        $deptId = $emp['department_id'] !== null ? (int) $emp['department_id'] : null;

        $employmentScore = 0;
        try {
            $activeContract = (int) $db->query("SELECT COUNT(*) FROM lc_contracts WHERE employee_id = {$eid} AND status = 'Active'")->fetchColumn();
            $employmentScore = $activeContract > 0 ? 100 : 0;
        } catch (Exception $e) {
            $employmentScore = 50;
        }

        $documentScore = 0;
        try {
            $totalDocs = (int) $db->query("SELECT COUNT(*) FROM lc_employee_documents WHERE employee_id = {$eid}")->fetchColumn();
            $verifiedDocs = (int) $db->query("SELECT COUNT(*) FROM lc_employee_documents WHERE employee_id = {$eid} AND verification_status = 'Verified' AND compliance_status != 'Expired'")->fetchColumn();
            $documentScore = $totalDocs > 0 ? (int) round(($verifiedDocs / $totalDocs) * 100) : 0;
        } catch (Exception $e) {
            $documentScore = 0;
        }

        $benefitsScore = 0;
        try {
            $verifiedBenefits = 0;
            $totalBenefits = 0;
            foreach (['sss','philhealth','pagibig','bir'] as $tbl) {
                $cnt = (int) $db->query("SELECT COUNT(*) FROM lc_{$tbl}_contributions WHERE employee_id = {$eid}")->fetchColumn();
                $totalBenefits += $cnt;
            }
            $benefitsScore = $totalBenefits > 0 ? 100 : 0;
        } catch (Exception $e) {
            $benefitsScore = 0;
        }

        $leaveScore = 0;
        try {
            $overdueLeaves = (int) $db->query("SELECT COUNT(*) FROM lc_compliance_items WHERE employee_id = {$eid} AND category = 'Leave' AND status = 'Overdue'")->fetchColumn();
            $totalLeaves = (int) $db->query("SELECT COUNT(*) FROM lc_compliance_items WHERE employee_id = {$eid} AND category = 'Leave'")->fetchColumn();
            $leaveScore = $totalLeaves > 0 ? (int) round((($totalLeaves - $overdueLeaves) / $totalLeaves) * 100) : 100;
        } catch (Exception $e) {
            $leaveScore = 100;
        }

        $workingConditionsScore = 100;
        try {
            $openIncidents = (int) $db->query("SELECT COUNT(*) FROM lc_incident_report WHERE assigned_to = {$eid} AND status NOT IN ('resolved','closed')")->fetchColumn();
            $workingConditionsScore = $openIncidents > 0 ? (int) max(0, 100 - ($openIncidents * 15)) : 100;
        } catch (Exception $e) {
            $workingConditionsScore = 100;
        }

        $workplaceProtectionScore = 100;
        try {
            $activeRisks = (int) $db->query("SELECT COUNT(*) FROM lc_risks WHERE employee_id = {$eid} AND archived = 0 AND status NOT IN ('resolved','closed')")->fetchColumn();
            $workplaceProtectionScore = $activeRisks > 0 ? (int) max(0, 100 - ($activeRisks * 10)) : 100;
        } catch (Exception $e) {
            $workplaceProtectionScore = 100;
        }

        $dataPrivacyScore = 100;
        try {
            $overduePrivacy = (int) $db->query("SELECT COUNT(*) FROM lc_compliance_items WHERE employee_id = {$eid} AND category = 'Data Privacy' AND status = 'Overdue'")->fetchColumn();
            $dataPrivacyScore = $overduePrivacy > 0 ? (int) max(0, 100 - ($overduePrivacy * 20)) : 100;
        } catch (Exception $e) {
            $dataPrivacyScore = 100;
        }

        $scores = [$employmentScore, $documentScore, $leaveScore, $benefitsScore, $workingConditionsScore, $workplaceProtectionScore, $dataPrivacyScore];
        $overallScore = (int) round(array_sum($scores) / count($scores));

        $status = 'compliant';
        if ($overallScore < 75) {
            $status = 'non_compliant';
        } elseif ($overallScore < 90) {
            $status = 'at_risk';
        }

        $criticalIssues = 0;
        $highRisks = 0;
        $mediumRisks = 0;
        $lowRisks = 0;
        try {
            $criticalIssues = (int) $db->query("SELECT COUNT(*) FROM lc_risks WHERE employee_id = {$eid} AND severity = 'Critical' AND archived = 0")->fetchColumn();
            $highRisks = (int) $db->query("SELECT COUNT(*) FROM lc_risks WHERE employee_id = {$eid} AND severity = 'High' AND archived = 0")->fetchColumn();
            $mediumRisks = (int) $db->query("SELECT COUNT(*) FROM lc_risks WHERE employee_id = {$eid} AND severity = 'Medium' AND archived = 0")->fetchColumn();
            $lowRisks = (int) $db->query("SELECT COUNT(*) FROM lc_risks WHERE employee_id = {$eid} AND severity = 'Low' AND archived = 0")->fetchColumn();
        } catch (Exception $e) {}

        $insert->execute([
            ':employee_id' => $eid,
            ':department_id' => $deptId,
            ':employment_score' => $employmentScore,
            ':leave_score' => $leaveScore,
            ':benefits_score' => $benefitsScore,
            ':working_conditions_score' => $workingConditionsScore,
            ':workplace_protection_score' => $workplaceProtectionScore,
            ':data_privacy_score' => $dataPrivacyScore,
            ':overall_score' => $overallScore,
            ':status' => $status,
            ':critical_issues' => $criticalIssues,
            ':high_risks' => $highRisks,
            ':medium_risks' => $mediumRisks,
            ':low_risks' => $lowRisks,
        ]);
    }

    $db->commit();
    echo "Compliance summary generated for " . count($employees) . " employees.\n";
} catch (Exception $e) {
    $db->rollBack();
    die('Failed to generate compliance summary: ' . $e->getMessage());
}
