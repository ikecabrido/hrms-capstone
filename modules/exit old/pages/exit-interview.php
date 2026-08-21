<?php
$currentRoleName = $_SESSION['role_name'] ?? 'Exit';
?>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="assets/css/custom.css">
<script>
    window.exitManagementUserRole = <?php echo json_encode($currentRoleName); ?>;
    window.exitManagementUserId = <?php echo json_encode($_SESSION['employee_id'] ?? null); ?>;
</script>

    <div class="module-header">
        <h1>Exit Interviews</h1>
    </div>

    <div class="module-content">
        <div id="interviews-section" class="section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2" style="flex: 1;">
                        <div class="input-group input-group-sm" style="flex: 19;">
                            <input type="text" id="interview-search" class="form-control" placeholder="Search interviews..." onkeyup="onInterviewSearchChange()">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                        <select id="interview-status-filter" class="form-control form-control-sm" onchange="onInterviewStatusFilterChange()" style="flex: 1; white-space: nowrap;">
                            <option value="all">All</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="card-tools d-flex align-items-center">
                        <button type="button" id="viewArchivedInterviewsButton" class="btn btn-warning btn-sm mr-2" onclick="openArchiveModal()">
                            <i class="fas fa-box-open"></i> Archived <span id="archive-notif-count" class="badge badge-danger ml-1" style="display:none;">0</span>
                        </button>
                        <button type="button" class="btn btn-success btn-sm" onclick="showInterviewModal()">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="interviews-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Interviewer</th>
                                    <th>Scheduled Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="interviews-tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="customToastContainer" style="position: fixed; top: 1rem; right: 1rem; z-index: 11000; display: flex; flex-direction: column; gap: .75rem;"></div>

    <!-- Exit Interview Modal -->
    <div class="modal fade exit-modal" id="interviewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="interviewModalTitle">Schedule Exit Interview</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="interviewForm">
                    <div class="modal-body">
                        <input type="hidden" id="interviewId" name="interview_id">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="interviewCaseSelect">Approved Exit Case *</label>
                                    <select class="form-control" id="interviewCaseSelect" required>
                                        <option value="">Select Approved Exit Case</option>
                                    </select>
                                    <div id="interviewCaseDisplay" class="form-control-plaintext" style="display:none; white-space: normal; word-break: break-word;"></div>
                                    <small id="interviewCaseHelpText" class="form-text text-danger" style="display:none;">Please select an approved exit case before scheduling the interview.</small>
                                    <input type="hidden" id="interviewExitCaseType" name="exit_case_type" />
                                    <input type="hidden" id="interviewExitCaseId" name="exit_case_id" />
                                    <input type="hidden" id="interviewEmployeeId" name="employee_id" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="interviewerSelect">Interviewer *</label>
                                    <select class="form-control" id="interviewerSelect" name="interviewer_id" required>
                                        <option value="">Select Interviewer</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="interviewDate">Interview Date *</label>
                                    <input type="date" class="form-control" id="interviewDate" name="scheduled_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Interview Time *</label>
                                    <div class="row">
                                        <div class="col-4">
                                            <input type="number" class="form-control" id="interviewHour" min="1" max="12" placeholder="HH">
                                        </div>
                                        <div class="col-4">
                                            <input type="number" class="form-control" id="interviewMinute" min="0" max="59" step="5" placeholder="MM">
                                        </div>
                                        <div class="col-4">
                                            <select class="form-control" id="interviewMeridiem">
                                                <option value="AM">AM</option>
                                                <option value="PM">PM</option>
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" id="interviewTime" name="scheduled_time">
                                    <small class="form-text text-muted">Enter the interview time in separate fields.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="interviewLocation">Location</label>
                            <input type="text" class="form-control" id="interviewLocation" name="location" value="Virtual">
                        </div>

                        <div class="form-group">
                            <label for="interviewNotes">Notes</label>
                            <textarea class="form-control" id="interviewNotes" name="notes" rows="2"></textarea>
                        </div>

                        <!-- Feedback section (for completed interviews) -->
                        <div id="feedbackSection" style="display: none;">
                            <hr>
                            <h6>Interview Feedback</h6>
                            <div class="form-group">
                                <label for="interviewOverallSatisfaction">Overall Satisfaction</label>
                                <select class="form-control" id="interviewOverallSatisfaction" name="overall_satisfaction">
                                    <option value="">Select Rating</option>
                                    <option value="1">1 - Very Dissatisfied</option>
                                    <option value="2">2 - Dissatisfied</option>
                                    <option value="3">3 - Neutral</option>
                                    <option value="4">4 - Satisfied</option>
                                    <option value="5">5 - Very Satisfied</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="interviewWorkEnvironmentRating">Work Environment Rating</label>
                                    <select class="form-control" id="interviewWorkEnvironmentRating" name="work_environment_rating">
                                        <option value="">Select Rating</option>
                                        <option value="1">1</option><option value="2">2</option>
                                        <option value="3">3</option><option value="4">4</option><option value="5">5</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="interviewManagementRating">Management Rating</label>
                                    <select class="form-control" id="interviewManagementRating" name="management_rating">
                                        <option value="">Select Rating</option>
                                        <option value="1">1</option><option value="2">2</option>
                                        <option value="3">3</option><option value="4">4</option><option value="5">5</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="interviewCompensationRating">Compensation Rating</label>
                                    <select class="form-control" id="interviewCompensationRating" name="compensation_rating">
                                        <option value="">Select Rating</option>
                                        <option value="1">1</option><option value="2">2</option>
                                        <option value="3">3</option><option value="4">4</option><option value="5">5</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="interviewWorkLifeBalanceRating">Work-Life Balance Rating</label>
                                    <select class="form-control" id="interviewWorkLifeBalanceRating" name="work_life_balance_rating">
                                        <option value="">Select Rating</option>
                                        <option value="1">1</option><option value="2">2</option>
                                        <option value="3">3</option><option value="4">4</option><option value="5">5</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="interviewReasonForLeaving">Reason for Leaving</label>
                                <textarea class="form-control" id="interviewReasonForLeaving" name="reason_for_leaving" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="interviewWouldRecommend">Would Recommend Working Here?</label>
                                <select class="form-control" id="interviewWouldRecommend" name="would_recommend">
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="interviewAdditionalComments">Additional Comments</label>
                                <textarea class="form-control" id="interviewAdditionalComments" name="additional_comments" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Employee Info (read-only) -->
                        <div id="employeeInfoSection" class="mt-3" style="display: none;">
                            <hr>
                            <h6>Employee Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Employee</label>
                                        <div id="employeeFullName" class="form-control-plaintext"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Department</label>
                                        <div id="employeeDepartment" class="form-control-plaintext"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Position</label>
                                        <div id="employeePosition" class="form-control-plaintext"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Date Hired</label>
                                        <div id="employeeDateHired" class="form-control-plaintext"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Years of Service</label>
                                        <div id="employeeYearsOfService" class="form-control-plaintext"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Manager</label>
                                        <div id="employeeManager" class="form-control-plaintext"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Exit Case Info (read-only) -->
                        <div id="exitCaseInfoSection" class="mt-3" style="display: none;">
                            <hr>
                            <h6>Exit Case Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Exit Reason</label>
                                        <div id="exitCaseReason" class="form-control-plaintext"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Notice Date</label>
                                        <div id="exitCaseNoticeDate" class="form-control-plaintext"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Last Working / Effective Date</label>
                                        <div id="exitCaseDate" class="form-control-plaintext"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Case Approved By / At</label>
                                        <div id="exitCaseApproved" class="form-control-plaintext"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Engagement placeholder -->
                        <div id="engagementSection" class="mt-3" style="display: none;">
                            <hr>
                            <h6>Employee Engagement</h6>
                            <div class="form-group">
                                <div id="engagementPlaceholder" class="text-muted">Survey integration placeholder (post-exit surveys will appear here).</div>
                            </div>
                        </div>

                        <!-- HR Assessment (admin editable) -->
                        <div id="hrAssessmentSection" class="mt-3" style="display: none;">
                            <hr>
                            <h6>HR Assessment</h6>
                            <div class="form-group">
                                <label for="hrSummary">Interview Summary</label>
                                <textarea class="form-control" id="hrSummary" name="hr_summary" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="hrKeyFindings">Key Findings</label>
                                <textarea class="form-control" id="hrKeyFindings" name="hr_key_findings" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="hrRecommendations">HR Recommendations</label>
                                <textarea class="form-control" id="hrRecommendations" name="hr_recommendations" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="hrFollowUpActions">Follow-up Actions</label>
                                <textarea class="form-control" id="hrFollowUpActions" name="hr_follow_up_actions" rows="2"></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="hrRehireEligibility">Rehire Eligibility</label>
                                    <select class="form-control" id="hrRehireEligibility" name="hr_rehire_eligibility">
                                        <option value="">Select</option>
                                        <option value="yes">Yes</option>
                                        <option value="no">No</option>
                                        <option value="conditional">Conditional</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="hrKnowledgeTransfer">Knowledge Transfer Required</label>
                                    <div class="form-control-plaintext"><input type="checkbox" id="hrKnowledgeTransfer" name="hr_knowledge_transfer"> Yes</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-info" id="editInterviewBtn" style="display:none;">Edit Interview</button>
                        <button type="button" class="btn btn-primary" id="saveHrAssessmentBtn" style="display:none;">Save HR Assessment</button>
                        <button type="submit" class="btn btn-success" id="interviewSubmitBtn">Schedule Interview</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archived Exit Interviews Modal -->
    <div class="modal fade exit-modal" id="archivedInterviewsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-secondary">
                    <h5 class="modal-title">Archived Exit Interviews</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Interviewer</th>
                                    <th>Scheduled Date</th>
                                    <th>Status</th>
                                    <th>Archived At</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="archived-interviews-tbody">
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Loading archived interviews...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="archived-interviews-pagination" class="mt-2 d-flex justify-content-end"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="assets/js/custom.js"></script>
<script>
    if (typeof loadInterviewsTable === 'function') {
        loadInterviewsTable('all', 1, '');
    }
    if (typeof loadEmployees === 'function') {
        loadEmployees();
    }
</script>
