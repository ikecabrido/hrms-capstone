<?php
namespace App\Models;

class Grievance extends BaseModel
{
    private function getAssignedHrSql()
    {
        if ($this->hasColumn('eer_grievances', 'assigned_to')) {
            return [
                'select' => 'COALESCE(ah.full_name, ah.username) AS assigned_hr',
                'join' => 'LEFT JOIN users ah ON g.assigned_to = ah.id',
            ];
        }

        return [
            'select' => 'NULL AS assigned_hr',
            'join' => '',
        ];
    }

    private function ensureGrievancePayrollTable()
    {
        if ($this->hasTable('eer_grievance_payroll')) {
            return true;
        }

        $sql = "CREATE TABLE IF NOT EXISTS eer_grievance_payroll (
            grievance_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            payroll_module ENUM('Salary Calculation','Payslip Generation','Benefits Management','Tax & Deductions','Compliance & Statutory Reporting') NOT NULL,
            reference_id INT NULL,
            complaint_title VARCHAR(150) NOT NULL,
            complaint_details TEXT NOT NULL,
            attachment VARCHAR(255) NULL,
            status ENUM('Pending','Under Review','Resolved','Rejected','Closed') DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL DEFAULT NULL,
            KEY employee_id (employee_id),
            CONSTRAINT eer_grievance_payroll_employee_fk FOREIGN KEY (employee_id) REFERENCES em_employees(employee_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $this->execute($sql);
        return true;
    }

    protected function hasTable($tableName)
    {
        $result = $this->execute('SHOW TABLES LIKE :tableName', ['tableName' => $tableName])->fetch();
        return !empty($result);
    }

    public function getGrievances()
    {
        $reporterName = "COALESCE(CONCAT_WS(' ', uc.first_name, uc.middle_name, uc.last_name), CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name), CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name)) AS filed_by";
        $employeeName = $this->getEmployeeNameSql('e', 'employee_name');
        $departmentName = "COALESCE(d.department_name, '') AS department";
        $assignedHr = $this->getAssignedHrSql();

        return $this->execute("SELECT g.*, g.eer_grievance_id AS id, $reporterName, $employeeName, $departmentName, {$assignedHr['select']},
                COALESCE(g.gross_pay, p.gross_pay) AS gross_pay,
                COALESCE(g.total_deductions, p.total_deductions) AS total_deductions,
                COALESCE(g.net_pay, p.net_pay) AS net_pay,
                COALESCE(g.payslip_information, CONCAT('Payslip ', p.payslip_id, ': gross=', COALESCE(p.gross_pay, 0), ', deductions=', COALESCE(p.total_deductions, 0), ', net=', COALESCE(p.net_pay, 0))) AS payslip_information,
                GROUP_CONCAT(DISTINCT CONCAT(pa.type, ': ', pa.description, ' (', COALESCE(pa.amount, 0), ')') SEPARATOR ' | ') AS employee_adjustments,
                COUNT(DISTINCT pa.adjustment_id) AS adjustment_count,
                COALESCE(SUM(pa.amount), 0) AS total_adjustment_amount,
                gp.payroll_module AS payroll_module,
                gp.reference_id AS payroll_reference_id
            FROM eer_grievances g
            LEFT JOIN em_employees uc ON g.created_by_employee_id = uc.employee_id
            LEFT JOIN em_employees u ON g.employee_id = u.employee_id
            LEFT JOIN em_employees e ON g.employee_id = e.employee_id
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN pr_payslips p ON p.payslip_id = g.payslip_id AND p.employee_id = g.employee_id
            LEFT JOIN pr_employee_adjustments pa ON pa.employee_id = g.employee_id
            LEFT JOIN eer_grievance_payroll gp ON gp.grievance_id = g.eer_grievance_id
            {$assignedHr['join']}
            GROUP BY g.eer_grievance_id
            ORDER BY g.created_at DESC")->fetchAll();
    }

    public function getDepartments()
    {
        return $this->execute("SELECT department_name
            FROM em_departments
            WHERE status = 'Active'
            ORDER BY department_name ASC")->fetchAll();
    }

    public function find($id)
    {
        return $this->execute('SELECT * FROM eer_grievances WHERE eer_grievance_id = :id', ['id' => $id])->fetch();
    }

    public function getGrievanceById($id)
    {
        $reporterName = "COALESCE(CONCAT_WS(' ', uc.first_name, uc.middle_name, uc.last_name), CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name), CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name)) AS filed_by";
        $employeeName = $this->getEmployeeNameSql('e', 'employee_name');
        $departmentName = "COALESCE(d.department_name, '') AS department";
        $assignedHr = $this->getAssignedHrSql();

        return $this->execute("SELECT g.*, g.eer_grievance_id AS id, $reporterName, $employeeName, $departmentName, {$assignedHr['select']},
                COALESCE(g.gross_pay, p.gross_pay) AS gross_pay,
                COALESCE(g.total_deductions, p.total_deductions) AS total_deductions,
                COALESCE(g.net_pay, p.net_pay) AS net_pay,
                COALESCE(g.payslip_information, CONCAT('Payslip ', p.payslip_id, ': gross=', COALESCE(p.gross_pay, 0), ', deductions=', COALESCE(p.total_deductions, 0), ', net=', COALESCE(p.net_pay, 0))) AS payslip_information,
                GROUP_CONCAT(DISTINCT CONCAT(pa.type, ': ', pa.description, ' (', COALESCE(pa.amount, 0), ')') SEPARATOR ' | ') AS employee_adjustments,
                COUNT(DISTINCT pa.adjustment_id) AS adjustment_count,
                COALESCE(SUM(pa.amount), 0) AS total_adjustment_amount,
                gp.payroll_module AS payroll_module,
                gp.reference_id AS payroll_reference_id
            FROM eer_grievances g
            LEFT JOIN em_employees uc ON g.created_by_employee_id = uc.employee_id
            LEFT JOIN em_employees u ON g.employee_id = u.employee_id
            LEFT JOIN em_employees e ON g.employee_id = e.employee_id
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN pr_payslips p ON p.payslip_id = g.payslip_id AND p.employee_id = g.employee_id
            LEFT JOIN pr_employee_adjustments pa ON pa.employee_id = g.employee_id
            LEFT JOIN eer_grievance_payroll gp ON gp.grievance_id = g.eer_grievance_id
            {$assignedHr['join']}
            WHERE g.eer_grievance_id = :id
            GROUP BY g.eer_grievance_id", ['id' => $id])->fetch();
    }

    public function fileGrievance($employee_id, $subject, $description, $category = 'Workplace Conflict', $anonymous = 0, $attachment_path = null, $created_by_employee_id = null, $payslip_id = null, $payslip_information = null, $attendance_window_days = 7, $attendance_reference_date = null)
    {
        // If an employee_id was provided, ensure it exists; otherwise allow null (filed by a system user)
        $employeeExists = false;
        if (!empty($employee_id)) {
            $employee = $this->execute('SELECT employee_id FROM em_employees WHERE employee_id = :id', ['id' => $employee_id])->fetch();
            $employeeExists = !empty($employee);
        }

        if (!empty($employee_id) && !$employeeExists && empty($created_by_employee_id)) {
            throw new \Exception("Employee with ID {$employee_id} does not exist in the system.");
        }

        // Load payslip values from source payroll tables when a payslip is selected.
        $grossPay = null;
        $totalDeductions = null;
        $netPay = null;
        if (!empty($payslip_id)) {
            $payslip = $this->execute(
                'SELECT gross_pay, total_deductions, net_pay FROM pr_payslips WHERE payslip_id = :payslip_id AND employee_id = :employee_id',
                ['payslip_id' => $payslip_id, 'employee_id' => $employee_id]
            )->fetch();

            if (!empty($payslip)) {
                $grossPay = $payslip['gross_pay'];
                $totalDeductions = $payslip['total_deductions'];
                $netPay = $payslip['net_pay'];
            }
        }

        if (empty($payslip_information) && !empty($payslip_id) && $grossPay !== null) {
            $payslip_information = sprintf(
                'Payslip %d: gross=%s, deductions=%s, net=%s',
                $payslip_id,
                number_format($grossPay, 2),
                number_format($totalDeductions ?? 0, 2),
                number_format($netPay ?? 0, 2)
            );
        }

        // If creator id wasn't provided, try to resolve 'hr_engagement' user or any user with role 'engagement_relations'
        // If no creator provided, default to HR employee id 1020
        $creatorId = $created_by_employee_id ?: 1020;

        $referenceDate = !empty($attendance_reference_date)
            ? date('Y-m-d', strtotime($attendance_reference_date))
            : date('Y-m-d');
        $windowDays = max(1, (int) $attendance_window_days);

        $sql = 'INSERT INTO eer_grievances (employee_id, subject, description, category, anonymous, attachment_path, created_by_employee_id, payslip_id, gross_pay, total_deductions, net_pay, payslip_information, status, created_at) 
            VALUES (:employee_id, :subject, :description, :category, :anonymous, :attachment_path, :created_by_employee_id, :payslip_id, :gross_pay, :total_deductions, :net_pay, :payslip_information, :status, NOW())';
        $params = [
            'employee_id' => $employeeExists ? $employee_id : null,
            'subject' => $subject,
            'description' => $description,
            'category' => $category,
            'anonymous' => $anonymous,
            'attachment_path' => $attachment_path,
            'created_by_employee_id' => $creatorId,
            'payslip_id' => !empty($payslip_id) ? $payslip_id : null,
            'gross_pay' => $grossPay,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'payslip_information' => !empty($payslip_information) ? $payslip_information : null,
            'status' => 'Pending',
        ];
        $this->execute($sql, $params);
        $grievanceId = $this->db->lastInsertId();

        $this->linkAttendanceToGrievance($grievanceId, $employee_id, $referenceDate, $windowDays);
        $this->linkPayrollToGrievance($grievanceId, $employee_id, $subject, $description, !empty($payslip_id) ? $payslip_id : null, $attachment_path);

        (new Notification())->notifyHr('A new grievance was filed: ' . $subject, 'grievance', $employee_id ? [(int)$employee_id] : []);

        return $grievanceId;
    }

    private function linkPayrollToGrievance($grievanceId, $employeeId, $subject, $description, $payslipId = null, $attachmentPath = null)
    {
        if (empty($grievanceId) || empty($employeeId)) {
            return false;
        }

        $this->ensureGrievancePayrollTable();

        $existing = $this->execute('SELECT grievance_id FROM eer_grievance_payroll WHERE grievance_id = :grievanceId LIMIT 1', ['grievanceId' => $grievanceId])->fetch();

        $payrollModule = null;
        $referenceId = null;

        if (!empty($payslipId)) {
            $payslip = $this->execute('SELECT payslip_id FROM pr_payslips WHERE payslip_id = :payslipId AND employee_id = :employeeId LIMIT 1', ['payslipId' => $payslipId, 'employeeId' => $employeeId])->fetch();
            if (!empty($payslip)) {
                $payrollModule = 'Payslip Generation';
                $referenceId = (int) $payslip['payslip_id'];
            }
        }

        if (empty($payrollModule) && $this->hasTable('pr_employee_benefits')) {
            $benefit = $this->execute('SELECT id FROM pr_employee_benefits WHERE employee_id = :employeeId ORDER BY id DESC LIMIT 1', ['employeeId' => $employeeId])->fetch();
            if (!empty($benefit)) {
                $payrollModule = 'Benefits Management';
                $referenceId = (int) $benefit['id'];
            }
        }

        if (empty($payrollModule) && $this->hasTable('pr_employee_deductions')) {
            $idColumn = $this->hasColumn('pr_employee_deductions', 'employee_deduction_id') ? 'employee_deduction_id' : ($this->hasColumn('pr_employee_deductions', 'id') ? 'id' : null);
            if ($idColumn) {
                $deduction = $this->execute('SELECT ' . $idColumn . ' AS id FROM pr_employee_deductions WHERE employee_id = :employeeId ORDER BY ' . $idColumn . ' DESC LIMIT 1', ['employeeId' => $employeeId])->fetch();
                if (!empty($deduction)) {
                    $payrollModule = 'Tax & Deductions';
                    $referenceId = (int) $deduction['id'];
                }
            }
        }

        if (empty($payrollModule) && $this->hasTable('pr_employee_adjustments')) {
            $adjustment = $this->execute('SELECT adjustment_id FROM pr_employee_adjustments WHERE employee_id = :employeeId ORDER BY adjustment_id DESC LIMIT 1', ['employeeId' => $employeeId])->fetch();
            if (!empty($adjustment)) {
                $payrollModule = 'Tax & Deductions';
                $referenceId = (int) $adjustment['adjustment_id'];
            }
        }

        if (empty($payrollModule) && $this->hasTable('pr_tax_tables')) {
            $tax = $this->execute('SELECT tax_id FROM pr_tax_tables ORDER BY tax_id ASC LIMIT 1')->fetch();
            if (!empty($tax)) {
                $payrollModule = 'Tax & Deductions';
                $referenceId = (int) $tax['tax_id'];
            }
        }

        if (empty($payrollModule)) {
            return false;
        }

        $payload = [
            'grievance_id' => $grievanceId,
            'employee_id' => $employeeId,
            'payroll_module' => $payrollModule,
            'reference_id' => $referenceId,
            'complaint_title' => substr($subject, 0, 150),
            'complaint_details' => $description,
            'attachment' => $attachmentPath,
            'status' => 'Pending',
        ];

        if (!empty($existing)) {
            $fields = [];
            foreach (['employee_id', 'payroll_module', 'reference_id', 'complaint_title', 'complaint_details', 'attachment', 'status'] as $field) {
                $fields[] = "$field = :$field";
            }
            $sql = 'UPDATE eer_grievance_payroll SET ' . implode(', ', $fields) . ' WHERE grievance_id = :grievance_id';
            $this->execute($sql, $payload);
        } else {
            $sql = 'INSERT INTO eer_grievance_payroll (grievance_id, employee_id, payroll_module, reference_id, complaint_title, complaint_details, attachment, status) VALUES (:grievance_id, :employee_id, :payroll_module, :reference_id, :complaint_title, :complaint_details, :attachment, :status)';
            $this->execute($sql, $payload);
        }

        return true;
    }

    public function getPayrollContext($employeeId, $payslipId = null)
    {
        $context = [
            'payslips' => [],
            'payslip_items' => [],
            'deductions' => [],
            'benefits' => [],
            'tax_tables' => [],
        ];

        if (empty($employeeId)) {
            return $context;
        }

        $context['payslips'] = $this->execute(
            'SELECT payslip_id, gross_pay, total_deductions, net_pay, generated_at FROM pr_payslips WHERE employee_id = :employeeId ORDER BY generated_at DESC',
            ['employeeId' => $employeeId]
        )->fetchAll();

        if (!empty($payslipId)) {
            $context['payslip_items'] = $this->execute(
                'SELECT pi.payslip_item_id AS id, pi.item_type, pi.description, pi.amount FROM pr_payslip_items pi WHERE pi.payslip_id = :payslipId ORDER BY pi.item_type, pi.description',
                ['payslipId' => $payslipId]
            )->fetchAll();
        } elseif (!empty($context['payslips'])) {
            $context['payslip_items'] = $this->execute(
                'SELECT pi.payslip_item_id AS id, pi.item_type, pi.description, pi.amount FROM pr_payslip_items pi WHERE pi.payslip_id = :payslipId ORDER BY pi.item_type, pi.description',
                ['payslipId' => $context['payslips'][0]['payslip_id']]
            )->fetchAll();
        }

        if ($this->hasTable('pr_employee_benefits')) {
            $context['benefits'] = $this->execute(
                'SELECT id, has_sss, has_philhealth, has_pagibig, sss_amount_override, philhealth_amount_override, pagibig_amount_override, is_active, created_at FROM pr_employee_benefits WHERE employee_id = :employeeId ORDER BY created_at DESC',
                ['employeeId' => $employeeId]
            )->fetchAll();
        }

        if ($this->hasTable('pr_employee_deductions')) {
            $deductionIdColumn = $this->hasColumn('pr_employee_deductions', 'employee_deduction_id') ? 'ed.employee_deduction_id AS id' : ($this->hasColumn('pr_employee_deductions', 'id') ? 'ed.id' : 'ed.employee_id AS id');
            $selectParts = [$deductionIdColumn, 'ed.deduction_id'];

            if ($this->hasColumn('pr_employee_deductions', 'amount')) {
                $selectParts[] = 'ed.amount';
            }
            if ($this->hasColumn('pr_employee_deductions', 'created_at')) {
                $selectParts[] = 'ed.created_at';
            }

            $selectParts[] = 'd.name';
            $selectParts[] = 'd.type';
            $selectParts[] = 'd.is_statutory';

            $orderColumn = $this->hasColumn('pr_employee_deductions', 'created_at') ? 'ed.created_at' : ($this->hasColumn('pr_employee_deductions', 'employee_deduction_id') ? 'ed.employee_deduction_id' : 'ed.employee_id');

            $context['deductions'] = $this->execute(
                'SELECT ' . implode(', ', $selectParts) . ' FROM pr_employee_deductions ed LEFT JOIN pr_deductions d ON d.deduction_id = ed.deduction_id WHERE ed.employee_id = :employeeId ORDER BY ' . $orderColumn . ' DESC',
                ['employeeId' => $employeeId]
            )->fetchAll();
        } elseif ($this->hasTable('pr_employee_adjustments')) {
            $adjustmentColumns = ['adjustment_id AS id', 'description', 'amount', 'created_at'];
            if ($this->hasColumn('pr_employee_adjustments', 'type')) {
                $adjustmentColumns[] = 'type AS name';
            } else {
                $adjustmentColumns[] = 'description AS name';
            }

            $context['deductions'] = $this->execute(
                'SELECT ' . implode(', ', $adjustmentColumns) . ' FROM pr_employee_adjustments WHERE employee_id = :employeeId ORDER BY created_at DESC',
                ['employeeId' => $employeeId]
            )->fetchAll();
        }

        if ($this->hasTable('pr_tax_tables')) {
            $income = null;
            if (!empty($payslipId)) {
                $p = $this->execute('SELECT gross_pay FROM pr_payslips WHERE payslip_id = :payslipId AND employee_id = :employeeId LIMIT 1', ['payslipId' => $payslipId, 'employeeId' => $employeeId])->fetch();
                $income = $p['gross_pay'] ?? null;
            } elseif (!empty($context['payslips'][0]['gross_pay'])) {
                $income = $context['payslips'][0]['gross_pay'];
            }

            if (!empty($income)) {
                $context['tax_tables'] = $this->execute(
                    'SELECT tax_id, min_income, max_income, tax_rate, fixed_tax FROM pr_tax_tables WHERE (:income BETWEEN min_income AND max_income) OR (:income >= min_income AND max_income IS NULL) ORDER BY tax_id ASC LIMIT 5',
                    ['income' => $income]
                )->fetchAll();
            }
        }

        return $context;
    }

    private function linkAttendanceToGrievance($grievanceId, $employeeId, $referenceDate, $windowDays)
    {
        if (empty($grievanceId) || empty($employeeId)) {
            return false;
        }

        if (!$this->hasTable('ta_attendance') || !$this->hasTable('eer_grievance_attendance_links')) {
            return false;
        }

        $startDate = date('Y-m-d', strtotime($referenceDate . ' -' . max(1, (int) $windowDays) . ' days'));
        $endDate = date('Y-m-d', strtotime($referenceDate));

        $sql = 'INSERT INTO eer_grievance_attendance_links (grievance_id, employee_id, attendance_id, attendance_date, attendance_status, late_minutes, early_out_minutes)
                SELECT :grievance_id, :employee_id, a.attendance_id, a.attendance_date, a.status, a.late_minutes, a.early_out_minutes
                FROM ta_attendance a
                WHERE a.employee_id = :employee_id
                  AND a.attendance_date BETWEEN :start_date AND :end_date
                  AND NOT EXISTS (
                      SELECT 1
                      FROM eer_grievance_attendance_links l
                      WHERE l.grievance_id = :grievance_id
                        AND l.attendance_id = a.attendance_id
                  )';

        $this->execute($sql, [
            'grievance_id' => $grievanceId,
            'employee_id' => $employeeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return true;
    }

    public function getAttendanceLinksByGrievanceId($grievanceId)
    {
        if (empty($grievanceId)) {
            return [];
        }

        if (!$this->hasTable('eer_grievance_attendance_links')) {
            return [];
        }

        return $this->execute(
            'SELECT id, grievance_id, employee_id, attendance_id, attendance_date, attendance_status, late_minutes, early_out_minutes, linked_at
             FROM eer_grievance_attendance_links
             WHERE grievance_id = :grievance_id
             ORDER BY attendance_date DESC, linked_at DESC',
            ['grievance_id' => $grievanceId]
        )->fetchAll();
    }

    public function getEmployeePayslips($employeeId)
    {
        return $this->execute(
            'SELECT p.payslip_id AS id, p.generated_at, p.gross_pay, p.total_deductions, p.net_pay
             FROM pr_payslips p
             WHERE p.employee_id = :employeeId
             ORDER BY p.generated_at DESC',
            ['employeeId' => $employeeId]
        )->fetchAll();
    }

    public function getEmployeeAdjustments($employeeId, $payslipId = null)
    {
        if (!$this->hasTable('pr_employee_adjustments')) {
            return [];
        }

        $selectColumns = [
            'a.adjustment_id AS id',
            'a.description',
            'a.amount',
            'a.created_at',
        ];

        if ($this->hasColumn('pr_employee_adjustments', 'payroll_period_id')) {
            $selectColumns[] = 'a.payroll_period_id';
        }
        if ($this->hasColumn('pr_employee_adjustments', 'type')) {
            $selectColumns[] = 'a.type';
        }
        if ($this->hasColumn('pr_employee_adjustments', 'file_path')) {
            $selectColumns[] = 'a.file_path';
        }
        if ($this->hasColumn('pr_employee_adjustments', 'deduction_subtype')) {
            $selectColumns[] = 'a.deduction_subtype';
        }

        $sql = 'SELECT ' . implode(', ', $selectColumns) . ' FROM pr_employee_adjustments a WHERE a.employee_id = :employeeId';
        $params = ['employeeId' => $employeeId];

        if (!empty($payslipId) && $this->hasTable('pr_payslips') && $this->hasColumn('pr_payslips', 'generated_at')) {
            $p = $this->execute('SELECT generated_at FROM pr_payslips WHERE payslip_id = :payslipId AND employee_id = :employeeId', ['payslipId' => $payslipId, 'employeeId' => $employeeId])->fetch();
            if (!empty($p) && !empty($p['generated_at'])) {
                $generated = $p['generated_at'];
                $start = date('Y-m-d', strtotime($generated . ' -7 days'));
                $end = date('Y-m-d', strtotime($generated . ' +7 days'));
                $sql .= ' AND DATE(a.created_at) BETWEEN :startDate AND :endDate';
                $params['startDate'] = $start;
                $params['endDate'] = $end;
            }
        }

        $sql .= ' ORDER BY a.created_at DESC';
        return $this->execute($sql, $params)->fetchAll();
    }

    public function getEmployeeBenefits($employeeId)
    {
        return $this->execute(
            'SELECT b.id AS id, b.has_sss, b.sss_amount_override, b.has_philhealth, b.philhealth_amount_override, b.has_pagibig, b.pagibig_amount_override, b.is_active, b.created_at, b.updated_at
             FROM pr_employee_benefits b
             WHERE b.employee_id = :employeeId
             ORDER BY b.created_at DESC',
            ['employeeId' => $employeeId]
        )->fetchAll();
    }

    public function getPayslipItems($employeeId, $payslipId)
    {
        $items = $this->execute(
            'SELECT pi.payslip_item_id AS id, pi.item_type, pi.description, pi.amount
             FROM pr_payslip_items pi
             JOIN pr_payslips p ON pi.payslip_id = p.payslip_id
             WHERE pi.payslip_id = :payslipId AND p.employee_id = :employeeId
             ORDER BY pi.item_type, pi.description',
            ['payslipId' => $payslipId, 'employeeId' => $employeeId]
        )->fetchAll();

        if (empty($items)) {
            // Fallback if the employee relation does not match exactly but the payslip exists
            $items = $this->execute(
                'SELECT pi.payslip_item_id AS id, pi.item_type, pi.description, pi.amount
                 FROM pr_payslip_items pi
                 WHERE pi.payslip_id = :payslipId
                 ORDER BY pi.item_type, pi.description',
                ['payslipId' => $payslipId]
            )->fetchAll();
        }

        return $items;
    }

    public function tableExists($tableName)
    {
        return $this->hasTable($tableName);
    }

    public function columnExists($tableName, $columnName)
    {
        return $this->hasColumn($tableName, $columnName);
    }

    public function updateStatus($id, $status)
    {
        $sql = 'UPDATE eer_grievances SET status = :status WHERE eer_grievance_id = :id';
        $this->execute($sql, ['status' => $status, 'id' => $id]);
        $grievance = $this->find($id);
        if ($grievance) {
            (new Notification())->notifyHr(
                'Grievance #' . (int)$id . ' status changed to ' . $status . '.',
                'grievance',
                !empty($grievance['employee_id']) ? [(int)$grievance['employee_id']] : []
            );
        }
        return $grievance;
    }

    public function updateGrievanceManagement($id, array $data, $hrPersonnelId)
    {
        $fields = [];
        $params = ['id' => $id];

        if (array_key_exists('status', $data)) {
            $fields[] = 'status = :status';
            $params['status'] = $data['status'];
        }

        if (array_key_exists('assigned_to', $data) && $this->hasColumn('eer_grievances', 'assigned_to')) {
            $fields[] = 'assigned_to = :assigned_to';
            $params['assigned_to'] = $data['assigned_to'];
        }

        if (array_key_exists('resolution_of_complaint', $data)) {
            $fields[] = 'resolution_of_complaint = :resolution_of_complaint';
            $params['resolution_of_complaint'] = $data['resolution_of_complaint'];
        }

        if (array_key_exists('action_taken', $data)) {
            $fields[] = 'action_taken = :action_taken';
            $params['action_taken'] = $data['action_taken'];
        }

        if (array_key_exists('attachment_path', $data)) {
            $fields[] = 'attachment_path = :attachment_path';
            $params['attachment_path'] = $data['attachment_path'];
        }

        if (array_key_exists('confidential', $data)) {
            $fields[] = 'confidential = :confidential';
            $params['confidential'] = (int) $data['confidential'];
        }

        if (array_key_exists('compliance_record_id', $data)) {
            $columnExists = $this->hasColumn('eer_grievances', 'compliance_record_id');
            $complianceTableExists = $this->hasTable('lc_compliance_records');

            if (!$columnExists || !$complianceTableExists) {
                unset($data['compliance_record_id']);
            } else {
                $complianceRecordId = $data['compliance_record_id'] !== null && $data['compliance_record_id'] !== ''
                    ? (int) $data['compliance_record_id']
                    : null;

                if ($complianceRecordId !== null) {
                    $record = $this->execute('SELECT record_id FROM lc_compliance_records WHERE record_id = :record_id LIMIT 1', [
                        'record_id' => $complianceRecordId,
                    ])->fetch();
                    if (!$record) {
                        throw new \InvalidArgumentException('The selected Compliance Record does not exist.');
                    }
                }

                $fields[] = 'compliance_record_id = :compliance_record_id';
                $params['compliance_record_id'] = $complianceRecordId;
            }
        }

        if (array_key_exists('escalation_level', $data)) {
            $fields[] = 'escalation_level = :escalation_level';
            $params['escalation_level'] = $data['escalation_level'];
        }

        if (array_key_exists('escalation_reason', $data)) {
            $fields[] = 'escalation_reason = :escalation_reason';
            $params['escalation_reason'] = $data['escalation_reason'];
        }

        if (array_key_exists('resolved_at', $data)) {
            $fields[] = 'resolved_at = :resolved_at';
            $params['resolved_at'] = $data['resolved_at'];
        } elseif (!empty($data['status']) && in_array(strtolower(trim($data['status'])), ['resolved', 'closed'], true)) {
            $fields[] = 'resolved_at = NOW()';
        }

        $fields[] = 'updated_at = NOW()';

        if (empty($fields)) {
            return $this->find($id);
        }

        $sql = 'UPDATE eer_grievances SET ' . implode(', ', $fields) . ' WHERE eer_grievance_id = :id';
        $this->execute($sql, $params);
        return $this->find($id);
    }

    public function addGrievanceUpdate($id, $updateText, $updatedByUserId)
    {
        $sql = 'INSERT INTO eer_grievance_updates (grievance_id, update_text, updated_by_employee_id, updated_at) VALUES (:id, :update_text, :updated_by_employee_id, NOW())';
        $this->execute($sql, ['id' => $id, 'update_text' => $updateText, 'updated_by_employee_id' => $updatedByUserId]);
        return true;
    }

    public function updateResolution($id, $resolution_notes, $action_taken)
    {
        $sql = 'UPDATE eer_grievances SET resolution_of_complaint = :resolution_of_complaint, action_taken = :action_taken, updated_at = NOW() WHERE eer_grievance_id = :id';
        $this->execute($sql, ['resolution_of_complaint' => $resolution_notes, 'action_taken' => $action_taken, 'id' => $id]);
        return $this->find($id);
    }

    public function submitSatisfaction($id, $rating, $comment)
    {
        $sql = 'UPDATE eer_grievances SET satisfaction_rating = :rating, satisfaction_comment = :comment, updated_at = NOW() WHERE eer_grievance_id = :id';
        $this->execute($sql, ['rating' => $rating, 'comment' => $comment, 'id' => $id]);
        return $this->find($id);
    }

    public function addInvestigationNotes($id, $notes, $hrPersonnelId)
    {
        $sql = 'INSERT INTO grievance_notes (grievance_id, hr_personnel_id, notes, created_at) 
                VALUES (:id, :hrPersonnelId, :notes, NOW())';
        $this->execute($sql, ['id' => $id, 'hrPersonnelId' => $hrPersonnelId, 'notes' => $notes]);
    }

    public function updateConfidentialFlag($id, $isConfidential)
    {
        $sql = 'UPDATE eer_grievances SET confidential = :isConfidential WHERE eer_grievance_id = :id';
        $this->execute($sql, ['isConfidential' => $isConfidential, 'id' => $id]);
    }

    public function resolveGrievance($id, $resolution, $hrPersonnelId)
    {
        $sql = 'UPDATE eer_grievances SET resolution_of_complaint = :resolution_of_complaint, action_taken = :action_taken,
                status = :status, resolved_at = NOW(), updated_at = NOW()
                WHERE eer_grievance_id = :id';
        $this->execute($sql, [
            'resolution_of_complaint' => $resolution,
            'action_taken' => 'Resolved by HR Personnel ID: ' . $hrPersonnelId,
            'status' => 'Resolved',
            'id' => $id
        ]);
        return $this->find($id);
    }

    public function escalateGrievance($id, $escalationReason, $newLevel)
    {
        $sql = 'UPDATE eer_grievances SET escalation_level = :level, escalation_reason = :reason,
                status = :status, updated_at = NOW() WHERE eer_grievance_id = :id';
        $this->execute($sql, [
            'level' => $newLevel,
            'reason' => $escalationReason,
            'status' => 'Escalated',
            'id' => $id
        ]);
        return $this->find($id);
    }

    public function getGrievanceStats()
    {
        $sql = "SELECT
                COUNT(*) as total_grievances,
                SUM(CASE WHEN status IN ('Submitted','Pending') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Under Review' OR status = 'Investigation' THEN 1 ELSE 0 END) as under_review,
                SUM(CASE WHEN status = 'Resolution Proposed' THEN 1 ELSE 0 END) as resolution_proposed,
                SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed,
                SUM(CASE WHEN status = 'Escalated' THEN 1 ELSE 0 END) as escalated,
                SUM(CASE WHEN priority IN ('high','urgent','critical') THEN 1 ELSE 0 END) as critical_priority,
                SUM(CASE WHEN anonymous = 1 THEN 1 ELSE 0 END) as anonymous_grievances,
                AVG(CASE WHEN satisfaction_rating IS NOT NULL THEN satisfaction_rating ELSE NULL END) as avg_satisfaction
                FROM eer_grievances";
        return $this->execute($sql)->fetch();
    }

    public function getMyGrievances($employeeId)
    {
        $reporterName = "COALESCE(CONCAT_WS(' ', uc.first_name, uc.middle_name, uc.last_name), CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name), CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name)) AS filed_by";

        return $this->execute("SELECT g.*, g.eer_grievance_id AS id, $reporterName FROM eer_grievances g
            LEFT JOIN em_employees uc ON g.created_by_employee_id = uc.employee_id
            LEFT JOIN em_employees u ON g.employee_id = u.employee_id
            LEFT JOIN em_employees e ON g.employee_id = e.employee_id
            WHERE g.employee_id = :employeeId
            ORDER BY g.created_at DESC", ['employeeId' => $employeeId])->fetchAll();
    }

    public function generateReport($startDate, $endDate)
    {
        $sql = "SELECT g.*, COALESCE(CONCAT_WS(' ', uc.first_name, uc.middle_name, uc.last_name), CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name), CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name)) AS filed_by
            FROM eer_grievances g
            LEFT JOIN em_employees uc ON g.created_by_employee_id = uc.employee_id
            LEFT JOIN em_employees u ON g.employee_id = u.employee_id
            LEFT JOIN em_employees e ON g.employee_id = e.employee_id
            WHERE g.created_at BETWEEN :startDate AND :endDate
            ORDER BY g.created_at DESC";
        return $this->execute($sql, ['startDate' => $startDate, 'endDate' => $endDate])->fetchAll();
    }
}


