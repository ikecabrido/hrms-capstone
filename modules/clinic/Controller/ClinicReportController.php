<?php

require_once __DIR__ . '/../model/ClinicReportModel.php';

class ClinicReportController
{
    private $model;

    public function __construct($pdo)
    {
        $this->model = new ClinicReportModel($pdo);
    }

    public function getDashboardStats()
    {
        return [
            'total_employees' => $this->model->getTotalEmployees(),
            'total_patients' => $this->model->getTotalPatients(),
            'total_visits' => $this->model->getTotalVisits(),
            'total_records' => $this->model->getTotalMedicalRecords(),
            'pending_records' => $this->model->getPendingRecords(),
            'common_conditions' => $this->model->getCommonConditions(5),
            'emergency_summary' => $this->model->getEmergencyCasesSummary(),
            'recent_visits' => $this->model->getRecentVisits(8),
            'success' => true
        ];
    }

    public function searchReports($search = '', $department = '', $consultation_type = '', $start_date = '', $end_date = '', $page = 1, $per_page = 20)
    {
        $offset = ($page - 1) * $per_page;
        $reports = $this->model->searchPatientReports($search, $department, $consultation_type, $start_date, $end_date, $per_page, $offset);
        $total = $this->model->countPatientReports($search, $department, $consultation_type, $start_date, $end_date);

        return [
            'reports' => $reports,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'pages' => max(1, (int) ceil($total / $per_page)),
            'success' => true
        ];
    }

    public function getReportDetail($record_id)
    {
        $record = $this->model->getMedicalRecordDetail($record_id);
        if (!$record) {
            return [
                'success' => false,
                'message' => 'Medical record not found.'
            ];
        }

        $vital_signs = $this->model->getVitalSignsForRecord($record_id);

        return [
            'success' => true,
            'record' => $record,
            'vital_signs' => $vital_signs
        ];
    }

    public function getDepartments()
    {
        return [
            'departments' => $this->model->getDepartmentsList(),
            'success' => true
        ];
    }

    public function getEmployees()
    {
        return ['employees' => $this->model->getEmployeesList(), 'success' => true];
    }

    public function generatePeriodReport(array $filters, $generatedBy, $generatedByName)
    {
        if (!in_array($filters['report_type'], ['daily', 'weekly', 'monthly'], true)) throw new InvalidArgumentException('Invalid report type selected.');
        foreach (['date_from', 'date_to'] as $key) {
            if ($filters[$key] !== '') {
                $date = DateTime::createFromFormat('Y-m-d', $filters[$key]);
                if (!$date || $date->format('Y-m-d') !== $filters[$key]) throw new InvalidArgumentException('Please provide valid dates.');
            }
        }
        if ($filters['report_type'] === 'daily' && $filters['date_from'] === '') throw new InvalidArgumentException('Please select a date.');
        if ($filters['report_type'] === 'weekly' && ($filters['date_from'] === '' || $filters['date_to'] === '')) throw new InvalidArgumentException('Please select the week dates.');
        if ($filters['date_from'] !== '' && $filters['date_to'] !== '' && $filters['date_from'] > $filters['date_to']) throw new InvalidArgumentException('Date From cannot be later than Date To.');
        if ($filters['report_type'] === 'monthly' && (!ctype_digit($filters['month']) || (int) $filters['month'] < 1 || (int) $filters['month'] > 12 || !ctype_digit($filters['year']) || (int) $filters['year'] < 2000)) throw new InvalidArgumentException('Please select a valid month and year.');
        if ($filters['employee_id'] !== '' && !ctype_digit($filters['employee_id'])) throw new InvalidArgumentException('Invalid employee selected.');
        $report = $this->model->getPeriodReport($filters);
        $report['report_type'] = $filters['report_type'];
        $report['generated_by'] = $generatedByName;
        $report['generated_at'] = date('c');
        $report['filters'] = $filters;
        return ['success' => true, 'report_id' => $this->model->savePeriodReport($report, $filters, (int) $generatedBy ?: null), 'report' => $report, 'message' => 'Clinic report generated successfully.'];
    }

    public function getStatisticsReport($start_date = '', $end_date = '')
    {
        return [
            'statistics' => $this->model->getReportStatistics($start_date, $end_date),
            'success' => true
        ];
    }
}
