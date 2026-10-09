<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$id = (int)($_POST['id']??0);
require_once dirname(__FILE__, 4) . '/classes/certificate.php';
$cert = new Certificate($pdo);
echo json_encode($cert->archive($id));
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
