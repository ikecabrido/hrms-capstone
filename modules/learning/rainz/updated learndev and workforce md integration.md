# Updated LearnDev ↔ Workforce MD Integration

**Audit of the Learning & Development (LearnDev) ↔ Workforce data exchange: what moves, in which direction, where it lands, and how it is shown on each side.**

| | |
|---|---|
| Audited | 2026-09-26 |
| Repo | `hrms-capstone`, branch `learndev` |
| Runtime | Apache + MySQL (`hrms`, root/no password), app at `http://localhost/hrms-capstone` |
| Scope | `modules/learning` (LearnDev) and `modules/workforce` (Workforce) |
| Audience | §**START HERE** is the work order for the Workforce team; §1–§13 are the audit it rests on |
| Method | Read every live endpoint/class/page + live SQL and HTTP probes (numbers below are the live DB at audit time) |
| Supplied artifacts | `workforce-20260925T051118Z-1-001.zip` (the Workforce team's own build, exported 2026-09-25, files dated 2026-08-25 → 2026-09-24, 46 files) and `hrmss workforce.sql` (the shared schema, 2026-09-21, MariaDB 10.4.32) — both read for the START HERE section |
| Supersedes | `integration mds/INTEGRATION_WORKFORCE_ANALYTICS.md` (2026-09-05) — two of its claims are now disproved, see §3.3 and §10 (G3) |

---

## 0. TL;DR

| Layer | State today | Evidence |
|---|---|---|
| LearnDev → others (outbound API) | 5 endpoints implemented, authenticated, logged, and answering real JSON | `api/outbound/*.php`; live `curl` 200s |
| Others → LearnDev (inbound API) | 4 endpoints implemented (appraisal, recognition, job test, employee profile) | `api/inbound/*.php` |
| L&D → destination tables | **No feed has ever landed.** `pm_employee_training` = 0 rows, `ta_attendance` = 0 rows; the 8 rows in `lc_trainings` are the supplied dump's seed block, not this endpoint's output (re-verified 2026-09-26) | §4, ▶ Completion audit |
| L&D → `wfa_*` (Workforce analytics tables) | **Never written by any code**; all 20 `wfa_*` tables = 0 rows | §4.5, §10 G4 |
| Workforce module UI | In **this checkout**: one page, empty box, 6 dead nav links, `Employee.php` pointing at tables that do not exist. In the **Workforce team's own build** (the supplied zip): 11 pages, real `em_*` queries, no `hrms_*` references left — but no notification path and a stubbed bell | §1, START HERE |
| Workforce → L&D data | **Has moved, but at DB level** — `ld_competency_requirement` (4 rows), `ld_reporting_requirement` (3), `ld_training_engagement` (1), `ld_turnover_risk` (3), `ld_cron_run` (2), and 5 workforce-sourced `ld_training_requests` | §6 |
| The code for that Workforce → L&D path | **Not in this repo.** Only `.freebuff/rollback-20260925/untracked/**` holds it; `ld_integration_log` still has 8 rows from its endpoints | §6.3 |
| How the exchange is *shown* | LearnDev: plenty (analytics dashboards, instructor queue, certificate lists) but only for L&D-owned data. Workforce: **nothing** | §7, §8 |

**One-line answer to "how does the data work and get shown":** LearnDev is the working half — it can produce every agreed payload and it accepts four inbound feeds. The consumer half (Workforce) does not read, store, or render any of it, and the only Workforce → LearnDev movement that has actually happened was written straight into L&D tables by a Workforce build whose pages are absent from this checkout.

---

## ▶ Completion audit — 2026-09-26

**Headline: the Workforce ↔ LearnDev integration is ~32% complete.** Every ratio below is backed by a command that was run live on 2026-09-26; the rubric is deliberately explicit so anyone can re-weight it.

**Method.** 12 workstreams, weights totalling 100, each scored 0–1 by evidence (file reads, SQL counts, HTTP probes). 100% = *Workforce sends → L&D stores → both sides show it, with consumer keys working and the exchange covered by tests.* Verified at that moment: `ld_notification` 28 · `ld_integration_log` 37 · `ld_integration_event` 11 · `ld_competency_requirement` 4 · `ld_reporting_requirement` 3 · `ld_training_engagement` 1 · `ld_turnover_risk` 3 · `ld_training_requests` 6 (5 workforce-sourced) · `ld_engagement_snapshot` 21 · `ld_skill_snapshot` 12 · `ld_api_key` 8 active · `wfa_*` **20 tables, 0 rows, 0 writers** · `pm_employee_training` 0 · `ta_attendance` 0 · `lc_trainings` 8 (seed rows — see the caveat) · harness `PASS — 127 checks`.

| # | Workstream | Weight | Score | Pts | Evidence |
|---|---|---|---|---|---|
| A | L&D outbound endpoints built, authenticated, logged | 10 | 1.00 | **10.0** | 5/5 files under `api/outbound/`; all call `requireAuth($pdo,'learning-development')` + `IntegrationLog`; `get-compliance-training-log` 200, `get-workforce-analytics` 200 (spot-checked live) |
| B | Outbound payload fidelity | 6 | 0.30 | **1.8** | `department_id=999&date_from=1900-01-01` returns a byte-identical payload (filters ignored); `total_learners=62` vs 11 distinct learners; `time_out` always `null`; `snapshot_date` only inside `risk_summary` |
| C | Consumer landing (the point of outbound) | 16 | 0.00 | **0.0** | `pm_employee_training` 0, `ta_attendance` 0, `wfa_*` 0/20 tables, no writer anywhere in code |
| D | Outbound auth usable by the consumer's own key | 8 | 0.00 | **0.0** | all five demand `learning-development`; consumer key `wa_…` → **401** live, though its `ld_api_key` row is active (id 6) |
| E | L&D inbound surface for the other modules | 6 | 1.00 | **6.0** | 4/4 endpoints, each naming its caller (`performance-management`, `recruitment`, `employee-engagement`, `employee-management`), dedupe keys + `ld_integration_event` wired |
| F | Workforce-specific inbound endpoints (4 domain + 1 notification) | 14 | 0.00 | **0.0** | none live — the four are rollback-only (§6.3); `receive-workforce-notification.php` → **404** live |
| G | Workforce → L&D rows actually on disk | 10 | 0.50 | **5.0** | rows exist in 4 tables + 5 workforce-sourced requests — but via direct DB writes from code that is not in the repo, with no log and no idempotency |
| H | Notification path end-to-end | 12 | 0.45 | **5.4** | store + shared bell + role pages + mark-read **live and verified** (Path A probe; 28 rows); sender **0**, endpoint **404** |
| I | L&D-side display of the exchanged data | 8 | 0.25 | **2.0** | instructor queue INNER JOIN shows 1 of 5 workforce orders; no page reads `ld_competency_requirement` / `ld_turnover_risk` / `ld_reporting_requirement` / `ld_training_engagement`; no reader for the inbound trails |
| J | Workforce module UI **in this repo** | 6 | 0.10 | **0.6** | checkout holds 1 stub page of the 11 in their build, 6 dead nav links, `hrms_*` fatal |
| K | Docs correct | 2 | 0.40 | **0.8** | this doc updated; `integration mds/INTEGRATION_WORKFORCE_ANALYTICS.md` still hands Workforce the `wa_…` key as if it worked |
| L | Tests | 2 | 0.35 | **0.7** | harness `PASS — 127 checks`; **0** checks mention `api/inbound`, `api/outbound`, `workforce` or `integration` |
|  | **Total** | **100** | | **32.3 ≈ 32%** | |

**By owner** (same numbers, regrouped so each team can see its own half):

| Bucket | Items | Weight | Pts | Complete |
|---|---|---|---|---|
| L&D-owned (what my side owes) | A, B, E, H, I, K, L | 46 | 26.7 | **58%** |
| Workforce-owned (what their side owes) | D, F, J | 28 | 0.6 | **2%** |
| Joint — the outcome both sides must reach | C, G | 26 | 5.0 | **19%** |

**If the Workforce team's own build replaced the checkout's stub**, item J would go from 0.10 to ~0.85 (11 real pages, no `hrms_*` left) and the overall figure to 6 × 0.75 = +4.5 → **≈ 37%**. Their build still contains **zero** API calls and zero notification calls, so items D and F do not move.

**Caveat on `lc_trainings` (8 rows).** Those rows are **not** integration output: they are byte-identical to the seed block in the supplied `hrmss workforce.sql` (ids 1–8, employees 6–15, same names/dates/statuses), and the live endpoint returns `{"success":true,"data":[],"count":0}` — its two compliance matches are courses 29 *Workplace Safety Compliance* and 30 *Data Privacy & GDPR*, which appear in none of the eight rows. Compliance & Legal has **not** consumed the feed.

**What moves the number fastest** (each line is weight × headroom):

| Fix | Points | Owner |
|---|---|---|
| A consumer that persists and displays the four outbound feeds (C) | **+16.0** | Workforce (+ Compliance/T&A/PM teams) |
| The five workforce inbound endpoints live and used (F) | **+14.0** | Learning (4 restored/1 new) + Workforce (sender) |
| Make each outbound endpoint accept its consumer's key (D) | **+8.0** | Learning |
| Finish the notification loop: sender + endpoint (H) | **+6.6** | Learning + Workforce |
| Bind the filters, fix `total_learners`, `time_out`, top-level `snapshot_date` (B) | **+4.2** | Learning |
| Give the sent rows a screen and a queue that shows NULL-learner rows (I) | **+6.0** | Learning |
| Ship the real Workforce module pages into the repo (J) | **+5.4** | Workforce |
| Correct the old contract doc, add integration tests (K, L) | **+2.5** | Learning |
| A reproducible Workforce → L&D write path with logging/idempotency (G) | **+5.0** | both |

Closing C + F + D alone — a persisting consumer, the workforce inbound endpoints, and a working consumer key — takes the figure from 32% to about **70%**.

**Sensitivity:** the same checklist counted with equal weights instead of these weights gives 18.5 of 47 items ≈ **39%**. The gap is the weighting choice, not the evidence — the checklist items are listed in the table above so the count can be reproduced item by item.

---

## ▶ START HERE — instruction for the Workforce team

**Audience: the Workforce (Workforce Management) module team. Goal: your module sends a notification, and the Learning & Development side stores it and shows it to an L&D user.**

Written 2026-09-26 against three sources, all of which were read for this section: your build `workforce-20260925T051118Z-1-001.zip` (exported 2026-09-25; its 46 files are dated 2026-08-25 → 2026-09-24), the shared schema dump `hrmss workforce.sql` (2026-09-21), and the live `hrms` database + live Learning code. **Every table, column, id, enum, message and response code below was read from those sources — nothing is inferred, and anything not yet built is labelled as not built.** Sections §1–§13 after this one are the audit; this section is the work order.

### W0. What you have today, and what is missing

```
 YOUR SIDE (modules/workforce)                      MY SIDE (modules/learning)
 ─────────────────────────────                      ──────────────────────────
 predicted-skill-competency-needs.php
 implementation-guide.php
 custom-reports.php            ─── currently ───▶  INSERT INTO ld_competency_requirement / ld_reporting_requirement
 turnover-risk-indicator.php                        (classes/Employee.php:271 and :237 — a direct DB write)
                                                      │
                                                      └─ result: the row exists, but NOTHING tells me it arrived

 WHAT THIS SECTION ADDS
 predicted-skill-competency-needs.php
 implementation-guide.php      ─── notification ──▶ 1. ld_integration_log      (audit trail)
 custom-reports.php                                2. ld_integration_event    (idempotency)
 turnover-risk-indicator.php                       3. the domain row (ld_competency_requirement / ld_turnover_risk /
                                                       ld_reporting_requirement / ld_training_engagement)
                                                   4. ld_notification — one row per L&D recipient  ──▶ my bell menu
                                                       └──────────────────────────────────────────▶ and my notification page
```

| Piece | State on 2026-09-26 | Evidence | Owner |
|---|---|---|---|
| `ld_notification` store + shared sidebar bell + per-role notification pages + mark-read endpoints | **live** | `includes/Notification.php`; `includes/sidebar.php` lines 56–66; `pages/{admin,instructor,learner}/notification.php` | Learning |
| `Notification::push()` — shared class, **Path A** in W2 | **live** | `includes/Notification.php::push()` | Learning (shared infra) |
| `POST api/inbound/receive-workforce-notification.php` — **Path B** contract in W3 | **not built yet** (the path does not exist; a request today cannot succeed) | `ls modules/learning/api/inbound/` → the four endpoints in §5 only | Learning |
| Workforce sender (a class + the four page call sites) | **to do** | your zip has no `curl`, no `X-API-Key`, no `api/` call anywhere | **Workforce** |
| Your module's own bell | **dead stub** — `<li class="notif-item"></li>`, never queries anything | `includes/sidebar.php` lines ~19–27 of your zip | Workforce |

**Two things this instruction fixes, stated plainly.** (1) Today your integration is a **direct write into Learning's tables** — it bypasses `ld_integration_log`, has no idempotency, and produces no signal I can see. (2) You currently have **no way to tell me something arrived**, which is what the notification path exists for.

### W1. What a "notification" is on my side (exact)

`ld_notification` — live schema, verified with `SHOW COLUMNS` on the live DB and matching the dump:

| Column | Type | Rule |
|---|---|---|
| `id` | `int(10) unsigned` PK, auto_increment | mine — never send it |
| `user_id` | `int(10) unsigned` NOT NULL | **the recipient's `em_employees.employee_id`** — see W1.1 |
| `type` | `varchar(50)` NOT NULL | your notification type (allow-list in W3) |
| `title` | `varchar(255)` NOT NULL | ≤ 255 chars, plain text |
| `message` | `text` NOT NULL | plain text (keep ≤ 2000 chars so the list stays readable) |
| `reference_type` | `varchar(50)` NULL | free label for the record it is about (e.g. `ld_turnover_risk`) |
| `reference_id` | `int(10) unsigned` NULL | that record's id — **not** your table's id unless you say which table via `reference_type` |
| `is_read` | `tinyint(1)` NOT NULL DEFAULT 0 | mine — my click sets it |
| `created_at` | `timestamp` NOT NULL DEFAULT current_timestamp() | leave the default |

Index: `idx_notification_user (user_id, is_read)` — the exact pair every read uses.

#### W1.1 The id rule (this is the #1 way to send me something I can never see)

`user_id` is **`em_employees.employee_id`**. Not `user_account.user_id` — those are different id spaces in the same database (live: `user_account` row `user_id=7` belongs to `employee_id=35`). Proof from the live readers:

* shared sidebar: `$notificationUserId = (int) ($current_employee_id ?? $_SESSION['employee_id'] ?? 0);`
* `pages/admin/notification.php`: `$adminId = (int) ($employeeClass->getEmployeeId() ?? 0);` and `getEmployeeId()` returns `$_SESSION['employee_id']`
* your own `Employee::getEmployeeId()` (your zip, line 735) returns the same `$_SESSION['employee_id']` — so on your side the correct value is the one your own session already carries

#### W1.2 Where I will see it

| Surface | Behaviour | Source |
|---|---|---|
| Sidebar bell (the shared markup) | latest **6** rows for my employee id, newest first; unread badge (caps at `9+`); placeholder when empty | `includes/sidebar.php` + `Notification::getRecent($userId, 6)` / `getUnreadCount()` |
| My notification page | the **50** most recent rows for my employee id, **no type filter** — any `type` you send is listed | `pages/admin/notification.php` line 20 (same shape on instructor/learner) |
| Clicking a row | marks it read (`Notification::markAsRead`) and opens the page `NotificationLinks::pageFor(role, type)` chooses | `classes/notificationlinks.php`, `js/bell-links.js` |
| Mark all read | `UPDATE ld_notification SET is_read = 1 WHERE user_id = :uid AND is_read = 0` | `includes/Notification.php::markAllAsRead()` |

Two honest caveats:

1. **Row links only work inside the learning module.** `js/bell-links.js` is loaded by the learning entry point; in other modules the shared bell prints the same rows as plain text. The row text is visible either way.
2. **An unmapped `type` opens my notification list, not a record page.** `NotificationLinks::PAGES` currently maps `conference_reminder`, `conference_starting`, `certificate`, `certificate_expiry`, `grade`, `enrollment`, `invitation`, `training_request`, `knowledge_transfer`. Anything else (including a Workforce type) falls back to `?page=<role>/notification&highlight=<id>`, and its icon falls back to a plain bell (`Notification::ICONS`). **That is still fully visible — there is no whitelist that hides it.** If you want a Workforce row to open a specific L&D page, that is a one-entry mapping Learning adds — ask; do not assume.

### W2. Path A — works today, no change on my side

Because both modules share one database (`database/db.php`), you can write the notification through the same class my own pages use. `includes/Notification.php` documents this as the intended cross-module path.

```php
<?php
// modules/workforce/classes/LearningNotify.php   (new file — yours to add)
// One place that turns "something happened here" into a bell row on the L&D side.

final class LearningNotify
{
    /**
     * The L&D people a Workforce signal has to reach: user_account carries the role,
     * and ld_notification wants the *employee* id, so we select employee_id — never user_id.
     */
    public static function audience(PDO $pdo): array
    {
        $ids = $pdo->query(
            "SELECT DISTINCT employee_id FROM user_account
              WHERE account_status = 'Active' AND role_id IN (1, 7)"
        )->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * One notification per recipient. Never throws: telling the L&D side must not break
     * the save the user actually asked for.
     *
     * @return int recipients notified (0 = nothing was sent; log and carry on)
     */
    public static function announce(
        PDO $pdo,
        string $type,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): int {
        $sent = 0;
        try {
            require_once dirname(__DIR__, 3) . '/includes/Notification.php';   // repo-root includes/
            $bell = new Notification($pdo);

            foreach (self::audience($pdo) as $employeeId) {
                $bell->push(
                    $employeeId,
                    mb_substr($type, 0, 50),
                    mb_substr($title, 0, 255),
                    mb_substr($message, 0, 2000),
                    $referenceType,
                    $referenceId
                );
                $sent++;
            }
        } catch (Throwable $e) {
            error_log('[workforce] Learning notification skipped: ' . $e->getMessage());
        }

        return $sent;
    }
}
```

Call it **after** the row you already insert, in the same request:

```php
// inside the handler of predicted-skill-competency-needs.php, right after $requirementId comes back
$pdo  = (new Database())->getConnection();
$sent = LearningNotify::announce(
    $pdo,
    'workforce_competency_requirement',
    'New competency requirement',
    sprintf(
        'Workforce Analytics predicted "%s" for %s (%d learner(s) need training).',
        $skillName,
        $department,
        $employeesNeeded
    ),
    'ld_competency_requirement',
    $requirementId
);
```

Rules that come with Path A:

* **Live-verified 2026-09-26:** a probe row pushed with exactly this call (`push(35, 'workforce_alert', …)` → read back through `getRecent(35, 1)` with `is_read = 0`) was visible immediately and then removed; `ld_notification` is back to its 28 rows. Path A works for the three ids in W4 today.
* `Notification::push()` **throws** `InvalidArgumentException` for a non-positive recipient id — the `try/catch` above is why that cannot fail your save. Keep it.
* Only claim success if `$sent > 0`. Your page currently prints *“Competency requirement #N was sent to the Learning system.”* — that stays true for the row, but the notification may have failed; say what actually happened or leave the message alone.
* Do not call this from a page-load path (it would push a row on every request). One event, one call.

### W3. Path B — the API path (Learning has to stand this endpoint up)

Path A writes rows but produces no audit trail and no idempotency, and it couples you to my table. Path B is the real integration: one POST, logged and de-duplicated, and **the notification is the transport**. Think of it as the same shape as the four inbound endpoints already live on my side (§5).

**Status: the endpoint below does not exist yet — Learning delivers it. Build your sender against this contract; until it is live, Path A is the working path.**

```
POST  http://localhost/hrms-capstone/modules/learning/api/inbound/receive-workforce-notification.php
Content-Type: application/json
X-API-Key: <ld_api_key.api_key WHERE module_name = 'workforce-analytics'>
```

The key row exists and is active — `ld_api_key` id 6, `module_name='workforce-analytics'`, 67 chars, live-verified. Get the value from Learning (it is in the same DB: `SELECT api_key FROM ld_api_key WHERE module_name='workforce-analytics'`). `Authorization: Bearer <key>` and `?api_key=` are accepted too, in that order after the header — that is `ApiAuth::extractKey()`.

#### W3.1 Request body

```json
{
  "notification_id": "wa-notif-2026-09-26-0001",
  "type": "workforce_turnover_risk",
  "title": "Critical turnover risk",
  "message": "Workforce Analytics flagged David Aquino (PSYCHOLOGY DEPARTMENT) as a critical turnover risk (score 88). Please triage.",
  "reference_type": "ld_turnover_risk",
  "reference_id": 3,
  "audience": "ld_staff",
  "data": {
    "external_reference_id": "TR-2003",
    "employee_id": 13,
    "employee_name": "David Aquino",
    "department": "PSYCHOLOGY DEPARTMENT",
    "position_name": "Psychology Instructor",
    "risk_level": "critical",
    "risk_score": 88,
    "risk_factors": "low engagement, expired certification",
    "recommended_action": "Assign refresher course and mentor check-in",
    "assessment_date": "2026-09-26"
  }
}
```

| Field | Required | Rule (a failure → 422, body shown in W3.4) |
|---|---|---|
| `notification_id` | **yes** | 1–120 chars, unique **per event**; the idempotency key (`wa-notif-<your id>`) |
| `type` | **yes** | one of: `workforce_competency_requirement`, `workforce_turnover_risk`, `workforce_reporting_requirement`, `workforce_training_engagement`, `workforce_alert` |
| `title` | **yes** | non-empty, ≤ 255 chars, **plain text** |
| `message` | **yes** | non-empty, ≤ 2000 chars, plain text (rendered escaped, so HTML would show as literal text) |
| `reference_type` | no | ≤ 50 chars; for domain types use the L&D table the record lands in (`ld_turnover_risk`, `ld_competency_requirement`, `ld_reporting_requirement`, `ld_training_engagement`) |
| `reference_id` | no | positive integer; the domain row id when you know it |
| `audience` | **yes** | `ld_staff` (resolve the audience yourself with the SQL in W4) or `explicit` |
| `recipients` | only for `audience="explicit"` | array of 1–100 positive integers, each an `em_employees.employee_id`; unknown ids are skipped and reported back |
| `data` | no | the domain record — required for the four `workforce_*` domain types (W3.2), omit for `workforce_alert` |

`workforce_alert` with no `data` is the minimal form: **a notification with no domain row** — the purest "send data via notification".

#### W3.2 `data` per type — the exact fields and where they land

Field names and enums are the live columns of each table (verified with `SHOW COLUMNS`) and match the fields your own pages already collect.

**`workforce_competency_requirement` → `ld_competency_requirement`** (your `predicted-skill-competency-needs.php` form)

| `data` field | Required | Column / rule |
|---|---|---|
| `skill_name` | yes | `varchar(150)` |
| `target_department` | yes | `varchar(150)` |
| `target_proficiency` | yes | `varchar(50)` |
| `expected_learning_output` | yes | `varchar(255)` |
| `employees_needing_training` | yes | integer ≥ 0 |
| `priority_level` | no | `low` \| `medium` \| `high` \| `critical`, default `medium` |
| `notes` | no | text |
| `external_reference_id` | no | your record id in `source_module`'s world; used as the domain-row dedupe key |

**`workforce_turnover_risk` → `ld_turnover_risk`** (live rows: 3, e.g. `TR-2001` David Aquino, `risk_level='high'`, `risk_score=72.00`)

| `data` field | Required | Column / rule |
|---|---|---|
| `risk_level` | yes | `low` \| `medium` \| `high` \| `critical` |
| `external_reference_id` | recommended | `varchar(120)`; dedupe key (`TR-2003` …) |
| `employee_id` | no | `int unsigned`; `em_employees.employee_id` |
| `employee_name`, `department`, `position_name` | no | `varchar(150)` each — send the name/department as text even when `employee_id` is null (row 2 on disk is exactly that: `employee_id=NULL`, name `Felix Del Rosario`) |
| `risk_score` | no | `decimal(5,2)` |
| `risk_factors`, `recommended_action` | no | text |
| `assessment_date` | no | `YYYY-MM-DD` |

**`workforce_reporting_requirement` → `ld_reporting_requirement`** (your `implementation-guide.php` / `custom-reports.php`)

| `data` field | Required | Column / rule |
|---|---|---|
| `target_name` | yes | `varchar(150)` |
| `reporting_period_start`, `reporting_period_end` | yes | `YYYY-MM-DD`, end ≥ start |
| `completion_target` | yes | number 0–100 (`decimal(5,2)`) |
| `expected_output` | yes | `varchar(255)` |
| `reporting_frequency` | no | `weekly` \| `monthly` \| `quarterly` \| `annual`, default `monthly` |
| `notes` | no | text |

**`workforce_training_engagement` → `ld_training_engagement`** (the form on your `turnover-risk-indicator.php`, which is currently inert — see W6)

| `data` field | Required | Column / rule |
|---|---|---|
| `department` | yes | `varchar(150)` |
| `training_activity` | yes | `course` \| `learning-path` \| `workshop` \| `compliance-training` \| `other` |
| `engagement_status` | yes | `active` \| `in-progress` \| `low` \| `inactive` |
| `learner_team_type` | no | `individual-learner` \| `department-team` \| `role-group`, default `individual-learner` |
| `learner_id`, `learner_name` | no | `int unsigned` / `varchar(150)` |
| `engagement_data`, `recommended_action`, `notes` | no | text |
| `external_reference_id` | recommended | `varchar(120)` |

**No extra notifications are added on my side.** Whatever you send is what I get — one row per recipient. (An earlier Workforce build auto-escalated `risk_level='critical'` / `priority_level='critical'` into its own alert; that rule is *not* part of this contract, so you will never be surprised by a second row. Put "Critical …" in your own title when it is critical.)

#### W3.3 What Learning does with an accepted call

1. Authenticate (`workforce-analytics` key) → `401` on failure.
2. Parse JSON → `400` if it is not an object; validate W3.1/W3.2 → `422` with the first failed rule.
3. Duplicate check: `ld_integration_event` on `(module_name='workforce-analytics', external_reference_id='wa-notif-<notification_id>')` → if present: **200** `{"success":true,"message":"Already processed","duplicate":true}` and nothing is written.
4. Insert the `data` row into its table (when `data` is present; its own `external_reference_id` de-duplicates the row).
5. Insert **one `ld_notification` row per resolved recipient** with `user_id` = `em_employees.employee_id`, `type`/`title`/`message`/`reference_type`/`reference_id` from your payload, `is_read = 0`.
6. Steps 3–5 run in **one transaction**: the domain row and every notification row commit together, or none of them does — you never get a half-arrived event. The `ld_integration_log` row (`direction='inbound'`, `module_name='workforce-analytics'`, `endpoint='receive-workforce-notification'`, `status='success'|'failed'`, the payload) and the `INSERT IGNORE` event row are written either way, so a failed call is still visible to both of us.

#### W3.4 Response codes (same shape as the four live inbound endpoints)

| HTTP | When | Body |
|---|---|---|
| `200` | stored | `{"success":true,"message":"Notification stored for 4 recipient(s)","notification_ids":[…],"recipients":[35,…],"record_id":3,"duplicate":false}` |
| `200` | re-sent `notification_id` | `{"success":true,"message":"Already processed","duplicate":true}` |
| `400` | body is not a JSON object | `{"success":false,"message":"Invalid JSON payload"}` |
| `401` | missing / wrong / inactive key | `{"success":false,"error":"Invalid API key"}` (inactive: `"API key is inactive"`) |
| `405` | not POST | `{"success":false,"message":"Method not allowed"}` |
| `422` | a field rule failed | `{"success":false,"error":"employees_needing_training must be zero or a positive whole number"}` — the message names the first failed field |
| `500` | server-side exception | `{"success":false,"error":"…"}` (the call is also logged `failed`) |

### W4. Recipients — who "the L&D side" is, exactly

L&D staff live in `user_account` (`role_id`), and the notification wants `em_employees.employee_id`:

```sql
SELECT DISTINCT u.employee_id, u.role_id, r.role_name
FROM user_account u
LEFT JOIN em_roles r ON r.role_id = u.role_id
WHERE u.account_status = 'Active' AND u.role_id IN (1, 7)   -- 1 = System Administrator, 7 = Learning and Development Staff
ORDER BY u.role_id, u.employee_id;
```

That is the same rule the removed `WorkforceIntegration::getAlertAudience()` used (`ALERT_ROLE_IDS = [1, 7]`). **Live result today — three ids:**

| employee_id | role | who |
|---|---|---|
| `35` | 7 | Rainiel Quebada — the real L&D staff account (this is "me") |
| `67999` | 7 | `Learner Test` (test account) |
| `99967` | 7 | `Instructor Test` (test account) |

Three warnings, all of them live-verified:

1. **`1018` must not be used.** The old build carried a legacy constant `LD_ADMIN_EMPLOYEE_IDS = [35, 1018]`; `1018` has no row in `em_employees`, so a notification pushed to it is stored and can never be read. That is precisely the failure mode W1.1 is about — and it is on disk: the pre-cleanup `ld_notification` rows include two addressed to `1018` that no account can ever open.
2. **Role 1 has no active account** (`user_account` has no active `role_id = 1` row), so the audience is role 7 only in practice. If a System Administrator is ever created, they join the audience automatically.
3. **The ids are employees, and employee ids are what instructors/learners use too** — so if you also want instructors to see a request, send `type='training_request'` (mapped to `instructor/training-requests`), or resolve your own list (`SELECT DISTINCT instructor_id FROM ld_course WHERE instructor_id IS NOT NULL AND status = 'active'`) and send them via `audience="explicit"`.

### W5. Idempotency, retries, failure handling

| Situation | What you do | What I see |
|---|---|---|
| First send | POST with a fresh `notification_id` | one `ld_notification` row per recipient, `is_read = 0` |
| Timeout / 5xx | retry with the **same** `notification_id`, backoff (e.g. 1s, 4s, 16s), max 3 attempts | either 1 set of rows, or 200 `duplicate:true` — never two sets |
| 200 `duplicate:true` | stop — it was already processed | nothing new |
| 4xx (`400`/`401`/`422`) | stop and fix the payload/key — retrying will never succeed | nothing |
| Same business record, **new** `notification_id` | that is a new event by definition → duplicate rows for me | two rows — only do this when it genuinely re-happened |
| Notification fails, the row insert succeeded | log it, do not roll back your user's work | nothing — retry from your side later (same `notification_id` if the call never landed) |

### W6. Your side, file by file

| Your file | Today | Change |
|---|---|---|
| `classes/Employee.php` — `saveCompetencyRequirement()` (line 271) | INSERT into `ld_competency_requirement` | keep the insert (or switch it to Path B's `data`) + `LearningNotify::announce(... 'workforce_competency_requirement' …  'ld_competency_requirement', $requirementId)` |
| `classes/Employee.php` — `saveLearningReportingRequirement()` (line 237) | INSERT into `ld_reporting_requirement` | same, with `workforce_reporting_requirement` |
| `pages/predicted-skill-competency-needs.php` | writes; message says "was sent to the Learning system" | call the notify helper after the insert; only claim delivery when `$sent > 0` |
| `pages/implementation-guide.php` | writes | same as above |
| `pages/custom-reports.php` | writes | same — **and fix the call**: unlike `implementation-guide.php` it passes no `reporting_frequency`, while the class reads `$input['reporting_frequency']` into a `NOT NULL` enum column (`weekly`/`monthly`/`quarterly`/`annual`); pass one from the form or default to `'monthly'` in the class |
| `pages/turnover-risk-indicator.php` | form is inert — its own copy says "This form is for planning only. No data is sent or saved." | if turnover risk must reach me, wire the POST handler → `ld_turnover_risk` (or Path B with `data`) and send the risk notification (`type='workforce_turnover_risk'` or `workforce_alert`) |
| `includes/sidebar.php` (yours) | bell is a hardcoded empty stub | either render it from `ld_notification` (`WHERE user_id = <your session employee_id>`) or leave it — the authoritative inbox is the learning module either way |
| `includes/header.php` + nav links | hard-code `/hrms-capstone/…` | put the app base URL in one config value and build both nav and API URLs from it; hard-coded paths break on any other deployment |

### W7. Definition of done (run these, then ask me to confirm)

```bash
# 1. your sender reaches the endpoint (Path B, once Learning has built it)
curl -sS -o /tmp/r.json -w "%{http_code}\n" \
  -X POST -H "Content-Type: application/json" \
  -H "X-API-Key: $(mysql -uroot hrms -N -B -e "SELECT api_key FROM ld_api_key WHERE module_name='workforce-analytics'")" \
  -d @/tmp/notification.json \
  http://localhost/hrms-capstone/modules/learning/api/inbound/receive-workforce-notification.php
cat /tmp/r.json            # expect 200 {"success":true,...,"duplicate":false}
# send it a second time → 200 {"success":true,"duplicate":true}
# GET instead of POST → 405 ; a wrong key → 401 ; a missing title → 422

# 2. what I will see (same DB, so you can check it yourself)
mysql -uroot hrms -e "SELECT n.id, n.user_id, n.type, n.title, n.is_read, n.created_at
                      FROM ld_notification n
                      WHERE n.reference_type = 'ld_turnover_risk' ORDER BY n.id DESC LIMIT 10"
mysql -uroot hrms -e "SELECT direction, module_name, endpoint, status, created_at
                      FROM ld_integration_log
                      WHERE module_name = 'workforce-analytics' ORDER BY id DESC LIMIT 5"
```

Acceptance, stated as what each of us checks:

| # | Check | Owner | Expected |
|---|---|---|---|
| 1 | `curl` with the `workforce-analytics` key | Workforce | `200`, `success:true` |
| 2 | same call with the `learning-development` key | Workforce | `401` — do not retry, use your own key |
| 3 | re-send the same `notification_id` | Workforce | `200 duplicate:true`, **no** new rows |
| 4 | one `ld_notification` row per recipient, `is_read = 0` | either | rows for 35 / 67999 / 99967 |
| 5 | bell badge + `?page=admin/notification` as employee 35 | Learning | the row is listed, newest first; the click marks it read |
| 6 | `ld_integration_log` row `inbound / workforce-analytics / receive-workforce-notification / success` | either | present with the payload |
| 7 | domain row present exactly once (when `data` sent) | Learning | one row, `source_module` per the endpoint's write |

### W8. Do-not list (the ways this goes wrong)

1. **Do not use `user_account.user_id` as `ld_notification.user_id`.** The correct value is `em_employees.employee_id` (your session `employee_id`). Wrong ids produce rows nobody can ever read.
2. **Do not push to `1018`**, or to any id you have not confirmed in `user_account`/`em_employees`.
3. **Do not send HTML** in `title`/`message` — it is rendered escaped, so tags appear as literal text.
4. **Do not do Path A and Path B for the same event** — you would send me two rows. Pick one; if you start with A and move to B, keep `type`/`reference_type`/`reference_id` identical.
5. **Do not assume a Workforce `type` opens a Workforce page on my side.** It opens my notification list unless Learning adds a mapping. The notification itself is always visible.
6. **Do not let a notification failure fail your save** — wrap it (W2) and log to `error_log`.
7. **Do not write notifications from page-load code** — one event, one call.
8. **Do not exceed the column widths** (`type` ≤ 50, `title` ≤ 255, `reference_type` ≤ 50, `message` ≤ 2000) — a longer value would be truncated at best.
9. **Do not put secrets in the payload** and do not hard-code the API key in a page — read it from config / `ld_api_key`.

---

## 1. What "Workforce" physically is in this checkout

```
modules/workforce/
├── index.php                     ← includes sidebar + header, renders one page
├── page-loader.php               ← same, for AJAX-style partial loads
├── classes/Page.php              ← nav model: 7 labels, only 1 page file exists
├── classes/Employee.php          ← reads hrms_employee / hrms_department / hrms_position
├── includes/{sidebar,header,footer}.php
├── pages/dashboard-overview.php  ← <h1>Dashboard Overview</h1> + EMPTY .module-content
├── css/, js/, assets/
└── (no cron, no api/, no classes/Integration*, no SQL)
```

Findings from reading and probing it:

1. **The only page is a stub.** `pages/dashboard-overview.php` is a header plus `<div class="module-content"> </div>` — nothing is queried, nothing is rendered.
2. **The nav advertises 7 pages, 6 of which do not exist** (`period-manager`, `payroll-processing`, `payslips`, `deductions`, `reports`, `final-settlement`). `Page::renderLink()` prints them as `<a href="#">` dead links. (Note the section list also misspells `payroll-processsing`, so that entry can never match a discovered file.)
3. **The module cannot currently render for a signed-in user.** `modules/workforce/classes/Employee.php::getEmployees()`/`getEmployeeName()` query `hrms_employee`, `hrms_department`, `hrms_position`. Those tables **do not exist** in the live `hrms` database (only `em_employees`, `em_departments`, `em_positions` do). The app-root sidebar (`includes/sidebar.php`) constructs `new Employee()` and calls `getEmployeeName()` outside any try/catch, so the page dies with `SQLSTATE[42S02] … Table 'hrms.hrms_employee' doesn't exist`. Verified by direct query probe:

   ```
   $ php -r '… PDO->query("SELECT … FROM hrms_employee …") …'
   THROWS: PDOException: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'hrms.hrms_employee' doesn't exist
   ```
4. **No `wfa_*` reader anywhere.** A repo-wide grep for `FROM wfa_` / `INSERT INTO wfa_` / `UPDATE wfa_` returns **zero hits** in any live PHP file. The 20 `wfa_*` tables exist in the schema and are all empty.
5. **Access control:** unauthenticated requests 302 to login (via `auth/session.php` pulled in by the shared sidebar). There is no Workforce-specific role gate — any signed-in account that reaches `/modules/workforce/` sees it.

**Consequence for this audit:** "shown on the workforce side" has exactly one honest answer today — **it isn't**. §8 lists what would have to exist for each data block to be shown.

**Also worth stating plainly:** the file list in the conversation-start repo summary (`classes/workforceintegration.php`, `classes/workforceintegrationview.php`, `classes/engagementsnapshot.php`, `api/inbound/receive-{turnover-risk,competency-requirement,reporting-requirement,training-engagement}.php`, `api/outbound/get-workforce-integration-status.php`, `pages/*/workforce-integration.php`, `cron/run-due.php`) **does not exist in the live tree**. The single copy is `.freebuff/rollback-20260925/untracked/…`. See §6.3.

**What this checkout is *not* showing (read 2026-09-26):** the Workforce team supplied their own build as `workforce-20260925T051118Z-1-001.zip` — 46 files dated 2026-08-25 → 2026-09-24. In it the module has eleven real pages (`dashboard-overview`, `attrition-turnover`, `predictive-analytics`, `predicted-skill-competency-needs`, `turnover-risk-indicator`, `diversity-inclusion`, `custom-reports`, `implementation-guide`, `certificate-print`, `profile-settings`, `change-password`), a 34 KB `classes/Employee.php`, and **zero `hrms_*` references** — it queries `em_employees`, `em_departments`, `em_positions` and reads `ld_enrollment`, `ld_certificate`, `ld_course`, `ld_engagement_snapshot`. So finding G2 below describes **the checkout's copy only**; in their build it is already fixed. What their build still lacks is any way to *tell* L&D something arrived — that is what the START HERE section specifies. The section numbers in this audit refer to the checkout; where the supplied build differs, it says so.

---

## 2. Exchange surface — direction matrix

Nine live endpoints under `modules/learning/api/`. All use `X-API-Key` (or `Authorization: Bearer`, or `?api_key=`), all log to `ld_integration_log`, all JSON.

| # | Endpoint | Direction | Caller / auth module | Primary source (L&D) | Declared destination (consumer) | Landed? |
|---|---|---|---|---|---|---|
| 1 | `outbound/get-training-completion.php` | → Performance Mgmt | `learning-development` | `ld_enrollment` (+`ld_certificate`, `ld_quiz_attempt`) | `pm_employee_training` | ❌ 0 rows |
| 2 | `outbound/get-learning-performance.php` | → Performance Mgmt | `learning-development` | `ld_quiz_attempt`, `ld_course_skill`, `ld_skill` | (perf. module – quiz/skill blocks) | ❌ not consumed |
| 3 | `outbound/get-attendance-data.php` | → Time & Attendance | `learning-development` | `ld_conference_attendance` ⋈ `ld_video_conference` | `ta_attendance` | ❌ 0 rows |
| 4 | `outbound/get-compliance-training-log.php` | → Compliance & Legal | `learning-development` | `ld_enrollment` ⋈ `ld_course` (compliance-tagged) | `lc_trainings` | ❌ 0 rows |
| 5 | `outbound/get-workforce-analytics.php` | → Workforce Analytics | `learning-development` | 8 L&D aggregates (see §4.5) | `wfa_skill_gap_analysis`, `wfa_reports`, `wfa_risk_assessment`, `wfa_at_risk_employees_summary` | ❌ 0 rows, no reader |
| 6 | `inbound/receive-appraisal-data.php` | ← Performance Mgmt | `performance-management` | `pm_training_recommendations` (theirs) | `ld_training_recommendation` + `ld_enrollment` invites | ✅ writes (0 rows today — nothing sent) |
| 7 | `inbound/receive-job-test-result.php` | ← Recruitment | `recruitment` | job-test result | `ld_assessment_result` + path invites | ✅ writes (0 rows today) |
| 8 | `inbound/receive-recognition-eligibility.php` | ← Employee Engagement | `employee-engagement` | `eer_recognitions` (theirs) | `ld_recognition_unlock` + invites | ✅ writes (0 rows today) |
| 9 | `inbound/receive-employee-profile.php` | ← Employee Management | `employee-management` | `em_employees` (read-only join) | — (read at time of use) | ✅ answers reads |

**Not in the live matrix:** any endpoint named `receive-turnover-risk`, `receive-competency-requirement`, `receive-reporting-requirement`, `receive-training-engagement`, `get-workforce-integration-status` — see §6.3.

---

## 3. Plumbing: auth, keys, logging, idempotency

### 3.1 `ApiAuth` (`classes/apiauth.php`)

```php
verify($apiKey, $moduleName)  →  SELECT id, module_name, api_key, is_active
                                 FROM ld_api_key
                                 WHERE module_name = :module AND api_key = :key
```

* Both `module_name` **and** `api_key` must match the same row. Failure → `401 {"success":false,"error":"…"}` + `exit`.
* Key extraction order: `X-API-Key` header → `Authorization: Bearer …` → `?api_key=` / `$_POST['api_key']`.
* `is_active = 0` is rejected with a distinct message.

`ld_api_key` (live, all active, 67-char keys):

| id | module_name | used by |
|---|---|---|
| 1 | `performance-management` | inbound appraisal |
| 2 | `recruitment` | inbound job test |
| 3 | `employee-management` | inbound employee profile |
| 4 | `employee-engagement` | inbound recognition |
| 5 | `compliance-legal` | *(seeded, unused by L&D code)* |
| 6 | `workforce-analytics` | *(seeded, **rejected** by L&D endpoints — see 3.3)* |
| 7 | `time-attendance` | *(seeded, unused by L&D code)* |
| 8 | `learning-development` | **all 5 outbound endpoints demand this key** |

### 3.2 `IntegrationLog` (`classes/integrationlog.php`)

* `logCall(direction, module_name, endpoint, status, payload, error_message)` → `ld_integration_log`. Live: **36 rows**.
* `isDuplicate(module_name, external_reference_id)` + `markProcessed(module_name, external_reference_id, event_type)` → `ld_integration_event` (`INSERT IGNORE`). Live: **11 rows**.
* Idempotency key is exactly the pair `(module_name, external_reference_id)`. A resend with a *different* sender-side id duplicates business data; a resend with the *same* id is silently accepted and skipped (success is still logged).

### 3.3 ⚠️ Verified auth defect: the Workforce key does not work

`INTEGRATION_WORKFORCE_ANALYTICS.md` instructs Workforce to call `get-workforce-analytics.php` with the seeded `wa_…` key. Probed live:

```
X-API-Key: <ld_api_key where module_name='learning-development'>   → 200 {"success":true,…}
X-API-Key: <ld_api_key where module_name='workforce-analytics'>    → 401 {"success":false,"error":"Invalid API key"}
```

Both keys are present, active, and 67 chars long. The endpoint calls `ApiAuth::requireAuth($pdo, 'learning-development')`, so it only accepts **L&D's own key** — the consumer must be handed LearnDev's key, and the `workforce-analytics` row is currently decorative. The same pattern applies to all five outbound endpoints (each names its *own* module). Fix direction: either each endpoint should accept whichever caller module it serves (`time-attendance`, `compliance-legal`, `performance-management`, `workforce-analytics`), or the existing key must be the one distributed.

### 3.4 Log evidence of the exchange

```
direction | module_name          | endpoint                          | status | created_at
outbound  | workforce-analytics  | get-workforce-integration-status   | success| 2026-09-25 17:55
inbound   | workforce-analytics  | receive-competency-requirement     | success| 2026-09-25 17:51  (x2)
inbound   | workforce-analytics  | receive-turnover-risk              | success| 2026-09-25 17:51  (x2)
inbound   | workforce-analytics  | receive-reporting-requirement      | failed | 2026-09-25 13:35
inbound   | performance-management| … (3 appraisal rows)
inbound   | employee-engagement   | … (2 recognition rows)
inbound   | recruitment           | lc-test-1-848811bd…                | (job_test_result)
```

Every row above names an endpoint that **is not in the repository** unless it appears in §2. The log is therefore also a history of removed code (§6.3).

---

## 4. Outbound — LearnDev → consumer modules

All five: `GET`, JSON, authenticated as `learning-development`, logged with `count` + the incoming `filters`.

### 4.1 `get-training-completion.php` → `pm_employee_training`

* **Filters:** `learner_id`, `course_id`, `status` (all optional, all applied).
* **Sources:** `ld_enrollment` `LEFT JOIN ld_certificate ON cert.completed_enrollment_id = e.id`, plus a per-`(learner_id, course_id)` average from `ld_quiz_attempt → ld_quiz → ld_module`.
* **Payload per row:** `employee_id`, `training_id` (=`course_id`), `completion_status` (`Completed`/`In Progress`/`Pending`), `completion_percentage` (100 / 50 / 0 — derived from enrollment status, *not* real progress), `final_score`, `certificate_status` (`Issued`/`Not Issued`), `assigned_date` (=`enrolled_at`), `end_date` (=`completed_at`).
* **Destination shape match:** `pm_employee_training` exists with matching columns (`completion_status`, `completion_percentage`, `final_score`, `certificate_status`, `assigned_date`, `end_date`, `attendance_status`, `remarks`…). The endpoint has no `attendance_status` and no `recommendation_id` — the consumer must leave `attendance_status` at default and correlate `recommendation_id` itself.
* **Not consumed:** 0 rows.

### 4.2 `get-learning-performance.php`

* **Filters:** `learner_id`, `course_id` (applied to quiz block; the skill block re-uses the same params after swapping the alias — worth a regression test if you change the WHERE builder).
* **Payload:** `quiz_performance[]` = `{employee_id, total_quizzes, passed_quizzes, avg_score, courses[]}`; `skill_proficiency[]` = `{employee_id, skills[{skill_name, proficiency, courses_completed, total_courses}]}` where `proficiency = completed courses with that skill / total courses with that skill × 100`.

### 4.3 `get-attendance-data.php` → `ta_attendance`

* **Filters:** `employee_id`, `conference_id`, `date_from`, `date_to` (all applied; dates filter `ld_video_conference.scheduled_at`).
* **Source:** `ld_conference_attendance` ⋈ `ld_video_conference` ⋈ `ld_course`. **Live rows: 0** (`ld_conference_attendance` is empty), so the endpoint returns `count: 0` plus a `note` explaining attendance tracking may not be active.
* **Payload:** `employee_id`, `attendance_date` (from `scheduled_at`), `time_in` (=`joined_at`), **`time_out` always `null`**, `status` (`PRESENT`/`ABSENT` from `attended`), `recorded_by: 'SYSTEM'`, `source: 'learning-development'`, `session_type`, `conference_title`, `course_title`.
* **Shape gaps vs `ta_attendance`:** destination needs `recorded_by ∈ ('MANUAL','QR','SYSTEM')` (ok) but the payload has no `shift_id`, `total_hours_worked`, `late_minutes`, approvals — the consumer must default them. Only **video-conference** attendance is representable; in-person training has no source table.

### 4.4 `get-compliance-training-log.php` → `lc_trainings`

* **Filters:** `employee_id`, `status`.
* **Compliance definition:** `c.is_compliance_required = 1 OR c.category IN ('Compliance','compliance','Safety')` — live matches: **2 courses**.
* **Payload:** `employee_id`, `training_name`, `training_type` (=category), `date_completed`, `expiry_date` (`ld_certificate.valid_until` when an active certificate exists, else `completed_at + 1 year`), `status` (`Completed` / `In Progress` / `Expired` (deadline passed) / `Pending`).
* **Not consumed:** the endpoint returns `{"success":true,"data":[],"count":0}` (re-verified live 2026-09-26). The 8 rows now in `lc_trainings` are the supplied dump's seed block — same ids, employees, names and dates — and none matches this endpoint's two compliance courses (29 *Workplace Safety Compliance*, 30 *Data Privacy & GDPR*). See the caveat in the ▶ Completion audit.

### 4.5 `get-workforce-analytics.php` — the Workforce bridge

* **Documented filters:** `department_id`, `date_from`, `date_to`. **⚠️ They are ignored.** The file's only use of them is `'filters' => $_GET` in the log line; every query is a bare `$pdo->query()` with no bound parameters.
* **Payload (10 blocks):**

| Block | Built from | Intended `wfa_*` landing |
|---|---|---|
| `total_learners` | `array_sum(enrollment_stats)` | `wfa_reports` |
| `enrollment_stats` | `ld_enrollment` grouped by status | `wfa_reports`, `wfa_employee_metrics` |
| `top_courses` (10) | `ld_course` ⋈ `ld_enrollment` | `wfa_reports` |
| `skill_gap_analysis[]` | `ld_skill` ⋈ `ld_course_skill` ⋈ `ld_enrollment`; `skill_gap_percentage = (total_enrolled − completed)/total_enrolled × 100`; priority `>70 critical / >50 high / >30 medium / else low` | `wfa_skill_gap_analysis` |
| `skill_snapshots[]` | latest `ld_skill_snapshot` date ⋈ `em_departments`/`em_positions` | `wfa_skill_gap_analysis` |
| `completion_trend[]` | `ld_enrollment` per month, last 12 months: `enrollments`, `completions` | `wfa_reports` |
| `total_certificates` | `COUNT(*) FROM ld_certificate` (all statuses) | `wfa_reports` |
| `expiring_certificates[]` | `ld_certificate` active, `valid_until` within 90 days | `wfa_reports` |
| `risk_summary` | latest `ld_engagement_snapshot` date: `bands` (≥70 high / ≥40 medium / else low) + `at_risk_learners` (≥40) | `wfa_risk_assessment`, `wfa_at_risk_employees_summary` |
| `knowledge_transfer` | `ld_learning_path` where `type='knowledge_transfer' OR kt_plan_id IS NOT NULL` | `wfa_reports` |

* **⚠️ Fidelity defect (verified live):** `total_learners` returned **62**, while the DB has **11 distinct `learner_id`s** and **62 enrollment rows**. `total_learners` is a count of enrollments, not learners; any Workforce headcount built on it will be wrong by ~6×. (`array_sum` over `enrollment_stats` is the cause.)
* **⚠️ Freshness:** the endpoint always serves `MAX(snapshot_date)` and says nothing about age. Live snapshots: `ld_skill_snapshot` last 2026-09-05; `ld_engagement_snapshot` has 2026-09-05 (10 learners) and 2026-09-25 (11 learners — written during the 2026-09-25 integration testing, not by the cron, whose last log line is 2026-09-05 06:50). A consumer shows "current risk" from data that is normally ~3 weeks old.

---

## 5. Inbound — other modules → LearnDev

| Endpoint | Auth module | Required body | Idempotency reference | Writes | Acts? |
|---|---|---|---|---|---|
| `receive-appraisal-data.php` | `performance-management` | `recommendation_id`, `employee_id`, `development_area`, `status` | `pm-rec-{recommendation_id}` / event `appraisal_recommendation` | `ld_training_recommendation` (source `appraisal`) | only when `status === 'Approved'`: `CourseMap::getCoursesForDevelopmentArea()` → `Enrollment::invite($employeeId, $courseId, 0)` |
| `receive-job-test-result.php` | `recruitment` | `employee_id`, `test_name` (+ `candidate_id`, `score`, `passed`, `recommended_learning_path_id`) | `lc-test-{employee_id}-md5(test_name+candidate_id)` / `job_test_result` | `ld_assessment_result` (source `recruitment_job_test`) — **always**, pass or fail | if `passed` and a path id is given: `ld_learning_path_item` ⋈ `ld_course` in `order_index` order → invites |
| `receive-recognition-eligibility.php` | `employee-engagement` | `recognition_id`, `receiver_id`, `status` | `eer-rec-{recognition_id}` / `recognition_eligibility` | `ld_recognition_unlock` (status `unlocked`/`redeemed`) | only when `status === 'approved'`: `CourseMap::getCoursesForRecognitionCategory()` → invites |
| `receive-employee-profile.php` | `employee-management` | — (GET) / `employee_id` (POST snapshot) | `em-profile-{employee_id}` / `employee_profile_snapshot` | nothing persisted — "read at time of use" | GET returns `em_employees` ⋈ `em_departments`/`em_positions` + `ld_enrollment`, `completed_skills`, `employee_skills`, `employee_certifications`, `employee_documents` |

**Live state:** `ld_training_recommendation`, `ld_recognition_unlock`, `ld_assessment_result` all have **0 rows** — nothing has called these since the tables were created.

**Downstream visibility of inbound data in LearnDev UI:** automation created by these endpoints shows up as **`ld_enrollment` invites** (learner `study.php` → *Invitations* tab, badge count at line 381/393). The audit trails they write (`ld_training_recommendation`, `ld_assessment_result`, `ld_recognition_unlock`) are **not rendered by any page** — the only reader is the unauthenticated `audit-system.php` debug script, which currently **fatals with a parse error at line 22**. The human-facing links are the admin mapping panels, which are documented in §7.5.

---

## 6. The Workforce → LearnDev movement that actually happened

### 6.1 Rows on disk (all created 2026-09-25)

| Table | Rows | Content |
|---|---|---|
| `ld_competency_requirement` | 4 (+1 deleted) | `source_module='workforce'`; skills *Data analysis* (IT DEPARTMENT, high, 12 people), *Cybersecurity awareness* (ADMIN STAFF, critical, 7), *Records management* (ADMIN STAFF, medium, 3), *Advanced Excel for payroll* (ADMIN STAFF, critical, 9); status `planned`/`received` |
| `ld_reporting_requirement` | 3 | *Q4 compliance training* 2026-10-01→12-31 @85%, *certification expiry* 2026-09-01→11-30 @90%, *training participation* 2026-09-01→12-31 @95% |
| `ld_training_engagement` | 1 | engagement reception row |
| `ld_turnover_risk` | 3 | `wa-turnover-TR-2001/2002`, `wa-turnover-probe-*` |
| `ld_cron_run` | 2 | scheduler bookkeeping for the run-due job |
| `ld_training_requests` | 6 total, **5 workforce-sourced** | ids 7,8,11 `source_module='workforce'`, `learner_id NULL`, `external_reference_id='competency:{1,2,3}'`; id 9 `learner_id=13`, `'turnover_risk:1'`, id 10 `learner_id NULL`, `'turnover_risk:2'`; id 12 is the only learner-raised row (`source_module='learning'`) |

### 6.2 The follow-up loop those rows express

```
Workforce writes a requirement/risk row (ld_competency_requirement / ld_turnover_risk / ld_reporting_requirement)
        ↓ (admin page, since removed)
LearnDev raises a training request: ld_training_requests (+ target_department, priority_level,
        external_reference_id = "competency:3" / "turnover_risk:1", learner_id NULL for team-level rows)
        ↓
pages/instructor/training-requests.php shows the queue → pages/instructor/ajax/manage-training-request.php
        sets instructor_id / instructor_note and accept|reject|complete
        ↓
pages/learner/ajax/request-training.php is the learner-raised equivalent (inserts with source_module defaulting to 'learning')
```

### 6.3 ⚠️ This half of the integration is not in the repo

Nothing in the live tree writes `source_module='workforce'` or `external_reference_id='competency:…'` (grep: **zero hits**). The only copies of that code live in the rollback snapshot:

```
.freebuff/rollback-20260925/untracked/modules/learning/
├── api/inbound/receive-{turnover-risk,competency-requirement,reporting-requirement,training-engagement}.php
├── api/outbound/get-workforce-integration-status.php
├── classes/{workforceintegration,workforceintegrationview,engagementsnapshot,cron}.php
├── cron/run-due.php
├── pages/admin/workforce-integration.php  +  pages/admin/ajax/workforce-integration-{create-request,run-cron,update-status}.php
├── pages/instructor/workforce-integration.php
└── sql/workforce_integration.sql          ← the only DDL for ld_competency_requirement, ld_reporting_requirement,
                                              ld_training_engagement, ld_turnover_risk, ld_training_requests, ld_cron_run
```

That SQL also records who was supposed to write the tables: `modules/workforce/pages/predicted-skill-competency-needs.php`, `…/implementation-guide.php`, `…/custom-reports.php` via `Employee::saveCompetencyRequirement()` / `saveLearningReportingRequirement()` — **none of those pages exist in this checkout**, though all three are present in the Workforce team's own build (zip, 2026-09-24; see §1) and, in that build, they write those tables directly (`classes/Employee.php` lines 237 and 271) with no API call and no notification. So the live database contains data produced by a *newer* Workforce module than the one in this repository, and the L&D page that turned those rows into training requests has been reverted.

**What survives:** the tables, the rows, and 8 `ld_integration_log` entries naming endpoints that no longer exist. **What broke:** the instructor queue still lists the workforce-sourced request id 9, but the four `learner_id IS NULL` rows (7, 8, 10, 11) are invisible — see §7.4.

---

## 7. How the exchange is **shown** on the LearnDev side

### 7.1 Role dashboards (own data only)

| Page | Shows | Reads across modules? |
|---|---|---|
| `pages/admin/analytics.php` | Total users (`COUNT(*) FROM em_employees`), learners, instructors, active courses, enrollments, completed, avg score, pending reports; top-10 courses table (enrolled/completed/avg score); 6-month enrollment bars; A–F grade distribution | `em_employees` for the headcount only |
| `pages/instructor/analytics.php` | Instructor-scoped courses/modules/learners/programs, completion + pass rate, quiz count; student leaderboard and **at-risk students** (`DATEDIFF(NOW(), last_accessed_at) > 14` or `AVG(score) < 60`) joined to `em_employees` for name/position; per-course engagement; grade distribution | `em_employees` for names/positions |
| `pages/learner/analytics.php` | Personal: enrollments, completed, avg score, pass rate, certificates, time spent, 28-day activity heatmap, per-skill coverage | none |

None of these three read `ld_engagement_snapshot`, `ld_skill_snapshot`, `wfa_*`, or `ld_integration_log`. The "at-risk" list on the instructor dashboard is computed live from enrolments — it is **not** the turnover-risk model that gets shipped to Workforce.

### 7.2 Skill gap (the L&D twin of `wfa_skill_gap_analysis`)

* `pages/learner/skill-gap.php` → protocol + `sg-request-btn` per gap skill → `pages/learner/ajax/request-training.php` → row in `ld_training_requests`.
* `pages/instructor/instructor-skill-gap.php` → `pages/instructor/ajax/get-instructor-skill-gap.php` (live query over `ld_skill` × `ld_course_skill` × `ld_course` × `ld_enrollment`).
* `cron/materialize-skill-snapshots.php` (daily) → `ld_skill_snapshot` (12 rows; last written 2026-09-05 by cron, per `cron/skill-snapshot.log`) → served outbound as `skill_snapshots`.
* `pages/learner/ajax/get-skill-snapshots.php` and `save-skill-snapshot.php` exist but **nothing calls them** (no JS/PHP caller found) — the cron is the only writer.

### 7.3 Certificates / compliance (the compliance feed's L&D view)

* `pages/instructor/certificate.php` (+ admin/instructor certificate AJAX) manages and lists certificates; the learner side shows issued certificates on `result.php`/`learner-home.php`.
* Expiring certificates (90-day window) are computed from `ld_certificate` both on-screen and outbound; no L&D page shows the compliance `status`/`expiry_date` projection from `get-compliance-training-log.php`.

### 7.4 Training requests queue (the only place Workforce-originated data reaches a screen)

`pages/instructor/training-requests.php`:

```sql
SELECT tr.*, CONCAT(emp.first_name,' ',emp.last_name) AS learner_name, emp.employee_code
FROM ld_training_requests tr
JOIN em_employees emp ON emp.employee_id = tr.learner_id      -- ⚠️ INNER JOIN
ORDER BY (status rank), tr.created_at DESC
```

Two defects, both visible in the live rows:

1. **INNER JOIN discards every row with `learner_id IS NULL`** — that is exactly the shape of Workforce-raised, department-level requests. Live effect: ids 7, 8, 10, 11 (all `source_module='workforce'`) never render; only id 9 (learner 13) does, and id 12 once a learner raises one.
2. **No `WHERE instructor_id = …`** — every instructor sees every request (`$instructorId` is fetched and then unused), so the queue is global, not personal. `manage-training-request.php` likewise looks requests up by id only.

### 7.5 Admin surfaces that make inbound flows work

* `pages/admin/settings.php` → **Recommendation Course Mappings** (15 rows live) and **Recognition Course Mappings** (9 rows live) — the admin-maintained lookup tables (`ld_recommendation_course_map`, `ld_recognition_course_map`) that `receive-appraisal-data.php` and `receive-recognition-eligibility.php` depend on. Copy on the page already explains each mapping in terms of the source module. (An extra 2 recognition rows use award-name categories: *Employee of the Month*, *Innovation Award*, *Customer Excellence*.)
* `pages/admin/user.php` → "Role & teaching assignments" panel (previous work) — unrelated to Workforce, but it is the other cross-module identity surface (`em_employees` → L&D role).
* `pages/admin/knowledge-transfer.php` + `learner/knowledge-transfer.php` — the KT paths counted in the `knowledge_transfer` block.

### 7.6 Not shown anywhere on the L&D side

| Data | Where it lives | Reader today |
|---|---|---|
| Appraisal recommendation trail | `ld_training_recommendation` | none (`audit-system.php` only, broken) |
| Job-test outcome | `ld_assessment_result` | none |
| Recognition unlock trail | `ld_recognition_unlock` | none |
| Turnover-risk / engagement model | `ld_engagement_snapshot` (21 rows) | outbound endpoint only |
| Per-learner skill snapshots (on screen) | `ld_skill_snapshot` | outbound endpoint only |
| Integration audit | `ld_integration_log` (36), `ld_integration_event` (11) | none |
| Workforce-originated requests with `learner_id IS NULL` | `ld_training_requests` | excluded by the INNER JOIN |

---

## 8. How the exchange is **shown** on the Workforce side

### 8.1 Today: it is not

In this checkout the dashboard page is an empty shell, there is no `wfa_*` reader, and the module cannot even render its sidebar (legacy `hrms_*` queries). **In the Workforce team's own build** the sidebar and eleven pages render (no `hrms_*` left), but they show only what Workforce itself wrote and read; nothing consumes the `get-workforce-analytics` feed, nothing reads `wfa_*`, and no notification path exists in either direction — the bell in that build is a hardcoded empty list item. A consumer that did exist would have to authenticate with LearnDev's key (§3.3), because its own key is rejected.

### 8.2 Where each block is supposed to be shown

The `get-workforce-analytics.php` docblock and `INTEGRATION_WORKFORCE_ANALYTICS.md` already agree on the mapping. Restated as a UI plan (all four `wfa_*` tables are currently empty, so pages must handle "no data yet" gracefully):

| Block from the endpoint | `wfa_*` table(s) | Suggested Workforce surface |
|---|---|---|
| `enrollment_stats`, `total_learners` | `wfa_reports`, `wfa_employee_metrics` | HR Dashboard Metrics cards: enrolled / in-progress / completed (+ fix the 62-vs-11 label first) |
| `completion_trend`, `top_courses` | `wfa_reports` | Custom HR report table + 12-month completion trend chart |
| `skill_gap_analysis`, `skill_snapshots` | `wfa_skill_gap_analysis` | Predictive Analytics: skills table with gap %, priority, department (`skill_snapshots` carries `department_name`/`position_name` for grouping) |
| `risk_summary.bands`, `risk_summary.at_risk_learners` | `wfa_risk_assessment`, `wfa_at_risk_employees_summary` | Attrition & Turnover Analysis: risk band donut + at-risk employee list with `risk_factors`, `active_days_30`, `expired_certificates` |
| `expiring_certificates`, `total_certificates` | `wfa_reports` | Certification expiry panel (90-day window) |
| `knowledge_transfer` | `wfa_reports` | Retention metric: active KT paths |
| `ld_competency_requirement`, `ld_reporting_requirement`, `ld_turnover_risk` (written **into** L&D tables) | *n/a — L&D tables* | The Workforce build lists what it sent back to itself: `Employee::getCompetencyRequirements()` / `getLearningReportingRequirements()` feed the send forms on `predicted-skill-competency-needs.php`, `implementation-guide.php` and `custom-reports.php`. What is missing is the other half — an **arrival signal on the L&D side**, which the START HERE section defines |

### 8.3 Minimum work to make it "shown"

1. Fix `modules/workforce/classes/Employee.php` to query `em_employees` / `em_departments` / `em_positions` (the shared identity every other module uses), or give Workforce its own copy of the L&D-style Employee class — otherwise no Workforce page renders at all.
2. Decide the consumption pattern (pull on schedule and persist into `wfa_*`, or read the endpoint live in the dashboard). Both are supported by the L&D side; the tables and the endpoint are ready.
3. Obtain the `learning-development` key (or change the endpoints to accept the `workforce-analytics` key — §3.3).
4. Add real page files under `modules/workforce/pages/` so the six dead nav links either exist or disappear.

---

## 9. End-to-end flows (as the data actually moves)

**Flow A — Performance appraisal → training.**
`pm_training_recommendations` → POST `receive-appraisal-data` (key `performance-management`) → validate 4 fields → `pm-rec-{id}` dedupe → if `Approved`: map `development_area` via `ld_recommendation_course_map` → `Enrollment::invite()` per course → insert `ld_training_recommendation` (status `invited`/`recommended`) → learner sees the invitation in **study.php → Invitations**; trainers see enrolments in course rosters. The recommendation trail itself is not surfaced.

**Flow B — Recruitment job test → learning path.**
test result → POST `receive-job-test-result` (key `recruitment`) → `lc-test-{employee}-{hash}` dedupe → always insert `ld_assessment_result` → if passed + path id: ordered `ld_learning_path_item` courses → invites. Visible only as invites today.

**Flow C — Recognition → reward learning.**
`eer_recognitions` → POST `receive-recognition-eligibility` (key `employee-engagement`) → `eer-rec-{id}` dedupe → if `approved`: `recognition_category` → courses → invites → insert `ld_recognition_unlock` (`unlocked`, or `redeemed` if the reward was consumed).

**Flow D — LearnDev → Workforce analytics (the bridge).**
`cron/materialize-engagement-snapshots.php` + `cron/materialize-skill-snapshots.php` (daily, last cron run 2026-09-05) build `ld_engagement_snapshot` / `ld_skill_snapshot` → `GET get-workforce-analytics` (key `learning-development`, filters ignored) → JSON of 10 blocks → *intended* `wfa_*` → *intended* Workforce dashboards. Today the loop stops at the JSON.

**Flow E — Workforce → LearnDev (direct table writes, currently headless).**
Workforce pages → `ld_competency_requirement` / `ld_reporting_requirement` / `ld_training_engagement` / `ld_turnover_risk` / `ld_cron_run` → admin page (removed) → `ld_training_requests` with `external_reference_id` pointers → instructor queue (rows with a learner only). Rows and DDL remain; the Workforce-side pages and the L&D conversion page do not.

**Side flow — attendance.** `ld_conference_attendance` (0 rows) → `get-attendance-data` → `ta_attendance` shape with `time_out = null`. Until attendance recording starts, Time & Attendance receives `count: 0` plus a note.

---

## 10. Gap & risk register

| # | Severity | Gap | Evidence | Suggested fix |
|---|---|---|---|---|
| G1 | High | Workforce module is a stub: 1 empty page, 6 dead nav links, zero `wfa_*` readers | §1.1–1.2, §8.1 | Build the dashboard, or delete the misleading nav entries until pages exist |
| G2 | High | Workforce `Employee.php` queries non-existent `hrms_employee`/`hrms_department`/`hrms_position` → fatal inside the shared sidebar | live PDOException 1146 | Switch to `em_employees`/`em_departments`/`em_positions` |
| G3 | High | All 5 outbound endpoints demand the `learning-development` key; the seeded `workforce-analytics` key is rejected (401) — contradicts `INTEGRATION_WORKFORCE_ANALYTICS.md` | live 200 vs 401 with both keys | Accept the consumer's own module key per endpoint (or re-issue/distribute deliberately) and correct the doc |
| G4 | High | No code writes `wfa_*`; all 20 tables 0 rows; "destination table" is a docblock promise | repo-wide grep = 0 writers | Pick a consumption pattern and implement one writer (cron or endpoint-transaction), then assert row counts in tests |
| G5 | Medium | `get-workforce-analytics` ignores `department_id`, `date_from`, `date_to` (logged only); `total_learners` counts enrollment rows (62) not learners (11) | §4.5, live payload | Bind the filters (or remove them from the docblock); compute `COUNT(DISTINCT learner_id)` |
| G6 | Medium | Snapshot freshness: endpoint silently serves `MAX(snapshot_date)`; cron last ran 2026-09-05 while 2026-09-25 rows exist from manual testing | `ld_engagement_snapshot` dates; cron logs | Include `snapshot_date` + age check in the payload; alert when stale |
| G7 | Medium | Attendance feed only covers video conferences and always sends `time_out = null`; `ld_conference_attendance` is empty | §4.3 | Add in-person session source or scope the contract to virtual sessions; compute `time_out` from `duration_minutes` |
| G8 | Medium | Inbound audit trails (`ld_training_recommendation`, `ld_assessment_result`, `ld_recognition_unlock`) have no UI — only a broken debug script reads them | §5, §7.6 | Add a read-only "Cross-module received" list for admins, or reuse them in the learner timeline |
| G9 | Medium | Instructor queue hides all `learner_id IS NULL` rows (the Workforce-raised shape) and is not scoped per instructor | live rows 7/8/10/11 | `LEFT JOIN` + branch for team-level rows; filter by `instructor_id` or show an unassigned bucket |
| G10 | Medium | Removed integration surface: 8 `ld_integration_log` rows and 6 tables whose DDL only exists in `.freebuff/rollback-20260925/` | §6.3 | Decide: restore the workforce-integration feature (move DDL into `rainz/sql/` + `pages/`), or formally deprecate the tables and note them in the schema docs |
| G11 | Medium | Unauthenticated debug scripts at the module root: `audit-system.php` (dumps every `ld_` table; **currently a parse error at line 22**) and `flowchart.php` | live HTTP 200 with PHP parse error | Move behind admin auth + `Page` routing or delete |
| G12 | Low | `ld_api_key` keys are plaintext, unscoped; no per-consumer rate limit; every outbound call is logged with the payload shape | `classes/apiauth.php` | Acceptable for the capstone; note it as a production-hardening item (hash keys, scope per endpoint) |
| G13 | High | **No arrival signal.** Workforce writes L&D tables directly (`classes/Employee.php:237,271` in their build) — no HTTP call, no `ld_integration_log`, no idempotency, and nothing that tells an L&D user a record arrived. `ld_notification` is the only store my bell reads, and no Workforce code has ever written it through the API path | START HERE W0–W3; their zip has zero `curl`/`X-API-Key` hits | Build the sender + the `receive-workforce-notification` endpoint (START HERE, Path A today / Path B as the integration) |
| G14 | Low | Workforce's own bell is a hardcoded empty stub, so nothing Workforce-side renders notifications even when rows exist for that user | their `includes/sidebar.php` (~lines 19–27) | Either render it from `ld_notification` for the session employee id, or delete the bell from their markup rather than showing a dead one |

---

## 11. Suggested sequencing (smallest useful milestones)

1. **Make Workforce render** (G2) — nothing else can be shown until the module loads.
2. **Agree the key** (G3) and have Workforce call `get-workforce-analytics` once, persisting into `wfa_reports` (G4) — proves the pipe end-to-end with a visible card on the Workforce dashboard.
3. **Fix the two payload defects** before anything is persisted (`total_learners`, ignored filters — G5), and stamp `snapshot_date` (G6).
4. **Expose what Workforce already sent** on the L&D side (G8, G9) so competency/risk requirements become visible work items again instead of hidden rows.
5. **Build the notification path** (G13): Learning stands up `receive-workforce-notification`; Workforce adds its sender and calls it from the four send pages — end state: a Workforce record arrives as a row in my bell the same minute.
6. **Decide the fate of the removed integration files** (G10) and retire or relocate the unauthenticated debug scripts (G11).

---

## 12. Verification appendix (reproducible)

```bash
# scope + live tree
git status --short ; find modules/workforce -type f
grep -rn "FROM wfa_" modules --include=*.php            # → 0 hits

# keys behave differently (see §3.3)
LD=$(mysql -uroot hrms -N -B -e "SELECT api_key FROM ld_api_key WHERE module_name='learning-development'")
WA=$(mysql -uroot hrms -N -B -e "SELECT api_key FROM ld_api_key WHERE module_name='workforce-analytics'")
curl -s -o /dev/null -w "%{http_code}\n" -H "X-API-Key: $LD" \
  "http://localhost/hrms-capstone/modules/learning/api/outbound/get-workforce-analytics.php"   # 200
curl -s -o /dev/null -w "%{http_code}\n" -H "X-API-Key: $WA" \
  "http://localhost/hrms-capstone/modules/learning/api/outbound/get-workforce-analytics.php"   # 401

# nothing has landed / table states
mysql -uroot hrms -e "SELECT COUNT(*) FROM pm_employee_training;  -- 0
                      SELECT COUNT(*) FROM ta_attendance;         -- 0
                      SELECT COUNT(*) FROM lc_trainings;          -- 8 = dump seed rows, not consumption
                      -- the endpoint itself: curl -s -H \"X-API-Key: $LD\" .../get-compliance-training-log.php → count: 0"
mysql -uroot hrms -e "SELECT COUNT(DISTINCT learner_id) AS learners, COUNT(*) AS enrollments FROM ld_enrollment"  # 11 / 62
mysql -uroot hrms -e "SELECT COUNT(*) FROM ld_engagement_snapshot"  # 21 (2026-09-05 + 2026-09-25)
mysql -uroot hrms -e "SELECT COUNT(*) FROM ld_skill_snapshot"       # 12 (last 2026-09-05)

# the de-facto Workforce → L&D path
mysql -uroot hrms -e "SELECT * FROM ld_competency_requirement"      # 4 rows, source_module='workforce'
mysql -uroot hrms -e "SELECT * FROM ld_reporting_requirement"       # 3 rows
mysql -uroot hrms -e "SELECT id,learner_id,source_module,external_reference_id FROM ld_training_requests"
mysql -uroot hrms -e "SELECT direction,module_name,endpoint,status FROM ld_integration_log ORDER BY id DESC LIMIT 12"
```

```bash
# the notification path (START HERE) — what I will see once it is wired
mysql -uroot hrms -e "SHOW COLUMNS FROM ld_notification"          # the 9 columns listed in START HERE W1
mysql -uroot hrms -e "SELECT COUNT(*) FROM ld_notification"        # 28 at audit time
mysql -uroot hrms -e "SELECT DISTINCT employee_id FROM user_account WHERE account_status='Active' AND role_id IN (1,7)"   # the audience: 35, 67999, 99967
mysql -uroot hrms -e "SELECT api_key FROM ld_api_key WHERE module_name='workforce-analytics'"  # the key the sender uses
mysql -uroot hrms -e "SELECT id,type,reference_type,reference_id FROM ld_notification ORDER BY id DESC LIMIT 5"   # newest rows, newest-first
```

Observed at audit time: 36 `ld_integration_log` rows, 11 `ld_integration_event` rows, 8 active `ld_api_key` rows, `ld_conference_attendance` 0 rows, 2 compliance-tagged courses, instructor queue INNER JOIN dropping 4 live rows. Re-read 2026-09-26 for the START HERE section: `ld_notification` 28 rows, `ld_integration_log` 37, `ld_integration_event` 11, `ld_competency_requirement` 4, `ld_reporting_requirement` 3, `ld_turnover_risk` 3, `ld_training_engagement` 1, `ld_training_requests` 6 (5 workforce-sourced), `ld_engagement_snapshot` 21.

---

## 13. Related documents

| Document | Relationship |
|---|---|
| `integration mds/INTEGRATION_WORKFORCE_ANALYTICS.md` | Prior contract. Correct in shape; **wrong on the key (§3.3)** and now understated — the endpoint exists and answers |
| `integration mds/ld-tables-for-workforce-analytics.md` | Table-level feed map; its "endpoint does not exist yet" statement is outdated, and it predates `ld_skill_snapshot` being cron-populated |
| `integration mds/ld-tables-gaps-improvements.md` | Gap 7/9 aggregates that `Analytics` and the snapshots now implement (`getEnrollmentsByDepartment()`, `getRiskSummary()`, `getCertificationExpiryOverview()`, `getSkillGapOverview()`) |
| `integration mds/CROSS_MODULE_INTEGRATION.md`, `CROSS_MODULE_GAPS.md` | Master cross-module index; the flows in §9 are the live implementation of those flows |
| `documention/api-integration-plan.md` | Original plan for the endpoint set; the live tree implements 9 of the planned files |
| `.freebuff/rollback-20260925/untracked/modules/learning/**` | The **only** copy of the removed Workforce-integration endpoints, pages, `classes/cron.php`, `classes/engagementsnapshot.php` and `sql/workforce_integration.sql` (§6.3) |
| `workforce-20260925T051118Z-1-001.zip` (supplied 2026-09-26) | The Workforce team's own build — the source for §START HERE W0/W6 and the corrections in §1, §6.3, §8.1 |
| `hrmss workforce.sql` (supplied 2026-09-26) | The shared schema dump (2026-09-21): `ld_notification`, the four `ld_*` destination tables, `user_account`, `ld_api_key`, all `wfa_*` |
| `rainz/sql/ld_instructor.sql`, `documention/instructor-designation.md` | Prior work; unrelated to data exchange but shares the `em_employees.employee_id` identity rule |

**Identity rule repeated once, because every join in this audit depends on it:** `ld_enrollment.learner_id` = `ld_grade.learner_id` = `ld_certificate.learner_id` = `ld_skill_snapshot.learner_id` = `em_employees.employee_id`, and L&D role/teaching state hangs off the same id, never off a position or department string.
