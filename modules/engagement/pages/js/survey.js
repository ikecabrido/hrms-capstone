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
    const queryTab = url.searchParams.get('survey_tab');
    const hashTab = url.hash.replace('#', '');
    if (validTabIds.includes(queryTab)) {
      explicitDeepLinkTab = queryTab;
      url.hash = '#' + queryTab;
    } else if (hashTab && !validTabIds.includes(hashTab)) {
      url.hash = '#satisfaction';
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
    if (hashFromUrl && validTabIds.includes(hashFromUrl) && hashFromUrl !== 'satisfaction') {
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
            return refreshCreatedSurveyList(selectedTab);
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

  function refreshCreatedSurveyList(surveyType) {
    const list = document.getElementById(surveyType === 'pulse' ? 'pulse-surveys-list' : 'satisfaction-surveys-list');
    if (!list) return Promise.resolve();

    const apiUrl = window.location.pathname.split('/modules/engagement/')[0]
      + '/modules/engagement/api/survey.php?action=list&t=' + Date.now();

    return fetch(apiUrl, { credentials: 'same-origin', cache: 'no-store' })
      .then(function(response) {
        if (!response.ok) throw new Error('Unable to refresh survey list.');
        return response.json();
      })
      .then(function(surveys) {
        const matchingSurveys = (Array.isArray(surveys) ? surveys : []).filter(function(survey) {
          return (survey.survey_type || 'satisfaction') === surveyType;
        });

        if (!matchingSurveys.length) {
          list.innerHTML = surveyType === 'pulse'
            ? '<div class="text-center text-muted py-4"><i class="fas fa-bolt fa-3x mb-3 text-warning"></i><p>No active pulse surveys</p><small>Create your first pulse survey to get quick feedback from employees</small></div>'
            : '<p class="text-muted">No satisfaction surveys yet.</p>';
          return;
        }

        if (surveyType === 'pulse') {
          list.innerHTML = '<div class="row">' + matchingSurveys.map(function(survey) {
            const surveyId = Number(survey.eer_survey_id || 0);
            return '<div class="col-md-6 mb-3"><div class="card border-warning"><div class="card-body">'
              + '<h6 class="card-title">' + escapeSurveyHtml(survey.title) + '</h6>'
              + '<p class="card-text small text-muted">Created: ' + escapeSurveyHtml(survey.created_at || 'N/A')
              + '<br>Anonymous: ' + (survey.is_anonymous ? 'Yes' : 'No') + '</p>'
              + '<div class="btn-group btn-group-sm"><a class="btn btn-outline-info" href="/hrms-capstone/modules/engagement/pages/survey_view.php?module=survey&action=view&id=' + surveyId + '">Take Survey</a></div>'
              + '</div></div></div>';
          }).join('') + '</div>';
          return;
        }

        list.innerHTML = '<div class="list-group">' + matchingSurveys.map(function(survey) {
          const surveyId = Number(survey.eer_survey_id || 0);
          return '<div class="list-group-item d-flex justify-content-between align-items-center">'
            + '<div><strong>' + escapeSurveyHtml(survey.title) + '</strong><br>'
            + '<small class="text-muted">Created: ' + escapeSurveyHtml(survey.created_at || 'N/A')
            + ' | Anonymous: ' + (survey.is_anonymous ? 'Yes' : 'No') + '</small></div>'
            + '<div class="btn-group" role="group"><a class="btn btn-sm btn-info" href="/hrms-capstone/modules/engagement/pages/survey_view.php?module=survey&action=view&id=' + surveyId + '">View</a></div></div>';
        }).join('') + '</div>';
      });
  }

  function initSurveyPage() {
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

    window.addEventListener('page:loaded', function(e) {
      if (e.detail && e.detail.page === 'survey') {
        setTimeout(function() {
          initTabClickHandlers();
          initSurveyFormHandlers();
          restoreSurveyTab();
        }, 100);
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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSurveyPage, { once: true });
  } else {
    initSurveyPage();
  }
})();