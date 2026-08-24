# Quick Start Guide - Custom Reports Module

## 🚀 Getting Started

Access the Reports module at: `http://localhost/capstone_hr_management_system/workforce/public/reports.php`

---

## 📊 Feature Walkthroughs

### 1. **Generate a Basic Report**
```
1. Open Reports page
2. Select filters:
   - Department: Choose specific or leave blank for all
   - Employee Type: Full-time, Part-time, etc.
   - Status: Active, Resigned, Terminated, etc.
3. (Optional) Quick Date Range: Last 30 Days, Last 90 Days, etc.
4. (Optional) Custom date range using Hire Date From/To
5. (Optional) Search by name, email, or position
6. Click "Generate Report"
7. Table displays filtered results (default 50 rows per page)
```

### 2. **Sort Results**
```
- Click any column header (ID, Name, Position, Department, etc.)
- First click = Ascending (↑)
- Second click = Descending (↓)
- Sort indicator shows current column and direction
- Sort persists when navigating pages
```

### 3. **Navigate Results (Pagination)**
```
- Select "Rows per page": 25, 50, 100, 200, or 500
- Use "Previous" and "Next" buttons to navigate
- Current page shown: "Page X of Y"
- Total record count displayed at bottom
```

### 4. **Show/Hide Columns**
```
1. Click "Columns" tab in filter section
2. Uncheck columns to hide them (check to show)
3. Changes apply immediately
4. Hidden columns don't appear in table or exports
5. Columns: ID, Full Name, Position, Department, Employee Type, Email, Contact, Hire Date, Status
```

### 5. **Use Advanced Filters**
```
1. Click "Advanced Filters" tab
2. Set one or more:
   - Min/Max Salary: Filter by salary range
   - Performance Rating: Minimum 1-5 rating
   - Min Tenure (Years): Filter by years employed
3. Click "Generate Report"
4. Advanced filters combine with Basic filters (both apply)
```

### 6. **Use Pre-built Date Ranges**
```
1. Go to Basic Filters tab
2. Use "Quick Date Range" dropdown:
   - Last 30 Days → Shows employees hired in last 30 days
   - Last 90 Days → Shows employees hired in last 90 days
   - Last 6 Months → etc.
   - Last 1 Year
   - Year to Date
3. Automatically fills Hire Date From/To fields
4. Click "Generate Report"
```

### 7. **Select Multiple Rows (Bulk Actions)**
```
1. Check checkbox next to employee name (or multiple rows)
2. Green bar appears showing "X employee(s) selected"
3. Selected rows highlighted in green
4. Use "Deselect All" button in bulk bar to clear
5. All selected rows stay selected when navigating pages
```

### 8. **Save & Load Filter Templates**
```
SAVE A TEMPLATE:
1. Configure desired filters (any combination)
2. Click "Saved Templates" tab
3. Enter template name (e.g., "Active Full-time Employees")
4. Click "Save Current Filters"
5. Template saved to browser (persists forever)

LOAD A TEMPLATE:
1. Go to "Saved Templates" tab
2. Find saved template in list
3. Click "Load" button
4. All filters auto-fill with saved values
5. Click "Generate Report" to apply

DELETE A TEMPLATE:
1. Go to "Saved Templates" tab
2. Find template to delete
3. Click "Delete" button
4. Template removed (confirm if prompted)

CLEAR ALL TEMPLATES:
1. Go to "Saved Templates" tab
2. Click "Clear Templates" button
3. All saved templates deleted
```

### 9. **Export Report**

#### Excel Export:
```
1. Generate report with desired filters
2. Click "Export to Excel"
3. File downloads: HR_Report_YYYY-MM-DD.xls
4. Only visible columns are included
5. Open in Excel, Google Sheets, or LibreOffice
```

#### PDF Export:
```
1. Generate report
2. Click "Export to PDF"
3. Browser print dialog opens
4. Select "Save as PDF" from printer dropdown
5. Click "Save"
6. PDF includes filtered results with selected columns
```

#### Print:
```
1. Generate report
2. Click "Print" button
3. Print preview opens in new window
4. Shows formatted table with header and summary
5. Click "Print" in preview window or Close to cancel
6. Select printer and print settings
```

### 10. **Refresh Data**
```
- Click "Refresh" button next to Report Preview title
- Re-fetches employee data from server
- Updates "Data last updated" timestamp
- Regenerates report with current data
```

### 11. **Clear All Filters**
```
- Click "Clear Filters" button
- Resets all basic and advanced filters
- Clears sort, page, and selection
- Shows all employees (default sort)
- Click "Generate Report" to see changes
```

---

## 🎯 Common Workflows

### Find Active Employees in IT Department
```
1. Department: IT
2. Status: Active
3. Click "Generate Report"
```

### Compare High-Performing Employees
```
1. Go to Advanced Filters tab
2. Performance Rating (Min): 4
3. Click "Generate Report"
```

### Find Recently Hired Employees
```
1. Quick Date Range: Last 90 Days
2. Click "Generate Report"
```

### Export Specific Department
```
1. Department: Sales
2. Status: Active
3. Columns: Show only Name, Position, Email
4. Click "Generate Report"
5. Click "Export to Excel"
```

### Create Recurring Report
```
1. Configure filters you use regularly
2. Save as Template with descriptive name
3. Next time: Load template → Generate Report → Export
```

---

## ℹ️ Tips & Tricks

### Performance
- Reports with 1000+ employees may take 2-3 seconds to generate
- Use filters to narrow results for faster performance
- Pagination keeps table responsive even with large datasets

### Sorting
- Click same column twice to reverse sort order
- Useful for finding highest/lowest values
- Sort by Department to group similar roles

### Bulk Selection
- Use "Select All" checkbox in table header for quick selection
- Selected rows stay selected when navigating pages
- Deselect All button clears instantly

### Templates
- Save different templates for recurring reports
- Examples: "Monthly Active Report", "Turnover Analysis", "Salary Review"
- Templates stored locally - no account needed

### Columns
- Hide columns you don't need for cleaner viewing
- Preferences reset when closing browser (stored per-session)
- All exports respect column visibility

### Refresh
- Refresh button updates data from server
- Use before exporting to ensure current data
- Timestamp shows when data was last loaded

---

## 🔧 Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| Tab | Navigate between filters |
| Enter | Generate Report (from text field) |
| Ctrl+P | Print (from browser) |

---

## ❓ Troubleshooting

### Report shows no results
- Check if any filters are too restrictive
- Try clearing filters and generating again
- Verify data exists for selected criteria

### Columns not showing
- Go to Columns tab and verify columns are checked
- Refresh page if columns still missing
- Try hiding/showing a different column

### Templates not saving
- Verify browser localStorage is enabled
- Try saving with different template name
- Check browser console for errors (F12)

### Export file won't open
- Excel export requires Excel or compatible spreadsheet application
- Try opening with Google Sheets or LibreOffice
- Verify file downloaded completely

### Print preview looks wrong
- Check that Print Preview opened in new window
- Adjust browser zoom if text appears cut off
- Close preview and adjust column visibility if needed

---

## 📝 Notes

- All filters are **optional** - leave blank to include all
- Multiple filters use **AND logic** (all must match)
- Filters can be combined: Basic + Advanced + Search all work together
- Report data is cached until filters change or refresh is clicked
- Templates save with browser - work across sessions but not across devices

---

## 🎓 Learning Resources

For detailed technical information, see: `CUSTOM_REPORTS_FEATURES.md`

Questions or issues? Refer to the system administrator or check application logs.

---

**Last Updated**: Session Complete  
**Version**: 1.0 - Full Feature Set  
**All 15+ Features**: Available and Ready to Use
