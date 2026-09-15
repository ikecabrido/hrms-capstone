<?php

require_once __DIR__ . '/../../../database/db.php';

class MedicineInventoryModel
{
    private PDO $conn;

    public function __construct(?PDO $pdo = null)
    {
        $this->conn = $pdo instanceof PDO ? $pdo : (new Database())->getConnection();
    }

    public function listMedicines(string $search = '', string $filter = 'all'): array
    {
        $sql = "SELECT m.*
                FROM cm_medicine_inventory m
                WHERE 1 = 1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                m.medicine_id LIKE :search
                OR m.medicine_name LIKE :search
                OR COALESCE(m.generic_name, '') LIKE :search
                OR COALESCE(m.category, '') LIKE :search
            )";
            $params[':search'] = '%' . $search . '%';
        }

        if ($filter !== '' && $filter !== 'all') {
            switch ($filter) {
                case 'active':
                    $sql .= " AND m.status NOT IN ('Inactive')";
                    break;
                case 'inactive':
                    $sql .= " AND m.status = 'Inactive'";
                    break;
                case 'low_stock':
                    $sql .= " AND m.current_stock <= m.reorder_level AND m.status != 'Inactive'";
                    break;
                case 'expired':
                    $sql .= " AND (m.expiry_date < CURDATE() OR m.status = 'Expired')";
                    break;
                case 'near_expiration':
                    $sql .= " AND m.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND m.status != 'Inactive'";
                    break;
                default:
                    $sql .= " AND m.status = :status_filter";
                    $params[':status_filter'] = ucfirst(str_replace('_', ' ', $filter));
                    break;
            }
        }

        $sql .= " ORDER BY m.medicine_name ASC, m.medicine_id ASC";
        return $this->fetchAll($sql, $params);
    }

    public function getExpiredAlertMedicines(): array
    {
        $sql = "SELECT medicine_id, medicine_name, generic_name, category, current_stock, reorder_level, expiry_date, status
                FROM cm_medicine_inventory
                WHERE current_stock > 0 AND expiry_date IS NOT NULL AND expiry_date < CURDATE()
                ORDER BY expiry_date ASC, medicine_name ASC";
        return $this->fetchAll($sql);
    }

    public function getLowStockAlertMedicines(): array
    {
        $sql = "SELECT medicine_id, medicine_name, generic_name, category, current_stock, reorder_level, expiry_date, status
                FROM cm_medicine_inventory
                WHERE current_stock > 0 AND current_stock <= reorder_level
                ORDER BY current_stock ASC, medicine_name ASC";
        return $this->fetchAll($sql);
    }

    public function getMedicineById(string $medicineId): ?array
    {
        $sql = "SELECT *
                FROM cm_medicine_inventory
                WHERE medicine_id = :medicine_id
                LIMIT 1";

        $rows = $this->fetchAll($sql, [':medicine_id' => $medicineId]);
        return $rows[0] ?? null;
    }

    public function getSuppliers(): array
    {
        $sql = "SELECT supplier_id, supplier_name
                FROM cm_suppliers
                ORDER BY supplier_name ASC";

        return $this->fetchAll($sql);
    }

    public function createMedicine(array $data): bool
    {
        $sql = "INSERT INTO cm_medicine_inventory (
                    medicine_id,
                    medicine_name,
                    generic_name,
                    category,
                    dosage_form,
                    strength,
                    unit,
                    current_stock,
                    reorder_level,
                    unit_cost,
                    selling_price,
                    expiry_date,
                    supplier,
                    manufacturer,
                    storage_requirements,
                    status,
                    created_by,
                    created_at,
                    updated_at
                ) VALUES (
                    :medicine_id,
                    :medicine_name,
                    :generic_name,
                    :category,
                    :dosage_form,
                    :strength,
                    :unit,
                    :current_stock,
                    :reorder_level,
                    :unit_cost,
                    :selling_price,
                    :expiry_date,
                    :supplier,
                    :manufacturer,
                    :storage_requirements,
                    :status,
                    :created_by,
                    NOW(),
                    NOW()
                )";

        $statement = $this->conn->prepare($sql);
        return $statement->execute([
            ':medicine_id' => $data['medicine_id'],
            ':medicine_name' => $data['medicine_name'],
            ':generic_name' => $data['generic_name'] ?? null,
            ':category' => $data['category'] ?? null,
            ':dosage_form' => $data['dosage_form'] ?? null,
            ':strength' => $data['strength'] ?? null,
            ':unit' => $data['unit'] ?? 'pcs',
            ':current_stock' => $data['current_stock'] ?? 0,
            ':reorder_level' => $data['reorder_level'] ?? 10,
            ':unit_cost' => $data['unit_cost'] ?? null,
            ':selling_price' => $data['selling_price'] ?? null,
            ':expiry_date' => $data['expiry_date'] ?? null,
            ':supplier' => $data['supplier'] ?? null,
            ':manufacturer' => $data['manufacturer'] ?? null,
            ':storage_requirements' => $data['storage_requirements'] ?? null,
            ':status' => $data['status'] ?? 'Available',
            ':created_by' => $this->resolveCreatedBy($data['created_by'] ?? null),
        ]);
    }

    public function updateMedicine(string $medicineId, array $data): bool
    {
        $sql = "UPDATE cm_medicine_inventory
                SET medicine_name = :medicine_name,
                    generic_name = :generic_name,
                    category = :category,
                    dosage_form = :dosage_form,
                    strength = :strength,
                    unit = :unit,
                    current_stock = :current_stock,
                    reorder_level = :reorder_level,
                    unit_cost = :unit_cost,
                    selling_price = :selling_price,
                    expiry_date = :expiry_date,
                    supplier = :supplier,
                    manufacturer = :manufacturer,
                    storage_requirements = :storage_requirements,
                    status = :status,
                    updated_at = NOW()
                WHERE medicine_id = :medicine_id";

        $statement = $this->conn->prepare($sql);
        return $statement->execute([
            ':medicine_id' => $medicineId,
            ':medicine_name' => $data['medicine_name'],
            ':generic_name' => $data['generic_name'] ?? null,
            ':category' => $data['category'] ?? null,
            ':dosage_form' => $data['dosage_form'] ?? null,
            ':strength' => $data['strength'] ?? null,
            ':unit' => $data['unit'] ?? 'pcs',
            ':current_stock' => $data['current_stock'] ?? 0,
            ':reorder_level' => $data['reorder_level'] ?? 10,
            ':unit_cost' => $data['unit_cost'] ?? null,
            ':selling_price' => $data['selling_price'] ?? null,
            ':expiry_date' => $data['expiry_date'] ?? null,
            ':supplier' => $data['supplier'] ?? null,
            ':manufacturer' => $data['manufacturer'] ?? null,
            ':storage_requirements' => $data['storage_requirements'] ?? null,
            ':status' => $data['status'] ?? 'Available',
        ]);
    }

    public function deactivateMedicine(string $medicineId): bool
    {
        $sql = "UPDATE cm_medicine_inventory
                SET status = 'Inactive', updated_at = NOW()
                WHERE medicine_id = :medicine_id";

        $statement = $this->conn->prepare($sql);
        return $statement->execute([':medicine_id' => $medicineId]);
    }

    public function updateStock(string $medicineId, int $newStock): bool
    {
        $medicine = $this->getMedicineById($medicineId);
        if (!$medicine) {
            return false;
        }

        $status = $this->calculateStatus((int) $newStock, (int) ($medicine['reorder_level'] ?? 10), $medicine['expiry_date'] ?? null, 'Available');

        $sql = "UPDATE cm_medicine_inventory
                SET current_stock = :current_stock,
                    status = :status,
                    updated_at = NOW()
                WHERE medicine_id = :medicine_id";

        $statement = $this->conn->prepare($sql);
        return $statement->execute([
            ':current_stock' => $newStock,
            ':status' => $status,
            ':medicine_id' => $medicineId,
        ]);
    }

    public function calculateStatus(int $currentStock, int $reorderLevel, ?string $expiryDate, string $requestedStatus = 'Available'): string
    {
        if ($requestedStatus === 'Inactive') {
            return 'Inactive';
        }

        if ($expiryDate !== null && $expiryDate !== '' && $expiryDate < date('Y-m-d')) {
            return 'Expired';
        }

        if ($expiryDate !== null && $expiryDate !== '' && $expiryDate <= date('Y-m-d', strtotime('+30 days'))) {
            return 'Expiring Soon';
        }

        if ($currentStock <= 0) {
            return 'Out of Stock';
        }

        if ($currentStock <= $reorderLevel) {
            return 'Low Stock';
        }

        return 'Available';
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->conn->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
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
}
