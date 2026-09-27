import { qs, esc, statusClass, getJSON } from './helpers.js';
import { renderRoadmap, renderUpcomingHearings } from './roadmap.js';
import { renderDocuments } from './documents.js';
import { renderReferences, loadSuggestedReferences } from './references.js';
import { renderNotes } from './notes.js';

var detailDocClickListener = null;

export function openCaseDetail(caseId) {
    EC.caseId = caseId;
    var listView = qs('#ecListView');
    var detailView = qs('#ecDetailView');

    if (listView) listView.style.display = 'none';
    if (detailView) detailView.style.display = 'block';

    loadCaseDetail();
    initDetailEventListeners();

    var url = new URL(window.location);
    url.searchParams.set('case_id', caseId);
    window.history.pushState({ caseId: caseId }, '', url);
}

export function showListView() {
    EC.caseId = null;
    var listView = qs('#ecListView');
    var detailView = qs('#ecDetailView');

    if (listView) listView.style.display = 'block';
    if (detailView) detailView.style.display = 'none';

    var url = new URL(window.location);
    url.searchParams.delete('case_id');
    window.history.pushState({ caseId: null }, '', url);
}

function initDetailEventListeners() {
    var moreMenuToggle = qs('#ecMoreCaseBtn');
    var moreMenu = qs('#ecMoreCaseMenu');

    if (moreMenuToggle && moreMenu) {
        moreMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            moreMenu.classList.toggle('is-open');
            moreMenuToggle.setAttribute('aria-expanded', moreMenu.classList.contains('is-open'));
        });
    }

    if (!detailDocClickListener) {
        detailDocClickListener = function(e) {
            var currentMoreMenu = qs('#ecMoreCaseMenu');
            var currentToggle = qs('#ecMoreCaseBtn');
            if (currentMoreMenu && currentMoreMenu.classList.contains('is-open') && !currentMoreMenu.contains(e.target) && e.target !== currentToggle) {
                currentMoreMenu.classList.remove('is-open');
                if (currentToggle) currentToggle.setAttribute('aria-expanded', 'false');
            }
        };
        document.addEventListener('click', detailDocClickListener);
    }
}

export function loadCaseDetail() {
    if (!EC.caseId) return;
    getJSON({ action: 'case_detail', case_id: EC.caseId }, function(data) {
        if (!data.case) return;
        var c = data.case;

        var editModal = qs('#ecEditModal');
        if (editModal) editModal.classList.remove('ec-modal-backdrop--open');

        var headerCaseNumber = qs('#ecHeaderCaseNumber');
        var headerStatus = qs('#ecHeaderStatus');
        var headerPriority = qs('#ecHeaderPriority');
        var headerAssigned = qs('#ecHeaderAssigned');
        var headerDateReceived = qs('#ecHeaderDateReceived');
        var headerExternalRef = qs('#ecHeaderExternalRef');
        var breadcrumbCurrent = qs('#ecBreadcrumbCurrent');
        if (headerCaseNumber) headerCaseNumber.textContent = c.case_number || '—';
        if (headerStatus) {
            headerStatus.className = 'lc-status-stamp ' + statusClass(c.current_status);
            headerStatus.textContent = c.current_status || '—';
        }
        if (headerPriority) headerPriority.textContent = c.priority || '—';
        if (headerAssigned) headerAssigned.textContent = c.assigned_name || 'Unassigned';
        if (headerDateReceived) headerDateReceived.textContent = c.date_received ? formatDate(c.date_received) : '—';
        if (headerExternalRef) headerExternalRef.textContent = c.external_reference_no || '—';
        if (breadcrumbCurrent) breadcrumbCurrent.textContent = 'Case Information';

        var quickAuthority = qs('#ecQuickAuthority');
        var quickCaseType = qs('#ecQuickCaseType');
        var quickPriority = qs('#ecQuickPriority');
        var quickStatus = qs('#ecQuickStatus');
        var quickAssigned = qs('#ecQuickAssigned');
        var quickDateReceived = qs('#ecQuickDateReceived');
        if (quickAuthority) quickAuthority.textContent = c.external_agency || '—';
        if (quickCaseType) quickCaseType.textContent = c.case_type || '—';
        if (quickPriority) quickPriority.textContent = c.priority || '—';
        if (quickStatus) {
            quickStatus.innerHTML = '<span class="lc-status-stamp ' + statusClass(c.current_status) + '">' + esc(c.current_status) + '</span>';
        }
        if (quickAssigned) quickAssigned.textContent = c.assigned_name || 'Unassigned';
        if (quickDateReceived) quickDateReceived.textContent = c.date_received ? formatDate(c.date_received) : '—';

        var detailCaseNumber = qs('#ecDetailCaseNumber');
        var detailAuthority = qs('#ecDetailAuthority');
        var detailCaseType = qs('#ecDetailCaseType');
        var detailStatus = qs('#ecDetailStatus');
        var detailPriority = qs('#ecDetailPriority');
        var detailExternalRef = qs('#ecDetailExternalRef');
        var detailDateFiled = qs('#ecDetailDateFiled');
        var detailDateReceived = qs('#ecDetailDateReceived');
        var detailAssigned = qs('#ecDetailAssigned');
        var detailRelated = qs('#ecDetailRelated');
        var detailDescription = qs('#ecDetailDescription');
        if (detailCaseNumber) detailCaseNumber.textContent = c.case_number || '—';
        if (detailAuthority) detailAuthority.textContent = c.external_agency || '—';
        if (detailCaseType) detailCaseType.textContent = c.case_type || '—';
        if (detailStatus) {
            detailStatus.innerHTML = '<span class="lc-status-stamp ' + statusClass(c.current_status) + '">' + esc(c.current_status) + '</span>';
        }
        if (detailPriority) detailPriority.textContent = c.priority || '—';
        if (detailExternalRef) detailExternalRef.textContent = c.external_reference_no || '—';
        if (detailDateFiled) detailDateFiled.textContent = c.date_filed ? formatDate(c.date_filed) : '—';
        if (detailDateReceived) detailDateReceived.textContent = c.date_received ? formatDate(c.date_received) : '—';
        if (detailAssigned) detailAssigned.textContent = c.assigned_name || 'Unassigned';
        if (detailRelated) {
            var relatedText = '—';
            var relatedHref = '';
            if (data.related_record) {
                var caseNumber = data.related_record.case_number || data.related_record.incident_id || '';
                var title = data.related_record.title || '';
                relatedText = caseNumber ? caseNumber + ' — ' + title : title;
                if (data.related_type === 'complaint' && data.related_record.id) {
                    relatedHref = '?page=complaint-workflow&id=' + data.related_record.id;
                } else if (data.related_type === 'incident' && data.related_record.id) {
                    relatedHref = '?page=incident-workflow&id=' + data.related_record.id;
                }
            } else if (data.related_complaint) {
                relatedText = data.related_complaint.title || '—';
                if (data.related_complaint.id) {
                    relatedHref = '?page=complaint-workflow&id=' + data.related_complaint.id;
                }
            }
            if (relatedHref) {
                detailRelated.innerHTML = '<a href="' + esc(relatedHref) + '" class="ec-related-link" target="_blank" rel="noopener">' + esc(relatedText) + '</a>';
            } else {
                detailRelated.textContent = relatedText;
            }
        }
        if (detailDescription) detailDescription.textContent = c.description || '—';

        renderRoadmap(data.events || []);
        renderUpcomingHearings(data.events || []);
        renderDocuments(data.documents || []);
        renderReferences(data.references || [], data);
        renderNotes(data.notes || []);
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    var d = new Date(dateStr + 'T00:00:00');
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

export { formatDate };
