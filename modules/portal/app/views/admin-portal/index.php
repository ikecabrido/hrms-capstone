<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/hrms-capstone/modules/portal/public/css/styles.css">
    <link rel="stylesheet" href="/hrms-capstone/modules/portal/public/css/dashboard-styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/hrms-capstone/modules/portal/public/assets/images/bcp-logo.png">
    <title><?= htmlspecialchars($title ?? 'HR Management System') ?></title>
</head>

<body>

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <?php require __DIR__ . '/../partials/admin-sidebar.php'; ?>

    <main class="main-content">
        <?php require $content; ?>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
    
    <!-- modals -->
    <?php require __DIR__ . '/../../views/all-modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/animejs@3.2.2/lib/anime.min.js"></script>
    <script type="module" src="/hrms-capstone/modules/portal/public/js/extended.js"></script>
</body>

</html>