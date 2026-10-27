# L&D ↔ Time & Attendance Management — Integration & Gaps

> For the **Time & Attendance Management** team. This is the contract between Learning & Development and your module, extracted from the master docs (`CROSS_MODULE_INTEGRATION.md`, `CROSS_MODULE_GAPS.md`). Status verified against the live `hrms` database on 2026-09-05.

## Shared foundation (already built)

- **Identity:** L&D's `learner_id` === `em_employees.employee_id`.
- **Auth:** `X-API-Key` header checked against `ld_api_key` — your key is seeded: `ta_a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8`.
- **Logging:** all calls recorded in `ld_integration_log`.

## Data flow (from the architecture diagram)

| Flow | Direction | Endpoint | Your table | Status |
|---|---|---|---|---|
| Training session attendance data → Course Enrollment Tracking | L&D → T&A | `api/outbound/get-attendance-data.php` | `ta_attendance` | ✅ working (fixed) |

## Outbound (L&D → T&A) — pull this

### `GET api/outbound/get-attendance-data.php`
Returns training attendance records formatted for `ta_attendance`:
```json
{ "employee_id": 5, "attendance_date": "2026-09-01", "time_in": "09:00:00",
  "time_out": null, "status": "PRESENT", "recorded_by": "SYSTEM",
  "source": "learning-development", "session_type": "video_conference",
  "conference_title": "...", "course_title": "..." }
```
Optional filters: `employee_id`, `conference_id`, `date_from`, `date_to`.

Covers **video conferences** today. We added `session_type` (`video_conference` / `in_person` / `virtual`) and `event_id` columns so in-person / virtual classroom sessions can be reported the same way once they're recorded.

## Gaps / what we need from you

| # | Gap | Status |
|---|---|---|
| T1 | **No attendance write-side yet.** `ld_conference_attendance` has 0 rows because no instructor UI marks learners present for a conference. We plan to build it — until then the endpoint returns empty data with a clear note. | ❌ our side (planned) |
| T2 | **In-person training attendance.** The columns exist (`session_type`, `event_id`) but nothing records attendance for classroom/calendar sessions yet. Once built, those rows will flow through the same endpoint. | ❌ our side (planned) |
| T3 | **`ta_attendance` merge.** When you start receiving rows, decide the dedup key (e.g. `employee_id` + `attendance_date` + `source`) so pulls don't duplicate your records. | ❌ your side |

The endpoint is live and returns `success:true` today (empty dataset until attendance recording is built).