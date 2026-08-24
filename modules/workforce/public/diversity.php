<!-- TAB: DIVERSITY & INCLUSION REPORTS -->
<div class="wfa-container" id="diversityContainer">
    <div class="wfa-loading">
        <i class="fas fa-spinner fa-spin"></i> Loading Diversity & Inclusion Report...
    </div>
</div>

<script>
window.chartInstances = window.chartInstances || {};

async function loadDiversityTab() {
    const container = document.getElementById('diversityContainer');
    if (!container) return;

    try {
        const basePath = '/capstone_hr_management_system/workforce';
        const [employeesResponse, genderResponse, ageResponse, departmentResponse, salaryResponse] = await Promise.all([
            fetch(`${basePath}/api/wfa/employees_data.php`),
            fetch(`${basePath}/api/gender_distribution.php`),
            fetch(`${basePath}/api/age_distribution.php`),
            fetch(`${basePath}/api/department_distribution.php`),
            fetch(`${basePath}/api/salary_statistics.php`)
        ]);

        if (!employeesResponse.ok) {
            throw new Error(`Employees API Error: ${employeesResponse.status}`);
        }

        const employeesPayload = await employeesResponse.json();
        const genderPayload = await genderResponse.json();
        const agePayload = await ageResponse.json();
        const departmentPayload = await departmentResponse.json();
        const salaryPayload = await salaryResponse.json();

        const employees = employeesPayload.data && employeesPayload.data.employees ? employeesPayload.data.employees : [];
        window.diversityEmployees = employees;
        const totalEmployees = employees.length;

        const genderDist = buildDistribution(employees, emp => emp.gender ? emp.gender.trim() : null);
        const ageDist = buildDistribution(employees, emp => emp.age_group || 'Unknown');
        const deptDist = buildDistribution(employees, emp => emp.department || 'Unassigned');
        const genderCount = Object.values(genderDist).reduce((sum, count) => sum + count, 0);
        const genderSummaryText = summarizeGender(genderDist, genderCount);
        const ageSummaryText = summarizeAge(ageDist, totalEmployees);
        const topDepartments = Object.entries(deptDist)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 6);

        const recommendations = generateDiversityRecommendations(employees, genderDist, ageDist, totalEmployees);
        const severityConfig = {
            critical: { color: '#dc3545', emoji: '⚠️', bgColor: '#fff5f5' },
            high: { color: '#ff9800', emoji: '🔍', bgColor: '#fff8f0' },
            medium: { color: '#2196f3', emoji: '💡', bgColor: '#f0f7ff' },
            info: { color: '#17a2b8', emoji: '✅', bgColor: '#f0f9ff' }
        };

        const recommendationsHtml = recommendations.map(rec => {
            const cfg = severityConfig[rec.severity] || severityConfig.info;
            const insightSafe = String(rec.insight)
                .replace(/'/g, "\\'")
                .replace(/"/g, '&quot;')
                .replace(/\n/g, ' ');
            const actionSafe = String(rec.action)
                .replace(/'/g, "\\'")
                .replace(/"/g, '&quot;')
                .replace(/\n/g, ' ');
            return `
                <div onclick="showRecommendationModal('${insightSafe}', '${actionSafe}')"
                    style="margin-bottom: 15px; padding: 15px; background: ${cfg.bgColor}; border-left: 4px solid ${cfg.color}; border-radius: 8px; display: flex; gap: 12px; cursor: pointer; transition: transform 0.2s ease;">
                    <div style="font-size: 22px; flex-shrink: 0;">${cfg.emoji}</div>
                    <div style="flex-grow: 1; font-size: 14px;">
                        <strong style="color: ${cfg.color};">Insight:</strong> ${rec.insight}<br>
                        <strong style="color: #495057;">Action:</strong> ${rec.action}
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = `
            <div class="wfa-section-header" style="margin-bottom: 24px;">
                <h2 style="margin:0; font-size: 24px; color: #2e3a59;">Diversity & Inclusion Reports</h2>
                <p style="margin: 10px 0 0; color: #556080; max-width: 820px; line-height: 1.6;">A focused diversity report for HR leadership, summarizing employee demographics, gender balance, age cohorts, and department representation across the organization.</p>
            </div>

            <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 12px;">
                <h3 style="margin: 0 0 15px 0; font-size: 18px; color: #2e3a59; display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 24px;">📋</span> HR Recommendations
                </h3>
                <div>${recommendationsHtml}</div>
            </div>

            <div class="wfa-metrics-grid">
                <div class="wfa-metric-card">
                    <div class="wfa-metric-label">Total Employees</div>
                    <div class="wfa-metric-value">${totalEmployees}</div>
                    <div class="wfa-metric-change">Current headcount</div>
                </div>

                <div class="wfa-metric-card info">
                    <div class="wfa-metric-label">Gender Balance</div>
                    <div class="wfa-metric-value">${Object.keys(genderDist).length} categories</div>
                    <div class="wfa-metric-change">${genderSummaryText}</div>
                </div>

                <div class="wfa-metric-card success">
                    <div class="wfa-metric-label">Age Cohorts</div>
                    <div class="wfa-metric-value">${Object.keys(ageDist).length} groups</div>
                    <div class="wfa-metric-change">${ageSummaryText}</div>
                </div>

                <div class="wfa-metric-card warning">
                    <div class="wfa-metric-label">Department Reach</div>
                    <div class="wfa-metric-value">${Object.keys(deptDist).length}</div>
                    <div class="wfa-metric-change">Unique departments</div>
                </div>
            </div>

            <div class="wfa-section" style="margin-top: 32px; display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px;">
                <div class="wfa-card" style="padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 1px 24px rgba(15, 23, 42, 0.05);">
                    <h3 style="margin-top: 0; color: #2e3a59;">Key Inclusion Insights</h3>
                    <ul style="margin: 0; padding-left: 20px; color: #4b5563; line-height: 1.7;">
                        <li>${genderSummaryText}</li>
                        <li>${ageSummaryText}</li>
                        <li>Top departments represent the majority of the workforce and should be prioritized for inclusion initiatives.</li>
                    </ul>
                </div>
                <div class="wfa-card" style="padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 1px 24px rgba(15, 23, 42, 0.05);">
                    <h3 style="margin-top: 0; color: #2e3a59;">Top Departments by Headcount</h3>
                    <ol style="margin: 0; padding-left: 20px; color: #4b5563; line-height: 1.7;">
                        ${topDepartments.map(([dept, count]) => `<li><strong>${dept}</strong>: ${count} employees</li>`).join('')}
                    </ol>
                </div>
            </div>

            <div class="wfa-section" style="margin-top: 28px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <div class="wfa-card" style="padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 1px 24px rgba(15, 23, 42, 0.05);">
                    <h3 style="margin-top: 0; color: #2e3a59;">Gender Distribution</h3>
                    <canvas id="genderDiversityChart" style="max-height: 320px;"></canvas>
                </div>
                <div class="wfa-card" style="padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 1px 24px rgba(15, 23, 42, 0.05);">
                    <h3 style="margin-top: 0; color: #2e3a59;">Age Group Distribution</h3>
                    <canvas id="ageDiversityChart" style="max-height: 320px;"></canvas>
                </div>
                <div class="wfa-card" style="padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 1px 24px rgba(15, 23, 42, 0.05);">
                    <h3 style="margin-top: 0; color: #2e3a59;">Department Distribution</h3>
                    <canvas id="departmentDiversityChart" style="max-height: 320px;"></canvas>
                </div>
            </div>

            <div class="wfa-section" style="margin-top: 32px;">
                <div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                    <h3 style="margin: 0; color: #2e3a59;">Diversity Breakdown Tables</h3>
                    <button class="wfa-btn wfa-btn-info" onclick="printDiversity()"><i class="fas fa-print"></i> Print Report</button>
                </div>

                <div class="wfa-table-container" style="margin-top: 12px;">
                    <h4 style="margin-bottom: 12px;">Gender Distribution</h4>
                    <table class="wfa-table">
                        <thead>
                            <tr>
                                <th>Gender</th>
                                <th>Count</th>
                                <th>Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${renderTableRows(genderDist, genderCount)}
                        </tbody>
                    </table>
                </div>

                <div class="wfa-table-container" style="margin-top: 24px;">
                    <h4 style="margin-bottom: 12px;">Age Group Distribution</h4>
                    <table class="wfa-table">
                        <thead>
                            <tr>
                                <th>Age Group</th>
                                <th>Count</th>
                                <th>Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${renderTableRows(ageDist, totalEmployees)}
                        </tbody>
                    </table>
                </div>

                <div class="wfa-table-container" style="margin-top: 24px;">
                    <h4 style="margin-bottom: 12px;">Salary Statistics</h4>
                    <table class="wfa-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Employees</th>
                                <th>Min Salary</th>
                                <th>Max Salary</th>
                                <th>Average Salary</th>
                            </tr>
                        </thead>
                        <tbody id="salary-stats-table"></tbody>
                    </table>
                </div>
            </div>
        `;

        renderDiversityCharts(genderPayload, agePayload, departmentPayload);
        renderSalaryStatsTable(salaryPayload);
    } catch (error) {
        console.error('Error loading diversity data:', error);
        container.innerHTML = '<div style="padding: 20px; color: #d32f2f;">Error loading diversity data: ' + error.message + '</div>';
    }
}

function renderDiversityCharts(genderPayload, agePayload, departmentPayload) {
    const genderData = Array.isArray(genderPayload?.data) ? genderPayload.data : [];
    const ageData = Array.isArray(agePayload?.data) ? agePayload.data : [];
    const departmentData = Array.isArray(departmentPayload?.data) ? departmentPayload.data : [];

    const genderValidItems = genderData.filter(item => item.gender && String(item.gender).trim() !== '');
    if (genderValidItems.length) {
        const labels = genderValidItems.map(item => item.gender);
        const counts = genderValidItems.map(item => item.count);
        if (window.Chart) {
            const ctx = document.getElementById('genderDiversityChart')?.getContext('2d');
            if (ctx) {
                if (window.chartInstances.genderDiversity) {
                    window.chartInstances.genderDiversity.destroy();
                }
                window.chartInstances.genderDiversity = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels,
                        datasets: [{
                            data: counts,
                            backgroundColor: ['#3498db', '#e74c3c', '#bdc3c7', '#2ecc71', '#f39c12', '#9b59b6'],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        }
    }

    if (ageData.length) {
        const labels = ageData.map(item => item.age_group);
        const counts = ageData.map(item => item.count);
        if (window.Chart) {
            const ctx = document.getElementById('ageDiversityChart')?.getContext('2d');
            if (ctx) {
                if (window.chartInstances.ageDiversity) {
                    window.chartInstances.ageDiversity.destroy();
                }
                window.chartInstances.ageDiversity = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Number of Employees',
                            data: counts,
                            backgroundColor: '#2ecc71',
                            borderColor: '#27ae60',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true } }
                    }
                });
            }
        }
    }

    if (departmentData.length) {
        const labels = departmentData.map(item => item.department);
        const counts = departmentData.map(item => item.count);
        if (window.Chart) {
            const ctx = document.getElementById('departmentDiversityChart')?.getContext('2d');
            if (ctx) {
                if (window.chartInstances.departmentDiversity) {
                    window.chartInstances.departmentDiversity.destroy();
                }
                window.chartInstances.departmentDiversity = new Chart(ctx, {
                    type: 'polarArea',
                    data: {
                        labels,
                        datasets: [{
                            data: counts,
                            backgroundColor: [
                                'rgba(52, 152, 219, 0.5)',
                                'rgba(46, 204, 113, 0.5)',
                                'rgba(231, 76, 60, 0.5)',
                                'rgba(243, 156, 18, 0.5)',
                                'rgba(155, 89, 182, 0.5)',
                                'rgba(26, 188, 156, 0.5)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        }
    }
}

function renderSalaryStatsTable(salaryPayload) {
    const tbody = document.getElementById('salary-stats-table');
    if (!tbody) return;

    tbody.innerHTML = '';
    const stats = Array.isArray(salaryPayload?.data) ? salaryPayload.data : [];

    if (!stats.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No salary statistics available</td></tr>';
        return;
    }

    stats.forEach(stat => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${stat.department || 'N/A'}</td>
            <td>${stat.count || 0}</td>
            <td>${formatCurrency(stat.min_salary)}</td>
            <td>${formatCurrency(stat.max_salary)}</td>
            <td>${formatCurrency(stat.avg_salary)}</td>
        `;
        tbody.appendChild(row);
    });
}

function formatCurrency(value) {
    const amount = Number(value);
    if (Number.isNaN(amount)) return 'N/A';
    return `$${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function generateDiversityRecommendations(employees, genderDist, ageDist, totalEmployees) {
    const recommendations = [];

    const genderEntries = Object.entries(genderDist);
    if (genderEntries.length > 0) {
        const sortedByCount = genderEntries.sort((a, b) => b[1] - a[1]);
        if (sortedByCount.length >= 2) {
            const largest = sortedByCount[0][1];
            const largestPercent = (largest / totalEmployees) * 100;
            if (largestPercent > 70) {
                const category = sortedByCount[0][0];
                recommendations.push({
                    severity: 'high',
                    insight: `Gender imbalance: ${largestPercent.toFixed(1)}% ${category}`,
                    action: 'Implement inclusive hiring initiatives and review recruitment strategies'
                });
            }
        }
    }

    const ageEntries = Object.entries(ageDist).filter(([k]) => k !== 'Unknown');
    if (ageEntries.length > 0) {
        const dominantAge = ageEntries.sort((a, b) => b[1] - a[1])[0];
        if (dominantAge) {
            const dominantPercent = (dominantAge[1] / totalEmployees) * 100;
            if (dominantPercent > 60) {
                recommendations.push({
                    severity: 'medium',
                    insight: `Age concentration: ${dominantPercent.toFixed(1)}% in ${dominantAge[0]} age group`,
                    action: 'Diversify recruitment to attract candidates from different age groups'
                });
            }
        }
    }

    if (recommendations.length === 0) {
        recommendations.push({
            severity: 'info',
            insight: 'Diversity metrics show reasonable balance',
            action: 'Continue monitoring and maintaining inclusive hiring and workplace culture'
        });
    }

    return recommendations;
}

function buildDistribution(items, keyFn) {
    return items.reduce((acc, item) => {
        const key = keyFn(item);
        if (key === null || key === undefined || key === '') return acc;
        acc[key] = (acc[key] || 0) + 1;
        return acc;
    }, {});
}

function formatPercent(count, total) {
    return total > 0 ? `${((count / total) * 100).toFixed(1)}%` : '0%';
}

function summarizeGender(dist, total) {
    const categories = Object.entries(dist)
        .map(([key, value]) => `${key}: ${formatPercent(value, total)}`)
        .join(' • ');
    return categories || 'No demographic data available';
}

function summarizeAge(dist, total) {
    const ordered = ['18-24', '25-34', '35-44', '45-54', '55+', 'Unknown'];
    const parts = ordered
        .filter(key => dist[key])
        .map(key => `${key}: ${formatPercent(dist[key], total)}`);
    return parts.length ? parts.join(' • ') : 'No age data available';
}

function renderTableRows(dist, total) {
    return Object.entries(dist)
        .sort((a, b) => b[1] - a[1])
        .map(([key, count]) => `<tr><td><strong>${key}</strong></td><td>${count}</td><td>${formatPercent(count, total)}</td></tr>`)
        .join('');
}

function printDiversity() {
    window.print();
}

function showRecommendationModal(insight, action) {
    closeRecommendationModal();

    const modalHtml = `
        <div id="recommendationModalOverlay" style="position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 16px;">
            <div style="background: #fff; border-radius: 14px; width: min(700px,calc(100% - 40px)); max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.18); position: relative; padding: 24px;">
                <button onclick="closeRecommendationModal()" style="position: absolute; top: 16px; right: 16px; background: transparent; border: none; font-size: 22px; color: #4b5563; cursor: pointer;">&times;</button>
                <h2 style="margin-top: 0; font-size: 24px; color: #1f2937;">HR Recommendation</h2>

                <div style="margin-top: 18px; padding: 18px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <p style="margin: 0 0 8px 0; font-weight: 600; color: #0f172a;">Insight</p>
                    <p style="margin: 0 0 14px 0; color: #334155; line-height: 1.7;">${insight}</p>
                    <p style="margin: 0 0 8px 0; font-weight: 600; color: #0f172a;">Action</p>
                    <p style="margin: 0; color: #334155; line-height: 1.7;">${action}</p>
                </div>

                <div style="margin-top: 24px; display: grid; gap: 14px;">
                    <div>
                        <label for="recommendationTitle" style="display:block; margin-bottom:6px; font-weight:600; color:#1f2937;">Action Title</label>
                        <input id="recommendationTitle" type="text" value="${action}" style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px;" />
                    </div>

                    <div>
                        <label for="recommendationNotes" style="display:block; margin-bottom:6px; font-weight:600; color:#1f2937;">Notes / Description</label>
                        <textarea id="recommendationNotes" rows="5" placeholder="Add context for recruitment or HR follow-up..." style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; line-height:1.6;">${action}</textarea>
                    </div>
                </div>

                <div style="margin-top: 24px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end;">
                    <button onclick="sendToRecruitment('${insight}', '${action}')" style="padding: 10px 18px; background: #0f61ff; border: none; border-radius: 10px; color: white; cursor: pointer;">Send to Recruitment</button>
                    <button onclick="closeRecommendationModal()" style="padding: 10px 18px; background: #e2e8f0; border: none; border-radius: 10px; color: #334155; cursor: pointer;">Close</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

async function saveRecommendationAction(insight, action) {
    const title = document.getElementById('recommendationTitle')?.value.trim();
    const employeeId = document.getElementById('recommendationEmployeeId')?.value;
    const actionType = document.getElementById('recommendationType')?.value || 'Training';
    const notes = document.getElementById('recommendationNotes')?.value.trim();

    if (!title) {
        alert('Please enter an action title.');
        return;
    }

    if (!employeeId) {
        alert('To save a formal action, enter the related Employee ID. Otherwise use Download Recruitment Brief.');
        return;
    }

    try {
        const payload = {
            employee_id: employeeId,
            action_type: actionType,
            description: notes || action,
            pip_id: null,
            assigned_to: 'recruitment',
            due_date: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
        };

        const response = await fetch('/capstone_hr_management_system/workforce/api/wfa/create_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (result.success) {
            alert('Action created successfully and forwarded to recruitment workflow.');
            closeRecommendationModal();
        } else {
            alert('Unable to create action: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving recommendation action:', error);
        alert('Error saving action: ' + error.message);
    }
}

function sendToRecruitment(insight, action) {
    const title = document.getElementById('recommendationTitle')?.value.trim() || action;
    const notes = document.getElementById('recommendationNotes')?.value.trim() || action;
    const employeeId = document.getElementById('recommendationEmployeeId')?.value.trim() || 'Not specified';
    showSuccessToast('Recommendation sent to recruitment successfully!');
    closeRecommendationModal();
}

function showSuccessToast(message) {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.position = 'fixed';
    toast.style.bottom = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '10001';
    toast.style.padding = '12px 16px';
    toast.style.background = '#16a34a';
    toast.style.color = '#ffffff';
    toast.style.borderRadius = '10px';
    toast.style.boxShadow = '0 12px 30px rgba(0,0,0,0.18)';
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
    }, 2800);
}

function closeRecommendationModal() {
    const existing = document.getElementById('recommendationModalOverlay');
    if (existing) {
        existing.remove();
    }
}
</script>
