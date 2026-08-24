<?php
require_once __DIR__ . '/../../../database/db.php';

class Appraisal
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

    private function fetchScalar(string $sql, array $params = []): int
    {
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value['value'], $value['type'] ?? PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
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

    private function employeeTable(): string
    {
        if ($this->tableExists('em_employees')) {
            return 'em_employees';
        }

        return $this->tableExists('hrms_employee') ? 'hrms_employee' : 'em_employees';
    }

    public function getEmployees(): array
    {
        $table = $this->employeeTable();
        if (!$this->tableExists($table)) {
            return [];
        }

        if ($table === 'em_employees') {
            $sql = "SELECT e.employee_id,
                           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                           d.department_name AS department,
                           p.position_name AS position
                    FROM em_employees e
                    LEFT JOIN em_departments d ON d.department_id = e.department_id
                    LEFT JOIN em_positions p ON p.position_id = e.position_id
                    WHERE e.is_archived = 0
                    ORDER BY e.first_name, e.last_name";
        } else {
            $sql = "SELECT e.employee_id,
                           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                           e.department,
                           e.position
                    FROM {$table} e
                    ORDER BY e.first_name, e.last_name";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardStats(): array
    {
        $stats = [
            'total_appraisals' => 0,
            'pending_appraisals' => 0,
            'in_progress_appraisals' => 0,
            'completed_appraisals' => 0,
            'overdue_appraisals' => 0,
            'average_rating' => null,
            'active_cycles' => 0,
        ];

        if (!$this->tableExists('pm_appraisals')) {
            return $stats;
        }

        $stats['total_appraisals'] = $this->fetchScalar('SELECT COUNT(*) FROM pm_appraisals');
        $stats['pending_appraisals'] = $this->fetchScalar(
            "SELECT COUNT(*) FROM pm_appraisals WHERE status IN ('Pending', 'Not Started')"
        );
        $stats['in_progress_appraisals'] = $this->fetchScalar(
            "SELECT COUNT(*) FROM pm_appraisals WHERE status IN ('In Progress', 'In Progress ')"
        );
        $stats['completed_appraisals'] = $this->fetchScalar(
            "SELECT COUNT(*) FROM pm_appraisals WHERE status = 'Completed'"
        );
        $stats['overdue_appraisals'] = $this->fetchScalar(
            "SELECT COUNT(*) FROM pm_appraisals WHERE due_date < CURDATE() AND status NOT IN ('Completed', 'Cancelled', 'Archived', 'Closed')"
        );

        $avgRow = $this->fetchAll(
            'SELECT AVG(overall_rating) AS avg_rating FROM pm_appraisals WHERE overall_rating IS NOT NULL AND overall_rating > 0'
        );
        if (!empty($avgRow[0]['avg_rating'])) {
            $stats['average_rating'] = round((float) $avgRow[0]['avg_rating'], 1);
        }

        if ($this->tableExists('pm_review_cycles')) {
            $stats['active_cycles'] = $this->fetchScalar(
                "SELECT COUNT(*) FROM pm_review_cycles WHERE status IN ('Active', 'In Progress')"
            );
        }

        return $stats;
    }

    public function getStatusSummary(): array
    {
        if (!$this->tableExists('pm_appraisals')) {
            return [];
        }

        $rows = $this->fetchAll(
            'SELECT status, COUNT(*) AS total FROM pm_appraisals GROUP BY status ORDER BY total DESC'
        );

        $summary = [];
        foreach ($rows as $row) {
            $summary[(string) $row['status']] = (int) $row['total'];
        }

        return $summary;
    }

    public function getReviewCycles(): array
    {
        if (!$this->tableExists('pm_review_cycles')) {
            return [];
        }

        return $this->fetchAll(
            'SELECT rc.*,
                    (SELECT COUNT(*) FROM pm_appraisals a WHERE a.review_cycle_id = rc.cycle_id) AS appraisal_count
             FROM pm_review_cycles rc
             ORDER BY rc.created_at DESC'
        );
    }

    public function getAppraisals(array $filters = []): array
    {
        if (!$this->tableExists('pm_appraisals')) {
            return [];
        }

        $sql = "SELECT a.*,
                       COALESCE(rc.title, 'N/A') AS cycle_title,
                       COALESCE(rc.cycle_period, 'N/A') AS appraisal_period,
                       rc.appraisal_type
                FROM pm_appraisals a
                LEFT JOIN pm_review_cycles rc ON rc.cycle_id = a.review_cycle_id
                WHERE 1 = 1";

        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (a.employee_name LIKE :search OR a.department LIKE :search OR a.reviewer_name LIKE :search OR rc.cycle_period LIKE :search)';
            $params[':search'] = ['value' => '%' . trim((string) $filters['search']) . '%', 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND a.status = :status';
            $params[':status'] = ['value' => trim((string) $filters['status']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['employee_id'])) {
            $sql .= ' AND a.employee_id = :employee_id';
            $params[':employee_id'] = ['value' => (int) $filters['employee_id'], 'type' => PDO::PARAM_INT];
        }

        if (!empty($filters['department'])) {
            $sql .= ' AND a.department = :department';
            $params[':department'] = ['value' => trim((string) $filters['department']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['cycle_id'])) {
            $sql .= ' AND a.review_cycle_id = :cycle_id';
            $params[':cycle_id'] = ['value' => (int) $filters['cycle_id'], 'type' => PDO::PARAM_INT];
        }

        if (!empty($filters['from_date'])) {
            $sql .= ' AND a.due_date >= :from_date';
            $params[':from_date'] = ['value' => trim((string) $filters['from_date']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['to_date'])) {
            $sql .= ' AND a.due_date <= :to_date';
            $params[':to_date'] = ['value' => trim((string) $filters['to_date']), 'type' => PDO::PARAM_STR];
        }

        $sql .= ' ORDER BY a.updated_at DESC, a.appraisal_id DESC';

        return $this->fetchAll($sql, $params);
    }

    public function getAppraisalById(int $appraisalId): ?array
    {
        if ($appraisalId <= 0 || !$this->tableExists('pm_appraisals')) {
            return null;
        }

        $rows = $this->fetchAll(
            "SELECT a.*,
                    COALESCE(rc.title, 'N/A') AS cycle_title,
                    COALESCE(rc.cycle_period, 'N/A') AS appraisal_period,
                    rc.appraisal_type,
                    rc.description AS cycle_description
             FROM pm_appraisals a
             LEFT JOIN pm_review_cycles rc ON rc.cycle_id = a.review_cycle_id
             WHERE a.appraisal_id = :appraisal_id
             LIMIT 1",
            [':appraisal_id' => ['value' => $appraisalId, 'type' => PDO::PARAM_INT]]
        );

        return $rows[0] ?? null;
    }

    public function getAppraisalItems(int $appraisalId): array
    {
        if ($appraisalId <= 0 || !$this->tableExists('pm_appraisal_items')) {
            return [];
        }

        return $this->fetchAll(
            'SELECT * FROM pm_appraisal_items WHERE appraisal_id = :appraisal_id ORDER BY item_id ASC',
            [':appraisal_id' => ['value' => $appraisalId, 'type' => PDO::PARAM_INT]]
        );
    }

    public function getAppraisalHistory(int $appraisalId): array
    {
        if ($appraisalId <= 0 || !$this->tableExists('pm_appraisal_history')) {
            return [];
        }

        return $this->fetchAll(
            'SELECT * FROM pm_appraisal_history WHERE appraisal_id = :appraisal_id ORDER BY created_at DESC',
            [':appraisal_id' => ['value' => $appraisalId, 'type' => PDO::PARAM_INT]]
        );
    }

    public function createReviewCycle(array $data): bool
    {
        if (!$this->tableExists('pm_review_cycles')) {
            return false;
        }

        $sql = 'INSERT INTO pm_review_cycles
                (title, cycle_period, appraisal_type, start_date, end_date, description, status)
                VALUES
                (:title, :cycle_period, :appraisal_type, :start_date, :end_date, :description, :status)';

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':title' => trim((string) ($data['title'] ?? '')),
            ':cycle_period' => trim((string) ($data['cycle_period'] ?? '')),
            ':appraisal_type' => trim((string) ($data['appraisal_type'] ?? 'Annual')),
            ':start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            ':end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
            ':description' => trim((string) ($data['description'] ?? '')),
            ':status' => trim((string) ($data['status'] ?? 'Active')),
        ]);
    }

    public function createAppraisal(array $data): bool
    {
        if (!$this->tableExists('pm_appraisals')) {
            return false;
        }

        $sql = 'INSERT INTO pm_appraisals
                (employee_id, employee_name, department, reviewer_id, reviewer_name, status, due_date, review_cycle_id)
                VALUES
                (:employee_id, :employee_name, :department, :reviewer_id, :reviewer_name, :status, :due_date, :review_cycle_id)';

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':employee_id' => (int) ($data['employee_id'] ?? 0),
            ':employee_name' => trim((string) ($data['employee_name'] ?? '')),
            ':department' => trim((string) ($data['department'] ?? '')),
            ':reviewer_id' => !empty($data['reviewer_id']) ? (int) $data['reviewer_id'] : null,
            ':reviewer_name' => trim((string) ($data['reviewer_name'] ?? '')),
            ':status' => trim((string) ($data['status'] ?? 'Not Started')),
            ':due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
            ':review_cycle_id' => !empty($data['review_cycle_id']) ? (int) $data['review_cycle_id'] : null,
        ]);

        if ($result) {
            $appraisalId = (int) $this->conn->lastInsertId();
            $this->logHistory($appraisalId, 'Appraisal created', 'New appraisal record created.', $data['created_by'] ?? 'System');
        }

        return $result;
    }

    public function updateAppraisal(array $data): bool
    {
        if (!$this->tableExists('pm_appraisals')) {
            return false;
        }

        $appraisalId = (int) ($data['appraisal_id'] ?? 0);
        if ($appraisalId <= 0) {
            return false;
        }

        $sql = 'UPDATE pm_appraisals SET
                    employee_id = :employee_id,
                    employee_name = :employee_name,
                    department = :department,
                    reviewer_id = :reviewer_id,
                    reviewer_name = :reviewer_name,
                    status = :status,
                    overall_rating = :overall_rating,
                    due_date = :due_date,
                    review_cycle_id = :review_cycle_id
                WHERE appraisal_id = :appraisal_id';

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':employee_id' => (int) ($data['employee_id'] ?? 0),
            ':employee_name' => trim((string) ($data['employee_name'] ?? '')),
            ':department' => trim((string) ($data['department'] ?? '')),
            ':reviewer_id' => !empty($data['reviewer_id']) ? (int) $data['reviewer_id'] : null,
            ':reviewer_name' => trim((string) ($data['reviewer_name'] ?? '')),
            ':status' => trim((string) ($data['status'] ?? 'Not Started')),
            ':overall_rating' => ($data['overall_rating'] ?? '') !== '' ? (float) $data['overall_rating'] : null,
            ':due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
            ':review_cycle_id' => !empty($data['review_cycle_id']) ? (int) $data['review_cycle_id'] : null,
            ':appraisal_id' => $appraisalId,
        ]);

        if ($result) {
            $this->logHistory($appraisalId, 'Appraisal updated', 'Appraisal details were updated.', $data['updated_by'] ?? 'System');
        }

        return $result;
    }

    public function updateStatus(int $appraisalId, string $status, string $updatedBy = 'System', string $details = ''): bool
    {
        if ($appraisalId <= 0 || !$this->tableExists('pm_appraisals')) {
            return false;
        }

        $stmt = $this->conn->prepare('UPDATE pm_appraisals SET status = :status WHERE appraisal_id = :appraisal_id');
        $result = $stmt->execute([
            ':status' => $status,
            ':appraisal_id' => $appraisalId,
        ]);

        if ($result) {
            $this->logHistory(
                $appraisalId,
                'Status changed',
                $details !== '' ? $details : 'Status updated to ' . $status . '.',
                $updatedBy
            );
        }

        return $result;
    }

    public function saveAppraisalItems(int $appraisalId, array $items, string $updatedBy = 'System'): bool
    {
        if ($appraisalId <= 0 || !$this->tableExists('pm_appraisal_items')) {
            return false;
        }

        $this->conn->beginTransaction();

        try {
            $deleteStmt = $this->conn->prepare('DELETE FROM pm_appraisal_items WHERE appraisal_id = :appraisal_id');
            $deleteStmt->execute([':appraisal_id' => $appraisalId]);

            $insertStmt = $this->conn->prepare(
                'INSERT INTO pm_appraisal_items (appraisal_id, criterion, rating, comments) VALUES (:appraisal_id, :criterion, :rating, :comments)'
            );

            $ratings = [];
            foreach ($items as $item) {
                $criterion = trim((string) ($item['criterion'] ?? ''));
                if ($criterion === '') {
                    continue;
                }

                $rating = ($item['rating'] ?? '') !== '' ? (float) $item['rating'] : null;
                if ($rating !== null) {
                    $ratings[] = $rating;
                }

                $insertStmt->execute([
                    ':appraisal_id' => $appraisalId,
                    ':criterion' => $criterion,
                    ':rating' => $rating,
                    ':comments' => trim((string) ($item['comments'] ?? '')),
                ]);
            }

            $overallRating = null;
            if (!empty($ratings)) {
                $overallRating = round(array_sum($ratings) / count($ratings), 2);
            }

            $updateStmt = $this->conn->prepare(
                'UPDATE pm_appraisals SET overall_rating = :overall_rating WHERE appraisal_id = :appraisal_id'
            );
            $updateStmt->execute([
                ':overall_rating' => $overallRating,
                ':appraisal_id' => $appraisalId,
            ]);

            $this->logHistory($appraisalId, 'Ratings saved', 'Appraisal criteria ratings were updated.', $updatedBy);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function logHistory(int $appraisalId, string $action, string $details = '', string $createdBy = 'System'): void
    {
        if (!$this->tableExists('pm_appraisal_history')) {
            return;
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO pm_appraisal_history (appraisal_id, action, details, created_by) VALUES (:appraisal_id, :action, :details, :created_by)'
        );
        $stmt->execute([
            ':appraisal_id' => $appraisalId,
            ':action' => $action,
            ':details' => $details,
            ':created_by' => $createdBy,
        ]);
    }

    public function getDefaultCriteria(): array
    {
        return [
            'Job Knowledge',
            'Quality of Work',
            'Productivity',
            'Communication',
            'Teamwork',
            'Initiative',
            'Attendance & Punctuality',
            'Leadership',
        ];
    }
}
