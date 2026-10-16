<?php

require_once __DIR__ . '/../../../database/db.php';

class Job
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get all jobs
     */
    public function all()
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                title,
                description,
                qualifications,
                category,
                location,
                max_applicants
            FROM rao_jobs
            ORDER BY id DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get only active jobs for application form
     */
    public function getActiveJobs()
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                title,
                description,
                qualifications,
                category,
                location,
                max_applicants
            FROM rao_jobs
            WHERE is_posted = 1
            ORDER BY title ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single job
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM rao_jobs
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => (int) $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Save the public posting content for an existing job.
     */
    public function updatePosting($id, $description, $qualifications)
    {
        $stmt = $this->db->prepare("
            UPDATE rao_jobs
            SET description = :description,
                qualifications = :qualifications,
                is_posted = 1
            WHERE id = :id
        ");

        return $stmt->execute([
            ':description'    => $description,
            ':qualifications' => $qualifications,
            ':id'             => (int) $id
        ]);
    }

    /**
     * Create job
     */
    public function create(
        $title,
        $desc,
        $qualifications,
        $category,
        $location,
        $max_applicants
    ) {
        $stmt = $this->db->prepare("
            INSERT INTO rao_jobs
            (
                title,
                description,
                qualifications,
                category,
                location,
                max_applicants
            )
            VALUES
            (
                :title,
                :description,
                :qualifications,
                :category,
                :location,
                :max_applicants
            )
        ");

        return $stmt->execute([
            ':title'          => $title,
            ':description'    => $desc,
            ':qualifications' => $qualifications,
            ':category'       => $category,
            ':location'       => $location,
            ':max_applicants' => (int) $max_applicants
        ]);
    }

    /**
     * Update job
     */
    public function update(
        $id,
        $title,
        $desc,
        $qualifications,
        $category,
        $location,
        $max_applicants
    ) {
        $stmt = $this->db->prepare("
            UPDATE rao_jobs
            SET
                title = :title,
                description = :description,
                qualifications = :qualifications,
                category = :category,
                location = :location,
                max_applicants = :max_applicants
            WHERE id = :id
        ");

        return $stmt->execute([
            ':title'          => $title,
            ':description'    => $desc,
            ':qualifications' => $qualifications,
            ':category'       => $category,
            ':location'       => $location,
            ':max_applicants' => (int) $max_applicants,
            ':id'             => (int) $id
        ]);
    }

    /**
     * Delete job
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("
            DELETE FROM rao_jobs
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => (int) $id
        ]);
    }
}
