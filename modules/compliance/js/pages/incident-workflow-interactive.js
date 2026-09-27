// =============================================================================
// Incident Reporting Workflow — Interactive Decision Engine
// Builds a question-driven, branching interview around the existing irwf-* UI.
// =============================================================================

const IRWF_API = './lib/api/incident_workflow_questions.php';
const IRWF_ACTION_API = './lib/api/incident_workflow_action.php';
const IRWF_EVIDENCE_API = './lib/api/incident_evidence.php';
const IRWF_WITNESS_API = './lib/api/incident_witness_statement.php';
const IRWF_STATUS_API = './lib/api/incident_workflow_status.php';

// =============================
// State
// =============================
let irwf = {
    incidentId: 0,
    currentStepKey: null,
    currentStepIndex: 0,
    currentQuestionId: null,
    workflowSteps: [],
    answers: {},            // questionId -> { value, text, answeredAt, skipped, skipReason }
    stepStatus: {},         // stepKey -> 'pending' | 'in_progress' | 'completed' | 'reopened'
    decisions: {},          // stepKey -> { decision, reason, decidedAt }
    evidence: {},           // stepKey -> { item: status }
    reopenHistory: [],      // [{ stepKey, reason, timestamp }]
    transitionHistory: [],  // [{ fromStep, toStep, action, reason, userId, userName, timestamp }]
    userInfo: { name: 'Compliance Officer', id: 1 },
    saveTimer: null,
    dirty: false,
    branching: {}           // dynamic branch overrides
};

// =============================
// Workflow Stage Definitions
// =============================
const IRWF_WORKFLOW_STAGES = {
    incident_occurs: {
        key: 'incident_occurs',
        name: 'Incident Occurs',
        description: 'Record the initial incident details and immediate response.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Incident Photos', required: false },
            { item: 'Initial Medical Report', required: false },
            { item: 'Damage Assessment', required: false }
        ],
        decisions: [
            { name: 'Advance to Legal Receipt', reason: 'Initial incident details recorded.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'legal_receives', reason: 'Proceed to legal receipt.' }
        ]
    },
    legal_receives: {
        key: 'legal_receives',
        name: 'Legal & Compliance Receives',
        description: 'Confirm the report has been received by Legal & Compliance.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Received Report Copy', required: true }
        ],
        decisions: [
            { name: 'Advance to Review & Classification', reason: 'Report received and assigned.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'review_classify', reason: 'Proceed to review and classification.' }
        ]
    },
    review_classify: {
        key: 'review_classify',
        name: 'Review & Classification',
        description: 'Classify the incident and determine the appropriate workflow path.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Classification Document', required: true }
        ],
        decisions: [
            { name: 'Advance to Investigation', reason: 'Classification complete and investigation warranted.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'investigation', reason: 'Advance to investigation.' }
        ]
    },
    investigation: {
        key: 'investigation',
        name: 'Investigation Conducted',
        description: 'Document the investigation process and findings.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Investigation Notes', required: true },
            { item: 'Witness Statements', required: false },
            { item: 'Employee Statement', required: true }
        ],
        decisions: [
            { name: 'Advance to Hazard Check', reason: 'Proceeding to hazard check.', condition: function(a) { return !!window.IRWF_CONFIG && window.IRWF_CONFIG.isHazardOrAccident; } },
            { name: 'Advance to Compliance Verification', reason: 'Proceeding to compliance verification.', condition: function(a) { return !window.IRWF_CONFIG || !window.IRWF_CONFIG.isHazardOrAccident; } }
        ],
        nextSteps: [
            { step: 'hazard_check', reason: 'Proceed to hazard check.' },
            { step: 'compliance_verify', reason: 'Proceed to compliance verification.' }
        ]
    },
    hazard_check: {
        key: 'hazard_check',
        name: 'Hazard Found?',
        description: 'Determine whether the property needs to fix the identified hazard.',
        questions: [],
        requiredQuestions: [],
        evidence: [],
        decisions: [
            { name: 'No Hazard Found — Close Case', reason: 'No hazard identified, closing case.', condition: function(a) { return false; } },
            { name: 'Hazard Found — Check Remediation', reason: 'Hazard identified, checking if remediated.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'close_archive', reason: 'No hazard found, proceed to closure.' },
            { step: 'hazard_remediated_check', reason: 'Hazard found, check if remediated.' }
        ]
    },
    hazard_remediated_check: {
        key: 'hazard_remediated_check',
        name: 'Has the hazard been remediated?',
        description: 'Verify whether the identified hazard has already been remediated.',
        questions: [],
        requiredQuestions: [],
        evidence: [],
        decisions: [
            { name: 'Not Remediated — File Fix', reason: 'Hazard not yet remediated, filing corrective action.', condition: function(a) { return false; } },
            { name: 'Remediated — Close Case', reason: 'Hazard already remediated, closing case.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'corrective_action', reason: 'Hazard not remediated, proceed to corrective action.' },
            { step: 'close_archive', reason: 'Hazard remediated, proceed to closure.' }
        ]
    },
    corrective_action: {
        key: 'corrective_action',
        name: 'Corrective Action / Fix',
        description: 'Property management must address the identified hazard.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Corrective Action Request', required: true },
            { item: 'Property Management Response', required: false }
        ],
        decisions: [
            { name: 'Advance to Close & Archive', reason: 'Fix completed or case closed.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'close_archive', reason: 'Proceed to closure.' }
        ]
    },
    corrective_request: {
        key: 'corrective_request',
        name: 'Create Corrective Action',
        description: 'Define the corrective actions required to address the incident.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Corrective Action Request Form', required: true }
        ],
        decisions: [
            { name: 'Advance to Department Assignment', reason: 'Corrective action defined.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'assign_department', reason: 'Proceed to department assignment.' }
        ]
    },
    assign_department: {
        key: 'assign_department',
        name: 'Assign to Department',
        description: 'Assign the corrective action to the responsible department.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Assignment Record', required: true }
        ],
        decisions: [
            { name: 'Advance to Department Repair', reason: 'Assignment accepted.', condition: function(a) { return true; } },
            { name: 'Reassign Department', reason: 'Assignment rejected.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'department_repair', reason: 'Proceed to department repair.' }
        ]
    },
    department_repair: {
        key: 'department_repair',
        name: 'Department Performs Repair',
        description: 'Document the corrective work performed by the department.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Work Completion Report', required: true },
            { item: 'Photos of Completed Work', required: false }
        ],
        decisions: [
            { name: 'Advance to Proof of Completion', reason: 'Work declared complete.', condition: function(a) { return true; } },
            { name: 'Return to Corrective Action', reason: 'Additional work required.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'proof_submitted', reason: 'Proceed to proof submission.' },
            { step: 'corrective_request', reason: 'Return to corrective action.' }
        ]
    },
    proof_submitted: {
        key: 'proof_submitted',
        name: 'Proof of Completion',
        description: 'Verify proof of completion has been submitted.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Proof of Completion', required: true }
        ],
        decisions: [
            { name: 'Advance to Legal Verification', reason: 'Proof submitted and sufficient.', condition: function(a) { return true; } },
            { name: 'Return to Department Repair', reason: 'Proof insufficient.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'legal_verifies', reason: 'Proceed to legal verification.' },
            { step: 'department_repair', reason: 'Return for additional work.' }
        ]
    },
    legal_verifies: {
        key: 'legal_verifies',
        name: 'Legal Verification',
        description: 'Legal & Compliance reviews the completed corrective action.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Legal Review Document', required: true }
        ],
        decisions: [
            { name: 'Advance to Hazard Elimination', reason: 'Verification complete, no additional action.', condition: function(a) { return true; } },
            { name: 'Return to Previous Step', reason: 'Additional action required.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'hazard_eliminated', reason: 'Proceed to hazard elimination check.' }
        ]
    },
    hazard_eliminated: {
        key: 'hazard_eliminated',
        name: 'Hazard Eliminated?',
        description: 'Formally verify that the hazard has been eliminated or adequately controlled.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Hazard Elimination Verification', required: true }
        ],
        decisions: [
            { name: 'Proceed to Close & Archive', reason: 'Hazard eliminated.', condition: function(a) { return true; } },
            { name: 'Return to Corrective Action', reason: 'Hazard not eliminated.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'close_archive', reason: 'Proceed to closure.' },
            { step: 'corrective_request', reason: 'Return for additional corrective action.' }
        ]
    },
    compliance_verify: {
        key: 'compliance_verify',
        name: 'Compliance Verification',
        description: 'Final compliance verification before closure.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Compliance Verification Report', required: true }
        ],
        decisions: [
            { name: 'Advance to Close & Archive', reason: 'Compliance verified.', condition: function(a) { return true; } }
        ],
        nextSteps: [
            { step: 'close_archive', reason: 'Proceed to closure.' }
        ]
    },
    close_archive: {
        key: 'close_archive',
        name: 'Close & Archive',
        description: 'Perform final checks before closing the incident.',
        questions: [],
        requiredQuestions: [],
        evidence: [
            { item: 'Closure Checklist', required: true },
            { item: 'Final Approval', required: true }
        ],
        decisions: [
            { name: 'Confirm Closure', reason: 'All requirements complete.', condition: function(a) { return true; } }
        ],
        nextSteps: []
    }
};

// =============================
// Helpers
// =============================
function irwfEsc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function irwfSetStatus(el, text, cls) {
    if (!el) return;
    el.textContent = text;
    el.className = 'irwf-question-status' + (cls ? ' ' + cls : '');
}

function irwfGetStepEl(key) {
    return document.querySelector('.irwf-flow-step[data-step-key="' + irwfEsc(key) + '"]');
}

function irwfGetCurrentStepEl() {
    return document.querySelector('.irwf-flow-step.current');
}

function irwfGetActionStatusEl(stepEl) {
    if (!stepEl) stepEl = irwfGetCurrentStepEl();
    if (!stepEl) return null;
    return stepEl.querySelector('.irwf-action-status');
}

// =============================
// Initialization
// =============================
function irwfInit() {
    const cfg = window.IRWF_CONFIG || {};
    irwf.incidentId = parseInt(cfg.incidentId || document.body.getAttribute('data-incident-id') || document.querySelector('[data-incident-id]')?.getAttribute('data-incident-id') || '0', 10);
    irwf.currentStepKey = cfg.currentStepKey || document.body.getAttribute('data-current-step-key') || '';
    console.log('[IRWF] init config', cfg, 'resolved incidentId', irwf.incidentId, 'step', irwf.currentStepKey);
    irwf.workflowSteps = Array.from(document.querySelectorAll('.irwf-flow-step')).map(function(el) {
        return {
            key: el.getAttribute('data-step-key'),
            index: parseInt(el.getAttribute('data-step-index') || '0', 10)
        };
    });
    irwf.userInfo = irwfReadUserInfo();

    irwfBindDecisionPoints();
    irwfBindActionToggles();

    if (irwf.currentStepKey && IRWF_WORKFLOW_STAGES[irwf.currentStepKey]) {
        irwfScrollToCurrent();
    }

    irwfStartRealtimeSync();

    window.addEventListener('beforeunload', function(e) {
        irwfStopRealtimeSync();
    });
}

function irwfReadUserInfo() {
    const el = document.getElementById('irwfUserInfo');
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
let irwfPollTimer = null;
let irwfLastKnownStatus = null;

function irwfStartRealtimeSync() {
    if (irwfPollTimer) return;
    irwfPollTimer = setInterval(irwfPollIncidentStatus, 5000);
}

function irwfStopRealtimeSync() {
    if (irwfPollTimer) {
        clearInterval(irwfPollTimer);
        irwfPollTimer = null;
    }
}

function irwfPollIncidentStatus() {
    if (!irwf.incidentId || !IRWF_STATUS_API) return;

    const xhr = new XMLHttpRequest();
    xhr.open('GET', IRWF_STATUS_API + '?incident_id=' + encodeURIComponent(irwf.incidentId), true);
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

        if (!data || !data.success || !data.incident) return;

        const incident = data.incident;
        const newStatus = incident.status;

        if (irwfLastKnownStatus && irwfLastKnownStatus !== newStatus) {
            irwfLastKnownStatus = newStatus;
            irwfRefreshStaticIncidentInfo(incident);
            irwfRefreshWorkflowStepStates(data.workflow || []);
        } else if (!irwfLastKnownStatus) {
            irwfLastKnownStatus = newStatus;
        }
    };
    xhr.send();
}

function irwfRefreshStaticIncidentInfo(incident) {
    const fields = document.querySelectorAll('.irwf-field');
    fields.forEach(function(field) {
        const label = field.querySelector('.irwf-label');
        const value = field.querySelector('.irwf-value');
        if (!label || !value) return;

        const labelText = (label.textContent || '').trim();
        if (labelText === 'Current Status' && incident.status) {
            value.textContent = incident.status;
        } else if (labelText === 'Severity' && incident.severity) {
            value.textContent = incident.severity.charAt(0).toUpperCase() + incident.severity.slice(1);
        } else if (labelText === 'Category' && incident.incident_type) {
            value.textContent = incident.incident_type;
        } else if (labelText === 'Assigned Officer' && incident.assigned_name) {
            value.textContent = incident.assigned_name;
        } else if (labelText === 'Reported By' && incident.reporter_name) {
            value.textContent = incident.reporter_name;
        }
    });

    if (incident.updated_at) {
        const date = new Date(incident.updated_at);
        const formatted = date.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
        const metaEls = document.querySelectorAll('.irwf-flow-meta');
        metaEls.forEach(function(el) { el.textContent = formatted; });
    }
}

function irwfRefreshWorkflowStepStates(workflowSteps) {
    const completedSteps = new Set();
    workflowSteps.forEach(function(step) {
        if (step.step_status === 'completed' || step.step_status === 'in_progress') {
            completedSteps.add(step.step);
        }
    });

    document.querySelectorAll('.irwf-flow-step').forEach(function(el) {
        const stepKey = el.getAttribute('data-step-key');
        if (!stepKey) return;

        const isCurrent = stepKey === irwf.currentStepKey;
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
function irwfAdvanceStep(fromStep, toStep, action, reason) {
    irwf.recordTransition(fromStep, toStep, action, reason);

    const fromEl = irwfGetStepEl(fromStep);
    const toEl = irwfGetStepEl(toStep);

    if (fromEl) {
        fromEl.classList.remove('current');
        fromEl.classList.add('completed');
        const actions = fromEl.querySelector('.irwf-step-actions');
        if (actions) actions.style.display = 'none';
        const decision = fromEl.querySelector('.irwf-decision');
        if (decision) decision.style.display = 'none';
        const question = fromEl.querySelector('.irwf-step-question');
        if (question) question.style.display = 'none';
    }

    if (toEl) {
        toEl.classList.add('current');
        toEl.classList.remove('completed', 'pending');
        const actions = toEl.querySelector('.irwf-step-actions');
        if (actions) actions.style.display = '';
        const decision = toEl.querySelector('.irwf-decision');
        if (decision) decision.style.display = '';
        const question = toEl.querySelector('.irwf-step-question');
        if (question) question.style.display = '';
    }

    irwf.currentStepKey = toStep;
    irwf.currentQuestionId = null;
    irwf.dirty = false;

    irwfNotify('Transitioned to ' + (IRWF_WORKFLOW_STAGES[toStep]?.name || toStep), 'success');
    setTimeout(function() {
        irwfScrollToCurrent();
    }, 400);
    setTimeout(function() { location.reload(); }, 1000);
}

function irwfReopenStep(stepKey) {
    const reason = prompt('Why are you reopening this step?');
    if (reason === null) return;

    irwf.reopenHistory.push({
        stepKey: stepKey,
        reason: reason,
        timestamp: new Date().toISOString(),
        userId: irwf.userInfo.id,
        userName: irwf.userInfo.name
    });

    irwf.stepStatus[stepKey] = 'reopened';
    irwf.answers = {};
    irwf.currentQuestionId = null;

    const stepEl = irwfGetStepEl(stepKey);
    if (stepEl) {
        stepEl.classList.add('current');
        stepEl.classList.remove('completed');
        const question = stepEl.querySelector('.irwf-step-question');
        if (question) question.style.display = '';
    }

    irwf.currentStepKey = stepKey;
    irwfNotify('Step reopened for reassessment.', 'success');
    setTimeout(function() {
        irwfScrollToCurrent();
    }, 400);
}

function irwfRecordTransition(fromStep, toStep, action, reason) {
    irwf.transitionHistory.push({
        caseId: irwf.incidentId,
        fromStep: fromStep,
        toStep: toStep,
        action: action,
        reason: reason,
        userId: irwf.userInfo.id,
        userName: irwf.userInfo.name,
        timestamp: new Date().toISOString()
    });
}

// =============================
// Decision Points
// =============================
function irwfBindDecisionPoints() {
    document.querySelectorAll('.irwf-flow-step.current .irwf-decision-options').forEach(function(opts) {
        opts.querySelectorAll('button.irwf-decision-opt').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const stepEl = btn.closest('.irwf-flow-step');
                if (!stepEl) return;
                const stepKey = stepEl.getAttribute('data-step-key');
                const label = (btn.textContent || '').trim();
                let action = null;

                if (stepKey === 'hazard_remediated_check') {
                    if (label.indexOf('Remediated') !== -1) {
                        action = 'remediated_yes';
                    } else if (label.indexOf('Not Remediated') !== -1 || label.indexOf('No') !== -1) {
                        action = 'remediated_no';
                    }
                } else if (label.indexOf('Close') !== -1 || label.indexOf('No') !== -1) {
                    action = 'close';
                } else if (label.indexOf('Reopen') !== -1 || label.indexOf('Yes') !== -1 || label.indexOf('Escalate') !== -1) {
                    action = 'advance';
                }

                if (!action) return;
                irwfPersistDecisionTransition(stepEl, action);
            });
        });
    });
}

function irwfPersistDecisionTransition(stepEl, action) {
    const stepKey = stepEl.getAttribute('data-step-key');
    const statusEl = irwfGetActionStatusEl(stepEl);
    irwfSetStatus(statusEl, 'Processing...', '');

    const formData = new FormData();
    formData.append('incident_id', irwf.incidentId);
    formData.append('action', action);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', IRWF_ACTION_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) {
            irwfSetStatus(statusEl, 'Request failed (' + xhr.status + ').', 'error');
            return;
        }
        try {
            const data = JSON.parse(xhr.responseText);
            if (data.success) {
                irwfSetStatus(statusEl, 'Updated.', 'success');

                let nextStep = null;
                if (stepKey === 'hazard_check') {
                    nextStep = action === 'hazard_yes' ? 'hazard_remediated_check' : 'close_archive';
                } else if (stepKey === 'hazard_remediated_check') {
                    nextStep = action === 'remediated_yes' ? 'close_archive' : 'corrective_action';
                } else if (stepKey === 'investigation' && action === 'close') {
                    nextStep = 'close_archive';
                }

                if (nextStep) {
                    const decisionLabel = nextStep === 'close_archive' ? 'Proceed to Close & Archive' : 'Route to Corrective Action';
                    irwfAdvanceStep(stepKey, nextStep, decisionLabel, 'Decision: ' + decisionLabel);
                } else {
                    irwfPersistStepTransition(stepEl);
                }

                if (data.new_status) {
                    irwfLastKnownStatus = data.new_status;
                }
            } else {
                irwfSetStatus(statusEl, data.message || 'Update failed.', 'error');
            }
        } catch (e) {
            console.error('[IRWF] Invalid JSON from action API. Step:', stepKey, 'Action:', action, 'Response:', xhr.responseText);
            irwfSetStatus(statusEl, 'Invalid server response.', 'error');
        }
    };
    xhr.send(formData);
}

function irwfPersistStepTransition(stepEl) {
    const currentStep = stepEl || irwfGetCurrentStepEl();
    if (!currentStep) return;

    const nextStep = currentStep.nextElementSibling;
    if (nextStep && nextStep.classList.contains('irwf-flow-step')) {
        const actions = currentStep.querySelector('.irwf-step-actions');
        if (actions) actions.style.display = 'none';
        const decision = currentStep.querySelector('.irwf-decision');
        if (decision) decision.style.display = 'none';
        const meta = currentStep.querySelector('.irwf-flow-meta');
        if (meta) meta.style.display = 'none';
        const question = currentStep.querySelector('.irwf-step-question');
        if (question) question.style.display = 'none';

        currentStep.classList.remove('current');
        currentStep.classList.add('completed');

        nextStep.classList.add('current');
        nextStep.classList.remove('completed');

        const nextQuestion = nextStep.querySelector('.irwf-step-question');
        if (nextQuestion) nextQuestion.style.display = '';
        const nextActions = nextStep.querySelector('.irwf-step-actions');
        if (nextActions) nextActions.style.display = '';
        const nextDecision = nextStep.querySelector('.irwf-decision');
        if (nextDecision) nextDecision.style.display = '';

        nextStep.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    setTimeout(function() { location.reload(); }, 1000);
}

// =============================
// Decision Persistence
// =============================
function irwfSaveDecision(decision, callback) {
    const stepKey = irwf.currentStepKey;
    const url = IRWF_API + '?incident_id=' + encodeURIComponent(irwf.incidentId) + '&workflow_step_key=' + encodeURIComponent(stepKey) + '&action=save_decision';
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
function irwfNotify(message, type) {
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

function irwfScrollToCurrent() {
    const currentEl = document.querySelector('.irwf-flow-step.current');
    if (currentEl) currentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// =============================
// Action Toggles
// =============================
function irwfBindActionToggles() {
    document.querySelectorAll('.irwf-step-actions-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
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
}

// =============================
// Confirm Modal Metadata
// =============================
const IRWF_ACTION_META = {
    'hazard_yes': { title: 'Mark hazard found?', description: 'This will advance the workflow to hazard remediation.', confirmLabel: 'Yes, Hazard Found', severity: 'danger' },
    'hazard_no': { title: 'Confirm no hazard?', description: 'This will proceed without hazard remediation.', confirmLabel: 'No Hazard', severity: 'default' },
    'remediated_yes': { title: 'Confirm remediation?', description: 'This will close the incident as remediated.', confirmLabel: 'Yes, Remediated', severity: 'default' },
    'remediated_no': { title: 'Require fix?', description: 'This will file a corrective action for the hazard.', confirmLabel: 'File Fix', severity: 'default' },
    'advance': { title: 'Advance workflow?', description: 'This will move the incident to the next stage.', confirmLabel: 'Confirm Update', severity: 'primary' },
    'close': { title: 'Close incident?', description: 'This will close the incident permanently.', confirmLabel: 'Close Incident', severity: 'danger' },
    'reopen': { title: 'Reopen incident?', description: 'This will reopen the incident for further review.', confirmLabel: 'Reopen', severity: 'default' },
};

function irwfGetActionTransition(action, currentStatus) {
    var transitionMap = {
        'submitted': { advance: 'Under Review', close: 'Closed' },
        'under_review': { advance: 'Investigation', close: 'Closed' },
        'investigation': { advance: 'Corrective Action', close: 'Closed' },
        'escalated': { advance: 'Compliance Verification', close: 'Closed', reopen: 'Investigation' },
        'resolved': { close: 'Closed', reopen: 'Investigation' },
        'hazard_open': { advance: 'Close & Archive', close: 'Closed' },
        'closed': { reopen: 'Investigation' },
    };
    var map = transitionMap[currentStatus] || {};
    return map[action] || null;
}

function irwfStatusLabel(status) {
    var map = {
        'submitted': 'Received',
        'under_review': 'Under Review',
        'investigation': 'Investigation',
        'escalated': 'Corrective Action',
        'resolved': 'Compliance Verification',
        'hazard_open': 'Corrective Action',
        'closed': 'Closed',
    };
    return map[status.toLowerCase()] || (status || '').replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
}

function irwfSetConfirmState(state) {
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

function irwfTrapFocus(modal) {
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

function irwfSetupConfirmDialog() {
    var modal = document.getElementById('irwfConfirmModal');
    if (!modal) return;
    var backdrop = modal;
    var escapeHandler = function(e) {
        if (e.key === 'Escape') {
            irwfConfirmModal(false);
        }
    };
    backdrop.addEventListener('keydown', escapeHandler);
    var focusable = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (focusable.length) {
        focusable[0].focus();
    }
    window._irwfConfirmEscapeHandler = escapeHandler;
    window._irwfConfirmFocusHandler = irwfTrapFocus(modal);
}

function irwfCleanupConfirmDialog() {
    if (window._irwfConfirmEscapeHandler) {
        var modal = document.getElementById('irwfConfirmModal');
        if (modal) {
            modal.removeEventListener('keydown', window._irwfConfirmEscapeHandler);
        }
        window._irwfConfirmEscapeHandler = null;
    }
    window._irwfConfirmFocusHandler = null;
}

// =============================
// Existing API wrappers
// =============================
window.irwfSubmitAction = async function(action, btn) {
    const stepBlock = btn ? btn.closest('.irwf-step-actions') : null;
    const statusEl = stepBlock ? stepBlock.querySelector('.irwf-action-status') : document.getElementById('irwfActionStatus');
    if (!statusEl) {
        const currentStep = document.querySelector('.irwf-flow-step.current .irwf-flow-body');
        statusEl = currentStep ? currentStep.querySelector('.irwf-action-status') : null;
    }
    if (!statusEl) return;

    const stepEl = btn ? btn.closest('.irwf-flow-step') : irwfGetCurrentStepEl();
    const stepKey = stepEl ? stepEl.getAttribute('data-step-key') : irwf.currentStepKey;

    if (btn) btn.disabled = true;

    const meta = IRWF_ACTION_META[action] || {};
    const currentStatus = (irwfLastKnownStatus || '').toLowerCase();
    const nextStatus = irwfGetActionTransition(action, currentStatus);
    const currentLabel = irwfStatusLabel(currentStatus);
    const nextLabel = nextStatus || '';
    const transitionHtml = nextLabel ? '<span class="irwf-confirm-status-current">' + currentLabel + '</span><span class="irwf-confirm-status-arrow">→</span><span class="irwf-confirm-status-next">' + nextLabel + '</span>' : '';
    const confirmed = await irwfConfirm({
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

    irwfSetConfirmState('loading');
    statusEl.textContent = 'Updating...';

    const formData = new FormData();
    formData.append('incident_id', irwf.incidentId);
    formData.append('action', action);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', IRWF_ACTION_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status < 200 || xhr.status >= 300) {
            irwfSetConfirmState('error');
            statusEl.textContent = 'Request failed (' + xhr.status + '). Check console.';
            statusEl.className = 'irwf-action-status error';
            if (btn) btn.disabled = false;
            setTimeout(function() {
                irwfSetConfirmState(null);
                irwfCloseModal('irwfConfirmModal');
            }, 1200);
            return;
        }
        let data = null;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            irwfSetConfirmState('error');
            statusEl.textContent = 'Invalid server response. Check console.';
            statusEl.className = 'irwf-action-status error';
            console.error('Invalid JSON', xhr.responseText);
            if (btn) btn.disabled = false;
            setTimeout(function() {
                irwfSetConfirmState(null);
                irwfCloseModal('irwfConfirmModal');
            }, 1200);
            return;
        }
        if (data && data.success) {
            irwfSetConfirmState('success');
            statusEl.textContent = 'Updated.';
            statusEl.className = 'irwf-action-status success';
            irwfPersistStepTransition(stepEl);

            if (data.new_status) {
                irwfLastKnownStatus = data.new_status;
            }
            setTimeout(function() {
                irwfSetConfirmState(null);
                irwfCloseModal('irwfConfirmModal');
            }, 800);
        } else {
            irwfSetConfirmState('error');
            statusEl.textContent = (data && data.message) || 'Update failed.';
            statusEl.className = 'irwf-action-status error';
            if (btn) btn.disabled = false;
            setTimeout(function() {
                irwfSetConfirmState(null);
                irwfCloseModal('irwfConfirmModal');
            }, 1200);
        }
    };
    xhr.onerror = function() {
        console.error('Workflow action network error. URL:', IRWF_ACTION_API);
        irwfSetConfirmState('error');
        statusEl.textContent = 'Network error. Check console for details.';
        statusEl.className = 'irwf-action-status error';
        if (btn) btn.disabled = false;
        setTimeout(function() {
            irwfSetConfirmState(null);
            irwfCloseModal('irwfConfirmModal');
        }, 1200);
    };
    xhr.send(formData);
};

window.irwfCloseModal = function(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
    irwfCleanupConfirmDialog();
};

window.irwfOpenModal = function(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
};

window.irwfConfirm = function(messageOrOptions) {
    return new Promise(function(resolve) {
        const modal = document.getElementById('irwfConfirmModal');
        const titleEl = document.getElementById('irwfConfirmTitle');
        const descEl = document.getElementById('irwfConfirmDesc');
        const transitionEl = document.getElementById('irwfConfirmTransition');
        const submitBtn = document.querySelector('.irwf-confirm-submit');
        const cancelBtn = document.querySelector('.irwf-confirm-cancel');
        const fallbackEl = document.getElementById('irwfConfirmMessage');
        
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

        window._irwfConfirmResolve = resolve;
        window._irwfConfirmTriggerBtn = options.triggerBtn || null;
        irwfSetConfirmState(null);
        irwfOpenModal('irwfConfirmModal');
        irwfSetupConfirmDialog();
    });
};

window.irwfConfirmModal = function(result) {
    if (result) {
        irwfSetConfirmState('loading');
        irwfCleanupConfirmDialog();
        if (typeof window._irwfConfirmResolve === 'function') {
            const resolve = window._irwfConfirmResolve;
            window._irwfConfirmResolve = null;
            resolve(result);
        }
    } else {
        irwfCloseModal('irwfConfirmModal');
        irwfCleanupConfirmDialog();
        if (typeof window._irwfConfirmResolve === 'function') {
            const resolve = window._irwfConfirmResolve;
            window._irwfConfirmResolve = null;
            resolve(result);
        }
        var triggerBtn = window._irwfConfirmTriggerBtn;
        window._irwfConfirmTriggerBtn = null;
        if (triggerBtn && typeof triggerBtn.focus === 'function') {
            triggerBtn.focus();
        }
    }
};

window.irwfViewEvidence = function() {
    const statusEl = document.getElementById('irwfEvidenceStatus');
    const bodyEl = document.getElementById('irwfEvidenceBody');
    if (!bodyEl) return;

    statusEl.textContent = '';
    statusEl.className = 'irwf-action-status';
    bodyEl.innerHTML = '<div class="irwf-evidence-loading">Loading...</div>';
    irwfOpenModal('irwfEvidenceModal');

    const xhr = new XMLHttpRequest();
    xhr.open('GET', IRWF_EVIDENCE_API + '?incident_id=' + encodeURIComponent(irwf.incidentId), true);
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
            const baseUrl = 'modules/compliance/';
            let html = '<div class="irwf-evidence-list">';
            for (let i = 0; i < data.evidence.length; i++) {
                const ev = data.evidence[i];
                const fileUrl = baseUrl + ev.file_path;
                html += '<div class="irwf-evidence-item">';
                html += '<div class="irwf-evidence-details">';
                html += '<div class="irwf-evidence-name"><a href="' + fileUrl + '" target="_blank">' + ev.file_name + '</a></div>';
                if (ev.description) html += '<div class="irwf-evidence-desc">' + ev.description + '</div>';
                html += '<div class="irwf-evidence-meta">' + (ev.file_type || '') + ' &middot; ' + (ev.file_size ? Math.round(ev.file_size / 1024) + ' KB' : '') + ' &middot; Uploaded ' + ev.uploaded_at + '</div>';
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
            bodyEl.innerHTML = '<div class="irwf-evidence-empty">No evidence uploaded.</div>';
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

window.irwfRequestAdditionalEvidence = function() {
    const cfg = window.IRWF_CONFIG || {};
    const reporterName = cfg.reporterName || '';
    const reporterEmail = cfg.reporterEmail || '';
    const reporterNo = cfg.reporterEmployeeNo || '';
    const incidentId = cfg.incidentIdNumber || irwf.incidentId;
    const incidentType = cfg.incidentType || '';

    const subject = encodeURIComponent('Request for Additional Evidence - ' + incidentId + ' (' + incidentType + ')');
    const body = encodeURIComponent("Dear " + (reporterName || 'Employee') + ",\n\nWe need additional evidence regarding incident " + incidentId + " (" + incidentType + ").\n\nPlease provide the following at your earliest convenience:\n- Any supporting documents or evidence\n- Additional details regarding the incident\n- Names of any additional witnesses\n\nYour prompt response is appreciated.\n\nThank you.");
    const name = encodeURIComponent(reporterName);
    const email = encodeURIComponent(reporterEmail);
    const no = encodeURIComponent(reporterNo);

    window.location.href = '?page=notification-compose&mode=new&to_recipient_name=' + name + '&to_recipient_email=' + email + '&to_recipient_no=' + no + '&subject=' + subject + '&body=' + body;
};

window.irwfOpenWitnessModal = function() {
    const textarea = document.getElementById('irwfWitnessText');
    if (textarea) textarea.value = '';
    irwfOpenModal('irwfWitnessModal');
    const statusEl = document.getElementById('irwfWitnessStatus');
    if (statusEl) {
        statusEl.textContent = '';
        statusEl.className = 'irwf-action-status';
    }
};

window.irwfSaveWitnessStatement = function() {
    const textarea = document.getElementById('irwfWitnessText');
    const statement = textarea ? textarea.value.trim() : '';
    const statusEl = document.getElementById('irwfWitnessStatus');
    if (!statement) {
        if (statusEl) {
            statusEl.textContent = 'Please enter a witness statement.';
            statusEl.className = 'irwf-action-status error';
        }
        return;
    }

    const formData = new FormData();
    formData.append('incident_id', irwf.incidentId);
    formData.append('statement', statement);

    const btn = document.querySelector('#irwfWitnessModal .cc-btn.primary');
    if (btn) btn.disabled = true;
    if (statusEl) {
        statusEl.textContent = 'Saving...';
        statusEl.className = 'irwf-action-status';
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', IRWF_WITNESS_API, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (btn) btn.disabled = false;
        if (statusEl) {
            statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
        }
        if (xhr.status < 200 || xhr.status >= 300) {
            if (statusEl) {
                statusEl.textContent = 'Request failed (' + xhr.status + ').';
                statusEl.className = 'irwf-action-status error';
            }
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
            return;
        }
        if (data && data.success) {
            if (statusEl) {
                statusEl.textContent = 'Statement saved successfully.';
                statusEl.className = 'irwf-action-status success';
            }
            if (textarea) textarea.value = '';
            setTimeout(function() {
                irwfCloseModal('irwfWitnessModal');
                if (statusEl) {
                    statusEl.textContent = '';
                    statusEl.className = 'irwf-action-status';
                }
            }, 800);
        } else {
            if (statusEl) {
                statusEl.textContent = data.message || 'Save failed.';
                statusEl.className = 'irwf-action-status error';
            }
        }
    };
    xhr.onerror = function() {
        if (btn) btn.disabled = false;
        if (statusEl) {
            statusEl.textContent = 'Network error.';
            statusEl.className = 'irwf-action-status error';
        }
    };
    xhr.send(formData);
};

window.irwfPersistStepTransition = window.irwfPersistStepTransition || irwfPersistStepTransition;
window.irwfShowQuestion = window.irwfShowQuestion || function() {};
window.irwfSilentAction = window.irwfSilentAction || function() {};
window.irwfDebugApi = function(questionId, stepKey) {
    stepKey = stepKey || irwf.currentStepKey;
    const url = IRWF_API + '?incident_id=' + encodeURIComponent(irwf.incidentId) + '&workflow_step_key=' + encodeURIComponent(stepKey) + '&local_question_id=' + encodeURIComponent(questionId) + '&question=' + encodeURIComponent('Debug+question') + '&purpose=' + encodeURIComponent('Debug+purpose') + '&can_advance=0';
    console.log('[IRWF DEBUG] GET', url);
    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        console.log('[IRWF DEBUG] status', xhr.status, 'responseText', xhr.responseText);
    };
    xhr.send();
};

// =============================
// Boot
// =============================
(function() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', irwfInit);
    } else {
        irwfInit();
    }
})();
