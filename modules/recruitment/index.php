<?php

require_once 'classes/Page.php';

$pageController = new Page();

$currentPage = $pageController->getPage();

/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
| Handle redirecting/action routes BEFORE any HTML is output.
|--------------------------------------------------------------------------
*/
$pageController->handleAction();

/*
|--------------------------------------------------------------------------
| Load layout
|--------------------------------------------------------------------------
*/
include 'includes/sidebar.php';
include 'includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <?php $pageController->render(); ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>