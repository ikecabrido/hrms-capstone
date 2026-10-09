<?php
/**
 * Instructor roster — designate an employee as an instructor before they teach.
 *
 *   php modules/learning/cron/designate-instructor.php --list
 *   php modules/learning/cron/designate-instructor.php <employee_id> [--by=<id>] [--note="..."]
 *   php modules/learning/cron/designate-instructor.php <employee_id> --remove
 *
 * Why this exists: the module used to call someone an instructor only when a course,
 * learning path, program or video conference named them as its instructor. A new
 * instructor therefore had no L&D access until something was assigned. This tool
 * writes the explicit designation to ld_instructor, which Page::isInstructor() reads,
 * so the account can sign in as an instructor straight away. The rule is
 * "teaching assignment OR roster row => instructor"; the L&D staff role is neither.
 *
 * The table is created here if it is missing, so the tool is self-sufficient.
 */

require_once dirname(__FILE__, 4) . '/database/db.php';
require_once dirname(__FILE__, 2) . '/classes/Page.php';

$argv = $_SERVER['argv'] ?? [];
$args = array_slice($argv, 1);

$employeeId = 0;
$designatedBy = null;
$note = null;
$remove = false;
$list = false;
$error = '';

foreach ($args as $arg) {
    if ($arg === '--remove') {
        $remove = true;
    } elseif ($arg === '--list') {
        $list = true;
    } elseif (strpos($arg, '--by=') === 0) {
        $designatedBy = (int) substr($arg, 5);
    } elseif (strpos($arg, '--note=') === 0) {
        $note = substr($arg, 7);
    } elseif (ctype_digit($arg)) {
        $employeeId = (int) $arg;
    } elseif ($arg !== '--help' && $arg !== '-h') {
        $error = 'Unrecognised argument: ' . $arg;
    }
}

function usage(): void {
    echo "Instructor roster\n";
    echo "  designate:  php cron/designate-instructor.php <employee_id> [--by=<id>] [--note=\"...\"]\n";
    echo "  remove:     php cron/designate-instructor.php <employee_id> --remove\n";
    echo "  list:       php cron/designate-instructor.php --list\n";
}

function ensureRosterTable(PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `ld_instructor` ('
        . '`employee_id` int(10) unsigned NOT NULL,'
        . '`designated_by` int(10) unsigned DEFAULT NULL,'
        . '`note` varchar(255) DEFAULT NULL,'
        . '`designated_at` timestamp NOT NULL DEFAULT current_timestamp(),'
        . 'PRIMARY KEY (`employee_id`)'
        . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
    );
}

function rosterRows(PDO $pdo): array {
    return $pdo->query(
        "SELECT i.employee_id, i.designated_by, i.note, i.designated_at,
                TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, ''))) AS full_name
         FROM ld_instructor i
         LEFT JOIN em_employees e ON e.employee_id = i.employee_id
         ORDER BY i.employee_id"
    )->fetchAll(PDO::FETCH_ASSOC);
}

function employeeExists(PDO $pdo, int $employeeId): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM em_employees WHERE employee_id = :eid LIMIT 1');
    $stmt->execute([':eid' => $employeeId]);

    return (bool) $stmt->fetchColumn();
}

if ($error !== '' || (in_array('--help', $args, true) || in_array('-h', $args, true))) {
    if ($error !== '') {
        fwrite(STDERR, $error . "\n\n");
    }
    usage();
    exit($error !== '' ? 1 : 0);
}

try {
    $pdo = (new Database())->getConnection();
    ensureRosterTable($pdo);
} catch (Throwable $e) {
    fwrite(STDERR, 'Database connection failed: ' . $e->getMessage() . "\n");
    exit(1);
}

if ($list || ($employeeId === 0 && !$remove)) {
    $rows = rosterRows($pdo);

    if ($rows === []) {
        echo "The instructor roster is empty.\n";
    } else {
        echo "Designated instructors (" . count($rows) . "):\n";
        foreach ($rows as $row) {
            $name = trim((string) ($row['full_name'] ?? '')) !== '' ? $row['full_name'] : 'Unknown employee';
            $suffix = trim((string) ($row['note'] ?? '')) !== '' ? ' — ' . $row['note'] : '';
            echo sprintf("  %-8s %s%s\n", $row['employee_id'], $name, $suffix);
        }
    }

    // Showing the roster doubles as the usage message when no id was given.
    if ($employeeId === 0 && !$list) {
        echo "\n";
        usage();
    }
    exit(0);
}

if (!employeeExists($pdo, $employeeId)) {
    fwrite(STDERR, 'No employee with id ' . $employeeId . " exists.\n");
    exit(1);
}

try {
    if ($remove) {
        Page::undesignateInstructor($pdo, $employeeId);
        echo 'Removed employee ' . $employeeId . " from the instructor roster.\n";
        echo 'They keep instructor access only if a course, path, program or conference names them.' . "\n";
    } else {
        Page::designateInstructor($pdo, $employeeId, $designatedBy, $note);
        echo 'Designated employee ' . $employeeId . " as an instructor.\n";
        echo "They can sign in to the instructor tree now, before any class is assigned.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Roster update failed: ' . $e->getMessage() . "\n");
    exit(1);
}

exit(0);
