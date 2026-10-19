document.addEventListener('DOMContentLoaded', function () {
    const syncBtn = document.getElementById('syncHolidaysBtn');
    if (syncBtn) syncBtn.addEventListener('click', function () { syncHolidays(syncBtn); });
});
function syncHolidays(btn) {
    if (btn.classList.contains('syncing')) return;
    btn.classList.add('syncing'); btn.disabled = true;
    fetch('app/api/holiday_api.php?action=sync', { method: 'POST', headers: { 'Content-Type': 'application/json' } })
        .then(response => response.json()).then(data => {
            btn.classList.remove('syncing'); btn.disabled = false;
            if (data.success) { showToast('Holidays synced successfully!', 'success'); setTimeout(() => location.reload(), 1000); }
            else showToast('Sync failed: ' + data.message, 'error');
        }).catch(error => { btn.classList.remove('syncing'); btn.disabled = false; showToast('Error: ' + error.message, 'error'); });
}
function showToast(message, type) {
    if (typeof Toast !== 'undefined') {
        Toast.show(message, type);
        return;
    }

    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.position = 'fixed';
    toast.style.right = '24px';
    toast.style.bottom = '24px';
    toast.style.zIndex = '200000';
    toast.style.pointerEvents = 'none';
    toast.style.padding = '12px 16px';
    toast.style.borderRadius = '8px';
    toast.style.background = type === 'error' ? '#c0392b' : '#218739';
    toast.style.color = '#fff';
    toast.style.fontWeight = '600';
    toast.style.boxShadow = '0 8px 24px rgba(0,0,0,.2)';
    document.body.appendChild(toast);
    setTimeout(function () {
        toast.style.opacity = '0';
        setTimeout(function () { toast.remove(); }, 250);
    }, 3200);
}