# External Cases — Case Detail Module Technical Specification

## Overview

This document specifies the UI/UX behavior, form contract, state management, and layout rules for the case detail modules inside `modules/compliance/pages/external-cases.php`. The goal is to make every section visually distinct, behaviorally consistent, and safe to extend.

---

## 1. Documents

### Component Type
Inline card form inside `.ec-detail-main` (`#ecDocumentsCard`).

### Input Fields
| Field | Label | Type | Constraints |
|-------|-------|------|-------------|
| `document_type` | Document Type | `<select>` | Required options populated from `$docTypes` |
| `document_date` | Document Date | `<input type="text">` | `dd/mm/yyyy` display, maxlength 10, auto-formatted on blur |
| `document` | File | `<input type="file">` | `accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt,.csv"`, server-side 10MB limit |
| `description` | Description | `<textarea>` | `rows="2"`, placeholder: `Brief description of the document...` |

### State Management
- **Empty State**: `#ecDocumentsList` renders `<div class="lc-empty">No documents uploaded yet.</div>`
- **Loading State**: `<div class="lc-empty">Loading documents…</div>`
- **Populated State**: Renders `.ec-file-list` containing `.ec-file-row` items with name, metadata, download action, and delete action.

### Action Elements
- **Upload Document** — submits `#ecUploadForm` via AJAX with `action=upload_document`
- **Download** — anchor tag targeting `file_path`
- **Delete** — `.ec-delete-doc-btn` triggers `action=delete_document`

### Layout & Styling
- Wrapper: `.lc-card.ec-detail-card#ecDocumentsCard`
- Header: `.lc-card-head` with title "Documents"
- Form class: `.ec-upload-form`
- Grid: `.ec-form-grid` (2 columns on desktop, 1 column on mobile)
- Hint text: `.lc-hint` beneath file input
- Submit button: `.lc-btn.primary` below the grid

---

## 2. Case Notes

### Component Type
Inline card form inside `.ec-detail-main` (`#ecNotesCard`).

### Input Fields
| Field | Label | Type | Constraints |
|-------|-------|------|-------------|
| `note` | Add Note | `<textarea>` | `rows="2"`, placeholder: `Enter case note…`, `required` |

### State Management
- **Empty State**: `#ecNotesList` renders `<div class="lc-empty">No notes yet.</div>`
- **Populated State**: Renders `.ec-note-item` blocks containing author, date, and note text.

### Action Elements
- **Add Note** — submits `#ecAddNoteForm` via AJAX with `action=add_note`

### Layout & Styling
- Wrapper: `.lc-card.ec-detail-card#ecNotesCard`
- Header: `.lc-card-head` with title "Case Notes"
- Display area: `.ec-notes-display#ecNotesList`
- Form class: `.ec-note-form`
- Full-width textarea via `grid-column:1/-1`
- Submit button: `.lc-btn.primary`

---

## 3. Resolution & Closing

### Component Type
Inline card form inside `.ec-detail-main` (`#ecResolutionSection`).

### Input Fields
| Field | Label | Type | Constraints |
|-------|-------|------|-------------|
| `resolution` | Resolution | `<select>` | `required`, options: Resolved, Settlement Reached, Compliance Completed, Referred, Withdrawn, No Further Action, Other |
| `date_resolved` | Resolution Date | `<input type="text">` | `dd/mm/yyyy` display, maxlength 10, auto-formatted on blur |
| `resolution_remarks` | Final Remarks | `<textarea>` | `rows="3"`, placeholder: `Final remarks about the case outcome...` |

### State Management
- **Hidden by default**: `style="display:none;"` on `#ecResolutionSection`
- **Visible when**: Case status is not `Closed`, `Resolved`, `Withdrawn`, or `Archived`
- **Post-submit**: Hidden immediately on successful closure, then `loadCaseDetail()` enforces final visibility based on server status

### Action Elements
- **Record Resolution & Close Case** — submits `#ecResolutionForm` via AJAX with `action=close_case`
- On success: section hides, detail and list refresh

### Layout & Styling
- Wrapper: `.lc-card.ec-detail-card.ec-resolution-card#ecResolutionSection`
- Header: `.lc-card-head` with title "Record Resolution & Close Case"
- Body: `.lc-card-body`
- Form class: `.ec-resolution-form`
- Grid: `.ec-form-grid` with `.ec-field-full` for full-width remarks
- Actions: `.ec-resolution-actions` with top border separator, right-aligned
- Required indicator: `<span class="required">*</span>` in red

---

## 4. Case Activity

### Component Type
Inline card with embedded modal (`#ecActivityCard` + `#ecAddEventModal`).

### Input Fields (Modal)
| Field | Label | Type | Constraints |
|-------|-------|------|-------------|
| `event_type` | Milestone Type | `<select>` | Options from `$eventTypes` |
| `title` | Title | `<input type="text">` | `placeholder="e.g. Initial Conference"`, `required` |
| `event_date` | Date | `<input type="date">` | `required` |
| `start_time` | Time | `<input type="time">` | Optional |
| `location` | Location / Platform | `<input type="text">` | `placeholder="DOLE Office / Online"` |
| `status` | Status | `<select>` | Options from `$eventStatuses` |
| `description` | Description | `<textarea>` | `placeholder="Optional description..."`, full-width |

### State Management
- **Empty State**: `#ecRoadmapList` renders `<div class="lc-empty">No roadmap events yet.</div>`
- **Populated State**: Renders `.ec-roadmap-item` timeline cards with status badges, metadata, and inline edit/delete actions

### Action Elements
- **Add Milestone** — opens `#ecAddEventModal` via `ec-modal-backdrop--open`
- **Edit** — pre-fills modal with event data, changes submit text to "Update Milestone"
- **Delete** — confirms then submits `action=delete_event`
- **Cancel / Close** — closes modal without saving

### Layout & Styling
- Wrapper: `.lc-card.ec-sidebar-card#ecActivityCard`
- Header: `.lc-card-head` with title and `.lc-btn.primary` action
- Timeline: `.ec-roadmap` with `.ec-roadmap-item` and `.ec-roadmap-card`
- Modal: `.ec-modal-backdrop#ecAddEventModal` with `.ec-modal`, `.ec-modal-head`, `.ec-modal-body`
- Modal form: `.ec-form-grid`, full-width description via `grid-column:1/-1`

---

## 5. Upcoming Hearings

### Component Type
Inline card with list view (`#ecUpcomingHearingsCard`).

### Input Fields (Modal)
| Field | Label | Type | Constraints |
|-------|-------|------|-------------|
| `event_type` | Event Type | `<select>` | Options from `$eventTypes` |
| `title` | Title | `<input type="text">` | `placeholder="e.g. Initial Conference"`, `required` |
| `event_date` | Date | `<input type="date">` | `required` |
| `start_time` | Start Time | `<input type="time">` | Optional |
| `end_time` | End Time | `<input type="time">` | Optional |
| `location` | Location / Platform | `<input type="text">` | `placeholder="DOLE Office / Online"` |
| `status` | Status | `<select>` | Options from `$eventStatuses` |
| `description` | Description | `<textarea>` | `placeholder="Optional description..."`, full-width |

### State Management
- **Empty State**: `#ecHearingsList` renders `<div class="lc-empty">No upcoming hearings.</div>`
- **Populated State**: Renders `.ec-hearing-compact` items filtered from events where type contains "conference", "hearing", or "meeting"

### Action Elements
- **Add Hearing** — opens `#ecAddHearingModal`
- **Delete** — per-item `.ec-delete-event-btn` confirms then submits `action=delete_event`

### Layout & Styling
- Wrapper: `.lc-card.ec-sidebar-card#ecUpcomingHearingsCard`
- Header: `.lc-card-head` with title and `.lc-btn.primary` action
- List: `.ec-hearing-compact` with `.ec-hearing-compact-item` cards
- Modal: `.ec-modal-backdrop#ecAddHearingModal`

---

## 6. Legal References

### Component Type
Inline card with collapsible search and list (`#ecReferencesCard`).

### Input Fields (Search)
| Field | Label | Type | Constraints |
|-------|-------|------|-------------|
| `q` | Search Labor Law References | `<input type="text">` | `placeholder="Search by title, keyword, or authority…"`, min 2 chars, debounced 300ms |

### State Management
- **Empty State**: `#ecReferencesList` renders `<div class="lc-empty">No references attached yet.</div>`
- **Populated State**: Renders `.ec-ref-list` with `.ec-ref-row` items showing title, reference number, category, authority, relation type, notes, and detach action

### Action Elements
- **Find References** — toggles `#ecRefSearch` visibility
- **Attach** — per-search-result button submits `action=attach_reference`
- **Detach** — per-attached-ref `.ec-detach-ref-btn` confirms then submits `action=detach_reference`

### Layout & Styling
- Wrapper: `.lc-card.ec-sidebar-card#ecReferencesCard`
- Header: `.lc-card-head` with title and toggle button
- Search: `#ecRefSearch` hidden by default, margin-bottom 8px when visible
- Results dropdown: `#ecRefSearchResults` with `.ec-search-results` styling
- List: `.ec-ref-list` with `.ec-ref-row` items

---

## 7. Quick Info

### Component Type
Read-only information card (`#ecQuickInfoCard`).

### Input Fields
None. Read-only display only.

### State Management
- **Populated State**: Renders `.ec-quick-info` with 6 key-value pairs:
  - Authority
  - Case Type
  - Priority
  - Status (rendered as `.lc-status-stamp`)
  - Assigned
  - Date Received
- All values update on `loadCaseDetail()`

### Action Elements
None.

### Layout & Styling
- Wrapper: `.lc-card.ec-sidebar-card#ecQuickInfoCard`
- Header: `.lc-card-head` with title "Quick Info"
- Content: `.ec-quick-info` with `.ec-quick-item` rows
- Labels: `.ec-quick-label` (uppercase, small, muted)
- Values: `.ec-quick-value` (bold, right-aligned)

---

## Cross-Cutting Layout Rules

### Visual Hierarchy
1. **Primary Content** (`.ec-detail-main`): Documents, Notes, Resolution — 2fr width
2. **Secondary Content** (`.ec-detail-sidebar`): Activity, Hearings, References, Quick Info — 1fr width
3. Each module is wrapped in `.lc-card` with `.lc-card-head` for consistent header treatment

### Distinct Section Grouping
- Use `.ec-detail-card` for main-column modules
- Use `.ec-sidebar-card` for sidebar modules
- Ensure `#ecDocumentsCard`, `#ecNotesCard`, and `#ecResolutionSection` are separated by at least `gap: 12px` via `.ec-detail-main`
- Do not nest forms inside `.lc-card-body` unless necessary; prefer direct `.lc-card` children for visual separation

### Current Rendering Issue Fix
If modules appear merged:
1. Verify each module has its own `.lc-card` wrapper
2. Ensure `.ec-detail-main` has `display: flex; flex-direction: column; gap: 12px;`
3. Ensure `.ec-detail-layout` uses `grid-template-columns: 2fr 1fr; gap: 12px;`
4. Add bottom margin or padding to `.ec-upload-form`, `.ec-note-form`, `.ec-resolution-form` to separate form from list content within the same card

### Responsive Behavior
- At `max-width: 1024px`: `.ec-detail-layout` becomes single column
- At `max-width: 768px`: `.ec-form-grid` becomes single column, `.ec-resolution-actions` stacks vertically
