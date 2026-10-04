function openLeaveRequestModal() {
    $('#leaveRequestModal').modal('show');
}

document.getElementById('leaveRequestForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const messageDiv = document.getElementById('leaveMessage');
    const submitBtn = document.getElementById('submitLeaveBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    fetch('../app/api/submit_leave.php', {
        method: 'POST',
        body: new URLSearchParams(new FormData(this))
    }).then(response => response.json()).then(data => {
        messageDiv.style.display = 'block';
        messageDiv.classList.toggle('alert-success', !!data.success);
        messageDiv.classList.toggle('alert-danger', !data.success);
        messageDiv.innerHTML = (data.success ? '<i class="fas fa-check-circle"></i> ' : '<i class="fas fa-exclamation-circle"></i> ') + data.message;
        if (data.success) {
            setTimeout(() => { $('#leaveRequestModal').modal('hide'); setTimeout(() => location.reload(), 500); }, 2000);
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
        }
    }).catch(error => {
        messageDiv.style.display = 'block';
        messageDiv.className = 'alert alert-danger';
        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error: ' + error.message;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
    });
});

document.getElementById('startDate')?.addEventListener('change', function () {
    const endDate = document.getElementById('endDate');
    if (endDate.value && this.value > endDate.value) endDate.value = this.value;
});