(function() {
  const SURVEY_STORAGE_KEY = 'engagement:survey:active-tab';
  const SURVEY_LEGACY_STORAGE_KEY = 'survey-active-tab';
  const validTabIds = ['satisfaction', 'pulse', 'hr-feedback', 'suggestions'];

  function getStoredSurveyTab() {
    const storageKeys = [SURVEY_STORAGE_KEY, SURVEY_LEGACY_STORAGE_KEY];

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

    const hashFromUrl = window.location.hash ? window.location.hash.replace('#', '') : '';
    if (hashFromUrl && validTabIds.includes(hashFromUrl)) {
      return hashFromUrl;
    }

    return 'satisfaction';
  }

  function persistSurveyTab(tabId) {
    const validTabId = validTabIds.includes(tabId) ? tabId : 'satisfaction';

    try {
      sessionStorage.setItem(SURVEY_STORAGE_KEY, validTabId);
      sessionStorage.setItem(SURVEY_LEGACY_STORAGE_KEY, validTabId);
      localStorage.setItem(SURVEY_STORAGE_KEY, validTabId);
      localStorage.setItem(SURVEY_LEGACY_STORAGE_KEY, validTabId);

      const nextHash = '#' + validTabId;
      if (window.location.hash !== nextHash) {
        window.history.replaceState({}, '', window.location.pathname + window.location.search + nextHash);
      }
    } catch (error) {
      console.warn('Unable to save active survey tab.', error);
    }
  }

  function applySurveyTab(tabId) {
    const validTabId = validTabIds.includes(tabId) ? tabId : 'satisfaction';
    const targetTab = document.querySelector('#survey-tabs a[href="#' + validTabId + '"]');
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

  function restoreSurveyTab() {
    const savedTab = getStoredSurveyTab();
    const targetTab = document.querySelector('#survey-tabs a[href="#' + savedTab + '"]');
    const targetPane = document.getElementById(savedTab);

    if (targetTab && targetPane) {
      applySurveyTab(savedTab);
      return;
    }

    let retryCount = 0;
    const maxRetries = 8;

    function retry() {
      const retryTab = document.querySelector('#survey-tabs a[href="#' + savedTab + '"]');
      const retryPane = document.getElementById(savedTab);

      if (retryTab && retryPane) {
        applySurveyTab(savedTab);
        return;
      }

      if (retryCount < maxRetries) {
        retryCount += 1;
        setTimeout(retry, 120);
      } else {
        applySurveyTab('satisfaction');
      }
    }

    retry();
  }

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
    restoreSurveyTab();

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
