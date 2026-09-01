<div>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Human Resource Management System. All rights reserved.</p>
    </footer>
</div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
    <script type="module" src="js/script.js"></script>
    <script>
        // Save current page to cookie for persistence across page refreshes and tab changes
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            let currentPage = urlParams.get('page');
            
            // Get previous page from cookie
            let previousPage = null;
            const cookies = document.cookie.split(';');
            for (let cookie of cookies) {
                const [name, value] = cookie.trim().split('=');
                if (name === 'lastEngagementPage') {
                    previousPage = decodeURIComponent(value);
                    break;
                }
            }

            // If no page parameter but we have a previous page, use it
            if (!currentPage && previousPage) {
                currentPage = previousPage;
            }

            // If page changed (previous page exists and is different from current), close all modals
            if (previousPage && currentPage && currentPage !== previousPage) {
                // Close all open modals immediately
                document.querySelectorAll('.modal.show').forEach(modal => {
                    if (window.bootstrap?.Modal) {
                        const bsModal = window.bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                    } else if ($ && $.fn.modal) {
                        $(modal).modal('hide');
                    }
                });

                // Clear the modal state cookie
                const expiryDate = new Date();
                expiryDate.setTime(expiryDate.getTime() - 1000);
                document.cookie = `openModal=; expires=${expiryDate.toUTCString()}; path=/hrms-capstone/modules/engagement/`;
            }
            
            // Check if user just logged in (PHP will output this)
            const isFreshlyLoggedIn = <?php echo isset($_SESSION['freshly_logged_in']) && $_SESSION['freshly_logged_in'] ? 'true' : 'false'; ?>;
            if (isFreshlyLoggedIn) {
                try {
                    sessionStorage.setItem('freshly_logged_in', 'true');
                } catch (e) {
                    // Ignore storage errors
                }
            }
            
            if (currentPage) {
                // Set cookie to expire in 7 days
                const expiryDate = new Date();
                expiryDate.setTime(expiryDate.getTime() + (7 * 24 * 60 * 60 * 1000));
                document.cookie = `lastEngagementPage=${encodeURIComponent(currentPage)}; expires=${expiryDate.toUTCString()}; path=/hrms-capstone/modules/engagement/`;
            }

            // Add event listeners to all navigation links to save the page when clicked
            document.addEventListener('click', function(event) {
                const link = event.target.closest('a[data-page]');
                if (link) {
                    const page = link.getAttribute('data-page');
                    if (page) {
                        // Close all modals when navigating
                        document.querySelectorAll('.modal.show').forEach(modal => {
                            if (window.bootstrap?.Modal) {
                                const bsModal = window.bootstrap.Modal.getInstance(modal);
                                if (bsModal) bsModal.hide();
                            } else if ($ && $.fn.modal) {
                                $(modal).modal('hide');
                            }
                        });

                        // Clear modal state when navigating
                        const expiryDate = new Date();
                        expiryDate.setTime(expiryDate.getTime() - 1000);
                        document.cookie = `openModal=; expires=${expiryDate.toUTCString()}; path=/hrms-capstone/modules/engagement/`;
                        
                        // Save new page
                        const futureDate = new Date();
                        futureDate.setTime(futureDate.getTime() + (7 * 24 * 60 * 60 * 1000));
                        document.cookie = `lastEngagementPage=${encodeURIComponent(page)}; expires=${futureDate.toUTCString()}; path=/hrms-capstone/modules/engagement/`;
                    }
                }
            });

            // Save modal state when modals open or close
            document.addEventListener('shown.bs.modal', function(event) {
                const modal = event.target;
                const modalId = modal.id;
                const expiryDate = new Date();
                expiryDate.setTime(expiryDate.getTime() + (7 * 24 * 60 * 60 * 1000));
                document.cookie = `openModal=${encodeURIComponent(modalId)}; expires=${expiryDate.toUTCString()}; path=/hrms-capstone/modules/engagement/`;
            });

            document.addEventListener('hidden.bs.modal', function(event) {
                const expiryDate = new Date();
                expiryDate.setTime(expiryDate.getTime() - 1000);
                document.cookie = `openModal=; expires=${expiryDate.toUTCString()}; path=/hrms-capstone/modules/engagement/`;
            });

            // Restore modal state on page load (only if on same page)
            window.addEventListener('load', function() {
                const cookies = document.cookie.split(';');
                let openModalId = null;
                let savedPage = null;
                
                for (let cookie of cookies) {
                    const [name, value] = cookie.trim().split('=');
                    if (name === 'openModal' && value) {
                        openModalId = decodeURIComponent(value);
                    }
                    if (name === 'lastEngagementPage' && value) {
                        savedPage = decodeURIComponent(value);
                    }
                }

                // Get current page from URL
                const urlParams = new URLSearchParams(window.location.search);
                const pageFromUrl = urlParams.get('page') || null;
                const effectiveCurrentPage = pageFromUrl || savedPage;

                // Only restore modal if it exists on current page AND page hasn't changed
                if (openModalId && savedPage === effectiveCurrentPage) {
                    const modal = document.getElementById(openModalId);
                    if (modal) {
                        // Use Bootstrap's modal method to open the modal
                        setTimeout(() => {
                            if (window.bootstrap?.Modal) {
                                new window.bootstrap.Modal(modal).show();
                            } else if ($ && $.fn.modal) {
                                $(modal).modal('show');
                            }
                        }, 100);
                    } else {
                        // Modal doesn't exist on this page, clear the cookie
                        const expiryDate = new Date();
                        expiryDate.setTime(expiryDate.getTime() - 1000);
                        document.cookie = `openModal=; expires=${expiryDate.toUTCString()}; path=/hrms-capstone/modules/engagement/`;
                    }
                } else if (openModalId && savedPage !== effectiveCurrentPage) {
                    // Page changed, make sure modal is closed and cookie is cleared
                    const expiryDate = new Date();
                    expiryDate.setTime(expiryDate.getTime() - 1000);
                    document.cookie = `openModal=; expires=${expiryDate.toUTCString()}; path=/hrms-capstone/modules/engagement/`;
                }
            });
        })();
    </script>
</body>
</html>