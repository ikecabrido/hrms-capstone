<div>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Human Resources Management System. All rights reserved.</p>
    </footer>
</div>
    <script>
        window.__TA_ROOT = window.__TA_ROOT || '/hrms/hrms-capstone/modules/time';
        window.__TA_API_ROOT = window.__TA_API_ROOT || (window.__TA_ROOT + '/app/api');
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script type="module" src="js/script.js"></script>
    <?php if (!empty($page_footer_extra)) { echo $page_footer_extra; } ?>
</body>
</html>