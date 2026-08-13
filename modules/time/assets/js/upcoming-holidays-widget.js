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
    if (typeof Toast !== 'undefined') Toast.show(message, type); else alert(message);
}