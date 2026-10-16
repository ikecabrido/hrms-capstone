<?php

require_once __DIR__ . '/../../../../database/db.php';

class Job
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $columnCheck = $this->db->prepare("SHOW COLUMNS FROM rao_jobs LIKE 'position'");
        $columnCheck->execute();
        if (!$columnCheck->fetch()) {
            $this->db->exec("ALTER TABLE rao_jobs ADD COLUMN position VARCHAR(255) NOT NULL DEFAULT '' AFTER title");
        }
        $columnCheck = $this->db->prepare("SHOW COLUMNS FROM rao_jobs LIKE 'department'");
        $columnCheck->execute();
        if (!$columnCheck->fetch()) {
            $this->db->exec("ALTER TABLE rao_jobs ADD COLUMN department VARCHAR(255) NOT NULL DEFAULT '' AFTER category");
        }
        $columnCheck = $this->db->prepare("SHOW COLUMNS FROM rao_jobs LIKE 'is_posted'");
        $columnCheck->execute();
        if (!$columnCheck->fetch()) {
            $this->db->exec("ALTER TABLE rao_jobs ADD COLUMN is_posted TINYINT(1) NOT NULL DEFAULT 0 AFTER max_applicants");
        }
        $this->db->exec("UPDATE rao_jobs SET position = title WHERE position IS NULL OR position = ''");
        $this->db->exec("UPDATE rao_jobs SET department = category WHERE department IS NULL OR department = ''");
        $this->db->exec("ALTER TABLE rao_jobs MODIFY category VARCHAR(255) NOT NULL DEFAULT ''");
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
                position,
                description,
                qualifications,
                department,
                location,
                max_applicants
            FROM rao_jobs
            WHERE is_posted = 1
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
                position,
                description,
                qualifications,
                department,
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
     * Create job
     */
    public function create(
        $title,
        $position,
        $desc,
        $qualifications,
        $department,
        $location,
        $max_applicants
    ) {
        $stmt = $this->db->prepare("
            INSERT INTO rao_jobs
            (
                title,
                position,
                description,
                qualifications,
                department,
                location,
                max_applicants,
                is_posted
            )
            VALUES
            (
                :title,
                :position,
                :description,
                :qualifications,
                :department,
                :location,
                :max_applicants,
                1
            )
        ");

        return $stmt->execute([
            ':title'          => $title,
            ':position'       => $position,
            ':description'    => $desc,
            ':qualifications' => $qualifications,
            ':department'     => $department,
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
        $position,
        $desc,
        $qualifications,
        $department,
        $location,
        $max_applicants
    ) {
        $stmt = $this->db->prepare("
            UPDATE rao_jobs
            SET
                title = :title,
                position = :position,
                description = :description,
                qualifications = :qualifications,
                department = :department,
                location = :location,
                max_applicants = :max_applicants
            WHERE id = :id
        ");

        return $stmt->execute([
            ':title'          => $title,
            ':position'       => $position,
            ':description'    => $desc,
            ':qualifications' => $qualifications,
            ':department'     => $department,
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
