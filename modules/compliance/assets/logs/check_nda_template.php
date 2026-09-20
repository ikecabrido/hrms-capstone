<?php
require_once __DIR__ . '/../../../../database/db.php';

$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT template_id, template_content FROM lc_document_templates WHERE template_code = :code LIMIT 1");
$stmt->execute([':code' => 'nda']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "NDA template not found.\n";
    exit;
}

echo "Template ID: " . $row['template_id'] . "\n";
echo "Content length: " . strlen($row['template_content']) . "\n";

$searchTerms = ['HR Directress', 'Bestlink College'];
foreach ($searchTerms as $term) {
    $count = substr_count(strtolower($row['template_content']), strtolower($term));
    echo "Found '$term': $count times\n";
}
