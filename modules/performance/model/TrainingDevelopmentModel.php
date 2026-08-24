<?php

require_once __DIR__ . '/../../../database/db.php';

class TrainingDevelopmentModel
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

    public function tableExists(string $tableName): bool
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

    private function employeeTable(): string
    {
        if ($this->tableExists('em_employees')) {
            return 'em_employees';
        }

        if ($this->tableExists('hrms_employee')) {
            return 'hrms_employee';
        }

        return 'em_employees';
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value['value'], $value['type'] ?? PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchScalar(string $sql, array $params = []): ?string
    {
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value['value'], $value['type'] ?? PDO::PARAM_STR);
        }
        $stmt->execute();
        $result = $stmt->fetchColumn();

        return $result === false ? null : (string) $result;
    }

    public function getEmployees(): array
    {
        $employeeTable = $this->employeeTable();
        if (!$this->tableExists($employeeTable)) {
            return [];
        }

        $firstNameCol = $this->columnExists($employeeTable, 'first_name') ? 'first_name' : 'employee_first_name';
        $lastNameCol = $this->columnExists($employeeTable, 'last_name') ? 'last_name' : 'employee_last_name';
        $idColumn = $this->columnExists($employeeTable, 'employee_id') ? 'employee_id' : 'id';

        $sql = "SELECT {$idColumn} AS employee_id,
                       CONCAT(COALESCE({$firstNameCol}, ''), ' ', COALESCE({$lastNameCol}, '')) AS employee_name
                FROM {$employeeTable}
                WHERE {$firstNameCol} IS NOT NULL OR {$lastNameCol} IS NOT NULL
                ORDER BY employee_name ASC";

        return $this->fetchAll($sql);
    }

    private function columnExists(string $table, string $column): bool
    {
        $sql = "SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = :table
                  AND column_name = :column";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':table', $table, PDO::PARAM_STR);
        $stmt->bindParam(':column', $column, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    private function employeeNameExpression(string $employeeTable): string
    {
        $firstNameCol = $this->columnExists($employeeTable, 'first_name') ? 'first_name' : 'employee_first_name';
        $lastNameCol = $this->columnExists($employeeTable, 'last_name') ? 'last_name' : 'employee_last_name';

        return "CONCAT(COALESCE(e.{$firstNameCol}, ''), ' ', COALESCE(e.{$lastNameCol}, ''))";
    }

    public function getPrograms(): array
    {
        if (!$this->tableExists('pm_training_programs')) {
            return [];
        }

        return $this->fetchAll(
            'SELECT training_id, training_title, training_category, skill_focus, description, training_type, duration_hours, delivery_mode, status
             FROM pm_training_programs
             WHERE status = "Active"
             ORDER BY training_title ASC'
        );
    }

    public function getDashboardStats(): array
    {
        $stats = [
            'total_recommendations' => 0,
            'upcoming_training' => 0,
            'registered_employees' => 0,
            'ongoing_training' => 0,
            'completed_training' => 0,
            'high_priority' => 0,
            'low_priority' => 0,
        ];

        if ($this->tableExists('pm_training_recommendations')) {
            $stats['total_recommendations'] = (int) $this->fetchScalar('SELECT COUNT(*) FROM pm_training_recommendations');
            $stats['upcoming_training'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_training_recommendations WHERE status IN ('Pending', 'Approved', 'In Progress') AND recommendation_date >= CURDATE()");
            $stats['ongoing_training'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_training_recommendations WHERE status IN ('In Progress', 'Approved')");
            $stats['completed_training'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_training_recommendations WHERE status = 'Completed'");
            $stats['high_priority'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_training_recommendations WHERE priority_level IN ('High', 'Critical')");
            $stats['low_priority'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_training_recommendations WHERE priority_level = 'Low'");
            $stats['registered_employees'] = (int) $this->fetchScalar('SELECT COUNT(DISTINCT employee_id) FROM pm_training_recommendations');
        }

        return $stats;
    }

    public function getRecommendations(array $filters = []): array
    {
        if (!$this->tableExists('pm_training_recommendations')) {
            return [];
        }

        $employeeTable = $this->employeeTable();
        $employeeIdCol = $this->columnExists($employeeTable, 'employee_id') ? 'employee_id' : 'id';
        $firstNameCol = $this->columnExists($employeeTable, 'first_name') ? 'first_name' : 'employee_first_name';
        $lastNameCol = $this->columnExists($employeeTable, 'last_name') ? 'last_name' : 'employee_last_name';
        $employeeNameSql = "CONCAT(COALESCE(e.{$firstNameCol}, ''), ' ', COALESCE(e.{$lastNameCol}, ''))";

        $sql = "SELECT tr.recommendation_id,
                       tr.employee_id,
                       tr.recommendation_date,
                       tr.development_area,
                       tr.performance_gap,
                       tr.recommendation_reason,
                       tr.priority_level,
                       tr.status,
                       tr.recommended_by,
                       tr.source_id,
                       tr.source_type,
                       COALESCE(tp.training_title, tr.development_area, 'Training Program') AS training_title,
                       COALESCE(tp.training_category, tr.development_area, 'Development') AS training_category,
                       COALESCE(tp.skill_focus, tr.performance_gap, 'General Capability') AS skill_focus,
                       {$employeeNameSql} AS employee_name
                FROM pm_training_recommendations tr
                LEFT JOIN {$employeeTable} e ON e.{$employeeIdCol} = tr.employee_id
                LEFT JOIN pm_training_programs tp ON tp.training_id = tr.source_id
                WHERE 1 = 1";

        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $sql .= ' AND (
                ' . $employeeNameSql . ' LIKE :search
                OR COALESCE(tp.training_title, tr.development_area, "") LIKE :search
                OR COALESCE(tp.training_category, tr.development_area, "") LIKE :search
                OR COALESCE(tr.recommendation_reason, "") LIKE :search
            )';
            $params[':search'] = ['value' => $search, 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND tr.status = :status';
            $params[':status'] = ['value' => trim((string) $filters['status']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['priority'])) {
            $sql .= ' AND tr.priority_level = :priority';
            $params[':priority'] = ['value' => trim((string) $filters['priority']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['employee_id'])) {
            $sql .= ' AND tr.employee_id = :employee_id';
            $params[':employee_id'] = ['value' => (int) $filters['employee_id'], 'type' => PDO::PARAM_INT];
        }

        if (!empty($filters['category'])) {
            $sql .= ' AND COALESCE(tp.training_category, tr.development_area, "") LIKE :category';
            $params[':category'] = ['value' => '%' . trim((string) $filters['category']) . '%', 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['from_date'])) {
            $sql .= ' AND tr.recommendation_date >= :from_date';
            $params[':from_date'] = ['value' => trim((string) $filters['from_date']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['to_date'])) {
            $sql .= ' AND tr.recommendation_date <= :to_date';
            $params[':to_date'] = ['value' => trim((string) $filters['to_date']), 'type' => PDO::PARAM_STR];
        }

        $sql .= ' ORDER BY tr.recommendation_date DESC, tr.created_at DESC';

        return $this->fetchAll($sql, $params);
    }

    public function createRecommendation(array $data): bool
    {
        if (!$this->tableExists('pm_training_recommendations')) {
            return false;
        }

        $employeeId = (int) ($data['employee_id'] ?? 0);
        $programId = isset($data['training_program_id']) ? (int) $data['training_program_id'] : 0;
        $priority = trim((string) ($data['priority_level'] ?? 'Medium'));
        $status = trim((string) ($data['status'] ?? 'Pending'));
        $recommendedBy = trim((string) ($data['recommended_by'] ?? 'System'));
        $recommendationDate = trim((string) ($data['recommendation_date'] ?? date('Y-m-d')));
        $reason = trim((string) ($data['recommendation_reason'] ?? ''));
        $developmentArea = trim((string) ($data['development_area'] ?? ''));
        $performanceGap = trim((string) ($data['performance_gap'] ?? ''));

        if ($employeeId <= 0 || $reason === '' || $developmentArea === '') {
            return false;
        }

        if ($programId > 0 && $this->tableExists('pm_training_programs')) {
            $program = $this->fetchAll('SELECT training_title, training_category, skill_focus FROM pm_training_programs WHERE training_id = :training_id', [
                ':training_id' => ['value' => $programId, 'type' => PDO::PARAM_INT],
            ]);

            if (!empty($program)) {
                $developmentArea = $developmentArea === '' ? (string) ($program[0]['training_title'] ?? '') : $developmentArea;
            }
        }

        $sql = 'INSERT INTO pm_training_recommendations (
                    employee_id,
                    recommendation_date,
                    source_type,
                    source_id,
                    development_area,
                    performance_gap,
                    recommendation_reason,
                    priority_level,
                    recommended_by,
                    status,
                    target_completion_date,
                    created_at
                ) VALUES (
                    :employee_id,
                    :recommendation_date,
                    :source_type,
                    :source_id,
                    :development_area,
                    :performance_gap,
                    :recommendation_reason,
                    :priority_level,
                    :recommended_by,
                    :status,
                    :target_completion_date,
                    NOW()
                )';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':recommendation_date', $recommendationDate ?: date('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':source_type', $programId > 0 ? 'Program' : 'Manual', PDO::PARAM_STR);
        $stmt->bindValue(':source_id', $programId > 0 ? $programId : null, $programId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':development_area', $developmentArea, PDO::PARAM_STR);
        $stmt->bindValue(':performance_gap', $performanceGap !== '' ? $performanceGap : $reason, PDO::PARAM_STR);
        $stmt->bindValue(':recommendation_reason', $reason, PDO::PARAM_STR);
        $stmt->bindValue(':priority_level', in_array($priority, ['Low', 'Medium', 'High', 'Critical'], true) ? $priority : 'Medium', PDO::PARAM_STR);
        $stmt->bindValue(':recommended_by', $recommendedBy !== '' ? $recommendedBy : 'System', PDO::PARAM_STR);
        $stmt->bindValue(':status', in_array($status, ['Pending', 'Approved', 'Rejected', 'In Progress', 'Completed'], true) ? $status : 'Pending', PDO::PARAM_STR);
        $stmt->bindValue(':target_completion_date', !empty($data['target_completion_date']) ? trim((string) $data['target_completion_date']) : null, PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function getCategorySummary(): array
    {
        if (!$this->tableExists('pm_training_recommendations')) {
            return [];
        }

        $rows = $this->fetchAll(
            'SELECT COALESCE(tp.training_category, tr.development_area, "General") AS category, COUNT(*) AS total
             FROM pm_training_recommendations tr
             LEFT JOIN pm_training_programs tp ON tp.training_id = tr.source_id
             GROUP BY COALESCE(tp.training_category, tr.development_area, "General")
             ORDER BY total DESC'
        );

        return $rows;
    }

    public function getUpcomingTraining(): array
    {
        if (!$this->tableExists('pm_training_recommendations')) {
            return [];
        }

        $employeeTable = $this->employeeTable();
        $employeeIdCol = $this->columnExists($employeeTable, 'employee_id') ? 'employee_id' : 'id';
        $firstNameCol = $this->columnExists($employeeTable, 'first_name') ? 'first_name' : 'employee_first_name';
        $lastNameCol = $this->columnExists($employeeTable, 'last_name') ? 'last_name' : 'employee_last_name';
        $employeeNameSql = "CONCAT(COALESCE(e.{$firstNameCol}, ''), ' ', COALESCE(e.{$lastNameCol}, ''))";

        return $this->fetchAll(
            "SELECT tr.recommendation_id,
                    COALESCE(tp.training_title, tr.development_area, 'Training Program') AS training_title,
                    COALESCE(tp.training_category, tr.development_area, 'Development') AS training_category,
                    {$employeeNameSql} AS employee_name,
                    tr.priority_level,
                    tr.status,
                    tr.recommendation_date
             FROM pm_training_recommendations tr
             LEFT JOIN pm_training_programs tp ON tp.training_id = tr.source_id
             LEFT JOIN {$employeeTable} e ON e.{$employeeIdCol} = tr.employee_id
             WHERE tr.recommendation_date IS NOT NULL
             ORDER BY tr.recommendation_date ASC
             LIMIT 6"
        );
    }
}
