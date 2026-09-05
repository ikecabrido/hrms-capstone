<?php

class DocumentData
{
    public static function load(PDO $db, string $templateCode, string $employeeId, string $documentType): array
    {
        $sourceTableMap = [
            'employment_contract' => 'new_hire_table',
            'contract_renewal' => 'em_employees',
            'contract_extension' => 'em_employees',
            'salary_rectification' => 'em_employees',
            'leave_agreement' => 'em_employees',
            'study_leave' => 'em_employees',
            'suspension_notice' => 'em_employees',
            'notice_of_decision' => 'em_employees',
            'termination_decision' => 'em_employees',
            'return_service' => 'em_employees',
            'employee_handbook' => 'new_hire_table',
            'nda' => 'new_hire_table',
            'onboarding_package' => 'em_employees',
        ];
        $sourceTable = $sourceTableMap[$templateCode] ?? 'em_employees';
        $idColumn = $sourceTable === 'new_hire_table' ? 'candidate_id' : 'employee_id';

        if ($templateCode === 'employee_handbook') {
            $employeeFullName = '';
            $employeePosition = '';
            $employeeDepartment = '';
            $employeeEmail = '';
            $employeeCode = '';

            if ($employeeId !== '') {
                $employeeRow = self::loadEmployeeRow($db, $sourceTable, $idColumn, $employeeId);
                if ($employeeRow) {
                    $employeeFullName = (string) ($employeeRow['full_name'] ?? '');
                    if ($employeeFullName === '') {
                        $parts = array_filter([$employeeRow['first_name'] ?? '', $employeeRow['middle_name'] ?? '', $employeeRow['last_name'] ?? '']);
                        $employeeFullName = trim(implode(' ', $parts));
                    }
                    if ($employeeFullName === '') {
                        $employeeFullName = 'Employee #' . $employeeId;
                    }

                    $departmentId = isset($employeeRow['department_id']) ? (int) $employeeRow['department_id'] : 0;
                    $positionId   = isset($employeeRow['position_id']) ? (int) $employeeRow['position_id'] : 0;

                    if ($departmentId > 0) {
                        try {
                            $stmt = $db->prepare("SELECT department_name FROM em_departments WHERE department_id = :id LIMIT 1");
                            $stmt->execute([':id' => $departmentId]);
                            $dept = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($dept && !empty($dept['department_name'])) {
                                $employeeDepartment = (string) $dept['department_name'];
                            }
                        } catch (Throwable $e) {
                            $employeeDepartment = '';
                        }
                    }

                    if ($employeeDepartment === '' && !empty($employeeRow['department'])) {
                        $employeeDepartment = (string) $employeeRow['department'];
                    }

                    if ($positionId > 0) {
                        try {
                            $stmt = $db->prepare("SELECT position_name FROM em_positions WHERE position_id = :id LIMIT 1");
                            $stmt->execute([':id' => $positionId]);
                            $pos = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($pos && !empty($pos['position_name'])) {
                                $employeePosition = (string) $pos['position_name'];
                            }
                        } catch (Throwable $e) {
                            $employeePosition = '';
                        }
                    }

                    if ($employeePosition === '' && !empty($employeeRow['position'])) {
                        $employeePosition = (string) $employeeRow['position'];
                    }

                    $employeeEmail = (string) ($employeeRow['email'] ?? '');
                    $employeeCode = (string) ($employeeRow['employee_code'] ?? '');
                    if ($employeeCode === '') {
                        $employeeCode = 'EMP-' . str_pad((string) $employeeId, 6, '0', STR_PAD_LEFT);
                    }
                }
            }

            return [
                'employee_id' => $employeeId,
                'document_type' => $documentType ?: 'Employee Handbook',
                'template_code' => $templateCode,
                'employee_full_name' => $employeeFullName,
                'employee_position' => $employeePosition,
                'employee_department' => $employeeDepartment,
                'employee_email' => $employeeEmail,
                'employee_code' => $employeeCode,
                'raw_date_hired' => '',
                'raw_employment_status' => '',
                'source_table' => $sourceTable,
                'id_column' => $idColumn,
            ];
        }

        $rawDateHired = '';
        if ($employeeId !== '') {
            try {
                $dateColumn = $sourceTable === 'new_hire_table' ? 'date_hired' : 'hire_date';
                $stmt = $db->prepare("SELECT {$dateColumn} FROM {$sourceTable} WHERE {$idColumn} = :id LIMIT 1");
                $stmt->execute([':id' => $employeeId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $rawDateHired = $row[$dateColumn] ?? '';
                }
            } catch (Throwable $e) {
                $rawDateHired = '';
            }
        }

        $rawEmploymentStatus = '';
        if ($employeeId !== '') {
            try {
                $stmt = $db->prepare("SELECT employment_status FROM {$sourceTable} WHERE {$idColumn} = :id LIMIT 1");
                $stmt->execute([':id' => $employeeId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $rawEmploymentStatus = $row['employment_status'] ?? '';
                }
            } catch (Throwable $e) {
                $rawEmploymentStatus = '';
            }
        }

        $employeeFullName = '';
        $employeePosition = '';
        $employeeDepartment = '';
        $employeeEmail = '';

        if ($employeeId !== '') {
            $employeeRow = self::loadEmployeeRow($db, $sourceTable, $idColumn, $employeeId);
            $hasMeaningfulData = $employeeRow && !empty($employeeRow['full_name']);
            if (!$hasMeaningfulData) {
                $employeeRow = self::loadEmployeeRow($db, 'em_employees', 'employee_id', $employeeId);
            }

            if ($employeeRow) {
                $employeeFullName = (string) ($employeeRow['full_name'] ?? '');
                if ($employeeFullName === '') {
                    $parts = array_filter([$employeeRow['first_name'] ?? '', $employeeRow['middle_name'] ?? '', $employeeRow['last_name'] ?? '']);
                    $employeeFullName = trim(implode(' ', $parts));
                }
                if ($employeeFullName === '') {
                    $employeeFullName = 'Employee #' . $employeeId;
                }

                $departmentId = isset($employeeRow['department_id']) ? (int) $employeeRow['department_id'] : 0;
                $positionId   = isset($employeeRow['position_id']) ? (int) $employeeRow['position_id'] : 0;

                if ($departmentId > 0) {
                    try {
                        $stmt = $db->prepare("SELECT department_name FROM em_departments WHERE department_id = :id LIMIT 1");
                        $stmt->execute([':id' => $departmentId]);
                        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($dept && !empty($dept['department_name'])) {
                            $employeeDepartment = (string) $dept['department_name'];
                        }
                    } catch (Throwable $e) {
                        $employeeDepartment = '';
                    }
                }

                if ($employeeDepartment === '' && !empty($employeeRow['department'])) {
                    $employeeDepartment = (string) $employeeRow['department'];
                }

                if ($positionId > 0) {
                    try {
                        $stmt = $db->prepare("SELECT position_name FROM em_positions WHERE position_id = :id LIMIT 1");
                        $stmt->execute([':id' => $positionId]);
                        $pos = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($pos && !empty($pos['position_name'])) {
                            $employeePosition = (string) $pos['position_name'];
                        }
                    } catch (Throwable $e) {
                        $employeePosition = '';
                    }
                }

                if ($employeePosition === '' && !empty($employeeRow['position'])) {
                    $employeePosition = (string) $employeeRow['position'];
                }

                if ($employeePosition === '' && !empty($employeeRow['ranking'])) {
                    $employeePosition = (string) $employeeRow['ranking'];
                }

                if ($employeePosition === '' && $sourceTable === 'new_hire_table') {
                    try {
                        $stmt = $db->prepare("
                            SELECT remarks, email
                            FROM new_hire_table
                            WHERE candidate_id = :id
                            LIMIT 1
                        ");
                        $stmt->execute([':id' => $employeeId]);
                        $nh = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($nh) {
                            $remarks = (string) ($nh['remarks'] ?? '');
                            if (preg_match('/New hire\s*[-–—]\s*(.+)$/i', $remarks, $m)) {
                                $employeePosition = trim($m[1]);
                            }
                        }
                    } catch (Throwable $e) {
                        $employeePosition = '';
                    }
                }

                $employeeEmail = (string) ($employeeRow['email'] ?? '');
            }
        }

        $employeeCode = (string) ($employeeRow['employee_code'] ?? '');
        if ($employeeCode === '' && $employeeId !== '') {
            $employeeCode = 'EMP-' . str_pad((string) $employeeId, 6, '0', STR_PAD_LEFT);
        }

        return [
            'employee_id' => $employeeId,
            'document_type' => $documentType,
            'template_code' => $templateCode,
            'employee_full_name' => $employeeFullName,
            'employee_position' => $employeePosition,
            'employee_department' => $employeeDepartment,
            'employee_email' => $employeeEmail,
            'employee_code' => $employeeCode,
            'raw_date_hired' => $rawDateHired,
            'raw_employment_status' => $rawEmploymentStatus,
            'source_table' => $sourceTable,
            'id_column' => $idColumn,
        ];
    }

    private static function loadEmployeeRow(PDO $db, string $sourceTable, string $idColumn, string $employeeId): ?array
    {
        if ($employeeId === '') {
            return null;
        }

        $allowedTables = ['em_employees', 'new_hire_table'];
        if (!in_array($sourceTable, $allowedTables, true)) {
            return null;
        }

        try {
            if ($sourceTable === 'em_employees') {
                $stmt = $db->prepare("SELECT * FROM em_employees WHERE employee_id = :id LIMIT 1");
                $stmt->execute([':id' => $employeeId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && empty($row['full_name'])) {
                    $parts = array_filter([$row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '']);
                    $row['full_name'] = trim(implode(' ', $parts));
                }
                return $row ?: null;
            }

            $stmt = $db->prepare("SELECT * FROM {$sourceTable} WHERE {$idColumn} = :id LIMIT 1");
            $stmt->execute([':id' => $employeeId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

