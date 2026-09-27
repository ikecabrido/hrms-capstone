// =============================================================================
// Complaint Workflow — Interactive Decision Engine
// Mirrors incident workflow patterns with complaint-specific logic.
// =============================================================================

const CHWF_API = './lib/api/complaint_workflow_action.php';
const CHWF_STATUS_API = './lib/api/complaint_workflow_status.php';
const CHWF_DECISION_HISTORY_API = './lib/api/get_lc_complaint_decision_history.php';
const CHWF_SEARCH_API = './lib/api/search_hr_employees.php';
const CHWF_EMPLOYEE_HISTORY_API = './lib/api/get_employee_disciplinary_history.php';

// =============================
// State
// =============================
let chwf = {
    complaintId: 0,
    currentStepKey: null,
    currentStepIndex: 0,
    workflowSteps: [],
    answers: {},
    stepStatus: {},
    decisions: {},
    reopenHistory: [],
    transitionHistory: [],
    userInfo: { name: 'Compliance Officer', id: 1 },
    saveTimer: null,
    dirty: false
};

// =============================
// Helpers
// =============================
function chwfEsc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function chwfSetStatus(el, text, cls) {
    if (!el) return;
    el.textContent = text;
    el.className = 'irwf-action-status' + (cls ? ' ' + cls : '');
}

function chwfGetStepEl(key) {
    return document.querySelector('.irwf-flow-step[data-step-key="' + chwfEsc(key) + '"]');
}

function chwfGetCurrentStepEl() {
    return document.querySelector('.irwf-flow-step.current');
}

function chwfGetActionStatusEl(stepEl) {
    if (!stepEl) stepEl = chwfGetCurrentStepEl();
    if (!stepEl) return null;
    return stepEl.querySelector('.irwf-action-status');
}

// =============================
// Initialization
// =============================
function chwfInit() {
    const cfg = window.CHWF_CONFIG || {};
    chwf.complaintId = parseInt(cfg.complaintId || document.body.getAttribute('data-complaint-id') || document.querySelector('[data-complaint-id]')?.getAttribute('data-complaint-id') || '0', 10);
    chwf.currentStepKey = cfg.currentStepKey || document.body.getAttribute('data-current-step-key') || '';
    chwf.workflowSteps = Array.from(document.querySelectorAll('.irwf-flow-step')).map(function(el) {
        return {
            key: el.getAttribute('data-step-key'),
            index: parseInt(el.getAttribute('data-step-index') || '0', 10)
        };
    });
    chwf.userInfo = chwfReadUserInfo();

    chwfBindActionToggles();
    chwfBindDecisionPoints();
    chwfBindOfficerSearch();

    if (chwf.currentStepKey) {
        chwfScrollToCurrent();
    }

    chwfStartRealtimeSync();

    window.addEventListener('beforeunload', function(e) {
        chwfStopRealtimeSync();
    });
}

function chwfReadUserInfo() {
    const el = document.getElementById('chwfUserInfo');
    if (!el) return { name: 'Compliance Officer', id: 1 };
    try {
        return JSON.parse(el.textContent) || { name: 'Compliance Officer', id: 1 };
    } catch {
        return { name: 'Compliance Officer', id: 1 };
    }
}

// =============================
// Realtime Sync
// =============================
let chwfPollTimer = null;
let chwfLastKnownStatus = null;

function chwfStartRealtimeSync() {
    if (chwfPollTimer) return;
    chwfPollTimer = setInterval(chwfPollComplaintStatus, 5000);
}

function chwfStopRealtimeSync() {
    if (chwfPollTimer) {
        clearInterval(chwfPollTimer);
        chwfPollTimer = null;
    }
}

function chwfPollComplaintStatus() {
    if (!chwf.complaintId || !CHWF_STATUS_API) return;

    const xhr = new XMLHttpRequest();
    xhr.open('GET', CHWF_STATUS_API + '?complaint_id=' + encodeURIComponent(chwf.complaintId), true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) return;

        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            return;
        }

        if (!data || !data.success || !data.complaint) return;

        const complaint = data.complaint;
        const newStatus = complaint.status;

        if (chwfLastKnownStatus && chwfLastKnownStatus !== newStatus) {
            chwfLastKnownStatus = newStatus;
            chwfRefreshStaticComplaintInfo(complaint);
            chwfRefreshWorkflowStepStates(data.workflow || []);
        } else if (!chwfLastKnownStatus) {
            chwfLastKnownStatus = newStatus;
        }
    };
    xhr.send();
}

function chwfRefreshStaticComplaintInfo(complaint) {
    const fields = document.querySelectorAll('.irwf-field');
    fields.forEach(function(field) {
        const label = field.querySelector('.irwf-label');
        const value = field.querySelector('.irwf-value');
        if (!label || !value) return;

        const labelText = (label.textContent || '').trim();
        if (labelText === 'Current Phase' && complaint.status) {
            value.textContent = chwfStatusLabel(complaint.status);
        } else if (labelText === 'Severity' && complaint.severity) {
            value.textContent = complaint.severity.charAt(0).toUpperCase() + complaint.severity.slice(1);
        } else if (labelText === 'Complaint Type' && complaint.type) {
            value.textContent = complaint.type;
        } else if (labelText === 'Assigned Officer' && complaint.assigned_name) {
            value.textContent = complaint.assigned_name;
        }
    });

    if (complaint.updated_at) {
        const date = new Date(complaint.updated_at.replace(' ', 'T'));
        const formatted = date.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
        const metaEls = document.querySelectorAll('.irwf-flow-meta');
        metaEls.forEach(function(el) { el.textContent = formatted; });
    }
}

function chwfRefreshWorkflowStepStates(workflowSteps) {
    const completedSteps = new Set();
    workflowSteps.forEach(function(step) {
        if (step.step_status === 'completed' || step.step_status === 'in_progress') {
            completedSteps.add(step.step);
        }
    });

    document.querySelectorAll('.irwf-flow-step').forEach(function(el) {
        const stepKey = el.getAttribute('data-step-key');
        if (!stepKey) return;

        const isCurrent = stepKey === chwf.currentStepKey;
        const isCompleted = completedSteps.has(stepKey);

        if (isCompleted && !isCurrent) {
            el.classList.add('completed');
            el.classList.remove('current', 'pending');
        } else if (isCurrent) {
            el.classList.add('current');
            el.classList.remove('completed', 'pending');
        }
    });
}

// =============================
// Actions / Transitions
// =============================
function chwfAdvanceStep(fromStep, toStep, action, reason) {
    chwf.recordTransition(fromStep, toStep, action, reason);

    const fromEl = chwfGetStepEl(fromStep);
    const toEl = chwfGetStepEl(toStep);

    if (fromEl) {
        fromEl.classList.remove('current');
        fromEl.classList.add('completed');
        const actions = fromEl.querySelector('.irwf-step-actions');
        if (actions) actions.style.display = 'none';
    }

    if (toEl) {
        toEl.classList.add('current');
        toEl.classList.remove('completed', 'pending');
        const actions = toEl.querySelector('.irwf-step-actions');
        if (actions) actions.style.display = '';
    }

    chwf.currentStepKey = toStep;
    chwf.dirty = false;

    chwfNotify('Transitioned to ' + (getStepLabel(toStep) || toStep), 'success');
    setTimeout(function() {
        chwfScrollToCurrent();
    }, 400);
    setTimeout(function() { location.reload(); }, 1000);
}

function getStepLabel(stepKey) {
    const map = {
        'assign_officer': 'Assign Compliance Officer',
        'evidence_check': 'Check Evidence and Complainant Testimony',
        'nte_issued': 'Send NTE (Notice to Explain) to the Employee',
        'employee_hearing': 'Hearing (Recording Employee Response)',
        'decision_made': 'Decision',
        'termination_employee_reply': 'Email Reply',
        'termination_review': 'Review Action'
    };
    return map[stepKey] || stepKey;
}

function chwfReopenStep(stepKey) {
    const reason = prompt('Why are you reopening this step?');
    if (reason === null) return;

    chwf.reopenHistory.push({
        stepKey: stepKey,
        reason: reason,
        timestamp: new Date().toISOString(),
        userId: chwf.userInfo.id,
        userName: chwf.userInfo.name
    });

    chwf.stepStatus[stepKey] = 'reopened';
    chwf.answers = {};
    chwf.currentQuestionId = null;

    const stepEl = chwfGetStepEl(stepKey);
    if (stepEl) {
        stepEl.classList.add('current');
        stepEl.classList.remove('completed');
    }

    chwf.currentStepKey = stepKey;
    chwfNotify('Step reopened for reassessment.', 'success');
    setTimeout(function() {
        chwfScrollToCurrent();
    }, 400);
}

function chwfRecordTransition(fromStep, toStep, action, reason) {
    chwf.transitionHistory.push({
        complaintId: chwf.complaintId,
        fromStep: fromStep,
        toStep: toStep,
        action: action,
        reason: reason,
        userId: chwf.userInfo.id,
        userName: chwf.userInfo.name,
        timestamp: new Date().toISOString()
    });
}

// =============================
// Decision Persistence
// =============================
function chwfSaveDecision(decision, callback) {
    const stepKey = chwf.currentStepKey;
    const url = './lib/api/complaint_workflow_questions.php?complaint_id=' + encodeURIComponent(chwf.complaintId) + '&workflow_step_key=' + encodeURIComponent(stepKey) + '&action=save_decision';
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) {
            if (callback) callback(false);
            return;
        }
        try {
            const data = JSON.parse(xhr.responseText);
            if (callback) callback(!!data.success);
        } catch (e) {
            if (callback) callback(false);
        }
    };
    xhr.send(JSON.stringify(decision));
}

// =============================
// UI Helpers
// =============================
function chwfNotify(message, type) {
    let notif = document.querySelector('.irwf-step-notification');
    if (!notif) {
        notif = document.createElement('div');
        notif.className = 'irwf-step-notification';
        notif.innerHTML = '<p class="irwf-step-notification-title"></p><p class="irwf-step-notification-message"></p>';
        document.body.appendChild(notif);
    }
    const title = notif.querySelector('.irwf-step-notification-title');
    const msg = notif.querySelector('.irwf-step-notification-message');
    if (title) title.textContent = type === 'error' ? 'Error' : (type === 'success' ? 'Success' : 'Notice');
    if (msg) msg.textContent = message;
    notif.classList.add('is-visible');
    setTimeout(function() {
        notif.classList.remove('is-visible');
    }, 4000);
}

function chwfScrollToCurrent() {
    const currentEl = document.querySelector('.irwf-flow-step.current');
    if (currentEl) currentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// =============================
// Action Toggles
// =============================
function chwfBindActionToggles() {
    document.querySelectorAll('.irwf-step-actions-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const stepEl = toggle.closest('.irwf-flow-step');
            const panel = toggle.nextElementSibling;
            if (!panel || !panel.classList.contains('irwf-step-actions-panel')) return;
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            if (isExpanded) {
                panel.setAttribute('hidden', '');
            } else {
                panel.removeAttribute('hidden');
            }
        });
    });

    document.addEventListener('click', function(e) {
        const openToggle = document.querySelector('.irwf-step-actions-toggle[aria-expanded="true"]');
        if (!openToggle) return;
        const panel = openToggle.nextElementSibling;
        if (!panel || !panel.classList.contains('irwf-step-actions-panel')) return;
        const stepEl = openToggle.closest('.irwf-flow-step');
        if (!stepEl) return;
        if (panel.contains(e.target) || stepEl.contains(e.target)) return;
        openToggle.setAttribute('aria-expanded', 'false');
        panel.setAttribute('hidden', '');
    });
}

window.chwfToggleStepActions = function(badgeEl, stepKey) {
    if (!badgeEl) return;
    const stepEl = badgeEl.closest('.irwf-flow-step');
    if (!stepEl) return;
    const toggle = stepEl.querySelector('.irwf-step-actions-toggle');
    if (!toggle) return;
    const panel = toggle.nextElementSibling;
    if (!panel || !panel.classList.contains('irwf-step-actions-panel')) return;
    const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
    if (isExpanded) {
        panel.setAttribute('hidden', '');
    } else {
        panel.removeAttribute('hidden');
    }
};

function chwfBindDecisionPoints() {
    document.querySelectorAll('.irwf-flow-step.current .irwf-decision-options').forEach(function(opts) {
        opts.querySelectorAll('button.irwf-decision-opt').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const stepEl = btn.closest('.irwf-flow-step');
                if (!stepEl) return;
                const stepKey = stepEl.getAttribute('data-step-key');
                const label = (btn.textContent || '').trim();
                let action = null;

                if (label.indexOf('Close') !== -1 || label.indexOf('No') !== -1) {
                    action = 'close';
                } else if (label.indexOf('Reopen') !== -1 || label.indexOf('Yes') !== -1 || label.indexOf('Escalate') !== -1) {
                    action = 'advance';
                }

                if (!action) return;
                chwfPersistDecisionTransition(stepEl, action);
            });
        });
    });
}

function chwfPersistDecisionTransition(stepEl, action) {
    const stepKey = stepEl.getAttribute('data-step-key');
    const statusEl = chwfGetActionStatusEl(stepEl);
    chwfSetStatus(statusEl, 'Processing...', '');

    const formData = new FormData();
    formData.append('complaint_id', chwf.complaintId);
    formData.append('action', action);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) {
            chwfSetStatus(statusEl, 'Request failed (' + xhr.status + ').', 'error');
            return;
        }
        try {
            const data = JSON.parse(xhr.responseText);
            if (data.success) {
                chwfSetStatus(statusEl, 'Updated.', 'success');
                chwfPersistStepTransition(stepEl);
                if (data.new_status) {
                    chwfLastKnownStatus = data.new_status;
                }
            } else {
                chwfSetStatus(statusEl, data.message || 'Update failed.', 'error');
            }
        } catch (e) {
            console.error('[CHWF] Invalid JSON from action API. Step:', stepKey, 'Action:', action, 'Response:', xhr.responseText);
            chwfSetStatus(statusEl, 'Invalid server response.', 'error');
        }
    };
    xhr.send(formData);
}

function chwfPersistStepTransition(stepEl) {
    const currentStep = stepEl || chwfGetCurrentStepEl();
    if (!currentStep) return;

    const nextStep = currentStep.nextElementSibling;
    if (nextStep && nextStep.classList.contains('irwf-flow-step')) {
        const actions = currentStep.querySelector('.irwf-step-actions');
        if (actions) actions.style.display = 'none';

        currentStep.classList.remove('current');
        currentStep.classList.add('completed');

        nextStep.classList.add('current');
        nextStep.classList.remove('completed');

        const nextActions = nextStep.querySelector('.irwf-step-actions');
        if (nextActions) nextActions.style.display = '';

        nextStep.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    setTimeout(function() { location.reload(); }, 1000);
}

// =============================
// Confirm Modal
// =============================
const CHWF_ACTION_META = {
    'advance': { title: 'Advance workflow?', description: 'This will move the complaint to the next stage.', confirmLabel: 'Confirm Update', severity: 'primary' },
    'reopen': { title: 'Reopen complaint?', description: 'This will reopen the complaint for further review.', confirmLabel: 'Reopen', severity: 'default' },
    'close': { title: 'Close complaint?', description: 'This will close the complaint permanently.', confirmLabel: 'Close Complaint', severity: 'danger' },
    'for_decision': { title: 'Route to decision?', description: 'This will move the complaint to the decision stage.', confirmLabel: 'For Decision', severity: 'primary' },
    'record_decision': { title: 'Record decision?', description: 'This will record the decision on the complaint.', confirmLabel: 'Record Decision', severity: 'primary' },
    'finalize_decision': { title: 'Finalize decision?', description: 'This will finalize the decision on the complaint.', confirmLabel: 'Finalize Decision', severity: 'primary' },
    'record_response': { title: 'Record response?', description: 'This will record the employee response.', confirmLabel: 'Save Response', severity: 'primary' },
};

function chwfGetActionTransition(action, currentStatus) {
    var transitionMap = {
        'under_initial_review': { advance: 'Under Investigation', close: 'Closed', for_decision: 'For Decision' },
        'under_investigation': { advance: 'NTE Issued', close: 'Closed', for_decision: 'For Decision', reopen: 'Initial Review' },
        'nte_issued': { advance: 'Awaiting Employee Response', close: 'Closed', reopen: 'Under Investigation' },
        'pending_employee_response': { advance: 'For Decision', close: 'Closed', reopen: 'Under Investigation' },
        'for_decision': { advance: 'Dismissed – No Violation', close: 'Closed', reopen: 'Under Investigation' },
        'closed_no_violation': { reopen: 'Under Investigation' },
        'closed_warning_issued': { reopen: 'Under Investigation' },
        'closed_second_written_warning': { reopen: 'Under Investigation' },
        'closed_final_written_warning': { reopen: 'Under Investigation' },
        'closed_suspension': { reopen: 'Under Investigation' },
        'closed_termination_recommended': { reopen: 'Under Investigation', advance: 'Awaiting Termination Employee Response' },
        'termination_employee_reply': { advance: 'Termination Reviewed', close: 'Closed', reopen: 'For Decision' },
        'termination_reviewed': { reopen: 'For Decision' },
        'closed_resolved': { reopen: 'Under Investigation' },
        'closed': { reopen: 'Under Investigation' },
    };
    var map = transitionMap[currentStatus] || {};
    return map[action] || null;
}

function chwfStatusLabel(status) {
    var map = {
        'under_initial_review': 'Initial Review',
        'under_investigation': 'Under Investigation',
        'nte_issued': 'NTE Issued',
        'pending_employee_response': 'Awaiting Employee Response',
        'for_decision': 'For Decision',
        'closed_no_violation': 'Dismissed – No Violation',
        'closed_warning_issued': 'Written Warning Issued',
        'closed_second_written_warning': 'Second Written Warning Issued',
        'closed_final_written_warning': 'Final Written Warning Issued',
        'closed_suspension': 'Suspension Issued',
        'closed_termination_recommended': 'Final Decision – Termination Recommended',
        'termination_employee_reply': 'Awaiting Termination Employee Response',
        'termination_reviewed': 'Termination Reviewed',
        'closed_resolved': 'Resolved',
        'closed': 'Closed',
    };
    return map[status.toLowerCase()] || (status || '').replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
}

function chwfSetConfirmState(state) {
    var submitBtn = document.querySelector('.irwf-confirm-submit');
    if (!submitBtn) return;
    submitBtn.classList.remove('is-loading', 'is-success', 'is-error');
    if (state) {
        submitBtn.classList.add(state);
        submitBtn.disabled = true;
    } else {
        submitBtn.disabled = false;
    }
}

function chwfTrapFocus(modal) {
    var focusable = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (!focusable.length) return;
    var first = focusable[0];
    var last = focusable[focusable.length - 1];

    function handler(e) {
        if (e.key !== 'Tab') return;
        if (e.shiftKey) {
            if (document.activeElement === first) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if (document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    }
    modal.addEventListener('keydown', handler);
    return handler;
}

function chwfSetupConfirmDialog() {
    var modal = document.getElementById('chwfConfirmModal');
    if (!modal) return;
    var backdrop = modal;
    var escapeHandler = function(e) {
        if (e.key === 'Escape') {
            chwfConfirmModal(false);
        }
    };
    backdrop.addEventListener('keydown', escapeHandler);
    var focusable = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (focusable.length) {
        focusable[0].focus();
    }
    window._chwfConfirmEscapeHandler = escapeHandler;
    window._chwfConfirmFocusHandler = chwfTrapFocus(modal);
}

function chwfCleanupConfirmDialog() {
    if (window._chwfConfirmEscapeHandler) {
        var modal = document.getElementById('chwfConfirmModal');
        if (modal) {
            modal.removeEventListener('keydown', window._chwfConfirmEscapeHandler);
        }
        window._chwfConfirmEscapeHandler = null;
    }
    window._chwfConfirmFocusHandler = null;
}

// =============================
// Core Actions
// =============================
window.chSubmitAction = async function(action, btn) {
    const stepBlock = btn ? btn.closest('.irwf-step-actions') : null;
    const statusEl = stepBlock ? stepBlock.querySelector('.irwf-action-status') : document.querySelector('.irwf-flow-step.current .irwf-action-status');
    if (!statusEl) return;

    const stepEl = btn ? btn.closest('.irwf-flow-step') : chwfGetCurrentStepEl();
    const stepKey = stepEl ? stepEl.getAttribute('data-step-key') : chwf.currentStepKey;

    if (btn) btn.disabled = true;

    const meta = CHWF_ACTION_META[action] || {};
    const currentStatus = (window.CHWF_CONFIG && window.CHWF_CONFIG.status ? window.CHWF_CONFIG.status : 'under_initial_review').toLowerCase();
    const nextStatus = chwfGetActionTransition(action, currentStatus);
    const currentLabel = chwfStatusLabel(currentStatus);
    const nextLabel = nextStatus || '';
    const transitionHtml = nextLabel ? '<span class="irwf-confirm-status-current">' + currentLabel + '</span><span class="irwf-confirm-status-arrow">→</span><span class="irwf-confirm-status-next">' + nextLabel + '</span>' : '';
    const confirmed = await chwfConfirm({
        title: meta.title || 'Confirm action',
        description: meta.description || 'This will advance the workflow accordingly.',
        confirmLabel: meta.confirmLabel || 'Confirm',
        severity: meta.severity || 'default',
        transition: transitionHtml,
        triggerBtn: btn,
    });
    if (!confirmed) {
        if (btn) btn.disabled = false;
        return;
    }

    chwfSetConfirmState('loading');
    statusEl.textContent = 'Updating...';

    const formData = new FormData();
    formData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
    formData.append('action', action);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (btn) btn.disabled = false;
        if (xhr.status < 200 || xhr.status >= 300) {
            chwfSetConfirmState('error');
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            chwfSetConfirmState('error');
            statusEl.textContent = 'Invalid server response.';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
            return;
        }
        if (data && data.success) {
            chwfSetConfirmState('success');
            statusEl.textContent = 'Updated.';
            statusEl.className = 'irwf-action-status success';
            chwfPersistStepTransition(stepEl);
            if (data.new_status) {
                chwfLastKnownStatus = data.new_status;
            }
            chwfReloadEvidenceModal();
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 800);
        } else {
            chwfSetConfirmState('error');
            statusEl.textContent = (data && data.message) || 'Update failed.';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
        }
    };
    xhr.onerror = function() {
        if (btn) btn.disabled = false;
        chwfSetConfirmState('error');
        statusEl.textContent = 'Network error.';
        statusEl.className = 'irwf-action-status error';
        setTimeout(function() {
            chwfSetConfirmState(null);
            chwfCloseModal('chwfConfirmModal');
        }, 1200);
    };
    xhr.send(formData);
};

window.chSubmitDecision = async function(targetStatus, btn) {
    const statusEl = document.querySelector('.irwf-flow-step.current .irwf-action-status');
    if (!statusEl) return;
    const decisionLabel = btn ? btn.textContent.trim() : 'Apply decision';
    const confirmed = await chwfConfirm({
        title: decisionLabel + '?',
        description: 'This will update the complaint status and record the decision.',
        confirmLabel: 'Confirm',
        severity: 'primary'
    });
    if (!confirmed) return;

    chwfSetConfirmState('loading');
    statusEl.textContent = 'Recording decision...';
    if (btn) btn.disabled = true;

    const formData = new FormData();
    formData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
    formData.append('action', 'finalize_decision');
    formData.append('target_status', targetStatus);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (btn) btn.disabled = false;
        if (xhr.status < 200 || xhr.status >= 300) {
            chwfSetConfirmState('error');
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            chwfSetConfirmState('error');
            statusEl.textContent = 'Invalid server response.';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
            return;
        }
        if (data && data.success) {
            chwfSetConfirmState('success');
            statusEl.textContent = data.message || 'Decision recorded successfully.';
            statusEl.className = 'irwf-action-status success';
            chwfReloadEvidenceModal();
            if (targetStatus === 'closed_termination_recommended') {
                setTimeout(function() { location.reload(); }, 900);
            } else {
                const complaintId = window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '';
                const employeeId = window.CHWF_CONFIG ? window.CHWF_CONFIG.employeeId : '';
                const redirectUrl = '?page=notification-compose&mode=reply&notification_id=0&complaint_id=' + encodeURIComponent(complaintId) + '&decision=' + encodeURIComponent(targetStatus) + '&employee_id=' + encodeURIComponent(employeeId);
                setTimeout(function() { window.location.href = redirectUrl; }, 900);
            }
        } else {
            chwfSetConfirmState('error');
            statusEl.textContent = data.message || 'Decision failed.';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
        }
    };
    xhr.onerror = function() {
        if (btn) btn.disabled = false;
        chwfSetConfirmState('error');
        statusEl.textContent = 'Network error. Check console for details.';
        statusEl.className = 'irwf-action-status error';
        setTimeout(function() {
            chwfSetConfirmState(null);
            chwfCloseModal('chwfConfirmModal');
        }, 1200);
    };
    xhr.send(formData);
};

window.chSubmitTerminationReview = async function(reviewAction, btn) {
    const statusEl = document.querySelector('.irwf-flow-step.current .irwf-action-status');
    if (!statusEl) return;
    const actionLabel = reviewAction === 'rejected' ? 'Reject' : 'Consider';
    const confirmed = await chwfConfirm({
        title: actionLabel + ' termination?',
        description: reviewAction === 'rejected' ? 'This will return the case to the decision stage.' : 'This will finalize the termination and close the case.',
        confirmLabel: 'Confirm',
        severity: reviewAction === 'rejected' ? 'danger' : 'primary'
    });
    if (!confirmed) return;

    chwfSetConfirmState('loading');
    statusEl.textContent = 'Recording review...';
    if (btn) btn.disabled = true;

    const targetStatus = reviewAction === 'rejected' ? 'for_decision' : 'closed';
    const formData = new FormData();
    formData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
    formData.append('action', 'finalize_decision');
    formData.append('target_status', targetStatus);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (btn) btn.disabled = false;
        if (xhr.status < 200 || xhr.status >= 300) {
            chwfSetConfirmState('error');
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            chwfSetConfirmState('error');
            statusEl.textContent = 'Invalid server response.';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
            return;
        }
        if (data && data.success) {
            chwfSetConfirmState('success');
            statusEl.textContent = data.message || 'Review recorded successfully.';
            statusEl.className = 'irwf-action-status success';
            chwfReloadEvidenceModal();
            setTimeout(function() { location.reload(); }, 900);
        } else {
            chwfSetConfirmState('error');
            statusEl.textContent = data.message || 'Review failed.';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
        }
    };
    xhr.onerror = function() {
        if (btn) btn.disabled = false;
        chwfSetConfirmState('error');
        statusEl.textContent = 'Network error. Check console for details.';
        statusEl.className = 'irwf-action-status error';
        setTimeout(function() {
            chwfSetConfirmState(null);
            chwfCloseModal('chwfConfirmModal');
        }, 1200);
    };
    xhr.send(formData);
};

window.chApplyStatus = async function() {
    const select = document.getElementById('chStatusSelect');
    const statusEl = document.getElementById('chwfActionStatus');
    if (!select || !statusEl) return;

    const newStatus = select.value;
    if (!newStatus) {
        statusEl.textContent = 'Please select a status.';
        statusEl.className = 'irwf-action-status error';
        return;
    }

    const statusLabelMap = {
        'closed_no_violation': 'No Violation',
        'closed_warning_issued': 'Warning Issued',
        'closed_second_written_warning': 'Second Written Warning',
        'closed_final_written_warning': 'Final Written Warning',
        'closed_suspension': 'Suspension',
        'closed_termination_recommended': 'Termination Recommended',
        'closed_resolved': 'Resolved'
    };
    const label = statusLabelMap[newStatus] || newStatus.replace(/_/g, ' ');

    const confirmed = await chwfConfirm({
        title: 'Apply status "' + label + '"?',
        description: 'This will update the case status and record the decision.',
        confirmLabel: 'Apply',
        severity: 'primary'
    });
    if (!confirmed) return;

    statusEl.textContent = 'Updating status...';
    const btn = document.getElementById('chApplyStatusBtn');
    if (btn) btn.disabled = true;

    const formData = new FormData();
    formData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
    formData.append('action', 'finalize_decision');
    formData.append('target_status', newStatus);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (btn) btn.disabled = false;
        if (xhr.status < 200 || xhr.status >= 300) {
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            statusEl.textContent = 'Invalid server response.';
            statusEl.className = 'irwf-action-status error';
            return;
        }
        if (data && data.success) {
            statusEl.textContent = data.message || 'Status updated successfully.';
            statusEl.className = 'irwf-action-status success';
            setTimeout(function() {
                statusEl.textContent = '';
                if (confirm('Case status updated. Would you like to send an email notification for this decision?')) {
                    chShowLetterSelection();
                } else {
                    const applyBtn = document.getElementById('chApplyStatusBtn');
                    if (applyBtn) applyBtn.disabled = false;
                }
            }, 300);
        } else {
            statusEl.textContent = data.message || 'Update failed.';
            statusEl.className = 'irwf-action-status error';
        }
    };
    xhr.onerror = function() {
        if (btn) btn.disabled = false;
        statusEl.textContent = 'Network error.';
        statusEl.className = 'irwf-action-status error';
    };
    xhr.send(formData);
};

window.chShowLetterSelection = function() {
    const wrap = document.getElementById('chLetterSelectionWrap');
    if (wrap) {
        wrap.style.display = 'block';
    }
    const letterSelect = document.getElementById('chLetterSelect');
    if (letterSelect) letterSelect.value = '';
    const letterStatus = document.getElementById('chLetterStatus');
    if (letterStatus) { letterStatus.textContent = ''; letterStatus.className = 'irwf-action-status'; }
    const applyBtn = document.getElementById('chApplyStatusBtn');
    if (applyBtn) applyBtn.disabled = false;
};

window.chSendLetter = async function() {
    const select = document.getElementById('chLetterSelect');
    const statusEl = document.getElementById('chLetterStatus');
    if (!select || !statusEl) return;

    const letterCode = select.value;
    if (!letterCode) {
        statusEl.textContent = 'Please select a letter type.';
        statusEl.className = 'irwf-action-status error';
        return;
    }

    const letterLabels = {
        'written_warning': 'Written Warning',
        'suspension_notice': 'Suspension',
        'termination_decision': 'Termination'
    };
    const label = letterLabels[letterCode] || letterCode;

    const confirmed = await chwfConfirm({
        title: 'Send ' + label + '?',
        description: 'This will record the document request and open the email composer.',
        confirmLabel: 'Send Letter',
        severity: 'primary'
    });
    if (!confirmed) return;

    statusEl.textContent = 'Sending...';
    const btn = document.getElementById('chSendLetterBtn');
    if (btn) btn.disabled = true;

    const formData = new FormData();
    formData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
    formData.append('action', 'save_document_request');
    formData.append('document_type', letterCode);
    formData.append('template_code', letterCode);
    formData.append('hr_signatory', window.CHWF_CONFIG ? (window.CHWF_CONFIG.investigatorName || '') : '');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (btn) btn.disabled = false;
        if (xhr.status < 200 || xhr.status >= 300) {
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            statusEl.textContent = 'Invalid server response.';
            statusEl.className = 'irwf-action-status error';
            return;
        }
        if (data && data.success) {
            statusEl.textContent = data.message || 'Letter sent.';
            statusEl.className = 'irwf-action-status success';
            chwfReloadEvidenceModal();
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            statusEl.textContent = data.message || 'Send failed.';
            statusEl.className = 'irwf-action-status error';
        }
    };
    xhr.onerror = function() {
        if (btn) btn.disabled = false;
        statusEl.textContent = 'Network error.';
        statusEl.className = 'irwf-action-status error';
    };
    xhr.send(formData);
};

// =============================
// Response Form
// =============================
window.chShowResponseForm = function() {
    const card = document.getElementById('chwfResponseCard');
    if (card) card.style.display = 'block';
};

window.chHideResponseForm = function() {
    const card = document.getElementById('chwfResponseCard');
    if (card) card.style.display = 'none';
};

window.chSubmitResponse = async function() {
    const textarea = document.getElementById('chwfResponseText');
    const statusEl = document.getElementById('chwfResponseStatus');
    if (!textarea || !statusEl) return;

    const text = textarea.value.trim();
    if (!text) {
        statusEl.textContent = 'Please enter the employee response.';
        statusEl.className = 'irwf-action-status error';
        return;
    }

    const confirmed = await chwfConfirm({
        title: 'Record employee response?',
        description: 'This will save the response to the case record.',
        confirmLabel: 'Save Response',
        severity: 'primary'
    });
    if (!confirmed) return;

    statusEl.textContent = 'Saving...';
    statusEl.className = 'irwf-action-status';

    const formData = new FormData();
    formData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
    formData.append('action', 'record_response');
    formData.append('employee_response', text);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) {
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            statusEl.textContent = 'Invalid server response.';
            statusEl.className = 'irwf-action-status error';
            return;
        }
        if (data && data.success) {
            statusEl.textContent = data.message || 'Response saved.';
            statusEl.className = 'irwf-action-status success';
            chwfReloadEvidenceModal();
            setTimeout(function() {
                chHideResponseForm();
                location.reload();
            }, 1000);
        } else {
            statusEl.textContent = data.message || 'Save failed.';
            statusEl.className = 'irwf-action-status error';
        }
    };
    xhr.onerror = function() {
        statusEl.textContent = 'Network error.';
        statusEl.className = 'irwf-action-status error';
    };
    xhr.send(formData);
};

// =============================
// Inline Response Form (employee_hearing)
// =============================
window.chShowInlineResponseForm = function() {
    const card = document.getElementById('chwfInlineResponseCard');
    const form = document.getElementById('chwfResponseForm');
    if (card) card.style.display = 'block';
    if (form) form.style.display = 'block';
    const textarea = document.getElementById('chwfInlineResponseText');
    if (textarea) textarea.focus();
};

window.chHideInlineResponseForm = function() {
    const form = document.getElementById('chwfResponseForm');
    if (form) form.style.display = 'none';
};

window.chSubmitInlineResponse = async function() {
    const textarea = document.getElementById('chwfInlineResponseText');
    const statusEl = document.getElementById('chwfInlineResponseStatus');
    if (!textarea || !statusEl) return;

    const text = textarea.value.trim();
    if (!text) {
        statusEl.textContent = 'Please enter the employee response.';
        statusEl.className = 'irwf-action-status error';
        return;
    }

    const confirmed = await chwfConfirm({
        title: 'Record employee response?',
        description: 'This will save the response to the case record.',
        confirmLabel: 'Save Response',
        severity: 'primary'
    });
    if (!confirmed) {
        chwfSetConfirmState(null);
        chwfCloseModal('chwfConfirmModal');
        return;
    }

    chwfSetConfirmState('loading');
    statusEl.textContent = 'Saving...';
    statusEl.className = 'irwf-action-status';

    const formData = new FormData();
    formData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
    formData.append('action', 'record_response');
    formData.append('employee_response', text);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) {
            chwfSetConfirmState('error');
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            chwfSetConfirmState('error');
            statusEl.textContent = 'Invalid server response.';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
            return;
        }
        if (data && data.success) {
            chwfSetConfirmState('success');
            statusEl.textContent = data.message || 'Response saved.';
            statusEl.className = 'irwf-action-status success';
            chwfReloadEvidenceModal();
            chHideInlineResponseForm();
            const card = document.getElementById('chwfInlineResponseCard');
            if (card) {
                const savedBlock = card.querySelector('.irwf-response-saved');
                if (!savedBlock) {
                    const h3 = card.querySelector('h3');
                    const div = document.createElement('div');
                    div.className = 'irwf-response-saved';
                    div.style.cssText = 'margin-bottom:10px; padding:10px; background:#f6f8fb; border:1px solid #e4e8ee;';
                    div.innerHTML = '<div style="font-size:0.75rem; color:#6b7280; margin-bottom:4px;">Recorded on ' + new Date().toLocaleString() + '</div>' +
                                    '<div style="white-space:pre-wrap; word-break:break-word; font-size:0.85rem; color:#1b2430;">' + chwfEscapeHtml(text) + '</div>';
                    if (h3 && h3.nextSibling) {
                        h3.parentNode.insertBefore(div, h3.nextSibling);
                    } else if (h3) {
                        h3.parentNode.appendChild(div);
                    }
                } else {
                    const metaEl = savedBlock.querySelector('div');
                    const msgEl = savedBlock.querySelectorAll('div')[1];
                    if (metaEl) metaEl.textContent = 'Recorded on ' + new Date().toLocaleString();
                    if (msgEl) msgEl.textContent = text;
                }
            }
            const saveBtn = document.querySelector('#chwfInlineResponseCard .irwf-response-form .cc-btn.primary');
            if (saveBtn) saveBtn.textContent = 'Update Response';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
                statusEl.textContent = '';
                statusEl.className = 'irwf-action-status';
            }, 800);
        } else {
            chwfSetConfirmState('error');
            statusEl.textContent = data.message || 'Save failed.';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
        }
    };
    xhr.onerror = function() {
        chwfSetConfirmState('error');
        statusEl.textContent = 'Network error.';
        statusEl.className = 'irwf-action-status error';
        setTimeout(function() {
            chwfSetConfirmState(null);
            chwfCloseModal('chwfConfirmModal');
        }, 1200);
    };
    xhr.send(formData);
};

// =============================
// Letter of Intent (LoI)
// =============================
window.chShowLOIForm = function() {
    const card = document.getElementById('chwfLOICard');
    if (card) card.style.display = 'block';
};

window.chHideLOIForm = function() {
    const card = document.getElementById('chwfLOICard');
    if (card) card.style.display = 'none';
};

window.chSubmitLOI = async function() {
    const textarea = document.getElementById('chwfLOIText');
    const fromInput = document.getElementById('chwfLOIFrom');
    const fileInput = document.getElementById('chwfLOIFile');
    const statusEl = document.getElementById('chwfLOIStatus');
    if (!textarea || !statusEl) return;

    const text = textarea.value.trim();
    const fromText = fromInput ? fromInput.value.trim() : '';
    if (!text) {
        statusEl.textContent = 'Please enter the Letter of Intent content or notes.';
        statusEl.className = 'irwf-action-status error';
        return;
    }

    const confirmed = await chwfConfirm({
        title: 'Record employee response?',
        description: 'This will save the response to the case record.',
        confirmLabel: 'Save Response',
        severity: 'primary'
    });
    if (!confirmed) {
        chwfSetConfirmState(null);
        chwfCloseModal('chwfConfirmModal');
        return;
    }

    chwfSetConfirmState('loading');
    statusEl.textContent = 'Saving...';
    statusEl.className = 'irwf-action-status';

    const formData = new FormData();
    formData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
    formData.append('action', 'submit_termination_loi');
    formData.append('loi_notes', text);
    formData.append('loi_from', fromText);
    if (fileInput && fileInput.files && fileInput.files[0]) {
        formData.append('loi_file', fileInput.files[0]);
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) {
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            statusEl.textContent = 'Invalid server response.';
            statusEl.className = 'irwf-action-status error';
            return;
        }
        if (data && data.success) {
            statusEl.textContent = data.message || 'Letter of Intent recorded.';
            statusEl.className = 'irwf-action-status success';
            setTimeout(function() {
                chHideLOIForm();
                location.reload();
            }, 1200);
        } else {
            statusEl.textContent = data.message || 'Save failed.';
            statusEl.className = 'irwf-action-status error';
        }
    };
    xhr.onerror = function() {
        statusEl.textContent = 'Network error.';
        statusEl.className = 'irwf-action-status error';
    };
    xhr.send(formData);
};

// =============================
// Termination Review Decision
// =============================
window.chShowReconsiderationEmailModal = function() {
    const cfg = window.CHWF_CONFIG || {};
    const caseNumber = cfg.caseNumber || '';
    const respondentEmail = cfg.respondentEmail || '';
    const respondentName = cfg.respondentName || '';
    const loiBody = cfg.terminationReply || '';
    const description = cfg.description || '';

    const toInput = document.getElementById('chwfReconsiderationEmailTo');
    const subjectInput = document.getElementById('chwfReconsiderationEmailSubject');
    const bodyInput = document.getElementById('chwfReconsiderationEmailBody');
    const statusEl = document.getElementById('chwfReconsiderationEmailStatus');
    if (!toInput || !subjectInput || !bodyInput) return;

    toInput.value = respondentEmail + (respondentEmail ? ', ' : '') + 'hr@bestlink.edu.ph';
    subjectInput.value = 'Reconsideration of Termination Recommendation - Case ' + caseNumber;
    bodyInput.value = 'Dear ' + (respondentName || 'Employee') + ',\n\nWe are writing to inform you that your Letter of Intent has been reviewed and considered.\n\nCase Details:\n- Case Number: ' + caseNumber + '\n- Description: ' + description + '\n\nLetter of Intent Content:\n' + loiBody + '\n\nThis email initiates the reconsideration process. Please contact HR for further instructions.\n\nBest regards,\nHuman Resources Management System';

    if (statusEl) {
        statusEl.textContent = '';
        statusEl.className = 'irwf-action-status';
    }
    chwfOpenModal('chwfReconsiderationEmailModal');
};

window.chSendReconsiderationEmail = async function() {
    const toInput = document.getElementById('chwfReconsiderationEmailTo');
    const subjectInput = document.getElementById('chwfReconsiderationEmailSubject');
    const bodyInput = document.getElementById('chwfReconsiderationEmailBody');
    const statusEl = document.getElementById('chwfReconsiderationEmailStatus');
    if (!toInput || !subjectInput || !bodyInput || !statusEl) return;

    const toEmail = toInput.value.trim();
    const emailSubject = subjectInput.value.trim();
    const emailBody = bodyInput.value.trim();
    if (!toEmail || !emailSubject || !emailBody) {
        statusEl.textContent = 'Please fill in all email fields.';
        statusEl.className = 'irwf-action-status error';
        return;
    }

    const confirmed = await chwfConfirm({
        title: 'Send reconsideration email and close case?',
        description: 'This will send the reconsideration email and close the case.',
        confirmLabel: 'Send Email & Close Case',
        severity: 'primary'
    });
    if (!confirmed) return;

    statusEl.textContent = 'Sending email...';
    statusEl.className = 'irwf-action-status';

    const emailFormData = new FormData();
    emailFormData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
    emailFormData.append('action', 'send_reconsideration_email');
    emailFormData.append('to_email', toEmail);
    emailFormData.append('email_subject', emailSubject);
    emailFormData.append('email_body', emailBody);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) {
            chwfSetConfirmState('error');
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            statusEl.textContent = 'Invalid server response.';
            statusEl.className = 'irwf-action-status error';
            return;
        }
        if (data && data.success) {
            statusEl.textContent = 'Email sent. Closing case...';
            statusEl.className = 'irwf-action-status';
            const reviewFormData = new FormData();
            reviewFormData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
            reviewFormData.append('action', 'submit_termination_review_decision');
            reviewFormData.append('review_decision', 'considered');
            reviewFormData.append('review_notes', 'Reconsideration email sent to: ' + toEmail);
            const reviewXhr = new XMLHttpRequest();
            reviewXhr.open('POST', CHWF_API, true);
            reviewXhr.setRequestHeader('Accept', 'application/json');
            reviewXhr.onreadystatechange = function() {
                if (reviewXhr.readyState !== 4) return;
                if (reviewXhr.status < 200 || reviewXhr.status >= 300) {
                    statusEl.textContent = 'Review update failed (' + reviewXhr.status + ').';
                    statusEl.className = 'irwf-action-status error';
                    chwfCloseModal('chwfReconsiderationEmailModal');
                    return;
                }
                let reviewData = null;
                try {
                    reviewData = JSON.parse(reviewXhr.responseText);
                } catch (e) {
                    statusEl.textContent = 'Invalid server response.';
                    statusEl.className = 'irwf-action-status error';
                    chwfCloseModal('chwfReconsiderationEmailModal');
                    return;
                }
                if (reviewData && reviewData.success) {
                    statusEl.textContent = (reviewData.message || 'Case closed successfully.') + ' Email sent.';
                    statusEl.className = 'irwf-action-status success';
                    chwfReloadEvidenceModal();
                    setTimeout(function() {
                        chwfCloseModal('chwfReconsiderationEmailModal');
                        location.reload();
                    }, 1200);
                } else {
                    statusEl.textContent = (reviewData && reviewData.message) || 'Review failed.';
                    statusEl.className = 'irwf-action-status error';
                    chwfCloseModal('chwfReconsiderationEmailModal');
                }
            };
            reviewXhr.onerror = function() {
                statusEl.textContent = 'Network error during review update.';
                statusEl.className = 'irwf-action-status error';
                chwfCloseModal('chwfReconsiderationEmailModal');
            };
            reviewXhr.send(reviewFormData);
        } else {
            statusEl.textContent = (data && data.message) || 'Email send failed.';
            statusEl.className = 'irwf-action-status error';
        }
    };
    xhr.onerror = function() {
        statusEl.textContent = 'Network error.';
        statusEl.className = 'irwf-action-status error';
    };
    xhr.send(emailFormData);
};

window.chShowRejectionWarningModal = function() {
    const statusEl = document.getElementById('chwfRejectionWarningStatus');
    if (statusEl) {
        statusEl.textContent = '';
        statusEl.className = 'irwf-action-status';
    }
    const notesInput = document.getElementById('chwfRejectionNotes');
    if (notesInput) notesInput.value = '';
    chwfOpenModal('chwfRejectionWarningModal');
};

window.chSubmitTerminationReviewDecision = async function(decision) {
    const statusEl = document.querySelector('.irwf-flow-step.current .irwf-action-status');
    const notesInput = document.getElementById('chwfRejectionNotes');
    const reviewNotes = notesInput ? notesInput.value.trim() : '';
    if (!statusEl) return;

    const formData = new FormData();
    formData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
    formData.append('action', 'submit_termination_review_decision');
    formData.append('review_decision', decision);
    formData.append('review_notes', reviewNotes);
    if (decision === 'rejected') {
        formData.append('confirm_rejected', '1');
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) {
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            chwfCloseModal('chwfRejectionWarningModal');
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            statusEl.textContent = 'Invalid server response.';
            statusEl.className = 'irwf-action-status error';
            chwfCloseModal('chwfRejectionWarningModal');
            return;
        }
        if (data && data.success) {
            chwfCloseModal('chwfRejectionWarningModal');
            statusEl.textContent = data.message || 'Review decision recorded.';
            statusEl.className = 'irwf-action-status success';
            chwfReloadEvidenceModal();
            setTimeout(function() { location.reload(); }, 1200);
        } else {
            statusEl.textContent = data.message || 'Review failed.';
            statusEl.className = 'irwf-action-status error';
            if (data.requires_confirmation) {
                statusEl.textContent = data.message || 'Please confirm the rejection.';
            }
            chwfCloseModal('chwfRejectionWarningModal');
        }
    };
    xhr.onerror = function() {
        statusEl.textContent = 'Network error.';
        statusEl.className = 'irwf-action-status error';
        chwfCloseModal('chwfRejectionWarningModal');
    };
    xhr.send(formData);
};

// =============================
// Decision Email & History
// =============================
let chwfDecisionEmailContext = { targetStatus: null, btn: null };

window.chLoadEmployeeDisciplinaryHistory = function() {
    const respondentId = window.CHWF_CONFIG ? (window.CHWF_CONFIG.respondentEmployeeId || window.CHWF_CONFIG.employeeId || '') : '';
    if (!respondentId) return;

    const xhr = new XMLHttpRequest();
    xhr.open('GET', CHWF_EMPLOYEE_HISTORY_API + '?employee_id=' + encodeURIComponent(respondentId), true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) return;
        let data = null;
        try { data = JSON.parse(xhr.responseText); } catch (e) { return; }
        if (data && data.success && Array.isArray(data.history)) {
            chwfUpdateDecisionButtonStates(data.history);
        }
    };
    xhr.onerror = function() {};
    xhr.send();
};

window.chwfUpdateDecisionButtonStates = function(history) {
    const stages = [
        'closed_warning_issued',
        'closed_second_written_warning',
        'closed_final_written_warning',
        'closed_termination_recommended'
    ];

    const currentStatus = (window.CHWF_CONFIG ? (window.CHWF_CONFIG.status || '') : '').toLowerCase();
    const canDecide = currentStatus === 'for_decision';

    const statuses = Array.isArray(history) ? history.map(function(h) { return h.status; }) : [];
    let highestStageIndex = -1;
    for (let i = stages.length - 1; i >= 0; i--) {
        if (statuses.indexOf(stages[i]) !== -1) {
            highestStageIndex = i;
            break;
        }
    }

    const buttons = document.querySelectorAll('.irwf-flow-step.current .cc-btn[onclick*="chSubmitDecision"], .irwf-flow-step.current .cc-btn[data-decision-status]');
    buttons.forEach(function(btn) {
        const onclick = btn.getAttribute('onclick') || '';
        const decisionStatus = btn.getAttribute('data-decision-status') || '';
        let target = decisionStatus;
        if (!target && onclick.indexOf("'closed_warning_issued'") !== -1) target = 'closed_warning_issued';
        else if (!target && onclick.indexOf("'closed_second_written_warning'") !== -1) target = 'closed_second_written_warning';
        else if (!target && onclick.indexOf("'closed_final_written_warning'") !== -1) target = 'closed_final_written_warning';
        else if (!target && onclick.indexOf("'closed_termination_recommended'") !== -1) target = 'closed_termination_recommended';

        if (!target) return;
        const targetIndex = stages.indexOf(target);
        if (targetIndex === -1) return;

        if (!canDecide || highestStageIndex >= targetIndex) {
            btn.disabled = true;
        } else {
            btn.disabled = false;
        }
    });
};

window.chShowDecisionEmailModal = function(targetStatus, btn) {
    if (btn && btn.disabled) return;
    const modal = document.getElementById('chwfDecisionEmailModal');
    if (!modal) return;

    chwfDecisionEmailContext.targetStatus = targetStatus;
    chwfDecisionEmailContext.btn = btn;

    const toInput = document.getElementById('chwfDecisionEmailTo');
    const subjectInput = document.getElementById('chwfDecisionEmailSubject');
    const bodyInput = document.getElementById('chwfDecisionEmailBody');
    const statusEl = document.getElementById('chwfDecisionEmailStatus');

    if (toInput) toInput.value = window.CHWF_CONFIG ? (window.CHWF_CONFIG.respondentEmail || window.CHWF_CONFIG.employeeName || '') : '';
    if (subjectInput) subjectInput.value = chGenerateDecisionEmailSubject(targetStatus);
    if (bodyInput) bodyInput.value = chGenerateDecisionEmailBody(targetStatus);
    if (statusEl) { statusEl.textContent = ''; statusEl.className = 'irwf-action-status'; }

    chwfOpenModal('chwfDecisionEmailModal');
};

window.chGenerateDecisionEmailSubject = function(targetStatus) {
    const employeeName = window.CHWF_CONFIG ? (window.CHWF_CONFIG.respondentName || window.CHWF_CONFIG.employeeName || 'Employee') : 'Employee';
    const caseNumber = window.CHWF_CONFIG ? window.CHWF_CONFIG.caseNumber : '';
    switch (targetStatus) {
        case 'closed_warning_issued': return 'Written Warning Issued - Case ' + caseNumber;
        case 'closed_second_written_warning': return 'Second Written Warning Issued - Case ' + caseNumber;
        case 'closed_final_written_warning': return 'Final Written Warning Issued - Case ' + caseNumber;
        case 'closed_termination_recommended': return 'Termination Recommended - Case ' + caseNumber;
        default: return 'Decision Notification - Case ' + caseNumber;
    }
};

window.chGenerateDecisionEmailBody = function(targetStatus) {
    const employeeName = window.CHWF_CONFIG ? (window.CHWF_CONFIG.respondentName || window.CHWF_CONFIG.employeeName || 'Employee') : 'Employee';
    const caseNumber = window.CHWF_CONFIG ? window.CHWF_CONFIG.caseNumber : '';
    const decisionDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    let decisionText = '';
    let body = '';

    switch (targetStatus) {
        case 'closed_warning_issued':
            decisionText = 'a Written Warning';
            body = 'This is your first written warning. Please be advised that any further violation of company policies may result in more severe disciplinary action, up to and including termination of employment.';
            break;
        case 'closed_second_written_warning':
            decisionText = 'a Second Written Warning';
            body = 'This is your second written warning. You have previously received a written warning. Any further violation of company policies will result in immediate termination of employment.';
            break;
        case 'closed_final_written_warning':
            decisionText = 'a Final Written Warning';
            body = 'This is your final written warning. You have previously received written warnings. As a consequence, you are hereby suspended from work for one (1) week effective immediately. Any further violation of company policies will result in immediate termination of employment without further notice.';
            break;
        case 'closed_termination_recommended':
            decisionText = 'a Termination Recommendation';
            body = 'Based on the findings of the investigation and your disciplinary history, the company has decided to recommend the termination of your employment. You are required to submit a letter of intent regarding this decision within five (5) days from receipt of this notice.';
            break;
        default:
            decisionText = 'a disciplinary decision';
            body = 'Please be advised of the decision rendered in your case.';
    }

    return 'Dear ' + employeeName + ',\n\n' +
        'This is to formally notify you that the management has rendered ' + decisionText + ' in connection with Case ' + caseNumber + ' dated ' + decisionDate + '.\n\n' +
        body + '\n\n' +
        'If you have any questions or concerns, please do not hesitate to contact the Human Resources Department.\n\n' +
        'Best regards,\n' +
        'Human Resources Department';
};

window.chSaveDecision = function(targetStatus) {
    return new Promise(function(resolve, reject) {
        const formData = new FormData();
        formData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
        formData.append('action', 'finalize_decision');
        formData.append('target_status', targetStatus);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', CHWF_API, true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status < 200 || xhr.status >= 300) {
                reject(new Error('Request failed (' + xhr.status + ').'));
                return;
            }
            let data = null;
            try { data = JSON.parse(xhr.responseText); } catch (e) {
                reject(new Error('Invalid server response.'));
                return;
            }
            if (data && data.success) {
                resolve(data);
            } else {
                reject(new Error(data.message || 'Decision failed.'));
            }
        };
        xhr.onerror = function() {
            reject(new Error('Network error.'));
        };
        xhr.send(formData);
    });
};

window.chSendDecisionEmail = async function() {
    const statusEl = document.getElementById('chwfDecisionEmailStatus');
    const targetStatus = chwfDecisionEmailContext.targetStatus;
    const complaintId = window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '';
    const employeeId = window.CHWF_CONFIG ? window.CHWF_CONFIG.employeeId : '';
    const respondentEmail = window.CHWF_CONFIG ? window.CHWF_CONFIG.respondentEmail : '';
    const respondentName = window.CHWF_CONFIG ? window.CHWF_CONFIG.respondentName : '';
    const caseNumber = window.CHWF_CONFIG ? window.CHWF_CONFIG.caseNumber : '';

    if (statusEl) {
        statusEl.textContent = 'Recording decision...';
        statusEl.className = 'irwf-action-status';
    }

    try {
        await chSaveDecision(targetStatus);
    } catch (err) {
        if (statusEl) {
            statusEl.textContent = 'Failed to save decision: ' + (err.message || err);
            statusEl.className = 'irwf-action-status error';
        }
        return;
    }

    if (statusEl) {
        statusEl.textContent = 'Decision saved. Opening email composer...';
        statusEl.className = 'irwf-action-status success';
    }

    let templateCode = 'written_warning';
    let notificationKey = 'warning';
    if (targetStatus === 'closed_termination_recommended') {
        templateCode = 'termination_decision';
        notificationKey = 'termination_decision';
    }

    const params = new URLSearchParams({
        page: 'notification-compose',
        mode: 'reply',
        notification_id: '0',
        complaint_id: complaintId,
        decision: targetStatus,
        employee_id: employeeId,
        to_recipient_email: respondentEmail,
        to_recipient_name: respondentName,
        template_code: templateCode,
        notification_key: notificationKey,
        scenario: 'general',
        subject: chGenerateDecisionEmailSubject(targetStatus),
        body: document.getElementById('chwfDecisionEmailBody').value,
        hr_signatory: window.CHWF_CONFIG ? (window.CHWF_CONFIG.investigatorName || '') : ''
    });

    chwfCloseModal('chwfDecisionEmailModal');
    window.location.href = '?' + params.toString();
};

window.chRecordDecisionOnly = async function() {
    chwfCloseModal('chwfDecisionEmailModal');
    const ctx = chwfDecisionEmailContext;
    if (!ctx || !ctx.targetStatus) return;

    const statusEl = document.querySelector('.irwf-flow-step.current .irwf-action-status');
    if (statusEl) {
        statusEl.textContent = 'Recording decision...';
        statusEl.className = 'irwf-action-status';
    }
    if (ctx.btn) ctx.btn.disabled = true;

    try {
        const data = await chSaveDecision(ctx.targetStatus);
        if (statusEl) {
            statusEl.textContent = data.message || 'Decision recorded successfully.';
            statusEl.className = 'irwf-action-status success';
        }
        chwfReloadEvidenceModal();
        setTimeout(function() { location.reload(); }, 900);
    } catch (err) {
        if (statusEl) {
            statusEl.textContent = 'Failed to save decision: ' + (err.message || err);
            statusEl.className = 'irwf-action-status error';
        }
        if (ctx.btn) ctx.btn.disabled = false;
        setTimeout(function() {
            statusEl.textContent = '';
            statusEl.className = 'irwf-action-status';
        }, 3000);
    }
};

window.chHandleDecision = function(targetStatus, btn) {
    if (btn && btn.disabled) return;
    chShowDecisionEmailModal(targetStatus, btn);
};

// =============================
// Decision History
// =============================
window.cwLoadDecisionHistory = function() {
    function esc(t) {
        if (t == null) return '';
        return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    const listEl = document.getElementById('cwDecisionHistoryList');
    if (!listEl) return;

    const apiUrl = CHWF_DECISION_HISTORY_API + '?complaint_id=' + encodeURIComponent(chwf.complaintId);
    listEl.innerHTML = '<div class="cw-dh-empty"><i class="bi bi-hourglass-split"></i><br>Loading…</div>';

    const xhr = new XMLHttpRequest();
    xhr.open('GET', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) {
            listEl.innerHTML = '<div class="cw-dh-error">Failed to load history (' + xhr.status + ').</div>';
            return;
        }
        try {
            const data = JSON.parse(xhr.responseText);
        } catch (e) {
            listEl.innerHTML = '<div class="cw-dh-error">Invalid server response.</div>';
            return;
        }
        const data = JSON.parse(xhr.responseText);
        if (!data.success || !Array.isArray(data.data) || !data.data.length) {
            listEl.innerHTML = '<div class="cw-dh-empty">No decision records yet.</div>';
            return;
        }

        function dotCls(action, ns) {
            if (action === 'reopen') return 'reopen';
            if (ns === 'closed' || ns === 'closed_no_violation' || ns === 'closed_warning_issued' || ns === 'closed_suspension' || ns === 'closed_termination_recommended' || ns === 'closed_resolved') return 'close';
            return 'status-change';
        }

        function fmtDate(d) {
            if (!d) return '—';
            const dt = new Date(d.replace(' ', 'T'));
            if (isNaN(dt.getTime())) return d;
            return dt.toLocaleString('en-PH', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
        }

        listEl.innerHTML = '<div class="cw-dh-list">' + data.data.map(function(r) {
            const cls = dotCls(r.action, r.new_status);
            let badges = '';
            if (r.old_status) {
                badges += '<span class="cw-dh-badge old">' + esc(r.old_status.replace(/_/g,' ')) + '</span>';
            }
            if (r.new_status) {
                badges += '<span class="cw-dh-arrow">→</span><span class="cw-dh-badge new">' + esc(r.new_status.replace(/_/g,' ')) + '</span>';
            }
            return '<div class="cw-dh-item">'
              + '<div class="cw-dh-dot ' + cls + '"></div>'
              + '<div class="cw-dh-body">'
              + '<div class="cw-dh-label">' + esc(r.decision_label || r.action) + '</div>'
              + '<div class="cw-dh-meta">'
              + '<span><i class="bi bi-person"></i> ' + esc(r.performer_name || 'User #' + r.performed_by) + '</span>'
              + '<span><i class="bi bi-calendar"></i> ' + fmtDate(r.created_at) + '</span>'
              + (badges ? '<span>' + badges + '</span>' : '')
              + '</div>'
              + (r.notes ? '<div style="font-size:0.75rem;color:var(--text-500,#6b7280);margin-top:3px;">' + esc(r.notes) + '</div>' : '')
              + '</div>'
              + '</div>';
        }).join('') + '</div>';
    };
    xhr.onerror = function() {
        listEl.innerHTML = '<div class="cw-dh-error">Network error. Check console for details.</div>';
    };
    xhr.send();
};

// =============================
// Investigator Search & Assign
// =============================
window.chwfInvestigatorSearch = function() {
    const container = document.querySelector('.chwf-investigator-select');
    if (!container) return;
    const searchEl = container.querySelector('.chwf-investigator-search');
    const resultsEl = container.querySelector('.chwf-investigator-results');
    const statusEl = container.querySelector('.chwf-investigator-status');
    const complaintId = parseInt((searchEl ? searchEl.getAttribute('data-complaint-id') : '0') || '0', 10);
    let debounceT = null;

    function buildItem(emp, idx) {
        const name = emp.full_name || 'Employee';
        const initials = name.split(' ').map(function(n){ return n.charAt(0); }).join('').substring(0,2).toUpperCase();
        const dept = emp.department || '';
        const pos = emp.job_title || emp.position_name || '';
        const sub = [dept, pos].filter(Boolean).join(' · ') || (emp.email || '');
        return '<div class="sr-item" role="option" tabindex="0" data-emp-index="' + idx + '" data-employee-id="' + (emp.employee_id || emp.id || '') + '" data-emp-name="' + name.replace(/"/g, '&quot;') + '" style="display:flex; align-items:center; gap:10px; padding:8px 10px; cursor:pointer;">'
          + '<div style="width:28px; height:28px; border-radius:50%; background:rgba(13,27,46,.06); display:inline-flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:700; color:var(--text-600,#5b6472); flex-shrink:0;">' + initials + '</div>'
          + '<div style="flex:1; min-width:0;">'
          + '<div class="sr-item-name" style="font-weight:600; color:var(--text-900,#1b2430); font-size:0.82rem;">' + name + '</div>'
          + '<div style="font-size:0.72rem; color:var(--text-500,#6b7280);">' + (sub || '') + '</div>'
          + '</div>'
          + '</div>';
    }

    function renderResults(items) {
        if (!resultsEl) return;
        if (!items.length) {
            resultsEl.innerHTML = '<div style="padding:10px; font-size:0.82rem; color:var(--text-500,#6b7280);">No employees found.</div>';
            resultsEl.style.display = 'block';
            return;
        }
        resultsEl.innerHTML = items.map(buildItem).join('');
        resultsEl.style.display = 'block';
        resultsEl.querySelectorAll('.sr-item').forEach(function(el) {
            el.addEventListener('click', function() {
                const eid = el.getAttribute('data-employee-id');
                const name = el.getAttribute('data-emp-name') || '';
                if (eid) chwfAssignInvestigator(complaintId, eid, name);
            });
        });
    }

    if (searchEl) {
        searchEl.addEventListener('input', function() {
            const q = (searchEl.value || '').trim();
            if (debounceT) clearTimeout(debounceT);
            if (q.length < 2) { resultsEl.innerHTML = ''; resultsEl.style.display = 'none'; if (statusEl) statusEl.textContent = ''; return; }
            if (statusEl) { statusEl.textContent = 'Searching…'; statusEl.className = 'chwf-investigator-status'; }
            debounceT = setTimeout(function() {
                const apiUrl = CHWF_SEARCH_API + '?q=' + encodeURIComponent(q) + '&department_id=';
                const xhr = new XMLHttpRequest();
                xhr.open('GET', apiUrl, true);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState !== 4) return;
                    if (xhr.status < 200 || xhr.status >= 300) {
                        if (statusEl) { statusEl.textContent = 'Search failed (' + xhr.status + ').'; statusEl.className = 'chwf-investigator-status error'; }
                        return;
                    }
                    try {
                        const data = JSON.parse(xhr.responseText);
                    } catch (e) {
                        if (statusEl) { statusEl.textContent = 'Search failed.'; statusEl.className = 'chwf-investigator-status error'; }
                        return;
                    }
                    const data = JSON.parse(xhr.responseText);
                    const items = (data && data.success && Array.isArray(data.data)) ? data.data : [];
                    renderResults(items);
                    if (statusEl && !items.length) { statusEl.textContent = ''; statusEl.className = 'chwf-investigator-status'; }
                };
                xhr.onerror = function() {
                    if (statusEl) { statusEl.textContent = 'Network error.'; statusEl.className = 'chwf-investigator-status error'; }
                };
                xhr.send();
            }, 250);
        });
    }
};

window.chwfAssignInvestigator = async function(complaintId, employeeId, fullName) {
    const container = document.querySelector('.chwf-investigator-select');
    const statusEl = container ? container.querySelector('.chwf-investigator-status') : document.querySelector('.chwf-investigator-status');
    if (!statusEl) return;

    const confirmed = await chwfConfirm({
        title: 'Assign investigator?',
        description: 'Assign ' + fullName + ' as investigator for this complaint?',
        confirmLabel: 'Assign',
        severity: 'primary'
    });
    if (!confirmed) return;

    statusEl.textContent = 'Assigning…';
    statusEl.className = 'chwf-investigator-status';

    const formData = new FormData();
    formData.append('complaint_id', String(complaintId));
    formData.append('action', 'assign_investigator');
    formData.append('employee_id', String(employeeId));

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
        if (xhr.status < 200 || xhr.status >= 300) {
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'chwf-investigator-status error';
            return;
        }
        try {
            const data = JSON.parse(xhr.responseText);
        } catch (e) {
            statusEl.textContent = 'Invalid server response. Check console.';
            statusEl.className = 'chwf-investigator-status error';
            console.error('Invalid JSON', xhr.responseText);
            return;
        }
        if (data.success) {
            statusEl.textContent = 'Investigator assigned.';
            statusEl.className = 'chwf-investigator-status success';
            chwfReloadEvidenceModal();
            if (container) {
                const wrap = container.querySelector('.chwf-investigator-search-wrap');
                const selected = container.querySelector('.chwf-investigator-selected');
                const nameEl = container.querySelector('.chwf-investigator-selected-name');
                if (wrap) wrap.style.display = 'none';
                if (selected) selected.style.display = 'flex';
                if (nameEl) nameEl.textContent = fullName;
            } else {
                setTimeout(function() { location.reload(); }, 600);
            }
        } else {
            statusEl.textContent = data.message || 'Failed to assign investigator.';
            statusEl.className = 'chwf-investigator-status error';
        }
    };
    xhr.onerror = function() {
        console.error('Assign investigator network error. URL:', CHWF_API);
        statusEl.textContent = 'Network error. Check console for details.';
        statusEl.className = 'chwf-investigator-status error';
    };
    xhr.send(formData);
};

window.chwfAssignFromStep = async function(btn) {
    const stepEl = btn ? btn.closest('.irwf-flow-step') : chwfGetCurrentStepEl();
    const statusEl = stepEl ? stepEl.querySelector('.irwf-action-status') : document.querySelector('.irwf-flow-step.current .irwf-action-status');
    if (!statusEl) return;

    const select = document.getElementById('chwfAssignOfficerSelect');
    if (!select) return;

    const employeeId = select.value;
    if (!employeeId) {
        statusEl.textContent = 'Please select an officer.';
        statusEl.className = 'irwf-action-status error';
        return;
    }

    const option = select.options[select.selectedIndex];
    const fullName = option ? option.textContent : ('Employee #' + employeeId);

    const confirmed = await chwfConfirm({
        title: 'Assign investigator?',
        description: 'Assign ' + fullName + ' as investigator for this complaint?',
        confirmLabel: 'Assign',
        severity: 'primary'
    });
    if (!confirmed) return;

    statusEl.textContent = 'Assigning…';
    statusEl.className = 'irwf-action-status';

    const formData = new FormData();
    formData.append('complaint_id', String(chwf.complaintId));
    formData.append('action', 'assign_investigator');
    formData.append('employee_id', String(employeeId));

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
        if (xhr.status < 200 || xhr.status >= 300) {
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            return;
        }
        try {
            const data = JSON.parse(xhr.responseText);
        } catch (e) {
            statusEl.textContent = 'Invalid server response. Check console.';
            statusEl.className = 'irwf-action-status error';
            console.error('Invalid JSON', xhr.responseText);
            return;
        }
        if (data.success) {
            statusEl.textContent = 'Investigator assigned.';
            statusEl.className = 'irwf-action-status success';
            chwfReloadEvidenceModal();
            if (window.CHWF_CONFIG) {
                window.CHWF_CONFIG.investigatorName = fullName;
                window.CHWF_CONFIG.assignedTo = String(employeeId);
            }
            if (btn) btn.disabled = true;
            if (select) select.disabled = true;
            setTimeout(function() { location.reload(); }, 600);
        } else {
            statusEl.textContent = data.message || 'Failed to assign investigator.';
            statusEl.className = 'irwf-action-status error';
        }
    };
    xhr.onerror = function() {
        console.error('Assign investigator network error. URL:', CHWF_API);
        statusEl.textContent = 'Network error. Check console for details.';
        statusEl.className = 'irwf-action-status error';
    };
    xhr.send(formData);
};

window.chAcceptForReview = async function(btn) {
    const stepBlock = btn ? btn.closest('.irwf-step-actions') : null;
    const statusEl = stepBlock ? stepBlock.querySelector('.irwf-action-status') : document.querySelector('.irwf-flow-step.current .irwf-action-status');
    if (!statusEl) return;

    if (btn) btn.disabled = true;

    const confirmed = await chwfConfirm({
        title: 'Accept complaint for review?',
        description: 'This will formally accept the complaint and advance it to the next stage.',
        confirmLabel: 'Accept for Review',
        severity: 'primary',
        triggerBtn: btn,
    });
    if (!confirmed) {
        if (btn) btn.disabled = false;
        return;
    }

    chwfSetConfirmState('loading');
    statusEl.textContent = 'Accepting...';

    const formData = new FormData();
    formData.append('complaint_id', window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '');
    formData.append('action', 'advance');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (btn) btn.disabled = false;
        if (xhr.status < 200 || xhr.status >= 300) {
            chwfSetConfirmState('error');
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            chwfSetConfirmState('error');
            statusEl.textContent = 'Invalid server response.';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
            return;
        }
        if (data && data.success) {
            chwfSetConfirmState('success');
            statusEl.textContent = 'Accepted for review.';
            statusEl.className = 'irwf-action-status success';
            const stepEl = btn ? btn.closest('.irwf-flow-step') : chwfGetCurrentStepEl();
            chwfPersistStepTransition(stepEl);
            if (data.new_status) {
                chwfLastKnownStatus = data.new_status;
            }
            chwfReloadEvidenceModal();
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 800);
        } else {
            chwfSetConfirmState('error');
            statusEl.textContent = (data && data.message) || 'Update failed.';
            statusEl.className = 'irwf-action-status error';
            setTimeout(function() {
                chwfSetConfirmState(null);
                chwfCloseModal('chwfConfirmModal');
            }, 1200);
        }
    };
    xhr.onerror = function() {
        if (btn) btn.disabled = false;
        chwfSetConfirmState('error');
        statusEl.textContent = 'Network error.';
        statusEl.className = 'irwf-action-status error';
        setTimeout(function() {
            chwfSetConfirmState(null);
            chwfCloseModal('chwfConfirmModal');
        }, 1200);
    };
    xhr.send(formData);
};

window.chwfClearInvestigator = async function(complaintId) {
    const container = document.querySelector('.chwf-investigator-select');
    const statusEl = container ? container.querySelector('.chwf-investigator-status') : document.querySelector('.chwf-investigator-status');
    if (!statusEl) return;

    const confirmed = await chwfConfirm({
        title: 'Remove investigator?',
        description: 'This will remove the assigned investigator from this complaint.',
        confirmLabel: 'Remove',
        severity: 'default'
    });
    if (!confirmed) return;

    statusEl.textContent = 'Removing…';
    statusEl.className = 'chwf-investigator-status';

    const formData = new FormData();
    formData.append('complaint_id', String(complaintId));
    formData.append('action', 'clear_investigator');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
        if (xhr.status < 200 || xhr.status >= 300) {
            statusEl.textContent = 'Request failed (' + xhr.status + ').';
            statusEl.className = 'chwf-investigator-status error';
            return;
        }
        try {
            const data = JSON.parse(xhr.responseText);
        } catch (e) {
            statusEl.textContent = 'Invalid server response. Check console.';
            statusEl.className = 'chwf-investigator-status error';
            console.error('Invalid JSON', xhr.responseText);
            return;
        }
        if (data.success) {
            statusEl.textContent = 'Investigator removed.';
            statusEl.className = 'chwf-investigator-status success';
            if (container) {
                const wrap = container.querySelector('.chwf-investigator-search-wrap');
                const selected = container.querySelector('.chwf-investigator-selected');
                if (wrap) wrap.style.display = 'block';
                if (selected) selected.style.display = 'none';
                const searchInput = container.querySelector('.chwf-investigator-search');
                if (searchInput) searchInput.value = '';
                const results = container.querySelector('.chwf-investigator-results');
                if (results) { results.innerHTML = ''; results.style.display = 'none'; }
            } else {
                setTimeout(function() { location.reload(); }, 600);
            }
        } else {
            statusEl.textContent = data.message || 'Failed to remove investigator.';
            statusEl.className = 'chwf-investigator-status error';
        }
    };
    xhr.onerror = function() {
        console.error('Clear investigator network error. URL:', CHWF_API);
        statusEl.textContent = 'Network error. Check console for details.';
        statusEl.className = 'chwf-investigator-status error';
    };
    xhr.send(formData);
};

// =============================
// Officer Search & Assign
// =============================
window.chwfBindOfficerSearch = function() {
    document.querySelectorAll('.chwf-officer-search').forEach(function(container) {
        const searchInput = container.querySelector('.chwf-officer-search-input');
        const resultsEl = container.querySelector('.chwf-officer-results');
        const statusEl = container.querySelector('.chwf-officer-status');
        if (!searchInput || !resultsEl) return;

        let debounceT = null;

        function renderItems(items) {
            if (!resultsEl) return;
            if (!items.length) {
                resultsEl.innerHTML = '<div style="padding:10px; font-size:0.82rem; color:var(--text-500,#6b7280);">No employees found.</div>';
                resultsEl.style.display = 'block';
                return;
            }
            resultsEl.innerHTML = items.map(function(emp) {
                const name = emp.full_name || 'Employee';
                const empId = emp.employee_no || emp.employee_code || ('#' + (emp.employee_id || ''));
                const pos = emp.job_title || emp.position_name || '';
                const dept = emp.department || '';
                const details = [pos, dept].filter(Boolean).join(' · ') || (emp.email || '');
                return '<div class="chwf-officer-result-item" role="option" tabindex="0" data-employee-id="' + (emp.employee_id || '') + '" data-employee-name="' + name.replace(/"/g, '&quot;') + '">'
                    + '<div class="chwf-result-name">' + name + '</div>'
                    + '<div class="chwf-result-id">' + empId + '</div>'
                    + '<div class="chwf-result-details">' + details + '</div>'
                    + '</div>';
            }).join('');
            resultsEl.style.display = 'block';

            resultsEl.querySelectorAll('.chwf-officer-result-item').forEach(function(el) {
                el.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const eid = el.getAttribute('data-employee-id');
                    const name = el.getAttribute('data-employee-name') || '';
                    container.setAttribute('data-selected-id', eid || '');
                    container.setAttribute('data-selected-name', name || '');
                    resultsEl.querySelectorAll('.chwf-officer-result-item').forEach(function(i) { i.classList.remove('is-selected'); });
                    el.classList.add('is-selected');
                    resultsEl.style.display = 'none';
                    if (statusEl) {
                        statusEl.textContent = 'Selected: ' + (name || 'Employee #' + eid);
                        statusEl.className = 'chwf-officer-status';
                    }
                    if (eid) {
                        chwfAssignSelectedOfficer(null);
                    }
                });
                el.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        el.click();
                    }
                });
            });
        }

        if (searchInput) {
            searchInput.addEventListener('focus', function() {
                const q = (searchInput.value || '').trim();
                if (q.length >= 2 && resultsEl.innerHTML) {
                    resultsEl.style.display = 'block';
                }
            });
            searchInput.addEventListener('input', function() {
                const q = (searchInput.value || '').trim();
                if (debounceT) clearTimeout(debounceT);
                if (q.length < 2) {
                    resultsEl.innerHTML = '';
                    resultsEl.style.display = 'none';
                    if (statusEl) { statusEl.textContent = ''; statusEl.className = 'chwf-officer-status'; }
                    container.setAttribute('data-selected-id', '');
                    container.setAttribute('data-selected-name', '');
                    return;
                }
                if (statusEl) { statusEl.textContent = 'Searching…'; statusEl.className = 'chwf-officer-status'; }
                debounceT = setTimeout(function() {
                    const xhr = new XMLHttpRequest();
                    xhr.open('GET', './lib/api/search_hr_employees.php?q=' + encodeURIComponent(q) + '&department_id=', true);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState !== 4) return;
                        if (xhr.status < 200 || xhr.status >= 300) {
                            if (statusEl) { statusEl.textContent = 'Search failed (' + xhr.status + ').'; statusEl.className = 'chwf-officer-status error'; }
                            return;
                        }
                        let data = null;
                        try {
                            data = JSON.parse(xhr.responseText);
                        } catch (e) {
                            if (statusEl) { statusEl.textContent = 'Search failed.'; statusEl.className = 'chwf-officer-status error'; }
                            return;
                        }
                        const items = (data && data.success && Array.isArray(data.data)) ? data.data : [];
                        renderItems(items);
                        if (statusEl && !items.length) { statusEl.textContent = ''; statusEl.className = 'chwf-officer-status'; }
                    };
                    xhr.onerror = function() {
                        if (statusEl) { statusEl.textContent = 'Network error.'; statusEl.className = 'chwf-officer-status error'; }
                    };
                    xhr.send();
                }, 250);
            });
        }

        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                resultsEl.style.display = 'none';
            }
        });
    });
};

window.chwfAssignSelectedOfficer = async function(btn) {
    const stepEl = btn ? btn.closest('.irwf-flow-step') : chwfGetCurrentStepEl();
    const container = stepEl ? stepEl.querySelector('.chwf-officer-search') : document.querySelector('.chwf-officer-search');
    if (!container) return;

    const statusEl = container.querySelector('.chwf-officer-status');
    const stepStatusEl = stepEl ? stepEl.querySelector('.irwf-action-status') : document.querySelector('.irwf-flow-step.current .irwf-action-status');

    const employeeId = container.getAttribute('data-selected-id');
    const employeeName = container.getAttribute('data-selected-name') || ('Employee #' + employeeId);

    if (!employeeId) {
        if (statusEl) { statusEl.textContent = 'Please select an employee.'; statusEl.className = 'chwf-officer-status error'; }
        return;
    }

    if (btn) btn.disabled = true;

    let confirmed = false;
    if (btn) {
        confirmed = await chwfConfirm({
            title: 'Assign officer?',
            description: 'Assign ' + employeeName + ' as compliance officer for this complaint?',
            confirmLabel: 'Assign',
            severity: 'primary',
            triggerBtn: btn,
        });
        if (!confirmed) {
            if (btn) btn.disabled = false;
            return;
        }
    } else {
        confirmed = true;
    }

    chwfSetConfirmState('loading');
    if (statusEl) { statusEl.textContent = 'Assigning…'; statusEl.className = 'chwf-officer-status'; }
    if (stepStatusEl) { stepStatusEl.textContent = 'Assigning…'; stepStatusEl.className = 'irwf-action-status'; }

    const formData = new FormData();
    formData.append('complaint_id', String(chwf.complaintId));
    formData.append('action', 'assign_investigator');
    formData.append('employee_id', String(employeeId));

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CHWF_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (btn) btn.disabled = false;
        if (xhr.status < 200 || xhr.status >= 300) {
            chwfSetConfirmState('error');
            if (statusEl) { statusEl.textContent = 'Request failed (' + xhr.status + ').'; statusEl.className = 'chwf-officer-status error'; }
            if (stepStatusEl) { stepStatusEl.textContent = 'Request failed (' + xhr.status + ').'; stepStatusEl.className = 'irwf-action-status error'; }
            setTimeout(function() { chwfSetConfirmState(null); chwfCloseModal('chwfConfirmModal'); }, 1200);
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            chwfSetConfirmState('error');
            if (statusEl) { statusEl.textContent = 'Invalid server response.'; statusEl.className = 'chwf-officer-status error'; }
            if (stepStatusEl) { stepStatusEl.textContent = 'Invalid server response.'; statusEl.className = 'irwf-action-status error'; }
            setTimeout(function() { chwfSetConfirmState(null); chwfCloseModal('chwfConfirmModal'); }, 1200);
            return;
        }
        if (data && data.success) {
            chwfSetConfirmState('success');
            if (statusEl) { statusEl.textContent = 'Officer assigned.'; statusEl.className = 'chwf-officer-status success'; }
            if (stepStatusEl) { stepStatusEl.textContent = 'Officer assigned.'; stepStatusEl.className = 'irwf-action-status success'; }
            chwfReloadEvidenceModal();
            setTimeout(function() { location.reload(); }, 800);
        } else {
            chwfSetConfirmState('error');
            const msg = (data && data.message) || 'Assignment failed.';
            if (statusEl) { statusEl.textContent = msg; statusEl.className = 'chwf-officer-status error'; }
            if (stepStatusEl) { stepStatusEl.textContent = msg; stepStatusEl.className = 'irwf-action-status error'; }
            setTimeout(function() { chwfSetConfirmState(null); chwfCloseModal('chwfConfirmModal'); }, 1200);
        }
    };
    xhr.onerror = function() {
        if (btn) btn.disabled = false;
        chwfSetConfirmState('error');
        if (statusEl) { statusEl.textContent = 'Network error.'; statusEl.className = 'chwf-officer-status error'; }
        if (stepStatusEl) { stepStatusEl.textContent = 'Network error.'; stepStatusEl.className = 'irwf-action-status error'; }
        setTimeout(function() { chwfSetConfirmState(null); chwfCloseModal('chwfConfirmModal'); }, 1200);
    };
    xhr.send(formData);
};

function chwfIsImage(name) {
    if (!name) return false;
    return /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(name);
}
function chwfEscapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
function chwfResolveEvidenceSrc(evidenceItem, notes, imagePath) {
    const assetBase = (window.CHWF_CONFIG && window.CHWF_CONFIG.assetBaseUrl) ? window.CHWF_CONFIG.assetBaseUrl : '/hrms-capstone/modules/compliance/assets/';
    if (imagePath) {
        const trimmed = imagePath.trim();
        if (/^https?:\/\//i.test(trimmed)) {
            return trimmed;
        }
        if (/^\.?\//.test(trimmed)) {
            return trimmed;
        }
        if (/^modules\/compliance\/assets\//i.test(trimmed)) {
            return assetBase + trimmed.slice('modules/compliance/assets/'.length);
        }
        if (/^modules\/compliance\//i.test(trimmed)) {
            return assetBase + trimmed.slice('modules/compliance/'.length);
        }
        if (/^[a-zA-Z]:\\|^\//.test(trimmed)) {
            const webPath = chwfFilesystemToWeb(trimmed);
            if (webPath) {
                if (/^modules\/compliance\/assets\//i.test(webPath)) {
                    return assetBase + webPath.slice('modules/compliance/assets/'.length);
                }
                if (/^modules\/compliance\//i.test(webPath)) {
                    return assetBase + webPath.slice('modules/compliance/'.length);
                }
                return webPath;
            }
        }
        if (chwfIsImage(trimmed)) {
            return assetBase + trimmed;
        }
    }
    if (notes) {
        const match = notes.match(/(modules\/compliance\/assets\/[^\s"']+)/);
        if (match) {
            return assetBase + match[1].slice('modules/compliance/assets/'.length);
        }
    }
    if (evidenceItem && /^\.?\//.test(evidenceItem)) {
        return evidenceItem;
    }
    if (evidenceItem && /^modules\/compliance\//i.test(evidenceItem)) {
        return assetBase + evidenceItem.slice('modules/compliance/'.length);
    }
    if (evidenceItem && chwfIsImage(evidenceItem)) {
        return assetBase + evidenceItem;
    }
    return null;
}
function chwfFilesystemToWeb(fsPath) {
    if (!fsPath) return null;
    let webPath = fsPath.replace(/\\/g, '/');
    const serverRoot = 'C:/xampp/htdocs/hrms-capstone/';
    if (webPath.toLowerCase().startsWith(serverRoot.toLowerCase())) {
        webPath = webPath.slice(serverRoot.length);
    }
    if (!webPath) return null;
    if (/^modules\/compliance\/assets\//i.test(webPath)) {
        const assetBase = 'modules/compliance/assets/';
        return assetBase + webPath.slice('modules/compliance/assets/'.length);
    }
    if (/^modules\/compliance\//i.test(webPath)) {
        return '../' + webPath.slice('modules/compliance/'.length);
    }
    return webPath;
}

const CHWF_EVIDENCE_STEP_ALIASES = {
    'evidence_check': 'under_initial_review',
};

window.chwfViewEvidenceByStep = function(stepKey) {
    const statusEl = document.getElementById('chwfEvidenceStatus');
    const bodyEl = document.getElementById('chwfEvidenceBody');
    if (!bodyEl) return;

    const apiStepKey = CHWF_EVIDENCE_STEP_ALIASES[stepKey] || stepKey;

    if (statusEl) {
        statusEl.textContent = '';
        statusEl.className = 'irwf-action-status';
    }
    bodyEl.innerHTML = '<div class="irwf-evidence-loading">Loading...</div>';
    chwfOpenModal('chwfEvidenceModal');

    const complaintId = window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '';
    const url = './lib/api/complaint_workflow_evidence.php?complaint_id=' + encodeURIComponent(complaintId) + '&workflow_step_key=' + encodeURIComponent(apiStepKey || '');

    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (statusEl) {
            statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
        }
        if (xhr.status < 200 || xhr.status >= 300) {
            if (statusEl) statusEl.className = 'irwf-action-status error';
            bodyEl.innerHTML = '<div class="irwf-evidence-empty">Failed to load evidence.</div>';
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            if (statusEl) {
                statusEl.textContent = 'Invalid server response.';
                statusEl.className = 'irwf-action-status error';
            }
            bodyEl.innerHTML = '<div class="irwf-evidence-empty">Invalid server response.</div>';
            return;
        }
        if (data && data.success && data.evidence && data.evidence.length > 0) {
            let html = '<div class="irwf-evidence-list">';
            for (let i = 0; i < data.evidence.length; i++) {
                const ev = data.evidence[i];
                html += '<div class="irwf-evidence-item">';
                html += '<div class="irwf-evidence-details">';
                html += '<div class="irwf-evidence-name">' + chwfEsc(ev.evidence_item) + '</div>';
                if (ev.notes) html += '<div class="irwf-evidence-desc">' + chwfEsc(ev.notes) + '</div>';
                html += '<div class="irwf-evidence-meta">';
                html += 'Status: ' + chwfEsc(ev.status || 'Pending');
                html += ' &middot; Required: ' + (ev.required ? 'Yes' : 'No');
                if (ev.uploaded_at) html += ' &middot; Uploaded ' + chwfEsc(ev.uploaded_at);
                html += '</div>';
                const imgSrc = chwfResolveEvidenceSrc(ev.evidence_item, ev.notes, ev.image_path);
                if (imgSrc) {
                    html += '<div style="margin-top:8px;"><img src="' + chwfEsc(imgSrc) + '" style="max-width:100%; max-height:240px; border:1px solid #e4e8ee; background:#f3f5f9;" onerror="this.style.display=\'none\'" /></div>';
                }
                html += '</div></div>';
            }
            html += '</div>';
            bodyEl.innerHTML = html;
            if (statusEl) {
                statusEl.textContent = '';
                statusEl.className = 'irwf-action-status';
            }
        } else {
            if (statusEl) {
                statusEl.textContent = '';
                statusEl.className = 'irwf-action-status';
            }
            bodyEl.innerHTML = '<div class="irwf-evidence-empty">No evidence uploaded for this step.</div>';
        }
    };
    xhr.onerror = function() {
        if (statusEl) {
            statusEl.textContent = 'Network error.';
            statusEl.className = 'irwf-action-status error';
        }
        bodyEl.innerHTML = '<div class="irwf-evidence-empty">Network error.</div>';
    };
    xhr.send();
};

window.chwfViewEvidence = function(btn) {
    const statusEl = document.getElementById('chwfEvidenceStatus');
    const bodyEl = document.getElementById('chwfEvidenceBody');
    if (!bodyEl) return;

    const stepEl = btn ? btn.closest('.irwf-flow-step') : chwfGetCurrentStepEl();
    const stepKey = stepEl ? stepEl.getAttribute('data-step-key') : (window.CHWF_CONFIG ? window.CHWF_CONFIG.currentStepKey : '');
    const apiStepKey = CHWF_EVIDENCE_STEP_ALIASES[stepKey] || stepKey;

    if (statusEl) {
        statusEl.textContent = '';
        statusEl.className = 'irwf-action-status';
    }
    bodyEl.innerHTML = '<div class="irwf-evidence-loading">Loading...</div>';
    chwfOpenModal('chwfEvidenceModal');

    const complaintId = window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '';
    const url = './lib/api/complaint_workflow_evidence.php?complaint_id=' + encodeURIComponent(complaintId) + '&workflow_step_key=' + encodeURIComponent(apiStepKey);

    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (statusEl) {
            statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
        }
        if (xhr.status < 200 || xhr.status >= 300) {
            if (statusEl) statusEl.className = 'irwf-action-status error';
            bodyEl.innerHTML = '<div class="irwf-evidence-empty">Failed to load evidence.</div>';
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            if (statusEl) {
                statusEl.textContent = 'Invalid server response.';
                statusEl.className = 'irwf-action-status error';
            }
            bodyEl.innerHTML = '<div class="irwf-evidence-empty">Invalid server response.</div>';
            return;
        }
        if (data && data.success && data.evidence && data.evidence.length > 0) {
            let html = '<div class="irwf-evidence-list">';
            for (let i = 0; i < data.evidence.length; i++) {
                const ev = data.evidence[i];
                html += '<div class="irwf-evidence-item">';
                html += '<div class="irwf-evidence-details">';
                html += '<div class="irwf-evidence-name">' + chwfEsc(ev.evidence_item) + '</div>';
                if (ev.notes) html += '<div class="irwf-evidence-desc">' + chwfEsc(ev.notes) + '</div>';
                html += '<div class="irwf-evidence-meta">';
                html += 'Status: ' + chwfEsc(ev.status || 'Pending');
                html += ' &middot; Required: ' + (ev.required ? 'Yes' : 'No');
                if (ev.uploaded_at) html += ' &middot; Uploaded ' + chwfEsc(ev.uploaded_at);
                html += '</div>';
                const imgSrc = chwfResolveEvidenceSrc(ev.evidence_item, ev.notes, ev.image_path);
                if (imgSrc) {
                    html += '<div style="margin-top:8px;"><img src="' + chwfEsc(imgSrc) + '" style="max-width:100%; max-height:240px; border:1px solid #e4e8ee; background:#f3f5f9;" onerror="this.style.display=\'none\'" /></div>';
                }
                html += '</div></div>';
            }
            html += '</div>';
            bodyEl.innerHTML = html;
            if (statusEl) {
                statusEl.textContent = '';
                statusEl.className = 'irwf-action-status';
            }
        } else {
            if (statusEl) {
                statusEl.textContent = '';
                statusEl.className = 'irwf-action-status';
            }
            bodyEl.innerHTML = '<div class="irwf-evidence-empty">No evidence uploaded for this step.</div>';
        }
    };
    xhr.onerror = function() {
        if (statusEl) {
            statusEl.textContent = 'Network error.';
            statusEl.className = 'irwf-action-status error';
        }
        bodyEl.innerHTML = '<div class="irwf-evidence-empty">Network error.</div>';
    };
    xhr.send();
};

window.chwfReloadEvidenceModal = function() {
    const modal = document.getElementById('chwfEvidenceModal');
    const bodyEl = document.getElementById('chwfEvidenceBody');
    const statusEl = document.getElementById('chwfEvidenceStatus');
    if (!modal || !bodyEl) return;
    if (!modal.classList.contains('open')) return;

    const stepEl = chwfGetCurrentStepEl();
    const stepKey = stepEl ? stepEl.getAttribute('data-step-key') : (window.CHWF_CONFIG ? window.CHWF_CONFIG.currentStepKey : '');
    const apiStepKey = CHWF_EVIDENCE_STEP_ALIASES[stepKey] || stepKey;

    if (statusEl) {
        statusEl.textContent = '';
        statusEl.className = 'irwf-action-status';
    }
    bodyEl.innerHTML = '<div class="irwf-evidence-loading">Loading...</div>';

    const complaintId = window.CHWF_CONFIG ? window.CHWF_CONFIG.complaintId : '';
    const url = './lib/api/complaint_workflow_evidence.php?complaint_id=' + encodeURIComponent(complaintId) + '&workflow_step_key=' + encodeURIComponent(apiStepKey);

    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (statusEl) {
            statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
        }
        if (xhr.status < 200 || xhr.status >= 300) {
            if (statusEl) statusEl.className = 'irwf-action-status error';
            bodyEl.innerHTML = '<div class="irwf-evidence-empty">Failed to load evidence.</div>';
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            if (statusEl) {
                statusEl.textContent = 'Invalid server response.';
                statusEl.className = 'irwf-action-status error';
            }
            bodyEl.innerHTML = '<div class="irwf-evidence-empty">Invalid server response.</div>';
            return;
        }
        if (data && data.success && data.evidence && data.evidence.length > 0) {
            let html = '<div class="irwf-evidence-list">';
            for (let i = 0; i < data.evidence.length; i++) {
                const ev = data.evidence[i];
                html += '<div class="irwf-evidence-item">';
                html += '<div class="irwf-evidence-details">';
                html += '<div class="irwf-evidence-name">' + chwfEsc(ev.evidence_item) + '</div>';
                if (ev.notes) html += '<div class="irwf-evidence-desc">' + chwfEsc(ev.notes) + '</div>';
                html += '<div class="irwf-evidence-meta">';
                html += 'Status: ' + chwfEsc(ev.status || 'Pending');
                html += ' &middot; Required: ' + (ev.required ? 'Yes' : 'No');
                if (ev.uploaded_at) html += ' &middot; Uploaded ' + chwfEsc(ev.uploaded_at);
                html += '</div>';
                const imgSrc = chwfResolveEvidenceSrc(ev.evidence_item, ev.notes, ev.image_path);
                if (imgSrc) {
                    html += '<div style="margin-top:8px;"><img src="' + chwfEsc(imgSrc) + '" style="max-width:100%; max-height:240px; border:1px solid #e4e8ee; background:#f3f5f9;" onerror="this.style.display=\'none\'" /></div>';
                }
                html += '</div></div>';
            }
            html += '</div>';
            bodyEl.innerHTML = html;
            if (statusEl) {
                statusEl.textContent = '';
                statusEl.className = 'irwf-action-status';
            }
        } else {
            if (statusEl) {
                statusEl.textContent = '';
                statusEl.className = 'irwf-action-status';
            }
            bodyEl.innerHTML = '<div class="irwf-evidence-empty">No evidence uploaded for this step.</div>';
        }
    };
    xhr.onerror = function() {
        if (statusEl) {
            statusEl.textContent = 'Network error.';
            statusEl.className = 'irwf-action-status error';
        }
        bodyEl.innerHTML = '<div class="irwf-evidence-empty">Network error.</div>';
    };
    xhr.send();
};

// =============================
// Modal Helpers
// =============================
window.chwfCloseModal = function(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
    chwfCleanupConfirmDialog();
};

window.chwfOpenModal = function(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
};

window.chwfConfirm = function(messageOrOptions) {
    return new Promise(function(resolve) {
        const modal = document.getElementById('chwfConfirmModal');
        const titleEl = document.getElementById('chwfConfirmTitle');
        const descEl = document.getElementById('chwfConfirmDesc');
        const transitionEl = document.getElementById('chwfConfirmTransition');
        const submitBtn = document.querySelector('.irwf-confirm-submit');

        if (!modal || !titleEl || !descEl) {
            var msg = typeof messageOrOptions === 'string' ? messageOrOptions : (messageOrOptions && messageOrOptions.message) || 'Are you sure?';
            resolve(window.confirm(msg));
            return;
        }

        var options = {};
        if (typeof messageOrOptions === 'string') {
            options = { message: messageOrOptions };
        } else {
            options = messageOrOptions || {};
        }

        titleEl.textContent = options.title || 'Confirm Action';
        descEl.textContent = options.message || options.description || 'Are you sure?';

        if (transitionEl) {
            var transition = options.transition || '';
            if (transition) {
                transitionEl.innerHTML = transition;
                transitionEl.style.display = '';
            } else {
                transitionEl.style.display = 'none';
            }
        }

        if (submitBtn) {
            submitBtn.textContent = options.confirmLabel || 'Confirm';
            submitBtn.className = 'cc-btn primary irwf-confirm-submit' + (options.severity === 'danger' ? ' danger' : '');
        }

        window._chwfConfirmResolve = resolve;
        window._chwfConfirmTriggerBtn = options.triggerBtn || null;
        chwfSetConfirmState(null);
        chwfOpenModal('chwfConfirmModal');
        chwfSetupConfirmDialog();
    });
};

window.chwfConfirmModal = function(result) {
    if (result) {
        chwfSetConfirmState('loading');
        chwfCleanupConfirmDialog();
        if (typeof window._chwfConfirmResolve === 'function') {
            const resolve = window._chwfConfirmResolve;
            window._chwfConfirmResolve = null;
            resolve(result);
        }
    } else {
        chwfCloseModal('chwfConfirmModal');
        chwfCleanupConfirmDialog();
        if (typeof window._chwfConfirmResolve === 'function') {
            const resolve = window._chwfConfirmResolve;
            window._chwfConfirmResolve = null;
            resolve(result);
        }
        var triggerBtn = window._chwfConfirmTriggerBtn;
        window._chwfConfirmTriggerBtn = null;
        if (triggerBtn && typeof triggerBtn.focus === 'function') {
            triggerBtn.focus();
        }
    }
};

// =============================
// Boot
// =============================
if (window.CHWF_CONFIG && window.CHWF_CONFIG.status) {
    chwfLastKnownStatus = window.CHWF_CONFIG.status;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        chwfInit();
        chwfBindDecisionPoints();
        chLoadEmployeeDisciplinaryHistory();
    });
} else {
    chwfInit();
    chwfBindDecisionPoints();
    chLoadEmployeeDisciplinaryHistory();
}
