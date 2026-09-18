# L&D ↔ Performance Management — Integration & Gaps

> For the **Performance Management** team. This is the contract between Learning & Development and your module, extracted from the master docs (`CROSS_MODULE_INTEGRATION.md`, `CROSS_MODULE_GAPS.md`). Status verified against the live `hrms` database on 2026-09-05.

## Shared foundation (already built)

- **Identity:** L&D's `learner_id` === your `em_employees.employee_id`.
- **Auth:** every endpoint checks an `X-API-Key` header against `ld_api_key` — your module key is already seeded: `pm_a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2`.
- **Logging:** every call is recorded in `ld_integration_log`; inbound calls are deduped via `ld_integration_event` (idempotent retries).

## Data flows (from the architecture diagram)

| Flow | Direction | Endpoint | Your table | Status |
|---|---|---|---|---|
| E-learning completion & scores → Training Program Mgmt | L&D → PM | `api/outbound/get-training-completion.php` | `pm_employee_training` | ✅ working |
| Identified skill gaps, scores → Training Program Mgmt | L&D → PM | `api/outbound/get-learning-performance.php` | your own tables | ✅ working (fixed) |
| Recommended training plan → Training Program Mgmt | PM → L&D | `api/inbound/receive-appraisal-data.php` | `pm_training_recommendations` | ✅ working |
| Appraisal results & turnover risk → Training Program Mgmt | PM → L&D | same as above | `pm_training_recommendations` | ✅ working |
| 360-degree feedback results → Training Program Mgmt | PM → L&D | **no endpoint yet** | `pm_feedback_360_entries` | ❌ open |

## Outbound (L&D → PM) — pull these

### `GET api/outbound/get-training-completion.php`
Returns enrollment/completion/certificate data formatted for `pm_employee_training`:
```json
{ "employee_id": 67999, "training_id": 9, "completion_status": "Pending",
  "completion_percentage": 0, "final_score": 0, "certificate_status": "Not Issued",
  "assigned_date": "...", "end_date": null }
```
Optional filters: `learner_id`, `course_id`, `status`.

### `GET api/outbound/get-learning-performance.php`
Returns quiz performance (per-employee aggregates) + skill proficiency (per learner per skill, % = completed courses with that skill / total courses with that skill).
Optional filters: `learner_id`, `course_id`.

## Inbound (PM → L&D) — push these

### `POST api/inbound/receive-appraisal-data.php`
```bash
curl -X POST https://<host>/modules/learning/api/inbound/receive-appraisal-data.php \
  -H "X-API-Key: pm_..." -H "Content-Type: application/json" \
  -d '{"recommendation_id": 1, "employee_id": 5, "development_area": "Leadership",
       "priority_level": "High", "status": "Approved", "recommendation_reason": "..."}'
```
Behavior: `status = "Approved"` → maps `development_area` → course via `ld_recommendation_course_map`, auto-invites the employee to those courses, and records the recommendation trail in `ld_training_recommendation` (idempotent per `recommendation_id`). Any other status is logged and skipped.

## Gaps / what we need from you

| # | Gap | Status |
|---|---|---|
| P1 | **360-degree feedback is never consumed.** Diagram says your 360 results feed Training Program Management, but there is no endpoint or mapping. We need to agree on a payload — ideally: employee_id, category/competency scores, `areas_for_improvement`, `recommendation` — so we can build `receive-360-feedback-data.php` that maps it to development areas → courses. | ❌ needs your input |
| P3 | **`pm_employee_training` write-back.** The table exists with `completion_status`, `final_score`, `certificate_status` all empty. Decide: should L&D `UPDATE pm_employee_training` directly, or does your code consume `get-training-completion.php` and write it? | ❌ decision needed |
| P4 | **`pm_training_recommendations` is empty (0 rows).** Once your module starts writing recommendations there, the inbound flow works end-to-end. | ❌ your side |
| P5 | **Duplicate program concept.** You have `pm_training_programs`; L&D has `ld_program`. Agree on the system of record or an external-reference mapping. | ❌ coordination |

Everything else is working on both sides. Questions or payload changes → open an issue against L&D's `api/` endpoints (they are versioned for exactly this).