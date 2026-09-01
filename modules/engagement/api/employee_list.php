<?php
require_once '../../auth/database.php';

$db = Database::getInstance()->getConnection();
// Include associated user id when available so frontend can map to user_account.id
$sql = 'SELECT e.employee_id, CONCAT_WS(" ", e.first_name, e.middle_name, e.last_name) as full_name, e.user_id
	FROM em_employees e
	ORDER BY CONCAT_WS(" ", e.first_name, e.middle_name, e.last_name)';
$stmt = $db->query($sql);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($employees);
