<div id="calendarModal" class="lc-modal-backdrop" aria-hidden="true">
    <div class="lc-modal lc-calendar-modal" role="dialog" aria-modal="true" aria-label="Calendar">
        <div class="lc-modal-header">
            <div class="lc-modal-title">Calendar</div>
            <button class="icon-btn" id="calendarModalClose" type="button" aria-label="Close calendar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="lc-modal-body">
            <div id="calendarToolbar" class="cal-toolbar">
                <div class="cal-toolbar-group">
                    <button class="cal-toolbar-btn" id="calTodayBtn" type="button">Today</button>
                    <button class="cal-toolbar-btn" id="calPrevBtn" type="button">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="cal-toolbar-btn" id="calNextBtn" type="button">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <span id="calToolbarLabel" class="cal-toolbar-label"></span>
            </div>
            <div id="fullCalendar"></div>
        </div>
    </div>
</div>
