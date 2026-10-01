
        // Update current time every second
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString();
            const dateStr = now.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
            document.getElementById('current-time').textContent = timeStr;
            document.getElementById('current-date').textContent = dateStr;
        }

        // Countdown timer
        let countdown = 30;
        function updateCountdown() {
            countdown--;
            document.getElementById('countdown').textContent = countdown;
            
            if (countdown <= 0) {
                // Refresh the page to get a new QR code
                location.reload();
            }
        }

        // Initialize
        updateTime();
        setInterval(updateTime, 1000);
        setInterval(updateCountdown, 1000);

        // Optional: Add fullscreen support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'f' || e.key === 'F') {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(err => {
                        console.log('Fullscreen request failed:', err);
                    });
                } else {
                    document.exitFullscreen();
                }
            }
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
    