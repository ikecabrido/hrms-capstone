// Dashboard-only behavior for the legacy time-attendance entry page.
document.addEventListener('DOMContentLoaded', function () {
  const preloader = document.querySelector('.preloader');
  window.setTimeout(function () {
    if (preloader) preloader.style.display = 'none';
  }, 3000);

  const tab = new URLSearchParams(window.location.search).get('tab');
  if (!tab) return;

  const tabLink = document.querySelector('a[href="#' + CSS.escape(tab) + '"]');
  if (!tabLink) return;
  if (window.jQuery && typeof window.jQuery.fn.tab === 'function') {
    window.jQuery(tabLink).tab('show');
  } else if (typeof window.bootstrap !== 'undefined') {
    new window.bootstrap.Tab(tabLink).show();
  }
});

function initTimeDashboardPage() {
  console.log('[TA INIT] Time Dashboard initialized');
  const runtime = window.__taDashboardRuntime || (window.__taDashboardRuntime = {});

  if (typeof runtime.cleanup === 'function') {
    try { runtime.cleanup(); } catch (e) { console.warn('Dashboard cleanup failed', e); }
  }

  runtime.cleanup = function () {
    if (runtime.clockTimer) {
      clearInterval(runtime.clockTimer);
      runtime.clockTimer = null;
    }
    if (runtime.pollTimer) {
      clearInterval(runtime.pollTimer);
      runtime.pollTimer = null;
    }
    if (runtime.documentKeyHandler) {
      document.removeEventListener('keydown', runtime.documentKeyHandler);
      runtime.documentKeyHandler = null;
    }
    if (runtime.documentClickHandler) {
      document.removeEventListener('click', runtime.documentClickHandler);
      runtime.documentClickHandler = null;
    }
  };

  if (typeof window.registerPageCleanup === 'function') {
    window.registerPageCleanup(runtime.cleanup);
  }

  try {
    if (typeof window.initDashboardOverviewPage === 'function') {
      window.initDashboardOverviewPage();
    } else if (typeof filterAndSort === 'function') {
      filterAndSort();
    }
  } catch (e) {
    console.error('initTimeDashboardPage error', e);
  }
}
