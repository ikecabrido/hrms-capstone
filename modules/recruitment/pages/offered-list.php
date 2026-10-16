<?php

require_once __DIR__ . '/../classes/Offer.php';

$offerModel = new Offer();

try {
    $offers = $offerModel->getAll();
    $error = null;
} catch (Throwable $e) {
    $offers = [];
    $error = $e->getMessage();
}
?>
<div class="module-header">

    <h1 style="margin:0; color:#1e293b;">Offered Applicants</h1>
    <p class="date-text">Monitor candidate progression and finalize hiring decisions.</p>
    <a href="index.php?page=offer" class="btn-create">
        <i class="fas fa-plus"></i> Create New Offer
    </a>
</div>

<div class="module-content">

    <?php if (!empty($_SESSION['success'])): ?>
        <div id="successPopup" class="alert-success">
            <i class="fas fa-check-circle"></i> <?= $_SESSION['success']; ?>
        </div>
        <script>
            setTimeout(() => {
                const popup = document.getElementById('successPopup');
                if (popup) {
                    popup.style.opacity = "0";
                    setTimeout(() => popup.remove(), 500);
                }
            }, 2500);
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Candidate</th>
                    <th>Position Details</th>
                    <th>Proposed Salary</th>
                    <th>Current Status</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($offers)): ?>
                    <?php foreach ($offers as $offer): ?>
                        <tr
                            class="candidate-row"
                            data-offer='<?= htmlspecialchars(json_encode($offer), ENT_QUOTES, 'UTF-8') ?>'
                            style="cursor:pointer;">

                            <td>
                                <span class="badge">
                                    #<?= htmlspecialchars($offer['application_id']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="candidate-name">
                                    <?= htmlspecialchars($offer['first_name'] . ' ' . $offer['last_name']) ?>
                                </span>
                            </td>

                            <td>
                                <div style="font-size: 13px; color: #475569;">
                                    <span style="font-weight: 600;">
                                        <?= htmlspecialchars($offer['position']) ?>
                                    </span>
                                    <br>

                                    <small style="opacity: 0.7;">
                                        Base: <?= htmlspecialchars($offer['base_job']) ?>
                                    </small>
                                </div>
                            </td>

                            <td>
                                <strong style="color: #1e293b;">
                                    ₱<?= number_format($offer['salary'], 2) ?>
                                </strong>
                            </td>

                            <td>
                                <?php
                                $statusStyle = "";
                                $icon = "";

                                switch ($offer['offer_status']) {

                                    case 'Accepted':
                                        $statusStyle = "background:#dcfce7; color:#166534;";
                                        $icon = "fa-check";
                                        break;

                                    case 'Rejected':
                                        $statusStyle = "background:#fee2e2; color:#991b1b;";
                                        $icon = "fa-times";
                                        break;

                                    case 'Negotiating':
                                        $statusStyle = "background:#ffedd5; color:#9a3412;";
                                        $icon = "fa-comments";
                                        break;

                                    default:
                                        $statusStyle = "background:#eff6ff; color:#1e40af;";
                                        $icon = "fa-paper-plane";
                                }
                                ?>

                                <span class="badge" style="<?= $statusStyle ?>">
                                    <i class="fas <?= $icon ?>"></i>
                                    <?= htmlspecialchars($offer['offer_status']) ?>
                                </span>
                            </td>

                            <td
                                style="text-align:center;"
                                onclick="event.stopPropagation();">

                                <div class="action-group">

                                    <?php if (
                                        $offer['offer_status'] === 'Accepted' ||
                                        $offer['offer_status'] === 'Rejected'
                                    ): ?>

                                        <span
                                            class="badge"
                                            style="background:#111827; color:white;">
                                            Closed
                                        </span>

                                    <?php elseif ($offer['offer_status'] === 'Negotiating'): ?>

                                        <form
                                            method="POST"
                                            action="index.php?page=update-salary"
                                            style="display:flex; gap:5px;">

                                            <input
                                                type="hidden"
                                                name="offer_id"
                                                value="<?= (int) $offer['offer_id'] ?>">

                                            <input
                                                type="number"
                                                step="0.01"
                                                name="new_salary"
                                                placeholder="New Salary"
                                                required
                                                class="input-mini">

                                            <button
                                                type="submit"
                                                class="btn-action"
                                                style="background:#6366f1; color:white;"
                                                title="Send Counter Offer">

                                                <i class="fas fa-paper-plane"></i>

                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <!-- ACCEPT -->
                                        <form
                                            method="POST"
                                            action="index.php?page=accept-offer"
                                            style="display:inline;">

                                            <input
                                                type="hidden"
                                                name="offer_id"
                                                value="<?= (int) $offer['offer_id'] ?>">

                                            <button
                                                type="submit"
                                                class="btn-action"
                                                style="background:#22c55e; color:white;"
                                                onclick="return confirm('Accept this offer?')"
                                                title="Accept Offer">

                                                <i class="fas fa-check"></i>

                                            </button>

                                        </form>

                                        <!-- REJECT -->
                                        <form
                                            method="POST"
                                            action="index.php?page=reject-offer"
                                            style="display:inline;">

                                            <input
                                                type="hidden"
                                                name="offer_id"
                                                value="<?= (int) $offer['offer_id'] ?>">

                                            <button
                                                type="submit"
                                                class="btn-action"
                                                style="background:#ef4444; color:white;"
                                                onclick="return confirm('Reject this offer?')"
                                                title="Reject Offer">

                                                <i class="fas fa-times"></i>

                                            </button>

                                        </form>

                                        <!-- NEGOTIATE -->
                                        <form
                                            method="POST"
                                            action="index.php?page=negotiate-offer"
                                            style="display:inline;">

                                            <input
                                                type="hidden"
                                                name="offer_id"
                                                value="<?= (int) $offer['offer_id'] ?>">

                                            <button
                                                type="submit"
                                                class="btn-action"
                                                style="background:#f59e0b; color:white;"
                                                title="Move to Negotiation">

                                                <i class="fas fa-comments"></i>

                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:30px;">No offers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Applicant Details Modal -->
        <div id="applicantDetailsModal" class="applicant-modal">

            <div class="applicant-modal-content">

                <div class="applicant-modal-header">
                    <div>
                        <h2 id="modalCandidateName">Applicant Details</h2>
                        <p id="modalApplicationId"></p>
                    </div>

                    <button type="button" id="closeApplicantModal" class="modal-close">
                        &times;
                    </button>
                </div>

                <div class="applicant-modal-body">

                    <div class="details-section">
                        <h3>
                            <i class="fas fa-user"></i>
                            Applicant Information
                        </h3>

                        <div class="details-grid">

                            <div class="detail-item">
                                <span class="detail-label">First Name</span>
                                <span id="modalFirstName" class="detail-value"></span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Last Name</span>
                                <span id="modalLastName" class="detail-value"></span>
                            </div>

                        </div>
                    </div>


                    <div class="details-section">

                        <h3>
                            <i class="fas fa-briefcase"></i>
                            Position Details
                        </h3>

                        <div class="details-grid">

                            <div class="detail-item">
                                <span class="detail-label">Position</span>
                                <span id="modalPosition" class="detail-value"></span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Base Job</span>
                                <span id="modalBaseJob" class="detail-value"></span>
                            </div>

                        </div>

                    </div>


                    <div class="details-section">

                        <h3>
                            <i class="fas fa-money-bill-wave"></i>
                            Offer Details
                        </h3>

                        <div class="details-grid">

                            <div class="detail-item">
                                <span class="detail-label">Proposed Salary</span>
                                <span id="modalSalary" class="detail-value salary-value"></span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Offer Status</span>
                                <span id="modalOfferStatus" class="detail-value"></span>
                            </div>

                        </div>

                    </div>
                    <!-- HIRE APPLICANT -->

                    <div class="action-group">

                        <form
                            method="POST"
                            action="index.php?page=hire-applicant"
                            id="hireApplicantForm"
                            style="display:inline;">

                            <!-- JavaScript will put the selected offer_id here -->
                            <input
                                type="hidden"
                                name="offer_id"
                                id="modalOfferId"
                                value="">

                            <button
                                type="submit"
                                id="hireApplicantButton"
                                class="hire-applicant"
                                style="background:#16a34a; color:white;"
                                onclick="return confirm(
                'Hire this applicant? This will move the applicant to the hired employees list.'
            )"
                                title="Hire Applicant">

                                <i class="fas fa-user-check"></i>
                                Hire

                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>
    </div>
</div>