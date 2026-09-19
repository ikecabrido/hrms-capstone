<?php
// Prefer the public view if present; otherwise render a minimal leave request form with file upload support
$publicPath = __DIR__ . '/../../../public/leave_request.php';
if (file_exists($publicPath)) {
	require_once $publicPath;
	return;
}
?>
<div class="leave-request-form">
	<form id="leaveRequestForm" action="<?php echo htmlspecialchars('/hrms/hrms-capstone/modules/time/app/api/leave/submit_leave.php', ENT_QUOTES, 'UTF-8'); ?>" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($_SESSION['user']['id'] ?? ''); ?>">
		<div>
			<label>Leave Type</label>
			<select name="leave_type_id" id="leaveTypeSelect">
				<?php
				$leaveModel = new \Leave();
				$types = $leaveModel->getLeaveTypes();
				foreach ($types as $t) {
					echo '<option value="' . intval($t['leave_type_id']) . '" data-accrual="' . htmlspecialchars($t['accrual_frequency'] ?? '') . '">' . htmlspecialchars($t['leave_type_name']) . '</option>';
				}
				?>
			</select>
		</div>
		<div>
			<label>Start Date</label>
			<input type="date" name="start_date" required>
		</div>
		<div>
			<label>End Date</label>
			<input type="date" name="end_date" required>
		</div>
		<div>
			<label>Reason</label>
			<textarea name="reason" required></textarea>
		</div>
		<div>
			<label>Supporting Document <span id="supportingDocNote" style="display:none;color:#c0392b;margin-left:8px;">A supporting document is required for this leave type</span></label>
			<input type="file" name="supporting_document" id="supportingDocumentInput" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
		</div>
		<div>
			<button type="submit">Submit Leave Request</button>
		</div>
	</form>
</div>
<script>
// Toggle supporting document note when certain leave types are selected
(function(){
	var select = document.getElementById('leaveTypeSelect');
	var note = document.getElementById('supportingDocNote');
	function updateNote() {
		try {
			var opt = select.options[select.selectedIndex];
			var accrual = (opt.dataset.accrual || '').toLowerCase();
			// Leave types that commonly require documents: sick, bereavement (use accrual as proxy here if needed)
			var requires = (opt.textContent || opt.innerText || '').toLowerCase();
			if (requires.indexOf('sick') !== -1 || requires.indexOf('bereave') !== -1) {
				note.style.display = 'inline';
			} else {
				note.style.display = 'none';
			}
		} catch (e) { /* ignore */ }
	}
	if (select) {
		select.addEventListener('change', updateNote);
		// initial state
		updateNote();
	}
})();
</script>
