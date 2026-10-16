<?php

require_once __DIR__ . '/../../../database/db.php';

class Offer
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get all job offers
     */
    public function getAll(): array
    {
        $sql = "
            SELECT
                o.id AS offer_id,
                o.application_id,
                o.position,
                o.base_job,
                o.salary,
                o.department,
                o.benefit_package,
                o.additional_note,
                o.status AS offer_status,
                o.created_at,

                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,
                a.phone,
                a.address

            FROM rao_offer o

            INNER JOIN rao_applications a
                ON o.application_id = a.id

            ORDER BY o.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update offer status
     */
    public function updateStatus($offer_id, $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE rao_offer
            SET status = :status
            WHERE id = :offer_id
        ");

        return $stmt->execute([
            ':status'   => $status,
            ':offer_id' => $offer_id
        ]);
    }

    /**
     * Get one offer by ID
     */
    public function getOfferById($offer_id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                o.id AS offer_id,
                o.application_id,
                o.position,
                o.base_job,
                o.salary,
                o.department,
                o.benefit_package,
                o.additional_note,
                o.status AS offer_status,
                o.created_at,

                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,
                a.address,
                a.phone,

                j.position_id AS job_position_id,
                j.department_id AS job_department_id,

                p.position_id,
                p.position_name,

                d.department_id,
                d.department_name

            FROM rao_offer o

            INNER JOIN rao_applications a
                ON o.application_id = a.id

            INNER JOIN rao_jobs j
                ON a.job_id = j.id

            LEFT JOIN em_positions p
                ON p.position_id = j.position_id

            LEFT JOIN em_departments d
                ON d.department_id = j.department_id

            WHERE o.id = :offer_id

            LIMIT 1
        ");

        $stmt->execute([
            ':offer_id' => $offer_id
        ]);

        $offer = $stmt->fetch(PDO::FETCH_ASSOC);

        return $offer ?: null;
    }

    /**
     * Create a job offer
     */
    public function createOffer(array $data): bool
    {
        $sql = "
            INSERT INTO rao_offer
            (
                application_id,
                position,
                base_job,
                salary,
                department,
                benefit_package,
                additional_note,
                status,
                created_at
            )
            VALUES
            (
                :application_id,
                :position,
                :base_job,
                :salary,
                :department,
                :benefit_package,
                :additional_note,
                :status,
                NOW()
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':application_id'  => $data['application_id'],
            ':position'        => $data['position'],
            ':base_job'        => $data['base_job'],
            ':salary'          => $data['salary'],
            ':department'      => $data['department'],
            ':benefit_package' => $data['benefit_package'] ?? '',
            ':additional_note' => $data['additional_note'] ?? '',
            ':status'          => $data['status'] ?? 'Sent'
        ]);
    }

    /**
     * Check whether applicant is already hired
     */
    public function alreadyHired($application_id): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM rao_hired
            WHERE application_id = :application_id
            LIMIT 1
        ");

        $stmt->execute([
            ':application_id' => $application_id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Hire applicant from an accepted offer.
     *
     * This is the ONLY method that should create the rao_hired record.
     */
    public function hireApplicant(int $offerId): bool
    {
        $db = $this->db;

        try {

            $db->beginTransaction();

            // =========================================================
            // 1. GET OFFER + APPLICANT + JOB POSITION
            // =========================================================

            $stmt = $db->prepare("
                SELECT
                    o.id AS offer_id,
                    o.application_id,
                    o.base_job,
                    o.position,
                    o.department,
                    o.salary,
                    o.status AS offer_status,

                    a.first_name,
                    a.middle_name,
                    a.last_name,
                    a.email,
                    a.phone,
                    a.address,

                    j.position_id AS job_position_id,
                    j.department_id AS job_department_id,

                    p.position_id,
                    p.position_name,

                    d.department_id,
                    d.department_name

                FROM rao_offer o

                INNER JOIN rao_applications a
                    ON a.id = o.application_id

                INNER JOIN rao_jobs j
                    ON j.id = a.job_id

                LEFT JOIN em_positions p
                    ON p.position_id = j.position_id

                LEFT JOIN em_departments d
                    ON d.department_id = j.department_id

                WHERE o.id = :offer_id

                LIMIT 1
            ");

            $stmt->execute([
                ':offer_id' => $offerId
            ]);

            $offer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$offer) {
                throw new Exception("Offer not found.");
            }

            // =========================================================
            // 2. ONLY ACCEPTED OFFERS CAN BE HIRED
            // =========================================================

            if (
                strtolower(trim($offer['offer_status'] ?? '')) !==
                'accepted'
            ) {
                throw new Exception(
                    "Only accepted offers can be hired."
                );
            }

            // =========================================================
            // 3. CHECK IF ALREADY HIRED
            // =========================================================

            $stmt = $db->prepare("
                SELECT id
                FROM rao_hired
                WHERE application_id = :application_id
                LIMIT 1
            ");

            $stmt->execute([
                ':application_id' => $offer['application_id']
            ]);

            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception(
                    "This applicant has already been hired."
                );
            }

            // =========================================================
            // 4. GET POSITION ID
            // =========================================================

            $positionId = (int) (
                $offer['position_id']
                ?? $offer['job_position_id']
                ?? 0
            );

            if ($positionId <= 0) {
                throw new Exception(
                    "The position could not be identified."
                );
            }

            // =========================================================
            // 5. GET DEPARTMENT ID
            // =========================================================

            $departmentId = (int) (
                $offer['department_id']
                ?? $offer['job_department_id']
                ?? 0
            );

            if ($departmentId <= 0) {

                // Fallback to department name if job.department_id
                // is not available.
                $departmentStmt = $db->prepare("
                    SELECT department_id
                    FROM em_departments
                    WHERE TRIM(LOWER(department_name))
                        = TRIM(LOWER(:department))
                    AND status = 'Active'
                    LIMIT 1
                ");

                $departmentStmt->execute([
                    ':department' => $offer['department']
                ]);

                $departmentRow =
                    $departmentStmt->fetch(PDO::FETCH_ASSOC);

                if ($departmentRow) {
                    $departmentId =
                        (int) $departmentRow['department_id'];
                }
            }

            if ($departmentId <= 0) {
                throw new Exception(
                    "The department could not be identified."
                );
            }

            // =========================================================
            // 6. INSERT INTO RAO_HIRED
            // =========================================================

            $insert = $db->prepare("
                INSERT INTO rao_hired
                (
                    application_id,
                    position_id,
                    department_id,
                    first_name,
                    middle_name,
                    last_name,
                    email,
                    phone,
                    address,
                    position,
                    department,
                    base_job,
                    salary,
                    hired_at
                )
                VALUES
                (
                    :application_id,
                    :position_id,
                    :department_id,
                    :first_name,
                    :middle_name,
                    :last_name,
                    :email,
                    :phone,
                    :address,
                    :position,
                    :department,
                    :base_job,
                    :salary,
                    NOW()
                )
            ");

            $insert->execute([
                ':application_id' =>
                $offer['application_id'],

                ':position_id' =>
                $positionId,

                ':department_id' =>
                $departmentId,

                ':first_name' =>
                $offer['first_name'],

                ':middle_name' =>
                $offer['middle_name'] ?? '',

                ':last_name' =>
                $offer['last_name'],

                ':email' =>
                $offer['email'],

                ':phone' =>
                $offer['phone'] ?? '',

                ':address' =>
                $offer['address'] ?? '',

                ':position' =>
                $offer['position'],

                ':department' =>
                $offer['department'],

                ':base_job' =>
                $offer['base_job'],

                ':salary' =>
                $offer['salary']
            ]);

            $hiredId = $db->lastInsertId();

            if (!$hiredId) {
                throw new Exception(
                    "Failed to create hired employee record."
                );
            }

            // =========================================================
            // 7. UPDATE APPLICATION
            // =========================================================

            $updateApplication = $db->prepare("
                UPDATE rao_applications
                SET
                    status = 'Hired',
                    hired = 1
                WHERE id = :application_id
            ");

            $updateApplication->execute([
                ':application_id' =>
                $offer['application_id']
            ]);

            // =========================================================
            // 8. KEEP OFFER AS ACCEPTED
            // =========================================================
            //
            // Do NOT change it to another status.
            // The offer remains Accepted while the applicant is hired.
            //

            $updateOffer = $db->prepare("
                UPDATE rao_offer
                SET status = 'Accepted'
                WHERE id = :offer_id
            ");

            $updateOffer->execute([
                ':offer_id' => $offerId
            ]);

            // =========================================================
            // 9. COMMIT
            // =========================================================

            $db->commit();

            return true;
        } catch (Throwable $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }
}
