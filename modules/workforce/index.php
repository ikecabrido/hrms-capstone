<?php
require_once __DIR__ . '/classes/Page.php';

$pageController = new Page();
$currentPage = $pageController->getPage();

include __DIR__ . '/includes/sidebar.php';
include __DIR__ . '/includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <?php $pageController->render(); ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>