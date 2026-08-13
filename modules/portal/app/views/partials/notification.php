<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'];
        unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'];
        unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['profile_incomplete'])): ?>

    <div class="modal fade" id="profileIncompleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-user-clock me-2"></i>
                        Employee Profile Required
                    </h5>
                </div>

                <div class="modal-body">
                    <?= htmlspecialchars($_SESSION['profile_incomplete']); ?>
                </div>

                <div class="modal-footer">
                    <small class="text-muted">
                        Click outside this window to close.
                    </small>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(
                document.getElementById('profileIncompleteModal')
            ).show();
        });
    </script>

    <?php unset($_SESSION['profile_incomplete']); ?>
<?php endif; ?>

<?php if (isset($_GET['timeout'])): ?>

    <div class="alert alert-warning">
        Your session expired due to 10 minutes of inactivity.
        Please log in again.
    </div>

<?php endif; ?>
