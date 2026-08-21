<?php
$currentRoleName = $_SESSION['role_name'] ?? 'Exit';
?>
<link rel="stylesheet" href="assets/vendor/flatpickr/flatpickr.min.css">
<link rel="stylesheet" href="assets/css/custom.css">
<script>
    window.exitManagementUserRole = <?php echo json_encode($currentRoleName); ?>;
    window.exitManagementUserId = <?php echo json_encode($_SESSION['employee_id'] ?? null); ?>;
</script>

    <div class="module-header">
        <h1>Resignations</h1>
    </div>

    <div class="module-content">
        <div id="resignations-section" class="section">
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
                        <button id="open-archived-resignations" type="button" class="btn btn-warning btn-sm mr-2" onclick="openArchivedResignationsModal()">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="resignations-table" class="table table-bordered table-striped table-sm">
                            <colgroup>
                                <col style="width: 15%;"><col style="width: 8%;"><col style="width: 14%;">
                                <col style="width: 10%;"><col style="width: 11%;"><col style="width: 8%;">
                                <col style="width: 10%;"><col style="width: 8%;"><col style="width: 10%;">
                                <col style="width: 6%;">
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
                                    <col style="width: 15%;"><col style="width: 8%;"><col style="width: 14%;">
                                    <col style="width: 10%;"><col style="width: 11%;"><col style="width: 8%;">
                                    <col style="width: 10%;"><col style="width: 8%;"><col style="width: 10%;">
                                    <col style="width: 6%;">
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

    <!-- Resignation Review Modal -->
    <div class="modal fade exit-modal" id="resignationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
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

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="employeeSelect">Employee *</label>
                                    <select class="form-control" id="employeeSelect" name="employee_id" required>
                                        <option value="">Select Employee</option>
                                    </select>
                                    <div id="eligibilityMessage" class="mt-2" style="display: none;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reason">Reason *</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="noticeDate">Notice Date *</label>
                                    <input type="date" class="form-control" id="noticeDate" name="notice_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lastWorkingDate">Last Working Date *</label>
                                    <input type="date" class="form-control" id="lastWorkingDate" name="last_working_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="comments">Additional Comments</label>
                            <textarea class="form-control" id="comments" name="comments" rows="2"></textarea>
                        </div>

                        <div class="form-group" id="resignationLetterSection" style="display: none;">
                            <label>Resignation Letter</label>
                            <div class="form-control-plaintext border rounded px-3 py-2 bg-light">
                                <a id="resignationLetterLink" href="#" target="_blank" rel="noopener" style="display: none;">
                                    <i class="fas fa-file-alt mr-1"></i><span id="resignationLetterName">View resignation letter</span>
                                </a>
                                <span id="resignationLetterMissing" class="text-danger" style="display: none;">No resignation letter attached.</span>
                            </div>
                        </div>

                        <div id="approvalSection" style="display: none;">
                            <hr>
                            <h6>Approval</h6>
                            <div class="form-group">
                                <label for="approvalStatus">Status</label>
                                <select class="form-control" id="approvalStatus" name="status">
                                    <option value="pending_legal_review">Approve HR review (send to Legal)</option>
                                    <option value="approved">Approve final</option>
                                    <option value="rejected">Reject</option>
                                    <option value="rejected_by_legal">Reject by Legal</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="approvalComments">Approval Comments</label>
                                <textarea class="form-control" id="approvalComments" name="approval_comments" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="resignationSubmitBtn">Save Decision</button>
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
                                <col style="width: 15%;"><col style="width: 10%;"><col style="width: 18%;">
                                <col style="width: 14%;"><col style="width: 12%;"><col style="width: 10%;">
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

<script src="assets/vendor/jquery/jquery.min.js"></script>
<script src="assets/vendor/flatpickr/flatpickr.min.js"></script>
<script src="assets/js/custom.js"></script>
<script>
    if (typeof loadResignationsTable === 'function') {
        loadResignationsTable('active', 1, '');
    }
    if (typeof loadEmployees === 'function') {
        loadEmployees();
    }
</script>
