<?php
require_once 'C:/xampp/htdocs/hrms-capstone/modules/compliance/classes/LalaNormalizer.php';
$n = new LalaNormalizer();
$tests = ['maternity leave', 'paternity leave', 'night differential', '13th month pay', 'philhealth', 'pagibig', 'employee termination'];
foreach ($tests as $q) {
    echo $q . ' => ' . $n->normalize($q) . "\n";
}
