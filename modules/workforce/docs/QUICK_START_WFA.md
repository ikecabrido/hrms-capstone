# WFA Action & Intervention System - Quick Start Guide

## ⚡ 5-Minute Setup

### 1. Create Tables (Copy & Paste)

```sql
-- Execute this in your MySQL:
source /xampp/htdocs/capstone_hr_management_system/workforce/sql/wfa_action_system.sql;

-- Verify:
SHOW TABLES LIKE 'wfa_%';
```

### 2. Verify Files Exist

```
✓ /workforce/models/ActionSystem.php
✓ /workforce/api/wfa/create_action.php
✓ /workforce/api/wfa/create_pip.php
✓ /workforce/api/wfa/analyze_employee.php
✓ /workforce/api/wfa/get_low_performers.php
✓ /workforce/views/wfa_action_dashboard.html
✓ /workforce/sql/wfa_action_system.sql
✓ /workforce/WFA_INTEGRATION_GUIDE.md
```

### 3. Access Dashboard

```
Open: http://localhost/capstone_hr_management_system/workforce/views/wfa_action_dashboard.html
```

---

## 🎯 System Overview

### What It Does
- **Detects** performance issues (low rating, absences, tardiness)
- **Analyzes** employee data WITHOUT modifying performance module
- **Recommends** appropriate interventions (Training, Warning, PIP, Mentoring)
- **Tracks** actions and PIPs in isolated WFA tables
- **Displays** dashboard for managers to take intervention

### Core Logic Flow

```
Employee Data (Read-Only)
    ↓
detectPerformanceIssues() - Analyzes data
    ↓
recommendAction() - Suggests intervention
    ↓
Dashboard - Displays results
    ↓
Manager Action - Creates PIP or Intervention
    ↓
Stored in WFA Tables (Isolated)
```

---

## 📊 Key Features

### 1. Automatic Issue Detection

```
✓ High Absenteeism       (>4 absences in 6 months)
✓ Frequent Tardiness    (>10 late arrivals)
✓ Low Performance       (rating < 2.5/5)
✓ Critical Performance  (rating < 2.0/5)
✓ Training Gaps         (from training recommendations)
```

### 2. Smart Recommendations

```
Critical Issues  →  Create PIP (95% confidence)
High + High      →  Create PIP (90% confidence)
High Performance →  Assign Training (85% confidence)
High Absenteeism →  Issue Warning (75% confidence)
Training Gap     →  Assign Training (70% confidence)
```

### 3. Dashboard Functions

| Button | Action |
|--------|--------|
| [Action] | Create intervention (Training, Warning, Mentoring, etc.) |
| [PIP] | Create formal Performance Improvement Plan |
| Filters | View Critical/High priority employees |
| Refresh | Reload data from database |

---

## 💻 API Reference

### Get Low Performers
```
GET /workforce/api/wfa/get_low_performers.php
Response: Array of employees with detected issues
```

### Analyze Employee
```
GET /workforce/api/wfa/analyze_employee.php?employee_id=5
Response: Detailed analysis + recommendation
```

### Create Action
```
POST /workforce/api/wfa/create_action.php
Body: { employee_id, action_type, description, due_date }
Response: { success, action_id }
```

### Create PIP
```
POST /workforce/api/wfa/create_pip.php
Body: { employee_id, reason, action_plan, start_date, end_date }
Response: { success, pip_id }
```

---

## 🔧 Customization

### Change Thresholds

Edit `/workforce/models/ActionSystem.php` lines 17-20:

```php
// Current values:
private const ABSENCE_THRESHOLD = 4;           // Change to 3 for stricter
private const TARDINESS_THRESHOLD = 10;        // Change to 8 for stricter
private const LOW_RATING_THRESHOLD = 2.5;      // Change to 3.0 for stricter
private const CRITICAL_RATING_THRESHOLD = 2.0; // Change to 2.5 for stricter
```

### Modify Dashboard Colors

Edit `/workforce/views/wfa_action_dashboard.html`:

```css
/* Find these sections and modify colors */
.severity-badge.critical { background: #e74c3c; }  /* Red */
.severity-badge.high { background: #e67e22; }      /* Orange */
.severity-badge.medium { background: #f39c12; }    /* Yellow */
.severity-badge.low { background: #27ae60; }       /* Green */
```

### Add Custom Actions

Edit recommendation logic in `ActionSystem.php` method `recommendAction()` (around line 210).

---

## 📋 Database Schema

### wfa_performance_improvement_plans
```
pip_id (PK)
employee_id (FK)
start_date
end_date
reason
action_plan
status (ONGOING|COMPLETED|FAILED)
performance_target
created_at
created_by
```

### wfa_actions
```
action_id (PK)
employee_id
action_type (Training|Warning|PIP|Mentoring|Counseling|Suspension)
description
status (Pending|In Progress|Completed|Cancelled)
assigned_to
due_date
completion_date
created_at
```

### wfa_action_recommendations
```
recommendation_id (PK)
employee_id
detected_issues (JSON)
recommended_action
confidence_score
acknowledged
created_at
```

### wfa_performance_issues
```
issue_id (PK)
employee_id
issue_type (Absenteeism|Tardiness|Low Performance|Skill Gap|Behavior|Other)
severity (Low|Medium|High|Critical)
description
detected_date
resolved
resolution_date
resolution_notes
```

---

## ✅ Important Notes

### What IS Tracked
✓ Actions and interventions (WFA tables only)
✓ Performance improvement plans (WFA tables only)
✓ Issue detections (WFA tables only)
✓ Recommendations (WFA tables only)

### What IS NOT Modified
✗ performance_reviews table
✗ pm_appraisals table
✗ pm_* tables (Performance Module)
✗ employees table
✗ attendance table (read-only)

**The system is 100% safe and non-invasive!**

---

## 🐛 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| API returns 404 | Check file path, verify /api/wfa/ directory exists |
| No employees shown | Check if performance_reviews has data, verify thresholds |
| Database error | Verify hr-management database exists, check credentials |
| Modal won't open | Clear browser cache, check console for JavaScript errors |
| No recommendations | Check employee data meets threshold criteria |

---

## 📖 Full Documentation

See **WFA_INTEGRATION_GUIDE.md** for:
- Detailed architecture
- Complete API documentation
- Code examples
- Testing checklist
- Phase 2 enhancements

---

## 🎓 For Capstone Project

This system demonstrates:

✅ **Database Design**: 4 new WFA tables with proper relationships
✅ **Root Cause Analysis**: Connects attendance + performance data
✅ **Recommendation Engine**: Smart logic for intervention suggestions
✅ **API Architecture**: RESTful endpoints for CRUD operations
✅ **Frontend Integration**: Interactive dashboard with real-time updates
✅ **Modular Design**: Standalone system that doesn't break existing modules
✅ **Scalability**: Handles multiple employees and concurrent operations
✅ **Security**: Input validation, error handling, audit trail
✅ **User Experience**: Professional UI with visual hierarchy
✅ **Documentation**: Well-commented code and comprehensive guides

**Ready to impress your thesis panel!** 🚀
