let recognitionPageInitialized = false;
let recognitionPageBindingsInitialized = false;
let recognitionTabClickHandlersBound = false;
let recognitionTabObserver = null;
let recognitionTabGuardTimer = null;
let employeeMonthCandidatesRequestId = 0;
let pendingNomination = null;
const recognitionApiBase = window.location.pathname.split('/modules/engagement/')[0]
  + '/modules/engagement/api/index.php';
const recognitionRewardApi = recognitionApiBase.replace('/index.php', '/reward.php');

function setRecognitionModalVisibility(modalId, shouldOpen) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  modal.classList.toggle('show', shouldOpen);
  modal.classList.toggle('d-block', shouldOpen);
  modal.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
  modal.setAttribute('aria-modal', shouldOpen ? 'true' : 'false');
  modal.style.display = shouldOpen ? (modalId === 'editRewardModal' ? 'flex' : 'block') : 'none';

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

function openRecognitionModal(recommendedEmployee = null) {
  loadRecognitionEmployees(true).then(function (employees) {
    const receiverIdInput = document.getElementById('rec-receiver');
    const receiverNameInput = document.getElementById('rec-receiver-name');
    const matchingEmployeeId = recommendedEmployee
      ? resolveRecommendationEmployeeId(recommendedEmployee, employees)
      : '';
    const matchingEmployee = employees.find(function (employee) {
      return String(employee.employee_id) === matchingEmployeeId;
    });
    if (receiverIdInput) receiverIdInput.value = matchingEmployeeId;
    if (receiverNameInput) {
      const matchedName = matchingEmployee
        ? ([matchingEmployee.first_name, matchingEmployee.middle_name, matchingEmployee.last_name].filter(Boolean).join(' ')
          || matchingEmployee.full_name || matchingEmployee.employee_name || '')
        : '';
      receiverNameInput.value = matchingEmployeeId
        ? matchedName
        : (recommendedEmployee ? recommendedEmployee.employee_name || '' : '');
    }
    const sendButton = document.getElementById('send-recognition-btn');
    if (sendButton) sendButton.disabled = !matchingEmployeeId;
    setRecognitionModalVisibility('sendRecognitionModal', true);
  }).catch(function (error) {
    console.warn('[Recognition] Could not load employees for the recipient list.', error);
    const sendButton = document.getElementById('send-recognition-btn');
    if (sendButton) sendButton.disabled = true;
    setRecognitionModalVisibility('sendRecognitionModal', true);
  });
}

function closeNominationModal() {
  setRecognitionModalVisibility('nominateEmployeeModal', false);
}

function closeAssignBadgeModal() {
  setRecognitionModalVisibility('assignBadgeModal', false);
}

function closeAddRewardModal() {
  const modal = document.getElementById('addRewardModal');
  setRecognitionModalVisibility('addRewardModal', false);
  if (modal) {
    modal.classList.remove('show', 'd-block');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  }
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
  const existingItem = Array.from(container.querySelectorAll('.candidate-item')).find(function(item) {
    return String(item.dataset.employeeId || '') === String(pendingNomination.employeeId);
  });
  if (existingItem) {
    existingItem.classList.add('recognition-pending-nomination');
    renderPendingNominationItem(existingItem);
    return;
  }
  const emptyMessage = container.querySelector('.text-center.text-muted');
  if (emptyMessage) emptyMessage.remove();
  const item = document.createElement('div');
  item.className = 'list-group-item candidate-item recognition-pending-nomination';
  item.dataset.employeeId = pendingNomination.employeeId;
  item.dataset.awardHistoryId = pendingNomination.awardHistoryId || '';
  renderPendingNominationItem(item);
  container.appendChild(item);
}

function renderPendingNominationItem(item) {
  const hasAwardId = Boolean(pendingNomination.awardHistoryId);
  const voteButton = '<button type="button" class="btn btn-sm btn-outline-warning mt-2"' + (hasAwardId ? '' : ' disabled') + '><i class="fas fa-vote-yea"></i> Vote +5</button>';
  item.innerHTML = '<div><strong>' + escapeHtml(pendingNomination.employeeName) + '</strong><br>'
    + '<small class="text-muted">Department: N/A • Votes: 0 • Performance: 0%</small>'
    + '<div class="mt-1"><span class="badge badge-warning">Nominated</span>'
    + ' <span class="text-muted small">Reason: ' + escapeHtml(pendingNomination.reason) + '</span></div></div>'
    + '<div class="text-right candidate-actions">'
    + voteButton + '</div>';
}

function populateBadgeEmployeesFromNominations() {
  const select = document.getElementById('badge_employee_id');
  if (!select) return;
  const selectedEmployeeId = select.value;
  select.innerHTML = '<option value="">Select employee</option>';

  const approvedEmployees = Array.isArray(window.approvedMonthlyRecognitionEmployees)
    ? window.approvedMonthlyRecognitionEmployees
    : [];
  if (approvedEmployees.length) {
    const approvedGroup = document.createElement('optgroup');
    approvedGroup.label = 'Approved Employee of the Month';
    approvedEmployees.forEach(function(employee) {
      const employeeId = employee.employee_id;
      if (!employeeId) return;
      const option = document.createElement('option');
      option.value = employeeId;
      option.textContent = employee.employee_name || employee.full_name || employee.employee_id;
      approvedGroup.appendChild(option);
    });
    if (approvedGroup.children.length) {
      select.appendChild(approvedGroup);
    }
  } else {
    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = 'No approved Employee of the Month';
    emptyOption.disabled = true;
    select.appendChild(emptyOption);
  }

  if (selectedEmployeeId) select.value = selectedEmployeeId;
}

function bindNominationFormEvents() {
  const nominationForm = document.getElementById('nomination-form');
  if (!nominationForm || nominationForm.dataset.bound === '1') return;
  nominationForm.dataset.bound = '1';

  nominationForm.addEventListener('submit', function(event) {
    event.preventDefault();
    event.stopImmediatePropagation();
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

    const selectedEmployee = nominationSel ? nominationSel.options[nominationSel.selectedIndex] : null;
    pendingNomination = {
      employeeId: employeeId,
      employeeName: selectedEmployee ? selectedEmployee.textContent.replace(/\s*\(\d+\)\s*$/, '') : employeeId,
      reason: reason,
      awardHistoryId: ''
    };
    nominationForm.reset();
    closeNominationModal();
    appendPendingNomination();
    populateBadgeEmployeesFromNominations();

    fetch(recognitionApiBase + '?resource=award_history&action=create', {
      method: 'POST',
      credentials: 'same-origin',
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
        const pendingItem = document.querySelector('.recognition-pending-nomination');
        if (pendingItem && result.id) pendingItem.dataset.awardHistoryId = result.id;
        if (pendingNomination) {
          pendingNomination.awardHistoryId = result.id || '';
          if (pendingItem) renderPendingNominationItem(pendingItem);
        }
        pendingNomination = null;
        loadEmployeeOfTheMonthCandidates();
      })
      .catch(error => {
        console.error('Failed to save nomination', error);
        const pendingItem = document.querySelector('.recognition-pending-nomination');
        if (pendingItem) pendingItem.remove();
        pendingNomination = null;
        setRecognitionModalVisibility('nominateEmployeeModal', true);
        alert(error.message);
      })
      .finally(() => {
        nominationForm.dataset.submitting = '0';
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.innerHTML = '<i class="fas fa-star mr-1"></i>Submit Nomination';
        }
      });
  }, true);
}

function bindAddRewardFormEvents() {
  if (document.documentElement.dataset.addRewardSubmitBound === '1') return;
  document.documentElement.dataset.addRewardSubmitBound = '1';

  document.addEventListener('submit', function(event) {
    const rewardForm = event.target.closest('#add-reward-form');
    if (!rewardForm) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    if (rewardForm.dataset.submitting === '1') return;
    if (!rewardForm.reportValidity()) return;
    rewardForm.dataset.bound = '1';

    const submitButton = rewardForm.querySelector('button[type="submit"]');
    rewardForm.dataset.submitting = '1';
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Saving...';
    }
    closeAddRewardModal();

    fetch(recognitionRewardApi, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: new FormData(rewardForm),
      credentials: 'same-origin'
    })
      .then(function(response) {
        if (!response.ok) {
          throw new Error('Unable to save reward.');
        }

        return response.text().then(function(responseText) {
          if (!responseText.trim()) return { success: true };
          let result;
          try {
            result = JSON.parse(responseText);
          } catch (parseError) {
            // A successful POST redirect returns HTML instead of JSON.
            return { success: true };
          }
          if (!result.success) throw new Error(result.error || 'Unable to save reward.');
          return result;
        });
      })
      .then(function() {
        rewardForm.reset();
        loadRewards();
      })
      .catch(function(error) {
        console.error('Failed to save reward', error);
        setRecognitionModalVisibility('addRewardModal', true);
        window.alert(error.message);
      })
      .finally(function() {
        rewardForm.dataset.submitting = '0';
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.innerHTML = '<i class="fas fa-plus mr-1"></i>Add Reward';
        }
      });
  }, true);
}

bindAddRewardFormEvents();

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
    option.textContent = name;
    select.appendChild(option);
  });
}

function ensureEmployeeInNominationList(employeeId, employeeName) {
  const select = document.getElementById('nominate-employee');
  if (!select || !employeeId) return;

  const id = String(employeeId);
  const existing = Array.from(select.options).find(function(option) {
    return String(option.value) === id || option.getAttribute('data-employee-id') === id;
  });
  if (existing) return;

  const option = document.createElement('option');
  option.value = id;
  option.setAttribute('data-employee-id', id);
  option.textContent = employeeName || 'Employee';
  select.appendChild(option);
}

function populateNominationEmployeesFromRecognitions(recognitions) {
  const select = document.getElementById('nominate-employee');
  if (!select || !Array.isArray(recognitions)) return;

  const selectedEmployeeId = select.value;
  const recognizedEmployees = new Map();
  recognitions.forEach(function(recognition) {
    const employeeId = recognition.receiver_id || recognition.employee_id;
    const employeeName = recognition.receiver_name || recognition.employee_name;
    if (employeeId && employeeName) recognizedEmployees.set(String(employeeId), employeeName);
  });

  recognizedEmployees.forEach(function(employeeName, employeeId) {
    ensureEmployeeInNominationList(employeeId, employeeName);
  });

  if (selectedEmployeeId) select.value = selectedEmployeeId;
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
  const currentPage = new URLSearchParams(window.location.search).get('page');
  const tabs = document.getElementById('recognition-tabs');
  if (currentPage !== 'recognition' || !tabs) {
    return;
  }

  const resetAfterLogin = tabs.dataset.resetTab === 'true';
  if (resetAfterLogin) {
    [RECOGNITION_STORAGE_KEY, RECOGNITION_LEGACY_STORAGE_KEY].forEach(function(key) {
      sessionStorage.removeItem(key);
      localStorage.removeItem(key);
    });
    clearRecognitionTabCookies();
    tabs.dataset.resetTab = 'false';
  }

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

  const hash = resetAfterLogin
    ? 'recognition'
    : (savedHash && validTabIds.includes(savedHash)
    ? savedHash
    : (urlHash && validTabIds.includes(urlHash) ? urlHash : 'recognition'));
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

function bindRecognitionSendForm() {
  if (document.documentElement.dataset.recognitionSendDelegated === '1') return;
  document.documentElement.dataset.recognitionSendDelegated = '1';

  document.addEventListener('submit', function(e) {
    const sendForm = e.target;
    if (!sendForm || sendForm.id !== 'send-recognition-form') return;

    e.preventDefault();
    e.stopImmediatePropagation();
    if (sendForm.dataset.submitting === '1') return;
    const btn = sendForm.querySelector('button[type="submit"]');

    const receiverEl = document.getElementById('rec-receiver');
    const receiverId = receiverEl ? receiverEl.value : null;
    const messageEl = document.getElementById('rec-message');
    const pointsEl = document.getElementById('rec-points');
    const message = messageEl ? messageEl.value.trim() : '';
    const points = pointsEl ? parseInt(pointsEl.value, 10) : 10;

    if (!receiverId || !message || Number.isNaN(points) || points < 1) {
      if (!receiverId) alert('Please select a recipient before sending recognition.');
      else if (!message) alert('Please enter a message before sending recognition.');
      else alert('Points must be a valid number greater than 0.');
      return;
    }

    sendForm.dataset.submitting = '1';
    if (btn) { btn.textContent = 'Sending...'; btn.disabled = true; }

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
          const receiverName = document.getElementById('rec-receiver-name')?.value || 'Employee';
          ensureEmployeeInNominationList(receiverId, receiverName);
          closeRecognitionModal();
          if (messageEl) messageEl.value = '';
          if (pointsEl) pointsEl.value = '10';
          loadRecognitionFeed();
          loadRecognitionPageData();
          refreshAllRecognitionSections();
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
        sendForm.dataset.submitting = '0';
        if (btn) { btn.textContent = 'Send Recognition'; btn.disabled = false; }
      });
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
    loadRecognitionPageData();
    loadBadges();
    loadAwardHistory();
    loadRewards();
    loadRewardRedemptions();
    loadEmployeeBadges();
    loadEmployeeOfTheMonthCandidates();
    bindNominationFormEvents();
    bindAddRewardFormEvents();
    bindRecognitionSendForm();
  });

  const form = document.querySelector('#sendRecognitionModal form');
  const receiverIdInput = document.getElementById('rec-receiver');
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

  if (!window.__recognitionBackdropCloseBound) {
    window.__recognitionBackdropCloseBound = true;
    document.addEventListener('click', function(event) {
      const modal = event.target.closest('.modal');
      if (!modal || !modal.classList.contains('show')) return;
      if (event.target !== modal || event.target.closest('.modal-dialog')) return;

      if (modal.id === 'nominateEmployeeModal') closeNominationModal();
      else if (modal.id === 'assignBadgeModal') closeAssignBadgeModal();
      else if (modal.id === 'addRewardModal') closeAddRewardModal();
      else if (modal.id === 'createBadgeModal') closeCreateBadgeModal();
      else if (modal.id === 'sendRecognitionModal') closeRecognitionModal();
    }, true);
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
      } else if (modalId === 'editRewardModal') {
        setRecognitionModalVisibility('editRewardModal', true);
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
      else if (modalId === 'editRewardModal') setRecognitionModalVisibility('editRewardModal', false);
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
  bindAddRewardFormEvents();

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

  if (form && receiverIdInput && sendBtn) {
    form.addEventListener('submit', function(e) {
      if (!receiverIdInput.value) {
        e.preventDefault();
        sendBtn.disabled = true;
        alert('The selected recommendation does not match a current employee record.');
      }
    });
  }

  window.employeeMonthVotes = window.employeeMonthVotes || [];

  bindRecognitionSendForm();

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
              closeAssignBadgeModal();
            assignBadgeForm.reset();
            refreshAllRecognitionSections();
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

  if (!document.documentElement.dataset.createBadgeSubmitBound) {
    document.documentElement.dataset.createBadgeSubmitBound = '1';
    const badgeTierPoints = { bronze: 10, silver: 25, gold: 50, platinum: 100 };
    document.addEventListener('change', function(e) {
      if (e.target.matches('#create-badge-tier')) {
        const badgePoints = document.getElementById('create-badge-points');
        if (badgePoints) badgePoints.value = badgeTierPoints[e.target.value] || 10;
      }
    });
    document.addEventListener('submit', function(e) {
      const createBadgeForm = e.target.closest('#create-badge-form');
      if (!createBadgeForm) return;
      e.preventDefault();
      if (createBadgeForm.dataset.submitting === '1' || !createBadgeForm.reportValidity()) return;
      createBadgeForm.dataset.submitting = '1';
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
          closeCreateBadgeModal();
          createBadgeForm.reset();
          const category = document.getElementById('create-badge-category');
          if (category) category.value = 'achievement';
          const tier = document.getElementById('create-badge-tier');
          if (tier) tier.value = 'bronze';
          const points = document.getElementById('create-badge-points');
          if (points) points.value = '10';
          const icon = document.getElementById('create-badge-icon');
          if (icon) icon.value = 'fas fa-medal';
          loadBadges();
        })
        .catch(error => console.error('Failed to create badge', error))
        .finally(() => {
          createBadgeForm.dataset.submitting = '0';
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
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ award_history_id: awardHistoryId })
      }).then(r => r.json())
        .then(res => {
          if (res && res.success) {
            voteSucceeded = true;
            if (!window.employeeMonthVotes.includes(awardHistoryId)) {
              window.employeeMonthVotes.push(awardHistoryId);
            }
            form.remove();
            refreshAllRecognitionSections();
          } else if (res && res.error) {
            alert('Error: ' + res.error);
          }
        }).catch(err => {
          console.error('Failed to record vote', err);
          alert('Failed to record vote.');
        }).finally(() => {
          if (btn && !voteSucceeded) { btn.textContent = 'Vote +5'; btn.disabled = false; }
        });
    }
  });
}

function hasRecognitionPageStructure() {
  return !!(
    document.getElementById('recognition-tabs') ||
    document.getElementById('employee-month') ||
    document.getElementById('current-winner-content') ||
    document.getElementById('award-history-feed') ||
    document.getElementById('recognition-feed')
  );
}

function initializeRecognitionPage() {
  if (!hasRecognitionPageStructure()) {
    console.log('[Recognition Tab] Recognition UI not found on this page; skipping initialization.');
    return;
  }

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
  loadRecognitionPageData();
  loadBadges();
  loadAwardHistory();
  loadRewards();
  loadRewardRedemptions();
  loadEmployeeBadges();
  loadEmployeeOfTheMonthCandidates();
  startRecognitionAutoRefresh();
}

document.addEventListener('DOMContentLoaded', initializeRecognitionPage);

window.addEventListener('page:loaded', function (event) {
  if (!event.detail || event.detail.page !== 'recognition') return;
  if (!recognitionPageInitialized) {
    initializeRecognitionPage();
  }
});

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
  const refreshInterval = intervalMs || 30000;
  recognitionRefreshTimer = setInterval(function() {
    if (!document.getElementById('employee-month') && !document.getElementById('recognition-tabs')) {
      stopRecognitionAutoRefresh();
      return;
    }
    if (document.visibilityState === 'hidden') return;
    refreshActiveRecognitionSection();
  }, refreshInterval);
}

function refreshActiveRecognitionSection() {
  const activeTab = document.querySelector('#recognition-tabs .nav-link.active');
  const activeTabId = activeTab ? (activeTab.getAttribute('href') || '').replace('#', '') : 'recognition';

  switch (activeTabId) {
    case 'employee-month':
      loadEmployeeOfTheMonthCandidates();
      break;
    case 'badges':
      loadBadges();
      loadEmployeeBadges();
      break;
    case 'rewards':
      loadRewards();
      loadRewardRedemptions();
      break;
    case 'leaderboard':
      loadRecognitionPageData();
      break;
    case 'recognition':
    default:
      loadRecognitionFeed();
      break;
  }
}

function refreshAllRecognitionSections() {
  loadRecognitionPageData();
  loadRecognitionFeed();
  loadAwardHistory();
  loadRewards();
  loadRewardRedemptions();
  loadEmployeeBadges();

  const assignBadgeModal = document.getElementById('assignBadgeModal');
  const isAssignBadgeModalOpen = assignBadgeModal && assignBadgeModal.classList.contains('show');
  if (!isAssignBadgeModalOpen) {
    loadBadges();
    loadEmployeeOfTheMonthCandidates();
  }
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
    var score = tp.overall_rating !== undefined ? tp.overall_rating : null;
    var formattedScore = score !== null ? Number(score).toFixed(2) : 'N/A';
    var left = '<div class="recognition-summary-content"><strong>' + escapeHtml(tp.employee_name || tp.employee_id) + '</strong>' +
      '<div class="recognition-summary-meta">Overall Rating: ' + escapeHtml(formattedScore) + '/5.00 <span aria-hidden="true">•</span> Review period: ' + escapeHtml(tp.review_period || 'N/A') + '</div></div>';
    var right = '<span class="badge badge-success recognition-score-badge">' + escapeHtml(formattedScore) + '/5.00</span>';
    li.innerHTML = left + right;
    ul.appendChild(li);
  });
  container.appendChild(ul);
}

function renderComprehensiveLeaderboard(items) {
  const body = document.getElementById('comprehensive-leaderboard-list');
  if (!body) return;
  if (!Array.isArray(items) || !items.length) {
    body.innerHTML = '<tr><td colspan="6" class="text-muted text-center">No monthly points have been earned yet.</td></tr>';
    return;
  }
  body.innerHTML = items.slice(0, 9).map(function(item, index) {
    const rank = Number(item.rank_position || index + 1);
    return '<tr><td><span class="badge ' + (rank <= 3 ? 'badge-warning' : 'badge-secondary') + '">' + rank + '</span></td>'
      + '<td><strong>' + escapeHtml(item.employee_name || 'Unknown') + '</strong><div class="text-muted small">' + escapeHtml(item.department || 'N/A') + '</div></td>'
      + '<td>' + Number(item.recognition_points || 0) + '</td>'
      + '<td>' + Number(item.badge_points || 0) + '</td><td>' + Number(item.award_points || 0) + '</td>'
      + '<td><span class="badge badge-success badge-pill">' + Number(item.total_points || 0) + ' pts</span></td></tr>';
  }).join('');
}

function renderDepartmentLeaderboard(items) {
  const container = document.getElementById('department-leaderboard-list');
  if (!container) return;
  if (!Array.isArray(items) || !items.length) {
    container.innerHTML = '<p class="text-muted text-center p-3">No department ranking data yet.</p>';
    return;
  }
  container.innerHTML = '<div class="department-leaderboard-scroll"><ul class="list-group list-group-flush">'
    + items.slice(0, 9).map(function(item) {
      return '<li class="list-group-item d-flex justify-content-between align-items-center"><div><strong>'
        + escapeHtml(item.employee_name || 'Unknown') + '</strong><div class="text-muted small">'
        + escapeHtml(item.department || 'N/A') + '</div></div><div class="text-right"><span class="badge badge-info">#'
        + Number(item.dept_rank || 1) + '</span><div class="text-muted small">' + Number(item.total_points || 0) + ' pts</div></div></li>';
    }).join('') + '</ul></div>';
}

function renderCurrentWinner(awardHistory, employees, cycle) {
  const container = document.getElementById('current-winner-content');
  if (!container) return;
  const winner = cycle && cycle.status === 'approved'
    ? (Array.isArray(cycle.candidates) ? cycle.candidates : []).find(function(item) {
        return String(item.award_status || '').toLowerCase() === 'winner';
      })
    : null;
  if (!winner) {
    container.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-trophy fa-3x mb-3 text-warning"></i><p>No approved Employee of the Month yet.</p><small>HR will finalize the current monthly cycle.</small></div>';
    return;
  }
  const employee = (Array.isArray(employees) ? employees : []).find(function(item) {
    return String(item.employee_id) === String(winner.employee_id);
  });
  const name = winner.employee_name || employee?.full_name || employee?.employee_name
    || [employee?.first_name, employee?.middle_name, employee?.last_name].filter(Boolean).join(' ')
    || ('Employee #' + winner.employee_id);
  const parts = name.trim().split(/\s+/);
  const initials = ((parts[0] || '?')[0] + (parts[parts.length - 1] || '?')[0]).toUpperCase();
  container.innerHTML = '<div class="current-winner-profile"><div class="current-winner-avatar" aria-hidden="true">' + escapeHtml(initials) + '</div><h5 class="current-winner-name">' + escapeHtml(name) + '</h5><p class="current-winner-period">' + escapeHtml(new Date().toLocaleString('en-US', {month: 'long', year: 'numeric'})) + '</p><span class="badge badge-warning current-winner-badge"><i class="fas fa-crown mr-1"></i>Employee of the Month</span></div>'
    + (winner.reason ? '<div class="current-winner-reason"><span class="current-winner-reason-label">Reason</span><span>' + escapeHtml(winner.reason) + '</span></div>' : '');
}

function resolveRecommendationEmployeeId(item, employees) {
  const employeeList = Array.isArray(employees) ? employees : [];
  const reportEmployeeId = String(item.employee_id || '');
  const byId = employeeList.find(function (employee) {
    return String(employee.employee_id) === reportEmployeeId;
  });
  if (byId) return String(byId.employee_id);

  const normalizeName = function (value) {
    return String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
  };
  const reportName = normalizeName(item.employee_name);
  if (!reportName) return '';

  const byName = employeeList.find(function (employee) {
    const name = [employee.first_name, employee.middle_name, employee.last_name]
      .filter(Boolean)
      .join(' ') || employee.full_name || employee.employee_name || '';
    return normalizeName(name) === reportName;
  });
  return byName ? String(byName.employee_id) : '';
}

function renderRecognitionRecommendation(item, employees) {
  const employeeId = resolveRecommendationEmployeeId(item, employees);
  const action = '<button type="button" class="btn btn-sm btn-outline-success recommend-recognize" data-employee-id="' + escapeHtml(employeeId) + '" data-employee-name="' + escapeHtml(item.employee_name || '') + '">Recognize</button>';

  return '<li class="list-group-item d-flex justify-content-between align-items-center"><div><strong>'
    + escapeHtml(item.employee_name || item.employee_id || 'Unknown') + '</strong><div class="text-muted small">'
    + escapeHtml(item.evaluation_period || 'Performance Review') + ' • Grade: ' + escapeHtml(item.final_grade || 'N/A')
    + ' • Score: ' + escapeHtml(item.final_rating_percent || 'N/A') + '%</div></div>' + action + '</li>';
}

function loadRecognitionEmployees(forceRefresh = false) {
  if (!forceRefresh && Array.isArray(window.recognitionEmployees) && window.recognitionEmployees.length) {
    return Promise.resolve(window.recognitionEmployees);
  }

  const moduleRoot = window.location.pathname.split('/modules/engagement/')[0];
  return fetch(moduleRoot + '/modules/engagement/api/employee_list.php', {
    credentials: 'same-origin',
    cache: 'no-store'
  }).then(function (response) {
    if (!response.ok) throw new Error('Unable to load employees.');
    return response.json();
  }).then(function (employees) {
    window.recognitionEmployees = Array.isArray(employees) ? employees : [];
    return window.recognitionEmployees;
  });
}

function loadRecognitionPerformanceFallback(apiRoot) {
  const requests = [
    ['recognition_recommendations', 'recommendations'],
    ['performance_leaderboard', 'performance_leaderboard'],
    ['comprehensive_leaderboard', 'comprehensive_leaderboard'],
    ['department_leaderboard', 'department_leaderboard']
  ];

  return Promise.all(requests.map(function (entry) {
    return fetch(apiRoot + '?action=' + entry[1] + '&limit=10', {credentials: 'same-origin', cache: 'no-store'})
      .then(function (response) { return response.ok ? response.json() : {success: false}; })
      .then(function (result) { return [entry[0], result && result.success && Array.isArray(result.data) ? result.data : []]; })
      .catch(function () { return [entry[0], []]; });
  })).then(function (results) {
    const performanceData = {};
    results.forEach(function (entry) { performanceData[entry[0]] = entry[1]; });

    const recommendations = document.getElementById('performance-recommendations-list');
    if (recommendations && performanceData.recognition_recommendations.length) {
      recommendations.innerHTML = '<ul class="list-group list-group-flush">' + performanceData.recognition_recommendations.map(function (item) {
        return renderRecognitionRecommendation(item, window.recognitionEmployees);
      }).join('') + '</ul>';
    }

    renderComprehensiveLeaderboard(performanceData.comprehensive_leaderboard);
    renderDepartmentLeaderboard(performanceData.department_leaderboard);
    renderTopPerformers(findCardBodyByTitle('Top Performers'), performanceData.performance_leaderboard);
  });
}

function loadRecognitionPageData() {
  if (!hasRecognitionPageStructure()) {
    return;
  }

  const apiRoot = window.location.pathname.split('/modules/engagement/')[0] + '/modules/engagement/api/recognition.php';
  const month = document.getElementById('employee-of-month-month');
  const year = document.getElementById('employee-of-month-year');
  const url = apiRoot + '?action=page_data&month=' + encodeURIComponent(month ? month.value : '') + '&year=' + encodeURIComponent(year ? year.value : '');

  fetch(url, {credentials: 'same-origin', cache: 'no-store'})
    .then(function (response) {
      if (!response.ok) throw new Error('Unable to load recognition data.');
      return response.json();
    })
    .then(function (response) {
      const apiData = response.data || {};
      const data = Object.assign({}, apiData);

      window.recognitionPageData = data;
      window.recognitionEmployees = data.employees || [];
      populateNominationEmployeesFromRecognitions(data.recognitions);
      populateBadgeEmployeesFromNominations();
      renderComprehensiveLeaderboard(data.comprehensive_leaderboard);
      renderDepartmentLeaderboard(data.department_leaderboard);
      renderCurrentWinner(data.award_history, data.employees, data.current_employee_of_month);

      const recommendations = document.getElementById('performance-recommendations-list');
      if (recommendations && Array.isArray(data.recognition_recommendations)) {
        recommendations.innerHTML = data.recognition_recommendations.length
          ? '<ul class="list-group list-group-flush">' + data.recognition_recommendations.map(function (item) {
              return renderRecognitionRecommendation(item, data.employees);
            }).join('') + '</ul>'
          : '<p class="text-muted text-center m-2">No performance recommendations available yet.</p>';
      }

      const withoutReports = document.getElementById('employees-without-reports-list');
      const withoutReportsPanel = document.getElementById('employees-without-reports-panel');
      const missingReportEmployees = Array.isArray(data.employees_without_reports)
        ? data.employees_without_reports
        : [];
      const recommendationsAvailable = Array.isArray(data.recognition_recommendations)
        && data.recognition_recommendations.length > 0;
      if (withoutReportsPanel) {
        withoutReportsPanel.style.display = recommendationsAvailable && missingReportEmployees.length
          ? ''
          : 'none';
      }
      if (withoutReports && Array.isArray(data.employees_without_reports)) {
        withoutReports.innerHTML = missingReportEmployees.length
          ? '<ul class="list-group list-group-flush">' + missingReportEmployees.map(function (item) {
              return '<li class="list-group-item recognition-summary-item rounded-list-item"><div class="recognition-summary-content"><strong>' + escapeHtml(item.employee_name || 'Unknown') + '</strong><div class="recognition-summary-meta">' + escapeHtml(item.department || 'No department') + '</div></div><span class="badge badge-danger recognition-status-badge">No Report</span></li>';
            }).join('') + '</ul>'
          : '';
      }

      renderTopPerformers(findCardBodyByTitle('Top Performers'), data.performance_leaderboard || []);
      const stats = document.querySelectorAll('.quick-stats-value');
      if (stats[0]) stats[0].textContent = String((data.recognitions || []).length);
      if (stats[1]) stats[1].textContent = String((data.recognitions || []).reduce(function (total, item) { return total + Number(item.points || 0); }, 0));
    })
    .catch(function (error) {
      console.warn('[Recognition] API page-data load failed; specialized loaders remain active.', error);
      loadRecognitionPerformanceFallback(apiRoot).catch(function (fallbackError) {
        console.warn('[Recognition] Performance fallback failed:', fallbackError);
      });
    });
}

// Handle quick recognize clicks (show informational modal)
document.addEventListener('click', function(e){
  const target = e.target && e.target.closest ? e.target.closest('.recommend-recognize') : null;
  if (target) {
    e.preventDefault();
    openRecognitionModal({
      employee_id: target.getAttribute('data-employee-id') || '',
      employee_name: target.getAttribute('data-employee-name') || ''
    });
  }
});

function loadRecognitionFeed() {
  const feed = document.getElementById('recognition-feed');
  if (!feed) return;

  const initialFeedHtml = feed.dataset.initializedHtml || feed.innerHTML.trim();
  if (initialFeedHtml && !feed.dataset.hasBeenRefreshed) {
    feed.dataset.initializedHtml = initialFeedHtml;
    feed.dataset.hasBeenRefreshed = '1';
  }

  fetch(recognitionApiBase + '?resource=recognition&action=list&t=' + Date.now(), { cache: 'no-store', credentials: 'same-origin' })
    .then(r => r.json())
    .then(res => {
      if (!feed) return;
      if (res && res.data && res.data.length) {
        feed.innerHTML = '';
        res.data.forEach(r => {
          const item = document.createElement('div');
          item.className = 'list-group-item recognition-entry';
          item.dataset.senderId = r.sender_id || '';
          item.dataset.senderName = r.sender_name || '';
          item.dataset.receiverId = r.receiver_id || '';
          item.dataset.receiverName = r.receiver_name || '';
          item.dataset.recognitionId = r.id || r.eer_recognition_id || '';
          const isMonthlyAward = ['employee_of_month', 'outstanding_employee'].includes(String(r.category || '').toLowerCase());
          const pointsLabel = isMonthlyAward ? 'Monthly Award' : '+' + escapeHtml(r.points || 0) + ' pts';
          const awardLabel = r.category === 'employee_of_month'
            ? '<span class="badge badge-warning mr-2">Employee of the Month</span>'
            : (r.category === 'outstanding_employee' ? '<span class="badge badge-info mr-2">Outstanding Employee</span>' : '');
          item.innerHTML = `
            <div class="recognition-entry-header">
              <div class="recognition-entry-people">
                <strong>${escapeHtml(r.sender_name || r.sender_id)}</strong>
                <span class="recognition-entry-arrow" aria-hidden="true">&rarr;</span>
                <strong>${escapeHtml(r.receiver_name || r.receiver_id)}</strong>
              </div>
              ${awardLabel}<span class="badge ${isMonthlyAward ? 'badge-secondary' : 'badge-success'} recognition-entry-points">${pointsLabel}</span>
            </div>
            <p class="recognition-entry-message">${escapeHtml(r.message || 'No message provided.')}</p>
            <small class="text-muted recognition-entry-time"><i class="fas fa-clock mr-1"></i>${escapeHtml(r.created_at || '')}</small>
          `;
          feed.appendChild(item);
        });
        populateNominationEmployeesFromFeed();

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
          window.lastSeenRecognitionId = res.data[0] && (res.data[0].id || res.data[0].eer_recognition_id) ? (res.data[0].id || res.data[0].eer_recognition_id) : window.lastSeenRecognitionId;
        } catch (err) {
          console.error('Error detecting new recognition', err);
        }
      } else if (!initialFeedHtml || !initialFeedHtml.includes('recognition-entry')) {
        feed.innerHTML = '<div class="text-muted">No recognition found.</div>';
      }
    })
    .catch(() => {
      if (initialFeedHtml && initialFeedHtml.includes('recognition-entry')) {
        feed.innerHTML = initialFeedHtml;
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
      const selectedBadgeId = badgeSelect ? badgeSelect.value : '';
      if (!feed) return;
      feed.innerHTML = '';
      if (badgeSelect) badgeSelect.innerHTML = '<option value="">Select badge</option>';
      if (res && res.data && res.data.length) {
        res.data.forEach(b => {
          const item = document.createElement('div');
          item.className = 'list-group-item';
          const badgeIcon = b.icon || 'fas fa-medal';
          item.innerHTML = `<div class="d-flex align-items-center"><span class="badge-icon" aria-hidden="true"><i class="${escapeHtml(badgeIcon)}"></i></span><b>${escapeHtml(b.name)}</b></div><span class="badge-description">${escapeHtml(b.description || '')}</span><div class="text-muted small mt-1">+${Number(b.points_value || 0)} points</div>`;
          feed.appendChild(item);
          if (badgeSelect) {
            const option = document.createElement('option');
            option.value = b.eer_badge_id || b.id;
            option.textContent = b.name;
            badgeSelect.appendChild(option);
          }
        });
        if (badgeSelect && selectedBadgeId) badgeSelect.value = selectedBadgeId;
      } else {
        feed.innerHTML = '<div class="badges-empty-state"><span class="badges-empty-icon"><i class="fas fa-award" aria-hidden="true"></i></span><div><strong>No badges found.</strong><p>Create and assign badges to recognize employee achievements.</p></div></div>';
      }
    });
}

function loadAwardHistory() {
  const feed = document.getElementById('award-history-feed');
  if (!feed) return;

  fetch(recognitionApiBase + '?resource=award_history')
    .then(async function(response) {
      if (!response.ok) {
        feed.innerHTML = '<div class="text-muted">No award history found.</div>';
        return;
      }

      const text = await response.text();
      if (!text || !text.trim()) {
        feed.innerHTML = '<div class="text-muted">No award history found.</div>';
        return;
      }

      let res = null;
      try {
        res = JSON.parse(text);
      } catch (error) {
        feed.innerHTML = '<div class="text-muted">No award history found.</div>';
        return;
      }

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
    })
    .catch(function() {
      if (feed) {
        feed.innerHTML = '<div class="text-muted">No award history found.</div>';
      }
    });
}

function loadRewards() {
  fetch(recognitionRewardApi)
    .then(r => r.json())
    .then(res => {
      const feed = document.getElementById('rewards-feed');
      const editSelect = document.getElementById('edit-reward-select');
      if (!feed) return;
      const selectedRewardId = editSelect ? editSelect.value : '';
      feed.innerHTML = '';
      if (editSelect) editSelect.innerHTML = '<option value="">Select reward</option>';
      if (res && res.data && res.data.length) {
        res.data.forEach(rw => {
          const item = document.createElement('div');
          item.className = 'list-group-item reward-item';
          const rewardIcon = rw.icon || 'fas fa-gift';
          item.innerHTML = `<div class="reward-icon" aria-hidden="true"><i class="${escapeHtml(rewardIcon)}"></i></div><div class="reward-content"><strong>${escapeHtml(rw.name)}</strong><span class="reward-description">${escapeHtml(rw.description || '')}</span><small class="text-muted">Performance requirement: ${Number(rw.performance_requirement || 0)}%</small></div><span class="badge badge-info reward-points">${Number(rw.points_required || 0)} pts</span>`;
          feed.appendChild(item);
          if (editSelect) {
            const option = document.createElement('option');
            option.value = rw.eer_reward_id;
            option.textContent = rw.name;
            option.dataset.name = rw.name || '';
            option.dataset.description = rw.description || '';
            option.dataset.points = rw.points_required || '';
            editSelect.appendChild(option);
          }
        });
      } else {
        feed.innerHTML = '<div class="text-muted">No rewards found.</div>';
      }
      if (editSelect) {
        editSelect.value = selectedRewardId;
        if (selectedRewardId && !editSelect.value) {
          document.getElementById('edit-reward-name').value = '';
          document.getElementById('edit-reward-description').value = '';
          document.getElementById('edit-reward-points').value = '';
        }
      }
    });
}

if (!window.__rewardEditBound) {
  window.__rewardEditBound = true;
  document.addEventListener('change', function(event) {
    if (!event.target.matches('#edit-reward-select')) return;
    const option = event.target.selectedOptions[0];
    if (!option) return;
    document.getElementById('edit-reward-name').value = option.dataset.name || '';
    document.getElementById('edit-reward-description').value = option.dataset.description || '';
    document.getElementById('edit-reward-points').value = option.dataset.points || '';
  });
  document.addEventListener('submit', function(event) {
    const form = event.target.closest('#edit-reward-form');
    if (!form) return;
    event.preventDefault();
    const select = document.getElementById('edit-reward-select');
    const payload = {
      id: select.value,
      name: document.getElementById('edit-reward-name').value.trim(),
      description: document.getElementById('edit-reward-description').value,
      points_required: Number(document.getElementById('edit-reward-points').value)
    };
    fetch(recognitionRewardApi + '?action=update', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    }).then(response => response.json().then(data => ({ response, data })))
      .then(({ response, data }) => {
        if (!response.ok || !data.success) throw new Error(data.error || 'Unable to update reward.');
        setRecognitionModalVisibility('editRewardModal', false);
        loadRewards();
      }).catch(error => window.alert(error.message));
  });
}

if (!window.__editRewardModalCloseBound) {
  window.__editRewardModalCloseBound = true;
  document.addEventListener('click', function(event) {
    const modal = document.getElementById('editRewardModal');
    if (!modal || !modal.classList.contains('show')) return;
    if (event.target === modal || event.target.closest('#editRewardModal [data-recognition-close="editRewardModal"]')) {
      event.preventDefault();
      setRecognitionModalVisibility('editRewardModal', false);
    }
  }, true);
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
          item.className = 'list-group-item reward-redemption-item';
          const status = String(rr.status || 'pending').toLowerCase();
          const actions = status === 'pending'
            ? `<div class="mt-2"><button type="button" class="btn btn-sm btn-success redemption-action" data-redemption-action="approved" data-redemption-id="${escapeHtml(rr.eer_reward_redemption_id)}">Approve</button> <button type="button" class="btn btn-sm btn-outline-danger redemption-action" data-redemption-action="rejected" data-redemption-id="${escapeHtml(rr.eer_reward_redemption_id)}">Reject</button></div>`
            : '';
          item.innerHTML = `<div class="d-flex justify-content-between align-items-start"><div><strong>${escapeHtml(rr.employee_name || rr.employee_id)}</strong><div class="text-muted">redeemed <b>${escapeHtml(rr.reward_name || rr.reward_id)}</b></div><small class="text-muted">${escapeHtml(rr.redeemed_at || '')}</small></div><span class="badge badge-secondary">${escapeHtml(status)}</span></div>${actions}`;
          feed.appendChild(item);
        });
      } else {
        feed.innerHTML = '<div class="text-muted">No reward redemptions found.</div>';
      }
    });
}

  if (!window.__rewardRedemptionActionsBound) {
    window.__rewardRedemptionActionsBound = true;
    document.addEventListener('click', function(event) {
      const actionButton = event.target.closest('.redemption-action');
      if (!actionButton) return;
      const status = actionButton.dataset.redemptionAction;
      const id = actionButton.dataset.redemptionId;
      const reason = status === 'rejected' ? window.prompt('Reason for rejection:') : null;
      if (status === 'rejected' && reason === null) return;
      actionButton.disabled = true;
      fetch(recognitionApiBase + '?resource=reward_redemption&action=update_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, status: status, reason: reason })
      })
        .then(response => response.json().then(data => ({ response, data })))
        .then(({ response, data }) => {
          if (!response.ok || !data.success) throw new Error(data.error || 'Unable to update redemption.');
          loadRewardRedemptions();
        })
        .catch(error => {
          actionButton.disabled = false;
          window.alert(error.message);
        });
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

function renderMonthlyRecognitionCycle(cycle) {
  const container = document.getElementById('employee-month-candidates-list');
  if (!container) return;
  const candidates = cycle.status === 'approved' && Array.isArray(cycle.candidates)
    ? cycle.candidates
    : (Array.isArray(cycle.ranked_employees)
    ? cycle.ranked_employees
    : (Array.isArray(cycle.candidates) ? cycle.candidates : []));
  window.approvedMonthlyRecognitionEmployees = cycle.status === 'approved'
    ? candidates.filter(function(candidate) {
        return candidate.award_status === 'winner' || candidate.award_status === 'shortlisted';
      }).map(function(candidate) {
        return {
          employee_id: candidate.employee_id,
          employee_name: candidate.employee_name || ('Employee #' + candidate.employee_id)
        };
      })
    : [];
  populateBadgeEmployeesFromNominations();
  const title = document.querySelector('#employee-month .card-title');
  const displayedCount = cycle.status === 'approved'
    ? Number(cycle.required_count || candidates.length)
    : Number(cycle.required_count || candidates.length);
  if (title) {
    title.innerHTML = '<i class="fas fa-star mr-2"></i>Top ' + displayedCount + ' Employees for Recognition';
  }
  if (cycle.status === 'unavailable' || cycle.status === 'no_reports' || cycle.status === 'no_recognitions') {
    container.innerHTML = '<p class="text-muted text-center">' + escapeHtml(cycle.message || 'No approved Recognition Feed recipients are available to rank.') + '</p>';
    return;
  }

  const periodLabel = cycle.review_period
    ? cycle.review_period
    : [cycle.period_start, cycle.period_end].filter(Boolean).join(' to ') || 'Recognition Feed recipients';
  const periodDates = [cycle.period_start, cycle.period_end].filter(Boolean).join(' to ');
  const reportSourceSummary = 'Finalized Overall Rating determines ranking; recipients without a finalized rating are listed afterward, not ranked by points';
  const hasFinalizedRating = candidates.some(function(candidate) {
    return candidate.overall_rating !== null && candidate.overall_rating !== undefined;
  });
  const periodHeading = cycle.status === 'approved'
    ? 'APPROVED CYCLE PERIOD'
    : 'RANKED FROM RECOGNITION FEED';
  const periodDescription = cycle.status === 'approved' && !hasFinalizedRating
    ? 'No finalized Overall Rating was available for this cycle; recipients are listed as ranked candidates, not ranked by points.'
    : cycle.status === 'approved'
      ? 'Approved recipients and available finalized ratings are shown below.'
      : (periodDates ? 'Latest report period in this pool: ' + periodDates + ' · ' : '') + reportSourceSummary;
  const periodSummary = '<div class="top-five-period"><span class="top-five-period-icon"><i class="far fa-calendar-alt"></i></span>'
    + '<div><span class="top-five-period-label">' + periodHeading + '</span><strong>'
    + escapeHtml(periodLabel) + '</strong><small>' + escapeHtml(periodDescription) + '</small></div></div>';

  if (cycle.status === 'report_conflict' || cycle.status === 'pending_review') {
    container.innerHTML = periodSummary + '<div class="alert alert-warning mb-0" role="alert">'
      + escapeHtml(cycle.message || 'HR review is required before this cycle can be approved.') + '</div>';
    return;
  }

  if (cycle.status === 'pending' && !cycle.can_approve && !candidates.length) {
    container.innerHTML = periodSummary
      + '<div class="top-five-pending-approval"><i class="fas fa-lock"></i><span><strong>Awaiting HR approval</strong><small>The monthly selection is pending. Performance ratings are visible only to authorized HR staff.</small></span></div>';
    return;
  }

  if (cycle.status === 'approved') {
    container.innerHTML = periodSummary
      + '<div class="top-five-approved-banner"><i class="fas fa-check-circle"></i><span><strong>Selection approved</strong><small>Saved to award history. This monthly cycle is locked.</small></span></div>'
      + (candidates.length < 5 ? '<p class="text-muted small">This cycle includes all ' + candidates.length + ' approved Recognition Feed recipients.</p>' : '')
      + candidates.map(function(candidate, index) {
        const isWinner = candidate.award_status === 'winner';
        const wasSelected = Boolean(candidate.award_status);
        const showPrivateRanking = Boolean(cycle.can_approve);
        const hasRating = candidate.overall_rating !== null && candidate.overall_rating !== undefined;
        const rating = hasRating
          ? '<span class="top-five-rating">' + Number(candidate.overall_rating).toFixed(2) + '<small>/ 5.00</small></span>'
          : '';
        const awardPoints = Number(candidate.award_points || 0);
        const badgePoints = Number(candidate.badge_points || 0);
        const awardLabel = !hasRating
          ? 'Ranked candidate'
          : (isWinner ? 'Employee of the Month' : (wasSelected ? 'Outstanding Employee' : 'Ranked candidate'));
        return '<div class="top-five-candidate top-five-approved-row' + (isWinner ? ' is-winner' : '') + '">'
          + '<div class="top-five-rank">' + (showPrivateRanking ? '#' + (index + 1) : '<i class="fas ' + (isWinner ? 'fa-crown' : (wasSelected ? 'fa-medal' : 'fa-user')) + '"></i>') + '</div>'
          + '<div class="top-five-employee"><strong>' + escapeHtml(candidate.employee_name || ('Employee #' + candidate.employee_id)) + '</strong>'
          + '<small>' + escapeHtml(candidate.department || 'No department') + '</small>'
          + '<span class="top-five-approved-meta">'
          + '<span class="top-five-award-label ' + (hasRating && isWinner ? 'winner' : '') + '"><i class="fas '
          + (hasRating ? (isWinner ? 'fa-crown' : (wasSelected ? 'fa-medal' : 'fa-user')) : 'fa-user') + '"></i>'
          + awardLabel + '</span>'
          + (candidate.badge_name || candidate.reward_name
            ? '<small class="text-muted top-five-award-details">'
              + escapeHtml(candidate.badge_name || '') + (candidate.badge_name && candidate.reward_name ? ' · ' : '')
              + escapeHtml(candidate.reward_name || '') + '</small>'
            : '')
          + '</span></div>'
          + '<div class="top-five-approved-points">'
          + rating
          + (awardPoints > 0 ? '<span class="badge badge-success">+' + awardPoints + ' EOM pts</span>' : '')
          + (badgePoints > 0 ? '<span class="badge badge-info">+' + badgePoints + ' badge pts</span>' : '')
          + '</div>'
          + '</div>';
      }).join('');
    return;
  }

  if (!candidates.length) {
    container.innerHTML = periodSummary + '<div class="top-five-empty"><i class="fas fa-user-clock"></i><strong>No recognized employees found</strong><span>There are no approved Recognition Feed recipients to rank.</span></div>';
    return;
  }

  const requiredCount = Number(cycle.required_count || 0);
  const canApprove = Boolean(cycle.can_approve);
  const firstSelected = candidates.find(function(candidate) { return candidate.selected_by_default; });
  const topRating = firstSelected && firstSelected.overall_rating !== null
    ? Number(firstSelected.overall_rating)
    : null;
  const candidateRows = candidates.map(function(candidate) {
    const employeeId = String(candidate.employee_id);
    const selected = Boolean(candidate.selected_by_default);
    const hasRating = candidate.overall_rating !== null && candidate.overall_rating !== undefined;
    const ratingSource = canApprove && hasRating && candidate.review_period
      ? '<small class="top-five-rating-period">Rating period: ' + escapeHtml(candidate.review_period)
        + (candidate.performance_report_status ? ' · ' + escapeHtml(candidate.performance_report_status) : '') + '</small>'
      : '';
    const isTopRating = hasRating && Number(candidate.overall_rating) === topRating;
    const selectionControl = canApprove
      ? '<label class="top-five-selection-control"><input type="checkbox" class="top-five-employee-checkbox" value="' + escapeHtml(employeeId) + '"'
        + (selected ? ' checked' : '') + (candidate.cutoff_tie ? '' : ' disabled')
        + '> <span>' + (candidate.cutoff_tie ? 'Select for Top 5' : 'In Top 5') + '</span></label>'
      : '';
    const winnerControl = canApprove && candidate.winner_eligible
      ? '<label class="top-five-winner-control"><input type="radio" name="employee_of_month_id" class="top-five-winner-radio" value="' + escapeHtml(employeeId) + '"'
        + (selected && employeeId === String(firstSelected.employee_id) ? ' checked' : '')
        + (selected ? '' : ' disabled') + '> <span><i class="fas fa-crown"></i> Set as Employee of the Month</span></label>'
      : '';
    const cutoffLabel = candidate.cutoff_tie ? '<span class="top-five-tie-label"><i class="fas fa-equals"></i> Tied at cutoff</span>' : '';
    return '<div class="top-five-candidate' + (selected ? ' is-selected' : '') + (isTopRating ? ' is-top-rated' : '') + '" data-employee-id="' + escapeHtml(employeeId) + '" data-rating="' + Number(candidate.overall_rating) + '" data-winner-eligible="' + (candidate.winner_eligible ? 'true' : 'false') + '">'
      + '<div class="top-five-rank">#' + Number(candidate.rank || 0) + '</div>'
      + '<div class="top-five-employee"><strong>' + escapeHtml(candidate.employee_name || ('Employee #' + employeeId)) + '</strong>'
      + '<small>' + escapeHtml(candidate.department || 'No department') + '</small>' + ratingSource + cutoffLabel + '</div>'
      + (hasRating
        ? '<span class="top-five-rating">' + Number(candidate.overall_rating).toFixed(2) + '<small>/ 5.00</small></span>'
        : '<span class="badge badge-secondary">No finalized rating</span>')
      + (canApprove ? '<div class="top-five-controls">' + selectionControl + winnerControl + '</div>' : '') + '</div>';
  }).join('');
  const approveForm = canApprove
    ? '<form id="approve-monthly-top-five-form" class="top-five-approval"><div class="top-five-approval-copy"><span class="top-five-approval-icon"><i class="fas fa-clipboard-check"></i></span><span><strong>Ready to finalize?</strong><small>Top ' + requiredCount + ' selections are prechecked. No extra checkbox is needed unless there is a cutoff tie.</small></span></div>'
      + '<div id="top-five-selection-error" class="alert alert-danger py-2 d-none" role="alert"></div>'
      + '<button type="submit" class="btn btn-warning" id="approve-monthly-top-five-button" data-required-count="' + requiredCount + '"><i class="fas fa-check mr-1"></i>Approve monthly Top 5</button>'
      + '</form>'
    : '<div class="top-five-pending-approval"><i class="fas fa-lock"></i><span><strong>Awaiting HR approval</strong><small>The monthly selection has not been finalized yet.</small></span></div>';
  container.innerHTML = periodSummary
    + (candidates.length > requiredCount ? '<p class="text-muted small mb-2">All ' + Number(candidates.length) + ' approved Recognition Feed recipients are shown; only the top ' + requiredCount + ' (plus cutoff ties) can be selected.</p>' : '')
    + '<div class="top-five-list-heading"><span>RANKED EMPLOYEES FROM RECOGNITION FEED</span><span>RATING / FEED POINTS</span></div>'
    + (cycle.cutoff_tie ? '<div class="top-five-tie-notice"><i class="fas fa-info-circle"></i><span>May tabla sa cutoff ng Top ' + requiredCount + '. Piliin ng HR ang huling puwesto.</span></div>' : '')
    + candidateRows + approveForm;
}

function bindMonthlyRecognitionApproval(container) {
  if (!container || container.dataset.topFiveApprovalBound === '1') return;
  container.dataset.topFiveApprovalBound = '1';

  container.addEventListener('change', function(event) {
    if (!event.target.classList.contains('top-five-employee-checkbox')) return;
    const rows = Array.from(container.querySelectorAll('.top-five-candidate'));
    const selectedRows = rows.filter(function(row) {
      const checkbox = row.querySelector('.top-five-employee-checkbox');
      return checkbox && checkbox.checked;
    });
    const highestRating = selectedRows.reduce(function(highest, row) {
      return Math.max(highest, Number(row.dataset.rating || 0));
    }, -Infinity);
    let selectedWinner = false;
    rows.forEach(function(row) {
      const radio = row.querySelector('.top-five-winner-radio');
      const checkbox = row.querySelector('.top-five-employee-checkbox');
      row.classList.toggle('is-selected', Boolean(checkbox && checkbox.checked));
      if (!radio) return;
      radio.disabled = !checkbox.checked || row.dataset.winnerEligible !== 'true'
        || Number(row.dataset.rating || 0) !== highestRating;
      if (radio.checked && radio.disabled) radio.checked = false;
      if (radio.checked) selectedWinner = true;
    });
    if (!selectedWinner) {
      const nextWinner = rows.find(function(row) {
        const radio = row.querySelector('.top-five-winner-radio');
        return radio && !radio.disabled;
      });
      if (nextWinner) nextWinner.querySelector('.top-five-winner-radio').checked = true;
    }
  });

  container.addEventListener('submit', function(event) {
    if (!event.target.matches('#approve-monthly-top-five-form')) return;
    event.preventDefault();
    const form = event.target;
    const checkboxes = Array.from(form.closest('#employee-month-candidates-list').querySelectorAll('.top-five-employee-checkbox'));
    const selectedEmployeeIds = checkboxes.filter(function(input) { return input.checked; }).map(function(input) { return input.value; });
    let employeeOfMonth = form.querySelector('input[name="employee_of_month_id"]:checked');
    const button = form.querySelector('button[type="submit"]');
    const errorBox = form.querySelector('#top-five-selection-error');
    const monthSelect = document.getElementById('employee-of-month-month');
    const yearSelect = document.getElementById('employee-of-month-year');
    const requiredCount = Number(form.querySelector('button').dataset.requiredCount || checkboxes.filter(function(input) {
      return input.defaultChecked;
    }).length);
    if (selectedEmployeeIds.length === requiredCount && !employeeOfMonth) {
      const selectedWinnerRow = Array.from(container.querySelectorAll('.top-five-candidate')).find(function(row) {
        const checkbox = row.querySelector('.top-five-employee-checkbox');
        const radio = row.querySelector('.top-five-winner-radio');
        return checkbox && checkbox.checked && radio && row.dataset.winnerEligible === 'true';
      });
      if (selectedWinnerRow) {
        employeeOfMonth = selectedWinnerRow.querySelector('.top-five-winner-radio');
        employeeOfMonth.disabled = false;
        employeeOfMonth.checked = true;
      }
    }
    if (selectedEmployeeIds.length !== requiredCount || !employeeOfMonth) {
      errorBox.textContent = 'Select exactly ' + requiredCount + ' employees.'
        + (selectedEmployeeIds.length === requiredCount ? ' Choose an eligible Employee of the Month.' : ' Resolve any cutoff tie, then approve.');
      errorBox.classList.remove('d-none');
      return;
    }
    if (!window.confirm('Approve and lock the Top 5 for this month? This action saves the awards to history.')) {
      return;
    }
    errorBox.classList.add('d-none');
    if (button) {
      button.disabled = true;
      button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Approving...';
    }
    fetch(recognitionApiBase + '?resource=recognition&action=approve_monthly_top_five', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        month_year: String(yearSelect ? yearSelect.value : new Date().getFullYear()) + '-'
          + String(monthSelect ? monthSelect.value : new Date().getMonth() + 1).padStart(2, '0'),
        selected_employee_ids: selectedEmployeeIds,
        employee_of_month_id: employeeOfMonth.value
      })
    }).then(function(response) {
      return response.json().then(function(result) {
        if (!response.ok || !result.success) {
          throw new Error(result.error || 'Could not approve the monthly recognition cycle.');
        }
        return result.data;
      });
    }).then(function(cycle) {
      cycle.can_approve = true;
      renderMonthlyRecognitionCycle(cycle);
      loadAwardHistory();
      loadRecognitionPageData();
    }).catch(function(error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove('d-none');
      if (button) {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-check mr-1"></i>Approve Top 5';
      }
    });
  });
}

function loadEmployeeOfTheMonthCandidates() {
  const requestId = ++employeeMonthCandidatesRequestId;
  const monthSelect = document.getElementById('employee-of-month-month');
  const yearSelect = document.getElementById('employee-of-month-year');
  const month = monthSelect ? monthSelect.value : new Date().getMonth() + 1;
  const year = yearSelect ? yearSelect.value : new Date().getFullYear();
  const monthYear = String(year) + '-' + String(month).padStart(2, '0');
  const container = document.getElementById('employee-month-candidates-list');
  if (container) bindMonthlyRecognitionApproval(container);

  return fetch(`${recognitionApiBase}?resource=recognition&action=monthly_top_five&month_year=${encodeURIComponent(monthYear)}&t=${Date.now()}`, {
    cache: 'no-store'
  })
    .then(r => r.json())
    .then(res => {
      if (requestId !== employeeMonthCandidatesRequestId) return;
      if (!container) return;
      if (!res || !res.success || !res.data) {
        throw new Error(res && res.error ? res.error : 'Unable to load finalized performance ratings.');
      }
      renderMonthlyRecognitionCycle(res.data);
    })
    .catch(function(error) {
      if (requestId !== employeeMonthCandidatesRequestId || !container) return;
      console.error('Unable to load monthly recognition candidates.', error);
      container.innerHTML = '<p class="text-danger text-center">' + escapeHtml(error.message) + '</p>';
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
          item.className = 'list-group-item employee-badge-item';
          const badgeIcon = eb.badge_icon || 'fas fa-medal';
          const cycleLabel = eb.recognition_month
            ? `<span class="employee-badge-cycle"><i class="far fa-calendar-check" aria-hidden="true"></i>${escapeHtml(eb.award_level === 'employee_of_month' ? 'Employee of the Month' : 'Outstanding Employee')}<span aria-hidden="true">·</span>${escapeHtml(eb.recognition_month)}</span>`
            : '';
          const awardedAt = eb.awarded_at || eb.earned_at || '';
          const badgePoints = Number(eb.badge_points || 0);
          const badgePointsLabel = badgePoints > 0
            ? `<span class="employee-badge-points"><i class="fas fa-plus" aria-hidden="true"></i>${badgePoints}<small>points</small></span>`
            : '';
          item.innerHTML = `<div class="employee-badge-icon" aria-hidden="true"><i class="${escapeHtml(badgeIcon)}"></i></div><div class="employee-badge-content"><strong>${escapeHtml(eb.employee_name || eb.employee_id)}</strong><div class="employee-badge-earned"><span>earned</span><b>${escapeHtml(eb.badge_name || eb.badge_id)}</b></div>${cycleLabel}${awardedAt ? `<small class="employee-badge-date"><i class="far fa-clock" aria-hidden="true"></i>${escapeHtml(awardedAt)}</small>` : ''}</div>${badgePointsLabel}`;
          feed.appendChild(item);
        });
      } else {
        feed.innerHTML = '<div class="text-muted">No employee badges found.</div>';
      }
    });
}
