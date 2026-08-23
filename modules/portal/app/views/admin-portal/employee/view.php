<div class="modal fade" id="employeeViewModal" tabindex="-1" aria-labelledby="employeeViewModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content border-0 shadow-lg rounded-4">

            <!-- HEADER -->
            <div class="modal-header px-4 py-3 border-bottom">

                <div>
                    <h5 class="modal-title fw-bold mb-1" id="employeeViewModalLabel">
                        Employee Details
                    </h5>

                    <small class="text-muted">
                        Employee profile information
                    </small>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body p-4">

                <!-- PROFILE -->
                <div class="d-flex align-items-center gap-3 pb-4 mb-4 border-bottom">


                    <div class="position-relative" style="width:56px;height:56px;">

                        <img id="modalProfileImage" src="" alt="Profile Photo" class="rounded-circle"
                            style="display:none;width:56px;height:56px;object-fit:cover;">

                        <div id="modalInitials"
                            class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-bold"
                            style="display:none;width:56px;height:56px;">
                        </div>

                    </div>

                    <div class="min-w-0">

                        <h5 id="modalFullName" class="fw-bold mb-1 text-dark">
                            --
                        </h5>

                        <div class="d-flex align-items-center gap-2">

                            <span id="modalEmployeeNumber" class="font-monospace small text-muted">
                                --
                            </span>

                            <span class="text-muted">•</span>

                            <span id="modalStatus" class="badge rounded-pill">
                                --
                            </span>

                        </div>

                    </div>

                </div>


                <!-- EMPLOYMENT INFORMATION -->
                <h6 class="fw-bold text-dark mb-3">
                    Employment Information
                </h6>

                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                Department
                            </small>
                            <div id="modalDepartment" class="fw-semibold text-dark">
                                --
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                Position
                            </small>
                            <div id="modalPosition" class="fw-semibold text-dark">
                                --
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                Employment Type
                            </small>
                            <div id="modalEmploymentType" class="fw-semibold text-dark">
                                --
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                Date Hired
                            </small>
                            <div id="modalDateHired" class="fw-semibold text-dark">
                                --
                            </div>
                        </div>
                    </div>

                </div>


                <!-- PERSONAL INFORMATION -->
                <h6 class="fw-bold text-dark mb-3">
                    Personal Information
                </h6>

                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                First Name
                            </small>
                            <div id="modalFirstName">
                                --
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                Middle Name
                            </small>
                            <div id="modalMiddleName">
                                --
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                Last Name
                            </small>
                            <div id="modalLastName">
                                --
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                Suffix
                            </small>
                            <div id="modalSuffix">
                                --
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                Gender
                            </small>
                            <div id="modalGender">
                                --
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                Birth Date
                            </small>
                            <div id="modalBirthDate">
                                --
                            </div>
                        </div>
                    </div>

                </div>


                <!-- CONTACT INFORMATION -->
                <h6 class="fw-bold text-dark mb-3">
                    Contact Information
                </h6>

                <div class="row g-3">

                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                Phone
                            </small>
                            <div id="modalPhone">
                                --
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="bg-light rounded-3 p-3">
                            <small class="text-muted d-block mb-1">
                                Address
                            </small>
                            <div id="modalAddress">
                                --
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer border-top px-4 py-3">

                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">
                    Close
                </button>

            </div>

        </div>

    </div>
</div>