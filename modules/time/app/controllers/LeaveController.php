<?php
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../helpers/AuditLog.php';
require_once __DIR__ . '/../helpers/LeaveAbsenceHelper.php';
require_once __DIR__ . '/../helpers/Helper.php';
require_once __DIR__ . '/../core/Session.php';

class LeaveController
{
    private $leaveModel;
    private $notificationModel;
    private $auditLog;

    public function __construct()
    {
        $this->leaveModel = new Leave();
        $this->notificationModel = new Notification();
        $this->auditLog = new AuditLog();
    }

    /**
     * Submit a new leave request
     */
    public function submitRequest($data)
    {
        try {
            Session::start();
            $user_id = Session::get('user_id');

            $dateValidation = Helper::validateLeaveRequestDates($data['start_date'], $data['end_date']);
            if (!$dateValidation['valid']) {
                $this->auditLog->log('LEAVE_REQUEST_FAILED', $user_id, $data['employee_id'], null,
                    (array)['reason' => $dateValidation['message']], 'FAILED');
                return ['success' => false, 'message' => $dateValidation['message']];
            }

            $requested_days = isset($data['total_days']) && $data['total_days'] !== '' ? (float)$data['total_days'] : $dateValidation['total_days'];
            $data['total_days'] = $requested_days;

            $overlap = $this->leaveModel->hasOverlappingRequest(
                $data['employee_id'],
                $data['leave_type_id'],
                $data['start_date'],
                $data['end_date']
            );
            if ($overlap) {
                $this->auditLog->log('LEAVE_REQUEST_FAILED', $user_id, $data['employee_id'], null,
                    (array)['reason' => 'You already have an overlapping leave request for this period'], 'FAILED');
                return ['success' => false, 'message' => 'You already have an overlapping leave request for this period'];
            }

            // Check leave balance before submitting
            $balanceCheck = $this->leaveModel->checkLeaveBalance(
                $data['employee_id'],
                $data['leave_type_id'],
                $requested_days
            );

            if (!$balanceCheck['status']) {
                $this->auditLog->log('LEAVE_REQUEST_FAILED', $user_id, $data['employee_id'], null,
                    (array)['reason' => $balanceCheck['message']], 'FAILED');
                return ['success' => false, 'message' => $balanceCheck['message']];
            }

            $created = $this->leaveModel->createRequest($data);
            if ($created) {
                $this->auditLog->log('LEAVE_REQUEST_SUBMITTED', $user_id, $data['employee_id'], null,
                    (array)['leave_type_id' => $data['leave_type_id'], 'days' => $requested_days], 'SUCCESS');
                return ['success' => true, 'message' => 'Leave request submitted successfully'];
            }
            $this->auditLog->log('LEAVE_REQUEST_FAILED', $user_id, $data['employee_id'], null,
                (array)$data, 'FAILED');
            return ['success' => false, 'message' => 'Failed to submit leave request'];
        } catch (Exception $e) {
            error_log("Leave Request Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }

    /**
     * Approve a leave request
     */
    public function approve($leave_request_id, $approver_id, $is_hr = false, $remarks = '')
    {
        try {
            Session::start();
            $user_id = Session::get('user_id');
            
            // Get leave request details
            $leaveRequest = $this->leaveModel->getById($leave_request_id);
            if (!$leaveRequest) {
                return ['success' => false, 'message' => 'Leave request not found'];
            }

            $status = $is_hr ? 'APPROVED_BY_HR' : 'APPROVED_BY_HEAD';
            $requested_days = 0;

            // For HR approval, verify balance before changing status
            if ($is_hr && $status === 'APPROVED_BY_HR') {
                $requested_days = Helper::calculateWorkingDays($leaveRequest['start_date'], $leaveRequest['end_date']);
                $balanceCheck = $this->leaveModel->checkLeaveBalance(
                    $leaveRequest['employee_id'], 
                    $leaveRequest['leave_type_id'], 
                    $requested_days
                );

                if (!$balanceCheck['status']) {
                    return ['success' => false, 'message' => $balanceCheck['message']];
                }
            }

            $result = $this->leaveModel->updateStatus($leave_request_id, $status, $approver_id, $remarks);
            
            if ($result) {
                if ($is_hr && $status === 'APPROVED_BY_HR') {
                    $this->leaveModel->deductLeaveBalance(
                        $leaveRequest['employee_id'],
                        $leaveRequest['leave_type_id'],
                        $requested_days
                    );
                    
                    // Mark absences as excused due to approved leave
                    LeaveAbsenceHelper::onLeaveApproved($leave_request_id);
                }

                $this->auditLog->log('LEAVE_' . $status, $user_id, null, null, 
                    ['leave_request_id' => $leave_request_id], 'SUCCESS');
                    
                return ['success' => true, 'message' => 'Leave request approved successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to approve leave request'];
        } catch (Exception $e) {
            error_log("Leave Approval Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }

    /**
     * Reject a leave request
     */
    public function reject($leave_request_id, $approver_id, $reason = '')
    {
        try {
            Session::start();
            $user_id = Session::get('user_id');
            
            $result = $this->leaveModel->updateStatus($leave_request_id, 'REJECTED', $approver_id, $reason);
            
            if ($result) {
                // Reverse any leave-based excuses
                LeaveAbsenceHelper::onLeaveRejected($leave_request_id);
                
                $this->auditLog->log('LEAVE_REJECTED', $user_id, null, null, 
                    ['leave_request_id' => $leave_request_id, 'reason' => $reason], 'SUCCESS');
                return ['success' => true, 'message' => 'Leave request rejected'];
            }
            
            return ['success' => false, 'message' => 'Failed to reject leave request'];
        } catch (Exception $e) {
            error_log("Leave Rejection Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }
}
