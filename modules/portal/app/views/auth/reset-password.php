<div class="text-center mb-4">

    <div class="mb-3">
        <i class="fas fa-lock fa-3x text-primary"></i>
    </div>

    <h4 class="font-weight-bold">
        Reset Password
    </h4>

    <p class="text-muted mb-0">
        Create a new password for your account.
    </p>

</div>

<form action="index.php?url=auth-update-password"
    method="POST">

    <!-- Reset token -->
    <input
        type="hidden"
        name="token"
        value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">

    <!-- New Password -->
    <div class="form-group">

        <label for="password" class="font-weight-bold">
            New Password
        </label>

        <div class="input-group">

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                </span>
            </div>

            <input
                type="password"
                name="password"
                id="password"
                class="form-control"
                placeholder="Enter new password"
                minlength="8"
                required>

            <div class="input-group-append">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    onclick="togglePassword('password', 'passwordIcon')"
                    tabindex="-1">

                    <i id="passwordIcon" class="fas fa-eye"></i>

                </button>
            </div>

        </div>

        <small class="form-text text-muted">
            Password must be at least 8 characters.
        </small>

    </div>


    <!-- Confirm Password -->
    <div class="form-group">

        <label for="password_confirmation"
            class="font-weight-bold">
            Confirm New Password
        </label>

        <div class="input-group">

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                </span>
            </div>

            <input
                type="password"
                name="password_confirmation"
                id="password_confirmation"
                class="form-control"
                placeholder="Confirm new password"
                minlength="8"
                required>

            <div class="input-group-append">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    onclick="togglePassword(
                        'password_confirmation',
                        'confirmPasswordIcon'
                    )"
                    tabindex="-1">

                    <i id="confirmPasswordIcon"
                        class="fas fa-eye"></i>

                </button>
            </div>

        </div>

    </div>


    <!-- Submit -->
    <button
        type="submit"
        class="btn btn-primary btn-block mt-4">

        <i class="fas fa-key mr-1"></i>
        Reset Password

    </button>

</form>


<div class="text-center mt-3">

    <a href="index.php?url=auth-index"
        class="text-muted">

        <i class="fas fa-arrow-left mr-1"></i>
        Back to Login

    </a>

</div>


<script>
    function togglePassword(inputId, iconId) {

        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        if (input.type === "password") {

            input.type = "text";

            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");

        } else {

            input.type = "password";

            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");

        }
    }
</script>