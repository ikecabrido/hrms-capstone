function normalizeCommunicationTab(tabId) {
  if (!tabId) return '';

  const normalized = String(tabId).trim().toLowerCase().replace(/_/g, '-');
  const validTabs = ['announcements', 'updates', 'messaging', 'policies'];

  if (validTabs.includes(normalized)) {
    return normalized;
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

function normalizeAnnouncementCategory(value) {
  return String(value || 'general').trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}

function renderCommunicationAnnouncementCard(announcement) {
  const title = escapeCommunicationHtml(announcement.title);
  const category = String(announcement.category || 'general');
  const categoryLabel = category.replace(/[_-]+/g, ' ').replace(/\b\w/g, function (character) {
    return character.toUpperCase();
  });
  const priority = String(announcement.priority || 'normal');
  const priorityLabel = priority.charAt(0).toUpperCase() + priority.slice(1);
  const date = announcement.created_at ? new Date(announcement.created_at).toLocaleString() : '';
  const author = announcement.author_name || announcement.created_by_name || 'Admin';
  const content = String(announcement.content || '');
  const contentMarkup = renderCommunicationContent(content, announcement.eer_announcements_id, 'primary')
    .replace('class="card-text"', 'class="announcement-content"');

  return '<div class="announcement-card card mb-3" data-category="' + escapeCommunicationHtml(normalizeAnnouncementCategory(category)) + '">' +
    '<div class="announcement-card-icon" aria-hidden="true"><i class="fas fa-bullhorn"></i></div>' +
    '<div class="card-body">' +
      '<div class="announcement-heading d-flex justify-content-between align-items-start mb-2">' +
        '<h6 class="card-title text-primary mb-1">' + title + '</h6>' +
        '<span class="badge badge-' + getPriorityBadgeClass(priority) + '">' + escapeCommunicationHtml(priorityLabel) + '</span>' +
      '</div>' +
      '<div class="announcement-meta">' +
        '<span><i class="fas fa-calendar"></i> ' + escapeCommunicationHtml(date) + '</span>' +
        '<span><i class="fas fa-tag"></i> ' + escapeCommunicationHtml(categoryLabel) + '</span>' +
        '<span><i class="fas fa-user"></i> ' + escapeCommunicationHtml(author) + '</span>' +
      '</div>' +
      contentMarkup +
    '</div>' +
  '</div>';
}

function renderCommunicationAnnouncements(announcements) {
  const container = document.getElementById('announcements-container');
  if (!container || !Array.isArray(announcements)) return;

  if (!announcements.length) {
    container.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-bullhorn fa-3x mb-3"></i><h5>No announcements yet</h5><p>Company announcements will appear here.</p></div>';
    applyAnnouncementFilter();
    return;
  }

  container.innerHTML = announcements.slice(0, 5).map(renderCommunicationAnnouncementCard).join('');
  applyAnnouncementFilter();
}

function applyAnnouncementFilter() {
  const container = document.getElementById('announcements-container');
  const filter = document.getElementById('announcement-category-filter');
  if (!container || !filter) return;

  const cards = Array.from(container.querySelectorAll('.announcement-card'));
  let visibleCount = 0;
  cards.forEach(function (card) {
    const matches = !filter.value || normalizeAnnouncementCategory(card.dataset.category) === normalizeAnnouncementCategory(filter.value);
    card.hidden = !matches;
    card.style.display = matches ? '' : 'none';
    if (matches) visibleCount += 1;
  });

  const emptyState = container.querySelector('[data-announcement-filter-empty]');
  if (cards.length > 0 && visibleCount === 0) {
    if (!emptyState) {
      container.insertAdjacentHTML('beforeend', '<div class="announcement-filter-empty" data-announcement-filter-empty>No announcements match this category.</div>');
    }
  } else if (emptyState) {
    emptyState.remove();
  }
}

function initAnnouncementFilter() {
  const filter = document.getElementById('announcement-category-filter');
  if (!filter || filter.dataset.filterBound === '1') return;

  filter.dataset.filterBound = '1';
  filter.addEventListener('change', applyAnnouncementFilter);
}

function initAnnouncementContentCounter() {
  const content = document.getElementById('announcement-content');
  const counter = document.getElementById('announcement-content-count');
  if (!content || !counter || content.dataset.counterBound === '1') return;

  content.dataset.counterBound = '1';
  const updateCount = function () {
    counter.textContent = String(content.value.length);
  };
  content.addEventListener('input', updateCount);
  const form = content.closest('form');
  if (form) {
    form.addEventListener('reset', function () {
      window.setTimeout(updateCount, 0);
    });
  }
  updateCount();
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
    const participant = isSent
      ? (message.receiver_name || message.receiver_id || '')
      : (message.sender_name || message.sender_id || '');
    const text = String(message.message || '');
    const date = message.timestamp ? new Date(message.timestamp) : null;
    const timestamp = date && !Number.isNaN(date.getTime())
      ? date.toLocaleDateString(undefined, {month: 'short', day: '2-digit', year: 'numeric'}) + ', ' + date.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'})
      : '';
    const initials = participant.trim().split(/\s+/).filter(Boolean).slice(0, 2).map(function (part) {
      return part.charAt(0).toUpperCase();
    }).join('') || '??';
    const preview = (isSent ? 'You: ' : '') + text;
    return '<div class="message-bubble ' + (isSent ? 'sent' : 'received') + '" data-message-search="' + escapeCommunicationHtml((participant + ' ' + text + ' ' + timestamp).toLowerCase()) + '">' +
      '<div class="message-avatar" aria-hidden="true">' + escapeCommunicationHtml(initials) + '</div>' +
      '<div class="message-summary">' +
        '<div class="message-summary-heading"><strong>' + escapeCommunicationHtml(participant) + '</strong>' +
          '<time' + (date && !Number.isNaN(date.getTime()) ? ' datetime="' + escapeCommunicationHtml(date.toISOString()) + '"' : '') + '><i class="fas fa-clock" aria-hidden="true"></i> ' + escapeCommunicationHtml(timestamp) + '</time>' +
        '</div>' +
        '<p>' + escapeCommunicationHtml(preview) + '</p>' +
      '</div>' +
    '</div>';
  }).join('') + emptySearch;
}

function initMessageContentCounter() {
  const content = document.getElementById('message-content');
  const counter = document.getElementById('message-content-count');
  if (!content || !counter || content.dataset.counterBound === '1') return;

  content.dataset.counterBound = '1';
  const updateCount = function () {
    counter.textContent = String(content.value.length);
  };
  content.addEventListener('input', updateCount);
  const form = content.closest('form');
  if (form) {
    form.addEventListener('reset', function () {
      window.setTimeout(updateCount, 0);
    });
  }
  updateCount();
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
      const contentType = response.headers.get('content-type') || '';
      if (!contentType.toLowerCase().includes('application/json')) {
        throw new Error('Invalid API response format.');
      }
      return response.text().then(function (text) {
        if (!text || !text.trim()) {
          throw new Error('Empty JSON response from server.');
        }
        try {
          return JSON.parse(text);
        } catch (error) {
          throw new Error('Malformed JSON response from server.');
        }
      });
    })
    .then(function (response) {
      const data = response.data || {};
      renderCommunicationAnnouncements(data.announcements);
      renderCommunicationUpdates(data.department_updates);
      renderCommunicationMessages(data.messageThreads, response.current_employee_id);
      populateCommunicationOptions(data);
    })
    .catch(function (error) {
      console.warn('[Communication] API data load failed; keeping server-rendered content.', error);
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
  initAnnouncementFilter();
  initAnnouncementContentCounter();
  initMessageContentCounter();

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
          const contentType = response.headers.get('content-type') || '';
          return response.text().then(function (text) {
            if (!text || !text.trim()) {
              throw new Error('Empty JSON response from server.');
            }
            let data;
            try {
              data = JSON.parse(text);
            } catch (error) {
              throw new Error('Malformed JSON response from server.');
            }
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
            if (container) {
              const emptyState = container.querySelector('.text-center.text-muted.py-4');
              if (emptyState) emptyState.remove();
              const announcement = {
                title: formData.get('title'),
                content: formData.get('content'),
                category: formData.get('category') || 'general',
                priority: formData.get('priority') || 'normal',
                created_at: new Date().toISOString(),
                author_name: 'You',
                eer_announcements_id: data.id
              };
              container.insertAdjacentHTML('afterbegin', renderCommunicationAnnouncementCard(announcement));
              applyAnnouncementFilter();
            }
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
              const recipient = form.querySelector('[name="receiver_id"]');
              const selectedRecipient = recipient && recipient.selectedIndex >= 0 ? recipient.options[recipient.selectedIndex].text : '';
              const messageDate = new Date();
              const messageText = String(formData.get('message') || '');
              const initials = selectedRecipient.trim().split(/\s+/).filter(Boolean).slice(0, 2).map(function (part) {
                return part.charAt(0).toUpperCase();
              }).join('') || '??';
              const messageCard = '<div class="message-bubble sent" data-message-search="' + escapeCommunicationHtml((selectedRecipient + ' ' + messageText + ' ' + messageDate.toLocaleString()).toLowerCase()) + '">' +
                '<div class="message-avatar" aria-hidden="true">' + escapeCommunicationHtml(initials) + '</div>' +
                '<div class="message-summary"><div class="message-summary-heading"><strong>' + escapeCommunicationHtml(selectedRecipient) + '</strong><time datetime="' + escapeCommunicationHtml(messageDate.toISOString()) + '"><i class="fas fa-clock" aria-hidden="true"></i> ' + escapeCommunicationHtml(messageDate.toLocaleDateString(undefined, {month: 'short', day: '2-digit', year: 'numeric'}) + ', ' + messageDate.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'})) + '</time></div>' +
                '<p>' + escapeCommunicationHtml('You: ' + messageText) + '</p></div></div>';
              container.insertAdjacentHTML('afterbegin', messageCard);
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
          return response.text().then(function (text) {
            if (!text || !text.trim()) {
              throw new Error('Empty JSON response from server.');
            }
            let data;
            try {
              data = JSON.parse(text);
            } catch (error) {
              throw new Error('Malformed JSON response from server.');
            }
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
      loadCommunicationPageData();
      console.log('[Communication Tab] Initialization complete after page load');
    }, 150);
  }
});
