# Integration work plan — Workforce ↔ LearnDev turnaround

**To: the Workforce module classmate. From: the LearnDev (L&D) classmate. Acted out with the source of truth open on 2026-09-26.**

This doc is two plans, one per side, plus a single combined definition of done. It is pinned to the exact state of the code and database as they are right now, with the file:line and table references spelled out so nothing is a vague "they change this".

## Reading first: what exists on each side is different from what the other thinks

**The warehouse of truth is one database — `hrms` — shared by both modules.** There is only one `ld_api_key`, one `ld_notification`, one `ld_integration_log`, one `ld_integration_event`, one set of `ld_*` destination tables. Both modules connect to the same Database class (`database/db.php`) and the same credentials (root, no password).

That fact is easy to miss and it is the reason three of the bugs below are silent. Two modules landing in the same column, two id spaces that look the same, one key that is both "active" and "rejected" — all of these are the same database seen through two different lenses.

**What is in the repo right now (the checklist you can verify with `ls` and `grep`):**

| Side | What is committed, today | What is committed elsewhere |
|---|---|---|
| `modules/learning` (L&D) | 5 outbound endpoints, 4 inbound endpoints, `classes/apiauth.php`, `classes/integrationlog.php`, `includes/Notification.php`, `classes/notificationlinks.php`, per-role notification pages + mark-read, bell-links.js, the bell, the bell's `idx_notification_user (user_id, is_read)` index, all of which are **live and wired** | the notification *sender* endpoint (`receive-workforce-notification.php`) is **not** in the repo — it is on the To Do list for the L&D side below |
| `modules/workforce` (this repo) | a one-page stub (`pages/dashboard-overview.php` empty, 6 dead nav links), `classes/Employee.php` that still queries `hrms_employee`/`hrms_department`/`hrms_position` (those tables do not exist), no api/, no notification sender, no Mode enum | the **Workforce team's own build** (the supplied `workforce-20260925T051118Z-1-001.zip`) is rebased on a newer `Employee.php` with 11 real pages, correct `em_*` queries, but it still writes to `ld_*` tables directly with no API call and no notification |

The instruction set for each side will say "the committed state" and "the supplied build state" separately where the two differ, because they do differ, and the work requested is "close the gap" — which means the committed state moves toward the supplied build for Workforce, and toward delivery for L&D.

## What "close the gap" means for the Workforce classmate

The gap on the Workforce side is three-fold:
1. **No arrival signal.** Their build writes to `ld_competency_requirement`, `ld_reporting_requirement`, `ld_training_engagement`, `ld_turnover_risk` directly (classes/Employee.php lines 237 and 271 in the supplied build, and the same functions are stubs in the repo). That inserts rows. But it produces nothing LearDev can see as an arrival — no `ld_integration_log`, no idempotency, no `ld_notification`. Today, a row is posted with no "I sent it" in any inbox on the L&D side.
2. **No real payload surface.** The L&D side waits for an HTTP call with a key. Workforce has posted nothing like that — no `curl`, no `X-API-Key`, no `api/` call anywhere in the supplied build.
3. **No side that shows the result to its own users.** Their bell is a hardcoded empty `<li>`. Their modules render notifications nowhere. And the `Training requests` queue they should act on is on the L&D side.

What closing the gap looks like, from the Workforce side, in priority order:

1. **Stop writing to L&D tables as a direct insert as the primary integration path, or at least stop treating it as the completion of the send.** Write it so that a row in `ld_*` is accompanied by the arrival-blame record — an `ld_integration_log` entry with direction `inbound`, module_name `workforce-analytics`, the endpoint name, the payload, a status — and an entry in `ld_integration_event` keyed on their own record id, so that a resend is detected and a single send is idempotent. This is the "one call, one audit row" rule both of us rely on; today anything else is silent.

2. **Pick one send destination and send it properly over HTTP.** The document the L&D classmate has already written — "START HERE" in `rainz/updated learndev and workforce md integration.md` — defines exactly one new receiver endpoint they will deliver:

   ```
   POST http://localhost/hrms-capstone/modules/learning/api/inbound/receive-workforce-notification.php
   Content-Type: application/json
   X-API-Key: <the active workforce-analytics key from ld_api_key, id 6, 67 chars, prefix wa_f>
   ```

   There is no way around that server-side contract for the notification path, because that is exactly what the L&D bell reads. The key is live and active — you can read it out of the shared DB yourself (`SELECT api_key FROM ld_api_key WHERE module_name='workforce-analytics'`) and confirm it against the header that Learning accepts. To apply and test, the id centers on the fact L&D already works: `ld_api_key` id 6 active, `ld_notification` 28 rows live, `ld_integration_log` + `ld_integration_event` live with 4 inbound endpoints that have already given us the confirmation pattern.

3. **Send through the path, not just store the row.** Each send carries a notification payload the L&D side stores under its own rules:
   - a notification_id 1–120 chars, unique per event, in the envelope as `wa-notif-<your id>` so that the L&D-side endpoint can collapse a re-send
   - a type from the allowed list (`workforce_competency_requirement`, `workforce_turnover_risk`, `workforce_reporting_requirement`, `workforce_training_engagement`, `workforce_alert`)
   - title ≤ 255, message ≤ 2000, both plain text — rendered escaped on the L&D side, so HTML will show as literal text
   - optional reference_type (the L&D table the record lands in) and reference_id (the id of that record on the L&D side when known)
   - audience `ld_staff` (the L&D recipient list, resolved the way L&D does it) or `explicit` with a list of employee ids
   - optional `data` block with the exact fields per domain type — those fields and their column names and enums are in the START HERE spec and are the same field names their own pages already collect, verified against the live `SHOW COLUMNS` of each `ld_*` table

   The sender on the Workforce side should be one helper class — for example `classes/LearningNotify.php` with an `announce()` method — so the four send pages do not each invent their own curl logic and header handling.

4. **Call it after the insert or instead of the direct insert, in the same request.** The spec says do not claim delivery to the user when the send silently failed — only claim `$sent > 0`. And do not write from a page-load path; one event, one call, with a retry policy for timeouts and 5xx using the same notification_id and a backoff (1s, 4s, 16s, max 3 attempts), and never retry on 4xx.

5. **Fix the send pages so the message they show is honest, and fix the bugs already in those pages.** The supplied build has real send pages — `predicted-skill-competency-needs.php`, `implementation-guide.php`, `custom-reports.php`, `turnover-risk-indicator.php` — but:
   - `custom-reports.php` calls `Employee::saveLearningReportingRequirement()` with no `reporting_frequency`, while the class reads `$input['reporting_frequency']` into a `NOT NULL` enum column (`weekly`/`monthly`/`quarterly`/`annual`). That mismatch must be closed — pass one or default to `'monthly'`.
   - `turnover-risk-indicator.php` says its own form is "this form is for planning only. No data is sent or saved." If turnover risk is supposed to reach L&D as the notification path, that form's POST handler needs to actually send it — either `ld_turnover_risk` plus the notification, or the Path B `data` variant. Right now it is the form that looked like it sent something and didn't.

6. **Close the recipient id mistake for the team, before it happens.** `ld_notification.user_id` is `em_employees.employee_id`, not `user_account.user_id`. The recipient list on the L&D side is `user_account WHERE role_id IN (1,7)` — that is the active L&D staff ids today: employee 35 (the real L&D staff), 67999, 99967. Do not send to `1018` (no `em_employees` row; two such rows already sit unreadable in the pre-cleanup dump), and do not send any id you have not confirmed. The easiest place for the team to settle this on their own code is to print the recipient list once and confirm it matches what they expect, and to never use `1018` again.

7. **Wire their own side to see notifications, or stop pretending the bell is a notification bell.** Their `includes/sidebar.php` currently has `<li class="notif-item"></li>` and never queries anything. Either render it from the same `ld_notification` store they share with L&D (with `WHERE user_id = that session employee_id`, the same id space that logs them in), or drop the bell and keep the authoritative inbox on the L&D side. Whichever they choose, "a bell that never shows anything my module sent" is a real gap — and the shared display is `ld_notification` + `includes/Notification.php` + the bell + the link script, all already wired on the L&D side.

8. **Hard-code nothing.** Their `includes/header.php` and nav links currently hard-code `/hrms-capstone/…`. Put the app base URL in one config constant and build both nav hrefs and API endpoint URLs from it; hard-coded paths break on any other deployment and the L&D endpoint contract expects the standard path shape.

9. **Send from the pages that actually send, not from visitors' page-load code.** The send is one event: after `saveCompetencyRequirement()` or `saveLearningReportingRequirement()`, call the notify helper in that request; do not call it on every page load.

## What closing the gap means for the LearnDev classmate

The L&D side already owns the arrival surface and the receiver pattern; the gaps here are the finish line that closes those Workforce-sourced signals into something visible and acted-on. In priorities:

1. **Build the receiver endpoint for workforce notifications.** Deliver `POST /modules/learning/api/inbound/receive-workforce-notification.php` exactly to the START HERE W3 contract — an idempotent authenticated receiver that inserts the domain row when present, inserts one `ld_notification` row per resolved recipient with `user_id` = `em_employees.employee_id`, writes `ld_integration_log` and `ld_integration_event`, and returns the documented 200/401/400/405/422/500 shapes. It must not exist only on paper: the Workforce classmate needs something live to call.

2. **Close the "no reader" gap for the tables Workforce already wrote into.** Today, `ld_competency_requirement`, `ld_reporting_requirement`, `ld_turnover_risk`, and `ld_training_engagement` have rows (4, 3, 3, 1 respectively) and `ld_training_requests` has 6 (5 workforce-sourced), but no L&D page reads `ld_competency_requirement`, `ld_turnover_risk`, or `ld_reporting_requirement`. The only place some of those data shapes show up is the instructor training-requests queue — and there the INNER JOIN hides the workforce-shaped rows. So bring them back as visible work items: a read-only "cross-module received" list for L&D admins, or a dedicated workforce-integration page that lists the competency/risk/reporting/engagement records with the training-request pointer (`external_reference_id`), and a queue that does not drop `learner_id IS NULL` rows.

3. **Fix the instructor training-requests queue so the workforce rows are visible and personal.** Replace the INNER JOIN with a LEFT JOIN so `learner_id IS NULL` department-level requests render, and add the per-instructor scoping the `instructor_id` column already supports (today `$instructorId` is fetched and then unused). That is the single screen where the workforce-sourced data already has a visible place, and it currently loses it.

4. **Fix the outbound feed quality before Workforce consumes it**, because the consumer-facing bridge is what Workforce wants and what the old contract tells them to call and currently fails on. The two defects the doc has already called out are live and verified: `total_learners` = 62 (a count of enrollment rows) vs 11 distinct learners; and `department_id`/`date_from`/`date_to` are ignored (a `department_id=999&date_from=1900-01-01` call returns a byte-identical payload). Fix `total_learners` to `COUNT(DISTINCT learner_id)`, bind the filters into the queries that actually take them, and stamp the top-level `snapshot_date` into the payload so "current risk" is not silently ~3 weeks old. The snapshot freshness note is also real: `ld_engagement_snapshot` has a 2026-09-25 snapshot from manual testing but the cron's last log line is 2026-09-05.

5. **Close the auth contract so the consumer's own key works.** Every outbound endpoint currently demands `learning-development`. The Workforce key (id 6, `workforce-analytics`, active, 67 chars) is rejected with **401** live against `get-workforce-analytics.php`. Either change each outbound endpoint to accept its consumer's own module key (so Workforce gets its own key to work, and PM/recruitment/engagement/employee-management get theirs where relevant), or hand Workforce L&D's key and rotate it deliberately and re-issue/consent it. Whichever way, the old contract document (`INTEGRATION_WORKFORCE_ANALYTICS.md`) must be corrected — it still tells Workforce to use the `wa_` key as if it works.

6. **Close the key for the inbound modules that need their own keys too, where relevant.** Today the four inbound endpoints correctly name their own caller (`performance-management`, `recruitment`, `employee-engagement`, `employee-management`). That part of the auth plumbing is sound and should stay that way; the work is only on the outbound side where the consumer is told to use one key and rejected for another.

7. **Wire the feedback loop that turns Workforce sends into instructed follow-up.** The earlier (rollback-only) L&D page `workforce-integration.php` turned `ld_competency_requirement`/`ld_turnover_risk`/`ld_reporting_requirement`/`ld_training_engagement` rows into `ld_training_requests` with `external_reference_id` pointers like `competency:3` / `turnover_risk:1`. That is the link between "Workforce predicted a skill need" and "an instructor can act on it in the queue". If the Workforce side decides to stop direct inserts and use the notification path, that conversion step still needs a place — either restored or re-implemented — so a competency need becomes an actionable training request rather than a silent table row.

8. **Give the `ld_*` destination side an owner decision for `wfa_*`.** The 20 `wfa_*` tables exist and are empty, and nothing writes them. Either the workforce analytics bridge persists into one or more of them (so Workforce has its own copy of the L&D data on its own tables), or the bridge stays live-read, or the tables are formally retired and noted. Today it is neither: the payload is produced, the endpoint exists, the `wfa_` tables exist, and none of it moves.

9. **Keep the notification display honest across the shared bell and the per-role notification pages.** The bell reads 6 most recent, the notification pages read 50, the type is not filtered on the notification page (any type you send is listed), and the bell-links.js route only works inside the learning module (the sidebar bell prints the same rows as plain text everywhere else). If the Workforce row types land there and you want them to lead to a real triage page, add a one-line mapping in `NotificationLinks::PAGES` — but the notification itself is always visible. Do not assume a workforce type opens a workforce page on my side; it opens the notification list unless mapped.

10. **Keep the "do not" list as part of the contract, not just the doc.** The recipient id space is `em_employees.employee_id`; never send to `1018`; do not do Path A and Path B for the same event; do not write notifications from page-load code; do not send HTML; do not exceed column widths; do not hard-code the API key; do not let a notification failure fail the user's save; wrap the sender so a silent notification cannot undo a real send. These are the ways the notification path goes wrong, and they are verified facts from the live table and the live readers, not style preferences.

## The single combined definition of done

| What "done" means | Where it shows up | Proof, run by both of us |
|---|---|---|
| Workforce sends a notification that L&D stores | `ld_notification` shows one row per recipient, `is_read = 0`, `type` from the allowed list | `SELECT id, user_id, type, is_read, created_at FROM ld_notification WHERE reference_type = ... ORDER BY id DESC LIMIT 10` — rows for employee 35 / 67999 / 99967 |
| Workforce tracks its send | `ld_integration_log` has `inbound / workforce-analytics / receive-workforce-notification / success` with the payload, plus an `INSERT IGNORE` event for the `notification_id` | `SELECT direction, module_name, endpoint, status, created_at FROM ld_integration_log WHERE module_name='workforce-analytics' ORDER BY id DESC LIMIT 5` |
| Workforce resends are idempotent | re-send the same notification_id returns 200 `{"success":true,"duplicate":true}` and **no new rows** | curl a second time; check `COUNT(*) FROM ld_notification` did not grow; check the log has no second success row |
| Wrong key is rejected, right key works | Workforce's own `wa_` key → **401**; L&D's `ld_` key → confirms (this is the current live state) — and, once fixed, each outbound endpoint accepts its consumer's own module key | curl two ways; note L&D key vs workforce key per endpoint |
| Workforce sends and L&D sees it in the bell and the notification page | employee 35 opens the learning module sidebar bell and `?page=admin/notification` and sees the row, newest first; click marks it read | live open on employee 35; bell badge increments; the row is in the 50 most recent on the notification page |
| The workforce records become actionable | the competency/risk/reporting/engagement records show in an L&D reader, and the instructor queue shows the NULL-learner workforce rows | a page that reads `ld_competency_requirement / ld_turnover_risk / ld_reporting_requirement / ld_training_engagement`, plus the queue's LEFT JOIN |
| The bridge is usable by Workforce | Workforce can call the outbound feed once with its own key and get a real payload, and the `total_learners` and filter defects are fixed | curl `get-workforce-analytics` with the workforce-analytics key (or the approved consumer key) and read `total_learners` vs `COUNT(DISTINCT learner_id)` and confirm the filters change the payload |
| The whole thing is undoable by silent writes | every send is tracked by `ld_integration_log`/`ld_integration_event`; direct-table-only writes are replaced by the sender path (or at least accompanied by the log row) | row exists in `ld_integration_log` for each notification; no `ld_*` write that was not accompanied by that |

## How each side confirms its own part before calling the other one done

**Workforce colleague first confirms:**
- their `Employee.php` no longer writes to `ld_*` tables as the completion of a send without also writing the arrival record, or at least no longer treats the direct insert as the end of the send
- their `LearningNotify` helper (or equivalent) exists, resolves the recipient list the way L&D does, and does not use `user_account.user_id` as the notification recipient
- from each send page the side-effect is one HTTP call with the correct type, title, message, reference_type/reference_id, and audience, using the `workforce-analytics` key
- the send page message is honest and only claims delivery when the send succeeded
- `custom-reports.php` closes its `reporting_frequency` mismatch
- `turnover-risk-indicator.php` either sends or stops pretending it sends
- their own bell no longer announces visibility it cannot deliver, or they have wired it to `ld_notification` for the session employee id
- nothing hard-codes `/hrms-capstone/…` anymore

**LearnDev classmate first confirms:**
- `POST /modules/learning/api/inbound/receive-workforce-notification.php` is live and returns 200 with the documented shape when called with the `workforce-analytics` key and a controlled payload, and returns **404** only because it is undelivered before that, and **401** for any key that is wrong
- the domain tables Workforce writes into (`ld_competency_requirement`, `ld_turnover_risk`, `ld_reporting_requirement`, `ld_training_engagement`) have an L&D-side reader — either a dedicated page or the cross-module received list
- the instructor training-requests queue shows the NULL-learner workforce rows and is scoped per instructor
- the outbound feed defects (`total_learners`, ignored filters, missing top-level `snapshot_date`) are fixed
- each outbound endpoint accepts its consumer's own key — OR the consumer is given the L&D key and the old contract doc is corrected, and the consumer's own key is either issued/distributed deliberately
- the `wfa_*` tables are either being populated by the bridge, or the approach is agreed and noted
- the Workforce inbound endpoints that resume (the four removed ones, plus the notification endpoint) all land with log + idempotency, not silent table writes

## One honest sentence to put in a shared project note

The one database both modules share is the reason the bugs are silent (same column, two id spaces that look the same, a key that is both active and rejected by the wrong endpoint). The work is not "add notifications to workforce"; the work is "make the workforce send land in a place both sides can see and prove, with the identity, key, log, and idempotency that already exist on the L&D side, and with a receiver that the workforce side can call."

## Where to read more, by who did what already

**For the Workforce classmate reading this plan first:** read the START HERE section in the doc the L&D classmate already wrote (`modules/learning/rainz/updated learndev and workforce md integration.md`, section `## ▶ START HERE — instruction for the Workforce team`). It contains the exact endpoint URL, the exact `data` fields per type with the real column names and enums, the exact recipient-list SQL, the exact response codes, and the exact definition-of-done SQL both of you already quote. Everything here is a reordering and a pairing of what is already in that START HERE section, plus the live numbers above.

**For the LearnDev classmate reading this plan first:** the START HERE section and this plan agree on the same endpoint. Your side's remaining blocks are (1) build the receiver, (2) give the workforce destination tables a reader, (3) fix the queue, (4) fix the outbound feed, (5) fix the auth for the consumer's own key, (6) decide the activation/conversion step so a workforce prediction becomes a training request, (7) decide `wfa_*`, (8) keep the display mapping honest.

A reminder of the live numbers this plan is pinned to: `ld_competency_requirement` 4, `ld_reporting_requirement` 3, `ld_training_engagement` 1, `ld_turnover_risk` 3, `ld_training_requests` 6 (5 workforce-sourced), `ld_notification` 28, `ld_integration_log` 43, `ld_integration_event` 11, `ld_engagement_snapshot` 21, `wfa_*` 20 tables and 0 rows, `ld_api_key` 8 active, `get-workforce-analytics` `total_learners` 62 vs 11, and the live 401 when the `workforce-analytics` key is used against the outbound endpoints.

## What to do next, in order, so nothing is duplicated

1. Both of you open the same START HERE section and agree it is the contract for the notification path — that is the piece the plans above both point back to.
2. The Workforce classmate delivers the helper class and the call sites first (so their side has a sender even while the L&D receiver is being built). Path A proves the destination, Path B proves the integration.
3. In parallel, the LearnDev classmate builds `receive-workforce-notification.php` to that contract.
4. Once both are live, the two of you run the definition-of-done checklist together, with the curl and SQL above, as a shared handoff — not as separate claims.
5. After that, close the service gaps in priority: reader for the workforce destination tables, queue fix, outbound feed fix, outbound key fix, activation/conversion step, and `wfa_*` owner decision.
6. Correct the old contract doc (`INTEGRATION_WORKFORCE_ANALYTICS.md`) as soon as the key way is decided, so the document no longer tells Workforce to use a key that returns 401.

If either of you wants the exact curl and SQL to reproduce any of these right now, the START HERE section already has them — this note only re-orders and pairs them by who owns what.
