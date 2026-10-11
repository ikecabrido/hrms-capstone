<?php

class ClinicReportModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getTotalEmployees()
    {
        try {
            $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM em_employees WHERE employment_status = "Active" AND is_archived = 0');
            return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        } catch (Throwable $e) {
            try {
                $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM em_employees WHERE employment_status = "Active"');
                return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            } catch (Throwable $e2) {
                return 0;
            }
        }
    }

    public function getTotalPatients()
    {
        try {
            $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM cm_patients WHERE status = "Active"');
            return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        } catch (Throwable $e) { return 0; }
    }

    public function getTotalMedicalRecords()
    {
        try {
            $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM cm_medical_records');
            return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        } catch (Throwable $e) { return 0; }
    }

    public function getTotalVisits()
    {
        try {
            $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM cm_medical_records WHERE status IN ("Completed", "Follow-up", "Follow-up Required")');
            return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        } catch (Throwable $e) { return 0; }
    }

    public function getPendingRecords()
    {
        try {
            $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM cm_medical_records WHERE status = "Pending"');
            return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        } catch (Throwable $e) { return 0; }
    }

    public function getCommonConditions($limit = 5)
    {
        try {
            $sql = '
                SELECT chief_complaint AS complaint_name, COUNT(*) AS count
                FROM cm_medical_records
                WHERE chief_complaint IS NOT NULL AND chief_complaint != ""
                GROUP BY chief_complaint
                ORDER BY count DESC
                LIMIT :limit
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map(function ($row) {
                $row['condition'] = $row['complaint_name'] ?? null;
                unset($row['complaint_name']);
                return $row;
            }, $rows);
        } catch (Throwable $e) { return []; }
    }

    public function getEmergencyCasesSummary()
    {
        try {
            $stmt = $this->pdo->query('
                SELECT severity_level, COUNT(*) as count 
                FROM cm_emergency_cases 
                WHERE case_status IN ("Active", "Open") 
                GROUP BY severity_level 
                ORDER BY FIELD(severity_level, "Critical", "High", "Medium", "Low", "Minor")
            ');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { return []; }
    }

    public function getRecentVisits($limit = 10)
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 
                    mr.record_id,
                    mr.visit_date,
                    mr.chief_complaint,
                    mr.diagnosis,
                    mr.status,
                    cp.patient_id,
                    e.first_name,
                    e.last_name,
                    CONCAT(COALESCE(e.first_name, ""), " ", COALESCE(e.last_name, "")) as employee_name,
                    d.department_name AS department
                FROM cm_medical_records mr
                LEFT JOIN cm_patients cp ON mr.patient_id = cp.patient_id
                LEFT JOIN em_employees e ON cp.employee_id = e.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                ORDER BY mr.visit_date DESC
                LIMIT :limit
            ');
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { return []; }
    }

    public function searchPatientReports($search = '', $department = '', $consultation_type = '', $start_date = '', $end_date = '', $limit = 50, $offset = 0)
    {
        try {
            $query = '
                SELECT 
                    mr.record_id,
                    mr.visit_date,
                    mr.chief_complaint,
                    mr.diagnosis,
                    mr.treatment,
                    mr.status,
                    mr.consultation_type,
                    cp.patient_id,
                    e.first_name,
                    e.last_name,
                    cp.patient_type,
                    e.employee_id,
                    CONCAT(COALESCE(e.first_name, ""), " ", COALESCE(e.last_name, "")) as employee_name,
                    d.department_name AS department,
                    pos.position_name AS position
                FROM cm_medical_records mr
                LEFT JOIN cm_patients cp ON mr.patient_id = cp.patient_id
                LEFT JOIN em_employees e ON cp.employee_id = e.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions pos ON pos.position_id = e.position_id
                WHERE 1=1
            ';
            $params = [];
            if (!empty($search)) {
                $query .= ' AND (e.first_name LIKE ? OR e.last_name LIKE ? OR cp.patient_id LIKE ? OR CONCAT(e.first_name, " ", e.last_name) LIKE ?)';
                $search_param = '%' . $search . '%';
                $params[] = $search_param; $params[] = $search_param; $params[] = $search_param; $params[] = $search_param;
            }
            if (!empty($department)) { $query .= ' AND d.department_name = ?'; $params[] = $department; }
            if (!empty($consultation_type)) { $query .= ' AND mr.consultation_type = ?'; $params[] = $consultation_type; }
            if (!empty($start_date)) { $query .= ' AND DATE(mr.visit_date) >= ?'; $params[] = $start_date; }
            if (!empty($end_date)) { $query .= ' AND DATE(mr.visit_date) <= ?'; $params[] = $end_date; }
            $query .= ' ORDER BY mr.visit_date DESC LIMIT :limit OFFSET :offset';
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $index => $value) { $stmt->bindValue($index + 1, $value); }
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { return []; }
    }

    public function countPatientReports($search = '', $department = '', $consultation_type = '', $start_date = '', $end_date = '')
    {
        try {
            $query = '
                SELECT COUNT(*) as total
                FROM cm_medical_records mr
                LEFT JOIN cm_patients cp ON mr.patient_id = cp.patient_id
                LEFT JOIN em_employees e ON cp.employee_id = e.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                WHERE 1=1
            ';
            $params = [];
            if (!empty($search)) {
                $query .= ' AND (e.first_name LIKE ? OR e.last_name LIKE ? OR cp.patient_id LIKE ? OR CONCAT(e.first_name, " ", e.last_name) LIKE ?)';
                $search_param = '%' . $search . '%';
                $params[] = $search_param; $params[] = $search_param; $params[] = $search_param; $params[] = $search_param;
            }
            if (!empty($department)) { $query .= ' AND d.department_name = ?'; $params[] = $department; }
            if (!empty($consultation_type)) { $query .= ' AND mr.consultation_type = ?'; $params[] = $consultation_type; }
            if (!empty($start_date)) { $query .= ' AND DATE(mr.visit_date) >= ?'; $params[] = $start_date; }
            if (!empty($end_date)) { $query .= ' AND DATE(mr.visit_date) <= ?'; $params[] = $end_date; }
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        } catch (Throwable $e) { return 0; }
    }

    public function getMedicalRecordDetail($record_id)
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 
                    mr.record_id,
                    mr.visit_date,
                    mr.chief_complaint,
                    mr.diagnosis,
                    mr.treatment,
                    mr.status,
                    mr.consultation_type,
                    mr.attending_physician,
                    mr.medications_prescribed,
                    mr.notes,
                    mr.follow_up_date,
                    cp.patient_id,
                    e.first_name,
                    e.last_name,
                    e.birth_date,
                    cp.gender,
                    cp.blood_type,
                    cp.allergies,
                    cp.medical_conditions,
                    cp.patient_type,
                    e.employee_id,
                    CONCAT(COALESCE(e.first_name, ""), " ", COALESCE(e.last_name, "")) as employee_name,
                    d.department_name AS department,
                    pos.position_name AS position,
                    COALESCE(e.mobile_no, e.phone_no, "") AS contact_number
                FROM cm_medical_records mr
                LEFT JOIN cm_patients cp ON mr.patient_id = cp.patient_id
                LEFT JOIN em_employees e ON cp.employee_id = e.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions pos ON pos.position_id = e.position_id
                WHERE mr.record_id = ?
            ');
            $stmt->execute([$record_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) { return null; }
    }

    public function getDepartmentsList()
    {
        $departments = [];
        $existing = [];

        try {
            $stmt = $this->pdo->query('SELECT DISTINCT d.department_name FROM em_employees e INNER JOIN em_departments d ON d.department_id = e.department_id WHERE d.department_name IS NOT NULL AND d.department_name <> "" ORDER BY d.department_name ASC');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                $name = (string)($row['department_name'] ?? '');
                if ($name !== '' && !isset($existing[$name])) {
                    $existing[$name] = true;
                    $departments[] = ['department_name' => $name];
                }
            }
        } catch (Throwable $e) {
        }

        try {
            $stmt = $this->pdo->query('SELECT department_name FROM em_departments ORDER BY department_name ASC');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                $name = (string)($row['department_name'] ?? '');
                if ($name !== '' && !isset($existing[$name])) {
                    $existing[$name] = true;
                    $departments[] = ['department_name' => $name];
                }
            }
        } catch (Throwable $e) {
        }

        return $departments;
    }

    public function getEmployeesList()
    {
        try {
            $stmt = $this->pdo->query('SELECT e.employee_id, e.employee_code, CONCAT_WS(" ", e.first_name, e.middle_name, e.last_name) AS employee_name, d.department_name FROM em_employees e LEFT JOIN em_departments d ON d.department_id = e.department_id WHERE e.employment_status = "ACTIVE" ORDER BY e.last_name, e.first_name');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { return []; }
    }

    private function periodBounds(array $filters)
    {
        if ($filters['report_type'] === 'daily') return [$filters['date_from'], $filters['date_from']];
        if ($filters['report_type'] === 'weekly') return [$filters['date_from'], $filters['date_to']];
        $start = sprintf('%04d-%02d-01', (int) $filters['year'], (int) $filters['month']);
        return [$start, date('Y-m-t', strtotime($start))];
    }

    private function visitWhere(array $filters, array &$params)
    {
        [$from, $to] = $this->periodBounds($filters);
        $where = ['DATE(mr.visit_date) BETWEEN ? AND ?'];
        $params[] = $from; $params[] = $to;
        if ($filters['department'] !== '') { $where[] = 'd.department_name = ?'; $params[] = $filters['department']; }
        if ($filters['employee_id'] !== '') { $where[] = 'e.employee_id = ?'; $params[] = (int) $filters['employee_id']; }
        return implode(' AND ', $where);
    }

    private function fetchReportRows($sql, array $params)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPeriodReport(array $filters)
    {
        $params = [];
        $where = $this->visitWhere($filters, $params);
        $joins = ' FROM cm_medical_records mr LEFT JOIN cm_patients cp ON cp.patient_id = mr.patient_id LEFT JOIN em_employees e ON e.employee_id = cp.employee_id LEFT JOIN em_departments d ON d.department_id = e.department_id LEFT JOIN em_positions p ON p.position_id = e.position_id';
        $rows = $this->fetchReportRows('SELECT e.employee_code AS employee_id, CONCAT_WS(" ", e.first_name, e.middle_name, e.last_name) AS employee_name, d.department_name AS department, p.position_name AS position, mr.visit_date, TIME_FORMAT(TIME(mr.visit_date), "%h:%i %p") AS visit_time, mr.chief_complaint, mr.diagnosis, COALESCE(NULLIF(mr.treatment, ""), mr.medications_prescribed, "") AS action_taken, mr.attending_physician' . $joins . ' WHERE ' . $where . ' ORDER BY mr.visit_date ASC', $params);
        $summaryParams = [];
        $summary = $this->fetchReportRows('SELECT COUNT(*) AS total_visits, COUNT(DISTINCT e.employee_id) AS unique_employees, COUNT(DISTINCT d.department_id) AS departments_served' . $joins . ' WHERE ' . $this->visitWhere($filters, $summaryParams), $summaryParams)[0] ?? ['total_visits' => 0, 'unique_employees' => 0, 'departments_served' => 0];
        $departmentParams = [];
        $departments = $this->fetchReportRows('SELECT COALESCE(d.department_name, "Unassigned") AS department, COUNT(*) AS total_visits' . $joins . ' WHERE ' . $this->visitWhere($filters, $departmentParams) . ' GROUP BY d.department_id, d.department_name ORDER BY total_visits DESC', $departmentParams);
        $reasonParams = [];
        $reasons = $this->fetchReportRows('SELECT COALESCE(NULLIF(mr.chief_complaint, ""), "Unspecified") AS reason, COUNT(*) AS total_visits' . $joins . ' WHERE ' . $this->visitWhere($filters, $reasonParams) . ' GROUP BY mr.chief_complaint ORDER BY total_visits DESC LIMIT 10', $reasonParams);
        $daily = [];
        if ($filters['report_type'] !== 'daily') { $dailyParams = []; $daily = $this->fetchReportRows('SELECT DATE(mr.visit_date) AS visit_date, COUNT(*) AS total_visits' . $joins . ' WHERE ' . $this->visitWhere($filters, $dailyParams) . ' GROUP BY DATE(mr.visit_date) ORDER BY visit_date', $dailyParams); }
        return ['summary' => $summary, 'rows' => $rows, 'department_summary' => $departments, 'reason_summary' => $reasons, 'daily_summary' => $daily, 'period' => $this->periodBounds($filters)];
    }

    public function savePeriodReport(array $report, array $filters, $generatedBy)
    {
        $nextId = (int) $this->pdo->query('SELECT COALESCE(MAX(report_id), 0) + 1 FROM cm_clinic_reports')->fetchColumn();
        [$from, $to] = $this->periodBounds($filters);
        $types = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];
        $stmt = $this->pdo->prepare('INSERT INTO cm_clinic_reports (report_id, report_type, report_date, start_date, end_date, report_data, generated_by, status, file_format) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, "Generated", "HTML")');
        $stmt->execute([$nextId, $types[$filters['report_type']], $from, $to, json_encode($report, JSON_UNESCAPED_SLASHES), $generatedBy ?: null]);
        return $nextId;
    }

    public function getVitalSignsForRecord($record_id)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT vital_signs FROM cm_medical_records WHERE record_id = ? LIMIT 1');
            $stmt->execute([$record_id]);
            $raw = $stmt->fetchColumn();
            if ($raw) {
                $decoded = @json_decode((string)$raw, true);
                if (is_array($decoded)) {
                    return [
                        'blood_pressure_systolic' => $decoded['blood_pressure'] ? explode('/', $decoded['blood_pressure'])[0] : null,
                        'blood_pressure_diastolic' => $decoded['blood_pressure'] ? (explode('/', $decoded['blood_pressure'])[1] ?? null) : null,
                        'heart_rate' => $decoded['heart_rate'] ?? null,
                        'temperature' => $decoded['temperature'] ?? null,
                        'respiratory_rate' => $decoded['respiratory_rate'] ?? null,
                        'weight' => $decoded['weight'] ?? null,
                        'height' => $decoded['height'] ?? null,
                    ];
                }
            }
        } catch (Throwable $e) {
        }

        try {
            $stmt = $this->pdo->prepare('SELECT * FROM cm_vital_signs WHERE record_id = ? LIMIT 1');
            $stmt->execute([$record_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        } catch (Throwable $e) {
        }

        return null;
    }

    public function getReportStatistics($start_date = '', $end_date = '')
    {
        try {
            $total_records = 0;
            try {
                $query = 'SELECT COUNT(*) as total FROM cm_medical_records WHERE 1=1';
                $params = [];
                if (!empty($start_date)) { $query .= ' AND DATE(visit_date) >= ?'; $params[] = $start_date; }
                if (!empty($end_date)) { $query .= ' AND DATE(visit_date) <= ?'; $params[] = $end_date; }
                $stmt = $this->pdo->prepare($query);
                $stmt->execute($params);
                $total_records = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            } catch (Throwable $e) { $total_records = 0; }

            $by_type = [];
            try {
                $query = 'SELECT consultation_type, COUNT(*) as count FROM cm_medical_records WHERE 1=1';
                $params = [];
                if (!empty($start_date)) { $query .= ' AND DATE(visit_date) >= ?'; $params[] = $start_date; }
                if (!empty($end_date)) { $query .= ' AND DATE(visit_date) <= ?'; $params[] = $end_date; }
                $query .= ' GROUP BY consultation_type';
                $stmt = $this->pdo->prepare($query);
                $stmt->execute($params);
                $by_type = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) { $by_type = []; }

            return [
                'total_records' => $total_records,
                'by_consultation_type' => $by_type
            ];
        } catch (Throwable $e) {
            return ['total_records' => 0, 'by_consultation_type' => []];
        }
    }
}
