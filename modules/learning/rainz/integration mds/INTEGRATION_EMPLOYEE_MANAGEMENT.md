# L&D ↔ Employee Management — Integration & Gaps

> For the **Employee Management** team. This is the contract between Learning & Development and your module, extracted from the master docs (`CROSS_MODULE_INTEGRATION.md`, `CROSS_MODULE_GAPS.md`). Status verified against the live `hrms` database on 2026-09-05.

## Shared foundation (already built)

- **Identity:** L&D's `learner_id` === your `em_employees.employee_id` (the shared identity across the whole HRIS).
- **Auth:** `X-API-Key` header checked against `ld_api_key` — your key is seeded: `em_c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4`.
- **Logging:** all calls recorded in `ld_integration_log`.

## Data flows (from the architecture diagram)

| Flow | Direction | Endpoint | Your table | Status |
|---|---|---|---|---|
| Employee documents & credentials → L&D | EM → L&D | `api/inbound/receive-employee-profile.php` (GET) | `em_employees`, `employee_documents` | ✅ working |
| Employee role & job history → L&D | EM → L&D | same endpoint | `em_employees` (+ dept/position) | ✅ working |
| Training completion & certification records → EM | L&D → EM | `api/outbound/get-training-completion.php` | *(for Document Mgmt / records)* | ✅ working |

## Inbound (EM → L&D) — pull this

### `GET api/inbound/receive-employee-profile.php?employee_id=123`
Returns a complete profile for use in the learner UI:
- `employee` — name, email, department, position (joined from `em_departments` / `em_positions`)
- `enrollments` — the employee's courses + status
- `completed_skills` — skills earned via completed L&D courses
- `external_skills` — **new**: your `employee_skills` (proficiency-tagged) — used as the baseline for skill-gap analysis
- `external_certifications` — **new**: your `employee_certifications` (issuer, issued/expiry dates)
- `documents` — **new**: metadata from `employee_documents` (name, type, category, expiry)

A `POST` variant accepts profile snapshots (validates the employee exists, dedupes, logs) — used if you want to push rather than have us pull.

## Outbound (L&D → EM) — pull this

### `GET api/outbound/get-training-completion.php`
Same endpoint Performance Management uses — enrollment/completion/certificate records formatted for your records side (`completion_status`, `final_score`, `certificate_status`, dates). Optional filters: `learner_id`, `course_id`, `status`.

## Gaps / what we need from you

| # | Gap | Status |
|---|---|---|
| E1 | **`employee_skills` is empty (0 rows).** L&D now consumes it as the acquired-skill baseline for gap analysis — populate it with proficiency levels and it flows into our skill snapshots automatically. | ❌ your side |
| E4 | **No FK: `ld_enrollment.learner_id` → `em_employees.employee_id`.** Identity is by convention only. We plan to add the FK, but some learner IDs (e.g. `67999`) may not resolve in `em_employees` — please confirm those are valid employee records (or migrate them) before we enforce it. | ❌ needs your confirmation |

Everything else is live. The GET endpoint was verified against real data on 2026-09-05.