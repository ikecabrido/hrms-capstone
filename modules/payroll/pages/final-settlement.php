<div class="module-header">
    <div class="fs-header-row">
        <div>
            <h1>Final Settlement</h1>
            <p class="fs-subtitle">Process final pay for employees sent to Payroll by Exit Management.</p>
        </div>
    </div>
</div>

<div class="module-content">
    <div class="pm-page" id="finalSettlementPage" data-page="final-settlement">

        <div class="pm-alert" id="fsAlert" role="alert" style="display:none;"></div>

        <!-- Summary cards -->
        <div class="fs-summary-cards" id="fsSummaryCards">
            <div class="pm-summary-card">
                <div class="pm-summary-icon fs-icon-pending"><i class="fa-solid fa-inbox"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="fsPendingRequestsCount">&mdash;</span>
                    <span class="pm-summary-label">Pending Requests</span>
                </div>
            </div>
            <div class="pm-summary-card">
                <div class="pm-summary-icon fs-icon-review"><i class="fa-solid fa-magnifying-glass"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="fsForApprovalCount">&mdash;</span>
                    <span class="pm-summary-label">For Approval</span>
                </div>
            </div>
            <div class="pm-summary-card">
                <div class="pm-summary-icon fs-icon-approved"><i class="fa-solid fa-check"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="fsApprovedCount">&mdash;</span>
                    <span class="pm-summary-label">Approved</span>
                </div>
            </div>
            <div class="pm-summary-card">
                <div class="pm-summary-icon fs-icon-released"><i class="fa-solid fa-circle-check"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="fsPaidCount">&mdash;</span>
                    <span class="pm-summary-label">Paid</span>
                </div>
            </div>
            <div class="pm-summary-card pm-summary-card-wide">
                <div class="pm-summary-icon fs-icon-total"><i class="fa-solid fa-peso-sign"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="fsTotalPaid">&mdash;</span>
                    <span class="pm-summary-label">Total Paid Out</span>
                </div>
            </div>
        </div>

        <!-- Workflow strip -->
        <div class="fs-flow-strip" aria-hidden="true">
            <span>Exit Management</span>
            <i class="fa-solid fa-arrow-right"></i>
            <span>Settlement Request</span>
            <i class="fa-solid fa-arrow-right"></i>
            <span>Processing &amp; Calculation</span>
            <i class="fa-solid fa-arrow-right"></i>
            <span>Approval</span>
            <i class="fa-solid fa-arrow-right"></i>
            <span class="fs-flow-current">Release / Paid</span>
        </div>

        <!-- ==================================================================
             MAIN WORKFLOW TABS
             ================================================================== -->
        <div class="tab-container fs-main-tabs">
            <div class="fs-tab-list fs-main-tab-list">
                <button type="button" class="tab-item active" data-tab="fsTabRequests">
                    Settlement Requests <span class="fs-tab-count" id="fsTabRequestsCount">0</span>
                </button>
                <button type="button" class="tab-item" data-tab="fsTabProcessing">
                    Processing &amp; Calculation <span class="fs-tab-count" id="fsTabProcessingCount">0</span>
                </button>
                <button type="button" class="tab-item" data-tab="fsTabApproval">
                    Approval &amp; Release <span class="fs-tab-count" id="fsTabApprovalCount">0</span>
                </button>
            </div>

            <!-- ============================================================
                 TAB 1 — SETTLEMENT REQUESTS
                 ============================================================ -->
            <div class="tab-content active" id="fsTabRequests">
                <p class="fs-tab-intro">Incoming final settlement requests sent from Exit Management. Accept a request to begin Payroll processing.</p>

                <div class="pm-toolbar">
                    <div class="pm-toolbar-filters">
                        <div class="pm-search-wrap">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="fsReqSearchInput" placeholder="Search employee or employee code...">
                        </div>
                        <div class="fs-filter-group">
                            <label for="fsReqStatusFilter">Status</label>
                            <select id="fsReqStatusFilter">
                                <option value="">All Status</option>
                                <option value="requested">Requested</option>
                                <option value="processing">Processing</option>
                                <option value="calculated">Calculated</option>
                                <option value="for_approval">For Approval</option>
                                <option value="approved">Approved</option>
                                <option value="paid">Paid</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="fs-filter-group">
                            <label for="fsReqExitTypeFilter">Exit Type</label>
                            <select id="fsReqExitTypeFilter">
                                <option value="">All Exit Types</option>
                                <option value="resignation">Resignation</option>
                                <option value="termination">Termination</option>
                            </select>
                        </div>
                    </div>
                    <div class="pm-toolbar-actions">
                        <button type="button" class="pm-btn pm-btn-secondary" id="fsReqBtnClear">
                            <i class="fa-solid fa-rotate-left"></i> Clear
                        </button>
                        <button type="button" class="pm-btn pm-btn-primary" id="fsReqBtnSearch">
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>
                    </div>
                </div>

                <div class="pm-table-card">
                    <div class="pm-table-wrapper">
                        <table class="pm-table">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Employee</th>
                                    <th>Employee Code</th>
                                    <th>Exit Type</th>
                                    <th>Last Working Date</th>
                                    <th>Request Date</th>
                                    <th>Status</th>
                                    <th class="pm-actions-col">Action</th>
                                </tr>
                            </thead>
                            <tbody id="fsReqTableBody">
                                <tr>
                                    <td colspan="8" class="pm-loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Loading settlement requests...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pm-empty-state" id="fsReqEmptyState" style="display:none;">
                        <i class="fa-regular fa-folder-open"></i>
                        <p id="fsReqEmptyStateText">No settlement requests found.</p>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 TAB 2 — PROCESSING & CALCULATION
                 ============================================================ -->
            <div class="tab-content" id="fsTabProcessing">
                <p class="fs-tab-intro">Settlements you are currently preparing and calculating.</p>

                <div class="pm-toolbar">
                    <div class="pm-toolbar-filters">
                        <div class="pm-search-wrap">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="fsProcSearchInput" placeholder="Search employee or employee code...">
                        </div>
                        <div class="fs-filter-group">
                            <label for="fsProcStatusFilter">Status</label>
                            <select id="fsProcStatusFilter">
                                <option value="">Processing &amp; Calculated</option>
                                <option value="processing">Processing</option>
                                <option value="calculated">Calculated</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="fs-filter-group">
                            <label for="fsProcExitTypeFilter">Exit Type</label>
                            <select id="fsProcExitTypeFilter">
                                <option value="">All Exit Types</option>
                                <option value="resignation">Resignation</option>
                                <option value="termination">Termination</option>
                            </select>
                        </div>
                    </div>
                    <div class="pm-toolbar-actions">
                        <button type="button" class="pm-btn pm-btn-secondary" id="fsProcBtnClear">
                            <i class="fa-solid fa-rotate-left"></i> Clear
                        </button>
                        <button type="button" class="pm-btn pm-btn-primary" id="fsProcBtnSearch">
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>
                    </div>
                </div>

                <div class="pm-table-card">
                    <div class="pm-table-wrapper">
                        <table class="pm-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Employee Code</th>
                                    <th>Exit Type</th>
                                    <th>Last Working Date</th>
                                    <th>Total Earnings</th>
                                    <th>Total Deductions</th>
                                    <th>Net Settlement</th>
                                    <th>Status</th>
                                    <th class="pm-actions-col">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="fsProcTableBody">
                                <tr>
                                    <td colspan="9" class="pm-loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Loading settlements...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pm-empty-state" id="fsProcEmptyState" style="display:none;">
                        <i class="fa-regular fa-folder-open"></i>
                        <p id="fsProcEmptyStateText">No settlements are currently being processed.</p>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 TAB 3 — APPROVAL & RELEASE
                 ============================================================ -->
            <div class="tab-content" id="fsTabApproval">
                <p class="fs-tab-intro">Settlements awaiting approval, approved and ready for payment, or already paid.</p>

                <div class="pm-toolbar">
                    <div class="pm-toolbar-filters">
                        <div class="pm-search-wrap">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="fsApprSearchInput" placeholder="Search employee or employee code...">
                        </div>
                        <div class="fs-filter-group">
                            <label for="fsApprStatusFilter">Status</label>
                            <select id="fsApprStatusFilter">
                                <option value="">For Approval, Approved &amp; Paid</option>
                                <option value="for_approval">For Approval</option>
                                <option value="approved">Approved</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div class="fs-filter-group">
                            <label for="fsApprExitTypeFilter">Exit Type</label>
                            <select id="fsApprExitTypeFilter">
                                <option value="">All Exit Types</option>
                                <option value="resignation">Resignation</option>
                                <option value="termination">Termination</option>
                            </select>
                        </div>
                    </div>
                    <div class="pm-toolbar-actions">
                        <button type="button" class="pm-btn pm-btn-secondary" id="fsApprBtnClear">
                            <i class="fa-solid fa-rotate-left"></i> Clear
                        </button>
                        <button type="button" class="pm-btn pm-btn-primary" id="fsApprBtnSearch">
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>
                    </div>
                </div>

                <div class="pm-table-card">
                    <div class="pm-table-wrapper">
                        <table class="pm-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Employee Code</th>
                                    <th>Exit Type</th>
                                    <th>Last Working Date</th>
                                    <th>Total Earnings</th>
                                    <th>Total Deductions</th>
                                    <th>Net Settlement</th>
                                    <th>Status</th>
                                    <th class="pm-actions-col">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="fsApprTableBody">
                                <tr>
                                    <td colspan="9" class="pm-loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Loading settlements...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pm-empty-state" id="fsApprEmptyState" style="display:none;">
                        <i class="fa-regular fa-folder-open"></i>
                        <p id="fsApprEmptyStateText">Nothing is awaiting approval or release.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
     Request Detail Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsRequestModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <div>
                <h2>Settlement Request</h2>
                <span class="fs-detail-subtitle" id="fsRequestSubtitle">&mdash;</span>
            </div>
            <button type="button" class="pm-modal-close" data-fs-close="fsRequestModalOverlay">&times;</button>
        </div>
        <div class="pm-modal-body">
            <div class="fs-section">
                <h3 class="fs-section-title">Employee</h3>
                <div class="fs-info-grid" id="fsRequestEmployeeInfo"></div>
            </div>
            <div class="fs-section">
                <h3 class="fs-section-title">Exit</h3>
                <div class="fs-info-grid" id="fsRequestExitInfo"></div>
            </div>
            <div class="fs-section">
                <h3 class="fs-section-title">Request</h3>
                <div class="fs-info-grid" id="fsRequestInfo"></div>
            </div>
        </div>
        <div class="pm-modal-footer" id="fsRequestActions"></div>
    </div>
</div>

<!-- ==========================================================================
     Accept Request Confirmation Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsAcceptModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2>Accept Settlement Request?</h2>
            <button type="button" class="pm-modal-close" data-fs-close="fsAcceptModalOverlay">&times;</button>
        </div>
        <div class="pm-modal-body">
            <p class="fs-confirm-lead" id="fsAcceptConfirmText">Accept this settlement request from Exit Management and begin Payroll processing?</p>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-fs-close="fsAcceptModalOverlay">Cancel</button>
            <button type="button" class="pm-btn pm-btn-primary" id="fsBtnConfirmAccept">Accept Request</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     Settlement Detail / Processing Workspace Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsDetailModalOverlay" style="display:none;">
    <div class="pm-modal fs-detail-modal">
        <div class="pm-modal-header">
            <div>
                <h2>Final Settlement</h2>
                <span class="fs-detail-subtitle" id="fsDetailSubtitle">&mdash;</span>
            </div>
            <button type="button" class="pm-modal-close" data-fs-close="fsDetailModalOverlay">&times;</button>
        </div>

        <div class="pm-modal-body fs-detail-body">

            <!-- Workflow -->
            <div class="fs-section">
                <h3 class="fs-section-title">Settlement Status</h3>
                <div class="fs-workflow" id="fsWorkflow"></div>
            </div>

            <!-- Employee information -->
            <div class="fs-section">
                <h3 class="fs-section-title">Employee Information</h3>
                <div class="fs-info-grid" id="fsEmployeeInfo"></div>
            </div>

            <!-- Exit information -->
            <div class="fs-section">
                <h3 class="fs-section-title">Exit Information</h3>
                <div class="fs-info-grid" id="fsExitInfo"></div>
            </div>

            <!-- Settlement information -->
            <div class="fs-section">
                <h3 class="fs-section-title">Settlement Information</h3>
                <div class="fs-info-grid" id="fsSettlementInfo"></div>
            </div>

            <!-- Earnings -->
            <div class="fs-section">
                <div class="fs-section-header-row">
                    <h3 class="fs-section-title">Earnings</h3>
                    <button type="button" class="pm-btn pm-btn-outline pm-btn-sm" id="fsBtnAddEarning">
                        <i class="fa-solid fa-plus"></i> Add Earning
                    </button>
                </div>
                <table class="pm-table fs-calc-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="fs-amount-col">Amount</th>
                            <th class="pm-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="fsEarningsBody"></tbody>
                    <tfoot>
                        <tr class="fs-total-row">
                            <td colspan="2">Total Earnings</td>
                            <td class="fs-amount-col" id="fsTotalEarnings">&mdash;</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Deductions -->
            <div class="fs-section">
                <div class="fs-section-header-row">
                    <h3 class="fs-section-title">Deductions</h3>
                    <button type="button" class="pm-btn pm-btn-outline pm-btn-sm" id="fsBtnAddDeduction">
                        <i class="fa-solid fa-plus"></i> Add Deduction
                    </button>
                </div>
                <table class="pm-table fs-calc-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="fs-amount-col">Amount</th>
                            <th class="pm-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="fsDeductionsBody"></tbody>
                    <tfoot>
                        <tr class="fs-total-row">
                            <td colspan="2">Total Deductions</td>
                            <td class="fs-amount-col" id="fsTotalDeductions">&mdash;</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Final calculation -->
            <div class="fs-section">
                <h3 class="fs-section-title">Settlement Summary</h3>
                <div class="fs-final-calc-card">
                    <div class="fs-final-calc-row">
                        <span>Total Earnings</span>
                        <strong id="fsCalcEarnings">&mdash;</strong>
                    </div>
                    <div class="fs-final-calc-row fs-final-calc-less">
                        <span>Less: Total Deductions</span>
                        <strong id="fsCalcDeductions">&mdash;</strong>
                    </div>
                    <div class="fs-final-calc-divider"></div>
                    <div class="fs-final-calc-net">
                        <span>Net Settlement</span>
                        <strong id="fsCalcNet">&mdash;</strong>
                    </div>
                </div>
            </div>

            <!-- Payment information (paid settlements) -->
            <div class="fs-section" id="fsPaymentSection" style="display:none;">
                <h3 class="fs-section-title">Payment Information</h3>
                <div class="fs-info-grid" id="fsPaymentInfo"></div>
            </div>

            <!-- Approval / cancellation information -->
            <div class="fs-section" id="fsActivitySection" style="display:none;">
                <h3 class="fs-section-title">Activity</h3>
                <div class="fs-info-grid" id="fsActivityInfo"></div>
            </div>

        </div>

        <div class="pm-modal-footer fs-detail-footer" id="fsDetailActions"></div>
    </div>
</div>

<!-- ==========================================================================
     Add / Edit Settlement Item Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsItemModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2 id="fsItemModalTitle">Add Settlement Item</h2>
            <button type="button" class="pm-modal-close" data-fs-close="fsItemModalOverlay">&times;</button>
        </div>
        <form id="fsItemForm" data-skip novalidate>
            <div class="pm-modal-body">
                <input type="hidden" id="fsItemId" value="">
                <input type="hidden" id="fsItemSettlementId" value="">

                <div class="pm-form-group">
                    <label for="fsItemType">Item Type <span class="pm-required">*</span></label>
                    <select id="fsItemType" required>
                        <option value="earning">Earning</option>
                        <option value="deduction">Deduction</option>
                    </select>
                </div>
                <div class="pm-form-group">
                    <label for="fsItemCategory">Item Category <span class="pm-required">*</span></label>
                    <input type="text" id="fsItemCategory" placeholder="e.g. Unpaid Salary, SSS Loan" required>
                </div>
                <div class="pm-form-group">
                    <label for="fsItemDescription">Description <span class="pm-required">*</span></label>
                    <input type="text" id="fsItemDescription" placeholder="e.g. Unpaid salary for August 1-15" required>
                </div>
                <div class="pm-form-group">
                    <label for="fsItemAmount">Amount <span class="pm-required">*</span></label>
                    <div class="fs-amount-input-wrap">
                        <span class="fs-peso-prefix">&#8369;</span>
                        <input type="number" id="fsItemAmount" min="0.01" step="0.01" placeholder="0.00" required>
                    </div>
                </div>
                <div class="pm-form-group">
                    <label for="fsItemCode">Item Code <span class="fs-optional-label">(optional)</span></label>
                    <input type="text" id="fsItemCode" placeholder="e.g. SSS-LOAN">
                </div>
                <div class="pm-form-error" id="fsItemFormError" style="display:none;"></div>
            </div>
            <div class="pm-modal-footer">
                <button type="button" class="pm-btn pm-btn-secondary" data-fs-close="fsItemModalOverlay">Cancel</button>
                <button type="submit" class="pm-btn pm-btn-primary" id="fsItemSubmitBtn">Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     Delete Item Confirmation Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsDeleteItemModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2>Remove Settlement Item?</h2>
            <button type="button" class="pm-modal-close" data-fs-close="fsDeleteItemModalOverlay">&times;</button>
        </div>
        <div class="pm-modal-body">
            <p class="fs-confirm-lead">Are you sure you want to remove this settlement item?</p>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-fs-close="fsDeleteItemModalOverlay">Cancel</button>
            <button type="button" class="pm-btn pm-btn-danger" id="fsBtnConfirmDeleteItem">Remove Item</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     Calculate Confirmation Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsCalculateModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2>Calculate Settlement?</h2>
            <button type="button" class="pm-modal-close" data-fs-close="fsCalculateModalOverlay">&times;</button>
        </div>
        <div class="pm-modal-body">
            <p class="fs-confirm-lead">This will total the recorded earnings and deductions and lock the settlement for approval routing. You can still cancel it afterward, but items can no longer be added, edited, or removed.</p>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-fs-close="fsCalculateModalOverlay">Cancel</button>
            <button type="button" class="pm-btn pm-btn-primary" id="fsBtnConfirmCalculate">Calculate Settlement</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     Submit for Approval Confirmation Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsSubmitApprovalModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2>Submit for Approval?</h2>
            <button type="button" class="pm-modal-close" data-fs-close="fsSubmitApprovalModalOverlay">&times;</button>
        </div>
        <div class="pm-modal-body">
            <p class="fs-confirm-lead">This settlement will be sent to an authorized approver and can no longer be modified.</p>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-fs-close="fsSubmitApprovalModalOverlay">Cancel</button>
            <button type="button" class="pm-btn pm-btn-primary" id="fsBtnConfirmSubmitApproval">Submit for Approval</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     Approve Confirmation Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsApproveModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2>Approve Settlement?</h2>
            <button type="button" class="pm-modal-close" data-fs-close="fsApproveModalOverlay">&times;</button>
        </div>
        <div class="pm-modal-body">
            <p class="fs-confirm-lead" id="fsApproveConfirmText">You are about to approve this final settlement.</p>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-fs-close="fsApproveModalOverlay">Cancel</button>
            <button type="button" class="pm-btn pm-btn-primary" id="fsBtnConfirmApprove">Approve Settlement</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     Release Settlement Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsReleaseModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2>Release Final Settlement</h2>
            <button type="button" class="pm-modal-close" data-fs-close="fsReleaseModalOverlay">&times;</button>
        </div>
        <form id="fsReleaseForm" data-skip novalidate>
            <div class="pm-modal-body">
                <p class="pm-modal-subtitle">This will mark the settlement as paid. Paid settlements cannot be edited.</p>
                <div class="pm-form-group">
                    <label for="fsReleasePaymentMethod">Payment Method <span class="pm-required">*</span></label>
                    <select id="fsReleasePaymentMethod" required>
                        <option value="">Select payment method</option>
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Check">Check</option>
                        <option value="Payroll Account Credit">Payroll Account Credit</option>
                    </select>
                </div>
                <div class="pm-form-group">
                    <label for="fsReleasePaymentReference">Payment Reference <span class="fs-optional-label">(optional)</span></label>
                    <input type="text" id="fsReleasePaymentReference" placeholder="e.g. Transaction/Check number">
                </div>
                <div class="pm-form-error" id="fsReleaseFormError" style="display:none;"></div>
            </div>
            <div class="pm-modal-footer">
                <button type="button" class="pm-btn pm-btn-secondary" data-fs-close="fsReleaseModalOverlay">Cancel</button>
                <button type="submit" class="pm-btn pm-btn-primary">Release Settlement</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     Cancel Settlement Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsCancelModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2>Cancel Settlement?</h2>
            <button type="button" class="pm-modal-close" data-fs-close="fsCancelModalOverlay">&times;</button>
        </div>
        <div class="pm-modal-body">
            <p class="fs-confirm-lead">This will cancel the final settlement. This action cannot be undone once the settlement is approved or paid.</p>
            <div class="pm-form-group">
                <label for="fsCancelRemarks">Remarks <span class="fs-optional-label">(optional)</span></label>
                <textarea id="fsCancelRemarks" rows="3" placeholder="Reason for cancellation..."></textarea>
            </div>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-fs-close="fsCancelModalOverlay">Keep Settlement</button>
            <button type="button" class="pm-btn pm-btn-danger" id="fsBtnConfirmCancel">Cancel Settlement</button>
        </div>
    </div>
</div>

<!-- Dedicated print sheet — hidden on screen, shown only via @media print -->
<div id="fsPrintSheet"></div>