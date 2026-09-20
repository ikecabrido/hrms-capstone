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
