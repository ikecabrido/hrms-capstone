<?php
require_once __DIR__ . '/../../../database/db.php';

class Goal
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

    private function employeeTable(): string
    {
        if ($this->tableExists('em_employees')) {
            return 'em_employees';
        }

        return $this->tableExists('hrms_employee') ? 'hrms_employee' : 'em_employees';
    }

    private function getEmployeeDisplayName(string $employeeId): ?string
    {
        $table = $this->employeeTable();
        if ($employeeId === '' || !$this->tableExists($table)) {
            return null;
        }

        $sql = "SELECT CONCAT(first_name, ' ', last_name) AS employee_name
                FROM {$table}
                WHERE employee_id = :employee_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['employee_name'] ?? null;
    }

    private function getSupervisorDisplayName(string $supervisorId): ?string
    {
        $table = $this->employeeTable();
        if ($supervisorId === '' || !$this->tableExists($table)) {
            return null;
        }

        $sql = "SELECT CONCAT(first_name, ' ', last_name) AS employee_name
                FROM {$table}
                WHERE employee_id = :employee_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':employee_id', $supervisorId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['employee_name'] ?? null;
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
                           p.position_name AS position,
                           e.employment_status AS status
                    FROM em_employees e
                    LEFT JOIN em_departments d ON d.department_id = e.department_id
                    LEFT JOIN em_positions p ON p.position_id = e.position_id
                    WHERE e.is_archived = 0
                    ORDER BY e.first_name, e.last_name";
        } else {
            $sql = "SELECT e.employee_id,
                           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                           e.department,
                           e.position,
                           e.status
                    FROM {$table} e
                    ORDER BY e.first_name, e.last_name";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardStats(): array
    {
        if (!$this->tableExists('pm_goals')) {
            return [
                'total_goals' => 0,
                'active_goals' => 0,
                'completed_goals' => 0,
                'in_progress_goals' => 0,
                'pending_goals' => 0,
                'overdue_goals' => 0,
                'high_priority_goals' => 0,
            ];
        }

        return [
            'total_goals' => (int) $this->fetchScalar('SELECT COUNT(*) FROM pm_goals'),
            'active_goals' => (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_goals WHERE status IN ('Active', 'In Progress', 'Pending')"),
            'completed_goals' => (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_goals WHERE status = 'Completed'"),
            'in_progress_goals' => (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_goals WHERE status = 'In Progress'"),
            'pending_goals' => (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_goals WHERE status = 'Pending'"),
            'overdue_goals' => (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_goals WHERE due_date < CURDATE() AND status NOT IN ('Completed', 'Cancelled', 'Archived')"),
            'high_priority_goals' => (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_goals WHERE priority_level IN ('High', 'Critical')"),
        ];
    }

    public function getGoals(array $filters = []): array
    {
        if (!$this->tableExists('pm_goals')) {
            return [];
        }

        $employeeTable = $this->employeeTable();

        $sql = "SELECT g.*,
                       CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                       CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) AS assigned_by_name,
                       g.goal_title AS title,
                       COALESCE(g.progress_percentage, 0) AS actual_progress,
                       COALESCE(g.target_completion_percentage, 0) AS target_progress
                FROM pm_goals g
                LEFT JOIN {$employeeTable} e ON e.employee_id = g.employee_id
                LEFT JOIN {$employeeTable} s ON s.employee_id = g.supervisor_id
                WHERE 1 = 1";

        $params = [];

        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim((string) $filters['search']) . '%';
            $sql .= ' AND (g.goal_title LIKE :search OR g.goal_description LIKE :search OR g.employee_name LIKE :search OR g.department LIKE :search)';
            $params[':search'] = ['value' => $searchTerm, 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND g.status = :status';
            $params[':status'] = ['value' => trim((string) $filters['status']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['priority'])) {
            $sql .= ' AND g.priority_level = :priority';
            $params[':priority'] = ['value' => trim((string) $filters['priority']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['category'])) {
            $sql .= ' AND g.goal_category = :category';
            $params[':category'] = ['value' => trim((string) $filters['category']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['employee_id'])) {
            $sql .= ' AND g.employee_id = :employee_id';
            $params[':employee_id'] = ['value' => (int) $filters['employee_id'], 'type' => PDO::PARAM_INT];
        }

        if (!empty($filters['department'])) {
            $sql .= ' AND g.department = :department';
            $params[':department'] = ['value' => trim((string) $filters['department']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['from_date'])) {
            $sql .= ' AND g.start_date >= :from_date';
            $params[':from_date'] = ['value' => trim((string) $filters['from_date']), 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['to_date'])) {
            $sql .= ' AND g.due_date <= :to_date';
            $params[':to_date'] = ['value' => trim((string) $filters['to_date']), 'type' => PDO::PARAM_STR];
        }

        $sql .= ' ORDER BY g.created_at DESC';

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value['value'], $value['type']);
        }
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$goal) {
            if (empty($goal['employee_name'])) {
                $goal['employee_name'] = $goal['employee_name'] ?? $this->getEmployeeDisplayName((string)($goal['employee_id'] ?? '')) ?? 'N/A';
            }
            if (empty($goal['assigned_by_name'])) {
                $goal['assigned_by_name'] = $goal['supervisor_name'] ?? $this->getSupervisorDisplayName((string)($goal['supervisor_id'] ?? '')) ?? 'System';
            }
            if (empty($goal['goal_category'])) {
                $goal['goal_category'] = 'Performance';
            }
            if (empty($goal['priority_level'])) {
                $goal['priority_level'] = 'Medium';
            }
            if (empty($goal['status'])) {
                $goal['status'] = 'Draft';
            }
            $goal['actual_progress'] = (int)($goal['actual_progress'] ?? 0);
            $goal['target_progress'] = (int)($goal['target_progress'] ?? 0);
            $goal['goal_type'] = !empty($goal['smart_notes']) && preg_match('/Goal Type:\s*(.+)/i', $goal['smart_notes'], $matches)
                ? trim($matches[1])
                : 'Individual Goal';
        }

        return $results;
    }

    public function getGoalById(int $goalId): ?array
    {
        if (!$this->tableExists('pm_goals')) {
            return null;
        }

        $employeeTable = $this->employeeTable();

        $sql = "SELECT g.*, e.first_name, e.last_name,
                       CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                       CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) AS supervisor_name
                FROM pm_goals g
                LEFT JOIN {$employeeTable} e ON e.employee_id = g.employee_id
                LEFT JOIN {$employeeTable} s ON s.employee_id = g.supervisor_id
                WHERE g.goal_id = :goal_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':goal_id', $goalId, PDO::PARAM_INT);
        $stmt->execute();

        $goal = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$goal) {
            return null;
        }

        $goal['goal_type'] = !empty($goal['smart_notes']) && preg_match('/Goal Type:\s*(.+)/i', $goal['smart_notes'], $matches)
            ? trim($matches[1])
            : 'Individual Goal';

        return $goal;
    }

    public function getGoalHistory(int $goalId): array
    {
        if (!$this->tableExists('pm_goal_history')) {
            return [];
        }

        $sql = "SELECT *
                FROM pm_goal_history
                WHERE goal_id = :goal_id
                ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':goal_id', $goalId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGoalProgressEntries(int $goalId): array
    {
        if (!$this->tableExists('pm_goal_progress')) {
            return [];
        }

        $sql = "SELECT *
                FROM pm_goal_progress
                WHERE goal_id = :goal_id
                ORDER BY update_date DESC, created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':goal_id', $goalId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGoalCategories(): array
    {
        return ['Performance', 'Productivity', 'Quality', 'Professional Development', 'Teamwork', 'Leadership', 'Operational', 'Strategic'];
    }

    public function getGoalTypes(): array
    {
        return ['Individual Goal', 'Team Goal', 'Department Goal', 'Organizational Goal', 'Development Goal'];
    }

    public function getGoalStatuses(): array
    {
        return ['Draft', 'Pending', 'Active', 'In Progress', 'Completed', 'Overdue', 'Cancelled'];
    }

    public function getPriorities(): array
    {
        return ['Low', 'Medium', 'High', 'Critical'];
    }

    public function saveGoal(array $data): bool
    {
        if (!$this->tableExists('pm_goals')) {
            return false;
        }

        $employeeId = trim((string) ($data['employee_id'] ?? ''));
        $employeeName = trim((string) ($data['employee_name'] ?? ''));
        if ($employeeId !== '' && $employeeName === '') {
            $employeeName = $this->getEmployeeDisplayName($employeeId) ?? 'Unknown Employee';
        }

        $supervisorId = trim((string) ($data['supervisor_id'] ?? ($data['assigned_by'] ?? '')));
        $supervisorName = trim((string) ($data['supervisor_name'] ?? ''));
        if ($supervisorId !== '' && $supervisorName === '') {
            $supervisorName = $this->getSupervisorDisplayName($supervisorId) ?? 'System';
        }

        $goalTitle = trim((string) ($data['goal_title'] ?? ''));
        $goalDescription = trim((string) ($data['goal_description'] ?? ''));
        $goalCategory = trim((string) ($data['goal_category'] ?? 'Performance'));
        $priorityLevel = trim((string) ($data['priority_level'] ?? 'Medium'));
        $status = trim((string) ($data['status'] ?? 'Draft'));
        $startDate = trim((string) ($data['start_date'] ?? ''));
        $dueDate = trim((string) ($data['due_date'] ?? ''));
        $targetCompletion = max(0, min(100, (int) ($data['target_completion_percentage'] ?? 0)));
        $kpiName = trim((string) ($data['kpi_name'] ?? ''));
        $kpiTarget = trim((string) ($data['kpi_target'] ?? ''));
        $expectedOutcome = trim((string) ($data['expected_outcome'] ?? ''));
        $notes = trim((string) ($data['smart_notes'] ?? ''));
        $progressPercentage = max(0, min(100, (int) ($data['progress_percentage'] ?? 0)));
        $progressNotes = trim((string) ($data['progress_notes'] ?? ''));
        $goalType = trim((string) ($data['goal_type'] ?? ''));

        if ($notes !== '' && $goalType !== '') {
            $notes .= "\nGoal Type: {$goalType}";
        } elseif ($goalType !== '') {
            $notes = "Goal Type: {$goalType}";
        }

        try {
            $sql = "INSERT INTO pm_goals (
                        employee_id,
                        employee_name,
                        department,
                        position,
                        supervisor_id,
                        supervisor_name,
                        goal_title,
                        goal_description,
                        goal_category,
                        priority_level,
                        start_date,
                        due_date,
                        target_completion_percentage,
                        kpi_name,
                        kpi_target,
                        expected_outcome,
                        smart_notes,
                        progress_percentage,
                        progress_notes,
                        latest_update_date,
                        status,
                        approval_comment,
                        rejection_reason,
                        completion_date,
                        created_at,
                        updated_at
                    ) VALUES (
                        :employee_id,
                        :employee_name,
                        :department,
                        :position,
                        :supervisor_id,
                        :supervisor_name,
                        :goal_title,
                        :goal_description,
                        :goal_category,
                        :priority_level,
                        :start_date,
                        :due_date,
                        :target_completion_percentage,
                        :kpi_name,
                        :kpi_target,
                        :expected_outcome,
                        :smart_notes,
                        :progress_percentage,
                        :progress_notes,
                        :latest_update_date,
                        :status,
                        :approval_comment,
                        :rejection_reason,
                        :completion_date,
                        NOW(),
                        NOW()
                    )";

            $stmt = $this->conn->prepare($sql);
            $department = trim((string) ($data['department'] ?? ''));
            $position = trim((string) ($data['position'] ?? ''));
            $approvalComment = trim((string) ($data['approval_comment'] ?? ''));
            $rejectionReason = trim((string) ($data['rejection_reason'] ?? ''));
            $completionDate = trim((string) ($data['completion_date'] ?? ''));
            $latestUpdateDate = !empty($data['latest_update_date']) ? trim((string) $data['latest_update_date']) : date('Y-m-d');

            $stmt->bindValue(':employee_id', (int) $employeeId, PDO::PARAM_INT);
            $stmt->bindValue(':employee_name', $employeeName ?: 'Unknown Employee', PDO::PARAM_STR);
            $stmt->bindValue(':department', $department, PDO::PARAM_STR);
            $stmt->bindValue(':position', $position, PDO::PARAM_STR);
            $stmt->bindValue(':supervisor_id', !empty($supervisorId) ? (int) $supervisorId : null, !empty($supervisorId) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':supervisor_name', $supervisorName ?: 'System', PDO::PARAM_STR);
            $stmt->bindValue(':goal_title', $goalTitle, PDO::PARAM_STR);
            $stmt->bindValue(':goal_description', $goalDescription, PDO::PARAM_STR);
            $stmt->bindValue(':goal_category', $goalCategory ?: 'Performance', PDO::PARAM_STR);
            $stmt->bindValue(':priority_level', $priorityLevel ?: 'Medium', PDO::PARAM_STR);
            $stmt->bindValue(':start_date', $startDate !== '' ? $startDate : null, $startDate !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':due_date', $dueDate !== '' ? $dueDate : null, $dueDate !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':target_completion_percentage', $targetCompletion, PDO::PARAM_INT);
            $stmt->bindValue(':kpi_name', $kpiName !== '' ? $kpiName : null, $kpiName !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':kpi_target', $kpiTarget !== '' ? $kpiTarget : null, $kpiTarget !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':expected_outcome', $expectedOutcome !== '' ? $expectedOutcome : null, $expectedOutcome !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':smart_notes', $notes !== '' ? $notes : null, $notes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':progress_percentage', $progressPercentage, PDO::PARAM_INT);
            $stmt->bindValue(':progress_notes', $progressNotes !== '' ? $progressNotes : null, $progressNotes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':latest_update_date', $latestUpdateDate, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status ?: 'Draft', PDO::PARAM_STR);
            $stmt->bindValue(':approval_comment', $approvalComment !== '' ? $approvalComment : null, $approvalComment !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':rejection_reason', $rejectionReason !== '' ? $rejectionReason : null, $rejectionReason !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':completion_date', $completionDate !== '' ? $completionDate : null, $completionDate !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

            $stmt->execute();

            $goalId = (int) $this->conn->lastInsertId();
            $this->appendHistory($goalId, 'Goal created', 'Goal created for employee: ' . $employeeName, $supervisorId ?: $employeeId);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateGoal(array $data): bool
    {
        if (!$this->tableExists('pm_goals')) {
            return false;
        }

        $goalId = (int) ($data['goal_id'] ?? 0);
        if ($goalId <= 0) {
            return false;
        }

        $employeeId = trim((string) ($data['employee_id'] ?? ''));
        $employeeName = trim((string) ($data['employee_name'] ?? ''));
        if ($employeeId !== '' && $employeeName === '') {
            $employeeName = $this->getEmployeeDisplayName($employeeId) ?? 'Unknown Employee';
        }

        $supervisorId = trim((string) ($data['supervisor_id'] ?? ($data['assigned_by'] ?? '')));
        $supervisorName = trim((string) ($data['supervisor_name'] ?? ''));
        if ($supervisorName === '' && $supervisorId !== '') {
            $supervisorName = $this->getSupervisorDisplayName($supervisorId) ?? 'System';
        }

        $goalTitle = trim((string) ($data['goal_title'] ?? ''));
        $goalDescription = trim((string) ($data['goal_description'] ?? ''));
        $goalCategory = trim((string) ($data['goal_category'] ?? 'Performance'));
        $priorityLevel = trim((string) ($data['priority_level'] ?? 'Medium'));
        $startDate = trim((string) ($data['start_date'] ?? ''));
        $dueDate = trim((string) ($data['due_date'] ?? ''));
        $status = trim((string) ($data['status'] ?? 'Draft'));
        $targetCompletion = max(0, min(100, (int) ($data['target_completion_percentage'] ?? 0)));
        $progressPercentage = max(0, min(100, (int) ($data['progress_percentage'] ?? 0)));
        $kpiName = trim((string) ($data['kpi_name'] ?? ''));
        $kpiTarget = trim((string) ($data['kpi_target'] ?? ''));
        $expectedOutcome = trim((string) ($data['expected_outcome'] ?? ''));
        $notes = trim((string) ($data['smart_notes'] ?? ''));
        $progressNotes = trim((string) ($data['progress_notes'] ?? ''));
        $goalType = trim((string) ($data['goal_type'] ?? ''));

        if ($notes !== '' && $goalType !== '') {
            $notes .= "\nGoal Type: {$goalType}";
        } elseif ($goalType !== '') {
            $notes = "Goal Type: {$goalType}";
        }

        try {
            $sql = "UPDATE pm_goals SET
                        employee_id = :employee_id,
                        employee_name = :employee_name,
                        department = :department,
                        position = :position,
                        supervisor_id = :supervisor_id,
                        supervisor_name = :supervisor_name,
                        goal_title = :goal_title,
                        goal_description = :goal_description,
                        goal_category = :goal_category,
                        priority_level = :priority_level,
                        start_date = :start_date,
                        due_date = :due_date,
                        target_completion_percentage = :target_completion_percentage,
                        kpi_name = :kpi_name,
                        kpi_target = :kpi_target,
                        expected_outcome = :expected_outcome,
                        smart_notes = :smart_notes,
                        progress_percentage = :progress_percentage,
                        progress_notes = :progress_notes,
                        latest_update_date = :latest_update_date,
                        status = :status,
                        completion_date = :completion_date,
                        updated_at = NOW()
                    WHERE goal_id = :goal_id";

            $stmt = $this->conn->prepare($sql);
            $department = trim((string) ($data['department'] ?? ''));
            $position = trim((string) ($data['position'] ?? ''));
            $completionDate = trim((string) ($data['completion_date'] ?? ''));
            $latestUpdateDate = !empty($data['latest_update_date']) ? trim((string) $data['latest_update_date']) : date('Y-m-d');

            $stmt->bindValue(':employee_id', (int) $employeeId, PDO::PARAM_INT);
            $stmt->bindValue(':employee_name', $employeeName ?: 'Unknown Employee', PDO::PARAM_STR);
            $stmt->bindValue(':department', $department, PDO::PARAM_STR);
            $stmt->bindValue(':position', $position, PDO::PARAM_STR);
            $stmt->bindValue(':supervisor_id', !empty($supervisorId) ? (int) $supervisorId : null, !empty($supervisorId) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':supervisor_name', $supervisorName ?: 'System', PDO::PARAM_STR);
            $stmt->bindValue(':goal_title', $goalTitle, PDO::PARAM_STR);
            $stmt->bindValue(':goal_description', $goalDescription, PDO::PARAM_STR);
            $stmt->bindValue(':goal_category', $goalCategory ?: 'Performance', PDO::PARAM_STR);
            $stmt->bindValue(':priority_level', $priorityLevel ?: 'Medium', PDO::PARAM_STR);
            $stmt->bindValue(':start_date', $startDate !== '' ? $startDate : null, $startDate !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':due_date', $dueDate !== '' ? $dueDate : null, $dueDate !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':target_completion_percentage', $targetCompletion, PDO::PARAM_INT);
            $stmt->bindValue(':kpi_name', $kpiName !== '' ? $kpiName : null, $kpiName !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':kpi_target', $kpiTarget !== '' ? $kpiTarget : null, $kpiTarget !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':expected_outcome', $expectedOutcome !== '' ? $expectedOutcome : null, $expectedOutcome !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':smart_notes', $notes !== '' ? $notes : null, $notes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':progress_percentage', $progressPercentage, PDO::PARAM_INT);
            $stmt->bindValue(':progress_notes', $progressNotes !== '' ? $progressNotes : null, $progressNotes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':latest_update_date', $latestUpdateDate, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status ?: 'Draft', PDO::PARAM_STR);
            $stmt->bindValue(':completion_date', $completionDate !== '' ? $completionDate : null, $completionDate !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':goal_id', $goalId, PDO::PARAM_INT);

            $success = $stmt->execute();
            if ($success) {
                $this->appendHistory($goalId, 'Goal updated', 'Goal details were updated.', $supervisorId ?: $employeeId);
            }

            return $success;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateProgress(int $goalId, int $progressPercentage, string $progressNotes, string $updatedBy = ''): bool
    {
        if (!$this->tableExists('pm_goals') || $goalId <= 0) {
            return false;
        }

        $progressPercentage = max(0, min(100, $progressPercentage));
        $progressNotes = trim($progressNotes);

        try {
            $this->conn->beginTransaction();

            $sql = "UPDATE pm_goals
                    SET progress_percentage = :progress_percentage,
                        progress_notes = :progress_notes,
                        latest_update_date = CURDATE(),
                        status = CASE
                            WHEN :progress_percentage >= 100 THEN 'Completed'
                            WHEN :progress_percentage > 0 THEN 'In Progress'
                            ELSE 'Pending'
                        END,
                        updated_at = NOW()
                    WHERE goal_id = :goal_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':progress_percentage', $progressPercentage, PDO::PARAM_INT);
            $stmt->bindValue(':progress_notes', $progressNotes !== '' ? $progressNotes : null, $progressNotes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':goal_id', $goalId, PDO::PARAM_INT);
            $stmt->execute();

            if ($this->tableExists('pm_goal_progress')) {
                $insertSql = "INSERT INTO pm_goal_progress (goal_id, progress_percentage, progress_notes, update_date, created_at)
                              VALUES (:goal_id, :progress_percentage, :progress_notes, CURDATE(), NOW())";
                $progressStmt = $this->conn->prepare($insertSql);
                $progressStmt->bindValue(':goal_id', $goalId, PDO::PARAM_INT);
                $progressStmt->bindValue(':progress_percentage', $progressPercentage, PDO::PARAM_INT);
                $progressStmt->bindValue(':progress_notes', $progressNotes !== '' ? $progressNotes : null, $progressNotes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $progressStmt->execute();
            }

            $this->appendHistory($goalId, 'Progress updated', $progressNotes !== '' ? $progressNotes : 'Progress updated to ' . $progressPercentage . '%', $updatedBy ?: 'System');

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    public function updateStatus(int $goalId, string $status, string $comment = ''): bool
    {
        if (!$this->tableExists('pm_goals') || $goalId <= 0) {
            return false;
        }

        $status = trim($status);
        if ($status === '') {
            return false;
        }

        try {
            $sql = "UPDATE pm_goals
                    SET status = :status,
                        approval_comment = :approval_comment,
                        completion_date = CASE WHEN :status = 'Completed' THEN CURDATE() ELSE completion_date END,
                        updated_at = NOW()
                    WHERE goal_id = :goal_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':approval_comment', $comment !== '' ? $comment : null, $comment !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':goal_id', $goalId, PDO::PARAM_INT);
            $success = $stmt->execute();

            if ($success) {
                $this->appendHistory($goalId, 'Status updated', 'Status changed to ' . $status . ($comment !== '' ? ': ' . $comment : ''), 'System');
            }

            return $success;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function appendHistory(int $goalId, string $action, string $details, string $createdBy = ''): void
    {
        if (!$this->tableExists('pm_goal_history')) {
            return;
        }

        $sql = "INSERT INTO pm_goal_history (goal_id, action, details, created_by, created_at)
                VALUES (:goal_id, :action, :details, :created_by, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':goal_id', $goalId, PDO::PARAM_INT);
        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':details', $details !== '' ? $details : null, $details !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':created_by', $createdBy !== '' ? $createdBy : 'System', PDO::PARAM_STR);
        $stmt->execute();
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
