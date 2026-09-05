<?php
require_once 'classes/Page.php';

$pageController = new Page();

header('Content-Type: text/html; charset=utf-8');
header('X-Rendered-Page: ' . $pageController->getPage());

$pageController->render();
exit;