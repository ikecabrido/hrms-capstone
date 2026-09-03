<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * EmployeeProfile
 *
 * Wraps the employee's extended profile sub-tables:
 *  - em_personal_information (1:1 per employee)
 *  - em_family_background (1:1 per employee)
 *  - em_government_ids (1:1 per employee)
 *  - employee_dependents (1:many)
 *  - employee_emergency_contacts (1:many)
 *
 * All FKs are employee_id -> em_employees.employee_id ON DELETE CASCADE.
 */
class EmployeeProfile
{
    private $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    // ---------- personal_information ----------

    public function getPersonalInformation($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM em_personal_information WHERE employee_id = :employee_id LIMIT 1"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Insert or update the employee's personal_information row (upsert by employee_id).
     */
    public function savePersonalInformation($employeeId, array $data)
    {
        $existing = $this->getPersonalInformation($employeeId);

        $fields = [
            'birth_date', 'gender', 'birth_place', 'civil_status', 'citizenship',
            'religion', 'blood_type', 'height', 'weight', 'spouse_name',
            'spouse_occupation', 'father_name', 'father_occupation', 'mother_name',
            'mother_occupation', 'emergency_contact_name', 'emergency_contact_relationship',
            'emergency_contact_number', 'disability_info', 'current_address', 'permanent_address',
        ];

        $values = [':employee_id' => $employeeId];
        foreach ($fields as $f) {
            $values[":$f"] = ($data[$f] ?? '') === '' ? null : $data[$f];
        }

        if ($existing) {
            $set = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
            $sql = "UPDATE em_personal_information SET $set WHERE employee_id = :employee_id";
        } else {
            $cols = implode(', ', array_merge(['employee_id'], $fields));
            $placeholders = implode(', ', array_merge([':employee_id'], array_map(fn($f) => ":$f", $fields)));
            $sql = "INSERT INTO em_personal_information ($cols) VALUES ($placeholders)";
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($values);
    }

    // ---------- family_background ----------

    public function getFamilyBackground($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM em_family_background WHERE employee_id = :employee_id LIMIT 1"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function saveFamilyBackground($employeeId, array $data)
    {
        $existing = $this->getFamilyBackground($employeeId);

        $fields = [
            'father_name', 'father_occupation', 'mother_name', 'mother_occupation',
            'spouse_name', 'spouse_occupation', 'number_of_children',
        ];

        $values = [':employee_id' => $employeeId];
        foreach ($fields as $f) {
            $values[":$f"] = ($data[$f] ?? '') === '' ? null : $data[$f];
        }

        if ($existing) {
            $set = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
            $sql = "UPDATE em_family_background SET $set WHERE employee_id = :employee_id";
        } else {
            $cols = implode(', ', array_merge(['employee_id'], $fields));
            $placeholders = implode(', ', array_merge([':employee_id'], array_map(fn($f) => ":$f", $fields)));
            $sql = "INSERT INTO em_family_background ($cols) VALUES ($placeholders)";
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($values);
    }

    // ---------- government_ids ----------

    public function getGovernmentIds($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM em_government_ids WHERE employee_id = :employee_id LIMIT 1"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function saveGovernmentIds($employeeId, array $data)
    {
        $existing = $this->getGovernmentIds($employeeId);

        $fields = ['sss_no', 'philhealth_no', 'pagibig_no', 'tin_no'];

        $values = [':employee_id' => $employeeId];
        foreach ($fields as $f) {
            $values[":$f"] = ($data[$f] ?? '') === '' ? null : $data[$f];
        }

        if ($existing) {
            $set = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
            $sql = "UPDATE em_government_ids SET $set WHERE employee_id = :employee_id";
        } else {
            $cols = implode(', ', array_merge(['employee_id'], $fields));
            $placeholders = implode(', ', array_merge([':employee_id'], array_map(fn($f) => ":$f", $fields)));
            $sql = "INSERT INTO em_government_ids ($cols) VALUES ($placeholders)";
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($values);
    }

    // ---------- employee_dependents (1:many) ----------

    public function getDependents($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM employee_dependents WHERE employee_id = :employee_id ORDER BY dependent_id"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addDependent($employeeId, array $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO employee_dependents (employee_id, name, relationship, birth_date)
             VALUES (:employee_id, :name, :relationship, :birth_date)"
        );
        return $stmt->execute([
            ':employee_id'  => $employeeId,
            ':name'         => $data['name'] ?? '',
            ':relationship' => $data['relationship'] ?? '',
            ':birth_date'   => $data['birth_date'] ?: null,
        ]);
    }

    public function deleteDependent($dependentId, $employeeId)
    {
        // employee_id included in WHERE to prevent cross-employee deletion via id guessing
        $stmt = $this->conn->prepare(
            "DELETE FROM employee_dependents WHERE dependent_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([':id' => $dependentId, ':employee_id' => $employeeId]);
    }

    // ---------- employee_emergency_contacts (1:many) ----------

    public function getEmergencyContacts($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM employee_emergency_contacts WHERE employee_id = :employee_id ORDER BY contact_id"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addEmergencyContact($employeeId, array $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO employee_emergency_contacts (employee_id, name, relationship, contact_number, address)
             VALUES (:employee_id, :name, :relationship, :contact_number, :address)"
        );
        return $stmt->execute([
            ':employee_id'    => $employeeId,
            ':name'           => $data['name'] ?? '',
            ':relationship'   => $data['relationship'] ?? '',
            ':contact_number' => $data['contact_number'] ?? '',
            ':address'        => $data['address'] ?? null,
        ]);
    }

    public function deleteEmergencyContact($contactId, $employeeId)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM employee_emergency_contacts WHERE contact_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([':id' => $contactId, ':employee_id' => $employeeId]);
    }

    /**
     * Aggregate everything for a single "profile" tab render.
     */
    public function getFullProfile($employeeId)
    {
        return [
            'personal_information' => $this->getPersonalInformation($employeeId),
            'family_background'    => $this->getFamilyBackground($employeeId),
            'government_ids'       => $this->getGovernmentIds($employeeId),
            'dependents'           => $this->getDependents($employeeId),
            'emergency_contacts'   => $this->getEmergencyContacts($employeeId),
        ];
    }
}
