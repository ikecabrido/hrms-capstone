<?php
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once __DIR__ . '/classes/LearningRole.php';

function resolveLearningRole(array $user, PDO $connection): string {
    return LearningRole::resolve($connection, $user);
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
                "SELECT u.user_id, u.employee_id, e.role_id, u.password, u.locked_until,
                        e.employee_code, e.first_name, e.last_name, e.position_id,
                        e.department_id, e.employment_status, p.position_name,
                        r.role_name, d.department_name
                 FROM user_account u
                 INNER JOIN em_employees e ON e.employee_id = u.employee_id
                 INNER JOIN em_roles r ON r.role_id = e.role_id
                 INNER JOIN em_positions p ON p.position_id = e.position_id
                 LEFT JOIN em_departments d ON d.department_id = e.department_id
                 WHERE e.employee_id = :employee_id
                 LIMIT 1"
            );
            $query->execute([':employee_id' => (int) $identifier]);
        } else {
            $query = $connection->prepare(
                "SELECT u.user_id, u.employee_id, e.role_id, u.password, u.locked_until,
                        e.employee_code, e.first_name, e.last_name, e.position_id,
                        e.department_id, e.employment_status, p.position_name,
                        r.role_name, d.department_name
                 FROM user_account u
                 INNER JOIN em_employees e ON e.employee_id = u.employee_id
                 INNER JOIN em_roles r ON r.role_id = e.role_id
                 INNER JOIN em_positions p ON p.position_id = e.position_id
                 LEFT JOIN em_departments d ON d.department_id = e.department_id
                 WHERE e.employee_code = :employee_code
                 LIMIT 1"
            );
            $query->execute([':employee_code' => $identifier]);
        }

        $user = $query->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['employment_status'] === 'Active'
            && (empty($user['locked_until']) || strtotime($user['locked_until']) <= time())
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

            $ldRole = resolveLearningRole($user, $connection);
            $_SESSION['learning_role'] = $ldRole;

            header('Location: index.php');
            exit();
        }

        $error = 'Invalid employee ID or password.';
    }
}

$loginSkills = [];
try {
    if (!isset($connection) || !($connection instanceof PDO)) {
        $database = new Database();
        $connection = $database->getConnection();
    }

    $skillQuery = $connection->query(
        "SELECT DISTINCT TRIM(name) AS name
         FROM ld_skill
         WHERE status = 'active' AND TRIM(name) <> ''
         ORDER BY name ASC"
    );
    $loginSkills = $skillQuery->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    error_log('Login skill rotation query failed: ' . $e->getMessage());
}

if (empty($loginSkills)) {
    $loginSkills = ['Leadership', 'Communication Skills', 'Problem Solving', 'Data Analysis'];
}

$loginSkillLabels = array_map(static function ($skill) {
    return $skill . '.';
}, $loginSkills);
$initialLoginSkill = (string) $loginSkillLabels[0];
$loginSkillsJson = json_encode(
    $loginSkillLabels,
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
) ?: '[]';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sign in to the Bestlink College of the Philippines Learning &amp; Development portal.">
    <title>Learning &amp; Development Sign In</title>
    <link rel="icon" type="image/png" href="../../assets/bcp-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Sora:wght@500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/pages/login.css?v=<?= (int) filemtime(__DIR__ . '/css/pages/login.css') ?>">
</head>
<body>
    <main class="login-page">
        <section class="login-brand" aria-labelledby="brand-title">
            <header class="brand-top">
                <div class="brand-mark">
                    <img src="../../assets/bcp-logo-transparent.png" alt="Bestlink College of the Philippines logo">
                </div>
                <div class="brand-wordmark">
                    <span>Bestlink College</span>
                    <small>of the Philippines</small>
                    <em>Learning &amp; Development</em>
                </div>
            </header>

            <div class="brand-copy">
                <p class="brand-eyebrow">Learning &amp; Development</p>
                <h1 id="brand-title">Your learning,<br><span>all in one place.</span></h1>
                <p class="brand-lede"><span class="skill-cycle" id="skillCycle" data-skills="<?= htmlspecialchars($loginSkillsJson, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($initialLoginSkill, ENT_QUOTES, 'UTF-8') ?></span><br>Track your progress and pick up where you left off.</p>
            </div>

            <footer class="brand-foot">
                <span class="brand-rule" aria-hidden="true"></span>
                <div class="brand-index" aria-label="Portal features">
                    <span>Courses</span>
                    <span>Skills</span>
                    <span>Certificates</span>
                </div>
                <p>Employee learning portal <span aria-hidden="true">&middot;</span> Est. 2002</p>
            </footer>
        </section>

        <section class="login-panel" aria-labelledby="signin-title">
            <div class="login-box">
                <div class="login-heading">
                    <p class="login-eyebrow">Welcome back</p>
                    <h2 id="signin-title">Sign in to continue</h2>
                    <p>Use your employee ID or employee code.</p>
                </div>

                <div class="error-message<?= $error !== '' ? ' show' : '' ?>" id="errorMsg" role="alert" aria-live="polite"><?= $error !== '' ? htmlspecialchars($error) : '' ?></div>

                <form id="loginForm" method="post" class="login-form">
                    <div class="input-group">
                        <label for="employee_id">Employee ID</label>
                        <div class="field">
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
                                   autocomplete="username"
                                   autofocus>
                        </div>
                    </div>

                    <div class="input-group">
                        <div class="field-head">
                            <label for="password">Password</label>
                            <a class="forgot" href="#">Forgot ID or password?</a>
                        </div>
                        <div class="field">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   placeholder="Enter your password"
                                   required
                                   autocomplete="current-password">
                        </div>
                    </div>

                    <button type="submit" class="login-button">
                        <span>Sign in</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"></path>
                            <path d="m13 6 6 6-6 6"></path>
                        </svg>
                    </button>
                </form>

                <div class="login-footer">
                    <a href="../../index.php">&larr; Back to HRMS Login</a>
                </div>
            </div>
        </section>
    </main>

    <script>
    (function () {
        var cycler = document.getElementById('skillCycle');
        if (!cycler) return;

        var skills;
        try {
            skills = JSON.parse(cycler.getAttribute('data-skills') || '[]');
        } catch (err) {
            return;
        }

        if (!Array.isArray(skills)) return;

        skills = skills.filter(function (skill) {
            return typeof skill === 'string' && skill.trim() !== '';
        });

        if (skills.length < 2) return;
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        var index = skills.indexOf(cycler.textContent.trim());
        if (index < 0) index = 0;

        var holdMs = 3000;
        var outMs = 320;
        var settleMs = 30;

        function advance() {
            cycler.classList.add('is-out');

            window.setTimeout(function () {
                index = (index + 1) % skills.length;
                cycler.textContent = skills[index];
                cycler.classList.remove('is-out');
                cycler.classList.add('is-in');

                window.setTimeout(function () {
                    cycler.classList.remove('is-in');
                    window.setTimeout(advance, holdMs);
                }, settleMs);
            }, outMs);
        }

        window.setTimeout(advance, holdMs);
    })();
    </script>

    <script>
    (function () {
        var form = document.getElementById('loginForm');
        var errorMsg = document.getElementById('errorMsg');
        var submitButton = form.querySelector('button[type="submit"]');
        var submitLabel = submitButton ? submitButton.querySelector('span') : null;

        function setPending(pending) {
            if (!submitButton) return;
            submitButton.disabled = pending;
            submitButton.setAttribute('aria-busy', pending ? 'true' : 'false');
            if (submitLabel) {
                submitLabel.textContent = pending ? 'Signing in...' : 'Sign in';
            }
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            errorMsg.className = 'error-message';
            errorMsg.textContent = '';
            setPending(true);

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
                            setPending(false);
                            errorMsg.textContent = data.message || 'Invalid employee ID or password.';
                            errorMsg.className = 'error-message show';
                        }
                    } catch (err) {
                        setPending(false);
                        errorMsg.textContent = 'Server error. Please try again.';
                        errorMsg.className = 'error-message show';
                    }
                } else {
                    setPending(false);
                    errorMsg.textContent = 'Something went wrong. Please try again.';
                    errorMsg.className = 'error-message show';
                }
            };

            request.onerror = function () {
                setPending(false);
                errorMsg.textContent = 'Something went wrong. Please try again.';
                errorMsg.className = 'error-message show';
            };

            request.send(new FormData(form));
        });
    })();
    </script>
</body>
</html>
