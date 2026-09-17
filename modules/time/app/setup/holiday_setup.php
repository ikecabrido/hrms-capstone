<?php

/**
 * Holiday Feature Setup and Initialization
 * Run this once to initialize the holiday system
 */

session_start();
require_once __DIR__ . '/../core/TimeDatabase.php';

// Auto-load classes FIRST
spl_autoload_register(function ($class) {
    $base = __DIR__ . '/..';
    $file = $base . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Now require the model files directly
require_once __DIR__ . '/../models/Holiday.php';
require_once __DIR__ . '/../services/NagerDateService.php';
require_once __DIR__ . '/../helpers/HolidayHelper.php';

use App\Models\Holiday;
use App\Services\NagerDateService;
use App\Helpers\HolidayHelper;

$db = TimeDatabase::getInstance()->getConnection();
$holidayModel = new Holiday($db);
$nagerService = new NagerDateService($db, 'PH');
HolidayHelper::init($db);

$message = '';
$status = '';

// Check if tables exist
try {
    $stmt = $db->query("SELECT COUNT(*) FROM ta_holidays");
    $tableExists = true;
} catch (\Exception $e) {
    $tableExists = false;
    $message = 'Holiday tables do not exist. Please run the migration first.';
    $status = 'error';
}

// Initialize if requested
if (($_POST['action'] ?? null) === 'init' && $tableExists) {
    try {
        // Sync current and next year holidays
        $result = $nagerService->syncHolidays($_SESSION['user_id'] ?? null);

        if ($result['success']) {
            $message = $result['message'];
            $status = 'success';
        } else {
            $message = 'Sync failed: ' . $result['message'];
            $status = 'error';
        }
    } catch (\Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $status = 'error';
    }
}

// Get statistics
$stats = null;
if ($tableExists) {
    try {
        $currentYear = date('Y');
        $stats = HolidayHelper::getHolidayStats($currentYear);
    } catch (\Exception $e) {
        // Silent fail
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Feature Setup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/holiday-setup.css">
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1><i class="fas fa-calendar-alt"></i> Holiday Feature Setup</h1>
            <p class="text-muted">Initialize and manage PH holidays</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $status === 'error' ? 'danger' : 'success'; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$tableExists): ?>
            <div class="alert alert-warning">
                <h5>⚠️ Database Not Ready</h5>
                <p>Please run the migration file first:</p>
                <code>migrations/003_create_holidays_table.sql</code>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Feature Status</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <span class="status-badge status-success">✓ Tables Created</span>
                    </p>
                    <p class="text-muted small">All required database tables are present and ready to use.</p>
                </div>
            </div>

            <?php if ($stats): ?>
                <div class="stats-card">
                    <h5>Current Year Holidays (<?php echo date('Y'); ?>)</h5>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="text-primary" style="font-size: 1.5rem; font-weight: bold;">
                                <?php echo $stats['total']; ?>
                            </div>
                            <small class="text-muted">Total Holidays</small>
                        </div>
                        <div class="col-6">
                            <div class="text-info" style="font-size: 1.5rem; font-weight: bold;">
                                <?php echo $stats['recurring']; ?>
                            </div>
                            <small class="text-muted">Recurring</small>
                        </div>
                    </div>
                    <hr>
                    <small class="text-muted">
                        National: <?php echo $stats['national']; ?> |
                        Regional: <?php echo $stats['regional']; ?> |
                        Optional: <?php echo $stats['optional']; ?>
                    </small>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Included Features</h5>
                </div>
                <div class="card-body">
                    <ul class="feature-list">
                        <li>Automatic holiday fetching from Nager.Date API</li>
                        <li>Support for recurring holidays (yearly)</li>
                        <li>Dashboard widget with countdown</li>
                        <li>Calendar integration with holiday marking</li>
                        <li>Automatic exclusion from attendance requirements</li>
                        <li>Leave balance calculation excluding holidays</li>
                        <li>Holiday awareness in absence tracking</li>
                        <li>Manual holiday management</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Initialize System</h5>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return confirm('This will sync PH holidays for current and next year. Continue?')">
                        <input type="hidden" name="action" value="init">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sync-alt"></i> Sync Holidays from API
                        </button>
                    </form>
                    <small class="text-muted d-block mt-2">
                        This will fetch holidays from nager.date API and populate your database.
                    </small>
                </div>
            </div>

            <div class="mt-4 p-3 bg-light rounded">
                <h6>Next Steps:</h6>
                <ol class="mb-0">
                    <li>Click "Sync Holidays from API" to populate holidays</li>
                    <li>Add the dashboard widget to your dashboard page</li>
                    <li>Integrate calendar with holiday calendar manager</li>
                    <li>Update attendance logic to use holiday integration</li>
                    <li>Enable leave/absence integration</li>
                </ol>
            </div>
        <?php endif; ?>

        <div class="mt-4 text-center">
            <a href="../../../time/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
