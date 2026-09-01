(function() {
  if (window.__engagementSurveyPageInit) {
    return;
  }
  window.__engagementSurveyPageInit = true;

  function initSurveyPage() {
    // Auto-switch to Analytics tab when viewing survey results
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

    function switchTab(tabLink, isInitialLoad = false) {
      const href = tabLink.getAttribute('href');
      if (!href || !href.startsWith('#')) return;
      
      const tabId = href.replace('#', '');
      const targetPane = document.getElementById(tabId);
      
      if (!targetPane) return;
      
      localStorage.setItem('survey-active-tab', tabId);
      if (window.location.hash !== '#' + tabId) {
        window.history.replaceState({}, '', window.location.pathname + window.location.search + '#' + tabId);
      }
      
      const allTabs = document.querySelectorAll('#survey-tabs .nav-link');
      const allPanes = document.querySelectorAll('.survey-area .tab-pane');
      
      if (isInitialLoad) {
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
      } else {
        const activePanes = document.querySelectorAll('.survey-area .tab-pane.active');
        activePanes.forEach(pane => {
          pane.style.opacity = '1';
          pane.style.transition = 'opacity 0.3s ease-out';
          pane.style.opacity = '0';
        });
        
        setTimeout(function() {
          allTabs.forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
          });
          
          allPanes.forEach(pane => {
            pane.classList.remove('show', 'active');
            pane.style.opacity = '1';
            pane.style.transition = 'none';
          });
          
          tabLink.classList.add('active');
          tabLink.setAttribute('aria-selected', 'true');
          targetPane.classList.add('show', 'active');
          targetPane.style.opacity = '0';
          targetPane.style.transition = 'opacity 0.3s ease-in';
          
          void targetPane.offsetWidth;
          targetPane.style.opacity = '1';
        }, 150);
      }
    }

    function activateSurveyTabFromHash(isInitialLoad = false) {
      const urlHash = window.location.hash ? window.location.hash.replace('#', '') : '';
      const savedHash = localStorage.getItem('survey-active-tab');
      const hash = urlHash && document.querySelector('#' + CSS.escape(urlHash)) ? urlHash : (savedHash && document.querySelector('#' + CSS.escape(savedHash)) ? savedHash : 'satisfaction');
      const targetTab = document.querySelector('#survey-tabs a[href="#' + CSS.escape(hash) + '"]');

      if (targetTab) {
        switchTab(targetTab, isInitialLoad);
      }
    }

    function initTabClickHandlers() {
      document.querySelectorAll('#survey-tabs .nav-link').forEach(function(tabLink) {
        const isAlreadyBound = tabLink.dataset.surveyTabBound === 'true';
        if (isAlreadyBound) {
          return;
        }
        tabLink.dataset.surveyTabBound = 'true';

        tabLink.addEventListener('click', function(event) {
          event.preventDefault();
          event.stopPropagation();
          switchTab(this, false);
        });
      });
    }

    initTabClickHandlers();
    activateSurveyTabFromHash(true);

    window.addEventListener('hashchange', function() {
      activateSurveyTabFromHash(false);
    }, { once: false });
    
    window.addEventListener('popstate', function() {
      activateSurveyTabFromHash(false);
    }, { once: false });

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

    window.addEventListener('page:loaded', function(e) {
      if (e.detail && e.detail.page === 'survey') {
        setTimeout(function() {
          initTabClickHandlers();
          activateSurveyTabFromHash(true);
        }, 50);
      }
    }, { once: false });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSurveyPage, { once: true });
  } else {
    initSurveyPage();
  }
})();
