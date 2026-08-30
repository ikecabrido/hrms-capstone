<?php
require_once dirname(__DIR__, 2) . '/database/db.php';

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
    <style>
        :root { color-scheme: light; font-family: Arial, sans-serif; background: #eef4fb; color: #14213d; }
        body { display: grid; min-height: 100vh; place-items: center; margin: 0; padding: 1rem; }
        main { width: min(100%, 24rem); padding: 2rem; background: #fff; border: 1px solid #d7e1ee; border-radius: 12px; box-shadow: 0 16px 40px rgba(20, 33, 61, .12); }
        h1 { margin: 0 0 .35rem; font-size: 1.5rem; }
        p { color: #53657d; }
        label { display: block; margin: 1rem 0 .35rem; font-weight: 700; }
        input { box-sizing: border-box; width: 100%; padding: .75rem; border: 1px solid #b9c8da; border-radius: 6px; font: inherit; }
        button { width: 100%; margin-top: 1.25rem; padding: .8rem; border: 0; border-radius: 6px; background: #087fbd; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .error { padding: .7rem; border-radius: 6px; background: #fff0f0; color: #a32121; }
    </style>
</head>
<body>
    <main>
        <h1>Learning &amp; Development</h1>
        <p>Sign in with your HRMS employee account.</p>
        <?php if ($error !== ''): ?><div class="error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <label for="employee_id">Employee ID</label>
            <input id="employee_id" name="employee_id" type="text" autocomplete="username" required>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit">Sign in</button>
        </form>
    </main>
</body>
</html>
