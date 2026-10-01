<?php

require_once __DIR__ . '/../model/MedicineInventoryModel.php';

class MedicineInventoryController
{
    private MedicineInventoryModel $model;

    public function __construct(?PDO $pdo = null)
    {
        $this->model = new MedicineInventoryModel($pdo);
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
            throw new RuntimeException('You are not authorized to manage medicine inventory.');
        }
    }

    public function listMedicines(array $input): array
    {
        $search = trim((string) ($input['search'] ?? ''));
        $filter = $this->filterValue($input['filter'] ?? 'all');
        return $this->model->listMedicines($search, $filter);
    }

    public function getMedicine(array $input): ?array
    {
        $medicineId = $this->medicineId($input['medicine_id'] ?? null);
        return $this->model->getMedicineById($medicineId);
    }

    public function getSuppliers(): array
    {
        return $this->model->getSuppliers();
    }

    public function saveMedicine(array $input): array
    {
        $medicineId = trim((string) ($input['medicine_id'] ?? ''));
        $data = $this->normalizeMedicineData($input);

        if ($medicineId === '') {
            $medicineId = $data['medicine_id'];
        }

        if ($this->model->getMedicineById($medicineId)) {
            $updated = $this->model->updateMedicine($medicineId, $data);
            if (!$updated) {
                throw new RuntimeException('Unable to update the medicine record.');
            }

            $saved = $this->model->getMedicineById($medicineId);
            if (!$saved) {
                throw new RuntimeException('Medicine record could not be reloaded after update.');
            }
            return $saved;
        }

        if (!$this->model->createMedicine($data)) {
            throw new RuntimeException('Unable to save the medicine record.');
        }

        $saved = $this->model->getMedicineById($data['medicine_id']);
        if (!$saved) {
            throw new RuntimeException('Medicine record could not be reloaded after save.');
        }

        return $saved;
    }

    public function deactivateMedicine(array $input): array
    {
        $medicineId = $this->medicineId($input['medicine_id'] ?? null);
        $medicine = $this->model->getMedicineById($medicineId);
        if (!$medicine) {
            throw new InvalidArgumentException('Medicine record not found.');
        }

        if (!$this->model->deactivateMedicine($medicineId)) {
            throw new RuntimeException('Unable to deactivate medicine record.');
        }

        $updated = $this->model->getMedicineById($medicineId);
        if (!$updated) {
            throw new RuntimeException('Medicine record could not be reloaded after deactivation.');
        }

        return $updated;
    }

    public function updateStock(array $input): array
    {
        $medicineId = $this->medicineId($input['medicine_id'] ?? null);
        $medicine = $this->model->getMedicineById($medicineId);
        if (!$medicine) {
            throw new InvalidArgumentException('Medicine record not found.');
        }

        $newStock = filter_var($input['current_stock'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($newStock === false) {
            throw new InvalidArgumentException('Stock quantity must be a valid non-negative integer.');
        }

        if (!$this->model->updateStock($medicineId, $newStock)) {
            throw new RuntimeException('Unable to update medicine stock.');
        }

        $updated = $this->model->getMedicineById($medicineId);
        if (!$updated) {
            throw new RuntimeException('Medicine stock could not be reloaded after update.');
        }

        return $updated;
    }

    private function normalizeMedicineData(array $input): array
    {
        $medicineId = trim((string) ($input['medicine_id'] ?? ''));
        if ($medicineId === '') {
            throw new InvalidArgumentException('Medicine ID is required.');
        }

        $medicineName = trim((string) ($input['medicine_name'] ?? ''));
        $genericName = trim((string) ($input['generic_name'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));
        $dosageForm = trim((string) ($input['dosage_form'] ?? ''));
        $strength = trim((string) ($input['strength'] ?? ''));
        $unit = trim((string) ($input['unit'] ?? 'pcs'));
        $currentStock = filter_var($input['current_stock'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $reorderLevel = filter_var($input['reorder_level'] ?? 10, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $unitCost = $this->decimal($input['unit_cost'] ?? null);
        $sellingPrice = $this->decimal($input['selling_price'] ?? null);
        $expiryDate = $this->dateOrNull($input['expiry_date'] ?? null);
        $supplier = trim((string) ($input['supplier'] ?? ''));
        $manufacturer = trim((string) ($input['manufacturer'] ?? ''));
        $storageRequirements = trim((string) ($input['storage_requirements'] ?? ''));
        $status = trim((string) ($input['status'] ?? ''));

        if ($medicineName === '') {
            throw new InvalidArgumentException('Medicine name is required.');
        }

        if ($currentStock === false) {
            throw new InvalidArgumentException('Current stock must be a non-negative integer.');
        }

        if ($reorderLevel === false) {
            throw new InvalidArgumentException('Reorder level must be a non-negative integer.');
        }

        if ($unit === '') {
            $unit = 'pcs';
        }

        if ($status === '') {
            $status = $this->model->calculateStatus((int) $currentStock, (int) $reorderLevel, $expiryDate, 'Available');
        } else {
            $status = $this->validateStatus($status);
        }

        return [
            'medicine_id' => $this->medicineId($medicineId),
            'medicine_name' => $medicineName,
            'generic_name' => $genericName !== '' ? $genericName : null,
            'category' => $category !== '' ? $category : null,
            'dosage_form' => $dosageForm !== '' ? $this->validateDosageForm($dosageForm) : null,
            'strength' => $strength !== '' ? $strength : null,
            'unit' => $unit,
            'current_stock' => (int) $currentStock,
            'reorder_level' => (int) $reorderLevel,
            'unit_cost' => $unitCost,
            'selling_price' => $sellingPrice,
            'expiry_date' => $expiryDate,
            'supplier' => $supplier !== '' ? $supplier : null,
            'manufacturer' => $manufacturer !== '' ? $manufacturer : null,
            'storage_requirements' => $storageRequirements !== '' ? $storageRequirements : null,
            'status' => $status,
            'created_by' => $_SESSION['user_id'] ?? null,
        ];
    }

    private function medicineId(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || strlen($value) > 20) {
            throw new InvalidArgumentException('A valid medicine ID is required.');
        }
        return $value;
    }

    private function filterValue(mixed $value): string
    {
        $value = strtolower(trim((string) $value));
        $allowed = ['all', 'active', 'inactive', 'low_stock', 'expired', 'near_expiration'];
        return in_array($value, $allowed, true) ? $value : 'all';
    }

    private function decimal(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $amount = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($amount === false) {
            throw new InvalidArgumentException('Price values must be valid numbers.');
        }

        return number_format((float) $amount, 2, '.', '');
    }

    private function dateOrNull(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim((string) $value));
        if (!$date || $date->format('Y-m-d') !== trim((string) $value)) {
            throw new InvalidArgumentException('Expiration date must be in YYYY-MM-DD format.');
        }

        return $date->format('Y-m-d');
    }

    private function validateDosageForm(string $value): string
    {
        $allowed = ['Tablet', 'Capsule', 'Liquid', 'Injection', 'Ointment', 'Other'];
        $normalized = trim($value);
        if ($normalized !== '' && !in_array($normalized, $allowed, true)) {
            throw new InvalidArgumentException('Dosage form is invalid.');
        }
        return $normalized;
    }

    private function validateStatus(string $value): string
    {
        $allowed = ['Available', 'Low Stock', 'Out of Stock', 'Expired', 'Expiring Soon', 'Inactive'];
        $normalized = trim($value);
        if (!in_array($normalized, $allowed, true)) {
            throw new InvalidArgumentException('Medicine status is invalid.');
        }
        return $normalized;
    }
}
