<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/dberror.php';
try {
    $pdo = (new Database())->getConnection();
    $rows = $pdo->query('SELECT id, title FROM ld_course WHERE status = \'active\' ORDER BY title ASC')->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'items' => $rows]);
} catch (Throwable $e) {
    DbError::json($e, 'instructor/get-courses-for-path');
}
