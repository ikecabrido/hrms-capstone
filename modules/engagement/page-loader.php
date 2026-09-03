<?php
require_once 'classes/Page.php';

$pageController = new Page();

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Rendered-Page: ' . $pageController->getPage());

$pageController->render();
exit;