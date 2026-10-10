import { qs, qsa, esc, statusClass, cleanJSON } from './helpers.js';
import { loadCaseDetail, formatDate } from './case-detail.js';

export function renderRoadmap(events) {
    var container = qs('#ecRoadmapList');
    if (!container) return;
    if (!events.length) {
        container.innerHTML = '<div class="lc-empty">No roadmap events yet.</div>';
        return;
    }
    var html = '';
    events.forEach(function (ev) {
        var cls = 'ec-roadmap-item--pending';
        if (ev.status === 'Completed' || ev.status === 'Cancelled') cls = 'ec-roadmap-item--completed';
        else if (ev.status === 'Scheduled' || ev.status === 'Rescheduled') cls = 'ec-roadmap-item--scheduled';
        html += '<div class="ec-roadmap-item ' + cls + '">' +
            '<div class="ec-roadmap-dot"></div>' +
             '<div class="lc-card ec-roadmap-card" data-title="' + esc(ev.title) + '" data-event-type="' + esc(ev.event_type || '') + '" data-event-date="' + esc(ev.event_date || '') + '" data-start-time="' + esc(ev.start_time || '') + '" data-location="' + esc(ev.location || '') + '" data-status="' + esc(ev.status || '') + '" data-description="' + esc((ev.description || '').replace(/"/g, '&quot;')) + '" data-event-id="' + ev.event_id + '">' +
             '<div class="ec-roadmap-header">' +
             '<div class="ec-roadmap-title">' + esc(ev.title) + '</div>' +
             '<span class="ec-roadmap-meta-item">' + esc(ev.event_date || '') + '</span>' +
             '<span class="lc-status-stamp ' + statusClass(ev.status) + ' ec-roadmap-status">' + esc(ev.status || 'Scheduled') + '</span>' +
             '</div></div>';
    });
    container.innerHTML = html;
    function openRoadmapItemEdit(eventId) {
        var item = qs('.ec-roadmap-card[data-event-id="' + eventId + '"]', container);
        if (!item || !eventId) return;
        var titleText = item.getAttribute('data-title') || '';
        var eventType = item.getAttribute('data-event-type') || '';
        var eventDate = item.getAttribute('data-event-date') || '';
        var startTime = item.getAttribute('data-start-time') || '';
        var location = item.getAttribute('data-location') || '';
        var status = item.getAttribute('data-status') || '';
        var description = item.getAttribute('data-description') || '';
        var eventTypeEl = qs('#ecEditRoadmapEventType');
        var titleInput = qs('#ecEditRoadmapTitle');
        var dateInput = qs('#ecEditRoadmapDate');
        var timeInput = qs('#ecEditRoadmapTime');
        var locationInput = qs('#ecEditRoadmapLocation');
        var statusSelect = qs('#ecEditRoadmapStatus');
        var descInput = qs('#ecEditRoadmapDescription');
        var eventIdInput = qs('#ecEditRoadmapEventId');
        if (eventTypeEl) eventTypeEl.value = eventType;
        if (titleInput) titleInput.value = titleText;
        if (dateInput) dateInput.value = eventDate;
        if (timeInput) timeInput.value = startTime;
        if (locationInput) locationInput.value = location;
        if (statusSelect) statusSelect.value = status;
        if (descInput) descInput.value = description;
        if (eventIdInput) eventIdInput.value = eventId;
        var editRoadmapModal = qs('#ecEditRoadmapModal');
        if (editRoadmapModal) editRoadmapModal.classList.add('ec-modal-backdrop--open');
    }
    function deleteRoadmapItem(eventId) {
        if (!eventId || !confirm('Delete this milestone?')) return;
        var fd = new FormData();
        fd.set('csrf_token', EC.csrf);
        fd.set('action', 'delete_event');
        fd.set('case_id', EC.caseId);
        fd.set('event_id', eventId);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', EC.api, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (xhr.status >= 200 && xhr.status < 300) {
                loadCaseDetail();
            } else {
                try {
                    var err = JSON.parse(cleanJSON(xhr.responseText));
                    alert(err.message || 'Failed to delete milestone. Please try again.');
                } catch (ex) {
                    alert('Failed to delete milestone. Please try again.');
                }
            }
        };
        xhr.send(fd);
    }
    container.addEventListener('click', function (e) {
        var card = e.target.closest('.ec-roadmap-card');
        if (!card) return;
        var eventId = card.getAttribute('data-event-id');
        if (e.target.closest('.ec-roadmap-item-menu')) return;
        if (e.target.closest('.ec-roadmap-delete-btn')) {
            deleteRoadmapItem(eventId);
            return;
        }
        if (e.target.closest('.ec-roadmap-edit-btn')) {
            openRoadmapItemEdit(eventId);
            return;
        }
        if (eventId) {
            openRoadmapItemDetail(card);
        }
    });

    function openRoadmapItemDetail(card) {
        if (!card) return;
        var titleText = card.getAttribute('data-title') || '';
        var eventType = card.getAttribute('data-event-type') || '';
        var eventDate = card.getAttribute('data-event-date') || '';
        var startTime = card.getAttribute('data-start-time') || '';
        var location = card.getAttribute('data-location') || '';
        var status = card.getAttribute('data-status') || '';
        var description = card.getAttribute('data-description') || '';
        var titleEl = qs('#ecRoadmapDetailTitle');
        var typeEl = qs('#ecRoadmapDetailType');
        var dateEl = qs('#ecRoadmapDetailDate');
        var timeEl = qs('#ecRoadmapDetailTime');
        var locationEl = qs('#ecRoadmapDetailLocation');
        var statusEl = qs('#ecRoadmapDetailStatus');
        var descEl = qs('#ecRoadmapDetailDescription');
        if (titleEl) titleEl.textContent = titleText || 'Roadmap Item Details';
        if (typeEl) typeEl.textContent = eventType || '—';
        if (dateEl) dateEl.textContent = eventDate ? formatDate(eventDate) : '—';
        if (timeEl) timeEl.textContent = startTime || '—';
        if (locationEl) locationEl.textContent = location || '—';
        if (statusEl) statusEl.innerHTML = status ? '<span class="lc-status-stamp ' + statusClass(status) + '">' + esc(status) + '</span>' : '—';
        if (descEl) descEl.textContent = description || '—';
        var modal = qs('#ecRoadmapDetailModal');
        if (modal) modal.classList.add('ec-modal-backdrop--open');
    }
}

export function renderUpcomingHearings(events) {
    var container = qs('#ecHearingsList');
    if (!container) return;
    var hearings = events.filter(function (ev) {
        var t = (ev.event_type || '').toLowerCase();
        return t.indexOf('conference') !== -1 || t.indexOf('hearing') !== -1 || t.indexOf('meeting') !== -1;
    });
    if (!hearings.length) {
        container.innerHTML = '<div class="lc-empty">No upcoming hearings.</div>';
        return;
    }
    var html = '<div class="ec-hearing-compact">';
    hearings.forEach(function (ev) {
        html += '<div class="ec-hearing-compact-item" data-title="' + esc(ev.title) + '" data-event-type="' + esc(ev.event_type || '') + '" data-event-date="' + esc(ev.event_date || '') + '" data-start-time="' + esc(ev.start_time || '') + '" data-location="' + esc(ev.location || '') + '" data-status="' + esc(ev.status || '') + '" data-description="' + esc((ev.description || '').replace(/"/g, '&quot;')) + '" data-event-id="' + ev.event_id + '">' +
            '<div class="ec-hearing-compact-date">' + esc(ev.event_date || '—') + '</div>' +
            '<div class="ec-hearing-compact-body">' +
            '<div class="ec-hearing-compact-title">' + esc(ev.title) + '</div>' +
            '<div class="ec-hearing-compact-meta">' +
            esc(ev.start_time || '') + (ev.location ? ' · ' + esc(ev.location) : '') +
            '</div>' +
            '</div>' +
            '<span class="lc-status-stamp ' + statusClass(ev.status) + ' ec-hearing-compact-status">' + esc(ev.status || '') + '</span>' +
            '<div class="ec-hearing-compact-actions">' +
            '<div class="ec-hearing-item-menu-wrap">' +
            '<button type="button" class="lc-btn ghost ec-hearing-item-menu-toggle" data-event-id="' + ev.event_id + '" title="Options" aria-haspopup="true" aria-expanded="false"><i class="bi bi-three-dots"></i></button>' +
            '<div class="ec-more-menu ec-hearing-item-menu" role="menu">' +
            '<button type="button" class="ec-more-menu-item ec-hearing-item-edit" data-event-id="' + ev.event_id + '" role="menuitem"><i class="bi bi-pencil"></i> Edit</button>' +
            '<button type="button" class="ec-more-menu-item ec-hearing-item-delete" data-event-id="' + ev.event_id + '" role="menuitem"><i class="bi bi-trash"></i> Delete</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
    });
    html += '</div>';
    container.innerHTML = html;
    qsa('.ec-hearing-item-menu-toggle', container).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var menu = qs('.ec-hearing-item-menu', btn.parentNode);
            var isOpen = menu && menu.classList.contains('is-open');
            qsa('.ec-hearing-item-menu', container).forEach(function (m) {
                m.classList.remove('is-open');
                m.classList.remove('is-fixed');
                m.style.position = '';
                m.style.top = '';
                m.style.left = '';
                m.style.right = '';
            });
            if (!isOpen && menu) {
                var rect = btn.getBoundingClientRect();
                menu.style.position = 'fixed';
                menu.style.top = (rect.bottom + 4) + 'px';
                menu.style.right = (window.innerWidth - rect.right) + 'px';
                menu.style.left = 'auto';
                menu.classList.add('is-fixed');
                menu.classList.add('is-open');
            }
        });
    });
    qsa('.ec-hearing-item-edit', container).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var eventId = btn.getAttribute('data-event-id');
            var menu = qs('.ec-hearing-item-menu', btn.parentNode);
            if (menu) {
                menu.classList.remove('is-open');
                menu.classList.remove('is-fixed');
                menu.style.position = '';
                menu.style.top = '';
                menu.style.left = '';
                menu.style.right = '';
            }
            if (!eventId) return;
            var item = qs('.ec-hearing-compact-item[data-event-id="' + eventId + '"]', container);
            if (!item) return;
            var titleText = item.getAttribute('data-title') || '';
            var eventType = item.getAttribute('data-event-type') || '';
            var eventDate = item.getAttribute('data-event-date') || '';
            var startTime = item.getAttribute('data-start-time') || '';
            var location = item.getAttribute('data-location') || '';
            var status = item.getAttribute('data-status') || '';
            var description = item.getAttribute('data-description') || '';
            var eventTypeEl = qs('#ecEditRoadmapEventType');
            var titleInput = qs('#ecEditRoadmapTitle');
            var dateInput = qs('#ecEditRoadmapDate');
            var timeInput = qs('#ecEditRoadmapTime');
            var locationInput = qs('#ecEditRoadmapLocation');
            var statusSelect = qs('#ecEditRoadmapStatus');
            var descInput = qs('#ecEditRoadmapDescription');
            var eventIdInput = qs('#ecEditRoadmapEventId');
            if (eventTypeEl) eventTypeEl.value = eventType;
            if (titleInput) titleInput.value = titleText;
            if (dateInput) dateInput.value = eventDate;
            if (timeInput) timeInput.value = startTime;
            if (locationInput) locationInput.value = location;
            if (statusSelect) statusSelect.value = status;
            if (descInput) descInput.value = description;
            if (eventIdInput) eventIdInput.value = eventId;
            var editRoadmapModal = qs('#ecEditRoadmapModal');
            if (editRoadmapModal) editRoadmapModal.classList.add('ec-modal-backdrop--open');
        });
    });
    qsa('.ec-hearing-item-delete', container).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var eventId = btn.getAttribute('data-event-id');
            var menu = qs('.ec-hearing-item-menu', btn.parentNode);
            if (menu) {
                menu.classList.remove('is-open');
                menu.classList.remove('is-fixed');
                menu.style.position = '';
                menu.style.top = '';
                menu.style.left = '';
                menu.style.right = '';
            }
            if (!eventId || !confirm('Delete this hearing?')) return;
            var fd = new FormData();
            fd.set('csrf_token', EC.csrf);
            fd.set('action', 'delete_event');
            fd.set('case_id', EC.caseId);
            fd.set('event_id', eventId);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', EC.api, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                if (xhr.status >= 200 && xhr.status < 300) {
                    loadCaseDetail();
                }
            };
            xhr.send(fd);
        });
    });
    qsa('.ec-hearing-compact-item', container).forEach(function (item) {
        item.addEventListener('click', function (e) {
            if (e.target.closest('.ec-hearing-item-menu-wrap')) return;
            var titleText = item.getAttribute('data-title') || '';
            var eventType = item.getAttribute('data-event-type') || '';
            var eventDate = item.getAttribute('data-event-date') || '';
            var startTime = item.getAttribute('data-start-time') || '';
            var location = item.getAttribute('data-location') || '';
            var status = item.getAttribute('data-status') || '';
            var description = item.getAttribute('data-description') || '';
            var titleEl = qs('#ecHearingDetailTitle');
            var typeEl = qs('#ecHearingDetailType');
            var dateEl = qs('#ecHearingDetailDate');
            var timeEl = qs('#ecHearingDetailTime');
            var locationEl = qs('#ecHearingDetailLocation');
            var statusEl = qs('#ecHearingDetailStatus');
            var descEl = qs('#ecHearingDetailDescription');
            if (titleEl) titleEl.textContent = titleText || 'Hearing Details';
            if (typeEl) typeEl.textContent = eventType || '—';
            if (dateEl) dateEl.textContent = eventDate ? formatDate(eventDate) : '—';
            if (timeEl) timeEl.textContent = startTime || '—';
            if (locationEl) locationEl.textContent = location || '—';
            if (statusEl) statusEl.innerHTML = status ? '<span class="lc-status-stamp ' + statusClass(status) + '">' + esc(status) + '</span>' : '—';
            if (descEl) descEl.textContent = description || '—';
            var modal = qs('#ecHearingDetailModal');
            if (modal) modal.classList.add('ec-modal-backdrop--open');
        });
    });
    var cancelHearingDetailBtn = qs('#ecCancelHearingDetailBtn');
    if (cancelHearingDetailBtn) {
        cancelHearingDetailBtn.addEventListener('click', function () {
            var modal = qs('#ecHearingDetailModal');
            if (modal) modal.classList.remove('ec-modal-backdrop--open');
        });
    }
    var hearingDetailModal = qs('#ecHearingDetailModal');
    if (hearingDetailModal) {
        hearingDetailModal.addEventListener('click', function (e) {
            if (e.target === hearingDetailModal) {
                hearingDetailModal.classList.remove('ec-modal-backdrop--open');
            }
        });
    }
    var cancelRoadmapDetailBtn = qs('#ecCancelRoadmapDetailBtn');
    if (cancelRoadmapDetailBtn) {
        cancelRoadmapDetailBtn.addEventListener('click', function () {
            var modal = qs('#ecRoadmapDetailModal');
            if (modal) modal.classList.remove('ec-modal-backdrop--open');
        });
    }
    var roadmapDetailModal = qs('#ecRoadmapDetailModal');
    if (roadmapDetailModal) {
        roadmapDetailModal.addEventListener('click', function (e) {
            if (e.target === roadmapDetailModal) {
                roadmapDetailModal.classList.remove('ec-modal-backdrop--open');
            }
        });
    }
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.ec-hearing-item-menu-wrap')) {
            qsa('.ec-hearing-item-menu', container).forEach(function (m) {
                m.classList.remove('is-open');
                m.classList.remove('is-fixed');
                m.style.position = '';
                m.style.top = '';
                m.style.left = '';
                m.style.right = '';
            });
        }
    });
}
