<?php
/**
 * Learning sidebar — nav data only.
 *
 * The markup is shared by every module: see includes/sidebar.php at the app root.
 */
$sidebar = [
    'dir'   => dirname(__DIR__),
    // This sidebar has never shown a module heading; the pages carry their own.
    'title' => null,
];

require dirname(__DIR__, 3) . '/includes/sidebar.php';
