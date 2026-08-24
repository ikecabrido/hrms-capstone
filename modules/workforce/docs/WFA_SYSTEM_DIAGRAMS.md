# WFA System - Architecture & Data Flow Diagrams

## 1. System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    BROWSER / FRONTEND                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  wfa_action_dashboard.html                                       │
│  ├── Load low performers (fetch API)                            │
│  ├── Display statistics & data table                            │
│  ├── Filter by severity                                         │
│  ├── Show issue detection & recommendations                     │
│  └── Handle Create Action / PIP modals                          │
│                                                                  │
└────────────────────────┬────────────────────────────────────────┘
                         │ HTTP Requests/JSON
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                      API LAYER (PHP)                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  GET /api/wfa/get_low_performers.php                           │
│  ├── Call ActionSystem::getLowPerformanceEmployees()           │
│  └── Return JSON array of flagged employees                    │
│                                                                  │
│  GET /api/wfa/analyze_employee.php                             │
│  ├── Call ActionSystem::detectPerformanceIssues()              │
│  ├── Call ActionSystem::recommendAction()                      │
│  └── Return JSON with analysis & recommendation                │
│                                                                  │
│  POST /api/wfa/create_action.php                               │
│  ├── Validate input                                            │
│  ├── Call ActionSystem::createAction()                         │
│  └── Return action_id & success status                         │
│                                                                  │
│  POST /api/wfa/create_pip.php                                  │
│  ├── Validate dates and input                                  │
│  ├── Call ActionSystem::createPerformanceImprovementPlan()    │
│  └── Return pip_id & success status                            │
│                                                                  │
└────────────────────────┬────────────────────────────────────────┘
                         │ Business Logic
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│               BUSINESS LOGIC LAYER (PHP)                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ActionSystem Class                                             │
│  ├── detectPerformanceIssues($employee_id)                     │
│  │   ├── Get latest performance rating                         │
│  │   ├── Get attendance statistics (6 months)                  │
│  │   ├── Check training gaps                                   │
│  │   ├── Apply detection rules                                 │
│  │   └── Calculate severity level                              │
│  │                                                              │
│  ├── recommendAction($analysis)                                │
│  │   ├── Evaluate severity & issue types                       │
│  │   ├── Apply recommendation rules                            │
│  │   ├── Calculate confidence score                            │
│  │   └── Generate rationale                                    │
│  │                                                              │
│  ├── createPerformanceImprovementPlan($data)                  │
│  │   └── Insert into wfa_performance_improvement_plans        │
│  │                                                              │
│  ├── createAction($data)                                       │
│  │   └── Insert into wfa_actions                               │
│  │                                                              │
│  └── getLowPerformanceEmployees()                              │
│      └── Query & aggregate employee data with issue analysis   │
│                                                                  │
└────────────────────────┬────────────────────────────────────────┘
                         │ SQL Queries
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                    DATABASE LAYER                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  READ-ONLY (No Modifications)                                   │
│  ├── employees                                                  │
│  ├── performance_reviews ← Latest rating                       │
│  ├── attendance ← Last 6 months stats                           │
│  ├── pm_training_recommendations ← Training gaps               │
│  └── pm_appraisals [NOT USED]                                  │
│                                                                  │
│  WRITE-ONLY (WFA Isolated Tables)                               │
│  ├── wfa_performance_improvement_plans ← PIP records           │
│  ├── wfa_actions ← Intervention records                        │
│  ├── wfa_action_recommendations ← Audit trail                  │
│  └── wfa_performance_issues ← Issue tracking                   │
│                                                                  │
│  [NO pm_ TABLE MODIFICATIONS]                                   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Data Flow - Get Low Performers

```
┌─────────────────────────────────────────────────────────────┐
│ User clicks "Refresh Data" in Dashboard                     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ↓
        ┌────────────────────────────────┐
        │ fetch('/api/wfa/get_low_        │
        │ performers.php?limit=20')       │
        └────────────────┬────────────────┘
                         │
                         ↓
      ┌──────────────────────────────────────┐
      │ get_low_performers.php               │
      │ (line ~40)                           │
      │ ├── ActionSystem::                   │
      │ │   getLowPerformanceEmployees()    │
      │ └── Return JSON array               │
      └────────────────┬─────────────────────┘
                       │
        ┌──────────────┴──────────────┐
        ↓                             ↓
  ┌─────────────┐            ┌─────────────────┐
  │ For each    │            │ Query database: │
  │ employee:   │            │ SELECT e.*, pr. │
  │             │            │ overall_score   │
  │ 1. Call     │            │ FROM employees  │
  │    detect   │            │ LEFT JOIN perf. │
  │    Issues   │            │ LEFT JOIN att.  │
  │             │            └────────┬────────┘
  │ 2. Call     │                    │
  │    recommend│ ◄──────────────────┘
  │    Action   │
  │             │
  │ 3. Return   │
  │    {issues, │
  │     severity│
  │     recomm.}│
  └─────┬───────┘
        │
        ↓
   ┌─────────────────────────┐
   │ Aggregate all employees │
   │ Create response JSON    │
   └──────────┬──────────────┘
              │
              ↓
      ┌──────────────────┐
      │ Return to Browser│
      │ Status 200 OK    │
      │ {success: true,  │
      │  data: [...],    │
      │  total: 12}      │
      └────────┬─────────┘
               │
               ↓
      ┌───────────────────────────┐
      │ Update Dashboard:         │
      │ ├── Update stat cards     │
      │ ├── Populate data table   │
      │ ├── Apply color coding    │
      │ └── Show recommendations  │
      └───────────────────────────┘
```

---

## 3. Data Flow - Detect Performance Issues

```
┌────────────────────────────────────────────────┐
│ ActionSystem::detectPerformanceIssues($emp_id) │
│ (Called from API or batch process)             │
└───────────────────┬────────────────────────────┘
                    │
        ┌───────────┼───────────┬────────────┐
        │           │           │            │
        ↓           ↓           ↓            ↓
   ┌────────┐  ┌─────────┐  ┌──────────┐  ┌─────────┐
   │ Get    │  │ Get     │  │ Check    │  │ Get all │
   │ Latest │  │ Attend. │  │ Training │  │ metrics │
   │ Rating │  │ Stats   │  │ Gaps     │  │         │
   │ from   │  │ (6mo)   │  │          │  │         │
   │ perf._ │  │ from    │  │          │  │         │
   │reviews │  │ attend. │  │          │  │         │
   └───┬────┘  └────┬────┘  └────┬─────┘  └────┬────┘
       │            │            │             │
       │ rating=2.1 │ absences=5  │ training=   │ metrics:
       │            │ tardiness=3 │ ['PHP','CS']│ { rating,
       │            │ attendance= │             │   absences,
       │            │ 89.5%       │             │   tardiness,
       │            │             │             │   trainings }
       └────────────┼─────────────┼─────────────┘
                    │
                    ↓
        ┌───────────────────────────────────┐
        │ Apply Detection Rules:            │
        │                                   │
        │ IF absences (5) > threshold (4)   │
        │   → Issue: "High Absenteeism"     │
        │   → Severity: "High"              │
        │                                   │
        │ IF tardiness (3) > threshold (10) │
        │   → SKIP (not triggered)          │
        │                                   │
        │ IF rating (2.1) < critical (2.0)  │
        │   → SKIP (2.1 > 2.0)              │
        │                                   │
        │ IF rating (2.1) < low threshold   │
        │    (2.5)                          │
        │   → Issue: "Low Performance"      │
        │   → Severity: "High"              │
        │                                   │
        │ IF training gaps exist            │
        │   → Issue: "Skill Development"    │
        │   → Severity: "Medium"            │
        │                                   │
        └───────────────┬───────────────────┘
                        │
                        ↓
        ┌────────────────────────────────────┐
        │ Calculate Overall Severity:        │
        │                                    │
        │ Has Critical? NO                   │
        │ Has 2+ High severity? NO           │
        │ Has 1+ High severity? YES          │
        │ → SEVERITY = "HIGH"                │
        │                                    │
        └───────────────┬────────────────────┘
                        │
                        ↓
        ┌──────────────────────────────────────┐
        │ Return: {                            │
        │   employee_id: 5,                    │
        │   issues: [                          │
        │     {                                │
        │       type: 'Absenteeism',           │
        │       message: 'High Absenteeism',   │
        │       severity: 'High',              │
        │       details: '5 absences...'       │
        │     },                               │
        │     {                                │
        │       type: 'Low Performance',       │
        │       message: 'Low Rating',         │
        │       severity: 'High',              │
        │       details: 'Rating 2.1/5...'    │
        │     },                               │
        │     {                                │
        │       type: 'Training Gap',          │
        │       message: 'Skill Dev Needed',   │
        │       severity: 'Medium'             │
        │     }                                │
        │   ],                                 │
        │   severity: 'HIGH',                  │
        │   issue_count: 3,                    │
        │   has_critical_issues: false         │
        │ }                                    │
        └──────────────────────────────────────┘
```

---

## 4. Data Flow - Recommend Action

```
┌─────────────────────────────────────────┐
│ ActionSystem::recommendAction($analysis)│
│ Input: Issue detection result           │
└──────────────┬──────────────────────────┘
               │
               ↓
    ┌──────────────────────────┐
    │ Extract analysis data:   │
    │ ├── issues[]             │
    │ ├── severity             │
    │ ├── issue_types[]        │
    │ └── issue_count          │
    └──────────────┬───────────┘
                   │
                   ↓
    ┌──────────────────────────────────────┐
    │ DECISION TREE:                       │
    │                                      │
    │ IF severity == CRITICAL              │
    │ ├── action: "Create PIP"             │
    │ ├── confidence: 0.95 (95%)           │
    │ └── rationale: "Multiple critical..."│
    │                                      │
    │ ELSE IF severity == HIGH             │
    │ ├── IF has Low Performance AND       │
    │ │   has Absenteeism                  │
    │ │   ├── action: "Create PIP"         │
    │ │   ├── confidence: 0.90 (90%)       │
    │ │   └── rationale: "Multiple high..."│
    │ │                                    │
    │ ├── ELSE IF has Low Performance      │
    │ │   ├── action: "Assign Training"    │
    │ │   ├── confidence: 0.85 (85%)       │
    │ │   └── rationale: "Skills develop..."│
    │ │                                    │
    │ ├── ELSE IF has Absenteeism          │
    │ │   ├── action: "Issue Warning"      │
    │ │   ├── confidence: 0.75 (75%)       │
    │ │   └── rationale: "Formal notify..."│
    │ │                                    │
    │ └── ELSE                             │
    │     ├── action: "Issue Warning"      │
    │     ├── confidence: 0.80 (80%)       │
    │     └── rationale: "Performance..."  │
    │                                      │
    │ ELSE IF severity == MEDIUM           │
    │ ├── IF has Training Gap              │
    │ │   ├── action: "Assign Training"    │
    │ │   └── confidence: 0.70 (70%)       │
    │ │                                    │
    │ ├── ELSE IF has Tardiness            │
    │ │   ├── action: "Issue Warning"      │
    │ │   └── confidence: 0.65 (65%)       │
    │ │                                    │
    │ └── ELSE                             │
    │     ├── action: "Assign Mentor"      │
    │     └── confidence: 0.60 (60%)       │
    │                                      │
    │ ELSE                                 │
    │ ├── action: "No Action Required"     │
    │ └── confidence: N/A                  │
    └──────────────┬───────────────────────┘
                   │
                   ↓
    ┌──────────────────────────────────┐
    │ Return: {                        │
    │   issues: [                      │
    │     "High Absenteeism",          │
    │     "Low Performance Rating",    │
    │     "Skill Development Needed"   │
    │   ],                             │
    │   recommended_action:            │
    │     "Create PIP",                │
    │   confidence_score: 0.90,        │
    │   severity: "High",              │
    │   action_rationale:              │
    │     "Multiple critical issues..." │
    │ }                                │
    └──────────────────────────────────┘
```

---

## 5. Database Schema Relationships

```
                    employees
                        │
                        │ (1:Many)
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ↓               ↓               ↓
   performance_    attendance      pm_training_
   reviews         [READ-ONLY]    recommendations
   [READ-ONLY]                    [READ-ONLY]
        │               │
        │ overall_score │ absences
        │ rating        │ tardiness
        └─────┬─────────┘
              │
              ↓
    ActionSystem
    Analysis & Logic
              │
              ├──────────────────────┐
              ↓                      ↓
    wfa_       wfa_action_    wfa_performance_   wfa_
    actions    recommendations   improvement_    performance_
    [WRITE]    [WRITE]           plans            issues
                                  [WRITE]         [WRITE]

    action_id  recommendation_id   pip_id          issue_id
    ├─ emp_id  ├─ emp_id          ├─ emp_id       ├─ emp_id
    ├─ type    ├─ detected_issues  ├─ start_date   ├─ issue_type
    ├─ status  ├─ rec_action       ├─ end_date     ├─ severity
    └─ created ├─ confidence       ├─ reason       └─ resolved
               └─ created          ├─ action_plan
                                   └─ status

    [NO CIRCULAR RELATIONSHIPS]
    [NO MODIFICATION TO SOURCE TABLES]
    [100% ISOLATED WFA NAMESPACE]
```

---

## 6. Dashboard State Flow

```
┌─────────────────────────────────────────────────┐
│ wfa_action_dashboard.html                       │
│ Page Load                                       │
└──────────────────┬──────────────────────────────┘
                   │
                   ├─ loadLowPerformers() ← Auto-called on load
                   │  └─ fetch('/api/wfa/get_low_performers.php')
                   │
                   ├─ setDefaultDates()
                   │  ├─ pipStartDate = Today
                   │  ├─ pipEndDate = Today + 3 months
                   │  └─ actionDueDate = Today + 30 days
                   │
                   └─ Page Ready

┌────────────────────────────────────────────┐
│ STATE: app = {                             │
│   employees: [],        // All employees   │
│   filteredEmployees: [],// After filter   │
│   currentFilter: 'all', // Current filter │
│   apiBaseUrl: '../api/wfa/'                │
│ }                                          │
└────────────────────────────────────────────┘

                   │
     ┌─────────────┼─────────────┐
     │             │             │
     ↓             ↓             ↓
 [Refresh]  [Filter:All] [Filter:Critical]
  Button     Button      Button
     │             │             │
     ├─────────────┴─────────────┤
     │                           │
     ↓                           ↓
loadLow      filterBySeverity()
Performers()            │
     │                  ├─ app.currentFilter = severity
     │                  ├─ app.filteredEmployees = 
     │                  │    app.employees.filter(...)
     ↓                  ↓
api call           renderTable()
   │                    │
   ↓                    ↓
app.employees    Update DOM
    = data        <tbody>
   │
   ↓
updateStatistics()
   │
   ├─ Count Critical
   ├─ Count High
   ├─ Count Medium
   └─ Update Cards

                   │
      ┌────────────┼────────────┐
      │            │            │
      ↓            ↓            ↓
[Action]  [PIP]   [Analyze]
Button   Button    Button (auto)
      │            │            │
      ├────────────┴────────────┤
      │                         │
      ↓                         ↓
openActionModal()    analyzeEmployee()
openPIPModal()             │
      │                    ↓
      ├─ Set emp_id     fetch('.../
      ├─ Set emp_name   analyze_employee.php')
      ├─ Clear form        │
      └─ Show modal        ↓
                        Update analysis
                        data in display

     ┌────────────────────────────┐
     │ Form Submission            │
     │                            │
     ├─ Create Action Modal ────→ submitCreateAction()
     │                                  │
     ├─ Create PIP Modal ─────────→ submitCreatePIP()
     │                                  │
     └─ Action/PIP Button               │
                                        ↓
                                POST to API
                                (create_action.php or
                                 create_pip.php)
                                        │
                                        ↓
                                API Response
                                        │
                                        ├─ Success
                                        │  ├─ Show Alert
                                        │  ├─ Close Modal
                                        │  └─ Reset Form
                                        │
                                        └─ Error
                                           └─ Show Alert
```

---

## 7. Issue Severity Calculation

```
Input: Array of detected issues

Step 1: Check for CRITICAL issues
┌─────────────────────────────┐
│ ANY issue.severity == 'Critical'? │
│ YES → Return 'CRITICAL'     │
│ NO  → Continue              │
└─────────────────────────────┘

Step 2: Count HIGH severity issues
┌─────────────────────────────┐
│ HIGH_COUNT = count where    │
│ issue.severity == 'High'    │
│                             │
│ HIGH_COUNT >= 2?            │
│ YES → Return 'HIGH'         │
│                             │
│ HIGH_COUNT == 1?            │
│ YES → Return 'HIGH'         │
│                             │
│ NO  → Continue              │
└─────────────────────────────┘

Step 3: Count MEDIUM severity issues
┌─────────────────────────────┐
│ MEDIUM_COUNT = count where  │
│ issue.severity == 'Medium'  │
│                             │
│ MEDIUM_COUNT >= 2?          │
│ YES → Return 'MEDIUM'       │
│                             │
│ MEDIUM_COUNT == 1?          │
│ YES → Return 'MEDIUM'       │
│                             │
│ NO  → Return 'LOW'          │
└─────────────────────────────┘

Output: Final severity level
        ('CRITICAL' | 'HIGH' | 'MEDIUM' | 'LOW')
```

---

## 8. Modal Form Flow - Create Action

```
┌──────────────────────────────────┐
│ User clicks [Action] button      │
│ for employee ID 5                │
└──────────┬───────────────────────┘
           │
           ↓
    openActionModal(5, 'John Doe')
           │
           ├─ Set: actionEmployeeId = 5
           ├─ Set: actionEmployeeName = 'John Doe'
           ├─ Clear: actionType
           ├─ Clear: actionDescription
           └─ Show: Modal

           ↓

    ┌──────────────────────────────┐
    │ MODAL SHOWN:                 │
    │                              │
    │ Employee: [John Doe]         │ (readonly)
    │ Action Type: [dropdown] ▼    │
    │   - Training                 │
    │   - Warning                  │
    │   - PIP                       │
    │   - Mentoring                │
    │ Description: [textarea]      │
    │ Due Date: [date picker]      │
    │                              │
    │ [Cancel] [Create Action]     │
    └──────────────┬───────────────┘
                   │
          ┌────────┴────────┐
          │                 │
          ↓                 ↓
      [Cancel]         [Create Action]
      Button           Button
          │                 │
          ↓                 ↓
      closeModal()    submitCreateAction()
          │                 │
          ↓                 ↓
      Hide Modal        Validate Form
                        ├─ employee_id ✓
                        ├─ action_type ✓
                        ├─ description ✓
                        └─ due_date ✓
                             │
                             ↓
                        POST /api/wfa/
                        create_action.php
                             │
                ┌────────────┴────────────┐
                │                         │
                ↓                         ↓
            Success                   Error
                │                         │
                ├─ Show Alert         ├─ Show Alert
                ├─ Close Modal        └─ Keep Modal
                ├─ Reset Form
                └─ (DB updated)

        Record created in:
        wfa_actions table
```

---

## 9. Complete API Call Sequence

```
BROWSER                    API LAYER              DATABASE
  │                          │                       │
  ├── GET /get_low_          │                       │
  │   performers.php ────→   │                       │
  │                          ├─ ActionSystem:       │
  │                          │  getLowPerformers() │
  │                          │                      │
  │                          ├─ SELECT * FROM      │
  │                          │  employees e        │
  │                          │  LEFT JOIN perf...──→ Query
  │                          │◄──────────────────── Result
  │                          │                      │
  │◄──── JSON Array ─────── │                      │
  │ [{emp_data, issues,   │                       │
  │   severity, rec}, ...]│                       │
  │                        │                       │
  ├── GET /analyze_        │                       │
  │   employee.php ────→   │                       │
  │  ?employee_id=5       │                       │
  │                        ├─ ActionSystem:       │
  │                        │  detectIssues(5)    │
  │                        │  recommendAction()  │
  │                        │  storeRecommend...  │
  │                        │                      │
  │                        ├─ SELECT ... WHERE   │
  │                        │  employee_id=5 ────→ Query
  │                        │◄──────────────────── Result
  │                        │                      │
  │                        ├─ INSERT INTO wfa_   │
  │                        │  action_recommend──→ Write
  │                        │                      │
  │◄─── JSON Analysis ──── │                      │
  │ {issues, severity, │                       │
  │  recommendation}  │                        │
  │                    │                       │
  ├── POST /create_    │                       │
  │   action.php ────→  │                       │
  │ {emp_id, type,    │                       │
  │  desc, due_date}  │                       │
  │                    ├─ ActionSystem:       │
  │                    │  createAction()      │
  │                    │                      │
  │                    ├─ INSERT INTO wfa_   │
  │                    │  actions ──────────→ Write
  │                    │◄──────────────────── ✓
  │                    │  lastInsertId()    │
  │◄─── {success,   ── │                      │
  │      action_id} │                       │
  │                  │                       │
  └── (repeat as needed)                     │
                                              │
     Database Updates:
     ├─ wfa_actions (new record)
     ├─ wfa_performance_improvement_plans (PIPs)
     ├─ wfa_action_recommendations (audit trail)
     └─ wfa_performance_issues (tracking)
     
     Original Tables [UNTOUCHED]:
     ├─ performance_reviews
     ├─ pm_appraisals
     ├─ pm_*
     └─ (All read-only)
```

---

## 10. Color Code Legend

```
SEVERITY LEVELS            RATING LEVELS
┌─────────────────────┐   ┌──────────────────────┐
│ CRITICAL: Red       │   │ < 2.0: Critical Red  │
│ #e74c3c             │   │ < 2.5: High Orange   │
│ (Urgent action)     │   │ < 3.5: Medium Yellow │
│                     │   │ ≥ 3.5: Low Green     │
│ HIGH: Orange        │   └──────────────────────┘
│ #e67e22             │
│ (Significant)       │   STATUS BADGES
│                     │   ┌──────────────────────┐
│ MEDIUM: Yellow      │   │ Pending: Gray        │
│ #f39c12             │   │ In Progress: Blue    │
│ (Moderate)          │   │ Completed: Green     │
│                     │   │ Cancelled: Red       │
│ LOW: Green          │   └──────────────────────┘
│ #27ae60             │
│ (Minor/None)        │
└─────────────────────┘
```

---

## Summary

This diagram set shows:

1. **System Architecture** - How layers communicate
2. **Data Flows** - Step-by-step processes
3. **Database Relationships** - Schema organization
4. **UI State** - Dashboard interactions
5. **Logic Flows** - Decision trees
6. **API Sequences** - Complete request/response cycles
7. **Color Coding** - Visual hierarchy

All components work together to create a seamless, non-invasive system that monitors employee performance and recommends appropriate interventions without modifying the performance module!

🎯 **The system is designed to be modular, scalable, and capstone-ready!**
