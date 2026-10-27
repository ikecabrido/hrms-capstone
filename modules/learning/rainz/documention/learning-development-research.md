# Learning and Development System — Curated Documentation

Research reference for the **Learning & Development module** of the HRMS capstone project. All structure, table names, page paths, and flows below were read from the source tree; version numbers were confirmed against the working environment.

---

## 1. Introduction

### 1.1 Background of the Capstone Project

Learning and development is the process through which people acquire the knowledge, skills, attitudes, and behaviors needed to perform effectively. Technology-enabled learning and development systems are now widely used to deliver, track, and monitor learning activities.

The module documented here is a server-side web application built for a human-resources capstone context. It brings together course authoring, enrolment, progress tracking, assessment, certification, skills management, training programmes, video conferences, learning paths, notifications, and cross-module integration. Its purpose is to replace manual record-keeping and reporting with a single system that stores learner profiles, courses, attendance, assignments, qualifications, progress, and generated reports, and that notifies learners about schedules, assignments, examinations, and certificate events.

The system is designed around three operational roles — **System Administrator**, **Instructor / Trainer**, and **Learner / Employee** — with additional read or advisory access for **Manager / Supervisor** and **Compliance Officer / Auditor**.

### 1.2 Context and Scope

The system supports learning and training activities including information management, material management, training schedules, assessments, progress monitoring, and report generation.

In scope:

- **Administration** — user management, programmes, learning paths, settings, moderation, audit, and analytics.
- **Instructor / Trainer activities** — authoring and managing learning materials and assessments; providing feedback; monitoring learner progress; issuing and extending certificates; responding to training requests.
- **Learner activities** — discovering available content in the catalog; enrolling; studying enrolled content; taking quizzes and evaluations; viewing progress, grades, and certificates; taking personal notes; managing a calendar of sessions and deadlines.

The module does not re-implement the underlying employee or user-account system. Learner, instructor, and administrator identities are drawn from the existing HRMS employee and user-account tables.

### 1.3 Problem Statement

The capstone context is an applied business project in human resource management. The learning and development system exists to consolidate practices that are otherwise handled with manual record-keeping, separate spreadsheets, and ad-hoc reporting — practices that are slow, difficult to track, and weak at monitoring progress or producing consistent reports.

The system addresses the need for:

- A shared place to manage learning programmes, courses, modules, lessons, quizzes, and evaluations.
- enrolment and progress tracking tied to identifiable learners and courses.
- Automated certificates when course completion rules are met.
- Skills tracking and skill-gap visibility for instructors and learners.
- Training programmes, video conferences, and a calendar for scheduled learning activities.
- Reporting and analytics for administrators and instructors.
- Controlled integration with other HRMS modules — performance management, compliance, attendance, workforce analytics, recruitment, employee management, engagement, and exit/knowledge transfer — through a small REST API layer.

### 1.4 Objectives and Goals

**General objective**

Support learning and development by giving administrators, instructors, and learners a shared digital environment for managing learning activities, materials, schedules, assessments, progress, and reports.

**Specific objectives**

- Support the creation and organization of learning content — courses, modules, lessons, quizzes, and evaluations.
- Support enrolment and tracking of learner participation in courses and scheduled training.
- Record lesson completion, quiz attempts, evaluations, grades, and certificates.
- Provide progress monitoring and reporting for instructors and administrators.
- Link learning content to skills and make skill gaps visible.
- Support training requests, learning paths, video conferences, notifications, and a shared calendar.
- Expose selected learning outcomes to other HRMS modules through an integration API.

**Goals**

- Improve learner knowledge, skills, and engagement with learning activities.
- Provide a structured environment for assessments, feedback, and progress review.
- Make it easier to monitor completion, performance, and certification status.
- Strengthen skills visibility across courses, modules, and learners.
- Support scheduled trainings and video conferences alongside self-paced e-learning.
- Maintain the learning record in a database that can feed reports and integrations.

---

## 2. System Context and Architecture

### 2.1 Environment

| Component | Version | Notes |
|---|---|---|
| PHP | 8.2.12 | Server-side runtime |
| MariaDB | 10.4.32 | Database, InnoDB, `utf8mb4_general_ci` |
| XAMPP | Apache + PHP + MariaDB bundle | Development host environment |
| phpMyAdmin | 5.2.1 | Database administration |

The module does not use a front-end framework. Presentation is built from a custom token-driven CSS system and small vanilla-JavaScript modules. Font Awesome 6.5.1 is loaded for iconography; Quill 1.3.7 is used for rich-text lesson editing.

### 2.2 Runtime model

The module is a single-entry-page application. A request arrives at `modules/learning/index.php?page=<role>/<page>`, and `classes/Page.php` resolves the requested page to a PHP file under `pages/<role>/...`. The page controller also maps the role to a permitted navigation tree and refuses unknown page paths.

There are three role portals:

| Role key | Portal home page |
|---|---|
| `admin` | `admin/admin-home` |
| `instructor` | `instructor/instructor-home` |
| `learner` | `learner/learner-home` |

Navigation is rendered from `includes/sidebar.php` and `includes/header.php`, and page content is loaded into the main container area. Background pages such as `dashboard-overview.php` are shared entry points outside the role trees.

### 2.3 Layers

The system is best understood as five cooperating layers:

1. **Presentation layer** — HTML pages, custom CSS, and small JS modules for the shell, dropdowns, file upload, modals, and page loading.
2. **Application layer** — role portals, page controllers, and the domain classes that implement authoring, enrolment, progress, certification, skills, notifications, moderation, and reporting.
3. **Data layer** — MariaDB with 57 learning-and-development tables plus shared HRMS tables for employees, user accounts, roles, and departments.
4. **Integration layer** — a small JSON REST API under `modules/learning/api/` for inbound synchronization and outbound reporting to other HRMS modules.
5. **Security and governance** — session-based sign-in, role gating through the page controller, PDO prepared statements, an audit log, and per-integration API keys.

### 2.4 Not part of this system

This module does not include a charting library, a calendar JS library, a PDF generation library, an SMTP mailing library, Bootstrap, or a Composer-managed dependency tree. Functionality that depends on those concerns is either implemented directly in PHP and markup, left as in-app only, or documented as an integration that another module provides.

---

## 3. Users and Roles

### 3.1 Role summary

| Role | Primary responsibilities |
|---|---|
| System Administrator | Manage users, settings, notifications, calendar, moderation, audit, backups, integration keys, and system analytics. |
| L&D Administrator | Manage the catalog, learning paths, programmes, settings, and reports. |
| Instructor / Trainer | Author courses, modules, lessons, quizzes, and evaluations; manage programmes and video conferences; grade and issue certificates; view learner progress and skill gaps; respond to training requests. |
| Manager / Supervisor | Submit training requests and review team progress and skill gaps where applicable. |
| Learner / Employee | Browse the catalog, enroll in courses, study enrolled content, take quizzes and evaluations, view progress, grades, and certificates, take notes, and manage personal calendar events. |
| Compliance Officer / Auditor | Review audit trails and consume compliance-related completion records through the integration layer. |

### 3.2 Role access model

The page controller separates pages into role portals. Instructor-facing pages are also available to administrators. Learner-facing pages are available to learners. The sidebar is built from the set of pages each role is permitted to see, so the visible menu reflects the role rather than a single shared menu.

Instructor and administrator roles share the instructor page space; they do not maintain separate copies of the same instructor pages.

---

## 4. Functional Modules and User Flows

### 4.1 Login and entry

Users sign in through the portal sign-in page. After sign-in, the system identifies the user’s role and directs them to the appropriate portal home page. A shortcut exists to move from the main HRMS entry point into the learning and development portal.

### 4.2 Administration

The administrator portal provides:

- User management for instructors and learners.
- System analytics and reporting.
- Calendar management.
- Notification management and history.
- Audit log review.
- Moderation of reported content.
- Settings and archive management.
- Gradebook and learner/timeline views.
- Recognition and recommendation mapping where configured.
- Certificate extension and revocation.

### 4.3 Instructor — e-learning authoring

The instructor e-learning flow is hierarchical:

1. **Course** — top-level container.
2. **Module** — belongs to a course.
3. **Lesson** — belongs to a module.
4. **Quiz** — belongs to a module.
5. **Evaluation** — attached directly to a course.

Authors can browse each entity as cards, add new items, edit existing items, and archive items through status rather than hard deletion. When browsing a course, its modules and evaluations are reachable from that course’s context. When browsing a module, its lessons and quizzes are reachable from that module’s context.

Lessons use a rich-text editor for content. Quizzes and evaluations carry questions, answer options, and correctness flags. Questions can be added to quizzes during course and quiz editing, and evaluations can also carry questions.

Skills can be tagged onto modules and courses from a shared master skill list.

### 4.4 Instructor — training management

Instructors manage:

- **Programs** — training programmes with skills attached.
- **Video conferences** — scheduled sessions with platform, meeting link, duration, reminders, and attendance.
- **Training requests** — learner requests for training tied to skill gaps, which instructors can review, accept, reject, or mark complete.
- **Learning paths** — ordered sequences that can combine courses, modules, lessons, quizzes, evaluations, programs, and video conferences.
- **Skills** — shared skill definitions used across content and training.
- **Certificates and certificate templates** — course-level certification rules and issued certificates.
- **Learner assignment and bulk enrollment** — assigning learners to courses and enrolling groups.

### 4.5 Instructor — progress and reporting

Instructors can view:

- Learner rosters and enrollment status per course.
- Progress dashboards.
- Grade books and scores.
- Skill-gap information per learner.
- Learner timelines.
- Certificate issuance and validity.
- Reports and analytics appropriate to the instructor role.

### 4.6 Learner — catalog

The catalog is the learner’s browse view of available content. Learners can:

- Browse courses, modules, lessons, quizzes, evaluations, programs, and video conferences.
- Filter content by category, skill, and other available criteria.
- Check prerequisites.
- View recommended content.
- Enroll in courses.
- Rate courses, ask questions, comment, and request content.
- Bookmark and favorite items.
- Report content for moderation.

### 4.7 Learner — study

Study is the enrolled view. Learners can:

- Resume enrolled content from the last incomplete item.
- Read lessons and consume materials.
- Mark lessons complete.
- Take quizzes and submit answers.
- View quiz attempts and reviews.
- Take personal notes attached to any content level.
- Unenroll from courses.
- View progress for enrolled courses and modules.

Study scopes its queries to content the learner is already enrolled in or has progress on; it does not provide the discovery actions that exist in the catalog.

### 4.8 Learner — results, certificates, skills, and calendar

Learners can view:

- Certificates and download issued certificates.
- Grades, averages, transcripts, and evaluation feedback.
- Skill summaries derived from completed content.
- Their assigned learning paths and the items within them.
- A calendar of video conferences, enrollment deadlines, and personal events.

### 4.9 Notifications, calendar, and collaboration

The system supports:

- In-app notifications for enrollment, training requests, conference reminders, certificate events, and other system events.
- A shared calendar of events, sessions, and deadlines.
- Messages, comments, announcements, and personal notes as collaboration artifacts.

### 4.10 Background jobs

The module includes scripts intended to be run on a schedule:

- Skill snapshot materialization — writes per-learner skill snapshots with position and department context.
- Engagement snapshot materialization — writes per-learner engagement and risk indicators.
- Video conference reminder dispatch — sends in-app reminders ahead of scheduled conferences and auto-completes past sessions.
- Database backup and restore — creates timestamped database dumps and prunes old ones.

These jobs write logs beside themselves to record when they have run.

---

## 5. Data Model

### 5.1 Database and conventions

- Database: `hrms`.
- Storage engine: InnoDB.
- Default collation: `utf8mb4_general_ci`.
- Learning-and-development tables use the `ld_` prefix.
- No views or triggers are currently defined in the learning database.

### 5.2 Table groups

**Content hierarchy**

- `ld_course`
- `ld_module`
- `ld_lesson`
- `ld_quiz`
- `ld_quiz_question`
- `ld_quiz_question_option`
- `ld_lesson_file`

**Enrollment and progress**

- `ld_enrollment`
- `ld_progress`
- `ld_quiz_attempt`
- `ld_quiz_session`
- `ld_quiz_session_answer`
- `ld_grade`
- `ld_assessment_result`
- `ld_certificate`
- `ld_certificate_template`
- `ld_evaluation`
- `ld_evaluation_feedback`

**Paths, programs, and templates**

- `ld_learning_path`
- `ld_learning_path_item`
- `ld_learning_path_skill`
- `ld_program`
- `ld_program_skill`
- `ld_course_template`
- `ld_course_version`
- `ld_course_instructor`
- `ld_prerequisite`

**Skills and competency**

- `ld_skill`
- `ld_course_skill`
- `ld_module_skill`
- `ld_skill_snapshot`
- `ld_engagement_snapshot`
- `ld_training_recommendation`
- `ld_recommendation_course_map`

**Recognition**

- `ld_recognition_course_map`
- `ld_recognition_unlock`

**Collaboration and communication**

- `ld_notification`
- `ld_notification_preference`
- `ld_message`
- `ld_comment`
- `ld_note`
- `ld_announcement`
- `ld_calendar_event`
- `ld_user_event`
- `ld_video_conference`
- `ld_conference_attendance`
- `ld_rating`
- `ld_bookmark`
- `ld_favorite`

**Reporting and administration**

- `ld_report`
- `ld_setting`
- `ld_display_preference`
- `ld_audit_log`
- `ld_request`
- `ld_training_requests`

**Integration**

- `ld_api_key`
- `ld_integration_log`
- `ld_integration_event`

**Shared HRMS tables referenced by the module**

- `em_employees`
- `user_account`
- `em_roles`
- `em_departments`

### 5.3 Key relationships

- Courses contain modules, which contain lessons and quizzes.
- Evaluations attach to courses rather than to modules or lessons.
- Enrollments tie learners to courses and carry the overall enrollment status.
- Progress records track completion of individual modules, lessons, quizzes, and evaluations within an enrollment.
- Quiz attempts and quiz sessions record quiz-level participation and answers.
- Grades record course-level final scores.
- Certificates are issued against completed enrollments and can be extended or revoked.
- Skills are shared master records that can be tagged onto courses and modules.
- Learning paths are ordered collections of mixed content types referenced by ID.
- Programs and video conferences are separate scheduled-training concepts.
- Notifications, messages, comments, notes, bookmarks, favorites, ratings, and announcements support the social and tracking side of the system.
- Integration tables support API keys and the logging of integration activity.

---

## 6. Integration and Cross-Module Data Flow

### 6.1 Integration layer

The learning module exposes a JSON REST API under `modules/learning/api/`. Calls are authenticated with an API key passed as `X-API-Key` or `Bearer <key>`. The API is validated through `classes/apiauth.php`, and every call is recorded through `classes/integrationlog.php` into the integration log and integration event tables.

### 6.2 Inbound synchronization

The following inbound endpoints receive data from other HRMS modules:

- Employee profile synchronization.
- Appraisal data for recommendations and skill gaps.
- Job test or assessment results from recruitment.
- Recognition eligibility information.

### 6.3 Outbound reporting

The following outbound endpoints send learning data to other HRMS modules:

- Training completion to performance management.
- Learning performance, including grades, quiz scores, and skill proficiency, to performance management.
- Compliance training completion logs to the compliance module.
- Attendance data to time and attendance.
- Workforce analytics data, including skill gap analysis and reports, to workforce analytics.

Optional filters such as learner, course, and status are supported on the outbound endpoints.

### 6.4 Integration significance

The integration layer is the principal way the learning module contributes to the wider HRMS. It does not duplicate other modules’ tables; instead, it publishes learning outcomes — completions, scores, compliance-tagged training, attendance, skill gaps, and analytics — for consumption by performance management, compliance, attendance, and workforce analytics.

---

## 7. Security and Governance

### 7.1 Authentication and session

The module uses session-based sign-in. After sign-in, the user’s role is stored in the session and used to determine which portal and pages are available.

### 7.2 Role-based access

Page access is controlled by the page controller, which maintains a whitelist of permitted role-to-page mappings and refuses unknown pages. Role checks also appear in selected AJAX endpoints.

### 7.3 Data access

Database access uses PDO prepared statements throughout the module.

### 7.4 API authentication

Cross-module API access is gated by per-integration API keys stored in the integration key table.

### 7.5 Audit

The module maintains an audit log written from the administrator audit interface, recording user actions against content types and records.

---

## 8. Implementation Notes for Research

### 8.1 Module size and composition

The learning module is composed of:

- 502 PHP files.
- 35 domain and infrastructure classes.
- 57 learning-and-development database tables.
- 9 integration endpoints.

### 8.2 Front-end approach

The module does not rely on a front-end framework. Its presentation layer is a custom CSS token system with a small set of vanilla JavaScript modules for shell behavior, dropdowns, file upload, modals, and page loading. This is deliberate and is reflected in the system architecture: the presentation layer is thin, and most logic lives in the application and data layers.

### 8.3 Curriculum and content model

The content model is hierarchical for self-paced e-learning — course, module, lesson, quiz — with evaluation attached at the course level. It is mixed and ordered for learning paths, which can combine multiple content types regardless of shared parentage. Skills act as a shared tag layer across content and training.

### 8.4 Enrollment and completion model

Enrollment is the bridge between learners and courses. Progress is tracked at item level inside an enrollment. Completion, grading, and certification are tied together so that certificates can be issued once a course’s completion rules are satisfied.

### 8.5 Training model

Training is broader than self-paced e-learning. It includes programs, video conferences, learning paths, skills, training requests, and a calendar. Video conferences carry their own attendance and reminder semantics.

### 8.6 Integration model

Integration is unidirectional and event-based rather than a full shared schema. Other modules push data in when relevant — employee profiles, appraisal data, job test results, recognition eligibility — and the learning module pushes learning outcomes out — completions, performance, compliance logs, attendance, and workforce analytics.

---

## 9. Summary

The Learning and Development system is a role-based, server-side web application for managing e-learning content, enrollment, progress, assessment, certification, skills, scheduled training, notifications, and cross-module reporting. It is organized around three portals — administrator, instructor, and learner — supported by 35 classes, 57 learning-and-development tables, and a small integration API that connects learning outcomes to performance management, compliance, attendance, and workforce analytics.

Its distinguishing design choices are a custom presentation layer instead of a framework, a hierarchical content model with skills as a shared tag layer, an enrollment-and-progress model that separates course-level status from item-level completion, and a defined integration surface that lets learning data feed the wider HRMS without merging the modules’ databases.
