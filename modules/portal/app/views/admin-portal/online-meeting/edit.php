<div class="modal fade" id="updateMeetingStatusModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-sm">

        <form method="POST" action="index.php?url=online-meeting-update-status">

            <div class="modal-content border-0 shadow-sm">

                <div class="modal-header">

                    <div>
                        <h5 class="modal-title fw-semibold text-slate-800">
                            Update Meeting
                        </h5>

                        <p id="updateMeetingTitle"
                            class="text-xs text-slate-400 mt-1 mb-0">
                            --
                        </p>
                    </div>

                    <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body p-4">

                    <input type="hidden"
                        name="meetings_id"
                        id="updateMeetingId">

                    <div class="mb-2">
                        <label for="updateMeetingStatus"
                            class="form-label text-sm fw-medium text-slate-700">
                            Meeting Status
                        </label>

                        <select name="status"
                            id="updateMeetingStatus"
                            class="form-select"
                            required>

                            <option value="scheduled">
                                Scheduled
                            </option>

                            <option value="completed">
                                Completed
                            </option>

                            <option value="cancelled">
                                Cancelled
                            </option>

                        </select>
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                        class="btn btn-primary">

                        <i class="fas fa-save me-1"></i>
                        Update Status

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>