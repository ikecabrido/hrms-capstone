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
        <h1>Resignations</h1>
    </div>

    <div class="module-content">
        <div id="resignations-section" class="section">
            <?php $alertId = 'resignation-action-alert'; $alertIcon = 'fas fa-exclamation-triangle'; $alertMessage = 'Pending resignations need approval review'; $alertCount = 0; $alertViewAction = 'pending_review'; include __DIR__ . '/../includes/action-alert.php'; ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2" style="flex: 1;">
                        <div class="input-group input-group-sm" style="flex: 19;">
                            <input type="text" id="resignation-search" class="form-control" placeholder="Search resignations..." onkeyup="onResignationSearchChange()">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                        <select id="resignation-status-filter" class="form-control form-control-sm" onchange="onResignationStatusFilterChange()" style="flex: 1; white-space: nowrap;">
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="pending_review">Pending Review</option>
                            <option value="pending_legal_review">Pending Legal Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="rejected_by_legal">Rejected by Legal</option>
                            <option value="withdrawn">Withdrawn</option>
                            <option value="archived">Archived</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                    <div class="card-tools d-flex align-items-center">
                        <button id="new-resignation-btn" type="button" class="btn btn-success btn-sm mr-2" onclick="showResignationModal()">
                            <i class="fas fa-plus"></i> New Resignation
                        </button>
                        <button id="open-archived-resignations" type="button" class="btn btn-warning btn-sm" onclick="openArchivedResignationsModal()">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="resignations-table" class="table table-bordered table-striped table-sm">
                            <colgroup>
                                <col style="width: 15%;"><col style="width: 10%;"><col style="width: 12%;">
                                <col style="width: 10%;"><col style="width: 10%;"><col style="width: 8%;">
                                <col style="width: 9%;"><col style="width: 8%;"><col style="width: 10%;">
                                <col style="width: 8%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th>Reason</th>
                                    <th>Notice Date</th>
                                    <th>Last Working Date</th>
                                    <th>Comments</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="resignations-tbody">
                            </tbody>
                        </table>
                    </div>

                    <div id="archived-resignations-container" class="mt-4" style="display: none;">
                        <h5>Archived Resignations</h5>
                        <div class="table-responsive">
                            <table id="archived-resignations-table" class="table table-bordered table-striped table-sm">
                                <colgroup>
                                    <col style="width: 15%;"><col style="width: 10%;"><col style="width: 12%;">
                                    <col style="width: 10%;"><col style="width: 10%;"><col style="width: 8%;">
                                    <col style="width: 9%;"><col style="width: 8%;"><col style="width: 10%;">
                                    <col style="width: 8%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Email</th>
                                        <th>Position</th>
                                        <th>Reason</th>
                                        <th>Notice Date</th>
                                        <th>Last Working Date</th>
                                        <th>Comments</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="archived-resignations-tbody">
                                </tbody>
                            </table>
                        </div>
                        <div id="archived-resignations-pagination" class="mt-2 d-flex justify-content-end"></div>
                    </div>

                    <div id="resignations-pagination" class="mt-3 d-flex justify-content-between align-items-center"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="customToastContainer" style="position: fixed; top: 1rem; right: 1rem; z-index: 11000; display: flex; flex-direction: column; gap: .75rem;"></div>

    <!-- Archive Resignation Modal -->
    <div class="modal fade exit-modal" id="archiveResignationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Archive Resignation</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="archiveResignationForm">
                    <div class="modal-body">
                        <input type="hidden" id="archiveResignationId" name="resignation_id">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Archiving will move this resignation record to the archive and remove it from active lists.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archiveEmployeeId">Employee ID</label>
                                    <input type="text" class="form-control" id="archiveEmployeeId" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archiveEmployeeName">Employee Name</label>
                                    <input type="text" class="form-control" id="archiveEmployeeName" readonly>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="archiveReason" name="archive_reason" value="Process completed; archived.">
                        <div class="form-group">
                            <label>Archive Reason</label>
                            <div class="form-control-plaintext">Process completed; archived.</div>
                            <small class="form-text text-muted">This reason is generated automatically when the process completes.</small>
                        </div>

                        <div class="form-group">
                            <label for="archiveNotes">Additional Notes (Optional)</label>
                            <textarea class="form-control" id="archiveNotes" name="archive_notes" rows="2" placeholder="Any additional notes about this archive action..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-archive"></i> Archive Resignation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Resignation Review Modal -->
    <div class="modal fade exit-modal" id="resignationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="resignationModalTitle">Submit Resignation</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="resignationForm">
                    <div class="modal-body">
                        <input type="hidden" id="resignationId" name="resignation_id">

                        <div class="resignation-section">
                            <div class="resignation-section-header">Employee Information</div>

                            <div class="resignation-grid resignation-grid--two">
                                <div class="resignation-field">
                                    <label for="employeeSelect">Employee <span class="text-danger">*</span></label>
                                    <select class="form-control" id="employeeSelect" name="employee_id" required>
                                        <option value="">Select Employee</option>
                                    </select>
                                    <div id="employeeDisplay" class="form-control-plaintext resignation-value-box d-none"></div>
                                    <div id="eligibilityMessage" class="resignation-helper d-none"></div>
                                    <div class="resignation-field-helper">Last attendance: Not available</div>
                                </div>

                                <div class="resignation-field d-none" id="resignationTypeGroup">
                                    <label for="resignationType">Resignation Type</label>
                                    <select class="form-control" id="resignationType" name="resignation_type">
                                        <option value="">Select Type</option>
                                        <option value="With Notice">With Notice</option>
                                        <option value="Immediate">Immediate</option>
                                    </select>
                                </div>
                            </div>

                            <div class="resignation-grid resignation-grid--two">
                                <div class="resignation-field">
                                    <label for="employeeDepartment">Department</label>
                                    <input type="text" class="form-control resignation-readonly" id="employeeDepartment" name="employee_department" readonly>
                                </div>
                                <div class="resignation-field">
                                    <label for="employeePosition">Position</label>
                                    <input type="text" class="form-control resignation-readonly" id="employeePosition" name="employee_position" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="resignation-section">
                            <div class="resignation-section-header">Resignation Details</div>

                            <div class="resignation-field">
                                <label for="reason">Reason for Separation <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                                <div id="reasonDisplay" class="form-control-plaintext resignation-value-box d-none"></div>
                            </div>

                            <div class="resignation-grid resignation-grid--two">
                                <div class="resignation-field">
                                    <label for="noticeDate">Date of Submission <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="noticeDate" name="notice_date" required>
                                    <div id="noticeDateDisplay" class="form-control-plaintext resignation-value-box d-none"></div>
                                </div>
                                <div class="resignation-field">
                                    <label for="lastWorkingDate">Intended Last Working Day <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="lastWorkingDate" name="last_working_date" required>
                                    <div id="lastWorkingDateDisplay" class="form-control-plaintext resignation-value-box d-none"></div>
                                </div>
                            </div>

                            <div class="resignation-field">
                                <label for="comments">Additional Comments</label>
                                <textarea class="form-control" id="comments" name="comments" rows="2"></textarea>
                                <div id="commentsDisplay" class="form-control-plaintext resignation-value-box d-none"></div>
                            </div>
                        </div>

                        <div class="resignation-section">
                            <div class="resignation-section-header">Attachment</div>

                            <div class="resignation-field">
                                <label for="resignationLetterFile">Resignation Letter Attachment</label>
                                <div class="resignation-file-picker">
                                    <input type="file" id="resignationLetterFile" name="resignation_letter" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                    <div class="resignation-file-hint">PDF, JPG, PNG, DOC, DOCX - max 5 MB</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Resignation Letter Preview</label>
                            <div id="resignationLetterPreview" class="border rounded bg-light p-3" style="min-height: 220px; background: #fff; overflow: auto;"></div>
                        </div>

                        <div class="resignation-section d-none" id="resignationLetterSection">
                            <div class="resignation-section-header">Generated Resignation Letter</div>
                            <div class="form-control-plaintext resignation-value-box">
                                <a id="resignationLetterLink" href="#" target="_blank" rel="noopener" class="d-none">
                                    <i class="fas fa-file-alt mr-1"></i><span id="resignationLetterName">View resignation letter</span>
                                </a>
                                <span id="resignationLetterMissing" class="text-danger d-none">No resignation letter attached.</span>
                            </div>
                        </div>

                        <div id="approvalSection" class="resignation-approval d-none">
                            <div class="resignation-approval-title">Approval</div>
                            <div class="form-group mb-3">
                                <label for="approvalStatus">Status</label>
                                <select class="form-control" id="approvalStatus" name="status">
                                    <option value="pending_legal_review">Approve HR review (send to Legal)</option>
                                    <option value="approved">Approve final</option>
                                    <option value="rejected">Reject</option>
                                    <option value="rejected_by_legal">Reject by Legal</option>
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label for="approvalComments">Approval Comments</label>
                                <textarea class="form-control" id="approvalComments" name="approval_comments" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="resignationSubmitBtn">Submit Resignation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archived Resignations Modal -->
    <div class="modal fade exit-modal" id="archivedResignationsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Archived Resignations</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <colgroup>
                                <col style="width: 15%;"><col style="width: 10%;"><col style="width: 12%;">
                                <col style="width: 14%;"><col style="width: 10%;"><col style="width: 8%;">
                                <col style="width: 9%;"><col style="width: 8%;"><col style="width: 14%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th>Reason</th>
                                    <th>Notice Date</th>
                                    <th>Last Working Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="modal-archived-resignations-tbody">
                                <tr><td colspan="10" class="text-center text-muted">Loading archived resignations...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="modal-archived-resignations-pagination" class="mt-2 d-flex justify-content-end"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<script src="assets/vendor/flatpickr/flatpickr.min.js"></script>
<script>
    if (typeof loadResignationsTable === 'function') {
        loadResignationsTable('active', 1, '');
    }
    if (typeof loadEmployees === 'function') {
        loadEmployees();
    }
</script>
