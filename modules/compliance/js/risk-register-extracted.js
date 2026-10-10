/* No custom scroll restoration on Risk Register. */

 (function() {
   var panel = document.getElementById('riskSourcesPanel');
   var toggleBtn = document.getElementById('riskSourcesToggle');
   var closeBtn = document.getElementById('riskSourcesClose');
   var backdrop = document.getElementById('riskSourcesBackdrop');

   function getFilterState() {
     var params = new URLSearchParams(window.location.search);
     return {
       status: params.get('status') || 'All',
       source: params.get('source') || 'All',
       severity: params.get('severity') || 'All',
       search: params.get('search') || '',
       date_from: params.get('date_from') || '',
       date_to: params.get('date_to') || ''
     };
   }

   function resetToInitial() {
     var url = new URL(window.location.href);
     var params = url.searchParams;
     var initial = new URLSearchParams();
     if (params.has('page')) initial.set('page', params.get('page'));
     url.search = initial.toString();
     window.location.href = url.toString();
   }

   if (panel && toggleBtn && closeBtn) {
     function openPanel() { panel.classList.add('is-open'); panel.setAttribute('aria-hidden', 'false'); if (backdrop) backdrop.classList.add('is-open'); }
     function closePanel() { panel.classList.remove('is-open'); panel.setAttribute('aria-hidden', 'true'); if (backdrop) backdrop.classList.remove('is-open'); }
     toggleBtn.addEventListener('click', function() { panel.classList.contains('is-open') ? closePanel() : openPanel(); });
     closeBtn.addEventListener('click', closePanel);
     if (backdrop) {
       backdrop.addEventListener('click', function() {
         closePanel();
         resetToInitial();
       });
     }
     document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && panel.classList.contains('is-open')) closePanel(); });
   }

   var summaryItems = document.querySelectorAll('.ra-summary-item');
   summaryItems.forEach(function(item) {
     item.addEventListener('click', function(e) {
       var href = item.getAttribute('href');
       if (!href) return;
       var url = new URL(href, window.location.origin);
       var params = new URLSearchParams(url.search);
       var clickedSeverity = params.get('severity') || 'All';
       var current = getFilterState();
       if (clickedSeverity !== 'All' && clickedSeverity === current.severity) {
         e.preventDefault();
         params.set('severity', 'All');
         url.search = params.toString();
         window.location.href = url.toString();
       }
     });
   });

   var sourceCards = document.querySelectorAll('.ra-source-card');
   sourceCards.forEach(function(card) {
     card.addEventListener('click', function(e) {
       var href = card.getAttribute('href');
       if (!href) return;
       var url = new URL(href, window.location.origin);
       var params = new URLSearchParams(url.search);
       var clickedSource = params.get('source') || 'All';
       var current = getFilterState();
       if (clickedSource !== 'All' && clickedSource === current.source) {
         e.preventDefault();
         params.set('source', 'All');
         url.search = params.toString();
         window.location.href = url.toString();
       }
     });
   });
 })();

  (function() {
    var runBtn = document.getElementById('runDetectorBtn');
    if (!runBtn) return;

    var departments = [
      'Recruitment & Onboarding', 'Employee Management', 'Payroll',
      'Legal & Compliance', 'Employee Portal', 'Admin Portal',
      'Time and Attendance', 'Workforce Management', 'Performance Management',
      'Engagement Management', 'Clinic / Health', 'Exit Management',
      'Learning and Development'
    ];
    var originalHtml = '';
    var pageScrollY = 0;

    

    runBtn.addEventListener('click', function() {
      pageScrollY = window.scrollY || window.pageYOffset || 0;
      originalHtml = runBtn.innerHTML;
      runBtn.disabled = true;
      runBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Detecting...';

      var overlay = document.getElementById('raLoadingOverlay');
      var deptTagsContainer = document.getElementById('raLoadingDepts');
      var deptText = document.getElementById('raLoadingDept');

      deptTagsContainer.innerHTML = '';
      departments.forEach(function(dept, index) {
        var tag = document.createElement('span');
        tag.className = 'ra-dept-tag';
        tag.textContent = dept;
        tag.dataset.dept = dept;
        tag.style.animationDelay = (index * 0.03) + 's';
        deptTagsContainer.appendChild(tag);
      });

      var deptTags = deptTagsContainer.querySelectorAll('.ra-dept-tag');
      var activeIndex = -1;

      // Keep the overlay outside transformed or scrolling page containers.
      if (overlay.parentElement !== document.body) {
        document.body.appendChild(overlay);
      }

      // Keep the page position stable while the detector overlay opens.
      // Restore only the saved scroll position; leave the animation untouched.
      overlay.classList.add('is-open');

      var detectorCard = overlay.querySelector('.ra-loading-card');
      if (detectorCard) detectorCard.scrollTop = 0;

      window.scrollTo(0, pageScrollY);
      requestAnimationFrame(function() {
        window.scrollTo(0, pageScrollY);
      });

      deptText.textContent = 'Initializing scan...';

      fetch('./lib/ajax/run_risk_detector.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ scope: 'all_departments' })
      })
      .then(function(response) {
        var reader = response.body.getReader();
        var decoder = new TextDecoder();
        var buffer = '';
        var finalData = null;

        function readStream() {
          return reader.read().then(function(result) {
            if (result.done) {
              if (finalData) {
                finishDetection(finalData);
              } else {
                throw new Error('No data received from server');
              }
              return;
            }

            buffer += decoder.decode(result.value, { stream: true });
            var lines = buffer.split('\n');
            buffer = lines.pop();

            lines.forEach(function(line) {
              if (!line.trim()) return;
              try {
                var data = JSON.parse(line);
                if (data.type === 'progress') {
                  deptText.textContent = 'Scanning: ' + data.department;
                  if (activeIndex >= 0 && activeIndex < deptTags.length) {
                    deptTags[activeIndex].classList.remove('is-active');
                    deptTags[activeIndex].classList.add('is-done');
                  }
                  activeIndex = departments.indexOf(data.department);
                  if (activeIndex >= 0 && activeIndex < deptTags.length) {
                    deptTags[activeIndex].classList.add('is-active');
                  }
                } else if (data.type === 'complete') {
                  finalData = data;
                } else if (data.type === 'error') {
                  throw new Error(data.message || 'Server error');
                }
              } catch (e) {
                if (e.message && e.message.includes('Server error')) {
                  throw e;
                }
              }
            });

            return readStream();
          });
        }

        return readStream();
      })
      .catch(function(error) {
        var overlay = document.getElementById('raLoadingOverlay');
        var card = overlay.querySelector('.ra-loading-card');
        var icon = document.getElementById('raLoadingIcon');
        var title = document.getElementById('raLoadingTitle');
        var deptText = document.getElementById('raLoadingDept');
        var footer = document.getElementById('raLoadingFooter');
        var closeBtn = document.getElementById('raLoadingClose');

        card.classList.add('is-error');
        card.classList.remove('is-success');
        title.textContent = 'Detection Failed';
        deptText.textContent = error.message || 'Network error';
        footer.textContent = 'Please check your connection and try again.';
        closeBtn.style.display = 'inline-flex';
        runBtn.disabled = false;
        runBtn.innerHTML = originalHtml;

        closeBtn.onclick = function() {
          overlay.classList.remove('is-open');
          // Restore the page position after the detector panel closes.
          window.scrollTo(0, pageScrollY);
          requestAnimationFrame(function() {
            window.scrollTo(0, pageScrollY);
            requestAnimationFrame(function() {
              window.scrollTo(0, pageScrollY);
            });
          });
          resetOverlay();
        };
      });
    });

    function finishDetection(data) {
      var overlay = document.getElementById('raLoadingOverlay');
      var card = overlay.querySelector('.ra-loading-card');
      var icon = document.getElementById('raLoadingIcon');
      var title = document.getElementById('raLoadingTitle');
      var deptText = document.getElementById('raLoadingDept');
      var footer = document.getElementById('raLoadingFooter');
      var closeBtn = document.getElementById('raLoadingClose');
      var deptTags = document.querySelectorAll('.ra-dept-tag');

      deptTags.forEach(function(tag) {
        tag.classList.remove('is-active');
        tag.classList.add('is-done');
      });

      if (data && data.success) {
        var message = data.message || 'Risk detection completed.';
        if (data.total_new === 0) {
          message = 'No new risks detected. All clear!';
        }
        card.classList.add('is-success');
        card.classList.remove('is-error');
        title.textContent = 'Detection Complete';
        deptText.textContent = message;
        footer.textContent = 'Refreshing risk register...';
        closeBtn.style.display = 'none';

        setTimeout(function() {
          overlay.classList.remove('is-open');
          // Restore the page position after the detector panel closes.
          window.scrollTo(0, pageScrollY);
          requestAnimationFrame(function() {
            window.scrollTo(0, pageScrollY);
            requestAnimationFrame(function() {
              window.scrollTo(0, pageScrollY);
            });
          });
          /* Keep the page position unchanged. */
          if (typeof refreshRiskRegister === 'function') {
            refreshRiskRegister();
          }
          runBtn.disabled = false;
          runBtn.innerHTML = originalHtml;
          resetOverlay();
        }, 1500);
      } else {
        card.classList.add('is-error');
        card.classList.remove('is-success');
        title.textContent = 'Detection Failed';
        deptText.textContent = (data && data.message ? data.message : 'Unknown error');
        footer.textContent = 'Please try again or contact support.';
        closeBtn.style.display = 'inline-flex';
        runBtn.disabled = false;
        runBtn.innerHTML = originalHtml;

        closeBtn.onclick = function() {
          overlay.classList.remove('is-open');
          // Restore the page position after the detector panel closes.
          window.scrollTo(0, pageScrollY);
          requestAnimationFrame(function() {
            window.scrollTo(0, pageScrollY);
            requestAnimationFrame(function() {
              window.scrollTo(0, pageScrollY);
            });
          });
          resetOverlay();
        };
      }
    }

    function resetOverlay() {
      var overlay = document.getElementById('raLoadingOverlay');
      var card = overlay.querySelector('.ra-loading-card');
      var icon = document.getElementById('raLoadingIcon');
      var title = document.getElementById('raLoadingTitle');
      var deptText = document.getElementById('raLoadingDept');
      var footer = document.getElementById('raLoadingFooter');
      var closeBtn = document.getElementById('raLoadingClose');

      card.classList.remove('is-success', 'is-error');
      icon.style.borderTopColor = '#3b82c4';
      icon.style.animation = 'ra-spin 0.9s linear infinite';
      title.textContent = 'Risk Detection in Progress';
      deptText.textContent = 'Initializing scan...';
      footer.textContent = 'Please wait while we scan all HR modules';
      closeBtn.style.display = 'none';
      closeBtn.onclick = null;
    }
  })();

 (function() {
   var analyticsToggle = document.getElementById('analyticsToggle');
   var analyticsModal = document.getElementById('analyticsModal');
   var analyticsBackdrop = document.getElementById('analyticsModalBackdrop');
   var analyticsClose = document.getElementById('analyticsModalClose');

   function openAnalyticsModal() {
     if (!analyticsModal) return;

     /* Escape transformed or constrained page containers. */
     if (analyticsBackdrop && analyticsBackdrop.parentElement !== document.body) {
       document.body.appendChild(analyticsBackdrop);
     }
     if (analyticsModal.parentElement !== document.body) {
       document.body.appendChild(analyticsModal);
     }

     /* Position relative to the visible browser viewport. */
     analyticsModal.style.position = 'fixed';
     analyticsModal.style.top = '50%';
     analyticsModal.style.left = '50%';
     analyticsModal.style.right = 'auto';
     analyticsModal.style.bottom = 'auto';
     analyticsModal.style.margin = '0';
     analyticsModal.style.transform = 'translate(-50%, -50%)';

     analyticsModal.classList.add('is-open');
     analyticsModal.setAttribute('aria-hidden', 'false');
     if (analyticsBackdrop) analyticsBackdrop.classList.add('is-open');
     document.body.style.overflow = 'hidden';
   }

   function closeAnalyticsModal() {
     if (!analyticsModal) return;

     /* Move focus out before hiding the modal from assistive technology. */
     var active = document.activeElement;
     if (active && analyticsModal.contains(active)) {
       if (analyticsToggle && typeof analyticsToggle.focus === 'function') {
         analyticsToggle.focus();
       } else if (active.blur) {
         active.blur();
       }
     }

     analyticsModal.classList.remove('is-open');
     if (analyticsBackdrop) analyticsBackdrop.classList.remove('is-open');
     analyticsModal.setAttribute('aria-hidden', 'true');
     document.body.style.overflow = '';
   }

   if (analyticsToggle) {
     analyticsToggle.addEventListener('click', function() {
       if (analyticsModal.classList.contains('is-open')) {
         closeAnalyticsModal();
       } else {
         openAnalyticsModal();
       }
     });
   }

   if (analyticsClose) {
     analyticsClose.addEventListener('click', closeAnalyticsModal);
   }

   if (analyticsBackdrop) {
     analyticsBackdrop.addEventListener('click', closeAnalyticsModal);
   }

   document.addEventListener('keydown', function(e) {
     if (e.key === 'Escape' && analyticsModal && analyticsModal.classList.contains('is-open')) {
       closeAnalyticsModal();
     }
   });
 })();
  

/* Risk Register default module heading position */
/* Automatic Risk Register page scrolling removed. */
