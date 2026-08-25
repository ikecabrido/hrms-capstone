<?php
// Debug script to reproduce getResignations error
require_once __DIR__ . '/models/ResignationModel.php';

try {
    $model = new ResignationModel();
    $res = $model->getResignations('active', 1, 10, '');
    echo "OK\n";
    echo json_encode($res, JSON_PRETTY_PRINT);
} catch (Throwable $t) {
    echo "EXCEPTION: " . $t->getMessage() . "\n";
    echo "TRACE:\n" . $t->getTraceAsString() . "\n";
    // If PDOException, try to show the last SQL (not available by default), so dump debug info
    if ($t instanceof PDOException) {
        // nothing extra
    }
}
