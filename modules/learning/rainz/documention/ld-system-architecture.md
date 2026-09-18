# Learning & Development — System Architecture

Companion to the architecture poster for the **Learning & Development** module of
the HRMS capstone. Everything here was read out of the source tree, not assumed —
counts and file paths were measured on **15 September 2026**.

| Artifact | What it is |
|---|---|
| `ld-system-architecture.html` | The poster. Self-contained (logo embedded as a data URI, icons inline, no CDN/network). Opens in any browser; scales fluidly; prints to one A3 landscape page. |
| `ld-system-architecture.png` | 4200 × 2060 raster of the poster (2× render) for slide decks. |
| `ld-system-architecture.pdf` | A3 landscape, single page, vector. |
| `ld-system-architecture.md` | This document. |

> **Folder note:** the parent directory is spelled `documention` in this repo, so
> these files live beside the existing `api-integration-plan.md` rather than in a
> new `documentation/` sibling.

---

## 1. How the stack is actually built

Versions verified in the working checkout:

| Component | Version | Evidence |
|---|---|---|
| PHP | 8.2.12 (ZTS, VC++ 2019 x64) | `php -v` |
| MariaDB | 10.4.32 | `mysql --version`, XAMPP `mysql/data` |
| XAMPP | Apache + PHP + MariaDB bundle | `C:\xampp` |
| phpMyAdmin | 5.2.1 | bundled with XAMPP |
| Font Awesome | 6.5.1 | CDN link in `includes/header.php` |
| Quill rich-text editor | 1.3.7 | CDN link in `pages/instructor/elearning-subpage/lesson.php` |
| Front-end framework | **none** | no Bootstrap/jQuery/React in the module — see §5 |

Module size: **502 PHP files**, **35 domain classes**, **57 `ld_` tables**, **9 integration endpoints**.

---

## 2. Layer-by-layer inventory

### 2.1 Presentation layer

No front-end framework. A hand-built, token-driven CSS system plus small vanilla-JS modules.

- `css/styles.css` — entry point; imports the layers below
- `css/base/{reset,variables,global,enhancements}.css` — design tokens
  (`--primary: rgba(32,0,130,1)`, `--accent: rgb(81,70,183)`, `--text`, `--surface`, `--border`, `--muted`, `--danger`)
- `css/layout/{header,sidebar,footer,module-container}.css`
- `css/components/{dropdown,dropzone}.css`
- `js/components/{dropdown,dropzone,learner-modals}.js`
- `js/layout/{hamburger,realtime,tab-content}.js` — `realtime.js` polls for notifications
- `js/{catalog-browse,course-builder,course-structure,script}.js`
- `js/utils/{main,page-init}.js`
- `js/admin/course-mappings.js`
- UI shell: `includes/header.php`, `includes/sidebar.php`, `includes/footer.php`
- A toast helper (`window.showNotification`) is defined inline in `includes/header.php`

### 2.2 Application layer

Entry: `modules/learning/index.php?page=<role>/<page>` → `classes/Page.php` resolves the
requested page, maps it to `pages/<role>/<page>.php` and falls back to the role home page.

| Role key | Portal home | Nav items |
|---|---|---|
| `admin` | `admin/admin-home` | User · Analytics · Calendar · Moderation · Notifications · Settings · Profile |
| `instructor` | `instructor/instructor-home` | E-Learning · Progress · Learners · Analytics · Grade Book · Calendar · Timeline · Trainings · Training Requests · Certificates · Skill Gaps · Notification |
| `learner` | `learner/learner-home` | (catalog, study, results, skill gap, notes, knowledge transfer, calendar, analytics, profile) |

Feature set (page files, 12 admin + 15 instructor + 12 learner):

- **Course authoring** — `instructor/elearning.php` → `course` → `module` → `lesson` → `quiz` → questions
- **Training programmes** — `instructor/training.php` → `program`, `video-conference`, `training-request`
- **Learning paths** — `admin/learning-path.php`, `learner/my-learning-path.php`
- **Enrolment & catalog** — `learner/catalog.php` (+ `catalog-subpage/ajax/enrollment/…`)
- **Delivery & scoring** — `learner/study.php`, `learner/result.php`, `instructor/gradebook.php`
- **Certification** — `instructor/certificate.php`, `classes/certificate.php`, `classes/certificate-expiry-checker.php`
- **Skills & competency** — `instructor/instructor-skill-gap.php`, `learner/skill-gap.php`, `classes/skill.php`
- **Learning gap analysis** — live per-learner / per-team gap query driven by `ld_skill` × `ld_course_skill` × `ld_course` × `ld_enrollment`; surfaced to instructors (`instructor-skill-gap.php`), learners (`skill-gap.php`), and the `get-workforce-analytics.php` outbound feed (`wfa_skill_gap_analysis`)
- **Reporting** — `admin/analytics.php`, `instructor/analytics.php`, `instructor/progress-dashboard.php`, `learner/analytics.php`
- **Governance** — `admin/moderation.php`, `admin/audit.php`, `admin/settings.php`, `admin/archive.php`
- **Collaboration** — `*/notification.php`, `ld_message`, `ld_comment`, `ld_note`, `ld_announcement`, `*/calendar.php`
- **Knowledge transfer** — `admin/knowledge-transfer.php`, `learner/knowledge-transfer.php`
- **Integrations** — `api/inbound/*`, `api/outbound/*` (see §4)

Background jobs in `cron/` (5 scripts, run manually — no scheduler is configured):

| Script | Purpose |
|---|---|
| `materialize-skill-snapshots.php` | writes `ld_skill_snapshot` |
| `materialize-engagement-snapshots.php` | writes `ld_engagement_snapshot` |
| `send-video-conference-reminder.php` | in-app conference reminders |
| `backup-database.php` / `restore-database.php` | DB dump / restore |

Both materialisers leave a `.log` next to them, confirming they have been run by hand.

### 2.3 System libraries / services

35 classes in `modules/learning/classes/`: analytics, announcement, apiauth, archive,
bookmark, calendarevent, certificate, certificate-expiry-checker, comment, course,
coursemap, csrf, employeedirectory, Employee, enrollment, evaluation, favorite, grade,
integrationlog, learningpath, lesson, message, module, note, program, progress, quiz,
quizsession, rating, report, setting, skill, user, videoconference, Page.

Two of these are infrastructure rather than domain: `apiauth.php` (API-key gate) and
`integrationlog.php` (writes `ld_integration_log` / `ld_integration_event`).

### 2.4 Data layer

MariaDB 10.4.32, database `hrms`, InnoDB / `utf8mb4_general_ci`, no views, no triggers.
**57 `ld_` tables**, grouped:

| Group | Tables |
|---|---|
| Content hierarchy | `ld_course`, `ld_module`, `ld_lesson`, `ld_quiz`, `ld_quiz_question`, `ld_quiz_question_option` |
| Enrolment & progress | `ld_enrollment`, `ld_progress`, `ld_quiz_attempt`, `ld_quiz_session`, `ld_quiz_session_answer`, `ld_grade`, `ld_assessment_result`, `ld_certificate`, `ld_certificate_template`, `ld_evaluation`, `ld_evaluation_feedback` |
| Paths, programmes, templates | `ld_learning_path`, `ld_learning_path_item`, `ld_learning_path_skill`, `ld_program`, `ld_program_skill`, `ld_course_template`, `ld_course_version`, `ld_course_instructor`, `ld_prerequisite` |
| Skills & competency (gap analysis backbone) | `ld_skill`, `ld_course_skill`, `ld_module_skill`, `ld_skill_snapshot`, `ld_engagement_snapshot`, `ld_training_recommendation`, `ld_recommendation_course_map` |
| Recognition | `ld_recognition_course_map`, `ld_recognition_unlock` |
| Collaboration & comms | `ld_notification`, `ld_notification_preference`, `ld_message`, `ld_comment`, `ld_note`, `ld_announcement`, `ld_calendar_event`, `ld_user_event`, `ld_video_conference`, `ld_conference_attendance`, `ld_rating`, `ld_bookmark`, `ld_favorite` |
| Reporting & admin | `ld_report`, `ld_setting`, `ld_display_preference`, `ld_audit_log`, `ld_request` |
| Integration | `ld_api_key`, `ld_integration_log`, `ld_integration_event` |

Shared HRMS tables referenced by the module: `em_employees`, `user_account`,
`em_roles`, `em_departments`.

Schema-only, idempotent re-runnable DDL:
`../ld_tables update/ld_tables schema.sql` (see that folder's notes).

### 2.5 Integration layer

`modules/learning/api/` — JSON REST, authenticated with `X-API-Key: <key>`
(or `Authorization: Bearer <key>`), scope `learning-development`, validated by
`classes/apiauth.php`, every call recorded through `classes/integrationlog.php`.

| Direction | Endpoint | Contract |
|---|---|---|
| Inbound | `receive-employee-profile.php` | POST (also GET by ID) — employee records synchronised into L&D |
| Inbound | `receive-appraisal-data.php` | POST — appraisal input for recommendations / skill gaps |
| Inbound | `receive-job-test-result.php` | POST — recruitment assessment results |
| Inbound | `receive-recognition-eligibility.php` | POST — who is eligible for recognition |
| Outbound | `get-training-completion.php` | GET → Performance Management, `pm_employee_training` (`completion_status`, `final_score`, `certificate_status`) |
| Outbound | `get-learning-performance.php` | GET → Performance Management: grades, quiz scores, skill proficiency |
| Outbound | `get-compliance-training-log.php` | GET → Learning Compliance, `lc_trainings` (compliance-tagged completions) |
| Outbound | `get-attendance-data.php` | GET → Time & Attendance, `ta_attendance` |
| Outbound | `get-workforce-analytics.php` | GET → Workforce Analytics, `wfa_skill_gap_analysis`, `wfa_reports` (also carries skill-gap analysis: per-skill gap %, employees needing training, priority) |

Optional filters on the outbound endpoints: `learner_id`, `course_id`, `status`.

### 2.6 Security

- **Dual sign-in** — HRMS portal `auth/login.php` and L&D portal
  `modules/learning/login.php`; `index.php` carries a Left-Shift + T shortcut into the L&D portal.
- **Role gating** — `classes/Page.php` whitelists `admin` / `instructor` / `learner` and
  refuses unknown page paths; role checks also appear in 7 AJAX endpoints.
- **Session** — `$_SESSION['learning_role']` set by the sign-in flow.
- **SQL** — PDO prepared statements throughout.
- **API auth** — per-integration API keys in `ld_api_key`.
- **Audit** — `ld_audit_log`, written from the admin audit surface.

### 2.7 Infrastructure

XAMPP (Apache, PHP 8.2.12, MariaDB 10.4.32), phpMyAdmin 5.2.1, Windows host.
Uploads are on local disk under `modules/learning/assets/uploads/`:
`certificates/`, `course-thumbnails/`, `lesson-materials/`, `lesson-media/`, `profile-photos/`.
Deploy = copy the tree; schema changes come from `ld_tables schema.sql`.

---

## 3. Users

| Role | How they use it |
|---|---|
| System Administrator | install, integration keys, backups, moderation, audit |
| L&D Administrator | catalogue, learning paths, programmes, settings, analytics |
| Instructor / Trainer | author courses/modules/lessons/quizzes, run trainings, grade, certificates |
| Manager / Supervisor | training requests, team progress and **skill-gap analysis** (identifies who is missing which required skills vs. role) |
| Learner / Employee | catalog → enrol → study → quiz → evaluation → certificate; **self-service skill-gap view** shows acquired vs. missing skills and surfaces training requests |
| Compliance Officer / Auditor | consumes the compliance training log outbound feed; audit trail |

---

## 4. Why this poster differs from the compliance architecture slide

The compliance slide was used as the **layout** reference only. Its technical content
does not describe this module, so it was replaced:

| Compliance slide says | This module actually has |
|---|---|
| Bootstrap 5.3.3 + Bootstrap Icons 1.11.3 | no Bootstrap at all — token-driven custom CSS in `css/base/\|layout/\|components/` |
| Chart.js 4.4.1, FullCalendar 6.1.11 | no charting or calendar library; hand-rolled markup |
| DOMPDF (PDF generation) | no PDF library anywhere in the module |
| PHPMailer 7.1 | no mailer, no SMTP, no `mail()` call |
| PHP Font Lib, PHP SVG Lib, Masterminds HTML5, Sabberworm, PHP CSS Parser | none of these are dependencies (there is no `composer.json` / `vendor/` in the project) |
| Navy blue palette | deep indigo `#200082` with `#5146B7` accent — taken from `css/base/variables.css` |
| Six columns, no integration layer | seven columns: the nine-endpoint REST integration layer is the module's distinguishing feature and gets its own column |
| "Documents & Compliance", "Legal Compliance" tiles | course authoring, quizzes, paths/programmes, skills, certificates, video conferences, training requests |

---

## 5. Known gaps and roadmap

Evidence-backed items found while building the poster. Nothing below was changed —
this is a review list.

1. **Skill-gap analysis is live-only and has no historical materialization.** The gap query in
   `get-skill-gap-analysis.php` and `Analytics::getSkillGapOverview()` is recomputed from
   `ld_skill` × `ld_course_skill` × `ld_course` × `ld_enrollment` on every request. The
   `ld_skill_snapshot` table exists in schema and is the intended per-learner gap feed into
   `wfa_skill_gap_analysis`, but no cron job or API endpoint writes it, so there is no stored
   gap history, no trend (gaps closing vs. widening), and `ld_skill_snapshot` is also missing
   the position/department context that `wfa_skill_gap_analysis` needs for role-requirement
   comparison. The `get-workforce-analytics.php` outbound endpoint serves a live gap list but
   does not back it from `ld_skill_snapshot` yet.

2. **Code references ten tables that do not exist**, so those features fail at runtime:

   | Referenced | In | Reality |
   |---|---|---|
   | `ld_training_requests` | `pages/instructor/ajax/manage-training-request.php` (SELECT + UPDATE) | table absent; the training-request page has no storage |
   | `ld_evaluation_question`, `ld_evaluation_question_option` | `ajax/edit-evaluation-question.php`, `edit-evaluation-option.php` | absent — evaluations have no question bank |
   | `ld_moderation_queue` | `pages/admin/ajax/edit-moderation.php` | absent |
   | `ld_analytics` | `pages/admin/ajax/edit-analytics.php` | absent |
   | `ld_archive` | `pages/admin/ajax/edit-archive.php` | absent |
   | `ld_employee` | `pages/admin/ajax/edit-employee.php` | absent (module uses `em_employees`) |
   | `ld_settings` | `flowchart.php` | actual table is `ld_setting` |
   | `ld_video_conference_attendee` | `pages/learner/calendar.php` (JOIN) | actual table is `ld_conference_attendance` |

4. **A quiz can never belong to a lesson.** `ld_quiz.module_id` is `NOT NULL`, the
   table has **no `lesson_id` column**, and `ld_evaluation.course_id` is `NOT NULL`
   too. Any "quiz under a lesson" feature needs a migration (`lesson_id` nullable
   `module_id`, plus the reverse in `countTrackableItems()`), not just UI work.

5. **Role enforcement is thin.** `learning_role` appears in only 11 files (4 classes +
   7 AJAX endpoints) out of 502 PHP files. None of the 29 top-level admin/instructor
   page files guard themselves — they rely entirely on `classes/Page.php`. Any endpoint
   reachable directly (see 4) is therefore unguarded.

6. **Every AJAX endpoint is routable as a page.** `Page::discoverPages()` whitelists
   `ajax/` files too, so endpoints can be hit through the page loader rather than only
   through the page that owns them.

7. **CSRF protection is effectively unused.** `classes/csrf.php` exists; only one
   endpoint (`pages/admin/ajax/edit-settings.php`) references it — 2 files in total,
   class included.

8. **Background jobs have no scheduler.** `cron/` holds 5 working scripts, but there is
   no `.bat`, `.sh` or Task Scheduler entry, so snapshots and conference reminders only
   run when someone starts them by hand (their `.log` files show manual runs).

9. **No email.** No SMTP client, no `mail()` — all "notifications" are in-app rows.
   `ld_notification_preference` has no consumer, and `Enrollment::invite()` still has a
   TODO where the invitation notification should be created.

10. **Integration endpoints have no rate limiting or scoping beyond the API key**; the
   key is checked, but the audit trail is `ld_integration_log` only.

11. **Repository hygiene.** 117+ files are uncommitted on `learndev` (including deleted
   `modules/exit/pages/*.php`), there is no automated test suite (only `_check.js` /
   `_test.js` scratch files at the module root), and stray `e2e-*.pdf` / `test-pdf.bin`
   artifacts sit in `assets/documents/` and `modules/assets/documents/`.

---

## 6. Regenerating the poster

The poster is plain HTML + inline CSS. To re-render the PNG/PDF after editing the HTML
(Edge or Chrome headless):

```bash
EDGE="/c/Program Files (x86)/Microsoft/Edge/Application/msedge.exe"
H="modules/learning/rainz/documention/ld-system-architecture.html"

# PNG (2x, ~4200x2060)
"$EDGE" --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=2 \
  --window-size=2100,1030 --virtual-time-budget=4000 \
  --screenshot="$(pwd)/modules/learning/rainz/documention/ld-system-architecture.png" \
  "file:///C:/xampp/htdocs/hrms-capstone/$H"

# PDF (one A3 landscape page)
"$EDGE" --headless=new --disable-gpu --no-pdf-header-footer --virtual-time-budget=5000 \
  --print-to-pdf="$(pwd)/modules/learning/rainz/documention/ld-system-architecture.pdf" \
  "file:///C:/xampp/htdocs/hrms-capstone/$H"
```

**Layout gotcha worth remembering:** the print engine lays the page out against the
`@page` box, and the fixed `@media print` block forces the seven columns into a single
row. Without that override (and with a fixed `--f`), the responsive breakpoint below
1250 px stacked the columns and the export came out as **3 pages**. The `--f` value in
print is `min(10.6px, calc(100vw / 145))`, so the poster scales to fit whatever paper
size is chosen. Verified: PNG 4200 × 2060; PDF `MediaBox 1191.12 × 841.92 pt`
(A3 landscape), 1 page, content filling the sheet.

Verified layout measurements (headless render, all widths):

| Viewport | Columns | Panel heights | Overflow / overlap / clipped text |
|---|---|---|---|
| 2076 px | 7 in a row | 722 px each, equal | none |
| 1576 px | 7 in a row | 555 px each, equal | none |
| 1256 px | 7 in a row | 526 px each, equal | none |
| ≤1250 px | stacked (screen only) | — | none |

Also checked: 54 icon references all resolve, 0 zero-sized elements, all 6 user roles
render, no element escapes the poster bounds.

---

## Appendix — image-generation prompt

For anyone who wants a bitmapped version from a generative model (note that image
models reliably garble small technical text; the HTML/PNG above is safer for a defence):

```
Wide 16:9 landscape "SYSTEM ARCHITECTURE DIAGRAM" poster, flat vector infographic,
thin-line icons, crisp legible sans-serif text, no photos, no 3D, white background with
very light lavender panels (#F4F2FF). Header band in a deep indigo (#200082) to violet
(#5146B7) gradient with bold white uppercase title "LEARNING & DEVELOPMENT SYSTEM
ARCHITECTURE" and smaller subtitle "HRMS Capstone — Learning & Development Module".

Seven tall rounded panels side by side, left to right, each with a bold indigo uppercase
layer title and a small gray caption, separated by thin double-headed arrows:

1) PRESENTATION LAYER "Web Browser - Client" — HTML5 · CSS3 (custom design-token system) ·
   JavaScript (vanilla ES6); chips: Font Awesome 6.5.1, Quill Rich-Text Editor 1.3.7
2) APPLICATION LAYER "Server-Side (Business Logic)" — indigo PHP badge "PHP 8.2.12",
   a 3x3 grid of tiles: Course Builder / Quiz & Assessment / Learning Paths & Programs /
   Video Conferences / Training Requests / Catalog & Enrollment / Progress & Certificates /
   Skills & Competency / Reports & Analytics / Learning Gap Analysis; note "Role portals: Administrator ·
   Instructor · Learner"
3) SYSTEM LIBRARIES / SERVICES — API Key Authentication, Integration Event Logger,
   File Upload Service (Dropzone), Certificate Engine & Expiry Checker, Snapshot Jobs
4) INTEGRATION LAYER "REST API · JSON · X-API-Key" — INBOUND: Employee Profile,
   Appraisal Data, Job Test Result, Recognition Eligibility; OUTBOUND: Training Completion
   → Performance Management, Compliance Training Log, Learning Performance, Attendance
   Data, Workforce Analytics (incl. skill-gap analysis); caption "ld_api_key · ld_integration_log ·
   ld_integration_event"

6) narrow vertical SECURITY band with shield icon — Authentication & Authorization,
   Role-Based Access Control, API Key Authentication, Session Management, Data Validation
   & Sanitization, Audit Logging
7) INFRASTRUCTURE LAYER "Server Environment" — XAMPP, Apache Web Server, PHP 8.2.12,
   MariaDB 10.4.32, phpMyAdmin 5.2.1, Local File Storage (/assets/uploads), Windows host

Bottom horizontal band "USERS — System End Users" with six flat icons: System
Administrator, L&D Administrator, Instructor / Trainer, Manager / Supervisor (team gap
analysis & training requests), Learner / Employee (self-service gap view & training
requests), Compliance Officer / Auditor.

Footer strip: "HRMS Capstone · Learning & Development Module — System Architecture".
Colors: indigo #200082, violet #5146B7, slate text #334155, panel borders #C9C2F0.
Clean, professional, PowerPoint-quality, all text spelled correctly and readable.
5) DATA LAYER "Database (Storage)" — database cylinder, "MariaDB 10.4.32", database
   "hrms", grouped bullets: Content Hierarchy, Enrollment & Progress, Paths & Programs,
   Skills & Competency (ld_skill, ld_course_skill, ld_module_skill, ld_skill_snapshot,
   ld_training_recommendation, ld_recommendation_course_map), Collaboration & Communication,
   Integration & Audit, plus "Shared HRMS tables: Employees, User Accounts, Roles, Departments"
6) narrow vertical SECURITY band with shield icon — Authentication & Authorization,
   Role-Based Access Control, API Key Authentication, Session Management, Data Validation
   & Sanitization, Audit Logging
7) INFRASTRUCTURE LAYER "Server Environment" — XAMPP, Apache Web Server, PHP 8.2.12,
   MariaDB 10.4.32, phpMyAdmin 5.2.1, Local File Storage (/assets/uploads), Windows host

Bottom horizontal band "USERS — System End Users" with six flat icons: System
Administrator, L&D Administrator, Instructor / Trainer, Manager / Supervisor (team gap analysis & training requests),
Learner / Employee (self-service gap view & training requests), Compliance Officer / Auditor.

Footer strip: "HRMS Capstone · Learning & Development Module — System Architecture".
Colors: indigo #200082, violet #5146B7, slate text #334155, panel borders #C9C2F0.
Clean, professional, PowerPoint-quality, all text spelled correctly and readable.
```
