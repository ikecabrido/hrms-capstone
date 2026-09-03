<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/guard.php';

// Module-level authorization: only System Administrator (1) or
// Employee Management Staff (3) may view this module's shell.
$ALLOWED_ROLE_IDS = [1, 3];
if (!isset($_SESSION['role_id']) || !in_array((int) $_SESSION['role_id'], $ALLOWED_ROLE_IDS, true)) {
    http_response_code(403);
    echo 'You are not authorized to access this module.';
    exit();
}

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
