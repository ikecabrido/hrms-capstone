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

                // Notify department heads for this employee using existing dept head lookup logic
                try {
                    $database = TimeDatabase::getInstance();
                    $conn = $database->getConnection();

                    $qry = "SELECT COALESCE(d.department_name, e.department) AS department,
                                   CONCAT(COALESCE(e.first_name,''),' ',COALESCE(e.last_name,'')) AS full_name
                              FROM em_employees e
                              LEFT JOIN em_departments d ON e.department_id = d.department_id
                             WHERE e.employee_id = :employee_id";
                    $s = $conn->prepare($qry);
                    $s->bindParam(':employee_id', $data['employee_id'], PDO::PARAM_INT);
                    $s->execute();
                    $emp = $s->fetch(PDO::FETCH_ASSOC);

                    if ($emp) {
                        $dept = $emp['department'];
                        $headQry = "SELECT user_id FROM ta_department_heads WHERE department = :department AND is_active = 1";
                        $h = $conn->prepare($headQry);
                        $h->bindParam(':department', $dept);
                        $h->execute();
                        $heads = $h->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($heads as $hd) {
                            $this->notificationModel->create([
                                'user_id' => $hd['user_id'],
                                'employee_id' => $data['employee_id'],
                                'notification_type' => 'LEAVE_REQUEST_SUBMITTED',
                                'title' => 'New Leave Request',
                                'message' => 'New leave request submitted by ' . ($emp['full_name'] ?? 'an employee'),
                                'related_id' => null,
                                'related_type' => 'leave_request',
                                'send_via_email' => 0,
                                'send_via_sms' => 0
                            ]);
                        }
                    }
                } catch (Exception $e) {
                    error_log('Failed to notify department heads: ' . $e->getMessage());
                }

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
    public function approve($leave_request_id, $approver_id, $remarks = '')
    {
        try {
            Session::start();
            $user_id = Session::get('user_id');
            
            // Get leave request details (with leave type name)
            $requestDetails = $this->leaveModel->getRequestWithType($leave_request_id);
            $leaveRequest = $requestDetails ?: $this->leaveModel->getById($leave_request_id);
            if (!$leaveRequest) {
                return ['success' => false, 'message' => 'Leave request not found'];
            }

            // Approval-time validations
            if ($requestDetails) {
                $leaveTypeName = $requestDetails['leave_type_name'] ?? '';

                // Document requirement: Sick Leave and Bereavement Leave
                if (in_array($leaveTypeName, ['Sick Leave', 'Bereavement Leave'], true)) {
                    $hasDocument = !empty($leaveRequest['supporting_document']) || !empty($leaveRequest['documents']);
                    if (!$hasDocument) {
                        return ['success' => false, 'message' => $leaveTypeName . ' requires a supporting document before it can be approved.'];
                    }
                }

                // Advance notice requirement: Vacation Leave needs at least 3 days between submission and start date
                if ($leaveTypeName === 'Vacation Leave') {
                    $submittedRaw = $leaveRequest['date_submitted'] ?? $leaveRequest['created_at'] ?? null;
                    if ($submittedRaw) {
                        $submitted = new DateTime($submittedRaw);
                        $start = new DateTime($leaveRequest['start_date']);
                        $daysNotice = (int)$submitted->diff($start)->days;
                        if ($daysNotice < 3) {
                            return ['success' => false, 'message' => 'Vacation Leave requires at least 3 days advance notice. This request was submitted only ' . $daysNotice . ' day(s) before the start date.'];
                        }
                    }
                }
            }

            // Single-stage approval: every approval is final
            $status = 'Approved';

            // Determine requested days and verify balance
            $requested_days = Helper::calculateWorkingDays($leaveRequest['start_date'], $leaveRequest['end_date']);
            $balanceCheck = $this->leaveModel->checkLeaveBalance(
                $leaveRequest['employee_id'], 
                $leaveRequest['leave_type_id'], 
                $requested_days
            );

            if (!$balanceCheck['status']) {
                return ['success' => false, 'message' => $balanceCheck['message']];
            }

            $result = $this->leaveModel->updateStatus($leave_request_id, $status, $approver_id, $remarks);

            if ($result) {
                // Deduct balance and mark absences as excused
                $this->leaveModel->deductLeaveBalance(
                    $leaveRequest['employee_id'],
                    $leaveRequest['leave_type_id'],
                    $requested_days
                );

                LeaveAbsenceHelper::onLeaveApproved($leave_request_id);

                $this->auditLog->log('LEAVE_' . strtoupper($status), $user_id, null, null, 
                    ['leave_request_id' => $leave_request_id], 'SUCCESS');

                // Notify the employee about approval
                try {
                    require_once __DIR__ . '/../models/Notification.php';
                    $notif = new Notification();
                    $notif->create([
                        'user_id' => null,
                        'employee_id' => $leaveRequest['employee_id'],
                        'notification_type' => 'LEAVE_REQUEST_APPROVED',
                        'title' => 'Leave Request Approved',
                        'message' => 'Your ' . ($requestDetails['leave_type_name'] ?? '') . ' request has been approved.',
                        'related_id' => $leave_request_id,
                        'related_type' => 'leave_request',
                        'send_via_email' => 0,
                        'send_via_sms' => 0
                    ]);
                } catch (Exception $e) {
                    error_log('Failed to send approval notification: ' . $e->getMessage());
                }

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
            // Fetch leave request for context
            $leaveRequest = $this->leaveModel->getRequestWithType($leave_request_id) ?: $this->leaveModel->getById($leave_request_id);

            $result = $this->leaveModel->updateStatus($leave_request_id, 'REJECTED', $approver_id, $reason);
            
            if ($result) {
                // Reverse any leave-based excuses
                LeaveAbsenceHelper::onLeaveRejected($leave_request_id);
                
                $this->auditLog->log('LEAVE_REJECTED', $user_id, null, null, 
                    ['leave_request_id' => $leave_request_id, 'reason' => $reason], 'SUCCESS');
                // Notify the employee about rejection
                try {
                    require_once __DIR__ . '/../models/Notification.php';
                    $notif = new Notification();
                    $notif->create([
                        'user_id' => null,
                        'employee_id' => $leaveRequest['employee_id'],
                        'notification_type' => 'LEAVE_REQUEST_REJECTED',
                        'title' => 'Leave Request Rejected',
                        'message' => 'Your ' . ($leaveRequest['leave_type_name'] ?? '') . ' request has been rejected.' . (!empty($reason) ? ' Reason: ' . $reason : ''),
                        'related_id' => $leave_request_id,
                        'related_type' => 'leave_request',
                        'send_via_email' => 0,
                        'send_via_sms' => 0
                    ]);
                } catch (Exception $e) {
                    error_log('Failed to send rejection notification: ' . $e->getMessage());
                }

                return ['success' => true, 'message' => 'Leave request rejected'];
            }
            
            return ['success' => false, 'message' => 'Failed to reject leave request'];
        } catch (Exception $e) {
            error_log("Leave Rejection Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }
}
