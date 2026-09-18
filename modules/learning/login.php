<?php
require_once dirname(__DIR__, 2) . '/database/db.php';

function resolveLearningRole(array $user): string {
    $roleId = (int) ($user['role_id'] ?? 0);
    $roleName = strtolower($user['role_name'] ?? '');
    $deptName = strtolower($user['department_name'] ?? '');
    $empId = (int) ($user['employee_id'] ?? 0);

    if ($roleId === 1 || stripos($roleName, 'system admin') !== false) {
        return 'admin';
    }

    // L&D admins - specific employee IDs that should have admin access
    $ldAdminEmployeeIds = [35, 1018];
    if (in_array($empId, $ldAdminEmployeeIds, true)) {
        return 'admin';
    }

    if ($roleId === 7 || stripos($roleName, 'learning') !== false || stripos($deptName, 'instructor') !== false) {
        return 'instructor';
    }

    return 'learner';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['employee_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string) ($_POST['employee_id'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $error = 'Enter your employee ID and password.';
    } else {
        $database = new Database();
        $connection = $database->getConnection();

        if (ctype_digit($identifier)) {
            $query = $connection->prepare(
                "SELECT u.user_id, u.employee_id, u.role_id, u.password, u.account_status,
                        e.employee_code, e.first_name, e.last_name, e.position_id,
                        e.department_id, e.employment_status, p.position_name,
                        r.role_name, d.department_name
                 FROM user_account u
                 INNER JOIN em_employees e ON e.employee_id = u.employee_id
                 INNER JOIN em_roles r ON r.role_id = u.role_id
                 INNER JOIN em_positions p ON p.position_id = e.position_id
                 LEFT JOIN em_departments d ON d.department_id = e.department_id
                 WHERE e.employee_id = :employee_id
                 LIMIT 1"
            );
            $query->execute([':employee_id' => (int) $identifier]);
        } else {
            $query = $connection->prepare(
                "SELECT u.user_id, u.employee_id, u.role_id, u.password, u.account_status,
                        e.employee_code, e.first_name, e.last_name, e.position_id,
                        e.department_id, e.employment_status, p.position_name,
                        r.role_name, d.department_name
                 FROM user_account u
                 INNER JOIN em_employees e ON e.employee_id = u.employee_id
                 INNER JOIN em_roles r ON r.role_id = u.role_id
                 INNER JOIN em_positions p ON p.position_id = e.position_id
                 LEFT JOIN em_departments d ON d.department_id = e.department_id
                 WHERE e.employee_code = :employee_code
                 LIMIT 1"
            );
            $query->execute([':employee_code' => $identifier]);
        }

        $user = $query->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['account_status'] === 'Active'
            && $user['employment_status'] === 'Active'
            && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['employee_id'] = $user['employee_id'];
            $_SESSION['employee_code'] = $user['employee_code'];
            $_SESSION['employee_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['position_id'] = $user['position_id'];
            $_SESSION['position_name'] = $user['position_name'];
            $_SESSION['department_id'] = $user['department_id'];
            $_SESSION['department_name'] = $user['department_name'];
            $_SESSION['last_activity'] = time();

            $ldRole = resolveLearningRole($user);
            $_SESSION['learning_role'] = $ldRole;

            header('Location: index.php');
            exit();
        }

        $error = 'Invalid employee ID or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learning &amp; Development Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #111827;
        }

        /* ---------- full-bleed background ---------- */
        .login-container {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1.25rem;
            overflow: auto;
        }

        .login-bg {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            z-index: 0;
        }

        .login-overlay {
            position: fixed;
            inset: 0;
            z-index: 1;
            background:
                radial-gradient(1100px 620px at 85% 0%, rgba(70, 88, 185, 0.42), transparent 62%),
                radial-gradient(900px 540px at 0% 100%, rgba(23, 32, 80, 0.55), transparent 60%),
                linear-gradient(175deg, rgba(15, 21, 55, 0.42) 0%, rgba(15, 21, 55, 0.30) 50%, rgba(15, 21, 55, 0.48) 100%);
            pointer-events: none;
        }

        /* ---------- glass card ---------- */
        .login-contents {
            position: relative;
            z-index: 2;
            width: min(100%, 25rem);
            max-width: 400px;
            background: linear-gradient(170deg, rgba(255, 255, 255, 0.55) 0%, rgba(255, 255, 255, 0.30) 100%);
            -webkit-backdrop-filter: blur(28px) saturate(1.5);
            backdrop-filter: blur(28px) saturate(1.5);
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 24px;
            box-shadow:
                0 34px 80px rgba(10, 15, 50, 0.45),
                0 12px 28px rgba(10, 15, 50, 0.22),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            padding: 1.9rem 2rem 1.2rem;
            text-align: center;
        }

        .login-contents h1 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1c2a63;
            margin: 0;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .subtitle {
            font-size: 0.8rem;
            font-weight: 500;
            color: rgba(28, 42, 99, 0.72);
            margin: 0.35rem 0 0;
        }

        .school-logo {
            width: 72px;
            height: 72px;
            margin: 0.9rem auto 0;
            border-radius: 18px;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 20px rgba(20, 12, 60, 0.28);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
            display: block;
        }

        /* ---------- form ---------- */
        .login-form {
            text-align: left;
            margin-top: 1.1rem;
        }

        .input-group {
            margin-bottom: 0.65rem;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 600;
            font-size: 0.8rem;
            color: #1c2a63;
            letter-spacing: 0.01em;
        }

        .input-group .icon {
            position: absolute;
            left: 0.8rem;
            bottom: 0.68rem;
            width: 16px;
            height: 16px;
            color: rgba(28, 42, 99, 0.55);
            pointer-events: none;
        }

        .input-group input {
            width: 100%;
            padding: 0.6rem 0.85rem 0.6rem 2.3rem;
            font: inherit;
            font-size: 0.92rem;
            color: #111827;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(28, 42, 99, 0.2);
            border-radius: 10px;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        .input-group input::placeholder {
            color: #94a0bd;
        }

        .input-group input:hover {
            border-color: rgba(28, 42, 99, 0.38);
        }

        .input-group input:focus {
            outline: none;
            border-color: #3b4aa0;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 74, 160, 0.18);
        }

        .error-message {
            display: none;
            margin-top: 0.7rem;
            padding: 0.55rem 0.8rem;
            border-radius: 10px;
            background: rgba(254, 242, 242, 0.92);
            border: 1px solid rgba(220, 60, 60, 0.35);
            color: #b91c1c;
            font-size: 0.82rem;
            text-align: left;
        }

        .error-message.show {
            display: block;
        }

        .forgot {
            display: block;
            text-align: center;
            font-size: 0.8rem;
            font-weight: 600;
            color: #3b4aa0;
            text-decoration: none;
            margin: 0.55rem 0 0.8rem;
        }

        .forgot:hover {
            text-decoration: underline;
            color: #1c2a63;
        }

        button[type="submit"] {
            width: 100%;
            padding: 0.68rem;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, #25336e 0%, #3b4aa0 100%);
            color: #ffffff;
            font: inherit;
            font-weight: 700;
            font-size: 0.94rem;
            letter-spacing: 0.02em;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(37, 51, 110, 0.4);
            transition: transform 0.12s, box-shadow 0.15s, filter 0.15s;
        }

        button[type="submit"]:hover {
            filter: brightness(1.12);
            box-shadow: 0 16px 34px rgba(37, 51, 110, 0.48);
            transform: translateY(-1px);
        }

        button[type="submit"]:active {
            transform: translateY(0) scale(0.98);
        }

        .login-footer {
            margin-top: 1rem;
            padding-top: 0.85rem;
            border-top: 1px solid rgba(28, 42, 99, 0.14);
            font-size: 0.76rem;
            color: rgba(28, 42, 99, 0.7);
        }

        .login-footer a {
            color: #25336e;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 420px) {
            .login-contents {
                padding: 1.6rem 1.4rem 1.1rem;
            }

            .login-contents h1 {
                font-size: 1.22rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img class="login-bg" src="../../assets/bg.jpg" alt="">
        <div class="login-overlay"></div>
        <div class="login-contents">
            <h1>Learning &amp; Development</h1>
            <p class="subtitle">Sign in to access your training dashboard</p>

            <div class="school-logo">
                <img src="../../assets/bcp-logo.png" alt="School Logo">
            </div>

            <div class="error-message" id="errorMsg"></div>

            <form id="loginForm" method="post" class="login-form">
                <div class="input-group">
                    <label for="employee_id">Employee ID *</label>
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <input type="text"
                           id="employee_id"
                           name="employee_id"
                           placeholder="Enter your employee ID"
                           required
                           value="<?= htmlspecialchars($identifier ?? '') ?>"
                           autocomplete="off">
                </div>

                <div class="input-group">
                    <label for="password">Password *</label>
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="Enter your password"
                           required
                           autocomplete="off">
                </div>

                <a class="forgot" href="#">Forgotten User ID or Password</a>
                <button type="submit">Login</button>
            </form>

            <div class="login-footer">
                <a href="../../index.php">&larr; Back to HRMS Login</a>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var form = document.getElementById('loginForm');
        var errorMsg = document.getElementById('errorMsg');

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            errorMsg.className = 'error-message';
            errorMsg.textContent = '';

            var request = new XMLHttpRequest();
            request.open('POST', 'ajax/login.php', true);
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            request.onload = function () {
                if (request.status >= 200 && request.status < 300) {
                    try {
                        var data = JSON.parse(request.responseText);

                        if (data.success) {
                            window.location.href = data.redirect;
                        } else {
                            errorMsg.textContent = data.message || 'Invalid employee ID or password.';
                            errorMsg.className = 'error-message show';
                        }
                    } catch (err) {
                        errorMsg.textContent = 'Server error. Please try again.';
                        errorMsg.className = 'error-message show';
                    }
                } else {
                    errorMsg.textContent = 'Something went wrong. Please try again.';
                    errorMsg.className = 'error-message show';
                }
            };

            request.onerror = function () {
                errorMsg.textContent = 'Something went wrong. Please try again.';
                errorMsg.className = 'error-message show';
            };

            request.send(new FormData(form));
        });
    })();
    </script>
</body>
</html>