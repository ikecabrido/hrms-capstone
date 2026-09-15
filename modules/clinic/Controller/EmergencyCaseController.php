<?php

require_once __DIR__ . '/../model/EmergencyCaseModel.php';

class EmergencyCaseController
{
	private EmergencyCaseModel $model;

	public function __construct(?PDO $pdo = null)
	{
		$this->model = new EmergencyCaseModel($pdo);
	}

	public function authorize(): void
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		$roleId = (int) ($_SESSION['role_id'] ?? $_SESSION['role'] ?? 0);
		$roleName = strtolower(trim((string) ($_SESSION['role_name'] ?? $_SESSION['role'] ?? '')));
		$roleName = preg_replace('/\s+/', ' ', $roleName) ?? $roleName;
		$isAuthorized = $roleId === 11 || str_contains($roleName, 'clinic') || str_contains($roleName, 'admin');
		if (!$isAuthorized) {
			http_response_code(403);
			throw new RuntimeException('You are not authorized to view emergency cases.');
		}
	}

	public function listCases(array $input): array
	{
		$dateFrom = $this->date($input['date_from'] ?? '');
		$dateTo = $this->date($input['date_to'] ?? '');
		if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
			throw new InvalidArgumentException('The start date must be before the end date.');
		}

		return $this->model->list([
			'search' => $this->text($input['search'] ?? '', 100),
			'status' => $this->enum($input['status'] ?? '', ['Active', 'Open', 'Resolved', 'Transferred', 'Closed']),
			'severity' => $this->enum($input['severity'] ?? '', ['Minor', 'Low', 'Medium', 'High', 'Critical']),
			'incident_type' => $this->enum($input['incident_type'] ?? '', ['Accident', 'Medical Emergency', 'Injury', 'Other', 'Illness', 'Fainting', 'Allergic Reaction']),
			'department' => $this->text($input['department'] ?? '', 100),
			'date_from' => $dateFrom,
			'date_to' => $dateTo,
			'sort' => $this->enum($input['sort'] ?? 'date', ['date', 'employee', 'status']) ?: 'date',
		]);
	}

	public function getCase(mixed $caseId): array
	{
		$id = $this->id($caseId, 'case');
		$case = $this->model->getById($id);
		if (!$case) {
			throw new RuntimeException('Emergency case not found.');
		}
		return $case;
	}

	public function patients(mixed $search = ''): array
	{
		return $this->model->getPatients($this->text($search, 100));
	}

	public function patient(mixed $patientId): array
	{
		$patient = $this->model->getPatientById($this->id($patientId, 'patient'));
		if (!$patient) {
			throw new RuntimeException('Patient not found or inactive.');
		}
		return $patient;
	}

	public function create(array $input): int
	{
		return $this->model->create($this->validatedCase($input));
	}

	public function update(array $input): bool
	{
		$caseId = $this->id($input['case_id'] ?? null, 'case');
		if (!$this->model->getById($caseId)) {
			throw new RuntimeException('Emergency case not found.');
		}
		return $this->model->update($caseId, $this->validatedCase($input));
	}

	public function delete(mixed $caseId): bool
	{
		$id = $this->id($caseId, 'case');
		if (!$this->model->getById($id)) {
			throw new RuntimeException('Emergency case not found.');
		}
		return $this->model->delete($id);
	}

	public function close(mixed $caseId): bool
	{
		$id = $this->id($caseId, 'case');
		if (!$this->model->getById($id)) {
			throw new RuntimeException('Emergency case not found.');
		}
		return $this->model->close($id);
	}

	private function validatedCase(array $input): array
	{
		$patientId = $this->id($input['patient_id'] ?? null, 'patient');
		if (!$this->model->getPatientById($patientId)) {
			throw new InvalidArgumentException('Please select an active patient.');
		}

		$chiefComplaint = $this->text($input['chief_complaint'] ?? '', 5000);
		$attendingStaff = $this->text($input['attending_staff'] ?? '', 255);
		if ($chiefComplaint === '') {
			throw new InvalidArgumentException('Chief complaint is required.');
		}
		if ($attendingStaff === '') {
			throw new InvalidArgumentException('Attending medical staff is required.');
		}

		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		return [
			'patient_id' => $patientId,
			'incident_date' => $this->dateTime($input['incident_date'] ?? '', true),
			'incident_type' => $this->enumValue($input['incident_type'] ?? null, ['Accident', 'Medical Emergency', 'Injury', 'Other', 'Illness', 'Fainting', 'Allergic Reaction'], 'Other', 'emergency type'),
			'severity_level' => $this->enumValue($input['severity_level'] ?? null, ['Minor', 'Low', 'Medium', 'High', 'Critical'], 'Medium', 'severity'),
			'chief_complaint' => $chiefComplaint,
			'initial_assessment' => $this->text($input['initial_assessment'] ?? '', 5000),
			'treatment_provided' => $this->text($input['treatment_provided'] ?? '', 5000),
			'attending_staff' => $attendingStaff,
			'case_status' => $this->enumValue($input['case_status'] ?? null, ['Active', 'Open', 'Resolved', 'Transferred', 'Closed'], 'Active', 'case status'),
			'ambulance_called' => $this->boolean($input['ambulance_called'] ?? false),
			'ambulance_arrival_time' => $this->dateTime($input['ambulance_arrival_time'] ?? '', false),
			'parents_notified' => $this->boolean($input['parents_notified'] ?? false),
			'parent_notification_time' => $this->dateTime($input['parent_notification_time'] ?? '', false),
			'witness_names' => $this->text($input['witness_names'] ?? '', 500),
			'transfer_hospital' => $this->text($input['transfer_hospital'] ?? '', 200),
			'follow_up_required' => $this->boolean($input['follow_up_required'] ?? false),
			'follow_up_date' => $this->date($input['follow_up_date'] ?? ''),
			'contact_person' => $this->text($input['contact_person'] ?? '', 150),
			'contact_phone' => $this->text($input['contact_phone'] ?? '', 20),
			'notes' => $this->text($input['notes'] ?? '', 5000),
			'created_by' => $_SESSION['user_id'] ?? null,
		];
	}

	private function id(mixed $value, string $label): int
	{
		$id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		if ($id === false) {
			throw new InvalidArgumentException("A valid {$label} ID is required.");
		}
		return (int) $id;
	}

	private function text(mixed $value, int $maxLength): string
	{
		return substr(trim((string) $value), 0, $maxLength);
	}

	private function enum(mixed $value, array $allowed): string
	{
		$value = trim((string) $value);
		return in_array($value, $allowed, true) ? $value : '';
	}

	private function enumValue(mixed $value, array $allowed, string $default, string $label): string
	{
		$value = trim((string) $value);
		if ($value === '') {
			return $default;
		}
		if (!in_array($value, $allowed, true)) {
			throw new InvalidArgumentException("Invalid {$label}.");
		}
		return $value;
	}

	private function boolean(mixed $value): int
	{
		return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
	}

	private function date(mixed $value): string
	{
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}
		$date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
		if (!$date || $date->format('Y-m-d') !== $value) {
			throw new InvalidArgumentException('Invalid date filter.');
		}
		return $value;
	}

	private function dateTime(mixed $value, bool $required): ?string
	{
		$value = trim((string) $value);
		if ($value === '') {
			if ($required) {
				throw new InvalidArgumentException('Incident date and time are required.');
			}
			return null;
		}
		$date = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $value)
			?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
		if (!$date) {
			throw new InvalidArgumentException('Invalid incident date and time.');
		}
		return $date->format('Y-m-d H:i:s');
	}
}
