<?php

namespace App\Controllers;

use Exception;
use App\Helper\Helper;
use App\Models\Employee;
use App\Models\OnlineMeeting;

class OnlineMeetingController
{
    private Employee $employeeModel;
    private OnlineMeeting $meetingModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->meetingModel = new OnlineMeeting();
    }
    public function index()
    {
        $userId = $_SESSION['user_id'];

        $meetingsList = $this->meetingModel->all();
        $employee = $this->employeeModel->getByUserId($userId);

        $createdBy = $userId;
        $employeeId = $employee['employee_id'] ?? '';

        $title = "Manage Online Meetings";
        $content = __DIR__ . '/../views/employee-portal/online-meeting/content.php';

        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function adminIndex()
    {
        $userId = $_SESSION['user_id'];

        $meetingsList = $this->meetingModel->all();
        $employee = $this->employeeModel->getByUserId($userId);

        $createdBy = $userId;
        $employeeId = $employee['employee_id'] ?? '';

        $title = "Manage Online Meetings";
        $content = __DIR__ . '/../views/admin-portal/online-meeting/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function store()
    {
        try {
            $userId = $_SESSION['user_id'];
            $employee = $this->employeeModel->getByUserId($userId);

            $title = trim($_POST['title'] ?? '');
            $employee_id = $employee['id'];
            $scheduled_at = $_POST['scheduled_at'] ?? '';
            $created_by = $_POST['created_by'] ?? '';

            if (!$title || !$employee_id || !$scheduled_at || !$created_by) {
                $_SESSION['error'] = "All fields are required.";
                header("Location: index.php?url=admin-online-meeting");
                exit;
            }

            $meeting_id = uniqid("hr_meeting_");
            $meeting_link = "https://meet.jit.si/" . $meeting_id;

            $this->meetingModel->create([
                'title' => $title,
                'meeting_link' => $meeting_link,
                'created_by' => $created_by,
                'employee_id' => $employee_id,
                'scheduled_at' => $scheduled_at,
                'status' => 'scheduled'
            ]);

            $_SESSION['success'] = "Meeting created successfully!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to create meeting.";
        }

        header("Location: index.php?url=admin-online-meeting");
        exit;
    }
    public function updateStatus()
    {
        $meetingId = (int) ($_POST['meetings_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowedStatuses = [
            'scheduled',
            'completed',
            'cancelled'
        ];

        if (!$meetingId || !in_array($status, $allowedStatuses, true)) {
            $_SESSION['error'] = "Invalid meeting status.";
            header("Location: index.php?url=admin-online-meeting");
            exit;
        }

        try {

            $this->meetingModel->updateStatus($meetingId, $status);

            $_SESSION['success'] = "Meeting status updated successfully.";

        } catch (Exception $e) {

            $_SESSION['error'] = "Failed to update meeting status.";

        }

        header("Location: index.php?url=admin-online-meeting");
        exit;
    }
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?url=admin-online-meeting");
            exit;
        }

        $meetingId = (int) ($_POST['meetings_id'] ?? 0);

        if (!$meetingId) {
            $_SESSION['error'] = "Invalid meeting.";
            header("Location: index.php?url=admin-online-meeting");
            exit;
        }

        try {
            $this->meetingModel->delete($meetingId);

            $_SESSION['success'] = "Meeting deleted successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to delete meeting.";
        }

        header("Location: index.php?url=admin-online-meeting");
        exit;
    }
}