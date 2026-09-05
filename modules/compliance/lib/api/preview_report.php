<?php

header('Content-Type: text/html; charset=utf-8');

$key = $_GET['key'] ?? '';
$export = $_GET['export'] ?? 'export_report';

$db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

function pv_value(PDO $db, string $sql, $default = 0) {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function export_contract_status(?string $start, ?string $end, ?string $dbStatus = null): string {
    $today = new DateTime('today');
    $startDate = $start && $start !== '0000-00-00' ? new DateTime($start) : null;
    $endDate = $end && $end !== '0000-00-00' ? new DateTime($end) : null;

    if (in_array(strtolower((string) $dbStatus), ['void', 'terminated', 'cancelled'], true)) {
        return ucfirst(strtolower((string) $dbStatus));
    }
    if ($dbStatus === 'Renewed') return 'Renewed';
    if ($dbStatus === 'Extended') return 'Extended';

    if (!$startDate && !$endDate) return 'Draft';
    if ($startDate && $today < $startDate) return 'Draft';

    if ($endDate && $today > $endDate) return 'Expired';

    if ($endDate) {
        $diff = (int) $today->diff($endDate)->format('%r%a');
        if ($diff <= 30) return 'Renewal Required';
        if ($diff <= 90) return 'Expiring Soon';
    }

    return 'Active';
}

function export_compliance_status(string $contractStatus, ?string $signatureStatus = 'none', bool $requiresSig = true): string {
    $sig = strtolower((string) $signatureStatus);
    if ($contractStatus === 'Expired') return 'Non-Compliant';
    if ($requiresSig && in_array($sig, ['none', 'partial'], true)) return 'Pending Signature';
    if ($contractStatus === 'Renewal Required') return 'For Renewal';
    if ($contractStatus === 'Extended') return 'For Extension';
    if (in_array($contractStatus, ['Under Review', 'Draft'], true)) return 'Under Review';
    return 'Compliant';
}

$reportConfig = [
    'employee_master_list' => [
        'table' => 'em_employees',
        'title' => 'Employee Master List',
        'summary' => function(PDO $db) {
            return [
                'Total Employees' => (int) pv_value($db, "SELECT COUNT(*) FROM em_employees WHERE employment_status NOT IN ('Resigned','Terminated')"),
                'Active' => (int) pv_value($db, "SELECT COUNT(*) FROM em_employees WHERE employment_status = 'Regular'"),
                'Probationary' => (int) pv_value($db, "SELECT COUNT(*) FROM em_employees WHERE employment_status = 'Probationary'"),
            ];
        },
    ],
    'employee_compliance' => [
        'table' => 'lc_compliance_records',
        'title' => 'Employee Compliance Status',
    ],
    'employee_documents' => [
        'table' => 'lc_employee_documents',
        'title' => 'Employee Documents',
    ],
    'employment_contracts' => [
        'type' => 'contract_compliance',
        'title' => 'Employment Contracts',
    ],
    'document_expiration' => [
        'table' => 'lc_employee_documents',
        'title' => 'Document Expiration',
    ],
    'training_certifications' => [
        'table' => 'lc_trainings',
        'title' => 'Training & Certifications',
    ],
    'policy_acknowledgement' => [
        'table' => 'lc_acknowledgment_log',
        'title' => 'Policy Acknowledgement',
    ],
    'leave_summary' => [
        'table' => 'leave_requests',
        'title' => 'Leave Summary',
    ],
    'sss_compliance' => [
        'type' => 'government',
        'subtype' => 'sss',
        'title' => 'SSS Compliance',
    ],
    'philhealth_compliance' => [
        'type' => 'government',
        'subtype' => 'philhealth',
        'title' => 'PhilHealth Compliance',
    ],
    'pagibig_compliance' => [
        'type' => 'government',
        'subtype' => 'pagibig',
        'title' => 'Pag-IBIG Compliance',
    ],
    'bir_compliance' => [
        'type' => 'government',
        'subtype' => 'bir',
        'title' => 'BIR Compliance',
    ],
    'government_submission' => [
        'table' => 'lc_government_validations',
        'title' => 'Government Submission Status',
    ],
    'missing_registrations' => [
        'table' => 'lc_government_requirements',
        'title' => 'Missing Government Registrations',
    ],
    'government_summary' => [
        'table' => 'lc_compliance_records',
        'title' => 'Government Compliance Summary',
    ],
    'incident_reports' => [
        'type' => 'incident',
        'title' => 'Incident Reports',
    ],
    'disciplinary_actions' => [
        'table' => 'lc_disciplinary_actions',
        'title' => 'Disciplinary Actions',
    ],
    'anonymous_reports' => [
        'table' => 'lc_complaints',
        'title' => 'Anonymous Reports',
    ],
    'legal_cases' => [
        'table' => 'lc_compliance_violations',
        'title' => 'Legal Cases',
    ],
    'risk_assessment' => [
        'type' => 'risk',
        'title' => 'Risk Assessment',
    ],
    'audit_findings' => [
        'table' => 'lc_audit_findings',
        'title' => 'Audit Findings',
    ],
    'recruitment_summary' => [
        'table' => 'lc_recruitment',
        'title' => 'Recruitment Summary',
    ],
    'new_employees' => [
        'table' => 'em_employees',
        'title' => 'New Employees',
    ],
    'contract_renewals' => [
        'type' => 'contract_compliance',
        'title' => 'Contract Renewals',
    ],
    'exit_clearance' => [
        'table' => 'lc_exit_clearance',
        'title' => 'Exit Clearance',
    ],
    'exit_summary' => [
        'table' => 'exit_resignations',
        'title' => 'Exit Summary',
    ],
    'job_posting_approval' => [
        'table' => 'lc_job_posting_requests',
        'title' => 'Job Posting Approval',
    ],
    'vacancy_reports' => [
        'table' => 'lc_vacant_positions',
        'title' => 'Vacancy Reports',
    ],
];

if (!isset($reportConfig[$key])) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><head><title>Invalid Report</title></head><body><h1>Invalid Report</h1><p>The requested report key is not recognized.</p></body></html>';
    exit;
}

$config = $reportConfig[$key];
$exportDate = date('Y-m-d H:i:s');
$summary = $config['summary'] ?? null;
$summaryData = $summary ? $summary($db) : null;
$columns = [];
$rows = [];

if (isset($config['type'])) {
    switch ($config['type']) {
        case 'contract_compliance':
            $sql = "SELECT c.contract_number, e.employee_code AS employee_no, CONCAT(e.first_name, ' ', e.last_name) AS full_name, d.department_name, p.position_name, c.start_date, c.end_date, c.monthly_salary
                    FROM lc_contracts c
                    LEFT JOIN em_employees e ON e.employee_id = c.employee_id
                    LEFT JOIN em_departments d ON d.department_id = e.department_id
                    LEFT JOIN em_positions p ON p.position_id = e.position_id
                    ORDER BY c.created_at DESC";
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $idx => $r) {
                $rows[$idx]['_computed_status'] = export_contract_status($r['start_date'] ?? null, $r['end_date'] ?? null, $r['status'] ?? null);
                $rows[$idx]['_compliance'] = export_compliance_status($rows[$idx]['_computed_status'], $r['digital_signature_status'] ?? 'none', (bool)($r['requires_dual_sig'] ?? 1));
            }
            $columns = ['Contract No.', 'Employee No.', 'Employee Name', 'Department', 'Position', 'Start Date', 'End Date', 'Contract Status', 'Compliance Status', 'Monthly Salary'];
            break;

        case 'government':
            $subtype = $config['subtype'];
            $table = $subtype . '_contributions';
            $summaryData = [
                'Total Employees' => (int) pv_value($db, "SELECT COUNT(*) FROM em_employees WHERE employment_status NOT IN ('Resigned','Terminated')"),
                'Submitted' => (int) pv_value($db, "SELECT COUNT(*) FROM $table WHERE status = 'submitted'"),
                'Pending' => (int) pv_value($db, "SELECT COUNT(*) FROM $table WHERE status = 'pending'"),
                'Rejected' => (int) pv_value($db, "SELECT COUNT(*) FROM $table WHERE status = 'rejected'"),
            ];
            $sql = "SELECT CONCAT(e.first_name, ' ', e.last_name) AS full_name, e.employee_no, c.contribution_number, c.status, c.created_at, c.updated_at
                    FROM $table c
                    LEFT JOIN em_employees e ON e.employee_id = c.employee_id
                    ORDER BY c.created_at DESC
                    LIMIT 100";
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $columns = ['full_name' => 'Employee', 'employee_no' => 'Employee No.', 'contribution_number' => 'Contribution No.', 'status' => 'Status', 'created_at' => 'Date Submitted', 'updated_at' => 'Updated'];
            break;

        case 'incident':
            $sql = "SELECT i.incident_id, i.incident_type, i.type, i.severity, i.status,
                           i.incident_date, i.incident_time, i.location, i.title,
                           COALESCE(i.reporter_name, 'Unassigned') AS reporter_name,
                           COALESCE(i.assigned_name, 'Unassigned') AS assigned_name
                    FROM incident_report i
                    ORDER BY i.incident_date DESC
                    LIMIT 200";
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $summaryData = ['Total Incidents' => count($rows)];
            $columns = ['incident_id' => 'Incident ID', 'incident_type' => 'Incident Type', 'type' => 'Type', 'reporter_name' => 'Reporter', 'assigned_name' => 'Assigned To', 'severity' => 'Severity', 'status' => 'Status', 'incident_date' => 'Date', 'location' => 'Location'];
            break;

        case 'risk':
            $sql = "SELECT r.id, r.risk_type, r.severity, r.status, r.description, r.created_at,
                           COALESCE(CONCAT(e.first_name, ' ', e.last_name), 'Unassigned') AS employee_name,
                           COALESCE(CONCAT(o.first_name, ' ', o.last_name), 'Unassigned') AS owner_name,
                           COALESCE(CONCAT(i.first_name, ' ', i.last_name), 'Unassigned') AS investigator_name
                    FROM lc_risks r
                    LEFT JOIN em_employees e ON e.employee_id = r.employee_id
                    LEFT JOIN em_employees o ON o.employee_id = r.owner_id
                    LEFT JOIN em_employees i ON i.employee_id = r.investigator_id
                    ORDER BY r.created_at DESC
                    LIMIT 100";
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $summaryData = ['Total Risks' => count($rows)];
            $columns = ['id' => 'Risk ID', 'risk_type' => 'Risk Type', 'severity' => 'Severity', 'status' => 'Status', 'employee_name' => 'Employee', 'owner_name' => 'Owner', 'investigator_name' => 'Investigator', 'created_at' => 'Created', 'description' => 'Description'];
            break;
    }
} elseif (isset($config['table'])) {
    try {
        $stmt = $db->query("SELECT * FROM `{$config['table']}` ORDER BY 1 DESC LIMIT 500");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columns = $rows ? array_keys($rows[0]) : [];
    } catch (Throwable $e) {
        $rows = [];
        $columns = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['title']) ?> Preview</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 13px;
            color: #1b2430;
            background: #f3f5f9;
            padding: 24px;
            line-height: 1.5;
        }
        .preview-container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(13,27,46,.08);
            overflow: hidden;
        }
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 20px 24px;
            border-bottom: 3px solid #a8791f;
            background: #fff;
        }
        .preview-title h1 {
            font-size: 18px;
            font-weight: 700;
            color: #0d1b2e;
            margin-bottom: 4px;
        }
        .preview-title p {
            font-size: 12px;
            color: #5b6472;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .preview-meta {
            text-align: right;
            font-size: 12px;
            color: #5b6472;
        }
        .preview-meta strong {
            color: #1b2430;
        }
        .preview-body {
            padding: 20px 24px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: #f8fafc;
            border: 1px solid #e4e8ee;
            border-radius: 10px;
            padding: 14px 16px;
            text-align: center;
        }
        .summary-card .value {
            font-size: 22px;
            font-weight: 800;
            color: #0d1b2e;
        }
        .summary-card .label {
            font-size: 11px;
            font-weight: 700;
            color: #5b6472;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 4px;
        }
        .data-table-wrap {
            border: 1px solid #e4e8ee;
            border-radius: 10px;
            position: relative;
        }
        .table-top-scroll {
            overflow-x: auto;
            overflow-y: hidden;
            border-bottom: 2px solid #dde3ea;
        }
        .table-header-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            table-layout: auto;
        }
        .table-scroll-main {
            overflow: auto;
            max-height: 70vh;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            table-layout: auto;
        }
        th, td {
            padding: 6px 12px;
            border-bottom: 1px solid #eef1f5;
            vertical-align: top;
            white-space: nowrap;
        }
        th {
            background: #f3f5f9;
            text-align: left;
            border-bottom: 2px solid #dde3ea;
            color: #0d1b2e;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }
        tr:nth-child(even) td {
            background: #fafbfc;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-compliant { background: rgba(47,158,110,.12); color: #1f7a52; }
        .badge-submitted { background: rgba(47,158,110,.12); color: #1f5a42; }
        .badge-pending { background: rgba(217,154,43,.14); color: #a86b13; }
        .badge-overdue { background: rgba(214,72,74,.12); color: #a3272a; }
        .badge-rejected { background: rgba(214,72,74,.12); color: #a3272a; }
        .badge-info { background: rgba(59,130,196,.12); color: #1c5a8a; }
        .badge-progress { background: rgba(217,154,43,.14); color: #8a5a10; }
        .no-data {
            text-align: center;
            color: #8b95a4;
            padding: 24px;
            font-style: italic;
        }
        .footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #dde3ea;
            font-size: 11px;
            color: #8b95a4;
            text-align: center;
        }
        .actions {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #dde3ea;
            background: #fff;
            color: #1c5a8a;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #f3f5f9;
            border-color: #d3d9e2;
        }
        .btn-primary {
            background: #3b82c4;
            color: #fff;
            border-color: #3b82c4;
        }
        .btn-primary:hover {
            background: #1c5a8a;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-header">
            <div class="preview-title">
                <h1><?= htmlspecialchars($config['title']) ?></h1>
                <p>HR Legal Compliance Management System</p>
            </div>
            <div class="preview-meta">
                <p><strong>Generated:</strong> <?= htmlspecialchars($exportDate) ?></p>
                <p><strong>Records:</strong> <?= number_format(count($rows)) ?></p>
            </div>
        </div>

        <div class="preview-body">
            <?php if ($summaryData): ?>
            <div class="summary-grid">
                <?php foreach ($summaryData as $label => $value): ?>
                <div class="summary-card">
                    <div class="value"><?= number_format((int)$value) ?></div>
                    <div class="label"><?= htmlspecialchars($label) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="data-table-wrap">
                <div class="table-top-scroll" id="tableTopScroll">
                    <div class="table-top-scroll-spacer" id="tableTopScrollSpacer"></div>
                </div>
                <div class="table-scroll-main" id="tableScrollMain">
                <table class="table-header-table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                            <th><?= htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', $col))) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="<?= count($columns) ?>" class="no-data">No records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                <td><?= htmlspecialchars((string)$cell) ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <div class="footer">
                <p>This preview was auto-generated by the HR Legal Compliance Management System on <?= htmlspecialchars($exportDate) ?></p>
                <p>Bestlink College of the Philippines — Legal Compliance Office</p>
            </div>
        </div>
    </div>
    <script>
    (function() {
        const top = document.getElementById('tableTopScroll');
        const main = document.getElementById('tableScrollMain');
        if (!top || !main) return;
        const headerTable = main.querySelector('.table-header-table');
        const bodyTable = main.querySelectorAll('table')[1] || main.querySelector('table');
        if (!headerTable || !bodyTable) return;

        const spacer = document.getElementById('tableTopScrollSpacer');

        let syncing = false;

        function syncWidth() {
            const width = bodyTable.scrollWidth + 'px';
            if (spacer) spacer.style.width = width;
            headerTable.style.width = width;
            headerTable.style.minWidth = width;
        }

        function syncFromMain() {
            if (syncing) return;
            syncing = true;
            top.scrollLeft = main.scrollLeft;
            headerTable.scrollLeft = main.scrollLeft;
            syncing = false;
        }

        function syncFromTop() {
            if (syncing) return;
            syncing = true;
            main.scrollLeft = top.scrollLeft;
            headerTable.scrollLeft = top.scrollLeft;
            syncing = false;
        }

        main.addEventListener('scroll', syncFromMain);
        top.addEventListener('scroll', syncFromTop);
        window.addEventListener('resize', syncWidth);

        requestAnimationFrame(function() {
            syncWidth();
            if (main.scrollLeft > 0) syncFromMain();
        });
    })();
    </script>
</body>
</html>


