document.addEventListener('DOMContentLoaded', function() {
  const submitBtn = document.querySelector('form button[type=submit]');
  if (submitBtn) {
    submitBtn.addEventListener('click', function() {
      submitBtn.textContent = 'Submitting...';
      submitBtn.disabled = true;
    });
  }
});