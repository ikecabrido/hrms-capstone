<div>
    <footer>
        <p>
            <span>&copy; <?php echo date('Y'); ?> School Management System. All rights reserved.</span>
            <span class="footer-link">
                <span class="footer-sep" aria-hidden="true">&middot;</span>
                <a href="?page=public/privacy">Privacy Policy</a>
            </span>
            <span class="footer-link">
                <span class="footer-sep" aria-hidden="true">&middot;</span>
                <a href="?page=public/terms">Terms of Service</a>
            </span>
        </p>
    </footer>
</div>
    <script type="module" src="js/script.js?v=<?= (int) filemtime(dirname(__DIR__) . '/js/script.js') ?>"></script>
</body>
</html>