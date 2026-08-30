# Learning Project — Organized Structure

## 1. File / Folder Structure

```
learning/
├── assets/
│   ├── bcp-logo.png
│   └── bg.jpg
├── classes/
│   ├── Employee.php
│   └── Page.php
├── css/
│   ├── base/
│   │   ├── global.css
│   │   ├── reset.css
│   │   └── variables.css
│   ├── components/
│   │   └── dropdown.css
│   ├── layout/
│   │   ├── footer.css
│   │   ├── header.css
│   │   ├── module-container.css
│   │   └── sidebar.css
│   └── styles.css
├── includes/
│   ├── footer.php
│   ├── header.php
│   └── sidebar.php
├── js/
│   ├── components/
│   │   └── dropdown.js
│   ├── layout/
│   │   ├── hamburger.js
│   │   ├── realtime.js
│   │   └── tab-content.js
│   ├── utils/
│   │   ├── main.js
│   │   └── page-init.js
│   └── script.js
├── pages/
│   └── dashboard-overview.php
├── .gitignore
├── index.php
└── page-loader.php
```

**Notes**
- CSS is split by purpose: `base` (foundational resets/vars), `components` (reusable UI pieces), `layout` (structural sections), plus a root `styles.css` entry point.
- JS mirrors the same pattern: `components`, `layout`, `utils`, plus a root `script.js` entry point.
- `includes/` holds shared PHP partials (footer, header, sidebar) reused across `pages/`.

---

## 2. Sitemap — Learning and Development

### ADMIN
- **Home**
  - Quick Actions
  - Overview
  - Timeline
  - Users
  - Status
- **Users**
  - Instructors
  - Learners

### INSTRUCTORS
- **Learning Path**
  - Management
- **E-Learning**
  - Courses
  - Modules
  - Lessons
  - Quizzes
  - Evaluations
- **Trainings**
  - Programs
  - Calendar
  - Skills
- **Reports**
  - Learning
  - Learners
  - Certificates
  - Instructors
- **Notification**
  - Overview
  - History
  - Pending
  - System

### LEARNER
- **Home**
  - Courses
  - Activity
  - Quick Actions
  - Overview
  - Timeline
  - Users
  - Status
- **Catalog**
  - Learning Path
  - E-Learning
    - Courses
    - Modules
    - Lessons
    - Quizzes
    - Evaluations
  - Programs
    - Programs
    - Video Conference
    - Skills
- **Study**
  - Calendar
  - Learning Path
    - E-Learning
      - Courses
      - Modules
      - Lessons
      - Quizzes
      - Evaluations
    - Programs
      - Programs
      - Video Conference
      - Skills
- **Results**
  - Certificates
  - Grades

**Notes**
- Three top-level roles: **Admin**, **Instructors**, **Learner** — each with its own nav tree.
- "E-Learning" and "Programs" sub-trees (Courses/Modules/Lessons/Quizzes/Evaluations and Programs/Video Conference/Skills) repeat identically under both **Catalog** and **Study** for the Learner role — **confirmed:** Catalog is the browse view (all available content), Study is the active/enrolled view (content the learner is currently taking). Kept as separate pages/queries, not merged.

---

## 3. `pages/` Folder Structure (with access rules)

```
pages/
├── admin/
│   ├── home.php
│   └── users/
│       ├── instructors.php
│       └── learners.php
│
├── instructor/                  ← shared: accessed by BOTH "instructor" and "admin" roles
│   ├── learning-path/
│   │   └── management.php
│   ├── elearning/
│   │   ├── courses.php
│   │   ├── modules.php
│   │   ├── lessons.php
│   │   ├── quizzes.php
│   │   └── evaluations.php
│   ├── trainings/
│   │   ├── programs.php
│   │   ├── calendar.php
│   │   └── skills.php
│   ├── reports/
│   │   ├── learning.php
│   │   ├── learners.php
│   │   ├── certificates.php
│   │   └── instructors.php
│   └── notification/
│       ├── overview.php
│       ├── history.php
│       ├── pending.php
│       └── system.php
│
├── learner/
│   ├── home.php
│   ├── catalog/
│   │   ├── learning-path.php
│   │   ├── elearning/
│   │   │   ├── courses.php
│   │   │   ├── modules.php
│   │   │   ├── lessons.php
│   │   │   ├── quizzes.php
│   │   │   └── evaluations.php
│   │   └── programs/
│   │       ├── programs.php
│   │       ├── video-conference.php
│   │       └── skills.php
│   ├── study/
│   │   ├── calendar.php
│   │   └── learning-path/
│   │       ├── elearning/       (confirmed separate from catalog/elearning — see section 2 table)
│   │       │   ├── courses.php
│   │       │   ├── modules.php
│   │       │   ├── lessons.php
│   │       │   ├── quizzes.php
│   │       │   └── evaluations.php
│   │       └── programs/
│   │           ├── programs.php
│   │           ├── video-conference.php
│   │           └── skills.php
│   └── results/
│       ├── certificates.php
│       └── grades.php
│
└── dashboard-overview.php       ← shared/general dashboard
```

### Shared vs. Separate — decision summary

| Content type | Instructor version | Learner version | Decision |
|---|---|---|---|
| E-Learning (Courses/Modules/Lessons/Quizzes/Evaluations) | Management view (create/edit/assign) | Browse/consume view | **Separate files** — different purpose despite same names (pink vs. green in sitemap) |
| Programs / Video Conference / Skills | Management view | Browse/consume view | **Separate files** — same reasoning as above |
| Admin access to Instructor pages | — | — | **Shared** — Admin reuses `pages/instructor/*` directly, gated by role permission (no `admin/instructor-*` duplicates) |
| Learner `catalog/elearning/*` vs `study/learning-path/elearning/*` | — | **Catalog = Browse view** (all available content, not yet enrolled) / **Study = Active/Enrolled view** (content the learner is currently taking) | **✅ Confirmed separate purposes** — Catalog and Study represent genuinely different states (available vs. in-progress), not just a cosmetic view toggle. Keep as separate pages/queries: Catalog queries "all published content," Study queries "content this learner is enrolled in / has progress on." They can still share the same card/UI components, but the underlying data source differs. |

### E-Learning Section — Full Structure (Instructor)

**Hub page + entity pages:**
```
instructor/
├── elearning.php              ← landing/hub: cards (recently added courses/modules/etc.) + nav buttons
└── elearning/
    ├── courses.php            ← browse by Course (cards)
    ├── modules.php            ← browse by Module (cards)
    ├── lessons.php             ← browse by Lesson (cards)
    ├── quizzes.php             ← browse by Quiz (cards)
    ├── evaluations.php        ← browse by Evaluation (cards)
    └── ajax/
        ├── get-modules.php        (modules for a given course_id)
        ├── get-lessons.php        (lessons for a given module_id)
        ├── get-quizzes.php        (quizzes for a given module_id)
        ├── get-evaluations.php    (evaluations for a given course_id)
        ├── add-course.php / edit-course.php / archive-course.php
        ├── add-module.php / edit-module.php / archive-module.php
        ├── add-lesson.php / edit-lesson.php / archive-lesson.php
        ├── add-quiz.php / edit-quiz.php / archive-quiz.php
        └── add-evaluation.php / edit-evaluation.php / archive-evaluation.php
```

**Parent-child relationships (confirmed):**

| Entity | Parent | Notes |
|---|---|---|
| Course | — (top-level) | — |
| Module | Course | `course_id` required |
| Lesson | Module | `module_id` required |
| Quiz | Module | `module_id` required |
| Evaluation | **Course** | `course_id` required — attaches directly to the course, not to a module or lesson |

**Behavior — each of the 5 entity pages supports:**

| Behavior | How it works |
|---|---|
| Browse (cards) | Grid of cards for that entity on its own page |
| Add — own page | "+ Add [Entity]" button opens a modal/form on that page; parent picked manually via dropdown |
| Add — from parent | Inside an expanded parent card (e.g. expanding a Course to add a Module, or expanding a Course to add an Evaluation), parent ID is pre-filled |
| Edit | Click a card / edit icon → modal pre-filled → submits to `edit-*.php` |
| Archive | "Archive" action per card → soft-delete via `status` column (`active` / `archived`), not hard-delete → `archive-*.php` |

**Browsing entry points:**
- `courses.php` → expand a course → see its Modules (dropdown) and its Evaluations
- `modules.php` → flat list of all modules (course shown as context tag) → expand → see its Lessons and Quizzes
- Same AJAX endpoints (`get-lessons.php`, `get-quizzes.php`, `get-evaluations.php`) power both the standalone entity pages and the nested accordions — no duplicate backend logic

**Open items:**
- ~~`modules.php` filtering~~ — **Resolved:** filterable by Course, Category/Tag, and Skill (multi-select). See "Skills as Master/Tag Table" below.
- ~~Catalog vs. Study merge~~ — **Resolved:** kept as separate pages/queries (Catalog = browse available, Study = enrolled/in-progress). See section 2 table.
- ~~Hub-page pattern scope~~ — **Resolved:** only **E-Learning** and **Trainings** have subpages, and both get the hub-page treatment (browsable cards + list, plus nav to their sub-entities — Trainings' subpages being Programs, Video Conference, Skills, Calendar). `analytics.php` (renamed from `reports.php`) and `notification.php` have no subpages of their own, so they don't need the hub + preview-card pattern — they're simpler standalone pages (their AJAX lives in the shared parent `ajax/` folder per the naming/placement rule already established). Note: `analytics.php` later grew its own subpages (Learning, Learner, Instructor, Certificate, Enrollment, Progress) once Enrollment/Progress tracking was added — see section on Analytics/Moderation for current structure.

### Skills as a Master/Tag Table

Skills are **not unique per module/course** — they come from a shared master list (name, description, date updated, suggested flag) and get **tagged onto** content. One module can have multiple skills; one skill can apply to many modules/courses. This is a many-to-many relationship, not a 1:1 field.

**Tables:**
```
ld_skill
  id, name, description, date_updated, suggested

ld_module_skill        (pivot/junction table)
  id, module_id, skill_id

ld_course_skill         (if courses can also be tagged directly, not just via their modules)
  id, course_id, skill_id
```

**Filtering `modules.php` (and optionally `courses.php`) by:**
- Course
- Category/Tag
- Skill (multi-select — a module can carry several skill tags)

**AJAX additions:**
```
instructor/ajax/ (or elearning-subpage/ajax/)
├── get-module.php?course_id=5        (existing — filter by course)
├── get-module.php?skill_id=12         (filter by skill)
├── get-module.php?category=xxx        (filter by category/tag)
├── get-skill.php                      (powers the skill filter dropdown/list — from ld_skill)
├── add-module-skill.php               (tag a skill onto a module: { module_id, skill_id })
└── remove-module-skill.php            (untag: { module_id, skill_id })
```

**Note:** since Skills already exists as its own entity under Trainings (`add-skill.php`, `edit-skill.php`, `archive-skill.php`, `get-skill.php` in `training-subpage/ajax/`), no duplicate CRUD is needed here — `get-skill.php` is reused to populate the tag list, and the *new* pieces are just the pivot table + the tag/untag endpoints.

### Access control pattern (for `page-loader.php`)

```php
$access = [
    'admin'      => ['admin'],
    'instructor' => ['instructor', 'admin'],   // admin inherits instructor pages
    'learner'    => ['learner', 'admin'],      // adjust if admin shouldn't see learner pages
];
```

- Check the requested section against `$access` before loading the file.
- If Admin needs a different header/actions when viewing an Instructor page (e.g. "editing as admin"), branch inside the same `.php` file using `$currentUserRole` — don't fork the file.
- Build the sidebar (`includes/sidebar.php`) dynamically from whichever sections the current role is allowed to see, so Admin's menu shows both Admin + Instructor trees.

---

## 4. Learning Path — Structure & AJAX

Learning Path is a **container/sequence** of mixed content — it does not require items to share a parent course. An instructor can combine a full Course, a single Quiz pulled from an unrelated Lesson, a Program, and a Video Conference into one ordered path.

**Page location:** `instructor/learning-path.php` — no subpages, so its AJAX lives in the shared `instructor/ajax/` folder.

### Tables
```
ld_learning_path
  id, title, description, assigned_to, status

ld_learning_path_item
  id, learning_path_id, item_type, reference_id, order, status
```
- `item_type`: 'course' | 'module' | 'lesson' | 'quiz' | 'evaluation' | 'program' | 'video-conference'
- `reference_id`: the ID of the actual record in that entity's own table (e.g. `ld_quiz.id`)
- `order`: step position within the path

### AJAX
```
instructor/ajax/
├── add-learning-path.php
├── edit-learning-path.php
├── archive-learning-path.php
├── get-learning-path.php
├── add-learning-path-item.php        (attach an item: { learning_path_id, item_type, reference_id, order })
├── remove-learning-path-item.php     (detach an item)
├── reorder-learning-path-item.php    (update step order)
└── search-learning-path-item.php     (unified picker — search across all entity types at once)
```

### `search-learning-path-item.php` — unified cross-entity picker
Instead of calling 7 separate `get-*.php` endpoints when building a path, the "Add Item" modal calls one search endpoint with an optional `type` filter:

```
GET search-learning-path-item.php?type=quiz&search=safety
GET search-learning-path-item.php?type=all&search=onboarding
```

The backing class routes the search to the correct table per type:
```php
class LearningPathItem {
    public function search($type, $search) {
        $map = [
            'course'           => 'ld_course',
            'module'           => 'ld_module',
            'lesson'           => 'ld_lesson',
            'quiz'             => 'ld_quiz',
            'evaluation'       => 'ld_evaluation',
            'program'          => 'ld_program',
            'video-conference' => 'ld_video_conference',
        ];

        if ($type === 'all') {
            $results = [];
            foreach ($map as $itemType => $table) {
                $results[$itemType] = $this->queryTable($table, $search);
            }
            return $results;
        }

        return $this->queryTable($map[$type], $search);
    }

    private function queryTable($table, $search) {
        // SELECT id, title FROM {$table} WHERE title LIKE '%search%'
    }
}
```

**Response shape (`type=all`):**
```json
{
  "course": [{ "id": 3, "title": "Onboarding Basics" }],
  "quiz": [{ "id": 12, "title": "Safety Quiz" }],
  "video-conference": [{ "id": 5, "title": "Live Orientation" }]
}
```

**Frontend flow:**
1. "Add Item" modal on `learning-path.php` shows a search box + type filter (All / Courses / Modules / Quizzes / etc.)
2. Calls `search-learning-path-item.php` as the instructor types
3. Results are grouped/tagged by type so a Quiz result clearly shows which course/lesson it originally belongs to
4. Selecting a result calls `add-learning-path-item.php` with `{ item_type, reference_id, order }`

### Learner-facing Learning Path (read-only)

Learners only **consume** an assigned Learning Path — they never build, edit, reorder, or remove items. That authority stays exclusively on the Instructor side above.

```
learner/catalog-subpage/ajax/discover/
├── get-learning-path.php          (the learner's assigned path(s) — list/summary)
└── get-learning-path-item.php      (all items within a specific path, in order, with each item's type + completion status)
```

`get-learning-path-item.php` joins `ld_learning_path_item` against the learner's own `ld_progress`/`ld_enrollment` records (by `item_type` + `reference_id`) so each step shown can indicate whether it's not started, in progress, or completed — letting the learner follow the path sequentially and jump into whichever item they click next.

---

## 5. Enrollment, Progress, Catalog Actions & Moderation

### Enrollment & Progress tracking

**Tables:**
```
ld_enrollment
  id, learner_id, course_id, status ('enrolled'|'in_progress'|'completed'|'withdrawn'),
  enrolled_at, completed_at, last_accessed_at

ld_progress
  id, enrollment_id, item_type ('module'|'lesson'|'quiz'|'evaluation'), reference_id,
  status ('not_started'|'in_progress'|'completed'), completed_at

ld_quiz_attempt
  id, learner_id, quiz_id, score, total_items, passed (bool), attempted_at

ld_grade
  id, learner_id, course_id, final_score, status ('passed'|'failed'), issued_at
```

- `ld_enrollment` = course-level status ("is this learner in this course, overall state").
- `ld_progress` = item-level status within that course (which specific modules/lessons/quizzes are done).
- `ld_quiz` / `ld_evaluation` should carry a `passing_score` field so `ld_quiz_attempt.passed` can be calculated. Add `max_attempts` on `ld_quiz` if retakes are capped.
- `ld_certificate` should reference `completed_enrollment_id` — a certificate should only be issuable once `ld_enrollment.status = 'completed'`.
- Video Conference attendance (if tracked): `ld_conference_attendance` (`learner_id`, `video_conference_id`, `attended`, `joined_at`).

**What powers common queries:**
| Question | Source |
|---|---|
| How many enrolled in a course | `COUNT(*)` on `ld_enrollment` where `course_id = X` |
| Not yet enrolled | Learners with no `ld_enrollment` row for that course |
| Not yet answered a quiz | No matching `ld_quiz_attempt` row, even if enrolled |
| Learner's enrolled courses | `ld_enrollment` filtered by `learner_id` — powers `study.php` |
| Grades and scores | `ld_grade` (course-level), `ld_quiz_attempt` (quiz-level) |

### Learner Results-subpage (Results + Progress)

`results.php` stays as the hub name — Progress was added as a tab alongside the original Certificates and Grades, not a rename or a new top-level page. Expanded to cover averages, transcript, evaluation feedback, and skill mastery. **Badges/Achievements and peer percentile/comparison are explicitly out of scope for now.**

```
learner/results-subpage/ajax/
├── certificate/
│   ├── get-certificate.php
│   └── download-certificate.php
│
├── grade/
│   ├── get-grade.php                  (per-course final grades)
│   ├── get-average-score.php           (aggregate across all courses/quizzes)
│   └── get-transcript.php               (full completion history/timeline)
│
├── progress/
│   └── get-progress.php                 (% complete per course — carried over from Study)
│
├── evaluation/
│   └── get-evaluation-feedback.php      (instructor comments/feedback on evaluations, not just score)
│
├── skill/
│   └── get-skill-summary.php            (skills demonstrated via completed content)
│
└── export/
    └── export-transcript.php             (PDF/CSV download of grades + completion history)
```

**New table implied:**
```
ld_evaluation_feedback
  id, evaluation_id, learner_id, instructor_id, comment, created_at
```

**Explicitly out of scope (for now):**
- Badges/Achievements (`ld_achievement`) — gamification, not currently planned
- Percentile/peer comparison — skipped by design (can feel discouraging in a corporate L&D context)

### Catalog — AJAX structure (grouped by concern)

```
learner/catalog-subpage/ajax/
├── discover/
│   ├── get-course.php                 (filterable by category, skill)
│   ├── get-module.php                  (filterable by course, category, skill)
│   ├── get-lesson.php
│   ├── get-quiz.php
│   ├── get-evaluation.php
│   ├── get-program.php
│   ├── get-video-conference.php
│   ├── get-skill.php                    (powers skill filter dropdown)
│   ├── get-recommended.php
│   └── check-prerequisite.php
│
├── engagement/
│   ├── rate-course.php
│   ├── ask-question.php
│   ├── get-comment.php
│   ├── delete-comment.php               (hard delete — blocked if was_ever_reported = true)
│   ├── share-content.php
│   └── request-content.php               (suggest new course/skill)
│
├── enrollment/
│   ├── enroll.php
│   ├── unenroll.php
│   └── get-enrollment.php
│
├── moderation/
│   └── report-content.php                (flag content/comment — feeds ld_report)
│
├── personal/
│   ├── bookmark-content.php               (hard delete allowed — exception to no-delete rule)
│   ├── remove-bookmark.php
│   ├── favorite-content.php
│   └── remove-favorite.php
│
└── progress/
    ├── mark-lesson-complete.php
    ├── submit-quiz-answer.php
    ├── get-quiz-attempt.php
    └── download-material.php
```

**Grouping rule of thumb:** `discover` = read-only browsing/search, `engagement` = learner interacting with content or the platform (ratings, comments, requests), `enrollment` = joining/leaving a course, `moderation` = flagging content (connects to `ld_report` and the Instructor/Admin moderation flow), `personal` = low-stakes user-only actions exempt from the no-delete rule, `progress` = tracked learning activity feeding `ld_progress` / `ld_quiz_attempt`.

**New tables implied by Catalog actions:**
```
ld_rating       (learner_id, course_id, rating, comment, created_at)
ld_request      (learner_id, requested_title, description, status, created_at)
ld_bookmark     (learner_id, item_type, reference_id)
ld_favorite     (learner_id, item_type, reference_id)
ld_comment      (learner_id, lesson_id, message, parent_comment_id, status('active'|'archived'), was_ever_reported (bool, never resets to false), created_at)
ld_material     (lesson_id, file_path, title)
ld_prerequisite (course_id, required_course_id | required_skill_id)
```

### Study — AJAX structure (enrolled content only)

Study is the in-progress counterpart to Catalog — everything here is scoped to content the learner is **already enrolled in**. Most of the underlying logic overlaps with Catalog's Progress/Engagement groups, but scoped queries and a few Study-specific actions are added.

```
learner/study-subpage/ajax/
├── browse/
│   ├── get-enrolled-course.php        (only courses this learner is enrolled in)
│   ├── get-enrolled-module.php
│   ├── get-enrolled-lesson.php
│   ├── get-enrolled-quiz.php
│   ├── get-enrolled-evaluation.php
│   ├── get-enrolled-program.php
│   ├── get-enrolled-video-conference.php
│   └── get-resume-point.php            (Study-specific — "continue where you left off," last incomplete item)
│
├── progress/
│   ├── mark-lesson-complete.php         (shared logic with Catalog's version)
│   ├── submit-quiz-answer.php
│   ├── get-quiz-attempt.php
│   ├── download-material.php
│   └── get-progress.php                 (% complete per course/module — powers progress bars)
│
├── engagement/
│   ├── ask-question.php
│   ├── get-comment.php
│   └── take-note.php                     (personal notes while studying — confirmed in scope)
│
├── enrollment/
│   └── unenroll.php                       (drop a course mid-study)
```

**Differences from Catalog:**
- No `enroll.php` — enrolling only happens in Catalog. Study only has `unenroll.php` (dropping).
- No `rate-course.php`, `request-content.php`, `bookmark-content.php`, `favorite-content.php` — those are Catalog/discovery actions, not Study actions.
- `get-resume-point.php` and `get-progress.php` are Study-specific — Study is the page where "continue" and progress bars matter most.

**Personal Notes (`ld_note`) — confirmed:**
```
ld_note
  id, learner_id, item_type, reference_id, note, created_at, updated_at
```
- `item_type` + `reference_id` let a learner attach a note to any content level (course, module, lesson, quiz) — same pattern as `ld_bookmark`/`ld_favorite`.
- Personal, learner-owned data — treated like Bookmarks/Favorites: **hard delete allowed** (exception to the no-delete rule), since it's private study material, not shared/moderated content.
- Needs a matching `edit-note.php` and `delete-note.php` alongside `take-note.php` and `get-note.php`:

```
learner/study-subpage/ajax/engagement/
├── take-note.php      (add-note.php equivalent — create)
├── edit-note.php
├── delete-note.php     (hard delete)
└── get-note.php         (fetch learner's own notes for a given item, or all notes)
```

### Timed Quiz — Session, Randomization, Navigator & Review

Quizzes are timed (default **10 minutes / 600 seconds**, overridable per quiz) and taken on a **dedicated full page**, not a popup — popups can be blocked by browsers, lose timer context if closed accidentally, and are unreliable on mobile. Clicking a Quiz from the course/module accordion navigates to this page rather than expanding inline, since a timed quiz needs a focused, distraction-free view.

**Copy/paste and AI/search prevention were considered and deliberately dropped** — client-side deterrents (`oncopy`, `oncontextmenu`, tab-blur detection) are trivially bypassed and give a false sense of security. Not implemented.

**Pages:**
```
learner/study-subpage/
├── quiz-taker.php        (timed quiz-taking experience, with question navigator)
└── quiz-review.php        (post-submission review — only if the instructor enabled it)
```

**Tables:**
```
ld_quiz_question
  id, quiz_id, question_text, question_type, ...

ld_quiz_question_option
  id, question_id, option_text, is_correct, ...

ld_quiz_session
  id, learner_id, quiz_id, started_at, duration_seconds,
  submitted_at, status ('in_progress'|'submitted'|'expired'),
  question_order (JSON — shuffled question order + shuffled option order per question, locked at start)

ld_quiz_session_answer     (one row per question per session — powers the number-grid navigator)
  id, quiz_session_id, question_id, selected_option_id (nullable if unanswered),
  is_marked_for_review (bool), answered_at
```

`ld_quiz` gains:
- `duration_seconds` (default `600`)
- `show_answers_after_submit` (bool, **default `false` / blind** — instructor decides per quiz whether learners can review correct answers after submitting)

**AJAX:**
```
learner/study-subpage/ajax/progress/
├── start-quiz.php             (creates ld_quiz_session, shuffles + locks question/option order, returns duration)
├── save-quiz-answer.php        (autosave — fires per question as the learner answers, not just at the end)
├── mark-quiz-question.php       (toggle "mark for review" on a question, independent of answered state)
├── get-quiz-status.php           (answered/unanswered/marked state for all questions — powers the number-grid panel)
├── submit-quiz-answer.php        (final submission — locks the session, calculates score; server validates elapsed time, rejects if past duration_seconds)
├── auto-submit-quiz.php           (server-side fallback — force-submits whatever was answered when the timer expires)
└── get-quiz-result.php             (post-submission breakdown per question — see access rule below)
```

**Server-authoritative timing:** the frontend countdown is UX only, synced from `started_at` + `duration_seconds`. The backend is the source of truth — `submit-quiz-answer.php` always re-checks `NOW() - started_at <= duration_seconds` server-side before accepting, since a client-side timer can be tampered with.

**Randomization (locked at `start-quiz.php`, not re-shuffled on refresh):**
- Question order is shuffled per attempt
- Answer options within each question are shuffled per attempt
- Both are stored in `ld_quiz_session.question_order` so a page refresh mid-quiz shows the same order, not a new shuffle
- Optional question pooling (e.g. 20 in the bank, 10 shown per attempt) — not yet confirmed as in scope; would need a `question_count` field on `ld_quiz` separate from total pool size

**Question navigator (on `quiz-taker.php`):** a side panel of numbered boxes, one per question, color-coded — answered / unanswered / marked for review. Clicking a number jumps to that question directly (non-linear). Before final submit, a summary warns of any unanswered or marked-for-review questions.

**Review access rule — blind by default:**
- `ld_quiz.show_answers_after_submit` defaults to `false` — learners see only their score, not the answer key, unless the instructor explicitly enables review for that quiz
- `get-quiz-result.php` must guard on two conditions: (1) the session belongs to the requesting learner, and (2) `ld_quiz_session.status` is `'submitted'` or `'expired'` (never `'in_progress'`) — prevents peeking at answers by guessing a session ID mid-attempt
```php
if ($session->learner_id !== $currentUserId || $session->status === 'in_progress') {
    return ['error' => 'Quiz not yet submitted.'];
}
if (!$quiz->show_answers_after_submit) {
    return ['error' => 'Answer review is not enabled for this quiz.'];
}
```



Calendar is not its own subpage anywhere — it's a widget/tab surfaced inside an existing page, backed by AJAX placed according to scope.

**Learner** — moved out of `study-subpage/` into the shared `learner/ajax/` folder, since a learner's schedule can eventually span both Catalog (e.g. enrollment deadlines) and Study (enrolled content), not just Study alone:
```
learner/ajax/
├── get-schedule.php            (upcoming lessons/quizzes/video conferences/deadlines)
└── get-schedule-detail.php      (click a calendar day/event for full details)
```

**Instructor** — `add/edit/archive/get-calendar-event.php` (already in `training-subpage/ajax/calendar/`) covers events the instructor manually created. A separate aggregated view is added for their full teaching commitments (including auto-generated entries like a Video Conference they're hosting, not just manually created events):
```
instructor/training-subpage/ajax/calendar/
├── add-calendar-event.php
├── edit-calendar-event.php
├── archive-calendar-event.php
├── get-calendar-event.php
└── get-instructor-schedule.php   (aggregated — all sessions/deadlines they're teaching/hosting)
```

**Admin** — no dedicated Calendar page. Admin already has shared access to Instructor pages, so an Instructor's calendar is viewable directly there. For Learners (which Admin doesn't otherwise have access to), a read-only lookup is added instead, surfaced as a "View Schedule" action on the existing user detail view:
```
admin/user-subpage/ajax/
└── get-user-schedule.php        (admin views a specific learner's or instructor's schedule by user_id, read-only)
```

### Skills as a Master/Tag Table

Skills are a shared master list, not unique per module/course — they get tagged onto content (many-to-many).

```
ld_skill
  id, name, description, date_updated, suggested

ld_module_skill  (pivot)
  id, module_id, skill_id

ld_course_skill   (pivot, if courses can be tagged directly too)
  id, course_id, skill_id
```

`modules.php` (and optionally `courses.php`) is filterable by Course, Category/Tag, and Skill (multi-select). `get-skill.php` (already existing under Trainings' Skill CRUD) is reused to populate the filter/tag list — no duplicate CRUD needed, just the pivot tables + `add-module-skill.php` / `remove-module-skill.php` to tag/untag.

### Moderation — flagged/reported content

`ld_report` is the single source of truth for flagged content, queried differently per role — no duplicate tables.

```
ld_report
  id, learner_id, item_type, reference_id, reason,
  status ('pending' | 'reviewed' | 'archived'),
  instructor_response (text, nullable),
  instructor_responded_at (nullable),
  reviewed_by (admin_id, nullable),
  reviewed_at (nullable),
  created_at
```

**Status flow:** `pending` → (Admin reviews within 7 days) → `reviewed`, or → (no action within 7 days, auto-archived by a scheduled job) → `archived`. No `dismissed`/`deleted` status — consistent with the system-wide no-delete rule.

**Instructor's role:** can respond and act on their own content, but cannot close the case — only Admin can set `status = 'reviewed'`.

```
instructor/moderation-subpage/ajax/
├── get-report.php               (flags on this instructor's own content only)
└── respond-report.php            (instructor's response/action note; does NOT change status)

admin/moderation-subpage/ajax/
├── get-report.php                (all flags, system-wide, no ownership filter)
├── review-report.php              (admin sets status = 'reviewed', reviewed_by, reviewed_at)
└── escalate-report.php            (optional — flag something for instructor's attention)
```

**Auto-archive job:** runs outside normal page traffic (cron), not user-triggered AJAX:
```
cron/archive-stale-report.php   (daily — WHERE status='pending' AND created_at < NOW() - 7 days → status='archived')
```

### Unified Archive (soft-deleted items across the system)

Since most entities use `status = 'archived'` instead of hard delete, a single Archive view surfaces everything in one place rather than being buried per-entity.

```
instructor/
├── archive.php
├── archive-subpage/ajax/
│   ├── get-archive.php            (?type=course|module|lesson|quiz|evaluation|program|skill|video-conference|certificate|learning-path|...)
│   ├── restore-archive.php         (un-archive → status back to 'active')
│   └── get-archive-count.php       (badge counts per type)

admin/
├── archive.php                     (kept at top level — separate from Instructor's, scoped to Users + system Reports/flags)
└── ajax/
    ├── get-archive.php              (?type=user|report)
    ├── restore-archive.php
    └── get-archive-count.php
```

**Note:** unlike Instructor's Archive (which has its own `archive-subpage/ajax/` since Instructor archives many distinct entity types), Admin's archive AJAX was flattened into the shared `admin/ajax/` folder — `archive.php` has no further subpages of its own on the Admin side, consistent with the "no subpages = shared parent ajax/" rule.

**Exceptions to the archive/no-delete rule:** Bookmarks and Favorites are hard-deletable (personal, low-stakes). Comments are hard-deletable *unless* they were ever reported — a reported comment is archived permanently for audit/legal purposes and can never be hard-deleted, even after being reviewed and found acceptable.

---

## 7. Additional System Features

### Authentication & Profile
Both live **outside** `pages/`, at the project root — confirmed existing already:
```
learning/
├── auth/            (login, logout, forgot/reset password)
├── profile/          (account settings, edit profile, change password — shared across roles)
├── errors/
│   ├── 403.php        (access denied — ties into the $access permission checks)
│   ├── 404.php
│   └── 500.php
├── pages/
│   ├── admin/
│   ├── instructor/
│   ├── learner/
│   └── shared/
│       └── ajax/
│           └── get-faq.php
```

### Global Search
Role-scoped, one endpoint per role rather than per subpage — reuses the same "unified search" pattern as `search-learning-path-item.php` (one endpoint, routes to different tables based on `type` + the current role's permissions).
```
{role}/ajax/
└── search-global.php     (Instructor: own content + learners; Learner: catalog; Admin: users + content)
```

### Assign-Learner with Confirmation Flow
Instructor-initiated enrollment requires the learner's explicit confirmation before they're actually enrolled — no force-adding.

`ld_enrollment.status` gains a new value: `'invited' | 'enrolled' | 'in_progress' | 'completed' | 'withdrawn'`

**Flow:**
1. `assign-learner.php` creates a `ld_enrollment` row with `status = 'invited'` (not `'enrolled'` yet)
2. Notification fires to the learner: *"You have been invited to [Course] by [Instructor Name]"*
3. Learner sees the invite (Notifications, or a "Pending Invitations" widget on `learner-home.php`/`catalog.php`)
4. Learner confirms → `status` flips to `'enrolled'`; declines → row archived/removed

```
instructor/ajax/
└── assign-learner.php            (existing — creates invited-status enrollment, triggers notification)

learner/catalog-subpage/ajax/enrollment/
├── accept-invitation.php          (status: invited → enrolled)
└── decline-invitation.php          (status: invited → archived/removed)
```

### Announcements
Admin-authored, broadcast to all users or filtered by role/audience.
```
ld_announcement
  id, title, message, audience ('all'|'instructor'|'learner'|'admin'), posted_by, created_at, expires_at (nullable)

admin/ajax/
├── add-announcement.php
├── edit-announcement.php
├── archive-announcement.php
└── get-announcement.php

{role}/ajax/
└── get-announcement.php          (Instructor/Learner read-only view, filtered by their audience)
```
Surfaces as a banner/widget on each role's home page (`admin-home.php`, `instructor-home.php`, `learner-home.php`).

### Audit Log
Pairs with the no-delete philosophy — logs every create/edit/archive/restore/review action system-wide. **Admin-only** — oversight/compliance data, not visible to Instructor or Learner.
```
ld_audit_log
  id, user_id, role, action ('create'|'edit'|'archive'|'restore'|'review'), item_type, reference_id, details (JSON), created_at

admin/audit-subpage/
├── audit.php
└── ajax/
    └── get-audit-log.php     (filterable by user, action, date range, item type)
```

### Learner Notifications
Instructor already has a full Notification system — Learner needs the equivalent, especially now that Learners receive invitations, moderation outcomes, and announcements.
```
learner/
├── notification.php
└── ajax/
    ├── get-overview-notification.php
    ├── get-history-notification.php
    ├── get-pending-notification.php    (e.g. pending course invitations)
    ├── mark-notification-read.php
    └── mark-all-notification-read.php
```

### Public Certificate Verification
```
pages/public/verify-certificate.php
```
**No authentication required — must bypass the login/session check entirely.** `page-loader.php` (or wherever the access-control gate lives) needs to explicitly whitelist `public/` as a no-auth zone, since every other route under `pages/` assumes a logged-in session. Requires `ld_certificate` to carry a unique verification code field that this page looks up.

### System Settings (Admin)
Site-wide configuration, editable by Admin. Only settings relevant to Learning & Development are covered here — other settings (unrelated to this module) already exist elsewhere in the system.
```
admin/
├── settings.php
└── ajax/
    ├── get-settings.php
    └── edit-settings.php
```

**Configurable fields:**
- Notification settings
- Default quiz duration (used by `ld_quiz.duration_seconds` when not overridden per quiz)
- Report/moderation auto-archive window (currently 7 days — must be configurable, not hardcoded, so it can be changed later)
- Max quiz attempts (global default, overridable per quiz)
- File upload limits (max size for lesson materials/attachments)
- Default certificate validity period (if certificates expire)
- Site timezone (affects deadlines, calendar, and quiz timer display)
- Enrollment invitation expiry (how long an unanswered `assign-learner.php` invite stays pending before auto-expiring)

```
ld_setting
  id, key, value, updated_at
```
(simple key-value table; `ld_report.status='pending'` auto-archive job, `ld_quiz.duration_seconds` default, etc. all read from here instead of being hardcoded)

### Course Duplication
A copy operation, not a new entity — clones a Course and its nested content as a starting point instead of building from scratch.
```
instructor/elearning-subpage/ajax/
└── duplicate-course.php     (clones course + its modules/lessons/quizzes/evaluations, marks status = 'draft')
```
**How it works:** creates a new `ld_course` row (title suffixed "— Copy"), then deep-copies related `ld_module`/`ld_lesson`/`ld_quiz`/`ld_evaluation` rows with IDs remapped to the new course. The copy is created with `status = 'draft'` so it doesn't appear in the Learner catalog until the instructor reviews and publishes it.

**Field needed:** `ld_course.status` must include `'draft'` as a value (`'draft' | 'active' | 'archived'`).

### Course Reviews (aggregate display)
`rate-course.php` (already exists) collects individual ratings into `ld_rating` — this adds the missing aggregate display.
```
learner/catalog-subpage/ajax/discover/
└── get-course-review.php     (average rating, review count, list of comments — shown on course cards/detail)
```

---

## 8. Cross-Module Integration (HR System)

Learning & Development is one module within a larger HR system. Other modules — **Employee Management, Performance Management, Compliance & Legal, Time & Attendance, Recruitment & Onboarding, Employee Recognition, Workforce Analytics & Reporting** — are being built in parallel by other teams. Since these are separate builds, `learning/api/` acts as the **contract boundary**: a defined set of endpoints other modules call to pull L&D data, and a defined set L&D calls (or receives from) to pull their data in. Treat this as a documented interface other teams can build against without needing to read L&D's internal page/AJAX structure.

```
learning/
└── api/
    ├── outbound/       (other modules pull FROM Learning & Development)
    │   ├── get-training-completion.php       → Employee Management (Document Management)
    │   ├── get-learning-performance.php        → Performance Management (scores, identified skill gaps)
    │   ├── get-compliance-training-log.php      → Compliance & Legal (audits & reporting)
    │   ├── get-attendance-data.php                → Time & Attendance (training session attendance)
    │   └── get-workforce-analytics.php             → Workforce Analytics (engagement trends, certification status/expiry, attrition/turnover indicators, predictive skill needs, custom HR reports)
    └── inbound/         (Learning & Development pulls FROM other modules)
        ├── receive-appraisal-data.php              ← Performance Management (appraisal results, turnover risk, recommended training plan — feeds assign-learner.php)
        ├── receive-job-test-result.php              ← Recruitment & Onboarding (assessment/course results — can trigger onboarding Learning Path auto-enrollment)
        ├── receive-recognition-eligibility.php       ← Employee Recognition (unlocks certain courses/programs as a reward)
        └── receive-employee-profile.php              ← Employee Management (employee docs/credentials, role/job history — used for learner profile pre-fill, prerequisite checks)
```

### Data mapping reference

| Direction | Module | Data | L&D Source / Destination |
|---|---|---|---|
| Outbound | Employee Management (Document Mgmt) | Training completion & certification records | `ld_certificate`, `ld_enrollment` |
| Outbound | Performance Management | E-learning completion & scores, identified skill gaps | `ld_grade`, `ld_quiz_attempt`, Skill Gaps Analysis |
| Outbound | Compliance & Legal | Compliance training completion logs | `ld_enrollment` filtered by compliance-tagged courses |
| Outbound | Time & Attendance | Training session attendance data | `ld_conference_attendance` |
| Outbound | Workforce Analytics (Attrition, HR Dashboard, Predictive, Custom Report) | Training engagement, certification status/expiry, learning engagement trends | `ld_enrollment`, `ld_certificate`, `ld_progress` aggregates |
| Inbound | Employee Management | Employee docs/credentials, role/job history | Pre-fills learner profile, feeds prerequisite checks |
| Inbound | Performance Management | 360 feedback, appraisal results, turnover risk, recommended training plan | Feeds `assign-learner.php` (suggest/assign training based on appraisal gaps) |
| Inbound | Recruitment & Onboarding | Job test / assessment results | Can trigger auto-enrollment into onboarding Learning Paths |
| Inbound | Employee Recognition | Recognition-linked training eligibility | Unlocks specific courses/programs as a reward |

### Skill Gaps Analysis (new — required by this integration)
Referenced repeatedly across the diagram (Performance Management, Predictive Analytics, Skill Gaps Analysis box itself) but didn't exist in the structure yet — added:
```
instructor/analytics-subpage/ajax/
└── get-skill-gap-analysis.php     (compares required skills per role/position vs. skills demonstrated via completed content — powered by the ld_skill tagging system already in place)
```

**Note on implementation:** since the other modules are separate codebases built by other teams, treat `learning/api/` endpoints as if they're a real API even if the system technically shares one database for now (e.g. validate/sanitize inputs on `inbound/`, version the contract if it might change, document expected request/response JSON shape for each endpoint) — this avoids tight coupling that breaks when another team changes their side independently.

### Integration Reliability — required for `api/inbound/` and `api/outbound/`

**1. System-to-system authentication (separate from user login)**
Inbound endpoints are called *by another system*, not a logged-in browser user — they need their own credential, independent of the session-based auth used elsewhere in the app.
```
ld_api_key
  id, module_name ('performance-management'|'employee-management'|'recruitment'|'recognition'|...), api_key (hashed), is_active, created_at
```
Every `api/inbound/*.php` checks a header (e.g. `X-API-Key`) against this table before processing — reject immediately if missing/invalid, before touching any data.

**2. Idempotency**
A duplicate call (network retry, duplicate webhook) must not create duplicate records — e.g. two calls to `receive-appraisal-data.php` for the same appraisal shouldn't create two training recommendations.
```
ld_integration_event
  id, module_name, external_reference_id, event_type, processed_at
```
Each inbound handler checks: "has this `external_reference_id` already been processed?" — if yes, return the prior result instead of reprocessing.

**3. Failure handling / retry logging**
If an outbound pull fails (the calling module is down, or errors), or an inbound call fails validation, log it instead of letting it silently disappear.
```
ld_integration_log
  id, direction ('inbound'|'outbound'), module_name, endpoint, status ('success'|'failed'|'pending'), payload (JSON), error_message (nullable), created_at
```
Failed/pending entries can be manually reviewed or retried later (Admin-facing, see Audit Log placement below).

**4. Data format contract versioning**
Every inbound/outbound payload includes a `version` field so a future change on either side is detected rather than silently breaking.
```json
{ "version": "1.0", "data": { ... } }
```
`api/inbound/*.php` checks the version and rejects or branches logic if it doesn't recognize it, rather than assuming the shape is always current.

**5. Sync direction & ownership of truth**
For data pulled *into* L&D (e.g. Employee Management's role/job history used for prerequisite checks), L&D treats it as a **one-time pull at time of use**, not a live-synced mirror — if the source data changes later, it does not retroactively affect a learner already mid-course. Each inbound table (or the relevant L&D record) should store *when* that data was pulled (`synced_at`), so it's clear the information may be stale rather than assumed always current.

**6. Integration-specific audit log**
Separate from `ld_audit_log` (which tracks user actions within L&D) — `ld_integration_log` (above) serves this purpose already, scoped specifically to system-to-system calls. Surfaced to Admin as its own view, distinct from the user-action audit log:
```
admin/audit-subpage/ajax/
└── get-integration-log.php     (filterable by module, direction, status, date range)
```

---

## 8.5 Foundational Gaps — Classes, File Storage, Email

### Classes (OOP layer)
Every entity with AJAX needs a matching class in `classes/`, following the same shape as the existing `employee.php` (instantiate → call method → return result). **All class files confirmed added, using all-lowercase filenames** (consistent with the rest of the project's naming — pages, AJAX, folders are all lowercase):
```
classes/
├── employee.php
├── page.php
├── course.php               (add/edit/archive/get/duplicate/publish)
├── module.php
├── lesson.php
├── quiz.php                  (+ quiz session/timing: start, save-answer, submit, auto-submit)
├── evaluation.php
├── program.php
├── skill.php
├── calendarevent.php
├── certificate.php
├── learningpath.php
├── enrollment.php            (enroll, unenroll, assign-learner, accept/decline-invitation)
├── progress.php
├── grade.php
├── report.php                  (moderation/flagging)
├── announcement.php
├── comment.php
├── note.php
├── bookmark.php
├── favorite.php
├── rating.php
├── user.php                     (Admin's Instructor/Learner account management)
├── analytics.php                 (get-*-analytics.php dashboards)
├── archive.php                    (unified archive/restore)
└── setting.php                     (System Settings key-value store)
```
Example shape:
```php
class Course {
    public function getAll($filters = []) { /* SELECT from ld_course */ }
    public function create($data) { /* INSERT */ }
    public function edit($id, $data) { /* UPDATE */ }
    public function archive($id) { /* status = archived */ }
    public function duplicate($id) { /* deep copy, status = draft */ }
}
```
**Note:** the class name itself (e.g. `class Course`, `class LearningPath`, `class CalendarEvent`) still uses PascalCase per PHP convention — only the **filename on disk** is lowercase. Since Windows/XAMPP file systems are case-insensitive, this works locally; if ever deployed to a case-sensitive Linux server, make sure every `include`/`require` reference matches the lowercase filename exactly (not the PascalCase class name) to avoid broken includes.

### File / Attachment Storage
Lives under the existing project-root `assets/` folder, alongside the current static images:
```
learning/
└── assets/
    ├── bcp-logo.png
    ├── bg.jpg
    └── uploads/
        ├── lesson-materials/       (PDFs, slides — download-material.php)
        ├── course-thumbnails/       (course card images)
        ├── certificates/             (generated certificate PDFs)
        └── profile-photos/            (user avatars)
```
**Rule:** generate a unique filename on upload (e.g. UUID + original extension) — never store/serve the raw original filename. Store only the generated filename/path in the DB (e.g. `ld_material.file_path`). Avoids collisions and keeps URLs unguessable. Enforced against the File Upload Limit defined in System Settings.

### Email Notifications
In-app Notifications currently have no email counterpart. Recommended approach: **PHPMailer** (library) + the organization's existing SMTP server — no external account needed, full control, standard for internal corporate tools. (Alternative if a dedicated transactional API is preferred later: Brevo's free tier.)
```
classes/
└── Mailer.php     (wraps PHPMailer, called from Notification.php / Enrollment.php when an email-worthy event fires)
```
**Confirmed — notification types that trigger an email (in addition to the in-app notification):**

| # | Notification | Recipient | Email? |
|---|---|---|---|
| 1 | Course/Learning Path invitation (`assign-learner.php`) | Learner | ✅ Yes |
| 2 | Invitation accepted/declined | Instructor | No — in-app only |
| 3 | Certificate issued | Learner | ✅ Yes |
| 4 | New comment/question on their lesson | Instructor | No — in-app only |
| 5 | Reply to their comment | Learner | No — in-app only |
| 6 | Content flagged/reported (new report) | Instructor + Admin | ✅ Yes |
| 7 | Report reviewed/resolved | Instructor (responder) | No — in-app only |
| 8 | Report auto-archived (7-day expiry) | Admin | ✅ Yes |
| 9 | New announcement posted | All (per audience) | No — in-app only (may revisit as a per-announcement toggle later) |
| 10 | Quiz/Evaluation graded | Learner | ✅ Yes |
| 11 | Enrollment deadline approaching | Learner | ✅ Yes |
| 12 | Video Conference starting soon (reminder) | Learner + Instructor | ✅ Yes |
| 13 | Content requested (`request-content.php`) | Admin/Instructor | No — in-app only |
| 14 | Account created (welcome email) | New user | ✅ Yes |
| 15 | Password reset requested | User | ✅ Yes — required, not optional; this is how password reset functions |

`Notification.php` (or wherever notifications are dispatched from) checks this mapping per type and calls `Mailer.php` only for the types marked ✅.

### Course Publish Workflow
Course Duplication introduced `status = 'draft'`, but no explicit action moves a course from draft to visible-in-catalog.
```
instructor/elearning-subpage/ajax/
└── publish-course.php     (draft → active)
```

### Co-Instructor / Shared Course Ownership
Currently every course assumes a single owning instructor. Adding support for co-teaching/hand-off:
```
ld_course_instructor   (pivot)
  id, course_id, instructor_id, role ('owner' | 'co-instructor')
```
Access checks (e.g. who can edit/archive a course, who receives moderation flags for it) should check membership in this pivot table rather than a single `instructor_id` column on `ld_course`.

### Notification Preferences (per user)
Notifications are currently all-or-nothing. Adding per-user, per-type opt-in/out:
```
ld_notification_preference
  id, user_id, notification_type ('invitation'|'comment'|'moderation'|'announcement'|...), enabled (bool)

{role}/ajax/
├── get-notification-preference.php
└── edit-notification-preference.php
```
Defaults to all `enabled = true`; user can mute specific types without affecting others.

---

---

## 8.6 Quiz/Evaluation Structure, Lesson Content, & Evaluation Gating

### Quiz and Evaluation share the same underlying engine
Evaluation is configurable timed/untimed, just like Quiz — rather than building a separate system, Evaluation reuses the same question/session tables and mechanics (timing, randomization, question navigator, blind-by-default review). Same classes, same AJAX pattern, applied to a different parent (Evaluation attaches to **Course**, Quiz attaches to **Module**).

```
ld_quiz_question         (shared — used by both Quiz and Evaluation)
ld_quiz_question_option   (shared)
ld_quiz_session            (gains: item_type ENUM('quiz'|'evaluation'), reference_id → ld_quiz.id OR ld_evaluation.id)
ld_quiz_session_answer     (shared)
```

`ld_evaluation` gains the same fields as `ld_quiz`:
- `duration_seconds` (**nullable** — untimed is a valid configuration, unlike Quiz which defaults to 600)
- `show_answers_after_submit` (bool, default `false` — same blind-by-default rule as Quiz)

**Instructor AJAX — Evaluation question authoring (mirrors Quiz):**
```
instructor/elearning-subpage/ajax/
├── add-quiz-question.php / edit-quiz-question.php / archive-quiz-question.php / get-quiz-question.php
├── add-quiz-option.php / edit-quiz-option.php / archive-quiz-option.php
├── add-evaluation-question.php / edit-evaluation-question.php / archive-evaluation-question.php / get-evaluation-question.php
└── add-evaluation-option.php / edit-evaluation-option.php / archive-evaluation-option.php
```

**Learner AJAX — Evaluation taking (mirrors Quiz's start/save/submit/review flow):**
```
learner/study-subpage/ajax/progress/
├── start-evaluation.php           (same logic as start-quiz.php, PLUS the completion-gate check below)
├── save-evaluation-answer.php
├── mark-evaluation-question.php
├── get-evaluation-status.php
├── submit-evaluation-answer.php
├── auto-submit-evaluation.php
└── get-evaluation-result.php
```

### Evaluation completion gate
Unlike Quiz (answerable anytime within its module), **Evaluation can only be started after every module/lesson/quiz within the course has been completed** — it functions as the course's final assessment.

```php
// start-evaluation.php — gate check before creating the session
public function canStartEvaluation($learner_id, $course_id) {
    $totalItems = countAllModulesLessonsQuizzesInCourse($course_id);
    $completedItems = countCompletedItems($learner_id, $course_id); // from ld_progress
    return $completedItems >= $totalItems;
}
```
If not yet eligible, `start-evaluation.php` returns an error (e.g. *"Complete all course content before taking the final evaluation"*) instead of creating a session. This is distinct from `check-prerequisite.php`, which handles course-to-course prerequisites — this check is scoped *within* a single course.

### Lesson Content Types
A Lesson's actual content was previously undefined. Locked in as multi-format:
```
ld_lesson
  ... existing fields, plus:
  content_type ('video' | 'text' | 'file' | 'mixed')
  content_body     (rich text/HTML, when type includes 'text')
  video_url          (embed URL, when type includes 'video' — self-hosted or external e.g. YouTube/Vimeo)

ld_lesson_file      (separate table — a lesson can have multiple uploaded files, not just one)
  id, lesson_id, file_path, title, uploaded_at
```
`download-material.php` reads from `ld_lesson_file`, uploaded files following the same naming/collision rule as the rest of the File/Attachment Storage section (UUID filename, stored under `assets/uploads/lesson-materials/`).

---

## 8.7 Prerequisites, Backup, Messaging, Quiz Pooling & Attempt Limits

### Course Prerequisites — setting them (not just checking)
`check-prerequisite.php` already verifies a learner meets requirements before enrolling — but nothing previously let an instructor actually *define* those requirements when building a course. Added:
```
instructor/elearning-subpage/ajax/
├── add-prerequisite.php       ({ course_id, required_course_id | required_skill_id })
├── remove-prerequisite.php
└── get-prerequisite.php        (list current prerequisites for a course, shown while editing)
```
Backed by the existing `ld_prerequisite` table (`course_id`, `required_course_id | required_skill_id`) — no new table needed, just the missing CRUD.

### Admin Data Export / Backup
Full system export, distinct from `export-transcript.php` (which is a single learner's own record). This is Admin-only, system-wide.
```
admin/ajax/
├── export-data.php        (full or filtered export — e.g. all users, all courses, all enrollments — CSV/JSON)
└── backup-database.php     (full DB snapshot/dump, for disaster recovery — likely triggers a server-side mysqldump or equivalent, not a live query)
```
**Note:** `backup-database.php` should probably not run synchronously on a request — for a real database, this is better as a scheduled cron job (`cron/backup-database.php`) writing to a secured, non-public folder, with `admin/ajax/backup-database.php` only *triggering* an on-demand backup or listing/downloading existing backup files, not generating one inline on every click.

### Instructor Messaging / Inbox
Direct messaging between Instructor and a specific Learner, separate from per-lesson Comments (which are content-scoped, not private).
```
ld_message
  id, sender_id, recipient_id, subject, body, is_read (bool), created_at

instructor/ajax/
├── send-message.php
├── get-message.php          (inbox/thread view)
└── mark-message-read.php

learner/ajax/
├── send-message.php          (reply)
├── get-message.php
└── mark-message-read.php
```
Both roles share the same `ld_message` table and mostly the same AJAX shape — a message just has a `sender_id`/`recipient_id`, so the same class (`Message.php`, add to the Classes list) serves both sides.

### Quiz/Evaluation Question Pooling
Confirmed in scope. A quiz/evaluation can have a larger question bank than what's actually served per attempt — e.g. 20 questions written, only 10 randomly shown each time.
```
ld_quiz
  ... existing fields, plus:
  question_count (int, nullable — if set, this many questions are randomly selected from the full pool at start-quiz.php; if null, all questions in the pool are shown)

ld_evaluation
  ... same addition: question_count
```
`start-quiz.php` / `start-evaluation.php` logic: if `question_count` is set and less than the total pool size, randomly select that many question IDs (in addition to the existing shuffle-order and shuffle-options logic) and lock the selection into `ld_quiz_session.question_order` for that attempt.

### Max Quiz/Evaluation Attempts
Default confirmed: **2 attempts**, overridable per quiz/evaluation.
```
ld_quiz
  ... existing fields, plus:
  max_attempts (int, default 2)

ld_evaluation
  ... same addition: max_attempts (int, default 2)
```
**Enforcement — checked at `start-quiz.php` / `start-evaluation.php`:**
```php
public function canStartAttempt($learner_id, $quiz_id) {
    $attemptCount = countSessionsByLearnerAndQuiz($learner_id, $quiz_id); // ld_quiz_session rows with status 'submitted' or 'expired'
    $maxAttempts = getQuiz($quiz_id)->max_attempts;
    return $attemptCount < $maxAttempts;
}
```
If the limit is reached, `start-quiz.php`/`start-evaluation.php` returns an error (e.g. *"You've used all 2 attempts for this quiz"*) instead of creating a new session. `get-quiz-attempt.php` / `get-quiz-result.php` should also surface the best/latest score across attempts, since a learner may have multiple submitted sessions on record.

---

## 8.8 Pagination, Security, Backup Restore, Legal Pages, Preview Mode & Reminder Timing

### Pagination & Sorting Standard
Nearly every `get-*.php` list endpoint in this system (courses, modules, learners, notifications, audit log, analytics tables) currently returns all rows unpaginated — this will not scale. Applied as one system-wide convention rather than a per-file solution.

**Page size:** default **10 cards per page**, user-configurable to **20 or 30** (saved as a display preference, not just a one-off dropdown — see below).

**Display mode:** in addition to the default **grid (card) layout**, a **list view toggle** is available — same data, denser row-based display for users who prefer scanning over browsing cards. Applies to any browsable entity (Courses, Modules, Users, Learners, etc.), not just one page.

**Request:**
```
GET get-course.php?page=1&limit=20&sort=created_at&order=desc&view=grid
```
| Param | Meaning |
|---|---|
| `page` | which page of results |
| `limit` | rows per page — `10` (default) / `20` / `30` |
| `sort` | column to sort by |
| `order` | `asc` or `desc` |
| `view` | `grid` (default) or `list` — display mode, not a data change, but worth passing through so the backend can adjust what fields it returns (list view may need fewer/denser fields than a full card) |

**Backend:**
```php
$offset = ($page - 1) * $limit;
// SELECT * FROM ld_course ORDER BY {$sort} {$order} LIMIT {$limit} OFFSET {$offset}
```

**Response shape (standard across all list endpoints):**
```json
{
  "data": [ /* rows for this page */ ],
  "total": 142,
  "page": 1,
  "limit": 20
}
```

**Saved user preference:** page size and view mode (grid/list) are remembered per user, not reset every visit.
```
ld_display_preference
  id, user_id, page_size (default 10), view_mode ('grid'|'list', default 'grid')

{role}/ajax/
├── get-display-preference.php
└── edit-display-preference.php
```

**Filters:** the existing per-entity filters (e.g. Modules filterable by Course/Category/Skill, per section 5) apply on top of pagination — filters narrow the dataset first, pagination then pages through the filtered result, e.g. `get-module.php?course_id=5&skill_id=12&page=1&limit=20`.

### Security Basics

**CSRF protection** — required on every state-changing AJAX call (add/edit/archive/enroll/submit/etc.). Without it, a malicious site could trick a logged-in user's browser into silently submitting a request using their session cookies.
```php
// generated once per session
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// checked on every state-changing POST
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid request.');
}
```

**Input validation/sanitization** — every AJAX file must validate `$_POST`/`$_GET` data (type, format, length) before it reaches the database — combined with prepared statements, this also protects against SQL injection.

**Rate limiting** — needed on abuse-prone endpoints (`submit-quiz-answer.php`, `report-content.php`, `ask-question.php`) to prevent spam/scripted abuse — track requests per user/IP within a time window and block/delay past a threshold.

### Backup & Restore
`backup-database.php` (already documented) is scheduled/automatic. **Restore is deliberately kept out of the web UI** — a live database restore is destructive if triggered by accident, so it stays a manual, off-website operation.
```
learning/
└── cron/
    ├── backup-database.php     (scheduled, automatic)
    └── restore-database.php     (manual only — run via command line directly on the server, never exposed as a clickable button)
```
Admin can still **view and download** existing backup files from the browser (`get-backup-list.php`) — only the restore action itself is excluded from the UI.

### Terms of Service / Privacy Policy
```
pages/public/terms.php
pages/public/privacy.php
```
Static legal content, no auth required — same `public/` bypass rule as `verify-certificate.php`.

### Instructor "Preview as Learner"
A view-mode toggle, not a separate page — reuses the existing Learner-facing pages, rendered under the Instructor's session in a special preview mode.
```
instructor/elearning-subpage/ajax/
└── get-course-preview.php     (same data as get-enrolled-course.php, but bypasses the enrollment check since it's the owning instructor, not a real learner)
```
**Flow:** a "Preview" button on the instructor's course card opens `learner/study-subpage/course.php?preview=true&course_id=X`. The page-loader allows this only if the requester is the course's owning instructor (checked against `ld_course_instructor`); otherwise blocked. A banner at the top reads "You are previewing as a learner," with an exit button back to the instructor view.

### Video Conference Reminder — dual timing, configurable
Two reminders, not one — a "first alarm" and a "snooze"/second alarm, both configurable via System Settings.
```
ld_setting additions:
  video_conference_reminder_first_minutes    (default: 30)
  video_conference_reminder_second_minutes    (default: 15)

ld_video_conference
  ... existing fields, plus:
  first_reminder_sent (bool, default false)
  second_reminder_sent (bool, default false)
```
```
cron/send-video-conference-reminder.php
  → runs periodically (e.g. every 5 min)
  → finds conferences starting in ~30 min, first_reminder_sent = false → sends + marks sent
  → finds conferences starting in ~15 min, second_reminder_sent = false → sends + marks sent
```

---

## 8.9 Accessibility, Localization, Mobile, Environment Config & Content Versioning

### Accessibility (a11y) — kept in mind throughout
No dedicated accessibility page/feature, but a standing design principle across the whole system:
- Screen reader support and keyboard navigation on all interactive elements (especially the Quiz question-navigator grid and the Catalog/Study card grids)
- Never rely on color alone for status (e.g. pass/fail, active/archived) — pair color with text/icon, since red/green-only indicators fail for colorblind users
- The Quiz timer (time pressure) should not block reasonable accommodation — flagged as a design tension to keep in mind, no specific mechanism decided yet

### Localization
**English-only** for the interface. Some **content** (course/lesson material) may be authored in **Tagalog** by instructors, since the platform is Philippines-based — this is treated as **regular content data**, not a system-wide translation requirement. No multi-language UI/i18n framework needed; `ld_lesson.content_body` etc. can simply contain Tagalog text like any other free-text field.

### Mobile-Friendly
Confirmed requirement — **all Learner-facing pages must be responsive**, not desktop-only. Directly affects:
- The Quiz question-navigator grid (must remain usable on small screens)
- Catalog/Study grid-vs-list display mode (list view is often the better default on mobile)
- Video Conference "Join" links/buttons (must be easily tappable)
- The Vidnotes/comments panels and any modal-based Add/Edit forms across Instructor and Learner pages

### Environment / Config Management
**Deferred — documented now, not implemented yet.** Credentials (DB, SMTP) should eventually be loaded from environment config rather than hardcoded into class files, but this adds setup overhead (Composer or a custom loader, `.env` handling) that isn't worth tackling until closer to when SMTP/Mailer.php or deployment is actually being worked on. Revisit before this project goes anywhere near production or a shared Git repo — hardcoded credentials are fine for local solo development in the meantime, just don't commit them.

**Planned structure, for when it's picked back up:**
```
learning/
├── .env                    (NEVER committed — actual secrets)
├── .env.example              (committed — template with dummy values, documents required keys)
└── config/
    └── config.php              (loads .env, exposes constants/variables to classes that need them — e.g. the DB connection, Mailer.php)
```
**`.env` holds (at minimum):**
```
DB_HOST=localhost
DB_NAME=learning_db
DB_USER=root
DB_PASS=

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=

APP_ENV=development
```
**`.gitignore` additions (once in use):**
```
.env
/cron/backups/*
```

### Content Versioning
When an instructor edits a **published** Course that learners have already completed, a learner's Certificate/Grade must reflect the course **as it was at the time they completed it**, not the current edited version — important for compliance/audit accuracy.
```
ld_course_version
  id, course_id, version_number, snapshot (JSON — full course structure at time of publish), published_at

ld_certificate
  ... existing fields, plus:
  course_version_id     (locks the certificate to the exact version completed)

ld_enrollment
  ... existing fields, plus:
  course_version_id     (locks the enrollment to the version the learner started/completed)
```
**Trigger:** a new `ld_course_version` snapshot is created whenever `publish-course.php` is called on a course that already has active enrollments — not on every minor edit, only on a deliberate re-publish. Draft edits (unpublished) don't need versioning since no learner has started them yet.

### Dark Mode
Piggybacks on the existing `ld_display_preference` table (already holds `page_size`, `view_mode`):
```
ld_display_preference
  ... existing fields, plus:
  theme ('light'|'dark', default 'light')

{role}/ajax/
└── edit-display-preference.php   (existing endpoint, now also accepts theme)
```

### Print-Friendly Certificate / Transcript View
Separate from the existing PDF download (`download-certificate.php`, `export-transcript.php`) — a browser-native print view, useful when a learner just wants to print directly without generating/downloading a file first.
```
learner/result-subpage/
├── certificate-print.php     (print-optimized HTML view of a certificate — CSS @media print styling, no nav/sidebar chrome)
└── transcript-print.php       (same treatment for the full transcript)
```

---

## 8.10 Database Table Reference (`ld_*`)

Full list of all tables in the schema (`ld_schema.sql`), 45 total, grouped to match this doc's sections:

**Core Content**
`ld_course`, `ld_course_instructor`, `ld_course_version`, `ld_module`, `ld_lesson`, `ld_lesson_file`

**Quiz & Evaluation Engine**
`ld_quiz`, `ld_evaluation`, `ld_evaluation_feedback`, `ld_quiz_question`, `ld_quiz_question_option`, `ld_quiz_session`, `ld_quiz_session_answer`, `ld_quiz_attempt`

**Training: Programs, Skills, Video Conference, Calendar**
`ld_program`, `ld_skill`, `ld_module_skill`, `ld_course_skill`, `ld_video_conference`, `ld_conference_attendance`, `ld_calendar_event`

**Learning Path**
`ld_learning_path`, `ld_learning_path_item`

**Enrollment, Progress, Grades**
`ld_enrollment`, `ld_progress`, `ld_grade`

**Certificate**
`ld_certificate`

**Catalog Engagement**
`ld_rating`, `ld_comment`, `ld_note`, `ld_bookmark`, `ld_favorite`, `ld_request`, `ld_prerequisite`

**Moderation**
`ld_report`

**Notifications, Announcements, Messaging**
`ld_notification`, `ld_notification_preference`, `ld_announcement`, `ld_message`

**Settings, Audit, Display Preferences**
`ld_setting`, `ld_audit_log`, `ld_display_preference`

**Cross-Module Integration**
`ld_api_key`, `ld_integration_event`, `ld_integration_log`

**Note:** no `ld_user`/`ld_employee` table is included in the schema — `instructor_id`/`learner_id`/`user_id` columns throughout reference the existing Employee/User system outside `pages/`, not a table defined here. Full `CREATE TABLE` statements with all fields, types, and foreign keys live in `ld_schema.sql`.

---

## 9. Project Conventions / Rules

1. **Naming should be in singular form** — files, pages, and AJAX endpoints use singular naming (e.g. `course.php`, `add-course.php`, `get-instructor-analytics.php`), not plural.
2. **Learning-related data must use the `ld_*` prefix in SQL/database structure** — all tables tied to the Learning & Development module (e.g. courses, modules, lessons, quizzes, evaluations, programs, skills, video conferences, calendar events) should be named with the `ld_` prefix, e.g. `ld_course`, `ld_module`, `ld_lesson`, `ld_quiz`, `ld_evaluation`, `ld_program`, `ld_skill`, `ld_video_conference`, `ld_calendar_event`.
3. **"Report" (the analytics dashboards) has been renamed to "Analytics" to eliminate confusion with content flagging — two separate concepts, do not mix them:**
   - **Analytics** (`analytics-subpage/`, hub page `analytics.php`) — read-only dashboards: Learning, Learner, Instructor, Certificate, Enrollment, Progress (Instructor side); Overview, User, Instructor, System (Admin side). All AJAX here is `get-*-analytics.php` only; nothing is actioned or responded to.
   - **Flagged/Reported Content** (`moderation-subpage/`, hub page `moderation.php`) — a learner flagging a course/module/lesson/comment as inappropriate or broken. This is content moderation, not analytics, and lives in its own subpage: `get-report.php` (flags on the instructor's own content) and `respond-report.php` (instructor's response/action note). Admin's parallel `moderation-subpage/` handles system-wide review/resolution via `review-report.php`.
   - This is the only place "report" still appears as a term — it now exclusively means the flagging/moderation feature. Analytics dashboards no longer use the word "report" anywhere (filenames, folders, or UI labels).
4. **When an entity moves from a shared `ajax/` folder into its own dedicated `{name}-subpage/ajax/`, delete the old copies from the shared folder — do not leave both.** This happened twice already (Video Conference and Analytics both left stale duplicates behind in `instructor/ajax/` after getting their own subpages). Before deleting, confirm no page still `include`s or fetches the old path, then remove the old files entirely. A shared `ajax/` folder should only ever contain endpoints for pages that have **no** subpage of their own.
