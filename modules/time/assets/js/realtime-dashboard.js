/**
 * Real-time Dashboard Updates
 * Polls the API for recent events and updates dashboard in real-time
 */

class RealtimeDashboard {
    constructor(options = {}) {
        this.apiUrl = options.apiUrl || '../app/api/realtime_updates.php';
        this.pollInterval = options.pollInterval || 5000; // 5 seconds
        this.eventsLimit = options.eventsLimit || 20;
        this.eventsList = [];
        this.isPolling = false;
        this.lastUpdate = null;
        this.updateCount = 0;
        
        this.init();
    }
    
    init() {
        // Check if we're on a dashboard page
        if (this.isDashboardPage()) {
            this.setupEventListeners();
            this.startPolling();
        }
    }
    
    isDashboardPage() {
        return document.body.innerHTML.includes('dashboard') || 
               window.location.pathname.includes('dashboard');
    }
    
    setupEventListeners() {
        // Listen for page visibility changes to pause/resume polling
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.startPolling();
            }
        });
        
        // Setup refresh button if it exists
        const refreshBtn = document.getElementById('realtimeRefresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.fetchUpdates();
            });
        }
    }
    
    startPolling() {
        if (this.isPolling) return;
        
        this.isPolling = true;
        this.fetchUpdates();
        
        this.pollingTimer = setInterval(() => {
            this.fetchUpdates();
        }, this.pollInterval);
        
        console.log('[RealtimeDashboard] Polling started');
    }
    
    stopPolling() {
        if (!this.isPolling) return;
        
        this.isPolling = false;
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
        
        console.log('[RealtimeDashboard] Polling stopped');
    }
    
    async fetchUpdates() {
        try {
            const response = await fetch(`${this.apiUrl}?limit=${this.eventsLimit}`);
            
            if (!response.ok) {
                console.warn('[RealtimeDashboard] API returned status:', response.status);
                return;
            }
            
            const data = await response.json();
            
            if (data.success && data.events) {
                this.lastUpdate = new Date();
                this.updateCount++;
                this.handleEvents(data.events);
                this.updateDashboard(data);
            }
        } catch (error) {
            console.error('[RealtimeDashboard] Fetch error:', error);
        }
    }
    
    handleEvents(events) {
        if (!events || events.length === 0) return;
        
        // Store new events that we haven't seen before
        const newEvents = events.filter(event => {
            return !this.eventsList.some(e => 
                e.time === event.time && 
                e.user_name === event.user_name && 
                e.type === event.type
            );
        });
        
        if (newEvents.length > 0) {
            this.eventsList = [...newEvents, ...this.eventsList].slice(0, 50);
            this.displayEvents(newEvents);
            this.triggerUpdateNotification(newEvents);
        }
    }
    
    displayEvents(events) {
        const container = document.getElementById('realtimeEventsContainer');
        if (!container) return;
        
        const eventsHtml = events.map(event => {
            const icon = this.getEventIcon(event.type);
            const color = this.getEventColor(event.type);
            
            return `
                <div class="realtime-event ${color} animate-in">
                    <span class="event-icon">${icon}</span>
                    <div class="event-details">
                        <strong>${event.user_name}</strong>
                        <small>${event.employee_number}</small>
                    </div>
                    <div class="event-type">${this.formatEventType(event.type)}</div>
                    <div class="event-time">${event.time}</div>
                    <div class="event-indicator pulse"></div>
                </div>
            `;
        }).join('');
        
        // Prepend new events to the container
        if (container.innerHTML.includes('realtime-event')) {
            // Append to existing events
            const firstEvent = container.querySelector('.realtime-event');
            if (firstEvent) {
                firstEvent.insertAdjacentHTML('beforebegin', eventsHtml);
            } else {
                container.innerHTML = eventsHtml + container.innerHTML;
            }
        } else {
            container.innerHTML = eventsHtml;
        }
        
        // Keep only the last 10 events
        const eventElements = container.querySelectorAll('.realtime-event');
        if (eventElements.length > 10) {
            for (let i = 10; i < eventElements.length; i++) {
                eventElements[i].remove();
            }
        }
    }
    
    getEventIcon(type) {
        const icons = {
            'LOGIN': '👤',
            'TIME_IN': '🕐',
            'TIME_OUT': '🕒'
        };
        return icons[type] || '•';
    }
    
    getEventColor(type) {
        const colors = {
            'LOGIN': 'event-login',
            'TIME_IN': 'event-timein',
            'TIME_OUT': 'event-timeout'
        };
        return colors[type] || 'event-default';
    }
    
    formatEventType(type) {
        const labels = {
            'LOGIN': 'Logged In',
            'TIME_IN': 'Time In',
            'TIME_OUT': 'Time Out'
        };
        return labels[type] || type;
    }
    
    updateDashboard(data) {
        // Update event count
        const countElement = document.getElementById('realtimeEventCount');
        if (countElement) {
            countElement.textContent = data.total_events;
        }
        
        // Update last refresh time
        const lastRefreshElement = document.getElementById('realtimeLastRefresh');
        if (lastRefreshElement) {
            lastRefreshElement.textContent = new Date().toLocaleTimeString();
        }
        
        // Update counters based on event types
        const loginCount = data.events.filter(e => e.type === 'LOGIN').length;
        const timeInCount = data.events.filter(e => e.type === 'TIME_IN').length;
        const timeOutCount = data.events.filter(e => e.type === 'TIME_OUT').length;
        
        this.updateMetrics(loginCount, timeInCount, timeOutCount);
    }
    
    updateMetrics(logins, timeIns, timeOuts) {
        const metricsElement = document.getElementById('realtimeMetrics');
        if (metricsElement) {
            metricsElement.innerHTML = `
                <div class="metric-item">
                    <span class="metric-label">Recent Logins:</span>
                    <span class="metric-value">${logins}</span>
                </div>
                <div class="metric-item">
                    <span class="metric-label">Time Ins:</span>
                    <span class="metric-value">${timeIns}</span>
                </div>
                <div class="metric-item">
                    <span class="metric-label">Time Outs:</span>
                    <span class="metric-value">${timeOuts}</span>
                </div>
            `;
        }
    }
    
    triggerUpdateNotification(events) {
        if (events.length === 0) return;
        
        // Show notification for first event
        const event = events[0];
        const message = `${this.formatEventType(event.type)}: ${event.user_name}`;
        
        // Show browser notification if enabled
        if (Notification.permission === 'granted') {
            new Notification('Time & Attendance Update', {
                body: message,
                icon: '../assets/pics/bcpLogo.png'
            });
        }
        
        // Show toast notification in page
        this.showToast(message, event.type);
    }
    
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `realtime-toast toast-${type}`;
        toast.innerHTML = `
            <span>${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        const container = document.body;
        container.appendChild(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }
    
    // Public method to manually refresh
    refresh() {
        this.fetchUpdates();
    }
    
    // Public method to get current events
    getEvents() {
        return this.eventsList;
    }
    
    // Request notification permission
    static requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.realtimeDashboard = new RealtimeDashboard({
            pollInterval: 5000, // 5 seconds
            eventsLimit: 30
        });
        
        // Request notification permission
        RealtimeDashboard.requestNotificationPermission();
    });
} else {
    window.realtimeDashboard = new RealtimeDashboard({
        pollInterval: 5000,
        eventsLimit: 30
    });
    
    RealtimeDashboard.requestNotificationPermission();
}
