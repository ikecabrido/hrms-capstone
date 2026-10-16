<?php if (isset($_GET['msg'])): ?>
    <div id="toast" class="toast">
        <?= htmlspecialchars($_GET['msg']) ?>
    </div>
<?php endif; ?>

<div class="dashboard-content">
    <?php if (!empty($interview)): ?>
        <div class="status-card warning" style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 20px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: flex-start; gap: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <i class="fas fa-calendar-check" style="color: #d97706; font-size: 20px; margin-top: 3px;"></i>
            <div>
                <strong style="color: #92400e; display: block; margin-bottom: 5px;">Interview Already Scheduled</strong>
                <p style="margin: 0; font-size: 14px; color: #b45309; line-height: 1.5;">
                    <strong>Date:</strong> <?= date('F j, Y', strtotime($interview['interview_date'])) ?> at <?= date('g:i A', strtotime($interview['interview_time'])) ?><br>
                    <strong>Type:</strong> <?= htmlspecialchars($interview['interview_type']) ?> (<?= htmlspecialchars($interview['interview_mode']) ?>)<br>
                    <strong>Interviewer:</strong> <?= htmlspecialchars($interview['interviewer']) ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <a href="index.php?page=applications" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Applications
        </a>
        <div class="form-header">

            <h2>Schedule Interview</h2>
            <p class="date-text">Set the evaluation details. An automated invitation will be sent to the candidate.</p>
        </div>

        <form action="index.php?page=save-interview" method="POST" class="admin-form" data-skip>
            <input type="hidden" name="application_id" value="<?= htmlspecialchars($app['id']) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>Candidate Name</label>
                    <input type="text" value="<?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?>" disabled style="background-color: #f8fafc; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="text" value="<?= htmlspecialchars($app['email']) ?>" disabled style="background-color: #f8fafc; cursor: not-allowed;">
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 20px 0;">

            <div class="form-row">
                <div class="form-group">
                    <label><i class="far fa-calendar-alt"></i> Interview Date</label>
                    <input type="date" name="interview_date" required min="<?= date('Y-m-d') ?>">

                </div>
                <div class="form-group">
                    <label><i class="far fa-clock"></i> Interview Time</label>
                    <input type="time" name="interview_time" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Interview Stage</label>
                    <select name="interview_type" required>
                        <option value="<?= $nextType ?>" selected><?= $nextType ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Interview Mode</label>
                    <select name="interview_mode" id="mode" required onchange="toggleMeetingField()">
                        <option value="Online">Online / Remote</option>
                        <option value="Face to Face">Face to Face / In-Office</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label id="meetingLabel">Meeting Link</label>
                <div style="position: relative;">
                    <i id="modeIcon" class="fas fa-video" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                    <input type="text" name="meeting_link" id="meetingInput"
                        placeholder="Enter Zoom, Meet, or Teams link"
                        style="padding-left: 35px;" required>
                </div>
            </div>

            <div class="form-group">
                <label>Assigned Interviewer</label>
                <input type="text" name="interviewer" placeholder="e.g. John Doe (Hiring Manager)" required>
            </div>

            <div class="form-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9;">

                <a href="index.php?page=applications" class="btn-cancel">
                    <i class="fas fa-times"></i> Discard
                </a>

                <div class="action-wrapper">
                    <?php if (!$this->interviewModel->canProceed($app['id'])): ?>
                        <button type="button" class="btn-submit btn-locked" disabled title="Complete previous stages first">
                            <i class="fas fa-lock"></i> Previous Stage Not Passed
                        </button>
                    <?php elseif (!empty($interview)): ?>
                        <button type="button" class="btn-submit btn-scheduled" disabled>
                            <i class="fas fa-calendar-check"></i> Already Scheduled
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> Schedule Interview
                        </button>
                    <?php endif; ?>
                </div>


            </div>
        </form>
        <div class="timeline">
            <?php foreach ($interviews as $i): ?>
                <div class="timeline-card">
                    <strong><?= htmlspecialchars($i['interview_type']) ?></strong>
                    <span style="color: #64748b;">Stage <?= $i['stage_order'] ?></span><br>
                    <i class="far fa-clock"></i> <?= date('M j', strtotime($i['interview_date'])) ?><br>

                    <?php
                    $res = $i['result'] ?? 'pending';
                    $color = ($res === 'passed') ? '#10b981' : (($res === 'failed') ? '#ef4444' : '#f59e0b');
                    ?>
                    <div style="margin-top: 8px; font-weight: bold; color: <?= $color ?>;">
                        <i class="fas <?= ($res === 'passed') ? 'fa-check-circle' : (($res === 'failed') ? 'fa-times-circle' : 'fa-hourglass-half') ?>"></i>
                        <?= ucfirst($res) ?>
                    </div>

                    <?php if ($res === 'pending'): ?>
                        <div style="margin-top: 10px; display: flex; gap: 5px;">
                            <form method="POST" action="index.php?page=update-result" data-skip>
                                <input type="hidden" name="id" value="<?= $i['id'] ?>">
                                <input type="hidden" name="result" value="passed">
                                <button class="btn-pass">✅ Pass</button>
                            </form>

                            <form method="POST" action="index.php?page=update-result" data-skip>
                                <input type="hidden" name="id" value="<?= $i['id'] ?>">
                                <input type="hidden" name="result" value="failed">
                                <button class="btn-fail">❌ Fail</button>
                            </form>
                        </div>
                    <?php endif; ?>


                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<script>
    function toggleMeetingField() {
        const mode = document.getElementById("mode").value;
        const label = document.getElementById("meetingLabel");
        const input = document.getElementById("meetingInput");
        const icon = document.getElementById("modeIcon");


        if (mode === "Online") {
            label.innerText = "Meeting Link";
            input.placeholder = "Enter Zoom, Meet, or Teams link";
            icon.className = "fas fa-video";
        } else {
            label.innerText = "Office Location";
            input.placeholder = "Enter physical office address or Room #";
            icon.className = "fas fa-map-marker-alt";
        }
    }
    const toast = document.getElementById("toast");
    if (toast) {
        setTimeout(() => {
            toast.style.opacity = "0";
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }

    document.querySelector('input[name="interview_date"]').addEventListener('change', function() {
        const timeInput = document.querySelector('input[name="interview_time"]');
        const today = new Date().toISOString().split('T')[0];

        if (this.value === today) {
            const now = new Date();
            let hours = String(now.getHours()).padStart(2, '0');
            let minutes = String(now.getMinutes()).padStart(2, '0');
            timeInput.min = `${hours}:${minutes}`;
        } else {
            timeInput.removeAttribute('min');
        }
    });

    // Logic for Toast handling remains the same as your script...
</script>