<?php
$currentRoleName = $_SESSION['role_name'] ?? 'Exit';
?>
<link rel="stylesheet" href="assets/vendor/flatpickr/flatpickr.min.css">
<link rel="stylesheet" href="assets/css/custom.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/custom.css'); ?>">
<style>
    .termination-warning-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px 12px;
        margin: 4px 0;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.15);
        border-left: 3px solid #d39e00;
        line-height: 1.5;
    }

    .termination-warning-row i {
        margin-top: 2px;
        flex-shrink: 0;
    }

    .termination-warning-row strong {
        font-weight: 700;
    }

    #terminationStatusAlert {
        display: flex;
        flex-direction: column;
        gap: 2px;
        margin: 0;
        width: 100%;
        min-height: 72px;
        font-weight: 600;
        border-left: 5px solid #d39e00;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    #terminationStatusAlertText {
        display: flex;
        flex-direction: column;
        gap: 4px;
        width: 100%;
    }
</style>
<script>
    window.exitManagementUserRole = <?php echo json_encode($currentRoleName); ?>;
    window.exitManagementUserId = <?php echo json_encode($_SESSION['employee_id'] ?? null); ?>;
</script>

    <div class="module-header">
        <h1>Terminations</h1>
    </div>

    <div class="module-content">
        <div id="terminations-section" class="section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2" style="flex: 1;">
                        <div class="input-group input-group-sm" style="flex: 19;">
                            <input type="text" id="termination-search" class="form-control" placeholder="Search terminations..." onkeyup="onTerminationSearchChange()">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                        <select id="termination-status-filter" class="form-control form-control-sm" onchange="onTerminationStatusFilterChange()" style="flex: 1; white-space: nowrap;">
                            <option value="active">Active</option>
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
                        <button type="button" class="btn btn-success btn-sm mr-2" onclick="showTerminationModal()">
                            <i class="fas fa-plus"></i> New Termination
                        </button>
                        <button id="open-archived-terminations" type="button" class="btn btn-warning btn-sm" onclick="openArchivedTerminationsModal()">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="terminationStatusAlertWrapper" style="margin-bottom: 16px; min-height: 120px; max-height: 220px; overflow-y: auto; border: 1px solid #f0d36d; background: #fffaf0; border-radius: 6px; padding: 8px; position: relative;">
                        <div id="terminationStatusAlert" class="alert alert-warning" role="alert" style="display:none; margin: 0; font-weight: 600; border-left: 5px solid #d39e00; box-shadow: 0 2px 8px rgba(0,0,0,0.08); width: 100%; min-height: 72px; display: flex; align-items: center;">
                            <i class="fas fa-exclamation-triangle"></i>Termination Alert: <span id="terminationStatusAlertCount" style="font-weight: 700; margin-left: 4px;"></span>
                            <span id="terminationStatusAlertText">Termination status checks will appear here.</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="terminations-table" class="table table-bordered table-striped table-sm">
                            <colgroup>
                                <col style="width: 14%;"><col style="width: 10%;"><col style="width: 14%;">
                                <col style="width: 10%;"><col style="width: 12%;"><col style="width: 12%;">
                                <col style="width: 8%;"><col style="width: 10%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th>Reason</th>
                                    <th>Effective Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="terminations-tbody">
                            </tbody>
                        </table>
                    </div>
                    <div id="terminations-pagination" class="mt-3 d-flex justify-content-between align-items-center"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="customToastContainer" style="position: fixed; top: 1rem; right: 1rem; z-index: 11000; display: flex; flex-direction: column; gap: .75rem;"></div>

    <!-- Termination Modal -->
    <div id="terminationModal" class="termination-custom-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="termination-modal-backdrop" data-close="termination-modal"></div>

        <div class="termination-modal-dialog" role="document">
            <div class="termination-modal-content">
                <div class="termination-modal-header">
                    <h5 class="termination-modal-title" id="terminationModalTitle">Initiate Termination</h5>
                    <button type="button" class="termination-modal-close" data-close="termination-modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>

                <form id="terminationForm">
                    <div class="termination-modal-body">
                        <input type="hidden" id="terminationId" name="termination_id">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="terminationEmployeeSelect">Employee *</label>
                                    <select class="form-control" id="terminationEmployeeSelect" name="employee_id" required>
                                        <option value="">Select Employee</option>
                                    </select>
                                    <div id="terminationEmployeeDisplay" class="form-control-plaintext" style="display: none;"></div>
                                    <div id="terminationEligibilityMessage" class="mt-2" style="display: none;"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="terminationEffectiveDate">Effective Date *</label>
                                    <input type="date" class="form-control" id="terminationEffectiveDate" name="effective_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="terminationReason">Termination Reason *</label>
                            <textarea class="form-control" id="terminationReason" name="termination_reason" rows="3" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="terminationComments">Additional Comments</label>
                            <textarea class="form-control" id="terminationComments" name="comments" rows="2"></textarea>
                        </div>

                        <div class="form-group" id="terminationLetterSection" style="display: none;">
                            <label>Termination Letter</label>
                            <div id="terminationLetterPreviewWrapper" class="termination-letter-preview-wrapper">
                                <div id="terminationLetterContent" class="termination-letter-content">
                                    Generated termination letter preview appears here.
                                </div>
                            </div>
                        </div>

                        <div id="terminationApprovalSection" style="display: none;">
                            <hr>
                            <h6>Approval</h6>
                            <div class="form-group">
                                <label for="terminationApprovalStatus">Status</label>
                                <select class="form-control" id="terminationApprovalStatus" name="status">
                                    <option value="pending_legal_review">Send to Legal Review</option>
                                    <option value="approved">Approve</option>
                                    <option value="rejected">Reject</option>
                                    <option value="rejected_by_legal">Reject by Legal</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="terminationApprovalComments">Approval Comments</label>
                                <textarea class="form-control" id="terminationApprovalComments" name="approval_comments" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="termination-modal-footer">
                        <button type="button" class="btn btn-secondary" data-close="termination-modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="terminationSubmitBtn">Submit Termination</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archive Termination Modal -->
    <div class="modal fade exit-modal" id="archiveTerminationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Archive Termination</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="archiveTerminationForm">
                    <div class="modal-body">
                        <input type="hidden" id="archiveTerminationId" name="termination_id">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Archiving will move this termination record to the archive database.
                            The record will be completely removed from active terminations and stored in the exit_archive table.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archiveTerminationEmployeeId">Employee ID</label>
                                    <input type="text" class="form-control" id="archiveTerminationEmployeeId" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archiveTerminationEmployeeName">Employee Name</label>
                                    <input type="text" class="form-control" id="archiveTerminationEmployeeName" readonly>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="archiveTerminationReason" name="archive_reason" value="Process completed; archived.">
                        <div class="form-group">
                            <label>Archive Reason</label>
                            <div class="form-control-plaintext">Process completed; archived.</div>
                            <small class="form-text text-muted">This reason is generated automatically when the process completes.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-archive"></i> Archive Termination
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archived Terminations List Modal -->
    <div class="modal fade exit-modal" id="archivedTerminationsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title">Archived Terminations</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table id="modal-archived-terminations-table" class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th>Reason</th>
                                    <th>Effective Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="modal-archived-terminations-tbody">
                                <tr><td colspan="7" class="text-center text-muted">Loading archived terminations...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="modal-archived-terminations-pagination" class="mt-2 d-flex justify-content-end"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<script src="assets/vendor/flatpickr/flatpickr.min.js"></script>
<script>
    if (typeof loadTerminationsTable === 'function') {
        loadTerminationsTable('active', 1, '');
    }
    if (typeof loadTerminationStatusAlert === 'function') {
        loadTerminationStatusAlert();
    }
    if (typeof loadEmployees === 'function') {
        loadEmployees();
    }
</script>
