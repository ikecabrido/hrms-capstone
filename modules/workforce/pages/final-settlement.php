<div class="module-header">
    <div class="fs-header-row">
        <div>
            <h1>Final Settlement</h1>
            <p class="fs-subtitle">Manage employee final pay and settlement processing for completed exits.</p>
        </div>
        <button type="button" class="pm-btn pm-btn-primary" id="fsBtnCreate">
            <i class="fa-solid fa-plus"></i> Create Settlement
        </button>
    </div>
</div>

<div class="module-content">
    <div class="pm-page" id="finalSettlementPage" data-page="final-settlement">

        <div class="pm-alert" id="fsAlert" role="alert" style="display:none;"></div>

        <!-- Summary cards -->
        <div class="fs-summary-cards" id="fsSummaryCards">
            <div class="pm-summary-card">
                <div class="pm-summary-icon fs-icon-pending"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="fsPendingCount">&mdash;</span>
                    <span class="pm-summary-label">Pending Settlement</span>
                </div>
            </div>
            <div class="pm-summary-card">
                <div class="pm-summary-icon fs-icon-review"><i class="fa-solid fa-magnifying-glass"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="fsForReviewCount">&mdash;</span>
                    <span class="pm-summary-label">For Review</span>
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
                    <span class="pm-summary-value" id="fsReleasedCount">&mdash;</span>
                    <span class="pm-summary-label">Released</span>
                </div>
            </div>
            <div class="pm-summary-card pm-summary-card-wide">
                <div class="pm-summary-icon fs-icon-total"><i class="fa-solid fa-peso-sign"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="fsTotalFinalPay">&mdash;</span>
                    <span class="pm-summary-label">Total Final Pay</span>
                </div>
            </div>
        </div>

        <!-- Workflow strip -->
        <div class="fs-flow-strip" aria-hidden="true">
            <span>Exit Management</span>
            <i class="fa-solid fa-arrow-right"></i>
            <span>Approved Exit</span>
            <i class="fa-solid fa-arrow-right"></i>
            <span class="fs-flow-current">Final Settlement</span>
            <i class="fa-solid fa-arrow-right"></i>
            <span>Review &amp; Approve</span>
            <i class="fa-solid fa-arrow-right"></i>
            <span>Release Final Pay</span>
        </div>

        <!-- Filter bar -->
        <div class="pm-toolbar">
            <div class="pm-toolbar-filters">
                <div class="pm-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="fsSearchInput" placeholder="Search employee or employee code...">
                </div>

                <div class="fs-filter-group">
                    <label for="fsStatusFilter">Status</label>
                    <select id="fsStatusFilter">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="for_review">For Review</option>
                        <option value="approved">Approved</option>
                        <option value="released">Released</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="fs-filter-group">
                    <label for="fsExitTypeFilter">Exit Type</label>
                    <select id="fsExitTypeFilter">
                        <option value="">All Exit Types</option>
                        <option value="Resignation">Resignation</option>
                        <option value="Termination">Termination</option>
                        <option value="Retirement">Retirement</option>
                        <option value="End of Contract">End of Contract</option>
                    </select>
                </div>
            </div>

            <div class="pm-toolbar-actions">
                <button type="button" class="pm-btn pm-btn-secondary" id="fsBtnClear">
                    <i class="fa-solid fa-rotate-left"></i> Clear
                </button>
                <button type="button" class="pm-btn pm-btn-primary" id="fsBtnSearch">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="pm-table-card">
            <div class="pm-table-wrapper">
                <table class="pm-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Employee Code</th>
                            <th>Exit Type</th>
                            <th>Exit Date</th>
                            <th>Final Pay</th>
                            <th>Status</th>
                            <th class="pm-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="fsTableBody">
                        <tr>
                            <td colspan="7" class="pm-loading-row">
                                <i class="fa-solid fa-spinner fa-spin"></i> Loading settlements...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pm-empty-state" id="fsEmptyState" style="display:none;">
                <i class="fa-regular fa-folder-open"></i>
                <p id="fsEmptyStateText">No settlement records found.</p>
                <button type="button" class="pm-btn pm-btn-primary" id="fsBtnCreateEmpty">
                    <i class="fa-solid fa-plus"></i> Create Settlement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
     Create Settlement Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsCreateModalOverlay" style="display:none;">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h2>Create Final Settlement</h2>
            <button type="button" class="pm-modal-close" data-fs-close="fsCreateModalOverlay">&times;</button>
        </div>
        <form id="fsCreateForm" data-skip novalidate>
            <div class="pm-modal-body">
                <p class="pm-modal-subtitle">Select an employee with an approved exit record to begin the final settlement.</p>

                <div class="pm-form-group">
                    <label for="fsCreateEmployeeSearchInput">Employee <span class="pm-required">*</span></label>
                    <div class="fs-combobox" id="fsCreateEmployeeCombobox">
                        <div class="fs-combobox-input-wrap">
                            <i class="fa-solid fa-magnifying-glass fs-combobox-icon"></i>
                            <input
                                type="text"
                                id="fsCreateEmployeeSearchInput"
                                class="fs-combobox-input"
                                placeholder="Search employee with approved exit..."
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-autocomplete="list">
                        </div>
                        <input type="hidden" id="fsCreateEmployeeId" value="">
                        <div class="fs-combobox-options" id="fsCreateEmployeeOptions" role="listbox"></div>
                    </div>
                </div>

                <div class="fs-create-details" id="fsCreateDetails" style="display:none;">
                    <div class="fs-create-details-grid">
                        <div>
                            <span class="fs-detail-label">Exit Type</span>
                            <span class="fs-detail-value" id="fsCreateExitType">&mdash;</span>
                        </div>
                        <div>
                            <span class="fs-detail-label">Exit Date</span>
                            <span class="fs-detail-value" id="fsCreateExitDate">&mdash;</span>
                        </div>
                        <div>
                            <span class="fs-detail-label">Last Working Day</span>
                            <span class="fs-detail-value" id="fsCreateLastWorkingDay">&mdash;</span>
                        </div>
                    </div>

                    <div class="pm-preview fs-settlement-preview">
                        <div class="pm-preview-row">
                            <span>Monthly Salary</span>
                            <strong id="fsPreviewMonthlySalary">&mdash;</strong>
                        </div>
                        <div class="pm-preview-row">
                            <span>Estimated Unpaid Salary</span>
                            <strong id="fsPreviewUnpaidSalary">&mdash;</strong>
                        </div>
                        <div class="pm-preview-row">
                            <span>Estimated Leave Conversion</span>
                            <strong id="fsPreviewLeaveConversion">&mdash;</strong>
                        </div>
                    </div>
                </div>

                <div class="pm-form-error" id="fsCreateFormError" style="display:none;"></div>
            </div>

            <div class="pm-modal-footer">
                <button type="button" class="pm-btn pm-btn-secondary" data-fs-close="fsCreateModalOverlay">Cancel</button>
                <button type="submit" class="pm-btn pm-btn-primary" id="fsCreateSubmitBtn">Create Settlement</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     Settlement Detail View Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsDetailModalOverlay" style="display:none;">
    <div class="pm-modal fs-detail-modal">
        <div class="pm-modal-header">
            <div>
                <h2>Final Settlement Details</h2>
                <span class="fs-detail-subtitle" id="fsDetailSubtitle">&mdash;</span>
            </div>
            <button type="button" class="pm-modal-close" data-fs-close="fsDetailModalOverlay">&times;</button>
        </div>

        <div class="pm-modal-body fs-detail-body">

            <!-- Settlement workflow -->
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

            <!-- Earnings -->
            <div class="fs-section">
                <h3 class="fs-section-title">Earnings</h3>
                <table class="pm-table fs-calc-table">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th class="fs-amount-col">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="fsEarningsBody"></tbody>
                    <tfoot>
                        <tr class="fs-total-row">
                            <td>Total Earnings</td>
                            <td class="fs-amount-col" id="fsTotalEarnings">&mdash;</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Deductions -->
            <div class="fs-section">
                <h3 class="fs-section-title">Deductions</h3>
                <table class="pm-table fs-calc-table">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th class="fs-amount-col">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="fsDeductionsBody"></tbody>
                    <tfoot>
                        <tr class="fs-total-row">
                            <td>Total Deductions</td>
                            <td class="fs-amount-col" id="fsTotalDeductions">&mdash;</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Final calculation -->
            <div class="fs-section">
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
                        <span>Net Final Settlement</span>
                        <strong id="fsCalcNet">&mdash;</strong>
                    </div>
                </div>
            </div>

            <!-- Adjustments -->
            <div class="fs-section">
                <div class="fs-section-header-row">
                    <h3 class="fs-section-title">Adjustments</h3>
                    <button type="button" class="pm-btn pm-btn-outline pm-btn-sm" id="fsBtnAddAdjustment">
                        <i class="fa-solid fa-plus"></i> Add Adjustment
                    </button>
                </div>

                <div class="tab-container fs-adjust-tabs">
                    <div class="fs-tab-list">
                        <button type="button" class="tab-item active" data-tab="fsTabEarnings">Additional Earnings</button>
                        <button type="button" class="tab-item" data-tab="fsTabDeductions">Additional Deductions</button>
                    </div>

                    <div class="tab-content active" id="fsTabEarnings">
                        <table class="pm-table fs-calc-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="fs-amount-col">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="fsAdjEarningsBody"></tbody>
                        </table>
                        <p class="fs-adjust-empty" id="fsAdjEarningsEmpty" style="display:none;">No additional earnings added.</p>
                    </div>

                    <div class="tab-content" id="fsTabDeductions">
                        <table class="pm-table fs-calc-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="fs-amount-col">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="fsAdjDeductionsBody"></tbody>
                        </table>
                        <p class="fs-adjust-empty" id="fsAdjDeductionsEmpty" style="display:none;">No additional deductions added.</p>
                    </div>
                </div>
            </div>

        </div>

        <div class="pm-modal-footer fs-detail-footer" id="fsDetailActions"></div>
    </div>
</div>

<!-- ==========================================================================
     Add Adjustment Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsAdjustModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2>Add Adjustment</h2>
            <button type="button" class="pm-modal-close" data-fs-close="fsAdjustModalOverlay">&times;</button>
        </div>
        <form id="fsAdjustForm" data-skip novalidate>
            <div class="pm-modal-body">
                <div class="pm-form-group">
                    <label for="fsAdjustType">Adjustment Type <span class="pm-required">*</span></label>
                    <select id="fsAdjustType" required>
                        <option value="earning">Earning</option>
                        <option value="deduction">Deduction</option>
                    </select>
                </div>
                <div class="pm-form-group">
                    <label for="fsAdjustDescription">Description <span class="pm-required">*</span></label>
                    <input type="text" id="fsAdjustDescription" placeholder="e.g. Signing Bonus" required>
                </div>
                <div class="pm-form-group">
                    <label for="fsAdjustAmount">Amount <span class="pm-required">*</span></label>
                    <div class="fs-amount-input-wrap">
                        <span class="fs-peso-prefix">&#8369;</span>
                        <input type="number" id="fsAdjustAmount" min="0.01" step="0.01" placeholder="0.00" required>
                    </div>
                </div>
                <div class="pm-form-error" id="fsAdjustFormError" style="display:none;"></div>
            </div>
            <div class="pm-modal-footer">
                <button type="button" class="pm-btn pm-btn-secondary" data-fs-close="fsAdjustModalOverlay">Cancel</button>
                <button type="submit" class="pm-btn pm-btn-primary">Add Adjustment</button>
            </div>
        </form>
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
     Release Confirmation Modal
     ========================================================================== -->
<div class="pm-modal-overlay" id="fsReleaseModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2>Release Final Settlement?</h2>
            <button type="button" class="pm-modal-close" data-fs-close="fsReleaseModalOverlay">&times;</button>
        </div>
        <div class="pm-modal-body">
            <p class="fs-confirm-lead">This will mark the settlement as released. Released settlements cannot be edited.</p>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-fs-close="fsReleaseModalOverlay">Cancel</button>
            <button type="button" class="pm-btn pm-btn-primary" id="fsBtnConfirmRelease">Mark as Released</button>
        </div>
    </div>
</div>

<!-- Dedicated print sheet — hidden on screen, shown only via @media print -->
<div id="fsPrintSheet"></div>