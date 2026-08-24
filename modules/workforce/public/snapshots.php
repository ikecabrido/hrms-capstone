<!-- TAB: PREDICTIVE ANALYTICS -->
<div class="wfa-container" id="snapshotContainer">
    <div class="wfa-loading">
        <i class="fas fa-spinner fa-spin"></i> Loading Predictive Analytics...</div>
</div>

<script>
async function loadSnapshotsTab() {
    console.log('loadSnapshotsTab() called');
    const container = document.getElementById('snapshotContainer');

    if (!container) {
        console.error('Snapshot container not found');
        return;
    }

    try {
        const basePath = '/capstone_hr_management_system';
        const [dashboardRes, attritionRes, insightsRes, appraisalsRes] = await Promise.all([
            fetch(`${basePath}/api/wfa/dashboard_metrics.php`),
            fetch(`${basePath}/api/wfa/attrition_metrics.php`),
            fetch(`${basePath}/api/wfa/insights_analytics.php`),
            fetch(`${basePath}/api/wfa/appraisals_data.php`, { cache: 'no-store' })
        ]);

        if (!dashboardRes.ok) throw new Error(`Dashboard metrics error: ${dashboardRes.status}`);
        if (!attritionRes.ok) throw new Error(`Attrition metrics error: ${attritionRes.status}`);
        if (!insightsRes.ok) throw new Error(`Insights analytics error: ${insightsRes.status}`);
        if (!appraisalsRes.ok) throw new Error(`Appraisals data error: ${appraisalsRes.status}`);

        const dashboardData = await dashboardRes.json();
        const attritionData = await attritionRes.json();
        const insightsData = await insightsRes.json();
        const appraisalsData = await appraisalsRes.json();

        const employeeMetrics = dashboardData.data?.employee_metrics || {};
        const attendanceTrend = dashboardData.data?.attendance_trend || [];
        const atRiskEmployees = insightsData.data?.at_risk_employees || dashboardData.data?.at_risk_employees || [];
        const attritionMetrics = attritionData.data || {};
        const insights = insightsData.data || {};
        const appraisals = appraisalsData.data?.appraisals || [];

        const totalEmployees = employeeMetrics.total_employees || 0;
        const totalAppraisals = appraisals.length;
        const lowAppraisalsCount = appraisals.filter(appraisal => Number(appraisal.overall_score) < 3).length;
        const appraisalRiskFactor = totalAppraisals > 0 ? lowAppraisalsCount / totalAppraisals : 0;
        const attritionRate = parseFloat(
            insights.attrition_rate ??
            insights.kpis?.attrition_rate ??
            attritionMetrics.summary?.attrition_rate ??
            attritionMetrics.attrition_rate ??
            0
        );
        const expectedTurnover = Math.max(0, Math.round((totalEmployees * attritionRate) / 100 / 12 * (1 + appraisalRiskFactor * 0.35)));
        const expectedTurnoverSubtitle = totalAppraisals > 0
            ? `${totalAppraisals} reviews, ${lowAppraisalsCount} below score threshold`
            : 'No appraisal reviews available';
        const employeeRiskCount = insights.at_risk_count ?? atRiskEmployees.length;
        const avgPerformance = Number(employeeMetrics.avg_performance || 0);
        const latestAttendancePoint = attendanceTrend.length ? attendanceTrend[attendanceTrend.length - 1] : null;
        const latestAttendanceRate = latestAttendancePoint ? (latestAttendancePoint.daily_rate ?? latestAttendancePoint.rate ?? 'N/A') : 'N/A';
        const attendanceValue = typeof latestAttendanceRate === 'number' ? `${latestAttendanceRate}%` : latestAttendanceRate;
        const reviewPeriod = employeeMetrics.review_period || insights.analysis_period || attritionMetrics.report_period || 'Current';
        const attendancePeriod = latestAttendancePoint?.period || latestAttendancePoint?.range || 'Recent';
        const topReason = attritionMetrics.resignation_reasons?.[0]?.reason || insights.top_resignation_reasons?.[0]?.reason || 'Not enough data';
        const trend = calculateAttendanceTrend(attendanceTrend);
        const narrative = employeeRiskCount > 0
            ? `${employeeRiskCount} employees are likely to resign soon based on performance, attendance, and attrition signals.`
            : 'No strong resignation risk identified at this time. Keep monitoring attendance, performance, and attrition trends.';

        let html = `
            <div style="margin-bottom: 24px; padding: 22px; background: #eef7ff; border-radius: 18px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);">
                <h2 style="margin: 0 0 10px; color: #163775;">Predictive Analytics</h2>
            </div>

            <div class="wfa-metrics-grid">
                <div class="wfa-metric-card danger">
                    <div class="wfa-metric-label">Employees at Risk</div>
                    <div class="wfa-metric-value">${employeeRiskCount}</div>
                    <div class="wfa-metric-change">From performance & attendance signals</div>
                </div>
                <div class="wfa-metric-card warning">
                    <div class="wfa-metric-label">Expected Turnover</div>
                    <div class="wfa-metric-value">${expectedTurnover}</div>
                    <div class="wfa-metric-change">${expectedTurnoverSubtitle}</div>
                </div>
                <div class="wfa-metric-card info" style="cursor: pointer;" onclick="showRiskRuleActionModal()">
                    <div class="wfa-metric-label">Attendance Trend</div>
                    <div class="wfa-metric-value">${trend.label}</div>
                    <div class="wfa-metric-change">${trend.detail}</div>
                </div>
                <div class="wfa-metric-card success">
                    <div class="wfa-metric-label">Attrition Rate</div>
                    <div class="wfa-metric-value">${attritionRate.toFixed(1)}%</div>
                    <div class="wfa-metric-change">Annualized rate</div>
                </div>
            </div>

            <div style="margin-top: 24px; background: white; border-radius: 18px; padding: 20px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);">
                <h3 style="margin: 0 0 14px; color: #1f2937;">Early Warning Summary</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 680px;">
                        <thead>
                            <tr style="background: #f8fafc; color: #334155; text-align: left;">
                                <th style="padding: 12px 14px; font-weight: 700;">Signal</th>
                                <th style="padding: 12px 14px; font-weight: 700;">Current Value</th>
                                <th style="padding: 12px 14px; font-weight: 700;">Review Period</th>
                                <th style="padding: 12px 14px; font-weight: 700;">Trend / Risk</th>
                                <th style="padding: 12px 14px; font-weight: 700;">Early Warning</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-top: 1px solid #e2e8f0;">
                                <td style="padding: 14px; vertical-align: top;">Attendance</td>
                                <td style="padding: 14px; vertical-align: top;">${attendanceValue}</td>
                                <td style="padding: 14px; vertical-align: top;">${attendancePeriod}</td>
                                <td style="padding: 14px; vertical-align: top;">${trend.label}</td>
                                <td style="padding: 14px; vertical-align: top; color: #475569;">${escapeHtml(trend.detail)}</td>
                            </tr>
                            <tr style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                                <td style="padding: 14px; vertical-align: top;">Performance</td>
                                <td style="padding: 14px; vertical-align: top;">${avgPerformance.toFixed(2)} / 5.0<br><small style="color: #64748b;">${totalAppraisals} reviews</small></td>
                                <td style="padding: 14px; vertical-align: top;">${reviewPeriod}</td>
                                <td style="padding: 14px; vertical-align: top;">${employeeRiskCount > 0 ? employeeRiskCount + ' at risk' : 'Low risk'}</td>
                                <td style="padding: 14px; vertical-align: top; color: #475569;">${lowAppraisalsCount > 0 ? lowAppraisalsCount + ' review(s) below 3.0, target coaching' : 'Appraisal reviews are stable'}</td>
                            </tr>
                            <tr style="border-top: 1px solid #e2e8f0;">
                                <td style="padding: 14px; vertical-align: top;">Attrition</td>
                                <td style="padding: 14px; vertical-align: top;">${attritionRate.toFixed(1)}% / ${expectedTurnover} expected exits</td>
                                <td style="padding: 14px; vertical-align: top;">${reviewPeriod}</td>
                                <td style="padding: 14px; vertical-align: top;">${attritionRate >= 5 ? 'Elevated' : 'Normal'}</td>
                                <td style="padding: 14px; vertical-align: top; color: #475569;">Top reason: ${escapeHtml(topReason)}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="margin-top: 24px; padding: 24px; background: #ffffff; border-radius: 18px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                    <div>
                        <h3 style="margin: 0; color: #1f2937;">Prediction Summary</h3>
                        <p style="margin: 8px 0 0; color: #556070;">${escapeHtml(narrative)}</p>
                    </div>
                    <div style="background: #e8f0fe; color: #1d4ed8; padding: 14px 20px; border-radius: 14px; font-weight: 700;">Looking ahead: act early to reduce turnover.</div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-top: 24px;">
                    <div id="riskRuleCard" onclick="showRiskRuleActionModal()" role="button" style="background: #f8fafc; padding: 16px; border-radius: 14px; border: 1px solid #e2e8f0; cursor: pointer; transition: transform 0.15s ease;">
                        <div style="font-size: 12px; color: #475569; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">Risk Rule</div>
                        <div style="font-size: 16px; font-weight: 700; color: #0f172a;">Absent often → high leave risk</div>
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 14px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 12px; color: #475569; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">Trend</div>
                        <div style="font-size: 16px; font-weight: 700; color: #0f172a;">${trend.label}</div>
                        <div style="margin-top: 6px; color: #64748b;">${escapeHtml(trend.detail)}</div>
                    </div>
                    <div id="supportValueCard" onclick="showRiskRuleActionModal(true)" role="button" style="background: #f8fafc; padding: 16px; border-radius: 14px; border: 1px solid #e2e8f0; cursor: pointer;">
                        <div style="font-size: 12px; color: #475569; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">Support Value</div>
                        <div style="font-size: 16px; font-weight: 700; color: #0f172a;">Early intervention</div>
                        <div style="margin-top: 6px; color: #64748b;">Help employees before issues escalate.</div>
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading predictive analytics:', error);
        container.innerHTML = `
            <div style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">
                <h4>Error Loading Predictive Analytics</h4>
                <p>${escapeHtml(error.message)}</p>
            </div>
        `;
    }
}

function calculateAttendanceTrend(trendData) {
    if (!Array.isArray(trendData) || trendData.length < 2) {
        return { label: 'Stable', detail: 'Not enough attendance history to determine trend.' };
    }

    const first = trendData[0].daily_rate || 0;
    const last = trendData[trendData.length - 1].daily_rate || 0;
    const change = first === 0 ? 0 : ((last - first) / first) * 100;
    const rounded = change.toFixed(1);

    if (change >= 2) {
        return { label: 'Improving', detail: `Attendance has improved by ${rounded}% over the last period.` };
    }
    if (change <= -2) {
        return { label: 'Declining', detail: `Attendance has declined by ${Math.abs(rounded)}% over the last period.` };
    }
    return { label: 'Stable', detail: 'Attendance is relatively steady based on recent data.' };
}

function riskColor(level) {
    if (!level) return '#f59e0b';
    const normalized = String(level).toLowerCase();
    if (normalized === 'high') return '#dc2626';
    if (normalized === 'medium') return '#f59e0b';
    return '#16a34a';
}

function escapeHtml(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function showRiskRuleActionModal(goToPerformance = false) {
    const modalId = 'snapshotActionModal';
    const existingModal = document.getElementById(modalId);
    if (existingModal) {
        existingModal.remove();
    }

    if (goToPerformance) {
        goToPerformanceAssessment();
    }

    const modalHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog" aria-labelledby="${modalId}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="${modalId}Label">Risk Action Recommendation</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>This risk rule has been triggered by repeated absenteeism and a high leave-risk signal.</p>
                        <p>Preventive actions should be taken now to support the employee and reduce turnover risk.</p>
                        <ul style="margin-top: 12px; padding-left: 20px; color: #34495e;">
                            <li>Verify the absence reasons and follow up with the employee as soon as possible.</li>
                            <li>Schedule a one-on-one coaching or check-in to address performance and wellbeing concerns.</li>
                            <li>Assign a retention support action with clear tasks, owner, and target date.</li>
                            <li>Update progress notes frequently and monitor the employee's risk score and attendance trends.</li>
                            <li>Offer targeted interventions such as flexible scheduling, training, or wellbeing support.</li>
                        </ul>
                        <div class="alert alert-warning" role="alert">
                            <strong>Priority:</strong> act now to prevent this predictive signal from turning into a resignation event.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="openActionDashboard()">Open Action Dashboard</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    if (window.jQuery && window.jQuery.fn.modal) {
        $(`#${modalId}`).modal('show');
    } else {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }
}

function goToPerformanceAssessment() {
    const performanceTabLink = document.querySelector('a[data-toggle="tab"][href="#performance"], a[href$="#performance"]');
    const performancePane = document.getElementById('performance');

    // Ensure the performance tab is active and other tabs are hidden
    document.querySelectorAll('a[data-toggle="tab"]').forEach(link => link.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));

    if (performanceTabLink) {
        performanceTabLink.classList.add('active');
    }
    if (performancePane) {
        performancePane.classList.add('show', 'active');
    }

    if (typeof loadPerformanceTab === 'function') {
        loadPerformanceTab();
    }

    window.location.hash = '#performance';
}

function openActionDashboard() {
    // Close the modal if Bootstrap is available
    const modal = document.getElementById('snapshotActionModal');
    if (modal) {
        if (window.jQuery && window.jQuery.fn.modal) {
            $(`#${modal.id}`).modal('hide');
        } else {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    }

    const actionTabLink = document.querySelector('a[data-toggle="tab"][href="#actions"]');
    if (actionTabLink) {
        actionTabLink.click();
        return;
    }

    // Fallback: navigate to the workforce page with the actions tab anchor
    window.location.href = '/capstone_hr_management_system/workforce/workforce.php#actions';
}
</script>
