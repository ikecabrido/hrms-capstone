<?php

require_once __DIR__ . '/../../../database/db.php';

class Application
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
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
                a.last_name,
                a.email,
                a.phone,
                a.resume,
                a.created_at,
                a.status,
                a.source,
                j.title AS job_title,
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
                j.position,
                j.department,
                j.position_id,
                j.department_id
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
    public function canApply($job_id)
    {
        $stmt = $this->db->prepare("
                        SELECT max_applicants
            FROM rao_jobs
                        WHERE id = ?
                            AND is_posted = 1
        ");

        $stmt->execute([$job_id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return false;
        }

        $max = (int) $result['max_applicants'];

        // 0 = unlimited
        if ($max === 0) {
            return true;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM rao_applications
            WHERE job_id = ?
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
            FROM rao_hired
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

            // Store the hired applicant in the table used by the hiring module.
            $insert = $this->db->prepare("
                INSERT INTO rao_hired (
                    application_id,
                    position_id,
                    department_id,
                    first_name,
                    middle_name,
                    last_name,
                    email,
                    phone,
                    address,
                    department,
                    position,
                    base_job,
                    salary
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insert->execute([
                $id,
                (int) ($applicant['position_id'] ?? 0),
                (int) ($applicant['department_id'] ?? 0),
                $applicant['first_name'] ?? '',
                $applicant['middle_name'] ?? '',
                $applicant['last_name'] ?? '',
                $applicant['email'] ?? '',
                $applicant['phone'] ?? '',
                $applicant['address'] ?? '',
                $applicant['department'] ?? '',
                $applicant['position'] ?? '',
                $applicant['job_title'] ?? '',
                0
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
        $whereClause = "a.is_archived = 0 AND a.status = :status";

        if ($status === 'rejected') {
            $whereClause = "a.is_archived = 1 AND a.status = :status";
        }

        $sql = "
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
                j.title AS job_title,
                j.position,
                j.department
            FROM rao_applications a
            JOIN rao_jobs j ON a.job_id = j.id
            WHERE " . $whereClause . "
            ORDER BY a.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status' => $status]);

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
    /**
     * Get qualified candidates who passed
     * Initial, Technical, and Final interviews
     */
    public function getQualifiedCandidates()
    {
        $stmt = $this->db->prepare("
        SELECT
            a.id AS application_id,
            a.first_name,
            a.middle_name,
            a.last_name,
            a.email,
            a.phone,
            a.job_id,

            j.title AS job_title,
            j.category AS base_job,
            j.department,

            p.position_id,
            p.position_name AS position,

            ss.midpoint_salary AS salary

        FROM rao_applications a

        INNER JOIN rao_jobs j
            ON j.id = a.job_id

        LEFT JOIN em_positions p
            ON p.position_id = j.position_id

        LEFT JOIN pr_salary_structures ss
            ON ss.position_id = p.position_id
            AND ss.status = 'Active'
            AND ss.effective_date <= CURDATE()
            AND (
                ss.end_date IS NULL
                OR ss.end_date >= CURDATE()
            )

        WHERE
            a.is_archived = 0
            AND a.status != 'hired'

            AND EXISTS (
                SELECT 1
                FROM rao_interviews i1
                WHERE i1.application_id = a.id
                  AND i1.stage_order = 1
                  AND LOWER(i1.result) = 'passed'
            )

            AND EXISTS (
                SELECT 1
                FROM rao_interviews i2
                WHERE i2.application_id = a.id
                  AND i2.stage_order = 2
                  AND LOWER(i2.result) = 'passed'
            )

            AND EXISTS (
                SELECT 1
                FROM rao_interviews i3
                WHERE i3.application_id = a.id
                  AND i3.stage_order = 3
                  AND LOWER(i3.result) = 'passed'
            )

        ORDER BY a.id DESC
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
                h.first_name,
                h.last_name,
                h.email,
                h.phone,
                h.address,
                h.department,
                h.position,
                h.base_job,
                h.salary
            FROM rao_hired h
            ORDER BY h.id DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get hired applicants eligible for offer creation
     */
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
            h.email,
            h.position,
            h.department,
            h.base_job,
            h.salary
        FROM rao_hired h
        ORDER BY h.id DESC
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
                status = 'rejected',
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
    /**
     * Get applicants ready for offer
     */
    /**
     * Get applicants marked Ready for Offer
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
            a.status,
            a.ready_for_offer,
            a.job_id
        FROM rao_applications a
        WHERE a.ready_for_offer = 1
          AND a.is_archived = 0
          AND a.status != 'hired'
        ORDER BY a.id DESC
    ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public function getOfferJobDetails($jobId)
    {
        $stmt = $this->db->prepare("
        SELECT
            j.id AS job_id,
            j.title AS job_title,
            j.category AS base_job,
            j.department,

            p.position_id,
            p.position_name AS position,

            ss.salary_structure_id,
            ss.salary_grade,
            ss.minimum_salary,
            ss.midpoint_salary,
            ss.maximum_salary,
            ss.effective_date,
            ss.end_date

        FROM rao_jobs j

        INNER JOIN em_positions p
            ON p.position_id = j.position_id

        LEFT JOIN pr_salary_structures ss
            ON ss.position_id = p.position_id
            AND ss.status = 'Active'
            AND ss.effective_date <= CURDATE()
            AND (
                ss.end_date IS NULL
                OR ss.end_date >= CURDATE()
            )

        WHERE j.id = :job_id

        ORDER BY
            ss.effective_date DESC,
            ss.salary_structure_id DESC

        LIMIT 1
    ");

        $stmt->execute([
            ':job_id' => (int) $jobId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
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
}
