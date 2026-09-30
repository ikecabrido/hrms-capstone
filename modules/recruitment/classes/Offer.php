<?php

include_once __DIR__ . '/../../../database/db.php';

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
                o.status AS offer_status,

                a.first_name,
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
                o.*,

                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,
                a.address,
                a.phone,
                j.position_id,
                j.department_id

            FROM rao_offer o

            INNER JOIN rao_applications a
                ON o.application_id = a.id

            INNER JOIN rao_jobs j
                ON a.job_id = j.id

            WHERE o.id = :offer_id

            LIMIT 1
        ");

        $stmt->execute([
            ':offer_id' => $offer_id
        ]);

        $offer = $stmt->fetch(PDO::FETCH_ASSOC);

        return $offer ?: null;
    }



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
            status
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
            :status
        )
    ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':application_id'  => $data['application_id'],
            ':position'        => $data['position'],
            ':base_job'        => $data['base_job'],
            ':salary'          => $data['salary'],
            ':department'      => $data['department'],
            ':benefit_package' => $data['benefit_package'],
            ':additional_note' => $data['additional_note'],
            ':status'          => $data['status']
        ]);
    }


    /**
     * Insert hired applicant
     */
    public function insertHired(array $offer): bool
    {
        $stmt = $this->db->prepare("
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
                department,
                position,
                base_job,
                salary
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
                :department,
                :position,
                :base_job,
                :salary
            )
        ");

        return $stmt->execute([
            ':application_id' => $offer['application_id'],
            ':position_id'    => (int) ($offer['position_id'] ?? 0),
            ':department_id'  => (int) ($offer['department_id'] ?? 0),
            ':first_name'     => $offer['first_name'],
            ':middle_name'    => $offer['middle_name'] ?? '',
            ':last_name'      => $offer['last_name'],
            ':email'          => $offer['email'],
            ':phone'          => $offer['phone'],
            ':address'        => $offer['address'],
            ':department'     => $offer['department'],
            ':position'       => $offer['position'],
            ':base_job'       => $offer['base_job'],
            ':salary'         => $offer['salary']
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
     * Hire an applicant from an accepted offer
     */
    public function hireApplicant(int $offerId): bool
    {
        $db = $this->db;

        try {

            $db->beginTransaction();

            // =========================================================
            // 1. GET OFFER + APPLICANT INFORMATION
            // =========================================================

            $stmt = $db->prepare("
            SELECT
                o.offer_id,
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
                a.address

            FROM rao_offer o

            INNER JOIN rao_applications a
                ON a.id = o.application_id

            WHERE o.offer_id = :offer_id
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
            // 2. MAKE SURE OFFER IS ACCEPTED
            // =========================================================

            if ($offer['offer_status'] !== 'Accepted') {
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

            if ($stmt->fetch()) {
                throw new Exception(
                    "This applicant has already been hired."
                );
            }


            // =========================================================
            // 4. FIND POSITION ID
            // =========================================================

            $stmt = $db->prepare("
            SELECT
                position_id,
                department_id
            FROM em_positions
            WHERE position_name = :position
            AND status = 'Active'
            LIMIT 1
        ");

            $stmt->execute([
                ':position' => $offer['position']
            ]);

            $position = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$position) {
                throw new Exception(
                    "Position '{$offer['position']}' was not found in em_positions."
                );
            }

            $positionId = (int) $position['position_id'];


            // =========================================================
            // 5. FIND DEPARTMENT ID
            // =========================================================

            $stmt = $db->prepare("
            SELECT
                department_id
            FROM em_departments
            WHERE department_name = :department
            AND status = 'Active'
            LIMIT 1
        ");

            $stmt->execute([
                ':department' => $offer['department']
            ]);

            $department = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$department) {
                throw new Exception(
                    "Department '{$offer['department']}' was not found in em_departments."
                );
            }

            $departmentId = (int) $department['department_id'];


            // =========================================================
            // 6. INSERT INTO RAO_HIRED
            // =========================================================

            $stmt = $db->prepare("
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
                position,
                department,
                base_job,
                salary,
                hired_at
            )
            VALUES (
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

            $stmt->execute([
                ':application_id' => $offer['application_id'],
                ':position_id'   => $positionId,
                ':department_id' => $departmentId,
                ':first_name'    => $offer['first_name'],
                ':middle_name'   => $offer['middle_name'],
                ':last_name'     => $offer['last_name'],
                ':email'         => $offer['email'],
                ':phone'         => $offer['phone'],
                ':address'       => $offer['address'],
                ':position'      => $offer['position'],
                ':department'    => $offer['department'],
                ':base_job'      => $offer['base_job'],
                ':salary'        => $offer['salary']
            ]);


            // =========================================================
            // 7. MARK APPLICATION AS HIRED
            // =========================================================

            $stmt = $db->prepare("
            UPDATE rao_applications
            SET
                status = 'hired',
                hired = 1
            WHERE id = :application_id
        ");

            $stmt->execute([
                ':application_id' => $offer['application_id']
            ]);


            // =========================================================
            // 8. MARK OFFER AS HIRED
            // =========================================================

            $stmt = $db->prepare("
            UPDATE rao_offer
            SET
                status = 'Accepted'
            WHERE offer_id = :offer_id
        ");

            $stmt->execute([
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
