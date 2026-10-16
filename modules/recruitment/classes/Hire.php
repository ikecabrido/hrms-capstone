<?php
require_once __DIR__ . '/../config/Database.php';

class Hire
{
    private $db;

    public function __construct($dbConn)
    {
        $this->db = $dbConn;
    }

    public function create($application_id)
    {
        // 1. Fetch from rao_applications using 'id'
        $stmt = $this->db->prepare("SELECT * FROM rao_applications WHERE id = ?");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $app = $stmt->get_result()->fetch_assoc();

        if (!$app) {
            throw new Exception("Applicant not found for ID: " . $application_id);
        }

        $employee_id = $this->generateEmployeeId();
        $status = 'active'; // Your rao_hired_applicants table default is 'active'

        // 2. Prepare Insert - Matching your table structure
        $stmt = $this->db->prepare("
        INSERT INTO rao_hired_applicants 
        (employee_id, application_id, job_id, first_name, last_name, email, phone, address, summary, cover_letter, resume, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

        $stmt->bind_param(
            "siisssssssss",
            $employee_id,
            $app['id'],        // Use 'id' from rao_applications
            $app['job_id'],
            $app['first_name'],
            $app['last_name'],
            $app['email'],
            $app['phone'],
            $app['address'],
            $app['summary'],
            $app['cover_letter'],
            $app['resume'],
            $status
        );

        if (!$stmt->execute()) {
            throw new Exception("Insert into hired_applicants failed: " . $stmt->error);
        }

        return true;
    }

    public function markAshired($application_id)
    {
        // Update the 'hired' column in rao_applications using 'id'
        $stmt = $this->db->prepare("UPDATE rao_applications SET hired = 1 WHERE id = ?");
        $stmt->bind_param("i", $application_id);

        if (!$stmt->execute()) {
            throw new Exception("Updating application status failed: " . $stmt->error);
        }

        return true;
    }

    public function generateEmployeeId()
    {
        $result = $this->db->query("
        SELECT employee_id 
        FROM rao_hired_applicants 
        ORDER BY id DESC 
        LIMIT 1
    ");

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastId = (int) str_replace('EMP-', '', $row['employee_id']);
            $newId = $lastId + 1;
        } else {
            $newId = 1;
        }

        return 'EMP-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
    }

    public function alreadyhired($application_id)
    {
        $stmt = $this->db->prepare("
        SELECT id FROM rao_hired_applicants WHERE application_id = ?
    ");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }
}
