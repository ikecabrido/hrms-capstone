# Cross-Module Integration — Learning & Development ↔ Other HRIS Modules

> **Scope:** maps every data flow between **Learning & Development** and the other modules in the architecture diagram (Performance, Compliance & Legal, Time & Attendance, Recruitment & Onboarding, Employee Management, Workforce Analytics, Employee Engagement) to the **actual implementation** in this workspace.
>
> **Verified against the live database on 2026-09-05** — endpoint statuses below are from direct tests, not from the plan.
>
> **Updated 2026-09-05:** the 3 previously-broken outbound endpoints are **fixed**; the inbound endpoints now write their trail records (`ld_training_recommendation`, `ld_recognition_unlock`, `ld_assessment_result`); `get-workforce-analytics.php` now serves risk + skill snapshots, cert expiry, and KT-path counts. See `CROSS_MODULE_GAPS.md` for the full change list.

---

## 1. The contract boundary

L&D exposes a real HTTP API under `modules/learning/api/` that other modules call (and that L&D is called by). It exists as a real interface even though all modules share one `hrms` database — so module teams don't need to read each other's code.

```
modules/learning/api/
├── inbound/    (other modules → L&D; L&D consumes their data)
│   ├── receive-appraisal-data.php
│   ├── receive-employee-profile.php
│   ├── receive-job-test-result.php
│   └── receive-recognition-eligibility.php
└── outbound/   (L&D → other modules; they pull L&D data)
    ├── get-training-completion.php
    ├── get-learning-performance.php
    ├── get-compliance-training-log.php
    ├── get-attendance-data.php
    └── get-workforce-analytics.php
```

### Shared foundation (all built and working)

| Piece | File | Role |
|---|---|---|
| `ApiAuth` | `classes/apiauth.php` | Verifies `X-API-Key` / `Authorization: Bearer` against `ld_api_key`; per-module keys seeded for all 8 modules |
| `IntegrationLog` | `classes/integrationlog.php` | Writes every call to `ld_integration_log`; idempotency via `ld_integration_event` |
| `CourseMap` | `classes/coursemap.php` | Translates external values (`development_area`, `recognition_category`) → `ld_course.id` via `ld_recommendation_course_map` (15 rows) + `ld_recognition_course_map` (9 rows) |
| `Enrollment` | `classes/enrollment.php` | `invite()` — the auto-assignment action used by all inbound endpoints |

**Shared identity:** `ld_*.learner_id` === `em_employees.employee_id`. The join is by value; no FK is enforced (see gaps doc).

---

## 2. Endpoint inventory (tested status)

| Endpoint | Dir | Module | Diagram flow | Their-side table | L&D tables used | Status (tested) |
|---|---|---|---|---|---|---|
| `receive-appraisal-data.php` | In | Performance | Recommended training plan / Appraisal results | `pm_training_recommendations` | `ld_recommendation_course_map`, `ld_enrollment`, `ld_training_recommendation` | ✅ auto-invites on `status=Approved` + writes recommendation trail |
| `receive-employee-profile.php` | In | Employee Mgmt | Employee docs & credentials, role & job history | `em_employees`, `em_departments`, `em_positions`, `employee_skills`, `employee_certifications`, `employee_documents` | `ld_enrollment`, `ld_course_skill` (reads) | ✅ works — GET now also returns external skills, certs, documents |
| `receive-job-test-result.php` | In | Recruitment | Assessment/course results | *(none found — open)* | `ld_learning_path_item`, `ld_enrollment`, `ld_assessment_result` | ✅ end-to-end tested (writes assessment row) |
| `receive-recognition-eligibility.php` | In | Engagement | Recognition-linked training eligibility | `eer_recognitions` | `ld_recognition_course_map`, `ld_enrollment`, `ld_recognition_unlock` | ✅ auto-invites on `status=approved` + writes unlock record |
| `get-training-completion.php` | Out | Performance | E-learning completion & scores, certification records | `pm_employee_training` | `ld_enrollment`, `ld_certificate`, `ld_quiz_attempt` | ✅ works |
| `get-learning-performance.php` | Out | Performance | Identified skill gaps, scores | pm's own tables | `ld_quiz_attempt`, `ld_course_skill`, `ld_enrollment` | ✅ fixed (`qa.attempted_at`) |
| `get-compliance-training-log.php` | Out | Compliance | Compliance training completion logs | `lc_trainings` | `ld_enrollment`, `ld_course`, `ld_certificate` | ✅ fixed (`c.enrollment_deadline`; `is_compliance_required` filter; cert-based expiry) |
| `get-attendance-data.php` | Out | Time & Attendance | Training session attendance data | `ta_attendance` | `ld_conference_attendance`, `ld_video_conference` | ✅ fixed (`ca.video_conference_id`; dropped `left_at`; `LEFT JOIN`) |
| `get-workforce-analytics.php` | Out | Workforce Analytics | All 7 analytics flows | `wfa_skill_gap_analysis`, `wfa_reports`, `wfa_risk_assessment` | `ld_enrollment`, `ld_course`, `ld_certificate`, `ld_skill`, `ld_engagement_snapshot`, `ld_skill_snapshot`, `ld_learning_path` | ✅ works — serves risk bands/at-risk, skill snapshots, expiring certs, KT paths |

---

## 3. Module-by-module flows

### Performance Management (pink group)

| Diagram flow | Direction | Path |
|---|---|---|
| E-learning completion & scores → Training Program Mgmt | L&D → PM | `get-training-completion.php` (→ `pm_employee_training`) ✅ |
| Identified skill gaps → Training Program Mgmt | L&D → PM | `get-learning-performance.php` (skill proficiency) ❌ broken |
| 360-degree feedback results → Training Program Mgmt | PM → L&D | **No endpoint** — `pm_feedback_360_entries` exists but is never consumed ⚠️ |
| Recommended training plan → Training Program Mgmt | PM → L&D | `receive-appraisal-data.php` ✅ (maps `development_area` → course via `ld_recommendation_course_map`, then `Enrollment::invite()`) |
| Appraisal results & turnover risk → Training Program Mgmt | PM → L&D | Same endpoint (appraisal-driven recommendations) |

**Their-side tables (real, in `hrms`):** `pm_training_recommendations` (0 rows), `pm_employee_training` (0 rows), `pm_feedback_360_entries` (0 rows), plus `pm_appraisals`, `pm_appraisal_items`, `pm_review_cycles`, `pm_training_programs`, `pm_training_evaluations`.

### Compliance & Legal Management (blue-grey group)

| Diagram flow | Direction | Path |
|---|---|---|
| Compliance training completion logs → Certification Mgmt | L&D → Compliance | `get-compliance-training-log.php` (→ `lc_trainings`) ❌ broken |

**Their-side table:** `lc_trainings` (8 rows exist — waiting on L&D data to merge).

### Time & Attendance Management (green group)

| Diagram flow | Direction | Path |
|---|---|---|
| Training session attendance data → Course Enrollment Tracking | L&D → T&A | `get-attendance-data.php` (→ `ta_attendance`) ❌ broken + no write-side data (0 attendance rows) |

**Their-side table:** `ta_attendance` (0 rows).

### Recruitment & Onboarding (magenta group)

| Diagram flow | Direction | Path |
|---|---|---|
| Assessment/course results → Skill Gaps Analysis | Recruitment → L&D | `receive-job-test-result.php` ✅ (loads; auto-enrolls onboarding path on pass) |

**Their-side table:** `lc_recruitment` has no test/assessment columns — **no source table confirmed yet** (open item from `api-integration-plan.md`, still open).

### Employee Management (orange group)

| Diagram flow | Direction | Path |
|---|---|---|
| Employee documents & credentials → L&D | EM → L&D | `receive-employee-profile.php` ✅ (GET reads `em_employees` + position/department + enrollments + completed skills) |
| Employee role & job history → L&D | EM → L&D | Same endpoint (position/department) |
| Training completion & certification records → EM | L&D → EM | `get-training-completion.php` ✅ (same data also feeds PM) |

**Their-side tables:** `em_employees`, `em_departments`, `em_positions`, `em_roles`, `em_education`, `employee_documents`, `employee_skills` (0 rows), `employee_certifications` (11 rows), `employment_history`, `employee_change_history`.

### Workforce Analytics & Reporting (purple group)

| Diagram flow | Direction | Path |
|---|---|---|
| Training engagement data, learning engagement trends, training participation & completion | L&D → WFA | `get-workforce-analytics.php` ✅ (enrollment stats, top courses, completion trend) |
| Certification status & expiry data | L&D → WFA | Partial — cert totals only; **no expiry list** ⚠️ |
| Turnover risk indicators | L&D → WFA | **Not served yet** — `ld_engagement_snapshot` (built, cron-populated) not in endpoint ⚠️ |
| Predicted skill/competency needs | L&D → WFA | Partial — live skill-gap query in endpoint; `ld_skill_snapshot` (built) not served ⚠️ |
| Reporting requirements/targets | — | `wfa_reports` is the consumer; L&D serves via `get-workforce-analytics.php` |
| Skill gap results (internal) | L&D internal | `instructor/analytics-subpage/ajax/get-skill-gap-analysis.php` ✅ + `Analytics::getSkillGapOverview()` ✅ |

**Their-side tables:** `wfa_skill_gap_analysis`, `wfa_reports`, `wfa_risk_assessment`, `wfa_at_risk_employees_summary`, `wfa_employee_metrics`, `wfa_performance_distribution`, plus 14 more (`wfa_attrition_tracking`, `wfa_monthly_attrition`, …). All exist in schema; none are read by module code yet (workforce module is a stub).

### Employee Engagement & Relations (cyan group)

| Diagram flow | Direction | Path |
|---|---|---|
| Recognition-linked training eligibility → Recognition & Rewards | Engagement → L&D (unlock) + L&D → Engagement (eligibility echo) | `receive-recognition-eligibility.php` ✅ (auto-invites on `status=approved` via `ld_recognition_course_map`) |

**Their-side tables:** `eer_recognitions` (0 rows), plus `eer_rewards`, `eer_reward_redemptions`, `eer_badges`, `eer_employee_badges`, `eer_surveys`, …

### Exit Management / Knowledge Transfer (bonus — not in diagram, live in workspace)

Fully documented in **`KNOWLEDGE_TRANSFER_INTEGRATION.md`**. Currently live: 1 KT learning path exists (`ld_learning_path` with `kt_plan_id` set). Exit tables (`exit_interviews`, `exit_surveys`, `exit_terminations`, `exit_knowledge_transfer_plans`) are the source of turnover context (0 interview rows today).

---

## 4. How to call the endpoints

**Auth:** every endpoint requires `X-API-Key: <key>` (or `Authorization: Bearer <key>`). Keys live in `ld_api_key` — one per module, e.g. `learning-development`, `performance-management`, `recruitment`, `employee-management`, `employee-engagement`.

**Inbound** (other modules → L&D): `POST` with JSON body, e.g.:
```bash
curl -X POST http://localhost/hrms-capstone/modules/learning/api/inbound/receive-appraisal-data.php \
  -H "X-API-Key: pm_..." -H "Content-Type: application/json" \
  -d '{"recommendation_id": 1, "employee_id": 5, "development_area": "Leadership", "status": "Approved"}'
```
Pattern: auth → validate → idempotency check (`ld_integration_event`) → business logic (`Enrollment::invite()`) → log → respond. Non-approved statuses are logged and skipped.

**Outbound** (L&D → other modules): `GET` with optional filters (`employee_id`, `course_id`, `date_from`, `date_to`), JSON response formatted to match the destination table's column names (`pm_employee_training`, `lc_trainings`, `ta_attendance`, `wfa_*` shapes).

---

## 5. Status summary

| Layer | Status |
|---|---|
| Foundation (`ApiAuth`, `IntegrationLog`, keys, `CourseMap`, `Enrollment`) | ✅ all built |
| Inbound endpoints (4) | ✅ all work — appraisal/recognition/job-test now write their trail records |
| Outbound endpoints (5) | ✅ **all 5 working** (3 schema-mismatch bugs fixed) |
| Workforce analytics feeds (snapshots, aggregates) | ✅ built **and served** — risk bands, skill snapshots, expiring certs, KT paths in the endpoint payload |
| Employee data consumption (skills, certs, documents) | ✅ profile endpoint returns all three; skill cron merges `employee_skills` |
| Exit / Knowledge Transfer | ✅ live (1 KT path generated) + exit-case risk factor in engagement snapshots |

Remaining open items (UI work + cross-team coordination) are tracked in **`CROSS_MODULE_GAPS.md`** §10.