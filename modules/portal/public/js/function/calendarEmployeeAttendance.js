const attendanceDate = document.getElementById('attendanceDate');
const attendanceSearch = document.getElementById('attendanceSearch');
const attendanceStatus = document.getElementById('attendanceStatus');
const showAllButton = document.getElementById('showAllAttendance');

const calendarDays = document.querySelectorAll('.attendance-day');

const selectedDaySummary = document.getElementById('selectedDaySummary');
const selectedDayTitle = document.getElementById('selectedDayTitle');
const selectedDayCount = document.getElementById('selectedDayCount');
const selectedEmployeeNames = document.getElementById('selectedEmployeeNames');

calendarDays.forEach(day => {

    day.addEventListener('click', function () {

        const selectedDate = this.dataset.date;

        if (attendanceDate) {
            attendanceDate.value = selectedDate;
        }

        filterAttendance(selectedDate);

        calendarDays.forEach(item => {
            item.classList.remove(
                'ring-2',
                'ring-blue-500',
                'ring-offset-1'
            );
        });

        this.classList.add(
            'ring-2',
            'ring-blue-500',
            'ring-offset-1'
        );

        showSelectedDay(this);
    });

});

attendanceDate?.addEventListener('change', function () {
    filterAttendance(this.value);

    const selected = document.querySelector(
        `.attendance-day[data-date="${this.value}"]`
    );

    calendarDays.forEach(item => {
        item.classList.remove(
            'ring-2',
            'ring-blue-500',
            'ring-offset-1'
        );
    });

    if (selected) {
        selected.classList.add(
            'ring-2',
            'ring-blue-500',
            'ring-offset-1'
        );

        showSelectedDay(selected);
    }
});

attendanceSearch?.addEventListener('input', function () {
    filterAttendance(attendanceDate?.value || '');
});

attendanceStatus?.addEventListener('change', function () {
    filterAttendance(attendanceDate?.value || '');
});

showAllButton?.addEventListener('click', function () {

    if (attendanceDate) {
        attendanceDate.value = '';
    }

    attendanceSearch.value = '';
    attendanceStatus.value = '';

    filterAttendance('');

    calendarDays.forEach(item => {
        item.classList.remove(
            'ring-2',
            'ring-blue-500',
            'ring-offset-1'
        );
    });

    selectedDaySummary.classList.add('hidden');

    this.classList.add('hidden');
});


function filterAttendance(date = '') {

    const search = attendanceSearch?.value
        .toLowerCase()
        .trim() || '';

    const status = attendanceStatus?.value || '';

    let visibleRows = 0;

    document.querySelectorAll('.attendance-row').forEach(row => {

        const rowDate = row.dataset.date || '';
        const name = row.dataset.name || '';
        const id = row.dataset.id || '';
        const rowStatus = row.dataset.status || '';

        const matchesDate =
            !date || rowDate === date;

        const matchesSearch =
            !search ||
            name.includes(search) ||
            id.includes(search);

        const matchesStatus =
            !status ||
            rowStatus === status;

        const visible =
            matchesDate &&
            matchesSearch &&
            matchesStatus;

        row.style.display = visible ? '' : 'none';

        if (visible) {
            visibleRows++;
        }

    });

    if (showAllButton) {
        showAllButton.classList.toggle(
            'hidden',
            !date
        );
    }
}


function showSelectedDay(dayElement) {

    const date = dayElement.dataset.date;
    const count = parseInt(dayElement.dataset.count || 0);

    const dateObject = new Date(date + 'T00:00:00');

    const formattedDate = dateObject.toLocaleDateString(
        'en-US',
        {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        }
    );

    selectedDayTitle.textContent = formattedDate;

    selectedDayCount.textContent =
        `${count} ${count === 1 ? 'employee' : 'employees'} present`;

    selectedEmployeeNames.innerHTML = '';

    const rows = document.querySelectorAll(
        `.attendance-row[data-date="${date}"]`
    );

    const employees = [];

    rows.forEach(row => {

        const nameElement =
            row.querySelector('.font-medium');

        const idElement =
            row.querySelector('.text-xs');

        if (nameElement) {

            const name = nameElement.textContent.trim();

            const id = idElement
                ? idElement.textContent.trim()
                : '';

            employees.push({
                name,
                id
            });

        }

    });

    if (!employees.length) {

        selectedEmployeeNames.innerHTML = `
            <span class="text-xs text-slate-400">
                No employees recorded.
            </span>
        `;

    } else {

        employees.forEach(employee => {

            const tag = document.createElement('span');

            tag.className =
                'inline-flex items-center gap-1.5 shrink-0 px-2.5 py-1.5 rounded-md bg-slate-50 border border-slate-200 text-xs text-slate-600';

            tag.innerHTML = `
                <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[9px] font-semibold">
                    ${employee.name.charAt(0).toUpperCase()}
                </span>

                <span>
                    ${escapeHtml(employee.name)}
                </span>

                <span class="text-[9px] text-slate-400">
                    ${escapeHtml(employee.id)}
                </span>
            `;

            selectedEmployeeNames.appendChild(tag);

        });

    }

    selectedDaySummary.classList.remove('hidden');
}


function escapeHtml(value) {

    const div = document.createElement('div');

    div.textContent = value;

    return div.innerHTML;
}


// Default: show today's attendance
filterAttendance(
    attendanceDate?.value || '<?= $today ?>'
);