s<!-- TAB: CUSTOM REPORTS -->
<div class="wfa-container" id="reportsContainer">
    <div class="wfa-loading">
        <i class="fas fa-spinner fa-spin"></i> Loading Reports Data...
    </div>
</div>

<script>
class EmployeeReportsManager {
    constructor(containerId) {
        this.containerId = containerId;
        this.allEmployees = [];
        this.attritionData = [];
        this.basePath = '/capstone_hr_management_system';
        this.employeeTotalChart = null;
        
        // New properties for pagination, sorting, filtering
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.selectedRows = new Set();
        this.currentFilteredData = [];
        this.savedTemplates = JSON.parse(localStorage.getItem('reportTemplates') || '[]');
    }

    async loadReportsTab() {
        console.log('loadReportsTab() called');
        const container = document.getElementById(this.containerId);

        if (!container) {
            console.error('Reports container not found');
            return;
        }

        try {
            console.log('Fetching employee and attrition data for reports...');

            const [employeeData, attritionResponse] = await Promise.all([
                this.fetchEmployeeData(),
                fetch(`${this.basePath}/api/wfa/attrition_data.php?year=${new Date().getFullYear()}`)
            ]);
            
            this.allEmployees = employeeData.data?.employees || [];
            
            if (attritionResponse.ok) {
                const attritionJson = await attritionResponse.json();
                this.attritionData = attritionJson.data?.separated_employees || [];
                console.log('Attrition data loaded:', this.attritionData);
            }

            // Get unique departments
            const departments = [...new Set(this.allEmployees.map(e => e.department).filter(d => d))];

            const html = this.generateHTML(departments);

            console.log('Reports HTML generated');
            container.innerHTML = html;

            const generateBtn = document.getElementById('generateReportBtn');
            if (generateBtn) {
                generateBtn.addEventListener('click', () => this.generateReport());
            }

            // Initial statistics and automatically render the report preview
            this.updateReportStats(this.allEmployees);
            this.generateReport();
            this.updateEmployeeTotalChart(this.allEmployees, this.attritionData);
            this.initializeAdditionalCharts();

        } catch (error) {
            console.error('Error loading reports data:', error);
            container.innerHTML = '<div style="padding: 20px; color: #d32f2f;">Error loading reports data: ' + error.message + '</div>';
        }
    }

    async fetchEmployeeData() {
        const response = await fetch(`${this.basePath}/api/wfa/employees_data.php`);
        console.log('Response status:', response.status);

        if (!response.ok) {
            throw new Error(`API Error: ${response.status}`);
        }

        const data = await response.json();
        console.log('Employee data:', data);
        return data;
    }

    generateHTML(departments) {
        const employmentTypes = [...new Set(this.allEmployees.map(e => e.employment_type).filter(Boolean))].sort();

        let html = `
            <div style="margin-bottom: 24px; padding: 24px; background: #f0f5ff; border-radius: 18px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);">
                <div style="display: flex; flex-wrap: wrap; gap: 18px; align-items: center; justify-content: space-between;">
                    <div style="min-width: 280px; max-width: 720px;">
                        <h2 style="margin: 0 0 10px; font-size: 30px; color: #1d2b4f;">Custom HR Reports</h2>
                    </div>
                </div>
            </div>

            <!-- Report Statistics Cards -->
            <div class="wfa-metrics-grid">
                <div class="wfa-metric-card">
                    <div class="wfa-metric-label">Total Employees</div>
                    <div class="wfa-metric-value" id="reportTotalCount">0</div>
                    <div class="wfa-metric-change">In Organization</div>
                </div>

                <div class="wfa-metric-card success">
                    <div class="wfa-metric-label">Active Employees</div>
                    <div class="wfa-metric-value" id="reportActiveCount">0</div>
                    <div class="wfa-metric-change">Currently Active</div>
                </div>

                <div class="wfa-metric-card info">
                    <div class="wfa-metric-label">Filtered Results</div>
                    <div class="wfa-metric-value" id="reportFilteredCount">0</div>
                    <div class="wfa-metric-change">Matching Criteria</div>
                </div>

                <div class="wfa-metric-card warning">
                    <div class="wfa-metric-label">Departments</div>
                    <div class="wfa-metric-value" id="reportDeptCount">0</div>
                    <div class="wfa-metric-change">Unique Departments</div>
                </div>
            </div>

            <!-- Total  Trend -->
            <div class="wfa-chart-card" style="margin-top: 24px; background: white; border-radius: 18px; padding: 24px; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 18px;">
                    <div>
                        <h3 style="margin: 0; font-size: 22px; color: #112239;">Total  Trends</h3>
                        <div style="color: #52606d; font-size: 14px;">Line chart showing total employee headcount over time.</div>
                    </div>
                </div>
                <div style="position: relative; width: 100%; height: 320px;">
                    <canvas id="employeeTotalChart" style="width: 100%; height: 100%;"></canvas>
                </div>
            </div>

            <!-- New Four Charts Section -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 24px; margin-top: 24px;">
                
                <!-- Attrition Trend -->
                <div class="wfa-chart-card" style="background: white; border-radius: 18px; padding: 24px; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);">
                    <div style="margin-bottom: 18px;">
                        <h3 style="margin: 0; font-size: 22px; color: #112239;">Attrition Trend</h3>
                        <div style="color: #52606d; font-size: 14px;">Monthly attrition rate with target benchmark line.</div>
                    </div>
                    <div style="position: relative; width: 100%; height: 280px;">
                        <canvas id="attritionTrendChart" style="width: 100%; height: 100%;"></canvas>
                    </div>
                </div>

            </div>

            <!-- Diversity and Payroll Charts -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 24px; margin-top: 24px;">

                <!-- Diversity Breakdown -->
                <div class="wfa-chart-card" style="background: white; border-radius: 18px; padding: 24px; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);">
                    <div style="margin-bottom: 18px;">
                        <h3 style="margin: 0; font-size: 22px; color: #112239;">Diversity Breakdown</h3>
                        <div style="color: #52606d; font-size: 14px;">Gender and age distribution of the workforce.</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <div style="text-align: center; font-size: 12px; font-weight: 600; margin-bottom: 10px; color: #1f2937;">Gender</div>
                            <div style="position: relative; width: 100%; height: 200px;">
                                <canvas id="genderChart" style="width: 100%; height: 100%;"></canvas>
                            </div>
                        </div>
                        <div>
                            <div style="text-align: center; font-size: 12px; font-weight: 600; margin-bottom: 10px; color: #1f2937;">Age Distribution</div>
                            <div style="position: relative; width: 100%; height: 200px;">
                                <canvas id="ageChart" style="width: 100%; height: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payroll Costs -->
                <div class="wfa-chart-card" style="background: white; border-radius: 18px; padding: 24px; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);">
                    <div style="margin-bottom: 18px;">
                        <h3 style="margin: 0; font-size: 22px; color: #112239;">Payroll Costs</h3>
                        <div style="color: #52606d; font-size: 14px;">Distribution of payroll expenses and budget allocation.</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <div style="text-align: center; font-size: 12px; font-weight: 600; margin-bottom: 10px; color: #1f2937;">Breakdown</div>
                            <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 6px; border-bottom: 1px solid #e5e7eb;">Salaries</td>
                                    <td style="padding: 6px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">65%</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px; border-bottom: 1px solid #e5e7eb;">Benefits</td>
                                    <td style="padding: 6px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">20%</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px;">Overtime</td>
                                    <td style="padding: 6px; text-align: right; font-weight: 600;">15%</td>
                                </tr>
                            </table>
                        </div>
                        <div>
                            <div style="text-align: center; font-size: 12px; font-weight: 600; margin-bottom: 10px; color: #1f2937;">Budget Allocation</div>
                            <div style="position: relative; width: 100%; height: 200px;">
                                <canvas id="payrollChart" style="width: 100%; height: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Report Filters -->
            <div class="wfa-filters-container" style="margin-top: 24px; background: #ffffff; border-radius: 16px; padding: 22px; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04);">
                <!-- Filter Tabs -->
                <div style="display: flex; gap: 12px; margin-bottom: 18px; border-bottom: 2px solid #e2e8f0;">
                    <button class="wfa-filter-tab active" onclick="reportsManager.switchFilterTab('basic')" style="padding: 12px 16px; border: none; background: none; cursor: pointer; font-weight: 600; color: #3b82f6; border-bottom: 3px solid #3b82f6; margin-bottom: -2px;">Basic Filters</button>
                    <button class="wfa-filter-tab" onclick="reportsManager.switchFilterTab('advanced')" style="padding: 12px 16px; border: none; background: none; cursor: pointer; font-weight: 600; color: #64748b;">Advanced Filters</button>
                    <button class="wfa-filter-tab" onclick="reportsManager.switchFilterTab('templates')" style="padding: 12px 16px; border: none; background: none; cursor: pointer; font-weight: 600; color: #64748b;">Saved Templates</button>
                    <button class="wfa-filter-tab" onclick="reportsManager.switchFilterTab('columns')" style="padding: 12px 16px; border: none; background: none; cursor: pointer; font-weight: 600; color: #64748b;">Columns</button>
                </div>

                <!-- Basic Filters Tab -->
                <div id="basicFiltersTab" class="wfa-filter-content">
                    <div class="wfa-filter-row" style="display: flex; flex-wrap: wrap; gap: 18px;">
                        <div class="wfa-filter-group" style="flex: 1 1 220px; min-width: 220px;">
                            <label>Department</label>
                            <select id="reportDeptFilter" class="wfa-filter-select" onchange="reportsManager.generateReport()">
                                <option value="">All Departments</option>`;

        departments.sort().forEach(dept => {
            html += `<option value="${dept}">${dept}</option>`;
        });

        html += `
                            </select>
                        </div>

                        <div class="wfa-filter-group" style="flex: 1 1 220px; min-width: 220px;">
                            <label>Employee Type</label>
                            <select id="reportTypeFilter" class="wfa-filter-select" onchange="reportsManager.generateReport()">
                                <option value="">All Types</option>`;

        employmentTypes.forEach(type => {
            html += `<option value="${type}">${type}</option>`;
        });

        html += `
                            </select>
                        </div>

                        <div class="wfa-filter-group" style="flex: 1 1 220px; min-width: 220px;">
                            <label>Status</label>
                            <select id="reportStatusFilter" class="wfa-filter-select" onchange="reportsManager.generateReport()">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Resigned">Resigned</option>
                                <option value="Terminated">Terminated</option>
                            </select>
                        </div>

                        <div class="wfa-filter-group" style="flex: 1 1 180px; min-width: 180px;">
                            <label>Quick Date Range</label>
                            <select id="reportDateRange" class="wfa-filter-select" onchange="reportsManager.applyDateRange()">
                                <option value="">Custom Dates</option>
                                <option value="30days">Last 30 Days</option>
                                <option value="90days">Last 90 Days</option>
                                <option value="6months">Last 6 Months</option>
                                <option value="1year">Last 1 Year</option>
                                <option value="ytd">Year to Date</option>
                            </select>
                        </div>

                        <div class="wfa-filter-group" style="flex: 1 1 180px; min-width: 180px;">
                            <label>Hire Date From</label>
                            <input type="date" id="reportHireFrom" class="wfa-filter-input" onchange="reportsManager.generateReport()">
                        </div>

                        <div class="wfa-filter-group" style="flex: 1 1 180px; min-width: 180px;">
                            <label>Hire Date To</label>
                            <input type="date" id="reportHireTo" class="wfa-filter-input" onchange="reportsManager.generateReport()">
                        </div>

                        <div class="wfa-filter-group" style="flex: 1 1 320px; min-width: 320px;">
                            <label>Search</label>
                            <input type="text" id="reportSearchFilter" class="wfa-filter-input" placeholder="Search name, email, position" onkeyup="reportsManager.generateReport()">
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters Tab -->
                <div id="advancedFiltersTab" class="wfa-filter-content" style="display: none;">
                    <div class="wfa-filter-row" style="display: flex; flex-wrap: wrap; gap: 18px;">
                        <div class="wfa-filter-group" style="flex: 1 1 180px; min-width: 180px;">
                            <label>Min Salary</label>
                            <input type="number" id="reportMinSalary" class="wfa-filter-input" placeholder="Min" onchange="reportsManager.generateReport()">
                        </div>
                        <div class="wfa-filter-group" style="flex: 1 1 180px; min-width: 180px;">
                            <label>Max Salary</label>
                            <input type="number" id="reportMaxSalary" class="wfa-filter-input" placeholder="Max" onchange="reportsManager.generateReport()">
                        </div>
                        <div class="wfa-filter-group" style="flex: 1 1 220px; min-width: 220px;">
                            <label>Performance Rating (Min)</label>
                            <select id="reportMinPerformance" class="wfa-filter-select" onchange="reportsManager.generateReport()">
                                <option value="">All Ratings</option>
                                <option value="1">1 - Poor</option>
                                <option value="2">2 - Below Average</option>
                                <option value="3">3 - Average</option>
                                <option value="4">4 - Good</option>
                                <option value="5">5 - Excellent</option>
                            </select>
                        </div>
                        <div class="wfa-filter-group" style="flex: 1 1 180px; min-width: 180px;">
                            <label>Min Tenure (Years)</label>
                            <input type="number" id="reportMinTenure" class="wfa-filter-input" min="0" placeholder="0" onchange="reportsManager.generateReport()">
                        </div>
                    </div>
                </div>

                <!-- Saved Templates Tab -->
                <div id="templatesTab" class="wfa-filter-content" style="display: none;">
                    <div style="display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
                        <input type="text" id="templateName" class="wfa-filter-input" placeholder="Enter template name" style="flex: 1 1 200px; min-width: 150px;">
                        <button class="wfa-btn wfa-btn-primary" onclick="reportsManager.saveTemplate()"><i class="fas fa-save"></i> Save Current Filters</button>
                        <button class="wfa-btn wfa-btn-secondary" onclick="reportsManager.clearTemplates()"><i class="fas fa-trash"></i> Clear Templates</button>
                    </div>
                    <div id="templatesList" style="display: grid; gap: 10px;"></div>
                </div>

                <!-- Columns Tab -->
                <div id="columnsTab" class="wfa-filter-content" style="display: none;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" class="reportColumnToggle" value="id" checked onchange="reportsManager.updateVisibleColumns()"> ID</label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" class="reportColumnToggle" value="full_name" checked onchange="reportsManager.updateVisibleColumns()"> Full Name</label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" class="reportColumnToggle" value="position" checked onchange="reportsManager.updateVisibleColumns()"> Position</label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" class="reportColumnToggle" value="department" checked onchange="reportsManager.updateVisibleColumns()"> Department</label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" class="reportColumnToggle" value="employment_type" checked onchange="reportsManager.updateVisibleColumns()"> Employee Type</label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" class="reportColumnToggle" value="email" checked onchange="reportsManager.updateVisibleColumns()"> Email</label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" class="reportColumnToggle" value="contact_number" checked onchange="reportsManager.updateVisibleColumns()"> Contact</label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" class="reportColumnToggle" value="date_hired" checked onchange="reportsManager.updateVisibleColumns()"> Hire Date</label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" class="reportColumnToggle" value="employment_status" checked onchange="reportsManager.updateVisibleColumns()"> Status</label>
                    </div>
                </div>

                <div class="wfa-filter-actions" style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px;">
                    <button id="generateReportBtn" type="button" class="wfa-btn wfa-btn-primary">
                        <i class="fas fa-search"></i> Generate Report
                    </button>
                    <button type="button" class="wfa-btn wfa-btn-secondary" onclick="reportsManager.clearReportFilters()">
                        <i class="fas fa-eraser"></i> Clear Filters
                    </button>
                    <button type="button" class="wfa-btn wfa-btn-success" onclick="reportsManager.exportReportExcel()">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                    <button class="wfa-btn wfa-btn-info" onclick="reportsManager.exportReportPDF()">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                    <button class="wfa-btn wfa-btn-warning" onclick="reportsManager.printReport()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>

                <div id="reportStatusMessage" class="wfa-report-status" style="margin-top:16px;color:#0f172a;background:#eef2ff;border:1px solid #c7d2fe;padding:12px 16px;border-radius:12px;display:inline-block;"><i class="fas fa-info-circle"></i> <span id="dataRefreshTime"></span> Data loaded. Ready to generate reports.</div>
            </div>

            <!-- Employee Report Data Table -->
            <div class="wfa-table-container" style="margin-top: 26px; background: white; padding: 24px; border-radius: 18px; box-shadow: 0 16px 28px rgba(15, 23, 42, 0.05);">
                <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px; align-items: center; margin-bottom: 18px;">
                    <div>
                        <h3 style="margin: 0; font-size: 22px; color: #1f2937;">Report Preview</h3>
                        <div style="color: #475569; font-size: 14px; margin-top: 4px;">Preview filtered employee results before exporting.</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <button class="wfa-btn wfa-btn-secondary" onclick="reportsManager.refreshData()" style="font-size: 12px; padding: 8px 12px;">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <div id="bulkActionsBar" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 12px; justify-content: space-between; flex-wrap: wrap;">
                        <div style="font-weight: 600; color: #166534;"><span id="selectedCount">0</span> employee(s) selected</div>
                        <div style="display: flex; gap: 8px;">
                            <button class="wfa-btn wfa-btn-secondary" onclick="reportsManager.deselectAll()" style="font-size: 12px; padding: 6px 10px;">Deselect All</button>
                        </div>
                    </div>
                </div>

                <!-- Rows Per Page & Sorting -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label style="font-size: 13px; color: #64748b; font-weight: 500;">Rows per page:</label>
                        <select id="rowsPerPage" class="wfa-filter-select" onchange="reportsManager.updatePagination()" style="padding: 6px 12px; font-size: 13px;">
                            <option value="25">25</option>
                            <option value="50" selected>50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div id="currentSort" style="font-size: 12px; color: #64748b;">Sort: None</div>
                </div>

                <!-- Table -->
                <div style="overflow-x: auto;">
                    <table class="wfa-table" id="reportTable" style="min-width: 100%; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="reportsManager.toggleSelectAll()" style="cursor: pointer;">
                                </th>
                                <th onclick="reportsManager.sortReport('id')" style="cursor: pointer; user-select: none;">ID <i class="fas fa-sort" style="font-size: 11px; margin-left: 4px;"></i></th>
                                <th onclick="reportsManager.sortReport('full_name')" style="cursor: pointer; user-select: none;">Full Name <i class="fas fa-sort" style="font-size: 11px; margin-left: 4px;"></i></th>
                                <th onclick="reportsManager.sortReport('position')" style="cursor: pointer; user-select: none;">Position <i class="fas fa-sort" style="font-size: 11px; margin-left: 4px;"></i></th>
                                <th onclick="reportsManager.sortReport('department')" style="cursor: pointer; user-select: none;">Department <i class="fas fa-sort" style="font-size: 11px; margin-left: 4px;"></i></th>
                                <th onclick="reportsManager.sortReport('employment_type')" style="cursor: pointer; user-select: none;">Type <i class="fas fa-sort" style="font-size: 11px; margin-left: 4px;"></i></th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th onclick="reportsManager.sortReport('date_hired')" style="cursor: pointer; user-select: none;">Hire Date <i class="fas fa-sort" style="font-size: 11px; margin-left: 4px;"></i></th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody">
                            <tr><td colspan="10" style="text-align: center; padding: 20px;">Click "Generate Report" to view results</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <div id="paginationControls" style="display: none; margin-top: 18px; display: flex; justify-content: center; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <button class="wfa-btn wfa-btn-secondary" onclick="reportsManager.previousPage()" style="padding: 8px 12px; font-size: 12px;">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <div id="pageInfo" style="font-weight: 600; color: #1f2937; font-size: 13px;">Page 1 of 1</div>
                    <button class="wfa-btn wfa-btn-secondary" onclick="reportsManager.nextPage()" style="padding: 8px 12px; font-size: 12px;">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                    <div style="border-left: 1px solid #e2e8f0; padding-left: 12px; font-size: 12px; color: #64748b;">
                        Total: <span id="totalRecords">0</span> records
                    </div>
                </div>
            </div>
        `;

        return html;
    }

    getFilteredEmployees() {
        const deptFilter = document.getElementById('reportDeptFilter')?.value || '';
        const typeFilter = document.getElementById('reportTypeFilter')?.value || '';
        const statusFilter = document.getElementById('reportStatusFilter')?.value || '';
        const hireFrom = document.getElementById('reportHireFrom')?.value;
        const hireTo = document.getElementById('reportHireTo')?.value;
        const searchFilter = document.getElementById('reportSearchFilter')?.value.toLowerCase().trim() || '';
        const minSalary = parseFloat(document.getElementById('reportMinSalary')?.value) || 0;
        const maxSalary = parseFloat(document.getElementById('reportMaxSalary')?.value) || Infinity;
        const minPerformance = parseInt(document.getElementById('reportMinPerformance')?.value) || 0;
        const minTenure = parseInt(document.getElementById('reportMinTenure')?.value) || 0;

        return this.allEmployees.filter(emp => {
            const deptMatch = !deptFilter || emp.department === deptFilter;
            const typeMatch = !typeFilter || emp.employment_type === typeFilter;
            const statusMatch = !statusFilter || emp.employment_status === statusFilter;
            const hireDate = emp.date_hired ? new Date(emp.date_hired) : null;
            const fromMatch = !hireFrom || (hireDate && hireDate >= new Date(hireFrom));
            const toMatch = !hireTo || (hireDate && hireDate <= new Date(hireTo));
            const searchMatch = !searchFilter || [emp.full_name, emp.email, emp.position, emp.department, emp.employment_type]
                .filter(Boolean).join(' ').toLowerCase().includes(searchFilter);
            const salaryMatch = (emp.salary ? parseFloat(emp.salary) : 0) >= minSalary && (emp.salary ? parseFloat(emp.salary) : 0) <= maxSalary;
            const performanceMatch = !minPerformance || (emp.performance_rating ? parseInt(emp.performance_rating) : 0) >= minPerformance;
            const tenureYears = hireDate ? (new Date() - hireDate) / (365.25 * 24 * 60 * 60 * 1000) : 0;
            const tenureMatch = tenureYears >= minTenure;
            
            return deptMatch && typeMatch && statusMatch && fromMatch && toMatch && searchMatch && salaryMatch && performanceMatch && tenureMatch;
        });
    }

    generateReport() {
        const filtered = this.getFilteredEmployees();
        this.currentFilteredData = filtered;
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';

        this.updateReportTable(filtered);
        this.updateReportStats(filtered);
        this.updateEmployeeTotalChart(filtered);
        this.updateDataRefreshTime();
        this.setReportStatusMessage(`Report generated. Showing ${filtered.length} result${filtered.length === 1 ? '' : 's'}.`);
    }

    updateEmployeeTotalChart(filteredEmployees) {
        const ctx = document.getElementById('employeeTotalChart');
        if (!ctx || typeof Chart === 'undefined') {
            return;
        }

        const chartData = this.buildEmployeeTotalDataset(filteredEmployees);
        const config = {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Total Employees',
                        data: chartData.totalEmployees,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.15)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#ffffff',
                        borderWidth: 3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#1f2937'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#475569' },
                        grid: { color: 'rgba(148, 163, 184, 0.15)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#475569' },
                        grid: { color: 'rgba(148, 163, 184, 0.15)' }
                    }
                }
            }
        };

        if (this.employeeTotalChart) {
            this.employeeTotalChart.data = config.data;
            this.employeeTotalChart.options = config.options;
            this.employeeTotalChart.update();
        } else {
            this.employeeTotalChart = new Chart(ctx.getContext('2d'), config);
        }
    }

    buildEmployeeTotalDataset(employees) {
        const hireDates = employees
            .filter(emp => emp.date_hired)
            .map(emp => new Date(emp.date_hired))
            .filter(date => !isNaN(date));

        const missingHireCount = employees.filter(emp => !emp.date_hired || emp.date_hired.trim() === '').length;

        if (hireDates.length === 0) {
            return { labels: ['Current'], totalEmployees: [missingHireCount] };
        }

        const sortedHireDates = hireDates.sort((a, b) => a - b);
        const startDate = new Date(sortedHireDates[0].getFullYear(), sortedHireDates[0].getMonth(), 1);
        const endDate = new Date(sortedHireDates[sortedHireDates.length - 1].getFullYear(), sortedHireDates[sortedHireDates.length - 1].getMonth(), 1);

        const labels = [];
        const totalCounts = [];
        const currentDate = new Date(startDate);

        while (currentDate <= endDate) {
            labels.push(currentDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short' }));

            const monthEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0, 23, 59, 59);
            let totalCount = hireDates.filter(date => date <= monthEnd).length;
            const isLastMonth = currentDate.getFullYear() === endDate.getFullYear() && currentDate.getMonth() === endDate.getMonth();
            if (isLastMonth) {
                totalCount += missingHireCount;
            }
            totalCounts.push(totalCount);

            currentDate.setMonth(currentDate.getMonth() + 1);
        }

        return {
            labels,
            totalEmployees: totalCounts
        };
    }

    setReportStatusMessage(message, timeout = 5000) {
        const status = document.getElementById('reportStatusMessage');
        if (!status) return;
        status.textContent = message;
        status.classList.add('active');
        if (timeout > 0) {
            clearTimeout(status.hideTimeout);
            status.hideTimeout = setTimeout(() => {
                status.classList.remove('active');
            }, timeout);
        }
    }

    updateReportTable(filteredEmployees) {
        const tbody = document.getElementById('reportTableBody');
        if (!tbody) return;

        // Apply sorting
        let dataToDisplay = [...filteredEmployees];
        if (this.sortColumn) {
            dataToDisplay.sort((a, b) => {
                let aVal = a[this.sortColumn];
                let bVal = b[this.sortColumn];
                if (typeof aVal === 'string') aVal = aVal.toLowerCase();
                if (typeof bVal === 'string') bVal = bVal.toLowerCase();
                const comp = aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
                return this.sortDirection === 'asc' ? comp : -comp;
            });
        }

        // Apply pagination
        const pageSize = parseInt(document.getElementById('rowsPerPage')?.value || 50);
        const totalPages = Math.ceil(dataToDisplay.length / pageSize);
        const start = (this.currentPage - 1) * pageSize;
        const end = start + pageSize;
        const pageData = dataToDisplay.slice(start, end);

        // Update pagination controls
        const paginationControls = document.getElementById('paginationControls');
        if (totalPages > 1) {
            paginationControls.style.display = 'flex';
            document.getElementById('pageInfo').textContent = `Page ${this.currentPage} of ${totalPages}`;
        } else {
            paginationControls.style.display = 'none';
        }
        document.getElementById('totalRecords').textContent = dataToDisplay.length;

        // Get visible columns
        const visibleCols = Array.from(document.querySelectorAll('.reportColumnToggle:checked')).map(cb => cb.value);
        const colMap = {id: 'employee_id', full_name: 'full_name', position: 'position', department: 'department',
                       employment_type: 'employment_type', email: 'email', contact_number: 'contact_number',
                       date_hired: 'date_hired', employment_status: 'employment_status'};

        if (dataToDisplay.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${visibleCols.length + 1}" style="text-align: center; padding: 20px;">No employees match the selected criteria</td></tr>`;
            return;
        }

        let html = '';
        pageData.forEach((emp, idx) => {
            const isSelected = this.selectedRows.has(emp.employee_id);
            const hireDate = emp.date_hired ? new Date(emp.date_hired).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : 'N/A';
            const statusClass = emp.employment_status === 'Active' ? 'success' : 'danger';
            
            html += `<tr style="background: ${isSelected ? '#f0fdf4' : ''};">
                <td><input type="checkbox" data-id="${emp.employee_id}" class="rowCheckbox" onchange="reportsManager.toggleRowSelection('${emp.employee_id}')" ${isSelected ? 'checked' : ''} style="cursor: pointer;"></td>`;
            
            if (visibleCols.includes('id')) html += `<td>${emp.employee_id || ''}</td>`;
            if (visibleCols.includes('full_name')) html += `<td><strong>${emp.full_name || 'N/A'}</strong></td>`;
            if (visibleCols.includes('position')) html += `<td>${emp.position || 'N/A'}</td>`;
            if (visibleCols.includes('department')) html += `<td>${emp.department || 'N/A'}</td>`;
            if (visibleCols.includes('employment_type')) html += `<td>${emp.employment_type || 'N/A'}</td>`;
            if (visibleCols.includes('email')) html += `<td>${emp.email || 'N/A'}</td>`;
            if (visibleCols.includes('contact_number')) html += `<td>${emp.contact_number || 'N/A'}</td>`;
            if (visibleCols.includes('date_hired')) html += `<td>${hireDate}</td>`;
            if (visibleCols.includes('employment_status')) html += `<td><span class="wfa-risk-badge ${statusClass}">${emp.employment_status || 'N/A'}</span></td>`;
            
            html += `</tr>`;
        });

        tbody.innerHTML = html;
        this.updateBulkActionsBar();
    }

    updateReportStats(filteredEmployees) {
        const totalCount = this.allEmployees.length;
        const activeCount = this.allEmployees.filter(e => e.employment_status === 'Active').length;
        const filteredCount = filteredEmployees.length;
        const deptCount = [...new Set(this.allEmployees.map(e => e.department).filter(d => d))].length;

        document.getElementById('reportTotalCount').textContent = totalCount;
        document.getElementById('reportActiveCount').textContent = activeCount;
        document.getElementById('reportFilteredCount').textContent = filteredCount;
        document.getElementById('reportDeptCount').textContent = deptCount;
    }

    exportReportExcel() {
        const filtered = this.getFilteredEmployees();
        if (filtered.length === 0) {
            alert('No data to export');
            return;
        }

        const reportDate = new Date();
        let tableHtml = `
            <table border="1" style="border-collapse: collapse; width: 100%;">
                <tr style="background: #f3f4f6;">
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Employee Type</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Hire Date</th>
                    <th>Status</th>
                </tr>`;

        filtered.forEach(emp => {
            const hireDate = emp.date_hired ? new Date(emp.date_hired).toLocaleDateString('en-US') : 'N/A';
            tableHtml += `
                <tr>
                    <td>${emp.employee_id || ''}</td>
                    <td>${emp.full_name || ''}</td>
                    <td>${emp.position || ''}</td>
                    <td>${emp.department || ''}</td>
                    <td>${emp.employment_type || ''}</td>
                    <td>${emp.email || ''}</td>
                    <td>${emp.contact_number || ''}</td>
                    <td>${hireDate}</td>
                    <td>${emp.employment_status || ''}</td>
                </tr>`;
        });

        tableHtml += '</table>';

        const excelFile = `\uFEFF${tableHtml}`;
        const blob = new Blob([excelFile], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.href = url;
        link.download = `HR_Report_${reportDate.toISOString().split('T')[0]}.xls`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    exportReportPDF() {
        const filtered = this.getFilteredEmployees();
        if (filtered.length === 0) {
            alert('Please generate a report first.');
            return;
        }

        const reportDate = new Date();
        let html = `
            <html>
            <head>
                <title>HR Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 24px; color: #111; }
                    h1 { font-size: 22px; margin-bottom: 12px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
                    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 12px; }
                    th { background: #f7fafc; }
                </style>
            </head>
            <body>
                <h1>Custom HR Report</h1>
                <div>Generated: ${reportDate.toLocaleString('en-US')}</div>
                <div>Records: ${filtered.length}</div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Type</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Hire Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>`;

        filtered.forEach(emp => {
            const hireDate = emp.date_hired ? new Date(emp.date_hired).toLocaleDateString('en-US') : 'N/A';
            html += `
                        <tr>
                            <td>${emp.employee_id || ''}</td>
                            <td>${emp.full_name || ''}</td>
                            <td>${emp.position || ''}</td>
                            <td>${emp.department || ''}</td>
                            <td>${emp.employment_type || ''}</td>
                            <td>${emp.email || ''}</td>
                            <td>${emp.contact_number || ''}</td>
                            <td>${hireDate}</td>
                            <td>${emp.employment_status || ''}</td>
                        </tr>`;
        });

        html += `
                    </tbody>
                </table>
            </body>
            </html>`;

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            alert('Unable to open print window. Please allow popups.');
            return;
        }
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }

    clearReportFilters() {
        document.getElementById('reportDeptFilter').value = '';
        document.getElementById('reportTypeFilter').value = '';
        document.getElementById('reportStatusFilter').value = '';
        document.getElementById('reportHireFrom').value = '';
        document.getElementById('reportHireTo').value = '';
        document.getElementById('reportSearchFilter').value = '';
        document.getElementById('reportDateRange').value = '';
        document.getElementById('reportMinSalary').value = '';
        document.getElementById('reportMaxSalary').value = '';
        document.getElementById('reportMinPerformance').value = '';
        document.getElementById('reportMinTenure').value = '';
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.currentPage = 1;
        this.deselectAll();
        this.generateReport();
        this.setReportStatusMessage('Filters cleared. Displaying all employees.');
    }

    // Initialize four charts with sample data
    initializeAdditionalCharts() {
        if (typeof Chart === 'undefined') return;
        
        this.renderAttritionTrendChart();
        this.renderGenderChart();
        this.renderAgeChart();
        this.renderPayrollChart();
    }

    renderAttritionTrendChart() {
        const ctx = document.getElementById('attritionTrendChart');
        if (!ctx) return;

        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Attrition Rate',
                        data: [4, 5, 4.2, 3.8, 3.5, 4.1],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Target Line',
                        data: [4.5, 4.5, 4.5, 4.5, 4.5, 4.5],
                        borderColor: '#f59e0b',
                        borderWidth: 2,
                        fill: false,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        tension: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { color: '#374151', font: { size: 12 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 0,
                        max: 6,
                        ticks: { 
                            color: '#64748b',
                            callback: function(value) { return value + '%'; }
                        },
                        grid: { color: 'rgba(148, 163, 184, 0.15)' }
                    },
                    x: {
                        ticks: { color: '#64748b' },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    renderGenderChart() {
        const ctx = document.getElementById('genderChart');
        if (!ctx) return;

        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Female', 'Male'],
                datasets: [{
                    data: [60, 40],
                    backgroundColor: ['#3b82f6', '#1e40af'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '50%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { color: '#374151', font: { size: 11 }, padding: 12 }
                    }
                }
            }
        });
    }

    renderAgeChart() {
        const ctx = document.getElementById('ageChart');
        if (!ctx) return;

        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['<25 (5%)', '25-30 (50%)', '30-50 (30%)', '<50 (<5%)'],
                datasets: [{
                    data: [5, 50, 30, 15],
                    backgroundColor: ['#fbbf24', '#3b82f6', '#10b981', '#f87171'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '50%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { color: '#374151', font: { size: 10 }, padding: 8 }
                    }
                }
            }
        });
    }

    renderPayrollChart() {
        const ctx = document.getElementById('payrollChart');
        if (!ctx) return;

        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Salaries (65%)', 'Benefits (20%)', 'Overtime (15%)'],
                datasets: [{
                    data: [65, 20, 15],
                    backgroundColor: ['#2563eb', '#10b981', '#f59e0b'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { color: '#374151', font: { size: 11 }, padding: 12 }
                    }
                }
            }
        });
    }

    // NEW FEATURES: Filter Tabs, Sorting, Pagination, Bulk Actions, Templates
    switchFilterTab(tabName) {
        document.querySelectorAll('.wfa-filter-tab').forEach(tab => {
            tab.style.color = '#64748b';
            tab.style.borderBottom = '3px solid transparent';
        });
        event.target.style.color = '#3b82f6';
        event.target.style.borderBottom = '3px solid #3b82f6';
        
        document.querySelectorAll('.wfa-filter-content').forEach(content => content.style.display = 'none');
        const tabId = tabName === 'basic' ? 'basicFiltersTab' : tabName === 'advanced' ? 'advancedFiltersTab' :
                      tabName === 'templates' ? 'templatesTab' : 'columnsTab';
        document.getElementById(tabId).style.display = 'block';
        
        if (tabName === 'templates') this.renderTemplatesList();
    }

    applyDateRange() {
        const range = document.getElementById('reportDateRange')?.value;
        const today = new Date();
        let fromDate = null;
        
        if (range === '30days') {
            fromDate = new Date(today.setDate(today.getDate() - 30));
        } else if (range === '90days') {
            fromDate = new Date(today.setDate(today.getDate() - 90));
        } else if (range === '6months') {
            fromDate = new Date(today.setMonth(today.getMonth() - 6));
        } else if (range === '1year') {
            fromDate = new Date(today.setFullYear(today.getFullYear() - 1));
        } else if (range === 'ytd') {
            fromDate = new Date(today.getFullYear(), 0, 1);
        }
        
        if (fromDate) {
            document.getElementById('reportHireFrom').value = fromDate.toISOString().split('T')[0];
            document.getElementById('reportHireTo').value = new Date().toISOString().split('T')[0];
            this.generateReport();
        }
    }

    updateVisibleColumns() {
        this.generateReport();
    }

    sortReport(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }
        this.currentPage = 1;
        document.getElementById('currentSort').textContent = `Sort: ${column} (${this.sortDirection === 'asc' ? '↑' : '↓'})`;
        this.updateReportTable(this.currentFilteredData);
    }

    toggleSelectAll() {
        const isChecked = document.getElementById('selectAllCheckbox').checked;
        document.querySelectorAll('.rowCheckbox').forEach(cb => {
            cb.checked = isChecked;
            const empId = cb.getAttribute('data-id');
            if (isChecked) {
                this.selectedRows.add(empId);
            } else {
                this.selectedRows.delete(empId);
            }
        });
        this.updateBulkActionsBar();
    }

    toggleRowSelection(empId) {
        if (this.selectedRows.has(empId)) {
            this.selectedRows.delete(empId);
        } else {
            this.selectedRows.add(empId);
        }
        this.updateBulkActionsBar();
    }

    deselectAll() {
        this.selectedRows.clear();
        document.getElementById('selectAllCheckbox').checked = false;
        document.querySelectorAll('.rowCheckbox').forEach(cb => cb.checked = false);
        this.updateBulkActionsBar();
    }

    updateBulkActionsBar() {
        const bulkBar = document.getElementById('bulkActionsBar');
        if (this.selectedRows.size > 0) {
            bulkBar.style.display = 'block';
            document.getElementById('selectedCount').textContent = this.selectedRows.size;
        } else {
            bulkBar.style.display = 'none';
        }
    }

    nextPage() {
        const pageSize = parseInt(document.getElementById('rowsPerPage')?.value || 50);
        const totalPages = Math.ceil(this.currentFilteredData.length / pageSize);
        if (this.currentPage < totalPages) {
            this.currentPage++;
            this.updateReportTable(this.currentFilteredData);
        }
    }

    previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.updateReportTable(this.currentFilteredData);
        }
    }

    updatePagination() {
        this.currentPage = 1;
        this.updateReportTable(this.currentFilteredData);
    }

    saveTemplate() {
        const templateName = document.getElementById('templateName')?.value.trim();
        if (!templateName) {
            alert('Please enter a template name');
            return;
        }

        const template = {
            id: Date.now(),
            name: templateName,
            filters: {
                dept: document.getElementById('reportDeptFilter').value,
                type: document.getElementById('reportTypeFilter').value,
                status: document.getElementById('reportStatusFilter').value,
                hireFrom: document.getElementById('reportHireFrom').value,
                hireTo: document.getElementById('reportHireTo').value,
                search: document.getElementById('reportSearchFilter').value,
                minSalary: document.getElementById('reportMinSalary').value,
                maxSalary: document.getElementById('reportMaxSalary').value,
                minPerformance: document.getElementById('reportMinPerformance').value,
                minTenure: document.getElementById('reportMinTenure').value
            }
        };

        if (!this.savedTemplates) this.savedTemplates = [];
        this.savedTemplates.push(template);
        localStorage.setItem('reportTemplates', JSON.stringify(this.savedTemplates));
        document.getElementById('templateName').value = '';
        this.renderTemplatesList();
        this.setReportStatusMessage(`Template "${templateName}" saved successfully!`);
    }

    loadTemplate(templateId) {
        const template = this.savedTemplates.find(t => t.id === templateId);
        if (!template) return;

        document.getElementById('reportDeptFilter').value = template.filters.dept || '';
        document.getElementById('reportTypeFilter').value = template.filters.type || '';
        document.getElementById('reportStatusFilter').value = template.filters.status || '';
        document.getElementById('reportHireFrom').value = template.filters.hireFrom || '';
        document.getElementById('reportHireTo').value = template.filters.hireTo || '';
        document.getElementById('reportSearchFilter').value = template.filters.search || '';
        document.getElementById('reportMinSalary').value = template.filters.minSalary || '';
        document.getElementById('reportMaxSalary').value = template.filters.maxSalary || '';
        document.getElementById('reportMinPerformance').value = template.filters.minPerformance || '';
        document.getElementById('reportMinTenure').value = template.filters.minTenure || '';
        
        this.generateReport();
        this.setReportStatusMessage(`Template "${template.name}" loaded!`);
    }

    deleteTemplate(templateId) {
        this.savedTemplates = this.savedTemplates.filter(t => t.id !== templateId);
        localStorage.setItem('reportTemplates', JSON.stringify(this.savedTemplates));
        this.renderTemplatesList();
    }

    clearTemplates() {
        if (confirm('Delete all saved templates?')) {
            this.savedTemplates = [];
            localStorage.removeItem('reportTemplates');
            this.renderTemplatesList();
            this.setReportStatusMessage('All templates deleted.');
        }
    }

    renderTemplatesList() {
        const container = document.getElementById('templatesList');
        if (!this.savedTemplates || this.savedTemplates.length === 0) {
            container.innerHTML = '<div style="color: #94a3b8; text-align: center; padding: 20px;">No saved templates yet</div>';
            return;
        }

        let html = '';
        this.savedTemplates.forEach(template => {
            html += `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div>
                        <div style="font-weight: 600; color: #1f2937;">${template.name}</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Saved ${new Date(template.id).toLocaleDateString()}</div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button class="wfa-btn wfa-btn-primary" onclick="reportsManager.loadTemplate(${template.id})" style="font-size: 12px; padding: 6px 10px;">Load</button>
                        <button class="wfa-btn wfa-btn-secondary" onclick="reportsManager.deleteTemplate(${template.id})" style="font-size: 12px; padding: 6px 10px;">Delete</button>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    refreshData() {
        this.setReportStatusMessage('Refreshing data...', 2000);
        this.generateReport();
    }

    updateDataRefreshTime() {
        const now = new Date().toLocaleString();
        document.getElementById('dataRefreshTime').textContent = `Data last updated: ${now} |`;
    }

    printReport() {
        const filteredData = this.currentFilteredData || this.getFilteredEmployees();
        const visibleCols = Array.from(document.querySelectorAll('.reportColumnToggle:checked')).map(cb => cb.value);
        
        let printHtml = `
            <html><head><title>Employee Report</title>
            <style>
                body { font-family: Arial, sans-serif; }
                h2 { color: #1f2937; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th { background: #3b82f6; color: white; padding: 8px; text-align: left; font-weight: 600; }
                td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
                tr:nth-child(even) { background: #f9fafb; }
                .summary { margin-bottom: 20px; padding: 12px; background: #f0f9ff; border-left: 4px solid #3b82f6; }
                @media print { body { margin: 0; } }
            </style></head><body>
            <h2>Employee Report - ${new Date().toLocaleDateString()}</h2>
            <div class="summary">
                <strong>Total Records:</strong> ${filteredData.length}<br>
                <strong>Report Generated:</strong> ${new Date().toLocaleString()}
            </div>
            <table><thead><tr>`;
        
        const colMap = {id: 'ID', full_name: 'Full Name', position: 'Position', department: 'Department',
                       employment_type: 'Type', email: 'Email', contact_number: 'Contact',
                       date_hired: 'Hire Date', employment_status: 'Status'};
        
        visibleCols.forEach(col => {
            printHtml += `<th>${colMap[col] || col}</th>`;
        });
        printHtml += `</tr></thead><tbody>`;
        
        const colDbMap = {id: 'employee_id', full_name: 'full_name', position: 'position', department: 'department',
                         employment_type: 'employment_type', email: 'email', contact_number: 'contact_number',
                         date_hired: 'date_hired', employment_status: 'employment_status'};
        
        filteredData.forEach(emp => {
            printHtml += '<tr>';
            visibleCols.forEach(col => {
                const dbCol = colDbMap[col];
                let val = emp[dbCol] || 'N/A';
                if (col === 'date_hired' && val !== 'N/A') {
                    val = new Date(val).toLocaleDateString();
                }
                printHtml += `<td>${val}</td>`;
            });
            printHtml += '</tr>';
        });
        
        printHtml += `</tbody></table></body></html>`;
        
        const printWindow = window.open('', 'Print Report', 'width=1200,height=600');
        printWindow.document.write(printHtml);
        printWindow.document.close();
        setTimeout(() => printWindow.print(), 250);
    }
}

// Initialize the reports manager
const reportsManager = new EmployeeReportsManager('reportsContainer');

// Legacy function for backward compatibility
function loadReportsTab() {
    reportsManager.loadReportsTab();
}
</script>
