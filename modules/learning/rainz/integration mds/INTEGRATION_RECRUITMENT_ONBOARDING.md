# L&D ↔ Recruitment & Onboarding — Integration & Gaps

> For the **Recruitment & Onboarding** team. This is the contract between Learning & Development and your module, extracted from the master docs (`CROSS_MODULE_INTEGRATION.md`, `CROSS_MODULE_GAPS.md`). Status verified against the live `hrms` database on 2026-09-05.

## Shared foundation (already built)

- **Identity:** L&D's `learner_id` === `em_employees.employee_id`. During onboarding, the new hire's employee ID is used before they have learning activity.
- **Auth:** `X-API-Key` header checked against `ld_api_key` — your key is seeded: `rc_b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3`.
- **Logging:** all calls recorded in `ld_integration_log`; inbound calls deduped via `ld_integration_event` (idempotent retries).

## Data flow (from the architecture diagram)

| Flow | Direction | Endpoint | Your table | Status |
|---|---|---|---|---|
| Assessment/course results → Skill Gaps Analysis | Recruitment → L&D | `api/inbound/receive-job-test-result.php` | *(no test table found — see R1)* | ✅ endpoint ready |

## Inbound (Recruitment → L&D) — push this

### `POST api/inbound/receive-job-test-result.php`
```bash
curl -X POST https://<host>/modules/learning/api/inbound/receive-job-test-result.php \
  -H "X-API-Key: rc_..." -H "Content-Type: application/json" \
  -d '{"employee_id": 123, "candidate_id": 45, "test_name": "Onboarding Assessment",
       "score": 88.5, "passed": true, "recommended_learning_path_id": 3}'
```
Behavior:
- Records the assessment outcome in `ld_assessment_result` (score, pass/fail, date) — this seeds the new hire's starting skill state for gap analysis.
- If `passed = true` and a `recommended_learning_path_id` is provided, L&D **auto-invites the employee to every course in that learning path** (onboarding curriculum).
- Idempotent per (employee, test name) — safe to retry.

## Gaps / what we need from you

| # | Gap | Status |
|---|---|---|
| R1 | **No job-test source table on your side.** `lc_recruitment` only has `position, department, employment_type, salary_grade, status` — no assessment/test fields. The endpoint is built and tested; we need you to either add a test/assessment table (e.g. per-candidate test results with score + passed + recommended path) or tell us where job-test results will live. | ❌ needs your table |
| R2 | **Seeding new-hire skills.** Once tests flow in, their `passed` assessments can seed `ld_skill_snapshot` so onboarding skill gaps are measurable from day one. | ✅ mechanism ready, waiting on R1 |

**What we need from you to go live:** a way to identify a job test result (candidate or employee ID + test name + score + pass/fail) and, optionally, a recommended onboarding learning path ID. Everything on our side is built and end-to-end tested.