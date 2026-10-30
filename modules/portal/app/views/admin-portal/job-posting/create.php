<!-- CREATE JOB POSTING MODAL -->

<!-- Font Awesome 6.5.1 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="modal fade" id="jobPostingModal" tabindex="-1" aria-labelledby="jobPostingModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">

        <div class="modal-content border-0 shadow-lg">

            <!-- HEADER -->
            <div class="modal-header text-white px-3 px-md-4 py-3"
                style="background: linear-gradient(135deg, #1e3a8a, #2563eb);">

                <div class="d-flex align-items-center gap-3">

                    <div class="job-header-icon">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>

                    <div class="min-w-0">
                        <h5 class="modal-title fw-bold mb-1" id="jobPostingModalLabel">
                            Create Job Posting
                        </h5>

                        <p class="mb-0 small text-white-50">
                            Add a new employment opportunity
                        </p>
                    </div>

                </div>

                <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close">
                </button>

            </div>

            <!-- FORM -->
            <form method="POST" enctype="multipart/form-data"
                action="<?= htmlspecialchars(appUrl('index.php?url=job-posting-store'), ENT_QUOTES, 'UTF-8') ?>">

                <!-- MODAL BODY -->
                <div class="modal-body">

                    <!-- INFORMATION NOTICE -->
                    <div class="alert alert-primary border-0 rounded-3 d-flex gap-2 align-items-start mb-4">

                        <i class="fa-solid fa-circle-info mt-1 flex-shrink-0"></i>

                        <div class="small">
                            Complete the job posting information below.
                            Fields marked with
                            <span class="text-danger fw-bold">*</span>
                            are required.
                        </div>

                    </div>

                    <!-- COMPANY INFORMATION -->
                    <div class="job-form-section">

                        <div class="job-section-heading">

                            <div class="job-section-icon text-primary bg-primary bg-opacity-10">
                                <i class="fa-solid fa-building"></i>
                            </div>

                            <div>
                                <h6 class="fw-bold mb-0">
                                    Company Information
                                </h6>

                                <small class="text-muted">
                                    Basic details about the hiring company
                                </small>
                            </div>

                        </div>

                        <div class="row g-3">

                            <!-- COMPANY NAME -->
                            <div class="col-12 col-md-6">

                                <label for="comp_name" class="form-label">
                                    Company Name
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        <i class="fa-solid fa-building"></i>
                                    </span>

                                    <input type="text" id="comp_name" name="comp_name" class="form-control"
                                        placeholder="Enter company name" maxlength="255" required>

                                </div>

                            </div>


                            <!-- COMPANY LOGO -->
                            <div class="col-12 col-md-6">

                                <label for="comp_logo" class="form-label">
                                    Company Logo
                                </label>

                                <!-- FILE INPUT -->
                                <div class="input-group" id="companyLogoInputContainer">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-image"></i>
                                    </span>

                                    <input type="file" id="comp_logo" name="comp_logo" class="form-control"
                                        accept="image/png, image/jpeg, image/webp">
                                    <small class="text-muted">
                                        Upload a PNG, JPG, JPEG, or WEBP image. Maximum size: 2MB.
                                    </small>
                                </div>

                                <!-- IMAGE PREVIEW -->
                                <div id="companyLogoPreviewContainer" class="mt-3 d-none">
                                    <div class="d-flex align-items-center gap-3">

                                        <img id="companyLogoPreview" src="" alt="Company Logo Preview"
                                            style="width: 90px; height: 90px; object-fit: contain; border: 1px solid #dee2e6; border-radius: 8px; padding: 5px;">

                                        <button type="button" id="removeCompanyLogo"
                                            class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-trash"></i>
                                            Remove
                                        </button>

                                    </div>
                                </div>

                            </div>


                            <!-- COMPANY EMAIL -->
                            <div class="col-12 col-md-6">

                                <label for="comp_email" class="form-label">
                                    Company Email
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        <i class="fa-solid fa-envelope"></i>
                                    </span>

                                    <input type="email" id="comp_email" name="comp_email" class="form-control"
                                        placeholder="company@example.com" maxlength="255" required>

                                </div>

                                <div id="emailFeedback" class="small mt-1"></div>

                            </div>


                            <!-- CONTACT NUMBER -->
                            <div class="col-12 col-md-6">

                                <label for="comp_number" class="form-label">
                                    Contact Number
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-phone"></i>
                                    </span>

                                    <input type="tel" id="comp_number" name="comp_number" class="form-control"
                                        placeholder="09XXXXXXXXX" maxlength="11" minlength="11" pattern="[0-9]{11}"
                                        inputmode="numeric" title="Please enter exactly 11 digits.">
                                </div>

                                <div id="numberFeedback" class="small mt-1"></div>

                            </div>


                            <!-- COMPANY ADDRESS -->
                            <div class="col-12">

                                <label for="comp_address" class="form-label">
                                    Company Address
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text align-items-start pt-2">
                                        <i class="fa-solid fa-location-dot"></i>
                                    </span>

                                    <textarea id="comp_address" name="comp_address" class="form-control" rows="2"
                                        placeholder="Enter complete company address" required></textarea>

                                </div>

                            </div>

                        </div>

                    </div>

                    <hr>

                    <!-- OPEN POSITIONS -->
                    <div class="job-form-section">

                        <div class="job-section-heading">

                            <div class="job-section-icon text-success bg-success bg-opacity-10">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>

                            <div>
                                <h6 class="fw-bold mb-0">
                                    Open Positions
                                </h6>

                                <small class="text-muted">
                                    Available job opportunities
                                </small>
                            </div>

                        </div>

                        <label for="open_positions" class="form-label">
                            Job Positions
                            <span class="text-danger">*</span>
                        </label>

                        <textarea id="open_positions" name="open_positions" class="form-control" rows="3"
                            placeholder="PHP Developer&#10;HR Assistant&#10;IT Support" required></textarea>

                        <div class="form-text">
                            <i class="fa-solid fa-lightbulb me-1"></i>
                            Enter one job position per line.
                        </div>

                    </div>

                    <hr>

                    <!-- QUALIFICATIONS -->
                    <div class="job-form-section">

                        <div class="job-section-heading">

                            <div class="job-section-icon text-warning bg-warning bg-opacity-10">
                                <i class="fa-solid fa-clipboard-check"></i>
                            </div>

                            <div>
                                <h6 class="fw-bold mb-0">
                                    Qualifications
                                </h6>

                                <small class="text-muted">
                                    Skills, education, and applicant requirements
                                </small>
                            </div>

                        </div>

                        <label for="qualifications" class="form-label">
                            Applicant Qualifications
                        </label>

                        <textarea id="qualifications" name="qualifications" class="form-control" rows="3"
                            placeholder="Bachelor's degree in a related field&#10;Good communication skills&#10;At least 1 year of experience"></textarea>

                        <div class="form-text">
                            Enter one qualification per line.
                        </div>

                    </div>

                    <hr>

                    <!-- EMPLOYEE BENEFITS -->
                    <div class="job-form-section">

                        <div class="job-section-heading">

                            <div class="job-section-icon text-info bg-info bg-opacity-10">
                                <i class="fa-solid fa-gift"></i>
                            </div>

                            <div>
                                <h6 class="fw-bold mb-0">
                                    Employee Benefits
                                </h6>

                                <small class="text-muted">
                                    Benefits offered by the company
                                </small>
                            </div>

                        </div>

                        <label for="benefits" class="form-label">
                            Available Benefits
                        </label>

                        <textarea id="benefits" name="benefits" class="form-control" rows="3"
                            placeholder="Government-mandated benefits&#10;Health insurance&#10;Paid leave"></textarea>

                        <div class="form-text">
                            Enter one benefit per line.
                        </div>

                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer">

                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">

                        <i class="fa-solid fa-xmark me-1"></i>
                        Cancel

                    </button>

                    <button type="submit" class="btn btn-primary">

                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        Save Job Posting

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- MODAL CUSTOM STYLES -->
<style>
    /* MODAL SIZE */
    #jobPostingModal .modal-dialog {
        max-width: 850px;
        width: calc(100% - 30px);
    }

    /* MODAL CONTENT */
    #jobPostingModal .modal-content {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
    }

    /* HEADER */
    #jobPostingModal .modal-header {
        padding: 14px 22px !important;
    }

    #jobPostingModal .modal-title {
        font-size: 17px;
    }

    #jobPostingModal .modal-header p {
        font-size: 11px;
    }

    #jobPostingModal .job-header-icon {
        width: 38px;
        height: 38px;
        min-width: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.2);
        font-size: 16px;
    }

    /* BODY */
    #jobPostingModal .modal-body {
        padding: 20px 24px;
        max-height: 65vh;
        overflow-y: auto;
    }

    #jobPostingModal .modal-body>.alert {
        padding: 10px 14px;
        font-size: 12px;
    }

    /* SECTION */
    #jobPostingModal .job-form-section {
        margin-bottom: 18px;
    }

    #jobPostingModal .job-section-heading {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }

    #jobPostingModal .job-section-heading h6 {
        font-size: 14px;
    }

    #jobPostingModal .job-section-heading small {
        font-size: 11px;
    }

    #jobPostingModal .job-section-icon {
        width: 32px;
        height: 32px;
        min-width: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 13px;
    }

    /* FONT AWESOME ICONS */
    #jobPostingModal i.fa-solid {
        display: inline-block;
        line-height: 1;
    }

    /* FORM */
    #jobPostingModal .form-label {
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 5px;
    }

    #jobPostingModal .form-control,
    #jobPostingModal .input-group-text {
        min-height: 38px;
        font-size: 12px;
    }

    #jobPostingModal .form-control {
        padding: 8px 11px;
    }

    #jobPostingModal textarea.form-control {
        min-height: 70px;
        resize: vertical;
    }

    #jobPostingModal .input-group-text {
        min-width: 38px;
        justify-content: center;
        padding: 7px;
        background: #f8fafc;
        color: #64748b;
        border-color: #dee2e6;
    }

    #jobPostingModal .form-control:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 0.15rem rgba(37, 99, 235, 0.12);
    }

    #jobPostingModal .form-text {
        font-size: 11px;
        margin-top: 5px;
    }

    /* DIVIDERS */
    #jobPostingModal hr {
        margin: 16px 0;
        opacity: 0.12;
    }

    /* FOOTER */
    #jobPostingModal .modal-footer {
        padding: 12px 24px;
        gap: 8px;
        background: #f8fafc;
    }

    #jobPostingModal .modal-footer .btn {
        font-size: 12px;
        font-weight: 600;
        padding: 8px 15px;
    }

    /* TABLET */
    @media (max-width: 991.98px) {

        #jobPostingModal .modal-dialog {
            max-width: 750px;
        }

        #jobPostingModal .modal-body {
            max-height: 70vh;
            padding: 18px !important;
        }

    }

    /* MOBILE */
    @media (max-width: 575.98px) {

        #jobPostingModal .modal-dialog {
            margin: 0;
            max-width: 100%;
            width: 100%;
            height: 100%;
        }

        #jobPostingModal .modal-content {
            height: 100%;
            min-height: 100%;
            border-radius: 0;
        }

        #jobPostingModal .modal-header {
            padding: 12px 15px !important;
        }

        #jobPostingModal .modal-title {
            font-size: 15px;
        }

        #jobPostingModal .modal-body {
            max-height: none;
            flex: 1 1 auto;
            padding: 15px !important;
        }

        #jobPostingModal .job-section-icon {
            width: 30px;
            height: 30px;
            min-width: 30px;
            font-size: 12px;
        }

        #jobPostingModal .job-section-heading h6 {
            font-size: 13px;
        }

        #jobPostingModal .modal-footer {
            padding: 10px 15px !important;
            display: flex;
            flex-wrap: nowrap;
        }

        #jobPostingModal .modal-footer .btn {
            flex: 1;
            padding: 9px 7px;
            font-size: 11px;
            white-space: nowrap;
        }

    }
</style>
<script>
    const logoInput = document.getElementById('comp_logo');
    const inputContainer = document.getElementById('companyLogoInputContainer');
    const previewContainer = document.getElementById('companyLogoPreviewContainer');
    const previewImage = document.getElementById('companyLogoPreview');
    const removeButton = document.getElementById('removeCompanyLogo');

    let previewUrl = null;

    logoInput.addEventListener('change', function () {
        const file = this.files[0];

        if (!file) return;

        const allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];

        if (!allowedTypes.includes(file.type)) {
            alert('Please select a PNG, JPG, or WEBP image.');
            this.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert('Image size must not exceed 2MB.');
            this.value = '';
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        previewUrl = URL.createObjectURL(file);
        previewImage.src = previewUrl;

        // Hide input and show preview
        inputContainer.classList.add('d-none');
        previewContainer.classList.remove('d-none');
    });

    removeButton.addEventListener('click', function () {
        logoInput.value = '';
        previewImage.src = '';

        // Show input and hide preview
        inputContainer.classList.remove('d-none');
        previewContainer.classList.add('d-none');

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
    });


    document.addEventListener('DOMContentLoaded', function () {

        const emailInput = document.getElementById('comp_email');
        const numberInput = document.getElementById('comp_number');

        const emailFeedback = document.getElementById('emailFeedback');
        const numberFeedback = document.getElementById('numberFeedback');

        // Reusable validation display
        function showValidation(input, feedback, isValid, message) {

            input.classList.remove('is-valid', 'is-invalid');

            if (input.value.trim() === '') {
                feedback.textContent = '';
                return;
            }

            input.classList.add(isValid ? 'is-valid' : 'is-invalid');

            feedback.textContent = message;
            feedback.className = isValid
                ? 'small mt-1 text-success'
                : 'small mt-1 text-danger';
        }


        // COMPANY EMAIL VALIDATION
        emailInput.addEventListener('input', function () {

            const email = this.value.trim();

            // Basic email format validation
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

            const isValid = emailPattern.test(email);

            showValidation(
                emailInput,
                emailFeedback,
                isValid,
                isValid
                    ? 'Valid email address.'
                    : 'Please enter a valid email address.'
            );

        });


        // CONTACT NUMBER VALIDATION
        numberInput.addEventListener('input', function () {

            const number = this.value.trim();

            // Allow digits, spaces, +, -, and parentheses
            const allowedCharacters = /^[0-9+\-()\s]+$/;

            // Count digits only
            const digitCount = number.replace(/\D/g, '').length;

            const isValid =
                allowedCharacters.test(number) &&
                digitCount >= 7 &&
                digitCount <= 15;

            showValidation(
                numberInput,
                numberFeedback,
                isValid,
                isValid
                    ? 'Valid contact number.'
                    : 'Enter 7–15 digits. Only numbers, +, -, and parentheses are allowed.'
            );

        });

    });

        const phoneInput = document.getElementById('comp_number');

        phoneInput.addEventListener('input', function () {
            // Allow digits only
            this.value = this.value.replace(/[^0-9]/g, '');

        // Limit to 11 digits
        this.value = this.value.slice(0, 11);
    });

</script>