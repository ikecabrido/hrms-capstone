/**
 * Workforce Analytics Dashboard Application
 * Main JavaScript file for AJAX calls and Chart.js visualizations
 */

const API_BASE = '../api/';
const chartInstances = window.chartInstances || {};

// ============ INITIALIZATION ============
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadDashboard();
    startClock();
});

/**
 * Initialize all event listeners
 */
function initializeEventListeners() {
    // Tab switching for horizontal nav (if exists)
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });
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
 * Toggle menu button
 */
function toggleMenu() {
    console.log('Menu button clicked');
    // Add menu functionality here
}

/**
 * Toggle sidebar visibility
 */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.getElementById('hamburger');
    sidebar.classList.toggle('active');
    hamburger.classList.toggle('active');
}

/**
 * Switch between tabs
 */
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tabName).classList.add('active');

    // Update active buttons (sidebar nav)
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.nav-btn[data-tab="${tabName}"]`).classList.add('active');

    // Update active buttons (horizontal tab-btn if exists)
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const tabBtn = document.querySelector(`.tab-btn[data-tab="${tabName}"]`);
    if (tabBtn) {
        tabBtn.classList.add('active');
    }

    // Load data for specific tabs
    if (tabName === 'attrition') {
        loadAttritionData();
    } else if (tabName === 'diversity') {
        loadDiversityData();
    } else if (tabName === 'performance') {
        loadPerformanceData();
    }
}

// ============ DASHBOARD TAB ============
/**
 * Load dashboard data
 */
function loadDashboard() {
    loadDashboardMetrics();
    loadDepartmentChart();
    loadAtRiskChart();
}

/**
 * Load dashboard metrics
 */
function loadDashboardMetrics() {
    fetch(API_BASE + 'dashboard_metrics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const metrics = data.data;
                document.getElementById('total-employees').textContent = metrics.total_employees || 0;
                document.getElementById('attendance-rate').textContent = (metrics.attendance_rate || 0).toFixed(1) + '%';
                const lateCount = metrics.late_count || 0;
                const absentCount = metrics.absent_count || 0;
                document.getElementById('late-absent').textContent = `${lateCount + absentCount} (${lateCount} late / ${absentCount} absent)`;
                document.getElementById('avg-performance').textContent = (metrics.avg_performance || 0).toFixed(2) + ' / 5';
                document.getElementById('turnover-rate').textContent = (metrics.turnover_rate || 0).toFixed(2) + '%';

                loadAttendanceChart(metrics.attendance_breakdown || []);
                loadPerformanceRatingChart(metrics.performance_ratings || []);
                loadTurnoverChart(metrics.turnover_trend || []);
            }
        })
        .catch(error => console.error('Error loading metrics:', error));
}

/**
 * Load department distribution chart
 */
function loadDepartmentChart() {
    fetch(API_BASE + 'department_distribution.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.data.map(item => item.department);
                const counts = data.data.map(item => item.count);

                const ctx = document.getElementById('departmentChart').getContext('2d');
                
                if (chartInstances.department) {
                    chartInstances.department.destroy();
                }

                chartInstances.department = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Employees',
                            data: counts,
                            backgroundColor: [
                                '#3498db',
                                '#2ecc71',
                                '#e74c3c',
                                '#f39c12',
                                '#9b59b6',
                                '#1abc9c'
                            ],
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
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading department chart:', error));
}

/**
 * Load employees at risk chart
 */
function loadAtRiskChart() {
    if (!document.getElementById('atRiskChart')) return;

    fetch(API_BASE + 'at_risk_employees.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const labels = Object.keys(data.data);
                const counts = labels.map(label => Array.isArray(data.data[label]) ? data.data[label].length : 0);

                const ctx = document.getElementById('atRiskChart').getContext('2d');
                if (chartInstances.atRisk) {
                    chartInstances.atRisk.destroy();
                }

                chartInstances.atRisk = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Employees at Risk',
                            data: counts,
                            fill: true,
                            tension: 0.35,
                            backgroundColor: 'rgba(231, 76, 60, 0.15)',
                            borderColor: '#e74c3c',
                            pointBackgroundColor: '#e74c3c',
                            pointBorderColor: '#ffffff',
                            pointRadius: 5,
                            pointHoverRadius: 7
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
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
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
 * Load attendance breakdown chart
 */
function loadAttendanceChart(attendanceData) {
    if (!document.getElementById('attendanceChart')) return;

    const labels = attendanceData.map(item => item.status || item.category || 'Unknown');
    const counts = attendanceData.map(item => parseInt(item.count) || 0);
    const ctx = document.getElementById('attendanceChart').getContext('2d');

    if (chartInstances.attendance) {
        chartInstances.attendance.destroy();
    }

    chartInstances.attendance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: ['#2ecc71', '#f1c40f', '#e74c3c', '#3498db'],
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

/**
 * Load performance rating distribution chart
 */
function loadPerformanceRatingChart(ratingData) {
    if (!document.getElementById('performanceChart')) return;

    const labels = ratingData.map(item => item.rating || item.performance_level || 'Unknown');
    const counts = ratingData.map(item => parseInt(item.count) || 0);
    const ctx = document.getElementById('performanceChart').getContext('2d');

    if (chartInstances.performance) {
        chartInstances.performance.destroy();
    }

    chartInstances.performance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Rating Count',
                data: counts,
                backgroundColor: '#8e44ad',
                borderColor: '#6441a5',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
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

/**
 * Load turnover trend chart
 */
function loadTurnoverChart(turnoverData) {
    if (!document.getElementById('turnoverChart')) return;

    const labels = turnoverData.map(item => item.month);
    const counts = turnoverData.map(item => parseInt(item.count) || 0);
    const ctx = document.getElementById('turnoverChart').getContext('2d');

    if (chartInstances.turnover) {
        chartInstances.turnover.destroy();
    }

    chartInstances.turnover = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Turnover',
                data: counts,
                borderColor: '#e67e22',
                backgroundColor: 'rgba(230, 126, 34, 0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
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

/**
 * Load gender distribution chart
 */
function loadGenderChart() {
    fetch(API_BASE + 'gender_distribution.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const validItems = data.data.filter(item => item.gender && item.gender.trim() !== '');
                if (!validItems.length) {
                    console.warn('No valid gender categories to render.');
                    return;
                }

                const labels = validItems.map(item => item.gender);
                const counts = validItems.map(item => item.count);

                const ctx = document.getElementById('genderChart').getContext('2d');
                
                if (chartInstances.gender) {
                    chartInstances.gender.destroy();
                }

                chartInstances.gender = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: counts,
                            backgroundColor: [
                                '#3498db',
                                '#e74c3c',
                                '#bdc3c7'
                            ],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
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
 * Load age group distribution chart
 */
function loadAgeChart() {
    fetch(API_BASE + 'age_distribution.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.data.map(item => item.age_group);
                const counts = data.data.map(item => item.count);

                const ctx = document.getElementById('ageChart').getContext('2d');
                
                if (chartInstances.age) {
                    chartInstances.age.destroy();
                }

                chartInstances.age = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Employees',
                            data: counts,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#3498db',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
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
    fetch(API_BASE + 'tenure_distribution.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.data.map(item => item.tenure_range);
                const counts = data.data.map(item => item.count);

                const ctx = document.getElementById('tenureChart').getContext('2d');
                
                if (chartInstances.tenure) {
                    chartInstances.tenure.destroy();
                }

                chartInstances.tenure = new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Employees',
                            data: counts,
                            borderColor: '#9b59b6',
                            backgroundColor: 'rgba(155, 89, 182, 0.2)',
                            borderWidth: 2,
                            pointBackgroundColor: '#9b59b6',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            r: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading tenure chart:', error));
}

// ============ ATTRITION TAB ============
/**
 * Load attrition data
 */
function loadAttritionData() {
    const year = document.getElementById('attrition-year').value || new Date().getFullYear();
    
    fetch(API_BASE + 'attrition_data.php?year=' + year)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('attrition-rate').textContent = data.data.attrition_rate + '%';
                loadAttritionChart(data.data.attrition_data);
                loadSeparatedEmployeesTable(data.data.separated_employees);
            }
        })
        .catch(error => console.error('Error loading attrition data:', error));

    loadPerformanceDistributionChart();
    loadAtRiskEmployees();
}

/**
 * Load attrition chart
 */
function loadAttritionChart(data) {
    // Group data by month
    const monthlyData = {};
    data.forEach(item => {
        if (!monthlyData[item.month]) {
            monthlyData[item.month] = {};
        }
        monthlyData[item.month][item.employment_status] = item.count;
    });

    const months = Object.keys(monthlyData).sort();
    const resigned = months.map(month => monthlyData[month]['Resigned'] || 0);
    const terminated = months.map(month => monthlyData[month]['Terminated'] || 0);
    const retired = months.map(month => monthlyData[month]['Retired'] || 0);

    const ctx = document.getElementById('attritionChart').getContext('2d');
    
    if (chartInstances.attrition) {
        chartInstances.attrition.destroy();
    }

    chartInstances.attrition = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Resigned',
                    data: resigned,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderWidth: 2,
                    tension: 0.4
                },
                {
                    label: 'Terminated',
                    data: terminated,
                    borderColor: '#c0392b',
                    backgroundColor: 'rgba(192, 57, 43, 0.1)',
                    borderWidth: 2,
                    tension: 0.4
                },
                {
                    label: 'Retired',
                    data: retired,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
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

/**
 * Load separated employees table
 */
function loadSeparatedEmployeesTable(employees) {
    const tbody = document.getElementById('separated-employees-table');
    tbody.innerHTML = '';

    if (employees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No separated employees found</td></tr>';
        return;
    }

    employees.slice(0, 20).forEach(emp => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${emp.name}</td>
            <td>${emp.position}</td>
            <td>${emp.department}</td>
            <td><span class="status-${emp.employment_status.toLowerCase()}">${emp.employment_status}</span></td>
            <td>${formatDate(emp.separation_date)}</td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Load performance distribution chart
 */
function loadPerformanceDistributionChart() {
    fetch(API_BASE + 'performance_distribution.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.data.map(item => item.performance_level);
                const counts = data.data.map(item => item.count);

                const ctx = document.getElementById('performanceChart').getContext('2d');
                
                if (chartInstances.performance) {
                    chartInstances.performance.destroy();
                }

                chartInstances.performance = new Chart(ctx, {
                    type: 'horizontalBar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Employees',
                            data: counts,
                            backgroundColor: [
                                '#27ae60',
                                '#2ecc71',
                                '#f39c12',
                                '#e67e22',
                                '#e74c3c'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading performance chart:', error));
}

/**
 * Load at-risk employees
 */
function loadAtRiskEmployees() {
    fetch(API_BASE + 'at_risk_employees.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allAtRiskEmployees = data.data;
                displayAtRiskEmployees('all');
            }
        })
        .catch(error => console.error('Error loading at-risk employees:', error));
}

/**
 * Filter at-risk employees by risk level
 */
function filterRiskLevel(btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const riskLevel = btn.dataset.risk;
    displayAtRiskEmployees(riskLevel);
}

/**
 * Display at-risk employees in table
 */
function displayAtRiskEmployees(riskLevel) {
    const tbody = document.getElementById('at-risk-table');
    tbody.innerHTML = '';

    let employees = [];
    if (riskLevel === 'all') {
        Object.values(allAtRiskEmployees).forEach(group => {
            employees = employees.concat(group);
        });
    } else {
        employees = allAtRiskEmployees[riskLevel] || [];
    }

    if (employees.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center">No employees found in this category</td></tr>`;
        return;
    }

    employees.forEach(emp => {
        const riskClass = 'risk-' + emp.risk_level.toLowerCase();
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${emp.name}</td>
            <td>${emp.department}</td>
            <td>${emp.position}</td>
            <td>${emp.performance_score.toFixed(2)} / 5</td>
            <td>${emp.absence_days}</td>
            <td>${emp.tenure_years}</td>
            <td><span class="${riskClass}">${emp.risk_level} Risk</span></td>
        `;
        tbody.appendChild(row);
    });
}

// ============ DIVERSITY TAB ============
/**
 * Load diversity data
 */
function loadDiversityData() {
    loadGenderDiversityChart();
    loadAgeDiversityChart();
    loadDepartmentDiversityChart();
    loadSalaryStatisticsTable();
}

/**
 * Load gender diversity chart
 */
function loadGenderDiversityChart() {
    fetch(API_BASE + 'gender_distribution.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const validItems = data.data.filter(item => item.gender && item.gender.trim() !== '');
                if (!validItems.length) {
                    console.warn('No valid gender categories to render.');
                    return;
                }

                const labels = validItems.map(item => item.gender);
                const counts = validItems.map(item => item.count);

                const ctx = document.getElementById('genderDiversityChart').getContext('2d');
                
                if (chartInstances.genderDiversity) {
                    chartInstances.genderDiversity.destroy();
                }

                chartInstances.genderDiversity = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: counts,
                            backgroundColor: ['#3498db', '#e74c3c', '#bdc3c7'],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading gender diversity chart:', error));
}

/**
 * Load age diversity chart
 */
function loadAgeDiversityChart() {
    fetch(API_BASE + 'age_distribution.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.data.map(item => item.age_group);
                const counts = data.data.map(item => item.count);

                const ctx = document.getElementById('ageDiversityChart').getContext('2d');
                
                if (chartInstances.ageDiversity) {
                    chartInstances.ageDiversity.destroy();
                }

                chartInstances.ageDiversity = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
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
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading age diversity chart:', error));
}

/**
 * Load department diversity chart
 */
function loadDepartmentDiversityChart() {
    fetch(API_BASE + 'department_distribution.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.data.map(item => item.department);
                const counts = data.data.map(item => item.count);

                const ctx = document.getElementById('departmentDiversityChart').getContext('2d');
                
                if (chartInstances.departmentDiversity) {
                    chartInstances.departmentDiversity.destroy();
                }

                chartInstances.departmentDiversity = new Chart(ctx, {
                    type: 'polarArea',
                    data: {
                        labels: labels,
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
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading department diversity chart:', error));
}

/**
 * Load salary statistics table
 */
function loadSalaryStatisticsTable() {
    fetch(API_BASE + 'salary_statistics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('salary-stats-table');
                tbody.innerHTML = '';

                data.data.forEach(stat => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${stat.department}</td>
                        <td>${stat.count}</td>
                        <td>$${parseFloat(stat.min_salary).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td>$${parseFloat(stat.max_salary).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td>$${parseFloat(stat.avg_salary).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    `;
                    tbody.appendChild(row);
                });
            }
        })
        .catch(error => console.error('Error loading salary statistics:', error));
}

// ============ PERFORMANCE TAB ============
/**
 * Load performance data
 */
function loadPerformanceData() {
    loadPerformanceDistChart();
    loadSalaryDistributionChart();
}

/**
 * Load performance distribution chart
 */
function loadPerformanceDistChart() {
    fetch(API_BASE + 'performance_distribution.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.data.map(item => item.performance_level);
                const counts = data.data.map(item => item.count);

                const ctx = document.getElementById('performanceDistChart').getContext('2d');
                
                if (chartInstances.performanceDist) {
                    chartInstances.performanceDist.destroy();
                }

                chartInstances.performanceDist = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: counts,
                            backgroundColor: [
                                '#27ae60',
                                '#2ecc71',
                                '#f39c12',
                                '#e67e22',
                                '#e74c3c'
                            ],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading performance distribution:', error));
}

/**
 * Load salary distribution chart
 */
function loadSalaryDistributionChart() {
    fetch(API_BASE + 'salary_statistics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.data.map(item => item.department);
                const avgSalaries = data.data.map(item => parseFloat(item.avg_salary));

                const ctx = document.getElementById('salaryChart').getContext('2d');
                
                if (chartInstances.salary) {
                    chartInstances.salary.destroy();
                }

                chartInstances.salary = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Average Salary',
                            data: avgSalaries,
                            backgroundColor: '#3498db',
                            borderColor: '#2980b9',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
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
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading salary distribution:', error));
}

// ============ CUSTOM REPORTS ============
/**
 * Generate custom report
 */
function generateCustomReport() {
    const department = document.getElementById('filter-department').value;
    const employment = document.getElementById('filter-employment').value;
    const hireFrom = document.getElementById('filter-hire-from').value;
    const hireTo = document.getElementById('filter-hire-to').value;

    let params = new URLSearchParams();
    if (department) params.append('department', department);
    if (employment) params.append('employment_type', employment);
    if (hireFrom) params.append('hire_date_from', hireFrom);
    if (hireTo) params.append('hire_date_to', hireTo);

    fetch(API_BASE + 'custom_report.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                customReportData = data.data;
                displayCustomReport(data.data);
                document.getElementById('report-count').textContent = `Total Records: ${data.total_records}`;
            }
        })
        .catch(error => console.error('Error generating custom report:', error));
}

/**
 * Display custom report in table
 */
function displayCustomReport(employees) {
    const tbody = document.getElementById('custom-report-table');
    tbody.innerHTML = '';

    if (employees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center">No records found</td></tr>';
        return;
    }

    employees.forEach(emp => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${emp.id}</td>
            <td>${emp.name}</td>
            <td>${emp.gender}</td>
            <td>${emp.department}</td>
            <td>${emp.position}</td>
            <td>${formatDate(emp.hire_date)}</td>
            <td><span class="status-${emp.employment_status.toLowerCase()}">${emp.employment_status}</span></td>
            <td>$${parseFloat(emp.salary).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td>${emp.performance_score.toFixed(2)} / 5</td>
            <td>${emp.absence_days}</td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Clear report filters
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
 * Export custom report as CSV
 */
function exportCustomReport() {
    if (customReportData.length === 0) {
        alert('Please generate a report first');
        return;
    }

    let csv = 'ID,Name,Gender,Department,Position,Hire Date,Status,Salary,Performance,Absence Days\n';
    
    customReportData.forEach(emp => {
        csv += `"${emp.id}","${emp.name}","${emp.gender}","${emp.department}","${emp.position}","${emp.hire_date}","${emp.employment_status}","${emp.salary}","${emp.performance_score}","${emp.absence_days}"\n`;
    });

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'HR_Report_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}

/**
 * Export complete dashboard report
 */
function exportReport() {
    window.print();
}

// ============ UTILITY FUNCTIONS ============
/**
 * Format date to readable format
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

/**
 * Format currency
 */
function formatCurrency(value) {
    return '$' + parseFloat(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
