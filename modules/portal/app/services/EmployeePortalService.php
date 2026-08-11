<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\TrainingRequest;
use App\Core\NotificationHelper;

class EmployeePortalService
{
    private Leave $leaveModel;
    private Employee $employeeModel;
    private Attendance $attendanceModel;
    private TrainingRequest $trainingModel;
    private Announcement $announcementModel;
    private Notification $notificationModel;
    public function __construct()
    {
        $this->leaveModel = new Leave();
        $this->employeeModel = new Employee();
        $this->attendanceModel = new Attendance();
        $this->trainingModel = new TrainingRequest();
        $this->notificationModel = new Notification();
        $this->announcementModel = new Announcement();
    }
    public function getPortalData(
        int $userId,
        int $employeeId,
        array $employee
    ): array {

        $leaveBalances =
            $this->leaveModel->getLeaveBalances($employeeId);

        $leaveRequests =
            $this->leaveModel->getLeaveRequestsByEmployee($employeeId);

        $monthlyAttendance =
            $this->attendanceModel->getMonthlyAttendance($employeeId);

        $announcements =
            $this->announcementModel->all();

        $trainingRequests =
            $this->trainingModel->getByEmployeeId($employeeId);

        $notifications =
            NotificationHelper::getEmployeeNotifications($employeeId);

        return [
            'leave_balances' => $leaveBalances,
            'leave_requests' => $leaveRequests,
            'monthly_attendance' => $monthlyAttendance,
            'announcements' => $announcements,
            'upcomingTrainings' => $trainingRequests,
            'notificationCount' =>
            $notifications['count'] ?? 0,
            'latestNotifications' =>
            $notifications['latest'] ?? [],

            'employeeInfo' => $employee,
            'employeeProfileInfo' =>
            $this->employeeModel->findByUserId($userId),
        ];
    }
    private function getPendingRequests(array $leaveRequests): array
    {
        $pendingRequests = array_filter(
            $leaveRequests,
            static function (array $leave): bool {
                return ($leave['status'] ?? '') === 'Pending';
            }
        );

        $leaveTypeMap = $this->buildLeaveTypeMap();

        foreach ($pendingRequests as &$request) {
            $leaveTypeId = $request['leave_type_id'] ?? null;

            $request['leave_type_name'] =
                $leaveTypeMap[$leaveTypeId] ?? 'Unknown';
        }

        unset($request);

        return $pendingRequests;
    }
    private function getUpcomingTrainings(int $employeeId): array
    {
        $trainings = $this->trainingModel->getByEmployeeId($employeeId);

        return array_filter(
            $trainings,
            static function (array $training): bool {
                return in_array(
                    $training['request_status'] ?? '',
                    ['New', 'Received', 'Approved'],
                    true
                );
            }
        );
    }
    private function buildLeaveTypeMap(): array
    {
        return [];
    }
    private function buildRecentActivities(
        array $leaveRequests,
        array $monthlyAttendance
    ): array {
        return [];
    }
}
