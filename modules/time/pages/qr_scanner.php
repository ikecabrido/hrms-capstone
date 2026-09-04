<?php
/**
 * QR Camera Scanner - separate tab for live QR camera scanning
 */
require_once __DIR__ . "/../app/controllers/AuthController.php";


$current_page = 'qr_scanner';
$current_role = $_SESSION['role'] ?? 'time';
?>
<link rel="stylesheet" href="assets/css/qr-scanner.css">
<div class="content-wrapper p-0" style="min-height:100vh;background:#f4f6f9;">
  <section class="content">
    <div class="container-fluid py-4">
      <div class="kiosk-container">
      <div class="kiosk-camera">
        <div class="card card-primary card-outline h-100" style="margin:0;">
          <div class="card-header kiosk-header">
            <div class="camera-card-title">
                <div class="d-flex align-items-center gap-2">
                <h1 style="margin:0;font-size:1.5rem;">Scan here!</h1>
              </div>
            </div>
            <!-- back button removed for kiosk scanner -->
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
          <div class="card-body employee-info-body" id="employeeInfo"></div>
        </div>
      </div>
    </div>

    </div>
  </section>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.1/html5-qrcode.min.js"></script>
<script src="assets/js/qr-scanner.js"></script>

