# L&D ↔ Workforce Analytics & Reporting — Integration & Gaps

> For the **Workforce Analytics & Reporting** team. This is the contract between Learning & Development and your module, extracted from the master docs (`CROSS_MODULE_INTEGRATION.md`, `CROSS_MODULE_GAPS.md`, `ld-tables-for-workforce-analytics.md`). Status verified against the live `hrms` database on 2026-09-05.

## Shared foundation (already built)

- **Identity:** L&D's `learner_id` === `em_employees.employee_id`.
- **Auth:** `X-API-Key` header checked against `ld_api_key` — your key is seeded: `wa_f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7`.
- **Logging:** all calls recorded in `ld_integration_log`.

## Data flows (all 7 from the architecture diagram → one endpoint)

| Diagram flow | Endpoint section | Status |
|---|---|---|
| Training participation & completion data | `enrollment_stats`, `completion_trend`, `top_courses` | ✅ served |
| Training engagement data / learning engagement trends | `completion_trend`, `enrollment_stats`, `risk_summary.active_days_30` | ✅ served |
| Certification status & expiry data | `total_certificates`, `expiring_certificates` (90-day window) | ✅ served |
| Turnover risk indicators | `risk_summary` — bands (low/medium/high) + `at_risk_learners` (score ≥ 40) from `ld_engagement_snapshot` | ✅ served |
| Predicted skill/competency needs | `skill_gap_analysis` (live) + `skill_snapshots` (per-learner with department/position) | ✅ served |
| Skill gap results (internal) | `skill_snapshots` from `ld_skill_snapshot` | ✅ served |
| Reporting requirements/targets | `knowledge_transfer` (KT path counts) + everything above → `wfa_reports` | ✅ served |

## Outbound (L&D → WFA) — pull this

### `GET api/outbound/get-workforce-analytics.php`
Optional filters: `department_id`, `date_from`, `date_to`. Returns:

```json
{
  "total_learners": 58,
  "enrollment_stats": {"invited": 3, "enrolled": 28, "in_progress": 9, "completed": 18},
  "total_certificates": 2,
  "top_courses": [ {"id": 17, "title": "...", "category": "Design", "enrollment_count": 4, "completions": 1} ],
  "skill_gap_analysis": [ {"skill_name": "...", "employees_needing_training": 3, "skill_gap_percentage": 100, "priority_level": "critical"} ],
  "skill_snapshots": [ {"learner_id": 67999, "department_name": "BTVTED", "position_name": "College Instructor", "total_skills": 32, "acquired_count": 20, "gap_count": 12, "acquired_skills": [...], "gap_skills": [...]} ],
  "completion_trend": [ {"month": "2026-08", "enrollments": 12, "completions": 11} ],
  "expiring_certificates": [ {"learner_id": 5, "course_title": "...", "valid_until": "2026-11-01"} ],
  "risk_summary": { "snapshot_date": "2026-09-05", "bands": [ {"band": "low", "employee_count": 10, "avg_risk_score": 7.5} ], "at_risk_learners": [] },
  "knowledge_transfer": { "total_paths": 1, "active_paths": 1 }
}
```

**Where each block should land on your side:** `enrollment_stats`/`completion_trend`/`top_courses` → `wfa_reports` & `wfa_employee_metrics`; `skill_gap_analysis` + `skill_snapshots` → `wfa_skill_gap_analysis`; `risk_summary` → `wfa_risk_assessment` / `wfa_at_risk_employees_summary`; `expiring_certificates` → `wfa_reports`.

## Supporting artifacts

- `ld-tables-for-workforce-analytics.md` — which `ld_` tables feed each flow, and their readiness.
- `ld-tables-gaps-improvements.md` — the snapshot tables (`ld_engagement_snapshot`, `ld_skill_snapshot`) are built and cron-populated daily; the `Analytics` class also exposes aggregate methods (`getEnrollmentsByDepartment()`, `getRiskSummary()`, `getCertificationExpiryOverview()`, …) if you prefer direct queries.

## Gaps / what we need from you

| # | Gap | Status |
|---|---|---|
| W3 | **`wfa_*` tables are unpopulated.** All 20 tables exist (`wfa_skill_gap_analysis`, `wfa_reports`, `wfa_risk_assessment`, …) but nothing reads the endpoint or writes them yet — your module is currently a stub. The analytics loop is one-way until you consume. | ❌ your side |
| — | **Consumption pattern.** Tell us if you prefer (a) pulling the endpoint on a schedule, (b) L&D writing directly into `wfa_*`, or (c) direct SQL views. We'll adjust accordingly. | ❌ decision needed |

L&D's side of this integration is fully implemented and tested — the data is ready whenever you start consuming.