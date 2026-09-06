<?php

require_once __DIR__ . '/../../../database/db.php';

class PatientModel
{
    private PDO $conn;

    public function __construct(?PDO $pdo = null)
    {
        $this->conn = $pdo instanceof PDO ? $pdo : (new Database())->getConnection();
    }

    public function searchEmployees(string $search = ''): array
    {
        $sql = "SELECT e.employee_id,
                       CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                       e.first_name,
                       e.last_name,
                       COALESCE(d.department_name, 'N/A') AS department,
                       COALESCE(p.position_name, 'N/A') AS position,
                       e.employment_status,
                       e.hire_date AS date_hired,
                       e.email,
                       COALESCE(e.mobile_no, e.phone_no, '') AS contact_number
                FROM em_employees e
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                WHERE 1 = 1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (
                CAST(e.employee_id AS CHAR) LIKE :search
                OR e.employee_code LIKE :search
                OR CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) LIKE :search
                OR COALESCE(d.department_name, '') LIKE :search
                OR COALESCE(p.position_name, '') LIKE :search
                OR COALESCE(e.email, '') LIKE :search
            )";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY e.first_name ASC, e.last_name ASC LIMIT 50";
        return $this->fetchAll($sql, $params);
    }

    public function getEmployeeById(int $employeeId): ?array
    {
        $sql = "SELECT e.employee_id,
                       e.employee_code,
                       CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                       e.first_name,
                       e.last_name,
                       COALESCE(e.current_address, e.permanent_address, '') AS address,
                       COALESCE(e.mobile_no, e.phone_no, '') AS contact_number,
                       e.email,
                       COALESCE(d.department_name, 'N/A') AS department,
                       COALESCE(p.position_name, 'N/A') AS position,
                       e.hire_date AS date_hired,
                       e.employment_status,
                       e.employment_status AS status_label
                FROM em_employees e
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                WHERE e.employee_id = :employee_id
                LIMIT 1";

        $rows = $this->fetchAll($sql, [':employee_id' => $employeeId]);
        return $rows[0] ?? null;
    }

    public function getPatients(string $search = ''): array
    {
        $sql = "SELECT p.patient_id, p.employee_id,
                       CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                       e.first_name, e.last_name, e.middle_name, e.email,
                       COALESCE(e.mobile_no, e.phone_no, '') AS phone,
                       COALESCE(e.current_address, e.permanent_address, '') AS address,
                       e.birth_date, p.gender, p.blood_type, p.allergies,
                       p.medical_conditions, p.current_medications, p.emergency_contact,
                       p.patient_type, p.status, p.created_at,
                       COALESCE(d.department_name, 'N/A') AS department,
                       COALESCE(pos.position_name, 'N/A') AS position,
                       e.employment_status,
                       (SELECT COUNT(*) FROM cm_medical_records mr WHERE mr.patient_id = p.patient_id) AS medical_record_count
                FROM cm_patients p
                LEFT JOIN em_employees e ON e.employee_id = p.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions pos ON pos.position_id = e.position_id
                WHERE 1 = 1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (CAST(p.patient_id AS CHAR) LIKE :search
                OR CAST(p.employee_id AS CHAR) LIKE :search
                OR CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) LIKE :search
                OR COALESCE(d.department_name, '') LIKE :search
                OR COALESCE(pos.position_name, '') LIKE :search
                OR COALESCE(e.email, '') LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY p.created_at DESC, p.patient_id DESC';
        return $this->fetchAll($sql, $params);
    }

    public function getPatientById(string $patientId): ?array
    {
        $sql = "SELECT p.patient_id,
                   p.employee_id,
                   e.first_name,
                   e.last_name,
                   e.middle_name,
                   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                   e.email,
                   COALESCE(e.mobile_no, e.phone_no, '') AS phone,
                   COALESCE(e.current_address, e.permanent_address, '') AS address,
                   e.birth_date,
                       p.gender,
                       p.blood_type,
                       p.allergies,
                       p.medical_conditions,
                       p.current_medications,
                       p.emergency_contact,
                       p.patient_type,
                       p.status,
                       p.created_at,
                       p.updated_at,
                       e.employment_status,
                       COALESCE(d.department_name, 'N/A') AS department,
                       COALESCE(pos.position_name, 'N/A') AS position,
                       (SELECT COUNT(*) FROM cm_medical_records mr WHERE mr.patient_id = p.patient_id) AS medical_record_count
                FROM cm_patients p
                LEFT JOIN em_employees e ON e.employee_id = p.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions pos ON pos.position_id = e.position_id
                WHERE p.patient_id = :patient_id
                LIMIT 1";

        $rows = $this->fetchAll($sql, [':patient_id' => $patientId]);
        return $rows[0] ?? null;
    }

    public function getPatientByEmployeeId(int $employeeId): ?array
    {
        $sql = "SELECT *
                FROM cm_patients
                WHERE employee_id = :employee_id
                LIMIT 1";

        $rows = $this->fetchAll($sql, [':employee_id' => $employeeId]);
        return $rows[0] ?? null;
    }

    public function createPatient(array $data): bool
    {
        $sql = "INSERT INTO cm_patients (
                    employee_id, gender, blood_type, allergies, medical_conditions,
                    current_medications, patient_type, status, emergency_contact
                ) VALUES (
                    :employee_id, :gender, :blood_type, :allergies, :medical_conditions,
                    :current_medications, :patient_type, :status, :emergency_contact
                )";

        $statement = $this->conn->prepare($sql);
        return $statement->execute([
            ':employee_id' => $data['employee_id'],
            ':gender' => $data['gender'] ?? null,
            ':blood_type' => $data['blood_type'] ?? null,
            ':allergies' => $data['allergies'] ?? null,
            ':medical_conditions' => $data['medical_conditions'] ?? null,
            ':current_medications' => $data['current_medications'] ?? null,
            ':patient_type' => $data['patient_type'] ?? 'Staff',
            ':status' => $data['status'] ?? 'Active',
            ':emergency_contact' => $data['phone'] ?? null,
        ]);
    }

    public function updatePatient(string $patientId, array $data): bool
    {
        $sql = "UPDATE cm_patients
            SET gender = :gender,
                    blood_type = :blood_type,
                    allergies = :allergies,
                    medical_conditions = :medical_conditions,
                    current_medications = :current_medications,
                    patient_type = :patient_type,
                    status = :status,
                    emergency_contact = :emergency_contact,
                    updated_at = NOW()
                WHERE patient_id = :patient_id";

        $statement = $this->conn->prepare($sql);
        return $statement->execute([
            ':patient_id' => $patientId,
            ':gender' => $data['gender'] ?? null,
            ':blood_type' => $data['blood_type'] ?? null,
            ':allergies' => $data['allergies'] ?? null,
            ':medical_conditions' => $data['medical_conditions'] ?? null,
            ':current_medications' => $data['current_medications'] ?? null,
            ':patient_type' => $data['patient_type'] ?? 'Staff',
            ':status' => $data['status'] ?? 'Active',
            ':emergency_contact' => $data['phone'] ?? null,
        ]);
    }

    public function deactivatePatient(string $patientId): bool
    {
        $sql = "UPDATE cm_patients
                SET status = 'Inactive', updated_at = NOW()
                WHERE patient_id = :patient_id";

        $statement = $this->conn->prepare($sql);
        return $statement->execute([':patient_id' => $patientId]);
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->conn->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
