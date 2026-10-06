import { qs, qsa, esc, getJSON, parseErr, cleanJSON } from './helpers.js';
import { updateIntakeForm, intakeMappings } from './intake.js';
import { loadCaseList, updateSummaryStats } from './case-list.js';
import { openCaseDetail, showListView, loadCaseDetail, formatDate } from './case-detail.js';
import { renderRoadmap, renderUpcomingHearings } from './roadmap.js';
import { renderDocuments } from './documents.js';
import { renderReferences, loadSuggestedReferences } from './references.js';
import { renderNotes } from './notes.js';
import { initEditRelatedSearch } from './related-record-search.js';

var modalCache = {};

function loadModal(name, onReady) {
    if (modalCache[name]) {
        if (onReady) onReady();
        return;
    }

    modalCache[name] = true;
    if (onReady) onReady();
}

export function init() {
    var urlCaseId = new URL(window.location).searchParams.get('case_id');
    if (urlCaseId) {
        openCaseDetail(parseInt(urlCaseId, 10));
    } else {
        loadCaseList();
    }

    window.addEventListener('popstate', function(e) {
        var cid = e.state && e.state.caseId ? e.state.caseId : new URL(window.location).searchParams.get('case_id');
        if (cid) {
            openCaseDetail(parseInt(cid, 10));
        } else {
            showListView();
            loadCaseList();
        }
    });

    initSummaryFilters();
    initSearchFilter();
    initEditCase();
    initCreateCase();
    initUploadDocument();
    initAddNote();
    initResolution();
    initAddEvent();
    initAddHearing();
    initEditRoadmap();
    initRefSearch();
    initDateInputs();
}

function initSummaryFilters() {
    qsa('.ec-summary-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var filterVal = item.getAttribute('data-filter') || '';
            if (filterVal.indexOf('status:') === 0) {
                EC.filters.status = filterVal.replace('status:', '');
                EC.filters.agency = '';
            } else if (filterVal.indexOf('agency:') === 0) {
                EC.filters.agency = filterVal.replace('agency:', '');
                EC.filters.status = '';
            } else {
                EC.filters.status = '';
                EC.filters.agency = '';
            }
            EC.filters.search = '';
            EC.page = 1;
            var statusSelect = qs('#ecFilterStatus');
            if (statusSelect) statusSelect.value = EC.filters.status;
            var agencySelect = qs('#ecFilterAgency');
            if (agencySelect) agencySelect.value = EC.filters.agency;
            var searchInput = qs('#ecSearchInput');
            if (searchInput) searchInput.value = '';
            qsa('.ec-summary-item').forEach(function(el) { el.classList.remove('ec-summary-item--active'); });
            item.classList.add('ec-summary-item--active');
            loadCaseList();
        });
    });

    var statusSelect = qs('#ecFilterStatus');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            EC.filters.status = this.value;
            EC.page = 1;
            loadCaseList();
        });
    }

    var agencySelect = qs('#ecFilterAgency');
    if (agencySelect) {
        agencySelect.addEventListener('change', function() {
            EC.filters.agency = this.value;
            EC.page = 1;
            loadCaseList();
        });
    }
}

function initSearchFilter() {
    var searchInput = qs('#ecSearchInput');
    if (searchInput) {
        var searchTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                EC.filters.search = searchInput.value.trim();
                EC.page = 1;
                loadCaseList();
            }, 250);
        });
    }
}

function initEditCase() {
    loadModal('modal-edit-case', function() {
        var editCaseBtn = qs('#ecMenuEditCase');
        var cancelEditBtn = qs('#ecCancelEditBtn');
        var editForm = qs('#ecEditCaseForm');
        var editAgency = qs('#ecEditAgency');
        var editSpecificType = qs('#ecEditSpecificCaseType');
        var editCaseType = qs('#ecEditCaseType');

        function updateEditForm() {
            var agencyVal = editAgency ? editAgency.value : '';
            var options = [];
            var category = '';
            if (agencyVal && intakeMappings[agencyVal]) {
                options = intakeMappings[agencyVal].types.slice();
                category = intakeMappings[agencyVal].category;
            }
            if (editSpecificType) {
                var currentVal = editSpecificType.value;
                editSpecificType.innerHTML = '<option value="">Select specific case type</option>';
                options.forEach(function(opt) {
                    var o = document.createElement('option');
                    o.value = opt;
                    o.textContent = opt;
                    editSpecificType.appendChild(o);
                });
                if (currentVal && options.indexOf(currentVal) === -1) {
                    var preserve = document.createElement('option');
                    preserve.value = currentVal;
                    preserve.textContent = currentVal;
                    preserve.selected = true;
                    editSpecificType.appendChild(preserve);
                } else if (currentVal) {
                    editSpecificType.value = currentVal;
                }
            }
            if (editCaseType) editCaseType.value = category;
        }

        function openEditCase() {
            if (!EC.caseId) return;
            getJSON({ action: 'case_detail', case_id: EC.caseId }, function(data) {
                if (!data.case) return;
                var c = data.case;
                var editCaseId = qs('#ecEditCaseId');
                var editCaseNumber = qs('#ecEditCaseNumber');
                var editAgencyEl = qs('#ecEditAgency');
                var editStatus = qs('#ecEditStatus');
                var editPriority = qs('#ecEditPriority');
                var editExternalRef = qs('#ecEditExternalRef');
                var editDateFiled = qs('#ecEditDateFiled');
                var editDateReceived = qs('#ecEditDateReceived');
                var editAssignedTo = qs('#ecEditAssignedTo');
                var editDescription = qs('#ecEditDescription');
                if (editCaseId) editCaseId.value = c.case_id || EC.caseId;
                if (editCaseNumber) editCaseNumber.value = c.case_number || '';
                if (editAgencyEl) {
                    editAgencyEl.value = c.external_agency || '';
                    updateEditForm();
                    setTimeout(function() {
                        var specificSelect = qs('#ecEditSpecificCaseType');
                        if (specificSelect && c.specific_case_type) {
                            specificSelect.value = c.specific_case_type;
                        }
                    }, 50);
                }
                if (editStatus) editStatus.value = c.current_status || '';
                if (editPriority) editPriority.value = c.priority || '';
                if (editExternalRef) editExternalRef.value = c.external_reference_no || '';
                if (editDateFiled) editDateFiled.value = c.date_filed || '';
                if (editDateReceived) editDateReceived.value = c.date_received || '';
                if (editAssignedTo) editAssignedTo.value = c.assigned_to || '';
                if (editDescription) editDescription.value = c.description || '';
                var editModal = qs('#ecEditModal');
                if (editModal) editModal.classList.add('ec-modal-backdrop--open');
                var editRelatedType = qs('#ecEditRelatedType');
                var editRelatedId = qs('#ecEditRelatedId');
                var editRelatedSearchInput = qs('#ecEditRelatedSearchInput');
                var editRelatedSearchField = qs('#ecEditRelatedSearchField');
                if (editRelatedType) {
                    if (data.related_type && data.related_type !== 'none') {
                        editRelatedType.value = data.related_type;
                    } else {
                        editRelatedType.value = 'none';
                    }
                }
                if (editRelatedSearchField) {
                    editRelatedSearchField.style.display = (editRelatedType && editRelatedType.value && editRelatedType.value !== 'none') ? 'block' : 'none';
                }
                if (editRelatedId && c.complaint_id) {
                    editRelatedId.value = c.complaint_id;
                }
                if (editRelatedSearchInput && data.related_record) {
                    var caseNumber = data.related_record.case_number || data.related_record.incident_id || '';
                    var title = data.related_record.title || '';
                    editRelatedSearchInput.value = caseNumber ? caseNumber + ' — ' + title : title;
                }
            });
        }

        function saveCaseEdit() {
            if (!editForm || !EC.caseId) return;
            var fd = new FormData(editForm);
            fd.set('csrf_token', EC.csrf);
            fd.set('case_id', EC.caseId);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', EC.api, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function() {
                if (xhr.readyState !== 4) return;
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var data = JSON.parse(cleanJSON(xhr.responseText));
                        if (data.success) {
                            var editModal = qs('#ecEditModal');
                            if (editModal) editModal.classList.remove('ec-modal-backdrop--open');
                            loadCaseDetail();
                            loadCaseList();
                            window.location.reload();
                        } else {
                            alert(data.message || 'Failed to update case.');
                        }
                    } catch (err) { alert('Invalid response.'); }
                } else {
                    alert('Request failed with status ' + xhr.status);
                }
            };
            xhr.send(fd);
        }

        function cancelEditCase() {
            var editModal = qs('#ecEditModal');
            if (editModal) editModal.classList.remove('ec-modal-backdrop--open');
        }

        var menuEditCase = qs('#ecMenuEditCase');
        if (menuEditCase) {
            menuEditCase.addEventListener('click', function() {
                openEditCase();
            });
        }
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', cancelEditCase);
        }
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveCaseEdit();
            });
        }
        if (editAgency) {
            editAgency.addEventListener('change', updateEditForm);
        }
        var editRelatedType = qs('#ecEditRelatedType');
        var editRelatedSearchField = qs('#ecEditRelatedSearchField');
        if (editRelatedType && editRelatedSearchField) {
            editRelatedType.addEventListener('change', function() {
                editRelatedSearchField.style.display = this.value === 'none' ? 'none' : 'block';
            });
        }
        initEditRelatedSearch();
    });
}

function initCreateCase() {
    loadModal('modal-create-case', function() {
        var newBtn = qs('#ecNewCaseBtn');
        var cancelCreateBtn = qs('#ecCancelCreateBtn');
        var createForm = qs('#ecCreateForm');
        var caseSource = qs('#ecCreateCaseSource');
        var externalAgency = qs('#ecCreateAgency');

        function saveCreateFormDraft() {
            if (!createForm) return;
            var data = {};
            var els = createForm.elements;
            for (var i = 0; i < els.length; i++) {
                var el = els[i];
                if (!el.name) continue;
                if (el.type === 'submit' || el.type === 'button') continue;
                if (el.tagName === 'NAV' || el.closest('.ec-breadcrumb')) continue;
                data[el.name] = el.value;
            }
            try { sessionStorage.setItem('ecCreateFormDraft', JSON.stringify(data)); } catch (e) {}
        }

        function restoreCreateFormDraft() {
            if (!createForm) return;
            try {
                var raw = sessionStorage.getItem('ecCreateFormDraft');
                if (!raw) return;
                var data = JSON.parse(raw);
                for (var name in data) {
                    var el = createForm.querySelector('[name="' + name + '"]');
                    if (el) el.value = data[name];
                }
            } catch (e) {}
        }

        function clearCreateFormDraft() {
            try { sessionStorage.removeItem('ecCreateFormDraft'); } catch (e) {}
        }

        var modal = qs('#ecCreateModal');
        if (newBtn) {
            newBtn.addEventListener('click', function() {
                restoreCreateFormDraft();
                if (modal) modal.classList.add('ec-modal-backdrop--open');
            });
        }
        if (cancelCreateBtn) {
            cancelCreateBtn.addEventListener('click', function() {
                saveCreateFormDraft();
                if (modal) modal.classList.remove('ec-modal-backdrop--open');
            });
        }
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    saveCreateFormDraft();
                    modal.classList.remove('ec-modal-backdrop--open');
                }
            });
        }
        if (caseSource) {
            caseSource.addEventListener('change', updateIntakeForm);
        }
        if (externalAgency) {
            externalAgency.addEventListener('change', updateIntakeForm);
        }
        if (createForm) {
            createForm.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                var errors = [];
                var requiredFields = createForm.querySelectorAll('[required]');
                requiredFields.forEach(function(field) {
                    var val = field.value && String(field.value).trim();
                    if (!val) {
                        errors.push(field);
                        field.style.borderColor = '#dc2626';
                    } else {
                        field.style.borderColor = '';
                    }
                });

                function validateVisibleContainer(containerId, fieldSelector, errorMessage) {
                    var container = qs('#' + containerId);
                    if (!container) return;
                    var style = window.getComputedStyle(container);
                    if (style.display === 'none' || style.visibility === 'hidden') {
                        return;
                    }
                    var field = qs(fieldSelector, container);
                    if (!field) return;
                    var val = field.value && String(field.value).trim();
                    if (!val) {
                        errors.push(field);
                        field.style.borderColor = '#dc2626';
                    } else {
                        field.style.borderColor = '';
                    }
                }

                validateVisibleContainer('ecCreateAgencyField', '#ecCreateAgency', 'External Authority is required.');

                var relatedType = qs('#ecCreateRelatedType');
                var relatedSearchField = qs('#ecCreateRelatedSearchField');
                if (relatedType && relatedSearchField) {
                    var relatedStyle = window.getComputedStyle(relatedSearchField);
                    if (relatedStyle.display !== 'none' && relatedType.value !== 'none') {
                        var relatedId = qs('#ecCreateRelatedId');
                        if (!relatedId || !relatedId.value) {
                            errors.push(qs('#ecCreateRelatedSearchInput'));
                            var relatedInput = qs('#ecCreateRelatedSearchInput');
                            if (relatedInput) relatedInput.style.borderColor = '#dc2626';
                        }
                    }
                }

                if (errors.length) {
                    alert('Please fill in all required fields before creating the case.');
                    if (errors[0]) errors[0].focus();
                    return;
                }

                var fd = new FormData(createForm);
                fd.set('csrf_token', EC.csrf);
                fd.set('action', 'create_case');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', EC.api, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState !== 4) return;
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            var data = JSON.parse(cleanJSON(xhr.responseText));
                            if (data.success) {
                                clearCreateFormDraft();
                                if (modal) modal.classList.remove('ec-modal-backdrop--open');
                                createForm.reset();
                                EC.page = 1;
                                loadCaseList();
                                alert(data.message || 'Case created successfully.');
                                window.location.reload();
                            } else {
                                alert(data.message || 'Failed to create case.');
                            }
                        } catch (err) { alert('Invalid server response.'); }
                    } else {
                        alert(parseErr(xhr));
                    }
                };
                xhr.send(fd);
            });
        }

        var relatedSearchInput = qs('#ecCreateRelatedSearchInput');
        var relatedId = qs('#ecCreateRelatedId');
        var relatedResults = qs('#ecCreateRelatedSearchResults');
        var relatedTypeSelect = qs('#ecCreateRelatedType');
        var relatedSearchField = qs('#ecCreateRelatedSearchField');
        if (relatedTypeSelect) {
            relatedTypeSelect.addEventListener('change', function() {
                if (relatedSearchField) {
                    relatedSearchField.style.display = (relatedTypeSelect.value && relatedTypeSelect.value !== 'none') ? '' : 'none';
                }
                if (relatedSearchInput) relatedSearchInput.value = '';
                if (relatedId) relatedId.value = '';
                if (relatedResults) relatedResults.style.display = 'none';
            });
        }
        if (relatedSearchInput && relatedResults) {
            var relatedTimer;
            relatedSearchInput.addEventListener('input', function() {
                clearTimeout(relatedTimer);
                var q = relatedSearchInput.value.trim();
                if (q.length < 1) { relatedResults.style.display = 'none'; return; }
                var typeParam = '';
                if (relatedTypeSelect) {
                    typeParam = relatedTypeSelect.value === 'none' ? '' : relatedTypeSelect.value;
                }
                relatedTimer = setTimeout(function() {
                    var url = '/hrms-capstone/modules/compliance/lib/api/search-related-records.php?q=' + encodeURIComponent(q);
                    if (typeParam) {
                        url += '&type=' + encodeURIComponent(typeParam);
                    }
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', url, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState !== 4) return;
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                var data = JSON.parse(cleanJSON(xhr.responseText));
                                if (data.success && data.data && data.data.length) {
                                    var html = '';
                                    data.data.forEach(function(r) {
                                        html += '<div class="ec-search-item" data-rid="' + r.record_id + '" data-case-number="' + esc(r.case_number) + '">' +
                                            '<strong>' + esc(r.case_number) + ' — ' + esc(r.title || r.short_title || 'Reference') + '</strong>' +
                                            '<span>' + esc(r.source || '') + ' · ' + esc(r.record_type || '') + '</span>' +
                                            '</div>';
                                    });
                                    relatedResults.innerHTML = html;
                                    qsa('.ec-search-item', relatedResults).forEach(function(item) {
                                        item.addEventListener('click', function() {
                                            var rid = item.getAttribute('data-rid');
                                            var caseNumber = item.getAttribute('data-case-number');
                                            if (relatedId) relatedId.value = rid;
                                            relatedSearchInput.value = caseNumber;
                                            relatedResults.style.display = 'none';
                                        });
                                    });
                                } else {
                                    relatedResults.innerHTML = '<div class="ec-search-item">No records found.</div>';
                                }
                                relatedResults.style.display = 'block';
                            } catch (e) {
                                relatedResults.innerHTML = '<div class="ec-search-item">Invalid response: ' + esc(xhr.responseText) + '</div>';
                                relatedResults.style.display = 'block';
                            }
                        } else {
                            var errMsg = 'Request failed with status ' + xhr.status;
                            try { var err = JSON.parse(cleanJSON(xhr.responseText)); if (err && err.error) errMsg = err.error; } catch (e) {}
                            relatedResults.innerHTML = '<div class="ec-search-item">' + esc(errMsg) + '</div>';
                            relatedResults.style.display = 'block';
                        }
                    };
                    xhr.send();
                }, 300);
            });
        }
    });
}

function initUploadDocument() {
    loadModal('modal-upload-document', function() {
        var uploadDocBtn = qs('#ecMenuUploadDocument');
        var cancelUploadBtn = qs('#ecCancelUploadBtn');
        var uploadDocModal = qs('#ecUploadDocModal');
        function openUploadDocModal() { if (uploadDocModal) uploadDocModal.classList.add('ec-modal-backdrop--open'); }
        function closeUploadDocModal() { if (uploadDocModal) uploadDocModal.classList.remove('ec-modal-backdrop--open'); }
        if (uploadDocBtn) {
            uploadDocBtn.addEventListener('click', openUploadDocModal);
        }
        if (cancelUploadBtn) {
            cancelUploadBtn.addEventListener('click', closeUploadDocModal);
        }
        if (uploadDocModal) {
            uploadDocModal.addEventListener('click', function(e) {
                if (e.target === uploadDocModal) closeUploadDocModal();
            });
        }
        var uploadForm = qs('#ecUploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var fd = new FormData(uploadForm);
                fd.set('csrf_token', EC.csrf);
                fd.set('action', 'upload_document');
                fd.set('case_id', EC.caseId);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', EC.api, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status >= 200 && xhr.status < 300) {
                        uploadForm.reset();
                        var docDateField = qs('#ecDocDate');
                        if (docDateField) docDateField.value = '';
                        closeUploadDocModal();
                        loadCaseDetail();
                        window.location.reload();
                    }
                };
                xhr.send(fd);
            });
        }
    });
}

function initAddNote() {
    loadModal('modal-add-note', function() {
        var addNoteBtn = qs('#ecMenuAddNote');
        var cancelNoteBtn = qs('#ecCancelNoteBtn');
        var addNoteModal = qs('#ecAddNoteModal');
        function openNoteModal() { if (addNoteModal) addNoteModal.classList.add('ec-modal-backdrop--open'); }
        function closeNoteModal() { if (addNoteModal) addNoteModal.classList.remove('ec-modal-backdrop--open'); }
        if (addNoteBtn) {
            addNoteBtn.addEventListener('click', function() {
                openNoteModal();
                var caseNote = qs('#ecCaseNote');
                if (caseNote) setTimeout(function() { caseNote.focus(); }, 300);
            });
        }
        if (cancelNoteBtn) {
            cancelNoteBtn.addEventListener('click', closeNoteModal);
        }
        if (addNoteModal) {
            addNoteModal.addEventListener('click', function(e) {
                if (e.target === addNoteModal) closeNoteModal();
            });
        }
        var addNoteForm = qs('#ecAddNoteForm');
        if (addNoteForm) {
            addNoteForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var fd = new FormData(addNoteForm);
                fd.set('csrf_token', EC.csrf);
                fd.set('action', 'add_note');
                fd.set('case_id', EC.caseId);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', EC.api, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status >= 200 && xhr.status < 300) {
                        addNoteForm.reset();
                        closeNoteModal();
                        loadCaseDetail();
                        window.location.reload();
                    }
                };
                xhr.send(fd);
            });
        }
    });
}

function initResolution() {
    loadModal('modal-resolution', function() {
        var closeCaseBtn = qs('#ecMenuCloseCase');
        var resolutionModal = qs('#ecResolutionModal');
        var closeResolutionModalBtn = qs('#ecCloseResolutionModalBtn');
        var cancelResolutionBtn = qs('#ecCancelResolutionBtn');
        function openResolutionModal() { if (resolutionModal) resolutionModal.classList.add('ec-modal-backdrop--open'); }
        function closeResolutionModal() { if (resolutionModal) resolutionModal.classList.remove('ec-modal-backdrop--open'); }
        if (closeCaseBtn) {
            closeCaseBtn.addEventListener('click', openResolutionModal);
        }
        if (closeResolutionModalBtn) {
            closeResolutionModalBtn.addEventListener('click', closeResolutionModal);
        }
        if (cancelResolutionBtn) {
            cancelResolutionBtn.addEventListener('click', closeResolutionModal);
        }
        if (resolutionModal) {
            resolutionModal.addEventListener('click', function(e) {
                if (e.target === resolutionModal) closeResolutionModal();
            });
        }
        var resForm = qs('#ecResolutionForm');
        if (resForm) {
            resForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!EC.caseId) return;
                var resDateField = qs('#ecResolutionDate');
                if (resDateField && resDateField.value) {
                    var raw = resDateField.value.replace(/\D/g, '');
                    if (raw.length === 8) {
                        resDateField.value = raw.slice(4, 8) + '-' + raw.slice(2, 4) + '-' + raw.slice(0, 2);
                    }
                }
                var fd = new FormData(resForm);
                fd.set('csrf_token', EC.csrf);
                fd.set('action', 'close_case');
                fd.set('case_id', EC.caseId);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', EC.api, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status >= 200 && xhr.status < 300) {
                        try {
                            var data = JSON.parse(cleanJSON(xhr.responseText));
                            if (data.success) {
                                closeResolutionModal();
                                loadCaseDetail();
                                loadCaseList();
                                alert(data.message || 'Case closed successfully.');
                                window.location.reload();
                            } else {
                                alert(data.message || 'Failed to close case.');
                            }
                        } catch (err) { alert('Invalid response.'); }
                    } else if (xhr.readyState === 4) {
                        alert('Request failed with status ' + xhr.status);
                    }
                };
                xhr.send(fd);
            });
        }
    });
}

function initAddEvent() {
    loadModal('modal-add-event', function() {
        var addEventBtn = qs('#ecMenuAddEvent') || qs('#ecAddEventBtn');
        var cancelEventBtn = qs('#ecCancelEventBtn');
        var addEventForm = qs('#ecAddEventForm');
        var addEventModal = qs('#ecAddEventModal');
        function openEventModal() { if (addEventModal) addEventModal.classList.add('ec-modal-backdrop--open'); }
        function closeEventModal() { if (addEventModal) addEventModal.classList.remove('ec-modal-backdrop--open'); }
        if (addEventBtn) {
            addEventBtn.addEventListener('click', openEventModal);
        }
        if (cancelEventBtn) {
            cancelEventBtn.addEventListener('click', closeEventModal);
        }
        if (addEventModal) {
            addEventModal.addEventListener('click', function(e) {
                if (e.target === addEventModal) closeEventModal();
            });
        }
        if (addEventForm) {
            addEventForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var fd = new FormData(addEventForm);
                fd.set('csrf_token', EC.csrf);
                var eventIdField = qs('input[name="event_id"]', addEventForm);
                var action = eventIdField && eventIdField.value ? 'update_event' : 'add_event';
                fd.set('action', action);
                fd.set('case_id', EC.caseId);
                if (eventIdField && eventIdField.value) {
                    fd.set('event_id', eventIdField.value);
                }
                var xhr = new XMLHttpRequest();
                xhr.open('POST', EC.api, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status >= 200 && xhr.status < 300) {
                        addEventForm.reset();
                        if (eventIdField) eventIdField.remove();
                        var actionHidden = qs('input[name="action"]', addEventForm);
                        if (actionHidden) actionHidden.remove();
                        var submitBtn = qs('button[type="submit"]', addEventForm);
                        if (submitBtn) {
                            submitBtn.innerHTML = '<i class="bi bi-plus-lg"></i> Add Milestone';
                        }
                        closeEventModal();
                        loadCaseDetail();
                        window.location.reload();
                    } else if (xhr.readyState === 4) {
                        try {
                            var err = JSON.parse(cleanJSON(xhr.responseText));
                            alert(err.message || 'Failed to save milestone. Please try again.');
                        } catch (ex) {
                            alert('Failed to save milestone. Please try again.');
                        }
                    }
                };
                xhr.send(fd);
            });
        }
    });
}

function initAddHearing() {
    loadModal('modal-add-hearing', function() {
        var addHearingBtn = qs('#ecMenuAddHearing');
        var cancelHearingBtn = qs('#ecCancelHearingBtn');
        var addHearingForm = qs('#ecAddHearingForm');
        var addHearingModal = qs('#ecAddHearingModal');
        function openHearingModal() { if (addHearingModal) addHearingModal.classList.add('ec-modal-backdrop--open'); }
        function closeHearingModal() { if (addHearingModal) addHearingModal.classList.remove('ec-modal-backdrop--open'); }
        if (addHearingBtn) {
            addHearingBtn.addEventListener('click', openHearingModal);
        }
        if (cancelHearingBtn) {
            cancelHearingBtn.addEventListener('click', closeHearingModal);
        }
        if (addHearingModal) {
            addHearingModal.addEventListener('click', function(e) {
                if (e.target === addHearingModal) closeHearingModal();
            });
        }
        if (addHearingForm) {
            addHearingForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var fd = new FormData(addHearingForm);
                fd.set('csrf_token', EC.csrf);
                fd.set('action', 'add_event');
                fd.set('case_id', EC.caseId);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', EC.api, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status >= 200 && xhr.status < 300) {
                        addHearingForm.reset();
                        closeHearingModal();
                        loadCaseDetail();
                        window.location.reload();
                    } else if (xhr.readyState === 4) {
                        try {
                            var err = JSON.parse(cleanJSON(xhr.responseText));
                            alert(err.message || 'Failed to save hearing. Please try again.');
                        } catch (ex) {
                            alert('Failed to save hearing. Please try again.');
                        }
                    }
                };
                xhr.send(fd);
            });
        }
    });
}

function initEditRoadmap() {
    loadModal('modal-edit-roadmap', function() {
        var editRoadmapModal = qs('#ecEditRoadmapModal');
        var closeEditRoadmapBtn = qs('#ecCloseEditRoadmapBtn');
        var cancelEditRoadmapBtn = qs('#ecCancelEditRoadmapBtn');
        var editRoadmapForm = qs('#ecEditRoadmapForm');
        function openEditRoadmapModal() { if (editRoadmapModal) editRoadmapModal.classList.add('ec-modal-backdrop--open'); }
        function closeEditRoadmapModal() { if (editRoadmapModal) editRoadmapModal.classList.remove('ec-modal-backdrop--open'); }
        if (closeEditRoadmapBtn) {
            closeEditRoadmapBtn.addEventListener('click', closeEditRoadmapModal);
        }
        if (cancelEditRoadmapBtn) {
            cancelEditRoadmapBtn.addEventListener('click', closeEditRoadmapModal);
        }
        if (editRoadmapModal) {
            editRoadmapModal.addEventListener('click', function(e) {
                if (e.target === editRoadmapModal) closeEditRoadmapModal();
            });
        }
        if (editRoadmapForm) {
            editRoadmapForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var fd = new FormData(editRoadmapForm);
                fd.set('csrf_token', EC.csrf);
                fd.set('action', 'update_event');
                fd.set('case_id', EC.caseId);
                var eventIdInput = qs('#ecEditRoadmapEventId');
                if (eventIdInput && eventIdInput.value) {
                    fd.set('event_id', eventIdInput.value);
                }
                var xhr = new XMLHttpRequest();
                xhr.open('POST', EC.api, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status >= 200 && xhr.status < 300) {
                        editRoadmapForm.reset();
                        closeEditRoadmapModal();
                        loadCaseDetail();
                        window.location.reload();
                    } else if (xhr.readyState === 4) {
                        try {
                            var err = JSON.parse(cleanJSON(xhr.responseText));
                            alert(err.message || 'Failed to update roadmap. Please try again.');
                        } catch (ex) {
                            alert('Failed to update roadmap. Please try again.');
                        }
                    }
                };
                xhr.send(fd);
            });
        }
    });
}

function initRefSearch() {
    var toggleRefSearch = qs('#ecToggleRefSearch');
    var refSearch = qs('#ecRefSearch');
    if (toggleRefSearch && refSearch) {
        toggleRefSearch.addEventListener('click', function() {
            refSearch.style.display = refSearch.style.display === 'none' ? 'block' : 'none';
        });
    }

    var refSearchInput = qs('#ecRefSearchInput');
    var refSearchResults = qs('#ecRefSearchResults');
    if (refSearchInput && refSearchResults) {
        var refTimer;
        refSearchInput.addEventListener('input', function() {
            clearTimeout(refTimer);
            var q = refSearchInput.value.trim();
            if (q.length < 2) { refSearchResults.style.display = 'none'; return; }
            refTimer = setTimeout(function() {
                getJSON({ action: 'search_references', q: q }, function(data) {
                    if (!data.data || !data.data.length) {
                        refSearchResults.innerHTML = '<div class="ec-search-item">No references found.</div>';
                    } else {
                        var html = '';
                        data.data.forEach(function(r) {
                            html += '<div class="ec-search-item" data-ref-id="' + r.id + '">' +
                                '<strong>' + esc(r.title || r.short_title || 'Reference') + '</strong>' +
                                '<span>' + esc(r.reference_number || '') + ' | ' + esc(r.issuing_authority || '') + '</span>' +
                                '<button class="lc-btn primary ec-attach-ref-btn" data-ref-id="' + r.id + '" style="font-size:0.65rem; padding:2px 8px; margin-top:4px;">Attach</button>' +
                                '</div>';
                        });
                        refSearchResults.innerHTML = html;
                        qsa('.ec-attach-ref-btn', refSearchResults).forEach(function(btn) {
                            btn.addEventListener('click', function(e) {
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
                                xhr.onreadystatechange = function() {
                                    if (xhr.readyState === 4 && xhr.status >= 200 && xhr.status < 300) {
                                        refSearchResults.style.display = 'none';
                                        refSearchInput.value = '';
                                        loadCaseDetail();
                                    }
                                };
                                xhr.send(fd);
                            });
                        });
                    }
                });
            });
        });
    }
}

function initDateInputs() {
    var resolutionDate = qs('#ecResolutionDate');
    function formatDateInput(el) {
        if (!el) return;
        el.addEventListener('blur', function() {
            var v = el.value.replace(/\D/g, '');
            if (v.length === 8) {
                el.value = v.slice(0, 2) + '/' + v.slice(2, 4) + '/' + v.slice(4, 8);
            }
        });
        el.addEventListener('input', function() {
            var v = el.value.replace(/\D/g, '');
            if (v.length > 8) v = v.slice(0, 8);
            el.value = v;
        });
    }
    formatDateInput(resolutionDate);
}
