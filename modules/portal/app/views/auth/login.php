<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/hrms-capstone/modules/portal/public/css/login.css">
    <link rel="stylesheet" href="/hrms-capstone/modules/portal/public/css/reset-password.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <title><?= htmlspecialchars($title ?? 'HR Management System') ?></title>
</head>

<body>
    <div class="login-container">
        <div class="login-contents">
            <h1 style="flex: auto; justify-content: center; font-weight: 900;">Employee Portal</h1>
            <div class="school-logo" style="flex:auto; justify-content: center ;">
                <img src="/hrms-capstone/modules/portal/public/assets/images/bcp-logo.png" alt="School Logo">
            </div>
            <?php require __DIR__ . '/../partials/notification.php'; ?>
            <?php require __DIR__ . '/rate-limit.php'; ?>
            <form id="loginForm" method="POST" action="index.php?url=auth-login">
                <div class="error-message" id="errorMsg">
                </div>
                <div class="input-group">
                    <label for="employee_id">Employee ID *</label>
                    <input type="text" id="employee_id" name="employee_id" required>
                </div>
                <div class="password-wrapper" style="
    position:relative;
    width:340px;
    margin-bottom:20px;
">

                    <label for="password" style="
        display:block;
        margin-bottom:8px;
        font-size:14px;
    ">
                        Password *
                    </label>

                    <input type="password" id="password" name="password" required style="
            width:320px;
            height:47px;
            padding:0 45px 0 15px;
            box-sizing:border-box;
            border:1px solid #ccc;
            border-radius:15px;
            box-shadow:inset 0 2px 4px rgba(0,0,0,0.1);
            font-size:16px;
        ">

                    <i id="passwordIcon" class="fas fa-eye" style="
            position:absolute;
            right:30px;
            bottom:16px;
            cursor:pointer;
            z-index:10;
        "></i>

                </div>
                <button type="submit" id="loginBtn">Login</button>
            </form>
            <div style="width: 300px; margin: 0 auto; display: flex; justify-content: center;">
                <a href="#" class="forgot-password" data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                    style="color: #000000 !important;">
                    Forgot password?
                </a>
            </div>
        </div>
    </div>
    <?php require __DIR__ . '/forgot-password.php'; ?>
    <script src="/hrms-capstone/modules/portal/public/js/function/togglePassword.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/hrms-capstone/modules/portal/public/js/extended.js"></script>
</body>

</html>