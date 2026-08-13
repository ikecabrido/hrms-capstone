<?php
/**
 * Employee Absence & Late Appeal Interface
 * Allows employees to submit excuses for absences and late arrivals
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/AbsenceLateMgmt.php';
require_once __DIR__ . '/../app/core/Session.php';

Session::start();

// Check authentication
if (!AuthController::isAuthenticated()) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

$absenceLateMgmt = new AbsenceLateMgmt();
$employee_id = $_SESSION['user']['employee_id'] ?? null;

// Get employee's records
$filters = [
    'employee_id' => $employee_id,
    'excuse_status' => $_GET['status'] ?? null,
    'limit' => 50
];

$records = $absenceLateMgmt->getRecords($filters);
$summary = $absenceLateMgmt->getEmployeeSummary($employee_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Absence & Late Appeals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../../assets/plugins/toastr/toastr.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/my-absence-appeals.css">
</head>
<body>
    <div class="container">
        <h1 style="margin-bottom: 10px;">
            <i class="fas fa-calendar-times"></i> My Absence & Late Appeals
        </h1>
        <p style="color: #666; margin-bottom: 30px;">Submit and track your excuses for absences and late arrivals</p>

        <!-- Summary -->
        <div class="summary-grid">
            <div class="summary-card">
                <h4>This Month</h4>
                <div class="summary-value"><?php echo $summary['total_records'] ?? 0; ?></div>
                <small>Total Records</small>
            </div>
            <div class="summary-card">
                <h4>Absences</h4>
                <div class="summary-value"><?php echo $summary['absent_count'] ?? 0; ?></div>
            </div>
            <div class="summary-card">
                <h4>Late Arrivals</h4>
                <div class="summary-value"><?php echo $summary['late_count'] ?? 0; ?></div>
            </div>
            <div class="summary-card">
                <h4>Pending</h4>
                <div class="summary-value"><?php echo $summary['pending_count'] ?? 0; ?></div>
            </div>
            <div class="summary-card">
                <h4>Approved</h4>
                <div class="summary-value"><?php echo $summary['excused_count'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="filterByStatus(null)">All</button>
            <button class="tab-btn" onclick="filterByStatus('PENDING')">Pending</button>
            <button class="tab-btn" onclick="filterByStatus('APPROVED')">Approved</button>
            <button class="tab-btn" onclick="filterByStatus('REJECTED')">Rejected</button>
        </div>

        <!-- Records -->
        <div class="records-list">
            <?php if (count($records) > 0): ?>
                <?php foreach ($records as $record): ?>
                <div class="record-card">
                    <div class="record-header">
                        <div>
                            <div class="record-type">
                                <i class="fas fa-<?php echo $record['type'] === 'ABSENT' ? 'user-slash' : 'clock'; ?>"></i>
                                <?php echo $record['type'] === 'ABSENT' ? 'Absence' : 'Late Arrival'; ?>
                            </div>
                            <small><?php echo date('F d, Y', strtotime($record['absence_date'])); ?></small>
                        </div>
                        <div>
                            <span class="record-badge <?php echo strtolower($record['excuse_status']); ?>">
                                <?php echo $record['excuse_status']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="record-info">
                        <div class="info-item">
                            <span class="info-label">Submitted</span>
                            <?php echo date('M d, Y', strtotime($record['submitted_date'])); ?>
                        </div>
                        <?php if ($record['reviewed_date']): ?>
                        <div class="info-item">
                            <span class="info-label">Reviewed</span>
                            <?php echo date('M d, Y', strtotime($record['reviewed_date'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($record['reason']): ?>
                    <div class="record-reason">
                        <strong>Your Reason:</strong><br>
                        <?php echo htmlspecialchars($record['reason']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($record['approval_notes']): ?>
                    <div class="record-reason" style="background: #f0f8ff;">
                        <strong>HR Notes:</strong><br>
                        <?php echo htmlspecialchars($record['approval_notes']); ?>
                    </div>
                    <?php endif; ?>

                    <div class="record-actions">
                        <?php if ($record['excuse_status'] === 'PENDING'): ?>
                        <button class="btn btn-primary" onclick="editExcuse(<?php echo $record['record_id']; ?>)">
                            <i class="fas fa-edit"></i> Edit Excuse
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-secondary" onclick="viewDetails(<?php echo $record['record_id']; ?>)">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No records found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit/Submit Modal -->
    <div class="modal-overlay" id="excuseModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Submit Excuse</h2>
                <button class="close-btn" onclick="closeModal('excuseModal')">&times;</button>
            </div>
            <form id="excuseForm">
                <div class="form-group">
                    <label>Reason for Absence/Late</label>
                    <textarea id="excuseReason" placeholder="Explain why you were absent or late..." required></textarea>
                    <small>Be as detailed as possible to help HR understand your situation</small>
                </div>
                <div class="form-group">
                    <label>Supporting Document (Optional)</label>
                    <input type="file" id="supportingDoc">
                    <small>Upload medical certificate, travel document, etc. if available</small>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('excuseModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Excuse</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/plugins/jquery/jquery.min.js"></script>
    <script src="../../assets/plugins/toastr/toastr.min.js"></script>
    <script src="../assets/js/my-absence-appeals.js"></script>
</body>
</html>
