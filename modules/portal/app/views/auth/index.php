<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/capstone_hr_management_system/employee_portal/public/assets/css/styles.css">
    <link rel="stylesheet" href="/capstone_hr_management_system/employee_portal/public/assets/css/reset-password.css">
    <title>Login Page</title>

</head>

<body>
    <div class="login-container">
        <div class="login-contents">
            <div class="school-logo">
                <img src="/capstone_hr_management_system/employee_portal/public/image/bcp-logo.png" alt="School Logo">
            </div>
            <?php require __DIR__ . '/../partials/notif.php'; ?>
            <form method="POST" action="index.php?url=auth-login">
                <!-- Error message appears here, above the fields -->
                <div class="error-message" id="errorMsg">
                </div>
                <div class="input-group">
                    <label for="employee_id">Employee ID *</label>
                    <input type="text" id="employee_id" name="employee_id" placeholder="EMP-XXXXX" required>
                </div>
                <div class="password-wrapper" style=" position: relative; width: 340px; margin-bottom: 20px; ">
                    <label for="password" style=" display: block; margin-bottom: 8px; font-size: 14px; "> Password *
                    </label>
                    <input type="password" id="password" name="password" required
                        style=" width: 320px; height: 47px; padding: 0 45px 0 15px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 15px; box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1); font-size: 16px; ">
                    <i id="passwordIcon" class="fas fa-eye" onclick="togglePassword()"
                        style=" position: absolute; right: 30px; bottom: 16px; cursor: pointer; z-index: 10; "></i>
                </div>
                <button type="submit" id="loginBtn">Login</button>
            </form>
            <div style="width: 300px; margin: 0 auto; display: flex; justify-content: center;">
                <a href="#" class="forgot-password" data-toggle="modal" data-target="#resetPasswordModal"
                    style="color: #000000 !important;">
                    Forgot password?
                </a>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <script src="/capstone_hr_management_system/employee_portal/public/assets/js/viewPassword.js"></script>
    <?php require __DIR__ . '/forgot-password-modal.php'; ?>
</body>

</html>