<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/base/enhancements.css">
    <link rel="stylesheet" href="css/components/dropzone.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="js/components/dropzone.js" defer></script>
    <title>Learning Management</title>
</head>
<body>
    <!-- Global toast notification system with stacking -->
    <script>
    (function() {
        var toastContainer = null;
        var toastCount = 0;
        function ensureContainer() {
            if (toastContainer) return;
            toastContainer = document.createElement('div');
            toastContainer.style.cssText = 'position:fixed; bottom:2rem; left:50%; transform:translateX(-50%); z-index:100000; display:flex; flex-direction:column-reverse; gap:0.5rem; align-items:center; pointer-events:none;';
            document.body.appendChild(toastContainer);
        }
        window.showToast = function(message, type) {
            ensureContainer();
            var bg = type === 'success' ? 'rgba(16,185,129,0.95)' : type === 'error' ? 'rgba(239,68,68,0.95)' : 'rgba(32,0,130,0.9)';
            var icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
            var id = 'toast-' + (++toastCount);
            var toast = document.createElement('div');
            toast.id = id;
            toast.style.cssText = 'padding:0.75rem 1.25rem; border-radius:10px; background:' + bg + '; color:#fff; font-size:0.85rem; font-weight:600; box-shadow:0 6px 24px rgba(0,0,0,0.2); display:flex; align-items:center; gap:0.5rem; opacity:0; transform:translateY(10px); transition:all 0.25s ease; pointer-events:auto; white-space:nowrap;';
            toast.innerHTML = '<i class="fas ' + icon + '"></i> ' + message;
            toastContainer.appendChild(toast);
            requestAnimationFrame(function() {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(function() { toast.remove(); }, 250);
            }, 3000);
        };
    })();
    </script>
    <header>
        <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
        </div>
        <div class="realtime" id="realtimeClock" aria-live="polite">--:-- </div>
    </header>
