# Cross-Module API — Implementation Plan (Brief)

Based on the real `hrms_full_database_v_5.sql`, confirming actual table/column names on the "other side" of each integration point. Key finding: **the shared identity column across every module is `employee_id`** (not `learner_id`) — `ld_enrollment.learner_id` and friends map directly to `em_employees.employee_id`.

---

## 1. Shared foundation (build once, used by every endpoint)

**`classes/apiauth.php`** — new class, shared by all inbound endpoints
- `verify($apiKey, $moduleName)` — checks `ld_api_key` (already exists in DB), rejects if missing/inactive
- Called at the top of every `api/inbound/*.php` before touching any data

**`classes/integrationlog.php`** — new class, shared by both inbound and outbound
- `logCall($direction, $moduleName, $endpoint, $status, $payload, $errorMessage = null)` — writes to `ld_integration_log`
- `isDuplicate($moduleName, $externalReferenceId)` — checks `ld_integration_event` before processing an inbound call (idempotency)
- `markProcessed($moduleName, $externalReferenceId, $eventType)` — inserts into `ld_integration_event` after successful processing

**Build order:** these two classes first — every one of the 9 endpoints below depends on them.

---

## 2. Outbound endpoints (LD → other modules)

| Endpoint | Destination table (real, confirmed) | Query logic |
|---|---|---|
| `get-training-completion.php` | `pm_employee_training` (writes back `completion_status`, `final_score`, `certificate_status`) | Reads `ld_enrollment` + `ld_certificate`, formats to match `pm_employee_training`'s expected shape |
| `get-learning-performance.php` | Performance Management's own tables read this | Reads `ld_grade`, `ld_quiz_attempt`, skill-gap query (via `ld_module_skill`/`ld_progress`) |
| `get-compliance-training-log.php` | `lc_trainings` | Reads `ld_enrollment` filtered to compliance-tagged courses (via `ld_course_skill`/category), formats to `lc_trainings` shape (`training_name`, `date_completed`, `status`) |
| `get-attendance-data.php` | `ta_attendance` reads this | Reads `ld_conference_attendance` |
| `get-workforce-analytics.php` | `wfa_skill_gap_analysis`, `wfa_reports` read this | Reads `ld_enrollment`, `ld_certificate`, `ld_progress` aggregates |

**Pattern for every outbound file:**
```php
1. ApiAuth::verify()
2. Query the relevant ld_* table(s)
3. Format response to match the destination table's actual column names
4. IntegrationLog::logCall('outbound', ..., 'success', $payload)
5. Return JSON
```

---

## 3. Inbound endpoints (other modules → LD)

| Endpoint | Source table (real, confirmed) | What it does in LD |
|---|---|---|
| `receive-appraisal-data.php` | `pm_training_recommendations` (has `employee_id`, `development_area`, `priority_level`, `recommendation_reason`) | Maps `employee_id` → potential `ld_enrollment.learner_id`; if `status = 'Approved'`, calls `Enrollment::invite()` to auto-assign the recommended course |
| `receive-job-test-result.php` | *(no dedicated recruitment table found in this DB — likely lives in a module not yet in this export, e.g. `lc_recruitment`)* | **Flag as open** — confirm actual source table before building; logic otherwise: triggers Learning Path assignment for onboarding |
| `receive-recognition-eligibility.php` | `eer_recognitions` (`receiver_id`, `status`, `category`) | If `status = 'approved'`, unlocks a specific `ld_course`/`ld_program` for that `receiver_id` |
| `receive-employee-profile.php` | `em_employees`, `employee_skills` | Read at time of use only (per the sync-as-snapshot rule) — feeds `ld_prerequisite` checks and learner profile display; no local copy stored |

**Pattern for every inbound file:**
```php
1. ApiAuth::verify()
2. IntegrationLog::isDuplicate() check — reject if already processed
3. Validate payload (required fields, types)
4. Apply the business logic (e.g. Enrollment::invite())
5. IntegrationLog::markProcessed()
6. IntegrationLog::logCall('inbound', ..., 'success', $payload)
7. Return JSON confirmation
```

---

## 4. Build order (suggested)

1. `ApiAuth.php` + `IntegrationLog.php` (foundation — nothing else works without these)
2. `receive-appraisal-data.php` — highest value, ties directly into `Enrollment::invite()` which is already built
3. `receive-recognition-eligibility.php` — second highest value, simple unlock logic
4. `get-training-completion.php` — outbound, feeds back completion status to Performance Management's own training table
5. `get-compliance-training-log.php` — outbound, straightforward format-and-return
6. `get-attendance-data.php`, `get-workforce-analytics.php`, `get-learning-performance.php` — remaining outbound, similar pattern to #4/#5
7. `receive-employee-profile.php` — read-only, low risk, can slot in anytime
8. `receive-job-test-result.php` — **held** until the actual source table is confirmed (see flag above)

---

## 5. Open items before coding starts

- **Confirm the real source table for Job Test results** — not found in this DB export under an obvious name (checked `lc_recruitment`, no job-test-specific columns visible; may need a follow-up look at that table's actual structure, or confirmation the feature doesn't exist yet on the Recruitment side).
- **Confirm which specific courses/programs `receive-recognition-eligibility.php` should unlock** — the `eer_recognitions.category` field could map to specific course categories, but that mapping isn't defined yet.
- **`get-training-completion.php` — one-way or round-trip?** `pm_employee_training` looks like it's meant to be *written to* by LD (it has `completion_status`, `final_score`, `certificate_status` columns sitting empty until LD provides them) rather than just read from — worth confirming whether this endpoint should `UPDATE pm_employee_training` directly, or just return data for Performance Management's own code to write.
