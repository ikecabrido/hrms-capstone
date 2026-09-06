<div class="module-header">
    <h1>Period Manager</h1>
</div>

<div class="module-content">
    <div class="pm-page" id="periodManagerPage" data-page="period-manager">

        <div class="pm-alert" id="periodAlert" role="alert" style="display:none;"></div>

        <!-- Summary cards -->
        <div class="pm-summary-cards" id="pmSummaryCards">
            <div class="pm-summary-card">
                <div class="pm-summary-icon pm-icon-total"><i class="fa-solid fa-layer-group"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="pmTotalCount">&mdash;</span>
                    <span class="pm-summary-label">Total Periods</span>
                </div>
            </div>
            <div class="pm-summary-card">
                <div class="pm-summary-icon pm-icon-open"><i class="fa-solid fa-lock-open"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="pmOpenCount">&mdash;</span>
                    <span class="pm-summary-label">Open Periods</span>
                </div>
            </div>
            <div class="pm-summary-card">
                <div class="pm-summary-icon pm-icon-closed"><i class="fa-solid fa-lock"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="pmClosedCount">&mdash;</span>
                    <span class="pm-summary-label">Closed Periods</span>
                </div>
            </div>
            <div class="pm-summary-card pm-summary-card-wide">
                <div class="pm-summary-icon pm-icon-current"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="pm-summary-text">
                    <span class="pm-summary-value" id="pmCurrentPeriod">&mdash;</span>
                    <span class="pm-summary-label">Current Open Period</span>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="pm-toolbar">
            <div class="pm-toolbar-filters">
                <div class="pm-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="pmSearchInput" placeholder="Search periods...">
                </div>
                <select id="pmStatusFilter">
                    <option value="">All Statuses</option>
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="pm-toolbar-actions">
                <button type="button" class="pm-btn pm-btn-outline" id="pmBtnGenerateNext">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Next Period
                </button>
                <button type="button" class="pm-btn pm-btn-primary" id="pmBtnCreate">
                    <i class="fa-solid fa-plus"></i> Create Payroll Period
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="pm-table-card">
            <div class="pm-table-wrapper">
                <table class="pm-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Pay Date</th>
                            <th>Status</th>
                            <th class="pm-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pmTableBody">
                        <tr>
                            <td colspan="6" class="pm-loading-row">
                                <i class="fa-solid fa-spinner fa-spin"></i> Loading payroll periods...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pm-empty-state" id="pmEmptyState" style="display:none;">
                <i class="fa-regular fa-folder-open"></i>
                <p>No payroll periods have been created yet.</p>
                <button type="button" class="pm-btn pm-btn-primary" id="pmBtnCreateEmpty">
                    <i class="fa-solid fa-plus"></i> Create Payroll Period
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create / Edit Modal -->
<div class="pm-modal-overlay" id="pmFormModalOverlay" style="display:none;">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h2 id="pmFormModalTitle">Create Payroll Period</h2>
            <button type="button" class="pm-modal-close" data-pm-close>&times;</button>
        </div>
        <form id="pmPeriodForm" data-skip novalidate>
            <input type="hidden" id="pmPeriodId" name="period_id" value="">
            <div class="pm-form-group">
                <label for="pmPeriodName">Period Name <span class="pm-required">*</span></label>
                <input type="text" id="pmPeriodName" name="period_name" maxlength="100" required placeholder="e.g. Aug 1-15, 2026">
            </div>
            <div class="pm-form-row">
                <div class="pm-form-group">
                    <label for="pmStartDate">Start Date <span class="pm-required">*</span></label>
                    <input type="date" id="pmStartDate" name="start_date" required>
                </div>
                <div class="pm-form-group">
                    <label for="pmEndDate">End Date <span class="pm-required">*</span></label>
                    <input type="date" id="pmEndDate" name="end_date" required>
                </div>
            </div>
            <div class="pm-form-group">
                <label for="pmPayDate">Pay Date <span class="pm-required">*</span></label>
                <input type="date" id="pmPayDate" name="pay_date" required>
            </div>
            <div class="pm-form-error" id="pmFormError" style="display:none;"></div>
            <div class="pm-modal-footer">
                <button type="button" class="pm-btn pm-btn-secondary" data-pm-close>Cancel</button>
                <button type="submit" class="pm-btn pm-btn-primary" id="pmFormSubmitBtn">Save Period</button>
            </div>
        </form>
    </div>
</div>

<!-- Generate Next Period Modal -->
<div class="pm-modal-overlay" id="pmGenerateModalOverlay" style="display:none;">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h2>Generate Next Payroll Period</h2>
            <button type="button" class="pm-modal-close" data-pm-close>&times;</button>
        </div>
        <div class="pm-modal-body">
            <p class="pm-modal-subtitle">Review the suggested period below before creating it.</p>
            <div class="pm-preview">
                <div class="pm-preview-row">
                    <span>Period Name</span>
                    <strong id="pmGenName">&mdash;</strong>
                </div>
                <div class="pm-preview-row">
                    <span>Start Date</span>
                    <strong id="pmGenStart">&mdash;</strong>
                </div>
                <div class="pm-preview-row">
                    <span>End Date</span>
                    <strong id="pmGenEnd">&mdash;</strong>
                </div>
                <div class="pm-preview-row">
                    <span>Pay Date</span>
                    <strong id="pmGenPay">&mdash;</strong>
                </div>
            </div>
            <div class="pm-form-error" id="pmGenerateError" style="display:none;"></div>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-pm-close>Cancel</button>
            <button type="button" class="pm-btn pm-btn-primary" id="pmBtnConfirmGenerate">Create Period</button>
        </div>
    </div>
</div>

<!-- Confirm Action Modal (close / delete) -->
<div class="pm-modal-overlay" id="pmConfirmModalOverlay" style="display:none;">
    <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header">
            <h2 id="pmConfirmTitle">Confirm Action</h2>
            <button type="button" class="pm-modal-close" data-pm-close>&times;</button>
        </div>
        <div class="pm-modal-body">
            <p id="pmConfirmMessage"></p>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-pm-close>Cancel</button>
            <button type="button" class="pm-btn pm-btn-danger" id="pmBtnConfirmAction">Confirm</button>
        </div>
    </div>
</div>