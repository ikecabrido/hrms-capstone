<?php
/**
 * QR Camera Scanner - separate tab for live QR camera scanning
 */
require_once __DIR__ . "/../app/controllers/AuthController.php";
require_once __DIR__ . "/../app/core/Session.php";

Session::start();

if (!AuthController::isAuthenticated()) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

$current_page = 'qr_scanner.php';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'time';
$page_title = 'QR Scanner';
$body_class = 'hold-transition';
$page_head_extra = "<link rel=\"stylesheet\" href=\"assets/css/style.css\">\n<link rel=\"stylesheet\" href=\"assets/css/hr-template.css\">\n<link rel=\"stylesheet\" href=\"assets/css/qr-scanner.css\">";

$page_footer_extra = <<<'SCRIPT'
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="assets/js/qr-scanner.js"></script>
SCRIPT;

?>
<?php require_once __DIR__ . '/../layout/page_start.php'; ?>
<div class="content-wrapper p-0" style="min-height:100vh;background:#f4f6f9;">
  <section class="content">
    <div class="container-fluid py-4">
      <div class="kiosk-container">
      <div class="kiosk-camera">
        <div class="card card-primary card-outline h-100" style="margin:0;">
          <div class="card-header kiosk-header">
            <div class="camera-card-title">
              <div class="d-flex align-items-center gap-2">
                <i class="fas fa-video text-primary"></i>
                <span>Camera Scanner</span>
              </div>
            </div>
            <div class="camera-card-toolbar">
              <button id="backBtn" type="button" class="btn btn-outline-secondary btn-sm kiosk-back-btn">Back</button>
            </div>
          </div>
          <div class="card-body d-flex flex-column p-3">
            <div class="scanner-panel">
            <div id="cameraScanner"></div>
            <div class="scanner-overlay"></div>
          </div>
            <div class="scanner-actions mt-4">
              <button id="startCam" class="btn btn-primary btn-lg"><i class="fas fa-play"></i>Start Camera</button>
              <button id="stopCam" class="btn btn-secondary btn-lg" disabled><i class="fas fa-stop"></i>Stop Camera</button>
            </div>
          </div>
        </div>
      </div>

      <div class="kiosk-info">
        <div class="card employee-info-card h-100">
          <div class="card-header">
            <h5 class="card-title mb-0">Employee Info</h5>
          </div>
          <div class="card-body employee-info-body" id="employeeInfo">
            <div class="employee-info-shell">
              <div class="greeting-label">Good morning</div>
              <div class="employee-avatar-circle"><i class="fas fa-user"></i></div>
              <div class="scan-summary-card">
                <div class="scan-summary-label">Last scan</div>
                <div class="scan-summary-value">Waiting for a QR code</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    </div>
  </section>
</div>

