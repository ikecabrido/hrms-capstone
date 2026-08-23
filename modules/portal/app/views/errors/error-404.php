<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: url("/hrms-capstone/modules/portal/public/assets/images/error-404.jpg") center center / cover no-repeat;
            background-size: 35% auto;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
        }

        .error-container {
            position: relative;
            z-index: 1;
            width: min(90%, 600px);
            padding: 50px 40px;
            text-align: center;
            color: #fff;
        }

        .error-code {
            font-size: clamp(100px, 20vw, 180px);
            font-weight: 800;
            line-height: 0.9;
            letter-spacing: -8px;
            margin-bottom: 20px;
            text-shadow: 0 8px 25px rgba(0, 0, 0, 0.35);
        }

        .error-container h1 {
            font-size: clamp(28px, 5vw, 42px);
            margin-bottom: 15px;
        }

        .error-container p {
            font-size: 17px;
            line-height: 1.6;
            max-width: 480px;
            margin: 0 auto 30px;
            color: rgba(255, 255, 255, 0.9);
        }

        .home-button {
            display: inline-block;
            padding: 13px 28px;
            border-radius: 8px;
            background: #fff;
            color: #222;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .home-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }

        @media (max-width: 600px) {
            .error-container {
                padding: 30px 20px;
            }

            .error-code {
                letter-spacing: -4px;
            }

            .error-container p {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>

    <div class="error-container">
        <div class="error-code">404</div>

        <h1>Page Not Found</h1>

        <p>
            Sorry, the page you're looking for doesn't exist or may have
            been moved to another location.
        </p>

        <?php if ($_SESSION['is_admin'] == true): ?>
            <a href="/hrms-capstone/modules/portal/index.php?url=admin-dashboard" class="home-button">
                Back to Home
            </a>
        <?php else: ?>
            <a href="/hrms-capstone/modules/portal/index.php?url=employee-dashboard" class="home-button">
                Back to Home
            </a>
        <?php endif; ?>
    </div>

</body>

</html>