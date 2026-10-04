/**
 * Mobile Responsive Utilities
 * Handles responsive features for the Time & Attendance System
 */

(function() {
    'use strict';

    // Don't prevent default on touchend - let click events fire normally
    // Buttons should work on both desktop and mobile without interference

    // Handle viewport meta tag
    function ensureViewportMeta() {
        let viewportMeta = document.querySelector('meta[name="viewport"]');
        if (!viewportMeta) {
            viewportMeta = document.createElement('meta');
            viewportMeta.name = 'viewport';
            viewportMeta.content = 'width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=5.0, user-scalable=yes';
            document.head.appendChild(viewportMeta);
        } else {
            viewportMeta.content = 'width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=5.0, user-scalable=yes';
        }
    }

    // Add touch feedback to clickable elements on mobile
    function addTouchFeedback() {
        if (!('ontouchstart' in window)) return;

        const clickableElements = document.querySelectorAll('button, a, [role="button"], .nav-item');
        
        clickableElements.forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            }, false);
            
            el.addEventListener('touchend', function() {
                this.style.opacity = '1';
            }, false);
            
            el.addEventListener('touchcancel', function() {
                this.style.opacity = '1';
            }, false);
        });
    }

    // Improve input field focus on mobile
    function improveMobileInputs() {
        const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="search"], textarea, select');
        
        inputs.forEach(input => {
            // Ensure minimum 44px height for accessibility
            if (input.offsetHeight < 44) {
                input.style.minHeight = '44px';
            }
            
            // Add padding for better tap targets
            const padding = window.getComputedStyle(input).padding;
            if (padding === '0px') {
                input.style.padding = '10px';
            }
        });
    }

    // Handle orientation change
    function handleOrientationChange() {
        // Force viewport recalculation on orientation change
        setTimeout(() => {
            window.scrollTo(0, 1);
        }, 100);
    }

    // Optimize table display for mobile
    function optimizeTables() {
        const tables = document.querySelectorAll('table:not([data-responsive="false"])');
        
        tables.forEach(table => {
            // Add data-label attributes to td elements for mobile display
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const headers = table.querySelectorAll('thead th');
                
                cells.forEach((cell, index) => {
                    if (headers[index] && !cell.hasAttribute('data-label')) {
                        cell.setAttribute('data-label', headers[index].textContent.trim());
                    }
                });
            });

            // Make table scrollable on mobile
            if (window.innerWidth <= 768) {
                if (!table.parentElement.classList.contains('table-responsive')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'table-responsive';
                    wrapper.style.overflowX = 'auto';
                    wrapper.style.webkitOverflowScrolling = 'touch';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            }
        });
    }

    // Fix iOS fixed positioning issues
    function fixIOSFixed() {
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        if (!isIOS) return;

        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);

        window.addEventListener('resize', () => {
            vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        });
    }

    // Initialize all mobile optimizations
    function init() {
        ensureViewportMeta();
        addTouchFeedback();
        improveMobileInputs();
        optimizeTables();
        fixIOSFixed();

        window.addEventListener('orientationchange', handleOrientationChange);
        window.addEventListener('resize', improveMobileInputs);
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Performance: Optimize animations on low-end devices
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.style.scrollBehavior = 'auto';
        document.querySelectorAll('*').forEach(el => {
            el.style.animation = 'none !important';
            el.style.transition = 'none !important';
        });
    }
})();
