<div
    class="modal fade"
    id="changePasswordModal"
    tabindex="-1"
    aria-labelledby="changePasswordModalLabel"
    aria-hidden="true"
    style="z-index: 99999;">

    <div
        class="modal-dialog modal-dialog-centered"
        style="z-index: 100000;">

        <div
            class="modal-content password-modal"
            style="
                position: relative;
                z-index: 100001;
                border: 0;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
            ">

            <!-- Header -->
            <div
                class="password-modal-header"
                style="
                    position: relative;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    padding: 24px 26px;
                    background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
                    border-bottom: 1px solid #e5e7eb;
                ">

                <div
                    class="password-icon"
                    style="
                        width: 48px;
                        height: 48px;
                        min-width: 48px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 14px;
                        background: #2563eb;
                        color: #ffffff;
                        font-size: 19px;
                        box-shadow: 0 6px 15px rgba(37, 99, 235, 0.25);
                    ">
                    <i class="fas fa-lock"></i>
                </div>

                <div>
                    <span
                        style="
                            display: block;
                            margin-bottom: 2px;
                            color: #2563eb;
                            font-size: 10px;
                            font-weight: 700;
                            letter-spacing: 0.12em;
                        ">
                        ACCOUNT SECURITY
                    </span>

                    <h5
                        id="changePasswordModalLabel"
                        style="
                            margin: 0;
                            color: #111827;
                            font-size: 20px;
                            font-weight: 700;
                        ">
                        Change Password
                    </h5>

                    <p
                        style="
                            margin: 3px 0 0;
                            color: #6b7280;
                            font-size: 12px;
                        ">
                        Keep your account secure with a strong password.
                    </p>
                </div>

                <button
                    type="button"
                    class="btn-close password-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                    style="
                        position: absolute;
                        top: 20px;
                        right: 20px;
                        z-index: 100002;
                    ">
                </button>

            </div>


            <!-- Form -->
            <form
                action="index.php?url=update-password"
                method="POST"
                onsubmit="return validatePassword();">

                <!-- Body -->
                <div
                    class="password-modal-body"
                    style="
                        padding: 26px;
                        background: #ffffff;
                    ">

                    <!-- New Password -->
                    <div
                        class="password-field"
                        style="margin-bottom: 20px;">

                        <label
                            for="newPassword"
                            style="
                                display: block;
                                margin-bottom: 8px;
                                color: #374151;
                                font-size: 13px;
                                font-weight: 600;
                            ">
                            New Password
                        </label>

                        <div
                            class="password-input-wrapper"
                            style="
                                position: relative;
                                display: flex;
                                align-items: center;
                            ">

                            <i
                                class="fas fa-key"
                                style="
                                    position: absolute;
                                    left: 14px;
                                    z-index: 2;
                                    color: #9ca3af;
                                    font-size: 14px;
                                ">
                            </i>

                            <input
                                type="password"
                                id="newPassword"
                                name="password"
                                class="form-control"
                                placeholder="Enter your new password"
                                autocomplete="new-password"
                                required
                                style="
                                    height: 46px;
                                    padding-left: 40px;
                                    padding-right: 45px;
                                    border: 1px solid #dbe1e8;
                                    border-radius: 11px;
                                    font-size: 13px;
                                    box-shadow: none;
                                ">

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('newPassword', this)"
                                aria-label="Show password"
                                style="
                                    position: absolute;
                                    right: 10px;
                                    border: 0;
                                    background: transparent;
                                    color: #9ca3af;
                                    cursor: pointer;
                                    padding: 6px;
                                    z-index: 3;
                                ">

                                <i class="fas fa-eye"></i>

                            </button>

                        </div>
                    </div>


                    <!-- Confirm Password -->
                    <div
                        class="password-field"
                        style="margin-bottom: 20px;">

                        <label
                            for="confirmPassword"
                            style="
                                display: block;
                                margin-bottom: 8px;
                                color: #374151;
                                font-size: 13px;
                                font-weight: 600;
                            ">
                            Confirm Password
                        </label>

                        <div
                            class="password-input-wrapper"
                            style="
                                position: relative;
                                display: flex;
                                align-items: center;
                            ">

                            <i
                                class="fas fa-shield-alt"
                                style="
                                    position: absolute;
                                    left: 14px;
                                    z-index: 2;
                                    color: #9ca3af;
                                    font-size: 14px;
                                ">
                            </i>

                            <input
                                type="password"
                                id="confirmPassword"
                                class="form-control"
                                placeholder="Confirm your new password"
                                autocomplete="new-password"
                                required
                                style="
                                    height: 46px;
                                    padding-left: 40px;
                                    padding-right: 45px;
                                    border: 1px solid #dbe1e8;
                                    border-radius: 11px;
                                    font-size: 13px;
                                    box-shadow: none;
                                ">

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('confirmPassword', this)"
                                aria-label="Show password"
                                style="
                                    position: absolute;
                                    right: 10px;
                                    border: 0;
                                    background: transparent;
                                    color: #9ca3af;
                                    cursor: pointer;
                                    padding: 6px;
                                    z-index: 3;
                                ">

                                <i class="fas fa-eye"></i>

                            </button>

                        </div>
                    </div>


                    <!-- Requirements -->
                    <div
                        class="password-requirements"
                        style="
                            padding: 14px 16px;
                            margin-top: 4px;
                            background: #f8fafc;
                            border: 1px solid #e5e7eb;
                            border-radius: 12px;
                        ">

                        <div
                            class="requirements-title"
                            style="
                                margin-bottom: 8px;
                                color: #374151;
                                font-size: 12px;
                                font-weight: 700;
                            ">

                            <i
                                class="fas fa-circle-info"
                                style="
                                    margin-right: 5px;
                                    color: #2563eb;
                                ">
                            </i>

                            Password Requirements

                        </div>

                        <div
                            class="requirement-item"
                            style="
                                display: flex;
                                align-items: center;
                                gap: 7px;
                                margin-top: 5px;
                                color: #6b7280;
                                font-size: 11px;
                            ">

                            <i
                                class="fas fa-check"
                                style="
                                    color: #22c55e;
                                    font-size: 9px;
                                ">
                            </i>

                            At least 6 characters

                        </div>

                        <div
                            class="requirement-item"
                            style="
                                display: flex;
                                align-items: center;
                                gap: 7px;
                                margin-top: 5px;
                                color: #6b7280;
                                font-size: 11px;
                            ">

                            <i
                                class="fas fa-check"
                                style="
                                    color: #22c55e;
                                    font-size: 9px;
                                ">
                            </i>

                            Passwords must match

                        </div>

                    </div>


                    <!-- Error -->
                    <div
                        id="passwordError"
                        class="password-error"
                        style="
                            display: none;
                            align-items: center;
                            gap: 8px;
                            margin-top: 15px;
                            padding: 11px 13px;
                            border-radius: 9px;
                            background: #fef2f2;
                            border: 1px solid #fecaca;
                            color: #dc2626;
                            font-size: 12px;
                        ">

                        <i class="fas fa-circle-exclamation"></i>

                        <span></span>

                    </div>

                </div>


                <!-- Footer -->
                <div
                    class="password-modal-footer"
                    style="
                        display: flex;
                        justify-content: flex-end;
                        gap: 10px;
                        padding: 18px 26px;
                        background: #f8fafc;
                        border-top: 1px solid #e5e7eb;
                    ">

                    <button
                        type="button"
                        class="btn btn-light password-cancel"
                        data-bs-dismiss="modal"
                        style="
                            border: 1px solid #d1d5db;
                            border-radius: 9px;
                            padding: 9px 18px;
                            font-size: 13px;
                        ">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary password-submit"
                        style="
                            border-radius: 9px;
                            padding: 9px 18px;
                            font-size: 13px;
                        ">

                        <i class="fas fa-lock me-1"></i>

                        Update Password

                    </button>

                </div>

            </form>

        </div>
    </div>
</div>


<script>
function togglePassword(inputId, button) {

    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');

    if (input.type === 'password') {

        input.type = 'text';

        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');

    } else {

        input.type = 'password';

        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');

    }
}


function validatePassword() {

    const password =
        document.getElementById('newPassword').value;

    const confirmPassword =
        document.getElementById('confirmPassword').value;

    const error =
        document.getElementById('passwordError');

    const errorText =
        error.querySelector('span');

    error.style.display = 'none';

    if (password.length < 6) {

        errorText.textContent =
            'Password must be at least 6 characters.';

        error.style.display = 'flex';

        return false;
    }

    if (password !== confirmPassword) {

        errorText.textContent =
            'Passwords do not match.';

        error.style.display = 'flex';

        return false;
    }

    return true;
}
</script>