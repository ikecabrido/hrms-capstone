<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestlink College of the Philippines — Careers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/portal.css">
</head>

<body>
    <header>
        <div class="header-inner">
            <div class="brand">
                <div class="logo-wrap" id="logoWrap">
                    <span class="logo-fallback" id="logoFallback">BC</span>
                    <img src="../assets/bcp-logo.png" id="logoImg">
                </div>
                <div class="brand-text">
                    <div class="school-name" id="schoolNameText">Bestlink College of the Philippines</div>
                    <div class="school-sub" id="schoolSubText">Careers &amp; Employment</div>
                </div>
            </div>
            <nav>
                <button class="nav-btn gold" onclick="openContactUs()">Contact Us</button>
            </nav>
        </div>
    </header>

    <section class="hero" id="heroSection">
        <div class="hero-overlay"></div>
        <div class="hero-inner">
            <div class="hero-text">
                <div class="hero-eyebrow">Office of Human Resources</div>
                <h1 id="heroHeadline">Build your career at Bestlink College of the Philippines</h1>
                <p id="heroSub">Browse current openings across our academic and administrative departments, and submit your application online — no printouts required.</p>
                <div class="hero-cta">
                    <button class="btn btn-primary" onclick="scrollToJobs()">View openings</button>
                </div>
            </div>
            <div class="seal-wrap" aria-hidden="true">
                <img src="../assets/teacher.png" alt="" class="teacher-image">
            </div>
        </div>
    </section>

    <section class="section" id="jobsSection">
        <div class="section-head">
            <div>
                <div class="kicker">Current openings</div>
                <h2>Job opportunities</h2>
                <p>Positions posted by the Office of Human Resources.</p>
            </div>
            <div class="count-pill" id="jobCountPill">0 open positions</div>
        </div>
        <div class="jobs-grid" id="jobsGrid"></div>
    </section>

    <footer>
        <div class="footer-inner">
            <div><b>Bestlink College of the Philippines</b> — Office of Human Resources</div>
        </div>
    </footer>

    <!-- ===================== POST JOB MODAL ===================== -->
    <div class="modal-backdrop" id="postJobModal">
        <div class="modal">
            <div class="modal-head">
                <div class="htext">
                    <div class="k">Office of Human Resources</div>
                    <h3>Post a job</h3>
                </div>
                <button class="modal-close" onclick="closeModal('postJobModal')" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="postJobForm" onsubmit="handlePostJobSubmit(event)">
                    <div class="form-grid">
                        <div class="form-row"><label class="req">Job Title</label><input type="text" id="pjTitle" required></div>
                        <div class="form-row"><label class="req">Position</label><input type="text" id="pjPosition" required></div>
                        <div class="form-row"><label class="req">Department / College</label><input type="text" id="pjDept" required></div>
                        <div class="form-row"><label class="req">Work Location</label><input type="text" id="pjLocation" required></div>
                        <div class="form-row"><label class="req">Number of Vacancies</label><input type="number" id="pjVacancy" min="0" value="0" required><span class="hint">Use 0 for unlimited applicants.</span></div>
                        <div class="form-row full" style="grid-column:1/-1;"><label class="req">Job Description</label><textarea id="pjDesc" required></textarea></div>
                        <div class="form-row full" style="grid-column:1/-1;"><label class="req">Qualifications</label><textarea id="pjQual" required></textarea></div>
                    </div>
                    <div class="submit-row">
                        <button type="submit" class="btn-submit">Publish job</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================== APPLY MODAL ===================== -->
    <div class="modal-backdrop" id="applyModal">
        <div class="modal wide">
            <div class="modal-head">
                <div class="htext">
                    <div class="k">Applicant application form</div>
                    <h3 id="applyModalTitle">Apply for position</h3>
                </div>
            </div>
            <div class="modal-body" id="applyModalBody">
                <div id="applyFormWrap">
                    <div class="pulled-card">
                        <div class="pulled-grid">
                            <div class="pulled-item">
                                <div class="l">Job Title</div>
                                <div class="v" id="pfJobTitle">—</div>
                            </div>
                            <div class="pulled-item">
                                <div class="l">Position</div>
                                <div class="v" id="pfPosition">—</div>
                            </div>
                            <div class="pulled-item">
                                <div class="l">Department / College</div>
                                <div class="v" id="pfDept">—</div>
                            </div>
                            <div class="pulled-item">
                                <div class="l">Employment Type</div>
                                <div class="v" id="pfType">—</div>
                            </div>
                            <div class="pulled-item">
                                <div class="l">Work Location</div>
                                <div class="v" id="pfLocation">—</div>
                            </div>
                            <div class="pulled-item">
                                <div class="l">Salary Range</div>
                                <div class="v" id="pfSalary">—</div>
                            </div>
                            <div class="pulled-item">
                                <div class="l">Number of Vacancies</div>
                                <div class="v" id="pfVacancy">—</div>
                            </div>
                            <div class="pulled-item full">
                                <div class="l">Job Description</div>
                                <div class="v small" id="pfDesc">—</div>
                            </div>
                            <div class="pulled-item full">
                                <div class="l">Qualifications</div>
                                <div class="v small" id="pfQual">—</div>
                            </div>
                        </div>
                    </div>

                    <form id="applyForm" onsubmit="handleApplySubmit(event)">
                        <input type="hidden" id="jobId" name="jobId" value="">
                        <div class="part-label">
                            <div class="part-num">1</div>
                            <h4>Personal information</h4>
                        </div>
                        <div class="form-grid name-inline">
                            <div class="form-row"><label class="req">First Name</label><input type="text" id="apFirstName" required></div>
                            <div class="form-row"><label>Middle Name</label><input type="text" id="apMiddleName"></div>
                            <div class="form-row"><label class="req">Last Name</label><input type="text" id="apLastName" required></div>
                        </div>
                        <div class="form-grid two-col-inline">
                            <div class="form-row"><label class="req">Age</label><input type="number" id="apAge" min="18" max="100" required></div>
                            <div class="form-row"><label class="req">Gender</label>
                                <select id="apGender" required>
                                    <option value="">Select</option>
                                    <option value="Female">Female</option>
                                    <option value="Male">Male</option>
                                </select>
                            </div>
                            <div class="form-row"><label class="req">Birthplace</label><input type="text" id="apBirthplace" placeholder="City / Province" required></div>
                            <div class="form-row"><label class="req">Birthdate</label><input type="date" id="apDob" required></div>
                            <div class="form-row"><label class="req">Civil Status</label>
                                <select id="apCivilStatus" required>
                                    <option value="">Select</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Separated">Separated</option>
                                    <option value="Annulled">Annulled</option>
                                </select>
                            </div>
                            <div class="form-row"><label class="req">Citizenship</label><input type="text" id="apCitizenship" placeholder="e.g. Filipino" required></div>
                            <div class="form-row"><label class="req">Religion</label><input type="text" id="apReligion" placeholder="e.g. Roman Catholic" required></div>
                            <div class="form-row"><label class="req">Contact Number</label><input type="tel" id="apContact" placeholder="09XX XXX XXXX" required></div>
                            <div class="form-row full"><label class="req">Email Address</label><input type="email" id="apEmail" placeholder="name@email.com" required></div>
                            <div class="form-row full" style="grid-column:1/-1;"><label class="req">Current Address</label><input type="text" id="apAddress" placeholder="House no., street, barangay, city" required></div>
                            <div class="form-row full" style="grid-column:1/-1;"><label>Permanent Address</label><input type="text" id="apPermanentAddress" placeholder="House no., street, barangay, city"></div>
                        </div>

                        <div class="part-label">
                            <div class="part-num">2</div>
                            <h4>Education</h4>
                        </div>
                        <div class="form-grid">
                            <div class="form-row"><label class="req">School</label><input type="text" id="apSchool" required></div>
                            <div class="form-row"><label class="req">Degree / Course</label><input type="text" id="apDegree" required></div>
                            <div class="form-row"><label class="req">Year Graduated</label><input type="number" id="apYear" placeholder="e.g. 2022" min="1960" max="2030" required></div>
                        </div>

                        <div class="part-label">
                            <div class="part-num">3</div>
                            <h4>Experience <span class="tl">Leave blank if not applicable</span></h4>
                        </div>
                        <div class="form-grid">
                            <div class="form-row"><label>Previous Employer</label><input type="text" id="apEmployer"></div>
                            <div class="form-row"><label>Position</label><input type="text" id="apPrevPosition"></div>
                            <div class="form-row"><label>Start Date</label><input type="date" id="apStart"></div>
                            <div class="form-row"><label>End Date</label><input type="date" id="apEnd"></div>
                            <div class="form-row full" style="grid-column:1/-1;"><label>Job Description</label><textarea id="apExpDesc" placeholder="Briefly describe your duties"></textarea></div>
                        </div>

                        <div class="part-label">
                            <div class="part-num">4</div>
                            <h4>Documents</h4>
                        </div>
                        <div class="form-row"><label class="req">Resume / CV</label>
                            <div class="file-row"><span class="fname" id="fn-resume">No file selected</span><label class="filebtn">Choose file<input type="file" id="apResume" onchange="onFilePicked('apResume','fn-resume')" required></label></div>
                        </div>
                        <!--
                        <div class="form-row"><label class="req">Diploma</label>
                            <div class="file-row"><span class="fname" id="fn-diploma">No file selected</span><label class="filebtn">Choose file<input type="file" id="apDiploma" onchange="onFilePicked('apDiploma','fn-diploma')" required></label></div>
                        </div>
                        <div class="form-row"><label class="req">Transcript of Records</label>
                            <div class="file-row"><span class="fname" id="fn-tor">No file selected</span><label class="filebtn">Choose file<input type="file" id="apTor" onchange="onFilePicked('apTor','fn-tor')" required></label></div>
                        </div>
                        <div class="form-row"><label>Certificates <span class="hint">optional</span></label>
                            <div class="file-row"><span class="fname" id="fn-certs">No file selected</span><label class="filebtn">Choose file<input type="file" id="apCerts" onchange="onFilePicked('apCerts','fn-certs')"></label></div>
                        </div>
                        <div class="form-row"><label>Other required documents <span class="hint">optional</span></label>
                            <div class="file-row"><span class="fname" id="fn-other">No file selected</span><label class="filebtn">Choose file<input type="file" id="apOther" onchange="onFilePicked('apOther','fn-other')"></label></div>
                        </div>
-->
                        <div class="submit-row">
                            <button type="submit" class="btn-submit">Submit application</button>
                        </div>
                    </form>
                </div>

                <div id="applySuccessWrap" style="display:none;">
                    <div class="success-view">
                        <div class="success-icon">✓</div>
                        <h3>Application submitted</h3>
                        <p id="successText">Thank you for applying. The HR office will review your application and reach out through the contact details you provided.</p>
                        <div class="ref-box" id="successRef">BCP-0000-00000</div>
                        <div><button class="btn btn-outline" style="color:var(--navy); border-color:var(--navy);" onclick="closeModal('applyModal')">Done</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== APPLICATIONS (ADMIN) MODAL ===================== -->
    <div class="modal-backdrop" id="applicationsModal">
        <div class="modal wide">
            <div class="modal-head">
                <div class="htext">
                    <div class="k">Office of Human Resources</div>
                    <h3>Submitted applications</h3>
                </div>
                <button class="modal-close" onclick="closeModal('applicationsModal')" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body" id="applicationsBody"></div>
        </div>
    </div>

    <!-- ===================== CONTACT US MODAL ===================== -->
    <div class="modal-backdrop" id="contactUsModal">
        <div class="modal">
            <div class="modal-head">
                <div class="htext">
                    <div class="k">Bestlink College of the Philippines</div>
                    <h3>Contact Us</h3>
                </div>
                <button class="modal-close"
                    onclick="closeModal('contactUsModal')"
                    aria-label="Close">
                    &times;
                </button>
            </div>

            <div class="modal-body">
                <div class="pulled-card">
                    <div class="pulled-note">Office of Human Resources</div>

                    <div class="pulled-grid">
                        <div class="pulled-item">
                            <div class="l">Office</div>
                            <div class="v">Human Resources Department</div>
                        </div>

                        <div class="pulled-item">
                            <div class="l">Email</div>
                            <div class="v">hr@bestlink.edu.ph</div>
                        </div>

                        <div class="pulled-item">
                            <div class="l">Phone</div>
                            <div class="v">(02) 7750-0000</div>
                        </div>

                        <div class="pulled-item">
                            <div class="l">Office Hours</div>
                            <div class="v">Monday – Friday</div>
                        </div>

                        <div class="pulled-item full">
                            <div class="l">Address</div>
                            <div class="v small">
                                Bestlink College of the Philippines IPO Road,
                                Barangay MinuyanProper, City of San Jose del Monte,
                                Bulacan
                            </div>
                        </div>
                    </div>
                </div>

                <div class="submit-row">
                    <button
                        type="button"
                        class="btn-submit"
                        onclick="closeModal('contactUsModal')">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/core.js"></script>
    <script src="../js/ui.js"></script>
    <script src="../js/jobs.js"></script>
    <script src="../js/applications.js"></script>
    <script src="../js/portal.js"></script>

</body>

</html>