<!-- VIEW RESIGNATION MODAL -->
<div class="modal fade" id="viewResignationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            <!-- HEADER -->
            <div class="modal-header border-0 px-4 py-3 bg-slate-50">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-blue-100 text-blue-600"
                            style="width:34px;height:34px;">
                            <i class="fas fa-file-signature"></i>
                        </div>

                        <div>
                            <h5 class="mb-0 fw-bold text-slate-800" style="font-size:15px;">
                                Resignation Details
                            </h5>

                            <small class="text-muted" style="font-size:10px;">
                                Employee resignation request
                            </small>
                        </div>
                    </div>
                </div>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body p-4">

                <!-- EMPLOYEE -->
                <div class="d-flex align-items-center gap-3 p-3 mb-3 rounded-3"
                    style="background:#f8fafc;border:1px solid #e2e8f0;">

                    <div id="viewEmployeeAvatar"
                        class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-blue-600"
                        style="width:48px;height:48px;background:#dbeafe;font-size:16px;">
                        —
                    </div>

                    <div class="flex-grow-1">
                        <div class="text-uppercase fw-bold text-muted"
                            style="font-size:9px;">
                            Employee
                        </div>

                        <div id="viewEmployeeName"
                            class="fw-bold text-slate-800"
                            style="font-size:14px;">
                            —
                        </div>

                        <div class="text-muted" style="font-size:10px;">
                            Resignation ID:
                            <span id="viewResignationId" class="fw-semibold">
                                —
                            </span>
                        </div>
                    </div>

                    <div id="viewStatus"
                        class="px-3 py-1 rounded-pill fw-bold"
                        style="font-size:10px;">
                        Pending
                    </div>

                </div>

                <!-- DETAILS -->
                <div class="row g-3">

                    <!-- RESIGNATION TYPE -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 h-100"
                            style="background:#f8fafc;border:1px solid #e2e8f0;">

                            <div class="text-uppercase fw-bold text-muted mb-1"
                                style="font-size:9px;">
                                <i class="fas fa-layer-group me-1"></i>
                                Resignation Type
                            </div>

                            <div id="viewResignationType"
                                class="fw-semibold text-slate-700"
                                style="font-size:12px;">
                                —
                            </div>

                        </div>
                    </div>

                    <!-- DATE SUBMITTED -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 h-100"
                            style="background:#f8fafc;border:1px solid #e2e8f0;">

                            <div class="text-uppercase fw-bold text-muted mb-1"
                                style="font-size:9px;">
                                <i class="fas fa-calendar-plus me-1"></i>
                                Date Submitted
                            </div>

                            <div id="viewDateSubmitted"
                                class="fw-semibold text-slate-700"
                                style="font-size:12px;">
                                —
                            </div>

                        </div>
                    </div>

                    <!-- LAST WORKING DAY -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 h-100"
                            style="background:#f8fafc;border:1px solid #e2e8f0;">

                            <div class="text-uppercase fw-bold text-muted mb-1"
                                style="font-size:9px;">
                                <i class="fas fa-calendar-check me-1"></i>
                                Intended Last Working Day
                            </div>

                            <div id="viewLastWorkingDay"
                                class="fw-semibold text-slate-700"
                                style="font-size:12px;">
                                —
                            </div>

                        </div>
                    </div>

                    <!-- STATUS -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 h-100"
                            style="background:#f8fafc;border:1px solid #e2e8f0;">

                            <div class="text-uppercase fw-bold text-muted mb-1"
                                style="font-size:9px;">
                                <i class="fas fa-circle-check me-1"></i>
                                Current Status
                            </div>

                            <div id="viewStatusText"
                                class="fw-semibold"
                                style="font-size:12px;">
                                —
                            </div>

                        </div>
                    </div>

                    <!-- REASON -->
                    <div class="col-12">
                        <div class="p-3 rounded-3"
                            style="background:#f8fafc;border:1px solid #e2e8f0;">

                            <div class="text-uppercase fw-bold text-muted mb-2"
                                style="font-size:9px;">
                                <i class="fas fa-comment-alt me-1"></i>
                                Resignation Reason
                            </div>

                            <div id="viewResignationReason"
                                class="text-slate-700"
                                style="font-size:12px;line-height:1.6;white-space:pre-wrap;">
                                —
                            </div>

                        </div>
                    </div>

                </div>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer border-0 px-4 py-3"
                style="background:#f8fafc;">

                <button type="button"
                    class="btn btn-light border px-3 py-2 rounded-3 fw-semibold"
                    style="font-size:11px;"
                    data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Close
                </button>

            </div>

        </div>
    </div>
</div>