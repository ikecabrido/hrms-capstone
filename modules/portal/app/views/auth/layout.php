<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="stylesheet"
        href="/hrms-capstone/modules/portal/public/css/login.css">

    <link rel="stylesheet"
        href="/hrms-capstone/modules/portal/public/css/reset-password.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <title><?= htmlspecialchars($title ?? 'HR Management System') ?></title>
</head>

<body>

    <div class="login-container">

        <div class="login-contents">

            <h1 style="
                text-align:center;
                font-weight:900;
                margin-bottom:15px;
            ">
                Employee Portal
            </h1>

            <div class="school-logo"
                style="
                    display:flex;
                    justify-content:center;
                    margin-bottom:20px;
                ">
                <img
                    src="/hrms-capstone/modules/portal/public/assets/images/bcp-logo.png"
                    alt="School Logo">
            </div>

            <?php require __DIR__ . '/../partials/notification.php'; ?>

            <?php require $content; ?>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="/hrms-capstone/modules/portal/public/js/function/togglePassword.js"></script>

    <script src="/hrms-capstone/modules/portal/public/js/extended.js"></script>

</body>

</html>