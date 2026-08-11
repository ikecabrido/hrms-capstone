<?php

class AllowanceDeductionModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Get employees
    public function getEmployees(): array
    {
        $stmt = $this->db->query("
            SELECT employee_id as id, full_name AS name
            FROM employees
            WHERE employment_status='Active'
            ORDER BY full_name
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get adjustments
    public function getRecords($periodId = null, $employeeId = null): array
    {
        $sql = "
            SELECT 
                ea.*,
                e.full_name AS employee_name,
                pp.period_name,
                pp.status AS period_status
            FROM pr_employee_adjustments ea
            JOIN employees e ON ea.employee_id = e.employee_id
            JOIN pr_periods pp ON ea.payroll_period_id = pp.period_id
            WHERE 1=1
        ";

        $params = [];

        if ($periodId) {
            $sql .= " AND ea.payroll_period_id = :pid";
            $params[':pid'] = $periodId;
        }

        if ($employeeId) {
            $sql .= " AND ea.employee_id = :eid";
            $params[':eid'] = $employeeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get totals
    public function getTotals($periodId = null, $employeeId = null): array
    {
        $sql = "
            SELECT 
                SUM(CASE WHEN type='allowance' THEN amount ELSE 0 END) AS total_allowance,
                SUM(CASE WHEN type='deduction' THEN amount ELSE 0 END) AS total_deduction
            FROM pr_employee_adjustments
            WHERE 1=1
        ";

        $params = [];

        if ($periodId) {
            $sql .= " AND payroll_period_id = :pid";
            $params[':pid'] = $periodId;
        }

        if ($employeeId) {
            $sql .= " AND employee_id = :eid";
            $params[':eid'] = $employeeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_allowance' => 0,
            'total_deduction' => 0
        ];
    }

    // Add record
    public function store(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO pr_employee_adjustments
            (employee_id, payroll_period_id, type, description, amount)
            VALUES (?,?,?,?,?)
        ");

        return $stmt->execute([
            $data['employee_id'],
            $data['period_id'],
            $data['type'],
            $data['description'],
            $data['amount']
        ]);
    }
    public function addAdjustment($emp, $type, $desc, $amt, $period)
    {
        $stmt = $this->db->prepare("
        INSERT INTO pr_employee_adjustments
        (employee_id,type,description,amount,payroll_period_id)
        VALUES (?,?,?,?,?)
    ");

        return $stmt->execute([
            $emp,
            $type,
            $desc,
            $amt,
            $period
        ]);
    }
    public function updateAdjustment($id, $desc, $amt)
    {
        $stmt = $this->db->prepare("
        UPDATE pr_employee_adjustments
        SET description=?, amount=?
        WHERE adjustment_id=?
    ");

        return $stmt->execute([$desc, $amt, $id]);
    }
    public function deleteAdjustment($id)
    {
        $stmt = $this->db->prepare("
        DELETE FROM pr_employee_adjustments
        WHERE adjustment_id=?
    ");

        return $stmt->execute([$id]);
    }
    public function isPeriodClosed(int $periodId): bool
    {
        $stmt = $this->db->prepare("
        SELECT status FROM pr_periods WHERE period_id = ?
    ");
        $stmt->execute([$periodId]);

        return $stmt->fetchColumn() === 'closed';
    }
}
