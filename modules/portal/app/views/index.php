<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/hrms-capstone/modules/portal/public/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" type="image/png" href="/hrms-capstone/modules/portal/public/assets/images/bcp-logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <title><?= htmlspecialchars($title ?? 'HR Management System') ?></title>
</head>

<body>

    <?php require $content; ?>
    
    <script>
        window.loginLockedUntil =
            <?= $lockedUntil * 1000 ?>;
    </script>
    <script src="/hrms-capstone/modules/portal/public/js/extended.js"></script>
</body>

</html>