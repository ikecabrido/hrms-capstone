(function () {
  const recognitionApiBase = window.location.pathname.indexOf('/pages/') !== -1
    ? '../api/index.php'
    : 'api/index.php';

  const state = {
    initialized: false,
    refreshTimer: null,
    lastSeenRecognitionId: null
  };

  const employeesWithoutReportsPageSize = 5;
  let employeesWithoutReportsPageIndex = 0;
  let employeesWithoutReportsData = [];
  let employeeMonthCandidatesRequestId = 0;

  function isRecognitionPage() {
    return document.querySelector('#recognition-tabs') !== null;
  }

  function setActiveTab(tabLink, isInitialLoad = false) {
    const href = tabLink && tabLink.getAttribute('href');
    if (!href || !href.startsWith('#')) return;

    const tabId = href.replace('#', '');
    const targetPane = document.getElementById(tabId);
    if (!targetPane) return;

    const currentTab = document.querySelector('#recognition-tabs .nav-link.active');
    const currentHref = currentTab ? currentTab.getAttribute('href') : null;
    if (currentHref === href && targetPane.classList.contains('active')) {
      return;
    }

    localStorage.setItem('recognition-active-tab', tabId);

    if (!isInitialLoad && window.location.hash !== href) {
      window.history.replaceState({}, '', href);
    }

    const allTabs = document.querySelectorAll('#recognition-tabs .nav-link');
    const allPanes = document.querySelectorAll('.recognition-area .tab-pane');

    allTabs.forEach(tab => {
      const isCurrent = tab === tabLink;
      tab.classList.toggle('active', isCurrent);
      tab.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
    });

    allPanes.forEach(pane => {
      const isCurrent = pane.id === tabId;
      pane.classList.toggle('show', isCurrent);
      pane.classList.toggle('active', isCurrent);
    });
  }

  function restoreTabFromState(isInitialLoad = false) {
    if (!isRecognitionPage()) return;

    const urlHash = window.location.hash ? window.location.hash.replace('#', '') : '';
    const savedHash = localStorage.getItem('recognition-active-tab');
    const navEntry = performance.getEntriesByType ? performance.getEntriesByType('navigation')[0] : null;
    const isReload = navEntry ? navEntry.type === 'reload' : (performance.navigation ? performance.navigation.type === 1 : false);

    let hash = 'recognition';
    if (urlHash && document.getElementById(urlHash)) {
      hash = urlHash;
    } else if (isReload && savedHash && document.getElementById(savedHash)) {
      hash = savedHash;
    }

    const targetTab = document.querySelector('#recognition-tabs a[href="#' + CSS.escape(hash) + '"]');
    if (!targetTab) return;

    setActiveTab(targetTab, isInitialLoad);
  }

  function bindTabClicks() {
    const tabLinks = document.querySelectorAll('#recognition-tabs .nav-link');
    tabLinks.forEach(link => {
      link.onclick = function (event) {
        event.preventDefault();
        setActiveTab(this, false);
      };
    });
  }

  function bindGlobalEvents() {
    window.addEventListener('hashchange', function () {
      restoreTabFromState(false);
    });

    window.addEventListener('popstate', function () {
      restoreTabFromState(false);
    });

    window.addEventListener('pageshow', function (event) {
      if (event.persisted || document.visibilityState === 'visible') {
        restoreTabFromState(true);
      }
    });

    window.addEventListener('page:loaded', function (e) {
      if (e.detail && e.detail.page === 'recognition') {
        bindTabClicks();
        restoreTabFromState(true);
        refreshAllSections();
      }
    });
  }

  function refreshAllSections() {
    loadRecognitionFeed();
    loadBadges();
    loadAwardHistory();
    loadRewards();
    loadRewardRedemptions();
    loadEmployeeBadges();
    loadPerformanceRecommendations();
    loadEmployeesWithoutReports();
    loadTopPerformers();
    loadEmployeeOfTheMonthCandidates();
  }

  function startAutoRefresh() {
    if (state.refreshTimer) {
      clearInterval(state.refreshTimer);
    }

    state.refreshTimer = setInterval(function () {
      refreshAllSections();
    }, 5000);
  }

  function escapeHtml(value) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return String(value ?? '').replace(/[&<>"']/g, function (char) {
      return map[char];
    });
  }

  function loadRecognitionFeed() {
    fetch(recognitionApiBase + '?resource=recognition&action=list&t=' + Date.now())
      .then(response => response.json())
      .then(res => {
        const feed = document.getElementById('recognition-feed');
        if (!feed) return;

        feed.innerHTML = '';

        if (!res || !res.data || !res.data.length) {
          feed.innerHTML = '<div class="text-muted">No recognition found.</div>';
          return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'list-group list-group-flush';

        res.data.forEach(item => {
          const row = document.createElement('div');
          row.className = 'list-group-item recognition-entry';
          row.dataset.senderId = item.sender_id || '';
          row.dataset.senderName = item.sender_name || '';
          row.dataset.receiverId = item.receiver_id || '';
          row.dataset.receiverName = item.receiver_name || '';
          row.innerHTML = `
            <div class="recognition-entry-header">
              <div class="recognition-entry-people">
                <strong>${escapeHtml(item.sender_name || item.sender_id)}</strong>
                <span class="recognition-entry-arrow" aria-hidden="true">&rarr;</span>
                <strong>${escapeHtml(item.receiver_name || item.receiver_id)}</strong>
              </div>
              <span class="badge badge-success recognition-entry-points">+${escapeHtml(item.points || 0)} pts</span>
            </div>
            <p class="recognition-entry-message">${escapeHtml(item.message || 'No message provided.')}</p>
            <small class="text-muted recognition-entry-time"><i class="fas fa-clock mr-1"></i>${escapeHtml(item.created_at || '')}</small>
          `;
          wrapper.appendChild(row);
        });

        feed.appendChild(wrapper);
      })
      .catch(err => {
        console.error('Failed to load recognition feed', err);
      });
  }

  function loadBadges() {
    fetch(recognitionApiBase + '?resource=badge&t=' + Date.now(), { cache: 'no-store' })
      .then(response => response.json())
      .then(res => {
        const feed = document.getElementById('badges-feed');
        const badgeSelect = document.getElementById('badge_id');
        if (!feed) return;
        feed.innerHTML = '';
        if (badgeSelect) badgeSelect.innerHTML = '<option value="">Select badge</option>';

        if (!res || !res.data || !res.data.length) {
          feed.innerHTML = '<div class="text-muted">No badges found.</div>';
          return;
        }

        res.data.forEach(item => {
          const row = document.createElement('div');
          row.className = 'list-group-item';
          row.innerHTML = `<b>${escapeHtml(item.name)}</b><br><span>${escapeHtml(item.description || '')}</span>`;
          feed.appendChild(row);
          if (badgeSelect) {
            const option = document.createElement('option');
            option.value = item.eer_badge_id || item.id;
            option.textContent = item.name;
            badgeSelect.appendChild(option);
          }
        });
      });
  }

  function loadAwardHistory() {
    fetch('../api/index.php?resource=award_history')
      .then(response => response.json())
      .then(res => {
        const feed = document.getElementById('award-history-feed');
        if (!feed) return;
        feed.innerHTML = '';

        if (!res || !res.data || !res.data.length) {
          feed.innerHTML = '<div class="text-muted">No award history found.</div>';
          return;
        }

        res.data.forEach(item => {
          const row = document.createElement('div');
          row.className = 'list-group-item';
          row.innerHTML = `<b>${escapeHtml(item.award_name || '')}</b> to <b>${escapeHtml(item.employee_name || item.employee_id)}</b> <small class='text-muted float-right'>${escapeHtml(item.awarded_at || '')}</small>`;
          feed.appendChild(row);
        });
      });
  }

  function loadRewards() {
    fetch(recognitionApiBase + '?resource=reward')
      .then(response => response.json())
      .then(res => {
        const feed = document.getElementById('rewards-feed');
        if (!feed) return;
        feed.innerHTML = '';

        if (!res || !res.data || !res.data.length) {
          feed.innerHTML = '<div class="text-muted">No rewards found.</div>';
          return;
        }

        res.data.forEach(item => {
          const row = document.createElement('div');
          row.className = 'list-group-item';
          row.innerHTML = `<b>${escapeHtml(item.name)}</b> <span class='badge badge-info ml-2'>${escapeHtml(item.points_required || 0)} pts</span><br><span>${escapeHtml(item.description || '')}</span>`;
          feed.appendChild(row);
        });
      });
  }

  function loadRewardRedemptions() {
    fetch(recognitionApiBase + '?resource=reward_redemption')
      .then(response => response.json())
      .then(res => {
        const feed = document.getElementById('reward-redemptions-feed');
        if (!feed) return;
        feed.innerHTML = '';

        if (!res || !res.data || !res.data.length) {
          feed.innerHTML = '<div class="text-muted">No reward redemptions found.</div>';
          return;
        }

        res.data.forEach(item => {
          const row = document.createElement('div');
          row.className = 'list-group-item';
          row.innerHTML = `<b>${escapeHtml(item.employee_name || item.employee_id)}</b> redeemed <b>${escapeHtml(item.reward_name || item.reward_id)}</b> <small class='text-muted float-right'>${escapeHtml(item.redeemed_at || '')}</small>`;
          feed.appendChild(row);
        });
      });
  }

  function loadEmployeeBadges() {
    fetch(recognitionApiBase + '?resource=employee_badge&t=' + Date.now(), { cache: 'no-store' })
      .then(response => response.json())
      .then(res => {
        const feed = document.getElementById('employee-badges-feed');
        if (!feed) return;
        feed.innerHTML = '';

        if (!res || !res.data || !res.data.length) {
          feed.innerHTML = '<div class="text-muted">No employee badges found.</div>';
          return;
        }

        res.data.forEach(item => {
          const row = document.createElement('div');
          row.className = 'list-group-item';
          row.innerHTML = `<b>${escapeHtml(item.employee_name || item.employee_id)}</b> earned <b>${escapeHtml(item.badge_name || item.badge_id)}</b> <small class='text-muted float-right'>${escapeHtml(item.earned_at || '')}</small>`;
          feed.appendChild(row);
        });
      });
  }

  function loadPerformanceRecommendations() {
    fetch('../api/index.php?resource=recognition&action=recommendations&limit=10')
      .then(response => response.json())
      .then(res => {
        const container = document.getElementById('performance-recommendations-list');
        if (!container) return;

        container.innerHTML = '';

        if (!res || !res.data || !res.data.length) {
          container.innerHTML = '<p class="text-muted text-center m-2">No recommendations available yet.</p>';
          return;
        }

        const ul = document.createElement('ul');
        ul.className = 'list-group list-group-flush';

        res.data.forEach(item => {
          const li = document.createElement('li');
          li.className = 'list-group-item d-flex justify-content-between align-items-center';
          li.innerHTML = `
            <div>
              <strong>${escapeHtml(item.employee_name || item.employee_id)}</strong>
              <div class="text-muted small">
                ${escapeHtml(item.evaluation_period || 'Performance Report')} • Grade: ${escapeHtml(item.final_grade || 'N/A')} • Score: ${escapeHtml(item.final_rating_percent || 'N/A')}%
              </div>
              ${item.period_end ? '<div class="text-muted extra-small">Period end: ' + escapeHtml(item.period_end) + '</div>' : ''}
            </div>
            <button class="btn btn-sm btn-outline-success recommend-recognize" data-employee-id="${escapeHtml(item.employee_id)}" data-employee-name="${escapeHtml(item.employee_name)}">Recognize</button>
          `;
          ul.appendChild(li);
        });

        container.appendChild(ul);
      });
  }

  function renderEmployeesWithoutReportsPagination(container, totalPages) {
    if (!container) return;

    container.innerHTML = '';
    if (totalPages <= 1) return;

    const nav = document.createElement('nav');
    nav.setAttribute('aria-label', 'Employees without reports pagination');

    const ul = document.createElement('ul');
    ul.className = 'pagination pagination-sm justify-content-center mb-0';

    const prevLi = document.createElement('li');
    prevLi.className = 'page-item' + (employeesWithoutReportsPageIndex <= 0 ? ' disabled' : '');
    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'page-link';
    prevBtn.textContent = 'Previous';
    prevBtn.disabled = employeesWithoutReportsPageIndex <= 0;
    prevBtn.addEventListener('click', function () {
      if (employeesWithoutReportsPageIndex > 0) {
        employeesWithoutReportsPageIndex -= 1;
        loadEmployeesWithoutReports();
      }
    });
    prevLi.appendChild(prevBtn);
    ul.appendChild(prevLi);

    for (let i = 0; i < totalPages; i++) {
      const pageLi = document.createElement('li');
      pageLi.className = 'page-item' + (i === employeesWithoutReportsPageIndex ? ' active' : '');

      const pageBtn = document.createElement('button');
      pageBtn.type = 'button';
      pageBtn.className = 'page-link';
      pageBtn.textContent = String(i + 1);
      pageBtn.disabled = i === employeesWithoutReportsPageIndex;
      pageBtn.addEventListener('click', function () {
        employeesWithoutReportsPageIndex = i;
        loadEmployeesWithoutReports();
      });

      pageLi.appendChild(pageBtn);
      ul.appendChild(pageLi);
    }

    const nextLi = document.createElement('li');
    nextLi.className = 'page-item' + (employeesWithoutReportsPageIndex >= totalPages - 1 ? ' disabled' : '');
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'page-link';
    nextBtn.textContent = 'Next';
    nextBtn.disabled = employeesWithoutReportsPageIndex >= totalPages - 1;
    nextBtn.addEventListener('click', function () {
      if (employeesWithoutReportsPageIndex < totalPages - 1) {
        employeesWithoutReportsPageIndex += 1;
        loadEmployeesWithoutReports();
      }
    });
    nextLi.appendChild(nextBtn);
    ul.appendChild(nextLi);

    nav.appendChild(ul);
    container.appendChild(nav);
  }

  function loadEmployeesWithoutReports() {
    const container = document.getElementById('employees-without-reports-list');
    const paginationContainer = document.getElementById('employees-without-reports-pagination');
    if (!container) return;

    const rawDataEl = document.getElementById('employees-without-reports-data');
    let data = [];

    if (rawDataEl) {
      try {
        data = JSON.parse(rawDataEl.textContent || '[]');
      } catch (err) {
        console.error('Unable to parse employees without reports data', err);
      }
    }

    if (!data.length) {
      container.innerHTML = '<p class="text-muted text-center m-2">All employees have performance report data.</p>';
      if (paginationContainer) paginationContainer.innerHTML = '';
      return;
    }

    employeesWithoutReportsData = data;
    const totalPages = Math.max(1, Math.ceil(employeesWithoutReportsData.length / employeesWithoutReportsPageSize));
    if (employeesWithoutReportsPageIndex >= totalPages) {
      employeesWithoutReportsPageIndex = totalPages - 1;
    }

    const startIndex = employeesWithoutReportsPageIndex * employeesWithoutReportsPageSize;
    const pagedItems = employeesWithoutReportsData.slice(startIndex, startIndex + employeesWithoutReportsPageSize);

    container.innerHTML = '';

    const ul = document.createElement('ul');
    ul.className = 'list-group list-group-flush';

    pagedItems.forEach(item => {
      const li = document.createElement('li');
      li.className = 'list-group-item recognition-summary-item';
      li.innerHTML = `
        <div class="recognition-summary-content">
          <strong>${escapeHtml(item.employee_name || item.employee_id)}</strong>
          <div class="recognition-summary-meta">${escapeHtml(item.department || 'No department')}</div>
        </div>
        <span class="badge badge-danger recognition-status-badge">No Report</span>
      `;
      ul.appendChild(li);
    });

    container.appendChild(ul);
    renderEmployeesWithoutReportsPagination(paginationContainer, totalPages);
  }

  function loadTopPerformers() {
    fetch('../api/index.php?resource=recognition&action=performance_leaderboard&limit=10')
      .then(response => response.json())
      .then(res => {
        const container = document.getElementById('top-performers-list');
        if (!container) return;

        container.innerHTML = '';

        if (!res || !res.data || !res.data.length) {
          container.innerHTML = '<p class="text-muted text-center m-2">No top performers found.</p>';
          return;
        }

        const ul = document.createElement('ul');
        ul.className = 'list-group list-group-flush';

        res.data.forEach(item => {
          const score = item.final_rating_percent !== undefined ? item.final_rating_percent : (item.performance_score !== undefined ? item.performance_score : 'N/A');
          const formattedScore = typeof score === 'number' ? Number(score.toFixed(1)) : score;
          const li = document.createElement('li');
          li.className = 'list-group-item recognition-summary-item';
          li.innerHTML = `
            <div class="recognition-summary-content">
              <strong>${escapeHtml(item.employee_name || item.employee_id)}</strong>
              <div class="recognition-summary-meta">Score: ${escapeHtml(formattedScore)}% <span aria-hidden="true">•</span> Grade: ${escapeHtml(item.final_grade || 'N/A')}</div>
            </div>
            <span class="badge badge-success recognition-score-badge">+${escapeHtml(formattedScore)}%</span>
          `;
          ul.appendChild(li);
        });

        container.appendChild(ul);
      });
  }

  function loadEmployeeOfTheMonthCandidates() {
    const requestId = ++employeeMonthCandidatesRequestId;
    const monthSelect = document.getElementById('employee-of-month-month');
    const yearSelect = document.getElementById('employee-of-month-year');
    const month = monthSelect ? monthSelect.value : new Date().getMonth() + 1;
    const year = yearSelect ? yearSelect.value : new Date().getFullYear();

    fetch(recognitionApiBase + '?resource=recognition&action=employee_of_month&month=' + encodeURIComponent(month) + '&year=' + encodeURIComponent(year) + '&t=' + Date.now(), { cache: 'no-store' })
      .then(response => response.json())
      .then(res => {
        if (requestId !== employeeMonthCandidatesRequestId) return;
        const container = document.getElementById('employee-month-candidates-list');
        if (!container) return;

        container.innerHTML = '';

        if (!res || !res.data || !res.data.length) {
          container.innerHTML = '<p class="text-muted text-center">No nominations yet.</p>';
          return;
        }

        res.data.forEach(candidate => {
          const item = document.createElement('div');
          item.className = 'list-group-item d-flex justify-content-between align-items-start candidate-item';
          item.dataset.awardHistoryId = candidate.eer_award_history_id || '';
          item.dataset.employeeId = candidate.employee_id || '';

          const voteButton = candidate.eer_award_history_id && !candidate.has_voted
            ? '<form method="POST" action="" class="mt-2 vote-form"><input type="hidden" name="action" value="vote_employee_month"><input type="hidden" name="award_history_id" value="' + escapeHtml(candidate.eer_award_history_id) + '"><button type="submit" class="btn btn-sm btn-outline-warning"><i class="fas fa-vote-yea"></i> Vote +5</button></form>'
            : (candidate.eer_award_history_id ? '' : '<button type="button" class="btn btn-sm btn-outline-secondary mt-2" disabled>Not Nominated</button>');

          item.innerHTML = `
            <div>
              <strong>${escapeHtml(candidate.employee_name || candidate.employee_id)}</strong><br>
              <small class="text-muted">Department: ${escapeHtml(candidate.department || 'N/A')} • Votes: ${escapeHtml(candidate.votes || 0)} • Performance: ${escapeHtml(candidate.performance_score || 0)}%</small>
              <div class="mt-1">
                <span class="badge ${candidate.status === 'winner' ? 'badge-success' : (candidate.status === 'shortlisted' ? 'badge-info' : 'badge-warning') }">${escapeHtml((candidate.status || 'nominated').charAt(0).toUpperCase() + (candidate.status || 'nominated').slice(1))}</span>
                ${candidate.nomination_reason ? '<span class="text-muted small">Reason: ' + escapeHtml(candidate.nomination_reason) + '</span>' : ''}
              </div>
            </div>
            <div class="text-right candidate-actions">
              <span class="badge badge-info">Recognition: ${escapeHtml(candidate.recognition_total || 0)} pts</span>
              ${candidate.eer_award_history_id ? '<button type="button" class="btn btn-sm btn-outline-danger delete-nomination mt-2" data-award-history-id="' + escapeHtml(candidate.eer_award_history_id) + '"><i class="fas fa-trash"></i> Delete</button>' : ''}
              ${voteButton}
            </div>
          `;

          container.appendChild(item);
        });
      });
  }

  function bindFormEvents() {
    const sendRecognitionForm = document.querySelector('#sendRecognitionModal form');
    if (sendRecognitionForm) {
      sendRecognitionForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const button = sendRecognitionForm.querySelector('button[type="submit"]');
        if (button) {
          button.textContent = 'Sending...';
          button.disabled = true;
        }

        const receiverId = document.getElementById('rec-receiver') ? document.getElementById('rec-receiver').value : null;
        const message = document.getElementById('rec-message') ? document.getElementById('rec-message').value.trim() : '';
        const points = document.getElementById('rec-points') ? parseInt(document.getElementById('rec-points').value, 10) : 10;

        if (!receiverId) {
          alert('Please select a recipient before sending recognition.');
          if (button) {
            button.textContent = 'Send Recognition';
            button.disabled = false;
          }
          return;
        }

        fetch(recognitionApiBase + '?resource=recognition&action=send', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ receiver_id: receiverId, message: message, points: points })
        })
          .then(async response => {
            const text = await response.text();
            let payload;
            try {
              payload = JSON.parse(text);
            } catch (err) {
              throw new Error('Invalid server response: ' + text);
            }

            if (!response.ok) {
              throw new Error(payload.error || 'Server returned status ' + response.status);
            }

            return payload;
          })
          .then(() => {
            if (window.jQuery) jQuery('#sendRecognitionModal').modal('hide');
            const recMessage = document.getElementById('rec-message');
            const recPoints = document.getElementById('rec-points');
            if (recMessage) recMessage.value = '';
            if (recPoints) recPoints.value = 10;
            refreshAllSections();
            alert('Recognition sent successfully.');
          })
          .catch(err => {
            console.error('Failed to send recognition', err);
            alert('Failed to send recognition. ' + err.message);
          })
          .finally(() => {
            if (button) {
              button.textContent = 'Send Recognition';
              button.disabled = false;
            }
          });
      });
    }

    document.addEventListener('submit', function (event) {
      if (event.target && event.target.matches('.vote-form')) {
        event.preventDefault();
        const form = event.target;
        const awardHistoryId = form.querySelector('input[name="award_history_id"]')?.value || null;
        const button = form.querySelector('button[type="submit"]');
        let voteSucceeded = false;

        if (button) {
          button.textContent = 'Voting...';
          button.disabled = true;
        }

        fetch(recognitionApiBase + '?resource=recognition&action=vote_employee_month', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ award_history_id: awardHistoryId })
        })
          .then(response => response.json())
          .then(res => {
            if (res && res.success) {
              voteSucceeded = true;
              refreshAllSections();
              alert('Vote recorded successfully.');
            } else if (res && res.error) {
              alert('Error: ' + res.error);
            }
          })
          .catch(err => {
            console.error('Failed to record vote', err);
            alert('Failed to record vote.');
          })
          .finally(() => {
            if (button && voteSucceeded) {
              button.textContent = 'Voted (+5)';
              button.disabled = true;
            }
            if (button && !voteSucceeded) {
              button.textContent = 'Vote +5';
              button.disabled = false;
            }
          });
      }
    });
  }

  function bindQuickRecognizeAction() {
    document.addEventListener('click', function (event) {
      const target = event.target.closest('.recommend-recognize');
      if (!target) return;

      event.preventDefault();

      const employeeId = target.getAttribute('data-employee-id');
      const receiverSelect = document.getElementById('rec-receiver');
      const sendButton = document.getElementById('send-recognition-btn');

      if (receiverSelect) {
        const foundOption = Array.from(receiverSelect.options).find(option => {
          return option.getAttribute('data-employee-id') === employeeId || option.value === employeeId || option.value === String(employeeId);
        });

        if (!foundOption && employeeId) {
          receiverSelect.innerHTML = '';
          const fallbackOption = document.createElement('option');
          fallbackOption.value = employeeId;
          fallbackOption.setAttribute('data-employee-id', employeeId);
          fallbackOption.textContent = (target.getAttribute('data-employee-name') || 'Employee') + ' (' + employeeId + ')';
          receiverSelect.appendChild(fallbackOption);
          receiverSelect.value = employeeId;
          if (sendButton) sendButton.disabled = false;
        }

        if (foundOption) {
          receiverSelect.value = foundOption.value;
          if (sendButton) sendButton.disabled = false;
        }
      }

      if (window.jQuery) {
        jQuery('#sendRecognitionModal').modal('show');
      }
    });
  }

  function initEmployeeList() {
    const select = document.getElementById('rec-receiver');
    if (!select) return;

    fetch('../api/index.php?resource=employee_list')
      .then(response => response.json())
      .then(list => {
        list.forEach(emp => {
          const option = document.createElement('option');
          option.value = emp.employee_id;
          option.setAttribute('data-employee-id', emp.employee_id);
          option.textContent = emp.full_name + ' (' + emp.employee_id + ')';
          select.appendChild(option);
        });
      })
      .catch(err => {
        console.error('Failed to load employee list', err);
        select.innerHTML = '<option value="">Unable to load employees</option>';
      });
  }

  function initializeRecognitionLoader() {
    if (state.initialized) return;
    state.initialized = true;

    bindTabClicks();
    bindGlobalEvents();
    bindQuickRecognizeAction();
    bindFormEvents();
    initEmployeeList();
    restoreTabFromState(true);
    refreshAllSections();
    startAutoRefresh();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeRecognitionLoader, { once: true });
  } else {
    initializeRecognitionLoader();
  }
})();
