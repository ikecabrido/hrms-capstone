<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'file' => __FILE__,
    'path' => __DIR__,
    'time' => date('Y-m-d H:i:s')
]);
