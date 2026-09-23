<?php
require_once 'classes/Page.php';

$pageController = new Page();
$currentPage = $pageController->getPage();

if ($currentPage === 'evaluation') {
    require_once __DIR__ . '/controller/EvaluationController.php';
    (new EvaluationController())->handleRequest();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentPage === 'goal-setting') {
        require_once 'controller/GoalSettingController.php';
        (new GoalSettingController())->handleRequest();
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