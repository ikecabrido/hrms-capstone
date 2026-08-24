<!-- TAB: ATTRITION & TURNOVER -->
<div class="wfa-container" id="attritionContainer">
    <div class="wfa-loading">
        <i class="fas fa-spinner fa-spin"></i> Loading Attrition Data...
    </div>
</div>

<script>
let separatedEmployeesCache = [];

async function loadAttritionTab() {
    const container = document.getElementById('attritionContainer');
    if (!container) return;

    container.innerHTML = `
        <div class="wfa-loading">
            <i class="fas fa-spinner fa-spin"></i> Loading Attrition Data...
        </div>
    `;

    try {
        const selectedYear = new Date().getFullYear();
        const attritionApiUrl = `/hrms-capstone/modules/workforce/api/attrition_data.php?year=${selectedYear}`;

        const attritionResponse = await fetch(attritionApiUrl);

        if (!attritionResponse.ok) throw new Error(`Attrition API error: ${attritionResponse.status}`);

        const attritionResult = await attritionResponse.json();
        if (!attritionResult.success) throw new Error(attritionResult.message || 'Unable to load attrition data');

        renderAttritionPage(attritionResult.data, parseInt(selectedYear, 10));
    } catch (error) {
        container.innerHTML = `
            <div style="padding: 20px; color: #d32f2f; background: #fff1f0; border: 1px solid #f8d7da; border-radius: 10px;">
                <strong>Error loading attrition metrics:</strong> ${error.message}
            </div>
        `;
        console.error('Attrition load error:', error);
    }
}



function getRiskBadgeClass(riskLevel) {
    const normalized = String(riskLevel || 'Low').toLowerCase();
    if (normalized.includes('high')) return '#dc2626';
    if (normalized.includes('medium')) return '#d97706';
    return '#16a34a';
}

function renderAtRiskWatchlist(riskEmployees) {
    if (!Array.isArray(riskEmployees) || riskEmployees.length === 0) {
        return `
            <div style="padding: 20px; background: #f8fafc; border-radius: 14px; border: 1px dashed #cbd5e1; color: #64748b; text-align: center;">
                No active at-risk employees are available for this evaluation.
            </div>
        `;
    }

    const highCount = riskEmployees.filter(emp => String(emp.risk_level || '').toLowerCase().includes('high')).length;
    const mediumCount = riskEmployees.filter(emp => String(emp.risk_level || '').toLowerCase().includes('medium')).length;
    const lowCount = riskEmployees.filter(emp => String(emp.risk_level || '').toLowerCase().includes('low')).length;

    const rows = riskEmployees.slice(0, 10).map(emp => {
        const riskLevel = emp.risk_level || 'Low';
        const badgeColor = getRiskBadgeClass(riskLevel);
        const performanceScore = emp.performance_score ?? 0;
        const tenureYears = emp.tenure_years ?? 0;
        return `
            <tr>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${emp.name || 'Unknown'}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${emp.department || 'N/A'}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${emp.position || 'N/A'}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${performanceScore}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${tenureYears} years</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">
                    <span style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 999px; color: white; background: ${badgeColor}; font-weight: 700; font-size: 12px; text-transform: uppercase;">
                        ${riskLevel}
                    </span>
                </td>
            </tr>
        `;
    }).join('');

    return `
        <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 10px 20px rgba(16, 24, 40, 0.06); margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3 style="margin: 0; font-size: 20px; color: #111827;">Retention Risk Watchlist</h3>
                    <p style="margin: 6px 0 0; color: #475569; font-size: 14px;">Employees currently flagged for possible retention follow-up.</p>
                </div>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <span style="padding: 8px 12px; border-radius: 999px; background: #fff1f2; color: #be123c; font-weight: 700;">High: ${highCount}</span>
                    <span style="padding: 8px 12px; border-radius: 999px; background: #fff7ed; color: #b45309; font-weight: 700;">Medium: ${mediumCount}</span>
                    <span style="padding: 8px 12px; border-radius: 999px; background: #ecfdf5; color: #047857; font-weight: 700;">Low: ${lowCount}</span>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="wfa-table" style="width: 100%; border-collapse: collapse; border-spacing: 0; min-width: 760px;">
                    <thead>
                        <tr style="background: #f8fafc; color: #102a43; text-align: left;">
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Name</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Department</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Position</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Performance</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Tenure</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Risk Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function renderRetentionActionCards(riskEmployees) {
    if (!Array.isArray(riskEmployees) || riskEmployees.length === 0) {
        return '';
    }

    const highCount = riskEmployees.filter(emp => String(emp.risk_level || '').toLowerCase().includes('high')).length;
    const mediumCount = riskEmployees.filter(emp => String(emp.risk_level || '').toLowerCase().includes('medium')).length;
    const lowCount = riskEmployees.filter(emp => String(emp.risk_level || '').toLowerCase().includes('low')).length;

    const cards = [
        {
            title: 'High Risk Follow-up',
            tone: '#fff1f2',
            color: '#be123c',
            count: highCount,
            items: [
                'Schedule an urgent manager check-in within 48 hours.',
                'Review workload, compensation, and growth opportunities.',
                'Document a clear retention action owner and target date.'
            ]
        },
        {
            title: 'Medium Risk Follow-up',
            tone: '#fff7ed',
            color: '#b45309',
            count: mediumCount,
            items: [
                'Offer career development feedback and growth planning.',
                'Monitor engagement, attendance, and performance trends.',
                'Prepare a targeted retention conversation with the manager.'
            ]
        },
        {
            title: 'Low Risk Follow-up',
            tone: '#ecfdf5',
            color: '#047857',
            count: lowCount,
            items: [
                'Maintain regular recognition and communication touchpoints.',
                'Keep development opportunities visible and current.',
                'Continue standard retention engagement checks.'
            ]
        }
    ];

    return `
        <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 10px 20px rgba(16, 24, 40, 0.06); margin-bottom: 30px;">
            <div style="margin-bottom: 18px;">
                <h3 style="margin: 0; font-size: 20px; color: #111827;">Recommended Retention Actions</h3>
                <p style="margin: 6px 0 0; color: #475569; font-size: 14px;">Action suggestions aligned to the current at-risk employee distribution.</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                ${cards.map(card => `
                    <div style="background: ${card.tone}; border-left: 4px solid ${card.color}; border-radius: 14px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 12px;">
                            <div style="font-size: 15px; font-weight: 700; color: #111827;">${card.title}</div>
                            <span style="padding: 6px 10px; border-radius: 999px; background: white; color: ${card.color}; font-weight: 700; font-size: 12px;">${card.count}</span>
                        </div>
                        <ul style="margin: 0; padding-left: 18px; color: #334155; line-height: 1.8;">
                            ${card.items.map(item => `<li>${item}</li>`).join('')}
                        </ul>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function loadRetentionTrackerActions(riskEmployees) {
    const employeeIds = riskEmployees.map(emp => emp.id).filter(Boolean);
    if (!employeeIds.length) {
        return [];
    }

    return fetch(`./api/wfa/get_retention_actions.php?employee_ids=${employeeIds.join(',')}`)
        .then(response => response.ok ? response.json() : { success: false, data: [] })
        .then(result => (result.success ? (result.data || []) : []))
        .catch(() => []);
}

function renderRetentionSummary(savedActions = []) {
    if (!Array.isArray(savedActions) || savedActions.length === 0) {
        return '';
    }

    const pendingCount = savedActions.filter(action => String(action.status || '').toLowerCase() === 'pending').length;
    const inProgressCount = savedActions.filter(action => String(action.status || '').toLowerCase() === 'in progress').length;
    const completedCount = savedActions.filter(action => String(action.status || '').toLowerCase() === 'completed').length;

    return `
        <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 10px 20px rgba(16, 24, 40, 0.06); margin-bottom: 30px;">
            <div style="margin-bottom: 18px;">
                <h3 style="margin: 0; font-size: 20px; color: #111827;">Retention Status Summary</h3>
                <p style="margin: 6px 0 0; color: #475569; font-size: 14px;">Quick view of current action follow-up workload.</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                <div style="padding: 18px; background: #fef2f2; border-left: 4px solid #dc2626; border-radius: 14px;">
                    <div style="font-size: 14px; color: #991b1b; font-weight: 700; margin-bottom: 8px;">Pending</div>
                    <div style="font-size: 30px; font-weight: 800; color: #7f1d1d;">${pendingCount}</div>
                </div>
                <div style="padding: 18px; background: #fff7ed; border-left: 4px solid #d97706; border-radius: 14px;">
                    <div style="font-size: 14px; color: #9a5b00; font-weight: 700; margin-bottom: 8px;">In Progress</div>
                    <div style="font-size: 30px; font-weight: 800; color: #92400e;">${inProgressCount}</div>
                </div>
                <div style="padding: 18px; background: #ecfdf5; border-left: 4px solid #16a34a; border-radius: 14px;">
                    <div style="font-size: 14px; color: #166534; font-weight: 700; margin-bottom: 8px;">Completed</div>
                    <div style="font-size: 30px; font-weight: 800; color: #14532d;">${completedCount}</div>
                </div>
            </div>
        </div>
    `;
}

function renderRetentionSummary(savedActions = [], riskEmployees = []) {
    const hasActions = Array.isArray(savedActions) && savedActions.length > 0;
    const hasRiskEmployees = Array.isArray(riskEmployees) && riskEmployees.length > 0;

    if (!hasActions && !hasRiskEmployees) {
        return '';
    }

    const pendingCount = Array.isArray(savedActions)
        ? savedActions.filter(action => String(action.status || '').toLowerCase() === 'pending').length
        : 0;
    const inProgressCount = Array.isArray(savedActions)
        ? savedActions.filter(action => String(action.status || '').toLowerCase() === 'in progress').length
        : 0;
    const completedCount = Array.isArray(savedActions)
        ? savedActions.filter(action => String(action.status || '').toLowerCase() === 'completed').length
        : 0;

    const riskCountMap = {
        high: 0,
        medium: 0,
        low: 0
    };

    if (hasRiskEmployees) {
        riskEmployees.forEach(emp => {
            const riskLabel = String(emp.risk_level || 'Low').toLowerCase();
            if (riskLabel.includes('high')) {
                riskCountMap.high += 1;
            } else if (riskLabel.includes('medium')) {
                riskCountMap.medium += 1;
            } else {
                riskCountMap.low += 1;
            }
        });
    }

    return `
        <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 10px 20px rgba(16, 24, 40, 0.06); margin-bottom: 20px;">
            <div style="margin-bottom: 18px;">
                <h3 style="margin: 0; font-size: 20px; color: #111827;">Retention Status Summary</h3>
                <p style="margin: 6px 0 0; color: #475569; font-size: 14px;">Operational view of current retention workload and at-risk concentration.</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 18px;">
                <div style="padding: 18px; background: #fff7ed; border-radius: 14px; border-left: 4px solid #f59e0b;">
                    <div style="font-size: 13px; color: #9a5b00; font-weight: 700; text-transform: uppercase;">Pending</div>
                    <div style="font-size: 28px; font-weight: 800; color: #9a5b00; margin-top: 8px;">${pendingCount}</div>
                </div>
                <div style="padding: 18px; background: #eff6ff; border-radius: 14px; border-left: 4px solid #2563eb;">
                    <div style="font-size: 13px; color: #1d4ed8; font-weight: 700; text-transform: uppercase;">In Progress</div>
                    <div style="font-size: 28px; font-weight: 800; color: #1d4ed8; margin-top: 8px;">${inProgressCount}</div>
                </div>
                <div style="padding: 18px; background: #ecfdf5; border-radius: 14px; border-left: 4px solid #059669;">
                    <div style="font-size: 13px; color: #047857; font-weight: 700; text-transform: uppercase;">Completed</div>
                    <div style="font-size: 28px; font-weight: 800; color: #047857; margin-top: 8px;">${completedCount}</div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                <div style="padding: 18px; background: #fef2f2; border-radius: 14px; border-left: 4px solid #dc2626;">
                    <div style="font-size: 13px; color: #991b1b; font-weight: 700; text-transform: uppercase;">High Risk</div>
                    <div style="font-size: 28px; font-weight: 800; color: #7f1d1d; margin-top: 8px;">${riskCountMap.high}</div>
                </div>
                <div style="padding: 18px; background: #fff7ed; border-radius: 14px; border-left: 4px solid #f59e0b;">
                    <div style="font-size: 13px; color: #9a5b00; font-weight: 700; text-transform: uppercase;">Medium Risk</div>
                    <div style="font-size: 28px; font-weight: 800; color: #9a5b00; margin-top: 8px;">${riskCountMap.medium}</div>
                </div>
                <div style="padding: 18px; background: #ecfdf5; border-radius: 14px; border-left: 4px solid #059669;">
                    <div style="font-size: 13px; color: #047857; font-weight: 700; text-transform: uppercase;">Low Risk</div>
                    <div style="font-size: 28px; font-weight: 800; color: #047857; margin-top: 8px;">${riskCountMap.low}</div>
                </div>
            </div>
        </div>
    `;
}

function renderTopPriorityRetentionCases(riskEmployees = [], savedActions = []) {
    if (!Array.isArray(riskEmployees) || riskEmployees.length === 0) {
        return '';
    }

    const actionMap = {};
    if (Array.isArray(savedActions)) {
        savedActions.forEach(action => {
            if (action.employee_id) {
                actionMap[action.employee_id] = action;
            }
        });
    }

    const priorityCases = riskEmployees
        .slice()
        .map(emp => {
            const action = actionMap[emp.id] || {};
            const riskLevel = String(emp.risk_level || 'Low').toLowerCase();
            const riskPriority = riskLevel.includes('high') ? 3 : riskLevel.includes('medium') ? 2 : 1;
            const overdueWeight = String(action.status || 'Pending').toLowerCase() === 'pending' ? 2 : 1;
            const dueDate = action.due_date ? new Date(action.due_date) : null;
            const dueDateValue = dueDate && !isNaN(dueDate.getTime()) ? dueDate.getTime() : Number.MAX_SAFE_INTEGER;
            const name = emp.name || 'Unknown Employee';

            return {
                name,
                riskLevel: riskLevel.includes('high') ? 'High' : riskLevel.includes('medium') ? 'Medium' : 'Low',
                status: action.status || 'Pending',
                dueDate: action.due_date || 'No due date',
                score: riskPriority * 10 + overdueWeight + (dueDateValue === Number.MAX_SAFE_INTEGER ? 0 : (Number.MAX_SAFE_INTEGER - dueDateValue) / 100000000),
                employeeId: emp.id
            };
        })
        .sort((a, b) => b.score - a.score)
        .slice(0, 5);

    const itemsHtml = priorityCases.map(item => {
        const severityColor = item.riskLevel === 'High' ? '#dc2626' : item.riskLevel === 'Medium' ? '#d97706' : '#059669';
        return `
            <div
                onclick="jumpToRetentionTrackerCase(${item.employeeId})"
                style="padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 12px; background: #f8fafc; margin-bottom: 10px; cursor: pointer; transition: transform 0.16s ease, box-shadow 0.16s ease;"
                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 10px 24px rgba(15, 23, 42, 0.08)';"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';"
            >
                <div style="display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 6px;">
                    <strong style="font-size: 15px; color: #111827;">${item.name}</strong>
                    <span style="padding: 4px 10px; border-radius: 999px; background: ${severityColor}22; color: ${severityColor}; font-weight: 700; font-size: 12px;">${item.riskLevel} Risk</span>
                </div>
                <div style="font-size: 13px; color: #475569; margin-bottom: 2px;">Status: ${item.status}</div>
                <div style="font-size: 13px; color: #475569;">Due date: ${item.dueDate}</div>
            </div>
        `;
    }).join('');

    return `
        <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 10px 20px rgba(16, 24, 40, 0.06); margin-bottom: 30px;">
            <div style="margin-bottom: 18px;">
                <h3 style="margin: 0; font-size: 20px; color: #111827;">Top Priority Retention Cases</h3>
                <p style="margin: 6px 0 0; color: #475569; font-size: 14px;">Most urgent employees to review first based on risk and tracker status.</p>
            </div>
            ${itemsHtml}
        </div>
    `;
}

function jumpToRetentionTrackerCase(employeeId) {
    if (!employeeId) {
        return;
    }

    const row = document.querySelector(`tr[data-retention-employee-id="${employeeId}"]`);
    if (!row) {
        return;
    }

    row.style.outline = '3px solid #2563eb';
    row.style.outlineOffset = '2px';
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });

    setTimeout(() => {
        row.style.outline = '';
        row.style.outlineOffset = '';
    }, 2600);
}

function exportRetentionAuditCsv() {
    const savedActions = Array.isArray(window.__retentionAuditData) ? window.__retentionAuditData : [];
    const riskEmployees = Array.isArray(window.__retentionAuditEmployees) ? window.__retentionAuditEmployees : [];
    const employeeMap = {};

    riskEmployees.forEach(emp => {
        employeeMap[emp.id] = emp.name || 'Unknown';
    });

    if (!savedActions.length) {
        showAttritionSuccessToast('No retention activity available to export.');
        return;
    }

    const rows = [
        ['Updated', 'Employee', 'Action Type', 'Status', 'Notes']
    ];

    savedActions
        .slice()
        .sort((a, b) => new Date(b.updated_at || b.created_at || 0) - new Date(a.updated_at || a.created_at || 0))
        .forEach(action => {
            const updatedAt = action.updated_at ? new Date(action.updated_at).toISOString() : 'N/A';
            const employeeName = employeeMap[action.employee_id] || `Employee #${action.employee_id}`;
            rows.push([
                updatedAt,
                employeeName,
                action.action_type || 'Counseling',
                action.status || 'Pending',
                (action.notes || 'No notes provided').replace(/\n/g, ' ')
            ]);
        });

    const csvContent = rows.map(row => row.map(value => `"${String(value).replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'retention_activity_audit.csv';
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(link.href);
    showAttritionSuccessToast('Retention audit exported as CSV.');
}

function renderRetentionHistory(savedActions = [], riskEmployees = []) {
    if (!Array.isArray(savedActions) || savedActions.length === 0) {
        return '';
    }

    const employeeMap = {};
    riskEmployees.forEach(emp => {
        employeeMap[emp.id] = emp.name || 'Unknown';
    });

    const historyRows = savedActions
        .slice()
        .sort((a, b) => new Date(b.updated_at || b.created_at || 0) - new Date(a.updated_at || a.created_at || 0))
        .map(action => {
            const employeeName = employeeMap[action.employee_id] || `Employee #${action.employee_id}`;
            const updatedAt = action.updated_at ? new Date(action.updated_at).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }) : 'N/A';
            const notes = action.notes || 'No notes provided';
            return `
                <tr>
                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${updatedAt}</td>
                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${employeeName}</td>
                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${action.action_type || 'Counseling'}</td>
                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${action.status || 'Pending'}</td>
                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${notes}</td>
                </tr>
            `;
        }).join('');

    return `
        <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 10px 20px rgba(16, 24, 40, 0.06); margin-bottom: 30px;">
            <div style="margin-bottom: 18px;">
                <h3 style="margin: 0; font-size: 20px; color: #111827;">Retention Activity Audit</h3>
                <p style="margin: 6px 0 0; color: #475569; font-size: 14px;">Review every saved tracker update, owner notes, and action status.</p>
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; align-items: center;">
                <input id="retentionHistorySearch" type="text" placeholder="Search employee, action, or notes" style="flex: 1 1 260px; min-width: 220px; padding: 10px 12px; border-radius: 10px; border: 1px solid #cbd5e1;">
                <select id="retentionHistoryStatusFilter" style="padding: 10px 12px; border-radius: 10px; border: 1px solid #cbd5e1; min-width: 180px;">
                    <option value="all">All statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <button type="button" onclick="clearRetentionHistoryFilters()" style="padding: 10px 14px; border: none; border-radius: 10px; background: #64748b; color: white; font-weight: 700; cursor: pointer;">Clear Filters</button>
                <button type="button" onclick="exportRetentionAuditCsv()" style="padding: 10px 14px; border: none; border-radius: 10px; background: #0f766e; color: white; font-weight: 700; cursor: pointer;">Export CSV</button>
            </div>
            <div style="overflow-x: auto;">
                <table class="wfa-table" style="width: 100%; border-collapse: collapse; border-spacing: 0; min-width: 760px;">
                    <thead>
                        <tr style="background: #f8fafc; color: #102a43; text-align: left;">
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Updated</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Employee</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Action Type</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Status</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Notes</th>
                        </tr>
                    </thead>
                    <tbody id="retentionHistoryBody">
                        ${historyRows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function clearRetentionHistoryFilters() {
    const searchInput = document.getElementById('retentionHistorySearch');
    const statusFilter = document.getElementById('retentionHistoryStatusFilter');
    if (searchInput) {
        searchInput.value = '';
    }
    if (statusFilter) {
        statusFilter.value = 'all';
    }
    if (typeof window.__retentionAuditData !== 'undefined' && Array.isArray(window.__retentionAuditData)) {
        setupRetentionHistoryFilters(window.__retentionAuditData, window.__retentionAuditEmployees || []);
    }
}

function setupRetentionHistoryFilters(savedActions = [], riskEmployees = []) {
    const searchInput = document.getElementById('retentionHistorySearch');
    const statusFilter = document.getElementById('retentionHistoryStatusFilter');
    const tbody = document.getElementById('retentionHistoryBody');

    if (!searchInput || !statusFilter || !tbody || !Array.isArray(savedActions)) {
        return;
    }

    const employeeMap = {};
    riskEmployees.forEach(emp => {
        employeeMap[emp.id] = emp.name || 'Unknown';
    });

    const applyFilters = () => {
        const query = (searchInput.value || '').toLowerCase().trim();
        const filterStatus = (statusFilter.value || 'all').toLowerCase();

        const filteredRows = savedActions
            .slice()
            .sort((a, b) => new Date(b.updated_at || b.created_at || 0) - new Date(a.updated_at || a.created_at || 0))
            .filter(action => {
                const employeeName = (employeeMap[action.employee_id] || `Employee #${action.employee_id}`).toLowerCase();
                const actionType = String(action.action_type || 'Counseling').toLowerCase();
                const status = String(action.status || 'Pending').toLowerCase();
                const notes = String(action.notes || '').toLowerCase();
                const matchesQuery = !query || `${employeeName} ${actionType} ${status} ${notes}`.includes(query);
                const matchesStatus = filterStatus === 'all' || status === filterStatus;
                return matchesQuery && matchesStatus;
            });

        tbody.innerHTML = filteredRows.map(action => {
            const employeeName = employeeMap[action.employee_id] || `Employee #${action.employee_id}`;
            const updatedAt = action.updated_at ? new Date(action.updated_at).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }) : 'N/A';
            const notes = action.notes || 'No notes provided';

            return `
                <tr>
                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${updatedAt}</td>
                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${employeeName}</td>
                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${action.action_type || 'Counseling'}</td>
                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${action.status || 'Pending'}</td>
                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${notes}</td>
                </tr>
            `;
        }).join('');

        if (!filteredRows.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" style="padding: 20px; text-align: center; color: #64748b;">No matching retention activity found.</td>
                </tr>
            `;
        }
    };

    searchInput.addEventListener('input', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    applyFilters();
}

function renderRetentionTracker(riskEmployees, savedActions = []) {
    if (!Array.isArray(riskEmployees) || riskEmployees.length === 0) {
        return '';
    }

    const savedActionMap = {};
    savedActions.forEach(action => {
        if (action.employee_id) {
            savedActionMap[action.employee_id] = action;
        }
    });

    const rows = riskEmployees.slice(0, 8).map((emp, index) => {
        const riskLevel = String(emp.risk_level || 'Low');
        const actionType = riskLevel.toLowerCase().includes('high')
            ? 'Counseling'
            : riskLevel.toLowerCase().includes('medium')
                ? 'Mentoring'
                : 'Training';
        const owner = riskLevel.toLowerCase().includes('high')
            ? 'HRBP + Department Head'
            : riskLevel.toLowerCase().includes('medium')
                ? 'Immediate Supervisor'
                : 'HR Generalist';
        const dueDate = new Date();
        dueDate.setDate(dueDate.getDate() + (index + 3));
        const dueDateString = dueDate.toISOString().split('T')[0];
        const savedAction = savedActionMap[emp.id] || null;
        const savedStatus = savedAction?.status || 'Pending';
        const savedNotes = savedAction?.notes || '';

        return `
            <tr data-retention-employee-id="${emp.id || 0}">
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${emp.name || 'Unknown'}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${riskLevel}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${savedAction?.action_type || actionType}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${owner}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${savedAction?.due_date || dueDateString}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">
                    <select
                        data-employee-id="${emp.id || 0}"
                        data-risk-level="${riskLevel}"
                        data-action-type="${savedAction?.action_type || actionType}"
                        data-owner="${owner}"
                        data-due-date="${savedAction?.due_date || dueDateString}"
                        data-status="${savedStatus}"
                        data-attrition-track-index="${index}"
                        onchange="saveRetentionStatus(this)"
                        style="padding: 8px 10px; border-radius: 8px; border: 1px solid #d1d5db; min-width: 160px;"
                    >
                        <option value="Pending" ${savedStatus === 'Pending' ? 'selected' : ''}>Pending</option>
                        <option value="In Progress" ${savedStatus === 'In Progress' ? 'selected' : ''}>In Progress</option>
                        <option value="Completed" ${savedStatus === 'Completed' ? 'selected' : ''}>Completed</option>
                    </select>
                </td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">
                    <textarea
                        data-employee-id="${emp.id || 0}"
                        data-risk-level="${riskLevel}"
                        data-action-type="${savedAction?.action_type || actionType}"
                        data-owner="${owner}"
                        data-due-date="${savedAction?.due_date || dueDateString}"
                        data-status="${savedStatus}"
                        rows="2"
                        onblur="saveRetentionNotes(this)"
                        style="width: 100%; min-width: 240px; padding: 10px 12px; border-radius: 10px; border: 1px solid #d1d5db; resize: vertical;"
                    >${savedNotes}</textarea>
                </td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">
                    <button
                        type="button"
                        onclick="markRetentionCaseReviewed(this)"
                        data-employee-id="${emp.id || 0}"
                        data-risk-level="${riskLevel}"
                        data-action-type="${savedAction?.action_type || actionType}"
                        data-owner="${owner}"
                        data-due-date="${savedAction?.due_date || dueDateString}"
                        style="padding: 8px 12px; border: none; border-radius: 10px; background: #2563eb; color: white; font-weight: 700; cursor: pointer;"
                    >
                        Mark Reviewed
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    return `
        <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 10px 20px rgba(16, 24, 40, 0.06); margin-bottom: 30px;">
            <div style="margin-bottom: 18px;">
                <h3 style="margin: 0; font-size: 20px; color: #111827;">Retention Action Tracker</h3>
                <p style="margin: 6px 0 0; color: #475569; font-size: 14px;">Manage ownership, due dates, follow-up notes, and status for each retention case.</p>
            </div>
            <div style="overflow-x: auto;">
                <table class="wfa-table" style="width: 100%; border-collapse: collapse; border-spacing: 0; min-width: 980px;">
                    <thead>
                        <tr style="background: #f8fafc; color: #102a43; text-align: left;">
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Employee</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Risk Level</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Action Type</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Owner</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Due Date</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Status</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Follow-up Notes</th>
                            <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

async function persistRetentionAction(payload) {
    const response = await fetch('./api/wfa/save_retention_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Unable to save tracker update');
    }

    return result;
}

async function saveRetentionStatus(selectElement) {
    const employeeId = Number(selectElement.getAttribute('data-employee-id'));
    const riskLevel = selectElement.getAttribute('data-risk-level') || 'Low';
    const actionType = selectElement.getAttribute('data-action-type') || 'Counseling';
    const owner = selectElement.getAttribute('data-owner') || 'HR';
    const dueDate = selectElement.getAttribute('data-due-date') || new Date().toISOString().split('T')[0];
    const status = selectElement.value;
    const row = selectElement.closest('tr');
    const notesField = row ? row.querySelector('textarea[data-employee-id="' + employeeId + '"]') : null;
    const notes = notesField ? notesField.value.trim() : `Owner: ${owner} | Status: ${status}`;

    if (!employeeId) {
        showAttritionSuccessToast('Employee id is missing for the tracker save.');
        return;
    }

    try {
        await persistRetentionAction({
            employee_id: employeeId,
            action_type: actionType,
            description: `Retention tracker update for ${riskLevel} risk employee`,
            status,
            assigned_to: owner,
            due_date: dueDate,
            notes: notes || `Owner: ${owner} | Status: ${status}`
        });

        showAttritionSuccessToast(`Tracker saved as ${status}.`);
    } catch (error) {
        console.error('Retention tracker save error:', error);
        showAttritionSuccessToast(error.message || 'Unable to save tracker update');
    }
}

async function saveRetentionNotes(textareaElement) {
    const employeeId = Number(textareaElement.getAttribute('data-employee-id'));
    const riskLevel = textareaElement.getAttribute('data-risk-level') || 'Low';
    const actionType = textareaElement.getAttribute('data-action-type') || 'Counseling';
    const owner = textareaElement.getAttribute('data-owner') || 'HR';
    const dueDate = textareaElement.getAttribute('data-due-date') || new Date().toISOString().split('T')[0];
    const row = textareaElement.closest('tr');
    const statusSelect = row ? row.querySelector('select[data-employee-id="' + employeeId + '"]') : null;
    const status = statusSelect ? statusSelect.value : textareaElement.getAttribute('data-status') || 'Pending';
    const notes = textareaElement.value.trim();

    if (!employeeId) {
        showAttritionSuccessToast('Employee id is missing for the tracker note save.');
        return;
    }

    try {
        await persistRetentionAction({
            employee_id: employeeId,
            action_type: actionType,
            description: `Retention tracker update for ${riskLevel} risk employee`,
            status,
            assigned_to: owner,
            due_date: dueDate,
            notes: notes || `Owner: ${owner} | Status: ${status}`
        });

        showAttritionSuccessToast('Follow-up note saved.');
    } catch (error) {
        console.error('Retention tracker note save error:', error);
        showAttritionSuccessToast(error.message || 'Unable to save follow-up note');
    }
}

async function markRetentionCaseReviewed(buttonElement) {
    const employeeId = Number(buttonElement.getAttribute('data-employee-id'));
    const riskLevel = buttonElement.getAttribute('data-risk-level') || 'Low';
    const actionType = buttonElement.getAttribute('data-action-type') || 'Counseling';
    const owner = buttonElement.getAttribute('data-owner') || 'HR';
    const dueDate = buttonElement.getAttribute('data-due-date') || new Date().toISOString().split('T')[0];
    const row = buttonElement.closest('tr');
    const statusSelect = row ? row.querySelector('select[data-employee-id="' + employeeId + '"]') : null;
    const notesField = row ? row.querySelector('textarea[data-employee-id="' + employeeId + '"]') : null;
    const status = statusSelect ? statusSelect.value : 'Pending';
    const baseNotes = notesField ? notesField.value.trim() : '';
    const reviewStamp = `Reviewed on ${new Date().toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`;
    const notes = [baseNotes, reviewStamp].filter(Boolean).join('\n');

    if (!employeeId) {
        showAttritionSuccessToast('Employee id is missing for the review action.');
        return;
    }

    try {
        await persistRetentionAction({
            employee_id: employeeId,
            action_type: actionType,
            description: `Retention case review for ${riskLevel} risk employee`,
            status: 'Completed',
            assigned_to: owner,
            due_date: dueDate,
            notes: notes || `Reviewed on ${new Date().toLocaleString('en-US')}`
        });

        if (statusSelect) {
            statusSelect.value = 'Completed';
        }
        if (notesField) {
            notesField.value = notes;
        }

        showAttritionSuccessToast('Retention case marked as reviewed.');
    } catch (error) {
        console.error('Retention review error:', error);
        showAttritionSuccessToast(error.message || 'Unable to mark case as reviewed');
    }
}

function generateAttritionRecommendations(data) {
    const recommendations = [];
    const topReasons = data.top_reasons || [];
    const attritionRate = data.attrition_rate || 0;
    const totalSeparated = data.total_separated || (Array.isArray(data.separated_employees) ? data.separated_employees.length : 0);

    if (totalSeparated >= 20) {
        recommendations.push({
            severity: 'critical',
            insight: `${totalSeparated} employees separated this year`,
            action: 'Initiate urgent review of separation drivers, exit interviews, and retention actions.'
        });
    } else if (totalSeparated >= 10) {
        recommendations.push({
            severity: 'high',
            insight: `${totalSeparated} employees separated this year`,
            action: 'Review recent separations and strengthen retention outreach with affected groups.'
        });
    } else if (totalSeparated > 0) {
        recommendations.push({
            severity: 'medium',
            insight: `${totalSeparated} employees separated this year`,
            action: 'Monitor separation trends and continue proactive retention efforts.'
        });
    }

    if (attritionRate > 20) {
        recommendations.push({
            severity: 'critical',
            insight: `High attrition rate of ${attritionRate.toFixed(1)}%`,
            action: 'Conduct immediate employee retention audit and exit interviews'
        });
    } else if (attritionRate > 15) {
        recommendations.push({
            severity: 'warning',
            insight: `Elevated attrition rate of ${attritionRate.toFixed(1)}%`,
            action: 'Review retention strategies and employee engagement programs'
        });
    }

    if (topReasons.length > 0 && topReasons[0].reason) {
        const topReason = topReasons[0];
        if (topReason.reason.toLowerCase().includes('salary')) {
            recommendations.push({
                severity: 'critical',
                insight: `Top resignation reason: ${topReason.reason} (${topReason.count} cases)`,
                action: 'Conduct market salary analysis and review compensation package'
            });
        } else if (topReason.reason.toLowerCase().includes('career')) {
            recommendations.push({
                severity: 'high',
                insight: `Key concern: ${topReason.reason} (${topReason.count} cases)`,
                action: 'Strengthen career development programs and promotion pathways'
            });
        }
    }

    if (recommendations.length === 0) {
        recommendations.push({
            severity: 'info',
            insight: `${totalSeparated} separated employees are within expected range`,
            action: 'Continue current retention initiatives and monitor trends'
        });
    }

    return recommendations;
}

function openAttritionRecommendationModal(index) {
    const recommendation = window.attritionRecommendations && window.attritionRecommendations[index];
    if (!recommendation) return;

    document.getElementById('modalRecommendationInsight').textContent = recommendation.insight;
    document.getElementById('modalRecommendationAction').textContent = recommendation.action;
    populateActionTitleOptions(recommendation.action);
    document.getElementById('modalActionNotes').value = recommendation.action;

    const modal = document.getElementById('attritionRecommendationModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
    }
}

function populateActionTitleOptions(selectedAction) {
    const actionSelect = document.getElementById('modalActionTitle');
    if (!actionSelect) return;

    const defaultOptions = [
        'Review recent separations and strengthen retention outreach with affected groups.',
        'Conduct immediate employee retention audit and exit interviews',
        'Review retention strategies and employee engagement programs',
        'Strengthen career development programs and promotion pathways',
        'Continue current retention initiatives and monitor trends'
    ];

    const options = Array.from(new Set([selectedAction, ...defaultOptions]));
    actionSelect.innerHTML = options.map(option => {
        const escaped = option.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return `<option value="${escaped}" ${option === selectedAction ? 'selected' : ''}>${escaped}</option>`;
    }).join('');
}

function closeAttritionRecommendationModal() {
    const modal = document.getElementById('attritionRecommendationModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
    }
}

function sendAttritionRecommendationToHR() {
    const modal = document.getElementById('attritionRecommendationModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
    }
    closeAttritionRecommendationModal();
    showAttritionSuccessToast('Recommendation sent to HR successfully!');
}

function openRecruitmentModal() {
    const modal = document.getElementById('recruitmentModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
    }
}

function closeRecruitmentModal() {
    const modal = document.getElementById('recruitmentModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
    }
}

function submitRecruitmentForm() {
    const role = document.getElementById('recruitmentRole')?.value.trim();
    const department = document.getElementById('recruitmentDepartment')?.value.trim();
    const hiringManager = document.getElementById('recruitmentHiringManager')?.value.trim();
    const openings = document.getElementById('recruitmentOpenings')?.value.trim();
    const startDate = document.getElementById('recruitmentStartDate')?.value;
    const notes = document.getElementById('recruitmentNotes')?.value.trim();

    if (!role || !department || !hiringManager || !openings) {
        alert('Please complete all required recruitment fields.');
        return;
    }

    closeRecruitmentModal();
    showAttritionSuccessToast('Recruitment request submitted successfully!');
}

function showAttritionSuccessToast(message) {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.position = 'fixed';
    toast.style.bottom = '24px';
    toast.style.right = '24px';
    toast.style.zIndex = '10001';
    toast.style.padding = '14px 18px';
    toast.style.background = '#16a34a';
    toast.style.color = '#ffffff';
    toast.style.borderRadius = '12px';
    toast.style.boxShadow = '0 16px 36px rgba(15, 23, 42, 0.24)';
    toast.style.fontSize = '14px';
    toast.style.fontWeight = '600';
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.25s ease-in-out';
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.opacity = '1';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, 2400);
}

function renderAttritionPage(data, year, riskEmployees = [], savedActions = []) {
    const container = document.getElementById('attritionContainer');
    window.__retentionAuditData = savedActions;
    window.__retentionAuditEmployees = riskEmployees;
    const attritionData = data.attrition_data || [];
    const separatedEmployees = Array.isArray(data.separated_employees) ? data.separated_employees : [];
    const recentSeparatedEmployees = separatedEmployees.slice(0, 50);
    separatedEmployeesCache = separatedEmployees;
    const topReasons = data.top_reasons || [];
    const totalSeparated = data.total_separated || separatedEmployees.length;
    const attritionRate = data.attrition_rate || 0;

    const resignationTypeTotals = attritionData.reduce((acc, item) => {
        const type = item.resignation_type || 'Unknown';
        acc[type] = (acc[type] || 0) + (parseInt(item.count, 10) || 0);
        return acc;
    }, {});

    const jobVacancies = Array.isArray(data.job_vacancies) ? data.job_vacancies : [
        {
            job_id: 'VAC-101',
            title: 'Senior HR Specialist',
            department: 'Human Resources',
            hiring_manager: 'Alicia Mendoza',
            openings: 2,
            status: 'Open',
            posted_date: '2026-07-12'
        },
        {
            job_id: 'VAC-102',
            title: 'Payroll Analyst',
            department: 'Finance',
            hiring_manager: 'Mark Santos',
            openings: 1,
            status: 'Interviewing',
            posted_date: '2026-07-15'
        },
        {
            job_id: 'VAC-103',
            title: 'Operations Coordinator',
            department: 'Operations',
            hiring_manager: 'Liza Gomez',
            openings: 3,
            status: 'Open',
            posted_date: '2026-07-22'
        },
        {
            job_id: 'VAC-104',
            title: 'Sales Executive',
            department: 'Sales',
            hiring_manager: 'Ramon Cruz',
            openings: 1,
            status: 'Filled',
            posted_date: '2026-07-08'
        }
    ];

    const months = [...new Set(attritionData.map(item => item.month))].sort();
    const monthlyTotals = months.map(month => {
        return attritionData
            .filter(item => item.month === month)
            .reduce((sum, item) => sum + (parseInt(item.count, 10) || 0), 0);
    });

    container.innerHTML = `
        <style>
            #attritionContainer .wfa-table-container {
                overflow-x: auto;
            }
            #attritionContainer .wfa-table {
                width: 100%;
                table-layout: fixed;
            }
            #attritionContainer .wfa-table th,
            #attritionContainer .wfa-table td {
                white-space: normal !important;
                word-break: break-word;
            }
            #attritionContainer .wfa-table thead th {
                white-space: normal !important;
            }
        </style>
        <div class="wfa-dashboard-hero" style="margin-bottom: 24px; padding: 24px; background: #ffffff; border-radius: 16px; box-shadow: 0 12px 24px rgba(33, 40, 82, 0.06);">
            <h2 style="margin: 0 0 10px; font-size: 28px; color: #253858;">Attrition & Turnover Analysis</h2>


        <div class="wfa-metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; align-items: stretch;">
            <div class="wfa-metric-card success" style="padding: 20px; background: #ffffff; color: #1f2937; border-left: 10px solid #009688; border-radius: 14px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);">
                <div style="font-size: 18px; color: #1f2937; margin-bottom: 8px;">Total Attrition</div>
                <div style="font-size: 34px; font-weight: 700; color: #0f172a;">${totalSeparated}</div>
                <div style="color: #475569; margin-top: 8px;">Employees who left in ${year}</div>
            </div>
            <div class="wfa-metric-card danger" style="padding: 20px; background: #ffffff; color: #1f2937; border-left: 10px solid #f44336; border-radius: 14px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);">
                <div style="font-size: 18px; color: #1f2937; margin-bottom: 8px;">Turnover Rate</div>
                <div style="font-size: 34px; font-weight: 700; color: #0f172a;">${attritionRate}%</div>
                <div style="color: #475569; margin-top: 8px;">Left vs active workforce</div>
            </div>
            <div class="wfa-metric-card info" style="padding: 20px; background: #ffffff; color: #1f2937; border-left: 10px solid #2563eb; border-radius: 14px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);">
                <div style="font-size: 18px; color: #1f2937; margin-bottom: 8px;">Job Vacancies</div>
                <div style="font-size: 34px; font-weight: 700; color: #0f172a;">${(data.job_vacancies && data.job_vacancies.length) || data.open_positions || 0}</div>
                <div style="color: #475569; margin-top: 8px;">Current open roles</div>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <div onclick="openRecruitmentModal()" role="button" tabindex="0" title="Open recruitment modal" onkeypress="if(event.key==='Enter'||event.key===' ') openRecruitmentModal();" style="display: flex; gap: 16px; align-items: center; padding: 20px 22px; border-radius: 24px; background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; color: #1f2937; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05); cursor: pointer;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 18px; background: #fff7ed; color: #c2410c; font-size: 20px;">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 16px; font-weight: 800; color: #1f2937; margin-bottom: 6px;">HR Recommendations</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; font-size: 14px; color: #334155; line-height: 1.6;">
                        <span style="font-weight: 700; color: #c2410c;">Insight:</span>
                        <span>Gender imbalance: 71.4% Male</span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; font-size: 14px; color: #334155; line-height: 1.6; margin-top: 4px;">
                        <span style="font-weight: 700; color: #1f2937;">Action:</span>
                        <span>Implement inclusive hiring initiatives and review recruitment strategies</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="wfa-table-container" style="background: white; padding: 24px; border-radius: 14px; box-shadow: 0 10px 20px rgba(16, 24, 40, 0.06);">
            <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px; align-items: center; margin-bottom: 18px;">
                <div>
                    <h3 style="margin: 0; font-size: 20px;">Recent Separated Employees</h3>
                    <p style="margin: 0; color: #667085;">Showing ${recentSeparatedEmployees.length} recent exits from the current dataset.</p>
                </div>
                <div style="flex: 1 1 320px; max-width: 520px; display: flex; gap: 8px; align-items: stretch; justify-content: flex-end;">
                    <input id="employeeSearchInput" type="search" placeholder="Search employees by name, department, position, or reason" style="flex: 1; padding: 12px 14px; border: 1px solid #ccd0d8; border-radius: 10px; outline: none;" oninput="filterSeparatedEmployees()" />
                    <button type="button" class="wfa-btn wfa-btn-secondary" onclick="clearEmployeeSearch()" style="padding: 12px 16px; border-radius: 10px; white-space: nowrap;">Clear</button>
                </div>
            </div>
            <div style="width: 100%; max-height: 520px; overflow-x: auto; overflow-y: auto;">
                <table class="wfa-table" style="width: 100%; min-width: 760px; border-collapse: collapse; border-spacing: 0;">
                <thead>
                    <tr style="background: #f8fafc; color: #102a43; text-align: left;">
                        <th style="position: sticky; top: 0; background: #f8fafc; z-index: 2; padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">ID</th>
                        <th style="position: sticky; top: 0; background: #f8fafc; z-index: 2; padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Name</th>
                        <th style="position: sticky; top: 0; background: #f8fafc; z-index: 2; padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Department</th>
                        <th style="position: sticky; top: 0; background: #f8fafc; z-index: 2; padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Position</th>
                        <th style="position: sticky; top: 0; background: #f8fafc; z-index: 2; padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Reason</th>
                        <th style="position: sticky; top: 0; background: #f8fafc; z-index: 2; padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Category</th>
                        <th style="position: sticky; top: 0; background: #f8fafc; z-index: 2; padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Notice Date</th>
                        <th style="position: sticky; top: 0; background: #f8fafc; z-index: 2; padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Last Working Day</th>
                        <th style="position: sticky; top: 0; background: #f8fafc; z-index: 2; padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Status</th>
                    </tr>
                </thead>
                <tbody id="attritionEmployeeTableBody">
                    ${renderSeparatedEmployeesRows(recentSeparatedEmployees)}
                </tbody>
            </table>
        </div>

        <div class="wfa-table-container" style="background: white; padding: 24px; border-radius: 14px; box-shadow: 0 10px 20px rgba(16, 24, 40, 0.06); margin-top: 30px;">
            <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px; align-items: center; margin-bottom: 18px;">
                <div>
                    <h3 style="margin: 0; font-size: 20px; color: #111827;">Job Vacancies</h3>
                    <p style="margin: 0; color: #667085;">Current active openings in the workforce.</p>
                </div>
            </div>
            <div style="width: 100%; overflow-x: auto;">
                <table class="wfa-table" style="width: 100%; min-width: 760px; border-collapse: collapse; border-spacing: 0;">
                    <thead>
                    <tr style="background: #f8fafc; color: #102a43; text-align: left;">
                        <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Vacancy ID</th>
                        <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Title</th>
                        <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Department</th>
                        <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Hiring Manager</th>
                        <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Openings</th>
                        <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Status</th>
                        <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">Posted Date</th>
                    </tr>
                </thead>
                <tbody id="jobVacancyTableBody">
                    ${renderJobVacancyRows(jobVacancies)}
                </tbody>
            </table>
        </div>

        <div id="attritionRecommendationModal" style="display: none; position: fixed; inset: 0; z-index: 10000; background: rgba(15, 23, 42, 0.45); align-items: center; justify-content: center; padding: 20px;">
            <div style="background: #ffffff; width: min(700px, 100%); border-radius: 24px; box-shadow: 0 32px 80px rgba(15, 23, 42, 0.24); overflow: hidden;">
                <div style="padding: 24px 24px 18px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="margin: 0; font-size: 22px; color: #111827;">HR Recommendation</h2>
                    </div>
                    <button type="button" onclick="closeAttritionRecommendationModal()" style="border: none; background: transparent; font-size: 24px; color: #6b7280; cursor: pointer;">&times;</button>
                </div>
                <div style="padding: 24px; background: #f8fafc;">
                    <div style="margin-bottom: 20px; padding: 20px; border-radius: 18px; background: #ffffff; border: 1px solid #e5e7eb;">
                        <p style="margin: 0 0 8px 0; font-weight: 700; color: #1f2937;">Insight</p>
                        <p id="modalRecommendationInsight" style="margin: 0 0 14px 0; color: #374151; line-height: 1.7;"></p>
                        <p style="margin: 0 0 8px 0; font-weight: 700; color: #1f2937;">Action</p>
                        <p id="modalRecommendationAction" style="margin: 0; color: #374151; line-height: 1.7;"></p>
                    </div>
                    <div style="margin-bottom: 18px;">
                        <label for="modalActionTitle" style="display: block; margin-bottom: 8px; font-weight: 700; color: #111827;">Action Title</label>
                        <select id="modalActionTitle" style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 14px; color: #111827; background: white;">
                        </select>
                    </div>
                    <div style="margin-bottom: 24px;">
                        <label for="modalActionNotes" style="display: block; margin-bottom: 8px; font-weight: 700; color: #111827;">Notes / Description</label>
                        <textarea id="modalActionNotes" rows="5" style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 14px; color: #111827; resize: vertical;"></textarea>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: flex-end;">
                        <button type="button" onclick="sendAttritionRecommendationToHR()" style="padding: 12px 22px; background: #2563eb; border: none; border-radius: 14px; color: white; cursor: pointer;">Send to HR</button>
                        <button type="button" onclick="closeAttritionRecommendationModal()" style="padding: 12px 22px; background: #f3f4f6; border: none; border-radius: 14px; color: #1f2937; cursor: pointer;">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="recruitmentModal" style="display: none; position: fixed; inset: 0; z-index: 10001; background: rgba(15, 23, 42, 0.55); align-items: center; justify-content: center; padding: 20px;">
            <div style="background: #ffffff; width: min(760px, 100%); border-radius: 24px; box-shadow: 0 32px 80px rgba(15, 23, 42, 0.24); overflow: hidden;">
                <div style="padding: 24px 24px 18px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="margin: 0; font-size: 22px; color: #111827;">Job Hiring Recruitment</h2>
                        <p style="margin: 6px 0 0; color: #475569; font-size: 14px;">Submit recruitment details for open roles and employee hiring requests.</p>
                    </div>
                    <button type="button" onclick="closeRecruitmentModal()" style="border: none; background: transparent; font-size: 24px; color: #6b7280; cursor: pointer;">&times;</button>
                </div>
                <div style="padding: 24px; background: #ffffff;">
                    <div style="display: grid; gap: 18px;">
                        <div>
                            <label for="recruitmentRole" style="display: block; margin-bottom: 8px; font-weight: 700; color: #111827;">Role / Vacancy</label>
                            <input id="recruitmentRole" type="text" placeholder="Enter the job title or vacancy ID" style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 14px;" />
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
                            <div>
                                <label for="recruitmentDepartment" style="display: block; margin-bottom: 8px; font-weight: 700; color: #111827;">Department</label>
                                <input id="recruitmentDepartment" type="text" placeholder="Department name" style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 14px;" />
                            </div>
                            <div>
                                <label for="recruitmentHiringManager" style="display: block; margin-bottom: 8px; font-weight: 700; color: #111827;">Hiring Manager</label>
                                <input id="recruitmentHiringManager" type="text" placeholder="Hiring manager name" style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 14px;" />
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
                            <div>
                                <label for="recruitmentOpenings" style="display: block; margin-bottom: 8px; font-weight: 700; color: #111827;">Openings</label>
                                <input id="recruitmentOpenings" type="number" min="1" placeholder="Number of hires" style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 14px;" />
                            </div>
                            <div>
                                <label for="recruitmentStartDate" style="display: block; margin-bottom: 8px; font-weight: 700; color: #111827;">Target Start</label>
                                <input id="recruitmentStartDate" type="date" style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 14px;" />
                            </div>
                        </div>
                        <div>
                            <label for="recruitmentNotes" style="display: block; margin-bottom: 8px; font-weight: 700; color: #111827;">Notes</label>
                            <textarea id="recruitmentNotes" rows="4" placeholder="Add any recruitment notes or candidate requirements" style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 14px; resize: vertical;"></textarea>
                        </div>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" onclick="submitRecruitmentForm()" style="padding: 12px 22px; background: #2563eb; border: none; border-radius: 14px; color: white; cursor: pointer;">Submit Request</button>
                        <button type="button" onclick="closeRecruitmentModal()" style="padding: 12px 22px; background: #f3f4f6; border: none; border-radius: 14px; color: #1f2937; cursor: pointer;">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const searchInput = document.getElementById('employeeSearchInput');
    if (searchInput) {
        searchInput.value = '';
    }
}

function renderJobVacancyRows(vacancies) {
    if (!Array.isArray(vacancies) || vacancies.length === 0) {
        return `
            <tr>
                <td colspan="7" style="padding: 18px 16px; text-align: center; color: #64748b;">No job vacancies available.</td>
            </tr>
        `;
    }

    return vacancies.map(vacancy => `
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${vacancy.job_id || 'N/A'}</td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${vacancy.title || 'N/A'}</td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${vacancy.department || 'N/A'}</td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${vacancy.hiring_manager || 'N/A'}</td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${vacancy.openings || 0}</td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${vacancy.status || 'Open'}</td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${vacancy.posted_date || 'N/A'}</td>
        </tr>
    `).join('');
}

function renderSeparatedEmployeesRows(employees) {
    if (employees.length === 0) {
        return `
            <tr>
                <td colspan="7" style="padding: 18px 16px; text-align: center; color: #64748b;">No separated employees found for the selected year.</td>
            </tr>
        `;
    }

    return employees.map(emp => {
        const leftOnDate = emp.notice_date ? formatDate(emp.notice_date) : 'N/A';
        const lastWorking = emp.last_working_date ? formatDate(emp.last_working_date) : 'N/A';
        const reasonText = emp.reason || emp.exit_reason || 'Not provided';
        const employeeName = emp.employee_name || emp.name || emp.full_name || emp.employee_full_name || (emp.employee_id ? `ID: ${emp.employee_id}` : 'Unknown');
        const statusText = emp.status || emp.employee_status || 'Unknown';
        const rawCategory = emp.resignation_type || emp.separation_type || emp.exit_type || 'Unknown';
        const categoryLabel = String(rawCategory).toLowerCase().includes('voluntary') || String(rawCategory).toLowerCase().includes('resign')
            ? 'Resign'
            : String(rawCategory).toLowerCase().includes('involuntary') || String(rawCategory).toLowerCase().includes('termin')
                ? 'Termination'
                : rawCategory;
        return `
            <tr>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${emp.employee_id || 'N/A'}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${employeeName}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${emp.department || 'N/A'}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${emp.position || 'N/A'}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${reasonText}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${categoryLabel}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${leftOnDate}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${lastWorking}</td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">${statusText}</td>
            </tr>
        `;
    }).join('');
}

function filterSeparatedEmployees() {
    const query = document.getElementById('employeeSearchInput')?.value.trim().toLowerCase();
    const tableBody = document.getElementById('attritionEmployeeTableBody');
    if (!tableBody) return;

    const filtered = separatedEmployeesCache.filter(emp => {
        if (!query) return true;
        const combined = [
            emp.employee_name,
            emp.name,
            emp.full_name,
            emp.department,
            emp.position,
            emp.reason,
            emp.exit_reason,
            emp.resignation_type,
            emp.exit_type,
            emp.employee_id
        ].filter(Boolean).join(' ').toLowerCase();
        return combined.includes(query);
    });

    tableBody.innerHTML = renderSeparatedEmployeesRows(filtered);
}

function clearEmployeeSearch() {
    const searchInput = document.getElementById('employeeSearchInput');
    if (searchInput) {
        searchInput.value = '';
        filterSeparatedEmployees();
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadAttritionTab, { once: true });
} else {
    loadAttritionTab();
}
</script>
