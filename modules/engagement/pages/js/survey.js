(function() {
  const SURVEY_SHARED_STORAGE_KEY = 'engagement:active-tab:survey-tabs';
  const SURVEY_STORAGE_KEY = 'engagement:survey:active-tab';
  const SURVEY_LEGACY_STORAGE_KEY = 'survey-active-tab';
  const validTabIds = ['satisfaction', 'pulse', 'hr-feedback', 'suggestions'];

  // Set only when the page was opened with an explicit ?survey_tab= link.
  // That kind of link is an intentional "go to this tab" instruction and
  // should win over whatever tab was last saved. A plain refresh has no
  // query param, so it will not touch this and will fall through to the
  // saved tab instead.
  let explicitDeepLinkTab = null;

  function normalizeSurveyUrlState() {
    const url = new URL(window.location.href);
    if (url.searchParams.get('page') !== 'survey') {
      return;
    }

    const queryTab = url.searchParams.get('survey_tab');
    const hashTab = url.hash.replace('#', '');
    const storedTab = [
      sessionStorage.getItem(SURVEY_SHARED_STORAGE_KEY),
      sessionStorage.getItem(SURVEY_STORAGE_KEY),
      localStorage.getItem(SURVEY_SHARED_STORAGE_KEY),
      localStorage.getItem(SURVEY_STORAGE_KEY),
      localStorage.getItem(SURVEY_LEGACY_STORAGE_KEY)
    ].find(function(tab) {
      return validTabIds.includes(tab);
    });

    if (validTabIds.includes(queryTab)) {
      explicitDeepLinkTab = queryTab;
      url.hash = '#' + queryTab;
    } else if (hashTab && !validTabIds.includes(hashTab)) {
      url.hash = '#' + (storedTab || 'satisfaction');
    } else if (!hashTab) {
      url.hash = '#' + (storedTab || 'satisfaction');
    }
    url.searchParams.delete('survey_tab');
    window.history.replaceState({}, '', url.toString());
  }

  normalizeSurveyUrlState();

  function persistSurveyTabState(tabId) {
    if (!validTabIds.includes(tabId)) return;
    try {
      sessionStorage.setItem(SURVEY_STORAGE_KEY, tabId);
      sessionStorage.setItem(SURVEY_LEGACY_STORAGE_KEY, tabId);
      sessionStorage.setItem(SURVEY_SHARED_STORAGE_KEY, tabId);
      localStorage.setItem(SURVEY_STORAGE_KEY, tabId);
      localStorage.setItem(SURVEY_LEGACY_STORAGE_KEY, tabId);
      localStorage.setItem(SURVEY_SHARED_STORAGE_KEY, tabId);
    } catch (error) {
      // Ignore storage errors and keep tab navigation usable.
    }
  }

  document.addEventListener('click', function(event) {
    const tabLink = event.target.closest('#survey-tabs .nav-link');
    if (!tabLink) return;
    const href = tabLink.getAttribute('href') || '';
    persistSurveyTabState(href.startsWith('#') ? href.slice(1) : '');
  }, true);

  function getStoredSurveyTab() {
    // An explicit ?survey_tab= link always wins - it's a deliberate
    // "open this tab" instruction from elsewhere in the app.
    if (explicitDeepLinkTab && validTabIds.includes(explicitDeepLinkTab)) {
      return explicitDeepLinkTab;
    }

    // A non-default URL hash is an explicit tab choice, so it must win over
    // browser storage when the page is opened directly on that tab.
    const hashFromUrl = window.location.hash ? window.location.hash.replace('#', '') : '';
    if (hashFromUrl && validTabIds.includes(hashFromUrl)) {
      return hashFromUrl;
    }

    const storageKeys = [
      SURVEY_SHARED_STORAGE_KEY,
      SURVEY_STORAGE_KEY,
      SURVEY_LEGACY_STORAGE_KEY
    ];

    // Saved tab state comes next. It's checked before the URL hash on
    // purpose: on a plain refresh the hash *should* still say e.g. "#pulse",
    // but if anything on the page resets/clears the hash before this runs,
    // localStorage/sessionStorage are unaffected and still remember the
    // tab the person was actually on.
    for (const key of storageKeys) {
      try {
        const savedTab = sessionStorage.getItem(key);
        if (savedTab && validTabIds.includes(savedTab)) {
          return savedTab;
        }
      } catch (error) {
        // ignore storage errors
      }
    }

    for (const key of storageKeys) {
      try {
        const savedTab = localStorage.getItem(key);
        if (savedTab && validTabIds.includes(savedTab)) {
          return savedTab;
        }
      } catch (error) {
        // ignore storage errors
      }
    }

    if (hashFromUrl && validTabIds.includes(hashFromUrl)) {
      return hashFromUrl;
    }

    return 'satisfaction';
  }

  function persistSurveyTab(tabId) {
    const validTabId = validTabIds.includes(tabId) ? tabId : 'satisfaction';

    try {
      persistSurveyTabState(validTabId);

      const nextUrl = new URL(window.location.href);
      nextUrl.searchParams.delete('survey_tab');
      nextUrl.hash = '#' + validTabId;
      if (window.location.href !== nextUrl.href) {
        window.history.replaceState({}, '', nextUrl.toString());
      }
    } catch (error) {
      console.warn('Unable to save active survey tab.', error);
    }
  }

  function applySurveyTab(tabId) {
    const validTabId = validTabIds.includes(tabId) ? tabId : 'satisfaction';
    const targetTab = document.querySelector('#survey-tabs a[href="#' + CSS.escape(validTabId) + '"]');
    const targetPane = document.getElementById(validTabId);

    if (!targetTab || !targetPane) {
      return;
    }

    document.querySelectorAll('#survey-tabs .nav-link').forEach(function(tab) {
      const isActive = tab === targetTab;
      tab.classList.toggle('active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    document.querySelectorAll('#survey-tab-content .tab-pane').forEach(function(pane) {
      const isActive = pane === targetPane;
      pane.classList.toggle('show', isActive);
      pane.classList.toggle('active', isActive);
    });

    persistSurveyTab(validTabId);
  }

  // The tabs card starts hidden (inline style in the HTML) so nothing
  // flashes on screen before the correct tab is selected. This reveals it.
  function revealSurveyTabsCard() {
    const card = document.getElementById('survey-tabs-card');
    if (card) {
      card.style.visibility = '';
    }
  }

  function restoreSurveyTab() {
    const savedTab = getStoredSurveyTab();
    const currentUrl = new URL(window.location.href);
    if (currentUrl.searchParams.get('page') === 'survey' && currentUrl.hash !== '#' + savedTab) {
      currentUrl.hash = '#' + savedTab;
      window.history.replaceState({}, '', currentUrl.toString());
    }

    const targetTab = document.querySelector('#survey-tabs a[href="#' + CSS.escape(savedTab) + '"]');
    const targetPane = document.getElementById(savedTab);

    if (targetTab && targetPane) {
      applySurveyTab(savedTab);
      revealSurveyTabsCard();
      return;
    }

    let retryCount = 0;
    const maxRetries = 8;

    function retry() {
      const retryTab = document.querySelector('#survey-tabs a[href="#' + CSS.escape(savedTab) + '"]');
      const retryPane = document.getElementById(savedTab);

      if (retryTab && retryPane) {
        applySurveyTab(savedTab);
        revealSurveyTabsCard();
        return;
      }

      if (retryCount < maxRetries) {
        retryCount += 1;
        setTimeout(retry, 120);
      } else {
        // Give up trying to match a saved tab - reveal anyway so the
        // page doesn't stay hidden forever.
        revealSurveyTabsCard();
      }
    }

    retry();
  }

  // Absolute safety net: no matter what happens above, never leave the
  // card hidden for more than a moment.
  setTimeout(revealSurveyTabsCard, 2000);

  function initTabClickHandlers() {
    document.querySelectorAll('#survey-tabs .nav-link').forEach(function(tabLink) {
      if (tabLink.dataset.surveyTabBound === 'true') {
        return;
      }

      tabLink.dataset.surveyTabBound = 'true';
      tabLink.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();

        const href = this.getAttribute('href');
        if (!href || !href.startsWith('#')) {
          return;
        }

        const tabId = href.replace('#', '');
        if (!validTabIds.includes(tabId)) {
          return;
        }

        persistSurveyTab(tabId);
        applySurveyTab(tabId);
      });
    });
  }

  function initSurveyFormHandlers() {
    document.querySelectorAll('form.survey-form[data-skip="true"], form.pulse-survey-form[data-skip="true"]').forEach(function(form) {
      if (form.dataset.surveySubmitBound === 'true') {
        return;
      }

      form.dataset.surveySubmitBound = 'true';
      form.addEventListener('submit', function(event) {
        event.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.innerHTML : '';
        const surveyTypeInput = form.querySelector('input[name="survey_type"]');
        const selectedTab = surveyTypeInput && surveyTypeInput.value === 'pulse' ? 'pulse' : 'satisfaction';

        if (submitButton) {
          submitButton.disabled = true;
          submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const apiUrl = window.location.pathname.split('/modules/engagement/')[0]
          + '/modules/engagement/api/survey.php?action=create';

        fetch(apiUrl, {
          method: form.getAttribute('method') || 'POST',
          body: new FormData(form),
          credentials: 'same-origin'
        })
          .then(function(response) {
            return response.json().then(function(data) {
              if (!response.ok || !data.success) {
                throw new Error(data.error || 'Survey submission failed.');
              }
              return data;
            });
          })
          .then(function() {
            form.reset();
            applySurveyTab(selectedTab);
            window.location.reload();
          })
          .catch(function(error) {
            window.alert(error.message || 'Unable to create the survey.');
          })
          .finally(function() {
            if (submitButton) {
              submitButton.disabled = false;
              submitButton.innerHTML = originalText;
            }
          });
      });
    });
  }

  function escapeSurveyHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function(character) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
    });
  }

  function surveyApiUrl(action) {
    return window.location.pathname.split('/modules/engagement/')[0]
      + '/modules/engagement/api/survey.php?action=' + action;
  }

  function renderSurveyEmployees(employees) {
    const select = document.getElementById('feedback-employee');
    if (!select || !Array.isArray(employees) || employees.length === 0) return;
    select.innerHTML = '<option value="">Select an employee</option>' + employees.map(function(employee) {
      const names = [employee.first_name, employee.middle_name, employee.last_name].filter(Boolean).join(' ');
      const name = employee.full_name || employee.name || names || ('Employee #' + (employee.employee_id || 'Unknown'));
      const department = employee.department || employee.department_name;
      return '<option value="' + Number(employee.employee_id || 0) + '">' + escapeSurveyHtml(name + (department ? ' (' + department + ')' : '')) + '</option>';
    }).join('');
  }

  function isSuggestion(feedback) {
    const comment = String(feedback.comment || '').toLowerCase();
    const category = String(feedback.category || '').toLowerCase();
    const type = String(feedback.evaluator_type || '').toLowerCase();
    return ['work_environment', 'management', 'policies', 'colleagues', 'compensation', 'work_life_balance', 'other'].includes(category)
      || type === 'suggestion' || /suggest|improve|better|recommend/.test(comment);
  }

  function renderFeedback(feedback) {
    const suggestions = (Array.isArray(feedback) ? feedback : []).filter(isSuggestion);
    const list = document.getElementById('suggestions-list');
    if (list) {
      list.innerHTML = suggestions.length ? suggestions.slice(0, 15).map(function(item) {
        const category = String(item.category || 'other').replace(/_/g, ' ');
        const author = item.is_anonymous ? 'Anonymous Submission' : 'From: ' + (item.employee_name || 'Unknown');
        return '<div class="card mb-3 suggestion-item" data-category="' + escapeSurveyHtml(item.category || 'other') + '" data-rating="' + Number(item.rating || 0) + '"><div class="card-body pb-2">'
          + '<div class="d-flex justify-content-between align-items-start mb-2"><span class="badge badge-pill badge-success">' + escapeSurveyHtml(category.charAt(0).toUpperCase() + category.slice(1)) + '</span><small class="text-muted">Rating: ' + escapeSurveyHtml(item.rating || 'N/A') + '</small></div>'
          + '<p class="mb-2">' + escapeSurveyHtml(item.comment || '').replace(/\n/g, '<br>') + '</p><small class="text-muted"><i class="fas fa-user-secret mr-1"></i>' + escapeSurveyHtml(author) + ' | <i class="fas fa-calendar mr-1"></i>' + escapeSurveyHtml(item.evaluation_date || 'Recent') + '</small>'
          + '</div></div>';
      }).join('') : '<div class="text-center text-muted py-4"><i class="fas fa-lightbulb fa-3x mb-3 text-warning"></i><p>No suggestions collected yet</p><small>Suggestions will appear here as employees submit feedback with improvement ideas</small></div>';
    }

    const counts = {};
    suggestions.forEach(function(item) { const category = item.category || 'other'; counts[category] = (counts[category] || 0) + 1; });
    const categoryAnalytics = document.getElementById('suggestion-category-analytics');
    if (categoryAnalytics) {
      categoryAnalytics.innerHTML = Object.keys(counts).length ? Object.keys(counts).sort(function(a, b) { return counts[b] - counts[a]; }).slice(0, 7).map(function(category) {
        return '<div class="d-flex justify-content-between align-items-center mb-2"><span>' + escapeSurveyHtml(category.replace(/_/g, ' ')) + '</span><span class="badge badge-primary">' + counts[category] + '</span></div>';
      }).join('') : '<p class="text-muted small">No categories yet</p>';
    }

    const ratings = suggestions.map(function(item) { return Number(item.rating || 0); });
    const highQuality = ratings.filter(function(rating) { return rating >= 4; }).length;
    const average = ratings.length ? ratings.reduce(function(total, rating) { return total + rating; }, 0) / ratings.length : 0;
    const quality = document.getElementById('suggestion-quality-analytics');
    if (quality) quality.innerHTML = '<div class="mb-2"><strong>Total Suggestions:</strong><br><span class="text-primary">' + suggestions.length + '</span></div><div class="mb-2"><strong>Avg Quality Rating:</strong><br><span class="text-info">' + average.toFixed(1) + ' ⭐</span></div><div><strong>Quality Suggestions:</strong><br><span class="text-success">' + highQuality + ' (' + (suggestions.length ? Math.round(highQuality / suggestions.length * 100) : 0) + '%)</span></div>';
  }

  function renderFeedbackHistory(feedback) {
    const list = document.getElementById('feedback-history-list');
    if (!list) return;

    const entries = (Array.isArray(feedback) ? feedback : []).filter(function(item) {
      return String(item.evaluator_type || '').toLowerCase() === 'hr';
    });

    if (!entries.length) {
      list.innerHTML = '<p class="text-muted text-center py-4 mb-0">No HR feedback submitted yet.</p>';
      return;
    }

    list.innerHTML = '<table class="table table-sm mb-0 feedback-history-table">' +
      '<thead><tr><th>Employee</th><th>Category</th><th>Rating</th><th>Date</th></tr></thead><tbody>' +
      entries.slice(0, 25).map(function(item) {
        const category = String(item.category || 'Other').replace(/_/g, ' ');
        const rating = Number(item.rating || 0);
        const date = item.evaluation_date || item.created_at || 'Recent';
        return '<tr>' +
          '<td>' + escapeSurveyHtml(item.employee_name || 'Unknown') + '</td>' +
          '<td>' + escapeSurveyHtml(category.charAt(0).toUpperCase() + category.slice(1)) + '</td>' +
          '<td>' + escapeSurveyHtml(rating ? rating + '/5' : 'N/A') + '</td>' +
          '<td>' + escapeSurveyHtml(date) + '</td>' +
          '</tr><tr><td colspan="4"><strong>Feedback:</strong> ' + escapeSurveyHtml(item.comment || item.comments || 'No comments available').replace(/\n/g, '<br>') + '</td></tr>';
      }).join('') +
      '</tbody></table>';
  }

  function loadSurveyPageData() {
    fetch(surveyApiUrl('page_data'), {credentials: 'same-origin', cache: 'no-store'})
      .then(function(response) {
        if (!response.ok) throw new Error('Unable to load survey data.');
        return response.json();
      })
      .then(function(result) {
        if (!result.success) throw new Error(result.error || 'Unable to load survey data.');
        const data = result.data || {};
        window.surveyPageData = data;
        renderSurveyEmployees(data.employees);
        renderFeedback(data.feedback);
        renderFeedbackHistory(data.feedback);
      })
      .catch(function(error) { console.warn('[Survey] API page-data load failed:', error.message); });
  }

  function initFeedbackForms() {
    document.querySelectorAll('.hr-feedback-form, .suggestion-form').forEach(function(form) {
      if (form.dataset.feedbackApiBound === 'true') return;
      form.dataset.feedbackApiBound = 'true';
      form.addEventListener('submit', function(event) {
        event.preventDefault();
        if (form.dataset.submitting === '1') return;

        if (!form.reportValidity()) return;

        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton ? submitButton.innerHTML : '';
        form.dataset.submitting = '1';
        if (submitButton) {
          submitButton.disabled = true;
          submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
        }

        const data = Object.fromEntries(new FormData(form).entries());
        data.comment = data.comments || data.comment || '';
        data.evaluator_type = form.classList.contains('suggestion-form') ? 'Suggestion' : 'HR';
        fetch(surveyApiUrl('feedback'), {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)})
          .then(function(response) { return response.json().then(function(result) { if (!response.ok || !result.success) throw new Error(result.error || 'Unable to save feedback.'); return result; }); })
          .then(function() { form.reset(); loadSurveyPageData(); })
          .catch(function(error) { window.alert(error.message); })
          .finally(function() {
            form.dataset.submitting = '0';
            if (submitButton) {
              submitButton.disabled = false;
              submitButton.innerHTML = originalButtonText;
            }
          });
      });
    });
  }

  function initFeedbackHistoryToggle() {
    const toggle = document.querySelector('.feedback-history-toggle');
    const modal = document.getElementById('feedbackHistoryDetails');
    const closeButton = modal ? modal.querySelector('.feedback-history-close') : null;

    if (!toggle || !modal) return;

    toggle.addEventListener('click', function() {
      const isHidden = modal.hidden;
      modal.hidden = !isHidden;
      document.body.classList.toggle('feedback-history-modal-open', isHidden);
      toggle.setAttribute('aria-expanded', String(isHidden));
      toggle.innerHTML = isHidden
        ? '<i class="fas fa-times mr-1"></i>Hide History'
        : '<i class="fas fa-clock mr-1"></i>Feedback History';
    });

    if (closeButton) {
      closeButton.addEventListener('click', function() {
        modal.hidden = true;
        document.body.classList.remove('feedback-history-modal-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = '<i class="fas fa-clock mr-1"></i>Feedback History';
      });
    }
  }

  function initSurveyPage() {
    const surveyAreas = document.querySelectorAll('.survey-area');
    surveyAreas.forEach(function(area, index) {
      if (index > 0) {
        area.remove();
      }
    });

    if (!document.querySelector('.survey-area')) {
      return;
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'view_results') {
      const analyticsTab = document.getElementById('analytics-tab');
      if (analyticsTab) {
        analyticsTab.click();
        setTimeout(function() {
          const analyticsSection = document.getElementById('analytics');
          if (analyticsSection) {
            analyticsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }, 100);
      }
    }

    initTabClickHandlers();
    initSurveyFormHandlers();
    initFeedbackForms();
    initFeedbackHistoryToggle();
    loadSurveyPageData();
    restoreSurveyTab();

    window.addEventListener('load', function() {
      if (document.getElementById('survey-tabs')) {
        restoreSurveyTab();
      }
    });

    window.addEventListener('pageshow', function() {
      if (document.getElementById('survey-tabs')) {
        restoreSurveyTab();
      }
    });

    // Defensive re-checks: if some other script on the page (framework
    // init, another tab plugin, etc.) flips the active tab back to the
    // default shortly after our restore, re-apply the saved tab. This is
    // a no-op in the normal case since the tab will already match.
    [300, 800, 1500].forEach(function(delay) {
      setTimeout(function() {
        if (!document.getElementById('survey-tabs')) return;
        const savedTab = getStoredSurveyTab();
        const currentActive = document.querySelector('#survey-tabs .nav-link.active');
        const currentTabId = currentActive
          ? (currentActive.getAttribute('href') || '').replace('#', '')
          : '';
        if (savedTab !== currentTabId) {
          applySurveyTab(savedTab);
        }
      }, delay);
    });

    window.addEventListener('hashchange', function() {
      const hashTab = window.location.hash ? window.location.hash.replace('#', '') : '';
      if (hashTab && validTabIds.includes(hashTab)) {
        applySurveyTab(hashTab);
      }
    }, { once: false });

    window.addEventListener('popstate', function() {
      const hashTab = window.location.hash ? window.location.hash.replace('#', '') : '';
      if (hashTab && validTabIds.includes(hashTab)) {
        applySurveyTab(hashTab);
      } else {
        restoreSurveyTab();
      }
    }, { once: false });

    document.querySelectorAll('.sort-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const sortType = this.dataset.sort;
        const suggestions = Array.from(document.querySelectorAll('.suggestion-item'));

        suggestions.sort(function(a, b) {
          switch (sortType) {
            case 'newest':
              return 0;
            case 'highest':
              return parseInt(b.dataset.rating, 10) - parseInt(a.dataset.rating, 10);
            case 'lowest':
              return parseInt(a.dataset.rating, 10) - parseInt(b.dataset.rating, 10);
            default:
              return 0;
          }
        });

        const container = document.querySelector('.suggestions-container');
        if (container) {
          suggestions.forEach(function(item) {
            container.appendChild(item);
          });
        }

        document.querySelectorAll('.sort-btn').forEach(function(button) {
          button.classList.remove('active');
        });
        this.classList.add('active');
      });
    });

    const categoryFilter = document.getElementById('category-filter');
    if (categoryFilter) {
      categoryFilter.addEventListener('change', function() {
        const filterValue = this.value;
        document.querySelectorAll('.suggestion-item').forEach(function(item) {
          if (!filterValue || item.dataset.category === filterValue) {
            item.style.display = 'block';
          } else {
            item.style.display = 'none';
          }
        });
      });
    }
  }

  // The page loader replaces the container after this module has loaded.
  // Initialize the survey again when that fragment arrives.
  window.addEventListener('page:loaded', function(e) {
    if (e.detail && e.detail.page === 'survey') {
      setTimeout(initSurveyPage, 100);
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSurveyPage, { once: true });
  } else {
    initSurveyPage();
  }
})();