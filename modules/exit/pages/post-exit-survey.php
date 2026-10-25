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
            <?php $alertId = 'survey-action-alert'; $alertIcon = 'fas fa-clipboard-check'; $alertMessage = 'Pending case surveys require follow-up'; $alertCount = 0; $alertViewAction = 'active'; include __DIR__ . '/../includes/action-alert.php'; ?>
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
                            <option value="pending">Pending</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <table id="surveys-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Case</th>
                                <th>Scheduled</th>
                                <th>Progress</th>
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

    <div class="modal fade exit-modal" id="manageSurveyModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-check mr-2"></i>Manage Post-Exit Survey
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <div class="font-weight-bold" id="manageSurveyEmployee">Employee</div>
                            <div class="small text-muted" id="manageSurveyCaseLabel">Case</div>
                        </div>
                        <span id="manageSurveyProgressBadge" class="badge badge-secondary">0 / 0 rated</span>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="manageSurveyDate" class="font-weight-bold">Scheduled Date</label>
                            <input type="date" id="manageSurveyDate" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="manageSurveyTime" class="font-weight-bold">Scheduled Time</label>
                            <input type="time" id="manageSurveyTime" class="form-control">
                        </div>
                    </div>

                    <div id="manageSurveyQuestions" class="mt-3"></div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                    <div>
                        <button type="button" class="btn btn-primary d-none" id="startSurveyBtn">
                            <i class="fas fa-play mr-2"></i>Start Survey
                        </button>
                        <button type="button" class="btn btn-success d-none" id="completeSurveyBtn">
                            <i class="fas fa-check-circle mr-2"></i>Complete Survey
                        </button>
                        <button type="button" class="btn btn-primary d-none" id="saveManageSurveyBtn">
                            <i class="fas fa-save mr-2"></i>Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="customToastContainer" style="position: fixed; top: 1rem; right: 1rem; z-index: 11000; display: flex; flex-direction: column; gap: .75rem;"></div>

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
