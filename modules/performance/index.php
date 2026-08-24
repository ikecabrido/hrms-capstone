<?php
require_once 'classes/Page.php';

$pageController = new Page();
$currentPage = $pageController->getPage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentPage === 'goal-setting') {
        require_once 'classes/GoalController.php';
        (new GoalController())->handleRequest();
    } elseif ($currentPage === '360-degree-feedback') {
        require_once 'classes/FeedbackController.php';
        (new FeedbackController())->handleRequest();
    } elseif ($currentPage === 'appraisals-review') {
        require_once 'classes/AppraisalController.php';
        (new AppraisalController())->handleRequest();
    } elseif ($currentPage === 'kpi-tracking') {
        require_once 'classes/KpiController.php';
        (new KpiController())->handleRequest();
    } elseif ($currentPage === 'training-development') {
        require_once __DIR__ . '/controller/TrainingDevelopmentController.php';
        (new TrainingDevelopmentController())->handleRequest();
    } elseif ($currentPage === 'performance-report') {
        require_once __DIR__ . '/controller/PerformanceReportController.php';
        (new PerformanceReportController())->handleRequest();
    }
}

include 'includes/sidebar.php';
include 'includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <?php $pageController->render(); ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>