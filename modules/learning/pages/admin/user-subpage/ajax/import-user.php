<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 7) . "/database/db.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") { http_response_code(405); echo json_encode(["success" => false, "message" => "Method not allowed"]); exit; }
if (!isset($_FILES["csv_file"]) || $_FILES["csv_file"]["error"] !== UPLOAD_ERR_OK) { http_response_code(400); echo json_encode(["success" => false, "message" => "No CSV file uploaded"]); exit; }
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $handle = fopen($_FILES["csv_file"]["tmp_name"], "r");
    if (!$handle) throw new Exception("Could not open uploaded file");
    $headers = fgetcsv($handle);
    $imported = 0;
    $errors = [];
    $lineNum = 1;
    while (($row = fgetcsv($handle)) !== false) {
        $lineNum++;
        if (count($row) < 3) { $errors[] = "Line $lineNum: insufficient columns"; continue; }
        $data = array_combine($headers, $row);
        $firstName = $data["first_name"] ?? $data["First Name"] ?? "";
        $lastName = $data["last_name"] ?? $data["Last Name"] ?? "";
        $email = $data["email"] ?? $data["Email"] ?? "";
        if (empty($firstName) || empty($lastName) || empty($email)) { $errors[] = "Line $lineNum: missing required fields"; continue; }
        $check = $pdo->prepare("SELECT employee_id FROM em_employees WHERE email = :email LIMIT 1");
        $check->execute([":email" => $email]);
        if ($check->fetch()) { $errors[] = "Line $lineNum: email $email already exists"; continue; }
        $stmt = $pdo->prepare("INSERT INTO em_employees (first_name, last_name, email) VALUES (:fn, :ln, :em)");
        $stmt->execute([":fn" => $firstName, ":ln" => $lastName, ":em" => $email]);
        $imported++;
    }
    fclose($handle);
    echo json_encode(["success" => true, "imported" => $imported, "errors" => $errors, "message" => "Imported $imported users" . ($errors ? " with " . count($errors) . " errors" : "")]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }