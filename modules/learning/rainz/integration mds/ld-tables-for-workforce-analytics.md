# Which `ld_` Tables Feed Workforce Analytics (Current System)

> **Scope:** maps the Learning & Development tables that exist in the `hrms` database today to the data flows the architecture diagram shows going into **Workforce Analytics and Reporting** (Attrition & Turnover Analysis, HR Dashboard Metrics, Predictive Analytics, Custom HR Report).
>
> **Key identity note:** the shared employee identity across the whole HRIS is `em_employees.employee_id`. In L&D, the same person appears as `ld_enrollment.learner_id`, `ld_grade.learner_id`, `ld_certificate.learner_id`, etc. Every analytic join into the `wfa_*` / `em_*` tables is `learner_id = employee_id`.

---

## 1. Data flows from the diagram → source tables

The diagram lists six labeled flows leaving Learning & Development toward Workforce Analytics. Here is the current table-level source for each:

| Diagram data flow | `ld_` tables that provide it (current workspace) | Ready? |
|---|---|---|
| **Training participation & completion data** | `ld_enrollment`, `ld_progress`, `ld_grade` | ✅ used by live code |
| **Training engagement data / learning engagement trends** | `ld_enrollment` (created/last_accessed), `ld_quiz_session`, `ld_quiz_attempt`, `ld_video_conference` + `ld_conference_attendance`, `ld_rating`, `ld_bookmark`, `ld_favorite`, `ld_comment` | ✅ mostly used by live code |
| **Certification status & expiry data** | `ld_certificate` (issued_at, valid_until, status), `ld_certificate_template`, `ld_course_version` | ✅ used by live code |
| **Turnover risk indicators** | Derived — no direct table; see §3 | ⚠️ derived only, not stored |
| **Predicted skill/competency needs** | `ld_skill`, `ld_course_skill`, `ld_module_skill`, `ld_program_skill`, `ld_learning_path_skill`, `ld_skill_snapshot` | ⚠️ `ld_skill_snapshot` exists but is **never written by code** |
| **Skill gaps analysis** | `ld_skill` + `ld_course_skill` + `ld_course` + `ld_enrollment` (live query in `get-skill-gap-analysis.php`) | ✅ computed live |
| **Reporting requirements/targets** | `ld_integration_log` (audit of what was pushed), `ld_audit_log` | ✅ schema + plumbing exist |

---

## 2. Table-by-table mapping (53 `ld_` tables)

### A. Core content — needed as the *dimension* side of analytics

| Table | Role in Workforce Analytics |
|---|---|
| `ld_course` | Course catalog; categories, status, `program_id`; base for completion/participation metrics and compliance tagging |
| `ld_program` | Training program grouping (courses/programs); program-level engagement |
| `ld_module`, `ld_lesson`, `ld_lesson_file` | Curriculum structure — used to normalize progress/quiz denominators |
| `ld_course_instructor` | Instructor load / who delivers what training |
| `ld_course_version` | Version an employee completed (snapshot correctness for historical metrics) |
| `ld_course_template` | Reusable course templates (HR planning) |

### B. Participation, completion & scores — **core feeds**

| Table | Columns that matter | Feeds |
|---|---|---|
| `ld_enrollment` | `learner_id`, `course_id`, `status` (invited/enrolled/in_progress/completed/withdrawn), `enrolled_at`, `completed_at`, `last_accessed_at`, `created_at`, `invited_by` | **Participation rate, completion rate, withdrawal rate, enrollment trend, engagement recency** → `wfa_skill_gap_analysis`, `wfa_reports`, `wfa_employee_metrics`, `wfa_risk_assessment.low_engagement_flag` |
| `ld_progress` | `enrollment_id`, `item_type` (module/lesson/quiz/evaluation), `reference_id`, `status`, `completed_at` | Granular module/lesson completion — progress %, time-to-complete |
| `ld_grade` | `learner_id`, `course_id`, `final_score`, `status` (passed/failed), `issued_at` | **Average score, pass rate, top performers** → `wfa_performance_distribution`, `wfa_reports` |

### C. Certification — **certification status & expiry flow**

| Table | Columns that matter | Feeds |
|---|---|---|
| `ld_certificate` | `learner_id`, `course_id`, `issued_at`, `valid_until`, `status` (active/archived) | **Certified headcount, expiring-soon alerts, expired certification lists** → `wfa_reports` |
| `ld_certificate_template` | `course_id`, `is_active` | Which courses actually grant certifications |

### D. Engagement & live training

| Table | Columns that matter | Feeds |
|---|---|---|
| `ld_quiz_session` | `learner_id`, `item_type`, `reference_id`, `started_at`, `submitted_at`, `status`, `score`, `passed` | Quiz attempt engagement, avg score per quiz, drop-off (started vs submitted) |
| `ld_quiz_attempt` | `learner_id`, `quiz_id`, `score`, `passed`, `attempted_at` | Simple attempt records for score summaries |
| `ld_quiz`, `ld_evaluation` | configuration (passing score, max attempts) | Denominators for quiz analytics |
| `ld_video_conference` | `scheduled_at`, `status`, `course_id`/`program_id` | Scheduled vs held live sessions |
| `ld_conference_attendance` | `learner_id`, `attended`, `joined_at` | **Live training attendance** → Time & Attendance + engagement |
| `ld_rating`, `ld_bookmark`, `ld_favorite`, `ld_comment`, `ld_note` | `learner_id`, timestamps, item refs | Engagement/interest signals (satisfaction proxies) |
| `ld_calendar_event`, `ld_user_event` | `event_date`, `type`, `reference_id` | Training calendar activity |

### E. Skills & gaps — **skill needs / predictive flow**

| Table | Columns that matter | Feeds |
|---|---|---|
| `ld_skill` | `name`, `status`, `suggested` | Skill taxonomy used across modules |
| `ld_course_skill`, `ld_module_skill`, `ld_program_skill`, `ld_learning_path_skill` | mapping tables (entity → skill) | Skill coverage per employee via completed content |
| `ld_skill_snapshot` | `learner_id`, `total_skills`, `acquired_count`, `gap_count`, `acquired_skills`, `gap_skills`, `snapshot_date` | **Direct per-employee skill-gap feed** → `wfa_skill_gap_analysis` (⚠️ table exists but no code writes it today) |
| `ld_recommendation_course_map` | `development_area`, `course_id` | Maps a development area to the course that fixes it (⚠️ schema-only, unused) |
| `ld_learning_path` | `assigned_to`, `type`, `status`, `kt_plan_id` | Onboarding / knowledge-transfer learning paths per employee |

### F. Plumbing (how the data travels)

| Table | Role |
|---|---|
| `ld_api_key` | Contains the seeded `workforce-analytics` API key — auth for the outbound `get-workforce-analytics.php` endpoint |
| `ld_integration_event` | Idempotency records for inbound/outbound calls |
| `ld_integration_log` | Audit trail of every payload pushed to Workforce Analytics |
| `ld_audit_log` | Admin audit trail (who generated reports, etc.) |
| `ld_setting` | System settings (timezone, expiry defaults) used when interpreting dates |
| `ld_notification`, `ld_announcement`, `ld_message` | Not strictly analytics, but provide context data (e.g., announcement → engagement spike correlation) |

---

## 3. How the data is currently consumed (workspace reality)

1. **Workforce Analytics module (`modules/workforce/`)** is currently a **stub** — `dashboard-overview.php` renders an empty page. None of the `wfa_*` tables are read by code yet; they only exist in the schema.
2. **The integration contract is documented, not built.** `modules/learning/rainz/documention/api-integration-plan.md` defines the outbound endpoint `get-workforce-analytics.php` that reads `ld_enrollment`, `ld_certificate`, and `ld_progress` aggregates and feeds `wfa_skill_gap_analysis` + `wfa_reports`. The endpoint file does not exist yet.
3. **In-module analytics that already work** (and are the closest thing to workforce analytics today):
   - `Analytics` class + `pages/admin/analytics.php` — course/learner counts, completion rates, pass rates, enrollment trends, popular courses, time spent.
   - `pages/instructor/analytics-subpage/ajax/get-skill-gap-analysis.php` — live skill-gap query (`ld_skill` × `ld_course_skill` × `ld_course` × `ld_enrollment`), threshold at 70% completion.
4. **`wfa_*` destination tables confirmed to exist** in the shared DB: `wfa_skill_gap_analysis`, `wfa_reports`, `wfa_attrition_tracking`, `wfa_risk_assessment`, `wfa_at_risk_employees_summary`, `wfa_employee_metrics`, `wfa_performance_distribution`, plus 13 more (`wfa_age_distribution`, `wfa_tenure_analysis`, `wfa_department_analytics`, …).

---

## 4. Bottom line

**Data L&D can hand over today (all in `ld_enrollment` / `ld_certificate` / `ld_progress` / `ld_grade` / `ld_quiz_*` / `ld_conference_attendance`):**
training participation & completion, learning engagement trends, certification status/expiry, and skill-gap aggregates.

**Data that is only derivable (no dedicated table, computed on the fly):** turnover risk indicators (from `last_accessed_at`, stalled enrollments, failed grades, expired certs).

**Data that exists in schema but is not populated by any code:** `ld_skill_snapshot`, `ld_recommendation_course_map`, `ld_recognition_course_map` — these are the intended bridges for skill gaps / training recommendations / recognition, waiting on the cross-module API work.

See **`ld-tables-gaps-improvements.md`** for what's missing and how to close the gaps.