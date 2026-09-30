<?php

include_once __DIR__ . '/../../../database/db.php';

class Onboarding
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }


    /**
     * Get accepted applicants who have not started onboarding
     */
    /**
     * Get accepted applicants who have not started onboarding
     */
    public function getAcceptedApplicants(): array
    {
        $sql = "
        SELECT
            h.application_id AS id,
            h.first_name,
            h.last_name,

            o.base_job,
            o.position,
            o.department,
            o.salary,
            o.benefit_package,
            o.additional_note

        FROM rao_hired h

        INNER JOIN rao_offer o
            ON o.application_id = h.application_id
            AND o.status = 'Accepted'

        LEFT JOIN rao_onboarding r
            ON r.application_id = h.application_id

        WHERE r.id IS NULL

        ORDER BY h.id DESC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Save onboarding record
     */
    public function insert(array $data): bool
    {
        $sql = "
            INSERT INTO rao_onboarding
            (
                application_id,
                full_name,
                base_job,
                position,
                department,
                location,
                start_date,
                mentor
            )
            VALUES
            (
                :application_id,
                :full_name,
                :base_job,
                :position,
                :department,
                :location,
                :start_date,
                :mentor
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':application_id' => $data['application_id'],
            ':full_name'      => $data['full_name'],
            ':base_job'       => $data['base_job'],
            ':position'       => $data['position'],
            ':department'     => $data['department'],
            ':location'       => $data['location'],
            ':start_date'     => $data['start_date'],
            ':mentor'         => $data['mentor']
        ]);
    }


    /**
     * Get all onboarding records
     */
    public function getAll(): array
    {
        $sql = "
            SELECT *
            FROM rao_onboarding
            ORDER BY created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Get one onboarding record by ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM rao_onboarding
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ?: null;
    }


    /**
     * Update onboarding checklist and progress
     */
    public function updateProgress(
        int $id,
        array $checklist,
        float $progress
    ): bool {

        $checklistJson = json_encode($checklist);

        $stmt = $this->db->prepare("
            UPDATE rao_onboarding
            SET
                checklist = :checklist,
                progress = :progress
            WHERE id = :id
        ");

        return $stmt->execute([
            ':checklist' => $checklistJson,
            ':progress' => $progress,
            ':id'        => $id
        ]);
    }
}
