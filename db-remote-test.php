<?php
/**
 * db-remote-test.php — External database connectivity diagnostic.
 *
 * Run on the SERVER (CLI is best; browser works too, delete the file after):
 *
 *   Option A (uses your real db_config.php automatically):
 *     php db-remote-test.php
 *
 *   Option B (pass credentials explicitly — good for testing a host BEFORE
 *   you trust it enough to put it in db_config.php):
 *     php db-remote-test.php <host> <port> <dbname> <user> <password>
 *
 * It reports four distinct stages so you can see exactly where a remote
 * connection fails:
 *   1. DNS        — can the server resolve the DB hostname at all?
 *   2. TCP        — is the MySQL port open and reachable through firewalls?
 *   3. Handshake  — does MySQL accept the user/password from THIS server's IP?
 *   4. Privileges — can that user actually USE the target database?
 *
 * SECURITY: delete this file from the web root after running it. It prints
 * connection details to whoever can reach it and must not stay deployed.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$failures = 0;

function stage(string $name, bool $ok, string $detail = ''): void {
    global $failures;
    $mark = $ok ? '[ OK ]' : '[FAIL]';
    if (!$ok) { $failures++; }
    echo $mark, ' ', $name;
    if ($detail !== '') {
        echo ' — ', $detail;
    }
    echo PHP_EOL;
}

// ── Gather credentials ───────────────────────────────────────────────────────

$argvCount = count($argv ?? []);
if ($argvCount >= 6) {
    // Explicit CLI arguments
    [$host, $port, $db, $user, $pass] = [$argv[1], $argv[2], $argv[3], $argv[4], $argv[5]];
    echo "Credentials source: command-line arguments", PHP_EOL;
} else {
    $configFile = __DIR__ . '/db_config.php';
    if (!is_file($configFile)) {
        echo "[FAIL] No credentials — db_config.php not found at project root.", PHP_EOL;
        echo "       Run with explicit arguments instead:", PHP_EOL;
        echo "       php db-remote-test.php <host> <port> <dbname> <user> <password>", PHP_EOL;
        exit(1);
    }
    $c = require $configFile;
    $host = (string)($c['host'] ?? '');
    $port = (string)($c['port'] ?? '3306');
    $db   = (string)($c['database'] ?? '');
    $user = (string)($c['username'] ?? '');
    $pass = (string)($c['password'] ?? '');
    echo "Credentials source: db_config.php", PHP_EOL;
}

// Never print the password; show everything else so output is debuggable.
$passShown = ($pass === '') ? '(empty)' : '(set, ' . strlen($pass) . ' chars)';
echo "Target: host={$host} port={$port} db={$db} user={$user} password={$passShown}", PHP_EOL;
echo "Server outbound IP as MySQL sees it is shown by the handshake failure, if one occurs.", PHP_EOL;
echo str_repeat('-', 72), PHP_EOL;

// ── Stage 1: DNS ─────────────────────────────────────────────────────────────

if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
    stage('1. DNS', true, "host is '{$host}' — no lookup needed");
    $ip = $host;
} else {
    $ip = gethostbyname($host);
    $resolved = ($ip !== $host);
    stage('1. DNS', $resolved, $resolved ? "resolved to {$ip}" : "could NOT resolve '{$host}' — check the hostname or use an IP");
}

// ── Stage 2: raw TCP ─────────────────────────────────────────────────────────

$socket = @fsockopen($ip, (int)$port, $errno, $errstr, 5);
if ($socket) {
    // MySQL sends a greeting packet immediately on connect.
    $greeting = fgets($socket, 128);
    fclose($socket);
    $looksLikeMysql = is_string($greeting) && (strpos($greeting, 'mysql') !== false || strlen($greeting) > 10);
    stage('2. TCP', true, "port {$port} open on {$ip}" . ($looksLikeMysql ? ' (MySQL greeting received)' : ''));
} else {
    stage('2. TCP', false, "cannot reach {$ip}:{$port} — {$errstr} (errno {$errno}). " .
        "On the DB host check: firewall/cpanel Remote MySQL IP whitelist, bind-address, and that MySQL is listening (netstat -tlnp | grep {$port})");
    exit(1);
}

// ── Stage 3: PDO handshake (auth + SSL negotiation) ─────────────────────────

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT            => 10,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    stage('3. Handshake', true, "authenticated and connected to {$db}");
} catch (PDOException $e) {
    $msg = $e->getMessage();
    stage('3. Handshake', false, $msg);

    if (strpos($msg, '1045') !== false) {          // Access denied (bad user/pass)
        echo "       -> Wrong username or password, or the user is not allowed from this host.", PHP_EOL;
    } elseif (strpos($msg, '1044') !== false) {    // Access denied to database
        echo "       -> User exists but has no privileges on '{$db}' — run:", PHP_EOL;
        echo "          GRANT ALL ON {$db}.* TO '{$user}'@'%';  FLUSH PRIVILEGES;", PHP_EOL;
    } elseif (strpos($msg, '1049') !== false) {    // Unknown database
        echo "       -> Database '{$db}' does not exist on that server.", PHP_EOL;
    } elseif (strpos($msg, '2002') !== false || strpos($msg, '2003') !== false) {
        echo "       -> MySQL refused/lost the connection — likely the user is only", PHP_EOL;
        echo "          defined as '{$user}'@'localhost' on the DB server, not '{$user}'@'%'.", PHP_EOL;
    }
    exit(1);
}

// ── Stage 4: privileges + how MySQL sees this server's IP ───────────────────

try {
    $who = $pdo->query("SELECT CURRENT_USER() AS cu, @@hostname AS server_hostname, @@version AS version")->fetch();
    stage('4. Privileges', true, "connected as {$who['cu']} on {$who['server_hostname']} (MySQL {$who['version']})");

    $grants = $pdo->query("SHOW GRANTS FOR CURRENT_USER()")->fetchAll(PDO::FETCH_COLUMN);
    echo "       Grants:", PHP_EOL;
    foreach ($grants as $g) {
        echo "         ", $g, PHP_EOL;
    }

    $inDb = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
    echo "       Tables visible in '{$db}': ", $inDb, PHP_EOL;
} catch (PDOException $e) {
    stage('4. Privileges', false, $e->getMessage());
}

echo str_repeat('-', 72), PHP_EOL;
echo $failures === 0
    ? "ALL CHECKS PASSED — PHP on this server can reach and use the external database."
    : "{$failures} check(s) FAILED — see messages above.",
    PHP_EOL;
exit($failures === 0 ? 0 : 1);
