<?php
require_once __DIR__ . '/../../../database/db.php';

class EvaluationModel
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

    public function getDashboardStats(): array
    {
        $totalEmployees = (int) $this->fetchScalar(
            "SELECT COUNT(*) FROM em_employees WHERE is_archived = 0"
        );

        $completed = (int) $this->fetchScalar(
            "SELECT COUNT(*) FROM pm_evaluations WHERE status = 'Completed'"
        );

        $inProgress = (int) $this->fetchScalar(
            "SELECT COUNT(*) FROM pm_evaluations WHERE status = 'In Progress'"
        );

        $evaluatedEmployees = (int) $this->fetchScalar(
            "SELECT COUNT(DISTINCT employee_id) FROM pm_evaluations WHERE status IN ('Completed', 'In Progress')"
        );

        $notEvaluated = max(0, $totalEmployees - $evaluatedEmployees);

        return [
            'total_employees' => $totalEmployees,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'not_evaluated' => $notEvaluated,
        ];
    }

    public function getEmployees(): array
    {
        $sql = "SELECT e.employee_id,
                       e.employee_code,
                       TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) AS employee_name,
                       COALESCE(d.department_name, 'N/A') AS department,
                       COALESCE(p.position_name, 'N/A') AS position,
                       e.employment_status
                FROM em_employees e
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                WHERE e.is_archived = 0
                ORDER BY e.employee_id ASC";

        return $this->fetchAll($sql);
    }

    public function getEmployeeById(int $employeeId): ?array
    {
        $sql = "SELECT e.employee_id,
                       e.employee_code,
                       TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) AS employee_name,
                       COALESCE(d.department_name, 'N/A') AS department,
                       COALESCE(p.position_name, 'N/A') AS position
                FROM em_employees e
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                WHERE e.employee_id = :employee_id AND e.is_archived = 0
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
    }

    public function searchEmployees(string $term, int $limit = 10): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $likeTerm = '%' . $term . '%';
        $sql = "SELECT e.employee_id,
                       e.employee_code,
                       TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) AS employee_name,
                       COALESCE(d.department_name, 'N/A') AS department,
                       COALESCE(p.position_name, 'N/A') AS position
                FROM em_employees e
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                WHERE e.is_archived = 0 AND (
                    e.employee_code LIKE :term OR
                    TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, ''))) LIKE :term OR
                    COALESCE(d.department_name, '') LIKE :term OR
                    COALESCE(p.position_name, '') LIKE :term
                )
                ORDER BY e.employee_id ASC
                LIMIT :limit";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':term', $likeTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEvaluations(array $filters = []): array
    {
        $sql = "SELECT ev.evaluation_id,
                       ev.employee_id,
                       ev.employee_name,
                       ev.department,
                       ev.position,
                       ev.evaluation_cycle,
                       ev.overall_score,
                       ev.overall_rating,
                       ev.status,
                       ev.evaluation_date,
                       ev.evaluation_period_start,
                       ev.evaluation_period_end,
                       ev.evaluator_name
                FROM pm_evaluations ev
                WHERE 1 = 1";

        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $sql .= " AND (
                ev.employee_name LIKE :search OR
                CAST(ev.employee_id AS CHAR) LIKE :search OR
                ev.department LIKE :search OR
                ev.position LIKE :search OR
                ev.evaluation_cycle LIKE :search
            )";
            $params[':search'] = ['value' => $search, 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['department'])) {
            $sql .= ' AND ev.department = :department';
            $params[':department'] = ['value' => trim((string) $filters['department']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['cycle'])) {
            $sql .= ' AND ev.evaluation_cycle = :cycle';
            $params[':cycle'] = ['value' => trim((string) $filters['cycle']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND ev.status = :status';
            $params[':status'] = ['value' => trim((string) $filters['status']), 'type' => PDO::PARAM_STR];
        }

        $sql .= ' ORDER BY ev.evaluation_date DESC, ev.evaluation_id DESC';

        return $this->fetchAllWithParams($sql, $params);
    }

    public function getEvaluationById(int $evaluationId): ?array
    {
        $sql = "SELECT ev.*
                FROM pm_evaluations ev
                WHERE ev.evaluation_id = :evaluation_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':evaluation_id', $evaluationId, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
    }

    public function getEvaluationCriteria(int $evaluationId): array
    {
        $sql = "SELECT criterion_id, evaluation_id, criterion_name, rating, score, comments
                FROM pm_evaluation_criteria
                WHERE evaluation_id = :evaluation_id
                ORDER BY criterion_id ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':evaluation_id', $evaluationId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createEvaluation(array $data): int
    {
        $sql = "INSERT INTO pm_evaluations (
                    employee_id,
                    employee_name,
                    department,
                    position,
                    evaluation_cycle,
                    evaluation_period_start,
                    evaluation_period_end,
                    evaluator_id,
                    evaluator_name,
                    evaluation_date,
                    overall_score,
                    overall_rating,
                    status,
                    strengths,
                    areas_for_improvement,
                    recommended_training,
                    final_remarks,
                    created_at,
                    updated_at
                ) VALUES (
                    :employee_id,
                    :employee_name,
                    :department,
                    :position,
                    :evaluation_cycle,
                    :evaluation_period_start,
                    :evaluation_period_end,
                    :evaluator_id,
                    :evaluator_name,
                    :evaluation_date,
                    :overall_score,
                    :overall_rating,
                    :status,
                    :strengths,
                    :areas_for_improvement,
                    :recommended_training,
                    :final_remarks,
                    NOW(),
                    NOW()
                )";

        $stmt = $this->conn->prepare($sql);
        $this->bindCommonEvaluationValues($stmt, $data);
        $stmt->execute();

        return (int) $this->conn->lastInsertId();
    }

    public function updateEvaluation(array $data): bool
    {
        $sql = "UPDATE pm_evaluations
                SET employee_id = :employee_id,
                    employee_name = :employee_name,
                    department = :department,
                    position = :position,
                    evaluation_cycle = :evaluation_cycle,
                    evaluation_period_start = :evaluation_period_start,
                    evaluation_period_end = :evaluation_period_end,
                    evaluator_id = :evaluator_id,
                    evaluator_name = :evaluator_name,
                    evaluation_date = :evaluation_date,
                    overall_score = :overall_score,
                    overall_rating = :overall_rating,
                    status = :status,
                    strengths = :strengths,
                    areas_for_improvement = :areas_for_improvement,
                    recommended_training = :recommended_training,
                    final_remarks = :final_remarks,
                    updated_at = NOW()
                WHERE evaluation_id = :evaluation_id";

        $stmt = $this->conn->prepare($sql);
        $this->bindCommonEvaluationValues($stmt, $data, true);
        $stmt->bindValue(':evaluation_id', (int) $data['evaluation_id'], PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function saveCriteria(int $evaluationId, array $criteria): bool
    {
        $this->conn->beginTransaction();

        try {
            $deleteSql = 'DELETE FROM pm_evaluation_criteria WHERE evaluation_id = :evaluation_id';
            $deleteStmt = $this->conn->prepare($deleteSql);
            $deleteStmt->bindValue(':evaluation_id', $evaluationId, PDO::PARAM_INT);
            $deleteStmt->execute();

            if ($criteria === []) {
                $this->conn->commit();
                return true;
            }

            $insertSql = 'INSERT INTO pm_evaluation_criteria (evaluation_id, criterion_name, rating, score, comments, created_at, updated_at)
                          VALUES (:evaluation_id, :criterion_name, :rating, :score, :comments, NOW(), NOW())';

            $insertStmt = $this->conn->prepare($insertSql);
            foreach ($criteria as $criterion) {
                $insertStmt->bindValue(':evaluation_id', $evaluationId, PDO::PARAM_INT);
                $insertStmt->bindValue(':criterion_name', $criterion['criterion_name'], PDO::PARAM_STR);
                $insertStmt->bindValue(':rating', (int) ($criterion['rating'] ?? 0), PDO::PARAM_INT);
                $insertStmt->bindValue(':score', (float) ($criterion['score'] ?? 0), PDO::PARAM_STR);
                $insertStmt->bindValue(':comments', $criterion['comments'] ?? '', PDO::PARAM_STR);
                $insertStmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function deleteEvaluation(int $evaluationId): bool
    {
        $sql = 'DELETE FROM pm_evaluations WHERE evaluation_id = :evaluation_id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':evaluation_id', $evaluationId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getDepartments(): array
    {
        $sql = "SELECT department_name AS department
                FROM em_departments
                WHERE department_name IS NOT NULL AND department_name <> ''
                ORDER BY department_name ASC";

        return $this->fetchAll($sql);
    }

    public function getCycles(): array
    {
        $sql = "SELECT DISTINCT evaluation_cycle AS cycle_name
                FROM pm_evaluations
                WHERE evaluation_cycle IS NOT NULL AND evaluation_cycle <> ''
                ORDER BY evaluation_cycle DESC";

        return $this->fetchAll($sql);
    }

    public function getPerformanceData(int $employeeId): array
    {
        $summary = [
            'goal_setting' => 'No data available',
            'kpi_tracking' => 'No data available',
            'feedback' => 'No data available',
            'training' => 'No data available',
        ];

        if ($this->tableExists('pm_goals')) {
            $goalCount = (int) $this->fetchScalar('SELECT COUNT(*) FROM pm_goals WHERE employee_id = :employee_id', [':employee_id' => ['value' => $employeeId, 'type' => PDO::PARAM_INT]]);
            $summary['goal_setting'] = $goalCount > 0 ? $goalCount . ' goal(s)' : 'No data available';
        }

        if ($this->tableExists('pm_kpi_assignments')) {
            $kpiCount = (int) $this->fetchScalar('SELECT COUNT(*) FROM pm_kpi_assignments WHERE assignee_id = :employee_id', [':employee_id' => ['value' => $employeeId, 'type' => PDO::PARAM_INT]]);
            $summary['kpi_tracking'] = $kpiCount > 0 ? $kpiCount . ' KPI item(s)' : 'No data available';
        }

        if ($this->tableExists('pm_feedback_assignments')) {
            $feedbackCount = (int) $this->fetchScalar('SELECT COUNT(*) FROM pm_feedback_assignments WHERE employee_id = :employee_id', [':employee_id' => ['value' => $employeeId, 'type' => PDO::PARAM_INT]]);
            $summary['feedback'] = $feedbackCount > 0 ? $feedbackCount . ' feedback record(s)' : 'No data available';
        }

        if ($this->tableExists('pm_training_recommendations')) {
            $trainingCount = (int) $this->fetchScalar('SELECT COUNT(*) FROM pm_training_recommendations WHERE employee_id = :employee_id', [':employee_id' => ['value' => $employeeId, 'type' => PDO::PARAM_INT]]);
            $summary['training'] = $trainingCount > 0 ? $trainingCount . ' recommended training' : 'No data available';
        }

        return $summary;
    }

    private function bindCommonEvaluationValues(PDOStatement $stmt, array $data, bool $isUpdate = false): void
    {
        $stmt->bindValue(':employee_id', (int) ($data['employee_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':employee_name', trim((string) ($data['employee_name'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':department', trim((string) ($data['department'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':position', trim((string) ($data['position'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':evaluation_cycle', trim((string) ($data['evaluation_cycle'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':evaluation_period_start', !empty($data['evaluation_period_start']) ? $data['evaluation_period_start'] : null, !empty($data['evaluation_period_start']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':evaluation_period_end', !empty($data['evaluation_period_end']) ? $data['evaluation_period_end'] : null, !empty($data['evaluation_period_end']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':evaluator_id', !empty($data['evaluator_id']) ? (int) $data['evaluator_id'] : null, !empty($data['evaluator_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':evaluator_name', trim((string) ($data['evaluator_name'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':evaluation_date', trim((string) ($data['evaluation_date'] ?? date('Y-m-d'))), PDO::PARAM_STR);
        $stmt->bindValue(':overall_score', isset($data['overall_score']) ? (float) $data['overall_score'] : 0.0, PDO::PARAM_STR);
        $stmt->bindValue(':overall_rating', trim((string) ($data['overall_rating'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':status', trim((string) ($data['status'] ?? 'Completed')), PDO::PARAM_STR);
        $stmt->bindValue(':strengths', trim((string) ($data['strengths'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':areas_for_improvement', trim((string) ($data['areas_for_improvement'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':recommended_training', trim((string) ($data['recommended_training'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':final_remarks', trim((string) ($data['final_remarks'] ?? '')), PDO::PARAM_STR);
    }

    private function tableExists(string $tableName): bool
    {
        $sql = "SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = :table_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':table_name', $tableName, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
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

    private function fetchAllWithParams(string $sql, array $params): array
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
}
