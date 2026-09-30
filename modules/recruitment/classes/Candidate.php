<?php

require_once __DIR__ . '/../../../database/db.php';

class Candidate
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get all candidates who passed the final interview
     */
    public function getPassedFinalInterview($finalStageOrder = 3)
    {
        $sql = "
            SELECT
                r.id AS interview_id,

                a.id AS id,
                a.id AS application_id,

                a.first_name,
                a.last_name,

                j.category AS base_job,
                j.title AS position,
                j.department AS department

            FROM rao_interviews r

            INNER JOIN rao_applications a
                ON r.application_id = a.id

            INNER JOIN rao_jobs j
                ON j.id = a.job_id

            WHERE r.result = :result
              AND r.stage_order = :stage_order

            ORDER BY r.interview_date DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':result' => 'passed',
            ':stage_order' => $finalStageOrder
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Get candidates who passed the final interview
     * together with their offer status.
     */
    public function getCandidatesWithOfferStatus()
    {
        $sql = "
            SELECT
                a.id AS id,
                a.id AS application_id,

                a.first_name,
                a.last_name,

                j.category AS base_job,
                j.title AS position,
                j.department AS department,

                COALESCE(o.salary, '') AS salary,
                COALESCE(o.salary, '') AS offer_salary,

                COALESCE(o.offer_status, '') AS offer_status

            FROM rao_applications a

            INNER JOIN rao_jobs j
                ON a.job_id = j.id

            LEFT JOIN rao_offer o
                ON a.id = o.application_id

            WHERE a.id IN (
                SELECT DISTINCT application_id
                FROM rao_interviews
                WHERE interview_type = :interview_type
                  AND result = :result
            )

            ORDER BY a.first_name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':interview_type' => 'Final Interview',
            ':result' => 'passed'
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
