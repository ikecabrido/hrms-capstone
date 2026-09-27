<?php
$file = 'C:\xampp\htdocs\hrms-capstone\modules\compliance\lib\ajax\lala-ai-chat.php';
echo 'File exists: ' . (file_exists($file) ? 'yes' : 'no') . "\n";
echo 'Modified time: ' . date('Y-m-d H:i:s', filemtime($file)) . "\n";
$content = file_get_contents($file);
echo "Contains 'workplace concern' in specificWords: " . (strpos($content, "'workplace concern'") !== false ? 'yes' : 'no') . "\n";
echo "Contains 'confidenceQuery': " . (strpos($content, 'confidenceQuery') !== false ? 'yes' : 'no') . "\n";
echo "Contains 'workplaceKeywords': " . (strpos($content, 'workplaceKeywords') !== false ? 'yes' : 'no') . "\n";
echo "Length: " . strlen($content) . "\n";
