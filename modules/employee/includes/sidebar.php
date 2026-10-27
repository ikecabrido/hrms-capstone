<?php
/**
 * Employee sidebar — nav data only.
 *
 * The markup is shared by every module: see includes/sidebar.php at the app root.
 */
$sidebar = [
    'dir'   => dirname(__DIR__),
    'title' => 'Employee Dashboard',
];

require dirname(__DIR__, 3) . '/includes/sidebar.php';
