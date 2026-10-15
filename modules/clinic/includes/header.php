<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $clinicBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/modules/clinic/index.php')), '/');
    $clinicStylesheet = __DIR__ . '/../css/styles.css';
    $clinicStylesheetVersion = is_file($clinicStylesheet) ? (string) filemtime($clinicStylesheet) : '1';
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($clinicBasePath . '/css/styles.css?v=' . $clinicStylesheetVersion, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Compliance</title>
</head>
<body>
    <header>
        <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
        </div>
        <div class="realtime" id="realtimeClock" aria-live="polite">--:-- </div>
    </header>
