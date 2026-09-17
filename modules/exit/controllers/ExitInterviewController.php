<?php

require_once __DIR__ . '/../models/ExitInterviewModel.php';

class ExitInterviewController extends ExitManagementController
{
    private ExitInterviewModel $interviewModel;

    public function __construct()
    {
        parent::__construct();
        $this->interviewModel = new ExitInterviewModel();
    }

    /**
     * Schedule exit interview
     */
    public function scheduleInterview(array $data): array
    {
        try {
            $assignedInterviewerId = (int)($data['interviewer_id'] ?? $_SESSION['employee_id'] ?? 0);
            if ($assignedInterviewerId <= 0) {
                return ['success' => false, 'message' => 'Exit HR staff is required to schedule the interview'];
            }

            $required = ['exit_case_type', 'exit_case_id', 'employee_id', 'scheduled_date', 'scheduled_time'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }

            $data['interviewer_id'] = $assignedInterviewerId;

            $exitCaseType = $data['exit_case_type'];
            $exitCaseId = (int)$data['exit_case_id'];
            $approvedCase = $this->interviewModel->getApprovedExitCase($exitCaseType, $exitCaseId);
            $employeeId = (string)$data['employee_id'];

            if (!$approvedCase || (string)$approvedCase['employee_id'] !== $employeeId) {
                return ['success' => false, 'message' => 'Selected exit case is not approved or does not match the employee'];
            }

            $interviewId = $this->interviewModel->scheduleInterview($data);

            return [
                'success' => true,
                'message' => 'Exit interview scheduled successfully',
                'interview_id' => $interviewId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Submit interview feedback
     */
    public function submitFeedback(int $interviewId, array $feedback): array
    {
        try {
            // Validate required fields
            $required = ['overall_satisfaction', 'work_environment_rating', 'management_rating',
                        'compensation_rating', 'work_life_balance_rating', 'reason_for_leaving', 'would_recommend'];
            foreach ($required as $field) {
                if (!isset($feedback[$field])) {
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }

            $success = $this->interviewModel->submitFeedback($interviewId, $feedback);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Feedback submitted successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to submit feedback'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get interview details
     */
    public function getInterview(int $interviewId): array
    {
        $interview = $this->interviewModel->getInterviewById($interviewId);

        if (!$interview) {
            return ['error' => 'Interview not found'];
        }

        // Get feedback if exists
        $feedback = $this->interviewModel->getFeedbackByInterview($interviewId);
        $interview['feedback'] = $feedback;

        if (!isset($interview['feedback'])) {
            $interview['feedback'] = null;
        }

        // hr_assessment is loaded by model.getInterviewById but ensure key exists
        if (!isset($interview['hr_assessment'])) {
            $interview['hr_assessment'] = $this->interviewModel->getHrAssessmentByInterview($interviewId);
        }

        // Attach exit case details and engagement data
        if (!empty($interview['exit_case_type']) && !empty($interview['exit_case_id'])) {
            $interview['exit_case_details'] = $this->interviewModel->getExitCaseDetails(
                $interview['exit_case_type'],
                (int)$interview['exit_case_id']
            );
        }

        if (!empty($interview['employee_id'])) {
            $interview['engagement_records'] = $this->interviewModel->getEngagementRecords($interview['employee_id']);
        } else {
            $interview['engagement_records'] = [];
        }

        return $interview;
    }

    /**
     * Render printable interview page
     */
    public function renderInterviewPrintPage(int $interviewId): string
    {
        $interview = $this->getInterview($interviewId);
        if (isset($interview['error'])) {
            return '<!doctype html><html><head><title>Interview Not Found</title></head><body><h1>Interview not found</h1></body></html>';
        }

        $employeeName = htmlspecialchars($interview['employee_full_name'] ?? 'Unknown', ENT_QUOTES);
        $employeeDepartment = htmlspecialchars($interview['employee_department'] ?? 'N/A', ENT_QUOTES);
        $employeePosition = htmlspecialchars($interview['employee_position'] ?? 'N/A', ENT_QUOTES);
        $interviewer = htmlspecialchars($interview['interviewer_name'] ?? 'N/A', ENT_QUOTES);
        $scheduledDate = htmlspecialchars($interview['scheduled_date'] ?? 'N/A', ENT_QUOTES);
        $scheduledTime = htmlspecialchars($interview['scheduled_time'] ?? 'N/A', ENT_QUOTES);
        $status = htmlspecialchars(isset($interview['status']) ? ucwords(str_replace('_', ' ', $interview['status'])) : 'N/A', ENT_QUOTES);
        $exitCaseType = htmlspecialchars(ucfirst((string)($interview['exit_case_type'] ?? 'N/A')), ENT_QUOTES);
        $exitCaseId = htmlspecialchars((string)($interview['exit_case_id'] ?? 'N/A'), ENT_QUOTES);
        $exitReason = htmlspecialchars($interview['exit_reason'] ?? 'N/A', ENT_QUOTES);
        $exitDate = htmlspecialchars($interview['exit_date'] ?? 'N/A', ENT_QUOTES);
        $noticeDate = htmlspecialchars($interview['notice_date'] ?? 'N/A', ENT_QUOTES);
        $caseApprovedAt = htmlspecialchars($interview['case_approved_at'] ?? 'N/A', ENT_QUOTES);

        $feedback = $interview['feedback'] ?? [];
        $hrAssessment = $interview['hr_assessment'] ?? [];

        $feedbackHtml = '';
        if (!empty($feedback) && is_array($feedback)) {
            foreach ($feedback as $label => $value) {
                $feedbackHtml .= '<tr><th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $label)), ENT_QUOTES) . '</th><td>' . htmlspecialchars((string)$value, ENT_QUOTES) . '</td></tr>';
            }
        } else {
            $feedbackHtml = '<tr><td colspan="2">No feedback submitted.</td></tr>';
        }

        $assessmentHtml = '';
        if (!empty($hrAssessment) && is_array($hrAssessment)) {
            foreach ($hrAssessment as $label => $value) {
                $assessmentHtml .= '<tr><th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $label)), ENT_QUOTES) . '</th><td>' . htmlspecialchars((string)$value, ENT_QUOTES) . '</td></tr>';
            }
        } else {
            $assessmentHtml = '<tr><td colspan="2">No HR assessment available.</td></tr>';
        }

        // Use same header and signatory layout as settlement for consistent preview
        $header = '<div class="school-header"><img src="/capstone_hr_management_system2/assets/pics/bcpLogo.png" alt="Bestlink College of the Philippines logo"><div><div class="school-name">Bestlink College of the Philippines - Bulacan Campus</div><div class="school-details">Lot 1 Ipo Road Brgy. Minuyan Proper, City of San Jose Del Monte, Bulacan.<br>Tel. No.: (044)792-1992</div></div></div>';

        $signatories = '<div class="signatories">'
            . '<div><strong>Prepared by:</strong><br><br>HR Staff</div>'
            . '<div><strong>Reviewed/Approved by:</strong><br><br>HR Administrator</div>'
            . '<div><strong>Employee Acknowledgment:</strong><br><br>Employee</div>'
            . '</div>';

        return '<!doctype html><html><head><meta charset="UTF-8"><title>Exit Interview Details</title>' .
            '<style>body{font-family:Arial,sans-serif;margin:24px;color:#172b4d;}h1,h2{margin-bottom:0.5rem;}table{width:100%;border-collapse:collapse;margin-top:1rem;}th,td{padding:10px;border:1px solid #ddd;text-align:left;}th{background:#f8f9fa;}.section{margin-top:24px;} .section-title{font-size:1rem;font-weight:700;margin-bottom:12px;} .panel{padding:16px;background:#f7f9fc;border:1px solid #e3e8ef;border-radius:6px;}.school-header{display:flex;align-items:center;border-bottom:2px solid #1f5fbf;padding-bottom:14px;margin-bottom:20px;}.school-header img{width:86px;height:86px;object-fit:contain;margin-right:18px;}.school-name{font-size:20px;font-weight:700;color:#174a8b;}.school-details{font-size:12px;line-height:1.6;color:#333;margin-top:4px;}table{width:100%;border-collapse:collapse;margin-top:1rem;}th,td{padding:8px;border:1px solid #ddd;text-align:left;}th{background:#f4f4f4;} .signatories{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:52px;page-break-inside:avoid;color:#172b4d;}.signatories>div{min-height:72px;border-top:1px solid #172b4d;padding-top:8px;font-size:12px;line-height:1.5;}</style>' .
            '</head><body>' .
            $header .
            '<h1>Exit Interview Report</h1>' .
            '<div class="panel"><strong>Interview Status:</strong> ' . $status . '</div>' .
            '<div class="section"><div class="section-title">Employee Details</div>' .
            '<table><tbody>' .
            '<tr><th>Name</th><td>' . $employeeName . '</td></tr>' .
            '<tr><th>Department</th><td>' . $employeeDepartment . '</td></tr>' .
            '<tr><th>Position</th><td>' . $employeePosition . '</td></tr>' .
            '</tbody></table></div>' .
            '<div class="section"><div class="section-title">Interview Details</div>' .
            '<table><tbody>' .
            '<tr><th>Interviewer</th><td>' . $interviewer . '</td></tr>' .
            '<tr><th>Scheduled Date</th><td>' . $scheduledDate . '</td></tr>' .
            '<tr><th>Scheduled Time</th><td>' . $scheduledTime . '</td></tr>' .
            '<tr><th>Exit Case Type</th><td>' . $exitCaseType . '</td></tr>' .
            '<tr><th>Exit Case ID</th><td>' . $exitCaseId . '</td></tr>' .
            '<tr><th>Exit Reason</th><td>' . $exitReason . '</td></tr>' .
            '<tr><th>Exit Date</th><td>' . $exitDate . '</td></tr>' .
            '<tr><th>Notice Date</th><td>' . $noticeDate . '</td></tr>' .
            '<tr><th>Case Approved At</th><td>' . $caseApprovedAt . '</td></tr>' .
            '</tbody></table></div>' .
            '<div class="section"><div class="section-title">Feedback</div><table><tbody>' . $feedbackHtml . '</tbody></table></div>' .
            '<div class="section"><div class="section-title">HR Assessment</div><table><tbody>' . $assessmentHtml . '</tbody></table></div>' .
            $signatories .
            '</body></html>';
    }

    /**
     * Get HR assessment for an interview
     */
    public function getHrAssessment(int $interviewId): array
    {
        $assessment = $this->interviewModel->getHrAssessmentByInterview($interviewId);
        if (!$assessment) {
            return ['success' => false, 'message' => 'No HR assessment found'];
        }

        return ['success' => true, 'data' => $assessment];
    }

    /**
     * Save HR assessment (admin-only)
     */
    public function saveHrAssessment(int $interviewId, array $data): array
    {
        if (empty($_SESSION['employee_id'])) {
            return ['success' => false, 'message' => 'Permission denied'];
        }

        $userId = $_SESSION['employee_id'] ?? null;

        try {
            $saved = $this->interviewModel->saveHrAssessment($interviewId, $data, $userId);

            if ($saved) {
                return ['success' => true, 'message' => 'HR assessment saved successfully'];
            }

            return ['success' => false, 'message' => 'Failed to save HR assessment'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get scheduled interviews
     */
    public function getScheduledInterviews(): array
    {
        return $this->interviewModel->getScheduledInterviews();
    }

    /**
     * Get all interviews (support status filter)
     */
    public function getInterviews(?string $status = null, int $page = 1, int $limit = 10, string $search = ''): array
    {
        // Auto-archive interviews that have remained completed for longer than the configured interval.
        $this->interviewModel->archiveDueCompletedInterviews(3);

        return $this->interviewModel->getAllInterviews($status, $page, $limit, $search);
    }

    /**
     * Complete interview
     */
    public function completeInterview(int $interviewId): array
    {
        try {
            $interview = $this->interviewModel->getInterviewById($interviewId);
            if (!$interview) {
                return ['success' => false, 'message' => 'Interview not found'];
            }

            $scheduledDate = $interview['scheduled_date'] ?? null;
            $today = date('Y-m-d');
            $canComplete = !$scheduledDate || $scheduledDate <= $today;

            if (!$canComplete) {
                return [
                    'success' => false,
                    'message' => 'This interview cannot be completed yet because its scheduled date is still in the future.'
                ];
            }

            if (!$this->interviewModel->hasHrAssessmentContent($interviewId)) {
                return [
                    'success' => false,
                    'message' => 'HR assessment must have content before this interview can be completed.'
                ];
            }

            $success = $this->interviewModel->updateInterviewStatus($interviewId, 'completed');

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Interview marked as completed'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update interview status'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle AJAX requests for interviews
     */
    public function handleAjaxRequest(string $action, array $data = []): array
    {
        switch ($action) {
            case 'submit_interview':
                return $this->scheduleInterview($data);

            case 'update_interview':
                $interviewId = (int)($data['interview_id'] ?? 0);
                if ($interviewId <= 0) {
                    return ['success' => false, 'message' => 'Interview ID is required for update'];
                }

                $assignedInterviewerId = (int)($data['interviewer_id'] ?? $_SESSION['employee_id'] ?? 0);
                if ($assignedInterviewerId <= 0) {
                    return ['success' => false, 'message' => 'Exit HR staff is required to update the interview'];
                }

                $required = ['exit_case_type', 'exit_case_id', 'employee_id', 'scheduled_date', 'scheduled_time'];
                foreach ($required as $field) {
                    if (empty($data[$field])) {
                        return ['success' => false, 'message' => "Field '$field' is required for update"];
                    }
                }

                $data['interviewer_id'] = $assignedInterviewerId;

                $exitCaseType = $data['exit_case_type'];
                $exitCaseId = (int)$data['exit_case_id'];
                $approvedCase = $this->interviewModel->getApprovedExitCase($exitCaseType, $exitCaseId);
                $employeeId = (string)$data['employee_id'];

                if (!$approvedCase || (string)$approvedCase['employee_id'] !== $employeeId) {
                    return ['success' => false, 'message' => 'Selected exit case is not approved or does not match the employee'];
                }

                $assessmentData = [];
                if (!empty($data['assessment'])) {
                    if (is_array($data['assessment'])) {
                        $assessmentData = $data['assessment'];
                    } elseif (is_string($data['assessment'])) {
                        $decoded = json_decode($data['assessment'], true);
                        if (is_array($decoded)) {
                            $assessmentData = $decoded;
                        }
                    }
                }

                if (empty($assessmentData)) {
                    $assessmentData = [
                        'summary' => $data['hr_summary'] ?? null,
                        'key_findings' => $data['hr_key_findings'] ?? null,
                        'hr_recommendations' => $data['hr_recommendations'] ?? null,
                        'follow_up_actions' => $data['hr_follow_up_actions'] ?? null,
                        'rehire_eligibility' => $data['hr_rehire_eligibility'] ?? null,
                        'knowledge_transfer_required' => !empty($data['hr_knowledge_transfer']) ? 1 : 0
                    ];
                }

                unset($data['interview_id'], $data['assessment'], $data['hr_summary'], $data['hr_key_findings'], $data['hr_recommendations'], $data['hr_follow_up_actions'], $data['hr_rehire_eligibility'], $data['hr_knowledge_transfer']);
                $success = $this->interviewModel->updateInterview($interviewId, $data);

                if ($success) {
                    if (!empty($assessmentData)) {
                        $hrResult = $this->saveHrAssessment($interviewId, $assessmentData);
                        if (empty($hrResult['success'])) {
                            return $hrResult;
                        }
                    }

                    return ['success' => true, 'message' => 'Exit interview updated successfully', 'interview_id' => $interviewId];
                }

                return ['success' => false, 'message' => 'Failed to update exit interview'];

            case 'submit_feedback':
            case 'update_feedback':
                return $this->submitFeedback(
                    $data['interview_id'] ?? 0,
                    $data['feedback'] ?? []
                );

            case 'get_interview':
                return $this->getInterview($data['interview_id'] ?? 0);

            case 'get_scheduled_interviews':
                return $this->getScheduledInterviews();

            case 'get_interviews':
                $status = $data['status'] ?? null;
                $page = (int)($data['page'] ?? 1);
                $limit = (int)($data['limit'] ?? 10);
                $search = $data['search'] ?? '';
                return $this->getInterviews($status, $page, $limit, $search);

            case 'complete_interview':
                return $this->completeInterview($data['interview_id'] ?? 0);

            case 'archive_interview':
                return $this->archiveInterview($data['interview_id'] ?? 0);

            case 'unarchive_interview':
                return $this->unarchiveInterview($data['interview_id'] ?? 0);

            case 'get_archived_interviews':
                $page = (int)($data['page'] ?? 1);
                $limit = (int)($data['limit'] ?? 10);
                $search = $data['search'] ?? '';
                return $this->interviewModel->getArchivedInterviews($page, $limit, $search);

            case 'get_interview_details':
                return $this->getInterviewDetails($data['interview_id'] ?? 0);

            case 'get_hr_assessment':
                return $this->getHrAssessment($data['interview_id'] ?? 0);

            case 'save_hr_assessment':
                return $this->saveHrAssessment($data['interview_id'] ?? 0, $data['assessment'] ?? []);

            default:
                return parent::handleAjaxRequest($action, $data);
        }
    }

    /**
     * Verify current user can manage interview archives
     */
    private function userCanManageInterviewArchives(): bool
    {
        return !empty($_SESSION['employee_id']);
    }

    /**
     * Archive interview
     */
    public function archiveInterview(int $interviewId): array
    {
        if (empty($_SESSION['employee_id']) || !$this->userCanManageInterviewArchives()) {
            return ['success' => false, 'message' => 'Permission denied'];
        }

        try {
            $archiveReason = $_POST['archive_reason'] ?? 'Manual archive';
            $success = $this->interviewModel->archiveInterview($interviewId, $archiveReason);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Interview archived successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to archive interview'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Unarchive interview
     */
    public function unarchiveInterview(int $interviewId): array
    {
        if (empty($_SESSION['employee_id']) || !$this->userCanManageInterviewArchives()) {
            return ['success' => false, 'message' => 'Permission denied'];
        }

        try {
            $success = $this->interviewModel->unarchiveInterview($interviewId);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Interview unarchived successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to unarchive interview'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get interview details for archiving
     */
    private function getInterviewDetails(int $interviewId): array
    {
        try {
            if (!is_numeric($interviewId) || $interviewId <= 0) {
                return [
                    'success' => false,
                    'message' => 'A valid interview ID is required'
                ];
            }

            $interview = $this->interviewModel->getInterviewById($interviewId);

            if (!$interview) {
                return [
                    'success' => false,
                    'message' => 'Interview not found'
                ];
            }

            $employeeName = 'Unknown';
            if (!empty($interview['employee_id'])) {
                $employee = $this->interviewModel->getEmployeeById($interview['employee_id']);
                if ($employee) {
                    $employeeName = $employee['full_name'] ?? trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
                }
            }
            if ($employeeName === 'Unknown' && !empty($interview['employee_full_name'])) {
                $employeeName = $interview['employee_full_name'];
            }

            $interviewerName = 'Unknown';
            if (!empty($interview['interviewer_id'])) {
                $interviewer = $this->interviewModel->getUserById($interview['interviewer_id']);
                if ($interviewer) {
                    $interviewerName = $interviewer['full_name'] ?? trim(($interviewer['first_name'] ?? '') . ' ' . ($interviewer['last_name'] ?? ''));
                }
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $interview['id'],
                    'employee_id' => $interview['employee_id'] ?? null,
                    'employee_name' => $employeeName,
                    'interviewer_name' => $interviewerName,
                    'scheduled_date' => $interview['scheduled_date'] ?? null,
                    'status' => $interview['status'] ?? null
                ]
            ];
        } catch (Exception $e) {
            error_log("Error getting interview details: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while retrieving interview details: ' . $e->getMessage()
            ];
        }
    }
}