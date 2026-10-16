<?php

require_once __DIR__ . '/../../../database/db.php';

try {

    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        SELECT id, title
        FROM rao_jobs
        WHERE is_posted = 1
        ORDER BY title ASC
    ");

    $stmt->execute();

    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {

    $jobs = [];

    $error = $e->getMessage();
}
?>

<div class="application-card">

    <div class="form-header">
        <h2>Submit Your Application</h2>
        <p>Please fill out the details below to apply for this position.</p>
    </div>

    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error']); ?>
        </div>

        <?php unset($_SESSION['error']); ?>

    <?php endif; ?>

    <form
        action="/hrms-capstone-master/modules/recruitment/index.php?page=store-application"
        method="POST"
        enctype="multipart/form-data"
        class="application-form"
        data-skip>

        <!-- ========================= -->
        <!-- PERSONAL INFORMATION -->
        <!-- ========================= -->

        <div class="form-section">

            <h3 class="section-title">
                <i class="fas fa-user"></i>
                <span>Personal Information</span>
            </h3>

            <div class="form-grid">

                <div class="form-group">

                    <label for="first_name">
                        First Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        placeholder="John"
                        required>

                </div>


                <div class="form-group">

                    <label for="middle_name">
                        Middle Name
                    </label>

                    <input
                        type="text"
                        id="middle_name"
                        name="middle_name"
                        placeholder="Michael">

                </div>


                <div class="form-group">

                    <label for="last_name">
                        Last Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        placeholder="Doe"
                        required>

                </div>


                <div class="form-group">

                    <label for="email">
                        Email Address
                        <span class="required">*</span>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="john.doe@example.com"
                        required>

                </div>


                <div class="form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        placeholder="09123456789">

                </div>


                <div class="form-group">

                    <label for="age">
                        Age
                    </label>

                    <input
                        type="number"
                        id="age"
                        name="age"
                        min="0"
                        max="120"
                        placeholder="25">

                </div>


                <div class="form-group">

                    <label for="gender">
                        Gender
                    </label>

                    <select
                        id="gender"
                        name="gender">

                        <option value="">
                            Select Gender
                        </option>

                        <option value="Male">
                            Male
                        </option>

                        <option value="Female">
                            Female
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="birthplace">
                        Birthplace
                    </label>

                    <input
                        type="text"
                        id="birthplace"
                        name="birthplace"
                        placeholder="City, Province">

                </div>


                <div class="form-group">

                    <label for="birthdate">
                        Birthdate
                    </label>

                    <input
                        type="date"
                        id="birthdate"
                        name="birthdate">

                </div>


                <div class="form-group">

                    <label for="civil_status">
                        Civil Status
                    </label>

                    <select
                        id="civil_status"
                        name="civil_status">

                        <option value="">
                            Select Civil Status
                        </option>

                        <option value="Single">
                            Single
                        </option>

                        <option value="Married">
                            Married
                        </option>

                        <option value="Widowed">
                            Widowed
                        </option>

                        <option value="Separated">
                            Separated
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="citizenship">
                        Citizenship
                    </label>

                    <input
                        type="text"
                        id="citizenship"
                        name="citizenship"
                        placeholder="Filipino">

                </div>


                <div class="form-group">

                    <label for="religion">
                        Religion
                    </label>

                    <input
                        type="text"
                        id="religion"
                        name="religion"
                        placeholder="Religion">

                </div>


                <div class="form-group full-width">

                    <label for="address">
                        Current Address
                    </label>

                    <input
                        type="text"
                        id="address"
                        name="address"
                        placeholder="123 Main St, City, Philippines">

                </div>


                <div class="form-group full-width">

                    <label for="permanent_address">
                        Permanent Address
                    </label>

                    <input
                        type="text"
                        id="permanent_address"
                        name="permanent_address"
                        placeholder="Permanent address">

                </div>

            </div>


            <!-- ========================= -->
            <!-- APPLICATION DETAILS -->
            <!-- ========================= -->

            <div class="form-section">

                <h3 class="section-title">

                    <i class="fas fa-briefcase"></i>

                    <span>Application Details</span>

                </h3>


                <div class="form-grid">


                    <!-- JOB -->

                    <div class="form-group">

                        <label for="job_id">

                            Applying For Position

                            <span class="required">*</span>

                        </label>


                        <div class="select-wrapper">

                            <select
                                id="job_id"
                                name="job_id"
                                required>

                                <option value="">
                                    Select a Job Position
                                </option>


                                <?php if (!empty($jobs)): ?>

                                    <?php foreach ($jobs as $job): ?>

                                        <option
                                            value="<?= (int)$job['id']; ?>">

                                            <?= htmlspecialchars(
                                                $job['title']
                                            ); ?>

                                        </option>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <option value="" disabled>
                                        No Jobs Available
                                    </option>

                                <?php endif; ?>

                            </select>

                        </div>

                    </div>


                    <!-- SOURCE -->

                    <div class="form-group">

                        <label for="source">
                            Source
                        </label>


                        <div class="select-wrapper">

                            <select
                                id="source"
                                name="source">

                                <option value="Direct Apply">
                                    Direct Apply
                                </option>

                                <option value="Referral">
                                    Referral
                                </option>

                                <option value="Online Posting">
                                    Online Posting
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- RESUME -->

                    <div class="form-group full-width">

                        <label for="resume">

                            Resume / CV

                            <small>
                                (PDF format)
                            </small>

                        </label>


                        <input
                            type="file"
                            id="resume"
                            name="resume"
                            accept=".pdf,application/pdf"
                            class="file-input">

                    </div>

                </div>

            </div>


            <!-- ========================= -->
            <!-- SUBMIT -->
            <!-- ========================= -->

            <div class="form-actions">

                <button
                    type="submit"
                    class="btn-submit">

                    <i class="fas fa-paper-plane"></i>

                    <span>
                        Save Applicant
                    </span>

                </button>

            </div>

    </form>

</div>