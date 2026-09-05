<?php
/**
 * Diagnostic script: construct the same SQL used by SettlementModel::getAllSettlements()
 * and execute it to capture the exact PDO exception and SQLSTATE/error code.
 * This script does NOT modify data.
 */
require_once __DIR__ . '/models/SettlementModel.php';

try {
    $model = new SettlementModel();
    $db = $model->getConnection();

    echo "--- Invoking SettlementModel::getAllSettlements() ---\n";
    try {
        $result = $model->getAllSettlements('all', 1, 10, '');
        echo "Method executed successfully. Returned keys: \n";
        print_r(array_keys($result));
    } catch (PDOException $ex) {
        echo "PDOException message: " . $ex->getMessage() . "\n";
        echo "errorInfo: "; var_export($ex->errorInfo); echo "\n";
    } catch (Throwable $t) {
        echo "Exception: " . $t->getMessage() . "\n";
        echo $t->getTraceAsString() . "\n";
    }

    // Print SHOW COLUMNS for relevant tables
    $tables = ['exit_employee_settlements','exit_resignations','exit_terminations','em_employees','em_departments','em_positions'];
    foreach ($tables as $t) {
        echo "\nColumns for table: {$t}\n";
        try {
            $colStmt = $db->query("SHOW COLUMNS FROM {$t}");
            $cols = $colStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$cols) {
                echo " (no such table or empty)\n";
                continue;
            }
            foreach ($cols as $c) {
                echo " - " . ($c['Field'] ?? '') . "\n";
            }
        } catch (Exception $e) {
            echo " Error: " . $e->getMessage() . "\n";
        }
    }

} catch (Throwable $t) {
    echo "Top-level exception: " . $t->getMessage() . "\n";
    echo $t->getTraceAsString() . "\n";
}
