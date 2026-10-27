<?php

$view = $view ?? [
    'eyebrow' => 'Workforce intelligence',
    'title' => 'Workforce analytics',
    'description' => 'Review the latest workforce signals.',
    'cards' => [],
];
$showReportActions = $showReportActions ?? false;
?>
<section class="wa-page">
    <div class="wa-hero">
        <div>
            <span class="wa-eyebrow"><?= htmlspecialchars($view['eyebrow']) ?></span>
            <h1><?= htmlspecialchars($view['title']) ?></h1>
            <p><?= htmlspecialchars($view['description']) ?></p>
        </div>
        <div class="wa-actions">
            <button class="wa-button" type="button" onclick="location.reload()">↻ Refresh data</button>
            <?php if ($showReportActions): ?>
                <button class="wa-button wa-button-secondary" type="button" onclick="window.print()">Print report</button>
                <button class="wa-button wa-button-secondary" type="button" id="exportWorkforceCsv">Export CSV</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="wa-sync">
        <span>● Data coverage: Last 30 recorded attendance days</span>
        <span>Last synced <?= htmlspecialchars(date('d M Y, h:i A')) ?></span>
    </div>
    <?php if (!empty($view['cards'])): ?>
        <div class="wa-cards">
            <?php foreach ($view['cards'] as $card): ?>
                <article class="wa-card">
                    <span><?= htmlspecialchars((string) $card[0]) ?></span>
                    <strong><?= htmlspecialchars((string) $card[1]) ?></strong>
                    <small><?= htmlspecialchars((string) $card[2]) ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
