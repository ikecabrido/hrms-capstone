
        document.getElementById('scanForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const employeeId = document.getElementById('employee_id').value.trim();
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            const messageDiv = document.getElementById('message');

            if (!employeeId) {
                showMessage('Please enter your Employee ID or Number', 'error');
                return;
            }

            // Disable button and show loading
            submitBtn.disabled = true;
            loading.style.display = 'block';
            messageDiv.style.display = 'none';

            try {
                const formData = new FormData();
                formData.append('token', document.querySelector('input[name="token"]').value);
                formData.append('employee_id', employeeId);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                loading.style.display = 'none';

                if (data.success) {
                    showMessage('✓ ' + data.message + '\n\nWelcome, ' + data.employee + '!', 'success');
                    document.getElementById('employee_id').value = '';
                    
                    // Auto-refresh after 2 seconds for next scan
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('✗ ' + data.message, 'error');
                    submitBtn.disabled = false;
                }
            } catch (error) {
                loading.style.display = 'none';
                showMessage('An error occurred: ' + error.message, 'error');
                submitBtn.disabled = false;
            }
        });

        function showMessage(msg, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.className = 'message ' + type;
            messageDiv.textContent = msg;
            messageDiv.style.display = 'block';
        }
    


        function hidePreloader() {
            const preloader = document.querySelector('.preloader');
            if (preloader) {
                preloader.style.display = 'none';
                preloader.style.visibility = 'hidden';
            }
        }

        function showPreloader() {
            const preloader = document.querySelector('.preloader');
            if (preloader) {
                preloader.style.display = 'flex';
                preloader.style.visibility = 'visible';
            }
        }

        window.addEventListener('load', hidePreloader);

        document.addEventListener('DOMContentLoaded', function() {
            // Delay hiding preloader so animation is visible
            setTimeout(hidePreloader, 6000);
            const navLinks = document.querySelectorAll('a');

            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const href = this.getAttribute('href');
                    if (href && !href.includes('logout') && !href.startsWith('javascript') && !href.startsWith('#') && !href.startsWith('mailto:') && !href.startsWith('tel:')) {
                        showPreloader();
                    }
                });
            });
        });
    


    function hidePreloader() {
        const preloader = document.querySelector('.preloader');
        if (preloader) {
            preloader.style.display = 'none';
            preloader.style.visibility = 'hidden';
        }
    }

    function showPreloader() {
        const preloader = document.querySelector('.preloader');
        if (preloader) {
            preloader.style.display = 'flex';
            preloader.style.visibility = 'visible';
        }
    }

    window.addEventListener('load', hidePreloader);

    document.addEventListener('DOMContentLoaded', function() {
        // Delay hiding preloader so animation is visible
        setTimeout(hidePreloader, 6000);
        const navLinks = document.querySelectorAll('a');

        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                const href = this.getAttribute('href');
                if (href && !href.includes('logout') && !href.startsWith('javascript') && !href.startsWith('#') && !href.startsWith('mailto:') && !href.startsWith('tel:')) {
                    showPreloader();
                }
            });
        });
    });



        document.getElementById('attendanceForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const loading = document.getElementById('loading');
            const form = document.getElementById('attendanceForm');
            const messageDiv = document.getElementById('message');

            loading.style.display = 'block';
            form.style.display = 'none';
            messageDiv.innerHTML = '';

            try {
                const response = await fetch((window.__TA_CONFIG || {}).requestUri || window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    messageDiv.innerHTML = `<div class="message success">${data.message}</div>`;
                    setTimeout(() => {
                        window.location.href = 'qr_display_kiosk.php';
                    }, 2000);
                } else {
                    messageDiv.innerHTML = `<div class="message error">${data.message}</div>`;
                    loading.style.display = 'none';
                    form.style.display = 'block';
                }
            } catch (error) {
                messageDiv.innerHTML = `<div class="message error">Error: ${error.message}</div>`;
                loading.style.display = 'none';
                form.style.display = 'block';
            }
        });
    