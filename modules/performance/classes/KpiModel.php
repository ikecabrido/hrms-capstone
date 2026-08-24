<?php
require_once __DIR__ . '/../../../database/db.php';

class KpiModel
{
    private PDO $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
            return;
        }

        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function ensureSchema(): void
    {
        $sql = [
            "CREATE TABLE IF NOT EXISTS school_kpis (
                kpi_id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                department_id INT DEFAULT NULL,
                kpi_title VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                target_value DECIMAL(10,2) NOT NULL DEFAULT 0,
                actual_value DECIMAL(10,2) NOT NULL DEFAULT 0,
                unit VARCHAR(50) DEFAULT '%',
                weight DECIMAL(5,2) NOT NULL DEFAULT 0,
                status ENUM('Achieved','In Progress','Partially Achieved','Not Achieved') NOT NULL DEFAULT 'In Progress',
                start_date DATE DEFAULT NULL,
                due_date DATE DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_school_kpis_employee (employee_id),
                INDEX idx_school_kpis_department (department_id),
                INDEX idx_school_kpis_status (status)
            )",
            "CREATE TABLE IF NOT EXISTS school_kpi_history (
                history_id INT AUTO_INCREMENT PRIMARY KEY,
                kpi_id INT NOT NULL,
                employee_id INT NOT NULL,
                previous_value DECIMAL(10,2) DEFAULT 0,
                new_value DECIMAL(10,2) DEFAULT 0,
                progress_percentage DECIMAL(8,2) DEFAULT 0,
                remarks TEXT DEFAULT NULL,
                recorded_by VARCHAR(255) DEFAULT NULL,
                recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_kpi_history_kpi (kpi_id),
                INDEX idx_kpi_history_employee (employee_id)
            )"
        ];

        foreach ($sql as $statement) {
            $this->conn->exec($statement);
        }
    }

    private function tableExists(string $tableName): bool
    {
        $sql = "SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = :table_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':table_name', $tableName, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    private function employeeTable(): ?string
    {
        $candidates = ['hrms_employee', 'em_employees'];
        foreach ($candidates as $table) {
            if ($this->tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    private function departmentTable(): ?string
    {
        $candidates = ['hrms_department', 'em_departments'];
        foreach ($candidates as $table) {
            if ($this->tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    private function columnExists(string $table, string $column): bool
    {
        $sql = "SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = :table_name
                  AND column_name = :column_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':table_name', $table, PDO::PARAM_STR);
        $stmt->bindParam(':column_name', $column, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    private function employeeDepartmentColumn(string $table): string
    {
        return $this->columnExists($table, 'department_id') ? 'department_id' : 'department';
    }

    private function employeePositionColumn(string $table): string
    {
        return $this->columnExists($table, 'position_id') ? 'position_id' : 'position';
    }

    private function employeeStatusColumn(string $table): string
    {
        return $this->columnExists($table, 'employment_status') ? 'employment_status' : 'status';
    }

    private function employeeCodeColumn(string $table): string
    {
        return $this->columnExists($table, 'employee_code') ? 'employee_code' : 'employee_id';
    }

    private function normalizeStatus(float $progress): string
    {
        if ($progress >= 100) {
            return 'Achieved';
        }
        if ($progress >= 75) {
            return 'In Progress';
        }
        if ($progress >= 50) {
            return 'Partially Achieved';
        }

        return 'Not Achieved';
    }

    private function calculateProgress(float $actual, float $target): float
    {
        if ($target <= 0) {
            return 0.0;
        }

        $progress = ($actual / $target) * 100;

        return min(max($progress, 0), 100);
    }

    private function getEmployeeNameById(int $employeeId): string
    {
        $employeeTable = $this->employeeTable();
        if ($employeeTable === null) {
            return 'Unknown Employee';
        }

        $sql = "SELECT CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS employee_name
                FROM {$employeeTable}
                WHERE employee_id = :employee_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        return trim((string) ($stmt->fetchColumn() ?: '')) ?: 'Unknown Employee';
    }

    private function getDepartmentNameById(?int $departmentId): string
    {
        if (empty($departmentId)) {
            return 'Unassigned';
        }

        $departmentTable = $this->departmentTable();
        if ($departmentTable === null) {
            return 'Unassigned';
        }

        $sql = "SELECT department_name FROM {$departmentTable} WHERE department_id = :department_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':department_id', $departmentId, PDO::PARAM_INT);
        $stmt->execute();

        $departmentName = trim((string) ($stmt->fetchColumn() ?: ''));

        return $departmentName !== '' ? $departmentName : 'Unassigned';
    }

    public function getEmployees(): array
    {
        $employeeTable = $this->employeeTable();
        if ($employeeTable === null) {
            return [];
        }

        $departmentTable = $this->departmentTable();
        $departmentColumn = $this->employeeDepartmentColumn($employeeTable);
        $positionColumn = $this->employeePositionColumn($employeeTable);
        $statusColumn = $this->employeeStatusColumn($employeeTable);
        $codeColumn = $this->employeeCodeColumn($employeeTable);

        $departmentJoin = $departmentTable ? "LEFT JOIN {$departmentTable} d ON d.department_id = e.{$departmentColumn}" : '';
        $positionJoin = $this->tableExists('hrms_position') ? 'LEFT JOIN hrms_position p ON p.position_id = e.' . $positionColumn : '';
        if ($this->tableExists('em_positions') && $employeeTable === 'em_employees') {
            $positionJoin = 'LEFT JOIN em_positions p ON p.position_id = e.' . $positionColumn;
        }

        $statusFilter = $this->columnExists($employeeTable, 'employment_status') ? 'WHERE e.employment_status IS NOT NULL' : 'WHERE e.status IS NOT NULL';

        $sql = "SELECT e.employee_id,
                       e.{$codeColumn} AS employee_code,
                       CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                       COALESCE(p.position_name, 'Teacher') AS position,
                       e.{$departmentColumn} AS department_id,
                       COALESCE(d.department_name, 'Unassigned') AS department_name,
                       e.{$statusColumn} AS status,
                       NULL AS profile_image
                FROM {$employeeTable} e
                {$departmentJoin}
                {$positionJoin}
                {$statusFilter}
                ORDER BY e.first_name, e.last_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartments(): array
    {
        $departmentTable = $this->departmentTable();
        if ($departmentTable === null) {
            return [];
        }

        $sql = "SELECT department_id, department_name FROM {$departmentTable} ORDER BY department_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardStats(): array
    {
        $this->ensureSchema();

        $totalKpis = (int) $this->conn->query('SELECT COUNT(*) FROM school_kpis')->fetchColumn();
        $assignedEmployees = (int) $this->conn->query('SELECT COUNT(DISTINCT employee_id) FROM school_kpis')->fetchColumn();

        $overallAchievement = 0.0;
        if ($totalKpis > 0) {
            $overallAchievement = (float) $this->conn->query(
                'SELECT AVG(LEAST((actual_value / NULLIF(target_value, 0)) * 100, 100)) FROM school_kpis WHERE target_value > 0'
            )->fetchColumn();
        }

        $achieved = (int) $this->conn->query("SELECT COUNT(*) FROM school_kpis WHERE status = 'Achieved'")->fetchColumn();
        $needsImprovement = (int) $this->conn->query("SELECT COUNT(*) FROM school_kpis WHERE status IN ('Partially Achieved', 'Not Achieved')")->fetchColumn();

        return [
            'total_kpis' => $totalKpis,
            'employees_with_kpi' => $assignedEmployees,
            'overall_achievement' => round($overallAchievement, 2),
            'achieved_kpis' => $achieved,
            'needs_improvement' => $needsImprovement,
        ];
    }

    public function getStatusDistribution(array $filters = []): array
    {
        $this->ensureSchema();

        $sql = "SELECT status, COUNT(*) AS total FROM school_kpis WHERE 1 = 1";
        $params = [];

        if (!empty($filters['employee_id'])) {
            $sql .= ' AND employee_id = :employee_id';
            $params[':employee_id'] = [(int) $filters['employee_id'], PDO::PARAM_INT];
        }
        if (!empty($filters['department'])) {
            $sql .= ' AND department_id = :department_id';
            $params[':department_id'] = [(int) $filters['department'], PDO::PARAM_INT];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params[':status'] = [trim((string) $filters['status']), PDO::PARAM_STR];
        }

        $sql .= ' GROUP BY status';

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => [$value, $type]) {
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();

        $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [
            'Achieved' => 0,
            'In Progress' => 0,
            'Partially Achieved' => 0,
            'Not Achieved' => 0,
        ];

        foreach ($counts as $row) {
            $value = trim((string) ($row['status'] ?? ''));
            if (isset($result[$value])) {
                $result[$value] = (int) $row['total'];
            }
        }

        $total = array_sum($result);
        $distribution = [];
        foreach (['Achieved', 'In Progress', 'Partially Achieved', 'Not Achieved'] as $status) {
            $count = (int) $result[$status];
            $distribution[] = [
                'label' => $status,
                'value' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                'color' => $this->statusColor($status),
            ];
        }

        return ['total' => $total, 'items' => $distribution];
    }

    private function statusColor(string $status): string
    {
        $colors = [
            'Achieved' => '#57d17b',
            'In Progress' => '#5ab8ff',
            'Partially Achieved' => '#f7c86d',
            'Not Achieved' => '#ff6b6b',
        ];

        return $colors[$status] ?? '#94a3b8';
    }

    public function getMonthlyTrend(string $semester = 'this-semester'): array
    {
        $this->ensureSchema();
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $data = array_fill(0, 12, 0);

        $startDate = null;
        $endDate = null;
        if ($semester === 'this-semester') {
            $monthAdjust = 5;
            $startDate = date('Y-m-01', strtotime("-{$monthAdjust} months"));
            $endDate = date('Y-m-t');
        } elseif ($semester === 'last-quarter') {
            $startDate = date('Y-m-01', strtotime('-3 months'));
            $endDate = date('Y-m-t');
        } elseif ($semester === 'this-year') {
            $startDate = date('Y-01-01');
            $endDate = date('Y-12-31');
        } else {
            $startDate = date('Y-m-01', strtotime('-11 months'));
            $endDate = date('Y-m-t');
        }

        $sql = "SELECT MONTH(COALESCE(updated_at, created_at)) AS month_no,
                       AVG(LEAST((actual_value / NULLIF(target_value, 0)) * 100, 100)) AS avg_progress
                FROM school_kpis
                WHERE (updated_at >= :start_date OR created_at >= :start_date)
                  AND (updated_at <= :end_date OR created_at <= :end_date)
                GROUP BY MONTH(COALESCE(updated_at, created_at))";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $monthNo = (int) ($row['month_no'] ?? 0);
            if ($monthNo >= 1 && $monthNo <= 12) {
                $data[$monthNo - 1] = round((float) ($row['avg_progress'] ?? 0), 1);
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getKpiRows(array $filters = []): array
    {
        $this->ensureSchema();

        $employeeTable = $this->employeeTable();
        $departmentTable = $this->departmentTable();
        $employeeJoin = $employeeTable ? "LEFT JOIN {$employeeTable} e ON e.employee_id = k.employee_id" : '';
        $departmentColumn = $this->employeeDepartmentColumn($employeeTable ?? 'hrms_employee');
        $departmentJoin = $departmentTable ? "LEFT JOIN {$departmentTable} d ON d.department_id = COALESCE(k.department_id, e.{$departmentColumn})" : '';

        $sql = "SELECT k.*,
                       CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                       COALESCE(d.department_name, 'Unassigned') AS department_name,
                       COALESCE(e.employee_code, e.employee_id) AS employee_code,
                       CASE
                           WHEN k.target_value > 0 THEN LEAST((k.actual_value / k.target_value) * 100, 100)
                           ELSE 0
                       END AS progress_percentage
                FROM school_kpis k
                {$employeeJoin}
                {$departmentJoin}
                WHERE 1 = 1";

        $params = [];

        if (!empty($filters['employee_id'])) {
            $sql .= ' AND k.employee_id = :employee_id';
            $params[':employee_id'] = ['value' => (int) $filters['employee_id'], 'type' => PDO::PARAM_INT];
        }

        if (!empty($filters['department'])) {
            $sql .= ' AND COALESCE(k.department_id, e.department_id) = :department_id';
            $params[':department_id'] = ['value' => (int) $filters['department'], 'type' => PDO::PARAM_INT];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND k.status = :status';
            $params[':status'] = ['value' => trim((string) $filters['status']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim((string) $filters['search']) . '%';
            $sql .= ' AND (k.kpi_title LIKE :search OR CONCAT(COALESCE(e.first_name, \'\'), \' \' , COALESCE(e.last_name, \'\')) LIKE :search OR COALESCE(d.department_name, \'Unassigned\') LIKE :search)';
            $params[':search'] = ['value' => $searchTerm, 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['semester'])) {
            [$startDate, $endDate] = $this->getSemesterWindow($filters['semester']);
            if ($startDate && $endDate) {
                $sql .= ' AND (k.start_date >= :start_date OR k.due_date >= :start_date) AND (k.start_date <= :end_date OR k.due_date <= :end_date)';
                $params[':start_date'] = ['value' => $startDate, 'type' => PDO::PARAM_STR];
                $params[':end_date'] = ['value' => $endDate, 'type' => PDO::PARAM_STR];
            }
        }

        $sql .= ' ORDER BY k.updated_at DESC, k.kpi_id DESC';

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $param) {
            $stmt->bindValue($key, $param['value'], $param['type']);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['progress_percentage'] = round((float) ($row['progress_percentage'] ?? 0), 1);
            $row['status'] = trim((string) ($row['status'] ?: $this->normalizeStatus((float) $row['progress_percentage'])));
            if ($row['target_value'] <= 0) {
                $row['progress_percentage'] = 0;
            }
            $row['employee_name'] = $row['employee_name'] ?: $this->getEmployeeNameById((int) ($row['employee_id'] ?? 0));
            $row['department_name'] = $row['department_name'] ?: $this->getDepartmentNameById((int) ($row['department_id'] ?? 0));
        }

        return $rows;
    }

    private function getSemesterWindow(string $semester): array
    {
        $default = [date('Y-m-01', strtotime('-5 months')), date('Y-m-t')];

        switch ($semester) {
            case 'last-quarter':
                return [date('Y-m-01', strtotime('-3 months')), date('Y-m-t')];
            case 'this-year':
                return [date('Y-01-01'), date('Y-12-31')];
            case 'all':
                return ['2000-01-01', date('Y-m-d')];
            case 'this-semester':
            default:
                return $default;
        }
    }

    public function getKpiHistoryMap(): array
    {
        $this->ensureSchema();
        $sql = 'SELECT * FROM school_kpi_history ORDER BY recorded_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = (int) ($row['kpi_id'] ?? 0);
            $map[$key][] = $row;
        }

        return $map;
    }

    public function getEmployeeSummary(?int $employeeId = null): array
    {
        $employeeTable = $this->employeeTable();
        if ($employeeTable === null) {
            return [
                'employee_id' => 0,
                'employee_name' => 'No employee selected',
                'employee_code' => 'N/A',
                'department_name' => 'N/A',
                'position' => 'N/A',
                'overall_kpi' => 0,
                'total_kpis' => 0,
                'achieved_kpis' => 0,
                'in_progress_kpis' => 0,
                'not_achieved_kpis' => 0,
            ];
        }

        $departmentTable = $this->departmentTable();
        $departmentColumn = $this->employeeDepartmentColumn($employeeTable);
        $positionColumn = $this->employeePositionColumn($employeeTable);
        $codeColumn = $this->employeeCodeColumn($employeeTable);

        $positionJoin = $this->tableExists('hrms_position') ? 'LEFT JOIN hrms_position p ON p.position_id = e.' . $positionColumn : '';
        if ($this->tableExists('em_positions') && $employeeTable === 'em_employees') {
            $positionJoin = 'LEFT JOIN em_positions p ON p.position_id = e.' . $positionColumn;
        }

        $baseSql = "SELECT e.employee_id,
                           e.{$codeColumn} AS employee_code,
                           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                           COALESCE(p.position_name, 'Teacher') AS position,
                           COALESCE(d.department_name, 'Unassigned') AS department_name
                    FROM {$employeeTable} e
                    LEFT JOIN {$departmentTable} d ON d.department_id = e.{$departmentColumn}
                    {$positionJoin}";

        if ($employeeId) {
            $baseSql .= ' WHERE e.employee_id = :employee_id';
            $stmt = $this->conn->prepare($baseSql);
            $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
            $stmt->execute();
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $baseSql .= ' WHERE EXISTS (SELECT 1 FROM school_kpis k WHERE k.employee_id = e.employee_id) ORDER BY e.first_name, e.last_name LIMIT 1';
            $stmt = $this->conn->prepare($baseSql);
            $stmt->execute();
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$employee) {
            return [
                'employee_id' => 0,
                'employee_name' => 'No employee selected',
                'employee_code' => 'N/A',
                'department_name' => 'N/A',
                'position' => 'N/A',
                'overall_kpi' => 0,
                'total_kpis' => 0,
                'achieved_kpis' => 0,
                'in_progress_kpis' => 0,
                'not_achieved_kpis' => 0,
            ];
        }

        $summarySql = "SELECT COUNT(*) AS total_kpis,
                              SUM(CASE WHEN status = 'Achieved' THEN 1 ELSE 0 END) AS achieved_kpis,
                              SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_kpis,
                              SUM(CASE WHEN status IN ('Partially Achieved', 'Not Achieved') THEN 1 ELSE 0 END) AS not_achieved_kpis,
                              AVG(CASE WHEN target_value > 0 THEN LEAST((actual_value / target_value) * 100, 100) ELSE 0 END) AS overall_kpi
                       FROM school_kpis
                       WHERE employee_id = :employee_id";

        $summaryStmt = $this->conn->prepare($summarySql);
        $summaryStmt->bindValue(':employee_id', (int) $employee['employee_id'], PDO::PARAM_INT);
        $summaryStmt->execute();
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

        $employeeSummary = [
            'employee_id' => (int) $employee['employee_id'],
            'employee_name' => trim((string) ($employee['employee_name'] ?? 'Unknown Employee')),
            'employee_code' => trim((string) ($employee['employee_code'] ?? 'N/A')),
            'department_name' => trim((string) ($employee['department_name'] ?? 'N/A')),
            'position' => trim((string) ($employee['position'] ?? 'Teacher')),
            'overall_kpi' => round((float) ($summary['overall_kpi'] ?? 0), 2),
            'total_kpis' => (int) ($summary['total_kpis'] ?? 0),
            'achieved_kpis' => (int) ($summary['achieved_kpis'] ?? 0),
            'in_progress_kpis' => (int) ($summary['in_progress_kpis'] ?? 0),
            'not_achieved_kpis' => (int) ($summary['not_achieved_kpis'] ?? 0),
        ];

        return $employeeSummary;
    }

    public function createKpi(array $data): bool
    {
        $this->ensureSchema();

        $employeeId = (int) ($data['employee_id'] ?? 0);
        $departmentId = (int) ($data['department_id'] ?? 0);
        $title = trim((string) ($data['kpi_title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $targetValue = (float) ($data['target_value'] ?? 0);
        $actualValue = (float) ($data['actual_value'] ?? 0);
        $unit = trim((string) ($data['unit'] ?? '%'));
        $weight = (float) ($data['weight'] ?? 0);
        $startDate = trim((string) ($data['start_date'] ?? '')) ?: null;
        $dueDate = trim((string) ($data['due_date'] ?? '')) ?: null;

        $progress = $this->calculateProgress($actualValue, $targetValue);
        $status = $this->normalizeStatus($progress);

        $sql = "INSERT INTO school_kpis (employee_id, department_id, kpi_title, description, target_value, actual_value, unit, weight, status, start_date, due_date)
                VALUES (:employee_id, :department_id, :kpi_title, :description, :target_value, :actual_value, :unit, :weight, :status, :start_date, :due_date)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':department_id', $departmentId > 0 ? $departmentId : null, $departmentId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':kpi_title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description !== '' ? $description : null, $description !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':target_value', $targetValue, PDO::PARAM_STR);
        $stmt->bindValue(':actual_value', $actualValue, PDO::PARAM_STR);
        $stmt->bindValue(':unit', $unit !== '' ? $unit : '%', PDO::PARAM_STR);
        $stmt->bindValue(':weight', $weight, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':start_date', $startDate, $startDate ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':due_date', $dueDate, $dueDate ? PDO::PARAM_STR : PDO::PARAM_NULL);

        $saved = $stmt->execute();

        if (!$saved) {
            return false;
        }

        $kpiId = (int) $this->conn->lastInsertId();
        $this->recordHistory($kpiId, $employeeId, 0, $actualValue, $progress, 'KPI created');

        return true;
    }

    public function updateKpi(int $kpiId, array $data): bool
    {
        $this->ensureSchema();
        $existing = $this->getKpiById($kpiId);
        if (!$existing) {
            return false;
        }

        $employeeId = (int) ($data['employee_id'] ?? (int) ($existing['employee_id'] ?? 0));
        $departmentId = (int) ($data['department_id'] ?? (int) ($existing['department_id'] ?? 0));
        $targetValue = (float) ($data['target_value'] ?? (float) ($existing['target_value'] ?? 0));
        $actualValue = (float) ($data['actual_value'] ?? (float) ($existing['actual_value'] ?? 0));
        $status = trim((string) ($data['status'] ?? ''));
        $progress = $this->calculateProgress($actualValue, $targetValue);
        $resolvedStatus = $status !== '' ? $status : $this->normalizeStatus($progress);

        $sql = "UPDATE school_kpis
                SET employee_id = :employee_id,
                    department_id = :department_id,
                    kpi_title = :kpi_title,
                    description = :description,
                    target_value = :target_value,
                    actual_value = :actual_value,
                    unit = :unit,
                    weight = :weight,
                    status = :status,
                    start_date = :start_date,
                    due_date = :due_date,
                    updated_at = CURRENT_TIMESTAMP
                WHERE kpi_id = :kpi_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':department_id', $departmentId > 0 ? $departmentId : null, $departmentId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':kpi_title', trim((string) ($data['kpi_title'] ?? $existing['kpi_title'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':description', trim((string) ($data['description'] ?? $existing['description'] ?? '')) !== '' ? trim((string) ($data['description'] ?? $existing['description'] ?? '')) : null, trim((string) ($data['description'] ?? $existing['description'] ?? '')) !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':target_value', $targetValue, PDO::PARAM_STR);
        $stmt->bindValue(':actual_value', $actualValue, PDO::PARAM_STR);
        $stmt->bindValue(':unit', trim((string) ($data['unit'] ?? $existing['unit'] ?? '%')) !== '' ? trim((string) ($data['unit'] ?? $existing['unit'] ?? '%')) : '%', PDO::PARAM_STR);
        $stmt->bindValue(':weight', (float) ($data['weight'] ?? $existing['weight'] ?? 0), PDO::PARAM_STR);
        $stmt->bindValue(':status', $resolvedStatus, PDO::PARAM_STR);
        $stmt->bindValue(':start_date', trim((string) ($data['start_date'] ?? $existing['start_date'] ?? '')) ?: null, trim((string) ($data['start_date'] ?? $existing['start_date'] ?? '')) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':due_date', trim((string) ($data['due_date'] ?? $existing['due_date'] ?? '')) ?: null, trim((string) ($data['due_date'] ?? $existing['due_date'] ?? '')) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':kpi_id', $kpiId, PDO::PARAM_INT);
        $updated = $stmt->execute();

        if ($updated) {
            $this->recordHistory($kpiId, $employeeId, (float) ($existing['actual_value'] ?? 0), $actualValue, $progress, 'KPI updated');
        }

        return $updated;
    }

    public function getKpiById(int $kpiId): ?array
    {
        $this->ensureSchema();
        $sql = "SELECT k.*,
                       CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                       COALESCE(d.department_name, 'Unassigned') AS department_name,
                       CASE
                           WHEN k.target_value > 0 THEN LEAST((k.actual_value / k.target_value) * 100, 100)
                           ELSE 0
                       END AS progress_percentage
                FROM school_kpis k
                LEFT JOIN {$this->employeeTable()} e ON e.employee_id = k.employee_id
                LEFT JOIN {$this->departmentTable()} d ON d.department_id = COALESCE(k.department_id, e.department_id)
                WHERE k.kpi_id = :kpi_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':kpi_id', $kpiId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $row['progress_percentage'] = round((float) ($row['progress_percentage'] ?? 0), 1);
        $row['status'] = trim((string) ($row['status'] ?: $this->normalizeStatus((float) $row['progress_percentage'])));

        return $row;
    }

    private function recordHistory(int $kpiId, int $employeeId, float $previousValue, float $newValue, float $progress, string $remarks): void
    {
        $sql = "INSERT INTO school_kpi_history (kpi_id, employee_id, previous_value, new_value, progress_percentage, remarks, recorded_by)
                VALUES (:kpi_id, :employee_id, :previous_value, :new_value, :progress_percentage, :remarks, :recorded_by)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':kpi_id', $kpiId, PDO::PARAM_INT);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':previous_value', $previousValue, PDO::PARAM_STR);
        $stmt->bindValue(':new_value', $newValue, PDO::PARAM_STR);
        $stmt->bindValue(':progress_percentage', $progress, PDO::PARAM_STR);
        $stmt->bindValue(':remarks', $remarks, PDO::PARAM_STR);
        $stmt->bindValue(':recorded_by', $_SESSION['employee_name'] ?? 'System', PDO::PARAM_STR);
        $stmt->execute();
    }
}
