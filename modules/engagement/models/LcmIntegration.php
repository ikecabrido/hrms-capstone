<?php
namespace App\Models;

class LcmIntegration extends BaseModel
{
    public function getComplianceRecords()
    {
        if (!$this->hasTable('lc_compliance_records')) {
            return [];
        }

        return $this->execute('SELECT record_id, compliance_type, status, created_at
            FROM lc_compliance_records
            ORDER BY record_id DESC')->fetchAll();
    }

    public function getComplianceRecord(int $recordId)
    {
        if ($recordId <= 0 || !$this->hasTable('lc_compliance_records')) {
            return null;
        }

        return $this->execute('SELECT record_id, compliance_type, status, created_at
            FROM lc_compliance_records
            WHERE record_id = :record_id
            LIMIT 1', ['record_id' => $recordId])->fetch() ?: null;
    }

    public function getNotificationsForEmployee(int $employeeId = null, bool $includeAll = false)
    {
        if (!$this->hasTable('lc_notifications')) {
            return [];
        }

        $sql = 'SELECT id, employee_id, user_id, recipient_id, title, message, type, notification_type, status, is_read, module, created_at, updated_at
                FROM lc_notifications';

        if (!$includeAll) {
            if ($employeeId === null) {
                return [];
            }
            $sql .= ' WHERE employee_id = :employee_id';
            $params = ['employee_id' => $employeeId];
        } else {
            $params = [];
        }

        $sql .= ' ORDER BY created_at DESC';

        return $this->execute($sql, $params)->fetchAll();
    }

    public function getPolicyAcknowledgmentsForEmployee(int $employeeId = null)
    {
        if (!$this->hasTable('lc_acknowledgment_log')) {
            return [];
        }

        $employeeName = $this->getEmployeeNameSql('he', 'employee_name');
        $sql = 'SELECT a.acknowledgment_id, a.employee_id, a.policy_id, a.acknowledged_at, a.status, ' . $employeeName . '
            FROM lc_acknowledgment_log a
            LEFT JOIN em_employees he ON he.employee_id = a.employee_id';

        if ($employeeId !== null) {
            $sql .= ' WHERE a.employee_id = :employee_id';
            $params = ['employee_id' => $employeeId];
        } else {
            $params = [];
        }

        $sql .= ' ORDER BY a.acknowledged_at DESC';

        return $this->execute($sql, $params)->fetchAll();
    }

    public function getEmployeeDocumentsForEmployee(int $employeeId = null)
    {
        if (!$this->hasTable('lc_employee_documents')) {
            return [];
        }

        $employeeName = $this->getEmployeeNameSql('he', 'employee_name');
        $sql = 'SELECT d.id, d.employee_id, d.document_name, d.document_type, d.document_number, d.category, d.verification_status, d.compliance_status, d.status, d.issued_date, d.expiry_date, d.flag_reason, d.flag_notes, d.verified_at, d.created_at, d.updated_at, ' . $employeeName . '
            FROM lc_employee_documents d
            LEFT JOIN em_employees he ON he.employee_id = d.employee_id';

        if ($employeeId !== null) {
            $sql .= ' WHERE d.employee_id = :employee_id';
            $params = ['employee_id' => $employeeId];
        } else {
            $params = [];
        }

        $sql .= ' ORDER BY d.expiry_date ASC';

        return $this->execute($sql, $params)->fetchAll();
    }

    public function getDepartments()
    {
        if (!$this->hasTable('em_departments')) {
            return [];
        }

        return $this->execute('SELECT department_id, department_name
            FROM em_departments
            WHERE status = "Active"
            ORDER BY department_name ASC')->fetchAll();
    }

    public function getIncidents(bool $includeConfidential = false)
    {
        $sql = 'SELECT id, incident_id, type, incident_type, severity, title, description, incident_date, location, respondent_id, reporter_id, reporter_name, status, current_workflow_step, status_changed_at, is_confidential, created_by, created_at, updated_at
                FROM lc_incidents';

        if (!$includeConfidential) {
            $sql .= ' WHERE is_confidential = 0';
        }

        $sql .= ' ORDER BY created_at DESC';

        return $this->execute($sql)->fetchAll();
    }

    public function getRisks(bool $includeSensitive = false)
    {
        if (!$this->hasTable('lc_risks') && !$this->hasTable('lc_risk')) {
            return [];
        }

        $table = $this->hasTable('lc_risks') ? 'lc_risks' : 'lc_risk';
        $employeeName = $this->getEmployeeNameSql('he', 'employee_name');
        $sql = 'SELECT r.id, r.employee_id, ' . $employeeName . ', r.risk_type, r.severity, r.description, r.mitigation_plan, r.status, r.monitoring_status, r.compliance_review, r.department_progress, r.review_findings, r.review_remarks, r.review_date, r.reviewed_by, r.last_reviewed, r.supporting_documents, r.archived, r.created_at, r.updated_at
            FROM ' . $table . ' r
            LEFT JOIN em_employees he ON he.employee_id = r.employee_id';

        if (!$includeSensitive) {
            $sql .= ' WHERE r.archived = 0';
        }

        $sql .= ' ORDER BY r.created_at DESC';

        return $this->execute($sql, [])->fetchAll();
    }

    public function getRiskFlags(bool $includeSensitive = false)
    {
        if (!$this->hasTable('lc_risk_flags') && !$this->hasTable('lc_risk_flag')) {
            return [];
        }

        $table = $this->hasTable('lc_risk_flags') ? 'lc_risk_flags' : 'lc_risk_flag';
        $employeeName = $this->getEmployeeNameSql('he', 'employee_name');
        $sql = 'SELECT rf.id, rf.employee_id, ' . $employeeName . ', rf.rule_id, rf.severity, rf.description, rf.status, rf.resolved_at, rf.created_at, rf.updated_at
            FROM ' . $table . ' rf
            LEFT JOIN em_employees he ON he.employee_id = rf.employee_id';

        if (!$includeSensitive) {
            $sql .= ' WHERE rf.status <> "Resolved"';
        }

        $sql .= ' ORDER BY rf.created_at DESC';

        return $this->execute($sql, [])->fetchAll();
    }

    public function getPolicies()
    {
        $priorityTables = ['lc_policies', 'lc_philippine_laws', 'lc_policy_documents', 'lc_policy_docs', 'lc_policy'];

        foreach ($priorityTables as $tbl) {
            $r = $this->execute('SHOW TABLES LIKE :t', ['t' => $tbl])->fetch();
            if (empty($r)) {
                continue;
            }

            $sql = "SELECT * FROM {$tbl}";
            if ($tbl === 'lc_philippine_laws') {
                $sql .= " WHERE status = 'Active'";
            } elseif ($tbl === 'lc_policies') {
                $sql .= " WHERE status IN ('Approved', 'Published')";
            }

            $rows = $this->execute($sql, [])->fetchAll();
            $mapped = [];
            $categoryMap = [];
            if ($this->hasTable('lc_policy_categories')) {
                $categoryRows = $this->execute('SELECT id, name FROM lc_policy_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();
                foreach ($categoryRows as $categoryRow) {
                    $categoryMap[(int)($categoryRow['id'] ?? 0)] = trim((string)($categoryRow['name'] ?? ''));
                }
            }

            foreach ($rows as $r) {
                $sourcePolicyId = $r['id'] ?? $r['policy_id'] ?? $r['policy_document_id'] ?? null;
                $updatedAt = $r['updated_at'] ?? null;
                $changeSummary = $r['change_summary'] ?? $r['changes'] ?? $r['update_notes'] ?? $r['update_description'] ?? null;
                $isUpdate = !empty($changeSummary)
                    || !empty($r['version'])
                    || !empty($r['revision'])
                    || !empty($r['is_update'])
                    || strtolower((string)($r['status'] ?? '')) === 'amended';
                $categoryId = $r['category_id'] ?? null;
                $categoryName = $r['category'] ?? ($categoryId !== null && isset($categoryMap[(int)$categoryId]) ? $categoryMap[(int)$categoryId] : null);
                $mapped[] = [
                    'source_module' => 'LCM',
                    'source_policy_id' => $sourcePolicyId,
                    'source_policy_key' => $tbl === 'lc_philippine_laws' && $updatedAt
                        ? $sourcePolicyId . '|' . $updatedAt
                        : ($isUpdate && $updatedAt ? $sourcePolicyId . '|' . $updatedAt : (string)$sourcePolicyId),
                    'title' => $r['title'] ?? $r['name'] ?? $r['policy_name'] ?? '',
                    'content' => $r['content'] ?? $r['description'] ?? '',
                    'change_summary' => $changeSummary,
                    'is_update' => $isUpdate,
                    'version' => $r['version'] ?? $r['revision'] ?? null,
                    'category' => $categoryName,
                    'effective_date' => $r['effective_date'] ?? $r['published_at'] ?? null,
                    'target_audience' => $r['target_audience'] ?? $r['audience'] ?? 'all',
                    'attachment_path' => $r['attachment_path'] ?? $r['document_path'] ?? $r['file_path'] ?? null,
                    'status' => $r['status'] ?? null,
                    'created_at' => $r['created_at'] ?? $r['published_at'] ?? null,
                    'updated_at' => $updatedAt,
                ];
            }
            usort($mapped, static function ($left, $right) {
                return strcmp((string)($right['created_at'] ?? ''), (string)($left['created_at'] ?? ''));
            });
            return $mapped;
        }

        // Some installations only have Compliance Records, which are the available
        // Legal & Compliance source until a dedicated policy table is installed.
        if ($this->hasTable('lc_compliance_records')) {
            return array_map(static function ($record) {
                return [
                    'source_module' => 'LCM',
                    'source_policy_id' => (int)$record['record_id'],
                    'source_policy_key' => (string)$record['record_id'],
                    'title' => $record['compliance_type'] ?: 'Compliance Record',
                    'content' => '',
                    'change_summary' => null,
                    'is_update' => false,
                    'version' => null,
                    'category' => $record['compliance_type'] ?? 'Compliance',
                    'effective_date' => null,
                    'target_audience' => 'all',
                    'attachment_path' => null,
                    'status' => $record['status'] ?? null,
                    'created_at' => $record['created_at'] ?? null,
                    'updated_at' => null,
                ];
            }, $this->execute("SELECT record_id, compliance_type, status, created_at
                FROM lc_compliance_records
                WHERE status = 'Compliant'
                ORDER BY record_id DESC")->fetchAll());
        }

        return [];
    }
}

