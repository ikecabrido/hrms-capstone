<?php

require_once __DIR__ . '/../../../../database/db.php';

$database = new Database();
$db = $database->getConnection();

if (!($db instanceof PDO)) {
    die('Database connection failed.');
}

$tables = [
    'lc_external_case_events',
    'lc_external_case_references',
    'lc_external_case_notes',
];

$allExist = true;
foreach ($tables as $table) {
    $stmt = $db->prepare("SHOW TABLES LIKE " . $db->quote($table));
    $stmt->execute();
    if (!$stmt->fetchColumn()) {
        $allExist = false;
        break;
    }
}

if ($allExist) {
    die('External case tables already exist.');
}

$sql = file_get_contents(__DIR__ . '/../../sql/external_cases.sql');
if ($sql === false) {
    die('Could not read SQL migration file.');
}

try {
    $db->beginTransaction();

    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if ($statement === '') continue;
        $db->exec($statement);
    }

    $db->commit();
    die('External case tables created successfully.');
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    die('Migration failed: ' . $e->getMessage());
}
