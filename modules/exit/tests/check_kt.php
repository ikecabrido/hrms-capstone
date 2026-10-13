<?php
require 'c:/xampp/htdocs/hrms/hrms-capstone/database/db.php';
$db = new Database();
$c = $db->getConnection();

$rows = $c->query('SELECT id,employee_id,successor_id,start_date,end_date,status FROM exit_knowledge_transfer_plans WHERE id IN (26,27)')->fetchAll(PDO::FETCH_ASSOC);
var_export($rows);
echo PHP_EOL;

$lp = $c->query('SELECT id,title,assigned_to,is_public,kt_plan_id FROM ld_learning_path WHERE kt_plan_id IN (26,27)')->fetchAll(PDO::FETCH_ASSOC);
var_export($lp);
echo PHP_EOL;