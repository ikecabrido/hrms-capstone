export class WorkflowRenderer {
  constructor(containerId, apiBase = '/modules/compliance/lib/api') {
    this.container = document.getElementById(containerId);
    this.apiBase = apiBase;
    this.caseId = null;
    this.state = null;
  }

  async load(caseId) {
    this.caseId = caseId;
    await this.fetchState();
    this.render();
    this.bindEvents();
  }

  async fetchState() {
    const url = `${this.apiBase}/ComplaintWorkflowStateAPI.php?complaint_id=${encodeURIComponent(this.caseId)}`;
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error('Failed to load workflow state');
    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'Unknown error');
    this.state = data;
  }

  render() {
    if (!this.container || !this.state) return;
    this.container.innerHTML = '';

    const timeline = this.buildTimeline();
    this.container.appendChild(timeline);

    const currentStep = this.state.steps.find((s) => s.status === 'current');
    if (currentStep) {
      const actionsPanel = this.buildActionsPanel(currentStep);
      this.container.appendChild(actionsPanel);
    }
  }

  buildTimeline() {
    const wrapper = document.createElement('div');
    wrapper.className = 'cw-flow';

    this.state.steps.forEach((step, idx) => {
      const el = document.createElement('div');
      el.className = `cw-flow-step ${step.status}`;
      el.dataset.stepKey = step.step_key;
      el.dataset.stepIndex = String(idx);

      const badgeClass =
        step.status === 'current'
          ? 'badge-current'
          : step.status === 'completed'
          ? 'badge-completed'
          : 'badge-pending';

      const badgeLabel =
        step.status === 'current'
          ? 'Current'
          : step.status === 'completed'
          ? 'Completed'
          : 'Pending';

      el.innerHTML = `
        <div class="cw-flow-dot"></div>
        <div class="cw-flow-body">
          <div class="cw-flow-title">
            ${this.escapeHtml(step.step_name)}
            <span class="cw-flow-badge ${badgeClass}">${this.escapeHtml(badgeLabel)}</span>
          </div>
          <div class="cw-flow-meta">${step.completed_at ? this.formatDate(step.completed_at) : 'Pending'}</div>
        </div>
      `;

      wrapper.appendChild(el);
    });

    return wrapper;
  }

  buildActionsPanel(currentStep) {
    const panel = document.createElement('div');
    panel.className = 'cw-step-actions';
    panel.dataset.stepIndex = String(this.state.steps.indexOf(currentStep));
    panel.dataset.stepKey = currentStep.step_key;

    const grid = document.createElement('div');
    grid.className = 'cw-action-grid';

    const actions = this.state.available_actions || [];
    actions.forEach((act) => {
      const cell = document.createElement('div');
      cell.className = 'cw-action-cell';

      if (act.action === 'apply_status') {
        const select = document.createElement('select');
        select.id = 'chStatusSelect';
        select.className = 'cw-form-select';
        select.innerHTML =
          '<option value="">— Select Status —</option>' +
          '<option value="closed_no_violation">No Violation</option>' +
          '<option value="closed_warning_issued">Warning Issued</option>' +
          '<option value="closed_suspension">Suspension</option>' +
          '<option value="closed_termination_recommended">Termination Recommended</option>' +
          '<option value="closed_resolved">Resolved</option>';
        cell.appendChild(select);
      } else {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = this.getButtonClass(act.action);
        button.dataset.action = act.action;
        button.disabled = !!act.disabled;
        if (act.disabled && act.disabled_reason) {
          button.title = act.disabled_reason;
        }

        const icon = this.getIconForAction(act.action);
        const label = this.getLabelForAction(act.action);
        button.innerHTML = icon ? `<i class="${icon}"></i> ${label}` : label;
        cell.appendChild(button);
      }

      grid.appendChild(cell);
    });

    panel.appendChild(grid);

    const statusArea = document.createElement('span');
    statusArea.className = 'cw-action-status';
    statusArea.dataset.actionStatusFor = currentStep.step_key;
    panel.appendChild(statusArea);

    return panel;
  }

  getButtonClass(action) {
    const map = {
      advance: 'cw-btn primary',
      apply_status: 'cw-btn danger',
      reopen: 'cw-btn',
      finalize_decision: 'cw-btn primary',
      record_decision: 'cw-btn primary',
    };
    return map[action] || 'cw-btn';
  }

  getIconForAction(action) {
    const map = {
      apply_status: 'bi bi-check2-circle',
      reopen: 'bi bi-arrow-counterclockwise',
      finalize_decision: 'bi bi-check2-circle',
      record_decision: 'bi bi-x-circle',
      advance: 'bi bi-arrow-right',
    };
    return map[action] || '';
  }

  getLabelForAction(action) {
    const map = {
      apply_status: 'Apply',
      reopen: 'Reopen Investigation',
      finalize_decision: 'Finalize Decision',
      record_decision: 'Dismiss Complaint',
      advance: 'Advance',
    };
    return map[action] || action;
  }

  bindEvents() {
    if (!this.container) return;

    this.container.addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn || btn.disabled) return;

      const action = btn.dataset.action;
      await this.submitAction(action);
    });

    const select = this.container.querySelector('#chStatusSelect');
    if (select) {
      select.addEventListener('change', () => {
        const applyBtn = this.container.querySelector('button[data-action="apply_status"]');
        if (applyBtn) applyBtn.disabled = !select.value;
      });
    }
  }

  async submitAction(action) {
    const statusEl = this.container.querySelector('.cw-action-status');
    if (statusEl) {
      statusEl.textContent = 'Processing...';
      statusEl.className = 'cw-action-status';
    }

    try {
      const payload = {
        action,
        version: this.state.version,
        decision: {},
      };

      if (action === 'apply_status' || action === 'record_decision') {
        const select = this.container.querySelector('#chStatusSelect');
        if (!select || !select.value) {
          if (statusEl) {
            statusEl.textContent = 'Please select a status.';
            statusEl.className = 'cw-action-status error';
          }
          return;
        }
        payload.decision.target_status = select.value;
      }

      const formData = new FormData();
      formData.append('complaint_id', String(this.caseId));
      formData.append('action', action);
      formData.append('version', String(payload.version));
      if (payload.decision.target_status) {
        formData.append('target_status', payload.decision.target_status);
      }
      formData.append('decision', JSON.stringify(payload.decision));

      const response = await fetch(`${this.apiBase}/complaint_workflow_action.php`, {
        method: 'POST',
        body: formData,
      });

      const data = await response.json();
      if (data.success) {
        if (statusEl) {
          statusEl.textContent = data.message || 'Success.';
          statusEl.className = 'cw-action-status success';
        }
        await this.fetchState();
        this.render();
        this.bindEvents();
      } else {
        if (statusEl) {
          statusEl.textContent = data.message || 'Action failed.';
          statusEl.className = 'cw-action-status error';
        }
      }
    } catch (err) {
      if (statusEl) {
        statusEl.textContent = 'Network error: ' + err.message;
        statusEl.className = 'cw-action-status error';
      }
    }
  }

  escapeHtml(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  formatDate(iso) {
    if (!iso) return 'Pending';
    const d = new Date(iso.replace(' ', 'T'));
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleString('en-PH', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }
}

