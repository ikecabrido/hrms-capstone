<?php

require_once __DIR__ . '/../../../database/db.php';

class ParsedResume
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Insert parsed resume
     */
    public function insert($data)
    {
        $sql = "
            INSERT INTO rao_parsed_resume
            (
                application_id,
                first_name,
                last_name,
                email,
                phone,
                job_title,
                resume,
                approved_at
            )
            VALUES
            (
                :application_id,
                :first_name,
                :last_name,
                :email,
                :phone,
                :job_title,
                :resume,
                NOW()
            )
        ";

        try {
            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                ':application_id' => $data['application_id'],
                ':first_name'    => $data['first_name'],
                ':last_name'     => $data['last_name'],
                ':email'         => $data['email'],
                ':phone'         => $data['phone'],
                ':job_title'     => $data['job_title'],
                ':resume'        => $data['resume']
            ]);

        } catch (PDOException $e) {
            die("Insert parsed resume failed: " . $e->getMessage());
        }
    }

    /**
     * Get all parsed resumes that have not
     * already passed an interview.
     */
    public function findAll()
    {
        $sql = "
            SELECT pr.*
            FROM rao_parsed_resume pr
            WHERE NOT EXISTS (
                SELECT 1
                FROM rao_interviews i
                WHERE i.application_id = pr.application_id
                AND i.result = 'passed'
            )
            ORDER BY pr.approved_at DESC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            die("Fetch parsed resumes failed: " . $e->getMessage());
        }
    }

    /**
     * Find parsed resume by application ID
     */
    public function findByApplicationId($applicationId)
    {
        $sql = "
            SELECT *
            FROM rao_parsed_resume
            WHERE application_id = :application_id
            LIMIT 1
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':application_id' => $applicationId
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            die("Find parsed resume failed: " . $e->getMessage());
        }
    }
}