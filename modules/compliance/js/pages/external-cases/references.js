import { qs, qsa, esc, getJSON, cleanJSON } from './helpers.js';
import { loadCaseDetail } from './case-detail.js';
import { EC } from './config.js';

export function renderReferences(refs, caseData) {
    var container = qs('#ecLawLibraryContainer');
    if (!container) return;
    if (!refs.length) {
        container.innerHTML = '<div class="lc-empty">No references attached yet.</div>';
        if (caseData) {
            loadSuggestedReferences(caseData);
        }
        return;
    }
    var html = '<div class="ec-ref-suggested-header">LAW LIBRARY</div>';
    html += '<div class="ec-ref-list">';
    refs.slice(0, 3).forEach(function (r) {
        html += '<div class="ec-ref-row">' +
            '<div class="ec-ref-text">' +
            '<strong>' + esc(r.title || r.short_title || 'Reference') + '</strong>' +
            '<span>' + esc(r.reference_number || '') + ' | ' + esc(r.category_name || '') + ' | ' + esc(r.issuing_authority || '') + ' | ' + esc(r.relation_type || '') + '</span>' +
            (r.notes ? '<span style="display:block; margin-top:2px; color:#5b6472;">' + esc(r.notes) + '</span>' : '') +
            '</div>' +
            '<button class="lc-btn-icon ec-detach-ref-btn" data-ref-id="' + r.reference_id + '" title="Detach"><i class="bi bi-x-circle"></i></button>' +
            '</div>';
    });
    html += '</div>';
    container.innerHTML = html;
    qsa('.ec-detach-ref-btn', container).forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Detach this reference?')) return;
            var fd = new FormData();
            fd.set('csrf_token', EC.csrf);
            fd.set('action', 'detach_reference');
            fd.set('case_id', EC.caseId);
            fd.set('reference_id', btn.getAttribute('data-ref-id'));
            var xhr = new XMLHttpRequest();
            xhr.open('POST', EC.api, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status >= 200 && xhr.status < 300) {
                    loadCaseDetail();
                }
            };
            xhr.send(fd);
        });
    });
    if (caseData) {
        loadSuggestedReferences(caseData);
    }
}

export function loadSuggestedReferences(caseData) {
    var container = qs('#ecReferencesList');
    if (!container || !caseData) return;

    var c = caseData.case || {};
    var relatedRecord = caseData.related_record || null;
    var relatedType = caseData.related_type || null;

    var terms = [];
    var dividerText = 'SUGGESTED REFERENCES';

    if (relatedRecord) {
        var relatedLabel = 'Related Record';
        if (relatedType === 'complaint') {
            relatedLabel = 'CMP-' + String(relatedRecord.id).padStart(5, '0') + ' — ' + (relatedRecord.title || '');
        } else if (relatedType === 'incident') {
            relatedLabel = (relatedRecord.incident_id || relatedRecord.id) + ' — ' + (relatedRecord.title || '');
        } else {
            relatedLabel = relatedRecord.title || 'Related Record';
        }
        dividerText = 'SUGGESTED FOR ' + relatedLabel;

        terms.push(relatedRecord.title, relatedRecord.description, relatedRecord.type || relatedRecord.incident_type || relatedRecord.record_type || '');
    }

    terms.push(c.case_type, c.specific_case_type, c.external_agency, c.case_title, c.description);

    var query = terms.filter(Boolean).map(function (v) { return String(v); }).join(' ');

    if (!query) {
        container.innerHTML = '<div class="lc-empty">No references attached yet.</div>';
        return;
    }

    var params = {
        action: 'search_references',
        q: query
    };

    if (relatedType && relatedRecord) {
        var relatedId = relatedRecord.id || relatedRecord.record_id || 0;
        if (relatedId) {
            params.related_type = relatedType;
            params.related_id = relatedId;
        }
    }

    getJSON(params, function (data) {
        if (!container) return;
        var suggestions = (data.data || []).slice(0, 3);
        if (!suggestions.length) {
            container.innerHTML = '<div class="lc-empty">No references attached yet.</div>';
            return;
        }
        var html = '<div class="ec-ref-suggested-header">' + esc(dividerText) + '</div>';
        html += '<div class="ec-ref-list">';
        suggestions.forEach(function (r) {
            html += '<div class="ec-ref-row ec-ref-row--suggested">' +
                '<div class="ec-ref-text">' +
                '<strong>' + esc(r.title || r.short_title || 'Reference') + '</strong>' +
                '<span>' + esc(r.reference_number || '') + ' | ' + esc(r.category_name || '') + ' | ' + esc(r.issuing_authority || '') + '</span>' +
                '</div>' +
                '<span class="ec-ref-suggested-badge">Suggested</span>' +
                '<button class="lc-btn primary ec-attach-ref-btn" data-ref-id="' + r.id + '" style="font-size:0.65rem; padding:2px 8px;">Attach</button>' +
                '</div>';
        });
        html += '</div>';
        container.innerHTML = html;
        qsa('.ec-attach-ref-btn', container).forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var refId = btn.getAttribute('data-ref-id');
                var fd = new FormData();
                fd.set('csrf_token', EC.csrf);
                fd.set('action', 'attach_reference');
                fd.set('case_id', EC.caseId);
                fd.set('reference_id', refId);
                fd.set('relation_type', 'Attached');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', EC.api, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onreadystatechange = function () {
                    if (xhr.readyState !== 4) return;
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            var res = JSON.parse(cleanJSON(xhr.responseText));
                            if (res.success) {
                                var libraryContainer = qs('#ecLawLibraryContainer');
                                if (libraryContainer) {
                                    var placeholder = libraryContainer.querySelector('.lc-empty');
                                    if (placeholder) {
                                        placeholder.remove();
                                    }
                                }
                                var newRef = res.data || { id: refId, title: 'Reference', reference_number: '', category_name: '', issuing_authority: '', relation_type: 'Attached' };
                                var refHtml = '<div class="ec-ref-row">' +
                                    '<div class="ec-ref-text">' +
                                    '<strong>' + esc(newRef.title || newRef.short_title || 'Reference') + '</strong>' +
                                    '<span>' + esc(newRef.reference_number || '') + ' | ' + esc(newRef.category_name || '') + ' | ' + esc(newRef.issuing_authority || '') + ' | ' + esc(newRef.relation_type || 'Attached') + '</span>' +
                                    '</div>' +
                                    '<button class="lc-btn-icon ec-detach-ref-btn" data-ref-id="' + newRef.id + '" title="Detach"><i class="bi bi-x-circle"></i></button>' +
                                    '</div>';
                                var list = libraryContainer.querySelector('.ec-ref-list');
                                if (!list) {
                                    list = document.createElement('div');
                                    list.className = 'ec-ref-list';
                                    libraryContainer.appendChild(list);
                                }
                                list.insertAdjacentHTML('beforeend', refHtml);
                                var newBtn = libraryContainer.querySelector('.ec-detach-ref-btn:last-child');
                                if (newBtn) {
                                    newBtn.addEventListener('click', function () {
                                        if (!confirm('Detach this reference?')) return;
                                        var detachFd = new FormData();
                                        detachFd.set('csrf_token', EC.csrf);
                                        detachFd.set('action', 'detach_reference');
                                        detachFd.set('case_id', EC.caseId);
                                        detachFd.set('reference_id', newBtn.getAttribute('data-ref-id'));
                                        var detachXhr = new XMLHttpRequest();
                                        detachXhr.open('POST', EC.api, true);
                                        detachXhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                                        detachXhr.onreadystatechange = function () {
                                            if (detachXhr.readyState === 4 && detachXhr.status >= 200 && detachXhr.status < 300) {
                                                newBtn.closest('.ec-ref-row').remove();
                                                if (!libraryContainer.querySelector('.ec-ref-row')) {
                                                    libraryContainer.innerHTML = '<div class="lc-empty">No references attached yet.</div>';
                                                }
                                            }
                                        };
                                        detachXhr.send(detachFd);
                                    });
                                }
                            } else {
                                alert(res.message || 'Failed to attach reference.');
                            }
                        } catch (err) {
                            alert('Invalid server response.');
                        }
                    } else {
                        alert('Failed to attach reference.');
                    }
                };
                xhr.send(fd);
            });
        });
    });
}

