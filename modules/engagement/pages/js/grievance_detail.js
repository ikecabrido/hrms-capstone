function manageGrievance(id) {
  if (window.parent && typeof window.parent.openGlobalModal === 'function') {
    window.parent.openGlobalModal('Manage Grievance', 'grievance_manage.php?id=' + id);
    return;
  }
  if (typeof openGlobalModal === 'function') {
    openGlobalModal('Manage Grievance', 'grievance_manage.php?id=' + id);
    return;
  }
  // Fallback: navigate to the manage page instead of opening a modal
  window.location.href = 'grievance_manage.php?id=' + id;
}

function closeGrievanceModal() {
  if (window.parent && typeof window.parent.closeGlobalModal === 'function') {
    window.parent.closeGlobalModal();
    return;
  }
  // Fallback: navigate back in history when not inside a modal
  if (window.history && typeof window.history.back === 'function') {
    window.history.back();
    return;
  }
  // Last resort: try closing the window
  if (typeof window.close === 'function') {
    window.close();
    return;
  }
  console.warn('Unable to close the grievance detail view.');
}
