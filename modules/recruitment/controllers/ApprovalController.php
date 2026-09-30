<?php
require_once "../app/config/Database.php";

class ApprovalController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // Display the list of interviews
    public function index()
    {
        $query = "
            SELECT i.id AS interview_id, a.id AS application_id, a.first_name, a.last_name, 
                   a.email, a.phone, i.interview_date, i.interview_time, 
                   i.interview_type, i.interview_mode, i.status
            FROM rao_interviews i
            JOIN rao_applications a ON i.application_id = a.id
            ORDER BY i.interview_date ASC, i.interview_time ASC
        ";
        $result = $this->db->query($query);
        $interviews = $result->fetch_all(MYSQLI_ASSOC);

        include "../pages/approval.php";
    }

    // Approve an interview and create employee record
    public function approve($interview_id)
    {
        // 1️⃣ Fetch the interview and applicant data
        $stmt = $this->db->prepare("
            SELECT i.id AS interview_id, a.id AS application_id, a.first_name, a.last_name, a.email, a.phone
            FROM rao_interviews i
            JOIN rao_applications a ON i.application_id = a.id
            WHERE i.id = ?
        ");
        $stmt->bind_param("i", $interview_id);
        $stmt->execute();
        $interview = $stmt->get_result()->fetch_assoc();

        if ($interview) {
            // 2️⃣ Update the interview status to Approved
            $stmtUpdate = $this->db->prepare("UPDATE rao_interviews SET status = 'Approved' WHERE id = ?");
            $stmtUpdate->bind_param("i", $interview_id);
            $stmtUpdate->execute();

            // 3️⃣ Insert into employees table
            // 3️⃣ Insert into employees table
            $full_name = $interview['first_name'] . ' ' . $interview['last_name'];
            $address = ''; // Add logic to get applicant address if available
            $contact_number = $interview['phone'];
            $email = $interview['email'];
            $department = ''; // Set default or fetch from application
            $position = '';   // Set default or fetch from application
            $date_hired = date("Y-m-d"); // TODAY
            $employment_status = 'Active'; // Default status
            $birthdate = null; // Add logic if available
            $sex = null;       // Add logic if available
            $teacher_qualification = null; // Add logic if available

            $stmtEmp = $this->db->prepare("
    INSERT INTO employees 
    (full_name, address, contact_number, email, department, position, date_hired, employment_status, created_at, updated_at, birthdate, sex, teacher_qualification)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?)
");

            $stmtEmp->bind_param(
                "sssssssssss",
                $full_name,
                $address,
                $contact_number,
                $email,
                $department,
                $position,
                $date_hired,
                $employment_status,
                $birthdate,
                $sex,
                $teacher_qualification
            );

            $stmtEmp->execute();

            // Redirect back to approval page
            header("Location: index.php?page=approval");
            exit();
        }
    }
}
