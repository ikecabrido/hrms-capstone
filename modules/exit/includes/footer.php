<div>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Bestlink College of the Philippines. All rights reserved.</p>
    </footer>
</div>

<!-- Shared confirmation modal (used by showConfirmation() across exit-management pages) -->
<div class="modal fade exit-modal" id="confirmationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmationMessage">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmActionBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

    <script type="module" src="js/script.js"></script>
</body>
</html>