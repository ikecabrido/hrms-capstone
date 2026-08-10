<?php

$password = "andreiburat123";

$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Password: " . htmlspecialchars($password) . "<br>";
echo "Bcrypt Hash:<br>";
echo "<textarea rows='3' cols='100'>" . htmlspecialchars($hash) . "</textarea>";
