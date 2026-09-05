<?php
require_once __DIR__ . '/../../../../database/db.php';

$db = (new Database())->getConnection();

$replacements = [
    'BESTLINK COLLEGE OF THE PHILIPPINES – BULACAN CAMPUS',
    'BESTLINK College of the Philippines – Bulacan Campus',
    'BESTLINK COLLEGE OF THE PHILIPPINES',
    'BESTLINK College of the Philippines',
    'Bestlink College of the Philippines',
    'HR Directress',
];

$stmt = $db->prepare("SELECT template_id, template_content FROM lc_document_templates WHERE template_code = :code LIMIT 1");
$stmt->execute([':code' => 'nda']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "NDA template not found.\n";
    exit;
}

$content = $row['template_content'];
$original = $content;

foreach ($replacements as $text) {
    $content = str_replace($text, '', $content);
}

$content = preg_replace('/<br\s*\/?>\s*<br\s*\/?>/', '<br>', $content);
$content = preg_replace('/\n{3,}/', "\n\n", $content);

if ($content === $original) {
    echo "No changes needed.\n";
    exit;
}

$update = $db->prepare("UPDATE lc_document_templates SET template_content = :content, updated_at = NOW() WHERE template_id = :id");
$update->execute([':content' => $content, ':id' => (int) $row['template_id']]);

echo "Updated NDA template_id " . (int) $row['template_id'] . ".\n";
