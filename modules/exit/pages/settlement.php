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
                            <option value="pending_approval">Pending Approval</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
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

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="settlementDate">Settlement Date *</label>
                                    <input type="date" class="form-control" id="settlementDate" name="settlement_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    This form submits a settlement request. Payroll will perform all financial calculations and determine the final net payable amount.
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="settlementStatus" name="status" value="pending_approval">
                        <input type="hidden" id="netPayable" name="net_payable" value="0">
                        <input type="hidden" id="basicSalary" name="basic_salary" value="0">
                        <input type="hidden" id="remainingSalary" name="remaining_salary" value="0">
                        <input type="hidden" id="unusedLeaveConversion" name="unused_leave_conversion" value="0">
                        <input type="hidden" id="overtimePay" name="overtime_pay" value="0">
                        <input type="hidden" id="holidayPay" name="holiday_pay" value="0">
                        <input type="hidden" id="bonuses" name="bonuses" value="0">
                        <input type="hidden" id="commission" name="commission" value="0">
                        <input type="hidden" id="hra" name="hra" value="0">
                        <input type="hidden" id="conveyance" name="conveyance" value="0">
                        <input type="hidden" id="lta" name="lta" value="0">
                        <input type="hidden" id="medicalAllowance" name="medical_allowance" value="0">
                        <input type="hidden" id="otherAllowances" name="other_allowances" value="0">
                        <input type="hidden" id="separationPay" name="separation_pay" value="0">
                        <input type="hidden" id="tax" name="tax" value="0">
                        <input type="hidden" id="sss" name="sss" value="0">
                        <input type="hidden" id="philhealth" name="philhealth" value="0">
                        <input type="hidden" id="pagibig" name="pagibig" value="0">
                        <input type="hidden" id="cashAdvance" name="cash_advance" value="0">
                        <input type="hidden" id="companyLoan" name="company_loan" value="0">
                        <input type="hidden" id="equipmentDamage" name="equipment_damage" value="0">
                        <input type="hidden" id="missingAssets" name="missing_assets" value="0">
                        <input type="hidden" id="lateDeductions" name="late_deductions" value="0">
                        <input type="hidden" id="absenceDeductions" name="absence_deductions" value="0">
                        <input type="hidden" id="providentFund" name="provident_fund" value="0">
                        <input type="hidden" id="gratuity" name="gratuity" value="0">
                        <input type="hidden" id="noticePay" name="notice_pay" value="0">
                        <input type="hidden" id="outstandingLoans" name="outstanding_loans" value="0">
                        <input type="hidden" id="otherDeductions" name="other_deductions" value="0">

                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title">Request Details</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">HR will only submit the employee, related resignation, and settlement date. Payroll will review the request, calculate the settlement components, and set the final net amount.</p>
                            </div>
                        </div>

                        <div class="card mt-3 d-none" id="payrollSettlementSummaryCard">
                            <div class="card-header">
                                <h6 class="card-title">Payroll Settlement Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tbody>
                                            <tr><th>Net Payable</th><td id="payrollSettlementSummary_net_payable">0.00</td></tr>
                                            <tr><th>Basic Salary</th><td id="payrollSettlementSummary_basic_salary">0.00</td></tr>
                                            <tr><th>Remaining Salary</th><td id="payrollSettlementSummary_remaining_salary">0.00</td></tr>
                                            <tr><th>Unused Leave Conversion</th><td id="payrollSettlementSummary_unused_leave_conversion">0.00</td></tr>
                                            <tr><th>Overtime Pay</th><td id="payrollSettlementSummary_overtime_pay">0.00</td></tr>
                                            <tr><th>Holiday Pay</th><td id="payrollSettlementSummary_holiday_pay">0.00</td></tr>
                                            <tr><th>Bonuses</th><td id="payrollSettlementSummary_bonuses">0.00</td></tr>
                                            <tr><th>Commission</th><td id="payrollSettlementSummary_commission">0.00</td></tr>
                                            <tr><th>HRA</th><td id="payrollSettlementSummary_hra">0.00</td></tr>
                                            <tr><th>Conveyance</th><td id="payrollSettlementSummary_conveyance">0.00</td></tr>
                                            <tr><th>LTA</th><td id="payrollSettlementSummary_lta">0.00</td></tr>
                                            <tr><th>Medical Allowance</th><td id="payrollSettlementSummary_medical_allowance">0.00</td></tr>
                                            <tr><th>Other Allowances</th><td id="payrollSettlementSummary_other_allowances">0.00</td></tr>
                                            <tr><th>Separation Pay</th><td id="payrollSettlementSummary_separation_pay">0.00</td></tr>
                                            <tr><th>Tax</th><td id="payrollSettlementSummary_tax">0.00</td></tr>
                                            <tr><th>SSS</th><td id="payrollSettlementSummary_sss">0.00</td></tr>
                                            <tr><th>PhilHealth</th><td id="payrollSettlementSummary_philhealth">0.00</td></tr>
                                            <tr><th>Pag-IBIG</th><td id="payrollSettlementSummary_pagibig">0.00</td></tr>
                                            <tr><th>Cash Advance</th><td id="payrollSettlementSummary_cash_advance">0.00</td></tr>
                                            <tr><th>Company Loan</th><td id="payrollSettlementSummary_company_loan">0.00</td></tr>
                                            <tr><th>Equipment Damage</th><td id="payrollSettlementSummary_equipment_damage">0.00</td></tr>
                                            <tr><th>Missing Assets</th><td id="payrollSettlementSummary_missing_assets">0.00</td></tr>
                                            <tr><th>Late Deductions</th><td id="payrollSettlementSummary_late_deductions">0.00</td></tr>
                                            <tr><th>Absence Deductions</th><td id="payrollSettlementSummary_absence_deductions">0.00</td></tr>
                                            <tr><th>Provident Fund</th><td id="payrollSettlementSummary_provident_fund">0.00</td></tr>
                                            <tr><th>Gratuity</th><td id="payrollSettlementSummary_gratuity">0.00</td></tr>
                                            <tr><th>Notice Pay</th><td id="payrollSettlementSummary_notice_pay">0.00</td></tr>
                                            <tr><th>Outstanding Loans</th><td id="payrollSettlementSummary_outstanding_loans">0.00</td></tr>
                                            <tr><th>Other Deductions</th><td id="payrollSettlementSummary_other_deductions">0.00</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-body">
                                <div class="alert alert-warning mb-0">
                                    <strong>Payroll owns settlement calculation:</strong> No payroll financial fields are editable in this form.
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
