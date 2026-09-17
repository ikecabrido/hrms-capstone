
  document.addEventListener('DOMContentLoaded', function(){
    let html5QrCode = null;
    let scanInProgress = false;
    let scanHistory = [];
    let scanCooldownUntil = 0;
    let lastDecodedText = null;
    let lastDecodedTime = 0;
    let lastToastKey = '';
    let lastToastAt = 0;
    const cooldownSeconds = 5;
    const startBtn = document.getElementById('startCam');
    const stopBtn = document.getElementById('stopCam');
    const infoPanel = document.getElementById('employeeInfo');
    const cameraElement = document.getElementById('cameraScanner');
    const overlayElement = document.querySelector('.scanner-overlay');
    const storageKey = 'qr_scanner_state';
    const toastContainer = document.createElement('div');
    toastContainer.className = 'scanner-toast-container';
    document.body.appendChild(toastContainer);

    function getGreeting() {
      const hour = new Date().getHours();
      if (hour < 12) return 'Good morning';
      if (hour < 18) return 'Good afternoon';
      return 'Good evening';
    }

    function formatDisplayDateTime(date) {
      return date.toLocaleString('en-US', {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      });
    }

    function isScanAllowed() {
      const now = Date.now();
      return !scanInProgress && now >= scanCooldownUntil;
    }

    function startScanCooldown(seconds = cooldownSeconds) {
      scanCooldownUntil = Date.now() + (seconds * 1000);
    }

    function isDuplicateScan(decodedText) {
      const now = Date.now();
      if (!decodedText) return false;
      if (decodedText === lastDecodedText && now - lastDecodedTime < cooldownSeconds * 1000) {
        return true;
      }
      lastDecodedText = decodedText;
      lastDecodedTime = now;
      return false;
    }

    function loadScanState() {
      try {
        const saved = localStorage.getItem(storageKey);
        if (!saved) return { active: true, scans: [] };
        const parsed = JSON.parse(saved);
        return {
          active: parsed.active !== false,
          scans: Array.isArray(parsed.scans) ? parsed.scans : []
        };
      } catch (error) {
        console.warn('Unable to load scanner state:', error);
        return { active: true, scans: [] };
      }
    }

    function saveScanState(activeState) {
      try {
        localStorage.setItem(storageKey, JSON.stringify({
          active: activeState,
          scans: scanHistory.slice(0, 2)
        }));
      } catch (error) {
        console.warn('Unable to save scanner state:', error);
      }
    }

    function pushScanHistory(entry) {
      const normalizedEntry = {
        ...entry,
        employee_id: entry.employee_id || null
      };

      scanHistory = [normalizedEntry].concat(
        scanHistory.filter(item => String(item.employee_id || '') !== String(normalizedEntry.employee_id || ''))
      ).slice(0, 2);

      saveScanState(true);
    }

    function showToast(message, type = 'info', throttleMs = 1600) {
      if (!message) return;
      const now = Date.now();
      const toastKey = type + ':' + message;
      if (toastKey === lastToastKey && now - lastToastAt < throttleMs) {
        return;
      }

      lastToastKey = toastKey;
      lastToastAt = now;

      const toast = document.createElement('div');
      toast.className = 'scanner-toast ' + (type === 'warning' ? 'warning' : '');
      toast.innerHTML = '<div class="scanner-toast-title">' + (type === 'warning' ? 'Notice' : 'Scanner') + '</div>'
        + '<div class="scanner-toast-message">' + message + '</div>';
      toastContainer.appendChild(toast);
      requestAnimationFrame(() => toast.classList.add('show'));
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 250);
      }, 4000);
    }

    function formatCooldownMessage(payload) {
      if (!payload || !payload.time_left_seconds) {
        return payload?.message || 'Unable to process scan.';
      }

      const remaining = Math.max(1, Math.ceil(payload.time_left_seconds));
      if (payload.time_left_text) {
        return payload.message + ' Available in ' + remaining + ' second' + (remaining === 1 ? '' : 's') + '.';
      }

      return payload.message + ' Available in ' + remaining + ' second' + (remaining === 1 ? '' : 's') + '.';
    }

    function buildRecentScansMarkup(employeeInfo = null, action = 'TIME_IN') {
      const entries = scanHistory.length
        ? scanHistory
        : (employeeInfo ? [{
            employee_name: employeeInfo.full_name || 'Employee',
            employee_id: employeeInfo.employee_id || null,
            department: employeeInfo.department || null,
            action: action,
            timestamp: new Date().toISOString()
          }] : []);

      if (!entries.length) {
        return '';
      }

      const items = entries.slice(0, 2).map(entry => {
        const timeLabel = entry.timestamp ? formatDisplayDateTime(new Date(entry.timestamp)) : 'Unknown time';
        const pillClass = entry.action === 'TIME_OUT' ? 'out' : 'in';
        const actionLabel = entry.action === 'TIME_OUT' ? 'Timed Out' : 'Timed In';
        return '<div class="recent-scan-item">'
          + '<div class="recent-scan-name">' + (entry.employee_name || 'Employee') + '</div>'
          + '<div class="recent-scan-meta">' + (entry.department ? entry.department + ' • ' : '') + timeLabel + '</div>'
          + '<span class="recent-scan-pill ' + pillClass + '">' + actionLabel + '</span>'
          + '</div>';
      }).join('');

      return '<div class="scan-summary-card">'
        + '<div class="scan-summary-label">Recent successful scans</div>'
        + '<div class="recent-scan-list">' + items + '</div>'
        + '</div>';
    }

    function renderIdleState() {
      if (!infoPanel) return;
      const now = new Date();
      infoPanel.innerHTML = '<div class="employee-info-shell">'
        + '<div class="greeting-label">' + getGreeting() + '</div>'
        + '<h4 class="employee-name">Ready to Scan</h4>'
        + '<p class="employee-subtext">Scan an employee QR code to record attendance.</p>'
        + '<div class="scan-summary-card">'
        + '<div class="scan-summary-label">Last scan</div>'
        + '<div class="scan-summary-value">Waiting for a QR code</div>'
        + '</div>'
        + buildRecentScansMarkup()
        + '<div class="scan-summary-card">'
        + '<div class="scan-summary-label">Current time</div>'
        + '<div class="scan-summary-value">' + formatDisplayDateTime(now) + '</div>'
        + '</div>'
        + '</div>';
    }

    function renderEmployeeDetails(employeeInfo, scanMessage, scanTime, action) {
      if (!infoPanel) return;
      const avatarMarkup = employeeInfo?.avatar && String(employeeInfo.avatar).trim() && !String(employeeInfo.avatar).includes('default-user.png')
        ? '<img src="' + employeeInfo.avatar + '" alt="Employee photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">'
        : '';
      const actionLabel = action === 'TIME_OUT' ? 'Timed Out' : 'Timed In';
      infoPanel.innerHTML = '<div class="employee-info-shell">'
        + '<div class="greeting-label">' + getGreeting() + '</div>'
        + '<div class="employee-avatar-circle">' + avatarMarkup + '</div>'
        + '<h4 class="employee-name">' + (employeeInfo?.full_name || 'Employee') + '</h4>'
        + '<p class="employee-subtext">' + (scanMessage || 'Attendance recorded successfully.') + '</p>'
        + '<div class="employee-detail-grid">'
        + '<div class="employee-detail-item"><span class="employee-detail-label">Employee ID</span><span class="employee-detail-value">' + (employeeInfo?.employee_id || 'N/A') + '</span></div>'
        + '<div class="employee-detail-item"><span class="employee-detail-label">Department</span><span class="employee-detail-value">' + (employeeInfo?.department || 'N/A') + '</span></div>'
        + '<div class="employee-detail-item"><span class="employee-detail-label">Position</span><span class="employee-detail-value">' + (employeeInfo?.position || 'N/A') + '</span></div>'
        + '</div>'
        + '<div class="scan-summary-card">'
        + '<div class="scan-summary-label">Current status</div>'
        + '<div class="scan-summary-value">' + actionLabel + '</div>'
        + '</div>'
        + '<div class="scan-summary-card">'
        + '<div class="scan-summary-label">Scanned at</div>'
        + '<div class="scan-summary-value">' + scanTime + '</div>'
        + '</div>'
        + buildRecentScansMarkup(employeeInfo, action)
        + '</div>';
    }

    function updateOverlaySize() {
      if (!overlayElement || !cameraElement) return;
      const width = cameraElement.clientWidth || window.innerWidth;
      const height = cameraElement.clientHeight || window.innerHeight;
      const size = Math.max(240, Math.min(width, height) * 0.82);
      overlayElement.style.width = `${size}px`;
      overlayElement.style.height = `${size}px`;
    }

    async function selectCameraConfig() {
      try {
        const devices = await Html5Qrcode.getCameras();
        console.log('Available cameras:', devices);
        if (devices && devices.length) {
          const preferred = devices.find(device => /back|rear|environment/i.test(device.label || '')) || devices[0];
          if (preferred && preferred.id) {
            return { deviceId: { exact: preferred.id } };
          }
        }
      } catch (error) {
        console.warn('Camera selection failed, falling back to facingMode:', error);
      }
      return { facingMode: 'environment' };
    }

    async function startCamera(){
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showToast('This browser does not support camera access. Use Chrome, Edge, or Safari on desktop/mobile.', 'warning');
        return;
      }

      const secureContextAllowed = window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
      if (!secureContextAllowed) {
        showToast('Camera access requires HTTPS or localhost. Open this page through localhost or enable HTTPS.', 'warning');
        return;
      }

      if (!window.Html5Qrcode) {
        showToast('Camera library failed to load. Please refresh the page and try again.', 'warning');
        return;
      }

      if (html5QrCode || scanInProgress) return;
      saveScanState(true);
      updateOverlaySize();

      const width = cameraElement?.clientWidth || 640;
      const height = cameraElement?.clientHeight || 480;
      const qrboxSize = Math.max(240, Math.min(width, height) * 0.72);
      const config = {
        fps: 10,
        qrbox: { width: qrboxSize, height: qrboxSize },
        aspectRatio: (width && height) ? width / height : 1.777,
        disableFlip: false
      };

      const mirrorVideo = () => {
        const videoEl = document.querySelector('#cameraScanner video');
        if (!videoEl) return;
        videoEl.style.transform = 'scaleX(-1)';
        videoEl.style.webkitTransform = 'scaleX(-1)';
        videoEl.style.mozTransform = 'scaleX(-1)';
      };

      try {
        html5QrCode = new Html5Qrcode('cameraScanner');
        const cameraConfig = await selectCameraConfig();

        console.log('Starting camera with config:', cameraConfig, config);

        await html5QrCode.start(cameraConfig, config,
          decodedText => {
            mirrorVideo();
            if (!isScanAllowed()) {
              const remaining = Math.max(1, Math.ceil((scanCooldownUntil - Date.now()) / 1000));
              const waitingMessage = 'Please wait ' + remaining + ' second' + (remaining === 1 ? '' : 's') + ' before scanning again.';
              showToast(waitingMessage, 'warning', 2500);
              return;
            }

            if (isDuplicateScan(decodedText)) {
              showToast('Duplicate QR scan ignored. Please move away and scan again.', 'warning', 2500);
              startScanCooldown(cooldownSeconds);
              return;
            }

            scanInProgress = true;
            startScanCooldown(cooldownSeconds);

            console.log('QR decoded:', decodedText);
            const apiRoot = window.__TA_ROOT || '/hrms/hrms-capstone/modules/time';
            const requestUrl = apiRoot + '/processStaticQR.php?id=' + encodeURIComponent(decodedText);
            console.log('QR request URL:', requestUrl);
            console.log('QR request params:', { id: decodedText, method: 'GET' });

            fetch(requestUrl, { headers: { Accept: 'application/json' } })
              .then(async r => {
                const text = await r.text();
                if (!r.ok) {
                  throw new Error('HTTP ' + r.status + ': ' + text.slice(0, 200));
                }
                try {
                  return JSON.parse(text);
                } catch (error) {
                  console.error('QR response was not valid JSON:', text.slice(0, 300));
                  throw new Error('Invalid JSON response from QR endpoint.');
                }
              })
              .then(j => {
              if (j && !j.success && j.time_left_seconds) {
                const warningMessage = formatCooldownMessage(j);
                showToast(warningMessage, 'warning');
                if (j.employee_info) {
                  const action = j.action || 'TIME_OUT';
                  const scanTime = formatDisplayDateTime(new Date());
                  renderEmployeeDetails(j.employee_info, warningMessage, scanTime, action);
                }
                return;
              }

              if (j && !j.success) {
                const failureMessage = j.message || 'Attendance request failed.';
                showToast(failureMessage, 'warning');
                if (j.employee_info) {
                  const action = j.action || 'TIME_IN';
                  const scanTime = formatDisplayDateTime(new Date());
                  renderEmployeeDetails(j.employee_info, failureMessage, scanTime, action);
                }
                return;
              }

              if (j && j.employee_info) {
                const action = j.action || 'TIME_IN';
                const scanMessage = j.message || 'QR scanned successfully.';
                const scanTime = formatDisplayDateTime(new Date());
                const entry = {
                  employee_name: j.employee_info.full_name || 'Employee',
                  employee_id: j.employee_info.employee_id || null,
                  department: j.employee_info.department || null,
                  action: action,
                  timestamp: new Date().toISOString()
                };

                pushScanHistory(entry);
                renderEmployeeDetails(j.employee_info, scanMessage, scanTime, action);
              }
            }).catch(error => {
              console.error('processStaticQR fetch error:', error);
              showToast(error.message || 'QR scan failed. Please try again.', 'error');
            }).finally(() => {
              scanInProgress = false;
              setTimeout(() => {
                if (!html5QrCode) {
                  startCamera();
                }
              }, 1000);
            });
          },
          errorMessage => {
            console.debug('QR scan error:', errorMessage);
          }
        );

        showToast('Camera is ready. Point it at the QR code.', 'info');
        startBtn.disabled = true;
        stopBtn.disabled = false;
      } catch(e){
        console.error('Camera start failed:', e);
        showToast('Camera failed to start. Please allow camera permission and try again.', 'warning');
        html5QrCode = null;
        scanInProgress = false;
      }
    }

    async function stopCamera(){
      if (!html5QrCode) return;
      try { await html5QrCode.stop(); } catch(e){ console.warn(e); }
      try { html5QrCode.clear(); } catch(e){}
      html5QrCode = null;
      saveScanState(false);
      renderIdleState();
      startBtn.disabled = false;
      stopBtn.disabled = true;
    }

    window.__taQrScannerStop = stopCamera;
    if (typeof window.registerPageCleanup === 'function') {
      window.registerPageCleanup(function () {
        if (typeof window.__taQrScannerStop === 'function') {
          window.__taQrScannerStop().catch(function () {});
        }
      });
    }

    startBtn.addEventListener('click', function(){ startCamera(); });
    stopBtn.addEventListener('click', function(){ stopCamera(); });

    const storedState = loadScanState();
    scanHistory = Array.isArray(storedState.scans) ? storedState.scans : [];
    renderIdleState();

    // Back button removed in kiosk mode; no-op if element missing
    const backBtnEl = document.getElementById('backBtn');
    if (backBtnEl) {
      backBtnEl.addEventListener('click', function(){ window.history.back(); });
    }
    
    // Auto-start camera when scanner page loads (if allowed by browser/permissions)
    (async function tryAutoStart() {
      try {
        const storedState = loadScanState();
        if (storedState && storedState.active !== false) {
          await startCamera();
        }
      } catch (err) {
        console.warn('Auto-start camera failed:', err);
      }
    })();
  });

window.addEventListener('pagehide', function () {
  if (typeof window.__taQrScannerStop === 'function') {
    window.__taQrScannerStop().catch(function () {});
  }
});
window.addEventListener('beforeunload', function () {
  if (typeof window.__taQrScannerStop === 'function') {
    window.__taQrScannerStop().catch(function () {});
  }
});

// Reinitialize on each visit to the scanner page (the page DOM is replaced by AJAX navigation).
function initQRScannerPage() {
  console.log('[TA INIT] QR Scanner initialized');
  try {
    if (typeof window.__taQrScannerStop === 'function') {
      window.__taQrScannerStop().catch(function () {});
    }
  } catch (err) {
    console.error('initQRScannerPage error', err);
  }
}
