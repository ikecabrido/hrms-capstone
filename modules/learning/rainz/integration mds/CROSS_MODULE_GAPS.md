# Cross-Module Gaps — What L&D Lacks Relative to the Other Modules

> **Companion to `CROSS_MODULE_INTEGRATION.md`.** Every gap below is grounded in the real tables/code in this workspace (checked against the live `hrms` database, 2026-09-05): something the other modules have or expect that L&D either doesn't have, doesn't read, or has wired incorrectly.
>
> **Implementation status (2026-09-05):** all P0 (broken endpoints) and all P1 (wiring built tables into endpoints) items are **implemented and tested** (✅). Most P2 code items are done (✅). Remaining ❌ items are admin-UI work or cross-team coordination.

---

## What was implemented

| Item | Change | Verified |
|---|---|---|
| P0 — 3 broken endpoints | Fixed column mismatches (`qa.attempted_at`, `c.enrollment_deadline`, `ca.video_conference_id`; dropped nonexistent `left_at`; `LEFT JOIN` course) | ✅ all 5 outbound endpoints return `success:true` |
| P2 — recommendation trail | `receive-appraisal-data.php` writes `ld_training_recommendation` (`source='appraisal'`, ext-ref `pm-rec-{id}`) | ✅ skip-path tested over HTTP |
| G1 — recognition unlock | `receive-recognition-eligibility.php` writes `ld_recognition_unlock` (`redeemed` on invite, else `unlocked`) | ✅ skip-path tested over HTTP |
| R2 — assessment record | `receive-job-test-result.php` writes `ld_assessment_result` | ✅ end-to-end tested (row created + cleaned) |
| W1 — risk + skill snapshots served | `get-workforce-analytics.php` returns `risk_summary` (bands + at-risk from `ld_engagement_snapshot`) and `skill_snapshots` (latest `ld_skill_snapshot`, with department/position) | ✅ payload verified (10 snapshots, risk bands) |
| W2 — cert expiry served | `expiring_certificates` (next 90 days) added to payload | ✅ payload verified |
| X2 — KT paths counted | `knowledge_transfer.total_paths/active_paths` added to payload | ✅ payload verified (1 path) |
| E1 — external skills consumed | Skill-snapshot cron merges `employee_skills` (matched to `ld_skill` by name) into acquired/total; profile endpoint returns `external_skills` | ✅ cron idempotent (10 rows, no dupes) |
| E2/E3 — certs + documents surfaced | Profile endpoint returns `external_certifications` + `documents` | ✅ GET verified |
| X1 — exit risk factor | Engagement cron adds `exit_case_filed` (+20) when resignations/terminations/interviews exist | ✅ cron runs |
| C1/C3 — compliance filter + expiry | Endpoint filters `is_compliance_required = 1` (category fallback); expiry from `ld_certificate.valid_until` (fallback +1 yr) | ✅ endpoint works |

---

## 1. Critical — 3 outbound endpoints are broken (schema mismatches)

These return `SQLSTATE 42S22 Column not found` today, so their flows are dead until fixed.

| Endpoint | Bug (real column → correct column) | Destination |
|---|---|---|
| `get-learning-performance.php` | `qa.submitted_at` → **`qa.attempted_at`** (`ld_quiz_attempt` has `attempted_at`) | PM scores/skill gaps |
| `get-compliance-training-log.php` | `e.enrollment_deadline` → **`c.enrollment_deadline`** (the column is on `ld_course`, not `ld_enrollment`) | `lc_trainings` |
| `get-attendance-data.php` | `ca.conference_id` → **`ca.video_conference_id`**; `ca.left_at` → **doesn't exist** (drop it; table only has `joined_at`) | `ta_attendance` |

Also in `get-attendance-data.php`: the join `JOIN ld_course c ON c.id = vc.course_id` drops program-only conferences (`vc.course_id` is nullable) — use `LEFT JOIN`.

**Fix priority: #1.** These three are the diagram's Compliance, Time & Attendance, and half the Performance flows.

---

## 2. Performance Management gaps

| # | Gap | Evidence | Status |
|---|---|---|---|
| P1 | **360-degree feedback is never consumed** | `pm_feedback_360_entries` exists (with `areas_for_improvement`, `recommendation`, `competency_scores`, `category`) — diagram flow "360-degree feedback results → Training Program Management" has **no endpoint or mapping** | ❌ **open** — needs a new inbound endpoint (`receive-360-feedback-data.php`) mapping 360 output → development area → course (reuse `ld_recommendation_course_map` / `ld_training_recommendation`) |
| P2 | **`ld_training_recommendation` has no writer** | Table built (gap-1 task), **0 rows**; `receive-appraisal-data.php` auto-invites but never records the recommendation itself | ✅ **done** — endpoint writes the trail row (`source='appraisal'`, ext-ref `pm-rec-{id}`) |
| P3 | **`pm_employee_training` write-back unresolved** | Table exists with `completion_status`, `final_score`, `certificate_status` **all empty (0 rows)**; `get-training-completion.php` only *returns* data | ❌ coordination — decide who writes (`UPDATE pm_employee_training` vs PM consumer) |
| P4 | **`pm_training_recommendations` is empty (0 rows)** | Their side has nothing sending yet | ❌ coordination — PM team populates the table; L&D side is ready |
| P5 | **Duplicate program concept** | PM has `pm_training_programs`; L&D has `ld_program` | ❌ coordination — pick system of record or external-ref mapping |

## 3. Compliance & Legal gaps

| # | Gap | Evidence | What's missing |
|---|---|---|---|
| C1 | **Endpoint ignores the new compliance columns** | `get-compliance-training-log.php` still filters `c.category IN ('Compliance','compliance','Safety')` — fragile string matching; the `is_compliance_required` / `compliance_category` columns added to `ld_course` are unused | ✅ **done** — filter is now `is_compliance_required = 1` with category fallback |
| C2 | **No admin UI to tag compliance courses** | Columns exist; no page/AJAX sets them | ❌ **open** — add a toggle/field on the instructor/admin course form |
| C3 | **Expiry rule hardcoded** | `DATE_ADD(e.completed_at, INTERVAL 1 YEAR)` inline — assumes all compliance training valid 1 year | ✅ **done** — expiry now uses `ld_certificate.valid_until`, falls back to +1 yr |

## 4. Time & Attendance gaps

| # | Gap | Evidence | What's missing |
|---|---|---|---|
| T1 | **No attendance write-side** | `ld_conference_attendance` has **0 rows**; the endpoint's own note admits "no write-side code" | ❌ **open** — UI/AJAX to mark learners present for a video conference (instructor side), recording `session_type`/`event_id` |
| T2 | **In-person attendance columns unused** | `session_type` (`video_conference`/`in_person`/`virtual`) + `event_id` added in the gap-1 migration, never written | ❌ **open** — attendance recording for `ld_calendar_event` / `ld_user_event` sessions |
| T3 | **`ta_attendance` on their side empty (0 rows)** | Their module has the table but nothing to receive | ✅ **done** — endpoint fixed and returns `ta_attendance`-shaped rows (empty until attendance is recorded) |

## 5. Recruitment & Onboarding gaps

| # | Gap | Evidence | What's missing |
|---|---|---|---|
| R1 | **No job-test source table on their side** | `lc_recruitment` columns: `position, department, employment_type, salary_grade, status` — **no test/assessment fields** | ❌ coordination — Recruitment-side table needed; L&D endpoint is ready |
| R2 | **`ld_assessment_result` has no writer** | Table built, **0 rows**; the inbound endpoint auto-enrolls a learning path but never stores the test outcome | ✅ **done** — endpoint writes the assessment row; ✅ skill-snapshot cron can now merge external skills for new hires |

## 6. Employee Management gaps

| # | Gap | Evidence | What's missing |
|---|---|---|---|
| E1 | **`employee_skills` (0 rows) never read** | EM has a proficiency-tagged skill table; L&D's `receive-employee-profile.php` only reads `em_employees` | ✅ **done** — skill-snapshot cron merges `employee_skills` into acquired/total; profile GET returns `external_skills`. (Table is still 0 rows on their side — merge kicks in once populated) |
| E2 | **`employee_certifications` (11 rows) not surfaced** | External certs with `date_issued`, `expiry_date` exist on their side; L&D only knows its own `ld_certificate` | ✅ **done** — profile GET returns `external_certifications` |
| E3 | **`employee_documents` not surfaced** | Credentials/docs exist per employee; diagram says "Employee documents & credentials → L&D" | ✅ **done** — profile GET returns `documents` metadata |
| E4 | **No FK: `ld_enrollment.learner_id` → `em_employees.employee_id`** | Identity is by convention only; data shows learner ids that don't resolve (`67999` has position/department NULL in older snapshots) | ❌ **open** — add FK or validation layer; WFA joins silently drop unknown learners |

## 7. Workforce Analytics gaps

| # | Gap | Evidence | What's missing |
|---|---|---|---|
| W1 | **Endpoint doesn't serve the new snapshot tables** | `ld_engagement_snapshot` (10 rows) + `ld_skill_snapshot` (12 rows) are cron-populated but `get-workforce-analytics.php` computes live and never reads them | ✅ **done** — payload includes `risk_summary` (bands + at-risk ≥ 40) and `skill_snapshots` (latest date, with department/position) |
| W2 | **No certification expiry feed** | Endpoint returns only `total_certificates`; `Analytics::getCertificationExpiryOverview()` exists but nothing serves it | ✅ **done** — `expiring_certificates` (90-day window) in payload (→ `wfa_reports`) |
| W3 | **`wfa_*` tables still unpopulated** | All 20 `wfa_*` tables exist; workforce module is a stub; nothing writes them | ❌ coordination — workforce team consumption or an L&D-side writer for `wfa_skill_gap_analysis` / `wfa_reports` |

## 8. Employee Engagement gaps

| # | Gap | Evidence | What's missing |
|---|---|---|---|
| G1 | **`ld_recognition_unlock` has no writer** | Table built, **0 rows**; `receive-recognition-eligibility.php` auto-invites but never records the unlock | ✅ **done** — endpoint writes the unlock row (ext-ref `eer-rec-{id}`; `redeemed` on invite, else `unlocked`) |
| G2 | **`eer_recognitions` empty (0 rows)** | Their side has nothing sending yet | ❌ coordination — Engagement team populates; `ld_recognition_course_map` (9 rows) + endpoint ready |
| G3 | **Category mapping is manual DB rows** | `ld_recognition_course_map`/`ld_recommendation_course_map` populated by hand | ❌ **open** — admin UI (CourseMap CRUD exists in class, no page/AJAX uses it) |

## 9. Exit / Turnover gaps (workspace module, not in diagram)

| # | Gap | Evidence | What's missing |
|---|---|---|---|
| X1 | **Turnover risk ignores exit signals** | `exit_interviews`/`exit_surveys`/`exit_terminations` exist (0 rows today); KT integration is live (1 path) | ✅ **done** — engagement cron adds `exit_case_filed` (+20) when resignations/terminations/interviews exist |
| X2 | **KT paths not counted in WFA** | `ld_learning_path` with `kt_plan_id` exists | ✅ **done** — `knowledge_transfer` totals in `get-workforce-analytics.php` payload |

---

## 10. Remaining work

All P0/P1 and most P2 are **implemented** (see "What was implemented" at the top). What's left:

| Priority | Items | Type |
|---|---|---|
| **Next — UI work (self-contained)** | C2 (compliance toggle on course form), T1/T2 (attendance recording UI for conferences + in-person), G3 (CourseMap admin UI for recommendation/recognition mappings) | L&D side only |
| **Next — new endpoint** | P1 (360-degree feedback: `receive-360-feedback-data.php` mapping `pm_feedback_360_entries` → development area → course) | L&D side + PM coordination on payload |
| **Data integrity** | E4 (FK `ld_enrollment.learner_id` → `em_employees.employee_id`) | L&D side, needs data cleanup first (`67999` etc.) |
| **Coordination with other teams** | P3 (pm_employee_training write-back), P4 (populate pm_training_recommendations), R1 (job-test source table), G2 (eer_recognitions), W3 (wfa_* population), P5 (program duplication) | cross-module |

**Immediate next step:** the UI items (C2, T1/T2, G3) — the data layer for all of them is already in place; only the admin/instructor pages are missing.