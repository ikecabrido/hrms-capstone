<?php

session_start();

header('Content-Type: text/plain');

echo "=== SESSION ROLE DEBUG ===\n";
echo "employee_id: " . ($_SESSION['employee_id'] ?? 'NOT SET') . "\n";
echo "employee_name: " . ($_SESSION['employee_name'] ?? 'NOT SET') . "\n";
echo "role_id: " . ($_SESSION['role_id'] ?? 'NOT SET') . "\n";
echo "role_name: " . ($_SESSION['role_name'] ?? 'NOT SET') . "\n";
echo "position_id: " . ($_SESSION['position_id'] ?? 'NOT SET') . "\n";
echo "position_name: " . ($_SESSION['position_name'] ?? 'NOT SET') . "\n";
