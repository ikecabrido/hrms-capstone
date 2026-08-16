<div class="modal fade" id="createMeetingModal" tabindex="-1" aria-labelledby="createMeetingModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-xl shadow-lg">

            <div class="modal-header border-b border-slate-200">
                <div>
                    <h5 class="modal-title text-base font-semibold text-slate-800" id="createMeetingModalLabel">
                        Schedule Meeting
                    </h5>
                    <p class="text-xs text-slate-400 mt-1 mb-0">
                        Create a new online meeting for an employee.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="index.php?url=online-meeting-store">

                <div class="modal-body p-4">

                    <input type="hidden" name="created_by" value="<?= htmlspecialchars($createdBy ?? '') ?>">

                    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employeeId ?? '') ?>">

                    <div class="mb-3">
                        <label for="meetingTitle" class="form-label text-sm fw-medium text-slate-700">
                            Meeting Title
                        </label>

                        <input type="text" name="title" id="meetingTitle" class="form-control"
                            placeholder="e.g. Midterm Planning" required>
                    </div>

                    <div class="mb-3">
                        <label for="scheduledAt" class="form-label text-sm fw-medium text-slate-700">
                            Schedule
                        </label>

                        <input type="datetime-local" name="scheduled_at" id="scheduledAt" class="form-control"
                            min="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>

                    <input type="hidden" name="status" value="scheduled">

                </div>

                <div class="modal-footer border-t border-slate-200">

                    <button type="button"
                        class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-medium hover:bg-slate-50 transition"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">
                        <i class="fas fa-calendar-plus"></i>
                        Create Meeting
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<script>
    const scheduledAt = document.getElementById('scheduledAt');

    function setMinSchedule() {
        const now = new Date();

        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        scheduledAt.min = `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    setMinSchedule();
    setInterval(setMinSchedule, 60000);
</script>