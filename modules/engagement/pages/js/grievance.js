(function () {
  const grievanceTabIds = ['all-grievances', 'management', 'reports'];
  const GRIEVANCE_STORAGE_KEY = 'engagement:grievance:active-tab';
  const GRIEVANCE_LEGACY_STORAGE_KEY = 'grievance-active-tab';

  function getSavedGrievanceTab() {
    const candidateKeys = [GRIEVANCE_STORAGE_KEY, GRIEVANCE_LEGACY_STORAGE_KEY];
    const validTabIds = ['all-grievances', 'management', 'reports'];

    // Priority 1: Check URL hash first
    const urlHash = window.location.hash.replace('#', '');
    if (urlHash && validTabIds.includes(urlHash)) {
      console.log('[Grievance Tab] Using URL hash:', urlHash);
      return urlHash;
    }

    // Priority 2: Check sessionStorage
    for (const key of candidateKeys) {
      try {
        const savedTab = sessionStorage.getItem(key);
        if (savedTab && validTabIds.includes(savedTab)) {
          console.log('[Grievance Tab] Using sessionStorage:', savedTab);
          return savedTab;
        }
      } catch (error) {
        // Ignore storage access issues
      }
    }

    // Priority 3: Check localStorage
    for (const key of candidateKeys) {
      try {
        const savedTab = localStorage.getItem(key);
        if (savedTab && validTabIds.includes(savedTab)) {
          console.log('[Grievance Tab] Using localStorage:', savedTab);
          return savedTab;
        }
      } catch (error) {
        // Ignore storage access issues
      }
    }

    console.log('[Grievance Tab] No saved tab found, using default');
    return null;
  }

  function persistGrievanceTab(tabId) {
    const validTabId = String(tabId || '').replace('#', '').trim();
    if (!grievanceTabIds.includes(validTabId)) return;

    try {
      sessionStorage.setItem(GRIEVANCE_STORAGE_KEY, validTabId);
      sessionStorage.setItem(GRIEVANCE_LEGACY_STORAGE_KEY, validTabId);
      localStorage.setItem(GRIEVANCE_STORAGE_KEY, validTabId);
      localStorage.setItem(GRIEVANCE_LEGACY_STORAGE_KEY, validTabId);
      console.log('[Grievance Tab] Persisted:', validTabId);
      
      // Also update URL hash
      const currentHash = window.location.hash.replace('#', '');
      if (currentHash !== validTabId) {
        window.history.replaceState({}, '', window.location.pathname + window.location.search + '#' + validTabId);
        console.log('[Grievance Tab] Updated URL hash to:', validTabId);
      }
    } catch (error) {
      console.warn('Unable to save active grievance tab.', error);
    }
  }

  function setActiveGrievanceTab(tabId, updateHash = true) {
    const validTabId = String(tabId || '').replace('#', '').trim();
    if (!grievanceTabIds.includes(validTabId)) return;

    const tabLink = document.querySelector('#grievance-tabs a[data-grievance-tab="' + validTabId + '"]');
    const tabPane = document.getElementById(validTabId);
    if (!tabLink || !tabPane) return;

    document.querySelectorAll('#grievance-tabs a[data-grievance-tab]').forEach(function (link) {
      const isActive = link === tabLink;
      link.classList.toggle('active', isActive);
      link.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    document.querySelectorAll('#grievance-tabs-content > .tab-pane').forEach(function (pane) {
      const isActive = pane === tabPane;
      pane.hidden = !isActive;
      pane.setAttribute('aria-hidden', isActive ? 'false' : 'true');
      pane.classList.toggle('show', isActive);
      pane.classList.toggle('active', isActive);
      pane.style.display = isActive ? 'block' : 'none';
      pane.style.visibility = isActive ? 'visible' : 'hidden';
      pane.style.opacity = isActive ? '1' : '0';
    });

    persistGrievanceTab(validTabId);

    if (updateHash) {
      window.history.replaceState({}, '', window.location.pathname + window.location.search + '#' + validTabId);
    }

    if (validTabId === 'reports') {
      resetAutomaticReportFilters();
      initReportEmployeeAutofill();
      setTimeout(function () { generateCustomReport(false); }, 0);
    }
  }

  function initializeGrievanceTabs() {
    if (!document.getElementById('grievance-tabs')) {
      console.warn('[Grievance Tab] Tab container not found');
      return;
    }
    
    const reportContainer = document.getElementById('grievance-tabs-content');
    if (reportContainer && reportContainer.dataset.reportData) {
      try {
        window.reportData = JSON.parse(reportContainer.dataset.reportData);
      } catch (error) {
        window.reportData = [];
        console.error('Unable to load grievance report data', error);
      }
    }

    // Retry logic for DOM readiness
    let retryCount = 0;
    const maxRetries = 5;

    function attemptInitialize() {
      const storedTab = getSavedGrievanceTab();
      const requestedTab = window.location.hash.replace('#', '').trim();
      const activeTab = grievanceTabIds.includes(requestedTab)
        ? requestedTab
        : (storedTab && grievanceTabIds.includes(storedTab) ? storedTab : 'all-grievances');
      
      const tabLink = document.querySelector('#grievance-tabs a[data-grievance-tab="' + activeTab + '"]');
      const tabPane = document.getElementById(activeTab);
      
      if (tabLink && tabPane) {
        console.log('[Grievance Tab] DOM ready, setting active tab:', activeTab);
        setActiveGrievanceTab(activeTab, false);
      } else if (retryCount < maxRetries) {
        retryCount++;
        console.log('[Grievance Tab] DOM not ready, retrying... (' + retryCount + '/' + maxRetries + ')');
        setTimeout(attemptInitialize, 100);
      } else {
        console.warn('[Grievance Tab] Failed to find tabs after', maxRetries, 'retries');
        setActiveGrievanceTab('all-grievances', false);
      }
    }

    attemptInitialize();
  }

  function resetAutomaticReportFilters() {
    ['report-start-date', 'report-end-date', 'report-department', 'report-category', 'report-status', 'report-employee'].forEach(function (id) {
      const field = document.getElementById(id);
      if (field) field.value = '';
    });
  }

  window.showGrievanceTab = setActiveGrievanceTab;

  if (!window.__grievanceTabClickBound) {
    window.__grievanceTabClickBound = true;
    document.addEventListener('click', function (event) {
      const tabLink = event.target.closest('#grievance-tabs a[data-grievance-tab]');
      if (!tabLink) return;
      event.preventDefault();
      setActiveGrievanceTab(tabLink.dataset.grievanceTab);
    });
  }

  function initGrievanceFilters() {
    if (!document.getElementById('all-grievances-table')) {
      return;
    }

    if (!window.__grievanceFiltersBound) {
      window.__grievanceFiltersBound = true;
      document.addEventListener('change', function (event) {
        if (event.target.matches('#status-filter, #category-filter, #department-filter, #date-filter')) {
          filterGrievances();
        }
      });
      document.addEventListener('input', function (event) {
        if (event.target.matches('#search-filter')) filterGrievances();
      });
    }
    filterGrievances();
  }

  window.addEventListener('page:loaded', function (event) {
    if (event && event.detail && event.detail.page === 'grievances') {
      setTimeout(function () {
        initializeGrievanceTabs();
        initGrievanceFilters();
      }, 0);
    }
  });

  if (document.readyState !== 'loading') {
    initializeGrievanceTabs();
    initGrievanceFilters();
  } else {
    document.addEventListener('DOMContentLoaded', function () {
      initializeGrievanceTabs();
      initGrievanceFilters();
    }, { once: true });
  }

  window.addEventListener('load', function () {
    initializeGrievanceTabs();
  }, { once: true });

  // Initialize charts when analytics tab is shown
  if (typeof window.jQuery === 'function') {
    window.jQuery('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
      if (e.target.id === 'analytics-tab') {
        initializeCharts();
      }
    });
  }
})();

function initializeCharts() {
  const grievances = window.grievancesData || [];
  window.reportData = grievances;

  const statusCounts = { Pending: 0, 'Under Review': 0, Resolved: 0, Closed: 0, Escalated: 0 };
  grievances.forEach(g => {
    const status = (g.status || 'Pending').toString();
    if (statusCounts.hasOwnProperty(status)) {
      statusCounts[status]++;
    } else if (status.toLowerCase() === 'submitted' || status.toLowerCase() === 'pending') {
      statusCounts['Pending']++;
    } else if (status.toLowerCase() === 'investigation' || status.toLowerCase() === 'investigating') {
      statusCounts['Under Review']++;
    }
  });

  const statusCtx = document.getElementById('statusChart').getContext('2d');
  new Chart(statusCtx, {
    type: 'pie',
    data: {
      labels: ['Pending', 'Under Review', 'Resolved', 'Closed', 'Escalated'],
      datasets: [{
        data: [statusCounts['Pending'], statusCounts['Under Review'], statusCounts['Resolved'], statusCounts['Closed'], statusCounts['Escalated']],
        backgroundColor: ['#f39c12', '#3498db', '#27ae60', '#95a5a6', '#e74c3c']
      }]
    }
  });

  const categoryCounts = {};
  grievances.forEach(g => {
    const category = g.category || 'Other';
    categoryCounts[category] = (categoryCounts[category] || 0) + 1;
  });
  const categoryLabels = Object.keys(categoryCounts);
  const categoryValues = categoryLabels.map(label => categoryCounts[label]);

  const categoryCtx = document.getElementById('categoryChart').getContext('2d');
  new Chart(categoryCtx, {
    type: 'bar',
    data: {
      labels: categoryLabels.length ? categoryLabels : ['No Data'],
      datasets: [{
        label: 'Grievances by Category',
        data: categoryValues.length ? categoryValues : [0],
        backgroundColor: '#3498db'
      }]
    }
  });

  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const monthCounts = {};
  grievances.forEach(g => {
    const date = g.created_at ? new Date(g.created_at) : null;
    if (date instanceof Date && !isNaN(date)) {
      monthCounts[monthNames[date.getMonth()]] = (monthCounts[monthNames[date.getMonth()]] || 0) + 1;
    }
  });
  const trendValues = monthNames.map(name => monthCounts[name] || 0);

  const trendCtx = document.getElementById('trendChart').getContext('2d');
  new Chart(trendCtx, {
    type: 'line',
    data: {
      labels: monthNames,
      datasets: [{
        label: 'Grievances Filed',
        data: trendValues,
        borderColor: '#e74c3c',
        backgroundColor: 'rgba(231, 76, 60, 0.2)',
        fill: true
      }]
    }
  });

  const departmentCounts = {};
  grievances.forEach(g => {
    const department = g.department || 'Unassigned';
    departmentCounts[department] = (departmentCounts[department] || 0) + 1;
  });
  const deptLabels = Object.keys(departmentCounts);
  const deptValues = deptLabels.map(label => departmentCounts[label]);

  const departmentCtx = document.getElementById('departmentChart').getContext('2d');
  new Chart(departmentCtx, {
    type: 'bar',
    data: {
      labels: deptLabels.length ? deptLabels : ['No Data'],
      datasets: [{
        label: 'Grievances by Department',
        data: deptValues.length ? deptValues : [0],
        backgroundColor: '#6f42c1'
      }]
    }
  });

  const resolvedItems = grievances.filter(g => g.status === 'Resolved' || g.status === 'Closed' || g.resolved_at);
  let avgResolutionDays = 0;
  if (resolvedItems.length) {
    const totalDays = resolvedItems.reduce((sum, item) => {
      if (!item.created_at || !item.resolved_at) {
        return sum;
      }
      const created = new Date(item.created_at);
      const resolved = new Date(item.resolved_at);
      return sum + Math.max(0, Math.round((resolved - created) / (1000 * 60 * 60 * 24)));
    }, 0);
    avgResolutionDays = Math.round(totalDays / resolvedItems.length);
  }

  const resolutionCtx = document.getElementById('resolutionChart').getContext('2d');
  new Chart(resolutionCtx, {
    type: 'bar',
    data: {
      labels: ['Avg. Days to Resolution'],
      datasets: [{
        label: 'Resolution Time',
        data: [avgResolutionDays],
        backgroundColor: '#20c997'
      }]
    }
  });
}


function isFinalizedStatus(status) {
  return ['resolved', 'closed'].includes((String(status) || '').toLowerCase().trim());
}

function updateManagementFormState() {
  const selectedOption = $('#management-grievance-select option:selected');
  const status = String($('#management-status-select').val() || selectedOption.data('status') || '').toLowerCase().trim();
  const escalationLevel = selectedOption.data('escalation-level') || '';
  const escalationReason = selectedOption.data('escalation-reason') || '';
  const finalized = isFinalizedStatus(status);
  const form = $('#management-update-form');
  const inputs = form.find('select[name="status"], textarea[name="hr_remarks"], textarea[name="final_resolution"], textarea[name="escalation_reason"], input[name="supporting_document"], input[name="confidential"], input[name="escalation_level"], button[type="submit"]');
  const escalationFields = $('#escalation-fields');
  const escalationLevelInput = $('#management-escalation-level');
  const escalationReasonInput = $('#management-escalation-reason');
  const alertBox = $('#management-form-alert');

  if (status === 'escalated') {
    escalationFields.removeClass('d-none');
    escalationLevelInput.prop('disabled', false).val(escalationLevel);
    escalationReasonInput.prop('disabled', false).val(escalationReason);
  } else {
    escalationFields.addClass('d-none');
    escalationLevelInput.prop('disabled', true).val('');
    escalationReasonInput.prop('disabled', true).val('');
  }

  if (finalized) {
    inputs.prop('disabled', true);
    $('#management-grievance-select').prop('disabled', false);
    $('#management-grievance-id').prop('disabled', false);
    alertBox.removeClass('alert-success alert-danger alert-info d-none').addClass('alert-warning').html('<i class="fas fa-lock"></i> This grievance is resolved or closed and cannot be edited.');
  } else if (selectedOption.val()) {
    inputs.prop('disabled', false);
    alertBox.addClass('d-none').removeClass('alert-success alert-danger alert-warning alert-info').html('');
  } else {
    inputs.prop('disabled', false);
    alertBox.addClass('d-none').removeClass('alert-success alert-danger alert-warning alert-info').html('');
  }
}

window.updateManagementFormState = updateManagementFormState;

if (!window.__grievanceManagementStatusBound) {
  window.__grievanceManagementStatusBound = true;
  document.addEventListener('change', function (event) {
    if (event.target.matches('#management-grievance-select')) {
      const selectedOption = event.target.options[event.target.selectedIndex];
      const status = selectedOption ? selectedOption.getAttribute('data-status') || 'Pending' : 'Pending';
      $('#management-grievance-id').val(event.target.value || '');
      $('#management-status-select').val(status.replace(/\b\w/g, function (letter) { return letter.toUpperCase(); }));
      $('#management-compliance-record').val(selectedOption ? selectedOption.getAttribute('data-compliance-record-id') || '' : '');
      updateManagementFormState();
    }
    if (event.target.matches('#management-status-select')) {
      updateManagementFormState();
    }
    if (event.target.matches('#status') && event.target.closest('#management-form')) {
      const isEscalated = event.target.value.toLowerCase().trim() === 'escalated';
      const escalationFields = document.getElementById('standalone-escalation-fields');
      const escalationInputs = document.querySelectorAll('#standalone-escalation-fields input, #standalone-escalation-fields textarea');
      if (escalationFields) escalationFields.classList.toggle('d-none', !isEscalated);
      escalationInputs.forEach(function (input) {
        input.disabled = !isEscalated;
      });
    }
  });
}

function updateGrievanceRow(grievanceId, status) {
  const row = $('#all-grievances-table tbody .grievance-row[data-id="' + grievanceId + '"]');

  const badgeClass = (status || '').toLowerCase() === 'resolved' ? 'success'
    : (status || '').toLowerCase() === 'closed' ? 'secondary'
    : (status || '').toLowerCase() === 'under review' ? 'info'
    : (status || '').toLowerCase() === 'escalated' ? 'danger'
    : 'warning';

  row.attr('data-status', (status || '').toLowerCase());
  row.find('td:nth-child(5)').html('<span class="badge badge-' + badgeClass + '">' + htmlspecialchars(status || 'Pending') + '</span>');

  (window.originalGrievanceRows || []).forEach(function (originalRow) {
    if (originalRow.getAttribute('data-id') === String(grievanceId)) {
      originalRow.setAttribute('data-status', (status || '').toLowerCase());
      originalRow.querySelector('td:nth-child(5)').innerHTML = '<span class="badge badge-' + badgeClass + '">' + htmlspecialchars(status || 'Pending') + '</span>';
    }
  });

  if ($('.grievance-row').length) {
    filterGrievances();
  }
}

function htmlspecialchars(str) {
  if (typeof str !== 'string') {
    return '';
  }
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function populatePayslips() {
  const employeeId = String($('#grievance-employee-select').val() || '');
  const payslipSelect = $('#grievance-payslip-select');
  const previousPayslipId = payslipSelect.val();
  const summaryBox = $('#grievance-payslip-summary');
  const hiddenField = $('#grievance-payslip-information');
  const payslips = employeeId && window.grievancePayslipsData ? (window.grievancePayslipsData[employeeId] || []) : [];

  payslipSelect.empty().append('<option value="">Select payslip (optional)</option>');
  payslipSelect.prop('disabled', true);
  hiddenField.val('');
  summaryBox.hide().html('');

  if (!employeeId) {
    summaryBox.show().html('<small class="text-muted">Select an employee to view available payslips.</small>');
    return;
  }

  if (!payslips.length) {
    summaryBox.show().html('<small class="text-muted">No payslips were found for this employee.</small>');
    return;
  }

  payslips.forEach(function(payslip) {
    const generatedDate = payslip.generated_at ? payslip.generated_at.split(' ')[0] : 'N/A';
    payslipSelect.append(
      $('<option></option>').val(payslip.id).text('Payslip #' + payslip.id + ' - ' + generatedDate + ' | Gross ' + formatCurrency(payslip.gross_pay) + ' | Net ' + formatCurrency(payslip.net_pay))
    );
  });
  payslipSelect.prop('disabled', false);

  if (previousPayslipId && payslipSelect.find('option[value="' + previousPayslipId + '"]').length) {
    payslipSelect.val(previousPayslipId);
  }

  updatePayslipSummary();
}

function updatePayslipSummary() {
  const payslipSelect = $('#grievance-payslip-select');
  const summaryBox = $('#grievance-payslip-summary');
  const hiddenField = $('#grievance-payslip-information');
  const selectedId = payslipSelect.val();
  const employeeId = String($('#grievance-employee-select').val() || '');
  const payslips = employeeId && window.grievancePayslipsData ? (window.grievancePayslipsData[employeeId] || []) : [];
  const selectedPayslip = payslips.find(function(item) { return String(item.id) === String(selectedId); });

  if (!selectedPayslip) {
    summaryBox.hide().html('');
    hiddenField.val('');
    return;
  }

  const info = 'Payslip ' + selectedPayslip.id + ': gross=' + formatCurrency(selectedPayslip.gross_pay) + ', deductions=' + formatCurrency(selectedPayslip.total_deductions) + ', net=' + formatCurrency(selectedPayslip.net_pay);
  hiddenField.val(info);
  summaryBox.show().html('<strong>Payslip Selected</strong><br>' + info);
}

function formatCurrency(value) {
  const amount = Number(value || 0);
  return '₱' + amount.toFixed(2);
}

function filterGrievances() {
  function normalize(val) {
    return String(val || '').toLowerCase().trim().replace(/[^a-z0-9 ]/g, '');
  }

  function compact(val) {
    return normalize(val).replace(/\s+/g, '');
  }

  function canonicalStatus(val) {
    const status = compact(val);
    if (status === 'submitted') {
      return 'pending';
    }
    if (status === 'investigation' || status === 'investigating') {
      return 'under review';
    }
    if (status === 'underreview') {
      return 'under review';
    }
    return status;
  }

  const valueOf = function (selector) {
    const element = document.querySelector(selector);
    return element ? element.value : '';
  };
  const statusFilter = canonicalStatus(valueOf('#status-filter'));
  const categoryFilter = normalize(valueOf('#category-filter'));
  const departmentFilter = normalize(valueOf('#department-filter'));
  const dateFilter = String(valueOf('#date-filter') || '').trim();
  const searchFilter = normalize(valueOf('#search-filter'));
  const sortFilter = String(valueOf('#sort-filter') || 'date_desc');
  const table = document.getElementById('all-grievances-table');
  const tbody = table ? table.querySelector('tbody') : null;
  if (!tbody) return;
  const rows = [];

  const currentRows = Array.from(tbody.querySelectorAll('.grievance-row'));
  const sourceRows = Array.from(new Set([...(window.originalGrievanceRows || []), ...currentRows]));
  sourceRows.forEach(function (row) {
    const status = canonicalStatus(String(row.getAttribute('data-status') || ''));
    const category = normalize(String(row.getAttribute('data-category') || ''));
    const date = String(row.getAttribute('data-date') || '').trim();
    const search = normalize(String(row.getAttribute('data-search') || ''));

    const statusMatch = !statusFilter || status === statusFilter;
    const categoryMatch = !categoryFilter || category.includes(categoryFilter) || categoryFilter.includes(category);
    const department = normalize(String(row.getAttribute('data-department') || ''));
    const departmentMatch = !departmentFilter || department.includes(departmentFilter) || departmentFilter.includes(department);
    const dateMatch = !dateFilter || date === dateFilter;
    const searchMatch = !searchFilter || search.includes(searchFilter);

    const matches = statusMatch && categoryMatch && departmentMatch && dateMatch && searchMatch;
    row.style.display = matches ? '' : 'none';
    if (matches) {
      rows.push(row);
    }
  });

  rows.sort(function(a, b) {
    const aDate = String(a.getAttribute('data-date') || '').trim();
    const bDate = String(b.getAttribute('data-date') || '').trim();
    const aSearch = String(a.getAttribute('data-search') || '').trim().toLowerCase();
    const bSearch = String(b.getAttribute('data-search') || '').trim().toLowerCase();

    if (sortFilter === 'subject_asc') {
      return aSearch.localeCompare(bSearch);
    }
    if (sortFilter === 'subject_desc') {
      return bSearch.localeCompare(aSearch);
    }
    if (sortFilter === 'date_asc') {
      return aDate.localeCompare(bDate);
    }
    return bDate.localeCompare(aDate);
  });

  const noResultsRow = tbody.querySelector('.no-results-row');
  if (noResultsRow) noResultsRow.remove();
  rows.forEach(function(row) {
    tbody.appendChild(row);
  });

  if (!rows.length) {
    tbody.insertAdjacentHTML('beforeend', '<tr class="no-results-row"><td colspan="10" class="text-center text-muted">No grievance records match the current filters.</td></tr>');
  }
}

window.filterGrievances = filterGrievances;

function closeGlobalModal() {
  const modal = document.getElementById('global-grievance-modal');
  if (!modal) return;
  modal.remove();
  document.body.classList.remove('modal-open');
}

function openGlobalModal(title, url) {
  closeGlobalModal();

  const modal = document.createElement('div');
  modal.id = 'global-grievance-modal';
  modal.className = 'global-grievance-modal grievance-area';
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
  modal.innerHTML = '<div class="global-grievance-modal-dialog">'
    + '<div class="global-grievance-modal-header"><h2>' + htmlspecialchars(title) + '</h2>'
    + '<button type="button" class="global-grievance-modal-close" aria-label="Close">&times;</button></div>'
    + '<div class="global-grievance-modal-body"><div class="text-muted text-center p-4">Loading details...</div></div>'
    + '</div>';

  const detailUrl = new URL(url, window.location.href);
  const styleLink = document.createElement('link');
  styleLink.rel = 'stylesheet';
  styleLink.href = new URL('css/style/grievance.css', detailUrl).href;
  modal.appendChild(styleLink);
  document.body.appendChild(modal);
  document.body.classList.add('modal-open');

  const closeButton = modal.querySelector('.global-grievance-modal-close');
  closeButton.addEventListener('click', closeGlobalModal);
  modal.addEventListener('click', function (event) {
    if (event.target === modal) closeGlobalModal();
  });

  fetch(url, { credentials: 'same-origin' })
    .then(function (response) {
      if (!response.ok) throw new Error('Unable to load grievance details.');
      return response.text();
    })
    .then(function (html) {
      const parsed = new DOMParser().parseFromString(html, 'text/html');
      const content = parsed.querySelector('.grievance-detail-content');
      if (content) {
        content.querySelectorAll('script, link[rel="stylesheet"]').forEach(function (element) {
          element.remove();
        });
      }
      modal.querySelector('.global-grievance-modal-body').innerHTML = content
        ? content.outerHTML
        : '<div class="alert alert-danger">Grievance details not found.</div>';
    })
    .catch(function (error) {
      modal.querySelector('.global-grievance-modal-body').innerHTML = '<div class="alert alert-danger">' + htmlspecialchars(error.message) + '</div>';
    });
}

document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape') closeGlobalModal();
});

function viewGrievanceDetails(id) {
  const engagementRoot = window.location.pathname.split('/modules/engagement/')[0]
    + '/modules/engagement/';
  const detailUrl = new URL('pages/grievance_detail.php', window.location.origin + engagementRoot);
  detailUrl.searchParams.set('page', 'grievance_detail');
  detailUrl.searchParams.set('id', String(id));

  openGlobalModal('Grievance Details', detailUrl.href);
}

window.viewGrievanceDetails = viewGrievanceDetails;

if (!window.__grievanceViewBound) {
  window.__grievanceViewBound = true;
  document.addEventListener('click', function (event) {
    const viewButton = event.target.closest('[data-grievance-view]');
    const detailLink = event.target.closest('a[href*="grievance_detail.php"]');
    if (!viewButton && !detailLink) return;

    if (detailLink) event.preventDefault();
    const grievanceId = viewButton
      ? Number(viewButton.getAttribute('data-grievance-view'))
      : Number(new URL(detailLink.href, window.location.href).searchParams.get('id'));
    if (grievanceId > 0) viewGrievanceDetails(grievanceId);
  });
}

function manageGrievance(id) {
  openGlobalModal('Manage Grievance', 'grievance_manage.php?id=' + id, refreshGrievances);
}

function generateCustomReport(downloadFile = true) {
  const type = document.getElementById('report-type').value;
  const startDate = document.getElementById('report-start-date').value;
  const endDate = document.getElementById('report-end-date').value;
  const department = document.getElementById('report-department').value;
  const category = document.getElementById('report-category').value;
  const status = document.getElementById('report-status').value;
  const employee = document.getElementById('report-employee').value.toLowerCase();
  const format = document.getElementById('report-format').value;
  const outputCard = document.getElementById('generated-report');
  const summary = document.getElementById('generated-report-summary');
  const table = document.getElementById('generated-report-table');
  const data = window.reportData || [];
  const normalize = value => String(value || '').trim().toLowerCase();

  let filtered = data.slice();
  if (startDate) {
    filtered = filtered.filter(item => item.created_at && String(item.created_at).slice(0, 10) >= startDate);
  }
  if (endDate) {
    filtered = filtered.filter(item => item.created_at && String(item.created_at).slice(0, 10) <= endDate);
  }
  if (department) {
    filtered = filtered.filter(item => normalize(item.department) === normalize(department));
  }
  if (category) {
    filtered = filtered.filter(item => normalize(item.category) === normalize(category));
  }
  if (status) {
    filtered = filtered.filter(item => normalize(item.status) === normalize(status));
  }
  if (employee) {
    filtered = filtered.filter(item => normalize((item.employee_name || '') + ' ' + (item.subject || '')).includes(normalize(employee)));
  }

  const reportSummary = function (formatLabel) {
    return `<strong>Matching Records:</strong> ${filtered.length} <span class="text-muted">of ${data.length} available</span><br /><strong>Format:</strong> ${formatLabel}`;
  };

  let headers = [];
  let rows = [];
  let reportTitle = '';

  switch (type) {
    case 'summary':
      reportTitle = 'Grievance Summary Report';
      const statusCounts = {};
      filtered.forEach(item => {
        const key = item.status || 'Unknown';
        statusCounts[key] = (statusCounts[key] || 0) + 1;
      });
      summary.innerHTML = reportSummary(format.toUpperCase());
      headers = ['Status', 'Count'];
      rows = Object.keys(statusCounts).map(status => [status, statusCounts[status]]);
      break;
    case 'detailed':
      reportTitle = 'Detailed Grievance Report';
      summary.innerHTML = reportSummary(format.toUpperCase());
      headers = ['Subject', 'Employee', 'Category', 'Date Submitted', 'Status'];
      rows = filtered.map(item => [item.subject || '', item.employee_name || 'Unknown', item.category || 'N/A', item.created_at ? item.created_at.split(' ')[0] : 'N/A', item.status || 'N/A']);
      break;
    case 'category':
      reportTitle = 'Category Analysis Report';
      const categoryCounts = {};
      filtered.forEach(item => {
        const categoryName = item.category || 'Uncategorized';
        categoryCounts[categoryName] = (categoryCounts[categoryName] || 0) + 1;
      });
      summary.innerHTML = `<strong>Matching Records:</strong> ${filtered.length} <span class="text-muted">of ${data.length} available</span><br /><strong>Total Categories:</strong> ${Object.keys(categoryCounts).length}<br /><strong>Format:</strong> ${format.toUpperCase()}`;
      headers = ['Category', 'Count'];
      rows = Object.keys(categoryCounts).map(cat => [cat, categoryCounts[cat]]);
      break;
    case 'resolution':
      reportTitle = 'Resolution Report';
      summary.innerHTML = `<strong>Matching Records:</strong> ${filtered.length} <span class="text-muted">of ${data.length} available</span><br /><strong>Total Resolved:</strong> ${filtered.filter(item => normalize(item.status) === 'resolved' || normalize(item.status) === 'closed').length}<br /><strong>Format:</strong> ${format.toUpperCase()}`;
      headers = ['Subject', 'Status', 'Resolution', 'Resolved At'];
      rows = filtered.filter(item => normalize(item.status) === 'resolved' || normalize(item.status) === 'closed').map(item => [item.subject || '', item.status || '', item.resolution_of_complaint || 'N/A', item.resolved_at ? item.resolved_at.split(' ')[0] : 'N/A']);
      break;
    default:
      reportTitle = 'Custom Grievance Report';
      summary.innerHTML = reportSummary(format.toUpperCase());
      headers = ['Subject', 'Status', 'Category', 'Date Submitted'];
      rows = filtered.map(item => [item.subject || '', item.status || '', item.category || 'N/A', item.created_at ? item.created_at.split(' ')[0] : 'N/A']);
      break;
  }

  const thead = table.querySelector('thead');
  const tbody = table.querySelector('tbody');
  thead.innerHTML = '<tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr>';
  tbody.innerHTML = rows.length ? rows.map(row => `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`).join('') : `<tr><td colspan="${headers.length}" class="text-center text-muted">No records match the selected filters. Clear or adjust the filters to see the ${data.length} available grievance record(s).</td></tr>`;
  outputCard.classList.remove('hidden');
  outputCard.style.display = 'block';
  outputCard.querySelector('.card-title').innerText = reportTitle;
  if (downloadFile) {
    downloadReport(reportTitle, headers, rows, format);
  }
}

window.generateCustomReport = generateCustomReport;

function formatDateForInput(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return year + '-' + month + '-' + day;
}

function initReportEmployeeAutofill() {
  const employeeField = document.getElementById('report-employee');
  if (!employeeField || employeeField.dataset.autofillBound === '1') return;
  employeeField.dataset.autofillBound = '1';

  const normalize = value => String(value || '').trim().toLowerCase().replace(/\s+/g, ' ');
  const setSelectValue = function (id, value) {
    const select = document.getElementById(id);
    if (!select || !value) return false;

    const targetValue = normalize(value);
    const option = Array.from(select.options).find(function (item) {
      return normalize(item.value) === targetValue || normalize(item.textContent) === targetValue;
    });

    if (option) {
      select.value = option.value;
      return true;
    }

    return false;
  };

  const autofillEmployee = function () {
    const enteredName = normalize(employeeField.value);
    if (!enteredName) return;

    const employeeRows = (window.reportData || [])
      .filter(function (item) {
        const employeeName = normalize(item.employee_name);
        return employeeName === enteredName || employeeName.includes(enteredName);
      })
      .sort(function (left, right) {
        return String(right.created_at || '').localeCompare(String(left.created_at || ''));
      });

    const employeeRecord = employeeRows[0];
    if (!employeeRecord) return;

    setSelectValue('report-department', employeeRecord.department);
    setSelectValue('report-category', employeeRecord.category);
    setSelectValue('report-status', employeeRecord.status);

    const grievanceDate = String(employeeRecord.created_at || '').slice(0, 10);
    const startField = document.getElementById('report-start-date');
    const endField = document.getElementById('report-end-date');

    if (grievanceDate && startField && endField) {
      const start = new Date(grievanceDate + 'T12:00:00');
      const end = new Date(start);
      end.setDate(end.getDate() + 3);
      startField.value = formatDateForInput(start);
      endField.value = formatDateForInput(end);
    }

    if (typeof generateCustomReport === 'function') {
      setTimeout(function () {
        generateCustomReport(false);
      }, 0);
    }
  };

  employeeField.addEventListener('input', autofillEmployee);
  employeeField.addEventListener('change', autofillEmployee);
  setTimeout(autofillEmployee, 0);
  setTimeout(autofillEmployee, 100);
  setTimeout(autofillEmployee, 500);
}

function downloadReport(reportTitle, headers, rows, format) {
  const safeName = reportTitle.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
  const filename = safeName + (format === 'excel' ? '.csv' : '.pdf');

  if (format === 'excel') {
    downloadCsv(filename, headers, rows);
    return;
  }

  downloadPdf(filename, reportTitle, headers, rows);
}

function downloadCsv(filename, headers, rows) {
  const csvLines = [headers.map(h => `"${String(h).replace(/"/g, '""')}"`).join(',')];
  rows.forEach(row => {
    csvLines.push(row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','));
  });
  const blob = new Blob([csvLines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.setAttribute('download', filename);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(link.href);
}

function downloadPdf(filename, title, headers, rows) {
  const jsPdfConstructor = window.jspdf?.jsPDF || window.jsPDF;
  if (typeof jsPdfConstructor !== 'function') {
    console.warn('jsPDF not loaded; falling back to CSV.');
    downloadCsv(filename.replace(/\.pdf$/i, '.csv'), headers, rows);
    return;
  }

  const doc = new jsPdfConstructor();
  doc.setFontSize(14);
  doc.text(title, 14, 20);
  const startY = 30;

  if (typeof doc.autoTable === 'function') {
    doc.autoTable({
      head: [headers],
      body: rows.length ? rows : [['No records found for this report.']],
      startY,
      styles: { fontSize: 9, cellPadding: 3 },
      headStyles: { fillColor: [41, 128, 185], textColor: 255 }
    });
  } else {
    doc.setFontSize(10);
    let y = startY;
    doc.text(headers.join(' | '), 14, y);
    y += 8;
    rows.length ? rows : [['No records found for this report.']];
    (rows.length ? rows : [['No records found for this report.']]).forEach(row => {
      if (y > 270) {
        doc.addPage();
        y = 20;
      }
      doc.text(row.map(cell => String(cell)).join(' | '), 14, y);
      y += 7;
    });
  }

  doc.save(filename);
}

function refreshGrievances() {
  location.reload();
}

