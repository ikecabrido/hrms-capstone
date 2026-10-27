# Designating an instructor in Learning & Development

Companion note to `ld-system-architecture.md`. It answers one question the role model
used to leave open: **how does the module decide that an employee is an instructor, and
how do you grant that access to someone new before they have a class?**

Everything below is read out of the source tree — `modules/learning/classes/Page.php`
and `modules/learning/login.php` — not assumed.

---

## 1. Short answer

An employee is an instructor when **either** of these is true:

1. **A teaching assignment names them.** A row with their `employee_id` in
   `ld_course.instructor_id`, `ld_course_instructor.instructor_id`,
   `ld_learning_path.instructor_id`, `ld_program.instructor_id` or
   `ld_video_conference.instructor_id`.
2. **They are on the instructor roster.** A row for their `employee_id` in
   `ld_instructor` — the module-owned table added for exactly this purpose.

The roster is the one that does **not** wait on a class, so it is how a new instructor
is granted access before they teach anything.

The single rule lives in `Page::roleForAccount()` and is called by the router and by
both sign-in paths:

```
role_id = 1 or role_name contains "system admin"   -> admin
employee_id in LD_ADMIN_EMPLOYEE_IDS ([35, 1018])  -> admin
isInstructor(employee_id)                           -> instructor
otherwise                                           -> learner
```

`Page::isInstructor()` is the "is this person an instructor" question; `roleForAccount()`
wraps it with the admin carve-outs.

---

## 2. What is *not* a designation

These are deliberately **not** treated as instructor signals:

| Thing | Why it is not enough |
|---|---|
| `user_account.role_id = 7` (Learning and Development Staff) | A staff role describes the *function* someone performs for L&D, not whether they stand in front of a class. An L&D account can hold role 7 and teach nothing — it stays a learner. |
| Any other `em_roles` row | The `em_roles` list is HRMS staff functions (Recruitment, Payroll, …). None of them means "teaches". |
| `em_employees.position_id` / `em_positions` | A job title such as "Trainer" is an HR label; the module does not infer teaching from it. |
| `em_employees.department_id` | Department membership is not a teaching signal. |

This was a real bug: the old resolver treated the L&D staff role as an instructor
(and even the substring "learning" in any role name), which handed learners the
instructor panel. That is fixed; the test harness holds the line against it.

### The admin carve-outs

`user_account.role_id = 1` (System Administrator), a role name containing
"system admin", and the two legacy L&D administrator employee ids `35` and `1018`
resolve to **admin**, regardless of teaching. They are module-level legacy
designations, not roles, and they live in `Page::LD_ADMIN_EMPLOYEE_IDS`.

---

## 3. Why the roster table exists

Before `ld_instructor`, instructor-ness could only come from a teaching assignment.
That left a gap: a newly hired instructor had **no** L&D access until an administrator
assigned them a course, learning path, program or conference. The employee either
waited, or the administrator had to create a throwaway class.

`ld_instructor` closes the gap. It is a module-owned roster — the same table style as
the other `ld_` tables — with one row per designated instructor:

| Column | Meaning |
|---|---|
| `employee_id` | the designated employee (primary key) |
| `designated_by` | employee id of whoever designated them (optional) |
| `note` | free-text reason (optional) |
| `designated_at` | when the designation was made |

DDL: `rainz/sql/ld_instructor.sql` (import-safe, `CREATE TABLE IF NOT EXISTS`).

---

## 4. How to designate a new instructor

The CLI tool creates the table if it is missing, so it is self-sufficient:

```bash
# grant instructor access before any class is assigned
php modules/learning/cron/designate-instructor.php <employee_id> \
    --by=<admin_id> --note="New trainer, starts next term"

# remove the explicit designation (a teaching assignment still grants access)
php modules/learning/cron/designate-instructor.php <employee_id> --remove

# show the roster
php modules/learning/cron/designate-instructor.php --list
```

The employee can now sign in to the L&D portal and lands in the **instructor** tree on
the very next request — no course required. When they later get a real teaching
assignment, nothing changes; the two signals simply agree.

Removing the roster row does not remove access if a course/path/program/conference
still names them as instructor.

---

## 5. What each role then gets

- **Learner tree is a baseline everyone keeps.** An instructor (and an admin) can open
  the whole `learner/` tree by URL — nobody loses the catalog or their own study pages.
- **The instructor sidebar carries only two learner entry points**: Study
  (`learner/study`) and the Catalog (`learner/catalog`). The rest of the learner tree
  (My Learning Path, Skill Gap, My Skills, Results, Analytics, Calendar, Notes,
  Notifications, Profile) is reachable **by URL only** — an instructor already has a
  Calendar, Notification, Profile and Analytics of their own in the staff nav, and
  repeating them would crowd the menu. This contract is
  `Page::INSTRUCTOR_LEARNER_NAV`, and the test harness enforces it.

---

## 6. Reviewing role / teaching disagreements

`Page::roleTeachingMismatches()` returns the employees the module treats as instructors
who teach (or are on the roster) **but whose account disagrees** — `user_account` is
missing, inactive, or carries the plain *Employee* role rather than any staff role.
A trainer being paid to teach with an account role that says otherwise is worth an
administrator's attention.

The list is surfaced as a **Role & teaching assignments** review panel at the top of
`admin/user`, and the harness verifies the detector finds real cases and does not
flag legitimate instructors whose account role is a staff role.
