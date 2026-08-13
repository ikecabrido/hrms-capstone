<?php
require_once __DIR__ . '/../../auth/guard.php';
require_once 'classes/Page.php';

$pageController = new Page();
$currentPage = $pageController->getPage();

include 'includes/header.php';
include 'includes/sidebar.php';

?>

<main class="main-content">
    <div class="container">
        <?php $pageController->render(); ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>