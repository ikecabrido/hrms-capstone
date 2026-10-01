<?php
/**
 * Temporary debug script — do not commit.
 * Reproduces getAllSettlements() call and prints schema + exception details.
 */
require_once __DIR__ . '/models/SettlementModel.php';

try {
    $model = new SettlementModel();

    echo "Column 'resignation_id' exists: ";
    echo $model->columnExists('exit_employee_settlements', 'resignation_id') ? "YES\n" : "NO\n";

    echo "\nColumns in exit_employee_settlements:\n";
    $db = $model->getConnection();
    $stmt = $db->query("SHOW COLUMNS FROM exit_employee_settlements");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo " - " . ($c['Field'] ?? '') . "\n";
    }

    echo "\nCalling getAllSettlements()...\n";
    $res = $model->getAllSettlements('all', 1, 10, '');
    echo "Success. Returned keys: \n";
    print_r(array_keys($res));

} catch (Throwable $t) {
    echo "EXCEPTION: " . $t->getMessage() . "\n";
    echo "Trace:\n" . $t->getTraceAsString() . "\n";
}

echo "\n--- debug_repro_settlements.php finished ---\n";
