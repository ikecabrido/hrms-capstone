<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/LegalCaseManager.php';
require_once __DIR__ . '/../classes/LaborLawReference.php';
require_once __DIR__ . '/../lib/ajax/document_template_helper.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

try {
    $db = (new Database())->getConnection();
} catch (Throwable $e) {
    $db = null;
}

$manager = $db instanceof PDO ? new LegalCaseManager($db) : null;
$llm = $db instanceof PDO ? new LaborLawReference($db) : null;

$agencies = ['DOLE','DepEd','CHED','NLRC','NCMB','POEA/DMW','Court','Other Government Agency','Other Regulatory Authority'];
$caseTypes = ['Labor / Employment','Academic / Education','Administrative','Regulatory Compliance','Occupational Safety','Employee Relations','Student-Related','Institutional Compliance','Other'];
$caseSources = ['Internal HR Complaint','Employee Directly Reported','Agency Notice','Government Referral','Management Referral','Other External Source'];
$priorities = ['Low','Medium','High','Critical'];
$statuses = ['Draft','Open','Notice Received','Scheduled','Hearing / Conference','Awaiting Response','Awaiting Resolution','Resolved','Closed','Withdrawn','Archived'];
$eventTypes = ['Case Created','External Notice Received','Conference','Hearing','Meeting','Document Submission','Response Submitted','Decision/Resolution Received','Follow-up','Other'];
$eventStatuses = ['Scheduled','Completed','Cancelled','Rescheduled'];
$docTypes = ['Notice of Conference','Complaint Copy','Position Paper','Employment Contract','Supporting Evidence','Agency Letter','Resolution','Decision','Other'];

$specificCaseTypes = [
  'DOLE' => ['SEnA / Request for Assistance','Labor Standards Inspection','Unpaid Wages / Salary','Overtime / Holiday / Rest-Day Pay','13th-Month Pay / Benefits','Minimum Wage','Leave / Service Incentive Leave','Occupational Safety and Health','Other Labor Matter'],
  'NLRC' => ['Illegal Dismissal / Termination','Monetary Claims','Unfair Labor Practice Allegation','Labor Complaint / Case','Execution / Compliance Proceeding','Other NLRC Matter'],
  'NCMB' => ['Preventive Mediation','Notice of Strike / Lockout','Conciliation / Mediation','Settlement / CBA Matter','Other NCMB Matter'],
  'DepEd' => ['School Regulatory Compliance','Permit / Recognition','Student Protection','Administrative Complaint','Records / Documentary Compliance','Other DepEd Matter'],
  'CHED' => ['Program / Institutional Compliance','Permit / Recognition / Authority','Regulatory Inspection / Evaluation','Student-Related Regulatory Complaint','Records / Compliance Submission','Other CHED Matter'],
  'POEA/DMW' => ['Agency-Specific Compliance','Investigation','Notice / Complaint','Other'],
  'Court' => ['Civil','Criminal','Labor-Related','Administrative','Other Court Matter'],
  'Other Government Agency' => ['Agency-Specific Compliance','Investigation','Notice / Complaint','Other'],
  'Other Regulatory Authority' => ['Regulatory Inspection','Licensing','Compliance','Enforcement','Other']
];

$internalComplaintTypes = ['Compensation / Payroll','Leave / Benefits','Working Conditions','Disciplinary Action','Harassment / Discrimination Allegation','Employee Relations / Grievance','Resignation / Termination Dispute','Other / Unclassified'];

$officers = [];
if ($db instanceof PDO) {
    try {
        $stmt = $db->query("SELECT employee_id, first_name, last_name FROM em_employees WHERE employment_status = 'Active' ORDER BY first_name, last_name");
        $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}

$flash = '';
if (isset($_GET['msg'])) {
    $raw = (string) $_GET['msg'];
    if (strpos($raw, '?msg=') !== false) {
        $parts = explode('?msg=', $raw);
        $raw = end($parts);
    }
    $flash = htmlspecialchars($raw, ENT_QUOTES);
}

function ec_csrf_token() {
    return htmlspecialchars(hm_csrf_token());
}

function ec_esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES);
}

function ec_date($date) {
    return $date ? date('M d, Y', strtotime($date)) : '—';
}

function ec_status_class($status) {
    if (!$status) return '';
    $s = strtolower($status);
    if (in_array($s, ['closed','resolved','withdrawn','archived'])) return 'lc-status-stamp--closed';
    if (in_array($s, ['overdue','awaiting'])) return 'lc-status-stamp--overdue';
    if (in_array($s, ['scheduled','hearing','conference'])) return 'lc-status-stamp--investigating';
    return 'lc-status-stamp--info';
}

function ec_priority_class($p) {
    if (!$p) return '';
    $s = strtolower($p);
    if ($s === 'critical') return 'lc-status-stamp--overdue';
    if ($s === 'high') return 'lc-status-stamp--investigating';
    return 'lc-status-stamp--info';
}

function ec_option_groups($agencies, $selected = '') {
    $html = '<option value="">Select authority</option>';
    $groups = [
        'Government Agencies' => ['DOLE','DepEd','CHED','NLRC','NCMB','POEA/DMW'],
        'Courts' => ['Court'],
        'Other' => ['Other Government Agency','Other Regulatory Authority']
    ];
    foreach ($groups as $group => $items) {
        $html .= '<optgroup label="' . htmlspecialchars($group) . '">';
        foreach ($items as $a) {
            $sel = $selected === $a ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($a) . '"' . $sel . '>' . htmlspecialchars($a) . '</option>';
        }
        $html .= '</optgroup>';
    }
    return $html;
}

$pageTitle = 'Legal Affairs';
?>

<div class="module-content">
  <?php if ($flash): ?>
    <?php [$fc, $fm] = explode('|', $flash, 2); ?>
    <div class="lc-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
  <?php endif; ?>

  <!-- LIST VIEW -->
  <div id="ecListView">
    <div class="ec-summary-bar" id="ecSummaryBar">
      <a href="#" class="ec-summary-item" data-filter="">
        <div class="ec-summary-value" id="ecStatTotal">—</div>
        <div class="ec-summary-label">Total Cases</div>
      </a>
      <a href="#" class="ec-summary-item" data-filter="status:Open">
        <div class="ec-summary-value" id="ecStatOpen">—</div>
        <div class="ec-summary-label">Open</div>
      </a>
      <a href="#" class="ec-summary-item" data-filter="status:Scheduled">
        <div class="ec-summary-value" id="ecStatScheduled">—</div>
        <div class="ec-summary-label">Scheduled</div>
      </a>
      <a href="#" class="ec-summary-item" data-filter="status:Hearing / Conference">
        <div class="ec-summary-value" id="ecStatHearing">—</div>
        <div class="ec-summary-label">Hearing / Conference</div>
      </a>
      <a href="#" class="ec-summary-item" data-filter="status:Awaiting Resolution">
        <div class="ec-summary-value" id="ecStatAwaiting">—</div>
        <div class="ec-summary-label">Awaiting Resolution</div>
      </a>
      <a href="#" class="ec-summary-item" data-filter="status:Closed">
        <div class="ec-summary-value" id="ecStatClosed">—</div>
        <div class="ec-summary-label">Closed</div>
      </a>
    </div>

    <div class="ec-table-wrap">
      <table class="ec-table">
        <thead>
          <tr class="ec-list-header-row">
            <th colspan="7">
              <div class="ec-list-header">
                <h3 class="ec-list-title">Legal Affairs</h3>
                <span class="ec-list-count" id="ecListCount">—</span>
                <button type="button" class="lc-btn primary" id="ecNewCaseBtn" style="font-size:0.66rem; margin-left:auto;">
                  <i class="bi bi-plus-lg"></i> New External Case
                </button>
              </div>
            </th>
          </tr>
          <tr>
            <th>Case No.</th>
            <th>Authority</th>
            <th>Subject / Case Type</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Date Received</th>
            <th>Assigned To</th>
          </tr>
        </thead>
        <tbody id="ecCaseTableBody">
          <tr><td colspan="7" style="text-align:center; padding:24px; color:#8b93a1;">Loading cases…</td></tr>
        </tbody>
      </table>
    </div>
    <div class="ec-pagination" id="ecPagination" style="display:none;">
      <span id="ecPageInfo"></span>
      <div class="ec-pagination-nav" id="ecPageNav"></div>
    </div>
  </div>

    <!-- DETAIL VIEW -->
    <div id="ecDetailView" style="display:none;">

       <!-- Edit Case Modal -->
      <div class="ec-modal-backdrop" id="ecEditModal">
        <div class="ec-modal">
           <div class="ec-modal-head">
             <h3>Edit Case</h3>
           </div>
          <div class="ec-modal-body">
            <form id="ecEditCaseForm">
              <input type="hidden" name="csrf_token" value="<?= ec_csrf_token() ?>">
              <input type="hidden" name="action" value="update_case">
              <input type="hidden" name="case_id" id="ecEditCaseId">
               <div class="ec-form-grid">
                 <div class="lc-field">
                   <label for="ecEditCaseNumber">Case Number</label>
                   <input type="text" name="case_number" id="ecEditCaseNumber" readonly>
                 </div>
                 <div class="lc-field">
                   <label for="ecEditAgency">External Authority *</label>
                   <select name="external_agency" id="ecEditAgency">
                     <option value="">Select authority</option>
                     <?php foreach ($agencies as $a): ?>
                       <option value="<?= ec_esc($a) ?>"><?= ec_esc($a) ?></option>
                     <?php endforeach; ?>
                   </select>
                 </div>
                 <div class="lc-field">
                   <label for="ecEditCaseType">Case Type</label>
                   <input type="text" name="case_type" id="ecEditCaseType" readonly>
                 </div>
                 <div class="lc-field">
                   <label for="ecEditSpecificCaseType">Specific Case Type *</label>
                   <select name="specific_case_type" id="ecEditSpecificCaseType" required>
                     <option value="">Select specific case type</option>
                   </select>
                 </div>
                 <div class="lc-field">
                   <label for="ecEditStatus">Status *</label>
                   <select name="current_status" id="ecEditStatus">
                     <?php foreach ($statuses as $s): ?>
                       <option value="<?= ec_esc($s) ?>"><?= ec_esc($s) ?></option>
                     <?php endforeach; ?>
                   </select>
                 </div>
                 <div class="lc-field">
                   <label for="ecEditPriority">Priority</label>
                   <select name="priority" id="ecEditPriority">
                     <?php foreach ($priorities as $p): ?>
                       <option value="<?= ec_esc($p) ?>"><?= ec_esc($p) ?></option>
                     <?php endforeach; ?>
                   </select>
                 </div>
                 <div class="lc-field">
                   <label for="ecEditExternalRef">External Reference No</label>
                   <input type="text" name="external_reference_no" id="ecEditExternalRef">
                 </div>
                 <div class="lc-field">
                   <label for="ecEditDateFiled">Date Reported Externally</label>
                   <input type="date" name="date_filed" id="ecEditDateFiled">
                 </div>
                 <div class="lc-field">
                   <label for="ecEditDateReceived">Date Received by School</label>
                   <input type="date" name="date_received" id="ecEditDateReceived">
                 </div>
                 <div class="lc-field">
                   <label for="ecEditAssignedTo">Assigned To</label>
                   <select name="assigned_to" id="ecEditAssignedTo">
                     <option value="">Unassigned</option>
                     <?php foreach ($officers as $o): ?>
                       <option value="<?= (int) $o['employee_id'] ?>"><?= ec_esc($o['first_name'] . ' ' . $o['last_name']) ?></option>
                     <?php endforeach; ?>
                   </select>
                 </div>
                 <div class="lc-field">
                   <label for="ecEditRelatedType">Related Internal Record</label>
                   <select name="related_type" id="ecEditRelatedType">
                     <option value="none">None</option>
                     <option value="complaint">Complaint</option>
                     <option value="incident">Incident</option>
                     <option value="risk">Risk</option>
                     <option value="employee">Employee Record</option>
                   </select>
                 </div>
                 <div class="lc-field" id="ecEditRelatedSearchField" style="display:none;">
                   <label for="ecEditRelatedSearchInput">Search Related Record</label>
                   <input type="text" id="ecEditRelatedSearchInput" placeholder="Search…" autocomplete="off">
                   <input type="hidden" name="complaint_id" id="ecEditRelatedId" value="">
                   <div id="ecEditRelatedSearchResults" style="position:relative; z-index:10; background:#fff; border:1px solid var(--border,#e4e8ee); border-radius:8px; max-height:200px; overflow:auto; width:100%; box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); display:none; margin-top:4px;"></div>
                 </div>
                 <div class="lc-field" style="grid-column:1/-1;">
                   <label for="ecEditDescription">Description</label>
                   <textarea name="description" id="ecEditDescription" placeholder="Case description…"></textarea>
                 </div>
               </div>
               <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
                 <button type="button" class="lc-btn ghost" id="ecCancelEditBtn">Cancel</button>
                 <button type="submit" class="lc-btn primary"><i class="bi bi-check"></i> Save Changes</button>
               </div>
            </form>
          </div>
        </div>
      </div>

       <!-- HEARING DETAIL MODAL -->
       <div class="ec-modal-backdrop" id="ecHearingDetailModal">
         <div class="ec-modal">
            <div class="ec-modal-head">
              <h3 id="ecHearingDetailTitle">Hearing Details</h3>
            </div>
           <div class="ec-modal-body">
             <div class="ec-form-grid">
                <div class="lc-field">
                  <label>Event Type</label>
                  <div class="ec-detail-read-value" id="ecHearingDetailType"></div>
                </div>
                <div class="lc-field">
                  <label>Date</label>
                  <div class="ec-detail-read-value" id="ecHearingDetailDate"></div>
                </div>
                <div class="lc-field">
                  <label>Time</label>
                  <div class="ec-detail-read-value" id="ecHearingDetailTime"></div>
                </div>
                <div class="lc-field">
                  <label>Location / Platform</label>
                  <div class="ec-detail-read-value" id="ecHearingDetailLocation"></div>
                </div>
                <div class="lc-field">
                  <label>Status</label>
                  <div class="ec-detail-read-value" id="ecHearingDetailStatus"></div>
                </div>
                <div class="lc-field" style="grid-column:1/-1;">
                  <label>Description</label>
                  <div class="ec-detail-read-value" id="ecHearingDetailDescription" style="white-space:pre-wrap;"></div>
                </div>
             </div>
             <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
               <button type="button" class="lc-btn ghost" id="ecCancelHearingDetailBtn">Close</button>
             </div>
           </div>
         </div>
       </div>

      <!-- Two-column layout -->
      <div class="ec-detail-layout">
        <!-- Main Column -->
        <div class="ec-detail-main">
          <!-- Case Details (read mode) -->
          <div class="lc-card ec-details-card" id="ecDetailsCard">
               <div class="lc-card-head">
                 <h3><i class="bi bi-info-circle"></i> Case Details</h3>
                 <nav class="ec-breadcrumb" aria-label="breadcrumb">
                   <ol class="breadcrumb">
                     <li class="breadcrumb-item"><a href="/modules/compliance/index.php?page=external-cases" id="ecBreadcrumbList">Legal Affairs</a></li>
                     <li class="breadcrumb-item active" aria-current="page" id="ecBreadcrumbCurrent">Case Information</li>
                   </ol>
                 </nav>
                 <div class="ec-case-header-actions">
                   <div class="ec-more-menu-wrap">
                     <button type="button" class="lc-btn ghost ec-more-menu-toggle" id="ecMoreCaseBtn" style="font-size:0.72rem;" title="More options" aria-haspopup="true" aria-expanded="false">
                       <i class="bi bi-three-dots"></i>
                     </button>
                      <div class="ec-more-menu" id="ecMoreCaseMenu" role="menu">
                        <button type="button" class="ec-more-menu-item" id="ecMenuAddHearing" role="menuitem">
                         <i class="bi bi-bank"></i> Add Hearing
                       </button>
                       <div class="ec-more-menu-sep" role="separator"></div>
                       <button type="button" class="ec-more-menu-item" id="ecMenuAddNote" role="menuitem">
                         <i class="bi bi-journal-text"></i> Add Note
                       </button>
                       <button type="button" class="ec-more-menu-item" id="ecMenuUploadDocument" role="menuitem">
                         <i class="bi bi-upload"></i> Upload Document
                       </button>
                       <button type="button" class="ec-more-menu-item" id="ecMenuCloseCase" role="menuitem">
                         <i class="bi bi-x-circle"></i> Close Case
                       </button>
                       <div class="ec-more-menu-sep" role="separator"></div>
                       <button type="button" class="ec-more-menu-item" id="ecMenuEditCase" role="menuitem">
                         <i class="bi bi-pencil"></i> Edit Case
                       </button>
                     </div>
                   </div>
                 </div>
               </div>
              <div class="ec-details-read" id="ecDetailsRead">
                <div class="ec-details-grid">
                  <div class="ec-detail-item">
                    <div class="ec-detail-label">Case Number</div>
                    <div class="ec-detail-value" id="ecDetailCaseNumber">—</div>
                  </div>
                  <div class="ec-detail-item">
                    <div class="ec-detail-label">External Authority</div>
                    <div class="ec-detail-value" id="ecDetailAuthority">—</div>
                  </div>
                  <div class="ec-detail-item">
                    <div class="ec-detail-label">Case Type</div>
                    <div class="ec-detail-value" id="ecDetailCaseType">—</div>
                  </div>
                  <div class="ec-detail-item">
                    <div class="ec-detail-label">Status</div>
                    <div class="ec-detail-value" id="ecDetailStatus">—</div>
                  </div>
                  <div class="ec-detail-item">
                    <div class="ec-detail-label">Priority</div>
                    <div class="ec-detail-value" id="ecDetailPriority">—</div>
                  </div>
                  <div class="ec-detail-item">
                    <div class="ec-detail-label">External Reference</div>
                    <div class="ec-detail-value" id="ecDetailExternalRef">—</div>
                  </div>
                  <div class="ec-detail-item">
                    <div class="ec-detail-label">Date Reported Externally</div>
                    <div class="ec-detail-value" id="ecDetailDateFiled">—</div>
                  </div>
                  <div class="ec-detail-item">
                    <div class="ec-detail-label">Date Received by School</div>
                    <div class="ec-detail-value" id="ecDetailDateReceived">—</div>
                  </div>
                  <div class="ec-detail-item">
                    <div class="ec-detail-label">Assigned Personnel</div>
                    <div class="ec-detail-value" id="ecDetailAssigned">Unassigned</div>
                  </div>
                  <div class="ec-detail-item">
                    <div class="ec-detail-label">Related Internal Record</div>
                    <div class="ec-detail-value" id="ecDetailRelated">—</div>
                  </div>
                  <div class="ec-detail-item ec-detail-item--full">
                    <div class="ec-detail-label">Description</div>
                    <div class="ec-detail-value" id="ecDetailDescription">—</div>
                  </div>
                </div>
              </div>
            </div>

              <!-- Documents -->
             <div class="lc-card ec-detail-card" id="ecDocumentsCard">
                <div class="lc-card-head">
                  <h3><i class="bi bi-folder2-open"></i> Documents</h3>
                </div>
               <div id="ecDocumentsList">
                 <div class="lc-empty">Loading documents…</div>
               </div>
             </div>

             <!-- UPLOAD DOCUMENT MODAL -->
             <div class="ec-modal-backdrop" id="ecUploadDocModal">
               <div class="ec-modal">
                  <div class="ec-modal-head">
                    <h3>Upload Document</h3>
                  </div>
                 <div class="ec-modal-body">
                   <form id="ecUploadForm" class="ec-upload-form" enctype="multipart/form-data">
                     <input type="hidden" name="csrf_token" value="<?= ec_csrf_token() ?>">
                     <div class="ec-form-grid">
                       <div class="lc-field">
                         <label for="ecUploadDocType">Document Type</label>
                         <select name="document_type" id="ecUploadDocType">
                           <option value="">Select type</option>
                           <?php foreach ($docTypes as $dt): ?>
                             <option value="<?= ec_esc($dt) ?>"><?= ec_esc($dt) ?></option>
                           <?php endforeach; ?>
                         </select>
                       </div>
                        <div class="lc-field">
                          <label for="ecUploadDocDate">Document Date</label>
                          <input type="date" name="document_date" id="ecUploadDocDate">
                        </div>
                       <div class="lc-field">
                         <label for="ecUploadFile">File</label>
                         <input type="file" name="document" id="ecUploadFile" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt,.csv">
                         <div class="lc-hint">Accepted formats: pdf, doc, docx, xls, xlsx, jpg, jpeg, png, txt, csv. Maximum size: 10MB</div>
                       </div>
                       <div class="lc-field">
                         <label for="ecUploadDocDesc">Description</label>
                         <textarea name="description" id="ecUploadDocDesc" rows="2" placeholder="Brief description of the document..."></textarea>
                       </div>
                     </div>
                     <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
                       <button type="button" class="lc-btn ghost" id="ecCancelUploadBtn">Cancel</button>
                       <button type="submit" class="lc-btn primary"><i class="bi bi-upload"></i> Upload Document</button>
                     </div>
                   </form>
                 </div>
               </div>
             </div>

              <!-- Notes -->
             <div class="lc-card ec-detail-card" id="ecNotesCard">
                <div class="lc-card-head">
                  <h3><i class="bi bi-journal-text"></i> Case Notes</h3>
                </div>
               <div class="ec-notes-display" id="ecNotesList">
                 <div class="lc-empty">No notes yet.</div>
               </div>
             </div>

             <!-- ADD NOTE MODAL -->
             <div class="ec-modal-backdrop" id="ecAddNoteModal">
               <div class="ec-modal">
                 <div class="ec-modal-head">
                   <h3>Add Note</h3>
                 </div>
                 <div class="ec-modal-body">
                   <form id="ecAddNoteForm" class="ec-note-form">
                     <input type="hidden" name="csrf_token" value="<?= ec_csrf_token() ?>">
                     <div class="lc-field" style="grid-column:1/-1;">
                       <label for="ecCaseNote">Add Note</label>
                       <textarea name="note" id="ecCaseNote" placeholder="Enter case note…" required rows="2"></textarea>
                     </div>
                     <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
                       <button type="button" class="lc-btn ghost" id="ecCancelNoteBtn">Cancel</button>
                       <button type="submit" class="lc-btn primary"><i class="bi bi-plus-lg"></i> Add Note</button>
                     </div>
                   </form>
                 </div>
               </div>
              </div>

              <!-- RESOLUTION MODAL -->
             <div class="ec-modal-backdrop" id="ecResolutionModal">
               <div class="ec-modal">
                  <div class="ec-modal-head">
                    <h3>Record Resolution &amp; Close Case</h3>
                  </div>
                 <div class="ec-modal-body">
                   <form id="ecResolutionForm" class="ec-resolution-form">
                     <input type="hidden" name="csrf_token" value="<?= ec_csrf_token() ?>">
                     <div class="ec-form-grid">
                       <div class="lc-field">
                         <label for="ecResolution">Resolution <span class="required">*</span></label>
                         <select name="resolution" id="ecResolution" required>
                           <option value="">Select resolution</option>
                           <?php foreach (['Resolved','Settlement Reached','Compliance Completed','Referred','Withdrawn','No Further Action','Other'] as $rv): ?>
                             <option value="<?= ec_esc($rv) ?>"><?= ec_esc($rv) ?></option>
                           <?php endforeach; ?>
                         </select>
                       </div>
                       <div class="lc-field">
                         <label for="ecResolutionDate">Resolution Date</label>
                         <input type="text" name="date_resolved" id="ecResolutionDate" placeholder="dd/mm/yyyy" maxlength="10" value="<?= ec_esc(date('d/m/Y')) ?>">
                       </div>
                       <div class="lc-field ec-field-full">
                         <label for="ecResolutionRemarks">Final Remarks</label>
                         <textarea name="resolution_remarks" id="ecResolutionRemarks" placeholder="Final remarks about the case outcome..." rows="3"></textarea>
                       </div>
                     </div>
                      <div class="ec-resolution-actions">
                        <button type="button" class="lc-btn ghost" id="ecCancelResolutionBtn">Cancel</button>
                        <button type="submit" class="lc-btn danger"><i class="bi bi-x-circle"></i> Record Resolution &amp; Close Case</button>
                      </div>
                   </form>
                 </div>
               </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="ec-detail-sidebar">
          <!-- Case Activity -->
          <div class="lc-card ec-sidebar-card" id="ecActivityCard">
              <div class="lc-card-head">
                <h3><i class="bi bi-diagram-3"></i> Case Activity</h3>
              </div>
            <div class="ec-roadmap" id="ecRoadmapList">
              <div class="lc-empty">No roadmap events yet.</div>
            </div>
           </div>

           <!-- Upcoming Hearings -->
          <div class="lc-card ec-sidebar-card" id="ecUpcomingHearingsCard">
             <div class="lc-card-head">
               <h3><i class="bi bi-bank"></i> Upcoming Hearings</h3>
             </div>
            <div id="ecHearingsList">
              <div class="lc-empty">No upcoming hearings.</div>
            </div>
           </div>

            <!-- Legal References -->
           <div class="lc-card ec-sidebar-card" id="ecReferencesCard">
             <div class="lc-card-head">
               <h3><i class="bi bi-book"></i> Legal References</h3>
             </div>
             <div id="ecReferencesList">
               <div class="lc-empty">No references attached yet.</div>
             </div>
             <div id="ecLawLibraryContainer"></div>
           </div>


        </div>
      </div>
    </div>

  <!-- CREATE CASE MODAL -->
  <div class="ec-modal-backdrop" id="ecCreateModal">
    <div class="ec-modal">
      <div class="ec-modal-head">
        <h3>Add Case</h3>
      </div>
      <div class="ec-modal-body">
        <form id="ecCreateForm" data-skip>
          <input type="hidden" name="csrf_token" value="<?= ec_csrf_token() ?>">
          <div class="ec-form-grid">
            <div class="lc-field">
              <label for="ecCreateCaseSource">Case Source *</label>
              <select name="case_source" id="ecCreateCaseSource" required>
                <option value="">Select source</option>
                <?php foreach ($caseSources as $cs): ?>
                  <option value="<?= ec_esc($cs) ?>"><?= ec_esc($cs) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="lc-field" id="ecCreateAgencyField" style="display:none;">
              <label for="ecCreateAgency">External Authority *</label>
              <select name="external_agency" id="ecCreateAgency">
                <option value="">Select authority</option>
                <?php foreach ($agencies as $a): ?>
                  <option value="<?= ec_esc($a) ?>"><?= ec_esc($a) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="lc-field">
              <label for="ecCreateCaseType">Case Type</label>
              <input type="text" name="case_type" id="ecCreateCaseType" readonly>
            </div>
            <div class="lc-field">
              <label for="ecCreateSpecificCaseType">Specific Case Type *</label>
              <select name="specific_case_type" id="ecCreateSpecificCaseType" required>
                <option value="">Select source and authority first</option>
              </select>
            </div>
            <div class="lc-field">
              <label for="ecCreateCaseTitle">Case Subject *</label>
              <input type="text" name="case_title" id="ecCreateCaseTitle" placeholder="Brief subject of the external case" required>
            </div>
            <div class="lc-field">
              <label for="ecCreateStatus">Status *</label>
              <select name="current_status" id="ecCreateStatus">
                <?php foreach ($statuses as $s): ?>
                  <option value="<?= ec_esc($s) ?>"><?= ec_esc($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="lc-field">
              <label for="ecCreatePriority">Priority</label>
              <select name="priority" id="ecCreatePriority">
                <?php foreach ($priorities as $p): ?>
                  <option value="<?= ec_esc($p) ?>"><?= ec_esc($p) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="lc-field">
              <label for="ecCreateExternalRef">External Reference No</label>
              <input type="text" name="external_reference_no" id="ecCreateExternalRef">
            </div>
            <div class="lc-field">
              <label for="ecCreateDateFiled">Date Reported Externally</label>
              <input type="date" name="date_filed" id="ecCreateDateFiled">
            </div>
            <div class="lc-field">
              <label for="ecCreateDateReceived">Date Received by School</label>
              <input type="date" name="date_received" id="ecCreateDateReceived" value="<?= ec_esc(date('Y-m-d')) ?>" required>
            </div>
            <div class="lc-field">
              <label for="ecCreateAssignedTo">Assigned To</label>
              <select name="assigned_to" id="ecCreateAssignedTo">
                <option value="">Unassigned</option>
                <?php foreach ($officers as $o): ?>
                  <option value="<?= (int) $o['employee_id'] ?>"><?= ec_esc($o['first_name'] . ' ' . $o['last_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="lc-field">
              <label for="ecCreateRelatedType">Related Internal Record</label>
              <select name="related_type" id="ecCreateRelatedType">
                <option value="none">None</option>
                <option value="complaint">Complaint</option>
                <option value="incident">Incident</option>
                <option value="risk">Risk</option>
                <option value="employee">Employee Record</option>
              </select>
            </div>
            <div class="lc-field" id="ecCreateRelatedSearchField" style="display:none;">
              <label for="ecCreateRelatedSearchInput">Search Related Record</label>
              <input type="text" id="ecCreateRelatedSearchInput" placeholder="Search…" autocomplete="off">
              <input type="hidden" name="complaint_id" id="ecCreateRelatedId" value="">
              <div id="ecCreateRelatedSearchResults" style="position:relative; z-index:10; background:#fff; border:1px solid var(--border,#e4e8ee); border-radius:8px; max-height:200px; overflow:auto; width:100%; box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); display:none; margin-top:4px;"></div>
            </div>
            <div class="lc-field" style="grid-column:1/-1;">
              <label for="ecCreateDescription">Description</label>
              <textarea name="description" id="ecCreateDescription" placeholder="Case description…"></textarea>
            </div>
          </div>
          <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
            <button type="button" class="lc-btn ghost" id="ecCancelCreateBtn">Cancel</button>
            <button type="submit" class="lc-btn primary"><i class="bi bi-check"></i> Create Case</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ADD MILESTONE MODAL -->
  <div class="ec-modal-backdrop" id="ecAddEventModal">
    <div class="ec-modal">
      <div class="ec-modal-head">
        <h3>Add Milestone</h3>
      </div>
      <div class="ec-modal-body">
        <form id="ecAddEventForm">
          <input type="hidden" name="csrf_token" value="<?= ec_csrf_token() ?>">
          <div class="ec-form-grid">
            <div class="lc-field">
              <label>Milestone Type</label>
              <select name="event_type">
                <?php foreach ($eventTypes as $et): ?>
                  <option value="<?= ec_esc($et) ?>"><?= ec_esc($et) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="lc-field">
              <label>Title</label>
              <input type="text" name="title" placeholder="e.g. Initial Conference" required>
            </div>
            <div class="lc-field">
              <label>Date</label>
              <input type="date" name="event_date" required>
            </div>
            <div class="lc-field">
              <label>Time</label>
              <input type="time" name="start_time">
            </div>
            <div class="lc-field">
              <label>Location / Platform</label>
              <input type="text" name="location" placeholder="DOLE Office / Online">
            </div>
            <div class="lc-field">
              <label>Status</label>
              <select name="status">
                <?php foreach ($eventStatuses as $es): ?>
                  <option value="<?= ec_esc($es) ?>"><?= ec_esc($es) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="lc-field" style="grid-column:1/-1;">
              <label>Description</label>
              <textarea name="description" placeholder="Optional description..."></textarea>
            </div>
          </div>
          <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
            <button type="button" class="lc-btn ghost" id="ecCancelEventBtn">Cancel</button>
            <button type="submit" class="lc-btn primary"><i class="bi bi-plus-lg"></i> Add Milestone</button>
          </div>
        </form>
      </div>
    </div>
  </div>

   <!-- ADD HEARING MODAL -->
   <div class="ec-modal-backdrop" id="ecAddHearingModal">
     <div class="ec-modal">
        <div class="ec-modal-head">
          <h3>Add Hearing / Conference</h3>
        </div>
       <div class="ec-modal-body">
         <form id="ecAddHearingForm">
           <input type="hidden" name="csrf_token" value="<?= ec_csrf_token() ?>">
           <div class="ec-form-grid">
             <div class="lc-field">
               <label>Event Type</label>
               <select name="event_type">
                 <?php foreach ($eventTypes as $et): ?>
                   <option value="<?= ec_esc($et) ?>"><?= ec_esc($et) ?></option>
                 <?php endforeach; ?>
               </select>
             </div>
             <div class="lc-field">
               <label>Title</label>
               <input type="text" name="title" placeholder="e.g. Initial Conference" required>
             </div>
             <div class="lc-field">
               <label>Date</label>
               <input type="date" name="event_date" required>
             </div>
             <div class="lc-field">
               <label>Start Time</label>
               <input type="time" name="start_time">
             </div>
             <div class="lc-field">
               <label>End Time</label>
               <input type="time" name="end_time">
             </div>
             <div class="lc-field">
               <label>Location / Platform</label>
               <input type="text" name="location" placeholder="DOLE Office / Online">
             </div>
             <div class="lc-field">
               <label>Status</label>
               <select name="status">
                 <?php foreach ($eventStatuses as $es): ?>
                   <option value="<?= ec_esc($es) ?>"><?= ec_esc($es) ?></option>
                 <?php endforeach; ?>
               </select>
             </div>
             <div class="lc-field" style="grid-column:1/-1;">
               <label>Description</label>
               <textarea name="description" placeholder="Optional description..."></textarea>
             </div>
           </div>
           <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
             <button type="button" class="lc-btn ghost" id="ecCancelHearingBtn">Cancel</button>
             <button type="submit" class="lc-btn primary"><i class="bi bi-plus-lg"></i> Add Hearing</button>
           </div>
         </form>
       </div>
     </div>
   </div>

   <!-- EDIT ROADMAP MODAL -->
    <div class="ec-modal-backdrop" id="ecEditRoadmapModal">
      <div class="ec-modal">
         <div class="ec-modal-head">
           <h3>Edit Roadmap</h3>
         </div>
        <div class="ec-modal-body">
          <form id="ecEditRoadmapForm">
            <input type="hidden" name="csrf_token" value="<?= ec_csrf_token() ?>">
            <input type="hidden" name="event_id" id="ecEditRoadmapEventId">
            <div class="ec-form-grid">
              <div class="lc-field">
                <label>Milestone Type</label>
                <select name="event_type" id="ecEditRoadmapEventType">
                  <?php foreach ($eventTypes as $et): ?>
                    <option value="<?= ec_esc($et) ?>"><?= ec_esc($et) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="lc-field">
                <label>Title</label>
                <input type="text" name="title" id="ecEditRoadmapTitle" placeholder="e.g. Initial Conference" required>
              </div>
              <div class="lc-field">
                <label>Date</label>
                <input type="date" name="event_date" id="ecEditRoadmapDate" required>
              </div>
              <div class="lc-field">
                <label>Time</label>
                <input type="time" name="start_time" id="ecEditRoadmapTime">
              </div>
              <div class="lc-field">
                <label>Location / Platform</label>
                <input type="text" name="location" id="ecEditRoadmapLocation" placeholder="DOLE Office / Online">
              </div>
              <div class="lc-field">
                <label>Status</label>
                <select name="status" id="ecEditRoadmapStatus">
                  <?php foreach ($eventStatuses as $es): ?>
                    <option value="<?= ec_esc($es) ?>"><?= ec_esc($es) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="lc-field" style="grid-column:1/-1;">
                <label>Description</label>
                <textarea name="description" id="ecEditRoadmapDescription" placeholder="Optional description..."></textarea>
              </div>
            </div>
              <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
                <button type="button" class="lc-btn ghost" id="ecCancelEditRoadmapBtn">Cancel</button>
                <button type="submit" class="lc-btn primary"><i class="bi bi-check"></i> Update Roadmap</button>
              </div>
          </form>
        </div>
      </div>
    </div>

    <div class="ec-modal-backdrop" id="ecRoadmapDetailModal">
      <div class="ec-modal">
         <div class="ec-modal-head">
           <h3 id="ecRoadmapDetailTitle">Roadmap Item Details</h3>
         </div>
        <div class="ec-modal-body">
          <div class="ec-form-grid">
             <div class="lc-field">
               <label>Milestone Type</label>
               <div class="ec-detail-read-value" id="ecRoadmapDetailType"></div>
             </div>
             <div class="lc-field">
               <label>Title</label>
               <div class="ec-detail-read-value" id="ecRoadmapDetailTitle"></div>
             </div>
             <div class="lc-field">
               <label>Date</label>
               <div class="ec-detail-read-value" id="ecRoadmapDetailDate"></div>
             </div>
             <div class="lc-field">
               <label>Time</label>
               <div class="ec-detail-read-value" id="ecRoadmapDetailTime"></div>
             </div>
             <div class="lc-field">
               <label>Location / Platform</label>
               <div class="ec-detail-read-value" id="ecRoadmapDetailLocation"></div>
             </div>
             <div class="lc-field">
               <label>Status</label>
               <div class="ec-detail-read-value" id="ecRoadmapDetailStatus"></div>
             </div>
             <div class="lc-field" style="grid-column:1/-1;">
               <label>Description</label>
               <div class="ec-detail-read-value" id="ecRoadmapDetailDescription" style="white-space:pre-wrap;"></div>
             </div>
          </div>
          <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
            <button type="button" class="lc-btn ghost" id="ecCancelRoadmapDetailBtn">Close</button>
          </div>
        </div>
      </div>
    </div>

  <script>
window.EC = {
  api: '/modules/compliance/lib/api/external-cases-api.php',
  csrf: '<?= htmlspecialchars(hm_csrf_token()) ?>',
  caseId: null,
  page: 1,
  filters: { search: '', status: '', agency: '' }
};
  </script>
   <script type="module" src="/modules/compliance/js/pages/external-cases/index.js" defer></script>

