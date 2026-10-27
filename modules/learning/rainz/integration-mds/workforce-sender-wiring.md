# Workforce → Learning notification sender — handoff

**This file is self-contained: it is the one document to send to the Workforce team.** It
contains the exact code to add, where to add it, the receiver contract it talks to, and how
to verify it. Everything below was executed against the live `hrms` database and the
running app on 2026-09-26; the row ids in §6 are from those runs.

Audience: the Workforce module developer. L&D does not patch your files — you apply the
four changes below in your own checkout.

---

## 0. What is waiting on the L&D side

The receiver endpoint is **live** (it did not exist before):

```
POST  http://localhost/hrms-capstone/modules/learning/api/inbound/receive-workforce-notification.php
Content-Type: application/json
X-API-Key: <ld_api_key.api_key WHERE module_name = 'workforce-analytics'>
```

Body: `notification_id` (1–120 chars, unique per event), `type`, `title` (≤255),
`message` (≤2000), `audience` (`ld_staff` or `explicit` + `recipients`), optional
`reference_type` / `reference_id` / `data`. Allowed types:
`workforce_competency_requirement`, `workforce_turnover_risk`,
`workforce_reporting_requirement`, `workforce_training_engagement`, `workforce_alert`.
Responses: `200` stored · `200 {"duplicate":true}` on a re-sent id · `400` / `401` / `405` /
`422` / `500` as documented in the START HERE section. A re-sent `notification_id` writes
nothing.

There are therefore **two ways to send** and you must pick **one per event**, never both:

* **Path A — `LearningNotify::announce()`** (this document): shares the database, works
  today, no HTTP client, no key handling. Use it unless you want the audit trail.
* **Path B — the endpoint above**: adds `ld_integration_log` auditing and idempotency, and
  decouples you from L&D's tables. Retry only on timeout/5xx, same `notification_id`, back
  off 1s/4s/16s, never retry 4xx.

## 1. Add `classes/LearningNotify.php`

```php
<?php
/**
 * LearningNotify — the one place that turns a Workforce event into a bell row on the
 * Learning & Development side.
 *
 * Path A of the integration contract: both modules share one database, so a Workforce
 * save can write the same `ld_notification` table the L&D bell reads. If you instead
 * POST to the receiver endpoint (Path B), do not also call this for the same event.
 *
 * Rules this class exists to keep:
 *   - the recipient is `em_employees.employee_id`, never `user_account.user_id`;
 *   - only the five types in TYPES are announced;
 *   - the audience is the active System Administrator / L&D Staff role list;
 *   - nothing here throws — a failed notification must never undo the user's save.
 *
 * Call it after the domain insert, in the same request, from the event handler only
 * (never from page-load code). `0` means nothing was sent: say so, do not claim delivery.
 */
final class LearningNotify
{
    /** The notification types the L&D receiver accepts. */
    public const TYPES = [
        'workforce_competency_requirement',
        'workforce_turnover_risk',
        'workforce_reporting_requirement',
        'workforce_training_engagement',
        'workforce_alert',
    ];

    /** Domain type → the L&D table its record lands in. */
    public const REFERENCE_TABLES = [
        'workforce_competency_requirement' => 'ld_competency_requirement',
        'workforce_turnover_risk'          => 'ld_turnover_risk',
        'workforce_reporting_requirement'  => 'ld_reporting_requirement',
        'workforce_training_engagement'    => 'ld_training_engagement',
    ];

    /**
     * The L&D people a Workforce signal has to reach: `user_account` carries the role,
     * and `ld_notification` wants the *employee* id, so this selects employee_id —
     * role 1 = System Administrator, role 7 = Learning and Development Staff.
     *
     * @return list<int> em_employees.employee_id values
     */
    public static function audience(PDO $pdo): array
    {
        $stmt = $pdo->query(
            "SELECT DISTINCT employee_id
               FROM user_account
              WHERE account_status = 'Active' AND role_id IN (1, 7)
              ORDER BY employee_id"
        );

        return array_values(array_unique(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    }

    /**
     * One notification per recipient. Never throws: telling the L&D side must not break
     * the save the user actually asked for.
     *
     * @param string      $type          one of TYPES; anything else is refused
     * @param string      $title         plain text, truncated to 255
     * @param string      $message       plain text, truncated to 2000
     * @param string|null $referenceType L&D table the record lives in (defaults per TYPES)
     * @param int|null    $referenceId   that record's id
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
        if (!in_array($type, self::TYPES, true)) {
            error_log('[workforce] Learning notification refused: type not allowed: ' . $type);

            return 0;
        }

        $referenceType = $referenceType ?? (self::REFERENCE_TABLES[$type] ?? null);
        $sent = 0;

        try {
            require_once dirname(__DIR__, 3) . '/includes/Notification.php';
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

`audience()` resolves to the shared database's active role 1/7 accounts. Today that is
employee ids **35, 67999, 99967** — the same three the L&D bell reads.

## 2. `classes/Employee.php` — make `reporting_frequency` safe

`custom-reports.php` calls `saveLearningReportingRequirement()` without a
`reporting_frequency`, and the method binds the missing key straight into a `NOT NULL`
enum — that call currently fails. One line fixes it:

```php
// old
':reporting_frequency' => $input['reporting_frequency'],

// new
':reporting_frequency' => in_array($input['reporting_frequency'] ?? '', ['weekly', 'monthly', 'quarterly', 'annual'], true) ? $input['reporting_frequency'] : 'monthly',
```

## 3. Call sites — one announce per save, inside the POST handler

### 3.1 `pages/predicted-skill-competency-needs.php`

Add the require next to the page's first line:

```php
$employeeClass = $employeeClass ?? new Employee();
require_once dirname(__DIR__) . '/classes/LearningNotify.php';
```

and the announce call immediately after the `]);` that closes `saveCompetencyRequirement()`:

```php
            ]);

            // One event, one call: announce from this POST handler only, after the row exists.
            $sent = LearningNotify::announce(
                (new Database())->getConnection(),
                'workforce_competency_requirement',
                'New competency requirement',
                sprintf(
                    'Workforce Analytics predicted "%s" for %s (%d learner(s) need training).',
                    $skillName,
                    $department,
                    (int) $employeesNeeded
                ),
                'ld_competency_requirement',
                $requirementId
            );
            $competencyMessage = $sent > 0
                ? "Competency requirement #{$requirementId} was sent to the Learning system and {$sent} L&D staff were notified."
                : "Competency requirement #{$requirementId} was saved in the Learning system, but the L&D notification could not be delivered.";
```

### 3.2 `pages/implementation-guide.php`

```php
$employeeClass = $employeeClass ?? new Employee();
require_once dirname(__DIR__) . '/classes/LearningNotify.php';
```

```php
            ]);

            // One event, one call: announce from this POST handler only, after the row exists.
            $sent = LearningNotify::announce(
                (new Database())->getConnection(),
                'workforce_reporting_requirement',
                'New learning reporting requirement',
                sprintf(
                    'Workforce Analytics set a reporting requirement "%s" for %s to %s.',
                    $target,
                    $periodStart,
                    $periodEnd
                ),
                'ld_reporting_requirement',
                $id
            );
            $message = $sent > 0
                ? "Reporting requirement #{$id} was sent to the Learning system and {$sent} L&D staff were notified."
                : "Reporting requirement #{$id} was saved in the Learning system, but the L&D notification could not be delivered.";
```

### 3.3 `pages/custom-reports.php`

Add the require right after the `analytics-setup.php` include, then:

```php
            ]);

            // One event, one call: announce from this POST handler only, after the row exists.
            $sent = LearningNotify::announce(
                (new Database())->getConnection(),
                'workforce_reporting_requirement',
                'New learning reporting requirement',
                sprintf(
                    'Workforce Analytics set a reporting requirement "%s" for %s to %s.',
                    $targetName,
                    $periodStart,
                    $periodEnd
                ),
                'ld_reporting_requirement',
                $requirementId
            );
            $reportingMessage = $sent > 0
                ? "Reporting requirement #{$requirementId} was sent to the Learning system and {$sent} L&D staff were notified."
                : "Reporting requirement #{$requirementId} was saved in the Learning system, but the L&D notification could not be delivered.";
```

## 4. Rules these edits encode

* **Inside the POST branch, after the insert.** Never announce from page-load code — one
  event, one call.
* **`$sent > 0` decides the page message.** A direct insert is not delivery; when the
  notification cannot be sent, say the row was saved but L&D was not notified.
* **Recipients are `em_employees.employee_id`** — never `user_account.user_id` (those are
  different id spaces; the wrong id produces a row nobody can open).
* **Wrap the call** so a notification failure cannot undo the save — `announce()` already
  catches everything and returns 0.
* **Do not do Path A and Path B for the same event** — you would create two rows.

## 5. How to verify in your checkout

After a real form submit, as the L&D side would see it:

```sql
-- one row per recipient, newest first
SELECT id, user_id, type, title, reference_type, reference_id, is_read, created_at
FROM ld_notification WHERE type = 'workforce_competency_requirement'
ORDER BY id DESC LIMIT 5;

-- your record itself (added once, marked received)
SELECT id, source_module, skill_name, target_department, priority_level, status, created_at
FROM ld_competency_requirement ORDER BY id DESC LIMIT 3;
```

A working submit on the L&D side is: one domain row + three `ld_notification` rows
(user_id 35, 67999, 99967, `is_read = 0`) + the page message saying how many staff were
notified.

## 6. What was proven against this exact code (2026-09-26)

| Handler | Domain row | Bell rows | Page message |
|---|---|---|---|
| `predicted-skill-competency-needs.php` | `ld_competency_requirement` #6 | 3 → ids 161–163, users 35/67999/99967, `is_read=0` | "…and 3 L&D staff were notified." |
| `implementation-guide.php` | `ld_reporting_requirement` #4, `frequency=monthly` | 3 → ids 164–166 | "…and 3 L&D staff were notified." |
| `custom-reports.php` (no frequency posted) | `ld_reporting_requirement` #5, `frequency=monthly` | 3 → ids 167–169 | "…and 3 L&D staff were notified." |

The self-test rows were removed afterwards; `ld_notification` and the two requirement
tables are back at their recorded baselines (28 / 4 / 3).

## 7. Still yours

* `pages/turnover-risk-indicator.php` has no save path at all — its own copy says the form
  is for planning only. Wire `ld_turnover_risk` + `workforce_turnover_risk` (or Path B
  `data`) when that form should send.
* Your sidebar bell is still the hardcoded empty stub; the authoritative inbox is the
  Learning module's bell and notification page (any `type` is listed there).
* Hardcoded `/hrms-capstone/…` paths in the nav/API URLs should become one config value.
