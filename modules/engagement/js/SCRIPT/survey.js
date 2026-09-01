(function() {
  if (window.__engagementSurveyPageInit) {
    return;
  }
  window.__engagementSurveyPageInit = true;

  document.addEventListener('DOMContentLoaded', function() {
    const SURVEY_STORAGE_KEY = 'survey-active-tab';
    const SURVEY_LEGACY_STORAGE_KEY = 'engagement:survey:active-tab';
    const validTabIds = ['satisfaction', 'pulse', 'hr-feedback', 'suggestions'];

    function getStoredSurveyTab() {
      for (const key of [SURVEY_STORAGE_KEY, SURVEY_LEGACY_STORAGE_KEY]) {
        try {
          const savedTab = sessionStorage.getItem(key);
          if (savedTab && validTabIds.includes(savedTab) && document.getElementById(savedTab)) {
            return savedTab;
          }
        } catch (error) {
          // Ignore storage errors.
        }
      }

      for (const key of [SURVEY_STORAGE_KEY, SURVEY_LEGACY_STORAGE_KEY]) {
        try {
          const savedTab = localStorage.getItem(key);
          if (savedTab && validTabIds.includes(savedTab) && document.getElementById(savedTab)) {
            return savedTab;
          }
        } catch (error) {
          // Ignore storage errors.
        }
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
      } catch (error) {
        console.warn('Unable to save active survey tab.', error);
      }
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

    function activateSurveyTabFromHash() {
      const hash = (window.location.hash || '').replace('#', '');
      let tabId = 'satisfaction';
      const storedTab = getStoredSurveyTab();
      if (hash && validTabIds.includes(hash) && document.getElementById(hash)) {
        tabId = hash;
      } else if (storedTab && validTabIds.includes(storedTab) && document.getElementById(storedTab)) {
        tabId = storedTab;
      }

      const targetTab = document.querySelector('#survey-tabs a[href="#' + CSS.escape(tabId) + '"]');
      const targetPane = document.getElementById(tabId);
      if (!targetTab || !targetPane) {
        return;
      }

      if (window.location.hash !== '#' + tabId) {
        history.replaceState(null, null, '#' + tabId);
      }

      persistSurveyTab(tabId);

      document.querySelectorAll('#survey-tabs .nav-link').forEach(function(navLink) {
        navLink.classList.remove('active');
        navLink.setAttribute('aria-selected', 'false');
      });
      document.querySelectorAll('.survey-area .tab-pane').forEach(function(tabPane) {
        tabPane.classList.remove('show', 'active');
      });

      targetTab.classList.add('active');
      targetTab.setAttribute('aria-selected', 'true');
      targetPane.classList.add('show', 'active');
    }

    const savedTab = getStoredSurveyTab();
    if (savedTab && savedTab !== 'satisfaction') {
      const targetTab = document.querySelector('#survey-tabs a[href="#' + CSS.escape(savedTab) + '"]');
      const targetPane = document.getElementById(savedTab);
      if (targetTab && targetPane) {
        targetTab.classList.add('active');
        targetTab.setAttribute('aria-selected', 'true');
        document.querySelectorAll('#survey-tabs .nav-link').forEach(function(navLink) {
          if (navLink !== targetTab) {
            navLink.classList.remove('active');
            navLink.setAttribute('aria-selected', 'false');
          }
        });
        document.querySelectorAll('.survey-area .tab-pane').forEach(function(tabPane) {
          const isActive = tabPane === targetPane;
          tabPane.classList.toggle('show', isActive);
          tabPane.classList.toggle('active', isActive);
        });
      }
    }

    document.querySelectorAll('form.survey-form[data-skip="true"], form.pulse-survey-form[data-skip="true"]').forEach(function(form) {
      if (form.dataset.surveyListenerBound === 'true') {
        return;
      }
      form.dataset.surveyListenerBound = 'true';

      form.addEventListener('submit', function(event) {
        event.preventDefault();

        const surveyTypeInput = form.querySelector('input[name="survey_type"]');
        const selectedTab = (surveyTypeInput && surveyTypeInput.value === 'pulse') ? 'pulse' : 'satisfaction';
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.innerHTML : '';

        if (submitButton) {
          submitButton.disabled = true;
          submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const request = fetch(form.action || window.location.href, {
          method: form.method || 'POST',
          body: new FormData(form),
          credentials: 'same-origin'
        });

        request
          .then(function(response) {
            if (!response.ok) {
              throw new Error('Survey submission failed.');
            }
            localStorage.setItem('survey-active-tab', selectedTab);
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

    document.querySelectorAll('#survey-tabs .nav-link').forEach(function(tabLink) {
      if (tabLink.dataset.surveyTabBound === 'true') {
        return;
      }
      tabLink.dataset.surveyTabBound = 'true';

      tabLink.addEventListener('click', function(event) {
        const href = this.getAttribute('href');
        if (href && href.startsWith('#')) {
          event.preventDefault();
          event.stopPropagation();
          const tabId = href.replace('#', '');
          persistSurveyTab(tabId);
          history.pushState(null, null, href);
          activateSurveyTabFromHash();
        }
      });
    });

    window.addEventListener('hashchange', activateSurveyTabFromHash);
    window.addEventListener('popstate', activateSurveyTabFromHash);
    activateSurveyTabFromHash();

    window.addEventListener('load', function() {
      var preloader = document.querySelector('.preloader');
      if (preloader) {
        preloader.style.display = 'none';
      }
    }, { once: true });

    document.querySelectorAll('.sort-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const sortType = this.dataset.sort;
        const suggestions = Array.from(document.querySelectorAll('.suggestion-item'));

        suggestions.sort((a, b) => {
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
          suggestions.forEach(s => container.appendChild(s));
        }

        document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
      });
    });

    const categoryFilter = document.getElementById('category-filter');
    if (categoryFilter) {
      categoryFilter.addEventListener('change', function() {
        const filterValue = this.value;
        document.querySelectorAll('.suggestion-item').forEach(item => {
          if (!filterValue || item.dataset.category === filterValue) {
            item.style.display = 'block';
          } else {
            item.style.display = 'none';
          }
        });
      });
    }
  });
})();
