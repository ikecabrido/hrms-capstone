<?php
namespace App\Models;

class PolicyShare extends BaseModel
{
    public function ensureTables()
    {
        // Table to track shares from external modules (does not duplicate policy content)
        $sql = "CREATE TABLE IF NOT EXISTS eer_policy_shares (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            source_module VARCHAR(100) NOT NULL,
            source_policy_id VARCHAR(100) NOT NULL,
            target_audience VARCHAR(100) DEFAULT 'all',
            shared_by_user_id INT DEFAULT NULL,
            shared_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'shared',
            UNIQUE KEY src (source_module, source_policy_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $this->execute($sql);

        $sql2 = "CREATE TABLE IF NOT EXISTS eer_policy_share_recipients (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            share_id INT NOT NULL,
            employee_id INT NOT NULL,
            notified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY share_rec (share_id, employee_id),
            FOREIGN KEY (share_id) REFERENCES eer_policy_shares(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $this->execute($sql2);
    }

    public function alreadyShared(string $sourceModule, string $sourcePolicyId)
    {
        $baseId = explode('|', (string)$sourcePolicyId, 2)[0];
        $row = $this->execute(
            'SELECT id FROM eer_policy_shares
             WHERE source_module = :m
               AND (
                   source_policy_id = :pid
                   OR source_policy_id = :base_id
                   OR source_policy_id LIKE :pid_version
                   OR source_policy_id LIKE :base_version
               )
             LIMIT 1',
            [
                'm' => $sourceModule,
                'pid' => $sourcePolicyId,
                'base_id' => $baseId,
                'pid_version' => $sourcePolicyId . '|%',
                'base_version' => $baseId . '|%',
            ]
        )->fetch();
        return !empty($row) ? (int)$row['id'] : false;
    }

    public function createShare(string $sourceModule, string $sourcePolicyId, string $targetAudience = 'all', $sharedBy = null, bool $ensureTables = true)
    {
        if ($ensureTables) {
            $this->ensureTables();
        }
        $existing = $this->alreadyShared($sourceModule, $sourcePolicyId);
        if ($existing) {
            return $existing;
        }

        $this->execute('INSERT INTO eer_policy_shares (source_module, source_policy_id, target_audience, shared_by_user_id) VALUES (:m, :pid, :ta, :sb)', ['m' => $sourceModule, 'pid' => $sourcePolicyId, 'ta' => $targetAudience, 'sb' => $sharedBy]);
        return (int)$this->db->lastInsertId();
    }

    public function markRecipientNotified(int $shareId, int $employeeId)
    {
        try {
            $this->execute('INSERT INTO eer_policy_share_recipients (share_id, employee_id) VALUES (:s, :e)', ['s' => $shareId, 'e' => $employeeId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getShareById(int $id)
    {
        return $this->execute('SELECT * FROM eer_policy_shares WHERE id = :id', ['id' => $id])->fetch();
    }

    public function getShare(string $sourceModule, string $sourcePolicyId)
    {
        if (!$this->hasTable('eer_policy_shares')) {
            return null;
        }

        $baseId = explode('|', (string)$sourcePolicyId, 2)[0];
        return $this->execute(
            'SELECT * FROM eer_policy_shares
             WHERE source_module = :module
               AND (
                   source_policy_id = :policy_id
                   OR source_policy_id = :base_id
                   OR source_policy_id LIKE :version_prefix
                   OR source_policy_id LIKE :base_prefix
               )
             LIMIT 1',
            [
                'module' => $sourceModule,
                'policy_id' => $sourcePolicyId,
                'base_id' => $baseId,
                'version_prefix' => $sourcePolicyId . '|%',
                'base_prefix' => $baseId . '|%',
            ]
        )->fetch();
    }

    public function hasPreviousShare(string $sourceModule, string $sourcePolicyId)
    {
        if (!$this->hasTable('eer_policy_shares')) {
            return false;
        }

        $baseId = explode('|', (string)$sourcePolicyId, 2)[0];
        $row = $this->execute(
            'SELECT id FROM eer_policy_shares
             WHERE source_module = :module
               AND (
                   source_policy_id = :policy_id
                   OR source_policy_id = :base_id
                   OR source_policy_id LIKE :version_prefix
                   OR source_policy_id LIKE :base_prefix
               )
             LIMIT 1',
            [
                'module' => $sourceModule,
                'policy_id' => $sourcePolicyId,
                'base_id' => $baseId,
                'version_prefix' => $sourcePolicyId . '|%',
                'base_prefix' => $baseId . '|%',
            ]
        )->fetch();

        return !empty($row);
    }

    public function notifyRecipients(int $shareId, string $targetAudience, string $title, string $message, bool $ensureTables = true)
    {
        if ($ensureTables) {
            $this->ensureTables();
        }
        $employees = [];
        $targetAudience = trim($targetAudience) ?: 'all';

        if ($targetAudience === 'all') {
            $employees = $this->execute('SELECT employee_id FROM em_employees WHERE employee_id IS NOT NULL')->fetchAll();
        } elseif (strpos($targetAudience, 'department:') === 0) {
            $department = trim(substr($targetAudience, strlen('department:')));
            $employees = $this->execute(
                'SELECT e.employee_id FROM em_employees e LEFT JOIN em_departments d ON d.department_id = e.department_id WHERE d.department_name = :department',
                ['department' => $department]
            )->fetchAll();
        } elseif (strpos($targetAudience, 'department_id:') === 0) {
            $departmentId = (int)substr($targetAudience, strlen('department_id:'));
            $employees = $this->execute(
                'SELECT employee_id FROM em_employees WHERE department_id = :department_id',
                ['department_id' => $departmentId]
            )->fetchAll();
        } elseif (strpos($targetAudience, 'employee:') === 0) {
            $employeeId = (int)substr($targetAudience, strlen('employee:'));
            if ($employeeId > 0) {
                $employees = [['employee_id' => $employeeId]];
            }
        } elseif (strpos($targetAudience, 'employees:') === 0) {
            $employeeIds = array_filter(array_map('intval', explode(',', substr($targetAudience, strlen('employees:')))));
            if ($employeeIds) {
                $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
                $stmt = $this->db->prepare('SELECT employee_id FROM em_employees WHERE employee_id IN (' . $placeholders . ')');
                $stmt->execute(array_values($employeeIds));
                $employees = $stmt->fetchAll();
            }
        }

        if (empty($employees)) {
            throw new \RuntimeException('No employees matched the selected target audience.');
        }

        foreach ($employees as $employee) {
            $employeeId = (int)($employee['employee_id'] ?? 0);
            if ($employeeId <= 0) {
                continue;
            }

            if (!$this->markRecipientNotified($shareId, $employeeId)) {
                throw new \RuntimeException('Unable to save the shared-policy recipient for employee ' . $employeeId . '.');
            }

            $notified = (new Notification($this->db))->notifyEmployees(
                [$employeeId],
                $message,
                'policy',
                $title
            );
            if ($notified !== 1) {
                throw new \RuntimeException('Unable to create the policy notification for employee ' . $employeeId . '.');
            }
        }

        return count($employees);
    }

    public function shareAndNotify(string $sourceModule, string $sourcePolicyId, string $targetAudience, $sharedBy, string $title, string $message)
    {
        $alreadyInTransaction = $this->db->inTransaction();
        if (!$alreadyInTransaction) {
            $this->ensureTables();
        }
        if ($alreadyInTransaction) {
            $shareId = $this->createShare($sourceModule, $sourcePolicyId, $targetAudience, $sharedBy, false);
            $this->notifyRecipients($shareId, $targetAudience, $title, $message, false);
            return $shareId;
        }

        $this->db->beginTransaction();
        try {
            $shareId = $this->createShare($sourceModule, $sourcePolicyId, $targetAudience, $sharedBy, false);
            $this->notifyRecipients($shareId, $targetAudience, $title, $message, false);
            $this->db->commit();
            return $shareId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
