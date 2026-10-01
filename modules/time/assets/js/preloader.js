(function () {
  'use strict';

  const PRELOADER_MIN_VISIBLE_MS = 1200;
  window.preloaderStartTime = window.preloaderStartTime || Date.now();
  window.preloaderHold = window.preloaderHold || false;

  function hideImmediate() {
    const preloader = document.querySelector('.preloader');
    if (preloader) {
      preloader.style.display = 'none';
      preloader.style.visibility = 'hidden';
    }
  }

  function hide() {
    if (window.preloaderHold) return;
    const elapsed = Date.now() - window.preloaderStartTime;
    const remaining = Math.max(0, PRELOADER_MIN_VISIBLE_MS - elapsed);
    if (remaining > 0) {
      window.setTimeout(hide, remaining);
      return;
    }
    hideImmediate();
  }

  function show() {
    const preloader = document.querySelector('.preloader');
    if (preloader) {
      preloader.style.display = 'flex';
      preloader.style.visibility = 'visible';
    }
  }

  window.releasePreloader = function (delay) {
    const release = function () {
      window.preloaderHold = false;
      hide();
    };
    if (delay > 0) window.setTimeout(release, delay);
    else release();
  };

  window.addEventListener('load', function () {
    if (!window.preloaderHold) hide();
  });

  document.addEventListener('DOMContentLoaded', function () {
    window.setTimeout(function () {
      if (window.preloaderHold) window.preloaderHold = false;
      hide();
    }, 6000);

    document.querySelectorAll('a[href]').forEach(function (link) {
      const href = link.getAttribute('href');
      if (href && !href.includes('logout') && !href.startsWith('javascript') &&
          !href.startsWith('#') && !href.startsWith('mailto:') && !href.startsWith('tel:')) {
        link.addEventListener('click', show);
      }
    });
  });
})();
