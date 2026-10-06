<?php
require_once 'C:/xampp/htdocs/hrms-capstone/database/db.php';
require_once 'C:/xampp/htdocs/hrms-capstone/modules/compliance/classes/LaborLawReference.php';
$model = new LaborLawReference((new Database())->getConnection());
$tests = ['maternity leave', 'paternity leave', 'night differential', '13th month pay', 'philhealth', 'pagibig', 'employee termination'];
foreach ($tests as $q) {
    $results = $model->searchReferencesForAssistant($q, 5);
    echo "QUERY: $q => " . count($results) . " results\n";
    if ($results) {
        foreach ($results as $r) {
            echo "  - " . ($r['short_title'] ?? $r['title']) . " (relevance: " . ($r['relevance'] ?? '?') . ")\n";
        }
    }
}
