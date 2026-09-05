<?php

require_once __DIR__ . '/../../../../database/db.php';

function dg_get_active_employer(PDO $db): array {
    static $cached = null;
    if ($cached !== null) return $cached;

    try {
        $stmt = $db->prepare("SELECT * FROM lc_employer_profiles WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        $cached = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $cached = [];
    }

    if (empty($cached)) {
        $companyName = 'Bestlink College of the Philippines';
        $cached = [
            'name'    => $companyName,
            'address' => '',
        ];
    }

    return $cached;
}

function dg_get_document_template(PDO $db, string $templateCode, ?string $version = null): ?array {
    try {
        if ($version !== null && $version !== '') {
            $stmt = $db->prepare("
                SELECT template_content, template_name, governing_law, jurisdiction, category_id, version
                FROM lc_document_templates
                WHERE template_code = :code AND version = :version AND status IN ('Active', 'Approved')
                LIMIT 1
            ");
            $stmt->execute([':code' => $templateCode, ':version' => $version]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        }

        $stmt = $db->prepare("
            SELECT template_content, template_name, governing_law, jurisdiction, category_id, version
            FROM lc_document_templates
            WHERE template_code = :code AND status = 'Active'
            ORDER BY is_default DESC, effective_date DESC, template_id DESC
            LIMIT 1
        ");
        $stmt->execute([':code' => $templateCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function dg_markdown_to_html(string $text): string {
    $lines = explode("\n", $text);
    $html = '';
    $inList = false;
    $listType = '';
    $inTable = false;
    $tableLines = [];

    $flushTable = function () use (&$html, &$inTable, &$tableLines) {
        if (!$inTable || empty($tableLines)) {
            return;
        }
        $html .= '<table style="width:100%; border-collapse:collapse; margin:4px 0;">';
        $headerProcessed = false;
        foreach ($tableLines as $tableLine) {
            if (preg_match('/^\|?\s*[-:]+(?:\s*\|\s*[-:]+)*\s*\|?\s*$/', $tableLine)) {
                $headerProcessed = true;
                continue;
            }
            $cells = array_values(array_filter(array_map('trim', explode('|', $tableLine)), function ($c) { return $c !== '' && $c !== '-'; }));
            if ($headerProcessed) {
                $html .= '<tr>';
                foreach ($cells as $cell) {
                    $cell = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $cell);
                    $cell = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $cell);
                    $html .= '<td style="padding:3px 4px; border:1px solid #d7dbe3; vertical-align:top; font-size:9pt;">' . $cell . '</td>';
                }
                $html .= '</tr>';
                $headerProcessed = false;
            }
        }
        $html .= '</table>';
        $inTable = false;
        $tableLines = [];
    };

    foreach ($lines as $line) {
        $trimmed = ltrim($line);

        if (preg_match('/^\|(.+)\|\s*$/', $line)) {
            if (!$inTable) {
                $flushTable();
                $inTable = true;
            }
            $tableLines[] = $line;
            continue;
        }

        if ($inTable) {
            $flushTable();
        }

        $listMatch = [];
        if (preg_match('/^(\s*)([-*+])\s+(.+)$/', $line, $listMatch)) {
            if (!$inList || $listType !== 'ul') {
                if ($inList) $html .= $listType === 'ul' ? "</ul>\n" : "</ol>\n";
                $html .= "<ul>\n";
                $inList = true;
                $listType = 'ul';
            }
            $item = $listMatch[3];
            $item = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $item);
            $item = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $item);
            $html .= '<li>' . $item . "</li>\n";
        } elseif (preg_match('/^(\s*)(\d+)\.\s+(.+)$/', $line, $listMatch)) {
            if (!$inList || $listType !== 'ol') {
                if ($inList) $html .= $listType === 'ul' ? "</ul>\n" : "</ol>\n";
                $html .= "<ol>\n";
                $inList = true;
                $listType = 'ol';
            }
            $item = $listMatch[3];
            $item = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $item);
            $item = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $item);
            $html .= '<li>' . $item . "</li>\n";
        } else {
            if ($inList) {
                $html .= $listType === 'ul' ? "</ul>\n" : "</ol>\n";
                $inList = false;
                $listType = '';
            }
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $headingMatch)) {
                $level = strlen($headingMatch[1]);
                $headingText = $headingMatch[2];
                $headingText = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $headingText);
                $headingText = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $headingText);
                $html .= '<h' . $level . '>' . $headingText . "</h{$level}>\n";
            } elseif (preg_match('/^---$/', $trimmed)) {
                $html .= "<hr />\n";
            } elseif (preg_match('/^\s*$/', $line)) {
                $html .= "<br />\n";
            } else {
                $text = $trimmed;
                $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
                $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
                $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
                $html .= '<p>' . $text . "</p>\n";
            }
        }
    }

    $flushTable();

    if ($inList) {
        $html .= $listType === 'ul' ? "</ul>\n" : "</ol>\n";
    }

    return $html;
}

function dg_apply_meta_overrides(array &$employee): void {
    $readOnlyKeys = ['full_name', 'employee_no', 'birthdate', 'sex', 'marital_status', 'nationality'];
    foreach ($_GET as $key => $value) {
        if ($value === null || $value === '') continue;
        if (in_array($key, $readOnlyKeys, true)) continue;
        if (array_key_exists($key, $employee)) {
            $employee[$key] = $value;
        }
    }
}

function dg_render_meta_form(array $employee, array $visibleFields): string {
    $readOnlyKeys = ['full_name', 'employee_no', 'birthdate', 'sex', 'marital_status', 'nationality'];
    $fieldLabels = [
        'full_name' => 'Full Name',
        'employee_no' => 'Employee No',
        'email' => 'Email',
        'phone_number' => 'Phone',
        'address' => 'Address',
        'department_name' => 'Department',
        'position_name' => 'Position',
        'date_hired' => 'Date Hired',
        'employment_status' => 'Employment Status',
        'birthdate' => 'Birthdate',
        'sex' => 'Sex',
        'marital_status' => 'Marital Status',
        'nationality' => 'Nationality',
        'teacher_qualification' => 'Teacher Qualification',
        'onboarding_stage' => 'Onboarding Stage',
        'last_working_day' => 'Last Working Day',
        'monthly_salary' => 'Monthly Basic Salary',
    ];
    $inputTypes = [
        'email' => 'email',
        'phone_number' => 'tel',
        'date_hired' => 'date',
        'birthdate' => 'date',
        'last_working_day' => 'date',
        'monthly_salary' => 'number',
    ];

    $html = '<form method="get" action="" style="margin-bottom:16px;">';
    $preserve = ['employee_id', 'document_type', 'template', 'hr_signatory'];
    foreach ($preserve as $key) {
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            $html .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($_GET[$key]) . '">';
        }
    }
    foreach (['template_code', 'contract_start_date', 'contract_end_date', 'contract_type', 'contract_salary_input'] as $key) {
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            $html .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($_GET[$key]) . '">';
        }
    }
    foreach ($visibleFields as $fieldKey) {
        if (isset($_GET[$fieldKey]) && $_GET[$fieldKey] !== '' && !in_array($fieldKey, $readOnlyKeys, true)) {
            $html .= '<input type="hidden" name="' . $fieldKey . '" value="' . htmlspecialchars($_GET[$fieldKey]) . '">';
        }
    }

    $html .= '<div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">';
    foreach ($visibleFields as $fieldKey) {
        $label = $fieldLabels[$fieldKey] ?? ucwords(str_replace('_', ' ', $fieldKey));
        $rawValue = (string) ($employee[$fieldKey] ?? '');
        $isReadOnly = in_array($fieldKey, $readOnlyKeys, true);
        $inputType = $inputTypes[$fieldKey] ?? 'text';
        $stepAttr = $fieldKey === 'monthly_salary' ? ' step="0.01"' : '';
        $readOnlyAttr = $isReadOnly ? ' readonly' : '';
        $displayValue = $rawValue;
        if (in_array($fieldKey, ['date_hired', 'birthdate', 'last_working_day'], true) && $rawValue !== '' && $rawValue !== '______') {
            $displayValue = date('Y-m-d', strtotime($rawValue));
        } elseif ($fieldKey === 'monthly_salary' && $rawValue !== '' && $rawValue !== '0.00' && $rawValue !== '_________________') {
            $displayValue = $rawValue;
        }

        $html .= '<div style="display:flex; flex-direction:column; gap:4px;">';
        $html .= '<label style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-400,#8b93a1);">' . htmlspecialchars($label) . '</label>';
        $html .= '<input type="' . $inputType . '" name="' . $fieldKey . '" value="' . htmlspecialchars($displayValue) . '"' . $readOnlyAttr . $stepAttr . ' style="padding:8px 10px; border-radius:8px; border:1px solid var(--border,#e4e8ee); font-size:.88rem; color:var(--text-900,#1b2430); background:var(--card-bg,#ffffff); min-width:160px;">';
        $html .= '</div>';
    }
    $html .= '<button type="submit" style="padding:8px 14px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:var(--card-bg,#ffffff); color:var(--text-900,#1b2430); font-weight:600; cursor:pointer;">Update</button>';
    $html .= '</div></form>';

    return $html;
}

function dg_number_to_words($number): string {
    $number = (float) str_replace([',', ' '], '', $number);
    if ($number == 0) return 'Zero';

    $negative = $number < 0;
    $number = abs($number);

    $millions = floor($number / 1000000);
    $thousands = floor(($number % 1000000) / 1000);
    $remainder = $number % 1000;

    $words = [];
    if ($negative) $words[] = 'Negative';

    if ($millions > 0) {
        $words[] = dg_number_to_words_chunk($millions) . ' Million';
    }
    if ($thousands > 0) {
        $words[] = dg_number_to_words_chunk($thousands) . ' Thousand';
    }
    if ($remainder > 0) {
        $words[] = dg_number_to_words_chunk($remainder);
    }

    return implode(' ', $words);
}

function dg_number_to_words_chunk($number): string {
    $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];

    $number = (int) $number;
    if ($number == 0) return '';
    if ($number < 20) return $ones[$number];
    if ($number < 100) return $tens[floor($number / 10)] . ($number % 10 > 0 ? ' ' . $ones[$number % 10] : '');
    if ($number < 1000) return $ones[floor($number / 100)] . ' Hundred' . ($number % 100 > 0 ? ' ' . dg_number_to_words_chunk($number % 100) : '');
    return $number;
}

function dg_replace_placeholders(string $content, array $employee, array $employer, array $context = []): string {
    if (empty($content)) return '';

    $today = date('F d, Y');
    $fullName      = htmlspecialchars((string) ($employee['full_name'] ?? ''), ENT_QUOTES);
    $employeeNo    = htmlspecialchars((string) ($employee['employee_no'] ?? ''), ENT_QUOTES);
    $position      = htmlspecialchars((string) ($employee['position_name'] ?? ''), ENT_QUOTES);
    $department    = htmlspecialchars((string) ($employee['department_name'] ?? ''), ENT_QUOTES);
    $address       = htmlspecialchars((string) ($employee['address'] ?? ''), ENT_QUOTES);
    $phone         = htmlspecialchars((string) ($employee['phone_number'] ?? ''), ENT_QUOTES);
    $email         = htmlspecialchars((string) ($employee['email'] ?? ''), ENT_QUOTES);
    $rawDateHired  = (string) ($employee['date_hired'] ?? $employee['hire_date'] ?? '');
    $dateHired     = $rawDateHired !== '' ? date('F d, Y', strtotime($rawDateHired)) : '______';
    $birthdate     = !empty($employee['birthdate']) ? date('F d, Y', strtotime($employee['birthdate'])) : '______';
    $sex           = htmlspecialchars((string) ($employee['sex'] ?? ''), ENT_QUOTES);
    $maritalStatus = htmlspecialchars((string) ($employee['marital_status'] ?? ''), ENT_QUOTES);
    $employmentStatus = htmlspecialchars((string) ($employee['employment_status'] ?? ''), ENT_QUOTES);
    $nationality   = htmlspecialchars((string) ($employee['nationality'] ?? 'Filipino'), ENT_QUOTES);

    $employerName     = htmlspecialchars((string) ($employer['name'] ?? ''), ENT_QUOTES);
    $employerAddress  = htmlspecialchars((string) ($employer['address'] ?? ''), ENT_QUOTES);

    $hrSignatory = dg_get_signature_image();

    $contractType = $employmentStatus;
    if ($contractType === '') {
        $contractType = '________________';
    }

    $rawContractType   = $_GET['contract_type'] ?? null;
    $rawContractSalary = $_GET['contract_salary_input'] ?? null;

    if ($rawContractType !== null && $rawContractType !== '' && in_array($rawContractType, ['Regular','Probationary','Fixed-Term','Project','Seasonal','Casual','Part-Time'], true)) {
        $contractType = $rawContractType;
    }

    $rawContractStart = $_GET['contract_start_date'] ?? null;
    $rawContractEnd   = $_GET['contract_end_date'] ?? null;

    $contractStart = $dateHired !== '______' ? $dateHired : '______';
    $contractEnd   = $contractStart !== '______'
        ? date('F d, Y', strtotime('+1 year', strtotime($rawDateHired)))
        : '______';

    if ($rawContractStart !== null && $rawContractStart !== '' && strtotime($rawContractStart) !== false) {
        $contractStart = date('F d, Y', strtotime($rawContractStart));
    }

    if ($rawContractEnd !== null && $rawContractEnd !== '' && strtotime($rawContractEnd) !== false) {
        $contractEnd = date('F d, Y', strtotime($rawContractEnd));
    } elseif ($rawContractStart !== null && $rawContractStart !== '' && strtotime($rawContractStart) !== false) {
        $contractEnd = date('F d, Y', strtotime('+1 year', strtotime($rawContractStart)));
    }

    $contractSalary = '_________________';
    if ($rawContractSalary !== null && $rawContractSalary !== '' && is_numeric($rawContractSalary)) {
        $contractSalary = 'PHP ' . number_format((float) $rawContractSalary, 2);
    } elseif (!empty($employee['monthly_salary'])) {
        $contractSalary = 'PHP ' . number_format((float) $employee['monthly_salary'], 2);
    }

    $rawOriginalSalary = $_GET['original_salary'] ?? null;
    $originalSalary = '_________________';
    if ($rawOriginalSalary !== null && $rawOriginalSalary !== '' && is_numeric($rawOriginalSalary)) {
        $originalSalary = 'PHP ' . number_format((float) $rawOriginalSalary, 2);
    }

    $contractExecutionDate = $today;

    $rawLeaveType = $_GET['leave_type'] ?? null;
    $rawLeaveStart = $_GET['leave_start_date'] ?? null;
    $rawLeaveEnd = $_GET['leave_end_date'] ?? null;
    $rawLeaveDuration = $_GET['leave_duration'] ?? null;

    $leaveType = '________________';
    $leaveStart = '__________';
    $leaveEnd = '__________';
    $leaveDuration = '__________';

    if ($rawLeaveType !== null && $rawLeaveType !== '') {
        $leaveType = $rawLeaveType;
    }

    if ($rawLeaveStart !== null && $rawLeaveStart !== '' && strtotime($rawLeaveStart) !== false) {
        $leaveStart = date('F d, Y', strtotime($rawLeaveStart));
    }

    if ($rawLeaveEnd !== null && $rawLeaveEnd !== '' && strtotime($rawLeaveEnd) !== false) {
        $leaveEnd = date('F d, Y', strtotime($rawLeaveEnd));
    } elseif ($rawLeaveStart !== null && $rawLeaveStart !== '' && strtotime($rawLeaveStart) !== false) {
        $leaveEnd = date('F d, Y', strtotime('+3 days', strtotime($rawLeaveStart)));
    }

    if ($rawLeaveDuration !== null && $rawLeaveDuration !== '') {
        $leaveDuration = $rawLeaveDuration;
    }

    $salaryForWords = '_________________';
    if ($rawContractSalary !== null && $rawContractSalary !== '' && is_numeric($rawContractSalary)) {
        $salaryForWords = (float) $rawContractSalary;
    } elseif (!empty($employee['monthly_salary']) && is_numeric($employee['monthly_salary'])) {
        $salaryForWords = (float) $employee['monthly_salary'];
    }
    $salaryWords = $salaryForWords !== '_________________' ? dg_number_to_words($salaryForWords) . ' Pesos' : '_________________';

    $governingLaw = htmlspecialchars((string) ($context['governing_law'] ?? ($_GET['governing_law'] ?? 'Philippine Labor Code (PD 442)')), ENT_QUOTES);
    $templateVersion = htmlspecialchars((string) ($context['template_version'] ?? ($_GET['template_version'] ?? '1.0')), ENT_QUOTES);
    $contractNumber = htmlspecialchars((string) ($context['contract_number'] ?? ($_GET['contract_number'] ?? '________________')), ENT_QUOTES);
    $authorizedRep = htmlspecialchars((string) ($context['authorized_representative'] ?? ($_GET['authorized_representative'] ?? ($employerName . ' – Human Resources Department'))), ENT_QUOTES);
    $authorizedPosition = htmlspecialchars((string) ($context['authorized_position'] ?? ($_GET['authorized_position'] ?? 'HR Directress')), ENT_QUOTES);
    $witnessName2 = htmlspecialchars((string) ($context['witness_name_2'] ?? ($_GET['witness_name_2'] ?? '________________')), ENT_QUOTES);

    $acknowledgementDate = date('F d, Y');

    $effectiveDate = htmlspecialchars((string) ($context['effective_date'] ?? ($_GET['effective_date'] ?? '')), ENT_QUOTES);
    $signatureDate = htmlspecialchars((string) ($context['signature_date'] ?? ($_GET['signature_date'] ?? '')), ENT_QUOTES);
    $authorizedRepresentative = htmlspecialchars((string) ($context['authorized_representative'] ?? ($_GET['authorized_representative'] ?? $authorizedRep)), ENT_QUOTES);
    $employerSignatureDate = htmlspecialchars((string) ($context['employer_signature_date'] ?? ($_GET['employer_signature_date'] ?? '')), ENT_QUOTES);
    $witnessName = htmlspecialchars((string) ($context['witness_name'] ?? ($_GET['witness_name'] ?? '')), ENT_QUOTES);
    $witnessPosition = htmlspecialchars((string) ($context['witness_position'] ?? ($_GET['witness_position'] ?? '')), ENT_QUOTES);
    $witnessDate = htmlspecialchars((string) ($context['witness_date'] ?? ($_GET['witness_date'] ?? '')), ENT_QUOTES);

    $placeholders = [
        '{{employer_name}}'        => $employerName,
        '{{employer_address}}'     => $employerAddress,
        '{{employer_signatory}}'   => $hrSignatory,
        '{{hr_signatory}}'         => $hrSignatory,
        '{{employee_name}}'        => $fullName,
        '{{employee_no}}'          => $employeeNo,
        '{{employee_position}}'     => $position,
        '{{employee_department}}'   => $department,
        '{{employee_address}}'      => $address,
        '{{employee_phone}}'        => $phone,
        '{{employee_email}}'        => $email,
        '{{employee_birthdate}}'    => $birthdate,
        '{{employee_sex}}'          => $sex,
        '{{employee_marital_status}}' => $maritalStatus,
        '{{employee_nationality}}'  => $nationality,
        '{{execution_date}}'        => $today,
        '{{contract_execution_date}}' => $today,
        '{{contract_type}}'         => $contractType,
        '{{contract_start_date}}'   => $contractStart,
        '{{contract_end_date}}'     => $contractEnd,
        '{{contract_salary}}'       => $contractSalary,
        '{{original_salary}}'       => $originalSalary,
        '{{leave_type}}'         => $leaveType,
        '{{leave_start_date}}'   => $leaveStart,
        '{{leave_end_date}}'     => $leaveEnd,
        '{{leave_duration}}'     => $leaveDuration,
        '{{hr_signatory}}'          => $hrSignatory,
        '{{acknowledgement_date}}'  => $acknowledgementDate,
        '{{date_hired}}'            => $dateHired !== '______' ? $dateHired : '',
        '{{employment_status}}'     => $employmentStatus,
        '{{company_name}}'          => $employerName,
        '{{company_address}}'       => $employerAddress,
        '{{effective_date}}'        => $effectiveDate,
        '{{signature_date}}'        => $signatureDate,
        '{{authorized_representative}}' => $authorizedRepresentative,
        '{{authorized_position}}'   => $authorizedPosition,
        '{{employer_signature_date}}' => $employerSignatureDate,
        '{{witness_name}}'          => $witnessName !== '' ? $witnessName : '________________',
        '{{witness_position}}'      => $witnessPosition,
        '{{witness_date}}'          => $witnessDate,
        '{{employee.full_name}}'         => $fullName,
        '{{employee.employee_no}}'       => $employeeNo,
        '{{employee.position}}'          => $position,
        '{{employee.department}}'        => $department,
        '{{employee.date_hired}}'        => $dateHired !== '______' ? $dateHired : '',
        '{{employee.employment_status}}' => $employmentStatus,
        '{{contract.start_date}}'        => $contractStart,
        '{{company.address}}'            => $employerAddress,
        '{{employee.monthly_salary}}'    => $contractSalary,
        '{{employee.monthly_salary_words}}' => $salaryWords,
        '{{contract.governing_law}}'     => $governingLaw,
        '{{authorized_representative}}'  => $authorizedRep,
        '{{authorized_position}}'        => $authorizedPosition,
        '{{document.version}}'           => $templateVersion,
        '{{document.date}}'              => $today,
        '{{contract.contract_number}}'   => $contractNumber,
        '{{witness_name_2}}'             => $witnessName2,
    ];

    $rendered = str_replace(array_keys($placeholders), array_values($placeholders), $content);

    $rendered = preg_replace('/\*\*(.+?)\*\*/', '<strong><em>$1</em></strong>', $rendered);

    $rendered = preg_replace_callback(
        '/^(#{1,6})\s*(.+)$/m',
        function ($matches) {
            return '<strong>' . $matches[2] . '</strong>';
        },
        $rendered
    );

    $rendered = preg_replace('/\r\n/', "\n", $rendered);
    $rendered = preg_replace('/\n{3,}/', "\n\n", $rendered);
    $paragraphs = explode("\n\n", $rendered);

    $htmlParts = [];
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if ($para === '') continue;
        $para = str_replace("\n", '<br>', $para);
        $htmlParts[] = '<p style="margin:0 0 8px;">' . $para . '</p>';
    }

    return implode("\n", $htmlParts);
}

function dg_get_signature_image(int $height = 90): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = __DIR__ . '/../../../../modules/compliance/assets/';

    $src = '';
    if (file_exists($basePath . 'images.png')) {
        $src = $protocol . $host . '/hrms-capstone/modules/compliance/assets/images.png';
    } elseif (file_exists($basePath . 'signature.svg')) {
        $src = $protocol . $host . '/hrms-capstone/assets/img/signature.svg';
    } elseif (file_exists($basePath . 'signature.png')) {
        $src = $protocol . $host . '/hrms-capstone/assets/img/signature.png';
    }

    if ($src !== '') {
        return '<img src="' . $src . '" alt="Signature" style="height:' . $height . 'px; vertical-align:middle; display:inline-block;">';
    }

    return '<div style="display:inline-block; vertical-align:middle; text-align:center; font-family:Georgia, serif; font-style:italic; color:#1b2430; line-height:1.2;">Blythe Enriquez, HR Directress<div style="border-bottom:1px solid #1b2430; width:140px; margin-top:2px;"></div></div>';
}

if (!function_exists('lc_get_signature_image')) {
    function lc_get_signature_image(int $height = 70): string {
        return dg_get_signature_image($height);
    }
}

if (!function_exists('lc_apply_meta_overrides')) {
    function lc_apply_meta_overrides(array &$employee): void {
        dg_apply_meta_overrides($employee);
    }
}

if (!function_exists('lc_get_document_template')) {
    function lc_get_document_template(PDO $db, string $templateCode): ?array {
        return dg_get_document_template($db, $templateCode);
    }
}

if (!function_exists('lc_get_active_employer')) {
    function lc_get_active_employer(PDO $db): array {
        return dg_get_active_employer($db);
    }
}

if (!function_exists('lc_replace_placeholders')) {
    function lc_replace_placeholders(string $content, array $employee, array $employer, array $context = []): string {
        return dg_replace_placeholders($content, $employee, $employer, $context);
    }
}

if (!function_exists('lc_get_fallback_template')) {
    function lc_get_fallback_template(string $documentType): string {
        return dg_get_fallback_template($documentType);
    }
}

function dg_get_fallback_template(string $documentType): string {
    $employerName = 'Bestlink College of the Philippines';
    $templates = [
        'employment_contract' => $employerName . "\n\nEMPLOYMENT CONTRACT\n\nThis Employment Contract is entered into on {{execution_date}}, by and between:\n\nEMPLOYER\n\n" . $employerName . "\n\nEMPLOYEE\n\nName: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\n1. EMPLOYMENT TERM\n\nThe Employer hereby employs the Employee as a {{contract_type}} employee, commencing on {{contract_start_date}} and ending on {{contract_end_date}}, unless renewed or extended by mutual agreement of both parties.\n\n2. DUTIES AND RESPONSIBILITIES\nThe Employee shall perform the duties and responsibilities assigned by the Employer and shall comply with all company policies, rules, and regulations.\n\n3. COMPENSATION AND BENEFITS\nThe Employee shall receive a monthly salary of {{contract_salary}}, plus all benefits mandated by law, including but not limited to SSS, PhilHealth, Pag-IBIG, and 13th-month pay.\n\n4. GOVERNING LAW\n\nThis Contract shall be governed by and construed in accordance with the Philippine Labor Code (Presidential Decree No. 442) and other applicable labor laws and regulations.\n\nIN WITNESS WHEREOF, the parties have hereunto set their hands this {{execution_date}}.",

        'contract_renewal' => $employerName . "\n\nCONTRACT RENEWAL AGREEMENT\n\nThis Contract Renewal Agreement is entered into on {{execution_date}}, by and between:\n\nEMPLOYER\n\n" . $employerName . "\n\nEMPLOYEE\n\nName: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\n1. RENEWAL TERM\n\nThe Employer agrees to renew the Employee's contract for a period commencing on {{contract_start_date}} and ending on {{contract_end_date}}.\n\n2. TERMS AND CONDITIONS\n\nAll previous terms and conditions of the original Employment Contract shall remain in full force and effect, except as expressly amended herein.\n\nThe Employee shall continue to perform the duties and responsibilities assigned by the Employer.\n\n3. COMPENSATION\n\nDuring the renewal period, the Employee shall continue to receive the same compensation and benefits as specified in the original contract, unless otherwise agreed upon in writing by both parties.\n\n4. GOVERNING LAW\n\nThis Agreement shall be governed by the Philippine Labor Code and other applicable laws.\n\nIN WITNESS WHEREOF, the parties have executed this Contract Renewal Agreement on the date first written above.",

        'contract_extension' => $employerName . "\n\nCONTRACT EXTENSION AGREEMENT\n\nThis Contract Extension Agreement is entered into on {{execution_date}}, by and between:\n\nEMPLOYER\n\n" . $employerName . "\n\nEMPLOYEE\n\nName: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\n1. EXTENSION TERM\n\nThe Employer agrees to extend the Employee's contract for a period of six (6) months, from {{contract_start_date}} to {{contract_end_date}}.\n\n2. TERMS AND CONDITIONS\n\nAll previous terms and conditions of the original Employment Contract shall remain in full force and effect, except as expressly amended herein.\n\nThe Employee shall continue to perform the duties and responsibilities assigned by the Employer.\n\n3. COMPENSATION\n\nDuring the extension period, the Employee shall continue to receive the same compensation and benefits as specified in the original contract, unless otherwise agreed upon in writing by both parties.\n\n4. SIGNATORIES\n\nIN WITNESS WHEREOF, the parties have executed this Contract Extension Agreement on the date first written above.",

        'salary_rectification_agreement' => $employerName . "\n\nSALARY RECTIFICATION AGREEMENT\n(Amendment to Employment Contract)\n\nThis Salary Rectification Agreement is executed on {{execution_date}} to correct the salary amount stated in the Employment Contract dated {{contract_start_date}}, which contained an administrative and/or clerical error.\n\nEmployee Name: {{employee_name}}\nEmployee ID: {{employee_no}}\nDepartment: {{employee_department}}\nPosition: {{employee_position}}\nOriginal Employment Contract Date: {{contract_start_date}}\nDate of Rectification: {{execution_date}}\n\nI. PURPOSE\n\nThis Agreement is executed solely to reflect the intended salary approved during the hiring process and shall not affect any other terms and conditions of employment.\n\nII. SALARY CORRECTION\n\nThe salary provision in the original Employment Contract shall be amended as follows:\n\nSalary Stated in Original Contract: PHP {{original_salary}}\nCorrect Monthly Salary: {{contract_salary}}\nEffective Date: {{contract_end_date}}\n\nThe corrected salary shall replace the previously stated salary beginning on the effective date indicated above.\n\nIII. PAYROLL ADJUSTMENT\n\nThe Human Resources Department and Payroll Office are authorized to implement the corrected salary in all payroll records. If any payroll has already been processed using the incorrect salary amount, any necessary adjustment shall be made in accordance with company policy and applicable Philippine labor laws.\n\nIV. CONFIRMATION\nThe Employee confirms understanding and acceptance of the corrected salary stated in this Agreement. Except for the salary provision amended herein, all other terms, conditions, duties, benefits, and provisions of the original Employment Contract shall remain valid and in full force and effect.\n\nV. EFFECTIVITY\n\nThis Agreement shall take effect on {{contract_end_date}} and shall form an integral part of the original Employment Contract.\n\nIN WITNESS WHEREOF, the parties have...",

        'leave_agreement' => $employerName . "\n\nLEAVE AGREEMENT\n\nThis Leave Agreement is entered into on {{execution_date}}, by and between:\n\nEMPLOYER\n\n" . $employerName . "\n\nEMPLOYEE\n\nName: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\n1. LEAVE DETAILS\n\nThe Employee is granted approved leave from {{leave_start_date}} to {{leave_end_date}}.\n\n2. CONDITIONS\nThe Employee shall remain responsible for all assigned tasks and shall ensure proper handover before going on leave. The Employee shall report back to work on {{leave_end_date}} unless an extension is approved in writing by the Department Head.\n\n3. RETURN TO WORK\nThe Employee shall immediately notify the Human Resources Department upon return. Failure to report back without prior notice may be considered abandonment of position.\n\n4. GOVERNING LAW\n\nThis Agreement shall be governed by the Philippine Labor Code and company policies.\n\nIN WITNESS WHEREOF, the parties have executed this Leave Agreement on the date first written above.\n\nDepartment Head: _________________________\nDate: {{execution_date}}\n\nHuman Resource Director: _________________________\n{{hr_signatory}}\nDate: {{execution_date}}\n\nEmployee: _________________________\n{{employee_name}}\nDate: {{execution_date}}",

        'return_service' => $employerName . "\n\nRETURN TO WORK ACKNOWLEDGEMENT\n\nThis Return to Work Acknowledgement is issued on {{execution_date}} for:\n\nName: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\nThe Employee has completed the approved leave period and is hereby acknowledged to have returned to work effective {{execution_date}}.\n\nThe Employee shall coordinate with the immediate supervisor for any pending responsibilities and shall comply with any medical or operational requirements as applicable.\n\nCertified by:\n\nSupervisor: _________________________\nDate: {{execution_date}}\n\nHR: _________________________\n{{hr_signatory}}\nDate: {{execution_date}}",

        'nda' => "BESTLINK COLLEGE OF THE PHILIPPINES – BULACAN CAMPUS\n\nNON-DISCLOSURE AND CONFIDENTIALITY AGREEMENT\n\nConfidentiality, Data Protection and Information Security Agreement\n\nCONFIDENTIALITY NOTICE\n\nThis Agreement establishes the Employee's obligations concerning confidential, proprietary, sensitive, personal, and non-public information accessed or received during employment. The Employee is expected to protect such information from unauthorized access, use, disclosure, copying, removal, or distribution.\n\nThis Non-Disclosure and Confidentiality Agreement (\"Agreement\") is entered into by and between BESTLINK College of the Philippines – Bulacan Campus (\"Company\" or \"Employer\") and {{employee_name}} (\"Employee\"), effective as of {{effective_date}}.\n\nPURPOSE\n\nThe Employee acknowledges that, during employment, they may have access to confidential, proprietary, sensitive, and non-public information belonging to the Employer, its employees, students, applicants, clients, suppliers, partners, and other stakeholders. This Agreement establishes the Employee's responsibilities in protecting such information from unauthorized access, use, disclosure, copying, or distribution.\n\nDEFINITION OF CONFIDENTIAL INFORMATION\n\n\"Confidential Information\" includes, but is not limited to:\n\n• Employee personnel records, compensation, employment records, and disciplinary records.\n\n• Student records, academic records, contact information, and other personally identifiable information.\n\n• Applicant and recruitment information.\n\n• Business plans, strategies, financial information, budgets, and reports.\n\n• Payroll, benefits, compensation, and government contribution information.\n\n• Employment contracts, policies, procedures, forms, manuals, and internal documents.\n\n• Legal documents, complaints, investigations, case records, and compliance information.\n\n• Passwords, system credentials, access codes, databases, and system configurations.\n\n• Information in the Employer's HR, payroll, accounting, legal, compliance, and administrative systems.\n\n• Trade secrets, proprietary processes, technical information, and internal methodologies.\n\n• Information received from third parties under an obligation of confidentiality.\n\nEMPLOYEE OBLIGATIONS\n\nThe Employee agrees to:\n\n• Keep all Confidential Information strictly confidential and use it only for legitimate employment-related purposes.\n\n• Access confidential records only when authorized and necessary to perform assigned duties.\n\n• Not disclose Confidential Information to unauthorized persons, whether inside or outside the organization.\n\n• Not copy, reproduce, photograph, download, transmit, publish, or distribute confidential documents without authorization.\n\n• Protect passwords, credentials, access cards, files, devices, and other means of accessing Confidential Information.\n\n• Immediately report any suspected loss, unauthorized access, disclosure, or security incident.\n\n• Follow all applicable company policies relating to information security, privacy, records management, and confidentiality.\n\nPERMITTED DISCLOSURE\n\nThe Employee may disclose Confidential Information only when:\n\n• The disclosure is specifically authorized in writing by the Employer.\n\n• The disclosure is necessary to perform legitimate employment duties and the recipient is authorized.\n\n• Disclosure is required by law, regulation, court order, or lawful government authority.\n\nWhen legally required, the Employee shall notify the Employer before making a disclosure so that the Employer may take protective measures.\n\nPERSONAL DATA AND PRIVACY\n\nThe Employee acknowledges that Confidential Information may contain personal and sensitive personal information. The Employee shall handle such information in accordance with applicable data privacy laws, regulations, and the Employer's data privacy and security policies. The Employee shall not access, process, disclose, or retain personal information beyond what is necessary and authorized for the performance of their duties.\n\nELECTRONIC AND DIGITAL INFORMATION\n\nThe Employee shall exercise reasonable care when handling electronic Confidential Information, including protecting company accounts, avoiding unauthorized storage or transfer of files, and not using personal devices, personal email accounts, cloud storage, messaging applications, or external storage media for Confidential Information unless expressly authorized.\n\nRETURN OF COMPANY INFORMATION\n\nUpon termination, resignation, expiration of employment, or whenever requested by the Employer, the Employee shall promptly return or surrender all company property and Confidential Information in their possession or control, including physical documents, electronic files, storage devices, access cards, company-issued devices, credentials, copies, notes, and other materials. Where applicable, the Employee shall delete Confidential Information stored on authorized personal devices or accounts upon the Employer's instruction, subject to applicable legal requirements.\n\nEXCLUSIONS FROM CONFIDENTIAL INFORMATION\n\nConfidential Information does not include information that the Employee can demonstrate was publicly available at the time of disclosure, becomes publicly available through no unauthorized act of the Employee, was lawfully known to the Employee before disclosure, was independently developed without using Confidential Information, or was lawfully obtained from a third party without a confidentiality obligation.\n\nNO UNAUTHORIZED USE\n\nThe Employee shall not use Confidential Information for personal benefit, financial gain, outside employment, business activities, or the benefit of another person or organization. The Employee shall not use confidential information to compete with, disadvantage, or otherwise cause harm to the Employer.\n\nINTELLECTUAL PROPERTY\n\nConfidential Information may include documents, materials, systems, procedures, databases, reports, templates, processes, and other work products developed or maintained as part of the Employee's employment. Nothing in this Agreement authorizes the Employee to reproduce, distribute, commercialize, or otherwise use such materials outside the scope of their employment without proper authorization.\n\nCONTINUING OBLIGATION\n\nThe Employee's confidentiality obligations shall continue after termination, resignation, or expiration of employment for as long as the information remains confidential or protected by applicable law or company policy.\n\nBREACH OF AGREEMENT\n\nUnauthorized access, use, copying, disclosure, or distribution of Confidential Information may constitute a violation of company policy and may result in appropriate administrative or disciplinary action. The Employer may also pursue appropriate legal remedies where permitted by law.\n\nNO WAIVER\n\nFailure by the Employer to immediately enforce any provision of this Agreement shall not constitute a waiver of its right to enforce that provision or any other provision in the future.\n\nCOMPLIANCE WITH COMPANY POLICIES\n\nThe Employee agrees to comply with all applicable company policies, employee handbooks, information security procedures, data privacy policies, records management procedures, and other rules relating to the protection of confidential information.\n\nACKNOWLEDGMENT\n\nBy signing this Agreement, the Employee confirms that they have read and understood this Agreement, have had the opportunity to ask questions, understand the confidential nature of information accessed during employment, agree to comply with the confidentiality obligations, and understand that these obligations may continue after the end of employment.\n\nGOVERNING LAW\n\nThis Agreement shall be interpreted and enforced in accordance with the applicable laws of the Republic of the Philippines.\n\nENTIRE AGREEMENT\n\nThis Agreement constitutes the understanding between the Employer and Employee regarding confidentiality and supplements applicable employment agreements, company policies, and other lawful obligations concerning the protection of confidential information. If any provision of this Agreement is determined to be invalid or unenforceable, the remaining provisions shall remain effective to the extent permitted by law.\n\nSIGNATURES\n\nBy signing below, both parties acknowledge that they have read, understood, and voluntarily agreed to the terms and conditions of this Non-Disclosure and Confidentiality Agreement.\n\nEMPLOYEE\n\nName: {{employee_name}}\nEmployee No.: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\nEmployee Signature: ______________________________\nDate: {{signature_date}}\n\nFOR THE EMPLOYER\n\nAuthorized Representative: {{authorized_representative}}\nPosition: {{authorized_position}}\nAuthorized Representative Signature: ______________________________\nDate: {{employer_signature_date}}\n\nWITNESS\n\nName: {{witness_name}}\nPosition: {{witness_position}}\nWitness Signature: ______________________________\nDate: {{witness_date}}\n\nELECTRONIC ACKNOWLEDGEMENT\n\nBy selecting the acknowledgement option in the employee portal, I confirm that I have read and understood this Non-Disclosure and Confidentiality Agreement and agree to comply with the confidentiality obligations, information security requirements, and applicable company policies stated herein.\n\nTHESIS PURPOSE DISCLAIMER\n\nDISCLAIMER: This document is prepared and presented for thesis and academic purposes only. It is a conceptual/sample document developed for educational and research purposes and is not intended to represent an official, approved, or legally binding Non-Disclosure and Confidentiality Agreement of BESTLINK College of the Philippines – Bulacan Campus. This document should not be used as an official institutional policy, employment agreement, or legal instrument without review, approval, and authorization by the appropriate institution and qualified legal professionals.\n\nBESTLINK College of the Philippines – Bulacan Campus\nNon-Disclosure and Confidentiality Agreement",

        'training_bond' => $employerName . "\n\nTRAINING BOND AGREEMENT\n\nThis Training Bond Agreement is entered into on {{execution_date}}, by and between:\n\nEMPLOYER\n\n" . $employerName . "\n\nEMPLOYEE\n\nName: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\n1. TRAINING UNDERTAKEN\nThe Employer has provided the Employee with the following training: ___________________________________________, at a cost of PHP _________________.\n\n2. SERVICE BOND\nThe Employee agrees to remain in the service of the Employer for a minimum period of one (1) year from the date of completion of training. In the event the Employee voluntarily resigns or is terminated for cause within this period, the Employee shall reimburse the Employer on a pro-rated basis for the cost of training.\n\n3. WAIVER\nThe Employer may waive the service bond at its sole discretion.\n\nIN WITNESS WHEREOF, the parties have executed this Training Bond Agreement on the date first written above.\n\n{{hr_signatory}}

Employee: _________________________
{{employee_name}}
Date: {{execution_date}}",

        'final_pay' => $employerName . "\n\nFINAL PAY COMPUTATION\n\nEmployee Name: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\nLast Working Day: {{contract_start_date}}\n\nPARTICULARS\n\nProrated Salary: _________________________\nLeave Conversion: _________________________\n13th Month Pay (Pro-rata): _________________________\nTotal Earnings: _________________________\nLess Deductions: _________________________\nNet Pay: _________________________\n\nPrepared by: _________________________\n{{hr_signatory}}\nDate: {{execution_date}}\n\nCertified correct by:\n\nFinance Manager: _________________________\nDate: {{execution_date}}",

        'exit_clearance' => $employerName . "\n\nEXIT CLEARANCE CHECKLIST\n\nEmployee Name: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\nDate of Separation: {{contract_start_date}}\n\nThis checklist is issued to ensure that all obligations are settled and company properties are returned before the employee's final clearance is approved.\n\nSECTION I — DEPARTMENT CLEARANCE\n\nImmediate Supervisor:\n[ ] All pending tasks delegated or transferred.\n[ ] Project files and documents returned.\nApproved by: _________________________ Date: ___________\n\nDepartment Head:\n[ ] No pending department-related obligations.\n[ ] Access to department systems revoked.\nApproved by: _________________________ Date: ___________\n\nSECTION II — ADMINISTRATIVE CLEARANCE\n\nHuman Resources:\n[ ] Clearance for resignation/termination received.\n[ ] Employee personal data updated.\n[ ] Final pay computation prepared.\nApproved by: _________________________ Date: ___________\n\nFinance Department:\n[ ] All cash advances, if any, liquidated.\n[ ] No pending financial obligations.\nApproved by: _________________________ Date: ___________\n\nIT Department:\n[ ] Laptop, desktop, and peripherals returned.\n[ ] System access revoked.\n[ ] Company email and accounts deactivated.\nApproved by: _________________________ Date: ___________\n\nProperty Custodian:\n[ ] ID badge returned.\n[ ] Keys, access cards, and other company properties returned.\n[ ] Uniforms and equipment returned.\nApproved by: _________________________ Date: ___________\n\nSECTION III — LEGAL AND COMPLIANCE\n\nLegal Officer:\n[ ] No pending legal obligations.\n[ ] Non-disclosure agreements on file.\nApproved by: _________________________ Date: ___________\n\nCERTIFICATION\n\nI hereby certify that the above employee has cleared all obligations with the department and is cleared from all liabilities.\n\n_________________________________________\n{{hr_signatory}}\nDate: {{execution_date}}",

        'study_leave' => $employerName . "\n\nSTUDY LEAVE AGREEMENT\n\nThis Study Leave Agreement is entered into on {{execution_date}}, by and between:\n\nEMPLOYER\n\n" . $employerName . "\n\nEMPLOYEE\n\nName: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\n1. STUDY LEAVE PERIOD\nThe Employer grants the Employee study leave from {{contract_start_date}} to {{contract_end_date}} for the purpose of pursuing _________________________.\n\n2. ACADEMIC STANDING\nThe Employee shall maintain a passing grade or satisfactory academic standing throughout the study leave period.\n\n3. PROGRESS REPORT\nThe Employee shall submit quarterly progress reports to the Department Head and Human Resources.\n\n4. RETURN TO SERVICE\nThe Employee agrees to return to the Employer and continue employment for a minimum period of one (1) year upon completion of the study leave. Failure to return shall require reimbursement of training or study costs on a pro-rated basis.\n\nIN WITNESS WHEREOF, the parties have executed this Study Leave Agreement on the date first written above.\n\nDepartment Head: _________________________\nDate: {{execution_date}}\n\nHuman Resource Director: _________________________\n{{hr_signatory}}\nDate: {{execution_date}}\n\nEmployee: _________________________\n{{employee_name}}\nDate: {{execution_date}}",

        'non_compete' => $employerName . "\n\nNON-COMPETE AND NON-SOLICITATION AGREEMENT\n\nThis Non-Compete and Non-Solicitation Agreement is entered into on {{execution_date}}, by and between:\n\nEMPLOYER\n\n" . $employerName . "\n\nEMPLOYEE\n\nName: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\n1. NON-COMPETE\nThe Employee agrees that for a period of one (1) year following the termination of employment, whether voluntary or involuntary, the Employee shall not engage in any business or activity that competes directly with the Employer within the Philippines.\n\n2. NON-SOLICITATION\nThe Employee shall not, during the term of employment and for one (1) year thereafter, solicit or induce any employee, client, or customer of the Employer to terminate their relationship with the Employer.\n\n3. CONFIDENTIALITY\nThe Employee reaffirms the obligation to maintain the confidentiality of all proprietary information even after the termination of employment.\n\n4. REMEDIES\nThe parties agree that any breach of this Agreement shall entitle the Employer to seek injunctive relief and damages in addition to any other remedies available at law.\n\nIN WITNESS WHEREOF, the parties have executed this Agreement on the date first written above.\n\n{{hr_signatory}}

Employee: _________________________
{{employee_name}}
Date: {{execution_date}}",

        'nte' => $employerName . "\n\nNOTICE TO EXPLAIN (NTE)\n\nDate: {{execution_date}}\n\nTo: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\nSubject: Notice to Explain — Administrative Incident\n\nDear {{employee_name}},\n\nThis Notice to Explain (NTE) is being issued to you in connection with the following incident:\n\nViolation: ___________________________________________\nDate of Incident: {{contract_start_date}}\nLocation: ___________________________________________\n\nYou are hereby required to submit a written explanation to the Human Resources Department within five (5) calendar days from receipt of this notice. Your explanation should include any evidence or witnesses that may support your side of the story.\n\nFailure to submit your explanation within the prescribed period shall be interpreted as an admission of the charge and the Employer shall take the appropriate administrative action based on its findings.\n\nPlease be advised that this notice is part of the due process required under the Philippine Labor Code and company policies.\n\nFor your information and compliance.\n\n_________________________________________\n{{hr_signatory}}\n" . $employerName . "\n\nReceived by:\n\nEmployee: _________________________\n{{employee_name}}\nDate: {{execution_date}}",

        'written_warning' => $employerName . "\n\nWRITTEN WARNING\n\nDate: {{execution_date}}\n\nTo: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\nSubject: Written Warning — Policy Violation\n\nDear {{employee_name}},\n\nThis is your first/second written warning. Further violation of company policies may result in a more severe administrative penalty, up to and including termination of employment.\n\nWe expect you to immediately rectify this behavior and comply with all institutional policies moving forward.\n\n_________________________________________\n{{hr_signatory}}\n" . $employerName . "\n\nAcknowledged by:\n\nEmployee: _________________________\n{{employee_name}}\nDate: {{execution_date}}",

        'suspension_notice' => $employerName . "\n\nNOTICE OF SUSPENSION\n\nDate: {{execution_date}}\n\nTo: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\nSubject: Notice of Suspension Pending Investigation\n\nDear {{employee_name}},\n\nYou are hereby notified that you are suspended from work effective {{contract_start_date}} to {{contract_end_date}}, pending the investigation of the following administrative charge:\n\nDuring the period of suspension, you are required to:\n\n1. Refrain from entering company premises without prior written authorization.\n2. Be available at all times for any inquiry or hearing that may be scheduled.\n3. Submit any documents or evidence relevant to the investigation.\n\nThis suspension is preventive in nature and does not constitute a finding of guilt. You will be notified of the investigation results and the appropriate action to be taken.\n\nPlease be advised that during your suspension, you shall continue to receive your basic salary in accordance with applicable laws and company policies.\n\n_________________________________________\n{{hr_signatory}}\n" . $employerName . "\n\nReceived by:\n\nEmployee: _________________________\n{{employee_name}}\nDate: {{execution_date}}",

        'notice_of_decision' => $employerName . "\n\nNOTICE OF DECISION\n\nDate: {{execution_date}}\n\nTo: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\nSubject: Decision on Administrative Case\n\nDear {{employee_name}},\n\nAfter conducting a thorough investigation and giving you the opportunity to be heard, this Office has reached the following decision regarding the administrative case filed against you:\n\nCharge: ___________________________________________\nInvestigation Period: {{contract_start_date}} to {{contract_end_date}}\n\nDECISION:\n\n___________________________________________\n___________________________________________\n___________________________________________\n\nThis decision is final and effective immediately. You are advised to comply with the terms set forth herein.\n\nIf you believe this decision is erroneous, you may file an appeal with the Office of the President within five (5) calendar days from receipt of this notice.\n\n_________________________________________\n{{hr_signatory}}\n" . $employerName . "\n\nReceived by:\n\nEmployee: _________________________\n{{employee_name}}\nDate: {{execution_date}}",

        'termination_decision' => $employerName . "\n\nTERMINATION DECISION\n\nDate: {{execution_date}}\n\nTo: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\n\nSubject: Notice of Termination Decision\n\nDear {{employee_name}},\n\nAfter due investigation and after giving you the opportunity to be heard, this Office has decided to terminate your employment effective {{contract_start_date}}.\n\nEffectivity Date: {{contract_end_date}}\n\nAppeal Rights:\nYou may file an appeal with the Office of the President within five (5) calendar days from receipt of this notice. The appeal must be in writing and submitted to the Human Resources Department.\n\nPending the resolution of any appealed matter, you are hereby directed to:\n1. Refrain from entering company premises without prior written authorization.\n2. Return all company property in your possession.\n3. Complete all exit requirements as prescribed by the Human Resources Department.\n\nThis decision is final and effective immediately, unless stayed by the proper authority.\n\n" . $employerName . "\n\nReceived by:\n\nEmployee: _________________________\n{{employee_name}}\nDate: {{execution_date}}",


        'coe' => $employerName . "\n\nCERTIFICATE OF EMPLOYMENT\n\nTO WHOM IT MAY CONCERN:\n\nThis is to certify that {{employee_name}}, Employee ID {{employee_no}}, is presently employed with " . $employerName . " as a {{employee_position}} under the {{employee_department}} Department.\n\nThe employee was hired on {{contract_start_date}} and is currently holding the position on a {{contract_type}} employment status.\n\nThis certification is being issued upon the request of the employee for whatever legal purpose it may serve.\n\nIssued this {{execution_date}}.\n\n_________________________________________\n{{hr_signatory}}\n" . $employerName,

        'quitclaim' => $employerName . "\n\nQUITCLAIM AND RELEASE\n\nKNOW ALL MEN BY THESE PRESENTS:\n\nI, {{employee_name}}, of legal age, Filipino, and a resident of the Philippines, for and in consideration of the sum of PHP _________________ (the \"Settlement Amount\"), the receipt of which is hereby acknowledged, do hereby forever release, acquit, and forever discharge " . $employerName . ", its officers, directors, agents, and em_employees, from any and all claims, demands, actions, and causes of action arising out of or in any way connected with my employment and separation therefrom.\n\n1. RELEASE OF CLAIMS\n\nI acknowledge that this Release covers all claims, whether known or unknown, that I may have against the Employer up to the date of this instrument, including but not limited to claims for salaries, benefits, incentives, separation pay, and damages.\n\n2. NO ADMISSION OF LIABILITY\n\nThis Release is made solely for the purpose of amicably settling any and all disputes between the parties. The Employer denies any liability whatsoever, and this Release shall not be construed as an admission of liability by the Employer.\n\n3. VOLUNTARY EXECUTION\n\nI acknowledge that I have read this Release, that I fully understand its contents, and that I am executing the same voluntarily and without any coercion or undue influence.\n\n4. TAX LIABILITY\n\nI shall be solely responsible for any and all taxes arising from the receipt of the Settlement Amount.\n\nIN WITNESS WHEREOF, I have hereunto set my hand this {{execution_date}}.\n\n_________________________________________\nEmployee: {{employee_name}}\nEmployee ID: {{employee_no}}\n\nSigned in the presence of:\n\nWitness: _________________________\n\nWitness: _________________________",

        'exit_acknowledgement' => $employerName . "\n\nEXIT ACKNOWLEDGEMENT\n\nEmployee Name: {{employee_name}}\nEmployee ID: {{employee_no}}\nPosition: {{employee_position}}\nDepartment: {{employee_department}}\nDate of Exit: {{contract_start_date}}\n\nThis Exit Acknowledgement is executed upon the separation of the above-named employee from the Institution. The Employee hereby acknowledges the following:\n\n1. FINAL PAY\nThe Employee acknowledges receipt of the final pay in the amount of PHP _________________, covering salaries, benefits, and other entitlements up to the date of separation.\n\n2. RETURN OF PROPERTY\nThe Employee certifies that all company properties, including but not limited to ID badges, keys, access cards, laptops, documents, and equipment, have been returned to the concerned em_departments.\n\n3. NON-DISCLOSURE\nThe Employee reaffirms the obligation to maintain the confidentiality of all proprietary information, records, and data belonging to the Employer, even after the termination of employment.\n\n4. NO FURTHER CLAIMS\nThe Employee confirms that no further claims shall be made against the Employer for any matter arising from the employment relationship or the separation therefrom.\n\nIN WITNESS WHEREOF, the parties have hereunto signed this Exit Acknowledgement on {{execution_date}}.\n\n_________________________________________\nEmployee: {{employee_name}}\n\n_________________________________________\n{{hr_signatory}}\nDate: {{execution_date}}",

        'employee_handbook' => "# EMPLOYEE HANDBOOK\n\n**BESTLINK COLLEGE OF THE PHILIPPINES**\n\nEmployee Policies, Workplace Standards and Guidelines\n\n## 1. WELCOME\n\nWelcome to the organization. This Employee Handbook provides the policies, expectations, and workplace standards that guide employees in performing their duties and working with one another.\n\nEvery employee is expected to read, understand, and follow the policies contained in this handbook, as well as any lawful instructions, procedures, and workplace rules communicated by management.\n\n## 2. EQUAL OPPORTUNITY AND RESPECTFUL WORKPLACE\n\nThe organization maintains a professional, respectful, and inclusive workplace.\n\nEmployees must treat colleagues, clients, customers, supervisors, and business partners with professionalism, courtesy, and respect.\n\nHarassment, discrimination, bullying, intimidation, threats, or other inappropriate workplace behavior will not be tolerated.\n\n## 3. EMPLOYEE RESPONSIBILITIES\n\nEmployees are expected to:\n\n• Perform assigned duties responsibly and professionally.\n• Follow reasonable and lawful instructions from authorized supervisors.\n• Observe company policies and workplace procedures.\n• Maintain honesty and integrity in all work-related activities.\n• Protect company property, information, and resources.\n• Maintain appropriate professional conduct.\n• Cooperate with colleagues and supervisors.\n• Report workplace concerns, safety issues, or policy violations promptly.\n\n## 4. ATTENDANCE AND PUNCTUALITY\n\nEmployees must report to work on time and maintain reliable attendance. Employees who are unable to report to work or expect to be late should notify their immediate supervisor as soon as reasonably possible.\n\nRepeated absences, tardiness, or failure to follow attendance procedures may result in corrective action.\n\n## 5. WORKING HOURS AND BREAKS\n\nEmployees must observe their assigned working schedule and comply with applicable break and meal-period procedures. Schedule changes must be properly authorized by the appropriate supervisor.\n\n## 6. WORKPLACE CONDUCT\n\nEmployees must maintain professional behavior while at work and when representing the organization.\n\nThe following may result in disciplinary action:\n\n• Dishonesty or falsification of records.\n• Theft or unauthorized use of company property.\n• Threatening, abusive, or violent behavior.\n• Harassment or discrimination.\n• Serious insubordination.\n• Unauthorized disclosure of confidential information.\n• Deliberate damage to company property.\n• Reporting to work under the influence of prohibited substances.\n• Other conduct violating company policies or applicable rules.\n\n## 7. CONFIDENTIALITY AND DATA PROTECTION\n\nEmployees may access confidential company, employee, customer, or business information. Confidential information must only be accessed, used, or disclosed for legitimate work-related purposes and in accordance with company policies.\n\nEmployees must protect passwords, accounts, documents, records, and other entrusted information. Unauthorized disclosure, copying, removal, or use of confidential information may result in disciplinary action.\n\n## 8. COMPANY PROPERTY AND RESOURCES\n\nCompany equipment, systems, documents, accounts, facilities, and other resources must be used responsibly for legitimate business purposes. Employees must take reasonable care of company property and promptly report loss, damage, or unauthorized access.\n\n## 9. INFORMATION TECHNOLOGY AND ELECTRONIC COMMUNICATIONS\n\nEmployees must use company-provided technology responsibly and must not attempt unauthorized access, share passwords, install unauthorized software, use systems for unlawful activities, introduce malicious software, or access prohibited content.\n\n## 10. HEALTH, SAFETY, AND SECURITY\n\nThe organization maintains a safe and secure workplace. Employees must follow applicable safety procedures and promptly report hazards, accidents, injuries, security concerns, or unsafe conditions.\n\n## 11. CONFLICT OF INTEREST\n\nEmployees must avoid situations where personal interests conflict with the interests of the organization. Employees must disclose potential conflicts to their supervisor and must not use their position or company resources for unauthorized personal gain.\n\n## 12. PROFESSIONAL APPEARANCE\n\nEmployees must maintain an appearance appropriate to their position and interactions with clients or business partners and must follow applicable dress code and identification requirements.\n\n## 13. SOCIAL MEDIA AND PUBLIC COMMUNICATIONS\n\nEmployees must exercise good judgment when using social media or communicating publicly about matters related to the organization. Employees must not disclose confidential information, represent personal opinions as official company statements, or use company information in an unauthorized manner.\n\n## 14. LEAVE AND TIME-OFF\n\nEmployees may request leave in accordance with applicable law and the organization's established procedures. Requests should be submitted through the appropriate channel with reasonable advance notice.\n\n## 15. COMPENSATION AND BENEFITS\n\nCompensation, benefits, allowances, incentives, and other employment-related programs are governed by applicable employment agreements, company policies, and applicable law. Employees should direct questions to the appropriate Human Resources representative.\n\n## 16. PERFORMANCE AND DEVELOPMENT\n\nEmployees are expected to perform their responsibilities according to the standards and expectations of their position. The organization may conduct performance reviews, provide feedback, establish development goals, and implement training programs. Employees are encouraged to communicate with their supervisors regarding work expectations and development opportunities.\n\n## 17. DISCIPLINE AND CORRECTIVE ACTION\n\nViolations of company policies, workplace rules, or reasonable and lawful instructions may result in corrective or disciplinary action, including coaching, written warnings, suspension, or termination, subject to applicable company procedures and law.\n\n## 18. REPORTING CONCERNS\n\nEmployees are encouraged to raise workplace concerns through their immediate supervisor, Human Resources, management, or another authorized reporting channel. Reports should be made honestly and in good faith.\n\n## 19. POLICY CHANGES\n\nThe organization may revise, update, or replace policies and procedures when necessary. Employees are responsible for reviewing official policy updates and complying with applicable policies.\n\n## 20. EMPLOYEE ACKNOWLEDGEMENT\n\nI acknowledge that I have received access to the Employee Handbook and have had the opportunity to read and understand its contents. I understand that I am responsible for following the organization's policies, procedures, workplace standards, and lawful instructions. I understand that this handbook provides general workplace guidance and does not replace applicable law, my employment agreement, or specific policies and procedures issued by the organization.\n\n**Employee Name:** {{employee_name}}  **Employee ID:** {{employee_no}}  **Date Acknowledged:** {{acknowledgement_date}}\n\nEmployee Signature: ______________________________________  Date: _________________________________________________\n\nBy selecting the acknowledgement option in the employee portal, I confirm that I have read and understood this Employee Handbook and agree to comply with its policies, procedures, and workplace standards.\n\n---\n\n**FOR THESIS PURPOSES ONLY** — This Employee Handbook is a sample document for academic and thesis purposes. It is intended to demonstrate the design and functionality of a Human Resources Management System (HRMS) and is **not an official policy, employment agreement, or authorized publication of Bestlink College of the Philippines**.\n\nThe contents are provided for academic demonstration and system development purposes only and should not be used as an official institutional document without appropriate review, validation, and authorization by the concerned institution and its authorized representatives.\n\n**BESTLINK COLLEGE OF THE PHILIPPINES** — Employee Handbook — **FOR THESIS PURPOSES ONLY**"
    ];

    return $templates[$documentType] ?? '';
}

function dg_split_full_name(string $fullName): array {
    $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
    $count = count($parts);

    if ($count === 0) {
        return ['first_name' => '', 'middle_name' => null, 'last_name' => ''];
    }

    if ($count === 1) {
        return ['first_name' => $parts[0], 'middle_name' => null, 'last_name' => ''];
    }

    if ($count === 2) {
        return ['first_name' => $parts[0], 'middle_name' => null, 'last_name' => $parts[1]];
    }

    return [
        'first_name' => $parts[0],
        'middle_name' => implode(' ', array_slice($parts, 1, -1)),
        'last_name' => $parts[$count - 1],
    ];
}

function dg_map_employment_status(string $status): string {
    $map = [
        'Regular' => 'Regular',
        'Probationary' => 'Probationary',
        'Contractual' => 'Contractual',
        'Part-Time' => 'Contractual',
        'Resigned' => 'Resigned',
        'Terminated' => 'Terminated',
        'Pending Onboarding' => 'Probationary',
        'Fixed-Term' => 'Contractual',
        'Project' => 'Contractual',
        'Seasonal' => 'Contractual',
        'Casual' => 'Contractual',
    ];

    return $map[$status] ?? 'Probationary';
}

function dg_ensure_employee_in_employees(PDO $db, int $employeeId, array $newHireData): int {
    $stmt = $db->prepare("SELECT employee_id FROM em_employees WHERE employee_id = :id LIMIT 1");
    $stmt->execute([':id' => $employeeId]);
    if ($stmt->fetchColumn()) {
        return $employeeId;
    }

    $nameParts = dg_split_full_name($newHireData['full_name'] ?? '');
    $mappedStatus = dg_map_employment_status($newHireData['employment_status'] ?? 'Regular');

    $insert = $db->prepare("
        INSERT INTO em_employees
            (employee_id, first_name, middle_name, last_name, gender, birth_date, civil_status,
             email, mobile_no, current_address, permanent_address,
             department_id, position_id, hire_date, employment_status,
             credentials, citizenship, created_at, updated_at)
        VALUES
            (:employee_id, :first_name, :middle_name, :last_name, :gender, :birth_date, :civil_status,
             :email, :mobile_no, :current_address, :permanent_address,
             :department_id, :position_id, :hire_date, :employment_status,
             :credentials, :citizenship, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            employee_id = employee_id
    ");
    $insert->execute([
        ':employee_id' => $employeeId,
        ':first_name' => $nameParts['first_name'],
        ':middle_name' => $nameParts['middle_name'] ?? '',
        ':last_name' => $nameParts['last_name'],
        ':gender' => $newHireData['sex'] ?? 'Male',
        ':birth_date' => $newHireData['birthdate'] ?? null,
        ':civil_status' => $newHireData['marital_status'] ?? 'Single',
        ':email' => $newHireData['email'] ?: 'employee' . $employeeId . '@hrms.local',
        ':mobile_no' => $newHireData['phone_number'] ?? null,
        ':current_address' => $newHireData['address'] ?? null,
        ':permanent_address' => $newHireData['address'] ?? null,
        ':department_id' => $newHireData['department_id'] ?? null,
        ':position_id' => $newHireData['position_id'] ?? null,
        ':hire_date' => $newHireData['date_hired'] ?? null,
        ':employment_status' => $mappedStatus,
        ':credentials' => $newHireData['teacher_qualification'] ?? null,
        ':citizenship' => $newHireData['nationality'] ?? 'Filipino',
    ]);

    return $employeeId;
}

function dg_has_employee_contract(PDO $db, int $employeeId): bool {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM lc_contracts
            WHERE employee_id = :id
              AND status IN ('Draft', 'Active', 'For Renewal')
        ");
        $stmt->execute([':id' => $employeeId]);
        return ((int) $stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function lc_has_employee_contract(PDO $db, int $employeeId): bool {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM lc_contracts
            WHERE employee_id = :id
              AND status IN ('Draft', 'Active', 'For Renewal')
        ");
        $stmt->execute([':id' => $employeeId]);
        return ((int) $stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function dg_generate_contract_number(PDO $db): string {
    $year = date('Y');
    try {
        $nextId = (int) $db->query("SELECT MAX(contract_id) FROM lc_contracts")->fetchColumn();
    } catch (Throwable $e) {
        $nextId = 0;
    }
    $nextId = $nextId + 1;
    return 'CTR-' . $year . '-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);
}

function dg_get_document_type_by_code(PDO $db, string $code): ?array {
    try {
        $stmt = $db->prepare("
            SELECT * FROM lc_document_types
            WHERE document_type_code = :code AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function dg_get_active_template(PDO $db, string $templateCode): ?array {
    try {
        $stmt = $db->prepare("
            SELECT template_id, template_code, template_name, version, status,
                   template_content, governing_law, jurisdiction, effective_date, is_default,
                   document_action, requirement_mode, applicability_rules, template_selection_rules
            FROM lc_document_templates
            WHERE template_code = :code AND status = 'Active'
            ORDER BY is_default DESC, effective_date DESC, template_id DESC
            LIMIT 1
        ");
        $stmt->execute([':code' => $templateCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function dg_get_template_versions(PDO $db, string $templateCode): array {
    try {
        $stmt = $db->prepare("
            SELECT template_id, template_name, version, status, effective_date, retired_date,
                   is_default, created_at, updated_at, document_action, requirement_mode,
                   applicability_rules, template_selection_rules
            FROM lc_document_templates
            WHERE template_code = :code
            ORDER BY effective_date DESC, template_id DESC
        ");
        $stmt->execute([':code' => $templateCode]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function dg_get_handbook_acknowledgement(PDO $db, int $employeeId, int $templateId): ?array {
    try {
        $stmt = $db->prepare("
            SELECT * FROM lc_handbook_acknowledgements
            WHERE employee_id = :eid AND template_id = :tid
            LIMIT 1
        ");
        $stmt->execute([':eid' => $employeeId, ':tid' => $templateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function dg_upsert_handbook_acknowledgement(PDO $db, int $employeeId, int $templateId, string $version, string $status, ?int $accountId = null): bool {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $now = date('Y-m-d H:i:s');

        $stmt = $db->prepare("
            INSERT INTO lc_handbook_acknowledgements
                (employee_id, template_id, template_code, version, status, viewed_at, acknowledged_at, ip_address, user_agent, account_id, updated_at)
            VALUES
                (:eid, :tid, 'employee_handbook', :version, :status, :viewed_at, :ack_at, :ip, :ua, :aid, :now)
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                viewed_at = COALESCE(viewed_at, VALUES(viewed_at)),
                acknowledged_at = CASE WHEN VALUES(status) = 'Acknowledged' THEN VALUES(acknowledged_at) ELSE acknowledged_at END,
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                account_id = VALUES(account_id),
                updated_at = VALUES(updated_at)
        ");
        return $stmt->execute([
            ':eid' => $employeeId,
            ':tid' => $templateId,
            ':version' => $version,
            ':status' => $status,
            ':viewed_at' => $now,
            ':ack_at' => $status === 'Acknowledged' ? $now : null,
            ':ip' => $ipAddress,
            ':ua' => $userAgent,
            ':aid' => $accountId,
            ':now' => $now,
        ]);
    } catch (Throwable $e) {
        return false;
    }
}

function dg_get_required_document_types(PDO $db): array {
    try {
        $stmt = $db->prepare("
            SELECT document_type_code, document_type_name, behavior, source_table, employee_specific, template_required
            FROM lc_document_types
            WHERE is_active = 1 AND is_required = 1
            ORDER BY sort_order, document_type_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

if (!function_exists('lc_get_document_type_by_code')) {
    function lc_get_document_type_by_code(PDO $db, string $code): ?array {
        return dg_get_document_type_by_code($db, $code);
    }
}

if (!function_exists('lc_get_active_template')) {
    function lc_get_active_template(PDO $db, string $templateCode): ?array {
        return dg_get_active_template($db, $templateCode);
    }
}

if (!function_exists('lc_get_template_versions')) {
    function lc_get_template_versions(PDO $db, string $templateCode): array {
        return dg_get_template_versions($db, $templateCode);
    }
}

if (!function_exists('lc_get_handbook_acknowledgement')) {
    function lc_get_handbook_acknowledgement(PDO $db, int $employeeId, int $templateId): ?array {
        return dg_get_handbook_acknowledgement($db, $employeeId, $templateId);
    }
}

if (!function_exists('lc_upsert_handbook_acknowledgement')) {
    function lc_upsert_handbook_acknowledgement(PDO $db, int $employeeId, int $templateId, string $version, string $status, ?int $accountId = null): bool {
        return dg_upsert_handbook_acknowledgement($db, $employeeId, $templateId, $version, $status, $accountId);
    }
}

if (!function_exists('lc_get_required_document_types')) {
    function lc_get_required_document_types(PDO $db): array {
        return dg_get_required_document_types($db);
    }
}

function dg_can_access_employee_document(?int $viewerEmployeeId, ?string $viewerRole, int $documentEmployeeId): bool {
    if ($viewerEmployeeId === null || $viewerEmployeeId <= 0) {
        return false;
    }
    $role = strtolower((string) ($viewerRole ?? ''));
    $adminRoles = ['admin', 'system administrator', 'compliance', 'legal', 'hr', 'human resource', 'recruitment'];
    foreach ($adminRoles as $r) {
        if (str_contains($role, $r)) {
            return true;
        }
    }
    return (int) $viewerEmployeeId === (int) $documentEmployeeId;
}

if (!function_exists('lc_can_access_employee_document')) {
    function lc_can_access_employee_document(?int $viewerEmployeeId, ?string $viewerRole, int $documentEmployeeId): bool {
        return dg_can_access_employee_document($viewerEmployeeId, $viewerRole, $documentEmployeeId);
    }
}

// ============================================================
// Handbook Management Helpers
// ============================================================

function hm_csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    if (empty($_SESSION['hm_csrf_token']) || !is_string($_SESSION['hm_csrf_token'])) {
        $_SESSION['hm_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['hm_csrf_token'];
}

function hm_validate_csrf(): bool {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['hm_csrf_token'] ?? '', (string) $token);
}

function hm_set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $_SESSION['hm_flash'] = ['type' => $type, 'message' => $message];
}

function hm_get_flash(): ?array {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $flash = $_SESSION['hm_flash'] ?? null;
    unset($_SESSION['hm_flash']);
    return $flash;
}

function hm_is_legal_role(): bool {
    $roleId = (int) ($_SESSION['role'] ?? 0);
    $roleName = strtolower((string) ($_SESSION['role_name'] ?? $_SESSION['role'] ?? ''));

    if ($roleId === 1 || $roleId === 8) {
        return true;
    }

    $adminRoles = ['compliance', 'legal', 'admin', 'system administrator'];
    foreach ($adminRoles as $r) {
        if (stripos($roleName, $r) !== false) {
            return true;
        }
    }

    return false;
}

function hm_write_audit(PDO $db, string $tableName, int $recordId, string $action, ?string $userType, string $description): void {
    try {
        $userId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        $stmt = $db->prepare("
            INSERT INTO lc_audit_trail (table_name, record_id, action, user_type, description, created_at)
            VALUES (:table_name, :record_id, :action, :user_type, :description, NOW())
        ");
        $stmt->execute([
            ':table_name' => $tableName,
            ':record_id' => $recordId,
            ':action' => $action,
            ':user_type' => $userType ?? ($userId > 0 ? 'User #' . $userId : 'System'),
            ':description' => $description,
        ]);
    } catch (Throwable $e) {
        error_log('hm_write_audit error: ' . $e->getMessage());
    }
}

function hm_validate_json_field(?string $json): ?string {
    if ($json === null || trim($json) === '') {
        return null;
    }
    json_decode($json);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return json_last_error_msg();
    }
    return null;
}

function dg_get_template_version(PDO $db, string $templateCode, string $version): ?array {
    try {
        $stmt = $db->prepare("
            SELECT template_id, template_code, template_name, version, status,
                   template_content, governing_law, jurisdiction, effective_date, is_default,
                   document_action, requirement_mode, applicability_rules, template_selection_rules,
                   created_by_role, approved_by, approved_at, created_at, updated_at
            FROM lc_document_templates
            WHERE template_code = :code AND version = :version
            LIMIT 1
        ");
        $stmt->execute([':code' => $templateCode, ':version' => $version]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function hm_can_transition(string $from, string $to): bool {
    $allowed = [
        'Draft'          => ['Pending Review'],
        'Pending Review' => ['Approved', 'Draft'],
        'Approved'       => ['Active', 'Draft'],
        'Active'         => ['Superseded', 'Retired'],
        'Superseded'     => ['Retired'],
        'Retired'        => [],
        'Inactive'       => ['Draft', 'Retired'],
    ];
    return in_array($to, $allowed[$from] ?? [], true);
}

function hm_get_ack_summary(PDO $db, int $templateId, ?string $applicabilityRulesJson): array {
    $required = 0;
    $acknowledged = 0;
    $pending = 0;
    $viewed = 0;

    try {
        $rules = [];
        if ($applicabilityRulesJson) {
            $rules = json_decode($applicabilityRulesJson, true);
            if (!is_array($rules)) {
                $rules = [];
            }
        }

        $sql = "SELECT COUNT(*) FROM em_employees e WHERE e.employment_status = 'Active' AND e.is_archived = 0";
        $params = [];

        if (!empty($rules['all_employees'])) {
            // all active employees - no additional filter
        } else {
            $conditions = [];
            if (!empty($rules['departments']) && is_array($rules['departments'])) {
                $deptPlaceholders = [];
                foreach ($rules['departments'] as $i => $deptName) {
                    $key = ':dept' . $i;
                    $deptPlaceholders[] = $key;
                    $params[$key] = $deptName;
                }
                $conditions[] = 'e.department_id IN (SELECT department_id FROM em_departments WHERE department_name IN (' . implode(',', $deptPlaceholders) . '))';
            }
            if (!empty($rules['positions']) && is_array($rules['positions'])) {
                $posPlaceholders = [];
                foreach ($rules['positions'] as $i => $posName) {
                    $key = ':pos' . $i;
                    $posPlaceholders[] = $key;
                    $params[$key] = $posName;
                }
                $conditions[] = 'e.position_id IN (SELECT position_id FROM em_positions WHERE position_name IN (' . implode(',', $posPlaceholders) . '))';
            }
            if (!empty($rules['employment_type']) && is_array($rules['employment_type'])) {
                $typePlaceholders = [];
                foreach ($rules['employment_type'] as $i => $type) {
                    $key = ':etype' . $i;
                    $typePlaceholders[] = $key;
                    $params[$key] = $type;
                }
                $conditions[] = 'e.employment_type IN (' . implode(',', $typePlaceholders) . ')';
            }
            if (!empty($rules['employment_status']) && is_array($rules['employment_status'])) {
                $statusPlaceholders = [];
                foreach ($rules['employment_status'] as $i => $status) {
                    $key = ':estat' . $i;
                    $statusPlaceholders[] = $key;
                    $params[$key] = $status;
                }
                $conditions[] = 'e.employment_status IN (' . implode(',', $statusPlaceholders) . ')';
            }
            if (!empty($conditions)) {
                $sql .= ' AND ' . implode(' AND ', $conditions);
            }
        }

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $required = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('hm_get_ack_summary error: ' . $e->getMessage());
    }

    try {
        $stmt = $db->prepare("
            SELECT status, COUNT(*) AS cnt FROM lc_handbook_acknowledgements
            WHERE template_id = :tid GROUP BY status
        ");
        $stmt->execute([':tid' => $templateId]);
        $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $acknowledged = (int) ($stats['Acknowledged'] ?? 0);
        $viewed = (int) ($stats['Viewed'] ?? 0);
        $pending = max(0, $required - $acknowledged);
    } catch (Throwable $e) {
        error_log('hm_get_ack_summary ack error: ' . $e->getMessage());
    }

    return [
        'required' => $required,
        'acknowledged' => $acknowledged,
        'pending' => $pending,
        'viewed' => $viewed,
        'compliance_rate' => $required > 0 ? round(($acknowledged / $required) * 100, 1) : 0,
    ];
}

function hm_get_pending_acks(PDO $db, int $templateId, ?string $applicabilityRulesJson): array {
    $employees = [];

    try {
        $rules = [];
        if ($applicabilityRulesJson) {
            $rules = json_decode($applicabilityRulesJson, true);
            if (!is_array($rules)) {
                $rules = [];
            }
        }

        $sql = "SELECT e.employee_id, e.employee_code, e.first_name, e.middle_name, e.last_name,
                       d.department_name, p.position_name, h.status as ack_status, h.acknowledged_at, h.viewed_at
                FROM em_employees e
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                LEFT JOIN lc_handbook_acknowledgements h ON h.employee_id = e.employee_id AND h.template_id = :tid
                WHERE e.employment_status = 'Active' AND e.is_archived = 0";
        $params = [':tid' => $templateId];

        if (!empty($rules['all_employees'])) {
            // no additional filter
        } else {
            $conditions = [];
            if (!empty($rules['departments']) && is_array($rules['departments'])) {
                $deptPlaceholders = [];
                foreach ($rules['departments'] as $i => $deptName) {
                    $key = ':dept' . $i;
                    $deptPlaceholders[] = $key;
                    $params[$key] = $deptName;
                }
                $conditions[] = 'e.department_id IN (SELECT department_id FROM em_departments WHERE department_name IN (' . implode(',', $deptPlaceholders) . '))';
            }
            if (!empty($rules['positions']) && is_array($rules['positions'])) {
                $posPlaceholders = [];
                foreach ($rules['positions'] as $i => $posName) {
                    $key = ':pos' . $i;
                    $posPlaceholders[] = $key;
                    $params[$key] = $posName;
                }
                $conditions[] = 'e.position_id IN (SELECT position_id FROM em_positions WHERE position_name IN (' . implode(',', $posPlaceholders) . '))';
            }
            if (!empty($rules['employment_type']) && is_array($rules['employment_type'])) {
                $typePlaceholders = [];
                foreach ($rules['employment_type'] as $i => $type) {
                    $key = ':etype' . $i;
                    $typePlaceholders[] = $key;
                    $params[$key] = $type;
                }
                $conditions[] = 'e.employment_type IN (' . implode(',', $typePlaceholders) . ')';
            }
            if (!empty($rules['employment_status']) && is_array($rules['employment_status'])) {
                $statusPlaceholders = [];
                foreach ($rules['employment_status'] as $i => $status) {
                    $key = ':estat' . $i;
                    $statusPlaceholders[] = $key;
                    $params[$key] = $status;
                }
                $conditions[] = 'e.employment_status IN (' . implode(',', $statusPlaceholders) . ')';
            }
            if (!empty($conditions)) {
                $sql .= ' AND ' . implode(' AND ', $conditions);
            }
        }

        $sql .= " ORDER BY e.last_name, e.first_name";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('hm_get_pending_acks error: ' . $e->getMessage());
    }

    return $employees;
}

function hm_get_version_ack_history(PDO $db, int $templateId, string $version): array {
    $history = [];
    try {
        $stmt = $db->prepare("
            SELECT h.ack_id, h.employee_id, h.version, h.status, h.viewed_at, h.acknowledged_at,
                   e.first_name, e.middle_name, e.last_name, e.employee_code,
                   d.department_name, p.position_name
            FROM lc_handbook_acknowledgements h
            INNER JOIN em_employees e ON e.employee_id = h.employee_id
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            WHERE h.template_id = :tid AND h.version = :version
            ORDER BY h.acknowledged_at DESC, h.viewed_at DESC, h.ack_id DESC
        ");
        $stmt->execute([':tid' => $templateId, ':version' => $version]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('hm_get_version_ack_history error: ' . $e->getMessage());
    }
    return $history;
}

// ============================================================
// Generic Document Template Management Helpers
// ============================================================

function dtm_get_available_templates(PDO $db): array {
    $excluded = ['employment_contract', 'contract_renewal', 'contract_extension'];
    try {
        $in = str_repeat('?,', count($excluded) - 1) . '?';
        $stmt = $db->prepare("
            SELECT DISTINCT template_code, template_name
            FROM lc_document_templates
            WHERE template_code NOT IN ({$in})
            ORDER BY template_name ASC
        ");
        $stmt->execute($excluded);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Throwable $e) {
        return [];
    }
}

function dtm_handle_create_version(PDO $db, string $templateCode): array {
    $version = trim((string) ($_POST['version'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $effectiveDate = trim((string) ($_POST['effective_date'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'Draft'));
    $templateName = trim((string) ($_POST['template_name'] ?? ''));
    $documentAction = trim((string) ($_POST['document_action'] ?? 'generate_sign'));
    $requirementMode = trim((string) ($_POST['requirement_mode'] ?? 'Required'));
    $applicabilityRules = trim((string) ($_POST['applicability_rules'] ?? ''));
    $templateSelectionRules = trim((string) ($_POST['template_selection_rules'] ?? ''));

    if ($version === '' || $content === '' || $templateName === '') {
        return ['error', 'Version, name, and content are required.'];
    }

    if (!preg_match('/^\d+\.\d+$/', $version)) {
        return ['error', 'Version must follow the X.Y format (e.g., 1.0, 2.1).'];
    }

    if (dtm_version_exists($db, $templateCode, $version)) {
        return ['error', 'Version ' . $version . ' already exists. Please use a different version number.'];
    }

    $jsonError = hm_validate_json_field($applicabilityRules);
    if ($jsonError !== null) {
        return ['error', 'Invalid Applicability Rules JSON: ' . $jsonError];
    }

    $jsonError = hm_validate_json_field($templateSelectionRules);
    if ($jsonError !== null) {
        return ['error', 'Invalid Template Selection Rules JSON: ' . $jsonError];
    }

    if (in_array($status, ['Active', 'Superseded', 'Retired'], true)) {
        return ['error', 'New versions must be created as Draft. Use the workflow actions to advance the status.'];
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            INSERT INTO lc_document_templates
                (template_code, template_name, version, status, template_content, effective_date, created_by_role,
                 document_action, requirement_mode, applicability_rules, template_selection_rules)
            VALUES
                (:code, :name, :version, :status, :content, :ed, 'legal',
                 :action, :mode, :rules, :tsr)
        ");
        $stmt->execute([
            ':code' => $templateCode,
            ':name' => $templateName,
            ':version' => $version,
            ':status' => $status,
            ':content' => $content,
            ':ed' => $effectiveDate ?: null,
            ':action' => $documentAction,
            ':mode' => $requirementMode,
            ':rules' => $applicabilityRules ?: null,
            ':tsr' => $templateSelectionRules ?: null,
        ]);

        $templateId = (int) $db->lastInsertId();
        hm_write_audit($db, 'lc_document_templates', $templateId, 'UPDATE', null, 'Created ' . $templateCode . ' version ' . $version . ' as ' . $status);

        $db->commit();
        return ['success', 'Document definition created successfully.'];
    } catch (Throwable $e) {
        $db->rollBack();
        error_log('dtm_handle_create_version error (' . $templateCode . '): ' . $e->getMessage());
        return ['error', 'Failed to create document definition. Please try again.'];
    }
}

function dtm_handle_update_draft(PDO $db, string $templateCode): array {
    $templateId = (int) ($_POST['template_id'] ?? 0);
    $version = trim((string) ($_POST['version'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $effectiveDate = trim((string) ($_POST['effective_date'] ?? ''));
    $templateName = trim((string) ($_POST['template_name'] ?? ''));
    $documentAction = trim((string) ($_POST['document_action'] ?? 'generate_sign'));
    $requirementMode = trim((string) ($_POST['requirement_mode'] ?? 'Required'));
    $applicabilityRules = trim((string) ($_POST['applicability_rules'] ?? ''));
    $templateSelectionRules = trim((string) ($_POST['template_selection_rules'] ?? ''));

    if ($templateId <= 0) {
        return ['error', 'Invalid template ID.'];
    }

    if ($version === '' || $content === '' || $templateName === '') {
        return ['error', 'Version, name, and content are required.'];
    }

    $existing = dg_get_template_version($db, $templateCode, $version);
    if (!$existing || (int) $existing['template_id'] !== $templateId) {
        return ['error', 'Version not found or does not match the template.'];
    }

    if ($existing['status'] !== 'Draft') {
        return ['error', 'Only Draft versions can be edited.'];
    }

    $jsonError = hm_validate_json_field($applicabilityRules);
    if ($jsonError !== null) {
        return ['error', 'Invalid Applicability Rules JSON: ' . $jsonError];
    }

    $jsonError = hm_validate_json_field($templateSelectionRules);
    if ($jsonError !== null) {
        return ['error', 'Invalid Template Selection Rules JSON: ' . $jsonError];
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            UPDATE lc_document_templates
            SET template_name = :name, version = :version, template_content = :content,
                effective_date = :ed, document_action = :action, requirement_mode = :mode,
                applicability_rules = :rules, template_selection_rules = :tsr,
                updated_at = NOW()
            WHERE template_id = :id AND template_code = :code
        ");
        $stmt->execute([
            ':name' => $templateName,
            ':version' => $version,
            ':content' => $content,
            ':ed' => $effectiveDate ?: null,
            ':action' => $documentAction,
            ':mode' => $requirementMode,
            ':rules' => $applicabilityRules ?: null,
            ':tsr' => $templateSelectionRules ?: null,
            ':id' => $templateId,
            ':code' => $templateCode,
        ]);

        hm_write_audit($db, 'lc_document_templates', $templateId, 'UPDATE', null, 'Updated draft version ' . $version . ' of ' . $templateCode);

        $db->commit();
        return ['success', 'Draft updated successfully.'];
    } catch (Throwable $e) {
        $db->rollBack();
        error_log('dtm_handle_update_draft error (' . $templateCode . '): ' . $e->getMessage());
        return ['error', 'Failed to update draft. Please try again.'];
    }
}

function dtm_handle_submit_for_review(PDO $db, string $templateCode): array {
    $templateId = (int) ($_POST['template_id'] ?? 0);
    if ($templateId <= 0) {
        return ['error', 'Invalid template ID.'];
    }

    $stmt = $db->prepare("SELECT template_id, version, status FROM lc_document_templates WHERE template_id = :id AND template_code = :code LIMIT 1");
    $stmt->execute([':id' => $templateId, ':code' => $templateCode]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        return ['error', 'Template not found.'];
    }

    $currentStatus = $record['status'];
    $targetStatus = 'Pending Review';

    if ($currentStatus === 'Pending Review') {
        $targetStatus = 'Draft';
    } elseif ($currentStatus === 'Approved') {
        $targetStatus = 'Draft';
    } elseif ($currentStatus !== 'Draft') {
        return ['error', 'Only Draft, Pending Review, or Approved versions can be submitted for review.'];
    }

    if (!hm_can_transition($currentStatus, $targetStatus)) {
        return ['error', 'Cannot transition from ' . $currentStatus . ' to ' . $targetStatus . '.'];
    }

    try {
        $stmt = $db->prepare("UPDATE lc_document_templates SET status = :status, updated_at = NOW() WHERE template_id = :id");
        $stmt->execute([':status' => $targetStatus, ':id' => $templateId]);

        $actionLabel = $targetStatus === 'Pending Review' ? 'Submitted for review' : 'Returned to draft';
        hm_write_audit($db, 'lc_document_templates', $templateId, 'UPDATE', null, $actionLabel . ' version ' . $record['version'] . ' of ' . $templateCode);

        return ['success', $actionLabel . ' successfully.'];
    } catch (Throwable $e) {
        error_log('dtm_handle_submit_for_review error (' . $templateCode . '): ' . $e->getMessage());
        return ['error', 'Failed to update status. Please try again.'];
    }
}

function dtm_handle_approve(PDO $db, string $templateCode): array {
    $templateId = (int) ($_POST['template_id'] ?? 0);
    if ($templateId <= 0) {
        return ['error', 'Invalid template ID.'];
    }

    $stmt = $db->prepare("SELECT template_id, version, status FROM lc_document_templates WHERE template_id = :id AND template_code = :code LIMIT 1");
    $stmt->execute([':id' => $templateId, ':code' => $templateCode]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        return ['error', 'Template not found.'];
    }

    if ($record['status'] !== 'Pending Review') {
        return ['error', 'Only versions in Pending Review can be approved.'];
    }

    try {
        $stmt = $db->prepare("
            UPDATE lc_document_templates
            SET status = 'Approved', approved_by = :uid, approved_at = NOW(), updated_at = NOW()
            WHERE template_id = :id
        ");
        $userId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        $stmt->execute([':uid' => $userId > 0 ? $userId : null, ':id' => $templateId]);

        hm_write_audit($db, 'lc_document_templates', $templateId, 'UPDATE', null, 'Approved version ' . $record['version'] . ' of ' . $templateCode);

        return ['success', 'Version approved successfully.'];
    } catch (Throwable $e) {
        error_log('dtm_handle_approve error (' . $templateCode . '): ' . $e->getMessage());
        return ['error', 'Failed to approve version. Please try again.'];
    }
}

function dtm_handle_activate(PDO $db, string $templateCode): array {
    $templateId = (int) ($_POST['template_id'] ?? 0);
    if ($templateId <= 0) {
        return ['error', 'Invalid template ID.'];
    }

    $stmt = $db->prepare("SELECT template_id, version, status FROM lc_document_templates WHERE template_id = :id AND template_code = :code LIMIT 1");
    $stmt->execute([':id' => $templateId, ':code' => $templateCode]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        return ['error', 'Template not found.'];
    }

    if ($record['status'] !== 'Approved') {
        return ['error', 'Only Approved versions can be activated. Current status: ' . $record['status'] . '.'];
    }

    $db->beginTransaction();
    try {
        $db->prepare("UPDATE lc_document_templates SET is_default = 0, status = 'Superseded', updated_at = NOW() WHERE template_code = :code AND status = 'Active'")->execute([':code' => $templateCode]);

        $stmt = $db->prepare("UPDATE lc_document_templates SET status = 'Active', is_default = 1, updated_at = NOW() WHERE template_id = :id");
        $stmt->execute([':id' => $templateId]);

        hm_write_audit($db, 'lc_document_templates', $templateId, 'UPDATE', null, 'Activated version ' . $record['version'] . ' of ' . $templateCode);

        $db->commit();
        return ['success', 'Version activated successfully.'];
    } catch (Throwable $e) {
        $db->rollBack();
        error_log('dtm_handle_activate error (' . $templateCode . '): ' . $e->getMessage());
        return ['error', 'Failed to activate version. Please try again.'];
    }
}

function dtm_handle_retire(PDO $db, string $templateCode): array {
    $templateId = (int) ($_POST['template_id'] ?? 0);
    if ($templateId <= 0) {
        return ['error', 'Invalid template ID.'];
    }

    $stmt = $db->prepare("SELECT template_id, version, status FROM lc_document_templates WHERE template_id = :id AND template_code = :code LIMIT 1");
    $stmt->execute([':id' => $templateId, ':code' => $templateCode]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        return ['error', 'Template not found.'];
    }

    if ($record['status'] === 'Retired') {
        return ['error', 'This version is already retired.'];
    }

    try {
        $stmt = $db->prepare("
            UPDATE lc_document_templates
            SET status = 'Retired', retired_date = CURDATE(), is_default = 0, updated_at = NOW()
            WHERE template_id = :id
        ");
        $stmt->execute([':id' => $templateId]);

        hm_write_audit($db, 'lc_document_templates', $templateId, 'UPDATE', null, 'Retired version ' . $record['version'] . ' of ' . $templateCode);

        return ['success', 'Version retired successfully.'];
    } catch (Throwable $e) {
        error_log('dtm_handle_retire error (' . $templateCode . '): ' . $e->getMessage());
        return ['error', 'Failed to retire version. Please try again.'];
    }
}

function dtm_version_exists(PDO $db, string $templateCode, string $version, ?int $excludeTemplateId = null): bool {
    $sql = "SELECT COUNT(*) FROM lc_document_templates WHERE template_code = :code AND version = :version";
    $params = [':code' => $templateCode, ':version' => $version];
    if ($excludeTemplateId !== null) {
        $sql .= " AND template_id != :id";
        $params[':id'] = $excludeTemplateId;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

// ============================================================
// Onboarding Document Package Helpers
// ============================================================

function op_generate_package_number(PDO $db): string {
    $year = date('Y');
    try {
        $nextId = (int) $db->query("SELECT COALESCE(MAX(package_id), 0) + 1 FROM lc_onboarding_packages")->fetchColumn();
    } catch (Throwable $e) {
        $nextId = 1;
    }
    return 'ONB-' . $year . '-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
}

function op_get_employee_for_package(PDO $db, string $employeeId): ?array {
    try {
        $stmt = $db->prepare("
            SELECT e.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
            FROM em_employees e
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
            WHERE e.employee_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $employeeId]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            $stmt = $db->prepare("
                SELECT rh.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
                FROM rao_hired rh
                LEFT JOIN em_departments d ON rh.department = d.department_name
                LEFT JOIN em_positions p ON rh.position = p.position_name
                WHERE rh.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $employeeId]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$emp) {
            $stmt = $db->prepare("
                SELECT rh.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
                FROM rao_hired rh
                LEFT JOIN em_departments d ON rh.department = d.department_name
                LEFT JOIN em_positions p ON rh.position = p.position_name
                WHERE rh.application_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $employeeId]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($emp && empty($emp['full_name'])) {
            $parts = array_filter([$emp['first_name'] ?? '', $emp['middle_name'] ?? '', $emp['last_name'] ?? '']);
            $emp['full_name'] = trim(implode(' ', $parts));
        }

        return $emp ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function op_get_rao_hired_applicant(PDO $db, int $raoHiredId): ?array {
    try {
        $stmt = $db->prepare("
            SELECT * FROM rao_hired
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $raoHiredId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function op_list_rao_hired_applicants(PDO $db, array $filters = []): array {
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['search'])) {
        $where[] = "(CONCAT(first_name, ' ', last_name) LIKE :search OR email LIKE :search OR position LIKE :search OR department LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    if (!empty($filters['department'])) {
        $where[] = 'department = :department';
        $params[':department'] = $filters['department'];
    }

    if (!empty($filters['position'])) {
        $where[] = 'position = :position';
        $params[':position'] = $filters['position'];
    }

    $whereSql = implode(' AND ', $where);

    try {
        $stmt = $db->prepare("
            SELECT * FROM rao_hired
            WHERE {$whereSql}
            ORDER BY hired_at DESC, id DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function op_create_document_request_from_rao_hired(PDO $db, int $raoHiredId, string $documentType, string $templateCode, ?int $createdBy = null): ?int {
    $applicant = op_get_rao_hired_applicant($db, $raoHiredId);
    if (!$applicant) {
        return null;
    }

    $employeeId = (int) ($applicant['application_id'] ?? $applicant['id'] ?? 0);
    if ($employeeId <= 0) {
        return null;
    }

    $requestId = null;
    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO lc_document_requests
                (employee_id, rao_hired_id, document_type, request_status, priority, notes, requires_signature, signature_status, template_code, created_by)
            VALUES
                (:employee_id, :rao_hired_id, :document_type, 'Pending', 'Medium', :notes, 1, 'none', :template_code, :created_by)
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':rao_hired_id' => $raoHiredId,
            ':document_type' => $documentType,
            ':template_code' => $templateCode,
            ':notes' => 'Auto-generated from rao_hired applicant #' . $raoHiredId,
            ':created_by' => $createdBy,
        ]);
        $requestId = (int) $db->lastInsertId();

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Failed to create document request from rao_hired: ' . $e->getMessage());
        return null;
    }

    return $requestId;
}

function op_render_contract_section(PDO $db, array $employee): string {
    $sourceTable = 'em_employees';
    $idColumn = 'employee_id';
    $employeeId = (string) ($employee['employee_id'] ?? $employee['candidate_id'] ?? '');

    if ($employeeId === '' && !empty($employee['id'])) {
        $sourceTable = 'rao_hired';
        $idColumn = 'id';
        $employeeId = (string) $employee['id'];
    } elseif ($employeeId === '' && !empty($employee['application_id'])) {
        $sourceTable = 'rao_hired';
        $idColumn = 'application_id';
        $employeeId = (string) $employee['application_id'];
    }

    $savedGet = $_GET;
    $_GET['onboarding'] = '1';
    $_GET['employee_id'] = $employeeId;
    $_GET['document_type'] = 'Employment Contract (New Hire)';
    $_GET['template_code'] = 'employment_contract';
    $_GET['hr_signatory'] = '';
    $_GET['contract_type'] = $employee['employment_status'] ?? '';
    $rawDateHired = (string) ($employee['date_hired'] ?? $employee['hire_date'] ?? '');
    if ($rawDateHired !== '') {
        $_GET['contract_start_date'] = date('Y-m-d', strtotime($rawDateHired));
        $_GET['contract_end_date'] = date('Y-m-d', strtotime('+1 year', strtotime($rawDateHired)));
    } else {
        $_GET['contract_start_date'] = '';
        $_GET['contract_end_date'] = '';
    }
    $_GET['contract_salary_input'] = $employee['monthly_salary'] ?? $employee['negotiated_salary'] ?? '';

    $data = [
        'employee_id' => $employeeId,
        'document_type' => 'Employment Contract (New Hire)',
        'template_code' => 'employment_contract',
        'source_table' => $sourceTable,
        'id_column' => $idColumn,
        'raw_employment_status' => (string) ($employee['employment_status'] ?? ''),
        'raw_date_hired' => $rawDateHired,
    ];

    ob_start();
    $templatePath = __DIR__ . '/../../pages/templates/employment_contract/preview.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    }
    $rendered = ob_get_clean();

    $_GET = $savedGet;
    return $rendered;
}

function op_render_handbook_section(PDO $db, array $employee): string {
    $sourceTable = 'em_employees';
    $idColumn = 'employee_id';
    $employeeId = (string) ($employee['employee_id'] ?? $employee['candidate_id'] ?? '');

    if ($employeeId === '' && !empty($employee['id'])) {
        $sourceTable = 'rao_hired';
        $idColumn = 'id';
        $employeeId = (string) $employee['id'];
    } elseif ($employeeId === '' && !empty($employee['application_id'])) {
        $sourceTable = 'rao_hired';
        $idColumn = 'application_id';
        $employeeId = (string) $employee['application_id'];
    }

    $savedGet = $_GET;
    $_GET['override_employee_id'] = $employeeId;
    $_GET['employee_id'] = $employeeId;
    $_GET['document_type'] = 'Employee Handbook';
    $_GET['template_code'] = 'employee_handbook';
    $_GET['hr_signatory'] = '';

    $data = [
        'employee_id' => $employeeId,
        'document_type' => 'Employee Handbook',
        'template_code' => 'employee_handbook',
        'employee_full_name' => (string) ($employee['full_name'] ?? ''),
        'employee_position' => (string) ($employee['position_name'] ?? ''),
        'employee_department' => (string) ($employee['department_name'] ?? ''),
        'employee_email' => (string) ($employee['email'] ?? ''),
        'employee_code' => (string) ($employee['employee_code'] ?? $employee['employee_no'] ?? ''),
        'source_table' => $sourceTable,
        'id_column' => $idColumn,
        'raw_date_hired' => (string) ($employee['date_hired'] ?? $employee['hire_date'] ?? ''),
        'raw_employment_status' => (string) ($employee['employment_status'] ?? ''),
    ];

    ob_start();
    $templatePath = __DIR__ . '/../../pages/templates/employee_handbook/preview.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    }
    $rendered = ob_get_clean();

    $_GET = $savedGet;
    return $rendered;
}

function op_render_nda_section(PDO $db, array $employee): string {
    $sourceTable = 'em_employees';
    $idColumn = 'employee_id';
    $employeeId = (string) ($employee['employee_id'] ?? $employee['candidate_id'] ?? '');

    if ($employeeId === '' && !empty($employee['id'])) {
        $sourceTable = 'rao_hired';
        $idColumn = 'id';
        $employeeId = (string) $employee['id'];
    } elseif ($employeeId === '' && !empty($employee['application_id'])) {
        $sourceTable = 'rao_hired';
        $idColumn = 'application_id';
        $employeeId = (string) $employee['application_id'];
    }

    $savedGet = $_GET;
    $_GET['employee_id'] = $employeeId;
    $_GET['document_type'] = 'Non-Disclosure Agreement (NDA)';
    $_GET['template_code'] = 'nda';
    $_GET['hr_signatory'] = '';

    $data = [
        'employee_id' => $employeeId,
        'document_type' => 'Non-Disclosure Agreement (NDA)',
        'template_code' => 'nda',
        'source_table' => $sourceTable,
        'id_column' => $idColumn,
    ];

    ob_start();
    $templatePath = __DIR__ . '/../../pages/templates/nda/preview.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    }
    $rendered = ob_get_clean();

    $_GET = $savedGet;
    return $rendered;
}

function op_generate_package_html(PDO $db, array $employee): string {
    $contractHtml = op_render_contract_section($db, $employee);
    $handbookHtml = op_render_handbook_section($db, $employee);
    $ndaHtml = op_render_nda_section($db, $employee);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
    $notarySrc = $protocol . $host . '/hrms-capstone/modules/compliance/assets/notary.png';

    $pageBreakStyle = 'style="page-break-after: always; break-after: page;"';

    $cssPath = __DIR__ . '/../../css/onboarding-package-pdf.css';
    $cssContent = '';
    if (is_file($cssPath)) {
        $cssContent = file_get_contents($cssPath);
        if ($cssContent === false) {
            $cssContent = '';
        }
    }

    $webCss = '
    .pkg-wrap {
        max-width: 900px;
        margin: 24px auto;
        padding: 0 16px;
        font-family: "Times New Roman", Times, serif;
        color: #111;
    }
    .pkg-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        position: relative;
        overflow: hidden;
    }
    .pkg-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-35deg);
        width: 320px;
        max-width: 70%;
        height: auto;
        opacity: 0.35;
        pointer-events: none;
        z-index: 10;
    }
    .pkg-section > *:not(.pkg-watermark) {
        position: relative;
        z-index: 1;
    }
    .pkg-section + .pkg-section {
        break-before: auto;
        page-break-before: auto;
    }
    .pkg-cover {
        border-bottom: 2px solid #0f2b4d;
        padding-bottom: 12px;
        margin-bottom: 16px;
    }
    .pkg-footer {
        margin-top: 16px;
        padding-top: 8px;
        border-top: 1px solid #d1d5db;
        font-size: 0.75rem;
        color: #6b7280;
    }
    .document-preview {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .document-header {
        border-top: none !important;
        border-left: none !important;
        border-right: none !important;
        box-shadow: none !important;
    }
    @media (max-width: 640px) {
        .pkg-wrap { padding: 0 8px; }
        .pkg-section { padding: 16px; }
    }
    ';

    $employeeName = htmlspecialchars((string) ($employee['full_name'] ?? ''), ENT_QUOTES);

    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    $html .= '<title>Onboarding Document Package</title>';
    $html .= '<style>' . $cssContent . $webCss . '</style></head><body><div class="pkg-wrap">';

    $html .= '<div class="pkg-section">';
    $html .= '<img src="' . $notarySrc . '" class="pkg-watermark" alt="">';
    $html .= '<div class="pkg-cover">';
    $html .= '<div class="pkg-doc-title">EMPLOYMENT CONTRACT</div>';
    $html .= '<div class="pkg-doc-subtitle">Employment Agreement · Governed by applicable Philippine labor laws</div>';
    $html .= '</div>';
    $html .= $contractHtml;
    $html .= '<div style="page-break-inside:avoid; margin-top:12px; padding:10px; border:1px solid #b45309; border-radius:4px; background:#fffbeb;">';
    $html .= '<p style="margin:0; color:#78350f; font-family:&quot;Times New Roman&quot;, Times, serif; font-size:0.7rem; line-height:1.4;">';
    $html .= '<strong>THESIS DISCLAIMER:</strong> This document is generated for <strong>academic and thesis purposes only</strong>. It is intended for system demonstration and evaluation and should not be considered official legal advice or a substitute for professional legal or HR review. All information and sample data should be validated and updated before actual use.';
    $html .= '</p>';
    $html .= '</div>';
    $html .= '<table class="pkg-footer" cellpadding="0" cellspacing="0" border="0"><tr><td class="pf-left">Onboarding Document Package</td><td class="pf-center">Human Resources Department</td><td class="pf-right">Page 1 of 3</td></tr></table>';
    $html .= '</div>';
    $html .= '<div ' . $pageBreakStyle . '></div>';

    $html .= '<div class="pkg-section">';
    $html .= '<img src="' . $notarySrc . '" class="pkg-watermark" alt="">';
    $html .= $handbookHtml;
    $html .= '<div style="page-break-inside:avoid; margin-top:12px; padding:10px; border:1px solid #b45309; border-radius:4px; background:#fffbeb;">';
    $html .= '<p style="margin:0; color:#78350f; font-family:&quot;Times New Roman&quot;, Times, serif; font-size:0.7rem; line-height:1.4;">';
    $html .= '<strong>THESIS DISCLAIMER:</strong> This document is generated for <strong>academic and thesis purposes only</strong>. It is intended for system demonstration and evaluation and should not be considered official legal advice or a substitute for professional legal or HR review. All information and sample data should be validated and updated before actual use.';
    $html .= '</p>';
    $html .= '</div>';
    $html .= '<table class="pkg-footer" cellpadding="0" cellspacing="0" border="0"><tr><td class="pf-left">Onboarding Document Package</td><td class="pf-center">Human Resources Department</td><td class="pf-right">Page 2 of 3</td></tr></table>';
    $html .= '</div>';
    $html .= '<div ' . $pageBreakStyle . '></div>';

    $html .= '<div class="pkg-section">';
    $html .= '<img src="' . $notarySrc . '" class="pkg-watermark" alt="">';
    $html .= $ndaHtml;
    $html .= '<div style="page-break-inside:avoid; margin-top:12px; padding:10px; border:1px solid #b45309; border-radius:4px; background:#fffbeb;">';
    $html .= '<p style="margin:0; color:#78350f; font-family:&quot;Times New Roman&quot;, Times, serif; font-size:0.7rem; line-height:1.4;">';
    $html .= '<strong>THESIS DISCLAIMER:</strong> This document is generated for <strong>academic and thesis purposes only</strong>. It is intended for system demonstration and evaluation and should not be considered official legal advice or a substitute for professional legal or HR review. All information and sample data should be validated and updated before actual use.';
    $html .= '</p>';
    $html .= '</div>';
    $html .= '<table class="pkg-footer" cellpadding="0" cellspacing="0" border="0"><tr><td class="pf-left">Onboarding Document Package</td><td class="pf-center">Human Resources Department</td><td class="pf-right">Page 3 of 3</td></tr></table>';
    $html .= '</div>';

    $html .= '</div></body></html>';

    return $html;
}


