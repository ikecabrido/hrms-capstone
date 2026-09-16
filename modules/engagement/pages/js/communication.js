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

function isCommunicationPage() {
  return Boolean(document.getElementById('communication-tabs'));
}

// Handle tab navigation based on the last selected tab, with a safe fallback
function activateTabFromHash() {
  if (!isCommunicationPage()) return;

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
      const firstTab = document.querySelector('#communication-tabs .nav-link');
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
  const allTabs = document.querySelectorAll('#communication-tabs .nav-link');
  const allPanes = document.querySelectorAll('#communication-tabs-content .tab-pane');
  
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
  if (!isCommunicationPage()) return;

  const tabLinks = document.querySelectorAll('#communication-tabs .nav-link');
  
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

function getPriorityBadgeClass(priority) {
  const classes = {urgent: 'danger', high: 'warning', normal: 'info', low: 'secondary'};
  return classes[String(priority || 'normal').toLowerCase()] || 'light';
}

function getNotificationTypeIcon(type) {
  const icons = {
    info: 'fas fa-info-circle text-info',
    warning: 'fas fa-exclamation-triangle text-warning',
    success: 'fas fa-check-circle text-success',
    danger: 'fas fa-times-circle text-danger'
  };
  return icons[String(type || '').toLowerCase()] || 'fas fa-bell text-primary';
}

function renderCommunicationAnnouncements(announcements) {
  const container = document.getElementById('announcements-container');
  if (!container || !Array.isArray(announcements)) return;

  if (!announcements.length) {
    container.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-bullhorn fa-3x mb-3"></i><h5>No announcements yet</h5><p>Company announcements will appear here.</p></div>';
    return;
  }

  container.innerHTML = announcements.slice(0, 5).map(function (announcement) {
    const content = String(announcement.content || '');
    const date = announcement.created_at ? new Date(announcement.created_at).toLocaleString() : '';
    return '<div class="announcement-card card mb-3"><div class="card-body">' +
      '<div class="d-flex justify-content-between align-items-start mb-2"><h6 class="card-title text-primary mb-1">' + escapeCommunicationHtml(announcement.title) + '</h6>' +
      '<span class="badge badge-' + getPriorityBadgeClass(announcement.priority) + '">' + escapeCommunicationHtml((announcement.priority || 'normal').charAt(0).toUpperCase() + (announcement.priority || 'normal').slice(1)) + '</span></div>' +
      '<p class="card-text text-muted small mb-2"><i class="fas fa-calendar"></i> ' + escapeCommunicationHtml(date) + ' | <i class="fas fa-tag"></i> ' + escapeCommunicationHtml(announcement.category || 'general') + ' | <i class="fas fa-user"></i> ' + escapeCommunicationHtml(announcement.author_name || announcement.created_by_name || 'Admin') + '</p>' +
      renderCommunicationContent(content, announcement.eer_announcements_id, 'primary') +
      '</div></div>';
  }).join('');
}

function renderCommunicationUpdates(updates) {
  const container = document.getElementById('updates-container');
  if (!container || !Array.isArray(updates)) return;

  if (!updates.length) {
    container.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-building fa-3x mb-3"></i><h5>Department Updates</h5><p>Updates from different departments will appear here.</p></div>';
    return;
  }

  container.innerHTML = updates.slice(0, 10).map(function (update) {
    const content = String(update.content || '');
    const date = update.created_at ? new Date(update.created_at).toLocaleString() : '';
    return '<div class="dept-update-item card mb-3" data-dept="' + escapeCommunicationHtml(update.department || '') + '"><div class="card-body">' +
      '<div class="d-flex justify-content-between align-items-start mb-2"><h6 class="card-title text-success mb-1">' + escapeCommunicationHtml(update.title) + '</h6>' +
      '<span class="badge badge-' + getPriorityBadgeClass(update.priority) + '">' + escapeCommunicationHtml((update.priority || 'normal').charAt(0).toUpperCase() + (update.priority || 'normal').slice(1)) + '</span></div>' +
      '<p class="card-text text-muted small mb-2"><i class="fas fa-calendar"></i> ' + escapeCommunicationHtml(date) + ' | <i class="fas fa-building"></i> ' + escapeCommunicationHtml(update.department || 'General') + ' | <i class="fas fa-user"></i> ' + escapeCommunicationHtml(update.author_name || update.created_by_name || 'Admin') + '</p>' +
      renderCommunicationContent(content, update.eer_announcements_id, 'success') +
      '</div></div>';
  }).join('');
}

function renderCommunicationNotifications(notifications, lcmNotifications) {
  const container = document.getElementById('notifications-container');
  if (!container || !Array.isArray(notifications)) return;
  const allNotifications = notifications.concat(Array.isArray(lcmNotifications) ? lcmNotifications : []);

  if (!allNotifications.length) {
    container.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-bell fa-3x mb-3"></i><h5>No notifications</h5><p>HR notifications will appear here.</p></div>';
    return;
  }

  container.innerHTML = allNotifications.map(function (notification) {
    const isRead = Number(notification.is_read || 0) === 1;
    const title = notification.title || (notification.notification_type ? 'Legal & Compliance' : 'HR Update');
    return '<div class="notification-item ' + (isRead ? 'notification-read' : 'notification-unread') + '"><div class="d-flex justify-content-between align-items-start"><div class="flex-grow-1">' +
      '<div class="d-flex align-items-center mb-1"><span class="badge badge-light notification-type-badge notification-type-badge--side">' + escapeCommunicationHtml(getNotificationTypeLabel(notification.type || notification.notification_type || 'info')) + '</span><i class="' + getNotificationTypeIcon(notification.type || notification.notification_type || 'info') + ' mr-2"></i><h6 class="mb-0">' + escapeCommunicationHtml(title) + '</h6></div>' +
      '<p class="text-muted small mb-1"><i class="fas fa-calendar"></i> ' + escapeCommunicationHtml(notification.created_at || '') + '</p><p class="mb-2">' + escapeCommunicationHtml(notification.message || '') + '</p></div>' +
      (!isRead && notification.id ? '<div class="ml-3 notification-actions"><button type="button" class="btn btn-sm btn-outline-success js-mark-notification-read" data-notification-id="' + Number(notification.id) + '"><i class="fas fa-check"></i> Mark Read</button><span class="badge badge-primary notification-new-badge">New</span></div>' : '') +
      '</div></div>';
  }).join('');
}

function getNotificationTypeLabel(type) {
  const labels = {survey: 'Survey', social: 'Social', recognition: 'Recognition', grievance: 'Grievance', policy: 'Policy'};
  return labels[String(type || '').toLowerCase()] || 'HR Update';
}

function renderCommunicationMessages(messages, currentEmployeeId) {
  const container = document.getElementById('messages-container');
  if (!container || !Array.isArray(messages)) return;
  const emptySearch = '<div id="message-search-empty" class="text-center text-muted py-4" style="display: none;"><i class="fas fa-search fa-2x mb-2"></i><h5>No matching messages</h5><p>Try a different name, message, or time.</p></div>';

  if (!messages.length) {
    container.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-comments fa-3x mb-3"></i><h5>No messages yet</h5><p>Your conversations with HR will appear here.</p></div>' + emptySearch;
    return;
  }

  container.innerHTML = messages.map(function (message) {
    const isSent = Number(message.sender_id) === Number(currentEmployeeId);
    const sender = message.sender_name || message.sender_id || '';
    const text = String(message.message || '');
    const timestamp = message.timestamp ? new Date(message.timestamp).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}) : '';
    return '<div class="message-bubble ' + (isSent ? 'sent' : 'received') + '" data-message-search="' + escapeCommunicationHtml((sender + ' ' + text + ' ' + timestamp).toLowerCase()) + '"><div class="p-2"><div class="d-flex justify-content-between align-items-center mb-1"><small class="text-muted"><i class="fas fa-user"></i> ' + escapeCommunicationHtml(sender) + '</small><small class="text-muted"><i class="fas fa-clock"></i> ' + escapeCommunicationHtml(timestamp) + '</small></div><p class="mb-0">' + escapeCommunicationHtml(text) + '</p></div></div>';
  }).join('') + emptySearch;
}

function populateCommunicationOptions(data) {
  const departments = Array.isArray(data.departments) ? data.departments : [];
  const employees = Array.isArray(data.employees) ? data.employees : [];
  const departmentSelects = document.querySelectorAll('select[name="department"], #dept-filter');
  const employeeSelect = document.querySelector('select[name="receiver_id"]');

  departmentSelects.forEach(function (select) {
    const original = select.id === 'dept-filter' ? '<option value="">All Departments</option>' : '<option value="">Select Department</option>';
    select.innerHTML = original + departments.map(function (department) {
      const name = department.department_name || '';
      return name ? '<option value="' + escapeCommunicationHtml(name) + '">' + escapeCommunicationHtml(name) + '</option>' : '';
    }).join('') + (select.id === 'dept-filter' ? '' : '<option value="all">All Departments</option>');
  });

  if (employeeSelect) {
    employeeSelect.innerHTML = '<option value="">Select recipient...</option>' + employees.map(function (employee) {
      const name = [employee.first_name, employee.middle_name, employee.last_name].filter(Boolean).join(' ');
      const id = Number(employee.employee_id || 0);
      return id ? '<option value="' + id + '">' + escapeCommunicationHtml(name || employee.employee_code || ('Employee #' + id)) + '</option>' : '';
    }).join('');
  }
}

function loadCommunicationPageData() {
  fetch('api/communication.php?action=page_data', {credentials: 'same-origin', cache: 'no-store'})
    .then(function (response) {
      if (!response.ok) throw new Error('Unable to load communication data.');
      return response.json();
    })
    .then(function (response) {
      const data = response.data || {};
      renderCommunicationAnnouncements(data.announcements);
      renderCommunicationUpdates(data.department_updates);
      renderCommunicationNotifications(data.notifications, data.lcm_notifications);
      renderCommunicationMessages(data.messageThreads, response.current_employee_id);
      populateCommunicationOptions(data);
    })
    .catch(function (error) {
      console.warn('[Communication] API data load failed; keeping server-rendered content.', error);
    });
}

function initCommunicationApiActions() {
  const container = document.getElementById('notifications-container');
  if (!container || container.dataset.apiActionsBound === '1') return;
  container.dataset.apiActionsBound = '1';
  container.addEventListener('click', function (event) {
    const button = event.target.closest('.js-mark-notification-read');
    if (!button) return;

    button.disabled = true;
    fetch('api/communication.php?action=mark_notification_read', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'same-origin',
      body: JSON.stringify({notification_id: button.dataset.notificationId})
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || data.error) throw new Error(data.error || 'Unable to mark notification as read.');
          return data;
        });
      })
      .then(function () {
        const item = button.closest('.notification-item');
        if (item) {
          item.classList.remove('notification-unread');
          item.classList.add('notification-read');
          item.querySelectorAll('.js-mark-notification-read, .notification-new-badge').forEach(function (element) { element.remove(); });
        }
      })
      .catch(function (error) {
        button.disabled = false;
        window.alert(error.message);
      });
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
      if (formType === 'message') {
        const messagesContainer = document.getElementById('messages-container');
        const emptyState = messagesContainer ? messagesContainer.querySelector('.text-center.text-muted') : null;
        if (emptyState) emptyState.remove();
      }
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
            if (container) {
              const emptyState = container.querySelector('.text-center.text-muted');
              if (emptyState) emptyState.remove();
              container.insertAdjacentHTML('afterbegin', '<div class="dept-update-item card mb-3"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-2"><h6 class="card-title text-success">' + escapeCommunicationHtml(formData.get('title')) + '</h6>' + priorityBadge + '</div><p class="text-muted small"><i class="fas fa-calendar"></i> ' + time + ' | <i class="fas fa-building"></i> ' + escapeCommunicationHtml(formData.get('department')) + '</p>' + renderCommunicationContent(formData.get('content'), data.id, 'success') + '</div></div>');
            }
          } else if (formType === 'message') {
            const container = document.getElementById('messages-container');
            if (container) {
              const emptyState = container.querySelector('.text-center.text-muted');
              if (emptyState) emptyState.remove();
              const messageText = escapeCommunicationHtml(formData.get('message'));
              container.insertAdjacentHTML('afterbegin', '<div class="message-bubble sent" data-message-search="' + messageText.toLowerCase() + ' current just now"><div class="p-2"><small class="text-muted"><i class="fas fa-clock"></i> Just now</small><p class="mb-0">' + messageText + '</p></div></div>');
              applyMessageSearch();
            }
          }
          form.reset();
        })
        .catch(function (error) {
          if (formType === 'message') {
            const container = document.getElementById('messages-container');
            if (container && !container.querySelector('.message-bubble')) {
              container.insertAdjacentHTML('beforeend', '<div class="text-center text-muted py-4"><i class="fas fa-comments fa-3x mb-3"></i><h5>No messages yet</h5><p>Your conversations with HR will appear here.</p></div>');
            }
          }
          window.alert(error.message);
        })
        .finally(function () {
          if (button) { button.disabled = false; button.innerHTML = originalText; }
        });
    });
  });
}

function applyMessageSearch() {
  const searchInput = document.getElementById('message-search');
  const container = document.getElementById('messages-container');
  const emptyState = document.getElementById('message-search-empty');

  if (!searchInput || !container) return;

  const query = String(searchInput.value || '').trim().toLowerCase();
  const bubbles = Array.from(container.querySelectorAll('.message-bubble'));
  let visibleCount = 0;

  bubbles.forEach(function (bubble) {
    const searchableText = (String(bubble.dataset.messageSearch || '') + ' ' + String(bubble.textContent || '')).toLowerCase();
    const isMatch = !query || searchableText.includes(query);
    bubble.style.display = isMatch ? '' : 'none';
    if (isMatch) visibleCount += 1;
  });

  if (emptyState) {
    emptyState.style.display = query && visibleCount === 0 ? 'block' : 'none';
  }
}

function initMessageSearch() {
  const searchInput = document.getElementById('message-search');

  if (!searchInput || searchInput.dataset.searchBound === '1') {
    applyMessageSearch();
    return;
  }

  searchInput.dataset.searchBound = '1';
  searchInput.addEventListener('input', applyMessageSearch);

  applyMessageSearch();
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
  const emptyState = document.querySelector('#policies-container .text-center.text-muted.py-4');

  if (!filterSelect) return;

  const normalizeCategory = function (value) {
    const normalized = String(value || '').trim().toLowerCase();
    if (!normalized || normalized === 'all') return '';
    return normalized.replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  };

  filterSelect.addEventListener('change', function () {
    const selected = normalizeCategory(this.value || '');
    let visibleCount = 0;

    document.querySelectorAll('#policies-container .policy-card').forEach(function (card) {
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

function addSharedPolicyCard(policyData, targetAudience) {
  const container = document.getElementById('policies-container');
  if (!container) return;

  const emptyState = container.querySelector('.text-center.text-muted.py-4');
  if (emptyState) emptyState.remove();

  const title = policyData.title;
  const content = policyData.content || 'Please review the policy shared by Legal & Compliance Management.';
  const effectiveDate = policyData.effective || '';
  const attachment = policyData.attachment || '';
  const isUpdate = policyData.isUpdate;
  const sourcePolicyId = policyData.sourcePolicyId;
  const now = new Date();
  const sharedAt = now.toLocaleDateString(undefined, {month: 'short', day: '2-digit', year: 'numeric'}) + ' ' + now.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
  const normalizedCategory = (policyData.category || 'General').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'general';
  const normalizedTitle = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  const preview = content.length > 150 ? content.slice(0, 150) + '...' : content;
  const alreadyShared = Array.from(container.querySelectorAll('.policy-card')).some(function (card) {
    const existingSourceId = String(card.dataset.sourcePolicyId || '').split('|', 1)[0];
    const baseSourceId = String(sourcePolicyId || '').split('|', 1)[0];
    return (existingSourceId && baseSourceId && existingSourceId === baseSourceId) || card.dataset.title === normalizedTitle;
  });
  if (alreadyShared) return;

  const download = attachment
    ? '<a href="policy_download.php?lcm=1&id=' + encodeURIComponent(sourcePolicyId) + '" class="btn btn-xs btn-outline-secondary" download style="font-size: 11px; padding: 4px 10px; line-height: 1.3; border-radius: 6px; margin-left: auto;"><i class="fas fa-download"></i> Download</a>'
    : '<span class="badge badge-secondary" style="font-size: 10px; padding: 4px 7px; line-height: 1.2;"><i class="fas fa-minus-circle"></i> No File</span>';

  container.insertAdjacentHTML('afterbegin',
    '<div class="card mb-3 policy-card" data-source-policy-id="' + escapeCommunicationHtml(sourcePolicyId) + '" data-category="' + escapeCommunicationHtml(normalizedCategory) + '" data-title="' + escapeCommunicationHtml(normalizedTitle) + '">' +
      '<div class="card-body">' +
        '<div class="d-flex justify-content-between align-items-start mb-2"><h6 class="card-title text-primary mb-1"><i class="fas fa-file-contract text-primary mr-2"></i>' + escapeCommunicationHtml(title) + (isUpdate ? ' <span class="badge badge-warning ml-2">Policy Update</span>' : '') + '</h6></div>' +
        '<p class="text-muted small mb-2"><i class="fas fa-calendar"></i> Policy date: ' + escapeCommunicationHtml(effectiveDate || 'N/A') + ' | <i class="fas fa-paper-plane"></i> Shared: ' + escapeCommunicationHtml(sharedAt) + ' | Audience: ' + escapeCommunicationHtml(targetAudience) + '</p>' +
        '<p class="card-text">' + escapeCommunicationHtml(preview).replace(/\n/g, '<br>') + '</p>' +
        '<div class="d-flex justify-content-between align-items-center flex-wrap" style="margin-top: 0.5rem; gap: 0.5rem;"><div style="flex: 1; min-width: 0;"></div>' + download + '</div>' +
      '</div>' +
    '</div>'
  );

  const filterSelect = document.getElementById('policy-filter');
  if (filterSelect) filterSelect.dispatchEvent(new Event('change'));
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
      if (shareForm.dataset.submitting === '1') return;
      shareForm.dataset.submitting = '1';

      const button = shareForm.querySelector('button[type="submit"]');
      if (!button) {
        shareForm.dataset.submitting = '0';
        return;
      }

      const formData = new FormData(shareForm);
      const sourcePolicyId = formData.get('source_policy_id');
      if (!sourcePolicyId) {
        shareForm.dataset.submitting = '0';
        window.alert('Please select a policy to share.');
        return;
      }

      const selectedAudience = formData.get('target_type') || 'all';
      if (selectedAudience === 'department' && !formData.get('department_id')) {
        shareForm.dataset.submitting = '0';
        window.alert('Please select a department for the policy audience.');
        return;
      }
      if (selectedAudience === 'employees' && formData.getAll('employee_ids[]').length === 0) {
        shareForm.dataset.submitting = '0';
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
      const selectedOption = policySelect.options[policySelect.selectedIndex];
      const selectedPolicy = {
        sourcePolicyId: String(sourcePolicyId),
        title: (selectedOption.dataset.title || selectedOption.textContent || '').trim(),
        content: selectedOption.dataset.content || '',
        effective: selectedOption.dataset.effective || '',
        attachment: selectedOption.dataset.attachment || '',
        isUpdate: selectedOption.dataset.isUpdate === '1'
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
          const audienceLabels = {all: 'all', department: 'department', employees: 'selected employees'};
          addSharedPolicyCard(selectedPolicy, audienceLabels[selectedAudience] || selectedAudience);
          shareForm.reset();
          clearPolicyPreview();
          updateAudienceFields();
          button.disabled = false;
          button.innerHTML = '<i class="fas fa-paper-plane"></i> <span id="lcm-share-label">Share Policy</span>';
          shareForm.dataset.submitting = '0';
        })
        .catch(function (error) {
          window.alert(error.message);
          button.disabled = false;
          button.innerHTML = '<i class="fas fa-paper-plane"></i> <span id="lcm-share-label">Share Policy</span>';
          shareForm.dataset.submitting = '0';
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
      if (!isCommunicationPage()) return;
      initTabClickHandlers();
      activateTabFromHash();
      initCommunicationForms();
      initMessageSearch();
      initPolicyFilter();
      initLcmPolicySharing();
      initCommunicationApiActions();
      loadCommunicationPageData();
      console.log('[Communication Tab] Initialization complete');
    }, 100);
  });
} else {
  console.log('[Communication Tab] DOM already loaded, initializing...');
  setTimeout(function() {
    if (!isCommunicationPage()) return;
    initTabClickHandlers();
    activateTabFromHash();
    initCommunicationForms();
    initMessageSearch();
    initPolicyFilter();
    initLcmPolicySharing();
    initCommunicationApiActions();
    loadCommunicationPageData();
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
      initMessageSearch();
      initPolicyFilter();
      initLcmPolicySharing();
      initCommunicationApiActions();
      loadCommunicationPageData();
      console.log('[Communication Tab] Initialization complete after page load');
    }, 150);
  }
});
