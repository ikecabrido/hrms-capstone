
<style id="dashboard-horizontal-fix">
.dash-row.dash-row--horizontal.dash-row--compact {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: stretch !important;
    width: 100% !important;
    gap: 16px !important;
}

.dash-row.dash-row--horizontal .dash-health-card {
    flex: 0 0 360px !important;
    width: 360px !important;
    min-width: 360px !important;
    box-sizing: border-box !important;
}

.dash-row.dash-row--horizontal .kpi-strip {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: stretch !important;
    flex: 1 1 auto !important;
    min-width: 0 !important;
    gap: 12px !important;
}

.dash-row.dash-row--horizontal .kpi-box,
.dash-row.dash-row--horizontal .kpi-box-link {
    flex: 1 1 0 !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
}

.dash-row.dash-row--horizontal .kpi-box-link {
    display: flex !important;
}

.dash-row.dash-row--horizontal .kpi-box-link .kpi-box {
    width: 100% !important;
}

.dash-row.dash-row--horizontal .kpi-box-label {
    white-space: nowrap !important;
}

@media (max-width: 900px) {
    .dash-row.dash-row--horizontal {
        overflow-x: auto !important;
    }

    .dash-row.dash-row--horizontal .dash-health-card {
        flex: 0 0 320px !important;
        width: 320px !important;
        min-width: 320px !important;
    }

    .dash-row.dash-row--horizontal .kpi-box,
    .dash-row.dash-row--horizontal .kpi-box-link {
        flex: 0 0 150px !important;
        min-width: 150px !important;
    }
}
</style>




<?php
ob_start();
require_once 'classes/Page.php';

$pageController = new Page();
$currentPage = $pageController->getPage();

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$isAjax) {
    include 'includes/sidebar.php';
    include 'includes/header.php';
}
?>

<main class="main-content">
    <div class="container">
        <?php $pageController->render(); ?>
    </div>
</main>

<?php
if (!$isAjax) {
    include __DIR__ . '/lib/includes/calendar_modal.php';
    include 'includes/footer.php';
}
