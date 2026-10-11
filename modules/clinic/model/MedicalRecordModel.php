<?php

require_once __DIR__ . '/../../../database/db.php';

class MedicalRecordModel
{
    private PDO $conn;

    public function __construct(?PDO $pdo = null)
    {
        $this->conn = $pdo instanceof PDO ? $pdo : (new Database())->getConnection();
        $this->ensureSchemaCompatibility();
    }

    public function searchEmployees(string $search = ''): array
    {
        $sql = "SELECT e.employee_id,
                   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                   d.department_name AS department, pos.position_name AS position,
                      e.employment_status, e.email,
                       COUNT(DISTINCT mr.record_id) AS record_count,
                       MAX(mr.visit_date) AS last_visit_date
            FROM em_employees e
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions pos ON pos.position_id = e.position_id
                LEFT JOIN cm_patients p ON p.employee_id = e.employee_id
                LEFT JOIN cm_medical_records mr ON mr.patient_id = p.patient_id
                WHERE 1 = 1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (CAST(e.employee_id AS CHAR) LIKE :search
                       OR CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) LIKE :search
                       OR d.department_name LIKE :search
                       OR pos.position_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " GROUP BY e.employee_id, e.first_name, e.last_name,
                 d.department_name, pos.position_name, e.employment_status, e.email
              ORDER BY full_name ASC LIMIT 50";

        return $this->fetchAll($sql, $params);
    }

    public function getEmployeeSummary(int $employeeId): ?array
    {
        $sql = "SELECT e.employee_id,
                   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                   d.department_name AS department, pos.position_name AS position,
                      e.email, COALESCE(e.mobile_no, e.phone_no, '') AS contact_number, e.employment_status,
                       p.patient_id, p.patient_type, p.status AS patient_status,
                       p.blood_type, p.allergies, p.medical_conditions,
                       COUNT(mr.record_id) AS record_count,
                       MAX(mr.visit_date) AS last_visit_date
                FROM em_employees e
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions pos ON pos.position_id = e.position_id
                LEFT JOIN cm_patients p ON p.employee_id = e.employee_id
                LEFT JOIN cm_medical_records mr ON mr.patient_id = p.patient_id
                WHERE e.employee_id = :employee_id
                GROUP BY e.employee_id, e.first_name, e.last_name,
                             d.department_name, pos.position_name, e.employment_status, e.email,
                         p.patient_id, p.patient_type, p.status, p.blood_type,
                         p.allergies, p.medical_conditions
                LIMIT 1";

        $rows = $this->fetchAll($sql, [':employee_id' => $employeeId]);
        return $rows[0] ?? null;
    }

    public function getHistory(int $employeeId, string $dateFrom = '', string $dateTo = '', int $page = 1, int $perPage = 10): array
    {
        $sql = "SELECT mr.record_id, mr.patient_id, mr.visit_date,
                       mr.examination, mr.consultation_type, mr.status, mr.chief_complaint,
                       mr.diagnosis, mr.treatment, mr.attending_physician,
                       mr.medications_prescribed, mr.notes, mr.follow_up_date,
                       mr.vital_signs, mr.created_at
                FROM cm_medical_records mr
                INNER JOIN cm_patients p ON p.patient_id = mr.patient_id
                                WHERE p.employee_id = :employee_id";
        $params = [':employee_id' => $employeeId];

        if ($dateFrom !== '') {
            $sql .= ' AND mr.visit_date >= :date_from';
            $params[':date_from'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $sql .= ' AND mr.visit_date < DATE_ADD(:date_to, INTERVAL 1 DAY)';
            $params[':date_to'] = $dateTo;
        }

        $countSql = 'SELECT COUNT(*) FROM cm_medical_records mr INNER JOIN cm_patients p ON p.patient_id = mr.patient_id WHERE p.employee_id = :employee_id';
        if ($dateFrom !== '') $countSql .= ' AND mr.visit_date >= :date_from';
        if ($dateTo !== '') $countSql .= ' AND mr.visit_date < DATE_ADD(:date_to, INTERVAL 1 DAY)';
        $count = $this->conn->prepare($countSql); $count->execute($params); $total = (int) $count->fetchColumn();
        $page = max(1, $page); $offset = ($page - 1) * $perPage; $sql .= ' ORDER BY mr.visit_date DESC, mr.record_id DESC LIMIT :limit OFFSET :offset';
        $statement = $this->conn->prepare($sql); foreach ($params as $key => $value) $statement->bindValue($key, $value); $statement->bindValue(':limit', $perPage, PDO::PARAM_INT); $statement->bindValue(':offset', $offset, PDO::PARAM_INT); $statement->execute();
        return ['items' => $statement->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function getRecord(string $recordId, int $employeeId): ?array
    {
        $sql = "SELECT mr.record_id, mr.patient_id, mr.visit_date,
                       mr.examination, mr.consultation_type, mr.status, mr.chief_complaint,
                       mr.diagnosis, mr.treatment, mr.attending_physician,
                       mr.vital_signs, mr.medications_prescribed, mr.notes,
                       mr.follow_up_date, mr.created_at, mr.updated_at,
                       e.employee_id,
                       CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                       d.department_name AS department, pos.position_name AS position
                FROM cm_medical_records mr
                INNER JOIN cm_patients p ON p.patient_id = mr.patient_id
                INNER JOIN em_employees e ON e.employee_id = p.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions pos ON pos.position_id = e.position_id
                WHERE mr.record_id = :record_id AND e.employee_id = :employee_id
                LIMIT 1";

        $rows = $this->fetchAll($sql, [
            ':record_id' => $recordId,
            ':employee_id' => $employeeId,
        ]);
        return $rows[0] ?? null;
    }

    public function getPatientForEmployee(int $employeeId): ?array { $statement=$this->conn->prepare('SELECT p.patient_id FROM cm_patients p WHERE p.employee_id=:employee_id AND p.status="Active" LIMIT 1'); $statement->execute([':employee_id'=>$employeeId]); return $statement->fetch(PDO::FETCH_ASSOC) ?: null; }
    public function createRecord(array $data): int { $statement=$this->conn->prepare('INSERT INTO cm_medical_records (patient_id,visit_date,examination,consultation_type,chief_complaint,diagnosis,treatment,vital_signs,notes,status,created_by) VALUES (:patient_id,:visit_date,:examination,:consultation_type,:chief_complaint,:diagnosis,:treatment,:vital_signs,:notes,:status,:created_by)'); $statement->execute($this->recordParams($data)); return (int)$this->conn->lastInsertId(); }
    public function updateRecord(string $recordId,int $employeeId,array $data): bool { $params=$this->recordParams($data)+[':record_id'=>$recordId,':employee_id'=>$employeeId]; $statement=$this->conn->prepare('UPDATE cm_medical_records mr INNER JOIN cm_patients p ON p.patient_id=mr.patient_id SET mr.visit_date=:visit_date,mr.examination=:examination,mr.consultation_type=:consultation_type,mr.chief_complaint=:chief_complaint,mr.diagnosis=:diagnosis,mr.treatment=:treatment,mr.vital_signs=:vital_signs,mr.notes=:notes,mr.status=:status WHERE mr.record_id=:record_id AND p.employee_id=:employee_id'); return $statement->execute($params); }
    public function deleteRecord(string $recordId,int $employeeId): bool { $statement=$this->conn->prepare('DELETE mr FROM cm_medical_records mr INNER JOIN cm_patients p ON p.patient_id=mr.patient_id WHERE mr.record_id=:record_id AND p.employee_id=:employee_id'); $statement->execute([':record_id'=>$recordId,':employee_id'=>$employeeId]); return $statement->rowCount()>0; }
    private function recordParams(array $data): array { return [':patient_id'=>$data['patient_id'],':visit_date'=>$data['record_date'].' 00:00:00',':examination'=>$data['examination'],':consultation_type'=>'Walk-in',':chief_complaint'=>$data['chief_complaint'],':diagnosis'=>$data['diagnosis'],':treatment'=>$data['treatment'],':vital_signs'=>$data['vital_signs'],':notes'=>$data['notes'],':status'=>$data['status'],':created_by'=>$this->resolveCreatedBy($data['created_by']??null)]; }
    private function resolveCreatedBy(mixed $userId): ?int { $id = filter_var($userId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]); if ($id === false) return null; try { $statement = $this->conn->prepare('SELECT 1 FROM user_account WHERE user_id = :user_id LIMIT 1'); $statement->execute([':user_id' => $id]); return $statement->fetchColumn() ? (int)$id : null; } catch (Throwable $e) { return null; } }

    private function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->conn->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function ensureSchemaCompatibility(): void {
        try {
            $check = $this->conn->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="cm_medical_records" AND COLUMN_NAME="examination"');
            $check->execute();
            if ((int)$check->fetchColumn() === 0) {
                $this->conn->exec('ALTER TABLE cm_medical_records ADD COLUMN examination varchar(100) DEFAULT NULL AFTER visit_date');
            }
            $colCheck = $this->conn->prepare("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='cm_medical_records' AND COLUMN_NAME='status' LIMIT 1");
            $colCheck->execute();
            $currentType = (string)($colCheck->fetchColumn() ?: '');
            $requiredEnum = "enum('Completed','Pending','Follow-up','Follow-up Required')";
            if ($currentType !== $requiredEnum && stripos($currentType, 'Follow-up Required') === false) {
                $this->conn->exec("ALTER TABLE cm_medical_records MODIFY status enum('Completed','Pending','Follow-up','Follow-up Required') DEFAULT 'Pending'");
            }
        } catch (Throwable $e) {
            error_log('Medical record schema check skipped: ' . $e->getMessage());
        }
    }
}
