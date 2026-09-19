<?php

class PayrollPeriodModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT * FROM pr_periods 
            ORDER BY start_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM pr_periods 
            WHERE period_id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO pr_periods (period_name, start_date, end_date, pay_date, status)
            VALUES (:name, :start, :end, :paydate, 'open')
        ");

        return $stmt->execute([
            ':name' => $data['period_name'],
            ':start' => $data['start_date'],
            ':end' => $data['end_date'],
            ':paydate' => $data['pay_date']
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE pr_periods
            SET period_name = :name,
                start_date = :start,
                end_date = :end,
                pay_date = :paydate
            WHERE period_id = :id AND status = 'open'
        ");

        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['period_name'],
            ':start' => $data['start_date'],
            ':end' => $data['end_date'],
            ':paydate' => $data['pay_date']
        ]);
    }

    public function delete(int $id): bool
    {
        // Check if period has payroll runs
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM pr_runs
            WHERE payroll_period_id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return false; // Can't delete if payroll runs exist
        }

        $stmt = $this->db->prepare("DELETE FROM pr_periods WHERE period_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function close(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE pr_periods 
            SET status = 'closed' 
            WHERE period_id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    public function generateNextPeriod(): array
    {
        // Get last period
        $stmt = $this->db->query("
            SELECT * FROM pr_periods
            ORDER BY end_date DESC
            LIMIT 1
        ");
        $lastPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lastPeriod) {
            // First period ever - start from current month
            $start = date('Y-m-01');
            $end = date('Y-m-15');
        } else {
            // Calculate next period
            $lastEnd = new DateTime($lastPeriod['end_date']);
            $start = $lastEnd->modify('+1 day')->format('Y-m-d');

            // Determine if 1-15 or 16-end of month
            $day = (int)date('d', strtotime($start));
            if ($day == 1) {
                $end = date('Y-m-15', strtotime($start));
            } else {
                $end = date('Y-m-t', strtotime($start)); // Last day of month
            }
        }

        $payDate = date('Y-m-d', strtotime($end . ' +5 days'));

        return [
            'period_name' => date('M j', strtotime($start)) . '-' . date('j, Y', strtotime($end)),
            'start_date' => $start,
            'end_date' => $end,
            'pay_date' => $payDate
        ];
    }
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("
        UPDATE pr_periods
        SET status = :status
        WHERE period_id = :id
    ");

        return $stmt->execute([
            ':id' => $id,
            ':status' => $status
        ]);
    }
}
