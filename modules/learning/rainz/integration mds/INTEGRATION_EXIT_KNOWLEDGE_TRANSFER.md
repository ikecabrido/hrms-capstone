# L&D ↔ Exit Management / Knowledge Transfer — Integration & Gaps

> For the **Exit Management** team. This is the contract between Learning & Development and your module, extracted from the master docs (`CROSS_MODULE_INTEGRATION.md`, `CROSS_MODULE_GAPS.md`). The knowledge-transfer feature is fully documented in `KNOWLEDGE_TRANSFER_INTEGRATION.md`. Status verified against the live `hrms` database on 2026-09-05.

## Shared foundation (already built)

- **Identity:** L&D's `learner_id` === `em_employees.employee_id`.
- **Knowledge Transfer is live:** 1 KT learning path already exists (`ld_learning_path` with `kt_plan_id` set, type `knowledge_transfer`).

## Data flows

| Flow | Direction | Mechanism | Status |
|---|---|---|---|
| Resignee's completed/in-progress courses → KT plan checklist | Exit → L&D | Exit module reads `ld_enrollment` for the resignee | ✅ live (see `KNOWLEDGE_TRANSFER_INTEGRATION.md`) |
| KT Learning Path creation + successor auto-enrollment | Exit → L&D | `generate-kt-learning-path.php` creates `ld_learning_path` (type `knowledge_transfer`, `kt_plan_id`) + `ld_learning_path_item` + enrolls successor | ✅ live |
| KT path visibility (public/private) | Exit ↔ L&D | `toggle-kt-path-public.php` + L&D training page toggle | ✅ live |
| Turnover risk signals → engagement/risk analytics | Exit → L&D (indirect) | `materialize-engagement-snapshots.php` cron checks your `exit_resignations`, `exit_terminations`, `exit_interviews` and adds an `exit_case_filed` risk factor (+20) | ✅ built |
| KT path volume → Workforce Analytics | L&D → WFA | `get-workforce-analytics.php` returns `knowledge_transfer.total_paths` / `active_paths` | ✅ built |

## Your tables L&D reads

| Table | Used for |
|---|---|
| `exit_knowledge_transfer_plans` | Linked back from `ld_learning_path.kt_plan_id` |
| `exit_resignations` / `exit_terminations` | `exit_case_filed` risk factor in daily engagement snapshots (statuses other than rejected/cancelled/archived count) |
| `exit_interviews` | Same risk factor |

## Gaps / what we need from you

| # | Gap | Status |
|---|---|---|
| X1 | **Exit tables are empty (0 rows).** The risk-factor wiring is built and runs daily, but with no resignations/terminations/interviews recorded, no employee gets flagged. Once real exit cases flow, the risk snapshots react automatically. | ❌ your side (data) |
| X3 | **Exit-survey results not consumed.** `exit_surveys` / `exit_survey_*` tables hold post-exit feedback that could enrich turnover analysis (e.g. reasons for leaving). Decide if L&D should read them (or if Workforce Analytics will) — currently untouched. | ❌ decision needed |

The core integration (KT plans → learning paths → successor onboarding) is **live and working**; the risk-signal bridge is built and waiting on data.