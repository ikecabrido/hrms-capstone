<?php
$totalFailedActions = 0;
$databaseFile = __DIR__ . '/../../auth/database.php';
if (file_exists($databaseFile)) {
    require_once $databaseFile;
    try {
        $db = \Database::getInstance()->getConnection();
        if ($db) {
            $failedStmt = $db->prepare("SELECT COUNT(*) FROM wfa_performance_actions WHERE LOWER(status) = 'failed'");
            $failedStmt->execute();
            $totalFailedActions = (int)$failedStmt->fetchColumn();
        }
    } catch (Exception $e) {
        // fallback to 0 when action tracking DB query fails
        $totalFailedActions = 0;
    }
}
?>
<!-- TAB: PERFORMANCE & DEVELOPMENT -->
<div class="wfa-container" id="performanceContainer">
    <div class="wfa-loading">
        <i class="fas fa-spinner fa-spin"></i> Loading Performance Data...
    </div>
</div>

<!-- Action Details Modal -->
<div class="modal fade" id="actionDetailModal" tabindex="-1" role="dialog" aria-labelledby="actionDetailModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="actionDetailModalTitle"><i class="fas fa-info-circle"></i> Action Details</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="actionDetailModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
async function loadPerformanceTab() {
    console.log('loadPerformanceTab() called');
    const container = document.getElementById('performanceContainer');
    
    if (!container) {
        console.error('Performance container not found');
        return;
    }
    
    try {
        const basePath = '/capstone_hr_management_system';
        console.log('Fetching employee and appraisals data...');
        
        const [employeesResponse, appraisalsResponse] = await Promise.all([
            fetch(`${basePath}/api/wfa/employees_data.php`, { cache: 'no-store' }),
            fetch(`${basePath}/api/wfa/appraisals_data.php`, { cache: 'no-store' })
        ]);

        if (!employeesResponse.ok) {
            throw new Error(`Employees API Error: ${employeesResponse.status}`);
        }
        if (!appraisalsResponse.ok) {
            throw new Error(`Appraisals API Error: ${appraisalsResponse.status}`);
        }

        const employeesData = await employeesResponse.json();
        const appraisalsData = await appraisalsResponse.json();
        console.log('Employee data:', employeesData);
        console.log('Appraisals data:', appraisalsData);

        const employees = employeesData.data?.employees || [];
        const appraisals = appraisalsData.data?.appraisals || [];
        const totalEmployees = employees.length;
        const totalAppraisals = appraisals.length;
        const totalFailedActions = <?php echo $totalFailedActions; ?>;
        
        // Calculate performance metrics
        const activeEmployees = employees.filter(e => e.employment_status === 'Active').length;
        const avgTenure = totalEmployees > 0 ? (employees.reduce((sum, e) => sum + (parseInt(e.years_employed) || 0), 0) / totalEmployees).toFixed(1) : 0;
        
        // Group by department
        const deptStats = {};
        employees.forEach(emp => {
            if (!deptStats[emp.department]) {
                deptStats[emp.department] = { count: 0, positions: new Set() };
            }
            deptStats[emp.department].count++;
            deptStats[emp.department].positions.add(emp.position);
        });
        
        let html = `
            <!-- Performance Metrics -->
            <div class="wfa-metrics-grid">
                <div class="wfa-metric-card success">
                    <div class="wfa-metric-label">Total Appraisals</div>
                    <div class="wfa-metric-value">${totalAppraisals}</div>
                    <div class="wfa-metric-change">Organization</div>
                </div>
                
                <div class="wfa-metric-card info">
                    <div class="wfa-metric-label">Total Completed Action</div>
                    <div class="wfa-metric-value">${activeEmployees}</div>
                    <div class="wfa-metric-change">Completed Actions</div>
                </div>
                
                <div class="wfa-metric-card warning">
                    <div class="wfa-metric-label">Expected Turnover</div>
                    <div class="wfa-metric-value">${totalAppraisals}</div>
                    <div class="wfa-metric-change">Next Month</div>
                </div>
                
                <div class="wfa-metric-card">
                    <div class="wfa-metric-label">Total Failed</div>
                    <div class="wfa-metric-value">${totalFailedActions}</div>
                    <div class="wfa-metric-change">Failed Actions</div>
                </div>
            </div>

            <!-- Performance Assessment & Action Panel (NEW) -->
            <div class="wfa-panel" style="margin-top: 30px; padding: 20px; border: 2px solid #2196F3; border-radius: 8px; background: #f8f9fa;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                    <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-flask-vial" style="color: #2196F3;"></i> Performance Assessment & Action Center
                    </h3>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div style="padding: 15px; background: white; border-radius: 6px; border-left: 4px solid #FF9800;">
                        <div style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 5px;">Select Employee</div>
                        <select id="assessmentEmployeeSelect" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <option value="">-- Choose Employee to Assess --</option>
                        </select>
                    </div>
                </div>
                
                <div id="assessmentContainer" style="display: none;">
                    <!-- Will be populated dynamically -->
                </div>
            </div>

            <!-- Appraisals & Reviews Table -->
            <div class="wfa-table-container" style="margin-top: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 15px;">
                    <h3 style="margin: 0;">Appraisals & Reviews (${appraisals.length})</h3>
                    <button class="wfa-btn wfa-btn-info" onclick="printPerformance()" style="white-space: nowrap;">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
                <table class="wfa-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Review Period</th>
                            <th>Overall Score</th>
                            <th>Review Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        if (appraisals.length > 0) {
            appraisals.forEach(appraisal => {
                const score = parseFloat(appraisal.overall_score) || 0;
                const scoreLevel = score >= 4 ? 'high' : score >= 3 ? 'medium' : 'low';
                const reviewDate = appraisal.review_date ? new Date(appraisal.review_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
                const daysAgo = appraisal.review_date ? Math.floor((new Date() - new Date(appraisal.review_date)) / (1000 * 60 * 60 * 24)) : null;
                const statusLabel = daysAgo !== null && daysAgo <= 90 ? 'Recent' : 'Past';
                
                html += `
                    <tr>
                        <td><strong>${appraisal.full_name || 'N/A'}</strong><br><small>${appraisal.position || 'N/A'}</small></td>
                        <td>${appraisal.department || 'N/A'}</td>
                        <td><span class="review-badge ${String(appraisal.review_period || 'Unknown').toLowerCase()}">${appraisal.review_period || 'Unknown'}</span></td>
                        <td><span class="font-weight-bold ${scoreLevel}">${score.toFixed(1)}/5.0</span></td>
                        <td>${reviewDate}</td>
                        <td><span class="status-pill ${statusLabel === 'Recent' ? 'active' : 'inactive'}">${statusLabel}</span></td>
                        <td>
                            <button class="btn btn-sm btn-secondary view-appraisal-btn" data-appraisal-id="${appraisal.appraisal_id}" title="View"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>
                `;
            });
        } else {
            html += '<tr><td colspan="7" class="text-center" style="padding: 20px;">No appraisals available</td></tr>';
        }

        html += `
                    </tbody>
                </table>
            </div>

            <!-- View Appraisal Modal -->
            <div class="modal fade" id="viewAppraisalModal" tabindex="-1" role="dialog">
              <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                  <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="viewAppraisalModalTitle"><i class="fas fa-eye"></i> View Appraisal</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body">
                    <div class="row mb-3">
                      <div class="col-md-6">
                        <p><strong>Review Period:</strong> <span id="viewReviewPeriod">N/A</span></p>
                      </div>
                      <div class="col-md-6">
                        <p><strong>Review Date:</strong> <span id="viewReviewDate">N/A</span></p>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-md-12">
                        <p><strong>Overall Score:</strong> <span id="viewOverallScore">N/A</span></p>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-6">
                        <h6>Goals & KPIs</h6>
                        <p id="viewGoalsKPIs" class="mb-3">N/A</p>
                      </div>
                      <div class="col-md-6">
                        <h6>Manager's Evaluation</h6>
                        <p id="viewManagerEvaluation" class="mb-3">N/A</p>
                      </div>
                    </div>
                    <div class="mb-3">
                      <h6>Performance Ratings</h6>
                      <div id="viewPerformanceRatings" class="list-group"></div>
                    </div>
                    <div class="mb-3">
                      <h6>Additional Comments</h6>
                      <p id="viewComments">N/A</p>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Department Stats Table -->
            <div class="wfa-table-container">
                <h3 style="margin-bottom: 15px;">Department Summary</h3>
                <table class="wfa-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Employee Count</th>
                            <th>Unique Positions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        Object.keys(deptStats).sort().forEach(dept => {
            html += `
                <tr>
                    <td><strong>${dept}</strong></td>
                    <td>${deptStats[dept].count}</td>
                    <td>${deptStats[dept].positions.size}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        console.log('HTML generated, inserting into container');
        container.innerHTML = html;

        const appraisalEmployees = getUniqueAppraisalEmployees(appraisals);
        const sourceEmployees = appraisalEmployees.length > 0 ? appraisalEmployees : employees;
        const employeeSelect = document.getElementById('assessmentEmployeeSelect');
        if (employeeSelect && sourceEmployees.length > 0) {
            sourceEmployees.forEach(emp => {
                const option = document.createElement('option');
                option.value = emp.employee_id;
                option.textContent = emp.full_name + (emp.department ? ` (${emp.department})` : '');
                employeeSelect.appendChild(option);
            });
            
            employeeSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    loadPerformanceAssessment(e.target.value);
                }
            });
        }

        const appraisalLookup = appraisals.reduce((map, appraisal) => {
            map[appraisal.appraisal_id] = appraisal;
            return map;
        }, {});

        const formatRatings = ratingsJson => {
            let ratings = [];
            try {
                ratings = JSON.parse(ratingsJson || '[]');
            } catch (e) {
                ratings = [];
            }
            if (!Array.isArray(ratings) || ratings.length === 0) {
                return '<div class="text-muted">No ratings available</div>';
            }
            return ratings.map(r => `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${r.name || 'Category'}</span>
                    <span class="badge badge-primary badge-pill">${r.score ?? '-'} / 5</span>
                </div>
            `).join('');
        };

        const viewButtons = container.querySelectorAll('.view-appraisal-btn');
        viewButtons.forEach(button => {
            button.addEventListener('click', () => {
                const appraisalId = button.dataset.appraisalId;
                const appraisal = appraisalLookup[appraisalId];
                if (!appraisal) return;

                document.getElementById('viewAppraisalModalTitle').textContent = `View Appraisal - ${appraisal.full_name || 'Employee'}`;
                document.getElementById('viewReviewPeriod').textContent = appraisal.review_period || 'N/A';
                document.getElementById('viewReviewDate').textContent = appraisal.review_date ? new Date(appraisal.review_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
                document.getElementById('viewOverallScore').textContent = appraisal.overall_score ? `${parseFloat(appraisal.overall_score).toFixed(2)}/5.0` : 'N/A';
                document.getElementById('viewGoalsKPIs').textContent = appraisal.goals_kpis || 'N/A';
                document.getElementById('viewManagerEvaluation').textContent = appraisal.manager_evaluation || 'N/A';
                document.getElementById('viewComments').textContent = appraisal.comments || 'N/A';
                document.getElementById('viewPerformanceRatings').innerHTML = formatRatings(appraisal.performance_ratings);

                $('#viewAppraisalModal').modal('show');
            });
        });
        
    } catch (error) {
        console.error('Error loading performance data:', error);
        container.innerHTML = '<div style="padding: 20px; color: #d32f2f;">Error loading performance data: ' + error.message + '</div>';
    }
}

function printPerformance() {
    window.print();
}

function getUniqueAppraisalEmployees(appraisals) {
    const uniqueMap = {};
    appraisals.forEach(appraisal => {
        const id = appraisal.employee_id;
        if (!id) return;
        if (!uniqueMap[id]) {
            uniqueMap[id] = {
                employee_id: id,
                full_name: appraisal.full_name || `Employee ${id}`,
                department: appraisal.department || 'N/A',
                position: appraisal.position || 'N/A'
            };
        }
    });
    return Object.values(uniqueMap);
}

/**
 * Load performance assessment for selected employee
 * Fetches root causes and recommended actions from API
 */
async function loadPerformanceAssessment(employeeId) {
    const container = document.getElementById('assessmentContainer');
    
    if (!employeeId) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';
    container.innerHTML = '<div style="padding: 20px; text-align: center;"><i class="fas fa-spinner fa-spin"></i> Analyzing performance...</div>';
    
    try {
        const basePath = '/capstone_hr_management_system';
        const response = await fetch(`${basePath}/workforce/api/wfa/performance_recommendations.php?employee_id=${employeeId}`, {
            cache: 'no-store'
        });
        
        if (!response.ok) {
            throw new Error(`API Error: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.status !== 'success' || !result.data.assessment) {
            throw new Error('Invalid response format');
        }
        
        const assessment = result.data.assessment;
        const rootCauses = assessment.root_causes || [];
        const recommendations = assessment.recommended_actions || [];
        const overallRisk = result.data.overall_risk || 'LOW';
        
        let html = `
            <!-- Assessment Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: white; border-radius: 6px;">
                <div>
                    <h4 style="margin: 0; font-size: 18px;">${assessment.full_name}</h4>
                    <small style="color: #666;">${assessment.position} | ${assessment.department}</small>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 5px;">Performance Status</div>
                    <div style="font-size: 28px; font-weight: bold; color: ${assessment.performance_score >= 4 ? '#4CAF50' : assessment.performance_score >= 3 ? '#FF9800' : '#f44336'};">
                        ${assessment.performance_score.toFixed(2)}/5.0
                    </div>
                    <small style="color: ${assessment.performance_score >= 4 ? '#4CAF50' : assessment.performance_score >= 3 ? '#FF9800' : '#f44336'};">
                        ${assessment.performance_status}
                    </small>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 5px;">Risk Level</div>
                    <div style="padding: 8px 15px; background: ${overallRisk === 'CRITICAL' ? '#f44336' : overallRisk === 'HIGH' ? '#FF9800' : overallRisk === 'MEDIUM' ? '#FFC107' : '#4CAF50'}; color: white; border-radius: 4px; font-weight: bold; min-width: 120px;">
                        ${overallRisk}
                    </div>
                </div>
            </div>
        `;
        
        // Root Causes Section
        if (rootCauses.length > 0) {
            html += `
                <div style="margin-bottom: 20px;">
                    <h5 style="margin-top: 0; color: #d32f2f;"><i class="fas fa-exclamation-circle"></i> Root Causes (${rootCauses.length})</h5>
                    <div style="display: grid; gap: 10px;">
            `;
            
            rootCauses.forEach(cause => {
                const severityColor = cause.severity === 'HIGH' ? '#f44336' : '#FF9800';
                html += `
                    <div style="padding: 12px; background: white; border-left: 4px solid ${severityColor}; border-radius: 4px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; gap: 10px;">
                            <div style="flex: 1;">
                                <div style="font-weight: bold; color: ${severityColor};">${cause.issue}</div>
                                <small style="color: #666; display: block; margin-top: 4px;">${cause.data}</small>
                            </div>
                            <span style="background: ${severityColor}; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                                ${cause.severity}
                            </span>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        }
        
        // Recommended Actions Section
        if (recommendations.length > 0) {
            html += `
                <div style="margin-bottom: 20px;">
                    <h5 style="margin-top: 0; color: #2196F3;"><i class="fas fa-lightbulb"></i> Recommended Actions (${recommendations.length})</h5>
                    <div style="display: grid; gap: 10px;">
            `;
            
            recommendations.forEach((rec, index) => {
                const priorityColor = rec.priority === 'CRITICAL' ? '#f44336' : rec.priority === 'HIGH' ? '#FF9800' : rec.priority === 'MEDIUM' ? '#2196F3' : '#4CAF50';
                const buttonClass = rec.priority === 'CRITICAL' ? 'danger' : rec.priority === 'HIGH' ? 'warning' : 'info';
                
                html += `
                    <div style="padding: 15px; background: white; border: 1px solid #ddd; border-radius: 6px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; gap: 10px; margin-bottom: 10px;">
                            <div style="flex: 1;">
                                <div style="font-weight: bold; color: ${priorityColor};">${rec.title}</div>
                                <small style="color: #666; display: block; margin-top: 4px;">${rec.description}</small>
                            </div>
                            <span style="background: ${priorityColor}; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                                ${rec.priority}
                            </span>
                        </div>
                        <div style="margin-top: 10px;">
                            <button class="btn btn-sm btn-${buttonClass}" onclick="createAction('${employeeId}', '${rec.action}', '${rec.title}', '${rec.action_type || ''}', '${rec.priority}')" style="margin-right: 5px;">
                                <i class="fas fa-check-circle"></i> Create Action
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="showActionDetails('${JSON.stringify(rec).replace(/'/g, "&apos;")}')">
                                <i class="fas fa-info-circle"></i> Details
                            </button>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        }
        
        // Active Actions
        const activeActions = result.data.active_actions || [];
        if (activeActions.length > 0) {
            html += `
                <div style="margin-bottom: 20px;">
                    <h5 style="margin-top: 0; color: #4CAF50;"><i class="fas fa-tasks"></i> Active Actions (${activeActions.length})</h5>
                    <div style="display: grid; gap: 10px;">
            `;
            
            activeActions.forEach(action => {
                const statusColor = action.status === 'ongoing' ? '#2196F3' : '#FF9800';
                html += `
                    <div style="padding: 12px; background: white; border-left: 4px solid ${statusColor}; border-radius: 4px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <div style="font-weight: bold;">${action.title}</div>
                                <small style="color: #666;">${action.action_type} | Created: ${new Date(action.created_date).toLocaleDateString()}</small>
                            </div>
                            <span style="background: ${statusColor}; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold;">
                                ${action.status.toUpperCase()}
                            </span>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        }
        
        container.innerHTML = html;
        
    } catch (error) {
        console.error('Error loading assessment:', error);
        container.innerHTML = `<div style="padding: 20px; color: #d32f2f; background: #ffebee; border-radius: 4px;">
            <i class="fas fa-exclamation-triangle"></i> Error loading assessment: ${error.message}
        </div>`;
    }
}

/**
 * Create a performance action for the employee
 */
async function createAction(employeeId, actionKey, actionTitle, actionType, priority) {
    if (!confirm(`Create action: ${actionTitle}?`)) {
        return;
    }
    
    try {
        const basePath = '/capstone_hr_management_system';
        
        // Prepare action data
        const actionData = {
            employee_id: parseInt(employeeId),
            action_type: actionType || 'COACHING',
            title: actionTitle,
            description: actionTitle,
            priority: priority || 'MEDIUM',
            start_date: new Date().toISOString().split('T')[0],
            target_date: new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0],
            reason: 'From Performance Assessment & Action Center',
            notes: 'Auto-created from smart recommendations'
        };
        
        // Send POST request to save action
        const response = await fetch(`${basePath}/workforce/api/wfa/save_performance_action.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(actionData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✓ Action created successfully!\n\nAction: ${actionTitle}\nEmployee ID: ${employeeId}\n\nThe action will now appear in the Action Tracking dashboard.`);
            
            // Reload the assessment to show updated active actions
            loadPerformanceAssessment(employeeId);
            
            // Reload action tracking in workforce if it's open
            if (typeof loadEmployeeActionTracking === 'function') {
                loadEmployeeActionTracking(employeeId);
            }
        } else {
            alert(`Error creating action: ${result.message}`);
        }
        
    } catch (error) {
        console.error('Error creating action:', error);
        alert('Error creating action: ' + error.message);
    }
}

/**
 * Show detailed information about a recommended action
 */
function showActionDetails(actionJson) {
    try {
        const safeJson = actionJson.replace(/&apos;/g, "'");
        const action = JSON.parse(safeJson);

        const htmlParts = [];
        htmlParts.push(`<p><strong>Description:</strong><br>${escapeHtml(action.description || 'No description available.')}</p>`);

        if (action.training_modules && action.training_modules.length > 0) {
            htmlParts.push(`<p><strong>Training Modules:</strong><br>${action.training_modules.map(m => `• ${escapeHtml(m)}`).join('<br>')}</p>`);
        }

        if (action.duration_days) {
            htmlParts.push(`<p><strong>Duration:</strong> ${escapeHtml(action.duration_days.toString())} days</p>`);
        }

        if (action.target_metric) {
            htmlParts.push(`<p><strong>Target:</strong> ${escapeHtml(action.target_metric)}</p>`);
        }

        const bodyHtml = `
            <div>
                <h5>${escapeHtml(action.title || 'Action Detail')}</h5>
                ${htmlParts.join('')}
            </div>
        `;

        const titleEl = document.getElementById('actionDetailModalTitle');
        const bodyEl = document.getElementById('actionDetailModalBody');
        if (titleEl) titleEl.textContent = action.title || 'Action Details';
        if (bodyEl) bodyEl.innerHTML = bodyHtml;

        if (typeof $ === 'function' && typeof $.fn.modal === 'function') {
            $('#actionDetailModal').modal('show');
        } else {
            alert(`${action.title}\n\n${action.description}`);
        }
    } catch (e) {
        console.error('Error displaying details:', e);
        alert('Error displaying details');
    }
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
</script>
