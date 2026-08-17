<?php

require_once __DIR__ . '/../models/SurveyModel.php';

class SurveyController extends ExitManagementController
{
    private SurveyModel $surveyModel;

    public function __construct()
    {
        parent::__construct();
        $this->surveyModel = new SurveyModel();
    }

    /**
     * Create survey
     */
    public function createSurvey(array $data): array
    {
        try {
            $required = ['title'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }

            if (!empty($data['employee_id']) || !empty($data['exit_case_type']) || !empty($data['exit_case_id'])) {
                if (empty($data['employee_id']) || empty($data['exit_case_type']) || empty($data['exit_case_id'])) {
                    return ['success' => false, 'message' => 'Employee and exit case selection are required for a scheduled post-exit survey.'];
                }
            }

            $data['created_by'] = $_SESSION['employee_id'] ?? 0;
            $surveyId = $this->surveyModel->createSurvey($data);

            return [
                'success' => true,
                'message' => 'Survey created successfully',
                'survey_id' => $surveyId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Submit survey response
     */
    public function submitSurveyResponse(int $surveyId, int $employeeId, array $responses, ?string $exitCaseType = null, ?int $exitCaseId = null, ?string $surveyType = null, ?string $scheduledDate = null, ?string $scheduledTime = null): array
    {
        try {
            if (($employeeId === 0 || empty($employeeId)) && $exitCaseType && $exitCaseId) {
                $exitCase = $this->surveyModel->getExitCaseDetails($exitCaseType, $exitCaseId);
                if ($exitCase && !empty($exitCase['employee_id'])) {
                    $employeeId = (int)$exitCase['employee_id'];
                }
            }

            if ($employeeId === 0) {
                return ['success' => false, 'message' => 'Employee ID is required for survey submission.'];
            }

            $success = $this->surveyModel->submitSurveyResponse($surveyId, $employeeId, $responses, $exitCaseType, $exitCaseId, $surveyType, $scheduledDate, $scheduledTime);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Survey response submitted successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to submit survey response'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get survey details
     */
    public function getSurvey(int $surveyId): array
    {
        $survey = $this->surveyModel->getSurveyById($surveyId);

        if (!$survey) {
            return ['error' => 'Survey not found'];
        }

        // Get questions
        $questions = $this->surveyModel->getSurveyQuestions($surveyId);
        $survey['questions'] = $questions;

        return $survey;
    }

    /**
     * Get active surveys for employee
     */
    public function getActiveSurveysForEmployee(int $employeeId): array
    {
        return $this->surveyModel->getActiveSurveysForEmployee($employeeId);
    }

    /**
     * Generate survey report
     */
    public function generateSurveyReport(int $surveyId): array
    {
        return $this->surveyModel->generateSurveyReport($surveyId);
    }

    /**
     * Get survey responses
     */
    public function getSurveyResponses(int $surveyId): array
    {
        return $this->surveyModel->getSurveyResponses($surveyId);
    }

    /**
     * Get survey response details
     */
    public function getSurveyResponseDetails(int $responseId): array
    {
        return $this->surveyModel->getSurveyResponseDetails($responseId);
    }

    /**
     * Get all surveys with optional status filter
     */
    public function getSurveys(?string $status = null): array
    {
        return $this->surveyModel->getAllSurveys($status);
    }

    /**
     * Duplicate survey
     */
    public function duplicateSurvey(int $surveyId): array
    {
        try {
            $newSurveyId = $this->surveyModel->duplicateSurvey($surveyId);

            if ($newSurveyId) {
                return [
                    'success' => true,
                    'message' => 'Survey duplicated successfully',
                    'survey_id' => $newSurveyId
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to duplicate survey'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle AJAX requests for surveys
     */
    public function handleAjaxRequest(string $action, array $data = []): array
    {
        switch ($action) {
            case 'submit_survey':
            case 'update_survey':
            case 'create_survey':
                return $this->createSurvey($data);

            case 'submit_survey_response':
                $employeeId = $data['employee_id'] ?? 0;
                if ($employeeId == 0 && isset($_SESSION['employee_id'])) {
                    $employeeId = $_SESSION['employee_id'];
                }
                return $this->submitSurveyResponse(
                    $data['survey_id'] ?? 0,
                    $employeeId,
                    $data['responses'] ?? [],
                    $data['exit_case_type'] ?? null,
                    isset($data['exit_case_id']) ? (int)$data['exit_case_id'] : null,
                    $data['survey_type'] ?? null,
                    $data['scheduled_date'] ?? null,
                    $data['scheduled_time'] ?? null
                );

            case 'get_survey':
                return $this->getSurvey($data['survey_id'] ?? 0);

            case 'get_active_surveys':
                return $this->getActiveSurveysForEmployee($data['employee_id'] ?? 0);

            case 'generate_survey_report':
                return $this->generateSurveyReport($data['survey_id'] ?? 0);

            case 'get_survey_responses':
                return $this->getSurveyResponses($data['survey_id'] ?? 0);

            case 'get_response_details':
                return $this->getSurveyResponseDetails($data['response_id'] ?? 0);

            case 'get_surveys':
                return $this->surveyModel->getAllSurveys(
                    $data['status'] ?? null,
                    $data['page'] ?? 1,
                    $data['limit'] ?? 10,
                    $data['search'] ?? ''
                );

            case 'duplicate_survey':
                return $this->duplicateSurvey($data['survey_id'] ?? 0);

            case 'archive_survey':
                return $this->archiveSurvey($data['survey_id'] ?? 0);

            case 'unarchive_survey':
                return $this->unarchiveSurvey($data['survey_id'] ?? 0);

            case 'get_survey_details':
                return $this->getSurveyDetails($data['survey_id'] ?? 0);

            default:
                return parent::handleAjaxRequest($action, $data);
        }
    }

    /**
     * Archive survey
     */
    public function archiveSurvey(int $surveyId): array
    {
        try {
            $archiveReason = $_POST['archive_reason'] ?? 'Manual archive';
            $success = $this->surveyModel->archiveSurvey($surveyId, $archiveReason);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Survey archived successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to archive survey'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Unarchive survey
     */
    public function unarchiveSurvey(int $surveyId): array
    {
        try {
            $success = $this->surveyModel->unarchiveSurvey($surveyId);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Survey unarchived successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to unarchive survey'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get survey details for archiving
     */
    private function getSurveyDetails(int $surveyId): array
    {
        try {
            if (empty($surveyId)) {
                return [
                    'success' => false,
                    'message' => 'Survey ID is required'
                ];
            }

            $survey = $this->surveyModel->getSurvey($surveyId);

            if (!$survey) {
                return [
                    'success' => false,
                    'message' => 'Survey not found'
                ];
            }

            // For surveys, we don't have a specific employee, so we'll use a generic approach
            return [
                'success' => true,
                'data' => [
                    'id' => $survey['id'],
                    'employee_id' => 0, // Surveys are not employee-specific
                    'employee_name' => 'All Employees',
                    'title' => $survey['title'],
                    'start_date' => $survey['start_date'],
                    'end_date' => $survey['end_date'],
                    'status' => $survey['status']
                ]
            ];
        } catch (Exception $e) {
            error_log("Error getting survey details: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while retrieving survey details'
            ];
        }
    }
}