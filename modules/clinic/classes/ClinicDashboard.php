<?php

require_once __DIR__ . '/../../../database/db.php';

class ClinicDashboard
{
    private PDO $conn;
    private array $tables = [];

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $this->conn = (new Database())->getConnection();
        }
    }

    public function getData(?string $range = null): array
    {
        try {
            $range = $range ?: 'this_month';
            $dateRange = $this->resolveRange($range);
            $data = [
                'updated_at' => date('c'),
                'range' => $range,
                'range_label' => $dateRange['label'],
                'range_start' => $dateRange['start'],
                'range_end' => $dateRange['end'],
                'metrics' => $this->getMetrics(),
                'visit_stats' => $this->getVisitStats($dateRange),
                'patient_visits' => $this->getPatientVisits($dateRange),
                'upcoming_appointments' => $this->getUpcomingAppointments(),
                'inventory_status' => $this->getInventoryStatus(),
                'recent_emergency' => $this->getRecentEmergency(),
                'recent_reports' => $this->getRecentReports(),
                'recent_medical_records' => $this->getRecentMedicalRecords(),
            ];
            return $data;
        } catch (Throwable $e) {
            @error_log('[ClinicDashboard] getData failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'updated_at' => date('c'),
                'range' => 'this_month',
                'range_label' => 'This Month',
                'range_start' => date('Y-m-01'),
                'range_end' => date('Y-m-d'),
                'metrics' => [
                    'total_patients' => 0,
                    'total_patients_change' => 0,
                    'todays_appointments' => 0,
                    'todays_appointments_change' => 0,
                    'emergency_cases' => 0,
                    'emergency_cases_change' => 0,
                    'low_stock' => 0,
                    'low_stock_change' => 0,
                    'expired_meds' => 0,
                    'expired_meds_change' => 0,
                ],
                'visit_stats' => ['total' => 0, 'total_change' => 0, 'average_day' => 0, 'average_change' => 0, 'label' => ''],
                'patient_visits' => [],
                'upcoming_appointments' => [],
                'inventory_status' => ['in_stock' => 0, 'low_stock' => 0, 'expired' => 0, 'total' => 0],
                'recent_emergency' => [],
                'recent_reports' => [],
                'recent_medical_records' => [],
            ];
        }
    }

    private function resolveRange(string $range): array
    {
        $today = new DateTimeImmutable();
        switch ($range) {
            case 'today':
                $start = $today;
                $label = 'Today';
                break;
            case 'last_7_days':
                $start = $today->modify('-6 days');
                $label = 'Last 7 Days';
                break;
            case 'last_30_days':
                $start = $today->modify('-29 days');
                $label = 'Last 30 Days';
                break;
            case 'this_month':
            default:
                $start = $today->modify('first day of this month');
                $label = 'This Month';
                break;
        }
        return [
            'start' => $start->format('Y-m-d'),
            'end' => $today->format('Y-m-d'),
            'label' => $label,
        ];
    }

    private function getMetrics(): array
    {
        $metrics = [
            'total_patients' => 0,
            'total_patients_change' => 12,
            'todays_appointments' => 0,
            'todays_appointments_change' => 8,
            'emergency_cases' => 0,
            'emergency_cases_change' => 25,
            'low_stock' => 0,
            'low_stock_change' => -15,
            'expired_meds' => 0,
            'expired_meds_change' => 2,
        ];

        try {
            if ($this->hasTable('cm_patients')) {
                $metrics['total_patients'] = (int) $this->scalar(
                    "SELECT COUNT(*) FROM cm_patients p INNER JOIN cm_medical_records mr ON mr.patient_id = p.patient_id WHERE DATE(mr.visit_date) >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
                );
                $metrics['total_patients_change'] = 12;
            }

            if ($this->hasTable('cm_appointments')) {
                $metrics['todays_appointments'] = (int) $this->scalar(
                    "SELECT COUNT(*) FROM cm_appointments WHERE appointment_date = CURDATE() AND status = 'Scheduled'"
                );
                $metrics['todays_appointments_change'] = 8;
            }

            if ($this->hasTable('cm_emergency_cases')) {
                $metrics['emergency_cases'] = (int) $this->scalar(
                    "SELECT COUNT(*) FROM cm_emergency_cases WHERE case_status IN ('Active', 'Open')"
                );
                $metrics['emergency_cases_change'] = 25;
            }

            if ($this->hasTable('cm_medicine_inventory')) {
                $metrics['low_stock'] = (int) $this->scalar(
                    "SELECT COUNT(*) FROM cm_medicine_inventory WHERE status = 'Low Stock'"
                );
                $metrics['low_stock_change'] = -15;
                $metrics['expired_meds'] = (int) $this->scalar(
                    "SELECT COUNT(*) FROM cm_medicine_inventory WHERE status = 'Expired'"
                );
                $metrics['expired_meds_change'] = 2;
            }
        } catch (Throwable $e) {
            @error_log('[ClinicDashboard] getMetrics error: ' . $e->getMessage());
        }

        return $metrics;
    }

    private function getVisitStats(array $range): array
    {
        try {
            $prevStart = (new DateTimeImmutable($range['start']))->modify('-1 month')->format('Y-m-d');
            $prevEnd = (new DateTimeImmutable($range['start']))->modify('-1 day')->format('Y-m-d');

            $total = 0;
            if ($this->hasTable('cm_medical_records')) {
                $total = (int) $this->scalar(
                    "SELECT COUNT(*) FROM cm_medical_records WHERE DATE(visit_date) BETWEEN :start AND :end",
                    [':start' => $range['start'], ':end' => $range['end']]
                );
            }

            $prevTotal = 0;
            if ($this->hasTable('cm_medical_records')) {
                $prevTotal = (int) $this->scalar(
                    "SELECT COUNT(*) FROM cm_medical_records WHERE DATE(visit_date) BETWEEN :start AND :end",
                    [':start' => $prevStart, ':end' => $prevEnd]
                );
            }

            $days = max(1, (int) date_diff(new DateTime($range['start']), new DateTime($range['end']))->days + 1);
            $avgDay = round($total / $days, 1);

            $prevDays = max(1, (int) date_diff(new DateTime($prevStart), new DateTime($prevEnd))->days + 1);
            $prevAvg = round($prevTotal / $prevDays, 1);

            $totalChange = $prevTotal > 0 ? round((($total - $prevTotal) / $prevTotal) * 100) : 12;
            $avgChange = $prevAvg > 0 ? round((($avgDay - $prevAvg) / $prevAvg) * 100) : 5;

            return [
                'total' => $total,
                'total_change' => $totalChange,
                'average_day' => $avgDay,
                'average_change' => $avgChange,
                'label' => $range['start'] . ' - ' . $range['end'],
            ];
        } catch (Throwable $e) {
            @error_log('[ClinicDashboard] getVisitStats error: ' . $e->getMessage());
            return ['total' => 0, 'total_change' => 12, 'average_day' => 0, 'average_change' => 5, 'label' => ''];
        }
    }

    private function getPatientVisits(array $range): array
    {
        try {
            $visits = [];
            $start = new DateTimeImmutable($range['start']);
            $end = new DateTimeImmutable($range['end']);
            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
            foreach ($period as $day) {
                $visits[$day->format('Y-m-d')] = 0;
            }

            if (!$this->hasTable('cm_medical_records')) {
                return $visits;
            }

            $rows = $this->rows(
                "SELECT DATE(visit_date) AS visit_day, COUNT(*) AS total FROM cm_medical_records
                    WHERE visit_date BETWEEN :start AND :end
                    GROUP BY DATE(visit_date) ORDER BY visit_day",
                [':start' => $range['start'] . ' 00:00:00', ':end' => $range['end'] . ' 23:59:59']
            );
            foreach ($rows as $row) {
                if (isset($visits[$row['visit_day']])) {
                    $visits[$row['visit_day']] = (int) $row['total'];
                }
            }
            return $visits;
        } catch (Throwable $e) {
            @error_log('[ClinicDashboard] getPatientVisits error: ' . $e->getMessage());
            return [];
        }
    }

    private function getUpcomingAppointments(): array
    {
        try {
            if (!$this->hasTable('cm_appointments')) return [];
            $hasPatientId = (bool) $this->scalar(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cm_appointments' AND COLUMN_NAME = 'patient_id'"
            );
            $joinBlock = $hasPatientId
                ? "LEFT JOIN cm_patients p ON p.patient_id = a.patient_id
                   LEFT JOIN em_employees e ON e.employee_id = COALESCE(p.employee_id, a.employee_id)"
                : "LEFT JOIN em_employees e ON e.employee_id = a.employee_id";
            $sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.purpose, a.status,
                           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS patient_name
                    FROM cm_appointments a
                    $joinBlock
                    WHERE a.status = 'Scheduled'
                    ORDER BY a.appointment_date ASC, a.appointment_time ASC
                    LIMIT 6";
            return $this->rows($sql);
        } catch (Throwable $e) {
            @error_log('[ClinicDashboard] getUpcomingAppointments error: ' . $e->getMessage());
            return [];
        }
    }

    private function getInventoryStatus(): array
    {
        try {
            if (!$this->hasTable('cm_medicine_inventory')) {
                return ['in_stock' => 0, 'low_stock' => 0, 'expired' => 0, 'total' => 0];
            }
            $rows = $this->rows("SELECT status, COUNT(*) AS total FROM cm_medicine_inventory GROUP BY status");
            $result = ['in_stock' => 0, 'low_stock' => 0, 'expired' => 0];
            foreach ($rows as $row) {
                $status = $row['status'];
                $total = (int) $row['total'];
                if ($status === 'Available') { $result['in_stock'] += $total; }
                elseif ($status === 'Low Stock') { $result['low_stock'] += $total; }
                elseif ($status === 'Expired') { $result['expired'] += $total; }
                else { $result['in_stock'] += $total; }
            }
            $result['total'] = $result['in_stock'] + $result['low_stock'] + $result['expired'];
            return $result;
        } catch (Throwable $e) {
            @error_log('[ClinicDashboard] getInventoryStatus error: ' . $e->getMessage());
            return ['in_stock' => 0, 'low_stock' => 0, 'expired' => 0, 'total' => 0];
        }
    }

    private function getRecentEmergency(): array
    {
        try {
            if (!$this->hasTable('cm_emergency_cases')) return [];
            $sql = "SELECT ec.case_id, ec.incident_date, ec.chief_complaint, ec.case_status, ec.severity_level,
                           COALESCE(CONCAT(e.first_name, ' ', e.last_name), '') AS patient_name
                    FROM cm_emergency_cases ec
                    LEFT JOIN cm_patients p ON p.patient_id = ec.patient_id
                    LEFT JOIN em_employees e ON e.employee_id = COALESCE(p.employee_id, ec.employee_id)
                    ORDER BY ec.incident_date DESC, ec.case_id DESC
                    LIMIT 3";
            return $this->rows($sql);
        } catch (Throwable $e) {
            @error_log('[ClinicDashboard] getRecentEmergency error: ' . $e->getMessage());
            return [];
        }
    }

    private function getRecentReports(): array
    {
        try {
            $sql = "SELECT cr.report_id, cr.report_type, cr.report_date, cr.created_at
                    FROM cm_clinic_reports cr
                    WHERE cr.status = 'Generated'
                    ORDER BY cr.report_date DESC LIMIT 3";
            return $this->hasTable('cm_clinic_reports') ? $this->rows($sql) : [];
        } catch (Throwable $e) {
            @error_log('[ClinicDashboard] getRecentReports error: ' . $e->getMessage());
            return [];
        }
    }

    private function getRecentMedicalRecords(): array
    {
        try {
            if (!$this->hasTable('cm_medical_records')) return [];
            $sql = "SELECT mr.record_id, mr.visit_date, mr.chief_complaint, mr.consultation_type, mr.status,
                           p.patient_id, p.employee_id,
                           e.first_name, e.last_name,
                           e.employee_code,
                           d.department_name,
                           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name
                    FROM cm_medical_records mr
                    INNER JOIN cm_patients p ON p.patient_id = mr.patient_id
                    LEFT JOIN em_employees e ON e.employee_id = p.employee_id
                    LEFT JOIN em_departments d ON d.department_id = e.department_id
                    ORDER BY mr.visit_date DESC, mr.record_id DESC
                    LIMIT 8";
            $rows = $this->rows($sql);
            return array_map(function ($r) {
                return [
                    'record_id' => $r['record_id'] ?? null,
                    'employee_id' => (int)($r['employee_id'] ?? 0),
                    'employee_code' => $r['employee_code'] ?? '',
                    'employee_name' => $r['employee_name'] ?: ($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''),
                    'department_name' => $r['department_name'] ?? 'N/A',
                    'visit_date' => $r['visit_date'] ?? '',
                    'chief_complaint' => $r['chief_complaint'] ?? '',
                    'consultation_type' => $r['consultation_type'] ?? 'General',
                    'status' => $r['status'] ?? 'Completed',
                ];
            }, $rows);
        } catch (Throwable $e) {
            @error_log('[ClinicDashboard] getRecentMedicalRecords error: ' . $e->getMessage());
            return [];
        }
    }

    private function hasTable(string $table): bool
    {
        if (!array_key_exists($table, $this->tables)) {
            try {
                $stmt = $this->conn->prepare('SHOW TABLES LIKE :table_name');
                $stmt->execute([':table_name' => $table]);
                $this->tables[$table] = (bool) $stmt->fetchColumn();
            } catch (Throwable $e) {
                $this->tables[$table] = false;
            }
        }
        return $this->tables[$table];
    }

    private function scalar(string $sql, array $params = []): mixed
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() ?: 0;
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function rows(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}
