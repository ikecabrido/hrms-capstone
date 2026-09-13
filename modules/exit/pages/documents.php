<?php
$currentRoleName = $_SESSION['role_name'] ?? 'Exit';
?>
<link rel="stylesheet" href="assets/vendor/flatpickr/flatpickr.min.css">
<link rel="stylesheet" href="assets/css/custom.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/custom.css'); ?>">
<script>
    window.exitManagementUserRole = <?php echo json_encode($currentRoleName); ?>;
    window.exitManagementUserId = <?php echo json_encode($_SESSION['employee_id'] ?? null); ?>;
</script>

    <div class="module-header">
        <h1>Documents</h1>
    </div>

    <div class="module-content">
        <div id="documents-section" class="section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2" style="flex: 1;">
                        <div class="input-group input-group-sm" style="flex: 19;">
                            <input type="text" id="document-search" class="form-control" placeholder="Search documents..." onkeyup="onDocumentSearchChange()" style="display:none;">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                        <select id="document-status-filter" class="form-control form-control-sm" onchange="onDocumentStatusFilterChange()" style="flex: 1; white-space: nowrap;">
                            <option value="all">All</option>
                            <option value="active">Active</option>
                            <option value="deleted">Deleted</option>
                        </select>
                    </div>
                    <div class="card-tools d-flex align-items-center">
                        <button type="button" class="btn btn-info btn-sm mr-2" onclick="showDocumentModal()">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                        <button type="button" class="btn btn-warning btn-sm mr-2" onclick="archiveDocuments()">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="openPrintSelectorModal()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="documents-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Case Type</th>
                                <th>Exit Reason</th>
                                <th>Notice / Exit Date</th>
                                <th>Progress</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="documents-tbody">
                        </tbody>
                    </table>
                    <div id="documents-pagination" class="d-flex justify-content-center mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="customToastContainer" style="position: fixed; top: 1rem; right: 1rem; z-index: 11000; display: flex; flex-direction: column; gap: .75rem;"></div>

    <!-- Document Upload Modal -->
    <div class="modal fade exit-modal" id="documentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title" id="documentModalTitle">Upload Document</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="documentForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="documentId" name="document_id">
                        <input type="hidden" id="documentExitCaseType" name="exit_case_type">
                        <input type="hidden" id="documentExitCaseId" name="exit_case_id">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="documentEmployeeSelect">Employee *</label>
                                    <select class="form-control" id="documentEmployeeSelect" name="employee_id" required>
                                        <option value="">Select Employee</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="documentType">Document Type *</label>
                                    <select class="form-control" id="documentType" name="document_type" required>
                                        <option value="">Select Type</option>
                                        <option value="resignation_letter">Resignation Letter</option>
                                        <option value="clearance_form">Clearance Form</option>
                                        <option value="handover_document">Handover Document</option>
                                        <option value="settlement_receipt">Settlement Receipt</option>
                                        <option value="exit_interview">Exit Interview Notes</option>
                                        <option value="certificate">Experience Certificate</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="documentCaseSelect">Link to Exit Case</label>
                            <select class="form-control" id="documentCaseSelect" name="document_case_select">
                                <option value="">No exit case linked</option>
                            </select>
                            <small class="form-text text-muted">Optional: link the document to a resignation or termination case.</small>
                        </div>

                        <div class="form-group">
                            <label for="documentTitle">Document Title *</label>
                            <input type="text" class="form-control" id="documentTitle" name="title" required>
                        </div>

                        <div class="form-group">
                            <label for="documentFile">File *</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="documentFile" name="document_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                <label class="custom-file-label" for="documentFile">Choose file</label>
                            </div>
                            <small class="form-text text-muted">Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info" id="documentSubmitBtn">Upload Document</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archive Document Modal -->
    <div class="modal fade exit-modal" id="archiveDocumentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Archive Document</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="archiveDocumentForm">
                    <div class="modal-body">
                        <input type="hidden" id="archiveDocumentId" name="document_id">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Archiving will move this document record to the archive database.
                            The record will be completely removed from active documents and stored in the exit_archive table.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archiveDocumentEmployeeId">Employee ID</label>
                                    <input type="text" class="form-control" id="archiveDocumentEmployeeId" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archiveDocumentEmployeeName">Employee Name</label>
                                    <input type="text" class="form-control" id="archiveDocumentEmployeeName" readonly>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="archiveDocumentReason" name="archive_reason" value="Process completed; archived.">
                        <div class="form-group">
                            <label>Archive Reason</label>
                            <div class="form-control-plaintext">Process completed; archived.</div>
                            <small class="form-text text-muted">This reason is generated automatically when the process completes.</small>
                        </div>

                        <div class="form-group">
                            <label for="archiveDocumentNotes">Additional Notes (Optional)</label>
                            <textarea class="form-control" id="archiveDocumentNotes" name="archive_notes" rows="2" placeholder="Any additional notes about this archive action..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-archive"></i> Archive Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archived Documents List Modal (separated from archiveDocumentModal - it was
         invalidly nested inside that modal's <form> in the old system) -->
    <div class="modal fade exit-modal" id="archivedDocumentsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Archived Documents</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table id="modal-archived-documents-table" class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Document</th>
                                    <th>Type</th>
                                    <th>Linked Case</th>
                                    <th>Archived At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="modal-archived-documents-tbody">
                                <tr><td colspan="6" class="text-center text-muted">Loading archived documents...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="modal-archived-documents-pagination" class="mt-2 d-flex justify-content-end"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- page scripts are loaded by the persistent module shell -->
<script>
    if (typeof loadDocumentsTable === 'function') {
        loadDocumentsTable('all', 1, 10, '');
    }
    if (typeof loadEmployees === 'function') {
        loadEmployees();
    }
</script>
