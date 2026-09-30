
<?php

require_once __DIR__ . '/../helpers/format.php';
require_once __DIR__ . '/../classes/Interview.php';

$interviewModel = new Interview();

try {
    $tracking = $interviewModel->getTrackingData();
    $error = null;
} catch (Throwable $e) {
    $tracking = [];
    $error = $e->getMessage();
}

?>

<div class="module-header">
    <h1>Interview Tracking Dashboard</h1>
    <p>Monitor candidate progression and finalize hiring decisions.</p>

    <div>
        <a
            href="index.php?page=offer"
            class="btn-create"
            style="background:#6366f1; text-decoration:none;"
        >
            <i class="fas fa-file-signature"></i>
            Offer
        </a>
    </div>
</div>

<div class="module-content">

    <div class="dashboard-content">

        <div class="table-container">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>Applicant Name</th>
                        <th>Initial Stage</th>
                        <th>Technical Stage</th>
                        <th>Final Stage</th>
                        <th>Overall Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($tracking)): ?>

                        <?php foreach ($tracking as $row): ?>

                            <?php
                            $initial = strtolower(
                                trim($row['initial_result'] ?? 'pending')
                            );

                            $technical = strtolower(
                                trim($row['technical_result'] ?? 'pending')
                            );

                            $final = strtolower(
                                trim($row['final_result'] ?? 'pending')
                            );

                            /*
                             * All three interviews must be passed
                             */
                          $isPassed = (
    ($initial ?? '') === 'passed' &&
    ($technical ?? '') === 'passed' &&
    ($final ?? '') === 'passed'
);

                            /*
                             * Candidate failed if ANY interview failed
                             */
                            $isFailed = (
                                $initial === 'failed' ||
                                $technical === 'failed' ||
                                $final === 'failed'
                            );
                            ?>

                            <tr
                                class="interview-result-row"
                                tabindex="0"
                                data-result-url="index.php?page=interview-result&id=<?= urlencode($row['application_id']) ?>"
                            >

                                <!-- Applicant Name -->
                                <td style="font-weight:600; color:#1e293b;">
                                    <?= htmlspecialchars(
                                        trim(
                                            ($row['first_name'] ?? '') . ' ' .
                                            ($row['last_name'] ?? '')
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <!-- Initial -->
                                <td>
                                    <?= formatResult($row['initial_result'] ?? null) ?>
                                </td>

                                <!-- Technical -->
                                <td>
                                    <?= formatResult($row['technical_result'] ?? null) ?>
                                </td>

                                <!-- Final -->
                                <td>
                                    <?= formatResult($row['final_result'] ?? null) ?>
                                </td>

                                <!-- Overall Status -->
                                <td>

                                    <?php if ($isPassed): ?>

                                        <div
                                            style="
                                                display:flex;
                                                align-items:center;
                                                gap:10px;
                                                flex-wrap:wrap;
                                            "
                                        >

                                            <span
                                                class="badge"
                                                style="
                                                    background:#dcfce7;
                                                    color:#166534;
                                                "
                                            >
                                                <i class="fas fa-check-double"></i>
                                                All Interviews Passed
                                            </span>

                                            <form
                                                method="POST"
                                                action="index.php?page=mark-ready-for-offer"
                                                style="margin:0;"
                                                onclick="event.stopPropagation();"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="application_id"
                                                    value="<?= htmlspecialchars(
                                                        $row['application_id'] ?? '',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                                >

                            

                                        </div>

                                    <?php elseif ($isFailed): ?>

                                        <span
                                            class="badge"
                                            style="
                                                background:#fee2e2;
                                                color:#991b1b;
                                            "
                                        >
                                            <i class="fas fa-user-slash"></i>
                                            Rejected
                                        </span>

                                    <?php elseif (
                                        $initial === 'passed' &&
                                        $technical === 'pending'
                                    ): ?>

                                        <span
                                            class="badge"
                                            style="
                                                background:#e0f2fe;
                                                color:#0369a1;
                                            "
                                        >
                                            <i class="fas fa-forward"></i>
                                            Ready for Technical
                                        </span>

                                    <?php elseif (
                                        $technical === 'passed' &&
                                        $final === 'pending'
                                    ): ?>

                                        <span
                                            class="badge"
                                            style="
                                                background:#f3e8ff;
                                                color:#6b21a8;
                                            "
                                        >
                                            <i class="fas fa-forward"></i>
                                            Ready for Final
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge"
                                            style="
                                                background:#fef9c3;
                                                color:#854d0e;
                                            "
                                        >
                                            <i class="fas fa-spinner fa-spin"></i>
                                            In Progress
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td
                                colspan="5"
                                style="
                                    text-align:center;
                                    padding:40px;
                                    color:#64748b;
                                "
                            >

                                <?php if (!empty($error)): ?>

                                    <?= htmlspecialchars(
                                        $error,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                <?php else: ?>

                                    No interview tracking records found.

                                <?php endif; ?>

                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="module" src="/js/main.js"></script>

