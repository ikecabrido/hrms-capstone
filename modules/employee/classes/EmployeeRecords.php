<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * EmployeeRecords
 *
 * Wraps the employee's repeatable record sub-tables (all 1:many, FK
 * employee_id -> em_employees.employee_id ON DELETE CASCADE):
 *  - em_education            (authoritative education table per approval;
 *                              employee_education is NOT used)
 *  - employee_certifications
 *  - employee_skills
 *  - employee_languages
 *  - employee_work_experience (authoritative work-history table per approval;
 *                              employment_history is NOT used)
 */
class EmployeeRecords
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

    // ---------- em_education ----------

    public function getEducation($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM em_education WHERE employee_id = :employee_id ORDER BY education_id"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addEducation($employeeId, array $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO em_education
                (employee_id, level, school_name, course, year_graduated, honors)
             VALUES
                (:employee_id, :level, :school_name, :course, :year_graduated, :honors)"
        );
        return $stmt->execute([
            ':employee_id'    => $employeeId,
            ':level'          => $data['level'] ?? '',
            ':school_name'    => $data['school_name'] ?? '',
            ':course'         => $data['course'] ?? null,
            ':year_graduated' => $data['year_graduated'] ?? null,
            ':honors'         => $data['honors'] ?? null,
        ]);
    }

    public function deleteEducation($educationId, $employeeId)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM em_education WHERE education_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([':id' => $educationId, ':employee_id' => $employeeId]);
    }

    // ---------- employee_certifications ----------

    public function getCertifications($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM employee_certifications WHERE employee_id = :employee_id ORDER BY cert_id"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addCertification($employeeId, array $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO employee_certifications
                (employee_id, cert_name, issuing_organization, date_issued, expiry_date)
             VALUES
                (:employee_id, :cert_name, :issuing_organization, :date_issued, :expiry_date)"
        );
        return $stmt->execute([
            ':employee_id'          => $employeeId,
            ':cert_name'            => $data['cert_name'] ?? '',
            ':issuing_organization' => $data['issuing_organization'] ?? '',
            ':date_issued'          => $data['date_issued'] ?: null,
            ':expiry_date'          => $data['expiry_date'] ?: null,
        ]);
    }

    public function deleteCertification($certId, $employeeId)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM employee_certifications WHERE cert_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([':id' => $certId, ':employee_id' => $employeeId]);
    }

    // ---------- employee_skills ----------

    public function getSkills($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM employee_skills WHERE employee_id = :employee_id ORDER BY skill_id"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addSkill($employeeId, array $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO employee_skills (employee_id, skill_name, proficiency)
             VALUES (:employee_id, :skill_name, :proficiency)"
        );
        return $stmt->execute([
            ':employee_id' => $employeeId,
            ':skill_name'  => $data['skill_name'] ?? '',
            ':proficiency' => $data['proficiency'] ?? 'Intermediate',
        ]);
    }

    public function deleteSkill($skillId, $employeeId)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM employee_skills WHERE skill_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([':id' => $skillId, ':employee_id' => $employeeId]);
    }

    // ---------- employee_languages ----------

    public function getLanguages($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM employee_languages WHERE employee_id = :employee_id ORDER BY language_id"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addLanguage($employeeId, array $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO employee_languages (employee_id, language_name, proficiency)
             VALUES (:employee_id, :language_name, :proficiency)"
        );
        return $stmt->execute([
            ':employee_id'   => $employeeId,
            ':language_name' => $data['language_name'] ?? '',
            ':proficiency'   => $data['proficiency'] ?? 'Intermediate',
        ]);
    }

    public function deleteLanguage($languageId, $employeeId)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM employee_languages WHERE language_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([':id' => $languageId, ':employee_id' => $employeeId]);
    }

    // ---------- employee_work_experience ----------

    public function getWorkExperience($employeeId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM employee_work_experience WHERE employee_id = :employee_id ORDER BY start_date DESC"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addWorkExperience($employeeId, array $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO employee_work_experience
                (employee_id, company_name, position, start_date, end_date, salary, reason_for_leaving)
             VALUES
                (:employee_id, :company_name, :position, :start_date, :end_date, :salary, :reason_for_leaving)"
        );
        return $stmt->execute([
            ':employee_id'        => $employeeId,
            ':company_name'       => $data['company_name'] ?? '',
            ':position'           => $data['position'] ?? '',
            ':start_date'         => $data['start_date'] ?: null,
            ':end_date'           => $data['end_date'] ?: null,
            ':salary'             => $data['salary'] ?: null,
            ':reason_for_leaving' => $data['reason_for_leaving'] ?? null,
        ]);
    }

    public function deleteWorkExperience($workExpId, $employeeId)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM employee_work_experience WHERE work_exp_id = :id AND employee_id = :employee_id"
        );
        return $stmt->execute([':id' => $workExpId, ':employee_id' => $employeeId]);
    }

    /**
     * Aggregate everything for a single "records" tab render.
     */
    public function getFullRecords($employeeId)
    {
        return [
            'education'        => $this->getEducation($employeeId),
            'certifications'   => $this->getCertifications($employeeId),
            'skills'           => $this->getSkills($employeeId),
            'languages'        => $this->getLanguages($employeeId),
            'work_experience'  => $this->getWorkExperience($employeeId),
        ];
    }
}
