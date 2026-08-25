<div class="module-header">
    <div class="dd-header-row">
        <div>
            <h1>Deductions Management</h1>
            <p class="dd-subtitle">Manage manual employee deduction adjustments for payroll.</p>
        </div>
        <button type="button" class="pm-btn pm-btn-primary" id="ddBtnAdd">
            <i class="fa-solid fa-plus"></i> Add Deduction
        </button>
    </div>
</div>

<div class="module-content">
    <div class="pm-page" id="deductionsPage" data-page="deductions">

        <div class="pm-alert" id="ddAlert" role="alert" style="display:none;"></div>

        <!-- Summary cards -->
        <div class="pm-summary-cards" id="ddSummaryCards">
            <div class="pm-summary-card">
                <div class="pm-summary-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="ddTotalAdjustments">&mdash;</span>
                    <span class="pm-summary-label">Total Adjustments</span>
                </div>
            </div>
            <div class="pm-summary-card">
                <div class="pm-summary-icon dd-icon-money"><i class="fa-solid fa-peso-sign"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="ddTotalDeductions">&mdash;</span>
                    <span class="pm-summary-label">Total Deductions</span>
                </div>
            </div>
            <div class="pm-summary-card">
                <div class="pm-summary-icon dd-icon-loans"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="ddTotalLoans">&mdash;</span>
                    <span class="pm-summary-label">Loan Deductions</span>
                </div>
            </div>
            <div class="pm-summary-card">
                <div class="pm-summary-icon dd-icon-other"><i class="fa-solid fa-list-check"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="ddTotalOther">&mdash;</span>
                    <span class="pm-summary-label">Other Deductions</span>
                </div>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="pm-toolbar">
            <div class="pm-toolbar-filters">

                <div class="dd-filter-group">
                    <label for="ddPeriodSearchInput">Payroll Period</label>
                    <div class="dd-combobox" id="ddPeriodCombobox">
                        <div class="dd-combobox-input-wrap">
                            <i class="fa-solid fa-magnifying-glass dd-combobox-icon"></i>
                            <input
                                type="text"
                                id="ddPeriodSearchInput"
                                class="dd-combobox-input"
                                placeholder="Search payroll period..."
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-autocomplete="list">
                            <button type="button" class="dd-combobox-clear" id="ddPeriodClearBtn" title="Clear" aria-label="Clear payroll period filter">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </button>
                        </div>
                        <input type="hidden" id="ddPeriodFilter" value="">
                        <div class="dd-combobox-options" id="ddPeriodOptions" role="listbox"></div>
                    </div>
                </div>

                <div class="dd-filter-group">
                    <label for="ddEmployeeSearchInput">Employee</label>
                    <div class="dd-combobox" id="ddEmployeeCombobox">
                        <div class="dd-combobox-input-wrap">
                            <i class="fa-solid fa-magnifying-glass dd-combobox-icon"></i>
                            <input
                                type="text"
                                id="ddEmployeeSearchInput"
                                class="dd-combobox-input"
                                placeholder="Search employee code or name..."
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-autocomplete="list">
                            <button type="button" class="dd-combobox-clear" id="ddEmployeeClearBtn" title="Clear" aria-label="Clear employee filter">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </button>
                        </div>
                        <input type="hidden" id="ddEmployeeFilter" value="">
                        <div class="dd-combobox-options" id="ddEmployeeOptions" role="listbox"></div>
                    </div>
                </div>

                <div class="dd-filter-group">
                    <label for="ddTypeFilter">Deduction Type</label>
                    <select id="ddTypeFilter">
                        <option value="">All Types</option>
                        <option value="loans">Loans</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div class="pm-toolbar-actions">
                <button type="button" class="pm-btn pm-btn-secondary" id="ddBtnClear">
                    <i class="fa-solid fa-rotate-left"></i> Clear
                </button>
                <button type="button" class="pm-btn pm-btn-primary" id="ddBtnSearch">
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
                            <th>Payroll Period</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Document</th>
                            <th>Date Added</th>
                            <th>Status</th>
                            <th class="pm-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ddTableBody">
                        <tr>
                            <td colspan="9" class="pm-loading-row">
                                <i class="fa-solid fa-spinner fa-spin"></i> Loading deductions...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pm-empty-state" id="ddEmptyState" style="display:none;">
                <i class="fa-regular fa-folder-open"></i>
                <p id="ddEmptyStateText">No deduction adjustments found.</p>
                <button type="button" class="pm-btn pm-btn-primary" id="ddBtnAddEmpty">
                    <i class="fa-solid fa-plus"></i> Add Deduction
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Deduction Modal -->
<div class="pm-modal-overlay" id="ddFormModalOverlay" style="display:none;">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h2 id="ddFormModalTitle">Add Deduction</h2>
            <button type="button" class="pm-modal-close" data-dd-close>&times;</button>
        </div>
        <form id="ddDeductionForm" data-skip novalidate enctype="multipart/form-data">
            <input type="hidden" id="ddAdjustmentId" name="adjustment_id" value="">

            <div class="pm-modal-body">
                <div class="pm-form-group">
                    <label for="ddFormEmployee">Employee <span class="pm-required">*</span></label>
                    <select id="ddFormEmployee" name="employee_id" required>
                        <option value="">Select employee...</option>
                    </select>
                </div>

                <div class="pm-form-group">
                    <label for="ddFormPeriod">Payroll Period <span class="pm-required">*</span></label>
                    <select id="ddFormPeriod" name="period_id" required>
                        <option value="">Select payroll period...</option>
                    </select>
                    <span class="dd-field-hint">Only open payroll periods can receive new deductions.</span>
                </div>

                <div class="pm-form-group">
                    <label for="ddFormType">Deduction Type <span class="pm-required">*</span></label>
                    <select id="ddFormType" name="deduction_subtype" required>
                        <option value="">Select type...</option>
                        <option value="loans">Loans</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="pm-form-group">
                    <label for="ddFormDescription">Description <span class="pm-required">*</span></label>
                    <textarea id="ddFormDescription" name="description" rows="3" placeholder="Enter reason for deduction..." required></textarea>
                </div>

                <div class="pm-form-group">
                    <label for="ddFormAmount">Amount <span class="pm-required">*</span></label>
                    <div class="dd-amount-input-wrap">
                        <span class="dd-peso-prefix">&#8369;</span>
                        <input type="number" id="ddFormAmount" name="amount" min="0.01" step="0.01" placeholder="0.00" required>
                    </div>
                </div>

                <div class="pm-form-group">
                    <label for="ddFormFile">Supporting Document (Optional)</label>
                    <input type="file" id="ddFormFile" name="supporting_document" accept=".pdf,.jpg,.jpeg,.png">
                    <span class="dd-field-hint">Accepted formats: PDF, JPG, JPEG, PNG. Maximum size: 5 MB.</span>
                    <div class="dd-current-file" id="ddCurrentFile" style="display:none;"></div>
                </div>

                <div class="pm-form-error" id="ddFormError" style="display:none;"></div>
            </div>

            <div class="pm-modal-footer">
                <button type="button" class="pm-btn pm-btn-secondary" data-dd-close>Cancel</button>
                <button type="submit" class="pm-btn pm-btn-primary" id="ddFormSubmitBtn">Save Deduction</button>
            </div>
        </form>
    </div>
</div>

<!-- View Deduction Modal -->
<div class="pm-modal-overlay" id="ddViewModalOverlay" style="display:none;">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h2>Deduction Details</h2>
            <button type="button" class="pm-modal-close" data-dd-close>&times;</button>
        </div>
        <div class="pm-modal-body">
            <div class="dd-view-grid">
                <div class="dd-view-row">
                    <span class="dd-view-label">Employee</span>
                    <div class="dd-view-value">
                        <div class="dd-view-employee-code" id="ddViewEmployeeCode">&mdash;</div>
                        <div id="ddViewEmployeeName">&mdash;</div>
                    </div>
                </div>
                <div class="dd-view-row">
                    <span class="dd-view-label">Payroll Period</span>
                    <div class="dd-view-value">
                        <div id="ddViewPeriodName">&mdash;</div>
                        <div class="dd-view-subtext" id="ddViewPeriodDates">&mdash;</div>
                        <div class="dd-view-subtext" id="ddViewPayDate">&mdash;</div>
                    </div>
                </div>
                <div class="dd-view-row">
                    <span class="dd-view-label">Deduction Type</span>
                    <div class="dd-view-value" id="ddViewType">&mdash;</div>
                </div>
                <div class="dd-view-row">
                    <span class="dd-view-label">Description</span>
                    <div class="dd-view-value" id="ddViewDescription">&mdash;</div>
                </div>
                <div class="dd-view-row">
                    <span class="dd-view-label">Amount</span>
                    <div class="dd-view-value dd-view-amount" id="ddViewAmount">&mdash;</div>
                </div>
                <div class="dd-view-row">
                    <span class="dd-view-label">Supporting Document</span>
                    <div class="dd-view-value" id="ddViewDocument">&mdash;</div>
                </div>
                <div class="dd-view-row">
                    <span class="dd-view-label">Created</span>
                    <div class="dd-view-value" id="ddViewCreated">&mdash;</div>
                </div>
                <div class="dd-view-row">
                    <span class="dd-view-label">Period Status</span>
                    <div class="dd-view-value" id="ddViewStatus">&mdash;</div>
                </div>
            </div>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-dd-close>Close</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="pm-modal-overlay" id="ddConfirmModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2>Delete Deduction</h2>
            <button type="button" class="pm-modal-close" data-dd-close>&times;</button>
        </div>
        <div class="pm-modal-body">
            <p class="dd-confirm-lead">Are you sure you want to delete this deduction?</p>
            <div class="dd-confirm-summary">
                <div class="dd-confirm-row">
                    <span>Employee</span>
                    <strong id="ddConfirmEmployee">&mdash;</strong>
                </div>
                <div class="dd-confirm-row">
                    <span>Payroll Period</span>
                    <strong id="ddConfirmPeriod">&mdash;</strong>
                </div>
                <div class="dd-confirm-row">
                    <span>Description</span>
                    <strong id="ddConfirmDescription">&mdash;</strong>
                </div>
                <div class="dd-confirm-row">
                    <span>Amount</span>
                    <strong id="ddConfirmAmount">&mdash;</strong>
                </div>
            </div>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-dd-close>Cancel</button>
            <button type="button" class="pm-btn pm-btn-danger" id="ddBtnConfirmDelete">Delete Deduction</button>
        </div>
    </div>
</div>