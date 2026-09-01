<?php
// Start output buffering early so pages can safely send headers after includes
if (!ob_get_level()) ob_start();
require_once 'classes/Page.php';

$pageController = new Page();
$currentPage = $pageController->getPage();

include 'includes/sidebar.php';
include 'includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <?php $pageController->render(); ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>