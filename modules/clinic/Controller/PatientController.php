<?php

require_once __DIR__ . '/../model/PatientModel.php';

class PatientController
{
    private PatientModel $model;

    public function __construct(?PDO $pdo = null)
    {
        $this->model = new PatientModel($pdo);
    }

    public function authorize(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $roleId = (int) ($_SESSION['role_id'] ?? $_SESSION['role'] ?? 0);
        $roleName = strtolower(trim((string) ($_SESSION['role_name'] ?? $_SESSION['role'] ?? '')));
        $roleName = preg_replace('/\s+/', ' ', $roleName) ?? $roleName;

        $isClinicRole = $roleId === 11 || str_contains($roleName, 'clinic');
        if (!$isClinicRole) {
            http_response_code(403);
            throw new RuntimeException('You are not authorized to manage employee patients.');
        }
    }

    public function searchEmployees(string $search = ''): array
    {
        return $this->model->searchEmployees($this->cleanSearch($search));
    }

    public function listPatients(string $search = ''): array
    {
        return $this->model->getPatients($this->cleanSearch($search));
    }

    public function getEmployee(array $input): ?array
    {
        $employeeId = $this->employeeId($input['employee_id'] ?? null);
        return $this->model->getEmployeeById($employeeId);
    }

    public function getPatient(array $input): ?array
    {
        $patientId = $this->patientId($input['patient_id'] ?? null);
        return $this->model->getPatientById($patientId);
    }

    public function registerEmployeeAsPatient(array $input): array
    {
        $employeeId = $this->employeeId($input['employee_id'] ?? null);
        $employee = $this->model->getEmployeeById($employeeId);
        if (!$employee) {
            throw new InvalidArgumentException('Employee not found.');
        }

        if ($this->model->getPatientByEmployeeId($employeeId)) {
            throw new RuntimeException('This employee is already registered as a patient.');
        }

        $patientData = $this->normalizePatientData($input, $employee);
        $patientId = $this->generatePatientId($employeeId);

        if (!$this->model->createPatient(array_merge($patientData, ['patient_id' => $patientId]))) {
            throw new RuntimeException('Unable to register the employee as a patient.');
        }

        return $this->model->getPatientById($patientId) ?? [];
    }

    public function savePatient(array $input): array
    {
        $patientId = trim((string) ($input['patient_id'] ?? ''));

        if ($patientId !== '') {
            return $this->updatePatient($input);
        }

        $employeeId = $this->employeeId($input['employee_id'] ?? null);
        $employee = $this->model->getEmployeeById($employeeId);
        if (!$employee) {
            throw new InvalidArgumentException('Employee not found.');
        }

        if ($this->model->getPatientByEmployeeId($employeeId)) {
            throw new RuntimeException('This employee is already registered as a patient.');
        }

        $normalized = $this->normalizePatientData($input, $employee);
        $patientId = $this->generatePatientId($employeeId);
        $normalized['patient_id'] = $patientId;

        if (!$this->model->createPatient($normalized)) {
            throw new RuntimeException('Unable to save the patient record.');
        }

        $saved = $this->model->getPatientById($patientId);
        if (!$saved) {
            throw new RuntimeException('Patient record could not be reloaded after saving.');
        }

        return $saved;
    }

    public function updatePatient(array $input): array
    {
        $patientId = $this->patientId($input['patient_id'] ?? null);
        $existing = $this->model->getPatientById($patientId);
        if (!$existing) {
            throw new InvalidArgumentException('Patient record not found.');
        }

        $employeeId = $existing['employee_id'] ?? null;
        if ($employeeId !== null && (int) $employeeId > 0) {
            $employee = $this->model->getEmployeeById((int) $employeeId);
            if ($employee) {
                $normalized = $this->normalizePatientData($input, $employee, $existing);
                $saved = $this->model->updatePatient($patientId, $normalized);
                if (!$saved) {
                    throw new RuntimeException('Unable to update patient information.');
                }
                $updated = $this->model->getPatientById($patientId);
                if (!$updated) {
                    throw new RuntimeException('Patient record could not be reloaded after update.');
                }
                return $updated;
            }
        }

        $normalized = $this->normalizePatientData($input, null, $existing);
        $saved = $this->model->updatePatient($patientId, $normalized);
        if (!$saved) {
            throw new RuntimeException('Unable to update patient information.');
        }
        return $this->model->getPatientById($patientId) ?? [];
    }

    public function deactivatePatient(array $input): array
    {
        $patientId = $this->patientId($input['patient_id'] ?? null);
        $patient = $this->model->getPatientById($patientId);
        if (!$patient) {
            throw new InvalidArgumentException('Patient record not found.');
        }

        if (!$this->model->deactivatePatient($patientId)) {
            throw new RuntimeException('Unable to deactivate patient record.');
        }

        return $this->model->getPatientById($patientId) ?? [];
    }

    private function normalizePatientData(array $input, ?array $employee = null, ?array $existing = null): array
    {
        $employeeFullName = trim((string) ($employee['full_name'] ?? ''));
        if (($input['first_name'] ?? '') === '' && $employeeFullName !== '' && ($employee['first_name'] ?? '') === '') {
            [$firstNameFromFullName, $lastNameFromFullName] = $this->splitFullName($employeeFullName);
            $input['first_name'] = $firstNameFromFullName;
            $input['last_name'] = $lastNameFromFullName;
        }

        $firstName = trim((string) ($input['first_name'] ?? ($employee['first_name'] ?? $existing['first_name'] ?? '')));
        $lastName = trim((string) ($input['last_name'] ?? ($employee['last_name'] ?? $existing['last_name'] ?? '')));
        $middleName = trim((string) ($input['middle_name'] ?? ($employee['middle_name'] ?? $existing['middle_name'] ?? '')));
        $email = trim((string) ($input['email'] ?? ($employee['email'] ?? $existing['email'] ?? '')));
        $phone = trim((string) ($input['phone'] ?? ($existing['phone'] ?? '')));
        $address = trim((string) ($input['address'] ?? ($existing['address'] ?? '')));
        $birthDate = trim((string) ($input['birth_date'] ?? ($existing['birth_date'] ?? '')));
        $gender = trim((string) ($input['gender'] ?? ($existing['gender'] ?? '')));
        $bloodType = trim((string) ($input['blood_type'] ?? ($existing['blood_type'] ?? '')));
        $allergies = trim((string) ($input['allergies'] ?? ($existing['allergies'] ?? '')));
        $medicalConditions = trim((string) ($input['medical_conditions'] ?? ($existing['medical_conditions'] ?? '')));
        $currentMedications = trim((string) ($input['current_medications'] ?? ($existing['current_medications'] ?? '')));
        $patientType = trim((string) ($input['patient_type'] ?? ($existing['patient_type'] ?? 'Staff')));
        $status = trim((string) ($input['status'] ?? ($existing['status'] ?? 'Active')));

        if ($firstName === '' || $lastName === '') {
            throw new InvalidArgumentException('First name and last name are required.');
        }

        if ($patientType === '') {
            $patientType = 'Staff';
        }
        if ($status === '') {
            $status = 'Active';
        }

        $gender = $gender !== '' ? $gender : 'Other';

        return [
            'employee_id' => $employee['employee_id'] ?? ($existing['employee_id'] ?? null),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $middleName !== '' ? $middleName : null,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
            'birth_date' => $this->validateDate($birthDate),
            'gender' => $this->validateGender($gender),
            'blood_type' => $bloodType !== '' ? $bloodType : null,
            'allergies' => $allergies !== '' ? $allergies : null,
            'medical_conditions' => $medicalConditions !== '' ? $medicalConditions : null,
            'current_medications' => $currentMedications !== '' ? $currentMedications : null,
            'patient_type' => $this->validatePatientType($patientType),
            'status' => $this->validateStatus($status),
        ];
    }

    private function employeeId(mixed $value): int
    {
        $employeeId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($employeeId === false) {
            throw new InvalidArgumentException('A valid employee is required.');
        }
        return $employeeId;
    }

    private function patientId(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || strlen($value) > 50) {
            throw new InvalidArgumentException('A valid patient ID is required.');
        }
        return $value;
    }

    private function cleanSearch(mixed $value): string
    {
        return substr(trim((string) $value), 0, 100);
    }

    private function generatePatientId(int $employeeId): string
    {
        $dateStamp = date('Ymd');
        return 'PAT-' . $dateStamp . '-' . str_pad((string) $employeeId, 6, '0', STR_PAD_LEFT);
    }

    private function splitFullName(string $fullName): array
    {
        $trimmed = trim($fullName);
        if ($trimmed === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts || count($parts) === 0) {
            return [$trimmed, ''];
        }

        $firstName = array_shift($parts);
        $lastName = implode(' ', $parts);
        return [$firstName, $lastName];
    }

    private function validateDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Birth date must be in YYYY-MM-DD format.');
        }
        return $value;
    }

    private function validateGender(string $value): string
    {
        $allowed = ['Male', 'Female', 'Other'];
        $normalized = trim($value);
        if ($normalized === '') {
            return 'Other';
        }
        if (!in_array($normalized, $allowed, true)) {
            throw new InvalidArgumentException('Gender must be Male, Female, or Other.');
        }
        return $normalized;
    }

    private function validatePatientType(string $value): string
    {
        $allowed = ['Student', 'Staff', 'Faculty', 'Visitor'];
        $normalized = trim($value);
        if (!in_array($normalized, $allowed, true)) {
            throw new InvalidArgumentException('Patient type is invalid.');
        }
        return $normalized;
    }

    private function validateStatus(string $value): string
    {
        $allowed = ['Active', 'Inactive'];
        $normalized = trim($value);
        if (!in_array($normalized, $allowed, true)) {
            throw new InvalidArgumentException('Status must be Active or Inactive.');
        }
        return $normalized;
    }
}
