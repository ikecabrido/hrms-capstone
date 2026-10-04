<div class="module-header">
    <h1>Change Password</h1>
    <p class="cp-subtitle">Keep your account secure by using a strong, unique password.</p>
</div>

<div class="module-content">
    <div class="pm-page" id="changePasswordPage">

        <div class="pm-alert" id="cpAlert" role="alert" style="display:none;"></div>

        <div class="cp-grid">

            <!-- Password form -->
            <div class="ps-card cp-card">
                <div class="ps-card-header">
                    <h3><i class="fa-solid fa-lock"></i> Update Password</h3>
                    <p>You'll need to confirm your current password before setting a new one.</p>
                </div>

                <form id="cpForm" class="ps-form" data-skip autocomplete="off">

                    <div class="pm-form-group">
                        <label for="cpCurrentPassword">Current Password</label>
                        <div class="cp-input-wrap">
                            <input type="password" id="cpCurrentPassword" name="current_password" required autocomplete="current-password">
                            <button type="button" class="cp-toggle-visibility" data-target="cpCurrentPassword" aria-label="Show password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pm-form-group">
                        <label for="cpNewPassword">New Password</label>
                        <div class="cp-input-wrap">
                            <input type="password" id="cpNewPassword" name="new_password" required autocomplete="new-password">
                            <button type="button" class="cp-toggle-visibility" data-target="cpNewPassword" aria-label="Show password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>

                        <div class="cp-strength" id="cpStrength">
                            <div class="cp-strength-bar">
                                <span id="cpStrengthFill"></span>
                            </div>
                            <span class="cp-strength-label" id="cpStrengthLabel">&nbsp;</span>
                        </div>

                        <ul class="cp-requirements" id="cpRequirements">
                            <li data-rule="length"><i class="fa-solid fa-circle"></i> At least 8 characters</li>
                            <li data-rule="upper"><i class="fa-solid fa-circle"></i> One uppercase letter</li>
                            <li data-rule="number"><i class="fa-solid fa-circle"></i> One number</li>
                            <li data-rule="special"><i class="fa-solid fa-circle"></i> One special character</li>
                        </ul>
                    </div>

                    <div class="pm-form-group">
                        <label for="cpConfirmPassword">Confirm New Password</label>
                        <div class="cp-input-wrap">
                            <input type="password" id="cpConfirmPassword" name="confirm_password" required autocomplete="new-password">
                            <button type="button" class="cp-toggle-visibility" data-target="cpConfirmPassword" aria-label="Show password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <span class="cp-match-msg" id="cpMatchMsg"></span>
                    </div>

                    <div class="ps-form-actions">
                        <button type="reset" class="pm-btn pm-btn-outline" id="cpResetBtn">Clear</button>
                        <button type="submit" class="pm-btn pm-btn-primary" id="cpSaveBtn">
                            <i class="fa-solid fa-key"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security tips -->
            <div class="ps-card cp-tips-card">
                <div class="ps-card-header">
                    <h3><i class="fa-solid fa-shield-halved"></i> Security Tips</h3>
                </div>
                <ul class="cp-tips-list">
                    <li><i class="fa-solid fa-check"></i> Use a unique password you don't use elsewhere.</li>
                    <li><i class="fa-solid fa-check"></i> Avoid using your name, birthdate, or employee code.</li>
                    <li><i class="fa-solid fa-check"></i> Mix uppercase, lowercase, numbers, and symbols.</li>
                    <li><i class="fa-solid fa-check"></i> Never share your password with anyone.</li>
                </ul>
            </div>

        </div>
    </div>
</div>