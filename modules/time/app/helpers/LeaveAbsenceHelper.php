<?php
/**
 * Leave & Absence Integration Helper
 * Handles synchronization between leave approvals and absence/late management
 */

require_once __DIR__ . '/../models/AbsenceLateMgmt.php';
require_once __DIR__ . '/../models/Leave.php';

class LeaveAbsenceHelper
{
    /**
     * Called when leave is approved by HR
     * Marks all absences during leave period as excused
     */
    public static function onLeaveApproved($leave_request_id)
    {
        try {
            $leaveModel = new Leave();
            $leaveRequest = $leaveModel->getById($leave_request_id);

            if (!$leaveRequest) {
                error_log("Leave request not found: {$leave_request_id}");
                return false;
            }

            $absenceLateMgmt = new AbsenceLateMgmt();
            $result = $absenceLateMgmt->markAbsencesExcusedByLeave(
                $leaveRequest['employee_id'],
                $leave_request_id,
                $leaveRequest['start_date'],
                $leaveRequest['end_date']
            );

            if ($result) {
                error_log("Successfully marked absences as excused for leave request: {$leave_request_id}");
                return true;
            }

            return false;
        } catch (Exception $e) {
            error_log("Error in onLeaveApproved: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Called when leave is rejected by HR
     * Reverses the excused status for that leave period
     */
    public static function onLeaveRejected($leave_request_id)
    {
        try {
            $leaveModel = new Leave();
            $leaveRequest = $leaveModel->getById($leave_request_id);

            if (!$leaveRequest) {
                error_log("Leave request not found: {$leave_request_id}");
                return false;
            }

            $absenceLateMgmt = new AbsenceLateMgmt();
            $result = $absenceLateMgmt->reverseLeaveExcuse(
                $leaveRequest['employee_id'],
                $leave_request_id
            );

            if ($result) {
                error_log("Successfully reversed leave excuses for leave request: {$leave_request_id}");
                return true;
            }

            return false;
        } catch (Exception $e) {
            error_log("Error in onLeaveRejected: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get leave approval status badge
     */
    public static function getLeaveStatusBadge($status)
    {
        $badges = [
            'Pending' => '<span class="badge badge-warning">Pending Approval</span>',
            'PENDING' => '<span class="badge badge-warning">Pending Approval</span>',
            'APPROVED_BY_HEAD' => '<span class="badge badge-info">Approved by Head</span>',
            'APPROVED_BY_HR' => '<span class="badge badge-success">Approved & Active</span>',
            'Approved' => '<span class="badge badge-success">Approved</span>',
            'REJECTED' => '<span class="badge badge-danger">Rejected</span>',
            'CANCELLED' => '<span class="badge badge-secondary">Cancelled</span>',
        ];

        return $badges[$status] ?? '<span class="badge badge-light">' . htmlspecialchars($status) . '</span>';
    }

    /**
     * Get excuse type badge
     */
    public static function getExcuseTypeBadge($excuse_type, $excuse_status)
    {
        if ($excuse_type === 'APPROVED_LEAVE') {
            return '<span class="badge badge-success" title="Automatically excused due to approved leave">
                    <i class="fas fa-check-circle"></i> Leave Approved
                    </span>';
        }

        $statuses = [
            'PENDING' => '<span class="badge badge-warning">Pending Review</span>',
            'APPROVED' => '<span class="badge badge-success">Approved</span>',
            'REJECTED' => '<span class="badge badge-danger">Rejected</span>',
            'AWAITING_DOCUMENTS' => '<span class="badge badge-info">Awaiting Documents</span>',
        ];

        return $statuses[$excuse_status] ?? '<span class="badge badge-light">' . htmlspecialchars($excuse_status) . '</span>';
    }
}
?>
