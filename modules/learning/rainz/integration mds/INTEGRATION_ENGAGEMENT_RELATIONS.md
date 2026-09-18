# L&D ↔ Employee Engagement & Relations — Integration & Gaps

> For the **Employee Engagement & Relations** team. This is the contract between Learning & Development and your module, extracted from the master docs (`CROSS_MODULE_INTEGRATION.md`, `CROSS_MODULE_GAPS.md`). Status verified against the live `hrms` database on 2026-09-05.

## Shared foundation (already built)

- **Identity:** L&D's `learner_id` === `em_employees.employee_id`.
- **Auth:** `X-API-Key` header checked against `ld_api_key` — your key is seeded: `ee_d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5`.
- **Logging:** all calls recorded in `ld_integration_log`; inbound calls deduped via `ld_integration_event` (idempotent retries).

## Data flow (from the architecture diagram)

| Flow | Direction | Endpoint | Your table | Status |
|---|---|---|---|---|
| Recognition-linked training eligibility → Recognition & Rewards | Engagement → L&D | `api/inbound/receive-recognition-eligibility.php` | `eer_recognitions` | ✅ endpoint ready |

## Inbound (Engagement → L&D) — push this

### `POST api/inbound/receive-recognition-eligibility.php`
```bash
curl -X POST https://<host>/modules/learning/api/inbound/receive-recognition-eligibility.php \
  -H "X-API-Key: ee_..." -H "Content-Type: application/json" \
  -d '{"recognition_id": 10, "receiver_id": 5, "status": "approved", "category": "Leadership", "points": 100}'
```
Behavior:
- `status = "approved"` → maps `category` → courses via `ld_recognition_course_map` (9 category→course mappings already configured), **auto-enrolls the recipient** in those courses, and records the unlock in `ld_recognition_unlock` (marked `redeemed` when the enrollment succeeds).
- Any other status is logged and skipped.
- Idempotent per `recognition_id` — safe to retry.

## Gaps / what we need from you

| # | Gap | Status |
|---|---|---|
| G2 | **`eer_recognitions` is empty (0 rows).** Once your module starts writing approved recognitions (with `receiver_id`, `status`, `category`), the reward-unlock flow works end-to-end. Our 9 category→course mappings are ready. | ❌ your side |
| G3 | **Category ↔ course mapping is manual.** The mappings live in `ld_recommendation_course_map` / `ld_recognition_course_map` and are currently maintained via direct SQL. We plan an admin UI — but coordinate now if you need specific categories added (e.g. tell us the `category` values you'll actually send). | ❌ our side (planned), needs your category list |

**What we need from you to go live:** the set of `category` values your recognitions use (so we keep the course mappings aligned), and confirmation that approved recognitions will call the endpoint above.