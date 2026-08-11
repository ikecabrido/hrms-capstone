<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/hrms-capstone/modules/portal/public/css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
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
            <form id="loginForm">
                <div class="error-message" id="errorMsg">
                </div>
                <div class="input-group">
                    <label for="employee_id">Employee ID *</label>
                    <input type="text" id="employee_id" name="employee_id" required>
                </div>
                <div class="password-wrapper" style=" position: relative; width: 340px; margin-bottom: 20px; ">
                    <label for="password" style=" display: block; margin-bottom: 8px; font-size: 14px; "> Password *
                    </label>
                    <input type="password" id="password" name="password" required
                        style=" width: 320px; height: 47px; padding: 0 45px 0 15px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 15px; box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1); font-size: 16px; ">
                    <i id="passwordIcon" class="fas fa-eye" onclick="togglePassword()"
                        style=" position: absolute; right: 30px; bottom: 16px; cursor: pointer; z-index: 10; "></i>
                </div>
                <div class="forgot">Forgotten User ID or Password</div>
                <button type="submit" id="loginBtn">Login</button>
            </form>
        </div>
    </div>

</body>

</html>