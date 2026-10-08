<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Employee;
use App\Helper\Helper;
use App\Core\Session;
use Exception;

class NotificationController
{
    private Notification $notificationModel;
    private NotificationRecipient $recipientModel;
    private Employee $employeeModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
        $this->recipientModel = new NotificationRecipient();
        $this->employeeModel = new Employee();
    }

    public function index()
    {
        $userId = Session::get('user_id');

        if (!$userId) {
            throw new Exception('User session not found.');
        }

        $employeeInfo = $this->employeeModel->getByUserId((int) $userId);

        if (!$employeeInfo) {
            throw new Exception('Employee profile not found.');
        }

        $employeeId = (int) $employeeInfo['employee_id'];

        $employeeNotification = $this->recipientModel->getEmployeeNotifications(
            $employeeId
        );

        $title = 'My Notifications';
        $content = __DIR__ . '/../views/employee-portal/notification/content.php';

        require __DIR__ . '/../views/employee-portal/index.php';
    }

    public function adminIndex()
    {
        $title = 'Admin Notifications';
        $content = __DIR__ . '/../views/admin-portal/notification/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }

    public function create()
    {
        try {
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $type = $_POST['type'] ?? 'general';
            $priority = $_POST['priority'] ?? 'normal';
            $employeeIds = $_POST['employee_ids'] ?? [];

            if (!$title || !$message) {
                throw new Exception('Please complete all required fields.');
            }

            if (!$employeeIds) {
                throw new Exception('Please select at least one employee.');
            }

            $notificationId = $this->notificationModel->create([
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'priority' => $priority,
                'created_by_user_id' => Session::get('user_id')
            ]);

            $this->recipientModel->createRecipients(
                $notificationId,
                $employeeIds
            );

            Session::set(
                'success',
                'Notification sent successfully.'
            );

        } catch (Exception $e) {
            Session::set('error', $e->getMessage());
        }

        Helper::redirect('index.php?url=admin-notification');
    }

    public function update()
    {
        try {
            $id = (int) ($_POST['notification_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $type = $_POST['type'] ?? 'general';
            $priority = $_POST['priority'] ?? 'normal';
            $employeeIds = $_POST['employee_ids'] ?? [];

            if (!$id || !$title || !$message) {
                throw new Exception('Please complete all required fields.');
            }

            if (!$employeeIds) {
                throw new Exception('Please select at least one employee.');
            }

            $this->notificationModel->update([
                'notification_id' => $id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'priority' => $priority
            ]);

            $this->recipientModel->deleteByNotification($id);

            $this->recipientModel->createRecipients(
                $id,
                $employeeIds
            );

            Session::set(
                'success',
                'Notification updated successfully.'
            );

        } catch (Exception $e) {
            Session::set('error', $e->getMessage());
        }

        Helper::redirect('index.php?url=admin-notification');
    }

    public function delete()
    {
        try {
            $id = (int) ($_POST['notification_id'] ?? 0);

            if (!$id) {
                throw new Exception('Notification not found.');
            }

            $this->recipientModel->deleteByNotification($id);

            $this->notificationModel->delete($id);

            Session::set(
                'success',
                'Notification deleted successfully.'
            );

        } catch (Exception $e) {
            Session::set('error', $e->getMessage());
        }

        Helper::redirect('index.php?url=admin-notification');
    }

    public function markRead()
    {
        $userId = Session::get('user_id');

        if (!$userId) {
            throw new Exception('User session not found.');
        }

        $employee = $this->employeeModel->getByUserId((int) $userId);

        $notificationId = (int) ($_POST['notification_id'] ?? 0);

        if ($employee && $notificationId > 0) {
            $this->recipientModel->markAsRead(
                $notificationId,
                (int) $employee['employee_id']
            );
        }

        Helper::redirect('index.php?url=notification');
    }

    public function markAllRead()
    {
        $userId = Session::get('user_id');

        if (!$userId) {
            throw new Exception('User session not found.');
        }

        $employee = $this->employeeModel->getByUserId((int) $userId);

        if ($employee) {
            $this->recipientModel->markAllAsRead(
                (int) $employee['employee_id']
            );
        }

        Helper::redirect('index.php?url=notification');
    }
}