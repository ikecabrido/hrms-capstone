# Learner Module --- Additions and Fixes Documentation

**Project area:** `modules/learning/pages/learner/`\
**Scope:** Learner-side code only\
**Status:** Completed batches 1--4

------------------------------------------------------------------------

## 1. Scope and Change Policy

All changes documented here are limited to the learner-side
implementation.

No changes were intentionally made to unrelated modules or to the shared
learning classes.

The learner-side implementation is located at:

``` text
modules/learning/pages/learner/
```

> Note: The original project reference to `modules/learner/` does not
> match the extracted project structure. The actual learner area is
> under `modules/learning/pages/learner/`.

------------------------------------------------------------------------

# 2. Batch 1 --- Notifications and Announcements

## Added

The following learner AJAX endpoints were implemented:

``` text
modules/learning/pages/learner/ajax/get-announcement.php
modules/learning/pages/learner/ajax/get-overview-notification.php
modules/learning/pages/learner/ajax/get-history-notification.php
modules/learning/pages/learner/ajax/get-pending-notification.php
modules/learning/pages/learner/ajax/mark-notification-read.php
modules/learning/pages/learner/ajax/mark-all-notification-read.php
```

## Functionality

### Announcements

`get-announcement.php`

-   Loads active announcements.
-   Restricts announcements to the learner/all audience.
-   Ignores expired announcements.
-   Supports a configurable result limit.
-   Returns JSON.

### Notification overview

`get-overview-notification.php`

-   Loads recent notifications for the logged-in learner.
-   Returns unread notification count.
-   Does not accept a learner ID from the client.

### Notification history

`get-history-notification.php`

-   Loads paginated notification history.
-   Supports `limit` and `offset`.
-   Returns total count and `has_more`.

### Pending notifications

`get-pending-notification.php`

-   Returns unread notifications only.
-   Scoped to the logged-in learner.

### Mark one notification as read

`mark-notification-read.php`

-   Requires `POST`.
-   Accepts notification ID.
-   Verifies the notification belongs to the logged-in learner before
    updating it.

### Mark all notifications as read

`mark-all-notification-read.php`

-   Requires `POST`.
-   Marks only the logged-in learner's unread notifications as read.

## Security improvement

Notification endpoints use the authenticated session:

``` php
$_SESSION['employee_id']
```

instead of trusting a submitted learner/user ID.

------------------------------------------------------------------------

# 3. Batch 2 --- Catalog Discovery

The learner catalog discovery layer was implemented for:

``` text
modules/learning/pages/learner/catalog-subpage/ajax/discover/
```

## Added endpoints

``` text
get-course.php
get-module.php
get-lesson.php
get-quiz.php
get-evaluation.php
get-skill.php
get-program.php
get-learning-path.php
```

## Course discovery

`get-course.php`

Supports:

-   retrieving a single active course by ID
-   retrieving active courses
-   JSON responses
-   learner authentication

## Module discovery

`get-module.php`

Supports:

-   retrieving a module by ID
-   retrieving modules belonging to a course
-   active-module filtering

## Lesson discovery

`get-lesson.php`

Supports:

-   retrieving a lesson by ID
-   retrieving lessons belonging to a module
-   active-lesson filtering

## Quiz discovery

`get-quiz.php`

Supports:

-   retrieving a quiz by ID
-   retrieving quizzes belonging to a module
-   active-quiz filtering

## Evaluation discovery

`get-evaluation.php`

Supports:

-   retrieving an evaluation by ID
-   retrieving evaluations belonging to a course
-   active-evaluation filtering

## Skill discovery

`get-skill.php`

Supports:

-   retrieving a skill by ID
-   retrieving active skills

## Program discovery

`get-program.php`

Supports:

-   retrieving a program by ID
-   retrieving active programs

The implementation does not invent learner-specific program assignment
rules where the current schema does not provide them.

## Learning path discovery

`get-learning-path.php`

Supports:

-   retrieving an active learning path
-   retrieving ordered learning-path items
-   checking learner-specific assignment when `assigned_to` is present
-   returning path items using `order_index`

The learning-path item data comes from:

``` text
ld_learning_path_item
```

------------------------------------------------------------------------

# 4. Batch 3 --- Prerequisites and Recommendations

## Added prerequisite checking

File:

``` text
modules/learning/pages/learner/catalog-subpage/ajax/discover/check-prerequisite.php
```

The endpoint checks prerequisites configured for a course.

Supported prerequisite types:

-   required course
-   required skill

### Course prerequisite

A course prerequisite is satisfied when the learner has an enrollment
with:

``` text
status = completed
```

for the required course.

### Skill prerequisite

The current schema does not contain a separate learner skill-acquisition
table.

Therefore, skill completion is inferred from completed courses mapped
through:

``` text
ld_course_skill
```

A required skill is considered satisfied when the learner has completed
a course associated with that skill.

## Prerequisite response

The endpoint returns:

``` text
can_enroll
prerequisites_met
total_prerequisites
satisfied_count
missing_count
satisfied
missing
```

This allows the frontend to explain why a learner cannot enroll.

------------------------------------------------------------------------

# 5. Course Recommendations

File:

``` text
modules/learning/pages/learner/catalog-subpage/ajax/discover/get-recommended.php
```

A first-pass recommendation system was added.

## Candidate filtering

The system:

-   considers active courses
-   excludes courses the learner is already enrolled in
-   considers completed-course skills
-   considers bookmarks
-   considers favorites

## Recommendation scoring

Current scoring:

``` text
+10 per matching learner skill
+3  bookmarked course
+5  favorite course
```

Courses are ranked by recommendation score.

Title is used as the secondary ordering.

## Returned recommendation metadata

Each course may include:

``` text
skills
matching_skills
recommendation_score
is_bookmarked
is_favorite
enrollment_closed
```

This gives the catalog UI enough information to explain why a course is
being recommended.

------------------------------------------------------------------------

# 6. Batch 4 --- Enrollment

## Enrollment endpoint

File:

``` text
modules/learning/pages/learner/catalog-subpage/ajax/enrollment/enroll.php
```

The endpoint was updated to:

-   require `POST`
-   authenticate the learner through the session
-   verify the course exists
-   verify the course is active
-   verify the enrollment deadline
-   check prerequisites before enrollment
-   prevent the client from selecting another learner's ID

## Enrollment lookup

File:

``` text
modules/learning/pages/learner/catalog-subpage/ajax/enrollment/get-enrollment.php
```

Supports:

-   retrieving enrollment for a specific course
-   retrieving the learner's enrollment list
-   optional status filtering

A course-specific lookup returns:

``` json
{
  "success": true,
  "item": {},
  "enrolled": true
}
```

## Unenrollment

File:

``` text
modules/learning/pages/learner/catalog-subpage/ajax/enrollment/unenroll.php
```

The endpoint was updated to:

-   require `POST`
-   use the authenticated learner
-   require a course ID
-   remove the learner's own enrollment through the existing enrollment
    class

------------------------------------------------------------------------

# 7. Enrollment Include-Path Fix

Several enrollment endpoints had an incorrect relative include depth for
the enrollment class.

The learner enrollment files use:

``` php
require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
```

instead of:

``` php
require_once dirname(__FILE__, 5) . '/classes/enrollment.php';
```

This applies to:

``` text
enroll.php
get-enrollment.php
unenroll.php
accept-invitation.php
decline-invitation.php
```

The invitation endpoints were identified as requiring the same
correction.

------------------------------------------------------------------------

# 8. Bookmarks

## Add bookmark

File:

``` text
modules/learning/pages/learner/catalog-subpage/ajax/personal/bookmark-content.php
```

Supports bookmarking learner-visible content.

Allowed content types include:

``` text
course
module
lesson
quiz
program
skill
learning_path
evaluation
```

The learner ID is always taken from the authenticated session.

## Remove bookmark

File:

``` text
modules/learning/pages/learner/catalog-subpage/ajax/personal/remove-bookmark.php
```

Removes a bookmark using:

``` text
learner + item type + reference ID
```

rather than trusting a bookmark ID supplied by another user.

------------------------------------------------------------------------

# 9. Favorites

## Add favorite

File:

``` text
modules/learning/pages/learner/catalog-subpage/ajax/personal/favorite-content.php
```

Supports the same learner content types as bookmarks.

## Remove favorite

File:

``` text
modules/learning/pages/learner/catalog-subpage/ajax/personal/remove-favorite.php
```

Removes a favorite using the authenticated learner and content
reference.

------------------------------------------------------------------------

# 10. Security and Consistency Improvements

Across the batches, learner-facing endpoints follow these rules:

### Authentication

Endpoints verify:

``` php
$_SESSION['employee_id']
```

before performing learner-specific operations.

### No client-controlled learner identity

The API does not rely on:

``` text
learner_id
user_id
```

submitted by the browser for learner ownership.

### Ownership checks

Operations such as marking notifications read are restricted to records
belonging to the authenticated learner.

### HTTP methods

Mutation endpoints require `POST`.

Read endpoints use normal request parameters.

### JSON responses

Learner AJAX endpoints consistently return JSON and appropriate HTTP
status codes for:

``` text
401 Unauthorized
404 Not Found
405 Method Not Allowed
422 Validation Error
500 Server Error
```

### Active-content filtering

Catalog discovery endpoints generally expose only content marked active.

------------------------------------------------------------------------

# 11. Current Learner Flow

The implemented pieces now support this flow:

``` text
Learner
   │
   ▼
Open catalog
   │
   ├── Discover courses
   ├── Discover modules
   ├── Discover lessons
   ├── Discover quizzes
   ├── Discover evaluations
   ├── Discover skills
   ├── Discover programs
   └── Discover learning paths
   │
   ▼
Check prerequisites
   │
   ├── Required courses
   └── Required skills
   │
   ▼
Enroll
   │
   ▼
Save course
   ├── Bookmark
   └── Favorite
   │
   ▼
View enrollment status
```

Notifications operate independently alongside the catalog:

``` text
Learner
   │
   ├── Announcement feed
   ├── Notification overview
   ├── Pending notifications
   ├── Notification history
   ├── Mark notification read
   └── Mark all notifications read
```

------------------------------------------------------------------------

# 12. Remaining Major Work

The learner side is **not complete yet**.

The next major area is the study functionality:

``` text
modules/learning/pages/learner/study-subpage/
```

The important remaining functionality includes:

## Study discovery

``` text
get-course.php
get-module.php
get-lesson.php
get-quiz.php
get-program.php
get-skill.php
get-learning-path.php
```

## Progress

``` text
lesson completion
course progress
module progress
quiz attempts
quiz submission
resume position
```

## Engagement

``` text
questions
comments
notes
```

## Materials

``` text
material download
learner-access validation
```

## Results

The result pages/endpoints also still need implementation for:

``` text
certificate
grade
progress
quiz results
```

------------------------------------------------------------------------

# 13. Recommended Implementation Order

To avoid building dependent endpoints before their underlying state
exists, continue in this order:

``` text
1. Study discovery
       ↓
2. Resume/enrollment validation
       ↓
3. Lesson progress
       ↓
4. Course/module progress
       ↓
5. Quiz session and attempts
       ↓
6. Quiz submission/results
       ↓
7. Notes/questions/comments
       ↓
8. Material downloads
       ↓
9. Result pages
       ↓
10. Certificate/grade/progress views
```

This keeps the learner experience dependency-safe.

------------------------------------------------------------------------

# 14. Change Boundary

All work described in this document belongs to:

``` text
modules/learning/pages/learner/
```

No changes should be made outside that directory unless a later
implementation is explicitly reviewed and approved.

The existing shared classes under:

``` text
modules/learning/classes/
```

are treated as dependencies and should not be modified as part of the
current learner-only scope.
