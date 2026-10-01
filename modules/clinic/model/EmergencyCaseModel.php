<?php

require_once __DIR__ . '/../../../database/db.php';

class EmergencyCaseModel
{
	private PDO $conn;

	public function __construct(?PDO $pdo = null)
	{
		$this->conn = $pdo instanceof PDO ? $pdo : (new Database())->getConnection();
	}

	public function list(array $filters = []): array
	{
		$sql = "SELECT ec.*, p.employee_id,
					   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS patient_name,
					   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
					   COALESCE(d.department_name, 'N/A') AS department,
					   COALESCE(pos.position_name, 'N/A') AS position,
					   COALESCE(e.mobile_no, e.phone_no, '') AS contact_number
				FROM cm_emergency_cases ec
				INNER JOIN cm_patients p ON p.patient_id = ec.patient_id
				LEFT JOIN em_employees e ON e.employee_id = p.employee_id
				LEFT JOIN em_departments d ON d.department_id = e.department_id
				LEFT JOIN em_positions pos ON pos.position_id = e.position_id
				WHERE 1 = 1";
		$params = [];

		$search = trim((string) ($filters['search'] ?? ''));
		if ($search !== '') {
			$sql .= " AND (CAST(ec.case_id AS CHAR) LIKE :search
					   OR CAST(p.patient_id AS CHAR) LIKE :search
					   OR CAST(p.employee_id AS CHAR) LIKE :search
					   OR CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) LIKE :search
					   OR COALESCE(d.department_name, '') LIKE :search
					   OR COALESCE(pos.position_name, '') LIKE :search
					   OR COALESCE(ec.chief_complaint, '') LIKE :search)";
			$params[':search'] = '%' . $search . '%';
		}

		$this->addFilter($sql, $params, 'ec.case_status', 'status', $filters['status'] ?? '');
		$this->addFilter($sql, $params, 'ec.severity_level', 'severity', $filters['severity'] ?? '');
		$this->addFilter($sql, $params, 'ec.incident_type', 'incident_type', $filters['incident_type'] ?? '');
		$this->addFilter($sql, $params, 'd.department_name', 'department', $filters['department'] ?? '');

		$dateFrom = trim((string) ($filters['date_from'] ?? ''));
		if ($dateFrom !== '') {
			$sql .= ' AND ec.incident_date >= :date_from';
			$params[':date_from'] = $dateFrom . ' 00:00:00';
		}
		$dateTo = trim((string) ($filters['date_to'] ?? ''));
		if ($dateTo !== '') {
			$sql .= ' AND ec.incident_date < DATE_ADD(:date_to, INTERVAL 1 DAY)';
			$params[':date_to'] = $dateTo;
		}

		$sort = (string) ($filters['sort'] ?? 'date');
		$orderBy = match ($sort) {
			'employee' => 'patient_name ASC, ec.incident_date DESC',
			'status' => 'ec.case_status ASC, ec.incident_date DESC',
			default => 'ec.incident_date DESC, ec.case_id DESC',
		};
		$sql .= " ORDER BY {$orderBy} LIMIT 200";

		return $this->fetchAll($sql, $params);
	}

	public function getById(int $caseId): ?array
	{
		$sql = "SELECT ec.*, p.employee_id,
					   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS patient_name,
					   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
					   COALESCE(d.department_name, 'N/A') AS department,
					   COALESCE(pos.position_name, 'N/A') AS position,
					   COALESCE(e.mobile_no, e.phone_no, '') AS contact_number
				FROM cm_emergency_cases ec
				INNER JOIN cm_patients p ON p.patient_id = ec.patient_id
				LEFT JOIN em_employees e ON e.employee_id = p.employee_id
				LEFT JOIN em_departments d ON d.department_id = e.department_id
				LEFT JOIN em_positions pos ON pos.position_id = e.position_id
				WHERE ec.case_id = :case_id LIMIT 1";
		$rows = $this->fetchAll($sql, [':case_id' => $caseId]);
		return $rows[0] ?? null;
	}

	public function getPatientById(int $patientId): ?array
	{
		$sql = "SELECT p.patient_id, p.employee_id,
					   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS patient_name,
					   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
					   COALESCE(d.department_name, 'N/A') AS department,
					   COALESCE(pos.position_name, 'N/A') AS position,
					   COALESCE(e.mobile_no, e.phone_no, '') AS phone
				FROM cm_patients p
				LEFT JOIN em_employees e ON e.employee_id = p.employee_id
				LEFT JOIN em_departments d ON d.department_id = e.department_id
				LEFT JOIN em_positions pos ON pos.position_id = e.position_id
				WHERE p.patient_id = :patient_id AND p.status = 'Active' LIMIT 1";
		$rows = $this->fetchAll($sql, [':patient_id' => $patientId]);
		return $rows[0] ?? null;
	}

	public function getPatients(string $search = ''): array
	{
		$sql = "SELECT p.patient_id, p.employee_id,
					   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS patient_name,
					   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
					   COALESCE(d.department_name, 'N/A') AS department,
					   COALESCE(pos.position_name, 'N/A') AS position,
					   COALESCE(e.mobile_no, e.phone_no, '') AS phone
				FROM cm_patients p
				LEFT JOIN em_employees e ON e.employee_id = p.employee_id
				LEFT JOIN em_departments d ON d.department_id = e.department_id
				LEFT JOIN em_positions pos ON pos.position_id = e.position_id
				WHERE p.status = 'Active'";
		$params = [];
		if ($search !== '') {
			$sql .= " AND (CAST(p.patient_id AS CHAR) LIKE :search
					   OR CAST(p.employee_id AS CHAR) LIKE :search
					   OR CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) LIKE :search
					   OR COALESCE(d.department_name, '') LIKE :search
					   OR COALESCE(pos.position_name, '') LIKE :search)";
			$params[':search'] = '%' . trim($search) . '%';
		}
		$sql .= ' ORDER BY patient_name ASC, p.patient_id ASC LIMIT 50';
		return $this->fetchAll($sql, $params);
	}

	public function create(array $data): int
	{
		$sql = "INSERT INTO cm_emergency_cases (
					patient_id, incident_date, incident_type, severity_level, chief_complaint,
					initial_assessment, treatment_provided, attending_staff, case_status,
					ambulance_called, ambulance_arrival_time, parents_notified, parent_notification_time,
					witness_names, transfer_hospital, follow_up_required, follow_up_date,
					contact_person, contact_phone, notes, created_by
				) VALUES (
					:patient_id, :incident_date, :incident_type, :severity_level, :chief_complaint,
					:initial_assessment, :treatment_provided, :attending_staff, :case_status,
					:ambulance_called, :ambulance_arrival_time, :parents_notified, :parent_notification_time,
					:witness_names, :transfer_hospital, :follow_up_required, :follow_up_date,
					:contact_person, :contact_phone, :notes, :created_by
				)";
		$statement = $this->conn->prepare($sql);
		$statement->execute($this->caseParams($data));
		return (int) $this->conn->lastInsertId();
	}

	public function update(int $caseId, array $data): bool
	{
		$sql = "UPDATE cm_emergency_cases SET
					patient_id = :patient_id, incident_date = :incident_date, incident_type = :incident_type,
					severity_level = :severity_level, chief_complaint = :chief_complaint,
					initial_assessment = :initial_assessment, treatment_provided = :treatment_provided,
					attending_staff = :attending_staff, case_status = :case_status,
					ambulance_called = :ambulance_called, ambulance_arrival_time = :ambulance_arrival_time,
					parents_notified = :parents_notified, parent_notification_time = :parent_notification_time,
					witness_names = :witness_names, transfer_hospital = :transfer_hospital,
					follow_up_required = :follow_up_required, follow_up_date = :follow_up_date,
					contact_person = :contact_person, contact_phone = :contact_phone, notes = :notes
				WHERE case_id = :case_id";
		$params = $this->caseParams($data);
		$params[':case_id'] = $caseId;
		return $this->conn->prepare($sql)->execute($params);
	}

	public function delete(int $caseId): bool
	{
		return $this->conn->prepare('DELETE FROM cm_emergency_cases WHERE case_id = :case_id')->execute([':case_id' => $caseId]);
	}

	public function close(int $caseId): bool
	{
		return $this->conn->prepare("UPDATE cm_emergency_cases SET case_status = 'Closed' WHERE case_id = :case_id")->execute([':case_id' => $caseId]);
	}

	private function addFilter(string &$sql, array &$params, string $column, string $name, mixed $value): void
	{
		$value = trim((string) $value);
		if ($value !== '') {
			$sql .= " AND {$column} = :{$name}";
			$params[":{$name}"] = $value;
		}
	}

	private function caseParams(array $data): array
	{
		return [
			':patient_id' => $data['patient_id'],
			':incident_date' => $data['incident_date'],
			':incident_type' => $data['incident_type'],
			':severity_level' => $data['severity_level'],
			':chief_complaint' => $data['chief_complaint'],
			':initial_assessment' => $data['initial_assessment'],
			':treatment_provided' => $data['treatment_provided'],
			':attending_staff' => $data['attending_staff'],
			':case_status' => $data['case_status'],
			':ambulance_called' => $data['ambulance_called'],
			':ambulance_arrival_time' => $data['ambulance_arrival_time'],
			':parents_notified' => $data['parents_notified'],
			':parent_notification_time' => $data['parent_notification_time'],
			':witness_names' => $data['witness_names'],
			':transfer_hospital' => $data['transfer_hospital'],
			':follow_up_required' => $data['follow_up_required'],
			':follow_up_date' => $data['follow_up_date'],
			':contact_person' => $data['contact_person'],
			':contact_phone' => $data['contact_phone'],
			':notes' => $data['notes'],
			':created_by' => $this->resolveCreatedBy($data['created_by'] ?? null),
		];
	}

	private function resolveCreatedBy(mixed $userId): ?int
	{
		$id = filter_var($userId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		if ($id === false) return null;
		try {
			$statement = $this->conn->prepare('SELECT 1 FROM user_account WHERE user_id = :user_id LIMIT 1');
			$statement->execute([':user_id' => $id]);
			return $statement->fetchColumn() ? (int)$id : null;
		} catch (Throwable $e) {
			return null;
		}
	}

	private function fetchAll(string $sql, array $params = []): array
	{
		$statement = $this->conn->prepare($sql);
		$statement->execute($params);
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}
}
