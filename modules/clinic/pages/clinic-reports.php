<div class="clinic-reports-module module-content">
    <div class="module-header"><div><span class="report-kicker">HR CLINIC MANAGEMENT SYSTEM</span><h1>Generate Clinic Report</h1><p>Employee clinic visits by day, week, or month.</p></div></div>
    <div id="reportsMessage" class="module-message" role="alert" hidden></div>
    <section class="report-builder section-card">
        <div class="report-builder-heading"><div><span class="section-eyebrow">Report builder</span><h2>Clinic Visit Reports</h2></div><i class="fa-solid fa-chart-line"></i></div>
        <div class="report-form-grid">
            <div class="report-field"><label for="periodReportType">Report Type</label><select id="periodReportType"><option value="daily">Daily Report</option><option value="weekly">Weekly Report</option><option value="monthly">Monthly Report</option></select></div>
            <div class="report-field period-field daily-field"><label for="dailyDate">Select Date</label><input type="date" id="dailyDate"></div>
            <div class="report-field period-field weekly-field" hidden><label for="weekStart">Week Start Date</label><input type="date" id="weekStart"></div>
            <div class="report-field period-field weekly-field" hidden><label for="weekEnd">Week End Date</label><input type="date" id="weekEnd"></div>
            <div class="report-field period-field monthly-field" hidden><label for="reportMonth">Select Month</label><select id="reportMonth"></select></div>
            <div class="report-field period-field monthly-field" hidden><label for="reportYear">Select Year</label><select id="reportYear"></select></div>
            <div class="report-field"><label for="departmentFilter">Department</label><select id="departmentFilter"><option value="">All Departments</option></select></div>
            <div class="report-field"><label for="employeeFilter">Employee</label><input id="employeeFilter" list="employeeOptions" placeholder="All Employees"><datalist id="employeeOptions"></datalist></div>
        </div>
        <div class="report-builder-actions"><button type="button" id="generateReportBtn" class="primary-btn"><i class="fa-solid fa-file-circle-plus"></i> Generate Report</button><button type="button" id="resetReportBtn" class="ghost-btn"><i class="fa-solid fa-rotate-left"></i> Reset</button></div>
    </section>
    <section id="generatedReport" class="generated-report" hidden aria-live="polite"></section>
</div>
