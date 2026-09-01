<?php
namespace App\Models;

require_once __DIR__ . '/../../../database/db.php';

use Database;
use PDO;

class BaseModel
{
    protected $db;
    protected $pdo;

    public function __construct(PDO $connection = null)
    {
        if ($connection !== null) {
            $this->db = $connection;
            $this->pdo = $connection;
        } else {
            $database = class_exists(Database::class) && method_exists(Database::class, 'getInstance')
                ? Database::getInstance()
                : new Database();

            $this->db = $database->getConnection();
            $this->pdo = $this->db;
        }
    }

    protected function execute($sql, $params = [])
    {
        // ✅ Removed debug logs (no more output issues)

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function getEmployeeNameSql($employeeAlias = 'e', $alias = 'employee_name')
    {
        $expression = $this->getEmployeeNameExpression($employeeAlias);
        return "$expression AS $alias";
    }

    protected function getEmployeeNameExpression($employeeAlias = 'e')
    {
        $candidates = ['em_employees', 'employees'];
        foreach ($candidates as $table) {
            $cols = $this->execute("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table", ['table' => $table])->fetchAll(PDO::FETCH_ASSOC);
            if (empty($cols)) continue;
            $columnNames = array_column($cols, 'COLUMN_NAME');

            if (in_array('full_name', $columnNames, true)) {
                return "$employeeAlias.full_name";
            }
            if (in_array('first_name', $columnNames, true) && in_array('middle_name', $columnNames, true) && in_array('last_name', $columnNames, true)) {
                return "CONCAT_WS(' ', $employeeAlias.first_name, $employeeAlias.middle_name, $employeeAlias.last_name)";
            }
            if (in_array('first_name', $columnNames, true) && in_array('last_name', $columnNames, true)) {
                return "CONCAT($employeeAlias.first_name, ' ', $employeeAlias.last_name)";
            }
            if (in_array('first_name', $columnNames, true)) {
                return "$employeeAlias.first_name";
            }
            if (in_array('last_name', $columnNames, true)) {
                return "$employeeAlias.last_name";
            }
        }

        return "''";
    }

    protected function hasTable($tableName)
    {
        $result = $this->execute('SHOW TABLES LIKE :tableName', ['tableName' => $tableName])->fetch();
        return !empty($result);
    }

    protected function hasColumn($table, $column)
    {
        $result = $this->execute("SHOW COLUMNS FROM {$table} LIKE :column", ['column' => $column])->fetch();
        return !empty($result);
    }

    public function tableHasColumns($table, array $columns)
    {
        if (!$this->hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (!$this->hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
}    