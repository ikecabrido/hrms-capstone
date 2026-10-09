<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

require_once dirname(__FILE__, 4) . '/classes/certificate.php';
$cert = new Certificate($pdo);
echo json_encode(['success'=>true,'items'=>$cert->getAll()]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
