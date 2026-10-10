(function () {
  'use strict';

  const calendarBtn = document.getElementById('calendarBtn');
  const calendarModal = document.getElementById('calendarModal');
  const calendarModalClose = document.getElementById('calendarModalClose');
  const calEventDot = document.getElementById('calEventDot');

  let fullCalendarInstance = null;

  function openCalendar() {
    if (!calendarModal) return;
    calendarModal.classList.add('show');
    calendarModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    initFullCalendar();
    requestAnimationFrame(function () {
      if (fullCalendarInstance) {
        fullCalendarInstance.updateSize();
        fullCalendarInstance.refetchEvents();
      }
    });
  }

  function closeCalendar() {
    if (!calendarModal) return;
    calendarModal.classList.remove('show');
    if (calendarBtn) {
      calendarBtn.focus();
    }
    calendarModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  if (calendarBtn && calendarModal) {
    calendarBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      openCalendar();
    });
  }

  if (calendarModalClose) {
    calendarModalClose.addEventListener('click', closeCalendar);
  }

  if (calendarModal) {
    calendarModal.addEventListener('click', function (e) {
      if (e.target === calendarModal) closeCalendar();
    });
  }

  function initFullCalendar() {
    if (fullCalendarInstance) {
      fullCalendarInstance.updateSize();
      return;
    }

    const calendarEl = document.getElementById('fullCalendar');
    if (!calendarEl) {
      console.error('Calendar element #fullCalendar not found');
      return;
    }
    if (typeof FullCalendar === 'undefined') {
      console.error('FullCalendar library not loaded');
      calendarEl.innerHTML = '<div style="padding:40px;text-align:center;color:#888;">Calendar is unavailable. Please check your internet connection and reload.</div>';
      return;
    }

    fullCalendarInstance = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      headerToolbar: false,
      height: '100%',
      contentHeight: 'auto',
      dayMaxEvents: 2,
      dayMaxEventRows: 2,
      fixedWeekCount: true,
      expandRows: true,
      showNonCurrentDates: true,
      moreLinkClassNames: 'cal-more-link',
      events: function (fetchInfo, successCallback, failureCallback) {
        const start = fetchInfo.startStr;
        const end = fetchInfo.endStr;
        fetch('lib/api/calendar_crud.php?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end), { credentials: 'same-origin' })
          .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text().then(function (text) {
              try {
                return JSON.parse(text);
              } catch (e) {
                throw new Error('Invalid JSON response: ' + text.slice(0, 200));
              }
            });
          })
          .then(function (data) {
            if (!data.success) {
              console.warn('Calendar API returned error:', data.message);
              successCallback([]);
              return;
            }
            const events = (data.data || []).map(function (ev) {
              const rawStart = ev.start_time || ev.start;
              const startDate = rawStart ? String(rawStart).slice(0, 10) : '';

              return {
                id: String(ev.id),
                title: ev.title,
                start: startDate || undefined,
                allDay: true,
                backgroundColor: ev.color || '#3B82C4',
                borderColor: ev.color || '#3B82C4',
                extendedProps: {
                  description: ev.description || '',
                  event_type: ev.event_type || 'Other',
                  status: ev.status || 'Scheduled',
                  priority: ev.priority || 'medium',
                  location: ev.location || '',
                  employee_name: ev.employee_name || '',
                  department_name: ev.department_name || '',
                },
              };
            });
            successCallback(events);
          })
          .catch(function (err) {
            console.error('Calendar events fetch failed:', err);
            successCallback([]);
          });
      },
      eventClick: function (info) {
        const ev = info.event;
        const ext = ev.extendedProps || {};
        const start = ev.start ? ev.start.toISOString() : '';
        const end = ev.end ? ev.end.toISOString() : '';
        const allDay = ev.allDay ? 'Yes' : 'No';
        const details = [
          'Type: ' + (ext.event_type || 'Other'),
          'Status: ' + (ext.status || 'Scheduled'),
          'Priority: ' + (ext.priority || 'medium'),
          'All day: ' + allDay,
          ext.employee_name ? 'Employee: ' + ext.employee_name : '',
          ext.department_name ? 'Department: ' + ext.department_name : '',
          ext.location ? 'Location: ' + ext.location : '',
          ext.description ? 'Description: ' + ext.description : '',
          'Start: ' + start,
          end ? 'End: ' + end : '',
        ].filter(Boolean).join('\n');
        alert(details || 'No additional details.');
      },
    });

    fullCalendarInstance.render();

    var calTodayBtn = document.getElementById('calTodayBtn');
    var calPrevBtn = document.getElementById('calPrevBtn');
    var calNextBtn = document.getElementById('calNextBtn');
    var calToolbarLabel = document.getElementById('calToolbarLabel');

    if (calTodayBtn) {
      calTodayBtn.addEventListener('click', function () {
        fullCalendarInstance.today();
      });
    }
    if (calPrevBtn) {
      calPrevBtn.addEventListener('click', function () {
        fullCalendarInstance.prev();
      });
    }
    if (calNextBtn) {
      calNextBtn.addEventListener('click', function () {
        fullCalendarInstance.next();
      });
    }

    fullCalendarInstance.on('datesSet', function (info) {
      if (calToolbarLabel) {
        calToolbarLabel.textContent = info.view.title || '';
      }
    });

    if (calToolbarLabel && fullCalendarInstance.view) {
      calToolbarLabel.textContent = fullCalendarInstance.view.title || '';
    }
  }

  function updateCalendarDot() {
    if (!calEventDot) return;
    fetch('lib/api/calendar_status.php', { credentials: 'same-origin' })
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (data) {
        if (data && (data.has_upcoming_events || data.show_indicator)) {
          calEventDot.classList.add('has-events');
        }
      })
      .catch(function () { /* non-critical */ });
  }

  updateCalendarDot();

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (calendarModal) {
        calendarModal.classList.remove('show');
        document.body.style.overflow = '';
      }
    }
  });
})();
