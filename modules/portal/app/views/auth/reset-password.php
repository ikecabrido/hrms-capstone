<div style="text-align:center;margin-bottom:24px;">

    <div
        style="width:64px;height:64px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#eaf2ff;color:#0d6efd;font-size:25px;">
        <i class="fas fa-lock"></i>
    </div>

    <h4 style="font-weight:700;font-size:22px;color:#222;margin:0 0 5px;">
        Reset Password
    </h4>

    <p style="font-size:14px;color:#6c757d;margin:0;">
        Create a new password for your account.
    </p>

</div>

<form action="index.php?url=auth-update-password" method="POST">

    <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">

    <div style="margin-bottom:18px;">

        <label for="password" style="display:block;font-size:14px;font-weight:600;color:#212529;margin-bottom:7px;">
            New Password
        </label>

        <div style="display:flex;width:100%;height:46px;">

            <span
                style="width:45px;min-width:45px;height:46px;display:flex;align-items:center;justify-content:center;background:#f8f9fa;border:1px solid #ced4da;border-right:0;border-radius:7px 0 0 7px;color:#6c757d;box-sizing:border-box;">
                <i class="fas fa-lock"></i>
            </span>

            <input type="password" name="password" id="password" placeholder="Enter new password" minlength="8" required
                style="width:100%;height:46px;min-width:0;padding:0 12px;border:1px solid #ced4da;border-right:0;border-radius:0;outline:none;box-shadow:none;font-size:14px;box-sizing:border-box;">

            <button type="button" onclick="togglePassword('password','passwordIcon')" tabindex="-1"
                style="width:48px;min-width:48px;height:46px;padding:0;border:1px solid #ced4da;border-radius:0 7px 7px 0;background:#fff;color:#6c757d;display:flex;align-items:center;justify-content:center;cursor:pointer;">

                <i id="passwordIcon" class="fas fa-eye"></i>

            </button>

        </div>

        <div style="font-size:12px;color:#6c757d;margin-top:5px;">
            Use at least 8 characters.
        </div>

    </div>

    <div style="margin-bottom:18px;">

        <label for="password_confirmation"
            style="display:block;font-size:14px;font-weight:600;color:#212529;margin-bottom:7px;">
            Confirm New Password
        </label>

        <div style="display:flex;width:100%;height:46px;">

            <span
                style="width:45px;min-width:45px;height:46px;display:flex;align-items:center;justify-content:center;background:#f8f9fa;border:1px solid #ced4da;border-right:0;border-radius:7px 0 0 7px;color:#6c757d;box-sizing:border-box;">
                <i class="fas fa-lock"></i>
            </span>

            <input type="password" name="password_confirmation" id="password_confirmation"
                placeholder="Confirm new password" minlength="8" required
                style="flex:1;width:100%;height:46px;min-width:0;padding:0 12px;border:1px solid #ced4da;border-right:0;border-radius:0;outline:none;box-shadow:none;font-size:14px;box-sizing:border-box;">

            <button type="button" onclick="toggleConfirmPassword()" tabindex="-1"
                style="width:48px;min-width:48px;height:46px;padding:0;border:1px solid #ced4da;border-radius:0 7px 7px 0;background:#fff;color:#6c757d;display:flex;align-items:center;justify-content:center;cursor:pointer;">

                <i id="confirmPasswordIcon" class="fas fa-eye"></i>

            </button>

        </div>

    </div>

    <button type="submit"
        style="width:100%;height:46px;margin-top:8px;border:0;border-radius:10px;background:#0d6efd;color:#fff;font-size:14px;font-weight:600;display:flex;align-items:center;justify-content:center;cursor:pointer;">

        <i class="fas fa-key" style="margin-right:8px;"></i>
        Reset Password

    </button>

</form>

<div style="text-align:center;margin-top:24px;">

    <a href="index.php?url=auth-index" style="font-size:14px;color:#6c757d;text-decoration:none;">

        <i class="fas fa-arrow-left" style="margin-right:8px;"></i>
        Back to Login

    </a>

</div>
<script>
function toggleConfirmPassword() {
    const input = document.getElementById('password_confirmation');
    const icon = document.getElementById('confirmPasswordIcon');

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
</script>