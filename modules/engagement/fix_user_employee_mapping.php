<?php
session_start();
require_once "../../auth/database.php";

// Get database connection
$db = Database::getInstance()->getConnection();

echo "<h1>Fix User-Employee Mapping</h1>";

// First, show the current state
echo "<h3>Current Mismatches:</h3>";

$sql = "SELECT u.id, u.username, u.employee_id as users_emp_id, e.employee_id, CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) as full_name
        FROM users u
        LEFT JOIN em_employees e ON u.id = e.user_id
        WHERE (u.employee_id IS NULL AND e.employee_id IS NOT NULL)
           OR (u.employee_id != CAST(e.employee_id AS CHAR) AND e.employee_id IS NOT NULL)
        ORDER BY u.id";

$stmt = $db->prepare($sql);
$stmt->execute();
$mismatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($mismatches)) {
    echo "<p>Found " . count($mismatches) . " mismatches:</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>User ID</th><th>Username</th><th>User emp_id</th><th>Employee ID</th><th>Employee Name</th></tr>";
    foreach ($mismatches as $m) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($m['id']) . "</td>";
        echo "<td>" . htmlspecialchars($m['username']) . "</td>";
        echo "<td>" . htmlspecialchars($m['users_emp_id'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($m['employee_id'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($m['full_name'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No mismatches found!</p>";
}

// Show the fix SQL
echo "<h3>Fix SQL:</h3>";
$fixSql = "UPDATE users u
           INNER JOIN em_employees e ON u.id = e.user_id
           SET u.employee_id = e.employee_id
           WHERE u.employee_id IS NULL OR u.employee_id != CAST(e.employee_id AS CHAR);";

echo "<p><strong>This query will:</strong> Update all users.employee_id to match the corresponding employees.employee_id</p>";
echo "<pre>" . htmlspecialchars($fixSql) . "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_fix'])) {
    try {
        $db->exec($fixSql);
        echo "<div class='alert alert-success'><strong>Success!</strong> Fixed all user-employee mappings.</div>";
        
        // Show new state
        echo "<h3>Updated Mapping:</h3>";
        $sql = "SELECT u.id, u.username, u.employee_id, e.employee_id, CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) as full_name
                FROM users u
                LEFT JOIN em_employees e ON u.id = e.user_id
                LIMIT 20";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>User ID</th><th>Username</th><th>User emp_id</th><th>Employee ID</th><th>Employee Name</th></tr>";
        foreach ($results as $r) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($r['id']) . "</td>";
            echo "<td>" . htmlspecialchars($r['username']) . "</td>";
            echo "<td>" . htmlspecialchars($r['employee_id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($r['employee_id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($r['full_name'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    ?>
    <form method="POST">
        <button type="submit" name="apply_fix" class="btn btn-warning">Apply Fix to Database</button>
    </form>
    <?php
}

?>

