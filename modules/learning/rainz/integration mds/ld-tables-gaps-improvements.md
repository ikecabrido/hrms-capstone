# Gaps & Improvements — `ld_` Tables for Workforce Analytics

> **Companion to `ld-tables-for-workforce-analytics.md`.** Everything below is a gap between what the architecture diagram expects from Learning & Development and what the current workspace actually has. Each item notes whether the fix is *add a table*, *populate an existing unused table*, or *wire up code*.

> **Implementation status (2026-09-05):** schema changes, snapshot crons, and Analytics aggregates are **implemented** (marked ✅ below). The remaining items marked ⚠️ depend on the cross-module API endpoints (`api/outbound/get-workforce-analytics.php`, `api/inbound/*`) documented in `modules/learning/rainz/documention/api-integration-plan.md`, which are still to be built.

---

## What was implemented

| Artifact | Path |
|---|---|
| Migration SQL (all tables + ALTERs) | `modules/learning/sql/workforce_analytics_improvements.sql` (applied to `hrms`) |
| Skill snapshot cron (Gaps 6+7) | `modules/learning/cron/materialize-skill-snapshots.php` (daily) |
| Engagement/risk snapshot cron (Gap 1) | `modules/learning/cron/materialize-engagement-snapshots.php` (daily) |
| Workforce analytics aggregate methods (Gap 9) | `modules/learning/classes/analytics.php` — `getEnrollmentsByDepartment()`, `getEnrollmentsByPosition()`, `getCompletionsByMonth()`, `getCertificationExpiryOverview()`, `getSkillGapOverview()`, `getRiskSummary()` |

---

## Gap 1 — Turnover risk indicators: no table stores them

**Diagram expects:** L&D sends "turnover risk indicators" to Attrition & Turnover Analysis.

**Current state:** nothing stores a risk signal. The `wfa_risk_assessment` table has the flags (`low_engagement_flag`, `low_performance_flag`, `high_absence_flag`) but expects an input feed. L&D *can* compute risk proxies from `ld_enrollment.last_accessed_at`, stalled `status = 'in_progress'` enrollments, failed `ld_grade` records, and `ld_certificate.valid_until` — but that computation is repeated on every query and has no history.

**Fix (recommended): add `ld_engagement_snapshot`** — a periodic per-employee snapshot that doubles as the predictive-analytics time series:

```sql
CREATE TABLE ld_engagement_snapshot (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,           -- = em_employees.employee_id
    snapshot_date DATE NOT NULL,
    active_days_30 INT UNSIGNED DEFAULT 0,
    courses_in_progress INT UNSIGNED DEFAULT 0,
    courses_completed_ytd INT UNSIGNED DEFAULT 0,
    avg_score DECIMAL(5,2) NULL,
    quizzes_started INT UNSIGNED DEFAULT 0,
    quizzes_abandoned INT UNSIGNED DEFAULT 0,
    days_since_last_activity INT UNSIGNED NULL,
    expired_certificates INT UNSIGNED DEFAULT 0,
    risk_score DECIMAL(5,2) NULL,               -- computed composite
    risk_factors JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_learner_snapshot (learner_id, snapshot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Populate via a cron (mirror the existing `modules/learning/cron/` pattern, e.g. `backup-database.php`). Outbound `get-workforce-analytics.php` then feeds `wfa_risk_assessment` / `wfa_at_risk_employees_summary` directly.

**Status: ✅ implemented** — table in the migration SQL, populated daily by `materialize-engagement-snapshots.php`. ⚠️ `get-workforce-analytics.php` (outbound push to `wfa_risk_assessment`) still to be built.

---

## Gap 2 — Per-employee training recommendation records

**Diagram expects:** Performance Management pushes "Recommended training plan" into L&D; L&D uses it for auto-assignment and Analytics should see it.

**Current state:** `ld_recommendation_course_map` (development_area → course) exists but is **schema-only**. The integration plan routes `pm_training_recommendations` → `Enrollment::invite()`, which means recommendations only exist as `ld_enrollment` rows with `status = 'invited'` — you lose the *why* (development area, priority, source appraisal).

**Fix: add `ld_training_recommendation`** to keep the recommendation itself queryable by Workforce Analytics:

```sql
CREATE TABLE ld_training_recommendation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,          -- = learner_id
    course_id INT UNSIGNED NOT NULL,
    development_area VARCHAR(150) NOT NULL,
    priority_level ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    source VARCHAR(50) NOT NULL,                -- 'appraisal' | '360_feedback' | 'skill_gap' | 'job_test'
    external_reference_id VARCHAR(255) NULL,    -- ties to pm_training_recommendations for idempotency
    status ENUM('recommended','invited','enrolled','completed','declined') NOT NULL DEFAULT 'recommended',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

This also gives the diagram's "Predicted skill/competency needs" a concrete record trail (recommendation → invite → enrollment → completion).

**Status: ⚠️ table implemented** — `ld_training_recommendation` exists in the migration SQL. No writer yet: `receive-appraisal-data.php` (inbound, maps `pm_training_recommendations` → this table) is still to be built.

---

## Gap 3 — Compliance training tagging

**Diagram expects:** Compliance & Legal receives "compliance training completion logs"; course enrollment tracking receives "training session attendance data".

**Current state:** no way to reliably mark a course as compliance training. `ld_course.category` is a free-text `VARCHAR(100)` and compliance detection is "by convention" via `ld_course_skill`. Fragile.

**Fix: add a compliance flag to `ld_course`** (one-line ALTER, no new table):

```sql
ALTER TABLE ld_course
    ADD COLUMN compliance_category VARCHAR(100) NULL AFTER category,
    ADD COLUMN is_compliance_required BOOLEAN NOT NULL DEFAULT FALSE AFTER compliance_category;
```

Then `get-compliance-training-log.php` filters `WHERE is_compliance_required = 1` instead of string matching.

**Status: ✅ columns implemented** on `ld_course` (`compliance_category`, `is_compliance_required`). ⚠️ `get-compliance-training-log.php` (outbound) still to be built.

---

## Gap 4 — Job test / assessment results from Recruitment

**Diagram expects:** Recruitment "Job Test" results flow into L&D (Skill Gaps Analysis / onboarding learning paths).

**Current state:** nothing. The integration plan explicitly flags `receive-job-test-result.php` as **open — source table unconfirmed** (no recruitment assessment table exists in this DB export). L&D has no place to store an external assessment outcome.

**Fix: add `ld_assessment_result`** (needed regardless of who writes it):

```sql
CREATE TABLE ld_assessment_result (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,           -- = employee_id (candidate id at onboarding time)
    source VARCHAR(50) NOT NULL,                -- 'recruitment_job_test' | 'onboarding_assessment'
    assessment_name VARCHAR(150) NOT NULL,
    score DECIMAL(5,2) NULL,
    result ENUM('passed','failed','pending') NOT NULL DEFAULT 'pending',
    skill_ids JSON NULL,                        -- skills probed by the assessment
    taken_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

This is also the seed data for `ld_skill_snapshot` on new hires (their starting skill state comes from the job test, not from completed courses).

**Status: ⚠️ table implemented** — `ld_assessment_result` exists in the migration SQL. No writer yet: `receive-job-test-result.php` (inbound) is still to be built (its source table on the Recruitment side is still unconfirmed).

---

## Gap 5 — Recognition-linked training eligibility (per employee)

**Diagram expects:** Employee Recognition unlocks courses/programs as rewards; E-Learning feeds "recognition-linked training eligibility" back.

**Current state:** `ld_recognition_course_map` (recognition_category → course) exists but is unused, and there is **no per-employee unlock record**. The plan routes `eer_recognitions` → "unlock a course", but nothing records who was unlocked, for which course, and whether they used it.

**Fix: add `ld_recognition_unlock`**:

```sql
CREATE TABLE ld_recognition_unlock (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    recognition_category VARCHAR(100) NOT NULL,
    external_reference_id VARCHAR(255) NULL,    -- eer_recognitions.id for idempotency
    status ENUM('unlocked','redeemed','expired') NOT NULL DEFAULT 'unlocked',
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    redeemed_at TIMESTAMP NULL,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Status: ⚠️ table implemented** — `ld_recognition_unlock` exists in the migration SQL. No writer yet: `receive-recognition-eligibility.php` (inbound) is still to be built.

## Gap 6 — Employee role/position context in skill snapshots

**Diagram expects:** Employee Management's role & job history feeds prerequisite checks and skill-gap analysis.

**Current state:** `ld_skill_snapshot` is per-learner but has **no position/department context**, so `wfa_skill_gap_analysis` (which is department-level: `department_id`, `required_proficiency`, `current_proficiency_avg`) can't be fed with the employee's role. The live gap query in `get-skill-gap-analysis.php` is per-instructor and doesn't know job requirements at all.

**Fix (two parts):**
1. Add context columns to `ld_skill_snapshot`:

```sql
ALTER TABLE ld_skill_snapshot
    ADD COLUMN position_id INT UNSIGNED NULL AFTER learner_id,
    ADD COLUMN department_id INT UNSIGNED NULL AFTER position_id;
```

2. Populate `ld_skill_snapshot` (see Gap 7) — right now it's never written, so the columns are theoretical.

**Status: ✅ implemented** — `position_id`/`department_id` added in the migration SQL and now populated by `materialize-skill-snapshots.php` (LEFT JOIN to `em_employees`).

---

## Gap 7 — `ld_skill_snapshot` exists but is never populated

**Current state (confirmed by search):** `ld_skill_snapshot` is referenced **nowhere** in `modules/`. Skill-gap analysis is recomputed live per request from `ld_skill`/`ld_course_skill`/`ld_course`/`ld_enrollment`. For Workforce Analytics that means: no history, no per-employee gap list at rest, no ability to show *trend* (gaps closing or widening).

**Fix:** a cron job that materializes the live query into `ld_skill_snapshot` (plus Gap 6's position/department context) on a schedule (e.g. daily). `get-workforce-analytics.php` then serves `ld_skill_snapshot` straight into `wfa_skill_gap_analysis`, and predictive analytics gets its time series.

**Status: ✅ implemented** — `materialize-skill-snapshots.php` (daily cron) computes per-learner acquired/gap skills and preserves history (one row per learner per day).

---

## Gap 8 — Attendance beyond video conferences

**Diagram expects:** "Training session attendance data" flows to Time & Attendance and enrollment tracking.

**Current state:** only `ld_conference_attendance` exists, which covers **video conferences only**. In-person / classroom trainings, which the training calendar (`ld_calendar_event`, `ld_user_event`) supports, have no attendance record.

**Fix:** extend `ld_conference_attendance` into a generic `ld_training_attendance` (or add `session_type`/`event_id` columns to the existing table) so every training session — virtual or physical — has an attendance row for `get-attendance-data.php` and Time & Attendance.

**Status: ✅ columns implemented** — `session_type` (`video_conference`/`in_person`/`virtual`) and `event_id` (→ `ld_calendar_event`/`ld_user_event`) added to `ld_conference_attendance` in the migration SQL.

---

## Gap 9 — Analytics data is unpaginated / not aggregate-friendly

**Current state:** the `Analytics` class returns raw counts; the docs (`learning and development md.md` §"unpaginated lists") already flag that list endpoints return everything unbounded. Workforce Analytics will need **aggregates by month/department/position**, not raw rows.

**Fix (no new table):** add aggregate methods to the `Analytics` class (or a dedicated `WorkforceAnalyticsProvider`) that GROUP BY `DATE_FORMAT(enrolled_at, '%Y-%m')`, department, and position — the joins to `em_employees` make department/position grouping possible today.

**Status: ✅ implemented** — six aggregate methods added to `Analytics` (see table at top of this doc).

---

## Summary table

| # | Gap | Type of fix | Priority | Status |
|---|---|---|---|---|
| 1 | Turnover risk indicators not stored | New table `ld_engagement_snapshot` + cron | High | ✅ done |
| 2 | No per-employee training recommendation record | New table `ld_training_recommendation` | High | ⚠️ table only — needs `receive-appraisal-data.php` |
| 3 | Compliance courses not reliably tagged | ALTER `ld_course` (+2 columns) | Medium | ✅ columns done |
| 4 | No place for job-test / assessment results | New table `ld_assessment_result` | Medium | ⚠️ table only — needs `receive-job-test-result.php` |
| 5 | No per-employee recognition unlock record | New table `ld_recognition_unlock` | Medium | ⚠️ table only — needs `receive-recognition-eligibility.php` |
| 6 | Skill snapshots lack position/department context | ALTER `ld_skill_snapshot` (+2 columns) | High | ✅ done |
| 7 | `ld_skill_snapshot` never populated | Cron to materialize live gap query | High | ✅ done (`materialize-skill-snapshots.php`) |
| 8 | No attendance for in-person trainings | Extend `ld_conference_attendance` | Low | ✅ columns done |
| 9 | No aggregate queries for WFA | Code-only (Analytics class methods) | Medium | ✅ done (6 methods) |

**Quick wins first:** #6 + #7 together (populate the existing skill-snapshot table with role context — everything downstream (`wfa_skill_gap_analysis`, predictive skill needs) unlocks from it), then #1 (risk/engagement snapshot) since `wfa_risk_assessment` is already waiting for that input.