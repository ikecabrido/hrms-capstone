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
    } else if (!validTabIds.includes(hashTab)) {
      return;
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

    // Only fall back to the hash (e.g. someone bookmarked #pulse directly)
    // when there is nothing saved yet.
    const hashFromUrl = window.location.hash ? window.location.hash.replace('#', '') : '';
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

        fetch(form.getAttribute('action') || window.location.href, {
          method: form.getAttribute('method') || 'POST',
          body: new FormData(form),
          credentials: 'same-origin'
        })
          .then(function(response) {
            if (!response.ok) {
              throw new Error('Survey submission failed.');
            }

            const nextUrl = new URL(window.location.href);
            nextUrl.searchParams.set('_survey_refresh', Date.now().toString());
            nextUrl.hash = '#' + selectedTab;
            window.location.replace(nextUrl.toString());
          })
          .catch(function(error) {
            window.alert(error.message || 'Unable to create the survey.');
            if (submitButton) {
              submitButton.disabled = false;
              submitButton.innerHTML = originalText;
            }
          });
      });
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