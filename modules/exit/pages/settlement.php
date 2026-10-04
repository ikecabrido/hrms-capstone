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
        <h1>Settlements</h1>
    </div>

    <div class="module-content">
        <div id="settlements-section" class="section">
            <?php $alertId = 'settlement-action-alert'; $alertIcon = 'fas fa-wallet'; $alertMessage = 'Settlement requests are pending action'; $alertCount = 0; $alertViewAction = 'requested'; include __DIR__ . '/../includes/action-alert.php'; ?>
            <div class="settlement-progress-steps mb-3">
                <div class="step completed"><span>Exit Management</span></div>
                <div class="step active"><span>Settlement Request</span></div>
                <div class="step"><span>Processing &amp; Calculation</span></div>
                <div class="step"><span>Approval</span></div>
                <div class="step"><span>Release / Paid</span></div>
            </div>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2" style="flex: 1;">
                        <div class="input-group input-group-sm" style="flex: 19;">
                            <input type="text" id="settlement-search" class="form-control" placeholder="Search settlements..." onkeyup="onSettlementSearchChange()">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                        <select id="settlement-status-filter" class="form-control form-control-sm" onchange="onSettlementStatusFilterChange()" style="flex: 1; white-space: nowrap;">
                            <option value="all">All</option>
                            <option value="requested">Requested</option>
                            <option value="processing">Processing</option>
                            <option value="calculated">Calculated</option>
                            <option value="for_approval">For Approval</option>
                            <option value="approved">Approved</option>
                            <option value="paid">Paid</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="card-tools d-flex align-items-center">
                        <button type="button" class="btn btn-warning btn-sm mr-2" onclick="openArchivedSettlementsModal()">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="showSettlementModal()">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="settlements-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Settlement Date</th>
                                    <th>Net Payable</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="settlements-tbody">
                            </tbody>
                        </table>
                    </div>
                    <div id="settlements-pagination" class="d-flex justify-content-center mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="customToastContainer" style="position: fixed; top: 1rem; right: 1rem; z-index: 11000; display: flex; flex-direction: column; gap: .75rem;"></div>

    <!-- Settlement Request Modal -->
    <div class="modal fade exit-modal" id="settlementModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title" id="settlementModalTitle">Request Settlement</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="settlementForm">
                    <div class="modal-body">
                        <input type="hidden" id="settlementId" name="settlement_id">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="settlementCaseSelect">Approved Exit Case *</label>
                                    <select class="form-control" id="settlementCaseSelect" required>
                                        <option value="">Select Approved Exit Case</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="settlementEmployeeId" name="employee_id" value="">
                        <input type="hidden" id="settlementExitCaseType" name="exit_case_type" value="">
                        <input type="hidden" id="settlementExitCaseId" name="exit_case_id" value="">
                        <input type="hidden" id="settlementResignationId" name="resignation_id" value="">
                        <input type="hidden" id="settlementStatus" name="status" value="requested">
                        <input type="hidden" id="settlementLastWorkingDate" name="last_working_date" value="">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="settlementLastWorkingDateDisplay">Last Working Date</label>
                                    <input type="text" class="form-control" id="settlementLastWorkingDateDisplay" readonly placeholder="Select approved exit case">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    This request submits the exit case handoff details only. Payroll will calculate the final settlement separately.
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title">Request Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-0">
                                    <label for="settlementRemarks">Remarks</label>
                                    <textarea class="form-control" id="settlementRemarks" name="remarks" rows="3" placeholder="Add any notes for the settlement request..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-info" id="settlementEditBtn" style="display:none;">Edit Settlement</button>
                        <button type="submit" class="btn btn-danger" id="settlementSubmitBtn">Save Settlement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archive Settlement Modal -->
    <div class="modal fade exit-modal settlement-view-modal" id="settlementViewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title" id="settlementViewModalTitle">Final Settlement</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body settlement-view-detail-body" id="settlementViewModalBody"></div>
                <div class="modal-footer settlement-view-detail-footer" id="settlementViewModalFooter"></div>
            </div>
        </div>
    </div>

    <div class="modal fade exit-modal" id="archiveSettlementModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Archive Settlement</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="archiveSettlementForm">
                    <div class="modal-body">
                        <input type="hidden" id="archiveSettlementId" name="settlement_id">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Archiving will move this settlement record to the archive database.
                        </div>

                        <div class="form-group">
                            <label for="archiveSettlementEmployeeName">Employee Name</label>
                            <input type="text" class="form-control" id="archiveSettlementEmployeeName" readonly>
                        </div>

                        <input type="hidden" id="archiveSettlementReason" name="archive_reason" value="Process completed; archived.">
                        <div class="form-group">
                            <label>Archive Reason</label>
                            <div class="form-control-plaintext">Process completed; archived.</div>
                        </div>

                        <div class="form-group">
                            <label for="archiveSettlementNotes">Notes (optional)</label>
                            <textarea class="form-control" id="archiveSettlementNotes" name="archive_notes" rows="2" placeholder="Any additional notes about this archive action..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-archive"></i> Archive Settlement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archived Settlements List Modal -->
    <div class="modal fade exit-modal" id="archivedSettlementsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title">Archived Settlements</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table id="modal-archived-settlements-table" class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Settlement Date</th>
                                    <th>Net Payable</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="modal-archived-settlements-tbody">
                                <tr><td colspan="5" class="text-center text-muted">Loading archived settlements...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="modal-archived-settlements-pagination" class="mt-2 d-flex justify-content-end"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<script src="assets/vendor/flatpickr/flatpickr.min.js"></script>
<script>
    if (typeof loadSettlementsTable === 'function') {
        loadSettlementsTable('all', 1, 10, '');
    }
</script>
