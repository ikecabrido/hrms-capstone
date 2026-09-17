/**
 * Holiday Calendar Integration
 * Handles marking holidays on FullCalendar
 */

class HolidayCalendarManager {
    constructor(calendarEl, config = {}) {
        this.calendarEl = calendarEl;
        this.config = {
            apiUrl: 'app/api/holiday_api.php',
            highlightColor: '#e74c3c',
            onHolidayClick: config.onHolidayClick || null,
            ...config
        };

        this.holidays = [];
        this.initialized = false;
    }

    /**
     * Initialize and fetch holidays
     */
    async init() {
        try {
            await this.fetchHolidays();
            this.initialized = true;
            return true;
        } catch (error) {
            console.error('Failed to initialize holiday calendar:', error);
            return false;
        }
    }

    /**
     * Fetch holidays from API
     */
    async fetchHolidays() {
        try {
            const currentYear = new Date().getFullYear();
            const response = await fetch(
                `${this.config.apiUrl}?action=get_all&year=${currentYear}`
            );

            if (!response.ok) {
                throw new Error(`API returned ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.holidays = data.data.holidays || [];
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error fetching holidays:', error);
            throw error;
        }
    }

    /**
     * Get FullCalendar event objects for holidays
     */
    getCalendarEvents() {
        return this.holidays.map(holiday => ({
            id: `holiday-${holiday.id}`,
            title: holiday.name,
            start: holiday.holiday_date,
            end: holiday.holiday_date,
            extendedProps: {
                category: holiday.category,
                isRecurring: holiday.is_recurring === 1,
                description: holiday.description,
                isHoliday: true
            },
            backgroundColor: this.getCategoryColor(holiday.category),
            borderColor: this.getCategoryColor(holiday.category),
            textColor: '#fff',
            className: `ta-holiday ta-holiday-${holiday.category}`,
            display: 'block'
        }));
    }

    /**
     * Get color based on category
     */
    getCategoryColor(category) {
        const colors = {
            'national': '#e74c3c',   // Red
            'regional': '#f39c12',   // Orange
            'optional': '#3498db',   // Blue
            'special': '#9b59b6'     // Purple
        };

        return colors[category] || '#95a5a6'; // Gray default
    }

    /**
     * Get category badge class
     */
    getCategoryBadgeClass(category) {
        const classes = {
            'national': 'danger',
            'regional': 'warning',
            'optional': 'info',
            'special': 'secondary'
        };

        return classes[category] || 'secondary';
    }

    /**
     * Create popup for holiday
     */
    createHolidayPopup(holiday) {
        const date = new Date(holiday.holiday_date);
        const daysLeft = this.calculateDaysUntil(holiday.holiday_date);
        
        return `
            <div class="ta-holiday-popup">
                <div class="holiday-popup-header" style="background-color: ${this.getCategoryColor(holiday.category)}; color: white;">
                    <h5 class="mb-0">${this.escapeHtml(holiday.name)}</h5>
                </div>
                <div class="holiday-popup-body p-2">
                    <div class="mb-2">
                        <span class="badge badge-${this.getCategoryBadgeClass(holiday.category)}">
                            ${this.escapeHtml(holiday.category.toUpperCase())}
                        </span>
                        ${holiday.is_recurring ? '<span class="badge badge-info ml-1"><i class="fas fa-sync-alt"></i> Recurring</span>' : ''}
                    </div>
                    <p class="mb-1">
                        <strong>Date:</strong> ${date.toLocaleDateString('en-US', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        })}
                    </p>
                    ${daysLeft >= 0 ? `<p class="mb-0"><strong>Days Left:</strong> ${daysLeft} day(s)</p>` : ''}
                    ${holiday.description ? `<p class="mb-0 mt-2"><em>${this.escapeHtml(holiday.description)}</em></p>` : ''}
                </div>
            </div>
        `;
    }

    /**
     * Calculate days until date
     */
    calculateDaysUntil(targetDate) {
        const today = new Date();
        const target = new Date(targetDate);

        today.setHours(0, 0, 0, 0);
        target.setHours(0, 0, 0, 0);

        const diff = target - today;
        return Math.floor(diff / (1000 * 60 * 60 * 24));
    }

    /**
     * Check if date is a holiday
     */
    isHoliday(date) {
        const dateStr = this.formatDate(date);
        return this.holidays.some(holiday => 
            this.formatDate(holiday.holiday_date) === dateStr
        );
    }

    /**
     * Get holiday for a date
     */
    getHolidayByDate(date) {
        const dateStr = this.formatDate(date);
        return this.holidays.find(holiday => 
            this.formatDate(holiday.holiday_date) === dateStr
        );
    }

    /**
     * Format date to YYYY-MM-DD
     */
    formatDate(date) {
        const d = new Date(date);
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${d.getFullYear()}-${month}-${day}`;
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Refresh holidays
     */
    async refresh() {
        await this.fetchHolidays();
        return this.getCalendarEvents();
    }

    /**
     * Add event listener for holiday clicks
     */
    onHolidayClicked(callback) {
        this.config.onHolidayClick = callback;
    }

    /**
     * Sync holidays from API
     */
    async syncHolidays() {
        try {
            const response = await fetch(`${this.config.apiUrl}?action=sync`, {
                method: 'POST'
            });

            const data = await response.json();

            if (data.success) {
                await this.refresh();
                return {
                    success: true,
                    synced: data.data.synced_count
                };
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error syncing holidays:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Get holidays for a specific month
     */
    getHolidaysForMonth(year, month) {
        return this.holidays.filter(holiday => {
            const date = new Date(holiday.holiday_date);
            return date.getFullYear() === year && 
                   (date.getMonth() + 1) === month;
        });
    }

    /**
     * Get statistics
     */
    getStatistics() {
        const stats = {
            total: this.holidays.length,
            national: 0,
            regional: 0,
            optional: 0,
            special: 0,
            recurring: 0
        };

        this.holidays.forEach(holiday => {
            stats[holiday.category]++;
            if (holiday.is_recurring === 1) {
                stats.recurring++;
            }
        });

        return stats;
    }
}

// Integrate with FullCalendar
function integrateHolidaysWithCalendar(calendar) {
    const holidayManager = new HolidayCalendarManager(calendar.el, {
        onHolidayClick: (holiday) => {
            console.log('Holiday clicked:', holiday);
        }
    });

    // Initialize
    holidayManager.init().then(() => {
        // Add holiday events to calendar
        const events = holidayManager.getCalendarEvents();
        events.forEach(event => {
            calendar.addEvent(event);
        });

        // Add click handler
        calendar.on('eventClick', (info) => {
            if (info.event.extendedProps.isHoliday) {
                const holiday = holidayManager.getHolidayByDate(info.event.start);
                if (holiday && holidayManager.config.onHolidayClick) {
                    holidayManager.config.onHolidayClick(holiday, info.event);
                }

                // Show popup
                const popup = holidayManager.createHolidayPopup(holiday);
                showHolidayPopup(popup, info.jsEvent);
            }
        });

        // Add CSS for styling
        addHolidayStyles();
    });

    return holidayManager;
}

/**
 * Show holiday popup on event click
 */
function showHolidayPopup(html, event) {
    // Remove existing popup
    const existing = document.querySelector('.ta-holiday-popup-container');
    if (existing) {
        existing.remove();
    }

    const container = document.createElement('div');
    container.className = 'ta-holiday-popup-container';
    container.innerHTML = html;

    // Position near click
    container.style.position = 'fixed';
    container.style.left = event.clientX + 'px';
    container.style.top = event.clientY + 'px';
    container.style.zIndex = '1000';
    container.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
    container.style.borderRadius = '4px';
    container.style.backgroundColor = 'white';
    container.style.maxWidth = '300px';

    document.body.appendChild(container);

    // Close on click outside
    setTimeout(() => {
        document.addEventListener('click', function closePopup(e) {
            if (!container.contains(e.target) && e.target !== event.target) {
                container.remove();
                document.removeEventListener('click', closePopup);
            }
        });
    }, 100);
}

/**
 * Add CSS styles for holidays
 */
function addHolidayStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .ta-holiday {
            font-weight: 600 !important;
            cursor: pointer !important;
        }

        .ta-holiday:hover {
            opacity: 0.8;
        }

        .ta-holiday-national {
            background-color: #e74c3c !important;
        }

        .ta-holiday-regional {
            background-color: #f39c12 !important;
        }

        .ta-holiday-optional {
            background-color: #3498db !important;
        }

        .ta-holiday-special {
            background-color: #9b59b6 !important;
        }

        .ta-holiday-popup-container {
            border: 2px solid #ddd;
        }

        .holiday-popup-header {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }

        .holiday-popup-body {
            font-size: 0.9rem;
        }

        .holiday-popup-body p {
            margin-bottom: 8px;
        }

        .holiday-popup-body .badge {
            font-size: 0.8rem;
        }

        /* Calendar styling */
        .fc-daygrid-day.fc-day-other {
            background-color: #fafafa;
        }

        .fc-daygrid-day.ta-holiday-day {
            background-color: #fff5f5;
        }

        .fc-daygrid-day.ta-holiday-day .fc-daygrid-day-number {
            color: #e74c3c;
            font-weight: bold;
        }
    `;

    document.head.appendChild(style);
}

// Export for use
if (typeof window !== 'undefined') {
    window.HolidayCalendarManager = HolidayCalendarManager;
    window.integrateHolidaysWithCalendar = integrateHolidaysWithCalendar;
    window.showHolidayPopup = showHolidayPopup;
    window.addHolidayStyles = addHolidayStyles;
}
