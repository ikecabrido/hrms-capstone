let recognitionPageInitialized = false;
let recognitionPageBindingsInitialized = false;
let recognitionTabClickHandlersBound = false;
let recognitionTabObserver = null;
let recognitionTabGuardTimer = null;
let employeeMonthCandidatesRequestId = 0;
let pendingNomination = null;
const recognitionApiBase = window.location.pathname.split('/modules/engagement/')[0]
  + '/modules/engagement/api/index.php';

function setRecognitionModalVisibility(modalId, shouldOpen) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  modal.classList.toggle('show', shouldOpen);
  modal.classList.toggle('d-block', shouldOpen);
  modal.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
  modal.setAttribute('aria-modal', shouldOpen ? 'true' : 'false');
  modal.style.display = shouldOpen ? 'block' : 'none';

  if (shouldOpen) {
    document.body.classList.add('modal-open');
    if (!document.querySelector('.modal-backdrop')) {
      const backdrop = document.createElement('div');
      backdrop.className = 'modal-backdrop fade show';
      document.body.appendChild(backdrop);
    }
  } else {
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
      backdrop.remove();
    });
  }
}

function closeRecognitionModal() {
  setRecognitionModalVisibility('sendRecognitionModal', false);
}

function openRecognitionModal() {
  setRecognitionModalVisibility('sendRecognitionModal', true);
}

function closeNominationModal() {
  setRecognitionModalVisibility('nominateEmployeeModal', false);
}

function closeAssignBadgeModal() {
  setRecognitionModalVisibility('assignBadgeModal', false);
}

function closeAddRewardModal() {
  setRecognitionModalVisibility('addRewardModal', false);
}

function closeCreateBadgeModal() {
  setRecognitionModalVisibility('createBadgeModal', false);
}

function closeAllRecognitionModals() {
  closeRecognitionModal();
  closeNominationModal();
  closeAssignBadgeModal();
  closeAddRewardModal();
  closeCreateBadgeModal();
}

function openNominationModal() {
  setRecognitionModalVisibility('nominateEmployeeModal', true);
}

window.closeRecognitionModal = closeRecognitionModal;
window.openRecognitionModal = openRecognitionModal;
window.closeNominationModal = closeNominationModal;
window.closeAssignBadgeModal = closeAssignBadgeModal;
window.closeAddRewardModal = closeAddRewardModal;
window.closeCreateBadgeModal = closeCreateBadgeModal;
window.openNominationModal = openNominationModal;

function getCurrentMonthYear() {
  const date = new Date();
  return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
}

function appendPendingNomination() {
  if (!pendingNomination) return;
  const container = document.getElementById('employee-month-candidates-list');
  if (!container) return;
  const exists = Array.from(container.querySelectorAll('.candidate-item')).some(function(item) {
    return String(item.dataset.employeeId || '') === String(pendingNomination.employeeId);
  });
  if (exists) {
    pendingNomination = null;
    return;
  }
  const emptyMessage = container.querySelector('.text-center.text-muted');
  if (emptyMessage) emptyMessage.remove();
  const item = document.createElement('div');
  item.className = 'list-group-item candidate-item recognition-pending-nomination';
  item.dataset.employeeId = pendingNomination.employeeId;
  item.dataset.awardHistoryId = pendingNomination.awardHistoryId || '';
  item.innerHTML = '<strong>' + escapeHtml(pendingNomination.employeeName) + '</strong>'
    + '<div class="text-muted small">Nomination submitted</div>'
    + '<div class="mt-1"><span class="badge badge-warning">Nominated</span>'
    + ' <span class="text-muted small">Reason: ' + escapeHtml(pendingNomination.reason) + '</span></div>';
  container.appendChild(item);
}

function populateBadgeEmployeesFromNominations() {
  const select = document.getElementById('badge_employee_id');
  if (!select) return;
  select.innerHTML = '<option value="">Select nominated employee</option>';

  document.querySelectorAll('#employee-month-candidates-list .candidate-item').forEach(function(candidate) {
    const employeeId = candidate.dataset.employeeId;
    const nameElement = candidate.querySelector('strong');
    if (!employeeId || !nameElement) return;
    const option = document.createElement('option');
    option.value = employeeId;
    option.textContent = nameElement.textContent.trim() + ' (' + employeeId + ')';
    select.appendChild(option);
  });
}

function bindNominationFormEvents() {
  const nominationForm = document.getElementById('nomination-form');
  if (!nominationForm || nominationForm.dataset.bound === '1') return;
  nominationForm.dataset.bound = '1';

  nominationForm.addEventListener('submit', function(event) {
    event.preventDefault();
    if (nominationForm.dataset.submitting === '1') return;

    const nominationSel = document.getElementById('nominate-employee');
    const employeeId = nominationSel ? nominationSel.value : '';
    const reasonField = document.getElementById('nomination-reason');
    const reason = reasonField ? reasonField.value.trim() : '';
    if (!employeeId || !reason) {
      nominationForm.reportValidity();
      return;
    }

    const submitButton = nominationForm.querySelector('button[type="submit"]');
    nominationForm.dataset.submitting = '1';
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Saving...';
    }

    fetch(recognitionApiBase + '?resource=award_history&action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        employee_id: employeeId,
        award_name: 'Employee of the Month Nomination',
        reason: reason,
        award_type: 'employee_of_month',
        month_year: getCurrentMonthYear(),
        status: 'nominated'
      })
    })
      .then(response => response.json().then(result => ({ response, result })))
      .then(({ response, result }) => {
        if (!response.ok || (!result.id && !result.success)) {
          throw new Error(result.error || 'Unable to save nomination.');
        }
          pendingNomination = null;
        nominationForm.reset();
        closeNominationModal();
          const recognitionUrl = new URL(window.location.href);
          recognitionUrl.searchParams.set('page', 'recognition');
          recognitionUrl.searchParams.set('refresh', Date.now().toString());
          recognitionUrl.hash = 'employee-month';
          window.location.assign(recognitionUrl.toString());
      })
      .catch(error => {
        console.error('Failed to save nomination', error);
        alert(error.message);
      })
      .finally(() => {
        nominationForm.dataset.submitting = '0';
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.innerHTML = '<i class="fas fa-star mr-1"></i>Submit Nomination';
        }
      });
  });
}

function populateNominationEmployeesFromFeed() {
  const select = document.getElementById('nominate-employee');
  if (!select || select.options.length > 1) return;
  const nominationModal = document.getElementById('nominateEmployeeModal');
  const currentEmployeeId = nominationModal ? nominationModal.dataset.currentEmployeeId : '';

  const employees = new Map();
  document.querySelectorAll('#recognition-feed .recognition-entry').forEach(function(entry) {
    [[entry.dataset.senderId, entry.dataset.senderName], [entry.dataset.receiverId, entry.dataset.receiverName]].forEach(function(employee) {
      if (employee[0] && employee[1] && employee[0] !== currentEmployeeId) employees.set(employee[0], employee[1]);
    });
  });

  if (!employees.size) return;
  select.innerHTML = '<option value="">Select employee</option>';
  employees.forEach(function(name, id) {
    const option = document.createElement('option');
    option.value = id;
    option.textContent = name + ' (' + id + ')';
    select.appendChild(option);
  });
}

// Add smooth transition styles
const recognitionStyle = document.createElement('style');
recognitionStyle.textContent = `
  .recognition-area .tab-pane {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
    pointer-events: none;
  }
  .recognition-area .tab-pane.show.active {
    opacity: 1;
    pointer-events: auto;
  }
  .recognition-area .nav-link {
    transition: all 0.3s ease-in-out;
  }
  .recognition-area .nav-link.active {
    transition: all 0.3s ease-in-out;
  }
`;
document.head.appendChild(recognitionStyle);

const RECOGNITION_STORAGE_KEY = 'engagement:recognition:active-tab';
const RECOGNITION_LEGACY_STORAGE_KEY = 'recognition-active-tab';
const RECOGNITION_COOKIE_KEY = 'engagement_recognition_tab';

function clearRecognitionTabCookies() {
  ['/hrms-capstone/modules/engagement/', '/'].forEach(function (cookiePath) {
    document.cookie = RECOGNITION_COOKIE_KEY + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=' + cookiePath + ';';
  });
}

function getRecognitionCookieValue() {
  const matches = Array.from(document.cookie.split(';'))
    .map(function (cookiePart) {
      const [name, ...valueParts] = cookiePart.split('=');
      return { name: name.trim(), value: valueParts.join('=').trim() };
    })
    .filter(function (cookieItem) {
      return cookieItem.name === RECOGNITION_COOKIE_KEY;
    });

  if (!matches.length) return '';
  return decodeURIComponent(matches[matches.length - 1].value || '');
}

function saveRecognitionTab(tabId) {
  if (!tabId) return;

  clearRecognitionTabCookies();
  document.cookie = RECOGNITION_COOKIE_KEY + '=' + encodeURIComponent(tabId) + '; max-age=604800; path=/hrms-capstone/modules/engagement/;';

  localStorage.setItem(RECOGNITION_STORAGE_KEY, tabId);
  localStorage.setItem(RECOGNITION_LEGACY_STORAGE_KEY, tabId);
  sessionStorage.setItem(RECOGNITION_STORAGE_KEY, tabId);
  sessionStorage.setItem(RECOGNITION_LEGACY_STORAGE_KEY, tabId);
  console.log('[Recognition Tab] Saved to storage:', tabId);

  const currentHash = (window.location.hash || '').replace('#', '');
  if (currentHash !== tabId) {
    const baseUrl = window.location.pathname + window.location.search;
    const nextUrl = tabId === 'recognition' ? baseUrl : baseUrl + '#' + tabId;
    window.history.replaceState({}, '', nextUrl);
    console.log('[Recognition Tab] Updated URL hash to:', tabId);
  }
}

function switchRecognitionTab(tabLink, isInitialLoad = false) {
  const href = tabLink.getAttribute('href');
  if (!href || !href.startsWith('#')) return;

  const tabId = href.replace('#', '');
  const targetPane = document.getElementById(tabId);
  if (!targetPane) return;

  const currentActiveTab = document.querySelector('#recognition-tabs .nav-link.active');
  const currentHref = currentActiveTab ? currentActiveTab.getAttribute('href') : null;
  if (currentHref === href && targetPane.classList.contains('active')) {
    protectRecognitionTab();
    return;
  }

  if (!isInitialLoad && window.location.hash !== href) {
    saveRecognitionTab(tabId);
  }

  const allTabs = document.querySelectorAll('#recognition-tabs .nav-link');
  const allPanes = document.querySelectorAll('.recognition-area .tab-pane');

  allTabs.forEach(tab => {
    tab.classList.remove('active');
    tab.setAttribute('aria-selected', 'false');
  });

  allPanes.forEach(pane => {
    pane.classList.remove('show', 'active');
  });

  tabLink.classList.add('active');
  tabLink.setAttribute('aria-selected', 'true');
  targetPane.classList.add('show', 'active');
  const recognitionCard = document.querySelector('.recognition-card');
  if (recognitionCard) recognitionCard.classList.remove('recognition-tabs-pending');
  saveRecognitionTab(tabId);
  protectRecognitionTab();
}

function protectRecognitionTab() {
  const tabs = document.getElementById('recognition-tabs');
  const recognitionCard = tabs ? tabs.closest('.recognition-card') : null;
  if (!tabs || recognitionTabObserver) return;

  recognitionTabObserver = new MutationObserver(function() {
    const savedTab = getSavedRecognitionTabId();
    const activeTab = tabs.querySelector('.nav-link.active');
    const activeTabId = activeTab ? (activeTab.getAttribute('href') || '').replace('#', '') : '';
    const activePane = document.getElementById(savedTab);

    if (savedTab !== activeTabId || !activePane || !activePane.classList.contains('active')) {
      activateRecognitionTabFromHash(true);
    }
  });

  recognitionTabObserver.observe(tabs, {
    subtree: true,
    attributes: true,
    attributeFilter: ['class', 'aria-selected']
  });

  if (recognitionCard) {
    recognitionTabObserver.observe(recognitionCard, {
      subtree: true,
      childList: true,
      attributes: true,
      attributeFilter: ['class', 'style', 'hidden', 'aria-selected']
    });
  }
}

function getSavedRecognitionTabId() {
  const validTabIds = ['recognition', 'employee-month', 'badges', 'rewards', 'leaderboard'];
  const savedTab =
    sessionStorage.getItem(RECOGNITION_STORAGE_KEY)
    || sessionStorage.getItem(RECOGNITION_LEGACY_STORAGE_KEY)
    || localStorage.getItem(RECOGNITION_STORAGE_KEY)
    || localStorage.getItem(RECOGNITION_LEGACY_STORAGE_KEY)
    || '';
  if (validTabIds.includes(savedTab)) return savedTab;

  const decodedCookieTab = getRecognitionCookieValue();
  if (validTabIds.includes(decodedCookieTab)) return decodedCookieTab;

  const hashTab = (window.location.hash || '').replace('#', '');
  return validTabIds.includes(hashTab) ? hashTab : 'recognition';
}

function resetRecognitionTabObserver() {
  if (recognitionTabObserver) {
    recognitionTabObserver.disconnect();
    recognitionTabObserver = null;
  }
}

function startRecognitionTabGuard() {
  if (recognitionTabGuardTimer) return;

  recognitionTabGuardTimer = window.setInterval(function() {
    const tabs = document.getElementById('recognition-tabs');
    if (!tabs) return;

    const savedTab = getSavedRecognitionTabId();
    const activeTab = tabs.querySelector('.nav-link.active');
    const activeTabId = activeTab ? (activeTab.getAttribute('href') || '').replace('#', '') : '';
    const activePane = document.querySelector('.recognition-area .tab-pane.show.active');
    const currentHash = (window.location.hash || '').replace('#', '');

    if (savedTab && (activeTabId !== savedTab || !activePane || activePane.id !== savedTab || currentHash !== savedTab)) {
      const targetTab = tabs.querySelector('a[href="#' + CSS.escape(savedTab) + '"]');
      if (targetTab) switchRecognitionTab(targetTab, true);
      saveRecognitionTab(savedTab);
    }
  }, 300);
}

function activateRecognitionTabFromHash(isInitialLoad = false) {
  const validTabIds = ['recognition', 'employee-month', 'badges', 'rewards', 'leaderboard'];
  const legacyTabMappings = {
    'employees-of-month': 'employee-month',
    'points-leaderboard': 'leaderboard',
  };

  function normalizeRecognitionTabId(tabId) {
    if (!tabId) return '';
    const trimmed = tabId.trim();
    return legacyTabMappings[trimmed] || trimmed;
  }

  const rawUrlHash = window.location.hash ? window.location.hash.replace('#', '') : '';
  const urlHash = normalizeRecognitionTabId(rawUrlHash);
  const savedCookie = getRecognitionCookieValue();
  const savedHash = normalizeRecognitionTabId(
    sessionStorage.getItem(RECOGNITION_STORAGE_KEY)
      || sessionStorage.getItem(RECOGNITION_LEGACY_STORAGE_KEY)
      || localStorage.getItem(RECOGNITION_STORAGE_KEY)
      || localStorage.getItem(RECOGNITION_LEGACY_STORAGE_KEY)
      || savedCookie
      || ''
  );

  const hash = savedHash && validTabIds.includes(savedHash)
    ? savedHash
    : (urlHash && validTabIds.includes(urlHash) ? urlHash : 'recognition');
  if (hash !== urlHash) {
    window.history.replaceState({}, '', window.location.pathname + window.location.search + '#' + hash);
    console.log('[Recognition Tab] Synchronized URL hash with active tab:', hash);
  }
  console.log('[Recognition Tab] Using active tab:', hash);

  console.log('[Recognition Tab] Activating tab:', hash);

  // Retry logic for DOM readiness
  let retryCount = 0;
  const maxRetries = 5;

  function attemptActivate() {
    const targetTab = document.querySelector('#recognition-tabs a[href="#' + CSS.escape(hash) + '"]');
    const targetPane = document.getElementById(hash);

    if (targetTab && targetPane) {
      console.log('[Recognition Tab] DOM ready, activating:', hash);
      
      const currentActiveTab = document.querySelector('#recognition-tabs .nav-link.active');
      const currentHref = currentActiveTab ? currentActiveTab.getAttribute('href') : null;
      const nextHref = '#' + hash;

      if (currentHref === nextHref && targetPane.classList.contains('active')) {
        const recognitionCard = document.querySelector('.recognition-card');
        if (recognitionCard) recognitionCard.classList.remove('recognition-tabs-pending');
        saveRecognitionTab(hash);
        return;
      }

      switchRecognitionTab(targetTab, isInitialLoad);
      saveRecognitionTab(hash);
    } else if (retryCount < maxRetries) {
      retryCount++;
      console.log('[Recognition Tab] DOM not ready, retrying... (' + retryCount + '/' + maxRetries + ')');
      setTimeout(attemptActivate, 100);
    } else {
      console.warn('[Recognition Tab] Failed to find tab after', maxRetries, 'retries');
    }
  }

  attemptActivate();
}

function initRecognitionTabClickHandlers() {
  document.querySelectorAll('#recognition-tabs .nav-link').forEach(function(tabLink) {
    tabLink.removeAttribute('data-toggle');

    if (tabLink.dataset.recognitionTabBound === '1') return;
    tabLink.dataset.recognitionTabBound = '1';
    tabLink.addEventListener('click', function(event) {
      event.preventDefault();
      event.stopImmediatePropagation();

      const href = tabLink.getAttribute('href') || '';
      const tabId = href.startsWith('#') ? href.slice(1) : '';
      if (!tabId) return;

      saveRecognitionTab(tabId);
      switchRecognitionTab(tabLink, false);
    }, true);
  });

  if (recognitionTabClickHandlersBound) return;
  recognitionTabClickHandlersBound = true;

  document.addEventListener('click', function(e) {
    const tabLink = e.target.closest('#recognition-tabs .nav-link');
    if (!tabLink) return;

    e.preventDefault();
    e.stopPropagation();

    const href = tabLink.getAttribute('href');
    if (href && href.startsWith('#')) {
      const tabId = href.replace('#', '');
      saveRecognitionTab(tabId);
      if (window.location.hash !== href) {
        const baseUrl = window.location.pathname + window.location.search;
        window.history.replaceState({}, '', baseUrl + '#' + tabId);
      }
    }

    switchRecognitionTab(tabLink, false);
  }, true);
}

function bindRecognitionPageEvents() {
  if (recognitionPageBindingsInitialized) return;
  recognitionPageBindingsInitialized = true;

  window.addEventListener('pageshow', function(event) {
    if (event.persisted || document.visibilityState === 'visible') {
      console.log('[Recognition Tab] Page show event fired, restoring tab...');
      closeAllRecognitionModals();
      activateRecognitionTabFromHash(true);
      resetRecognitionState();
      loadRecognitionFeed();
      loadBadges();
      loadAwardHistory();
      loadRewards();
      loadRewardRedemptions();
      loadEmployeeBadges();
      loadEmployeeOfTheMonthCandidates();
      bindNominationFormEvents();
    }
  });

    if (!window.__employeeMonthDeleteBound) {
      window.__employeeMonthDeleteBound = true;
      document.addEventListener('click', function (event) {
        const deleteButton = event.target.closest('.delete-nomination');
        if (!deleteButton) return;
        if (!window.confirm('Delete this nomination?')) return;

        const awardHistoryId = deleteButton.dataset.awardHistoryId;
        deleteButton.disabled = true;
        fetch(recognitionApiBase + '?resource=recognition&action=delete_employee_month', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ award_history_id: awardHistoryId })
        })
          .then(response => response.json().then(data => ({ response, data })))
          .then(({ response, data }) => {
            if (!response.ok || !data.success) throw new Error(data.error || data.message || 'Unable to delete nomination.');
            deleteButton.closest('.candidate-item')?.remove();
          })
          .catch(error => {
            deleteButton.disabled = false;
            alert(error.message);
          });
      });
    }

  window.addEventListener('hashchange', function() {
    activateRecognitionTabFromHash(false);
  });

  window.addEventListener('popstate', function() {
    activateRecognitionTabFromHash(false);
  });

  window.addEventListener('page:loaded', function(e) {
    if (!e.detail || e.detail.page !== 'recognition') {
      recognitionPageInitialized = false;
      stopRecognitionAutoRefresh();
      return;
    }

    console.log('[Recognition Tab] Page loaded event fired');
    closeAllRecognitionModals();
    resetRecognitionState();
    resetRecognitionTabObserver();
    initRecognitionTabClickHandlers();
    activateRecognitionTabFromHash(true);
    console.log('[Recognition Tab] Tab restoration complete after page load');
    loadRecognitionFeed();
    loadBadges();
    loadAwardHistory();
    loadRewards();
    loadRewardRedemptions();
    loadEmployeeBadges();
    loadEmployeeOfTheMonthCandidates();
    bindNominationFormEvents();
  });

  const form = document.querySelector('#sendRecognitionModal form');
  const sel = document.getElementById('rec-receiver');
  const nominationSel = document.getElementById('nominate-employee');
  const nominationForm = document.getElementById('nomination-form');
  const sendBtn = document.getElementById('send-recognition-btn');
  const recognitionModal = document.getElementById('sendRecognitionModal');
  const nominationModal = document.getElementById('nominateEmployeeModal');
  const assignBadgeModal = document.getElementById('assignBadgeModal');
  const addRewardModal = document.getElementById('addRewardModal');
  const createBadgeModal = document.getElementById('createBadgeModal');

  const directCloseSelectors = [
    '#nominateEmployeeModal [data-dismiss="modal"]',
    '#assignBadgeModal [data-dismiss="modal"]',
    '#addRewardModal [data-dismiss="modal"]',
    '#createBadgeModal [data-dismiss="modal"]',
    '#sendRecognitionModal [data-dismiss="modal"]'
  ];

  directCloseSelectors.forEach(function(selector) {
    document.querySelectorAll(selector).forEach(function(closeButton) {
      if (closeButton.dataset.recognitionCloseBound === '1') return;
      closeButton.dataset.recognitionCloseBound = '1';
      closeButton.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();

        const modal = closeButton.closest('.modal');
        if (!modal) return;

        if (modal.id === 'nominateEmployeeModal') closeNominationModal();
        else if (modal.id === 'assignBadgeModal') closeAssignBadgeModal();
        else if (modal.id === 'addRewardModal') closeAddRewardModal();
        else if (modal.id === 'createBadgeModal') closeCreateBadgeModal();
        else if (modal.id === 'sendRecognitionModal') closeRecognitionModal();
      }, true);
    });
  });

  if (nominationModal && !nominationModal.dataset.recognitionModalCloseBound) {
    nominationModal.dataset.recognitionModalCloseBound = '1';
    nominationModal.addEventListener('click', function(event) {
      if (event.target === nominationModal || event.target.closest('[data-dismiss="modal"]')) {
        closeNominationModal();
      }
    });
  }

  if (assignBadgeModal && !assignBadgeModal.dataset.recognitionModalCloseBound) {
    assignBadgeModal.dataset.recognitionModalCloseBound = '1';
    assignBadgeModal.addEventListener('click', function(event) {
      if (event.target === assignBadgeModal || event.target.closest('[data-dismiss="modal"]')) {
        closeAssignBadgeModal();
      }
    });
  }

  if (addRewardModal && !addRewardModal.dataset.recognitionModalCloseBound) {
    addRewardModal.dataset.recognitionModalCloseBound = '1';
    addRewardModal.addEventListener('click', function(event) {
      if (event.target === addRewardModal || event.target.closest('[data-dismiss="modal"]')) {
        closeAddRewardModal();
      }
    });
  }

  if (createBadgeModal && !createBadgeModal.dataset.recognitionModalCloseBound) {
    createBadgeModal.dataset.recognitionModalCloseBound = '1';
    createBadgeModal.addEventListener('click', function(event) {
      if (event.target === createBadgeModal || event.target.closest('[data-dismiss="modal"]')) {
        closeCreateBadgeModal();
      }
    });
  }

  document.addEventListener('click', function(event) {
    const openRecognitionModalTrigger = event.target.closest('[data-recognition-open]');
    if (openRecognitionModalTrigger) {
      event.preventDefault();
      event.stopPropagation();
      const modalId = openRecognitionModalTrigger.getAttribute('data-recognition-open');
      if (modalId === 'nominateEmployeeModal') {
        openNominationModal();
        if (nominationSel) nominationSel.focus();
      } else if (modalId === 'assignBadgeModal') {
        setRecognitionModalVisibility('assignBadgeModal', true);
      } else if (modalId === 'addRewardModal') {
        setRecognitionModalVisibility('addRewardModal', true);
      } else if (modalId === 'createBadgeModal') {
        setRecognitionModalVisibility('createBadgeModal', true);
      }
      return;
    }

    const targetModal = event.target.closest('[data-target]');
    if (targetModal) {
      const targetId = targetModal.getAttribute('data-target');
      if (targetId === '#assignBadgeModal' || targetId === '#addRewardModal' || targetId === '#nominateEmployeeModal') {
        event.preventDefault();
        event.stopPropagation();
        const modalKey = targetId.replace('#', '');
        if (modalKey === 'nominateEmployeeModal') {
          openNominationModal();
          if (nominationSel) nominationSel.focus();
        } else {
          setRecognitionModalVisibility(modalKey, true);
        }
        return;
      }
    }

    const closeRecognitionModalTrigger = event.target.closest('[data-recognition-close]');
    if (closeRecognitionModalTrigger) {
      event.preventDefault();
      event.stopPropagation();
      const modalId = closeRecognitionModalTrigger.getAttribute('data-recognition-close');
      if (modalId === 'nominateEmployeeModal') closeNominationModal();
      else if (modalId === 'assignBadgeModal') closeAssignBadgeModal();
      else if (modalId === 'addRewardModal') closeAddRewardModal();
      else if (modalId === 'createBadgeModal') closeCreateBadgeModal();
      else if (modalId === 'sendRecognitionModal') closeRecognitionModal();
      return;
    }

    const rewardModal = document.getElementById('addRewardModal');
    if (rewardModal && (event.target === rewardModal || event.target.closest('#addRewardModal [data-dismiss="modal"]'))) {
      event.preventDefault();
      closeAddRewardModal();
      return;
    }

    const assignModal = document.getElementById('assignBadgeModal');
    if (assignModal && (event.target === assignModal || event.target.closest('#assignBadgeModal [data-dismiss="modal"]'))) {
      event.preventDefault();
      closeAssignBadgeModal();
      return;
    }
  }, true);

  bindNominationFormEvents();

  document.addEventListener('click', function(event) {
    const closeButton = event.target.closest('#sendRecognitionModal [data-dismiss="modal"]');
    const modal = document.getElementById('sendRecognitionModal');
    if (closeButton || (modal && event.target === modal)) {
      event.preventDefault();
      closeRecognitionModal();
    }
  });

  document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    if (recognitionModal && recognitionModal.classList.contains('show')) closeRecognitionModal();
    if (nominationModal && nominationModal.classList.contains('show')) closeNominationModal();
    if (assignBadgeModal && assignBadgeModal.classList.contains('show')) closeAssignBadgeModal();
    const currentAddRewardModal = document.getElementById('addRewardModal');
    if (currentAddRewardModal && currentAddRewardModal.classList.contains('show')) closeAddRewardModal();
  });

  if (form) {
    const oldInput = form.querySelector('input[name="receiver_id"]');
    if (oldInput) oldInput.remove();
  }

  const employeeListApi = window.location.pathname.indexOf('/pages/') !== -1
    ? '../api/index.php?resource=employee_list'
    : 'api/index.php?resource=employee_list';

  fetch(employeeListApi)
    .then(res => res.json())
    .then(list => {
      list.forEach(emp => {
        [sel, nominationSel].forEach(select => {
          if (!select) return;
          if (select === nominationSel && String(emp.employee_id) === String(nominationModal?.dataset.currentEmployeeId || '')) return;
          const opt = document.createElement('option');
          opt.value = emp.employee_id;
          opt.setAttribute('data-employee-id', emp.employee_id);
          opt.textContent = emp.full_name + ' (' + emp.employee_id + ')';
          select.appendChild(opt);
        });
      });
      if (sel) sel.addEventListener('change', function() {
        if (sendBtn) sendBtn.disabled = !sel.value;
      });
    }).catch(err => {
      console.error('Failed to load employee list', err);
      if (sel) {
        sel.innerHTML = '<option value="">Unable to load employees</option>';
      }
      if (nominationSel) {
        nominationSel.innerHTML = '<option value="">Select employee</option>';
        populateNominationEmployeesFromFeed();
      }
    });

  if (form && sel && sendBtn) {
    form.addEventListener('submit', function(e) {
      if (!sel.value) {
        e.preventDefault();
        sendBtn.disabled = true;
        alert('Please select a receiver.');
      }
    });
  }

  window.employeeMonthVotes = window.employeeMonthVotes || [];

  const sendForm = document.querySelector('#sendRecognitionModal form');
  if (sendForm) {
    sendForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = sendForm.querySelector('button[type=submit]');
      if (btn) { btn.textContent = 'Sending...'; btn.disabled = true; }

      const receiverEl = document.getElementById('rec-receiver');
      const receiverId = receiverEl ? receiverEl.value : null;
      const message = document.getElementById('rec-message') ? document.getElementById('rec-message').value.trim() : '';
      const points = document.getElementById('rec-points') ? parseInt(document.getElementById('rec-points').value, 10) : 10;

      if (!receiverId) {
        alert('Please select a recipient before sending recognition.');
        if (btn) { btn.textContent = 'Send Recognition'; btn.disabled = false; }
        return;
      }

      fetch(recognitionApiBase + '?resource=recognition&action=send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ receiver_id: receiverId, message: message, points: points })
      })
      .then(async response => {
        const payloadText = await response.text();
        let payload;
        try {
          payload = JSON.parse(payloadText);
        } catch (jsonErr) {
          throw new Error('Invalid server response: ' + payloadText);
        }

        if (!response.ok) {
          throw new Error(payload.error || 'Server returned status ' + response.status);
        }

        return payload;
      })
      .then(res => {
        if (res && (res.id || res.success)) {
          if (window.jQuery && jQuery.fn && jQuery.fn.modal) {
            jQuery('#sendRecognitionModal').modal('hide');
          } else {
            closeRecognitionModal();
          }
          if (document.getElementById('rec-message')) document.getElementById('rec-message').value = '';
          if (document.getElementById('rec-points')) document.getElementById('rec-points').value = 10;
          refreshAllRecognitionSections();
          alert('Recognition sent successfully.');
        } else if (res && res.error) {
          alert('Error: ' + res.error);
        } else {
          throw new Error('Unexpected response from server');
        }
      })
      .catch(err => {
        console.error('Failed to send recognition', err);
        alert('Failed to send recognition. ' + err.message);
      })
      .finally(() => {
        if (btn) { btn.textContent = 'Send Recognition'; btn.disabled = false; }
      });
    });
  }

  const employeeMonthFilterForm = document.getElementById('employee-month-filter-form');
  if (employeeMonthFilterForm) {
    employeeMonthFilterForm.addEventListener('submit', function(e) {
      e.preventDefault();
      loadEmployeeOfTheMonthCandidates();
    });
  }

  const assignBadgeForm = document.getElementById('assign-badge-form');
  if (assignBadgeForm) {
    assignBadgeForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = assignBadgeForm.querySelector('button[type=submit]');
      if (btn) { btn.textContent = 'Assigning...'; btn.disabled = true; }

      const employeeId = document.getElementById('badge_employee_id') ? document.getElementById('badge_employee_id').value : null;
      const badgeId = document.getElementById('badge_id') ? document.getElementById('badge_id').value : null;

      fetch(recognitionApiBase + '?resource=recognition&action=assign_badge', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ employee_id: employeeId, badge_id: badgeId })
      }).then(r => r.json())
        .then(res => {
          if (res && res.success) {
            if (window.jQuery && jQuery.fn && jQuery.fn.modal) {
              jQuery('#assignBadgeModal').modal('hide');
            } else if (assignBadgeModal) {
              assignBadgeModal.classList.remove('show');
              assignBadgeModal.setAttribute('aria-hidden', 'true');
              document.body.classList.remove('modal-open');
            }
            assignBadgeForm.reset();
            refreshAllRecognitionSections();
            alert('Badge assigned successfully.');
          } else if (res && res.error) {
            alert('Error: ' + res.error);
          }
        }).catch(err => {
          console.error('Failed to assign badge', err);
          alert('Failed to assign badge.');
        }).finally(() => {
          if (btn) { btn.textContent = 'Assign Badge'; btn.disabled = false; }
        });
    });
  }

  const createBadgeForm = document.getElementById('create-badge-form');
  if (createBadgeForm) {
    createBadgeForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = createBadgeForm.querySelector('button[type=submit]');
      if (btn) { btn.textContent = 'Creating...'; btn.disabled = true; }

      const formData = new FormData(createBadgeForm);
      const payload = Object.fromEntries(formData.entries());
      fetch(recognitionApiBase + '?resource=badge&action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }).then(r => r.json().then(data => ({ response: r, data })))
        .then(({ response, data }) => {
          if (!response.ok || !data.success) throw new Error(data.error || 'Failed to create badge.');
          if (window.jQuery && jQuery.fn && jQuery.fn.modal) {
            jQuery('#createBadgeModal').modal('hide');
          }
          createBadgeForm.reset();
          const category = document.getElementById('create-badge-category');
          if (category) category.value = 'achievement';
          loadBadges();
          alert('Badge created successfully.');
        })
        .catch(error => alert(error.message))
        .finally(() => {
          if (btn) { btn.textContent = 'Create Badge'; btn.disabled = false; }
        });
    });
  }

  document.addEventListener('submit', function(e) {
    if (e.target && e.target.matches('.vote-form')) {
      e.preventDefault();
      const form = e.target;
      const awardHistoryId = form.querySelector('input[name="award_history_id"]') ? form.querySelector('input[name="award_history_id"]').value : null;
      const btn = form.querySelector('button[type=submit]');
      let voteSucceeded = false;
      if (btn) { btn.textContent = 'Voting...'; btn.disabled = true; }

      fetch(recognitionApiBase + '?resource=recognition&action=vote_employee_month', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ award_history_id: awardHistoryId })
      }).then(r => r.json())
        .then(res => {
          if (res && res.success) {
            voteSucceeded = true;
            window.employeeMonthVotes.push(awardHistoryId);
            refreshAllRecognitionSections();
            alert('Vote recorded successfully.');
          } else if (res && res.error) {
            alert('Error: ' + res.error);
          }
        }).catch(err => {
          console.error('Failed to record vote', err);
          alert('Failed to record vote.');
        }).finally(() => {
          if (btn && voteSucceeded) { btn.textContent = 'Voted (+5)'; btn.disabled = true; }
          if (btn && !voteSucceeded) { btn.textContent = 'Vote +5'; btn.disabled = false; }
        });
    }
  });
}

function initializeRecognitionPage() {
  if (recognitionPageInitialized) return;
  recognitionPageInitialized = true;

  console.log('[Recognition Tab] Initializing recognition page');
  resetRecognitionState();
  initRecognitionTabClickHandlers();
  bindRecognitionPageEvents();
  activateRecognitionTabFromHash(true);
  startRecognitionTabGuard();
  console.log('[Recognition Tab] Initialization complete');

  loadRecognitionFeed();
  loadBadges();
  loadAwardHistory();
  loadRewards();
  loadRewardRedemptions();
  loadEmployeeBadges();
  loadEmployeeOfTheMonthCandidates();
  startRecognitionAutoRefresh();
}

document.addEventListener('DOMContentLoaded', initializeRecognitionPage);

let recognitionRefreshTimer = null;

function getStoredPaginationPage(storageKey, fallbackPage) {
  try {
    const value = parseInt(localStorage.getItem(storageKey), 10);
    return Number.isFinite(value) && value >= 0 ? value : fallbackPage;
  } catch (err) {
    return fallbackPage;
  }
}

function setStoredPaginationPage(storageKey, pageIndex) {
  try {
    localStorage.setItem(storageKey, String(Math.max(0, pageIndex)));
  } catch (err) {
    console.warn('Unable to save recognition pagination state', err);
  }
}

function resetRecognitionState() {
  recommendationPageIndex = getStoredPaginationPage('recognition_recommendation_page', 0);
  recommendationData = [];
  employeesWithoutReportsPageIndex = getStoredPaginationPage('recognition_employees_without_reports_page', 0);
  employeesWithoutReportsData = [];
  topPerformersPageIndex = getStoredPaginationPage('recognition_top_performers_page', 0);
  topPerformersData = [];
}

function stopRecognitionAutoRefresh() {
  if (recognitionRefreshTimer) {
    clearInterval(recognitionRefreshTimer);
    recognitionRefreshTimer = null;
  }
}

function startRecognitionAutoRefresh(intervalMs) {
  stopRecognitionAutoRefresh();
  const refreshInterval = intervalMs || 5000;
  recognitionRefreshTimer = setInterval(function() {
    if (!document.getElementById('employee-month') && !document.getElementById('recognition-tabs')) {
      stopRecognitionAutoRefresh();
      return;
    }
    refreshAllRecognitionSections();
  }, refreshInterval);
}

function refreshAllRecognitionSections() {
  loadRecognitionFeed();
  loadBadges();
  loadAwardHistory();
  loadRewards();
  loadRewardRedemptions();
  loadEmployeeBadges();
  loadEmployeeOfTheMonthCandidates();
}

(function injectHighlightStyle(){
  var css = '.new-recognition{box-shadow:0 0 0 4px rgba(76,175,80,0.12) inset; animation: rrHighlight 2s ease;} @keyframes rrHighlight{0%{background:#e9fff0}100%{background:transparent}}';
  var head = document.head || document.getElementsByTagName('head')[0];
  var style = document.createElement('style');
  style.type = 'text/css';
  style.appendChild(document.createTextNode(css));
  head.appendChild(style);
})();

function escapeHtml(text) {
  var map = {
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  };
  return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function findCardBodyByTitle(title) {
  var cards = document.querySelectorAll('.card');
  for (var i = 0; i < cards.length; i++) {
    var header = cards[i].querySelector('.card-title');
    if (!header) continue;
    if (header.textContent && header.textContent.trim().indexOf(title) !== -1) {
      return cards[i].querySelector('.card-body');
    }
  }
  return null;
}

function renderTopPerformers(container, items) {
  if (!container) return;
  container.innerHTML = '';
  if (!items || !items.length) {
    container.innerHTML = '<p class="text-muted text-center m-2">No top performers found.</p>';
    return;
  }
  var ul = document.createElement('ul');
  ul.className = 'list-group list-group-flush';
  items.forEach(function(tp){
    var li = document.createElement('li');
    li.className = 'list-group-item recognition-summary-item';
    var score = (tp.final_rating_percent !== undefined) ? tp.final_rating_percent : (tp.performance_score !== undefined ? tp.performance_score : null);
    var formattedScore = typeof score === 'number' ? Number(score.toFixed(1)) : score;
    var left = '<div class="recognition-summary-content"><strong>' + escapeHtml(tp.employee_name || tp.employee_id) + '</strong>' +
      '<div class="recognition-summary-meta">Score: ' + (score !== null ? escapeHtml(formattedScore) + '%' : 'N/A') + ' <span aria-hidden="true">•</span> Grade: ' + escapeHtml(tp.final_grade || 'N/A') + '</div></div>';
    var right = '<span class="badge badge-success recognition-score-badge">+' + (score !== null ? escapeHtml(formattedScore) + '%' : 'N/A') + '</span>';
    li.innerHTML = left + right;
    ul.appendChild(li);
  });
  container.appendChild(ul);
}

// Handle quick recognize clicks (show informational modal)
document.addEventListener('click', function(e){
  var target = e.target && e.target.closest ? e.target.closest('.recommend-recognize') : null;
  if (target) {
    e.preventDefault();
    var employeeId = target.getAttribute('data-employee-id');
    var employeeName = target.getAttribute('data-employee-name') || 'Employee';
    var btn = document.getElementById('send-recognition-btn');
    // Set the receiver select to the recommended employee if available
    const sel = document.getElementById('rec-receiver');
    if (sel) {
      // Try to find option by data-employee-id first, otherwise by value
      let found = Array.from(sel.options).find(o => o.getAttribute('data-employee-id') === employeeId);
      if (!found) found = Array.from(sel.options).find(o => o.value === employeeId || o.value === (employeeId + ''));
      if (!found && employeeId) {
        sel.innerHTML = '';
        found = document.createElement('option');
        found.value = employeeId;
        found.setAttribute('data-employee-id', employeeId);
        found.textContent = employeeName + ' (' + employeeId + ')';
        sel.appendChild(found);
      }
      if (found) {
        sel.value = found.value;
        if (btn) btn.disabled = false;
      }
    }
    if (window.jQuery && jQuery.fn && jQuery.fn.modal) {
      jQuery('#sendRecognitionModal').modal('show');
    } else {
      openRecognitionModal();
    }
  }
});

function loadRecognitionFeed() {
  fetch(recognitionApiBase + '?resource=recognition&action=list&t=' + Date.now())
    .then(r => r.json())
    .then(res => {
      const feed = document.getElementById('recognition-feed');
      if (!feed) return;
      feed.innerHTML = '';
      if (res && res.data && res.data.length) {
        res.data.forEach(r => {
          const item = document.createElement('div');
          item.className = 'list-group-item recognition-entry';
          item.dataset.senderId = r.sender_id || '';
          item.dataset.senderName = r.sender_name || '';
          item.dataset.receiverId = r.receiver_id || '';
          item.dataset.receiverName = r.receiver_name || '';
          // store recognition id for change detection
          item.dataset.recognitionId = r.id || r.eer_recognition_id || '';
          item.innerHTML = `
            <div class="recognition-entry-header">
              <div class="recognition-entry-people">
                <strong>${escapeHtml(r.sender_name || r.sender_id)}</strong>
                <span class="recognition-entry-arrow" aria-hidden="true">&rarr;</span>
                <strong>${escapeHtml(r.receiver_name || r.receiver_id)}</strong>
              </div>
              <span class="badge badge-success recognition-entry-points">+${escapeHtml(r.points || 0)} pts</span>
            </div>
            <p class="recognition-entry-message">${escapeHtml(r.message || 'No message provided.')}</p>
            <small class="text-muted recognition-entry-time"><i class="fas fa-clock mr-1"></i>${escapeHtml(r.created_at || '')}</small>
          `;
          feed.appendChild(item);
        });
        populateNominationEmployeesFromFeed();

        // highlight newest item if it's new since last load
        try {
          const newFirstId = res.data[0] && (res.data[0].id || res.data[0].eer_recognition_id) ? (res.data[0].id || res.data[0].eer_recognition_id) : null;
          if (typeof lastSeenRecognitionId !== 'undefined' && lastSeenRecognitionId !== null && newFirstId && newFirstId != lastSeenRecognitionId) {
            const newEl = feed.querySelector('[data-recognition-id="' + newFirstId + '"]');
            if (newEl) {
              newEl.style.transition = 'background-color 0.4s ease';
              newEl.style.backgroundColor = '#e6ffed';
              setTimeout(() => { newEl.style.backgroundColor = ''; }, 3000);
            }
          }
          // update last seen id
          window.lastSeenRecognitionId = res.data[0] && (res.data[0].id || res.data[0].eer_recognition_id) ? (res.data[0].id || res.data[0].eer_recognition_id) : window.lastSeenRecognitionId;
        } catch (err) {
          console.error('Error detecting new recognition', err);
        }
      } else {
        feed.innerHTML = '<div class="text-muted">No recognition found.</div>';
      }
    });
}

// Initialize tracking variable
window.lastSeenRecognitionId = window.lastSeenRecognitionId || null;

function loadBadges() {
  fetch(recognitionApiBase + '?resource=badge&t=' + Date.now(), { cache: 'no-store' })
    .then(r => r.json())
    .then(res => {
      const feed = document.getElementById('badges-feed');
      const badgeSelect = document.getElementById('badge_id');
      if (!feed) return;
      feed.innerHTML = '';
      if (badgeSelect) badgeSelect.innerHTML = '<option value="">Select badge</option>';
      if (res && res.data && res.data.length) {
        res.data.forEach(b => {
          const item = document.createElement('div');
          item.className = 'list-group-item';
          item.innerHTML = `<b>${escapeHtml(b.name)}</b><br><span>${escapeHtml(b.description)}</span>`;
          feed.appendChild(item);
          if (badgeSelect) {
            const option = document.createElement('option');
            option.value = b.eer_badge_id || b.id;
            option.textContent = b.name;
            badgeSelect.appendChild(option);
          }
        });
      } else {
        feed.innerHTML = '<div class="text-muted">No badges found.</div>';
      }
    });
}

function loadAwardHistory() {
  fetch('../api/index.php?resource=award_history')
    .then(r => r.json())
    .then(res => {
      const feed = document.getElementById('award-history-feed');
      if (!feed) return;
      feed.innerHTML = '';
      if (res && res.data && res.data.length) {
        res.data.forEach(a => {
          const item = document.createElement('div');
          item.className = 'list-group-item';
          item.innerHTML = `<b>${escapeHtml(a.award_name || '')}</b> to <b>${escapeHtml(a.employee_name || a.employee_id)}</b> <small class='text-muted float-right'>${escapeHtml(a.awarded_at || '')}</small>`;
          feed.appendChild(item);
        });
      } else {
        feed.innerHTML = '<div class="text-muted">No award history found.</div>';
      }
    });
}

function loadRewards() {
  fetch(recognitionApiBase + '?resource=reward')
    .then(r => r.json())
    .then(res => {
      const feed = document.getElementById('rewards-feed');
      if (!feed) return;
      feed.innerHTML = '';
      if (res && res.data && res.data.length) {
        res.data.forEach(rw => {
          const item = document.createElement('div');
          item.className = 'list-group-item';
          item.innerHTML = `<b>${escapeHtml(rw.name)}</b> <span class='badge badge-info ml-2'>${rw.points_required} pts</span><br><span>${escapeHtml(rw.description || '')}</span>`;
          feed.appendChild(item);
        });
      } else {
        feed.innerHTML = '<div class="text-muted">No rewards found.</div>';
      }
    });
}

function loadRewardRedemptions() {
  fetch(recognitionApiBase + '?resource=reward_redemption')
    .then(r => r.json())
    .then(res => {
      const feed = document.getElementById('reward-redemptions-feed');
      if (!feed) return;
      feed.innerHTML = '';
      if (res && res.data && res.data.length) {
        res.data.forEach(rr => {
          const item = document.createElement('div');
          item.className = 'list-group-item';
          item.innerHTML = `<b>${escapeHtml(rr.employee_name || rr.employee_id)}</b> redeemed <b>${escapeHtml(rr.reward_name || rr.reward_id)}</b> <small class='text-muted float-right'>${escapeHtml(rr.redeemed_at || '')}</small>`;
          feed.appendChild(item);
        });
      } else {
        feed.innerHTML = '<div class="text-muted">No reward redemptions found.</div>';
      }
    });
}

const recommendationPageSize = 2;
let recommendationPageIndex = 0;
let recommendationData = [];
const employeesWithoutReportsPageSize = 2;
let employeesWithoutReportsPageIndex = 0;
let employeesWithoutReportsData = [];
const topPerformersPageSize = 2;
let topPerformersPageIndex = 0;
let topPerformersData = [];

function renderPagination(container, pageIndex, totalPages, onPrev, onNext, onPage) {
  container.innerHTML = '';
  const nav = document.createElement('nav');
  nav.setAttribute('aria-label', 'Pagination');

  const ul = document.createElement('ul');
  ul.className = 'pagination pagination-sm justify-content-center mb-0';

  const prevLi = document.createElement('li');
  prevLi.className = 'page-item' + (pageIndex <= 0 ? ' disabled' : '');
  const prevLink = document.createElement('button');
  prevLink.type = 'button';
  prevLink.className = 'page-link';
  prevLink.textContent = 'Previous';
  prevLink.disabled = pageIndex <= 0;
  prevLink.addEventListener('click', onPrev);
  prevLi.appendChild(prevLink);
  ul.appendChild(prevLi);

  const batchSize = 3;
  const batchStart = Math.floor(pageIndex / batchSize) * batchSize;
  const batchEnd = Math.min(totalPages, batchStart + batchSize);

  if (batchStart > 0) {
    const ellipsisLi = document.createElement('li');
    ellipsisLi.className = 'page-item disabled';
    const ellipsisSpan = document.createElement('span');
    ellipsisSpan.className = 'page-link';
    ellipsisSpan.textContent = '...';
    ellipsisLi.appendChild(ellipsisSpan);
    ul.appendChild(ellipsisLi);
  }

  for (let i = batchStart; i < batchEnd; i++) {
    const pageLi = document.createElement('li');
    pageLi.className = 'page-item' + (i === pageIndex ? ' active' : '');
    const pageLink = document.createElement('button');
    pageLink.type = 'button';
    pageLink.className = 'page-link';
    pageLink.textContent = (i + 1).toString();
    pageLink.disabled = i === pageIndex;
    pageLink.addEventListener('click', function() {
      onPage(i);
    });
    pageLi.appendChild(pageLink);
    ul.appendChild(pageLi);
  }

  if (batchEnd < totalPages) {
    const ellipsisLi = document.createElement('li');
    ellipsisLi.className = 'page-item disabled';
    const ellipsisSpan = document.createElement('span');
    ellipsisSpan.className = 'page-link';
    ellipsisSpan.textContent = '...';
    ellipsisLi.appendChild(ellipsisSpan);
    ul.appendChild(ellipsisLi);
  }

  const nextLi = document.createElement('li');
  nextLi.className = 'page-item' + (pageIndex >= totalPages - 1 ? ' disabled' : '');
  const nextLink = document.createElement('button');
  nextLink.type = 'button';
  nextLink.className = 'page-link';
  nextLink.textContent = 'Next';
  nextLink.disabled = pageIndex >= totalPages - 1;
  nextLink.addEventListener('click', onNext);
  nextLi.appendChild(nextLink);
  ul.appendChild(nextLi);

  nav.appendChild(ul);
  container.appendChild(nav);
}

function buildNumberedList(data, pageIndex, pageSize) {
  const startIndex = pageIndex * pageSize;
  const list = document.createElement('ul');
  list.className = 'list-group list-group-flush';
  data.slice(startIndex, startIndex + pageSize).forEach(function(item, idx) {
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between align-items-center';
    li.style.borderRadius = '12px';
    li.style.marginBottom = '.5rem';
    const number = startIndex + idx + 1;
    li.innerHTML = `
      <div class="numbered-list-item">
        <div class="numbered-item-index">${number}</div>
        <div class="numbered-item-content">${item.html}</div>
      </div>
    `;
    list.appendChild(li);
  });
  return list;
}

function loadEmployeeOfTheMonthCandidates() {
  const requestId = ++employeeMonthCandidatesRequestId;
  const monthSelect = document.getElementById('employee-of-month-month');
  const yearSelect = document.getElementById('employee-of-month-year');
  const month = monthSelect ? monthSelect.value : new Date().getMonth() + 1;
  const year = yearSelect ? yearSelect.value : new Date().getFullYear();

  return fetch(`${recognitionApiBase}?resource=recognition&action=employee_of_month&month=${encodeURIComponent(month)}&year=${encodeURIComponent(year)}&t=${Date.now()}`, {
    cache: 'no-store'
  })
    .then(r => r.json())
    .then(res => {
      if (requestId !== employeeMonthCandidatesRequestId) return;
      const container = document.getElementById('employee-month-candidates-list');
      if (!container) return;
      if (!res || !res.data || !res.data.length) {
        container.innerHTML = '<p class="text-muted text-center">No nominations yet.</p>';
        appendPendingNomination();
        populateBadgeEmployeesFromNominations();
        return;
      }

      container.innerHTML = '';
      res.data.forEach(function(candidate) {
        const hasVoted = Boolean(candidate.has_voted);
        const statusBadge = candidate.status === 'winner' ? 'badge-success' : (candidate.status === 'shortlisted' ? 'badge-info' : 'badge-warning');
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-start candidate-item';
        item.dataset.employeeId = candidate.employee_id || '';
        item.dataset.awardHistoryId = candidate.eer_award_history_id || '';
        const voteButton = candidate.eer_award_history_id && !hasVoted
          ? '<form method="POST" action="" class="mt-2 vote-form"><input type="hidden" name="action" value="vote_employee_month"><input type="hidden" name="award_history_id" value="' + escapeHtml(candidate.eer_award_history_id) + '"><button type="submit" class="btn btn-sm btn-outline-warning"><i class="fas fa-vote-yea"></i> Vote +5</button></form>'
          : (candidate.eer_award_history_id ? '' : '<button type="button" class="btn btn-sm btn-outline-secondary mt-2" disabled>Not Nominated</button>');
        item.innerHTML = `<div><strong>${escapeHtml(candidate.employee_name || candidate.employee_id)}</strong><br><small class="text-muted">Department: ${escapeHtml(candidate.department || 'N/A')} • Votes: ${escapeHtml(candidate.votes || 0)} • Performance: ${escapeHtml(candidate.performance_score || 0)}%</small><div class="mt-1"><span class="badge ${statusBadge}">${escapeHtml((candidate.status || 'nominated').charAt(0).toUpperCase() + (candidate.status || 'nominated').slice(1))}</span>${candidate.nomination_reason ? ' <span class="text-muted small">Reason: ' + escapeHtml(candidate.nomination_reason) + '</span>' : ''}</div></div><div class="text-right"><span class="badge badge-info">Recognition: ${escapeHtml(candidate.recognition_total || 0)} pts</span>${voteButton}</div>`;
        container.appendChild(item);
      });
      appendPendingNomination();
      populateBadgeEmployeesFromNominations();
    });
}

function loadEmployeeBadges() {
  fetch(recognitionApiBase + '?resource=employee_badge&t=' + Date.now(), { cache: 'no-store' })
    .then(r => r.json())
    .then(res => {
      const feed = document.getElementById('employee-badges-feed');
      if (!feed) return;
      feed.innerHTML = '';
      if (res && res.data && res.data.length) {
        res.data.forEach(eb => {
          const item = document.createElement('div');
          item.className = 'list-group-item';
          item.innerHTML = `<b>${escapeHtml(eb.employee_name || eb.employee_id)}</b> earned <b>${escapeHtml(eb.badge_name || eb.badge_id)}</b> <small class='text-muted float-right'>${escapeHtml(eb.earned_at || '')}</small>`;
          feed.appendChild(item);
        });
      } else {
        feed.innerHTML = '<div class="text-muted">No employee badges found.</div>';
      }
    });
}
