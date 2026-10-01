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
  if (initTimeDashboardPage._inited) return;
  initTimeDashboardPage._inited = true;
  console.log('[TA INIT] Time Dashboard initialized');
  try { if (typeof filterAndSort === 'function') filterAndSort(); } catch (e) { console.error('initTimeDashboardPage error', e); }
}
