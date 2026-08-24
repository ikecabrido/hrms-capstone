/**
 * Workforce Analytics Dashboard - Data Loading
 * Loads data from API endpoints and displays on UI
 */

// Chart storage for later updates
var charts = {};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard initializing...');
    console.log('Chart.js available:', typeof Chart !== 'undefined');
    
    loadDashboardData();
    startClock();
    setupTabListeners();
});

/**
 * Setup tab listeners for data loading
 */
function setupTabListeners() {
    // When tabs are clicked, load corresponding data
    document.querySelectorAll('a[data-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const tabId = e.target.getAttribute('href').substring(1);
            console.log('Tab switched to:', tabId);
            
            if (tabId === 'attrition') {
                loadAttritionData();
            } else if (tabId === 'diversity') {
                loadDiversityData();
            } else if (tabId === 'performance') {
                loadPerformanceData();
            } else if (tabId === 'reports') {
                loadReportsData();
            }
        });
    });
}

/**
 * Load and display dashboard metrics
 */
function loadDashboardData() {
    console.log('Loading dashboard data...');
    fetch('./api/dashboard_metrics.php')
        .then(response => response.json())
        .then(data => {
            console.log('Dashboard data:', data);
            if (data.success && data.data) {
                // Update metric cards
                const metrics = data.data;
                
                if (document.getElementById('total-employees')) {
                    document.getElementById('total-employees').textContent = metrics.total_employees || 0;
                }
                if (document.getElementById('total-teachers')) {
                    document.getElementById('total-teachers').textContent = metrics.total_teachers || 0;
                }
                if (document.getElementById('total-staff')) {
                    document.getElementById('total-staff').textContent = metrics.total_staff || 0;
                }
                if (document.getElementById('new-hires')) {
                    document.getElementById('new-hires').textContent = metrics.new_hires || 0;
                }
                if (document.getElementById('avg-performance')) {
                    document.getElementById('avg-performance').textContent = (metrics.avg_performance || 0).toFixed(2) + ' / 5';
                }
                
                console.log('Dashboard metrics updated');
                
                // Load charts
                loadDepartmentChart();
                loadAtRiskChart();
                loadGenderChart();
                loadAgeChart();
                loadTenureChart();
            }
        })
        .catch(error => console.error('Error loading dashboard:', error));
}

/**
 * Load department distribution chart
 */
function loadDepartmentChart() {
    console.log('Loading department distribution...');
    fetch('./api/department_distribution.php')
        .then(response => response.json())
        .then(data => {
            console.log('Department data:', data);
            if (data.success && data.data && document.getElementById('departmentChart')) {
                const labels = data.data.map(item => item.department);
                const counts = data.data.map(item => parseInt(item.count));
                
                const ctx = document.getElementById('departmentChart').getContext('2d');
                
                // Destroy existing chart if it exists
                if (charts.department) {
                    charts.department.destroy();
                }
                
                // Add a dummy department with zero employees so the chart shows a zero entry
                if (!labels.includes('Dummy Department')) {
                    labels.push('Dummy Department');
                    counts.push(0);
                }

                charts.department = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Employees',
                            data: counts,
                            backgroundColor: '#3498db',
                            borderColor: '#2c3e50',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    },
                    plugins: [{
                        id: 'departmentValueLabels',
                        afterDatasetsDraw(chart) {
                            const ctx = chart.ctx;
                            chart.data.datasets.forEach((dataset, datasetIndex) => {
                                const meta = chart.getDatasetMeta(datasetIndex);
                                meta.data.forEach((element, index) => {
                                    const value = dataset.data[index];
                                    const position = element.tooltipPosition();
                                    ctx.save();
                                    ctx.fillStyle = '#333';
                                    ctx.font = 'bold 12px Arial';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'bottom';
                                    ctx.fillText(value.toString(), position.x, position.y - 6);
                                    ctx.restore();
                                });
                            });
                        }
                    }]
                });
            }
        })
        .catch(error => console.error('Error loading department chart:', error));
}

/**
 * Load at-risk employees chart
 */
function loadAtRiskChart() {
    console.log('Loading at-risk employees chart...');
    fetch('./api/at_risk_employees.php')
        .then(response => response.json())
        .then(data => {
            console.log('At-risk chart data:', data);
            if (data.success && data.data && document.getElementById('atRiskChart')) {
                const labels = Object.keys(data.data);
                const counts = labels.map(label => Array.isArray(data.data[label]) ? data.data[label].length : 0);

                const ctx = document.getElementById('atRiskChart').getContext('2d');
                if (charts.atRisk) {
                    charts.atRisk.destroy();
                }

                charts.atRisk = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Employees at Risk',
                            data: counts,
                            backgroundColor: ['#e74c3c', '#f1c40f', '#2ecc71'],
                            borderColor: '#ffffff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: context => `${context.label}: ${context.parsed}`
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading at-risk employees chart:', error));
}

/**
 * Load gender distribution chart
 */
function loadGenderChart() {
    console.log('Loading gender distribution...');
    fetch('./api/gender_distribution.php')
        .then(response => response.json())
        .then(data => {
            console.log('Gender data:', data);
            if (data.success && data.data && document.getElementById('genderChart')) {
                const labels = data.data.map(item => item.category || item.department || item.gender);
                const counts = data.data.map(item => parseInt(item.count));
                
                const ctx = document.getElementById('genderChart').getContext('2d');
                
                // Destroy existing chart if it exists
                if (charts.gender) {
                    charts.gender.destroy();
                }
                
                charts.gender = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Distribution',
                            data: counts,
                            backgroundColor: [
                                '#2ecc71',
                                '#3498db',
                                '#e74c3c',
                                '#f39c12',
                                '#9b59b6'
                            ],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading gender chart:', error));
}

/**
 * Load age/tenure distribution chart
 */
function loadAgeChart() {
    console.log('Loading age distribution...');
    fetch('./api/age_distribution.php')
        .then(response => response.json())
        .then(data => {
            console.log('Age data:', data);
            if (data.success && data.data && document.getElementById('ageChart')) {
                const labels = data.data.map(item => item.tenure_group || item.age_group);
                const counts = data.data.map(item => parseInt(item.count));
                
                const ctx = document.getElementById('ageChart').getContext('2d');
                
                // Destroy existing chart if it exists
                if (charts.age) {
                    charts.age.destroy();
                }
                
                charts.age = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Employees',
                            data: counts,
                            borderColor: '#1976d2',
                            backgroundColor: 'rgba(25, 118, 210, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading age chart:', error));
}

/**
 * Load tenure distribution chart
 */
function loadTenureChart() {
    console.log('Loading tenure distribution...');
    fetch('./api/tenure_distribution.php')
        .then(response => response.json())
        .then(data => {
            console.log('Tenure data:', data);
            if (data.success && data.data && document.getElementById('tenureChart')) {
                const labels = data.data.map(item => item.tenure_range);
                const counts = data.data.map(item => parseInt(item.count));
                
                const ctx = document.getElementById('tenureChart').getContext('2d');
                
                // Destroy existing chart if it exists
                if (charts.tenure) {
                    charts.tenure.destroy();
                }
                
                charts.tenure = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Employees',
                            data: counts,
                            backgroundColor: '#27ae60',
                            borderColor: '#2c3e50',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading tenure chart:', error));
}

/**
 * Load attrition data
 */
function loadAttritionData() {
    console.log('Loading attrition data...');
    fetch('./api/attrition_data.php')
        .then(response => response.json())
        .then(data => {
            console.log('Attrition data:', data);
            if (data.success) {
                // Load attrition rate
                fetch('./api/attrition_data.php?rate=1')
                    .then(r => r.json())
                    .then(rateData => {
                        if (document.getElementById('attrition-rate')) {
                            document.getElementById('attrition-rate').textContent = (rateData.data || 0).toFixed(2) + '%';
                        }
                    });
                
                // Load separated employees table
                fetch('./api/at_risk_employees.php')
                    .then(r => r.json())
                    .then(empData => {
                        if (empData.success && document.getElementById('separated-employees-table')) {
                            const table = document.getElementById('separated-employees-table');
                            table.innerHTML = '';
                            
                            if (empData.data && empData.data.length > 0) {
                                empData.data.forEach(emp => {
                                    const row = table.insertRow();
                                    row.innerHTML = `
                                        <td>${emp.name || ''}</td>
                                        <td>${emp.position || ''}</td>
                                        <td>${emp.department || ''}</td>
                                        <td>${emp.risk_level || ''}</td>
                                        <td>${emp.performance_score || 'N/A'}</td>
                                    `;
                                });
                            }
                        }
                    });
            }
        })
        .catch(error => console.error('Error loading attrition:', error));
}

/**
 * Load diversity data
 */
function loadDiversityData() {
    console.log('Loading diversity data...');
    // Load salary statistics
    fetch('./api/salary_statistics.php')
        .then(response => response.json())
        .then(data => {
            console.log('Salary stats:', data);
            if (data.success && document.getElementById('salary-stats-table')) {
                const table = document.getElementById('salary-stats-table');
                table.innerHTML = '';
                
                if (data.data && data.data.length > 0) {
                    data.data.forEach(dept => {
                        const row = table.insertRow();
                        row.innerHTML = `
                            <td>${dept.department}</td>
                            <td>${dept.count || 0}</td>
                            <td>${dept.positions || 'N/A'}</td>
                            <td>-</td>
                            <td>-</td>
                        `;
                    });
                }
            }
        })
        .catch(error => console.error('Error loading diversity:', error));
}

/**
 * Load performance data
 */
function loadPerformanceData() {
    console.log('Loading performance data...');
    fetch('./api/performance_distribution.php')
        .then(response => response.json())
        .then(data => {
            console.log('Performance data:', data);
            if (data.success && data.data && document.getElementById('performanceDistChart')) {
                const labels = data.data.map(item => item.performance_level);
                const counts = data.data.map(item => parseInt(item.count));
                
                const ctx = document.getElementById('performanceDistChart').getContext('2d');
                
                // Destroy existing chart if it exists
                if (charts.performance) {
                    charts.performance.destroy();
                }
                
                charts.performance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Employees',
                            data: counts,
                            backgroundColor: ['#27ae60', '#2ecc71', '#f39c12', '#e74c3c', '#c0392b'],
                            borderColor: '#2c3e50',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Load at-risk employees
            loadAtRiskEmployees();
        })
        .catch(error => console.error('Error loading performance:', error));
}

/**
 * Load at-risk employees table
 */
function loadAtRiskEmployees() {
    fetch('./api/at_risk_employees.php')
        .then(response => response.json())
        .then(data => {
            console.log('At-risk employees:', data);
            if (data.success && document.getElementById('at-risk-table')) {
                const table = document.getElementById('at-risk-table');
                table.innerHTML = '';
                
                if (data.data && data.data.length > 0) {
                    data.data.forEach(emp => {
                        const row = table.insertRow();
                        row.innerHTML = `
                            <td>${emp.name || ''}</td>
                            <td>${emp.department || ''}</td>
                            <td>${emp.position || ''}</td>
                            <td>${emp.performance_score || 'N/A'}</td>
                            <td>-</td>
                            <td>${emp.tenure_years || 0}</td>
                            <td><span class="badge badge-${emp.risk_level === 'High' ? 'danger' : emp.risk_level === 'Medium' ? 'warning' : 'success'}">${emp.risk_level || 'N/A'}</span></td>
                        `;
                    });
                }
            }
        })
        .catch(error => console.error('Error loading at-risk employees:', error));
}

/**
 * Load reports data
 */
function loadReportsData() {
    console.log('Loading reports data...');
    // This would load custom report data when filters are applied
}

/**
 * Start and update the clock
 */
function startClock() {
    const clock = document.getElementById('clock');
    if (!clock) return;
    
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        clock.textContent = `${hours}:${minutes}:${seconds}`;
    }
    
    updateClock();
    setInterval(updateClock, 1000);
}

/**
 * Generate custom report
 */
function generateCustomReport() {
    const filters = {
        department: document.getElementById('filter-department')?.value || '',
        employment_type: document.getElementById('filter-employment')?.value || '',
        hire_date_from: document.getElementById('filter-hire-from')?.value || '',
        hire_date_to: document.getElementById('filter-hire-to')?.value || ''
    };
    
    console.log('Generating report with filters:', filters);
    
    fetch('./api/custom_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(filters)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Report data:', data);
        if (data.success && document.getElementById('custom-report-table')) {
            const table = document.getElementById('custom-report-table');
            table.innerHTML = '';
            
            if (data.data && data.data.length > 0) {
                data.data.forEach(emp => {
                    const row = table.insertRow();
                    row.innerHTML = `
                        <td>${emp.employee_id || ''}</td>
                        <td>${emp.full_name || ''}</td>
                        <td>-</td>
                        <td>${emp.department || ''}</td>
                        <td>${emp.position || ''}</td>
                        <td>${emp.date_hired || ''}</td>
                        <td>${emp.employment_status || ''}</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    `;
                });
                
                const count = document.getElementById('report-count');
                if (count) {
                    count.textContent = `Total: ${data.data.length} employees`;
                }
            }
        }
    })
    .catch(error => console.error('Error generating report:', error));
}

/**
 * Filter risk level
 */
function filterRiskLevel(btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    const riskLevel = btn.getAttribute('data-risk');
    console.log('Filtering by risk level:', riskLevel);
    
    const rows = document.querySelectorAll('#at-risk-table tbody tr');
    rows.forEach(row => {
        if (riskLevel === 'all') {
            row.style.display = '';
        } else {
            const risk = row.querySelector('td:last-child').textContent;
            row.style.display = risk.includes(riskLevel) ? '' : 'none';
        }
    });
}

/**
 * Clear filters
 */
function clearFilters() {
    document.getElementById('filter-department').value = '';
    document.getElementById('filter-employment').value = '';
    document.getElementById('filter-hire-from').value = '';
    document.getElementById('filter-hire-to').value = '';
    
    document.getElementById('custom-report-table').innerHTML = '<tr><td colspan="10" class="text-center">Use filters above to generate a report</td></tr>';
    document.getElementById('report-count').textContent = '';
}

/**
 * Export custom report
 */
function exportCustomReport() {
    console.log('Exporting report...');
    alert('Export functionality coming soon!');
}
