<div>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> School Management System. All rights reserved.</p>
    </footer>
</div>
    <?php
    $clinicAlertJsFile = __DIR__ . '/../js/components/clinic-stock-alert.js';
    $clinicAlertJsVersion = @file_exists($clinicAlertJsFile) ? @filemtime($clinicAlertJsFile) : '1';
    $clinicMainJsFile = __DIR__ . '/../js/script.js';
    $clinicMainJsVersion = @file_exists($clinicMainJsFile) ? @filemtime($clinicMainJsFile) : '1';
    ?>
    <script src="js/components/clinic-stock-alert.js?v=<?= $clinicAlertJsVersion ?>" defer></script>
    <script type="module" src="js/script.js?v=<?= $clinicMainJsVersion ?>"></script>
</body>
</html>