<?php
$message = $message ?? 'Something went wrong.';
$title = $title ?? 'Request Failed';
$type = $type ?? 'error';

$icon = match ($type) {
    'success' => 'fa-check-circle',
    'warning' => 'fa-exclamation-triangle',
    'info' => 'fa-info-circle',
    default => 'fa-times-circle'
};

$color = match ($type) {
    'success' => '#16a34a',
    'warning' => '#d97706',
    'info' => '#2563eb',
    default => '#dc2626'
};

$background = match ($type) {
    'success' => '#f0fdf4',
    'warning' => '#fffbeb',
    'info' => '#eff6ff',
    default => '#fef2f2'
};
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($title) ?></title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body style="
    margin:0;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
    box-sizing:border-box;
    background:#f8fafc;
    font-family:Arial, sans-serif;
">

    <div style="
        width:100%;
        max-width:460px;
        padding:35px 30px;
        box-sizing:border-box;
        background:#fff;
        border:1px solid #e5e7eb;
        border-radius:16px;
        text-align:center;
        box-shadow:0 10px 30px rgba(0,0,0,.06);
    ">

        <!-- ICON -->
        <div style="
            width:64px;
            height:64px;
            margin:0 auto 18px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:16px;
            background:<?= $background ?>;
            color:<?= $color ?>;
            font-size:26px;
        ">
            <i class="fas <?= $icon ?>"></i>
        </div>

        <!-- TITLE -->
        <h2 style="
            margin:0 0 8px;
            color:#111827;
            font-size:21px;
            font-weight:700;
        ">
            <?= htmlspecialchars($title) ?>
        </h2>

        <!-- MESSAGE -->
        <p style="
            margin:0 auto 22px;
            max-width:380px;
            color:#6b7280;
            font-size:13px;
            line-height:1.6;
        ">
            <?= htmlspecialchars($message) ?>
        </p>

        <!-- BACK -->
        <button
            type="button"
            onclick="history.back()"
            style="
                display:inline-flex;
                align-items:center;
                justify-content:center;
                gap:7px;

                padding:9px 15px;

                border:1px solid #d1d5db;
                border-radius:9px;

                background:#fff;
                color:#374151;

                font-size:11px;
                font-weight:600;

                cursor:pointer;
            "
        >
            <i class="fas fa-arrow-left"></i>
            Go Back
        </button>

    </div>

</body>

</html>