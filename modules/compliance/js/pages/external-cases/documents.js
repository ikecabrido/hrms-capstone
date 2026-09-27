import { qs, qsa, esc } from './helpers.js';
import { loadCaseDetail } from './case-detail.js';

export function renderDocuments(docs) {
    var container = qs('#ecDocumentsList');
    if (!container) return;
    if (!docs.length) {
        container.innerHTML = '<div class="lc-empty">No documents uploaded yet.</div>';
        return;
    }
    var html = '<div class="ec-file-list">';
    docs.forEach(function (d) {
        html += '<div class="ec-file-row">' +
            '<div class="ec-file-text">' +
            '<strong>' + esc(d.document_name) + '</strong>' +
            '<span>' + esc(d.document_type || '') + ' | ' + esc(d.created_at || '') + ' | Uploaded by ' + esc(d.uploaded_by_name || '—') + '</span>' +
            '</div>' +
            '<div class="ec-file-actions">' +
            '<a href="' + esc(d.file_path) + '" target="_blank" class="lc-btn-icon" title="Download"><i class="bi bi-download"></i></a>' +
            '<button class="lc-btn-icon ec-delete-doc-btn" data-doc-id="' + d.document_id + '" title="Delete"><i class="bi bi-trash"></i></button>' +
            '</div>' +
            '</div>';
    });
    html += '</div>';
    container.innerHTML = html;
    qsa('.ec-delete-doc-btn', container).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (!confirm('Delete this document?')) return;
            var fd = new FormData();
            fd.set('csrf_token', EC.csrf);
            fd.set('action', 'delete_document');
            fd.set('case_id', EC.caseId);
            fd.set('document_id', btn.getAttribute('data-doc-id'));
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
}
