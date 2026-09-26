<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content"
            style="border:0;border-radius:16px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,.2);">

            <form action="index.php?url=update-user-profile" method="POST">

                <div
                    style="display:flex;justify-content:space-between;padding:20px 22px;background:linear-gradient(135deg,#eff6ff,#fff);border-bottom:1px solid #e5e7eb;">
                    <div>
                        <small style="color:#2563eb;font-weight:700;letter-spacing:.1em;">ACCOUNT</small>
                        <h5 id="editProfileModalLabel" style="margin:3px 0;font-weight:700;color:#111827;">Edit Profile
                        </h5>
                        <p style="margin:0;color:#6b7280;font-size:12px;">Update your personal information.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div style="padding:24px 22px;background:#fff;">
                    <div class="row g-3">

                        <!-- First Name -->
                        <div class="col-md-4">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control"
                                value="<?= htmlspecialchars($employeeProfileInfo['first_name'] ?? '') ?>">
                        </div>

                        <!-- Middle Name -->
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control"
                                value="<?= htmlspecialchars($employeeProfileInfo['middle_name'] ?? '') ?>">
                        </div>

                        <!-- Last Name -->
                        <div class="col-md-4">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control"
                                value="<?= htmlspecialchars($employeeProfileInfo['last_name'] ?? '') ?>">
                        </div>

                        <!-- Suffix -->
                        <div class="col-md-4">
                            <label class="form-label">Suffix</label>
                            <input type="text" name="suffix" class="form-control"
                                value="<?= htmlspecialchars($employeeProfileInfo['suffix'] ?? '') ?>">
                        </div>

                        <!-- Email -->
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($userInfos['email'] ?? '') ?>">
                        </div>

                        <!-- Mobile -->
                        <div class="col-md-4">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile_no" class="form-control"
                                value="<?= htmlspecialchars($employeeProfileInfo['mobile_no'] ?? '') ?>">
                        </div>

                        <!-- Gender -->
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="Male" <?= ($employeeProfileInfo['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($employeeProfileInfo['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <!-- Birth Date -->
                        <div class="col-md-4">
                            <label class="form-label">Birth Date</label>
                            <input type="date" name="birth_date" class="form-control"
                                value="<?= htmlspecialchars($employeeProfileInfo['birth_date'] ?? '') ?>">
                        </div>

                        <!-- Civil Status -->
                        <div class="col-md-4">
                            <label class="form-label">Civil Status</label>
                            <select name="civil_status" class="form-select">
                                <option value="">Select Status</option>
                                <option value="Single" <?= ($employeeProfileInfo['civil_status'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                                <option value="Married" <?= ($employeeProfileInfo['civil_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                                <option value="Widowed" <?= ($employeeProfileInfo['civil_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                                <option value="Separated" <?= ($employeeProfileInfo['civil_status'] ?? '') === 'Separated' ? 'selected' : '' ?>>Separated</option>
                            </select>
                        </div>

                        <!-- Current Address -->
                        <div class="col-12">
                            <label class="form-label">Current Address</label>
                            <textarea name="current_address" class="form-control"
                                rows="3"><?= htmlspecialchars($employeeProfileInfo['current_address'] ?? '') ?></textarea>
                        </div>

                    </div>
                </div>

                <div
                    style="display:flex;justify-content:flex-end;gap:8px;padding:15px 22px;background:#f8fafc;border-top:1px solid #e5e7eb;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Save Changes
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>