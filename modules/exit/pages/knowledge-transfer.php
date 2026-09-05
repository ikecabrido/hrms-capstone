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
        <h1>Knowledge Transfer</h1>
    </div>

    <div class="module-content">
        <div id="transfers-section" class="section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2" style="flex: 1;">
                        <div class="input-group input-group-sm" style="flex: 19;">
                            <input type="text" id="transfer-search" class="form-control" placeholder="Search transfers..." onkeyup="onTransferSearchChange()">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                        <select id="transfer-status-filter" class="form-control form-control-sm" onchange="onTransferStatusFilterChange()" style="flex: 1; white-space: nowrap;">
                            <option value="all">All</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="card-tools d-flex align-items-center">
                        <button type="button" class="btn btn-warning btn-sm mr-2 position-relative" onclick="archiveTransfers()">
                            <i class="fas fa-archive"></i> Archive
                            <span id="transfer-archive-notif-count" class="badge badge-danger archive-count-badge" style="display:none; position:absolute; top:0; right:0; transform: translate(50%, -50%);">0</span>
                        </button>
                        <button type="button" class="btn btn-success btn-sm" onclick="showTransferModal()">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="transfers-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Successor</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="transfers-tbody">
                            </tbody>
                        </table>
                    </div>
                    <div id="transfers-pagination" class="d-flex justify-content-center mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="customToastContainer" style="position: fixed; top: 1rem; right: 1rem; z-index: 11000; display: flex; flex-direction: column; gap: .75rem;"></div>

    <!-- Knowledge Transfer Modal -->
    <div class="modal fade exit-modal" id="transferModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="transferModalTitle">Create Knowledge Transfer Plan</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="transferForm">
                    <div class="modal-body">
                        <input type="hidden" id="transferPlanId" name="plan_id">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transferEmployeeSelect">Employee Leaving *</label>
                                    <select class="form-control" id="transferEmployeeSelect" name="employee_id" required>
                                        <option value="">Select Employee</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="successorSelect">Successor</label>
                                    <select class="form-control" id="successorSelect" name="successor_id">
                                        <option value="">Select Successor</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transferStartDate">Start Date *</label>
                                    <input type="date" class="form-control" id="transferStartDate" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transferEndDate">End Date *</label>
                                    <input type="date" class="form-control" id="transferEndDate" name="end_date" required>
                                </div>
                            </div>
                        </div>

                        <!-- Transfer Items Section -->
                        <div class="form-group">
                            <label>Knowledge Transfer Items</label>
                            <div id="transferItemsContainer">
                                <div class="transfer-item mb-3 p-3 border rounded">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <select class="form-control" name="items[0][type]" required>
                                                <option value="">Select Type</option>
                                                <option value="process">Process</option>
                                                <option value="system">System</option>
                                                <option value="contact">Contact</option>
                                                <option value="document">Document</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" name="items[0][title]" placeholder="Title" required>
                                        </div>
                                        <div class="col-md-2">
                                            <select class="form-control" name="items[0][priority]">
                                                <option value="medium">Medium</option>
                                                <option value="low">Low</option>
                                                <option value="high">High</option>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-12 mb-2">
                                            <textarea class="form-control" name="items[0][description]" rows="2" placeholder="Description"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <textarea class="form-control" name="items[0][notes]" rows="2" placeholder="Notes"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addTransferItem">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-info" id="editTransferBtn" style="display:none;">Edit Transfer Plan</button>
                        <button type="submit" class="btn btn-warning" id="transferSubmitBtn">Create Transfer Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archived Transfer Plans Modal -->
    <div class="modal fade exit-modal" id="archivedTransfersModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-secondary">
                    <h5 class="modal-title">Archived Transfer Plans</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Archived At</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="archived-transfers-tbody">
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Loading archived transfers...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="archived-transfers-pagination" class="mt-2 d-flex justify-content-end"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<script src="assets/vendor/flatpickr/flatpickr.min.js"></script>
<script>
    if (typeof loadTransfersTable === 'function') {
        loadTransfersTable('all', 1, 10, '');
    }
    if (typeof loadEmployees === 'function') {
        loadEmployees();
    }
</script>
