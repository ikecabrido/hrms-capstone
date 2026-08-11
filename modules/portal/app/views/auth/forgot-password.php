<div class="modal fade"
    id="resetPasswordModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="resetPasswordModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered" role="document">

        <div class="modal-content reset-modal">

            <!-- Header -->
            <div class="reset-modal-header">

                <div>
                    <h5 class="reset-modal-title" id="resetPasswordModalLabel">
                        Reset Password
                    </h5>

                    <p class="reset-modal-subtitle">
                        Enter your registered Gmail address
                    </p>
                </div>

                <button type="button"
                    class="reset-modal-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>


            <!-- Body -->
            <div class="reset-modal-body">

                <!-- Icon -->
                <div class="school-logo" style="flex: auto; justify-content: center;">
                    <img src="/hrms-capstone/modules/portal/public/assets/images/bcp-logo.png" alt="School Logo">
                </div>


                <!-- Description -->
                <p class="reset-description">
                    We'll send a password reset link to your registered
                    Gmail account.
                </p>


                <form action="index.php?url=auth-forgot-password"
                    method="POST">

                    <!-- Email -->
                    <div class="reset-form-group">

                        <label for="reset_email">
                            Gmail Address
                        </label>


                        <div class="reset-email-wrapper">

                            <div class="reset-email-icon">
                                <i class="fas fa-envelope"></i>
                            </div>

                            <input
                                type="email"
                                name="email"
                                id="reset_email"
                                placeholder="example@gmail.com"
                                required
                                autocomplete="email">

                        </div>


                        <small>
                            Use the Gmail address registered with your
                            employee account.
                        </small>

                    </div>


                    <!-- Buttons -->
                    <div class="reset-modal-actions">

                        <button type="button"
                            class="reset-cancel-btn"
                            data-bs-dismiss="modal">
                            Cancel
                        </button>


                        <button type="submit"
                            class="reset-submit-btn">

                            <i class="fas fa-paper-plane"></i>

                            Send Reset Link

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>