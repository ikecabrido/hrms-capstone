
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <title>Login Page</title>
</head>
<body>
    <div class="login-container">
        <div class="login-contents">
            <h1>HRMS Login</h1>
            <div class="school-logo">
                <img src="assets/bcp-logo.png" alt="School Logo">
            </div>
            <form id="loginForm">
                <!-- Error message appears here, above the fields -->
                <div class="error-message" id="errorMsg">
                </div>

                <div class="input-group">
                    <label for="employee_id">Employee ID *</label>
                    <input type="text" id="employee_id" name="employee_id" required>
                </div>
                <div class="input-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password"  required>
                </div>
                <div class="forgot">Forgotten User ID or Password</div>
                <button type="submit" id="loginBtn">Login</button>
            </form>
        </div>
    </div>
    <script src="login.js"></script>
</body>
</html>