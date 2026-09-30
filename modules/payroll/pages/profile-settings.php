<div class="module-header">
    <h1>Profile Settings</h1>
    <p class="pfs-subtitle">View your employment details and update your personal contact information.</p>
</div>

<div class="module-content">
    <div class="pm-page" id="profileSettingsPage">

        <div class="pm-alert" id="pfsAlert" role="alert" style="display:none;"></div>

        <!-- Profile summary -->
        <div class="pfs-summary-card">
            <div class="pfs-avatar" id="pfsAvatar">&nbsp;</div>
            <div class="pfs-summary-info">
                <h2 id="pfsFullName">&mdash;</h2>
                <p id="pfsPosition">&mdash;</p>
                <div class="pfs-summary-badges">
                    <span class="pfs-badge" id="pfsEmployeeCode">&mdash;</span>
                    <span class="pfs-badge" id="pfsDepartment">&mdash;</span>
                    <span class="pfs-badge pfs-badge-status" id="pfsStatus">&mdash;</span>
                </div>
            </div>
        </div>

        <div class="pfs-grid">

            <!-- Editable contact info -->
            <div class="pfs-card">
                <div class="pfs-card-header">
                    <h3><i class="fa-regular fa-address-card "></i> Contact Information</h3>
                    <p>These details can be updated by you at any time.</p>
                </div>

                <form id="pfsForm" class="pfs-form" data-skip>
                    <div class="pm-form-group">
                        <label for="pfsEmail">Email Address</label>
                        <input type="email" id="pfsEmail" name="email" placeholder="you@bcp.edu.ph">
                    </div>

                    <div class="pfs-form-row">
                        <div class="pm-form-group">
                            <label for="pfsMobile">Mobile Number</label>
                            <input type="text" id="pfsMobile" name="mobile_no" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="pm-form-group">
                            <label for="pfsPhone">Phone Number</label>
                            <input type="text" id="pfsPhone" name="phone_no" placeholder="(02) XXX XXXX">
                        </div>
                    </div>

                    <div class="pm-form-group">
                        <label for="pfsCurrentAddress">Current Address</label>
                        <textarea id="pfsCurrentAddress" name="current_address" rows="2" placeholder="House No., Street, Barangay, City/Municipality"></textarea>
                    </div>

                    <div class="pm-form-group">
                        <label for="pfsPermanentAddress">Permanent Address</label>
                        <textarea id="pfsPermanentAddress" name="permanent_address" rows="2" placeholder="House No., Street, Barangay, City/Municipality"></textarea>
                    </div>

                    <div class="pfs-form-actions">
                        <button type="button" class="pm-btn pm-btn-outline" id="pfsResetBtn">Reset</button>
                        <button type="submit" class="pm-btn pm-btn-primary" id="pfsSaveBtn">
                            <i class="fa-solid fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Read-only employment details -->
            <div class="pfs-card">
                <div class="pfs-card-header">
                    <h3><i class="fa-solid fa-briefcase"></i> Employment Details</h3>
                    <p>Managed by HR &mdash; contact your administrator to request changes.</p>
                </div>

                <dl class="pfs-info-list">
                    <div class="pfs-info-row">
                        <dt>Employee Code</dt>
                        <dd id="pfsInfoCode">&mdash;</dd>
                    </div>
                    <div class="pfs-info-row">
                        <dt>Department</dt>
                        <dd id="pfsInfoDepartment">&mdash;</dd>
                    </div>
                    <div class="pfs-info-row">
                        <dt>Position</dt>
                        <dd id="pfsInfoPosition">&mdash;</dd>
                    </div>
                    <div class="pfs-info-row">
                        <dt>Employment Type</dt>
                        <dd id="pfsInfoType">&mdash;</dd>
                    </div>
                    <div class="pfs-info-row">
                        <dt>Employment Status</dt>
                        <dd id="pfsInfoStatus">&mdash;</dd>
                    </div>
                    <div class="pfs-info-row">
                        <dt>Date Hired</dt>
                        <dd id="pfsInfoHireDate">&mdash;</dd>
                    </div>
                    <div class="pfs-info-row">
                        <dt>Last Login</dt>
                        <dd id="pfsInfoLastLogin">&mdash;</dd>
                    </div>
                </dl>
            </div>

        </div>
    </div>
</div>