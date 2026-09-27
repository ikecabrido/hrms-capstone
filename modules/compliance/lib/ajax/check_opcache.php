<?php
echo 'OPcache enabled: ' . (ini_get('opcache.enable') ? 'yes' : 'no') . "\n";
echo 'OPcache file: ' . __FILE__ . "\n";
echo 'Modified time: ' . date('Y-m-d H:i:s', filemtime(__FILE__)) . "\n";
$content = file_get_contents(__FILE__);
echo "Contains 'workplace concern' in specificWords: " . (strpos($content, "'workplace concern'") !== false ? 'yes' : 'no') . "\n";
echo "Contains 'confidenceQuery': " . (strpos($content, 'confidenceQuery') !== false ? 'yes' : 'no') . "\n";
