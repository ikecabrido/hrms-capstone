<?php
// Admin analytics uses the same six-tab analytics view as instructors, but
// the shared page widens every query to the whole platform.
$analyticsScope = 'admin';
require __DIR__ . '/../instructor/analytics.php';
