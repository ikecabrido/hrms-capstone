<?php

require_once __DIR__ . '/../classes/Interview.php';

$interviewModel = new Interview();

try {
    $interviews = $interviewModel->getAllSchedules();
} catch (Exception $e) {
    $interviews = [];
    $error = $e->getMessage();
}

?>
<div class="module-header">
    <h1>📅 Interview Schedule Tracker</h1>
    <p>Monitor upcoming candidate evaluations and real-time status.</p>

</div>

<div class="module-content">

    <div class="table-filter-bar" style="margin-bottom:16px;">
        <button type="button" class="filter-pill active-all stage-filter" data-stage-filter="all">
            All
        </button>
        <button type="button" class="filter-pill stage-filter" data-stage-filter="1">
            Initial
        </button>
        <button type="button" class="filter-pill stage-filter" data-stage-filter="2">
            Technical
        </button>
        <button type="button" class="filter-pill stage-filter" data-stage-filter="3">
            Final
        </button>
        <button type="button" class="filter-pill stage-filter" data-stage-filter="completed">
            Completed
        </button>
    </div>

    <table class="admin-table schedule-table">
        <thead>
            <tr>
                <th width="80">App ID</th>
                <th>Applicant Name</th>
                <th class="date-column">Date</th>
                <th class="time-column">Time</th>
                <th class="completed-position-column">Position</th>
                <th class="completed-department-column">Department</th>
                <th class="interviewer-column">Interviewer</th>
                <th class="stage-column">Stage</th>
                <th>Status</th>
                <th>Result</th>
            </tr>
        </thead>
        <tbody>

            <?php if (!empty($interviews)): ?>

                <?php foreach ($interviews as $row): ?>

                    <?php
                    $res = $row['result'] ?? 'pending';
                    $stage = (int)($row['stage_order'] ?? 0);
                    $appId = $row['application_id'];
                    $interId = $row['id'];
                    $status = $row['computed_status'] ?? 'Scheduled';
                    $isCompleted = strtolower($status) === 'completed';
                    ?>

                    <tr data-interview-stage="<?= $stage ?>"
                        data-interview-status="<?= htmlspecialchars(strtolower($status), ENT_QUOTES, 'UTF-8') ?>"
                        data-read-only="<?= $isCompleted ? 'true' : 'false' ?>"
                        data-schedule-url="index.php?page=schedule-interview&id=<?= urlencode($appId) ?>"
                        class="schedule-row" tabindex="0">

                        <td>
                            <span class="badge"
                                style="background:#f1f5f9;color:#64748b;font-family:monospace;">
                                #<?= htmlspecialchars($appId) ?>
                            </span>
                        </td>

                        <td style="font-weight:600;color:#1e293b;">
                            <?= htmlspecialchars($row['full_name'] ?? 'Unknown Applicant') ?>
                        </td>

                        <td class="date-column">
                            <i class="far fa-calendar-alt" style="color:#94a3b8;width:15px;"></i>
                            <?= !empty($row['interview_date'])
                                ? date("M d, Y", strtotime($row['interview_date']))
                                : 'N/A'
                            ?>
                        </td>

                        <td class="time-column">
                            <i class="far fa-clock" style="color:#94a3b8;width:15px;"></i>
                            <?= !empty($row['interview_time'])
                                ? date("h:i A", strtotime($row['interview_time']))
                                : 'N/A'
                            ?>
                        </td>

                        <td class="completed-position-column">
                            <?= htmlspecialchars($row['position'] ?? 'N/A') ?>
                        </td>

                        <td class="completed-department-column">
                            <?= htmlspecialchars($row['department'] ?? 'N/A') ?>
                        </td>

                        <td class="interviewer-column">
                            <?= htmlspecialchars($row['interviewer'] ?? 'Not Assigned') ?>
                        </td>

                        <td class="stage-column">
                            <?php
                            if ($stage === 1) {
                                echo '<span class="badge stage-initial">Initial Interview</span>';
                            } elseif ($stage === 2) {
                                echo '<span class="badge stage-technical">Technical Interview</span>';
                            } elseif ($stage === 3) {
                                echo '<span class="badge stage-final">Final Interview</span>';
                            } else {
                                echo '<span class="badge">Unknown</span>';
                            }
                            ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($status) ?>
                        </td>

                        <td>
                            <?php if ($res === 'pending'): ?>

                                <span style="color:#64748b;">
                                    Pending
                                </span>

                            <?php elseif ($res === 'passed'): ?>

                                <b style="color:#10b981;">
                                    <i class="fas fa-check-circle"></i>
                                    Passed
                                </b>

                            <?php elseif ($res === 'failed'): ?>

                                <b style="color:#ef4444;">
                                    <i class="fas fa-times-circle"></i>
                                    Failed
                                </b>

                            <?php endif; ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8"
                        style="text-align:center;padding:50px;color:#94a3b8;">

                        <?php if (!empty($error)): ?>

                            <?= htmlspecialchars($error) ?>

                        <?php else: ?>

                            No schedules found.

                        <?php endif; ?>

                    </td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>
</div>
</div>

<!-- Simple Feedback Modal -->
<div id="feedbackModal" style="display:none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 16px; width: 400px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <h3 style="margin-top: 0; color: #1e293b;">Candidate Feedback</h3>
        <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">Provide a rating for the final evaluation.</p>

        <form id="feedbackForm">
            <input type="hidden" name="application_id" id="modal_app_id">
            <input type="hidden" name="interview_id" id="modal_inter_id">

            <!-- Star Rating -->
            <div class="star-rating" style="font-size: 32px; color: #cbd5e1; margin-bottom: 20px; cursor: pointer; display: flex; justify-content: center; gap: 8px;">
                <i class="fas fa-star" data-rating="1"></i>
                <i class="fas fa-star" data-rating="2"></i>
                <i class="fas fa-star" data-rating="3"></i>
                <i class="fas fa-star" data-rating="4"></i>
                <i class="fas fa-star" data-rating="5"></i>
                <input type="hidden" name="rating" id="rating_value" value="0">
            </div>

            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 8px;">EVALUATION SUMMARY</label>
            <textarea name="feedback" placeholder="What stood out about this candidate?" style="width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; height: 100px; font-family: inherit; resize: none; font-size: 14px;"></textarea>

            <div style="margin-top: 25px; display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="closeFeedbackModal()" style="background: #f1f5f9; border: none; color: #475569; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 500;">Cancel</button>
                <button type="submit" style="background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">Save Feedback</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div id="statusToast" style="display:none; position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 9999; animation: slideIn 0.3s ease-out;">
    <i class="fas fa-check-circle"></i> Rating submitted successfully!
</div>

<script>
    document.querySelectorAll('.schedule-row').forEach(function(row) {
        row.addEventListener('click', function(event) {
            if (event.target.closest('a, button, input, select, textarea')) {
                return;
            }

            if (row.dataset.readOnly === 'true') {
                return;
            }

            window.location.href = row.dataset.scheduleUrl;
        });

        row.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (row.dataset.readOnly === 'true') {
                    return;
                }
                window.location.href = row.dataset.scheduleUrl;
            }
        });
    });

    function openFeedbackModal(interviewId, appId) {
        document.getElementById('modal_inter_id').value = interviewId;
        document.getElementById('modal_app_id').value = appId;
        document.getElementById('feedbackModal').style.display = 'flex';

        document.getElementById('rating_value').value = 0;
        document.querySelectorAll('.star-rating i').forEach(s => s.style.color = '#cbd5e1');
    }

    function closeFeedbackModal() {
        document.getElementById('feedbackModal').style.display = 'none';
        document.getElementById('feedbackForm').reset();
    }

    // Handle Form Submission
    document.getElementById('feedbackForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');

        // Basic Validation
        if (document.getElementById('rating_value').value == "0") {
            alert("Please select a star rating.");
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerText = "Saving...";

        fetch('index.php?page=submit-feedback', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeFeedbackModal();
                    const toast = document.getElementById('statusToast');
                    toast.style.display = 'block';

                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert("Error: " + (data.message || "Could not save feedback."));
                    submitBtn.disabled = false;
                    submitBtn.innerText = "Save Feedback";
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("An error occurred. Check console.");
                submitBtn.disabled = false;
            });
    });

    // Star Rating Interactivity
    document.querySelectorAll('.star-rating i').forEach(star => {
        star.addEventListener('mouseover', function() {
            let rating = this.getAttribute('data-rating');
            highlightStars(rating);
        });

        star.addEventListener('click', function() {
            let rating = this.getAttribute('data-rating');
            document.getElementById('rating_value').value = rating;
            highlightStars(rating);
        });
    });

    function highlightStars(rating) {
        document.querySelectorAll('.star-rating i').forEach(s => {
            s.style.color = s.getAttribute('data-rating') <= rating ? '#f59e0b' : '#cbd5e1';
        });
    }

    // Reset stars if mouse leaves and no rating is set
    document.querySelector('.star-rating').addEventListener('mouseleave', function() {
        highlightStars(document.getElementById('rating_value').value);
    });

    document.getElementById('feedbackForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');

        // Validation
        if (document.getElementById('rating_value').value == "0") {
            alert("Please select a star rating.");
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerText = "Saving...";

        fetch('save_feedback.php', { // Call the PHP backend
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeFeedbackModal();

                    // Show toast
                    const toast = document.getElementById('statusToast');
                    toast.style.display = 'block';
                    setTimeout(() => {
                        toast.style.display = 'none';
                    }, 1500);

                    // If final stage, show Ready for Offer button
                    if (data.finalStage) {
                        const appId = document.getElementById('modal_app_id').value;
                        const actionCell = document.querySelector(`td[data-app-id='${appId}'] .action-buttons`);
                        if (actionCell) {
                            const readyBtn = document.createElement('button');
                            readyBtn.classList.add('btn-action');
                            readyBtn.style.background = '#6366f1';
                            readyBtn.style.color = 'white';
                            readyBtn.style.padding = '6px 12px';
                            readyBtn.style.borderRadius = '4px';
                            readyBtn.style.fontSize = '12px';
                            readyBtn.innerHTML = '<i class="fas fa-file-signature"></i> Ready for Offer';

                            readyBtn.addEventListener('click', function() {
                                Swal.fire({
                                    title: 'Confirm Ready for Offer',
                                    text: 'Are you sure you want to mark this candidate as Ready for Offer?',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#6366f1',
                                    cancelButtonColor: '#d33',
                                    confirmButtonText: 'Yes, mark ready!'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        const postData = new FormData();
                                        postData.append('application_id', appId);

                                        fetch('index.php?page=process-ready-offer', {
                                                method: 'POST',
                                                body: postData
                                            })
                                            .then(resp => resp.text())
                                            .then(() => location.reload())
                                            .catch(() => Swal.fire('Error', 'Could not mark as Ready for Offer.', 'error'));
                                    }
                                });
                            });

                            actionCell.innerHTML = '';
                            actionCell.appendChild(readyBtn);
                        }
                    }

                    submitBtn.disabled = false;
                    submitBtn.innerText = "Save Feedback";

                } else {
                    alert(data.message || "Could not save feedback.");
                    submitBtn.disabled = false;
                    submitBtn.innerText = "Save Feedback";
                }
            })
            .catch(err => {
                console.error(err);
                alert("An error occurred. Check console.");
                submitBtn.disabled = false;
                submitBtn.innerText = "Save Feedback";
            });
    });
</script>