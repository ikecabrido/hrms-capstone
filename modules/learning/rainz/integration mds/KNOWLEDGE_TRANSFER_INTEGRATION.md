# Knowledge Transfer Integration — Exit Management ↔ Learning & Development

## The Idea

When an employee resigns, the system pulls all the Learning & Development materials they've taken and compiles them into a **Knowledge Transfer Learning Path**. The successor assigned to that employee then takes that path so they inherit the knowledge.

The L&D Admin can also create Knowledge Transfer paths manually and choose to make them public in the catalog for anyone to take.

---

## How It Connects

```
EXIT MANAGEMENT                          LEARNING & DEVELOPMENT
─────────────────                        ──────────────────────
Resignee identified                      Resignee's courses exist
         │                                        │
         ▼                                        ▼
KT Plan created ──────────────────► Pull courses from
(employee + successor + dates)       ld_enrollment
         │                                        │
         ▼                                        ▼
Admin reviews courses                Courses compiled into a
(check/uncheck what to include)      "Knowledge Transfer" learning path
         │                                        │
         ▼                                        ▼
"Generate Learning Path" ──────────► Successor auto-enrolled
                                     Path appears in their dashboard
                                     Path optionally visible in catalog
```

---

## What Gets Created

When the admin clicks **Generate Learning Path**, the system creates:

### 1. A Learning Path (`ld_learning_path`)

| Field | Value |
|-------|-------|
| `title` | "Knowledge Transfer — {resignee name}" |
| `description` | Summary of what the path contains |
| `instructor_id` | The plan creator or system admin |
| `assigned_to` | The successor's employee ID |
| `status` | `active` |
| `type` | `knowledge_transfer` |
| `is_public` | `0` (private by default) |
| `kt_plan_id` | Links back to the exit KT plan |

### 2. Path Items (`ld_learning_path_item`)

One entry per selected course:

| Field | Value |
|-------|-------|
| `item_type` | `course` |
| `reference_id` | The `ld_course.id` |
| `order_index` | 0, 1, 2, ... (ordered) |

### 3. Successor Enrollments (`ld_enrollment`)

The successor is auto-enrolled in each selected course (skips if already enrolled).

---

## Database Changes Needed

Add three columns to `ld_learning_path`:

| Column | Type | Default | Purpose |
|--------|------|---------|---------|
| `type` | `ENUM('standard','knowledge_transfer')` | `'standard'` | Distinguish KT paths from regular ones |
| `is_public` | `TINYINT(1)` | `0` | Whether the path shows in the learner catalog |
| `kt_plan_id` | `INT UNSIGNED` | `NULL` | Foreign key linking to `exit_knowledge_transfer_plans.id` |

Add an index on `(type, is_public, status)` for catalog queries.

---

## Two Access Points

### From Exit Management

| Page | What the Admin Sees |
|------|-------------------|
| KT Plan View Modal | Resignee's completed/in-progress courses listed as a checklist |
| | "Generate Learning Path" button (hidden if already generated) |
| | After generation: "Path Generated" badge + Public/Private toggle |

**Flow:**
1. Admin opens a KT plan in Exit Management
2. System shows all courses the resignee completed or is taking
3. Admin checks/unchecks which ones the successor should take
4. Admin clicks **Generate Learning Path**
5. Path is created, successor is auto-enrolled
6. Admin can toggle Public/Private to control catalog visibility

### From Learning & Development Admin

| Page | What the Admin Sees |
|------|-------------------|
| Training Page (Learning Path tab) | KT paths shown with "Knowledge Transfer" badge |
| | Public/Private toggle on each KT path card |
| | L&D Admin can create KT paths manually via the learning path form |

**Flow:**
1. L&D Admin creates a learning path and sets `type = knowledge_transfer`
2. Toggles "Public in Catalog" if they want it available to all learners
3. KT paths appear in the learner catalog when public

### From Learner Catalog

| What Shows | Condition |
|-----------|-----------|
| Standard learning paths | Always visible |
| Knowledge transfer paths | Only if `is_public = 1` |

---

## What the Successor Sees

1. **Auto-enrolled courses** appear in their learner dashboard like any other enrollment
2. **The KT learning path** appears in their Learning Paths list
3. If the path is public, it also shows in the catalog for other learners

---

## Endpoints Needed (Exit Management Side)

### `POST generate-kt-learning-path.php`

**Input:** `{ plan_id: int, course_ids: int[] }`

**What it does:**
- Validates the plan exists and no path has been generated yet
- Creates the learning path with `type='knowledge_transfer'`
- Adds each course as a path item
- Auto-enrolls the successor in each course
- Returns the new path ID and course count

### `POST toggle-kt-path-public.php`

**Input:** `{ path_id: int }`

**What it does:**
- Toggles `is_public` between 0 and 1
- Returns the new state

Both endpoints also need to be callable from the L&D Training Page for the toggle.

---

## What Needs to Be Updated

### Exit Management — KT View Modal
- Add resignee's courses checklist after transfer items
- Add Generate Learning Path button
- Add path status indicator + public toggle after generation

### Exit Management — KT AJAX Endpoint (`get-knowledge-transfer.php`)
- Also fetch the resignee's enrollments from `ld_enrollment`
- Also check if a learning path already exists for this plan

### L&D Training Page
- Learning path query includes `type` and `is_public`
- KT paths show "Knowledge Transfer" badge
- KT path cards include Public/Private toggle

### L&D Learner Catalog
- Filter: show standard paths + public KT paths only
- KT paths labeled "Knowledge Transfer" in category
