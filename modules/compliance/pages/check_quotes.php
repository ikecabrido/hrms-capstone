<?php
$p = 'C:\\xampp\\htdocs\\hrms-capstone\\modules\\compliance\\pages\\incident-reports.php';
$content = file_get_contents($p);

$phptags = [];
preg_match_all('/<\?(?:php|=)?|\\?>/', $content, $matches, PREG_OFFSET_CAPTURE);

$state = 'closed';
$unclosed = [];

foreach ($matches[0] as $i => $m) {
    $tag = $m[0];
    $offset = $m[1];
    $line = substr_count(substr($content, 0, $offset), "\n") + 1;
    
    if ($tag === '<?php' || $tag === '<?=') {
        if ($state === 'closed') {
            $state = 'open';
        } else {
            echo "Line $line: WARNING - PHP opened while already open ($tag)\n";
        }
    } elseif ($tag === '?>') {
        if ($state === 'open') {
            $state = 'closed';
        } else {
            echo "Line $line: WARNING - PHP closed while already closed (?>)\n";
        }
    }
}

if ($state === 'open') {
    echo "ERROR: PHP block is still open at end of file!\n";
} else {
    echo "All PHP blocks are properly closed.\n";
}
