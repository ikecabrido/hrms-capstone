<?php

require_once __DIR__ . '/../../../../database/db.php';

class Application
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function create($jobId, $firstName, $middleName, $lastName, $email, $phone, $address, $summary, $resume, $source = 'Online Posting', $gender = null, $birthplace = null, $birthdate = null, $age = null, $civilStatus = null, $citizenship = null, $religion = null, $currentAddress = null, $permanentAddress = null)
    {
        $currentAddress = $currentAddress !== null && trim((string) $currentAddress) !== '' ? trim((string) $currentAddress) : trim((string) $address);

        $stmt = $this->db->prepare("\n            INSERT INTO rao_applications\n                (job_id, first_name, middle_name, last_name, email, phone, gender, birthplace, birthdate, age, civil_status, citizenship, religion, address, current_address, permanent_address, summary, resume, source)\n            VALUES\n                (:job_id, :first_name, :middle_name, :last_name, :email, :phone, :gender, :birthplace, :birthdate, :age, :civil_status, :citizenship, :religion, :address, :current_address, :permanent_address, :summary, :resume, :source)\n        ");

        $stmt->execute([
            ':job_id' => (int) $jobId,
            ':first_name' => $firstName,
            ':middle_name' => $middleName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':phone' => $phone,
            ':gender' => $gender,
            ':birthplace' => $birthplace,
            ':birthdate' => $birthdate,
            ':age' => $age,
            ':civil_status' => $civilStatus,
            ':citizenship' => $citizenship,
            ':religion' => $religion,
            ':address' => $address,
            ':current_address' => $currentAddress,
            ':permanent_address' => $permanentAddress,
            ':summary' => $summary,
            ':resume' => $resume,
            ':source' => $source
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get all active applications
     */
    public function getAll()
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id,
                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,
                a.phone,
                a.resume,
                a.created_at,
                a.status,
                a.source,
                j.title,
                j.position,
                j.department
            FROM rao_applications a
            JOIN rao_jobs j ON a.job_id = j.id
            WHERE a.is_archived = 0
            ORDER BY a.id DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get application by ID
     */
    public function findById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.*,
                j.title AS job_title,
                j.position AS job_position,
                j.department AS job_dept
            FROM rao_applications a
            JOIN rao_jobs j ON a.job_id = j.id
            WHERE a.id = ?
        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check whether applicant can apply for a job
     */
    /**
     * Check whether applicant can apply for a job
     *
     * max_applicants = 0 means unlimited.
     */
    public function canApply($job_id)
    {
        // Get the job vacancy limit
        $stmt = $this->db->prepare("
        SELECT max_applicants
        FROM rao_jobs
        WHERE id = ?
          AND is_posted = 1
        LIMIT 1
    ");

        $stmt->execute([$job_id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Job does not exist or is not posted
        if (!$result) {
            return false;
        }

        $max = (int) ($result['max_applicants'] ?? 0);

        // 0 = unlimited applicants
        if ($max === 0) {
            return true;
        }

        // Count only active/non-archived applications
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM rao_applications
        WHERE job_id = ?
          AND is_archived = 0
    ");

        $stmt->execute([$job_id]);

        $count = (int) $stmt->fetchColumn();

        return $count < $max;
    }

    /**
     * Hire applicant and migrate data to hired table atomically
     */
    public function hire(int $id)
    {
        $applicant = $this->findById($id);

        if (!$applicant) {
            return [
                'success' => false,
                'message' => 'Applicant not found'
            ];
        }

        // Check if already hired
        $check = $this->db->prepare("
            SELECT id
            FROM rao_hired_applicants
            WHERE application_id = ?
        ");

        $check->execute([$id]);

        if ($check->fetch(PDO::FETCH_ASSOC)) {
            return [
                'success' => false,
                'message' => 'Applicant already hired'
            ];
        }

        try {
            $this->db->beginTransaction();

            // Generate unique Employee ID
            $employee_id = 'EMP' . date('Y') . sprintf("%04d", rand(1, 9999));

            // Insert into hired applicants
            $insert = $this->db->prepare("
                INSERT INTO rao_hired_applicants (
                    application_id,
                    employee_id,
                    job_id,
                    first_name,
                    last_name,
                    email,
                    phone,
                    hired_at,
                    address,
                    summary,
                    cover_letter,
                    resume,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, 'active')
            ");

            $insert->execute([
                $id,
                $employee_id,
                $applicant['job_id'],
                $applicant['first_name'],
                $applicant['last_name'],
                $applicant['email'],
                $applicant['phone'],
                $applicant['address'] ?? null,
                $applicant['summary'] ?? null,
                $applicant['cover_letter'] ?? null,
                $applicant['resume'] ?? null
            ]);

            // Update application status
            $update = $this->db->prepare("
                UPDATE rao_applications
                SET status = 'hired'
                WHERE id = ?
            ");

            $update->execute([$id]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Applicant hired successfully.'
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get applications by status
     */
    public function getByStatus($status)
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id,
                a.first_name,
                a.last_name,
                a.email,
                a.phone,
                a.resume,
                a.created_at,
                a.status,
                a.source,
                j.title AS job_title
            FROM rao_applications a
            JOIN rao_jobs j ON a.job_id = j.id
            WHERE a.is_archived = 0
            AND a.status = ?
            ORDER BY a.id DESC
        ");

        $stmt->execute([$status]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Save interview feedback
     */
    public function saveFeedback($interview_id, $rating, $feedback)
    {
        $stmt = $this->db->prepare("
            UPDATE rao_interviews
            SET 
                rating = ?,
                feedback = ?,
                feedback_date = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([
            $rating,
            $feedback,
            $interview_id
        ]);
    }

    /**
     * Get qualified candidates who passed initial, technical, and final interviews
     */
    public function getQualifiedCandidates()
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id AS application_id,
                a.first_name,
                a.last_name,
                a.email,
                j.title AS job_title
            FROM rao_applications a
            JOIN rao_jobs j ON a.job_id = j.id
            LEFT JOIN rao_interviews i1 
                ON i1.application_id = a.id AND i1.interview_type = 'initial'
            LEFT JOIN rao_interviews i2 
                ON i2.application_id = a.id AND i2.interview_type = 'technical'
            LEFT JOIN rao_interviews i3 
                ON i3.application_id = a.id AND i3.interview_type = 'final'
            WHERE 
                i1.result = 'passed'
                AND i2.result = 'passed'
                AND i3.result = 'passed'
                AND a.status != 'hired'
                AND a.is_archived = 0
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get hired applicants
     */
    public function getHiredApplicants()
    {
        $stmt = $this->db->prepare("
            SELECT 
                h.id,
                h.application_id,
                h.employee_id,
                h.first_name,
                h.last_name,
                h.email,
                h.phone,
                h.status,
                h.hired_at,
                j.title AS job_title
            FROM rao_hired_applicants h
            JOIN rao_jobs j ON h.job_id = j.id
            ORDER BY h.hired_at DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get hired applicants eligible for offer creation
     */
    public function getHiredForOffer()
    {
        $stmt = $this->db->prepare("
            SELECT 
                h.application_id,
                h.first_name,
                h.last_name,
                j.title AS job_title
            FROM rao_hired_applicants h
            JOIN rao_jobs j ON h.job_id = j.id
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Archive application
     */
    public function archive($id)
    {
        $stmt = $this->db->prepare("
            UPDATE rao_applications
            SET 
                is_archived = 1,
                archived_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }

    /**
     * Permanently delete archived applications older than 30 days
     */
    public function deleteArchivedOlderThan30Days()
    {
        $stmt = $this->db->prepare("
            DELETE FROM rao_applications
            WHERE is_archived = 1
            AND archived_at <= NOW() - INTERVAL 30 DAY
        ");

        return $stmt->execute();
    }

    /**
     * Get archived applications
     */
    public function getArchived()
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.*,
                j.title AS job_title
            FROM rao_applications a
            LEFT JOIN rao_jobs j ON a.job_id = j.id
            WHERE a.is_archived = 1
            ORDER BY a.archived_at DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Restore archived application
     */
    public function restore($id)
    {
        $stmt = $this->db->prepare("
            UPDATE rao_applications
            SET 
                is_archived = 0,
                archived_at = NULL
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }

    /**
     * Get active jobs
     */
    public function getActiveJobs()
    {
        $stmt = $this->db->prepare("
            SELECT id, title
            FROM rao_jobs
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get applicants ready for offer
     */
    /**
     * Get applicants ready for offer
     */
    public function getReadyForOffer()
    {
        $stmt = $this->db->prepare("
        SELECT 
            a.id,
            a.first_name,
            a.last_name,
            a.email,
            j.title AS job_title,
            o.status AS offer_status,
            o.salary,
            o.position,
            o.department
        FROM rao_applications a
        JOIN rao_jobs j 
            ON a.job_id = j.id
        LEFT JOIN rao_offer o 
            ON o.application_id = a.id
        WHERE 
            a.ready_for_offer = 1
            AND a.is_archived = 0
            AND a.status != 'hired'
            AND (
                o.status IS NULL
                OR o.status NOT IN ('Accepted', 'Rejected')
            )
        ORDER BY a.id DESC
    ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Get offered applicants
     */
    public function getOfferedApplicants()
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id AS application_id,
                a.first_name,
                a.last_name,
                o.base_job,
                o.position,
                o.salary,
                o.id AS offer_id,
                o.status AS offer_status
            FROM rao_applications a
            INNER JOIN rao_offer o ON a.id = o.application_id
            ORDER BY o.created_at DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update offer status
     */
    public function updateOfferStatus($offer_id, $status)
    {
        $stmt = $this->db->prepare("
            UPDATE rao_offer
            SET status = ?
            WHERE id = ?
        ");

        return $stmt->execute([$status, $offer_id]);
    }

    /**
     * Update salary
     */
    public function updateSalary($offer_id, $salary)
    {
        $stmt = $this->db->prepare("
            UPDATE rao_offer
            SET 
                salary = ?,
                status = 'Sent'
            WHERE id = ?
        ");

        return $stmt->execute([$salary, $offer_id]);
    }



    /**
     * Update the resume path for an application.
     *
     * The actual resume file is stored in:
     * modules/recruitment/uploads/{applicationId}/resume/
     *
     * The database stores the relative path.
     */
    public function updateResumePath(
        int $applicationId,
        string $resumePath
    ): bool {
        $stmt = $this->db->prepare("
        UPDATE rao_applications
        SET resume = ?
        WHERE id = ?
    ");

        return $stmt->execute([
            $resumePath,
            $applicationId
        ]);
    }
}
