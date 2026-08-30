<?php
$currentRoleName = $_SESSION['role_name'] ?? 'Exit';
?>
<link rel="stylesheet" href="assets/vendor/flatpickr/flatpickr.min.css">
<link rel="stylesheet" href="assets/css/custom.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/custom.css'); ?>">
<script>
    window.exitManagementUserRole = <?php echo json_encode($currentRoleName); ?>;
    window.exitManagementUserId = <?php echo json_encode($_SESSION['employee_id'] ?? null); ?>;
</script>

    <div class="module-header">
        <h1>Post-Exit Survey</h1>
    </div>

    <div class="module-content">
        <div id="surveys-section" class="section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2" style="flex: 1;">
                        <div class="input-group input-group-sm" style="flex: 19;">
                            <input type="text" id="survey-search" class="form-control" placeholder="Search surveys..." onkeyup="onSurveySearchChange()">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                        <select id="survey-status-filter" class="form-control form-control-sm" onchange="onSurveyStatusFilterChange()" style="flex: 1; white-space: nowrap;">
                            <option value="all">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="card-tools d-flex align-items-center">
                        <button id="showSurveyModalBtn" type="button" class="btn btn-success btn-sm" onclick="showSurveyModal()" aria-label="Schedule Post-Exit Survey">
                            <i class="fas fa-plus"></i> Schedule Survey
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="surveys-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Survey Title</th>
                                <th>Schedule Date</th>
                                <th>Schedule Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="surveys-tbody">
                        </tbody>
                    </table>
                    <div id="surveys-pagination" class="d-flex justify-content-center mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="customToastContainer" style="position: fixed; top: 1rem; right: 1rem; z-index: 11000; display: flex; flex-direction: column; gap: .75rem;"></div>

    <!-- Schedule Survey Modal -->
    <div class="modal fade exit-modal" id="surveyModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="surveyModalTitle">
                        <i class="fas fa-calendar-check mr-2"></i>Schedule Post-Exit Survey
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="surveyForm">
                    <div class="modal-body">
                        <input type="hidden" id="surveyId" name="survey_id">

                        <div class="card mb-4 border-left-primary">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-user-check text-primary mr-2"></i>Eligible Employee</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-0">
                                    <label for="surveyEmployeeSelect" class="font-weight-bold">Employee <span class="text-danger">*</span></label>
                                    <select class="form-control" id="surveyEmployeeSelect" name="employee_id" required>
                                        <option value="">Select eligible employee</option>
                                    </select>
                                    <small class="form-text text-muted">Only employees with an approved exit case that completed the required workflow are available. The eligible exit case is auto-selected for them.</small>
                                    <input type="hidden" id="surveyExitCaseType" name="exit_case_type">
                                    <input type="hidden" id="surveyExitCaseId" name="exit_case_id">
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4 border-left-info">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-calendar text-info mr-2"></i>Survey Schedule</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="surveyTitle" class="font-weight-bold">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="surveyTitle" name="title" value="Post-Exit Survey" required>
                                </div>

                                <div class="form-group">
                                    <label for="surveyDescription" class="font-weight-bold">Notes</label>
                                    <textarea class="form-control" id="surveyDescription" name="description" rows="2" placeholder="Add instructions or reminders for the exit survey."></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="surveyScheduledDate" class="font-weight-bold">Scheduled Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="surveyScheduledDate" name="scheduled_date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="surveyScheduledTime" class="font-weight-bold">Scheduled Time <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control" id="surveyScheduledTime" name="scheduled_time" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info alert-sm mb-0" role="alert">
                            <i class="fas fa-lightbulb mr-2"></i>
                            The survey will use the default 15-question post-exit review and can be approved only after the employee has completed all 15 items.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="surveySubmitBtn">
                            <i class="fas fa-save mr-2"></i>Schedule Survey
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Record Feedback Modal -->
    <div class="modal fade exit-modal" id="answerSurveyModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="answerSurveyTitle">Record Post-Exit Feedback</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="answerSurveyForm">
                    <div class="modal-body">
                        <input type="hidden" id="answerSurveyId" name="survey_id">
                        <input type="hidden" id="answerSurveyEmployeeId" name="employee_id">
                        <input type="hidden" id="answerSurveyExitCaseType" name="exit_case_type">
                        <input type="hidden" id="answerSurveyExitCaseId" name="exit_case_id">

                        <div class="form-group">
                            <label for="answerSurveyCaseSelect">Eligible Exit Case *</label>
                            <select class="form-control" id="answerSurveyCaseSelect" required>
                                <option value="">Select Eligible Exit Case</option>
                            </select>
                            <small class="form-text text-muted">Only approved exit cases that have completed the required exit-management steps are shown here.</small>
                        </div>

                        <div class="card mb-3 border-left-info">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-clock text-info mr-2"></i>Survey Schedule</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="surveyType">Survey Type *</label>
                                            <select class="form-control" id="surveyType" name="survey_type" required>
                                                <option value="">Select Survey Type</option>
                                                <option value="post_exit_feedback">Post-Exit Feedback</option>
                                                <option value="exit_interview_summary">Exit Interview Summary</option>
                                                <option value="clearance_survey">Clearance Survey</option>
                                                <option value="other">Other</option>
                                            </select>
                                            <small class="form-text text-muted">Choose the type of survey being recorded.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="surveyScheduledDate">Survey Date *</label>
                                            <input type="date" class="form-control" id="surveyScheduledDate" name="scheduled_date" required>
                                            <small class="form-text text-muted">When the survey was administered or scheduled.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="surveyScheduledTime">Survey Time *</label>
                                            <input type="time" class="form-control" id="surveyScheduledTime" name="scheduled_time" required>
                                            <small class="form-text text-muted">Time the survey session was recorded.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="progress mb-4" style="height: 8px;">
                            <div class="progress-bar bg-success" id="surveyProgress" style="width: 0%"></div>
                        </div>

                        <div class="survey-info mb-4">
                            <h6 id="answerSurveyDesc" class="text-muted"></h6>
                        </div>

                        <div id="surveyQuestionContainer" class="text-center">
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" id="prevQuestionBtn" style="display: none;">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <div>
                            <span class="text-muted mr-3" id="questionCounter">Question 1 of 1</span>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="nextQuestionBtn">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                            <button type="submit" class="btn btn-success" id="answerSurveySubmitBtn" style="display: none;">
                                <i class="fas fa-paper-plane"></i> Submit Survey
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archive Survey Modal -->
    <div class="modal fade exit-modal" id="archiveSurveyModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Archive Survey</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="archiveSurveyForm">
                    <div class="modal-body">
                        <input type="hidden" id="archiveSurveyId" name="survey_id">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Archiving will move this survey record to the archive database.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archiveSurveyEmployeeId">Employee ID</label>
                                    <input type="text" class="form-control" id="archiveSurveyEmployeeId" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archiveSurveyEmployeeName">Employee Name</label>
                                    <input type="text" class="form-control" id="archiveSurveyEmployeeName" readonly>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="archiveSurveyReason" name="archive_reason" value="Process completed; archived.">
                        <div class="form-group">
                            <label>Archive Reason</label>
                            <div class="form-control-plaintext">Process completed; archived.</div>
                        </div>

                        <div class="form-group">
                            <label for="archiveSurveyNotes">Notes (optional)</label>
                            <textarea class="form-control" id="archiveSurveyNotes" name="archive_notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-archive"></i> Archive Survey
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src="assets/vendor/flatpickr/flatpickr.min.js"></script>
<script>
    if (typeof loadSurveysTable === 'function') {
        loadSurveysTable('all', 1, 10, '');
    }
    if (typeof loadEmployees === 'function') {
        loadEmployees();
    }
</script>
