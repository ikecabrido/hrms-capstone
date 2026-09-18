# L&D ↔ Compliance & Legal Management — Integration & Gaps

> For the **Compliance & Legal Management** team. This is the contract between Learning & Development and your module, extracted from the master docs (`CROSS_MODULE_INTEGRATION.md`, `CROSS_MODULE_GAPS.md`). Status verified against the live `hrms` database on 2026-09-05.

## Shared foundation (already built)

- **Identity:** L&D's `learner_id` === `em_employees.employee_id`.
- **Auth:** `X-API-Key` header checked against `ld_api_key` — your key is seeded: `cl_e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6`.
- **Logging:** all calls recorded in `ld_integration_log`.

## Data flow (from the architecture diagram)

| Flow | Direction | Endpoint | Your table | Status |
|---|---|---|---|---|
| Compliance training completion logs → Certification Mgmt | L&D → Compliance | `api/outbound/get-compliance-training-log.php` | `lc_trainings` | ✅ working (fixed) |

## Outbound (L&D → Compliance) — pull this

### `GET api/outbound/get-compliance-training-log.php`
Returns compliance-tagged training completions formatted for `lc_trainings`:
```json
{ "employee_id": 5, "training_name": "...", "training_type": "...",
  "date_completed": "2026-08-01", "expiry_date": "2027-08-01", "status": "Completed" }
```
Optional filters: `employee_id`, `status`.

**How compliance is detected (now):** a course counts as compliance when `ld_course.is_compliance_required = 1` (with a legacy fallback to category strings `'Compliance'`/`'Safety'`). Expiry comes from the employee's issued certificate `valid_until`, falling back to completion + 1 year.

## Gaps / what we need from you

| # | Gap | Status |
|---|---|---|
| C2 | **No admin UI to tag compliance courses.** L&D admins can't yet mark a course as compliance-required from the UI (the flag exists in the database; only direct SQL sets it). We will build the toggle on our course form — meanwhile, coordinate if you need specific courses flagged now. | ❌ our side (planned) |
| C3 | **Expiry rules per compliance type.** Expiry currently defaults to certificate `valid_until` or +1 year. If different compliance categories (e.g. safety vs. statutory) need different validity periods, tell us the rules and we'll wire them to `compliance_category`. | ❌ needs your rules |
| — | **`lc_trainings` already has 8 rows.** Once you pull from our endpoint, decide how to merge/refresh them (key by `employee_id` + `training_name` + `date_completed`?). | ❌ your side |

The endpoint is live and returns `success:true` today (0 records because no compliance-tagged courses have completed enrollments yet — tag some and they'll appear).