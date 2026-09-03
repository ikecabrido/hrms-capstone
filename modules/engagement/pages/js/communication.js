function normalizeCommunicationTab(tabId) {
  if (!tabId) return '';

  const normalized = String(tabId).trim().toLowerCase().replace(/_/g, '-');
  const validTabs = ['announcements', 'notifications', 'updates', 'messaging', 'policies'];

  if (validTabs.includes(normalized)) {
    return normalized;
  }

  if (normalized === 'hr-notification' || normalized === 'hr-notification-tab') {
    return 'notifications';
  }

  return '';
}

const COMMUNICATION_STORAGE_KEY = 'engagement:communication:active-tab';
const COMMUNICATION_LEGACY_STORAGE_KEY = 'communication-active-tab';

// Handle tab navigation based on the last selected tab, with a safe fallback
function activateTabFromHash() {
  let activeTabId = '';

  try {
    if (window.location.hash) {
      const hashTab = normalizeCommunicationTab(window.location.hash.replace('#', ''));
      if (hashTab) {
        activeTabId = hashTab;
      }
    }

    if (!activeTabId) {
      const namespacedTab = normalizeCommunicationTab(localStorage.getItem(COMMUNICATION_STORAGE_KEY));
      const legacyTab = normalizeCommunicationTab(localStorage.getItem(COMMUNICATION_LEGACY_STORAGE_KEY));
      const storedTab = namespacedTab || legacyTab;
      if (storedTab) {
        activeTabId = storedTab;
      }
    }

    if (!activeTabId) {
      const namespacedTab = normalizeCommunicationTab(sessionStorage.getItem(COMMUNICATION_STORAGE_KEY));
      const legacyTab = normalizeCommunicationTab(sessionStorage.getItem(COMMUNICATION_LEGACY_STORAGE_KEY));
      const storedTab = namespacedTab || legacyTab;
      if (storedTab) {
        activeTabId = storedTab;
      }
    }
  } catch (error) {
    console.warn('[Communication Tab] Storage access error:', error);
  }

  if (!activeTabId) {
    activeTabId = 'announcements';
  }

  console.log('[Communication Tab] Activating tab:', activeTabId);

  // Attempt to find and activate the tab with retry logic
  let retryCount = 0;
  const maxRetries = 5;
  
  function attemptActivateTab() {
    const tabLink = document.querySelector(`a[href="#${activeTabId}"]`);
    const tabPane = document.getElementById(activeTabId);

    if (tabLink && tabPane) {
      console.log('[Communication Tab] DOM ready, activating:', activeTabId);
      switchTab(tabLink, false, true);
    } else if (retryCount < maxRetries) {
      retryCount++;
      console.log('[Communication Tab] DOM not ready, retrying... (' + retryCount + '/' + maxRetries + ')');
      setTimeout(attemptActivateTab, 100);
    } else {
      console.warn('[Communication Tab] Failed to find tab after', maxRetries, 'retries, using first available');
      const firstTab = document.querySelector('.nav-link');
      if (firstTab) {
        switchTab(firstTab, false, true);
      }
    }
  }

  attemptActivateTab();
}


// Smooth tab switching function
function switchTab(tabLink, updateHistory = true, isInitialLoad = false) {
  // Get the target pane
  const paneId = tabLink.getAttribute('href').substring(1);
  const normalizedPaneId = normalizeCommunicationTab(paneId);
  const pane = document.getElementById(normalizedPaneId || paneId);
  
  if (!pane) return;
  
  const persistedTab = normalizedPaneId || paneId;
  localStorage.setItem(COMMUNICATION_STORAGE_KEY, persistedTab);
  localStorage.setItem(COMMUNICATION_LEGACY_STORAGE_KEY, persistedTab);
  sessionStorage.setItem(COMMUNICATION_STORAGE_KEY, persistedTab);
  sessionStorage.setItem(COMMUNICATION_LEGACY_STORAGE_KEY, persistedTab);
  
  // Update URL hash for better persistence
  if (!window.location.hash || window.location.hash.replace('#', '') !== persistedTab) {
    window.history.replaceState({}, '', window.location.pathname + window.location.search + '#' + persistedTab);
  }
  
  // Get all tabs and panes
  const allTabs = document.querySelectorAll('.nav-link');
  const allPanes = document.querySelectorAll('.tab-pane');
  
  if (isInitialLoad) {
    // No animation on initial load - just activate
    allTabs.forEach(tab => {
      tab.classList.remove('active');
    });
    
    allPanes.forEach(p => {
      p.classList.remove('active', 'show');
    });
    
    tabLink.classList.add('active');
    pane.classList.add('active', 'show');
    
    // Update URL hash if needed
    if (updateHistory) {
      const href = tabLink.getAttribute('href');
      if (href && href.startsWith('#')) {
        window.history.pushState(null, null, href);
      }
    }
  } else {
    // Smooth animation when user clicks
    // Fade out current pane
    const activePanes = document.querySelectorAll('.tab-pane.active');
    activePanes.forEach(p => {
      p.style.opacity = '1';
      p.style.transition = 'opacity 0.3s ease-out';
      p.style.opacity = '0';
    });
    
    // Remove active class from all tabs after fade out
    setTimeout(function() {
      allTabs.forEach(tab => {
        tab.classList.remove('active');
      });
      
      allPanes.forEach(p => {
        p.classList.remove('active', 'show');
        p.style.opacity = '1';
        p.style.transition = 'none';
      });
      
      // Add active class to the clicked tab
      tabLink.classList.add('active');
      
      // Add active class to the corresponding pane with fade in
      pane.classList.add('active', 'show');
      pane.style.opacity = '0';
      pane.style.transition = 'opacity 0.3s ease-in';
      
      // Trigger reflow to start animation
      void pane.offsetWidth;
      pane.style.opacity = '1';
      
      // Update URL hash for browser history/bookmarking
      if (updateHistory) {
        const href = tabLink.getAttribute('href');
        if (href && href.startsWith('#')) {
          window.history.pushState(null, null, href);
        }
      }
      
    }, 150);
  }
}

// Add click handlers to all tab links
function initTabClickHandlers() {
  const tabLinks = document.querySelectorAll('.nav-link');
  
  tabLinks.forEach(link => {
    // Remove any existing listeners to avoid duplicates
    const newLink = link.cloneNode(true);
    link.parentNode.replaceChild(newLink, link);
    
    newLink.addEventListener('click', function(e) {
      e.preventDefault();
      switchTab(this, true, false); // true = update history, false = user click (animate)
    });
  });
}

function escapeCommunicationHtml(value) {
  return String(value || '').replace(/[&<>'"]/g, function (character) {
    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'}[character];
  });
}

function renderCommunicationContent(content, announcementId, buttonClass) {
  const rawContent = String(content || '');
  const isLong = rawContent.length > 200;
  const preview = escapeCommunicationHtml(isLong ? rawContent.slice(0, 200) + '...' : rawContent).replace(/\n/g, '<br>');
  const readMore = isLong && announcementId
    ? '<button class="btn btn-sm btn-outline-' + buttonClass + '" onclick="viewFullAnnouncement(' + Number(announcementId) + ')"><i class="fas fa-eye"></i> Read More</button>'
    : '';
  return '<p class="card-text">' + preview + '</p>' + readMore;
}

function initCommunicationForms() {
  document.querySelectorAll('#announcements form, #updates form, #messaging form').forEach(function (form) {
    if (form.dataset.ajaxBound === '1') return;
    form.dataset.ajaxBound = '1';
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      const originalText = button ? button.innerHTML : '';
      const formData = new FormData(form);
      const formType = formData.get('form_type');
      const apiAction = formType === 'announcement' ? 'post' : (formType === 'department_update' ? 'post_department_update' : 'send_message');
      const requestData = {};
      formData.forEach(function (value, key) {
        if (key !== 'form_type') requestData[key] = value;
      });
      if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
      }

      fetch('api/communication.php?action=' + apiAction, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(requestData),
        credentials: 'same-origin'
      })
        .then(function (response) {
          return response.json().then(function (data) {
            if (!response.ok || data.error) throw new Error(data.error || data.message || 'Unable to save.');
            return data;
          });
        })
        .then(function (data) {
          const now = new Date();
          const time = now.toLocaleDateString(undefined, {month: 'short', day: '2-digit', year: 'numeric'}) + ' ' + now.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
          const priority = escapeCommunicationHtml(formData.get('priority') || 'normal');
          const priorityBadge = '<span class="badge badge-' + (priority === 'urgent' ? 'danger' : (priority === 'high' ? 'warning' : (priority === 'low' ? 'secondary' : 'info'))) + '">' + priority.charAt(0).toUpperCase() + priority.slice(1) + '</span>';
          if (formType === 'announcement') {
            const container = document.getElementById('announcements-container');
            if (container) container.insertAdjacentHTML('afterbegin', '<div class="announcement-card card mb-3"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-2"><h6 class="card-title text-primary">' + escapeCommunicationHtml(formData.get('title')) + '</h6>' + priorityBadge + '</div><p class="text-muted small"><i class="fas fa-calendar"></i> ' + time + ' | <i class="fas fa-tag"></i> ' + escapeCommunicationHtml(formData.get('category') || 'general') + '</p>' + renderCommunicationContent(formData.get('content'), data.id, 'primary') + '</div></div>');
          } else if (formType === 'department_update') {
            const container = document.getElementById('updates-container');
            if (container) container.insertAdjacentHTML('afterbegin', '<div class="dept-update-item card mb-3"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-2"><h6 class="card-title text-success">' + escapeCommunicationHtml(formData.get('title')) + '</h6>' + priorityBadge + '</div><p class="text-muted small"><i class="fas fa-calendar"></i> ' + time + ' | <i class="fas fa-building"></i> ' + escapeCommunicationHtml(formData.get('department')) + '</p>' + renderCommunicationContent(formData.get('content'), data.id, 'success') + '</div></div>');
          } else if (formType === 'message') {
            const container = document.getElementById('messages-container');
            if (container) container.insertAdjacentHTML('afterbegin', '<div class="message-bubble sent"><div class="p-2"><small class="text-muted"><i class="fas fa-clock"></i> Just now</small><p class="mb-0">' + escapeCommunicationHtml(formData.get('message')) + '</p></div></div>');
          }
          form.reset();
        })
        .catch(function (error) { window.alert(error.message); })
        .finally(function () {
          if (button) { button.disabled = false; button.innerHTML = originalText; }
        });
    });
  });
}

window.addEventListener('notifications:all-read', function () {
  document.querySelectorAll('#notifications-container .notification-item').forEach(function (item) {
    item.classList.remove('notification-unread');
    item.classList.add('notification-read');
    item.querySelectorAll('.notification-new-badge, .notification-actions form').forEach(function (element) {
      element.remove();
    });
  });
});

function initPolicyFilter() {
  const filterSelect = document.getElementById('policy-filter');
  const policyCards = document.querySelectorAll('.policy-card');
  const emptyState = document.querySelector('#policies-container .text-center.text-muted.py-4');

  if (!filterSelect || !policyCards.length) return;

  const normalizeCategory = function (value) {
    const normalized = String(value || '').trim().toLowerCase();
    if (!normalized || normalized === 'all') return '';
    return normalized.replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  };

  filterSelect.addEventListener('change', function () {
    const selected = normalizeCategory(this.value || '');
    let visibleCount = 0;

    policyCards.forEach(function (card) {
      const title = normalizeCategory(card.dataset.title || '');
      const category = normalizeCategory(card.dataset.category || '');
      const shouldShow = !selected || title === selected || category === selected;
      const isVisible = shouldShow ? 'block' : 'none';
      card.style.display = isVisible;
      if (shouldShow) visibleCount += 1;
    });

    if (emptyState) {
      emptyState.style.display = visibleCount > 0 ? 'none' : 'block';
    }
  });

  filterSelect.value = '';
  filterSelect.dispatchEvent(new Event('change'));
}

function initLcmPolicySharing() {
  const policySelect = document.getElementById('lcm-policy-select');
  const targetType = document.getElementById('lcm-target-type');
  const shareForm = policySelect ? policySelect.closest('form') : null;
  const departmentGroup = document.getElementById('lcm-department-group');
  const employeesGroup = document.getElementById('lcm-employees-group');
  if (!policySelect || !targetType) return;

  function clearPolicyPreview() {
    const setValue = function (id, value) {
      const element = document.getElementById(id);
      if (element) element.value = value || '';
    };
    setValue('lcm-title', '');
    setValue('lcm-category', '');
    setValue('lcm-status-updated', '');
    const attachment = document.getElementById('lcm-attachment');
    if (attachment) attachment.textContent = '(no attachment)';
    const label = document.getElementById('lcm-share-label');
    if (label) label.textContent = 'Share Policy';
  }

  function updatePolicyPreview() {
    const option = policySelect.options[policySelect.selectedIndex];
    const selectedOption = option && String(option.value || '').trim() !== '' ? option : null;

    if (!selectedOption) {
      clearPolicyPreview();
      return;
    }

    const setValue = function (id, value) {
      const element = document.getElementById(id);
      if (element) element.value = value || '';
    };

    const title = (selectedOption.dataset.title || selectedOption.textContent || '').trim();
    const status = selectedOption.dataset.status || 'Unknown';
    const updated = selectedOption.dataset.updated || '';
    const attachmentValue = selectedOption.dataset.attachment || '';
    setValue('lcm-title', title);
    setValue('lcm-category', selectedOption.dataset.category || '');
    setValue('lcm-status-updated', [status, updated].filter(Boolean).join(' / '));
    const attachment = document.getElementById('lcm-attachment');
    if (attachment) attachment.textContent = attachmentValue || '(no attachment)';
    const label = document.getElementById('lcm-share-label');
    if (label) label.textContent = selectedOption.dataset.isUpdate === '1' ? 'Announce Update' : 'Share Policy from LCM';
  }

  function updateAudienceFields() {
    const type = targetType.value;
    if (departmentGroup) departmentGroup.hidden = type !== 'department';
    if (employeesGroup) employeesGroup.hidden = type !== 'employees';
    const department = document.getElementById('lcm-department');
    const employees = document.getElementById('lcm-employees');
    if (department) department.required = type === 'department';
    if (employees) employees.required = type === 'employees';
  }

  policySelect.value = '';
  policySelect.selectedIndex = 0;
  clearPolicyPreview();

  policySelect.addEventListener('change', function () {
    if (!policySelect.value || String(policySelect.value).trim() === '') {
      clearPolicyPreview();
      return;
    }
    updatePolicyPreview();
  });
  targetType.addEventListener('change', updateAudienceFields);
  updateAudienceFields();
  if (shareForm) {
    shareForm.addEventListener('submit', function (event) {
      event.preventDefault();

      const button = shareForm.querySelector('button[type="submit"]');
      if (!button) return;

      const formData = new FormData(shareForm);
      const sourcePolicyId = formData.get('source_policy_id');
      if (!sourcePolicyId) {
        window.alert('Please select a policy to share.');
        return;
      }

      const selectedAudience = formData.get('target_type') || 'all';
      if (selectedAudience === 'department' && !formData.get('department_id')) {
        window.alert('Please select a department for the policy audience.');
        return;
      }
      if (selectedAudience === 'employees' && formData.getAll('employee_ids[]').length === 0) {
        window.alert('Please select at least one employee for the policy audience.');
        return;
      }

      const payload = {
        source_module: 'LCM',
        source_policy_id: String(sourcePolicyId),
        target_type: selectedAudience,
        department_id: formData.get('department_id') || '',
        employee_ids: formData.getAll('employee_ids[]'),
        announcement: formData.get('announcement') || ''
      };

      button.disabled = true;
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sharing...';

      fetch('api/communication.php?action=share_lcm_policy', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      })
        .then(function (response) {
          return response.json().then(function (data) {
            if (!response.ok || data.error) {
              throw new Error(data.error || data.message || 'Unable to share policy.');
            }
            return data;
          });
        })
        .then(function () {
          const currentUrl = new URL(window.location.href);
          currentUrl.searchParams.set('_policy_refresh', Date.now().toString());
          currentUrl.hash = '#policies';
          window.location.href = currentUrl.toString();
        })
        .catch(function (error) {
          window.alert(error.message);
          button.disabled = false;
          button.innerHTML = '<i class="fas fa-paper-plane"></i> <span id="lcm-share-label">Share Policy</span>';
        });
    });
  }
  updatePolicyPreview();
  updateAudienceFields();
}

// Initialize on page load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    console.log('[Communication Tab] DOMContentLoaded fired, initializing...');
    setTimeout(function() {
      initTabClickHandlers();
      activateTabFromHash();
      initCommunicationForms();
      initPolicyFilter();
      initLcmPolicySharing();
      console.log('[Communication Tab] Initialization complete');
    }, 100);
  });
} else {
  console.log('[Communication Tab] DOM already loaded, initializing...');
  setTimeout(function() {
    initTabClickHandlers();
    activateTabFromHash();
    initCommunicationForms();
    initPolicyFilter();
    initLcmPolicySharing();
    console.log('[Communication Tab] Initialization complete');
  }, 100);
}

// Listen for hash changes (browser back/forward)
window.addEventListener('hashchange', function() {
  activateTabFromHash();
});

// Listen for AJAX page loads
window.addEventListener('page:loaded', function(e) {
  if (e.detail && e.detail.page === 'communication') {
    console.log('[Communication Tab] Page loaded event fired, initializing tabs...');
    // Increased timeout to ensure DOM is fully ready
    setTimeout(function() {
      initTabClickHandlers();
      activateTabFromHash();
      initCommunicationForms();
      initPolicyFilter();
      initLcmPolicySharing();
      console.log('[Communication Tab] Initialization complete after page load');
    }, 150);
  }
});
