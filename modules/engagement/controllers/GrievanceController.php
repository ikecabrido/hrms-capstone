<?php
namespace App\Controllers;

use App\Models\Grievance;
use App\Models\GrievanceUpdate;
use App\Models\LcmIntegration;

class GrievanceController
{
    private $grievance;
    private $update;
    private $lcm;

    public function __construct()
    {
        $this->grievance = new Grievance();
        $this->update = new GrievanceUpdate();
        $this->lcm = new LcmIntegration();
    }

    public function getGrievances()
    {
        return $this->grievance->getGrievances();
    }

    public function getDepartments()
    {
        return $this->grievance->getDepartments();
    }

    public function getGrievanceById($id)
    {
        return $this->grievance->getGrievanceById($id);
    }

    public function hasTable($tableName)
    {
        return $this->grievance->tableExists($tableName);
    }

    public function hasColumn($tableName, $columnName)
    {
        return $this->grievance->columnExists($tableName, $columnName);
    }

    public function fileGrievance($employee_id, $subject, $description, $category = 'Workplace Conflict', $anonymous = 0, $attachment_path = null, $created_by_employee_id = null, $payslip_id = null, $payslip_information = null, $attendance_window_days = 7, $attendance_reference_date = null)
    {
        try {
            return $this->grievance->fileGrievance($employee_id, $subject, $description, $category, $anonymous, $attachment_path, $created_by_employee_id, $payslip_id, $payslip_information, $attendance_window_days, $attendance_reference_date);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function updateStatus($id, $status)
    {
        return $this->grievance->updateStatus($id, $status);
    }

    public function updateGrievanceManagement($id, array $data, $hrPersonnelId)
    {
        return $this->grievance->updateGrievanceManagement($id, $data, $hrPersonnelId);
    }

    public function updateResolution($id, $resolution_notes, $action_taken)
    {
        return $this->grievance->updateResolution($id, $resolution_notes, $action_taken);
    }

    public function submitSatisfaction($id, $rating, $comment)
    {
        return $this->grievance->submitSatisfaction($id, $rating, $comment);
    }

    public function addUpdate($grievance_id, $update_text, $updated_by_employee_id)
    {
        return $this->grievance->addGrievanceUpdate($grievance_id, $update_text, $updated_by_employee_id);
    }

    public function history($id)
    {
        return $this->update->getByGrievance($id);
    }

    public function getGrievanceReport($startDate, $endDate)
    {
        return $this->grievance->generateReport($startDate, $endDate);
    }

    public function addInvestigationNotes($id, $notes, $hrPersonnelId)
    {
        return $this->grievance->addInvestigationNotes($id, $notes, $hrPersonnelId);
    }

    public function markConfidential($id, $isConfidential)
    {
        return $this->grievance->updateConfidentialFlag($id, $isConfidential);
    }

    public function resolveGrievance($id, $resolution, $hrPersonnelId)
    {
        return $this->grievance->resolveGrievance($id, $resolution, $hrPersonnelId);
    }

    public function escalateGrievance($id, $escalationReason, $newLevel)
    {
        return $this->grievance->escalateGrievance($id, $escalationReason, $newLevel);
    }

    public function getGrievanceStats()
    {
        return $this->grievance->getGrievanceStats();
    }

    public function getComplianceRecords()
    {
        return $this->lcm->getComplianceRecords();
    }

    public function getComplianceRecord(int $recordId)
    {
        return $this->lcm->getComplianceRecord($recordId);
    }

    public function getAttendanceLinks($grievanceId)
    {
        return $this->grievance->getAttendanceLinksByGrievanceId($grievanceId);
    }

    public function getEmployeePayslips($employeeId)
    {
        return $this->grievance->getEmployeePayslips($employeeId);
    }

    public function getPayslipItems($employeeId, $payslipId)
    {
        return $this->grievance->getPayslipItems($employeeId, $payslipId);
    }

    public function getEmployeeAdjustments($employeeId, $payslipId = null)
    {
        return $this->grievance->getEmployeeAdjustments($employeeId, $payslipId);
    }

    public function getEmployeeBenefits($employeeId)
    {
        return $this->grievance->getEmployeeBenefits($employeeId);
    }

    public function getPayrollContext($employeeId, $payslipId = null)
    {
        return $this->grievance->getPayrollContext($employeeId, $payslipId);
    }

    public function getMyGrievances($employeeId)
    {
        return $this->grievance->getMyGrievances($employeeId);
    }

    public function getLcmIncidents($includeConfidential = false)
    {
        return $this->lcm->getIncidents($includeConfidential);
    }

    public function getLcmRisks($includeSensitive = false)
    {
        return $this->lcm->getRisks($includeSensitive);
    }

    public function getLcmRiskFlags($includeSensitive = false)
    {
        return $this->lcm->getRiskFlags($includeSensitive);
    }
}
